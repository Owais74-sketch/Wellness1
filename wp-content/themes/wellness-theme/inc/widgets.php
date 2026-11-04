<?php
/**
 * Register Theme Widget Areas
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register widget areas.
 */
function wellness_widgets_init() {
	// Sidebar Widget Area
	register_sidebar( array(
		'name'          => esc_html__( 'Blog Sidebar', 'wellness-theme' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here to appear in your blog sidebar.', 'wellness-theme' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s card" style="padding:1.5rem; margin-bottom:1.5rem;">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title" style="font-size:var(--text-base); margin-bottom:1rem; color:var(--color-primary);">',
		'after_title'   => '</h3>',
	) );

	// Footer Column 1
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 1 (About)', 'wellness-theme' ),
		'id'            => 'footer-1',
		'description'   => esc_html__( 'Widgets in first footer column.', 'wellness-theme' ),
		'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="footer-widget-title">',
		'after_title'   => '</h4>',
	) );

	// Footer Column 2
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 2 (Quick Links)', 'wellness-theme' ),
		'id'            => 'footer-2',
		'description'   => esc_html__( 'Widgets in second footer column.', 'wellness-theme' ),
		'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="footer-widget-title">',
		'after_title'   => '</h4>',
	) );

	// Footer Column 3
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 3 (Categories)', 'wellness-theme' ),
		'id'            => 'footer-3',
		'description'   => esc_html__( 'Widgets in third footer column.', 'wellness-theme' ),
		'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="footer-widget-title">',
		'after_title'   => '</h4>',
	) );

	// Footer Column 4
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 4 (Policies & Legal)', 'wellness-theme' ),
		'id'            => 'footer-4',
		'description'   => esc_html__( 'Widgets in fourth footer column.', 'wellness-theme' ),
		'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="footer-widget-title">',
		'after_title'   => '</h4>',
	) );
}
add_action( 'widgets_init', 'wellness_widgets_init' );
