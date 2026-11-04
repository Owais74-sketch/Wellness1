<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Implements Rule 2: Delete data only if "Delete all data on uninstall" is ticked.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$general_settings    = get_option( 'wc_general_settings', array() );
$delete_on_uninstall = isset( $general_settings['delete_on_uninstall'] ) && '1' === (string) $general_settings['delete_on_uninstall'];

// Only purge data if explicitly enabled by administrator
if ( $delete_on_uninstall ) {
	global $wpdb;

	// 1. Delete all wc_request posts and associated metadata
	$requests = get_posts( array(
		'post_type'   => 'wc_request',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $requests ) ) {
		foreach ( $requests as $request_id ) {
			wp_delete_post( $request_id, true );
		}
	}

	// 2. Drop custom database table
	$table_name = $wpdb->prefix . 'wc_activity_log';
	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

	// 3. Delete all registered plugin options
	$options_to_delete = array(
		'wc_consultation_types',
		'wc_payment_settings',
		'wc_manual_methods',
		'wc_availability_settings',
		'wc_countries_settings',
		'wc_general_settings',
		'wc_email_templates',
		'wc_blocked_contacts',
		'wc_activity_log_db_version',
	);

	foreach ( $options_to_delete as $opt ) {
		delete_option( $opt );
	}

	// 4. Remove custom roles
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-roles.php';
	WC_Roles::remove_roles();
}
