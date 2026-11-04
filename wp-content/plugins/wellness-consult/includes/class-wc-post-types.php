<?php
/**
 * Register Custom Post Types and Taxonomies
 *
 * Implements wc_request (Private, No front-end URLs)
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Post_Types {

	/**
	 * Register wc_request custom post type.
	 */
	public static function register_post_types() {
		$labels = array(
			'name'               => _x( 'Consultation Requests', 'post type general name', 'wellness-consult' ),
			'singular_name'      => _x( 'Consultation Request', 'post type singular name', 'wellness-consult' ),
			'menu_name'          => _x( 'Consultation Requests', 'admin menu', 'wellness-consult' ),
			'name_admin_bar'     => _x( 'Consultation Request', 'add new on admin bar', 'wellness-consult' ),
			'add_new'            => _x( 'Add New', 'request', 'wellness-consult' ),
			'add_new_item'       => __( 'Add New Consultation Request', 'wellness-consult' ),
			'new_item'           => __( 'New Consultation Request', 'wellness-consult' ),
			'edit_item'          => __( 'Edit Consultation Request', 'wellness-consult' ),
			'view_item'          => __( 'View Consultation Request', 'wellness-consult' ),
			'all_items'          => __( 'All Requests', 'wellness-consult' ),
			'search_items'       => __( 'Search Requests', 'wellness-consult' ),
			'not_found'          => __( 'No requests found.', 'wellness-consult' ),
			'not_found_in_trash' => __( 'No requests found in Trash.', 'wellness-consult' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false, // Managed via custom Consultations menu
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
			'exclude_from_search'=> true,
		);

		register_post_type( 'wc_request', $args );
	}
}
