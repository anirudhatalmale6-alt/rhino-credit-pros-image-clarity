<?php
/**
 * Plugin Name: Rhino Site Template
 * Plugin URI:  https://github.com/anirudhatalmale6-alt/rhino-credit-pros-image-clarity
 * Description: Adds a "Rhino Standard" page template with a site-wide header and footer, so every page looks uniform. Switch it on or off per page from the Pages list. The front page is left alone.
 * Version:     1.2.0
 * Author:      Anirudha Talmale
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RHINO_TPL_VERSION', '1.2.0' );
define( 'RHINO_TPL_FILE', __FILE__ );
define( 'RHINO_TPL_SLUG', 'rhino-standard' );
define( 'RHINO_TPL_MENU', 'rhino_primary' );

/** Brand palette, sampled from the client's own artwork. */
function rhino_tpl_colors() {
	return array(
		'navy'   => '#001C50',
		'orange' => '#FF5613',
		'ink'    => '#0B1F3B',
		'muted'  => '#5A6B85',
		'rule'   => '#E3E8EF',
	);
}

function rhino_tpl_url( $path = '' ) {
	return plugin_dir_url( RHINO_TPL_FILE ) . ltrim( $path, '/' );
}

/* ------------------------------------------------------------------ *
 * Settings
 * ------------------------------------------------------------------ */

function rhino_tpl_defaults() {
	return array(
		'cta_text'   => 'SCHEDULE A CONSULTATION',
		'cta_url'    => '',
		'facebook'   => '',
		'instagram'  => '',
		'linkedin'   => '',
		'youtube'    => '',
		'tagline'    => 'Smarter Credit. A Brighter Future.',
		'copyright'  => '© ' . gmdate( 'Y' ) . ' Rhino Credit Pros. All rights reserved.',
	);
}

function rhino_tpl_opt( $key ) {
	$o = wp_parse_args( (array) get_option( 'rhino_tpl_options', array() ), rhino_tpl_defaults() );
	return isset( $o[ $key ] ) ? $o[ $key ] : '';
}

add_action( 'admin_menu', function () {
	add_options_page( 'Rhino Template', 'Rhino Template', 'manage_options', 'rhino-tpl', 'rhino_tpl_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'rhino_tpl', 'rhino_tpl_options', array(
		'sanitize_callback' => function ( $in ) {
			$out = array();
			foreach ( rhino_tpl_defaults() as $k => $d ) {
				$v = isset( $in[ $k ] ) ? trim( (string) $in[ $k ] ) : '';
				if ( in_array( $k, array( 'cta_url', 'facebook', 'instagram', 'linkedin', 'youtube' ), true ) ) {
					$out[ $k ] = $v ? esc_url_raw( $v ) : '';
				} else {
					$out[ $k ] = sanitize_text_field( $v );
				}
			}
			return $out;
		},
	) );
} );

function rhino_tpl_settings_page() {
	$fields = array(
		'cta_text'  => array( 'Button text', 'Text on the orange header button.' ),
		'cta_url'   => array( 'Button link', 'Where the orange button goes. Leave blank to hide the button.' ),
		'facebook'  => array( 'Facebook URL', '' ),
		'instagram' => array( 'Instagram URL', '' ),
		'linkedin'  => array( 'LinkedIn URL', '' ),
		'youtube'   => array( 'YouTube URL', '' ),
		'tagline'   => array( 'Footer tagline', '' ),
		'copyright' => array( 'Copyright line', '' ),
	);
	?>
	<div class="wrap">
		<h1>Rhino Template</h1>
		<p>Social icons stay hidden until you paste a link in, so the footer never looks half-finished.<br>
		To edit the menu itself, go to <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Appearance &rsaquo; Menus</a>.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'rhino_tpl' ); ?>
			<table class="form-table" role="presentation"><tbody>
			<?php foreach ( $fields as $key => $meta ) : ?>
				<tr>
					<th scope="row"><label for="rt-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $meta[0] ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="rt-<?php echo esc_attr( $key ); ?>"
							name="rhino_tpl_options[<?php echo esc_attr( $key ); ?>]"
							value="<?php echo esc_attr( rhino_tpl_opt( $key ) ); ?>">
						<?php if ( $meta[1] ) : ?><p class="description"><?php echo esc_html( $meta[1] ); ?></p><?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* ------------------------------------------------------------------ *
 * Register the page template so it appears in the Template dropdown
 * ------------------------------------------------------------------ */

