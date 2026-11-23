<?php
/**
 * The header for our theme
 * Displays all of the <head> section and everything up until <div id="content">
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$default_theme_mode = get_theme_mod( 'wellness_default_color_mode', 'dark' );
if ( ! in_array( $default_theme_mode, array( 'dark', 'light' ), true ) ) {
	$default_theme_mode = 'dark';
}
?><!doctype html>
<html <?php language_attributes(); ?> data-default-theme="<?php echo esc_attr( $default_theme_mode ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="theme-color" content="">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<script>
		(function() {
			var defaultMode = document.documentElement.getAttribute('data-default-theme') === 'light' ? 'light' : 'dark';
			var mode = defaultMode;
			try {
				var savedMode = localStorage.getItem('wc-theme');
				if (savedMode === 'light' || savedMode === 'dark') {
					mode = savedMode;
				}
			} catch (e) {}

			document.documentElement.setAttribute('data-theme', mode);
		})();
	</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?><?php if ( get_theme_mod( 'wellness_show_quick_exit', false ) ) echo ' data-quick-exit'; ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary">
	<?php esc_html_e( 'Skip to content', 'wellness-theme' ); ?>
</a>

<div id="page" class="site-wrapper">

	<!-- =====================================================================
	     Site Header (FR-2: Logo, Navigation, Search, Quick Exit)
	     ===================================================================== -->
	<header id="masthead" class="site-header" role="banner">

		<!-- 1. Top Utility & Safety Bar -->
		<div class="top-utility-bar">
			<div class="container">
				<div class="utility-notice">
					<span class="pulse-dot" aria-hidden="true"></span>
					<?php
					$topbar_default = wellness_consultations_enabled()
						? __( 'Demo Consultation Booking • Worldwide Time Zone Support', 'wellness-theme' )
						: __( 'Practical • Evidence-informed • Everyday Wellness', 'wellness-theme' );
					$topbar_text    = get_theme_mod( 'wellness_topbar_text', $topbar_default );
					?>
					<span><?php echo esc_html( $topbar_text ); ?></span>
				</div>

				<div class="utility-actions">
					<!-- Light / Dark Theme Mode Toggle -->
					<?php wellness_render_theme_toggle(); ?>

					<!-- Quick Exit Button (Desktop & Mobile header) -->
					<?php wellness_render_quick_exit_button(); ?>
				</div>
			</div>
		</div>

		<!-- 2. Main Navigation Bar -->
		<div class="main-header-bar">
			<div class="container">

				<!-- Branding -->
				<?php wellness_site_branding(); ?>

				<!-- Desktop Navigation Menu -->
				<nav id="site-navigation" class="nav-desktop" aria-label="<?php esc_attr_e( 'Primary Navigation', 'wellness-theme' ); ?>" role="navigation">
					<?php
					if ( has_nav_menu( 'primary' ) ) {
						wp_nav_menu( array(
							'theme_location' => 'primary',
							'menu_class'     => 'nav-menu',
							'container'      => false,
							'fallback_cb'    => 'wellness_fallback_primary_menu',
						) );
					} else {
						wellness_fallback_primary_menu();
					}
					?>
				</nav>

				<!-- Header Actions: Search Toggle & Mobile Menu Trigger -->
				<div class="header-actions">
					<!-- Search Button Toggle -->
					<button type="button" id="search-toggle-btn" class="search-toggle-btn" aria-expanded="false" aria-controls="search-modal" aria-label="<?php esc_attr_e( 'Toggle search bar', 'wellness-theme' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<circle cx="11" cy="11" r="8"></circle>
							<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
						</svg>
					</button>

					<!-- Mobile Menu Button (Hamburger) -->
					<button type="button" id="mobile-menu-toggle" class="mobile-menu-toggle" aria-expanded="false" aria-controls="mobile-nav-drawer" aria-label="<?php esc_attr_e( 'Open mobile navigation menu', 'wellness-theme' ); ?>">
						<span aria-hidden="true"></span>
						<span aria-hidden="true"></span>
						<span aria-hidden="true"></span>
					</button>
				</div>

			</div>
		</div>

		<!-- 3. Accessible Search Dropdown / Modal -->
		<div id="search-modal" class="search-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Site Search', 'wellness-theme' ); ?>">
			<div class="container container-narrow">
				<form role="search" method="get" class="search-form-wrap" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted" aria-hidden="true">
						<circle cx="11" cy="11" r="8"></circle>
						<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
					</svg>
					<label for="header-search-field" class="screen-reader-text"><?php esc_html_e( 'Search topics, articles, and questions', 'wellness-theme' ); ?></label>
					<input type="search" id="header-search-field" placeholder="<?php echo esc_attr( get_theme_mod( 'wellness_header_search_placeholder', __( 'Search questions, symptoms, articles...', 'wellness-theme' ) ) ); ?>" value="<?php echo get_search_query(); ?>" name="s" autocomplete="off" />
					<button type="submit"><?php esc_html_e( 'Search', 'wellness-theme' ); ?></button>
				</form>
			</div>
		</div>

		<!-- 4. Mobile Navigation Drawer (Off-Canvas) -->
		<div id="mobile-nav-drawer" class="mobile-nav-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Mobile Menu', 'wellness-theme' ); ?>">
			<div class="mobile-nav-panel">
				<div class="mobile-nav-header">
					<div class="brand-text">
						<span class="brand-title"><?php bloginfo( 'name' ); ?></span>
						<span class="brand-tagline"><?php echo esc_html( get_theme_mod( 'wellness_mobile_tagline', __( 'Everyday Wellness', 'wellness-theme' ) ) ); ?></span>
					</div>
					<button type="button" id="mobile-nav-close" class="mobile-nav-close" aria-label="<?php esc_attr_e( 'Close navigation menu', 'wellness-theme' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</button>
				</div>

				<!-- Mobile Navigation Links -->
				<nav class="mobile-nav" role="navigation" aria-label="<?php esc_attr_e( 'Mobile Navigation', 'wellness-theme' ); ?>">
					<?php
					if ( has_nav_menu( 'primary' ) ) {
						wp_nav_menu( array(
							'theme_location' => 'primary',
							'menu_class'     => 'mobile-nav-menu',
							'container'      => false,
							'fallback_cb'    => 'wellness_fallback_mobile_menu',
						) );
					} else {
						wellness_fallback_mobile_menu();
					}
					?>
				</nav>

				<!-- Mobile Drawer Footer Actions -->
				<div class="mobile-nav-footer">
					<!-- Quick Exit in Drawer -->
					<?php wellness_render_quick_exit_button(); ?>

					<!-- Theme Display Mode in Drawer -->
					<div class="mobile-theme-row" style="margin-bottom: 0.75rem;">
						<span class="text-xs text-muted" style="display:block; margin-bottom: 6px; font-weight:600;"><?php esc_html_e( 'Theme Mode:', 'wellness-theme' ); ?></span>
						<?php wellness_render_theme_toggle(); ?>
					</div>

				</div>
			</div>
		</div>

	</header><!-- #masthead -->

	<?php
	$_demo_on   = get_theme_mod( 'wellness_demo_notice_on', true );
	$_demo_text = get_theme_mod( 'wellness_demo_notice_text', __( 'Portfolio demo — content and payments are for demonstration only. No real consultations occur.', 'wellness-theme' ) );
	if ( $_demo_on && $_demo_text ) :
	?>
	<div class="demo-notice-bar" role="status">
		<span><?php echo esc_html( $_demo_text ); ?></span>
	</div>
	<?php endif; ?>

	<div id="content" class="site-content">
