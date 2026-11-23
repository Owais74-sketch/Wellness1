<?php
/**
 * Public Feature Visibility Helpers and Menu Filters
 *
 * Implements theme-level kill-switch readers and navigation filters.
 * When consultations or anonymous questions are disabled in Consultations > Settings,
 * this file ensures all links, menu items, and page references are hidden from visitors.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if the consultation system is enabled and open to the public.
 *
 * Returns false if the plugin is inactive or if the option is missing / disabled.
 *
 * @return bool
 */
function wellness_consultations_enabled() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active( 'wellness-consult/wellness-consult.php' ) ) {
		return false;
	}

	return (bool) get_option( 'wc_consultations_enabled', false );
}

/**
 * Check if the anonymous questions feature is enabled and open to the public.
 *
 * Returns false if the plugin is inactive or if the option is missing / disabled.
 *
 * @return bool
 */
function wellness_questions_enabled() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active( 'wellness-consult/wellness-consult.php' ) ) {
		return false;
	}

	return (bool) get_option( 'wc_questions_enabled', false );
}

/**
 * Filter menu items across all registered WordPress navigation menus.
 *
 * Removes links to booking, tracking, refund policy, and anonymous questions
 * when their corresponding feature switch is disabled.
 *
 * @param array    $items An array of menu item post objects.
 * @param stdClass $args  An object containing wp_nav_menu() arguments.
 * @return array
 */
function wellness_filter_nav_menu_objects( $items, $args ) {
	if ( ! is_array( $items ) || empty( $items ) ) {
		return $items;
	}

	$consult_on   = wellness_consultations_enabled();
	$questions_on = wellness_questions_enabled();

	// If both features are ON, no filtering needed
	if ( $consult_on && $questions_on ) {
		return $items;
	}

	$blocked_consult_slugs   = array( 'book-consultation', 'track-my-request', 'refund-policy' );
	$blocked_questions_slugs = array( 'ask-a-question' );

	$filtered_items = array();

	foreach ( $items as $key => $item ) {
		$slug = '';

		// 1. Resolve slug if linked to a post/page object
		if ( 'page' === $item->object && ! empty( $item->object_id ) ) {
			$post_obj = get_post( $item->object_id );
			if ( $post_obj ) {
				$slug = $post_obj->post_name;
			}
		}

		// 2. Fallback: Parse URL path if custom link or slug unresolved
		if ( empty( $slug ) && ! empty( $item->url ) ) {
			$path  = trim( (string) wp_parse_url( $item->url, PHP_URL_PATH ), '/' );
			$parts = explode( '/', $path );
			$slug  = end( $parts );
		}

		// Check consultation restriction
		if ( ! $consult_on && in_array( $slug, $blocked_consult_slugs, true ) ) {
			continue;
		}

		// Check anonymous questions restriction
		if ( ! $questions_on && in_array( $slug, $blocked_questions_slugs, true ) ) {
			continue;
		}

		$filtered_items[ $key ] = $item;
	}

	return $filtered_items;
}
add_filter( 'wp_nav_menu_objects', 'wellness_filter_nav_menu_objects', 10, 2 );
