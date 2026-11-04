<?php
/**
 * Wellness Theme Main Functions
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WELLNESS_THEME_VERSION', '1.0.0' );

// Load Modular Core Files from inc/
require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/widgets.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/template-tags.php';

/**
 * Enqueue scripts and styles.
 */
function wellness_theme_scripts() {
	$theme_version = wp_get_theme()->get( 'Version' );

	// Main Stylesheet
	wp_enqueue_style(
		'wellness-style',
		get_stylesheet_uri(),
		array(),
		$theme_version
	);

	// Dedicated RTL Stylesheet (FR-6: Urdu support)
	if ( is_rtl() ) {
		wp_enqueue_style(
			'wellness-rtl',
			get_template_directory_uri() . '/rtl.css',
			array( 'wellness-style' ),
			$theme_version
		);
	}

	// Main JavaScript
	wp_enqueue_script(
		'wellness-theme-js',
		get_template_directory_uri() . '/assets/js/theme.js',
		array(),
		$theme_version,
		true
	);

	// Quick Exit URL from Customizer (default: Google)
	$quick_exit_url = get_theme_mod( 'wellness_quick_exit_url', 'https://www.google.com' );
	$quick_exit_url = apply_filters( 'wellness_quick_exit_url', $quick_exit_url );

	wp_localize_script( 'wellness-theme-js', 'wellnessThemeConfig', array(
		'quickExitUrl' => esc_url( $quick_exit_url ),
		'strings'      => array(
			'menuOpen'  => esc_html__( 'Open navigation menu', 'wellness-theme' ),
			'menuClose' => esc_html__( 'Close navigation menu', 'wellness-theme' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'wellness_theme_scripts' );

/**
 * Fallback menu for Header if no menu is assigned yet.
 */
function wellness_fallback_primary_menu() {
	$items = array(
		array( 'title' => __( 'Home', 'wellness-theme' ), 'url' => home_url( '/' ) ),
		array( 'title' => __( 'Articles', 'wellness-theme' ), 'url' => home_url( '/articles/' ) ),
		array( 'title' => __( 'Myths vs Facts', 'wellness-theme' ), 'url' => home_url( '/myths-vs-facts/' ) ),
		array( 'title' => __( 'Ask a Question', 'wellness-theme' ), 'url' => home_url( '/ask-a-question/' ) ),
		array( 'title' => __( 'About', 'wellness-theme' ), 'url' => home_url( '/about/' ) ),
		array( 'title' => __( 'Track Request', 'wellness-theme' ), 'url' => home_url( '/track-my-request/' ) ),
		array( 'title' => __( 'Book Consultation', 'wellness-theme' ), 'url' => home_url( '/book-consultation/' ), 'is_cta' => true ),
	);

	echo '<ul class="nav-menu">';
	foreach ( $items as $item ) {
		$class = ! empty( $item['is_cta'] ) ? 'nav-cta' : '';
		printf(
			'<li class="%s"><a href="%s">%s</a></li>',
			esc_attr( $class ),
			esc_url( $item['url'] ),
			esc_html( $item['title'] )
		);
	}
	echo '</ul>';
}

/**
 * Fallback menu for Mobile Drawer.
 */
function wellness_fallback_mobile_menu() {
	$items = array(
		array( 'title' => __( 'Home', 'wellness-theme' ), 'url' => home_url( '/' ) ),
		array( 'title' => __( 'Articles & Education', 'wellness-theme' ), 'url' => home_url( '/articles/' ) ),
		array( 'title' => __( 'Myths vs Facts', 'wellness-theme' ), 'url' => home_url( '/myths-vs-facts/' ) ),
		array( 'title' => __( 'Ask Anonymously (Free Q&A)', 'wellness-theme' ), 'url' => home_url( '/ask-a-question/' ) ),
		array( 'title' => __( 'Our Doctor & Mission', 'wellness-theme' ), 'url' => home_url( '/about/' ) ),
		array( 'title' => __( 'Track My Request', 'wellness-theme' ), 'url' => home_url( '/track-my-request/' ) ),
		array( 'title' => __( 'Book Private Consultation', 'wellness-theme' ), 'url' => home_url( '/book-consultation/' ), 'is_cta' => true ),
	);

	echo '<ul class="mobile-nav-menu">';
	foreach ( $items as $item ) {
		$class = ! empty( $item['is_cta'] ) ? 'nav-cta' : '';
		printf(
			'<li class="%s"><a href="%s">%s</a></li>',
			esc_attr( $class ),
			esc_url( $item['url'] ),
			esc_html( $item['title'] )
		);
	}
	echo '</ul>';
}
