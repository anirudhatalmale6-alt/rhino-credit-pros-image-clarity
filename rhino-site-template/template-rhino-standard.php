<?php
/**
 * Rhino Standard page template.
 *
 * Deliberately standalone: every page on this site uses Elementor's blank
 * canvas, so there is no theme header or footer to inherit. This renders the
 * whole document itself, which is also what keeps every page uniform.
 *
 * @package RhinoSiteTemplate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'rhino-page' ); ?>>
<?php wp_body_open(); ?>

<div class="rhino-page-wrap">

	<?php rhino_tpl_render_header(); ?>

	<main class="rhino-main" id="content">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<div class="rhino-shell entry-content">
				<?php the_content(); ?>
			</div>
			<?php
		endwhile;
		?>
	</main>

	<?php rhino_tpl_render_footer(); ?>

</div>

<script><?php echo rhino_tpl_js(); // phpcs:ignore WordPress.Security.EscapeOutput ?></script>
<?php wp_footer(); ?>
</body>
</html>
