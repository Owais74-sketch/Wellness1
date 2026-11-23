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
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/widgets.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/meta-boxes.php';

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
		filemtime( get_stylesheet_directory() . '/style.css' )
	);

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
	?>
	<ul class="nav-menu">
		<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'wellness-theme' ); ?></a></li>
		<li class="menu-item-has-children">
			<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>"><?php esc_html_e( 'Articles', 'wellness-theme' ); ?></a>
			<ul class="sub-menu">
				<li><a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>"><?php esc_html_e( 'All Articles & Guides', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/nutrition-eating/' ) ); ?>"><?php esc_html_e( 'Nutrition &amp; Eating', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/fitness-movement/' ) ); ?>"><?php esc_html_e( 'Fitness &amp; Movement', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/stress-mind/' ) ); ?>"><?php esc_html_e( 'Stress &amp; Mind', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/sleep-recovery/' ) ); ?>"><?php esc_html_e( 'Sleep &amp; Recovery', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/myths-vs-facts/' ) ); ?>"><?php esc_html_e( 'Myths vs Facts', 'wellness-theme' ); ?></a></li>
			</ul>
		</li>
		<li><a href="<?php echo esc_url( home_url( '/myths-vs-facts/' ) ); ?>"><?php esc_html_e( 'Myths vs Facts', 'wellness-theme' ); ?></a></li>
		<?php if ( wellness_questions_enabled() ) : ?>
			<li><a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>"><?php esc_html_e( 'Ask a Question', 'wellness-theme' ); ?></a></li>
		<?php endif; ?>
		<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About', 'wellness-theme' ); ?></a></li>
		<?php if ( wellness_consultations_enabled() ) : ?>
			<li><a href="<?php echo esc_url( home_url( '/track-my-request/' ) ); ?>"><?php esc_html_e( 'Track Request', 'wellness-theme' ); ?></a></li>
			<li class="nav-cta"><a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>"><?php esc_html_e( 'Book Consultation', 'wellness-theme' ); ?></a></li>
		<?php endif; ?>
	</ul>
	<?php
}

/**
 * Fallback menu for Mobile Drawer.
 */
function wellness_fallback_mobile_menu() {
	?>
	<ul class="mobile-nav-menu">
		<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'wellness-theme' ); ?></a></li>
		<li class="menu-item-has-children">
			<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>"><?php esc_html_e( 'Articles & Topics', 'wellness-theme' ); ?></a>
			<ul class="sub-menu">
				<li><a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>"><?php esc_html_e( 'All Articles', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/nutrition-eating/' ) ); ?>"><?php esc_html_e( 'Nutrition &amp; Eating', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/fitness-movement/' ) ); ?>"><?php esc_html_e( 'Fitness &amp; Movement', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/stress-mind/' ) ); ?>"><?php esc_html_e( 'Stress &amp; Mind', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/sleep-recovery/' ) ); ?>"><?php esc_html_e( 'Sleep &amp; Recovery', 'wellness-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/category/myths-vs-facts/' ) ); ?>"><?php esc_html_e( 'Myths vs Facts Topics', 'wellness-theme' ); ?></a></li>
			</ul>
		</li>
		<li><a href="<?php echo esc_url( home_url( '/myths-vs-facts/' ) ); ?>"><?php esc_html_e( 'Myths vs Facts', 'wellness-theme' ); ?></a></li>
		<?php if ( wellness_questions_enabled() ) : ?>
			<li><a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>"><?php esc_html_e( 'Ask Anonymously (Free Q&A)', 'wellness-theme' ); ?></a></li>
		<?php endif; ?>
		<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'Our Doctor & Mission', 'wellness-theme' ); ?></a></li>
		<?php if ( wellness_consultations_enabled() ) : ?>
			<li><a href="<?php echo esc_url( home_url( '/track-my-request/' ) ); ?>"><?php esc_html_e( 'Track My Request', 'wellness-theme' ); ?></a></li>
			<li class="nav-cta"><a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>"><?php esc_html_e( 'Book Private Consultation', 'wellness-theme' ); ?></a></li>
		<?php endif; ?>
	</ul>
	<?php
}
