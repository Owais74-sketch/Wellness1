<?php
/**
 * Custom template tags for this theme
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output Site Branding (Custom Logo or default SVG Emblem).
 * Uses get_bloginfo('name') dynamically.
 */
function wellness_site_branding() {
	if ( has_custom_logo() ) {
		the_custom_logo();
	} else {
		$site_title = get_bloginfo( 'name' );
		$site_desc  = get_bloginfo( 'description' );
		if ( empty( $site_desc ) ) {
			$site_desc = esc_html__( 'Everyday Wellness', 'wellness-theme' );
		}
		?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-branding" rel="home">
			<div class="brand-emblem" aria-hidden="true">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
					<path d="M9 12l2 2 4-4"/>
				</svg>
			</div>
			<div class="brand-text">
				<span class="brand-title"><?php echo esc_html( $site_title ); ?></span>
				<span class="brand-tagline"><?php echo esc_html( $site_desc ); ?></span>
			</div>
		</a>
		<?php
	}
}

/**
 * Quick Exit Button Component (FR-2).
 *
 * @param string $additional_classes Extra CSS classes.
 */
function wellness_render_quick_exit_button( $additional_classes = '' ) {
	// Respect the Customizer toggle (default: OFF for the demo).
	if ( ! get_theme_mod( 'wellness_show_quick_exit', false ) ) {
		return;
	}
	$class = trim( 'quick-exit-btn ' . $additional_classes );
	?>
	<button type="button" class="<?php echo esc_attr( $class ); ?>" title="<?php esc_attr_e( 'Quick Exit: Immediately leave this site and open Google', 'wellness-theme' ); ?>" aria-label="<?php esc_attr_e( 'Quick Exit from this site', 'wellness-theme' ); ?>">
		<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M18 6L6 18M6 6l12 12"/>
		</svg>
		<span><?php esc_html_e( 'Quick Exit', 'wellness-theme' ); ?></span>
		<span class="esc-hint" aria-hidden="true">3×Esc</span>
	</button>
	<?php
}

/**
 * Medical Disclaimer Content (FR-4 & Section 10).
 */
function wellness_get_medical_disclaimer_text() {
	$default = esc_html__(
		'Medical disclaimer: The content on this website is for general education only. It does not replace professional medical advice, diagnosis or treatment. If you have a medical concern or emergency, contact your local emergency services or a qualified healthcare professional.',
		'wellness-theme'
	);
	$val = get_theme_mod( 'wellness_footer_disclaimer', '' );
	if ( ! empty( $val ) ) {
		return $val;
	}
	return get_theme_mod( 'wellness_disclaimer_text', $default );
}

/**
 * Footer About Description Text.
 */
function wellness_footer_about_text() {
	$default = esc_html__(
		'Your everyday guide to nutrition, sleep, fitness and stress management.',
		'wellness-theme'
	);
	$val = get_theme_mod( 'wellness_footer_tagline', '' );
	if ( ! empty( $val ) ) {
		return $val;
	}
	return get_theme_mod( 'wellness_footer_about', $default );
}

/**
 * Footer Copyright Text (Item 3).
 * Dynamically binds to bloginfo('name') and current year.
 */
function wellness_footer_copyright_text() {
	$custom = get_theme_mod( 'wellness_footer_copyright', '' );
	if ( ! empty( $custom ) ) {
		return esc_html( $custom );
	}
	$year = date_i18n( 'Y' );
	$name = get_bloginfo( 'name' );
	return sprintf( esc_html__( '&copy; %1$s %2$s. All rights reserved.', 'wellness-theme' ), esc_html( $year ), esc_html( $name ) );
}


/**
 * Output the Light / Dark Theme Mode Toggle.
 */
function wellness_render_theme_toggle() {
	?>
	<div class="theme-mode-toggle" role="group" aria-label="<?php esc_attr_e( 'Theme display mode', 'wellness-theme' ); ?>">
		<button type="button" class="theme-mode-btn" data-mode="light" title="<?php esc_attr_e( 'Light Mode', 'wellness-theme' ); ?>" aria-pressed="false">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
			<span class="toggle-mode-label"><?php esc_html_e( 'Light', 'wellness-theme' ); ?></span>
		</button>
		<button type="button" class="theme-mode-btn" data-mode="dark" title="<?php esc_attr_e( 'Dark Mode', 'wellness-theme' ); ?>" aria-pressed="false">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
			<span class="toggle-mode-label"><?php esc_html_e( 'Dark', 'wellness-theme' ); ?></span>
		</button>
	</div>
	<?php
}
