<?php
/**
 * Secure Proof Viewer Handler
 *
 * Implements Rule 2: Admins view the proof through a secure handler checking capability,
 * request ownership for consultants, and a nonce, setting Content-Type inline,
 * X-Content-Type-Options nosniff, and no-cache. Anyone else gets 403.
 *
 * Phase 11 hardening:
 * - Nonce and capability are checked BEFORE the request post is looked up,
 *   so a logged-in user without access cannot learn which request IDs exist.
 * - Output buffers are fully cleared before streaming the file.
 * - Download filename extension comes from the verified MIME type, not the stored path.
 * - Extra headers: private cache, no-referrer, noindex.
 *
 * @package Wellness_Consult
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Proof_Viewer {

	/**
	 * Build a nonce-protected URL for viewing a request proof.
	 *
	 * @param int      $request_id Request post ID.
	 * @param int|null $proof_index Optional proof history index.
	 * @return string
	 */
	public static function get_view_url( $request_id, $proof_index = null ) {
		$args = array(
			'action'     => 'wc_view_proof',
			'request_id' => absint( $request_id ),
		);
		if ( null !== $proof_index ) {
			$args['proof_index'] = absint( $proof_index );
		}

		return wp_nonce_url(
			add_query_arg( $args, admin_url( 'admin-post.php' ) ),
			'wc_view_proof_' . absint( $request_id )
		);
	}

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
	 * Generic "not found" used for every file-level failure (no detail leaked).
	 */
	private static function file_not_found() {
		status_header( 404 );
		wp_die( esc_html__( 'Proof file could not be loaded.', 'wellness-consult' ), 'Not Found', array( 'response' => 404 ) );
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

		// Only now look up the request.
		$request = get_post( $request_id );
		if ( ! $request || 'wc_request' !== $request->post_type ) {
			status_header( 404 );
			wp_die( esc_html__( 'Consultation request not found.', 'wellness-consult' ), 'Not Found', array( 'response' => 404 ) );
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

		// Locate the requested proof entry, defaulting to the current upload.
		$relative_file = '';
		$history = get_post_meta( $request_id, '_wc_proof_history', true );
		if ( isset( $_GET['proof_index'] ) ) {
			$proof_index = absint( $_GET['proof_index'] );
			if ( ! is_array( $history ) || ! isset( $history[ $proof_index ] ) ) {
				status_header( 404 );
				wp_die( esc_html__( 'The requested proof history entry was not found.', 'wellness-consult' ), 'Not Found', array( 'response' => 404 ) );
			}
			$proof_entry = $history[ $proof_index ];
			$relative_file = is_array( $proof_entry )
				? ( $proof_entry['relative_path'] ?? ( $proof_entry['path'] ?? '' ) )
				: '';
		} else {
			$relative_file = get_post_meta( $request_id, '_wc_proof_file', true );
			if ( empty( $relative_file ) && is_array( $history ) && ! empty( $history ) ) {
				$last_entry = end( $history );
				$relative_file = is_array( $last_entry )
					? ( $last_entry['relative_path'] ?? ( $last_entry['path'] ?? '' ) )
					: '';
			}
		}

		if ( ! is_string( $relative_file ) || '' === $relative_file ) {
			self::file_not_found();
		}

		// Path traversal defense
		$relative_file = wp_normalize_path( $relative_file );
		if ( '' === $relative_file || '/' === substr( $relative_file, 0, 1 ) || preg_match( '/^[A-Za-z]:\//', $relative_file ) || in_array( '..', explode( '/', $relative_file ), true ) ) {
			self::file_not_found();
		}
		$base_dir  = realpath( WC_PROOFS_DIR );
		$full_path = realpath( trailingslashit( WC_PROOFS_DIR ) . $relative_file );

		if ( false === $full_path || false === $base_dir || 0 !== strpos( wp_normalize_path( $full_path ), trailingslashit( wp_normalize_path( $base_dir ) ) ) || ! is_file( $full_path ) || ! is_readable( $full_path ) ) {
			self::file_not_found();
		}

		// Determine safe MIME type
		$finfo = function_exists( 'finfo_open' ) ? finfo_open( FILEINFO_MIME_TYPE ) : false;
		if ( false === $finfo ) {
			self::file_not_found();
		}
		$mime_type = finfo_file( $finfo, $full_path );
		finfo_close( $finfo );

		$allowed_types = array(
			'image/jpeg'      => 'jpg',
			'image/png'       => 'png',
			'application/pdf' => 'pdf',
		);
		if ( ! isset( $allowed_types[ $mime_type ] ) ) {
			self::file_not_found();
		}

		$display_filename = sprintf( 'proof-%d.%s', $request_id, $allowed_types[ $mime_type ] );

		// Clean every output buffer level before streaming
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		// Required Security Headers (Rule 2)
		if ( ! headers_sent() ) {
			header( 'Content-Type: ' . $mime_type );
			header( 'Content-Disposition: inline; filename="' . $display_filename . '"' );
			header( 'Content-Length: ' . filesize( $full_path ) );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Cache-Control: private, no-cache, no-store, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			header( 'Referrer-Policy: no-referrer' );
			header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		}

		readfile( $full_path );
		exit;
	}
}