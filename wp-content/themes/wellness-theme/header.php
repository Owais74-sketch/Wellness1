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
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="theme-color" content="#0f3b4c">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<script>
		(function() {
			var mode = localStorage.getItem('wellness_theme_mode') || 'auto';
			if (mode === 'dark' || (mode === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
				document.documentElement.setAttribute('data-theme', 'dark');
			} else if (mode === 'light') {
				document.documentElement.setAttribute('data-theme', 'light');
			}
		})();
	</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary">
	<?php esc_html_e( 'Skip to content', 'wellness-theme' ); ?>
</a>

<div id="page" class="site-wrapper">

	<!-- =====================================================================
	     Site Header (FR-2: Logo, Navigation, Search, Lang Switcher, Quick Exit)
	     ===================================================================== -->
	<header id="masthead" class="site-header" role="banner">

		<!-- 1. Top Utility & Safety Bar -->
		<div class="top-utility-bar">
			<div class="container">
				<div class="utility-notice">
					<span class="pulse-dot" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Confidential & Private • For emergencies, call 911 / 112 immediately', 'wellness-theme' ); ?></span>
				</div>

				<div class="utility-actions">
					<!-- Auto / Light / Dark Theme Mode Toggle -->
					<?php wellness_render_theme_toggle(); ?>

					<!-- Language Switcher (FR-6) -->
					<div class="lang-switcher" aria-label="<?php esc_attr_e( 'Language Switcher', 'wellness-theme' ); ?>" role="navigation">
						<a href="?lang=en" class="lang-btn active" title="<?php esc_attr_e( 'English (Default)', 'wellness-theme' ); ?>">EN</a>
						<a href="?lang=ur" class="lang-btn" title="<?php esc_attr_e( 'Urdu (اردو)', 'wellness-theme' ); ?>">اردو</a>
						<a href="?lang=ru" class="lang-btn" title="<?php esc_attr_e( 'Roman Urdu', 'wellness-theme' ); ?>">RU</a>
					</div>

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
					<input type="search" id="header-search-field" placeholder="<?php esc_attr_e( 'Search questions, symptoms, articles...', 'wellness-theme' ); ?>" value="<?php echo get_search_query(); ?>" name="s" autocomplete="off" />
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
						<span class="brand-tagline"><?php esc_html_e( 'Confidential & Discreet', 'wellness-theme' ); ?></span>
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

					<!-- Language Selection in Drawer -->
					<div class="mobile-lang-row">
						<span class="text-xs text-muted" style="display:block; margin-bottom: 6px; font-weight:600;"><?php esc_html_e( 'Choose Language:', 'wellness-theme' ); ?></span>
						<div class="lang-switcher">
							<a href="?lang=en" class="lang-btn active">English</a>
							<a href="?lang=ur" class="lang-btn">اردو</a>
							<a href="?lang=ru" class="lang-btn">Roman Urdu</a>
						</div>
					</div>
				</div>
			</div>
		</div>

	</header><!-- #masthead -->

	<div id="content" class="site-content">
