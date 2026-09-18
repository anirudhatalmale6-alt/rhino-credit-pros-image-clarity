<?php
/**
 * Plugin Name: Rhino Image Clarity
 * Description: Keeps full-width artwork sharp: stops WordPress shrinking tall uploads, and tells the browser how wide the image really is.
 * Version:     1.0.0
 * Author:      Anirudha Talmale
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. WordPress caps the LONGEST side of an upload at 2560px and serves that
 *    "-scaled" copy instead of the original. The homepage artwork is 2508x5646,
 *    so the cap applied to its HEIGHT and dragged the WIDTH down to 1137px.
 *    Removing the cap keeps the full 2508px of horizontal detail.
 */
add_filter( 'big_image_size_threshold', '__return_false' );

/**
 * 2. A "alignfull" image spans the whole viewport, but WordPress writes a
 *    sizes="" attribute based on the *file* it inserted (455px), so the browser
 *    downloads a 455px file and stretches it across ~1440px. Declaring 100vw
 *    makes the browser pick the largest candidate instead.
 */
add_filter(
	'render_block',
	function ( $content, $block ) {
		if ( empty( $block['blockName'] ) || 'core/image' !== $block['blockName'] ) {
			return $content;
		}
		if ( false === strpos( $content, 'alignfull' ) ) {
			return $content;
		}
		if ( false === strpos( $content, 'srcset=' ) ) {
			return $content;
		}

		if ( preg_match( '/\ssizes=("|\')(.*?)\1/i', $content ) ) {
			$content = preg_replace( '/\ssizes=("|\').*?\1/i', ' sizes="100vw"', $content, 1 );
		} else {
			$content = preg_replace( '/<img\s/i', '<img sizes="100vw" ', $content, 1 );
		}

		return $content;
	},
	10,
	2
);

/**
 * 3. Belt and braces: if the image is ever re-inserted at a smaller registered
 *    size, still advertise the full-size file as a srcset candidate.
 */
add_filter( 'max_srcset_image_width', function () {
	return 4096;
} );
