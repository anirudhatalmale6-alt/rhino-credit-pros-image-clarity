<?php
/**
 * Plugin Name: Rhino Image Clarity
 * Plugin URI:  https://github.com/anirudhatalmale6-alt/rhino-credit-pros-image-clarity
 * Description: Makes the full-width homepage artwork render at full resolution. Ships the optimised WebP masters with it, so nothing needs re-uploading or re-inserting in the editor.
 * Version:     2.0.0
 * Author:      Anirudha Talmale
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RHINO_ICL_VERSION', '2.0.0' );

/**
 * The artwork this plugin replaces. Matched on the upload's base filename so it
 * keeps working no matter which generated size WordPress inserted.
 */
function rhino_icl_target_slug() {
	return 'Rhino_Credit_Pros_webfinal_3x';
}

/**
 * The bundled masters, widest last.
 *
 * @return array<int,int> width => intrinsic height
 */
function rhino_icl_assets() {
	return array(
		1024 => 2305,
		1536 => 3458,
		2048 => 4610,
		2508 => 5646,
	);
}

function rhino_icl_assets_url() {
	return plugin_dir_url( __FILE__ ) . 'assets/';
}

/**
 * Rewrite one <img> tag so it points at the bundled full-resolution artwork.
 *
 * Kept free of WordPress calls so it can be unit-tested against real markup.
 *
 * @param string $html      Markup containing the <img>.
 * @param string $base_url  URL of the folder holding the .webp files.
 * @param array  $assets    width => height map.
 * @param string $slug      Filename fragment identifying the target image.
 * @return string
 */
function rhino_icl_rewrite_img_html( $html, $base_url, $assets, $slug ) {
	if ( false === strpos( $html, '<img' ) ) {
		return $html;
	}

	// Only touch the artwork we shipped replacements for.
	if ( false === strpos( $html, $slug ) && false === strpos( $html, 'rhino-home-' ) ) {
		return $html;
	}

	// Already rewritten (filter ran twice) - leave it alone.
	if ( false !== strpos( $html, 'data-rhino-icl=' ) ) {
		return $html;
	}

	ksort( $assets );
	$widths = array_keys( $assets );
	$max    = end( $widths );
	$height = $assets[ $max ];

	$srcset = array();
	foreach ( $assets as $w => $h ) {
		$srcset[] = $base_url . 'rhino-home-' . $w . 'w.webp ' . $w . 'w';
	}
	$srcset = implode( ', ', $srcset );
	$src    = $base_url . 'rhino-home-' . $max . 'w.webp';

	$replace = function ( $attr, $value, $tag ) {
		$pattern = '/\s' . preg_quote( $attr, '/' ) . '=("|\').*?\1/i';
		if ( preg_match( $pattern, $tag ) ) {
			return preg_replace( $pattern, ' ' . $attr . '="' . $value . '"', $tag, 1 );
		}
		return preg_replace( '/<img\s/i', '<img ' . $attr . '="' . $value . '" ', $tag, 1 );
	};

	return preg_replace_callback(
		'/<img\b[^>]*>/i',
		function ( $m ) use ( $replace, $src, $srcset, $max, $height, $slug ) {
			$tag = $m[0];

			if ( false === strpos( $tag, $slug ) && false === strpos( $tag, 'rhino-home-' ) ) {
				return $tag;
			}

			$tag = $replace( 'src', $src, $tag );
			$tag = $replace( 'srcset', $srcset, $tag );
			$tag = $replace( 'sizes', '100vw', $tag );
			$tag = $replace( 'width', (string) $max, $tag );
			$tag = $replace( 'height', (string) $height, $tag );

			// Full-width hero: let it paint as early as possible.
			$tag = $replace( 'decoding', 'sync', $tag );
			$tag = $replace( 'loading', 'eager', $tag );
			$tag = $replace( 'fetchpriority', 'high', $tag );

			// Marker so we never process the same tag twice.
			$tag = $replace( 'data-rhino-icl', RHINO_ICL_VERSION, $tag );

			// The theme emits some attributes twice (e.g. fetchpriority). Keep
			// the first of each and drop the rest.
			$seen = array();
			$tag  = preg_replace_callback(
				'/\s([a-zA-Z_:][a-zA-Z0-9_:.-]*)=("|\').*?\2/',
				function ( $a ) use ( &$seen ) {
					$name = strtolower( $a[1] );
					if ( isset( $seen[ $name ] ) ) {
						return '';
					}
					$seen[ $name ] = true;
					return $a[0];
				},
				$tag
			);

			return $tag;
		},
		$html
	);
}

/**
 * Swap the artwork wherever it is rendered.
 */
function rhino_icl_filter_html( $html ) {
	return rhino_icl_rewrite_img_html(
		$html,
		rhino_icl_assets_url(),
		rhino_icl_assets(),
		rhino_icl_target_slug()
	);
}

add_filter(
	'render_block',
	function ( $content, $block ) {
		if ( empty( $block['blockName'] ) || 'core/image' !== $block['blockName'] ) {
			return $content;
		}
		return rhino_icl_filter_html( $content );
	},
	10,
	2
);

// Safety net for anything not rendered through the block pipeline.
add_filter( 'the_content', 'rhino_icl_filter_html', 999 );

/**
 * WordPress caps the LONGEST side of an upload at 2560px. This artwork's
 * longest side is its height (5646px), so the cap was applied vertically and
 * pulled the width down to 1137px. Lifting it keeps full width on future
 * uploads of tall images.
 */
add_filter( 'big_image_size_threshold', '__return_false' );

/**
 * Let srcset advertise candidates wider than the 1600px default ceiling.
 */
add_filter( 'max_srcset_image_width', function () {
	return 4096;
} );

/**
 * Any other full-width image block should declare its real slot width, or the
 * browser will pick a file sized for the thumbnail WordPress inserted.
 */
add_filter(
	'render_block',
	function ( $content, $block ) {
		if ( empty( $block['blockName'] ) || 'core/image' !== $block['blockName'] ) {
			return $content;
		}
		if ( false === strpos( $content, 'alignfull' ) || false === strpos( $content, 'srcset=' ) ) {
			return $content;
		}
		if ( preg_match( '/\ssizes=("|\')(.*?)\1/i', $content ) ) {
			return preg_replace( '/\ssizes=("|\').*?\1/i', ' sizes="100vw"', $content, 1 );
		}
		return preg_replace( '/<img\s/i', '<img sizes="100vw" ', $content, 1 );
	},
	11,
	2
);

/**
 * Purge LiteSpeed Cache on activation so the old markup isn't served from cache.
 */
register_activation_hook( __FILE__, function () {
	do_action( 'litespeed_purge_all' );
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
} );