add_filter( 'theme_page_templates', function ( $templates ) {
	$templates[ RHINO_TPL_SLUG ] = 'Rhino Standard';
	return $templates;
} );

add_filter( 'template_include', function ( $template ) {
	if ( is_singular() ) {
		$id = get_queried_object_id();
		// Never render on the front page, even if the meta says so. The
		// homepage is a flat artwork with its own painted header and
		// hand-built hotspot links; a second real header doubles the menu.
		if ( in_array( (int) $id, rhino_tpl_excluded_ids(), true ) ) {
			return $template;
		}
		if ( RHINO_TPL_SLUG === get_page_template_slug( $id ) ) {
			return plugin_dir_path( RHINO_TPL_FILE ) . 'template-rhino-standard.php';
		}
	}
	return $template;
}, 99 );

/* ------------------------------------------------------------------ *
 * Menus
 * ------------------------------------------------------------------ */

add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		RHINO_TPL_MENU => 'Rhino Header & Footer',
	) );
} );

/**
 * Build the default menu once, from the pages that actually exist.
 * Never touches an existing menu.
 */
function rhino_tpl_seed_menu() {
	if ( get_option( 'rhino_tpl_menu_seeded' ) ) {
		return;
	}

	$name = 'Rhino Main Menu';
	$menu = wp_get_nav_menu_object( $name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			return;
		}
	} else {
		$menu_id = $menu->term_id;
	}

	$by_slug = function ( $slug ) {
		$p = get_page_by_path( $slug );
		return $p ? (int) $p->ID : 0;
	};

	$add = function ( $title, $page_id, $parent = 0, $url = '' ) use ( $menu_id ) {
		$args = array(
			'menu-item-title'     => $title,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
		);
		if ( $page_id ) {
			$args['menu-item-object']    = 'page';
			$args['menu-item-object-id'] = $page_id;
			$args['menu-item-type']      = 'post_type';
		} else {
			$args['menu-item-url']  = $url ? $url : home_url( '/' );
			$args['menu-item-type'] = 'custom';
		}
		return wp_update_nav_menu_item( $menu_id, 0, $args );
	};

	$add( 'Home', 0, 0, home_url( '/' ) );

	$funding = $add( 'Credit / Funding', 0, 0, home_url( '/' ) );
	if ( ! is_wp_error( $funding ) ) {
		$personal = $by_slug( 'personal-funding' );
		$business = $by_slug( 'business-funding-page' );
		if ( $personal ) {
			$add( 'Personal Funding', $personal, $funding );
		}
		if ( $business ) {
			$add( 'Business Funding', $business, $funding );
		}
	}

	foreach ( array(
		'All Rhino Credit Services' => 'all-rhino-credit-services',
		'About'                     => 'about-us',
		'Contact'                   => 'contact-us',
	) as $title => $slug ) {
		$id = $by_slug( $slug );
		if ( $id ) {
			$add( $title, $id );
		}
	}

	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations[ RHINO_TPL_MENU ] ) ) {
		$locations[ RHINO_TPL_MENU ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	update_option( 'rhino_tpl_menu_seeded', 1 );
}

/* ------------------------------------------------------------------ *
 * Header / footer markup
 * ------------------------------------------------------------------ */

function rhino_tpl_logo( $class ) {
	printf(
		'<a class="%1$s" href="%2$s" rel="home"><img src="%3$s" srcset="%4$s 280w, %3$s 560w" sizes="220px" width="560" height="389" alt="%5$s"></a>',
		esc_attr( $class ),
		esc_url( home_url( '/' ) ),
		esc_url( rhino_tpl_url( 'assets/logo-560.webp' ) ),
		esc_url( rhino_tpl_url( 'assets/logo-280.webp' ) ),
		esc_attr( get_bloginfo( 'name' ) )
	);
}

function rhino_tpl_nav( $extra_class = '' ) {
	$args = array(
		'theme_location' => RHINO_TPL_MENU,
		'container'      => false,
		'menu_class'     => 'rhino-menu ' . $extra_class,
		'depth'          => 2,
		'fallback_cb'    => 'rhino_tpl_nav_fallback',
	);
	wp_nav_menu( $args );
}

function rhino_tpl_nav_fallback() {
	echo '<ul class="rhino-menu"><li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li></ul>';
}

function rhino_tpl_socials() {
	$icons = array(
		'facebook'  => 'M13.5 9H16l.5-3h-3V4.2c0-.9.3-1.4 1.5-1.4H16V.1C15.7.1 14.8 0 13.8 0 11.6 0 10 1.3 10 3.9V6H7.5v3H10v8h3.5V9z',
		'instagram' => 'M8.5 1.5c2.3 0 2.5 0 3.4.1.8 0 1.3.2 1.6.3.4.2.7.4 1 .7.3.3.5.6.7 1 .1.3.3.8.3 1.6 0 .9.1 1.1.1 3.4s0 2.5-.1 3.4c0 .8-.2 1.3-.3 1.6-.2.4-.4.7-.7 1-.3.3-.6.5-1 .7-.3.1-.8.3-1.6.3-.9 0-1.1.1-3.4.1s-2.5 0-3.4-.1c-.8 0-1.3-.2-1.6-.3-.4-.2-.7-.4-1-.7-.3-.3-.5-.6-.7-1-.1-.3-.3-.8-.3-1.6 0-.9-.1-1.1-.1-3.4s0-2.5.1-3.4c0-.8.2-1.3.3-1.6.2-.4.4-.7.7-1 .3-.3.6-.5 1-.7.3-.1.8-.3 1.6-.3.9 0 1.1-.1 3.4-.1zm0 4A3.5 3.5 0 1 0 12 9a3.5 3.5 0 0 0-3.5-3.5zm0 5.8A2.3 2.3 0 1 1 10.8 9a2.3 2.3 0 0 1-2.3 2.3zM13 5.2a.8.8 0 1 1-.8-.8.8.8 0 0 1 .8.8z',
		'linkedin'  => 'M3.6 16H.6V6.1h3V16zM2.1 4.8A1.7 1.7 0 1 1 3.8 3a1.7 1.7 0 0 1-1.7 1.8zM16 16h-3v-4.8c0-1.2 0-2.6-1.6-2.6s-1.8 1.2-1.8 2.5V16h-3V6.1h2.9v1.3h.1a3.2 3.2 0 0 1 2.8-1.5c3 0 3.6 2 3.6 4.5V16z',
		'youtube'   => 'M17.6 4.6a2.2 2.2 0 0 0-1.6-1.6C14.6 2.6 9 2.6 9 2.6s-5.6 0-7 .4A2.2 2.2 0 0 0 .4 4.6 23 23 0 0 0 0 9a23 23 0 0 0 .4 4.4A2.2 2.2 0 0 0 2 15c1.4.4 7 .4 7 .4s5.6 0 7-.4a2.2 2.2 0 0 0 1.6-1.6A23 23 0 0 0 18 9a23 23 0 0 0-.4-4.4zM7.2 11.7V6.3L11.9 9z',
	);
	$out = '';
	foreach ( $icons as $key => $path ) {
		$url = rhino_tpl_opt( $key );
		if ( ! $url ) {
			continue;
		}
		$out .= sprintf(
			'<a class="rhino-social" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">'
			. '<svg viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false"><path d="%s"/></svg></a>',
			esc_url( $url ),
			esc_attr( ucfirst( $key ) ),
			esc_attr( $path )
		);
	}
	return $out ? '<div class="rhino-socials">' . $out . '</div>' : '';
}

function rhino_tpl_render_header() {
	$cta_url  = rhino_tpl_opt( 'cta_url' );
	$cta_text = rhino_tpl_opt( 'cta_text' );
	?>
	<header class="rhino-header">
		<div class="rhino-shell">
			<?php rhino_tpl_logo( 'rhino-logo' ); ?>

			<button class="rhino-burger" aria-expanded="false" aria-controls="rhino-nav" aria-label="Menu">
				<span></span><span></span><span></span>
			</button>

			<nav class="rhino-nav" id="rhino-nav" aria-label="Main">
				<?php rhino_tpl_nav(); ?>
				<?php if ( $cta_url && $cta_text ) : ?>
					<a class="rhino-cta rhino-cta--inline" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_text ); ?></a>
				<?php endif; ?>
			</nav>

			<?php if ( $cta_url && $cta_text ) : ?>
				<a class="rhino-cta rhino-cta--bar" href="<?php echo esc_url( $cta_url ); ?>">
					<?php echo esc_html( $cta_text ); ?><span aria-hidden="true">&rsaquo;</span>
				</a>
			<?php endif; ?>
		</div>
	</header>
	<?php
}

