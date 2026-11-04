<?php
/**
 * Theme Setup, Supports, Menus, and Image Sizes
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function wellness_setup() {
	// Translations.
	load_theme_textdomain( 'wellness-theme', get_template_directory() . '/languages' );

	// Default RSS feed links.
	add_theme_support( 'automatic-feed-links' );

	// Title Tag management.
	add_theme_support( 'title-tag' );

	// Post Thumbnails.
	add_theme_support( 'post-thumbnails' );

	// Custom Image Sizes.
	set_post_thumbnail_size( 720, 480, true );
	add_image_size( 'wellness-card', 600, 360, true );
	add_image_size( 'wellness-hero', 1200, 500, true );
	add_image_size( 'wellness-consultant', 400, 400, true );

	// Register 3 Navigation Menus.
	register_nav_menus( array(
		'primary' => esc_html__( 'Primary Navigation (Header)', 'wellness-theme' ),
		'footer'  => esc_html__( 'Footer Menu (Quick Links)', 'wellness-theme' ),
		'legal'   => esc_html__( 'Legal Policies Menu', 'wellness-theme' ),
	) );

	// HTML5 markup support.
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );

	// Custom Logo support.
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 280,
		'flex-width'  => true,
		'flex-height' => true,
	) );

	// Selective Refresh for Widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	// Responsive embeds.
	add_theme_support( 'responsive-embeds' );

	// Block editor styles & wide alignment.
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
}
add_action( 'after_setup_theme', 'wellness_setup' );
