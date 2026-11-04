<?php
/**
 * PayPal REST API Gateway Integration (Hardened)
 *
 * Implements WC_Gateway_Interface, PayPal Orders v2, webhook cryptographic verification,
 * idempotency deduplication, server-side amount & currency checking, single-use token binding,
 * and refund ceiling controls (FR-8, FR-12, FR-13).
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Gateway_PayPal implements WC_Gateway_Interface {

	const SANDBOX_BASE = 'https://api-m.sandbox.paypal.com';
	const LIVE_BASE    = 'https://api-m.paypal.com';
	const GATEWAY_ID   = 'paypal';

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
		return __( 'PayPal Secure Checkout', 'wellness-consult' );
	}

	/**
	 * Check if PayPal is enabled and properly configured.
	 *
	 * @return bool
	 */
	public static function is_available() {
		$settings = get_option( 'wc_payment_settings', array() );
		if ( empty( $settings['paypal_enabled'] ) ) {
			return false;
		}

		$client_id = self::get_client_id();
		$secret    = self::get_client_secret();

		return ( ! empty( $client_id ) && ! empty( $secret ) );
	}

	/**
	 * Get Client ID (supports constant and option).
	 *
	 * @return string
	 */
	public static function get_client_id() {
		if ( defined( 'WC_PAYPAL_CLIENT_ID' ) && WC_PAYPAL_CLIENT_ID ) {
			return WC_PAYPAL_CLIENT_ID;
		}
		$settings = get_option( 'wc_payment_settings', array() );
		return $settings['paypal_client_id'] ?? '';
	}

	/**
	 * Get Client Secret (supports constant and decrypted option - Requirement 7).
	 *
	 * @return string
	 */
	public static function get_client_secret() {
		if ( defined( 'WC_PAYPAL_CLIENT_SECRET' ) && WC_PAYPAL_CLIENT_SECRET ) {
			return WC_PAYPAL_CLIENT_SECRET;
		}
		$settings = get_option( 'wc_payment_settings', array() );
		$stored   = $settings['paypal_secret'] ?? '';
		return class_exists( 'WC_Encryption' ) ? WC_Encryption::decrypt( $stored ) : $stored;
	}

	/**
	 * Get Webhook ID (supports constant and option).
	 *
	 * @return string
	 */
	public static function get_webhook_id() {
		if ( defined( 'WC_PAYPAL_WEBHOOK_ID' ) && WC_PAYPAL_WEBHOOK_ID ) {
			return WC_PAYPAL_WEBHOOK_ID;
		}
		$settings = get_option( 'wc_payment_settings', array() );
		return $settings['paypal_webhook_id'] ?? '';
	}

	/**
	 * Get API Base URL depending on environment mode.
	 *
	 * @return string
	 */
	public static function get_api_base() {
		$settings = get_option( 'wc_payment_settings', array() );
		$is_test  = ! empty( $settings['test_mode'] ) && '1' === (string) $settings['test_mode'];
		return $is_test ? self::SANDBOX_BASE : self::LIVE_BASE;
	}

	/**
	 * Obtain OAuth2 Access Token from PayPal API.
	 *
	 * @return string|WP_Error
	 */
	public static function get_access_token() {
		$client_id = self::get_client_id();
		$secret    = self::get_client_secret();

		if ( empty( $client_id ) || empty( $secret ) ) {
			return new WP_Error( 'wc_paypal_missing_keys', esc_html__( 'PayPal API credentials are not configured.', 'wellness-consult' ) );
		}

		// Requirement 6: Mock execution strictly gated
		if ( defined( 'WC_ALLOW_MOCK_GATEWAYS' ) && true === WC_ALLOW_MOCK_GATEWAYS && ( 'mock_client' === $client_id || strpos( $client_id, 'mock' ) !== false ) ) {
			return 'mock_paypal_access_token_' . wp_generate_password( 16, false );
		}

		// Check transient cache
		$cache_key = 'wc_paypal_token_' . md5( $client_id );
		$cached    = get_transient( $cache_key );
		if ( ! empty( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_post( self::get_api_base() . '/v1/oauth2/token', array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'body'    => 'grant_type=client_credentials',
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status || empty( $body['access_token'] ) ) {
			return new WP_Error( 'wc_paypal_token_error', esc_html__( 'Failed to authenticate with PayPal API.', 'wellness-consult' ) );
		}

		$token   = sanitize_text_field( $body['access_token'] );
		$expires = max( 60, (int) ( $body['expires_in'] ?? 3600 ) - 120 );
		set_transient( $cache_key, $token, $expires );

		return $token;
	}

	/**
	 * Interface checkout alias.
	 *
	 * @param int   $post_id
	 * @param array $args
	 * @return array|WP_Error
	 */
	public static function create_checkout( $post_id, $args ) {
		return self::create_order( $post_id, $args );
	}

	/**
	 * Create PayPal Checkout Order and return approval link.
	 *
	 * @param int   $post_id
	 * @param array $args
	 * @return array|WP_Error
	 */
	public static function create_order( $post_id, $args ) {
		$token = self::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$reference_id = get_post_meta( $post_id, '_wc_reference_id', true );
		$fee_usd      = (float) get_post_meta( $post_id, '_wc_fee_usd', true );
		$type_name    = get_post_meta( $post_id, '_wc_type_name', true );
		$consult_mode = get_post_meta( $post_id, '_wc_consult_mode', true );
		$mode_label   = WC_Options::get_mode_label( $consult_mode );

		// Single-use token for return capture (Requirements 4 & 5)
		$confirm_token = wp_generate_password( 32, false );
		set_transient( 'wc_confirm_' . $confirm_token, array(
			'reference_id' => $reference_id,
			'status'       => WC_Status::STATUS_AWAITING_PAYMENT,
			'fee_usd'      => $fee_usd,
			'name'         => $args['name'] ?? '',
			'consult_mode' => $consult_mode,
			'type_name'    => $type_name,
			'request_id'   => $post_id,
		), 1800 );

		// Requirement 6: Mock execution strictly gated
		if ( defined( 'WC_ALLOW_MOCK_GATEWAYS' ) && true === WC_ALLOW_MOCK_GATEWAYS && strpos( $token, 'mock' ) !== false ) {
			$mock_order_id = 'ORDER-TEST-MOCK-' . wp_generate_password( 8, false );
			update_post_meta( $post_id, '_wc_paypal_order_id', $mock_order_id );
			return array(
				'id'  => $mock_order_id,
				'url' => 'https://www.sandbox.paypal.com/checkoutnow?token=' . $mock_order_id,
			);
		}

		$return_url = add_query_arg( array(
			'request_id' => $post_id,
			'token'      => $confirm_token,
		), home_url( '/wp-json/wc-consult/v1/paypal-return' ) );

		$cancel_url = add_query_arg( array(
			'wc_cancelled' => 1,
			'ref'          => $reference_id,
		), home_url( '/book-consultation/' ) );

		$order_payload = array(
			'intent'         => 'CAPTURE',
			'purchase_units' => array(
				array(
					'reference_id' => $reference_id,
					'custom_id'    => (string) $post_id,
					'description'  => sprintf( '%s (%s) - Ref: %s', $type_name, $mode_label, $reference_id ),
					'amount'       => array(
						'currency_code' => 'USD',
						'value'         => number_format( $fee_usd, 2, '.', '' ),
					),
				),
			),
			'application_context' => array(
				'brand_name'          => get_bloginfo( 'name' ),
				'landing_page'        => 'BILLING',
				'user_action'         => 'PAY_NOW',
				'return_url'          => $return_url,
				'cancel_url'          => $cancel_url,
			),
		);

		$response = wp_remote_post( self::get_api_base() . '/v2/checkout/orders', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $order_payload ),
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 201 !== $status || empty( $body['id'] ) ) {
			$err = $body['message'] ?? esc_html__( 'Failed to create PayPal order.', 'wellness-consult' );
			return new WP_Error( 'wc_paypal_order_failed', $err );
		}

		$order_id    = sanitize_text_field( $body['id'] );
		$approve_url = '';

		if ( ! empty( $body['links'] ) && is_array( $body['links'] ) ) {
			foreach ( $body['links'] as $link ) {
				if ( isset( $link['rel'] ) && 'approve' === $link['rel'] ) {
					$approve_url = esc_url_raw( $link['href'] );
					break;
				}
			}
		}

		if ( empty( $approve_url ) ) {
			return new WP_Error( 'wc_paypal_no_approval_link', esc_html__( 'PayPal approval link was not returned.', 'wellness-consult' ) );
		}

		// Store order ID on request post for strict binding (Requirement 4)
		update_post_meta( $post_id, '_wc_paypal_order_id', $order_id );

		return array(
			'id'  => $order_id,
			'url' => $approve_url,
		);
	}

	/**
	 * Capture an approved PayPal order upon return (Requirement 5).
	 *
	 * @param string $order_id
	 * @param int    $post_id
	 * @return array|WP_Error
	 */
	public static function capture_order( $order_id, $post_id ) {
		// Prevent double capture (Requirement 5)
		$already_captured = get_post_meta( $post_id, '_wc_paypal_captured', true );
		$current_status   = get_post_meta( $post_id, '_wc_status', true );
		if ( $already_captured || WC_Status::STATUS_PAID_AWAITING_SCHEDULING === $current_status ) {
			return array( 'already_captured' => true );
		}

		$token = self::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		// Requirement 6: Mock execution strictly gated
		if ( defined( 'WC_ALLOW_MOCK_GATEWAYS' ) && true === WC_ALLOW_MOCK_GATEWAYS && strpos( $token, 'mock' ) !== false ) {
			$mock_capture_id = 'cap_mock_paypal_capture_888';
			$amount_expected = (float) get_post_meta( $post_id, '_wc_fee_usd', true );
			update_post_meta( $post_id, '_wc_payment_provider_id', $mock_capture_id );
			update_post_meta( $post_id, '_wc_amount_received', $amount_expected );
			update_post_meta( $post_id, '_wc_paid_at', current_time( 'mysql', 1 ) );
			update_post_meta( $post_id, '_wc_paypal_captured', 1 );
			WC_Status::change_status( $post_id, WC_Status::STATUS_PAID_AWAITING_SCHEDULING, 'Online payment confirmed via PayPal return' );
			return array( 'status' => 'COMPLETED', 'capture_id' => $mock_capture_id );
		}

		$response = wp_remote_post( self::get_api_base() . '/v2/checkout/orders/' . urlencode( $order_id ) . '/capture', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! in_array( $status, array( 200, 201 ), true ) || empty( $body['status'] ) || 'COMPLETED' !== $body['status'] ) {
			$err = $body['message'] ?? esc_html__( 'PayPal capture failed.', 'wellness-consult' );
			return new WP_Error( 'wc_paypal_capture_failed', $err );
		}

		// Extract capture ID and amount
		$capture_id  = '';
		$paid_amount = 0.0;
		$currency    = 'USD';

		if ( ! empty( $body['purchase_units'][0]['payments']['captures'][0] ) ) {
			$cap_data    = $body['purchase_units'][0]['payments']['captures'][0];
			$capture_id  = sanitize_text_field( $cap_data['id'] ?? '' );
			$paid_amount = (float) ( $cap_data['amount']['value'] ?? 0.0 );
			$currency    = strtoupper( $cap_data['amount']['currency_code'] ?? 'USD' );
		}

		// Amount and currency check (Requirement 3)
		$expected_fee = (float) get_post_meta( $post_id, '_wc_fee_usd', true );
		if ( abs( $paid_amount - $expected_fee ) > 0.01 || 'USD' !== $currency ) {
			update_post_meta( $post_id, '_wc_payment_mismatch', array(
				'provider'        => 'paypal',
				'paid_amount'     => $paid_amount,
				'expected_amount' => $expected_fee,
				'paid_currency'   => $currency,
				'timestamp'       => current_time( 'mysql', 1 ),
			) );
			WC_Database::log( array(
				'request_id'   => $post_id,
				'reference_id' => get_post_meta( $post_id, '_wc_reference_id', true ),
				'action'       => 'payment_mismatch',
				'note'         => sprintf( 'Payment mismatch via PayPal return! Expected: $%0.2f USD, Received: %s %s.', $expected_fee, $paid_amount, $currency ),
			) );
			return new WP_Error( 'wc_payment_mismatch', 'Payment mismatch. Status left unchanged.' );
		}

		update_post_meta( $post_id, '_wc_payment_provider_id', $capture_id );
		update_post_meta( $post_id, '_wc_amount_received', $paid_amount );
		update_post_meta( $post_id, '_wc_paid_at', current_time( 'mysql', 1 ) );
		update_post_meta( $post_id, '_wc_paypal_captured', 1 );

		WC_Status::change_status(
			$post_id,
			WC_Status::STATUS_PAID_AWAITING_SCHEDULING,
			sprintf( 'Online payment captured via PayPal (%s). Amount: $%0.2f USD.', $capture_id, $paid_amount )
		);

		return $body;
	}

	/**
	 * Verify PayPal Webhook Signature Cryptographically (Requirement 1).
	 *
	 * @param WP_REST_Request $request
	 * @param string          $webhook_id
	 * @return true|WP_Error
	 */
	public static function verify_webhook_signature( $request, $webhook_id ) {
		$headers = array(
			'auth_algo'         => $request->get_header( 'paypal-auth-algo' ),
			'cert_url'          => $request->get_header( 'paypal-cert-url' ),
			'transmission_id'   => $request->get_header( 'paypal-transmission-id' ),
			'transmission_sig'  => $request->get_header( 'paypal-transmission-sig' ),
			'transmission_time' => $request->get_header( 'paypal-transmission-time' ),
		);

		// Requirement 1: Missing headers cause 401 rejection
		foreach ( $headers as $k => $v ) {
			if ( empty( $v ) ) {
				return new WP_Error( 'wc_paypal_missing_headers', 'Missing PayPal webhook verification header: ' . $k );
			}
		}

		// Requirement 6: Mock execution strictly gated
		if ( defined( 'WC_ALLOW_MOCK_GATEWAYS' ) && true === WC_ALLOW_MOCK_GATEWAYS && ( 'mock_client' === self::get_client_id() || strpos( $headers['transmission_id'], 'mock' ) !== false ) ) {
			return true;
		}

		$token = self::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$verification_payload = array(
			'auth_algo'         => $headers['auth_algo'],
			'cert_url'          => $headers['cert_url'],
			'transmission_id'   => $headers['transmission_id'],
			'transmission_sig'  => $headers['transmission_sig'],
			'transmission_time' => $headers['transmission_time'],
			'webhook_id'        => $webhook_id,
			'webhook_event'     => json_decode( $request->get_body(), true ),
		);

		$response = wp_remote_post( self::get_api_base() . '/v1/notifications/verify-webhook-signature', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $verification_payload ),
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $body['verification_status'] ) || 'SUCCESS' !== $body['verification_status'] ) {
			return new WP_Error( 'wc_paypal_sig_invalid', 'PayPal webhook signature verification failed' );
		}

		return true;
	}

	/**
	 * Handle PayPal Webhook Notification with Verification & Idempotency (Requirements 1, 2, 3, 4).
	 *
	 * Endpoint: POST /wp-json/wc-consult/v1/paypal-webhook
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function handle_webhook( $request ) {
		$payload    = $request->get_body();
		$event      = json_decode( $payload, true );
		$webhook_id = self::get_webhook_id();

		if ( empty( $event ) || ! is_array( $event ) || empty( $event['id'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid JSON webhook payload' ), 400 );
		}

		$event_id = sanitize_text_field( $event['id'] );

		// Requirement 1: Cryptographic signature verification
		if ( ! empty( $webhook_id ) ) {
			$verified = self::verify_webhook_signature( $request, $webhook_id );
			if ( is_wp_error( $verified ) ) {
				return new WP_REST_Response( array( 'error' => $verified->get_error_message() ), 401 );
			}
		}

		// Requirement 2: Idempotency deduplication
		if ( class_exists( 'WC_Gateway_Stripe' ) && WC_Gateway_Stripe::is_event_processed( $event_id ) ) {
			return new WP_REST_Response( array(
				'received'   => true,
				'idempotent' => true,
				'message'    => 'Event already processed',
			), 200 );
		}

		$event_type = $event['event_type'] ?? '';

		if ( 'PAYMENT.CAPTURE.COMPLETED' === $event_type ) {
			$resource      = $event['resource'] ?? array();
			$capture_id    = sanitize_text_field( $resource['id'] ?? '' );
			$custom_id     = sanitize_text_field( $resource['custom_id'] ?? '' );
			$amount_paid   = (float) ( $resource['amount']['value'] ?? 0.0 );
			$currency_paid = strtoupper( $resource['amount']['currency_code'] ?? 'USD' );

			// Requirement 4: Request Binding
			$req_id = 0;
			if ( is_numeric( $custom_id ) && $custom_id > 0 ) {
				$candidate = get_post( absint( $custom_id ) );
				if ( $candidate && 'wc_request' === $candidate->post_type ) {
					$req_id = $candidate->ID;
				}
			}

			if ( ! $req_id && ! empty( $custom_id ) ) {
				$posts = get_posts( array(
					'post_type'   => 'wc_request',
					'meta_key'    => '_wc_reference_id',
					'meta_value'  => $custom_id,
					'numberposts' => 1,
				) );
				if ( ! empty( $posts ) ) {
					$req_id = $posts[0]->ID;
				}
			}

			if ( $req_id > 0 ) {
				$current_status = get_post_meta( $req_id, '_wc_status', true );
				if ( ! in_array( $current_status, array( WC_Status::STATUS_PAID_AWAITING_SCHEDULING, WC_Status::STATUS_SCHEDULED, WC_Status::STATUS_COMPLETED ), true ) ) {

					// Requirement 3: Amount and currency check
					$expected_fee = (float) get_post_meta( $req_id, '_wc_fee_usd', true );
					if ( abs( $amount_paid - $expected_fee ) > 0.01 || 'USD' !== $currency_paid ) {
						update_post_meta( $req_id, '_wc_payment_mismatch', array(
							'provider'        => 'paypal',
							'paid_amount'     => $amount_paid,
							'expected_amount' => $expected_fee,
							'paid_currency'   => $currency_paid,
							'timestamp'       => current_time( 'mysql', 1 ),
						) );
						WC_Database::log( array(
							'request_id'   => $req_id,
							'reference_id' => get_post_meta( $req_id, '_wc_reference_id', true ),
							'action'       => 'payment_mismatch',
							'from_status'  => $current_status,
							'to_status'    => $current_status,
							'note'         => sprintf( 'Payment mismatch via PayPal Webhook! Expected: $%0.2f USD, Received: %s %s. Status remains awaiting_payment.', $expected_fee, $amount_paid, $currency_paid ),
						) );
						return new WP_REST_Response( array( 'error' => 'Payment amount or currency mismatch' ), 400 );
					}

					update_post_meta( $req_id, '_wc_payment_provider_id', $capture_id );
					update_post_meta( $req_id, '_wc_amount_received', $amount_paid );
					update_post_meta( $req_id, '_wc_paid_at', current_time( 'mysql', 1 ) );

					WC_Status::change_status(
						$req_id,
						WC_Status::STATUS_PAID_AWAITING_SCHEDULING,
						sprintf( 'Online payment verified via PayPal Webhook (%s). Amount: $%0.2f USD.', $capture_id, $amount_paid )
					);
				}
			}
		}

		// Mark event ID as processed
		if ( class_exists( 'WC_Gateway_Stripe' ) ) {
			WC_Gateway_Stripe::mark_event_processed( $event_id );
		}

		return new WP_REST_Response( array( 'received' => true ), 200 );
	}

	/**
	 * Process gateway refund for a PayPal payment with ceiling check (Requirement 10).
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

		$token      = self::get_access_token();
		$capture_id = get_post_meta( $post_id, '_wc_payment_provider_id', true );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		if ( empty( $capture_id ) ) {
			return new WP_Error( 'wc_paypal_no_capture', esc_html__( 'No PayPal capture reference found for this consultation request.', 'wellness-consult' ) );
		}

		// Requirement 6: Mock execution strictly gated
		if ( defined( 'WC_ALLOW_MOCK_GATEWAYS' ) && true === WC_ALLOW_MOCK_GATEWAYS && ( strpos( $token, 'mock' ) !== false || strpos( $capture_id, 'sim_' ) === 0 || strpos( $capture_id, 'cap_mock' ) === 0 ) ) {
			$mock_refund_id = 'REFUND-TEST-MOCK-' . wp_generate_password( 8, false );
			update_post_meta( $post_id, '_wc_refund_id', $mock_refund_id );
			update_post_meta( $post_id, '_wc_refunded_at', current_time( 'mysql', 1 ) );
			return true;
		}

		$refund_payload = array();
		if ( ! empty( $amount ) && (float) $amount > 0 ) {
			$refund_payload['amount'] = array(
				'currency_code' => 'USD',
				'value'         => number_format( (float) $amount, 2, '.', '' ),
			);
		}

		if ( ! empty( $reason ) ) {
			$refund_payload['note_to_payer'] = sanitize_text_field( $reason );
		}

		$response = wp_remote_post( self::get_api_base() . '/v2/payments/captures/' . urlencode( $capture_id ) . '/refund', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $refund_payload ),
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 201 !== $status ) {
			$err = $body['message'] ?? esc_html__( 'PayPal refund failed.', 'wellness-consult' );
			return new WP_Error( 'wc_paypal_refund_failed', $err );
		}

		$refund_id = sanitize_text_field( $body['id'] );
		update_post_meta( $post_id, '_wc_refund_id', $refund_id );
		update_post_meta( $post_id, '_wc_refunded_at', current_time( 'mysql', 1 ) );

		return true;
	}
}
