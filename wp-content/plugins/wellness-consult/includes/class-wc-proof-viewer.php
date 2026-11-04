<?php
/**
 * Secure Proof Viewer Handler
 *
 * Implements Rule 2: Admins view the proof through a secure handler checking capability,
 * request ownership for consultants, and a nonce, setting Content-Type inline,
 * X-Content-Type-Options nosniff, and no-cache. Anyone else gets 403.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Proof_Viewer {

	/**
	 * Register admin_post hook for secure proof viewing.
	 */
	public static function init() {
		add_action( 'admin_post_wc_view_proof', array( __CLASS__, 'handle_view_proof' ) );
		// Unauthenticated requests hook to the same endpoint to strictly return 403
		add_action( 'admin_post_nopriv_wc_view_proof', array( __CLASS__, 'handle_unauthenticated_view' ) );
	}

	/**
	 * Strictly block unauthenticated access with 403 Forbidden.
	 */
	public static function handle_unauthenticated_view() {
		status_header( 403 );
		wp_die(
			esc_html__( 'Forbidden: Authentication is required to view confidential patient payment records.', 'wellness-consult' ),
			esc_html__( 'Access Denied', 'wellness-consult' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Authenticated secure proof viewer.
	 */
	public static function handle_view_proof() {
		if ( ! is_user_logged_in() ) {
			self::handle_unauthenticated_view();
		}

		$request_id = isset( $_GET['request_id'] ) ? absint( $_GET['request_id'] ) : 0;
		if ( ! $request_id ) {
			status_header( 400 );
			wp_die( esc_html__( 'Invalid request identifier.', 'wellness-consult' ), 'Bad Request', array( 'response' => 400 ) );
		}

		// Verify Nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wc_view_proof_' . $request_id ) ) {
			status_header( 403 );
			wp_die( esc_html__( 'Security check failed. Nonce is invalid or has expired.', 'wellness-consult' ), 'Forbidden', array( 'response' => 403 ) );
		}

		// Capability check: User must be able to view requests
		if ( ! current_user_can( 'wc_manage_requests' ) && ! current_user_can( 'wc_manage_assigned_requests' ) ) {
			status_header( 403 );
			wp_die( esc_html__( 'Forbidden: You do not have permissions to access consultation proofs.', 'wellness-consult' ), 'Forbidden', array( 'response' => 403 ) );
		}

		// Consultant ownership check: If user is wc_consultant (and not full administrator)
		$current_user = wp_get_current_user();
		$is_admin     = current_user_can( 'manage_options' ) || current_user_can( 'wc_manage_all_requests' );

		if ( ! $is_admin ) {
			$assigned_consultant_id = absint( get_post_meta( $request_id, '_wc_consultant_id', true ) );
			if ( empty( $assigned_consultant_id ) || $assigned_consultant_id !== $current_user->ID ) {
				status_header( 403 );
				wp_die(
					esc_html__( 'Forbidden: You are not the assigned healthcare consultant for this consultation request.', 'wellness-consult' ),
					esc_html__( 'Access Denied', 'wellness-consult' ),
					array( 'response' => 403 )
				);
			}
		}

		// Locate file from post meta
		$relative_file = get_post_meta( $request_id, '_wc_proof_file', true );
		if ( empty( $relative_file ) ) {
			$history = get_post_meta( $request_id, '_wc_proof_history', true );
			if ( is_array( $history ) && ! empty( $history ) ) {
				$last_entry = end( $history );
				$relative_file = $last_entry['relative_path'] ?? ( $last_entry['path'] ?? '' );
			}
		}

		if ( empty( $relative_file ) ) {
			status_header( 404 );
			wp_die( esc_html__( 'No payment proof file associated with this request.', 'wellness-consult' ), 'Not Found', array( 'response' => 404 ) );
		}

		// Path traversal defense
		$base_dir  = realpath( WC_PROOFS_DIR );
		$full_path = realpath( WC_PROOFS_DIR . $relative_file );

		if ( false === $full_path || false === $base_dir || 0 !== strpos( $full_path, $base_dir ) || ! file_exists( $full_path ) ) {
			status_header( 404 );
			wp_die( esc_html__( 'Payment proof file could not be located in secure storage.', 'wellness-consult' ), 'Not Found', array( 'response' => 404 ) );
		}

		// Determine safe MIME type
		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $full_path );
		finfo_close( $finfo );

		if ( ! in_array( $mime_type, array( 'image/jpeg', 'image/png', 'application/pdf' ), true ) ) {
			$mime_type = 'application/octet-stream';
		}

		$extension = pathinfo( $full_path, PATHINFO_EXTENSION );
		$display_filename = sprintf( 'proof-%d.%s', $request_id, $extension );

		// Clean output buffer before streaming
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Required Security Headers (Rule 2)
		if ( ! headers_sent() ) {
			header( 'Content-Type: ' . $mime_type );
			header( 'Content-Disposition: inline; filename="' . $display_filename . '"' );
			header( 'Content-Length: ' . filesize( $full_path ) );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
		}

		readfile( $full_path );
		exit;
	}
}
