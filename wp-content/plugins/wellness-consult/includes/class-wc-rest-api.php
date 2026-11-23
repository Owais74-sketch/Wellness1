<?php
/**
 * REST API Routes for Payment Gateways & Webhooks (Hardened)
 *
 * Implements endpoints for Stripe Webhook, PayPal Webhook,
 * and PayPal Return Capture with one-time token verification (FR-8, FR-12).
 *
 * Phase 11 hardening:
 * - The "already captured" shortcut no longer hands out a confirmation token
 *   (it let anyone read a paid request's details by guessing its numeric ID).
 *   It now just sends the patient to the Track My Request page.
 * - Return tokens keep their original letter case (sanitize_key lowercased them,
 *   which only worked by accident on case-insensitive database lookups).
 * - A return URL that is opened twice no longer asks PayPal to capture twice.
 *
 * @package Wellness_Consult
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;

class WC_REST_API {

	const NAMESPACE = 'wc-consult/v1';

	/**
	 * Register REST routes on rest_api_init.
	 */
	public static function register_routes() {
		// Stripe Webhook Endpoint
		register_rest_route( self::NAMESPACE, '/stripe-webhook', array(
			'methods'             => 'POST',
			'callback'            => array( 'WC_Gateway_Stripe', 'handle_webhook' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( self::NAMESPACE, '/stripe-return', array(
			'methods'             => 'GET',
			'callback'            => array( 'WC_Gateway_Stripe', 'handle_verified_return' ),
			'permission_callback' => '__return_true',
		) );

		// PayPal Webhook Endpoint
		register_rest_route( self::NAMESPACE, '/paypal-webhook', array(
			'methods'             => 'POST',
			'callback'            => array( 'WC_Gateway_PayPal', 'handle_webhook' ),
			'permission_callback' => '__return_true',
		) );

		// PayPal Return Handler Endpoint (Requirements 4 & 5)
		register_rest_route( self::NAMESPACE, '/paypal-return', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'handle_paypal_return' ),
			'permission_callback' => '__return_true',
		) );

		// Webhook Simulation Endpoint: ONLY available when WC_ALLOW_MOCK_GATEWAYS is true (Requirement 6)
		register_rest_route( self::NAMESPACE, '/simulate-webhook', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'simulate_webhook' ),
			'permission_callback' => array( __CLASS__, 'check_simulation_permission' ),
		) );
	}

	/**
	 * Clean a return token while keeping its letter case.
	 *
	 * @param mixed $value Raw request parameter.
	 * @return string
	 */
	protected static function clean_token( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		return substr( preg_replace( '/[^A-Za-z0-9]/', '', $value ), 0, 64 );
	}

	/**
	 * Handle return from PayPal approval flow with one-time token verification (Requirements 4 & 5).
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_paypal_return( $request ) {
		$post_id  = absint( $request->get_param( 'request_id' ) );
		$token    = self::clean_token( $request->get_param( 'token' ) );

		if ( empty( $post_id ) || empty( $token ) ) {
			wp_die( esc_html__( 'Forbidden: Missing return request identification or token.', 'wellness-consult' ), 'Forbidden', array( 'response' => 403 ) );
		}

		// Requirement 4 & 5: Verify that the one-time token exists and matches THIS request ID
		$confirm_data = get_transient( 'wc_confirm_' . $token );

		if ( empty( $confirm_data ) || ! is_array( $confirm_data ) || (int) ( $confirm_data['request_id'] ?? 0 ) !== $post_id ) {
			// Already captured earlier (e.g. the link was opened again after the token expired):
			// show no request data and no new token, just point the patient to Track My Request.
			$already_captured = get_post_meta( $post_id, '_wc_paypal_captured', true );
			if ( $already_captured && WC_Status::STATUS_PAID_AWAITING_SCHEDULING === get_post_meta( $post_id, '_wc_status', true ) ) {
				wp_safe_redirect( home_url( '/track-my-request/' ) );
				exit;
			}

			// Unauthorized token or token from someone else's request
			wp_die( esc_html__( 'Forbidden: Invalid, expired, or mismatched PayPal return token.', 'wellness-consult' ), 'Forbidden', array( 'response' => 403 ) );
		}

		$order_id = get_post_meta( $post_id, '_wc_paypal_order_id', true );

		if ( ! empty( $order_id ) ) {
			// Capture only once (Requirement 5)
			if ( ! get_post_meta( $post_id, '_wc_paypal_captured', true ) ) {
				$capture = WC_Gateway_PayPal::capture_order( $order_id, $post_id );
				if ( is_wp_error( $capture ) ) {
					wp_die( esc_html( $capture->get_error_message() ), 'Payment verification failed', array( 'response' => 400 ) );
				}
			}
		} else {
			wp_die( esc_html__( 'The payment order could not be verified.', 'wellness-consult' ), 'Payment verification failed', array( 'response' => 400 ) );
		}

		$confirm_data['reference_id'] = get_post_meta( $post_id, '_wc_reference_id', true );
		$confirm_data['status']       = get_post_meta( $post_id, '_wc_status', true );
		if ( ! $confirm_data['reference_id'] || WC_Status::STATUS_PAID_AWAITING_SCHEDULING !== $confirm_data['status'] ) {
			wp_die( esc_html__( 'Payment verification is still pending. Your booking is not confirmed yet.', 'wellness-consult' ), 'Payment pending', array( 'response' => 409 ) );
		}
		set_transient( 'wc_confirm_' . $token, $confirm_data, 900 );
		$booking_url = home_url( '/book-consultation/' );
		$redirect_to = add_query_arg( array(
			'wc_confirmed' => 1,
			'token'        => $token,
		), $booking_url );

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Permission check for simulation endpoint (Requirement 6).
	 * Strictly blocked unless WC_ALLOW_MOCK_GATEWAYS is defined as true in wp-config.php.
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public static function check_simulation_permission( $request ) {
		if ( ! defined( 'WC_ALLOW_MOCK_GATEWAYS' ) || true !== WC_ALLOW_MOCK_GATEWAYS ) {
			return false;
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Simulate payment webhook completion (strictly gated by WC_ALLOW_MOCK_GATEWAYS).
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function simulate_webhook( $request ) {
		if ( ! defined( 'WC_ALLOW_MOCK_GATEWAYS' ) || true !== WC_ALLOW_MOCK_GATEWAYS ) {
			return new WP_REST_Response( array( 'error' => 'Mock gateways disabled' ), 403 );
		}

		$gateway   = sanitize_key( $request->get_param( 'gateway' ) );
		$post_id   = absint( $request->get_param( 'request_id' ) );
		$tx_id     = sanitize_text_field( $request->get_param( 'transaction_id' ) );
		$amount    = (float) $request->get_param( 'amount' );

		$post = get_post( $post_id );
		if ( ! $post || 'wc_request' !== $post->post_type ) {
			return new WP_REST_Response( array( 'error' => 'Request not found' ), 404 );
		}

		$expected_fee = (float) get_post_meta( $post_id, '_wc_fee_usd', true );
		$paid_amount  = $amount > 0 ? $amount : $expected_fee;

		// Amount check in simulation too (Requirement 3)
		if ( abs( $paid_amount - $expected_fee ) > 0.01 ) {
			update_post_meta( $post_id, '_wc_payment_mismatch', array(
				'provider'        => $gateway,
				'paid_amount'     => $paid_amount,
				'expected_amount' => $expected_fee,
				'paid_currency'   => 'usd',
				'timestamp'       => current_time( 'mysql', 1 ),
			) );
			WC_Database::log( array(
				'request_id'   => $post_id,
				'reference_id' => get_post_meta( $post_id, '_wc_reference_id', true ),
				'action'       => 'payment_mismatch',
				'note'         => sprintf( 'Simulation amount mismatch! Expected: $%0.2f USD, Received: $%0.2f USD.', $expected_fee, $paid_amount ),
			) );
			return new WP_REST_Response( array( 'error' => 'Payment amount mismatch. Status left unchanged.' ), 400 );
		}

		$provider_id = $tx_id ? $tx_id : 'sim_' . wp_generate_password( 16, false );

		update_post_meta( $post_id, '_wc_payment_provider_id', $provider_id );
		update_post_meta( $post_id, '_wc_amount_received', $paid_amount );
		update_post_meta( $post_id, '_wc_paid_at', current_time( 'mysql', 1 ) );

		$log_note = sprintf(
			'Online payment confirmed via %s Webhook Simulation. Transaction ID: %s. Amount: $%0.2f USD.',
			ucfirst( $gateway ),
			$provider_id,
			$paid_amount
		);

		$transition = WC_Status::change_status( $post_id, WC_Status::STATUS_PAID_AWAITING_SCHEDULING, $log_note );

		return new WP_REST_Response( array(
			'success'     => ! is_wp_error( $transition ),
			'post_id'     => $post_id,
			'status'      => get_post_meta( $post_id, '_wc_status', true ),
			'provider_id' => $provider_id,
		), 200 );
	}
}