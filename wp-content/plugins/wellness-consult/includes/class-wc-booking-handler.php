<?php
/**
 * Booking Submission Handler & POST-Redirect-GET Controller
 *
 * Implements Rule 3: Honeypot, nonces, rate limiting, atomic reference ID,
 * server-side fee verification, consent logging, and single-use confirmation token.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Booking_Handler {

	/**
	 * Transient prefix for flash errors.
	 */
	const FLASH_KEY = 'wc_booking_flash_';

	/**
	 * Initialize submission listener on init.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'process_submission' ) );
	}

	/**
	 * Get client session/cookie ID for flash messaging.
	 *
	 * @return string
	 */
	protected static function get_client_token() {
		if ( ! empty( $_COOKIE['wc_client_token'] ) ) {
			return sanitize_key( $_COOKIE['wc_client_token'] );
		}
		$token = wp_generate_password( 24, false );
		if ( ! headers_sent() ) {
			setcookie( 'wc_client_token', $token, time() + 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
		$_COOKIE['wc_client_token'] = $token;
		return $token;
	}

	/**
	 * Store flash errors for redirect.
	 *
	 * @param array $errors
	 * @param array $data
	 */
	public static function set_flash( $errors = array(), $data = array() ) {
		$token = self::get_client_token();
		set_transient( self::FLASH_KEY . $token, array(
			'errors' => $errors,
			'data'   => $data,
		), 300 );
	}

	/**
	 * Retrieve and clear flash errors.
	 *
	 * @return array
	 */
	public static function get_flash_errors() {
		$token = self::get_client_token();
		$flash = get_transient( self::FLASH_KEY . $token );
		if ( ! empty( $flash['errors'] ) && is_array( $flash['errors'] ) ) {
			return $flash['errors'];
		}
		return array();
	}

	/**
	 * Retrieve and clear flash input data.
	 *
	 * @return array
	 */
	public static function get_flash_data() {
		$token = self::get_client_token();
		$flash = get_transient( self::FLASH_KEY . $token );
		if ( ! empty( $flash['data'] ) && is_array( $flash['data'] ) ) {
			delete_transient( self::FLASH_KEY . $token );
			return $flash['data'];
		}
		return array();
	}

	/**
	 * Process booking form submission.
	 */
	public static function process_submission() {
		if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['wc_booking_submit'] ) ) {
			return;
		}

		// 1. Nonce Verification
		if ( ! isset( $_POST['wc_booking_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wc_booking_nonce'] ), 'wc_submit_booking_action' ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'wellness-consult' ), 'Forbidden', array( 'response' => 403 ) );
		}

		// 2. Honeypot check (hidden field with tabindex -1)
		if ( ! empty( $_POST['wc_hp_gate'] ) ) {
			wp_die( esc_html__( 'Spam submission detected.', 'wellness-consult' ), 'Forbidden', array( 'response' => 400 ) );
		}

		// 3. Rate Limit check: 3 submissions per IP per hour (Rule 3)
		$raw_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		$ip_hash = hash_hmac( 'sha256', $raw_ip, wp_salt() );
		$rate_transient_key = 'wc_rate_limit_' . substr( $ip_hash, 0, 32 );
		$submission_count   = (int) get_transient( $rate_transient_key );

		if ( $submission_count >= 3 ) {
			$errors = array( esc_html__( 'Too many booking submissions from this network. Please wait an hour before trying again.', 'wellness-consult' ) );
			self::set_flash( $errors, $_POST );
			self::safe_redirect_and_exit( wp_get_referer() ? wp_get_referer() : home_url( '/book-consultation/' ) );
			return;
		}

		// 4. Idempotency Token check (prevent duplicate submissions)
		$idempotency_token = ! empty( $_POST['wc_idempotency_token'] ) ? sanitize_key( $_POST['wc_idempotency_token'] ) : '';
		if ( ! empty( $idempotency_token ) ) {
			$existing_ref = get_transient( 'wc_idem_' . $idempotency_token );
			if ( $existing_ref ) {
				// Already processed, redirect to confirmation directly
				$token = wp_generate_password( 24, false );
				set_transient( 'wc_confirm_' . $token, $existing_ref, 900 );
				$target_url = add_query_arg( array(
					'wc_confirmed' => 1,
					'token'        => $token,
				), wp_get_referer() ? wp_get_referer() : home_url( '/book-consultation/' ) );
				self::safe_redirect_and_exit( $target_url );
				return;
			}
		}

		// Sanitize Inputs
		$type_id       = sanitize_key( wp_unslash( $_POST['consultation_type'] ?? '' ) );
		$consult_mode  = sanitize_key( wp_unslash( $_POST['consult_mode'] ?? '' ) );
		$method_id     = sanitize_key( wp_unslash( $_POST['payment_method_id'] ?? '' ) );
		$name          = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$age           = absint( wp_unslash( $_POST['age'] ?? 0 ) );
		$gender        = sanitize_text_field( wp_unslash( $_POST['gender'] ?? '' ) );
		$country       = strtoupper( sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ) );
		$timezone      = sanitize_text_field( wp_unslash( $_POST['timezone'] ?? 'UTC' ) );
		$phone         = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$email         = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$concern       = sanitize_text_field( wp_unslash( $_POST['concern'] ?? '' ) );
		$message       = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$pref_window   = sanitize_text_field( wp_unslash( $_POST['preferred_window'] ?? 'morning' ) );
		$trans_id      = sanitize_text_field( wp_unslash( $_POST['transaction_id'] ?? '' ) );
		$consent       = isset( $_POST['consent_agreed'] ) && '1' === (string) $_POST['consent_agreed'];

		if ( WC_Fraud_Protection::is_blocked( $email, $phone, $country ) ) {
			WC_Database::log( array(
				'action' => 'blocked_booking_attempt',
				'note'   => 'Booking submission matched a configured contact or country block.',
			) );
			self::set_flash( array( esc_html__( 'We cannot accept this booking.', 'wellness-consult' ) ), array() );
			self::safe_redirect_and_exit( wp_get_referer() ? wp_get_referer() : home_url( '/book-consultation/' ) );
			return;
		}

		$input_data = array(
			'consultation_type' => $type_id,
			'consult_mode'      => $consult_mode,
			'payment_method_id' => $method_id,
			'name'              => $name,
			'age'               => $age > 0 ? $age : '',
			'gender'            => $gender,
			'country'           => $country,
			'timezone'          => $timezone,
			'phone'             => $phone,
			'email'             => $email,
			'concern'           => $concern,
			'message'           => $message,
			'preferred_window'  => $pref_window,
			'transaction_id'    => $trans_id,
		);

		$errors = array();

		// 5. Server-Side Validation: Consultation Type & Mode (CR-1) & Server-Side Fee Verification
		$all_types   = get_option( 'wc_consultation_types', array() );
		$valid_modes = array( 'chat', 'voice', 'video' );
		$fee_usd     = 0.0;
		$type_name   = '';

		if ( empty( $type_id ) || ! isset( $all_types[ $type_id ] ) || empty( $all_types[ $type_id ]['is_active'] ) ) {
			$errors[] = esc_html__( 'Please select a valid, active consultation package.', 'wellness-consult' );
		} elseif ( empty( $consult_mode ) || ! in_array( $consult_mode, $valid_modes, true ) ) {
			$errors[] = esc_html__( 'Please select a consultation mode (Chat, Voice Call, or Video Call).', 'wellness-consult' );
		} else {
			$selected_type = $all_types[ $type_id ];
			$enable_key    = 'enable_' . $consult_mode;
			$fee_key       = 'fee_' . $consult_mode;

			// Validate mode is enabled for this package (CR-1 Rule 1 & 3)
			if ( empty( $selected_type[ $enable_key ] ) ) {
				$errors[] = esc_html__( 'The selected consultation mode is not available for this package.', 'wellness-consult' );
			} else {
				// Read fee strictly from server configuration based on selected mode (Rule 1 & CR-1)
				$fee_usd   = isset( $selected_type[ $fee_key ] ) ? (float) $selected_type[ $fee_key ] : 0.0;
				$type_name = $selected_type['name'];
			}
		}

		// 6. Server-Side Validation: Payment Method (Online Gateways & Manual Transfers)
		$manual_methods = get_option( 'wc_manual_methods', array() );
		$is_stripe = ( 'stripe' === $method_id );
		$is_paypal = ( 'paypal' === $method_id );
		$is_online = ( $is_stripe || $is_paypal );
		$is_manual = isset( $manual_methods[ $method_id ] ) && ! empty( $manual_methods[ $method_id ]['active'] );

		if ( $is_online ) {
			$errors[] = esc_html__( 'Online card and PayPal payments are not available in this update. Please choose a manual payment method.', 'wellness-consult' );
		} elseif ( ! $is_manual ) {
			$errors[] = esc_html__( 'Please select an active payment method.', 'wellness-consult' );
		} elseif ( ! empty( $manual_methods[ $method_id ]['allowed_countries'] ) && ! in_array( $country, (array) $manual_methods[ $method_id ]['allowed_countries'], true ) ) {
			$errors[] = esc_html__( 'The selected payment method is not available in your country of residence.', 'wellness-consult' );
		}
		if ( $is_manual && empty( $trans_id ) ) {
			$errors[] = esc_html__( 'For manual payments, enter a transaction ID or the sender name.', 'wellness-consult' );
		}

		// 7. Age 18+ Rule (Rule 1 & SRS Section 10)
		if ( empty( $age ) ) {
			$errors[] = esc_html__( 'Age is required.', 'wellness-consult' );
		} elseif ( $age < 18 ) {
			$errors[] = esc_html__( 'Consultations are strictly for adults aged 18 and older. Submissions under 18 are rejected.', 'wellness-consult' );
		}

		// 8. Name or Alias
		if ( empty( $name ) ) {
			$errors[] = esc_html__( 'Name or alias is required.', 'wellness-consult' );
		}

		// 9. Gender
		if ( empty( $gender ) ) {
			$errors[] = esc_html__( 'Please select your gender.', 'wellness-consult' );
		}

		// 10. Country Compliance Check (Rule 1 & SRS Section 10)
		$all_countries = WC_Options::get_all_iso_countries();
		if ( empty( $country ) || ! isset( $all_countries[ $country ] ) ) {
			$errors[] = esc_html__( 'Please select a valid country of residence.', 'wellness-consult' );
		} else {
			$countries_settings = get_option( 'wc_countries_settings', array(
				'mode'         => 'all_except_blocked',
				'allowed_list' => array(),
				'blocked_list' => array(),
			) );
			$mode         = $countries_settings['mode'] ?? 'all_except_blocked';
			$blocked_list = $countries_settings['blocked_list'] ?? array();
			$allowed_list = $countries_settings['allowed_list'] ?? array();

			$is_blocked = ( 'all_except_blocked' === $mode ) ? in_array( $country, $blocked_list, true ) : ! in_array( $country, $allowed_list, true );
			if ( $is_blocked ) {
				$errors[] = esc_html__( 'Consultations are not available in your country due to healthcare licensing regulations.', 'wellness-consult' );
			}
		}

		// 11. Timezone Check
		if ( ! in_array( $timezone, timezone_identifiers_list(), true ) ) {
			$timezone = 'UTC';
		}

		// 12. Contact Requirement (At least one of phone or email)
		if ( empty( $phone ) && empty( $email ) ) {
			$errors[] = esc_html__( 'Please provide at least one contact method: WhatsApp phone number or email address.', 'wellness-consult' );
		}
		if ( ! empty( $email ) && ! is_email( $email ) ) {
			$errors[] = esc_html__( 'Please provide a valid email address.', 'wellness-consult' );
		}

		// 13. Concern Category
		if ( empty( $concern ) ) {
			$errors[] = esc_html__( 'Please select your primary consultation concern.', 'wellness-consult' );
		}

		// 14. Consent Check
		if ( ! $consent ) {
			$errors[] = esc_html__( 'You must agree to the Terms, Privacy Policy, and Refund Policy to proceed.', 'wellness-consult' );
		}

		// 15. Screenshot Upload (Required for manual transfer)
		$upload_result = null;
		if ( $is_manual ) {
			if ( empty( $_FILES['payment_proof'] ) || empty( $_FILES['payment_proof']['name'] ) ) {
				$errors[] = esc_html__( 'Payment proof screenshot is required for manual bank/wallet transfers.', 'wellness-consult' );
			} else {
				$upload_result = WC_Upload_Handler::handle_upload( $_FILES['payment_proof'] );
				if ( is_wp_error( $upload_result ) ) {
					$errors[] = $upload_result->get_error_message();
				}
			}
		}

		// If validation errors exist, flash and redirect back
		if ( ! empty( $errors ) ) {
			self::set_flash( $errors, $input_data );
			$referer = wp_get_referer();
			self::safe_redirect_and_exit( $referer ? $referer : home_url( '/book-consultation/' ) );
			return;
		}

		// ========================================================
		// 16. Create Request: Atomic Reference ID & Post Creation
		// ========================================================
		$reference_id = WC_Atomic_Reference::generate();

		$post_id = wp_insert_post( array(
			'post_title'  => $reference_id,
			'post_type'   => 'wc_request',
			'post_status' => 'publish',
		) );

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			self::set_flash( array( esc_html__( 'System database error creating consultation request. Please contact support.', 'wellness-consult' ) ), $input_data );
			self::safe_redirect_and_exit( wp_get_referer() ? wp_get_referer() : home_url( '/book-consultation/' ) );
			return;
		}

		// Initial status (FR-8: Online checkout starts in awaiting_payment; manual starts in pending_verification)
		$initial_status = $is_online ? WC_Status::STATUS_AWAITING_PAYMENT : WC_Status::STATUS_PENDING_VERIFICATION;

		// Save Post Meta
		update_post_meta( $post_id, '_wc_reference_id', $reference_id );
		update_post_meta( $post_id, '_wc_status', $initial_status );
		update_post_meta( $post_id, '_wc_type_id', $type_id );
		update_post_meta( $post_id, '_wc_type_name', $type_name );
		update_post_meta( $post_id, '_wc_consult_mode', $consult_mode ); // CR-1: chat | voice | video
		update_post_meta( $post_id, '_wc_fee_usd', $fee_usd ); // Server-verified fee for selected mode

		if ( $is_online ) {
			$method_name = ( 'stripe' === $method_id ) ? __( 'Credit / Debit Card (Stripe Checkout)', 'wellness-consult' ) : __( 'PayPal Secure Checkout', 'wellness-consult' );
			$method_type = 'online';

			update_post_meta( $post_id, '_wc_payment_method', $method_id );
			update_post_meta( $post_id, '_wc_payment_method_id', $method_id );
			update_post_meta( $post_id, '_wc_payment_method_name', $method_name );
			update_post_meta( $post_id, '_wc_payment_method_type', $method_type );
		} else {
			$method_info = $manual_methods[ $method_id ] ?? array();
			$method_name = $method_info['name'] ?? 'Manual Transfer';
			$method_type = $method_info['method_type'] ?? 'bank';

			update_post_meta( $post_id, '_wc_payment_method', 'manual' );
			update_post_meta( $post_id, '_wc_payment_method_id', $method_id );
			update_post_meta( $post_id, '_wc_payment_method_name', $method_name );
			update_post_meta( $post_id, '_wc_payment_method_type', $method_type );
		}
		update_post_meta( $post_id, '_wc_amount_expected', $fee_usd );
		update_post_meta( $post_id, '_wc_name', $name );
		update_post_meta( $post_id, '_wc_age', $age );
		update_post_meta( $post_id, '_wc_gender', $gender );
		update_post_meta( $post_id, '_wc_country', $country );
		update_post_meta( $post_id, '_wc_timezone', $timezone );
		update_post_meta( $post_id, '_wc_contact_phone', $phone );
		update_post_meta( $post_id, '_wc_contact_email', $email );
		update_post_meta( $post_id, '_wc_contact_phone_hash', WC_Fraud_Protection::hash_value( 'phone', $phone ) );
		update_post_meta( $post_id, '_wc_contact_email_hash', WC_Fraud_Protection::hash_value( 'email', $email ) );
		update_post_meta( $post_id, '_wc_concern', $concern );
		update_post_meta( $post_id, '_wc_message', $message );
		update_post_meta( $post_id, '_wc_preferred_window', $pref_window );
		update_post_meta( $post_id, '_wc_transaction_id', $trans_id );
		update_post_meta( $post_id, '_wc_transaction_id_hash', WC_Fraud_Protection::hash_value( 'transaction', $trans_id ) );
		update_post_meta( $post_id, '_wc_ip_hash', $ip_hash ); // Anonymized IP hash
		update_post_meta( $post_id, '_wc_created_at', current_time( 'mysql', 1 ) );

		// Proof file and history
		if ( ! empty( $upload_result ) && is_array( $upload_result ) ) {
			update_post_meta( $post_id, '_wc_proof_file', $upload_result['relative_path'] );
			$proof_entry = array(
				'relative_path' => $upload_result['relative_path'],
				'filename'      => $upload_result['filename'],
				'hash'          => $upload_result['hash'],
				'size'          => $upload_result['size'],
				'mime'          => $upload_result['mime'],
				'uploaded_at'   => $upload_result['uploaded_at'],
			);
			update_post_meta( $post_id, '_wc_proof_history', array( $proof_entry ) );
		}

		// Consent record (what, when, policy versions, ip_hash)
		$consent_record = array(
			'terms'           => true,
			'privacy'         => true,
			'refund'          => true,
			'health_data'     => true,
			'timestamp'       => current_time( 'mysql', 1 ),
			'policy_version'  => '2.0',
			'ip_hash'         => $ip_hash,
		);
		update_post_meta( $post_id, '_wc_consent_record', $consent_record );

		// Write to Activity Log
		WC_Database::log( array(
			'request_id'   => $post_id,
			'reference_id' => $reference_id,
			'action'       => 'request_submitted',
			'from_status'  => '',
			'to_status'    => $initial_status,
			'note'         => sprintf(
				'Consultation request submitted (%s, Mode: %s, $%0.2f USD, %s). Proof uploaded: %s.',
				$type_name,
				WC_Options::get_mode_label( $consult_mode ),
				$fee_usd,
				$country,
				! empty( $upload_result['filename'] ) ? $upload_result['filename'] : 'None'
			),
		) );

		// Increment Rate Limit Count
		set_transient( $rate_transient_key, $submission_count + 1, 3600 );

		// Cache Idempotency Token
		if ( ! empty( $idempotency_token ) ) {
			set_transient( 'wc_idem_' . $idempotency_token, array(
				'reference_id' => $reference_id,
				'status'       => $initial_status,
				'fee_usd'      => $fee_usd,
				'name'         => $name,
				'consult_mode' => $consult_mode,
			), 900 );
		}

		// ========================================================
		// 17. Notifications: Email Patient & Admin
		// ========================================================
		self::send_submission_emails( array(
			'reference_id'      => $reference_id,
			'name'              => $name,
			'email'             => $email,
			'consultation_type' => $type_name,
			'consult_mode'      => WC_Options::get_mode_label( $consult_mode ),
			'platform'          => __( 'Pending assignment', 'wellness-consult' ),
			'fee_usd'           => '$' . number_format( $fee_usd, 2 ) . ' USD',
			'track_url'         => home_url( '/track-my-request/?ref=' . urlencode( $reference_id ) ),
		) );

		// ========================================================
		// 18. Online Gateway Redirect or PRG Confirmation Screen
		// ========================================================
		if ( $is_online ) {
			if ( 'stripe' === $method_id ) {
				$gateway_res = WC_Gateway_Stripe::create_checkout_session( $post_id, array(
					'name'  => $name,
					'email' => $email,
					'phone' => $phone,
				) );
			} else {
				$gateway_res = WC_Gateway_PayPal::create_order( $post_id, array(
					'name'  => $name,
					'email' => $email,
					'phone' => $phone,
				) );
			}

			if ( is_wp_error( $gateway_res ) ) {
				self::set_flash( array( $gateway_res->get_error_message() ), $input_data );
				self::safe_redirect_and_exit( wp_get_referer() ? wp_get_referer() : home_url( '/book-consultation/' ) );
				return;
			}

			self::safe_redirect_and_exit( $gateway_res['url'] );
			return;
		}

		$confirmation_token = wp_generate_password( 32, false );
		set_transient( 'wc_confirm_' . $confirmation_token, array(
			'reference_id' => $reference_id,
			'status'       => $initial_status,
			'fee_usd'      => $fee_usd,
			'name'         => $name,
			'consult_mode' => $consult_mode,
			'type_name'    => $type_name,
			'request_id'   => $post_id,
		), 900 ); // 15-minute validity

		$booking_page_url = wp_get_referer() ? wp_get_referer() : home_url( '/book-consultation/' );
		$redirect_url     = add_query_arg( array(
			'wc_confirmed' => 1,
			'token'        => $confirmation_token,
		), $booking_page_url );

		self::safe_redirect_and_exit( $redirect_url );
	}

	/**
	 * Safe redirect and exit helper (respects WC_UNIT_TESTING).
	 *
	 * @param string $url Redirect target URL.
	 */
	protected static function safe_redirect_and_exit( $url ) {
		wp_safe_redirect( $url );
		if ( ! defined( 'WC_UNIT_TESTING' ) ) {
			exit;
		}
	}

	/**
	 * Send request submitted email using template placeholders.
	 *
	 * @param array $args Placeholder tokens.
	 */
	protected static function send_submission_emails( $args ) {
		$templates = get_option( 'wc_email_templates', WC_Options::get_default_email_templates() );
		$template  = $templates['request_submitted'] ?? WC_Options::get_default_email_templates()['request_submitted'];

		$placeholders = array(
			'{reference_id}'      => $args['reference_id'],
			'{name}'              => $args['name'],
			'{consultation_type}' => $args['consultation_type'],
			'{consult_mode}'      => $args['consult_mode'] ?? '',
			'{platform}'          => $args['platform'] ?? '',
			'{fee_usd}'           => $args['fee_usd'],
			'{track_url}'         => $args['track_url'],
		);

		$subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template['subject'] );
		$body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template['body'] );

		// Send to patient if email was provided
		if ( ! empty( $args['email'] ) ) {
			wp_mail( $args['email'], $subject, $body );
		}

		// Send notification alert to site administrator
		$admin_email   = get_option( 'admin_email' );
		$admin_subject = sprintf( '[Consultation Alert] New Booking - %s (%s, %s)', $args['reference_id'], $args['consultation_type'], $args['consult_mode'] ?? '' );
		$admin_body    = sprintf(
			"A new private consultation booking has been submitted.\n\nReference ID: %s\nPatient: %s\nPackage: %s\nMode: %s\nFee: %s\n\nReview proof and verify in Admin Dashboard:\n%s\n",
			$args['reference_id'],
			$args['name'],
			$args['consultation_type'],
			$args['consult_mode'] ?? '',
			$args['fee_usd'],
			admin_url( 'admin.php?page=wc-all-requests' )
		);
		wp_mail( $admin_email, $admin_subject, $admin_body );
	}

	/**
	 * Send an existing patient notification template for a request status event.
	 *
	 * @param string $event Event template key.
	 * @param array  $args  Template values.
	 * @return bool|null Email send result, or null when no patient email exists.
	 */
	public static function send_event_email( $event, $args ) {
		if ( empty( $args['email'] ) ) {
			return null;
		}

		$defaults  = WC_Options::get_default_email_templates();
		$templates = get_option( 'wc_email_templates', $defaults );
		$template  = $templates[ $event ] ?? ( $defaults[ $event ] ?? null );
		if ( ! is_array( $template ) || empty( $template['subject'] ) || empty( $template['body'] ) ) {
			return false;
		}

		$placeholders = array(
			'{reference_id}'      => $args['reference_id'] ?? '',
			'{name}'              => $args['name'] ?? '',
			'{consultation_type}' => $args['consultation_type'] ?? '',
			'{consult_mode}'      => $args['consult_mode'] ?? '',
			'{platform}'          => $args['platform'] ?? '',
			'{fee_usd}'           => $args['fee_usd'] ?? '',
			'{track_url}'         => $args['track_url'] ?? '',
			'{rejection_reason}'  => $args['rejection_reason'] ?? '',
		);

		$subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template['subject'] );
		$body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template['body'] );
		return wp_mail( $args['email'], $subject, $body );
	}
}
