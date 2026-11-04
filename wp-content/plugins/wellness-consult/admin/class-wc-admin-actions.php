<?php
/**
 * Handles admin mutations before page output begins.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

class WC_Admin_Actions {

	/**
	 * Register admin-post endpoints.
	 */
	public static function init() {
		add_action( 'admin_post_wc_delete_request', array( __CLASS__, 'delete_request' ) );
		add_action( 'admin_post_wc_bulk_delete_requests', array( __CLASS__, 'bulk_delete_requests' ) );
		add_action( 'admin_post_wc_clear_activity_logs', array( __CLASS__, 'clear_activity_logs' ) );
		add_action( 'admin_post_wc_export_settings', array( __CLASS__, 'export_settings' ) );
		add_action( 'admin_post_wc_save_email_template', array( __CLASS__, 'save_email_template' ) );
		add_action( 'admin_post_wc_reset_email_template', array( __CLASS__, 'reset_email_template' ) );
		add_action( 'admin_post_wc_test_email_template', array( __CLASS__, 'test_email_template' ) );
	}

	/**
	 * Redirect to an email-template screen with a short status code.
	 *
	 * @param string $event_key Template event key.
	 * @param string $notice Notice code.
	 */
	private static function redirect_email_template( $event_key, $notice ) {
		wp_safe_redirect( add_query_arg(
			array(
				'page'   => 'wc-email-templates',
				'event'  => sanitize_key( $event_key ),
				'notice' => sanitize_key( $notice ),
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Get valid email templates with canonical labels.
	 *
	 * @return array
	 */
	private static function get_email_templates() {
		$defaults = WC_Options::get_default_email_templates();
		$saved    = get_option( 'wc_email_templates', array() );
		$templates = array_merge( $defaults, is_array( $saved ) ? $saved : array() );
		foreach ( $defaults as $key => $default ) {
			$templates[ $key ]['label'] = $default['label'];
		}
		return $templates;
	}

	/**
	 * Save a template before output begins.
	 */
	public static function save_email_template() {
		if ( ! current_user_can( 'wc_manage_email_templates' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage email templates.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'wc_save_email_action', 'wc_email_save_nonce' );

		$event_key = isset( $_POST['event_key'] ) && is_string( $_POST['event_key'] ) ? sanitize_key( wp_unslash( $_POST['event_key'] ) ) : '';
		$templates = self::get_email_templates();
		if ( ! isset( $templates[ $event_key ] ) ) {
			self::redirect_email_template( 'request_submitted', 'invalid_event' );
		}

		$subject = isset( $_POST['subject'] ) && is_string( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$body    = isset( $_POST['body'] ) && is_string( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : '';
		if ( '' === $subject || '' === $body ) {
			self::redirect_email_template( $event_key, 'empty_template' );
		}

		$templates[ $event_key ]['subject'] = $subject;
		$templates[ $event_key ]['body']    = $body;
		update_option( 'wc_email_templates', $templates );
		WC_Database::log( array(
			'action' => 'update_email_template',
			'note'   => sprintf( 'Email template updated for event: %s.', $event_key ),
		) );

		self::redirect_email_template( $event_key, 'saved' );
	}

	/**
	 * Reset one template to its default before output begins.
	 */
	public static function reset_email_template() {
		if ( ! current_user_can( 'wc_manage_email_templates' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage email templates.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		$event_key = isset( $_GET['event_key'] ) ? sanitize_key( wp_unslash( $_GET['event_key'] ) ) : '';
		check_admin_referer( 'wc_reset_email_' . $event_key );

		$defaults = WC_Options::get_default_email_templates();
		if ( ! isset( $defaults[ $event_key ] ) ) {
			self::redirect_email_template( 'request_submitted', 'invalid_event' );
		}

		$templates = self::get_email_templates();
		$templates[ $event_key ] = $defaults[ $event_key ];
		update_option( 'wc_email_templates', $templates );
		WC_Database::log( array(
			'action' => 'reset_email_template',
			'note'   => sprintf( 'Email template reset to default for event: %s.', $event_key ),
		) );

		self::redirect_email_template( $event_key, 'reset' );
	}

	/**
	 * Send a saved template to the logged-in administrator only.
	 */
	public static function test_email_template() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Only an administrator can send a test email to their own address.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		$event_key = isset( $_POST['event_key'] ) && is_string( $_POST['event_key'] ) ? sanitize_key( wp_unslash( $_POST['event_key'] ) ) : '';
		check_admin_referer( 'wc_test_email_' . $event_key, 'wc_test_email_nonce' );
		$templates = self::get_email_templates();
		if ( ! isset( $templates[ $event_key ] ) ) {
			self::redirect_email_template( 'request_submitted', 'invalid_event' );
		}

		$user = wp_get_current_user();
		$recipient = sanitize_email( $user->user_email );
		if ( ! is_email( $recipient ) ) {
			self::redirect_email_template( $event_key, 'test_failed' );
		}

		$rate_key = 'wc_email_test_' . get_current_user_id();
		$attempts = absint( get_transient( $rate_key ) );
		if ( $attempts >= 5 ) {
			self::redirect_email_template( $event_key, 'rate_limited' );
		}
		set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

		$template = $templates[ $event_key ];
		$sample = array(
			'{site_name}'             => get_bloginfo( 'name' ),
			'{name}'                  => 'Test Patient',
			'{reference_id}'          => 'WC-TEST-0001',
			'{consultation_type}'     => 'Sample Consultation',
			'{consult_mode}'          => 'Video',
			'{platform}'              => 'Example Meeting Platform',
			'{fee_usd}'               => '$45.00 USD',
			'{track_url}'             => 'https://example.com/track-my-request/?ref=WC-TEST-0001',
			'{rejection_reason}'      => 'Sample verification note.',
			'{date_time_patient}'     => 'January 15, 2030 at 10:00 AM UTC',
			'{date_time_consultant}'  => 'January 15, 2030 at 10:00 AM UTC',
			'{appointment_local}'     => 'January 15, 2030 at 10:00 AM UTC',
			'{appointment_consultant}'=> 'January 15, 2030 at 10:00 AM UTC',
			'{meeting_link}'          => 'https://example.com/meeting/WC-TEST-0001',
			'{reason}'                => 'Sample cancellation reason.',
		);
		$subject = '[TEST] ' . str_replace( array_keys( $sample ), array_values( $sample ), $template['subject'] );
		$body    = str_replace( array_keys( $sample ), array_values( $sample ), $template['body'] );
		$sent    = wp_mail( $recipient, $subject, $body );

		self::redirect_email_template( $event_key, $sent ? 'test_sent' : 'test_failed' );
	}

	/**
	 * Delete one request and its protected proof files.
	 *
	 * @param int $request_id Request ID.
	 * @return bool|WP_Error
	 */
	private static function delete_request_record( $request_id ) {
		$request_id = absint( $request_id );
		$request    = get_post( $request_id );
		if ( ! $request || 'wc_request' !== $request->post_type ) {
			return false;
		}

		$reference_id = get_post_meta( $request_id, '_wc_reference_id', true );
		$proof_paths  = array( get_post_meta( $request_id, '_wc_proof_file', true ) );
		$proof_history = get_post_meta( $request_id, '_wc_proof_history', true );
		if ( is_array( $proof_history ) ) {
			foreach ( $proof_history as $proof_entry ) {
				if ( is_array( $proof_entry ) ) {
					$proof_paths[] = $proof_entry['relative_path'] ?? ( $proof_entry['path'] ?? '' );
				}
			}
		}

		$proof_directory = realpath( WC_PROOFS_DIR );
		if ( false !== $proof_directory ) {
			$proof_directory = trailingslashit( wp_normalize_path( $proof_directory ) );
			foreach ( array_unique( array_filter( $proof_paths, 'is_string' ) ) as $relative_path ) {
				$relative_path = wp_normalize_path( $relative_path );
				if ( '' === $relative_path || 0 === strpos( $relative_path, '/' ) || preg_match( '/^[A-Za-z]:\//', $relative_path ) || in_array( '..', explode( '/', $relative_path ), true ) ) {
					continue;
				}
				$proof_path = realpath( trailingslashit( WC_PROOFS_DIR ) . $relative_path );
				if ( false !== $proof_path ) {
					$proof_path = wp_normalize_path( $proof_path );
					if ( 0 === strpos( $proof_path, $proof_directory ) && is_file( $proof_path ) ) {
						wp_delete_file( $proof_path );
					}
				}
			}
		}

		if ( ! wp_delete_post( $request_id, true ) ) {
			return false;
		}

		global $wpdb;
		$current_user = wp_get_current_user();
		$logged = $wpdb->insert(
			$wpdb->prefix . 'wc_activity_log',
			array(
				'request_id'   => $request_id,
				'reference_id' => sanitize_text_field( $reference_id ),
				'action'       => 'request_deleted',
				'user_id'      => get_current_user_id(),
				'user_name'    => sanitize_text_field( $current_user->display_name ),
				'from_status'  => '',
				'to_status'    => '',
				'note'         => 'Request permanently deleted.',
				'ip_address'   => '',
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return 1 === $logged ? true : new WP_Error( 'wc_delete_audit_failed', __( 'Request permanently deleted, but its audit entry could not be recorded.', 'wellness-consult' ) );
	}

	/**
	 * Handle single-request deletion before output.
	 */
	public static function delete_request() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wc_manage_all_requests' ) ) {
			wp_die( esc_html__( 'Only administrators can permanently delete requests.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}

		$request_id = isset( $_GET['request_id'] ) ? absint( $_GET['request_id'] ) : 0;
		check_admin_referer( 'wc_delete_request_' . $request_id );

		$result = self::delete_request_record( $request_id );
		if ( is_wp_error( $result ) ) {
			$args = array(
				'deleted'           => 1,
				'deleted_count'     => 1,
				'delete_log_errors' => 1,
			);
		} elseif ( $result ) {
			$args = array(
				'deleted'       => 1,
				'deleted_count' => 1,
			);
		} else {
			wp_die( esc_html__( 'The request could not be deleted.', 'wellness-consult' ) );
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=wc-all-requests' ) ) );
		exit;
	}

	/**
	 * Handle bulk request deletion before output.
	 */
	public static function bulk_delete_requests() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wc_manage_all_requests' ) ) {
			wp_die( esc_html__( 'Only administrators can permanently delete requests.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'wc_bulk_delete_requests', 'wc_bulk_delete_nonce' );

		$selected_ids = array();
		if ( isset( $_POST['request_ids'] ) && is_array( $_POST['request_ids'] ) ) {
			foreach ( wp_unslash( $_POST['request_ids'] ) as $selected_id ) {
				if ( is_scalar( $selected_id ) && absint( $selected_id ) ) {
					$selected_ids[] = absint( $selected_id );
				}
			}
		}
		$selected_ids = array_values( array_unique( $selected_ids ) );
		if ( empty( $selected_ids ) ) {
			wp_die( esc_html__( 'Select at least one request to delete.', 'wellness-consult' ) );
		}

		$deleted_count     = 0;
		$delete_log_errors = 0;
		foreach ( $selected_ids as $selected_id ) {
			$result = self::delete_request_record( $selected_id );
			if ( true === $result ) {
				++$deleted_count;
			} elseif ( is_wp_error( $result ) && 'wc_delete_audit_failed' === $result->get_error_code() ) {
				++$deleted_count;
				++$delete_log_errors;
			}
		}

		wp_safe_redirect( add_query_arg(
			array(
				'deleted'           => 1,
				'deleted_count'     => $deleted_count,
				'delete_log_errors' => $delete_log_errors,
			),
			admin_url( 'admin.php?page=wc-all-requests' )
		) );
		exit;
	}

	/**
	 * Clear the activity log before output begins.
	 */
	public static function clear_activity_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Only administrators can clear the activity log.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'wc_clear_activity_logs' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_activity_log';
		$wpdb->query( "TRUNCATE TABLE $table_name" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		WC_Database::log( array(
			'action' => 'clear_log',
			'note'   => 'Audit history truncated by administrator.',
		) );

		wp_safe_redirect( add_query_arg( 'notice', 'logs_cleared', admin_url( 'admin.php?page=wc-activity-log' ) ) );
		exit;
	}

	/**
	 * Export settings before output begins.
	 */
	public static function export_settings() {
		if ( ! current_user_can( 'wc_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to export consultation settings.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'wc_export_settings_nonce' );

		$export_data = array(
			'site_url'           => home_url(),
			'export_timestamp'   => current_time( 'mysql' ),
			'schema_version'     => '1.1.0',
			'general_settings'   => get_option( 'wc_general_settings', array() ),
			'consultation_types' => get_option( 'wc_consultation_types', array() ),
			'platforms'          => get_option( 'wc_platforms', WC_Options::get_default_platforms() ),
			'countries_settings' => get_option( 'wc_countries_settings', array() ),
			'email_templates'    => get_option( 'wc_email_templates', array() ),
		);

		$filename = 'wellness-consult-settings-' . gmdate( 'Y-m-d' ) . '.json';
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $export_data, JSON_PRETTY_PRINT );
		exit;
	}
}

WC_Admin_Actions::init();