function rhino_tpl_render_footer() {
	?>
	<footer class="rhino-footer">
		<div class="rhino-shell rhino-footer__top">
			<?php rhino_tpl_logo( 'rhino-logo rhino-logo--footer' ); ?>
			<nav class="rhino-footer__nav" aria-label="Footer">
				<?php rhino_tpl_nav( 'rhino-menu--footer' ); ?>
			</nav>
			<?php echo rhino_tpl_socials(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<div class="rhino-shell rhino-footer__legal">
			<span><?php echo esc_html( rhino_tpl_opt( 'copyright' ) ); ?></span>
			<span class="rhino-tagline"><?php echo esc_html( rhino_tpl_opt( 'tagline' ) ); ?></span>
		</div>
	</footer>
	<?php
}

/* ------------------------------------------------------------------ *
 * Styles
 * ------------------------------------------------------------------ */

add_action( 'wp_enqueue_scripts', function () {
	if ( ! rhino_tpl_is_active() ) {
		return;
	}
	wp_register_style( 'rhino-tpl', false, array(), RHINO_TPL_VERSION );
	wp_enqueue_style( 'rhino-tpl' );
	wp_add_inline_style( 'rhino-tpl', rhino_tpl_css() );
} );

function rhino_tpl_is_active() {
	if ( ! is_singular() ) {
		return false;
	}
	$id = get_queried_object_id();
	if ( in_array( (int) $id, rhino_tpl_excluded_ids(), true ) ) {
		return false;
	}
	return RHINO_TPL_SLUG === get_page_template_slug( $id );
}

function rhino_tpl_css() {
	$c = rhino_tpl_colors();
	return '
:root{--rhino-navy:' . $c['navy'] . ';--rhino-orange:' . $c['orange'] . ';--rhino-ink:' . $c['ink'] . ';--rhino-muted:' . $c['muted'] . ';--rhino-rule:' . $c['rule'] . ';}
.rhino-page *,.rhino-page *::before,.rhino-page *::after{box-sizing:border-box;}
.rhino-page{margin:0;color:var(--rhino-ink);background:#fff;
 font-family:Hind,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
 -webkit-font-smoothing:antialiased;}
.rhino-shell{max-width:1280px;margin:0 auto;padding:0 24px;display:flex;align-items:center;gap:24px;}

/* header */
.rhino-header{border-bottom:1px solid var(--rhino-rule);background:#fff;position:sticky;top:0;z-index:100;}
body.admin-bar .rhino-header{top:32px;}
.rhino-header .rhino-shell{min-height:108px;}
.rhino-logo{display:block;flex:0 0 auto;line-height:0;}
.rhino-logo img{display:block;width:auto;height:76px;}
.rhino-nav{margin-left:auto;display:flex;align-items:center;gap:28px;}
.rhino-menu{list-style:none;margin:0;padding:0;display:flex;align-items:center;gap:30px;}
.rhino-menu li{position:relative;}
.rhino-menu a{color:var(--rhino-navy);text-decoration:none;font-size:15px;font-weight:600;
 letter-spacing:.01em;white-space:nowrap;padding:8px 0;display:inline-block;}
.rhino-menu a:hover,.rhino-menu a:focus-visible{color:var(--rhino-orange);}
.rhino-menu .current-menu-item>a,.rhino-menu .current_page_item>a{color:var(--rhino-orange);}

/* dropdown */
.rhino-menu .sub-menu{list-style:none;margin:0;padding:8px 0;position:absolute;left:-16px;top:100%;
 min-width:232px;background:#fff;border:1px solid var(--rhino-rule);border-radius:10px;
 box-shadow:0 14px 34px rgba(0,28,80,.13);opacity:0;visibility:hidden;transform:translateY(6px);
 transition:opacity .16s ease,transform .16s ease,visibility .16s;z-index:20;}
.rhino-menu li:hover>.sub-menu,.rhino-menu li:focus-within>.sub-menu{opacity:1;visibility:visible;transform:translateY(0);}
.rhino-menu .sub-menu a{display:block;padding:9px 18px;font-weight:500;}
.rhino-menu .menu-item-has-children>a::after{content:"";display:inline-block;width:6px;height:6px;margin-left:8px;
 border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:translateY(-2px) rotate(45deg);}

/* cta */
.rhino-cta{display:inline-flex;align-items:center;gap:10px;background:var(--rhino-orange);color:#fff;
 text-decoration:none;font-weight:700;font-size:14px;letter-spacing:.04em;text-transform:uppercase;
 padding:15px 26px;border-radius:6px;white-space:nowrap;
 box-shadow:0 2px 0 rgba(0,0,0,.08);transition:filter .15s ease,transform .15s ease;}
.rhino-cta:hover{filter:brightness(1.07);transform:translateY(-1px);}
.rhino-cta--inline{display:none;}

/* burger */
.rhino-burger{display:none;margin-left:auto;width:44px;height:44px;border:0;background:transparent;
 cursor:pointer;padding:10px;}
.rhino-burger span{display:block;height:2px;background:var(--rhino-navy);border-radius:2px;
 transition:transform .2s ease,opacity .2s ease;}
.rhino-burger span+span{margin-top:5px;}
.rhino-burger[aria-expanded="true"] span:nth-child(1){transform:translateY(7px) rotate(45deg);}
.rhino-burger[aria-expanded="true"] span:nth-child(2){opacity:0;}
.rhino-burger[aria-expanded="true"] span:nth-child(3){transform:translateY(-7px) rotate(-45deg);}

/* body */
.rhino-main{min-height:46vh;}
.rhino-main>.rhino-shell{display:block;padding-top:48px;padding-bottom:64px;}
.rhino-main .alignfull{margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);max-width:100vw;width:100vw;}
.rhino-main img{max-width:100%;height:auto;}

/* footer */
.rhino-footer{border-top:1px solid var(--rhino-rule);background:#fff;margin-top:auto;}
.rhino-footer__top{padding-top:30px;padding-bottom:26px;gap:32px;flex-wrap:wrap;}
.rhino-logo--footer img{height:96px;}
.rhino-footer__nav{margin:0 auto;}
.rhino-menu--footer{gap:28px;flex-wrap:wrap;justify-content:center;}
.rhino-menu--footer a{font-weight:500;}
.rhino-menu--footer .sub-menu{display:none;}
.rhino-menu--footer .menu-item-has-children>a::after{display:none;}
.rhino-socials{display:flex;gap:10px;margin-left:auto;}
.rhino-social{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;
 border-radius:50%;background:var(--rhino-navy);color:#fff;transition:background .15s ease;}
.rhino-social:hover{background:var(--rhino-orange);}
.rhino-social svg{fill:currentColor;}
.rhino-footer__legal{border-top:1px solid var(--rhino-rule);padding-top:18px;padding-bottom:26px;
 justify-content:space-between;font-size:14px;color:var(--rhino-muted);flex-wrap:wrap;gap:8px;}
.rhino-tagline{color:var(--rhino-navy);font-weight:600;}

/* wrapper */
.rhino-page-wrap{display:flex;flex-direction:column;min-height:100vh;}

@media (max-width:980px){
 .rhino-header .rhino-shell{min-height:78px;gap:12px;}
 .rhino-logo img{height:50px;}
 .rhino-burger{display:block;}
 .rhino-cta--bar{display:none;}
 .rhino-cta--inline{display:inline-flex;align-self:flex-start;}
 .rhino-nav{position:absolute;left:0;right:0;top:100%;background:#fff;border-bottom:1px solid var(--rhino-rule);
  box-shadow:0 18px 30px rgba(0,28,80,.10);flex-direction:column;align-items:stretch;gap:0;
  padding:8px 24px 22px;margin:0;display:none;}
 .rhino-nav.is-open{display:flex;}
 /* stacked list applies to the HEADER nav only - the footer stays a compact row */
 .rhino-nav .rhino-menu{flex-direction:column;align-items:stretch;gap:0;}
 .rhino-nav .rhino-menu>li{border-bottom:1px solid var(--rhino-rule);}
 .rhino-nav .rhino-menu a{display:block;padding:14px 0;font-size:16px;}
 .rhino-nav .rhino-menu .sub-menu{position:static;opacity:1;visibility:visible;transform:none;border:0;
  box-shadow:none;padding:0 0 8px 16px;min-width:0;}
 .rhino-nav .rhino-menu .sub-menu a{padding:10px 0;}
 .rhino-nav .rhino-menu .menu-item-has-children>a::after{float:right;margin-top:8px;}
 .rhino-footer__top{flex-direction:column;text-align:center;gap:20px;}
 .rhino-footer__nav,.rhino-socials{margin:0;}
 .rhino-menu--footer{gap:10px 22px;}
 .rhino-menu--footer a{font-size:15px;padding:4px 0;}
 .rhino-logo--footer img{height:72px;}
 .rhino-footer__legal{justify-content:center;text-align:center;}
}
@media (max-width:520px){
 .rhino-shell{padding:0 16px;}
 .rhino-logo img{height:44px;}
}
@media (prefers-reduced-motion:reduce){
 .rhino-cta,.rhino-menu .sub-menu,.rhino-burger span{transition:none;}
}
';
}

function rhino_tpl_js() {
	return '(function(){var b=document.querySelector(".rhino-burger"),n=document.getElementById("rhino-nav");'
		. 'if(!b||!n)return;b.addEventListener("click",function(){var o=b.getAttribute("aria-expanded")==="true";'
		. 'b.setAttribute("aria-expanded",String(!o));n.classList.toggle("is-open",!o);});'
		. 'document.addEventListener("keydown",function(e){if(e.key==="Escape"&&n.classList.contains("is-open")){'
		. 'n.classList.remove("is-open");b.setAttribute("aria-expanded","false");b.focus();}});})();';
}

/* ------------------------------------------------------------------ *
 * Activation
 * ------------------------------------------------------------------ */

register_activation_hook( __FILE__, function () {
	rhino_tpl_seed_menu();
	do_action( 'litespeed_purge_all' );
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
} );

// Also seed on first admin load, in case activation ran before pages existed.
add_action( 'admin_init', 'rhino_tpl_seed_menu' );

/* ------------------------------------------------------------------ *
 * Bulk apply: switch every page onto the template
 * ------------------------------------------------------------------ */

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'settings_page_rhino-tpl', 'plugins' ), true ) ) {
		return;
	}
	$remaining = rhino_tpl_pages_not_on_template();
	if ( ! $remaining ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'options-general.php?page=rhino-tpl&rhino_apply_all=1' ), 'rhino_apply_all' );
	printf(
		'<div class="notice notice-info"><p>%d page%s not using the Rhino Standard template. '
		. '<a class="button button-primary" href="%s">Apply it to all pages</a></p></div>',
		count( $remaining ),
		count( $remaining ) === 1 ? ' is' : 's are',
		esc_url( $url )
	);
} );

