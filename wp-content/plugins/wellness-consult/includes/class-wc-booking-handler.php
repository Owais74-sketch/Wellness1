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
		add_action( 'admin_post_nopriv_wc_booking_flow', array( __CLASS__, 'process_submission' ) );
		add_action( 'admin_post_wc_booking_flow', array( __CLASS__, 'process_submission' ) );
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

		if ( ! get_option( 'wc_consultations_enabled', false ) ) {
			wp_die( esc_html__( 'Consultations are not currently available.', 'wellness-consult' ), 'Service Unavailable', array( 'response' => 503 ) );
		}

		$stage = isset( $_POST['wc_booking_stage'] ) ? sanitize_key( wp_unslash( $_POST['wc_booking_stage'] ) ) : '';
		if ( ! in_array( $stage, array( 'create_draft', 'submit_manual', 'create_checkout' ), true ) ) {
			wp_die( esc_html__( 'Invalid booking action.', 'wellness-consult' ), 'Bad Request', array( 'response' => 400 ) );
		}

		// 1. Nonce Verification
		if ( ! isset( $_POST['wc_booking_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wc_booking_nonce'] ), 'wc_submit_booking_action' ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'wellness-consult' ), 'Forbidden', array( 'response' => 403 ) );
		}

		$draft_token = ! empty( $_COOKIE['wc_booking_draft'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['wc_booking_draft'] ) ) : '';
		$draft_id    = 0;
		if ( ! empty( $draft_token ) ) {
			$draft_id = self::find_browser_draft( $draft_token );
			if ( $draft_id ) {
				$draft_created = absint( get_post_meta( $draft_id, '_wc_draft_created_at', true ) );
				if ( $draft_created && time() - $draft_created >= DAY_IN_SECONDS ) {
					foreach ( (array) get_post_meta( $draft_id, '_wc_proof_history', true ) as $proof ) {
						if ( ! empty( $proof['relative_path'] ) ) {
							WC_Upload_Handler::delete_proof( $proof['relative_path'] );
						}
					}
					$proof_file = get_post_meta( $draft_id, '_wc_proof_file', true );
					if ( $proof_file ) {
						WC_Upload_Handler::delete_proof( $proof_file );
					}
					$idempotency_hash = get_post_meta( $draft_id, '_wc_draft_idempotency_hash', true );
					if ( $idempotency_hash ) {
						delete_option( 'wc_draft_idem_' . sanitize_key( $idempotency_hash ) );
						delete_transient( 'wc_draft_idem_' . sanitize_key( $idempotency_hash ) );
					}
					wp_delete_post( $draft_id, true );
					self::clear_browser_draft_cookie();
					$draft_id = 0;
				}
			}
		}

		// 2. Honeypot check (hidden field with tabindex -1)
		if ( ! empty( $_POST['wc_hp_gate'] ) ) {
			wp_die( esc_html__( 'Spam submission detected.', 'wellness-consult' ), 'Forbidden', array( 'response' => 400 ) );
		}

		if ( 'create_draft' === $stage ) {
			$payment_config = get_option( 'wc_payment_settings', array() );
			if ( ! empty( $payment_config['turnstile_site_key'] ) ) {
				$turnstile_result = self::verify_turnstile( $payment_config );
				if ( is_wp_error( $turnstile_result ) ) {
					self::set_flash( array( $turnstile_result->get_error_message() ), $_POST );
					self::safe_redirect_and_exit( wp_get_referer() ?: home_url( '/book-consultation/' ) );
					return;
				}
			}
		}

		// 3. Rate Limit check: 3 submissions per IP per hour (Rule 3)
		$raw_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		$ip_hash = hash_hmac( 'sha256', $raw_ip, wp_salt() );
		$rate_transient_key = 'wc_rate_limit_' . substr( $ip_hash, 0, 32 );
		$submission_count   = (int) get_transient( $rate_transient_key );

		if ( 'create_draft' === $stage && $submission_count >= 3 ) {
			$errors = array( esc_html__( 'Too many booking submissions from this network. Please wait an hour before trying again.', 'wellness-consult' ) );
			self::set_flash( $errors, $_POST );
			self::safe_redirect_and_exit( wp_get_referer() ? wp_get_referer() : home_url( '/book-consultation/' ) );
			return;
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
		$gateways       = class_exists( 'WC_Gateway_Registry' ) ? WC_Gateway_Registry::get_available() : array();
		$payment_settings = get_option( 'wc_payment_settings', array() );
		$require_online = ! empty( $payment_settings['require_online_payment'] );
		$is_gateway    = isset( $gateways[ $method_id ] );
		$is_manual     = isset( $manual_methods[ $method_id ] ) && ! empty( $manual_methods[ $method_id ]['active'] );

		if ( 'create_draft' !== $stage ) {
			if ( $is_gateway && 'create_checkout' !== $stage ) {
				$errors[] = esc_html__( 'Continue with the selected online checkout option.', 'wellness-consult' );
			} elseif ( 'create_checkout' === $stage && ! $is_gateway ) {
				$errors[] = esc_html__( 'This online payment method is not currently available.', 'wellness-consult' );
			} elseif ( 'submit_manual' === $stage && ( ! $is_manual || $require_online ) ) {
				$errors[] = esc_html__( 'Please select an available manual payment method.', 'wellness-consult' );
			} elseif ( 'create_checkout' === $stage && $require_online && ! $is_gateway ) {
				$errors[] = esc_html__( 'Online payment is required for this booking.', 'wellness-consult' );
			} elseif ( $is_manual && ! empty( $manual_methods[ $method_id ]['allowed_countries'] ) && ! in_array( $country, (array) $manual_methods[ $method_id ]['allowed_countries'], true ) ) {
				$errors[] = esc_html__( 'The selected payment method is not available in your country of residence.', 'wellness-consult' );
			}
		}
		if ( 'submit_manual' === $stage && $is_manual && empty( $trans_id ) ) {
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
		if ( 'submit_manual' === $stage && $is_manual ) {
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

		$post_id = 'create_draft' === $stage ? self::find_browser_draft( $draft_token ) : self::find_browser_draft( $draft_token );
		if ( 'create_draft' === $stage && $post_id ) {
			self::safe_redirect_and_exit( add_query_arg( 'wc_payment_step', 1, wp_get_referer() ?: home_url( '/book-consultation/' ) ) );
			return;
		}

		if ( 'create_draft' === $stage ) {
			$idempotency_token = ! empty( $_POST['wc_idempotency_token'] ) ? sanitize_text_field( wp_unslash( $_POST['wc_idempotency_token'] ) ) : '';
			if ( strlen( $idempotency_token ) < 16 ) {
				wp_die( esc_html__( 'The booking submission token is invalid. Please refresh the page.', 'wellness-consult' ), 'Bad Request', array( 'response' => 400 ) );
			}
			$idempotency_hash = hash( 'sha256', $idempotency_token );
			$idempotency_key  = 'wc_draft_idem_' . $idempotency_hash;
			$existing = get_transient( $idempotency_key );
			if ( is_array( $existing ) && ! empty( $existing['token'] ) ) {
				if ( ! headers_sent() ) {
					setcookie( 'wc_booking_draft', $existing['token'], array(
						'expires'  => time() + DAY_IN_SECONDS,
						'path'     => COOKIEPATH ?: '/',
						'domain'   => COOKIE_DOMAIN,
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					) );
				}
				self::safe_redirect_and_exit( add_query_arg( 'wc_payment_step', 1, wp_get_referer() ?: home_url( '/book-consultation/' ) ) );
				return;
			}

			$lock_acquired = add_option( $idempotency_key, time(), '', false );
			if ( ! $lock_acquired ) {
				$lock_time = absint( get_option( $idempotency_key ) );
				if ( $lock_time && time() - $lock_time > 900 ) {
					delete_option( $idempotency_key );
					$lock_acquired = add_option( $idempotency_key, time(), '', false );
				}
			}
			if ( ! $lock_acquired ) {
				self::set_flash( array( esc_html__( 'Your booking details are being saved. Please wait a moment and try again.', 'wellness-consult' ) ), $input_data );
				self::safe_redirect_and_exit( wp_get_referer() ?: home_url( '/book-consultation/' ) );
				return;
			}

			$draft_token = wp_generate_password( 64, false, false );
			$post_id = wp_insert_post( array(
				'post_title'  => 'Unpaid booking draft',
				'post_type'   => 'wc_request',
				'post_status' => 'draft',
			), true );
			if ( is_wp_error( $post_id ) || ! $post_id ) {
				delete_option( $idempotency_key );
				self::set_flash( array( esc_html__( 'We could not save your booking details. Please try again.', 'wellness-consult' ) ), $input_data );
				self::safe_redirect_and_exit( wp_get_referer() ?: home_url( '/book-consultation/' ) );
				return;
			}
			update_post_meta( $post_id, '_wc_is_draft', 1 );
			update_post_meta( $post_id, '_wc_status', WC_Status::STATUS_AWAITING_PAYMENT );
			update_post_meta( $post_id, '_wc_draft_token_hash', hash( 'sha256', $draft_token ) );
			update_post_meta( $post_id, '_wc_draft_created_at', time() );
			update_post_meta( $post_id, '_wc_draft_idempotency_hash', $idempotency_hash );
			update_post_meta( $post_id, '_wc_gateway_reference', 'DRAFT-' . wp_generate_password( 32, false, false ) );
			self::save_request_details( $post_id, compact( 'type_id', 'type_name', 'consult_mode', 'fee_usd', 'name', 'age', 'gender', 'country', 'timezone', 'phone', 'email', 'concern', 'message', 'pref_window', 'ip_hash' ) );
			set_transient( $idempotency_key, array( 'request_id' => $post_id, 'token' => $draft_token ), DAY_IN_SECONDS );
			set_transient( 'wc_rate_limit_' . substr( $ip_hash, 0, 32 ), $submission_count + 1, 3600 );
			if ( ! headers_sent() ) {
				setcookie( 'wc_booking_draft', $draft_token, array(
					'expires'  => time() + DAY_IN_SECONDS,
					'path'     => COOKIEPATH ?: '/',
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				) );
			}
			$_COOKIE['wc_booking_draft'] = $draft_token;
			self::safe_redirect_and_exit( add_query_arg( 'wc_payment_step', 1, wp_get_referer() ?: home_url( '/book-consultation/' ) ) );
			return;
		}

		if ( ! $post_id ) {
			$post_id = wp_insert_post( array(
				'post_title'  => 'Booking Request',
				'post_type'   => 'wc_request',
				'post_status' => ( 'create_checkout' === $stage ) ? 'draft' : 'publish',
			), true );
			if ( is_wp_error( $post_id ) || ! $post_id ) {
				self::set_flash( array( esc_html__( 'We could not save your booking details. Please try again.', 'wellness-consult' ) ), $input_data );
				self::safe_redirect_and_exit( wp_get_referer() ?: home_url( '/book-consultation/' ) );
				return;
			}
		}

		self::save_request_details( $post_id, compact( 'type_id', 'type_name', 'consult_mode', 'fee_usd', 'name', 'age', 'gender', 'country', 'timezone', 'phone', 'email', 'concern', 'message', 'pref_window', 'ip_hash' ) );
		update_post_meta( $post_id, '_wc_amount_expected', $fee_usd );
		$gateway_class = $is_gateway ? $gateways[ $method_id ] : null;
		$method_info   = $manual_methods[ $method_id ] ?? array();
		$method_name   = $is_gateway ? $gateway_class::get_name() : ( $method_info['name'] ?? '' );
		$method_type   = $is_gateway ? 'online' : ( $method_info['method_type'] ?? 'manual' );
		update_post_meta( $post_id, '_wc_payment_method', $is_gateway ? $method_id : 'manual' );
		update_post_meta( $post_id, '_wc_payment_method_id', $method_id );
		update_post_meta( $post_id, '_wc_payment_method_name', $method_name );
		update_post_meta( $post_id, '_wc_payment_method_type', $method_type );

		if ( 'create_checkout' === $stage ) {
			$result = $gateway_class::create_checkout( $post_id, array( 'name' => $name, 'email' => $email, 'phone' => $phone ) );
			if ( is_wp_error( $result ) ) {
				self::set_flash( array( $result->get_error_message() ), $input_data );
				self::safe_redirect_and_exit( add_query_arg( 'wc_payment_step', 1, wp_get_referer() ?: home_url( '/book-consultation/' ) ) );
				return;
			}
			$checkout_url = isset( $result['url'] ) ? esc_url_raw( $result['url'] ) : '';
			if ( ! $checkout_url || ! wp_http_validate_url( $checkout_url ) ) {
				wp_die( esc_html__( 'The payment provider returned an invalid checkout URL.', 'wellness-consult' ), 'Payment unavailable', array( 'response' => 502 ) );
			}
			wp_redirect( $checkout_url, 303, 'Wellness Consult' );
			exit;
		}

		update_post_meta( $post_id, '_wc_transaction_id', $trans_id );
		update_post_meta( $post_id, '_wc_transaction_id_hash', WC_Fraud_Protection::hash_value( 'transaction', $trans_id ) );
		update_post_meta( $post_id, '_wc_ip_hash', $ip_hash );
		if ( ! empty( $upload_result ) && is_array( $upload_result ) ) {
			update_post_meta( $post_id, '_wc_proof_file', $upload_result['relative_path'] );
			update_post_meta( $post_id, '_wc_proof_hash', $upload_result['hash'] );
			update_post_meta( $post_id, '_wc_proof_history', array( array(
				'relative_path' => $upload_result['relative_path'],
				'filename'      => $upload_result['filename'],
				'hash'          => $upload_result['hash'],
				'size'          => $upload_result['size'],
				'mime'          => $upload_result['mime'],
				'uploaded_at'   => $upload_result['uploaded_at'],
			) ) );
		}

		$reference_id = WC_Atomic_Reference::generate();
		update_post_meta( $post_id, '_wc_reference_id', $reference_id );
		delete_post_meta( $post_id, '_wc_is_draft' );
		wp_update_post( array( 'ID' => $post_id, 'post_title' => $reference_id, 'post_status' => 'publish' ) );
		$status_result = WC_Status::change_status( $post_id, WC_Status::STATUS_PENDING_VERIFICATION, 'Manual payment proof submitted for verification.' );
		if ( is_wp_error( $status_result ) ) {
			wp_die( esc_html( $status_result->get_error_message() ), 'Booking could not be completed', array( 'response' => 500 ) );
		}
		self::send_submission_emails( array(
			'request_id'        => $post_id,
			'reference_id'      => $reference_id,
			'name'              => $name,
			'email'             => $email,
			'consultation_type' => $type_name,
			'consult_mode'      => WC_Options::get_mode_label( $consult_mode ),
			'platform'          => __( 'Pending assignment', 'wellness-consult' ),
			'fee_usd'           => '$' . number_format( $fee_usd, 2 ) . ' USD',
			'track_url'         => home_url( '/track-my-request/?ref=' . urlencode( $reference_id ) ),
		) );
		self::clear_browser_draft_cookie();
		$confirmation_token = self::set_confirmation_token( $post_id, $reference_id, WC_Status::STATUS_PENDING_VERIFICATION, $fee_usd, $name, $consult_mode, $type_name );
		self::safe_redirect_and_exit( add_query_arg( array( 'wc_confirmed' => 1, 'token' => $confirmation_token ), wp_get_referer() ?: home_url( '/book-consultation/' ) ) );
	}

	/**
	 * Find the unpaid draft bound to this browser's unguessable cookie.
	 *
	 * @param string $token Browser draft token.
	 * @return int
	 */
	public static function find_browser_draft( $token ) {
		if ( ! is_string( $token ) || strlen( $token ) < 40 ) {
			return 0;
		}
		$drafts = get_posts( array(
			'post_type'      => 'wc_request',
			'post_status'    => 'draft',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array( 'key' => '_wc_is_draft', 'value' => '1' ),
				array( 'key' => '_wc_draft_token_hash', 'value' => hash( 'sha256', $token ) ),
			),
		) );
		return empty( $drafts ) ? 0 : absint( $drafts[0] );
	}

	/**
	 * Verify Cloudflare Turnstile when the public widget is configured.
	 *
	 * @param array $settings Payment settings.
	 * @return true|WP_Error
	 */
	protected static function verify_turnstile( $settings ) {
		$secret = defined( 'WC_TURNSTILE_SECRET_KEY' ) && WC_TURNSTILE_SECRET_KEY
			? WC_TURNSTILE_SECRET_KEY
			: ( $settings['turnstile_secret_key'] ?? '' );
		$token  = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
		if ( empty( $secret ) || empty( $token ) ) {
			return new WP_Error( 'wc_turnstile_missing', __( 'Please complete the security check before continuing.', 'wellness-consult' ) );
		}
		$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
			'timeout' => 10,
			'body'    => array(
				'secret'   => $secret,
				'response' => $token,
				'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
			),
		) );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'wc_turnstile_unavailable', __( 'The security check could not be verified. Please try again.', 'wellness-consult' ) );
		}
		$result = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) || empty( $result['success'] ) ) {
			return new WP_Error( 'wc_turnstile_failed', __( 'The security check failed. Please try again.', 'wellness-consult' ) );
		}
		return true;
	}

	/**
	 * Persist validated booking details on a draft.
	 *
	 * @param int   $post_id Request post ID.
	 * @param array $data    Validated form values.
	 */
	protected static function save_request_details( $post_id, $data ) {
		$meta = array(
			'_wc_type_id'            => $data['type_id'],
			'_wc_type_name'          => $data['type_name'],
			'_wc_consult_mode'       => $data['consult_mode'],
			'_wc_fee_usd'            => $data['fee_usd'],
			'_wc_amount_expected'    => $data['fee_usd'],
			'_wc_name'               => $data['name'],
			'_wc_age'                => $data['age'],
			'_wc_gender'             => $data['gender'],
			'_wc_country'            => $data['country'],
			'_wc_timezone'           => $data['timezone'],
			'_wc_contact_phone'      => $data['phone'],
			'_wc_contact_email'      => $data['email'],
			'_wc_contact_phone_hash' => WC_Fraud_Protection::hash_value( 'phone', $data['phone'] ),
			'_wc_contact_email_hash' => WC_Fraud_Protection::hash_value( 'email', $data['email'] ),
			'_wc_concern'             => $data['concern'],
			'_wc_message'             => $data['message'],
			'_wc_preferred_window'    => $data['pref_window'],
			'_wc_ip_hash'             => $data['ip_hash'],
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
		if ( ! get_post_meta( $post_id, '_wc_created_at', true ) ) {
			update_post_meta( $post_id, '_wc_created_at', current_time( 'mysql', true ) );
		}
		if ( ! get_post_meta( $post_id, '_wc_consent_record', true ) ) {
			update_post_meta( $post_id, '_wc_consent_record', array(
				'terms'          => true,
				'privacy'        => true,
				'refund'         => true,
				'health_data'    => true,
				'timestamp'      => current_time( 'mysql', true ),
				'policy_version' => '2.0',
				'ip_hash'        => $data['ip_hash'],
			) );
		}
	}

	/**
	 * Create the short-lived confirmation token after a manual proof submission.
	 *
	 * @param int    $post_id Request ID.
	 * @param string $reference_id Reference ID.
	 * @param string $status Request status.
	 * @param float  $fee Fee in USD.
	 * @param string $name Patient display name.
	 * @param string $mode Consultation mode.
	 * @param string $type_name Consultation type.
	 * @return string
	 */
	protected static function set_confirmation_token( $post_id, $reference_id, $status, $fee, $name, $mode, $type_name ) {
		$token = wp_generate_password( 32, false, false );
		set_transient( 'wc_confirm_' . $token, array(
			'reference_id' => $reference_id,
			'status'       => $status,
			'fee_usd'      => $fee,
			'name'         => $name,
			'consult_mode' => $mode,
			'type_name'    => $type_name,
			'request_id'   => $post_id,
		), 900 );
		return $token;
	}

	/**
	 * Clear the browser-bound draft cookie after manual submission.
	 */
	protected static function clear_browser_draft_cookie() {
		if ( ! headers_sent() ) {
			setcookie( 'wc_booking_draft', '', array(
				'expires'  => time() - HOUR_IN_SECONDS,
				'path'     => COOKIEPATH ?: '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			) );
		}
		unset( $_COOKIE['wc_booking_draft'] );
	}

	/**
	 * Finalize a draft only after a gateway has verified amount and currency.
	 *
	 * @param int    $post_id Request ID.
	 * @param string $provider_id Gateway transaction ID.
	 * @param float  $amount Amount received.
	 * @param string $currency Payment currency.
	 * @return true|WP_Error
	 */
	public static function confirm_online_payment( $post_id, $provider_id, $amount, $currency ) {
		$post_id = absint( $post_id );
		$expected = (float) get_post_meta( $post_id, '_wc_fee_usd', true );
		$current_status = get_post_meta( $post_id, '_wc_status', true );
		if ( ! $post_id ) {
			return new WP_Error( 'wc_invalid_payment_draft', 'The payment is not bound to a booking request.' );
		}
		if ( abs( (float) $amount - $expected ) > 0.01 || 'USD' !== strtoupper( $currency ) ) {
			return new WP_Error( 'wc_payment_mismatch', 'The verified payment amount or currency does not match the booking.' );
		}
		if ( in_array( $current_status, array( WC_Status::STATUS_PAID_AWAITING_SCHEDULING, WC_Status::STATUS_SCHEDULED, WC_Status::STATUS_COMPLETED ), true ) && get_post_meta( $post_id, '_wc_reference_id', true ) ) {
			return true;
		}
		if ( WC_Status::STATUS_AWAITING_PAYMENT !== $current_status ) {
			return new WP_Error( 'wc_invalid_payment_draft', 'The payment is not bound to an unpaid booking draft.' );
		}

		$is_draft = '1' === (string) get_post_meta( $post_id, '_wc_is_draft', true );
		$reference_id = get_post_meta( $post_id, '_wc_reference_id', true );
		if ( $is_draft ) {
			$reference_id = WC_Atomic_Reference::generate();
			update_post_meta( $post_id, '_wc_reference_id', $reference_id );
			delete_post_meta( $post_id, '_wc_is_draft' );
			wp_update_post( array( 'ID' => $post_id, 'post_title' => $reference_id, 'post_status' => 'publish' ) );
		} elseif ( empty( $reference_id ) ) {
			return new WP_Error( 'wc_invalid_payment_reference', 'The verified payment has no matching request reference.' );
		}
		update_post_meta( $post_id, '_wc_amount_received', (float) $amount );
		update_post_meta( $post_id, '_wc_payment_provider_id', sanitize_text_field( $provider_id ) );
		update_post_meta( $post_id, '_wc_paid_at', current_time( 'mysql', true ) );
		WC_Status::change_status( $post_id, WC_Status::STATUS_PAID_AWAITING_SCHEDULING, 'Online payment verified by the payment provider.' );

		self::send_submission_emails( array(
			'request_id'        => $post_id,
			'reference_id'      => $reference_id,
			'name'              => get_post_meta( $post_id, '_wc_name', true ),
			'email'             => get_post_meta( $post_id, '_wc_contact_email', true ),
			'consultation_type' => get_post_meta( $post_id, '_wc_type_name', true ),
			'consult_mode'      => WC_Options::get_mode_label( get_post_meta( $post_id, '_wc_consult_mode', true ) ),
			'platform'          => __( 'Pending assignment', 'wellness-consult' ),
			'fee_usd'           => '$' . number_format( $expected, 2 ) . ' USD',
			'track_url'         => home_url( '/track-my-request/?ref=' . rawurlencode( $reference_id ) ),
			'email_event'       => 'payment_confirmed',
		) );
		return true;
	}

	/**
	 * Create a short-lived confirmation view token for an already verified request.
	 *
	 * @param int $post_id Request ID.
	 * @return string
	 */
	public static function create_confirmation_view_token( $post_id ) {
		return self::set_confirmation_token(
			$post_id,
			get_post_meta( $post_id, '_wc_reference_id', true ),
			get_post_meta( $post_id, '_wc_status', true ),
			(float) get_post_meta( $post_id, '_wc_fee_usd', true ),
			get_post_meta( $post_id, '_wc_name', true ),
			get_post_meta( $post_id, '_wc_consult_mode', true ),
			get_post_meta( $post_id, '_wc_type_name', true )
		);
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
		$event = ! empty( $args['email_event'] ) ? sanitize_key( $args['email_event'] ) : 'request_submitted';
		self::send_event_email( $event, $args );

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
		$admin_sent = wp_mail( $admin_email, $admin_subject, $admin_body );
		WC_Database::log( array(
			'request_id'   => $args['request_id'] ?? 0,
			'reference_id' => $args['reference_id'] ?? '',
			'action'       => 'email_delivery',
			'note'         => 'Event request_submitted_admin_alert: ' . ( $admin_sent ? 'sent.' : 'failed.' ),
		) );
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

		$defaults = WC_Options::get_default_email_templates();
		$saved_templates = get_option( 'wc_email_templates', array() );
		$templates = array_merge( $defaults, is_array( $saved_templates ) ? $saved_templates : array() );
		$template  = $templates[ $event ] ?? ( $defaults[ $event ] ?? null );
		if ( ! is_array( $template ) || empty( $template['subject'] ) || empty( $template['body'] ) ) {
			self::log_email_delivery( $event, $args, false );
			return false;
		}

		$meeting_link = in_array( $event, array( 'scheduled', 'rescheduled', 'appointment_scheduled' ), true ) ? ( $args['meeting_link'] ?? '' ) : '';
		$placeholders = array(
			'{site_name}'             => get_bloginfo( 'name' ),
			'{reference_id}'      => $args['reference_id'] ?? '',
			'{name}'              => $args['name'] ?? '',
			'{consultation_type}' => $args['consultation_type'] ?? '',
			'{consult_mode}'      => $args['consult_mode'] ?? '',
			'{platform}'          => $args['platform'] ?? '',
			'{fee_usd}'           => $args['fee_usd'] ?? '',
			'{track_url}'         => $args['track_url'] ?? '',
			'{rejection_reason}'  => $args['rejection_reason'] ?? '',
			'{appointment_local}'      => $args['appointment_local'] ?? '',
			'{appointment_consultant}' => $args['appointment_consultant'] ?? '',
			'{date_time_patient}'      => $args['date_time_patient'] ?? ( $args['appointment_local'] ?? '' ),
			'{date_time_consultant}'   => $args['date_time_consultant'] ?? ( $args['appointment_consultant'] ?? '' ),
			'{meeting_link}'           => $meeting_link,
			'{reason}'                 => $args['reason'] ?? '',
		);

		$subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template['subject'] );
		$body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template['body'] );
		$sent = wp_mail( $args['email'], $subject, $body );
		self::log_email_delivery( $event, $args, $sent );
		return $sent;
	}

	/**
	 * Record email delivery status without storing addresses or message content.
	 *
	 * @param string $event Event template key.
	 * @param array  $args  Event data.
	 * @param bool   $sent  wp_mail result.
	 */
	private static function log_email_delivery( $event, $args, $sent ) {
		if ( empty( $args['request_id'] ) ) {
			return;
		}
		WC_Database::log( array(
			'request_id'   => absint( $args['request_id'] ),
			'reference_id' => $args['reference_id'] ?? '',
			'action'       => 'email_delivery',
			'note'         => sprintf( 'Event %s: %s.', sanitize_key( $event ), $sent ? 'sent' : 'failed' ),
		) );
	}
}
