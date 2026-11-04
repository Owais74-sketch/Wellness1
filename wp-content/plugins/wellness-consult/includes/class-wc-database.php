<?php
/**
 * Database Management and Activity Logging
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Database {

	const DB_VERSION = '1.0.0';

	/**
	 * Create or update custom database tables using dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'wc_activity_log';

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			reference_id varchar(64) DEFAULT '' NOT NULL,
			action varchar(64) NOT NULL,
			user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			user_name varchar(128) DEFAULT '' NOT NULL,
			from_status varchar(64) DEFAULT '' NOT NULL,
			to_status varchar(64) DEFAULT '' NOT NULL,
			note text DEFAULT '' NOT NULL,
			ip_address varchar(45) DEFAULT '' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY request_id (request_id),
			KEY reference_id (reference_id),
			KEY action (action)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'wc_activity_log_db_version', self::DB_VERSION );
	}

	/**
	 * Log an activity event into the database.
	 *
	 * @param array $args Data to log.
	 * @return int|false Inserted ID or false.
	 */
	public static function log( $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wc_activity_log';

		$current_user = wp_get_current_user();
		$user_id      = isset( $args['user_id'] ) ? absint( $args['user_id'] ) : ( $current_user ? $current_user->ID : 0 );
		$user_name    = isset( $args['user_name'] ) ? sanitize_text_field( $args['user_name'] ) : ( $current_user && $current_user->exists() ? $current_user->display_name : 'System' );

		// Capture IP Address safely
		$ip_address = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$data = array(
			'request_id'   => isset( $args['request_id'] ) ? absint( $args['request_id'] ) : 0,
			'reference_id' => isset( $args['reference_id'] ) ? sanitize_text_field( $args['reference_id'] ) : '',
			'action'       => isset( $args['action'] ) ? sanitize_key( $args['action'] ) : 'general',
			'user_id'      => $user_id,
			'user_name'    => $user_name,
			'from_status'  => isset( $args['from_status'] ) ? sanitize_key( $args['from_status'] ) : '',
			'to_status'    => isset( $args['to_status'] ) ? sanitize_key( $args['to_status'] ) : '',
			'note'         => isset( $args['note'] ) ? sanitize_textarea_field( $args['note'] ) : '',
			'ip_address'   => $ip_address,
			'created_at'   => current_time( 'mysql', 1 ),
		);

		$format = array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' );

		$wpdb->insert( $table_name, $data, $format );
		return $wpdb->insert_id;
	}

	/**
	 * Get activity logs with pagination and optional action/search filter.
	 *
	 * @param int    $limit  Number of items to fetch.
	 * @param int    $offset Offset.
	 * @param string $action Optional action filter.
	 * @return array
	 */
	public static function get_logs( $limit = 50, $offset = 0, $action = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wc_activity_log';
		$limit      = absint( $limit );
		$offset     = absint( $offset );

		if ( ! empty( $action ) ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table_name WHERE action = %s ORDER BY id DESC LIMIT %d OFFSET %d",
					$action,
					$limit,
					$offset
				),
				ARRAY_A
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY id DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);
	}

	/**
	 * Count total logs for pagination.
	 *
	 * @param string $action Optional filter.
	 * @return int
	 */
	public static function count_logs( $action = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_activity_log';

		if ( ! empty( $action ) ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE action = %s", $action )
			);
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
	}

	/**
	 * Run Change Request CR-1 data model migration safely.
	 *
	 * - Migrates consultation types to store fee_chat, fee_voice, fee_video, and enabled flags.
	 * - Preserves name, duration, description.
	 * - Preserves existing fee_usd as a legacy field.
	 * - Sets all three modes disabled by default with needs_mode_fees flag.
	 * - Migrates request meta _wc_mode to _wc_platform.
	 * - Initializes wc_platforms settings (no defaults active).
	 *
	 * @return bool True if migration executed or already done.
	 */
	public static function migrate_cr1() {
		// 1. Migrate Consultation Types
		$types = get_option( 'wc_consultation_types', array() );
		$updated_types = false;

		if ( is_array( $types ) && ! empty( $types ) ) {
			foreach ( $types as $id => $type ) {
				// If type does not have fee_chat or enable flags, migrate it
				if ( ! isset( $type['fee_chat'] ) || ! isset( $type['enable_chat'] ) ) {
					$legacy_fee = isset( $type['fee_usd'] ) ? (float) $type['fee_usd'] : 0.0;

					$types[ $id ]['fee_chat']        = 0.0;
					$types[ $id ]['enable_chat']     = 0;
					$types[ $id ]['fee_voice']       = 0.0;
					$types[ $id ]['enable_voice']    = 0;
					$types[ $id ]['fee_video']       = 0.0;
					$types[ $id ]['enable_video']    = 0;
					$types[ $id ]['legacy_fee']      = $legacy_fee;
					$types[ $id ]['fee_usd']         = $legacy_fee; // Preserved as legacy
					$types[ $id ]['needs_mode_fees'] = 1;

					$updated_types = true;
				}
			}

			if ( $updated_types ) {
				update_option( 'wc_consultation_types', $types );
				self::log( array(
					'action' => 'cr1_migration_types',
					'note'   => 'Migrated consultation types to multi-mode structure (CR-1).',
				) );
			}
		}

		// 2. Migrate existing wc_request meta: _wc_mode -> _wc_platform
		$legacy_requests = get_posts( array(
			'post_type'   => 'wc_request',
			'meta_key'    => '_wc_mode',
			'numberposts' => -1,
			'post_status' => 'any',
		) );

		if ( ! empty( $legacy_requests ) ) {
			foreach ( $legacy_requests as $req ) {
				$old_mode = get_post_meta( $req->ID, '_wc_mode', true );
				if ( ! empty( $old_mode ) && empty( get_post_meta( $req->ID, '_wc_platform', true ) ) ) {
					update_post_meta( $req->ID, '_wc_platform', sanitize_text_field( $old_mode ) );
					delete_post_meta( $req->ID, '_wc_mode' );
				}
			}
		}

		// 3. Initialize Platforms in wc_platforms if not set
		if ( false === get_option( 'wc_platforms' ) ) {
			add_option( 'wc_platforms', WC_Options::get_default_platforms() );
		}

		update_option( 'wc_migration_cr1_done', 1 );
		return true;
	}
}
