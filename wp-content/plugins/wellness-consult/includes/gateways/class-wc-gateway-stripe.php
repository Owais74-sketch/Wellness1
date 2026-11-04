<?php
/**
 * Stripe Checkout Gateway Integration (Hardened)
 *
 * Implements WC_Gateway_Interface, hosted Stripe Checkout sessions,
 * cryptographic webhook verification with replay protection, idempotency deduplication,
 * server-side amount & currency verification, request binding, and refund controls (FR-8, FR-12, FR-13).
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Gateway_Stripe implements WC_Gateway_Interface {

	const STRIPE_API_BASE   = 'https://api.stripe.com/v1';
	const GATEWAY_ID        = 'stripe';
	const REPLAY_TOLERANCE  = 300; // 5-minute replay tolerance window

	/**
	 * Unique gateway identifier.
	 *
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}

	/**
	 * Gateway display name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Credit / Debit Card (Stripe Checkout)', 'wellness-consult' );
	}

	/**
	 * Check if Stripe is enabled with valid API keys.
	 *
	 * @return bool
	 */
	public static function is_available() {
		$settings = get_option( 'wc_payment_settings', array() );
		if ( empty( $settings['stripe_enabled'] ) ) {
			return false;
		}

		$sk = self::get_secret_key();
		$pk = self::get_publishable_key();

		return ( ! empty( $sk ) && ! empty( $pk ) );
	}

	/**
	 * Get appropriate Secret Key based on environment mode.
	 * Supports wp-config.php constant overrides and AES-256 decrypted options (Requirement 7).
	 *
	 * @return string
	 */
	public static function get_secret_key() {
		$settings = get_option( 'wc_payment_settings', array() );
		$is_test  = ! empty( $settings['test_mode'] ) && '1' === (string) $settings['test_mode'];

		if ( $is_test ) {
			if ( defined( 'WC_STRIPE_TEST_SK' ) && WC_STRIPE_TEST_SK ) {
				return WC_STRIPE_TEST_SK;
			}
			$stored = $settings['stripe_test_sk'] ?? '';
			return class_exists( 'WC_Encryption' ) ? WC_Encryption::decrypt( $stored ) : $stored;
		}

		if ( defined( 'WC_STRIPE_LIVE_SK' ) && WC_STRIPE_LIVE_SK ) {
			return WC_STRIPE_LIVE_SK;
		}
		$stored = $settings['stripe_live_sk'] ?? '';
		return class_exists( 'WC_Encryption' ) ? WC_Encryption::decrypt( $stored ) : $stored;
	}

	/**
	 * Get appropriate Publishable Key based on environment mode.
	 *
	 * @return string
	 */
	public static function get_publishable_key() {
		$settings = get_option( 'wc_payment_settings', array() );
		$is_test  = ! empty( $settings['test_mode'] ) && '1' === (string) $settings['test_mode'];

		if ( $is_test ) {
			if ( defined( 'WC_STRIPE_TEST_PK' ) && WC_STRIPE_TEST_PK ) {
				return WC_STRIPE_TEST_PK;
			}
			return $settings['stripe_test_pk'] ?? '';
		}

		if ( defined( 'WC_STRIPE_LIVE_PK' ) && WC_STRIPE_LIVE_PK ) {
			return WC_STRIPE_LIVE_PK;
		}
		return $settings['stripe_live_pk'] ?? '';
	}

	/**
	 * Get Stripe Webhook Secret (supports constant and decrypted option).
	 *
	 * @return string
	 */
	public static function get_webhook_secret() {
		if ( defined( 'WC_STRIPE_WEBHOOK_SECRET' ) && WC_STRIPE_WEBHOOK_SECRET ) {
			return WC_STRIPE_WEBHOOK_SECRET;
		}
		$settings = get_option( 'wc_payment_settings', array() );
		$stored   = $settings['stripe_webhook_secret'] ?? '';
		return class_exists( 'WC_Encryption' ) ? WC_Encryption::decrypt( $stored ) : $stored;
	}

	/**
	 * Interface checkout alias.
	 *
	 * @param int   $post_id
	 * @param array $args
	 * @return array|WP_Error
	 */
	public static function create_checkout( $post_id, $args ) {
		return self::create_checkout_session( $post_id, $args );
	}

	/**
	 * Create a hosted Stripe Checkout Session.
	 *
	 * @param int   $post_id Consultation Request Post ID.
	 * @param array $args    Submission data.
	 * @return array|WP_Error Array with 'id' and 'url', or WP_Error.
	 */
	public static function create_checkout_session( $post_id, $args ) {
		$secret_key = self::get_secret_key();
		if ( empty( $secret_key ) ) {
			return new WP_Error( 'wc_stripe_missing_key', esc_html__( 'Stripe API keys are not configured.', 'wellness-consult' ) );
		}

		// Requirement 6: Mock gateways only execute when WC_ALLOW_MOCK_GATEWAYS is true in wp-config.php
		if ( defined( 'WC_ALLOW_MOCK_GATEWAYS' ) && true === WC_ALLOW_MOCK_GATEWAYS && ( 'sk_test_mock' === $secret_key || strpos( $secret_key, 'mock' ) !== false ) ) {
			$mock_id = 'cs_test_mock_' . wp_generate_password( 16, false );
			update_post_meta( $post_id, '_wc_stripe_session_id', $mock_id );
			return array(
				'id'  => $mock_id,
				'url' => 'https://checkout.stripe.com/c/pay/' . $mock_id,
			);
		}

		$reference_id = get_post_meta( $post_id, '_wc_gateway_reference', true );
		$fee_usd      = (float) get_post_meta( $post_id, '_wc_fee_usd', true );
		$type_name    = get_post_meta( $post_id, '_wc_type_name', true );
		$consult_mode = get_post_meta( $post_id, '_wc_consult_mode', true );
		$mode_label   = WC_Options::get_mode_label( $consult_mode );

		$unit_amount = (int) round( $fee_usd * 100 ); // In cents
		if ( $unit_amount <= 0 ) {
			return new WP_Error( 'wc_stripe_invalid_fee', esc_html__( 'Invalid consultation fee amount.', 'wellness-consult' ) );
		}

		$booking_page_url = home_url( '/book-consultation/' );
		$success_url      = add_query_arg( 'request_id', absint( $post_id ), rest_url( 'wc-consult/v1/stripe-return' ) ) . '&session_id={CHECKOUT_SESSION_ID}';

		$cancel_url = add_query_arg( 'wc_payment_cancelled', 1, $booking_page_url );

		$session_params = array(
			'payment_method_types'                                 => array( 'card' ),
			'mode'                                                 => 'payment',
			'client_reference_id'                                  => $reference_id,
			'line_items[0][price_data][currency]'                  => 'usd',
			'line_items[0][price_data][unit_amount]'               => $unit_amount,
			'line_items[0][price_data][product_data][name]'        => sprintf( '%s (%s)', $type_name, $mode_label ),
			'line_items[0][price_data][product_data][description]' => sprintf( 'Private consultation session (%s)', $mode_label ),
			'line_items[0][quantity]'                              => 1,
			'metadata[request_id]'                                 => (string) $post_id,
			'metadata[reference_id]'                               => (string) $reference_id,
			'metadata[consult_mode]'                               => (string) $consult_mode,
			'success_url'                                          => $success_url,
			'cancel_url'                                           => $cancel_url,
		);

		if ( ! empty( $args['email'] ) ) {
			$session_params['customer_email'] = sanitize_email( $args['email'] );
		}

		$response = wp_remote_post( self::STRIPE_API_BASE . '/checkout/sessions', array(
			'headers' => array(
				'Authorization'  => 'Bearer ' . $secret_key,
				'Content-Type'   => 'application/x-www-form-urlencoded',
				'Stripe-Version' => '2023-10-16',
			),
			'body'    => $session_params,
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code || empty( $body['url'] ) ) {
			$error_message = $body['error']['message'] ?? esc_html__( 'Unable to initiate Stripe checkout session.', 'wellness-consult' );
			return new WP_Error( 'wc_stripe_api_error', $error_message );
		}

		// Store session ID on request post for strict binding (Requirement 4)
		update_post_meta( $post_id, '_wc_stripe_session_id', sanitize_text_field( $body['id'] ) );

		return array(
			'id'  => sanitize_text_field( $body['id'] ),
			'url' => esc_url_raw( $body['url'] ),
		);
	}

	/**
	 * Handle Stripe Webhook Notification with Signature, Replay & Idempotency Controls (Requirements 1 & 2).
	 *
	 * Endpoint: POST /wp-json/wc-consult/v1/stripe-webhook
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function handle_webhook( $request ) {
		$payload        = $request->get_body();
		$sig_header     = $request->get_header( 'stripe-signature' );
		$webhook_secret = self::get_webhook_secret();

		// Requirement 1: Missing signature header strictly rejected
		if ( empty( $sig_header ) ) {
			return new WP_REST_Response( array( 'error' => 'Missing stripe-signature header' ), 400 );
		}

		if ( empty( $webhook_secret ) ) {
			return new WP_REST_Response( array( 'error' => 'Stripe webhook secret is not configured' ), 400 );
		}

		// Requirement 1: Cryptographic signature verification with tolerance check
		$sig_check = self::verify_signature( $payload, $sig_header, $webhook_secret );
		if ( is_wp_error( $sig_check ) ) {
			return new WP_REST_Response( array( 'error' => $sig_check->get_error_message() ), 400 );
		}

		$event = json_decode( $payload, true );
		if ( empty( $event ) || ! is_array( $event ) || empty( $event['id'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid JSON webhook payload' ), 400 );
		}

		$event_id = sanitize_text_field( $event['id'] );

		// Requirement 2: Idempotency deduplication
		if ( self::is_event_processed( $event_id ) ) {
			return new WP_REST_Response( array(
				'received'   => true,
				'idempotent' => true,
				'message'    => 'Event already processed',
			), 200 );
		}

		$event_type = $event['type'] ?? '';

		if ( 'checkout.session.completed' === $event_type ) {
			$session = $event['data']['object'] ?? array();
			$result  = self::process_completed_session( $session );
			if ( is_wp_error( $result ) ) {
				// Payment mismatch or invalid binding does not mark event as processed
				return new WP_REST_Response( array(
					'error'   => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				), 400 );
			}
		}

		// Mark event ID as processed
		self::mark_event_processed( $event_id );

		return new WP_REST_Response( array( 'received' => true ), 200 );
	}

	/**
	 * Verify Stripe Webhook Signature Header against replay attacks (Requirement 1).
	 *
	 * @param string $payload
	 * @param string $sig_header
	 * @param string $secret
	 * @return true|WP_Error
	 */
	public static function verify_signature( $payload, $sig_header, $secret ) {
		$items      = explode( ',', $sig_header );
		$timestamp  = null;
		$signatures = array();

		foreach ( $items as $item ) {
			$parts = explode( '=', trim( $item ), 2 );
			if ( 2 === count( $parts ) ) {
				if ( 't' === $parts[0] ) {
					$timestamp = $parts[1];
				} elseif ( 'v1' === $parts[0] ) {
					$signatures[] = $parts[1];
				}
			}
		}

		if ( empty( $timestamp ) || empty( $signatures ) ) {
			return new WP_Error( 'wc_stripe_invalid_header', 'Malformed stripe-signature header' );
		}

		// Requirement 1: Tolerance window check against replay of old events
		if ( abs( time() - (int) $timestamp ) > self::REPLAY_TOLERANCE ) {
			return new WP_Error( 'wc_stripe_timestamp_expired', 'Webhook signature timestamp outside tolerance window' );
		}

		$signed_payload = $timestamp . '.' . $payload;
		$expected_sig   = hash_hmac( 'sha256', $signed_payload, $secret );

		foreach ( $signatures as $sig ) {
			if ( hash_equals( $expected_sig, $sig ) ) {
				return true;
			}
		}

		return new WP_Error( 'wc_stripe_sig_mismatch', 'Webhook signature verification failed' );
	}

	/**
	 * Check if a webhook event ID was already processed (Requirement 2).
	 *
	 * @param string $event_id
	 * @return bool
	 */
	public static function is_event_processed( $event_id ) {
		$processed = get_option( 'wc_processed_webhook_events', array() );
		return isset( $processed[ $event_id ] );
	}

	/**
	 * Record a processed webhook event ID.
	 *
	 * @param string $event_id
	 */
	public static function mark_event_processed( $event_id ) {
		$processed = get_option( 'wc_processed_webhook_events', array() );
		$processed[ $event_id ] = time();

		// Keep only the last 1000 event IDs to prevent unbounded option growth
		if ( count( $processed ) > 1000 ) {
			$processed = array_slice( $processed, -1000, 1000, true );
		}

		update_option( 'wc_processed_webhook_events', $processed, false );
	}

	/**
	 * Process a completed Stripe checkout session with binding & amount checks (Requirements 3 & 4).
	 *
	 * @param array $session
	 * @return true|WP_Error
	 */
	public static function process_completed_session( $session ) {
		$session_id  = sanitize_text_field( $session['id'] ?? '' );
		$client_ref  = sanitize_text_field( $session['client_reference_id'] ?? '' );
		$meta_req_id = absint( $session['metadata']['request_id'] ?? 0 );
		$metadata_ref = sanitize_text_field( $session['metadata']['reference_id'] ?? '' );
		if ( empty( $session_id ) || empty( $client_ref ) || ! hash_equals( $client_ref, $metadata_ref ) || 'paid' !== ( $session['payment_status'] ?? '' ) ) {
			return new WP_Error( 'wc_stripe_payment_unverified', 'Stripe session is not paid or its request reference does not match.' );
		}

		// Requirement 4: Request Binding Verification
		$req_id = 0;
		if ( $meta_req_id > 0 ) {
			$post = get_post( $meta_req_id );
			if ( $post && 'wc_request' === $post->post_type ) {
				$stored_session = get_post_meta( $meta_req_id, '_wc_stripe_session_id', true );
				$stored_ref     = get_post_meta( $meta_req_id, '_wc_gateway_reference', true );
				if ( empty( $stored_ref ) ) {
					$stored_ref = get_post_meta( $meta_req_id, '_wc_reference_id', true );
				}

				// Validate session ID or client reference binds to this post
				if ( ! empty( $stored_ref ) && $stored_ref === $client_ref && ( empty( $stored_session ) || $stored_session === $session_id ) ) {
					$req_id = $meta_req_id;
				}
			}
		}

		if ( ! $req_id && ! empty( $client_ref ) ) {
			$posts = get_posts( array(
				'post_type'   => 'wc_request',
				'meta_key'    => '_wc_gateway_reference',
				'meta_value'  => $client_ref,
				'numberposts' => 1,
			) );
			if ( empty( $posts ) ) {
				$posts = get_posts( array(
					'post_type'   => 'wc_request',
					'meta_key'    => '_wc_reference_id',
					'meta_value'  => $client_ref,
					'numberposts' => 1,
				) );
			}
			if ( ! empty( $posts ) ) {
				$candidate_id   = $posts[0]->ID;
				$stored_session = get_post_meta( $candidate_id, '_wc_stripe_session_id', true );
				$candidate_ref = get_post_meta( $candidate_id, '_wc_gateway_reference', true );
				if ( empty( $candidate_ref ) ) {
					$candidate_ref = get_post_meta( $candidate_id, '_wc_reference_id', true );
				}
				if ( $candidate_ref === $client_ref && ( empty( $stored_session ) || $stored_session === $session_id ) ) {
					$req_id = $candidate_id;
				}
			}
		}

		if ( ! $req_id ) {
			return new WP_Error( 'wc_binding_failed', 'Request binding failed: No matching consultation request found for provider reference.' );
		}

		$current_status = get_post_meta( $req_id, '_wc_status', true );
		if ( in_array( $current_status, array( WC_Status::STATUS_PAID_AWAITING_SCHEDULING, WC_Status::STATUS_SCHEDULED, WC_Status::STATUS_COMPLETED ), true ) ) {
			// Already confirmed
			return true;
		}

		// Requirement 3: Amount and Currency Verification
		$expected_fee  = (float) get_post_meta( $req_id, '_wc_fee_usd', true );
		$paid_amount   = isset( $session['amount_total'] ) ? ( (float) $session['amount_total'] ) / 100 : 0.0;
		$paid_currency = strtolower( $session['currency'] ?? '' );

		$currency_mismatch = ( 'usd' !== $paid_currency );
		$amount_mismatch   = ( abs( $paid_amount - $expected_fee ) > 0.01 );

		if ( $currency_mismatch || $amount_mismatch ) {
			// Do NOT transition to paid_awaiting_scheduling
			update_post_meta( $req_id, '_wc_payment_mismatch', array(
				'provider'        => 'stripe',
				'paid_amount'     => $paid_amount,
				'expected_amount' => $expected_fee,
				'paid_currency'   => $paid_currency,
				'expected_currency'=> 'usd',
				'timestamp'       => current_time( 'mysql', 1 ),
			) );
			update_post_meta( $req_id, '_wc_admin_alert_mismatch', 1 );

			WC_Database::log( array(
				'request_id'   => $req_id,
				'reference_id' => get_post_meta( $req_id, '_wc_reference_id', true ),
				'action'       => 'payment_mismatch',
				'from_status'  => $current_status,
				'to_status'    => $current_status,
				'note'         => sprintf(
					'Payment mismatch detected via Stripe! Expected: $%0.2f USD, Received: %s %s. Status remains awaiting_payment.',
					$expected_fee,
					number_format( $paid_amount, 2 ),
					strtoupper( $paid_currency )
				),
			) );

			return new WP_Error( 'wc_payment_mismatch', 'Payment amount or currency mismatch. Status left unchanged.' );
		}

		// Save verified payment metadata
		$payment_intent = sanitize_text_field( $session['payment_intent'] ?? ( $session['id'] ?? '' ) );
		delete_post_meta( $req_id, '_wc_admin_alert_mismatch' );
		return WC_Booking_Handler::confirm_online_payment( $req_id, $payment_intent, $paid_amount, $paid_currency );
	}

	/**
	 * Verify a browser return directly against Stripe before showing confirmation.
	 *
	 * @param WP_REST_Request $request Return request.
	 */
	public static function handle_verified_return( $request ) {
		$request_id = absint( $request->get_param( 'request_id' ) );
		$session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
		$secret_key = self::get_secret_key();
		if ( ! $request_id || empty( $session_id ) || empty( $secret_key ) ) {
			wp_die( esc_html__( 'The payment return could not be verified.', 'wellness-consult' ), 'Payment verification failed', array( 'response' => 400 ) );
		}

		$response = wp_remote_get( self::STRIPE_API_BASE . '/checkout/sessions/' . rawurlencode( $session_id ), array(
			'headers' => array(
				'Authorization'  => 'Bearer ' . $secret_key,
				'Stripe-Version' => '2023-10-16',
			),
			'timeout' => 20,
		) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			wp_die( esc_html__( 'The payment provider could not verify your payment. Please try again shortly.', 'wellness-consult' ), 'Payment verification pending', array( 'response' => 502 ) );
		}

		$session = json_decode( wp_remote_retrieve_body( $response ), true );
		$reference = get_post_meta( $request_id, '_wc_gateway_reference', true );
		if ( empty( $reference ) ) {
			$reference = get_post_meta( $request_id, '_wc_reference_id', true );
		}
		$expected = (float) get_post_meta( $request_id, '_wc_fee_usd', true );
		$amount   = isset( $session['amount_total'] ) ? (float) $session['amount_total'] / 100 : 0.0;
		$metadata = $session['metadata'] ?? array();
		if ( empty( $session['id'] ) || $session['id'] !== $session_id
			|| (int) ( $metadata['request_id'] ?? 0 ) !== $request_id
			|| empty( $reference )
			|| ! hash_equals( (string) $reference, (string) ( $session['client_reference_id'] ?? '' ) )
			|| ! hash_equals( (string) $reference, (string) ( $metadata['reference_id'] ?? '' ) )
			|| (string) get_post_meta( $request_id, '_wc_stripe_session_id', true ) !== $session_id
			|| 'paid' !== ( $session['payment_status'] ?? '' )
			|| 'usd' !== strtolower( $session['currency'] ?? '' )
			|| abs( $amount - $expected ) > 0.01
		) {
			wp_die( esc_html__( 'Payment verification is still pending. Your booking is not confirmed yet.', 'wellness-consult' ), 'Payment pending', array( 'response' => 409 ) );
		}

		$payment_intent = sanitize_text_field( $session['payment_intent'] ?? $session_id );
		$result = WC_Booking_Handler::confirm_online_payment( $request_id, $payment_intent, $amount, 'USD' );
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ), 'Payment verification failed', array( 'response' => 409 ) );
		}
		$token = WC_Booking_Handler::create_confirmation_view_token( $request_id );
		wp_safe_redirect( add_query_arg(
			array( 'wc_confirmed' => 1, 'token' => $token ),
			home_url( '/book-consultation/' )
		) );
		exit;
	}

	/**
	 * Process gateway refund for a Stripe payment with ceiling check (Requirement 10).
	 *
	 * @param int        $post_id
	 * @param float|null $amount
	 * @param string     $reason
	 * @return true|WP_Error
	 */
	public static function refund( $post_id, $amount = null, $reason = '' ) {
		// Requirement 10: Partial refund cannot exceed total amount paid
		$amount_paid = (float) get_post_meta( $post_id, '_wc_amount_received', true );
		if ( $amount_paid <= 0 ) {
			$amount_paid = (float) get_post_meta( $post_id, '_wc_fee_usd', true );
		}

		if ( ! empty( $amount ) && (float) $amount > $amount_paid ) {
			return new WP_Error(
				'wc_refund_amount_exceeded',
				sprintf(
					esc_html__( 'Refund amount ($%0.2f USD) cannot exceed the total amount paid ($%0.2f USD).', 'wellness-consult' ),
					(float) $amount,
					$amount_paid
				)
			);
		}

		$secret_key     = self::get_secret_key();
		$payment_intent = get_post_meta( $post_id, '_wc_payment_provider_id', true );

		if ( empty( $secret_key ) || empty( $payment_intent ) ) {
			return new WP_Error( 'wc_stripe_refund_error', esc_html__( 'Missing Stripe credentials or payment transaction reference.', 'wellness-consult' ) );
		}

		// Requirement 6: Mock execution strictly gated
		if ( defined( 'WC_ALLOW_MOCK_GATEWAYS' ) && true === WC_ALLOW_MOCK_GATEWAYS && ( 'sk_test_mock' === $secret_key || strpos( $secret_key, 'mock' ) !== false || strpos( $payment_intent, 'sim_' ) === 0 || strpos( $payment_intent, 'cs_test_mock' ) === 0 || strpos( $payment_intent, 'pi_mock' ) === 0 ) ) {
			$mock_ref_id = 're_test_mock_' . wp_generate_password( 12, false );
			update_post_meta( $post_id, '_wc_refund_id', $mock_ref_id );
			update_post_meta( $post_id, '_wc_refunded_at', current_time( 'mysql', 1 ) );
			return true;
		}

		$refund_params = array(
			'payment_intent' => $payment_intent,
		);

		if ( ! empty( $amount ) && (float) $amount > 0 ) {
			$refund_params['amount'] = (int) round( (float) $amount * 100 );
		}

		if ( ! empty( $reason ) ) {
			$refund_params['metadata[refund_reason]'] = sanitize_text_field( $reason );
		}

		$response = wp_remote_post( self::STRIPE_API_BASE . '/refunds', array(
			'headers' => array(
				'Authorization'  => 'Bearer ' . $secret_key,
				'Content-Type'   => 'application/x-www-form-urlencoded',
				'Stripe-Version' => '2023-10-16',
			),
			'body'    => $refund_params,
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code || empty( $body['id'] ) ) {
			$err = $body['error']['message'] ?? esc_html__( 'Stripe refund failed.', 'wellness-consult' );
			return new WP_Error( 'wc_stripe_refund_failed', $err );
		}

		$refund_id = sanitize_text_field( $body['id'] );
		update_post_meta( $post_id, '_wc_refund_id', $refund_id );
		update_post_meta( $post_id, '_wc_refunded_at', current_time( 'mysql', 1 ) );

		return true;
	}
}