/**
 * Pages that bulk-apply is allowed to touch.
 *
 * The front page is excluded: on this site the homepage is a single flat
 * artwork with its own painted header and hand-built hotspot links, so adding
 * a second real header on top of it produces two menus.
 */
function rhino_tpl_excluded_ids() {
	$ids = array();
	$front = (int) get_option( 'page_on_front' );
	if ( $front ) {
		$ids[] = $front;
	}
	$posts = (int) get_option( 'page_for_posts' );
	if ( $posts ) {
		$ids[] = $posts;
	}
	return apply_filters( 'rhino_tpl_excluded_ids', $ids );
}

function rhino_tpl_pages_not_on_template() {
	$ids = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	$skip = rhino_tpl_excluded_ids();
	$out  = array();
	foreach ( $ids as $id ) {
		if ( in_array( (int) $id, $skip, true ) ) {
			continue;
		}
		if ( RHINO_TPL_SLUG !== get_page_template_slug( $id ) ) {
			$out[] = $id;
		}
	}
	return $out;
}

add_action( 'admin_init', function () {
	if ( empty( $_GET['rhino_apply_all'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'rhino_apply_all' );
	$n = 0;
	foreach ( rhino_tpl_pages_not_on_template() as $id ) {
		update_post_meta( $id, '_wp_page_template', RHINO_TPL_SLUG );
		$n++;
	}
	set_transient( 'rhino_tpl_applied', $n, 60 );
	wp_safe_redirect( admin_url( 'options-general.php?page=rhino-tpl' ) );
	exit;
} );

add_action( 'admin_notices', function () {
	$n = get_transient( 'rhino_tpl_applied' );
	if ( false === $n ) {
		return;
	}
	delete_transient( 'rhino_tpl_applied' );
	printf( '<div class="notice notice-success is-dismissible"><p>Rhino Standard applied to %d page%s.</p></div>',
		(int) $n, 1 === (int) $n ? '' : 's' );
} );

/* ------------------------------------------------------------------ *
 * Per-page control from the Pages list.
 *
 * Block themes do not offer classic page templates in the editor's template
 * picker, so this is where per-page on/off actually lives.
 * ------------------------------------------------------------------ */

add_filter( 'manage_pages_columns', function ( $cols ) {
	$cols['rhino_tpl'] = 'Rhino Template';
	return $cols;
} );

add_action( 'manage_pages_custom_column', function ( $col, $post_id ) {
	if ( 'rhino_tpl' !== $col ) {
		return;
	}
	if ( in_array( (int) $post_id, rhino_tpl_excluded_ids(), true ) ) {
		echo '<span style="color:#787c82;">Front page &mdash; skipped</span>';
		return;
	}
	$on  = RHINO_TPL_SLUG === get_page_template_slug( $post_id );
	$url = wp_nonce_url(
		admin_url( 'edit.php?post_type=page&rhino_toggle=' . (int) $post_id ),
		'rhino_toggle_' . (int) $post_id
	);
	printf(
		'<a href="%s" class="button button-small">%s</a>',
		esc_url( $url ),
		$on ? 'On &mdash; turn off' : 'Off &mdash; turn on'
	);
}, 10, 2 );

add_action( 'admin_init', function () {
	if ( empty( $_GET['rhino_toggle'] ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$id = (int) $_GET['rhino_toggle'];
	check_admin_referer( 'rhino_toggle_' . $id );
	if ( in_array( $id, rhino_tpl_excluded_ids(), true ) ) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
		exit;
	}
	// Mark it handled either way, so the first-save rule never reverses a
	// decision made here.
	update_post_meta( $id, '_rhino_tpl_seeded', 1 );
	if ( RHINO_TPL_SLUG === get_page_template_slug( $id ) ) {
		delete_post_meta( $id, '_wp_page_template' );
	} else {
		update_post_meta( $id, '_wp_page_template', RHINO_TPL_SLUG );
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
	exit;
} );

/**
 * New pages get the template automatically.
 *
 * Without this, every page you create comes out with no header or footer until
 * you remember to apply it. Only ever sets the template when the page does not
 * already have one, so an explicit choice is never overridden.
 */
add_action( 'save_post_page', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( ! $post || 'trash' === $post->post_status ) {
		return;
	}
	if ( in_array( (int) $post_id, rhino_tpl_excluded_ids(), true ) ) {
		return;
	}

	// Act exactly once per page, on its first save. After that the page is the
	// author's to control - switch it off from the Pages list and it stays off.
	// This has to override rather than defer, because a block theme assigns its
	// own default template on creation, so the slug is rarely empty by now.
	if ( get_post_meta( $post_id, '_rhino_tpl_seeded', true ) ) {
		return;
	}
	update_post_meta( $post_id, '_rhino_tpl_seeded', 1 );
	update_post_meta( $post_id, '_wp_page_template', RHINO_TPL_SLUG );
}, 10, 2 );

/** Bulk actions, for doing several at once. */
add_filter( 'bulk_actions-edit-page', function ( $actions ) {
	$actions['rhino_tpl_on']  = 'Use Rhino Standard template';
	$actions['rhino_tpl_off'] = 'Remove Rhino Standard template';
	return $actions;
} );

add_filter( 'handle_bulk_actions-edit-page', function ( $redirect, $action, $ids ) {
	if ( 'rhino_tpl_on' !== $action && 'rhino_tpl_off' !== $action ) {
		return $redirect;
	}
	$skip = rhino_tpl_excluded_ids();
	$n    = 0;
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( in_array( $id, $skip, true ) || ! current_user_can( 'edit_post', $id ) ) {
			continue;
		}
		update_post_meta( $id, '_rhino_tpl_seeded', 1 );
		if ( 'rhino_tpl_on' === $action ) {
			update_post_meta( $id, '_wp_page_template', RHINO_TPL_SLUG );
		} else {
			delete_post_meta( $id, '_wp_page_template' );
		}
		$n++;
	}
	return add_query_arg( 'rhino_bulk', $n, $redirect );
}, 10, 3 );

add_action( 'admin_notices', function () {
	if ( ! isset( $_GET['rhino_bulk'] ) ) {
		return;
	}
	printf( '<div class="notice notice-success is-dismissible"><p>Updated %d page%s.</p></div>',
		(int) $_GET['rhino_bulk'], 1 === (int) $_GET['rhino_bulk'] ? '' : 's' );
} );
