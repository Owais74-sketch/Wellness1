<?php
/**
 * Admin Screen: All Requests List & Request Detail View
 *
 * Implements Rule 4 & SRS v2.0 Section 4.7 FR-27, FR-28.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Capability check
if ( ! current_user_can( 'wc_manage_requests' ) && ! current_user_can( 'wc_manage_assigned_requests' ) ) {
	wp_die( esc_html__( 'You do not have permission to view consultation requests.', 'wellness-consult' ) );
}

$is_admin        = current_user_can( 'wc_manage_all_requests' ) || current_user_can( 'administrator' );
$can_verify      = current_user_can( 'wc_manage_requests' );
$current_user_id = get_current_user_id();

$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
$req_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$can_delete_requests = current_user_can( 'manage_options' ) || current_user_can( 'wc_manage_all_requests' );
// Detail View
if ( 'view' === $action && $req_id > 0 ) :
	$post = get_post( $req_id );
	if ( ! $post || 'wc_request' !== $post->post_type ) {
		wp_die( esc_html__( 'Consultation request not found.', 'wellness-consult' ) );
	}

	// Consultant ownership check: Consultant sees ONLY their own assigned requests (Rule 4)
	if ( ! $is_admin ) {
		$assigned_consultant = absint( get_post_meta( $req_id, '_wc_consultant_id', true ) );
		if ( empty( $assigned_consultant ) || $assigned_consultant !== $current_user_id ) {
			wp_die( esc_html__( 'Forbidden: You are not assigned to this consultation request.', 'wellness-consult' ), 403, array( 'response' => 403 ) );
		}
	}

	$ref_id         = get_post_meta( $req_id, '_wc_reference_id', true );
	$status         = get_post_meta( $req_id, '_wc_status', true );
	$type_name      = get_post_meta( $req_id, '_wc_type_name', true );
	$fee_usd        = get_post_meta( $req_id, '_wc_fee_usd', true );
	$name           = get_post_meta( $req_id, '_wc_name', true );
	$age            = get_post_meta( $req_id, '_wc_age', true );
	$gender         = get_post_meta( $req_id, '_wc_gender', true );
	$country        = get_post_meta( $req_id, '_wc_country', true );
	$timezone       = get_post_meta( $req_id, '_wc_timezone', true );
	$phone          = get_post_meta( $req_id, '_wc_contact_phone', true );
	$email          = get_post_meta( $req_id, '_wc_contact_email', true );
	$concern        = get_post_meta( $req_id, '_wc_concern', true );
	$message        = get_post_meta( $req_id, '_wc_message', true );
	$window         = get_post_meta( $req_id, '_wc_preferred_window', true );
	$pay_method     = get_post_meta( $req_id, '_wc_payment_method', true );
	$trans_id       = get_post_meta( $req_id, '_wc_transaction_id', true );
	$proof_file     = get_post_meta( $req_id, '_wc_proof_file', true );
	$proof_history  = get_post_meta( $req_id, '_wc_proof_history', true );
	$current_proof_entry = is_array( $proof_history ) && ! empty( $proof_history )
		? end( $proof_history )
		: array();
	if ( empty( $proof_file ) && is_array( $current_proof_entry ) ) {
		$proof_file = $current_proof_entry['relative_path'] ?? ( $current_proof_entry['path'] ?? '' );
	}
	$current_proof_index = is_array( $proof_history ) && ! empty( $proof_history )
		? array_key_last( $proof_history )
		: null;
	$current_proof_mime = is_array( $current_proof_entry ) && isset( $current_proof_entry['mime'] )
		? $current_proof_entry['mime']
		: '';
	$consent_record = get_post_meta( $req_id, '_wc_consent_record', true );
	$ip_hash        = get_post_meta( $req_id, '_wc_ip_hash', true );
	$all_countries  = WC_Options::get_all_iso_countries();
	$country_name   = $all_countries[ $country ] ?? $country;

	// International Payment Method Meta (CR-2)
	$pay_method_id    = get_post_meta( $req_id, '_wc_payment_method_id', true );
	$pay_method_name  = get_post_meta( $req_id, '_wc_payment_method_name', true );
	$pay_method_type  = get_post_meta( $req_id, '_wc_payment_method_type', true );
	$amount_expected  = get_post_meta( $req_id, '_wc_amount_expected', true );
	if ( empty( $amount_expected ) ) {
		$amount_expected = $fee_usd;
	}
	$amount_received   = get_post_meta( $req_id, '_wc_amount_received', true );
	$verification_note = get_post_meta( $req_id, '_wc_verification_note', true );
	$rejection_reason  = get_post_meta( $req_id, '_wc_rejection_reason', true );
	$verification_checklist = get_post_meta( $req_id, '_wc_payment_verification_checklist', true );
	$new_proof_reason = get_post_meta( $req_id, '_wc_new_proof_request_reason', true );
	$checklist_labels = array(
		'account_visible'          => __( 'Money is visible in MY OWN account (not just the screenshot)', 'wellness-consult' ),
		'amount_matches'           => __( 'Amount received matches the expected amount', 'wellness-consult' ),
		'currency_matches'         => __( 'Currency matches', 'wellness-consult' ),
		'sender_matches'           => __( 'Sender name / reference matches this request', 'wellness-consult' ),
		'payment_after_submission' => __( 'Payment date is after the request was submitted', 'wellness-consult' ),
	);

	// Online Gateway Metadata (Phase 5: Stripe & PayPal)
	$payment_provider_id = get_post_meta( $req_id, '_wc_payment_provider_id', true );
	$paid_at             = get_post_meta( $req_id, '_wc_paid_at', true );
	$refund_id           = get_post_meta( $req_id, '_wc_refund_id', true );
	$refund_amount       = get_post_meta( $req_id, '_wc_refund_amount', true );
	$refund_reason       = get_post_meta( $req_id, '_wc_refund_reason', true );
	$refunded_at         = get_post_meta( $req_id, '_wc_refunded_at', true );
	$is_online_payment   = in_array( $pay_method, array( 'stripe', 'paypal' ), true ) || in_array( $pay_method_id, array( 'stripe', 'paypal' ), true ) || 'online' === $pay_method_type;

	// CR-1: Mode, Platform, and Access
	$consult_mode     = get_post_meta( $req_id, '_wc_consult_mode', true );
	if ( empty( $consult_mode ) ) {
		$consult_mode = 'chat';
	}
	$platform         = get_post_meta( $req_id, '_wc_platform', true );
	$meeting_link     = get_post_meta( $req_id, '_wc_meeting_link', true );
	$appointment_time = get_post_meta( $req_id, '_wc_appointment_time', true );
	$appointment_duration = absint( get_post_meta( $req_id, '_wc_duration', true ) );
	$assigned_consultant_id = absint( get_post_meta( $req_id, '_wc_consultant_id', true ) );
	$appointment_utc  = WC_Scheduling::get_request_utc( $req_id );
	$type_id = get_post_meta( $req_id, '_wc_type_id', true );
	$consultation_types = get_option( 'wc_consultation_types', array() );
	$default_duration = absint( $consultation_types[ $type_id ]['duration'] ?? 30 );
	$appointment_duration = $appointment_duration ? $appointment_duration : ( $default_duration ? $default_duration : 30 );
	$mode_label       = WC_Options::get_mode_label( $consult_mode );
	$mode_icon        = WC_Options::get_mode_icon( $consult_mode );
	$can_schedule     = $can_verify;

	$schedule_notice      = '';
	$schedule_notice_type = 'success';
	$email_delivery_failed = false;

	// Handle payment actions only for users explicitly allowed to manage requests.
	if ( isset( $_POST['wc_verify_payment'] ) ) {
		if ( ! $can_verify ) {
			wp_die( esc_html__( 'You do not have permission to verify payments.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'wc_verify_payment_action_' . $req_id, 'wc_verify_payment_nonce' );
		$raw_received = isset( $_POST['amount_received'] ) && is_string( $_POST['amount_received'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['amount_received'] ) ) ) : '';
		$val_received = is_numeric( $raw_received ) ? (float) $raw_received : 0;
		$val_note     = isset( $_POST['verification_note'] ) && is_string( $_POST['verification_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['verification_note'] ) ) : '';
		$override_reason = isset( $_POST['underpayment_override_reason'] ) && is_string( $_POST['underpayment_override_reason'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['underpayment_override_reason'] ) ) ) : '';
		$checklist_keys = array( 'account_visible', 'amount_matches', 'currency_matches', 'sender_matches', 'payment_after_submission' );
		$submitted_checks = isset( $_POST['verification_checklist'] ) && is_array( $_POST['verification_checklist'] )
			? array_map(
				static function( $value ) {
					return is_string( $value ) ? sanitize_key( $value ) : '';
				},
				wp_unslash( $_POST['verification_checklist'] )
			)
			: array();
		$checks_complete = empty( array_diff( $checklist_keys, $submitted_checks ) );
		$expected_val = (float) ( $amount_expected ? $amount_expected : $fee_usd );

		if ( WC_Status::STATUS_PENDING_VERIFICATION !== $status ) {
			$schedule_notice = esc_html__( 'This request is no longer awaiting payment verification.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( ! preg_match( '/^\d+(?:\.\d{1,2})?$/', $raw_received ) || $val_received <= 0 ) {
			$schedule_notice = esc_html__( '"Amount received (USD)" must be a positive amount.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( ! $checks_complete ) {
			$schedule_notice = esc_html__( 'Complete every verification checklist item before approving payment.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( $val_received < $expected_val && '' === $override_reason ) {
			$schedule_notice = esc_html__( 'An underpayment override reason is required when the received amount is below the expected amount.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} else {
			$checklist_record = array(
				'checks'     => $checklist_keys,
				'checked_by' => $current_user_id,
				'checked_at' => current_time( 'mysql', true ),
			);
			$log_note = sprintf(
				'Payment verified. Received $%0.2f USD; expected $%0.2f USD. Checklist completed by user %d at %s. %s%s',
				$val_received,
				$expected_val,
				$current_user_id,
				$checklist_record['checked_at'],
				$val_note ? 'Note: ' . $val_note . '. ' : '',
				$val_received < $expected_val ? 'Underpayment override: ' . $override_reason : ''
			);
			$transition_res = WC_Status::change_status( $req_id, WC_Status::STATUS_PAID_AWAITING_SCHEDULING, $log_note );
			if ( is_wp_error( $transition_res ) ) {
				$schedule_notice = $transition_res->get_error_message();
				$schedule_notice_type = 'error';
			} else {
				$checklist_record['amount_received_usd'] = $val_received;
				$checklist_record['expected_amount_usd'] = $expected_val;
				$checklist_record['override_reason'] = $val_received < $expected_val ? $override_reason : '';
				update_post_meta( $req_id, '_wc_payment_verification_checklist', $checklist_record );
				update_post_meta( $req_id, '_wc_amount_received', $val_received );
				update_post_meta( $req_id, '_wc_verification_note', $val_note );
				update_post_meta( $req_id, '_wc_verified_by', $current_user_id );
				update_post_meta( $req_id, '_wc_verified_at', $checklist_record['checked_at'] );
				WC_Database::log( array(
					'request_id'   => $req_id,
					'reference_id' => $ref_id,
					'action'       => 'payment_approved',
					'from_status'  => WC_Status::STATUS_PENDING_VERIFICATION,
					'to_status'    => WC_Status::STATUS_PAID_AWAITING_SCHEDULING,
					'note'         => $log_note,
				) );
				$status = WC_Status::STATUS_PAID_AWAITING_SCHEDULING;
				$amount_received = $val_received;
				$verification_note = $val_note;
				$verification_checklist = $checklist_record;
				$send_result = WC_Booking_Handler::send_event_email( 'payment_confirmed', array(
					'request_id'        => $req_id,
					'email'             => $email,
					'reference_id'      => $ref_id,
					'name'              => $name,
					'consultation_type' => $type_name,
					'consult_mode'      => WC_Options::get_mode_label( $consult_mode ),
					'fee_usd'           => '$' . number_format( $expected_val, 2 ) . ' USD',
					'track_url'          => home_url( '/track-my-request/?ref=' . rawurlencode( $ref_id ) ),
				) );
				$schedule_notice = esc_html__( 'Payment approved. The request is now awaiting scheduling.', 'wellness-consult' );
				if ( false === $send_result ) {
					$email_delivery_failed = true;
					$schedule_notice_type = 'error';
				}
			}
		}
	}

	// Handle Payment Rejection Submission
	if ( isset( $_POST['wc_reject_payment'] ) ) {
		if ( ! $can_verify ) {
			wp_die( esc_html__( 'You do not have permission to reject payment proofs.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'wc_reject_payment_action_' . $req_id, 'wc_reject_payment_nonce' );
		$rej_reason = isset( $_POST['rejection_reason'] ) && is_string( $_POST['rejection_reason'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['rejection_reason'] ) ) ) : '';
		if ( WC_Status::STATUS_PENDING_VERIFICATION !== $status ) {
			$schedule_notice = esc_html__( 'This request is no longer awaiting payment verification.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( empty( $rej_reason ) ) {
			$schedule_notice      = esc_html__( 'Error: Rejection reason must be filled when rejecting payment.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} else {
			$transition_res = WC_Status::change_status( $req_id, WC_Status::STATUS_REJECTED, sprintf( 'Proof rejected: %s', $rej_reason ) );
			if ( is_wp_error( $transition_res ) ) {
				$schedule_notice      = $transition_res->get_error_message();
				$schedule_notice_type = 'error';
			} else {
				update_post_meta( $req_id, '_wc_rejection_reason', $rej_reason );
				WC_Database::log( array(
					'request_id'   => $req_id,
					'reference_id' => $ref_id,
					'action'       => 'payment_rejected',
					'from_status'  => WC_Status::STATUS_PENDING_VERIFICATION,
					'to_status'    => WC_Status::STATUS_REJECTED,
					'note'         => $rej_reason,
				) );
				$status           = WC_Status::STATUS_REJECTED;
				$rejection_reason = $rej_reason;
				$send_result = WC_Booking_Handler::send_event_email( 'request_rejected', array(
					'request_id'        => $req_id,
					'email'             => $email,
					'reference_id'      => $ref_id,
					'name'              => $name,
					'consultation_type' => $type_name,
					'consult_mode'      => WC_Options::get_mode_label( $consult_mode ),
					'track_url'          => home_url( '/track-my-request/?ref=' . rawurlencode( $ref_id ) ),
					'rejection_reason'  => $rej_reason,
				) );
				$schedule_notice = esc_html__( 'Payment proof rejected.', 'wellness-consult' );
				if ( false === $send_result ) {
					$email_delivery_failed = true;
					$schedule_notice_type = 'error';
				}
			}
		}
	}

	if ( isset( $_POST['wc_request_new_proof'] ) ) {
		if ( ! $can_verify ) {
			wp_die( esc_html__( 'You do not have permission to request a new proof.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'wc_request_new_proof_action_' . $req_id, 'wc_request_new_proof_nonce' );
		$reason = isset( $_POST['new_proof_reason'] ) && is_string( $_POST['new_proof_reason'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['new_proof_reason'] ) ) ) : '';
		if ( WC_Status::STATUS_PENDING_VERIFICATION !== $status ) {
			$schedule_notice = esc_html__( 'This request is no longer awaiting payment verification.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( '' === $reason ) {
			$schedule_notice = esc_html__( 'Enter a reason to request a new screenshot.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} else {
			update_post_meta( $req_id, '_wc_new_proof_request_reason', $reason );
			update_post_meta( $req_id, '_wc_new_proof_requested_at', current_time( 'mysql', true ) );
			update_post_meta( $req_id, '_wc_new_proof_requested_by', $current_user_id );
			WC_Database::log( array(
				'request_id'   => $req_id,
				'reference_id' => $ref_id,
				'action'       => 'new_proof_requested',
				'from_status'  => $status,
				'to_status'    => $status,
				'note'         => $reason,
			) );
			$new_proof_reason = $reason;
			$schedule_notice = esc_html__( 'New screenshot request recorded.', 'wellness-consult' );
		}
	}

	if ( isset( $_POST['wc_block_request_contact'] ) ) {
		if ( ! $can_verify ) {
			wp_die( esc_html__( 'You do not have permission to block a contact.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'wc_block_request_contact_action_' . $req_id, 'wc_block_request_contact_nonce' );
		$reason = isset( $_POST['block_contact_reason'] ) && is_string( $_POST['block_contact_reason'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['block_contact_reason'] ) ) ) : '';
		$added = array();
		if ( '' !== $reason ) {
			foreach ( array( 'email' => $email, 'phone' => $phone ) as $type => $value ) {
				if ( '' !== $value && WC_Fraud_Protection::add_block( $type, $value ) ) {
					$added[] = $type;
				}
			}
		}
		if ( $added ) {
			WC_Database::log( array(
				'request_id'   => $req_id,
				'reference_id' => $ref_id,
				'action'       => 'block_request_contact',
				'note'         => sprintf( 'Blocked %s contact from request. Reason: %s', implode( ' and ', $added ), $reason ),
			) );
			$schedule_notice = esc_html__( 'Contact block added.', 'wellness-consult' );
		} else {
			$schedule_notice = '' === $reason
				? esc_html__( 'Enter a reason before blocking this contact.', 'wellness-consult' )
				: esc_html__( 'No contact details are available to block.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		}
	}

	// Handle Gateway Refund Submission (Phase 5 / FR-12)
	if ( ! empty( $_POST['wc_gateway_refund'] ) && check_admin_referer( 'wc_refund_action_' . $req_id, 'wc_refund_nonce' ) ) {
		if ( ! $is_admin ) {
			wp_die( esc_html__( 'Forbidden: Only administrators can process refunds.', 'wellness-consult' ), 403 );
		}

		$ref_amount = ! empty( $_POST['refund_amount'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['refund_amount'] ) ) ) : (float) ( $amount_received ? $amount_received : $fee_usd );
		$ref_reason = sanitize_textarea_field( wp_unslash( $_POST['refund_reason'] ?? '' ) );

		$actual_paid = (float) ( $amount_received ? $amount_received : $fee_usd );

		if ( empty( $ref_reason ) ) {
			$schedule_notice      = esc_html__( 'Error: A refund reason is required.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( $ref_amount <= 0 ) {
			$schedule_notice      = esc_html__( 'Error: Refund amount must be greater than zero.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( $ref_amount > $actual_paid ) {
			$schedule_notice      = sprintf( esc_html__( 'Error: Refund amount ($%0.2f USD) cannot exceed the total amount paid ($%0.2f USD).', 'wellness-consult' ), $ref_amount, $actual_paid );
			$schedule_notice_type = 'error';
		} else {
			$is_stripe_tx = ( 'stripe' === $pay_method || 'stripe' === $pay_method_id );
			$is_paypal_tx = ( 'paypal' === $pay_method || 'paypal' === $pay_method_id );

			if ( $is_stripe_tx ) {
				$refund_result = WC_Gateway_Stripe::refund( $req_id, $ref_amount, $ref_reason );
			} elseif ( $is_paypal_tx ) {
				$refund_result = WC_Gateway_PayPal::refund( $req_id, $ref_amount, $ref_reason );
			} else {
				$refund_result = new WP_Error( 'wc_unsupported_refund', esc_html__( 'Online gateway refund is only supported for Stripe and PayPal payments.', 'wellness-consult' ) );
			}

			if ( is_wp_error( $refund_result ) ) {
				$schedule_notice      = sprintf( esc_html__( 'Gateway refund failed: %s', 'wellness-consult' ), $refund_result->get_error_message() );
				$schedule_notice_type = 'error';
			} else {
				$gateway_name = $is_stripe_tx ? 'Stripe Checkout' : 'PayPal';
				$log_msg = sprintf(
					'Gateway refund of $%0.2f USD processed via %s. Reason: %s',
					$ref_amount,
					$gateway_name,
					$ref_reason
				);
				$status_trans = WC_Status::change_status( $req_id, WC_Status::STATUS_REFUNDED, $log_msg );

				if ( is_wp_error( $status_trans ) ) {
					$schedule_notice      = $status_trans->get_error_message();
					$schedule_notice_type = 'error';
				} else {
					update_post_meta( $req_id, '_wc_refund_amount', $ref_amount );
					update_post_meta( $req_id, '_wc_refund_reason', $ref_reason );
					update_post_meta( $req_id, '_wc_refunded_by', $current_user_id );
					$status               = WC_Status::STATUS_REFUNDED;
					$refund_amount        = $ref_amount;
					$refund_reason        = $ref_reason;
					$refund_id            = get_post_meta( $req_id, '_wc_refund_id', true );
					$schedule_notice      = sprintf( esc_html__( 'Success: Gateway refund of $%0.2f USD was processed successfully and status set to Refunded.', 'wellness-consult' ), $ref_amount );
					$schedule_notice_type = 'success';
				}
			}
		}
	}

	// Schedule and reschedule only after payment approval.
	if ( isset( $_POST['wc_schedule_request'] ) ) {
		if ( ! $can_schedule ) {
			wp_die( esc_html__( 'You do not have permission to schedule appointments.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'wc_schedule_action_' . $req_id, 'wc_schedule_nonce' );
		$selected_consultant = isset( $_POST['schedule_consultant'] ) ? absint( $_POST['schedule_consultant'] ) : 0;
		$selected_date = isset( $_POST['schedule_date'] ) && is_string( $_POST['schedule_date'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_date'] ) ) : '';
		$selected_slot = isset( $_POST['schedule_slot'] ) && is_string( $_POST['schedule_slot'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_slot'] ) ) : '';
		$selected_duration = isset( $_POST['schedule_duration'] ) ? absint( $_POST['schedule_duration'] ) : 0;
		$selected_platform_id = isset( $_POST['schedule_platform'] ) && is_string( $_POST['schedule_platform'] ) ? sanitize_key( wp_unslash( $_POST['schedule_platform'] ) ) : '';
		$selected_access = isset( $_POST['meeting_link_or_handle'] ) && is_string( $_POST['meeting_link_or_handle'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['meeting_link_or_handle'] ) ) ) : '';
		$consultant_user = get_userdata( $selected_consultant );
		$consultant_is_valid = $consultant_user && ( in_array( 'wc_consultant', (array) $consultant_user->roles, true ) || in_array( 'administrator', (array) $consultant_user->roles, true ) );
		$available_platforms = WC_Options::get_platforms_for_mode( $consult_mode, true );
		$chosen_platform = $available_platforms[ $selected_platform_id ] ?? array();

		if ( $consultant_user && ! $consultant_is_valid ) {
			$schedule_notice = esc_html__( 'Select a valid consultant account.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( ! $consultant_is_valid ) {
			$schedule_notice = esc_html__( 'Select a consultant before scheduling.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( $selected_duration < 5 || $selected_duration > 480 ) {
			$schedule_notice = esc_html__( 'Appointment duration must be between 5 and 480 minutes.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( empty( $chosen_platform ) ) {
			$schedule_notice = esc_html__( 'Select an active platform for the patient’s consultation mode.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( 'video' === $consult_mode && ( ! filter_var( $selected_access, FILTER_VALIDATE_URL ) || ! in_array( strtolower( (string) wp_parse_url( $selected_access, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) ) {
			$schedule_notice = esc_html__( 'Enter a valid http or https meeting URL for a video consultation.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( in_array( $consult_mode, array( 'voice', 'chat' ), true ) && '' === $selected_access ) {
			$schedule_notice = esc_html__( 'Enter the phone number or username to use for this consultation.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} else {
			$new_access = 'video' === $consult_mode ? esc_url_raw( $selected_access ) : $selected_access;
			$new_platform = sanitize_text_field( $chosen_platform['name'] ?? '' );
			$old_status = $status;
			$old_utc = $appointment_utc;
			$reservation = WC_Scheduling::reserve( $req_id, $selected_consultant, $selected_date, $selected_slot, $selected_duration, $new_platform, $new_access );
			if ( is_wp_error( $reservation ) ) {
				$schedule_notice = $reservation->get_error_message();
				$schedule_notice_type = 'error';
			} else {
				$status = WC_Status::STATUS_SCHEDULED;
				$appointment_utc = $selected_slot;
				$appointment_duration = $selected_duration;
				$assigned_consultant_id = $selected_consultant;
				$platform = $new_platform;
				$meeting_link = $new_access;
				$availability = WC_Scheduling::get_availability( $selected_consultant );
				$consultant_timezone = $availability['base_timezone'] ?? 'UTC';
				if ( ! in_array( $consultant_timezone, timezone_identifiers_list(), true ) ) {
					$consultant_timezone = 'UTC';
				}
				$time_labels = WC_Scheduling::get_time_labels( $appointment_utc, $timezone, $selected_consultant );
				$event = WC_Status::STATUS_SCHEDULED === $old_status ? 'rescheduled' : 'scheduled';
				$email_result = WC_Booking_Handler::send_event_email( $event, array(
					'request_id'            => $req_id,
					'email'                  => $email,
					'reference_id'           => $ref_id,
					'name'                   => $name,
					'consultation_type'      => $type_name,
					'consult_mode'           => $mode_label,
					'platform'               => $platform,
					'appointment_local'      => $time_labels['patient'],
					'appointment_consultant' => $time_labels['consultant'],
					'meeting_link'           => $meeting_link,
					'track_url'              => home_url( '/track-my-request/?ref=' . rawurlencode( $ref_id ) ),
				) );
				$schedule_notice = 'rescheduled' === $event
					? esc_html__( 'Appointment rescheduled; the previous time is retained in the activity log.', 'wellness-consult' )
					: esc_html__( 'Appointment scheduled successfully.', 'wellness-consult' );
				if ( false === $email_result ) {
					$email_delivery_failed = true;
					$schedule_notice_type = 'error';
				}
				$appointment_time = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $appointment_utc, new DateTimeZone( 'UTC' ) )->setTimezone( new DateTimeZone( $consultant_timezone ) )->format( 'Y-m-d\TH:i' );
			}
		}
	}

	// Administrative cancellation, completion, no-show, and manual refund actions.
	if ( isset( $_POST['wc_request_action'] ) ) {
		check_admin_referer( 'wc_request_action_' . $req_id, 'wc_request_action_nonce' );
		$request_action = isset( $_POST['request_action'] ) && is_string( $_POST['request_action'] ) ? sanitize_key( wp_unslash( $_POST['request_action'] ) ) : '';
		$reason = isset( $_POST['action_reason'] ) && is_string( $_POST['action_reason'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['action_reason'] ) ) ) : '';
		$is_assigned_consultant = ! $can_verify && absint( get_post_meta( $req_id, '_wc_consultant_id', true ) ) === $current_user_id;
		$target_status = array(
			'cancel'    => WC_Status::STATUS_CANCELLED,
			'complete'  => WC_Status::STATUS_COMPLETED,
			'no_show'   => WC_Status::STATUS_NO_SHOW,
			'refund'    => WC_Status::STATUS_REFUNDED,
		);
		$allowed_action = isset( $target_status[ $request_action ] )
			&& ( ( in_array( $request_action, array( 'complete', 'no_show' ), true ) && ( $can_verify || $is_assigned_consultant ) )
				|| ( in_array( $request_action, array( 'cancel', 'refund' ), true ) && $can_verify ) );
		if ( ! $allowed_action ) {
			wp_die( esc_html__( 'You do not have permission to perform this request action.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		if ( in_array( $request_action, array( 'cancel', 'refund' ), true ) && '' === $reason ) {
			$schedule_notice = esc_html__( 'A reason is required for this action.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( in_array( $request_action, array( 'complete', 'no_show' ), true ) && WC_Status::STATUS_SCHEDULED !== $status ) {
			$schedule_notice = esc_html__( 'Only scheduled consultations can be marked completed or no-show.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( 'refund' === $request_action && $is_online_payment ) {
			$schedule_notice = esc_html__( 'Manual refunds cannot be recorded for an online gateway payment.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( 'refund' === $request_action && ( ! isset( $_POST['refund_amount'] ) || ! is_string( $_POST['refund_amount'] ) || ! preg_match( '/^\d+(?:\.\d{1,2})?$/', trim( wp_unslash( $_POST['refund_amount'] ) ) ) || (float) wp_unslash( $_POST['refund_amount'] ) <= 0 || (float) wp_unslash( $_POST['refund_amount'] ) > (float) ( $amount_received ?: $amount_expected ) ) ) {
			$schedule_notice = esc_html__( 'Enter a positive refund amount no greater than the amount received.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} else {
			$action_from_status = $status;
			$note = 'cancel' === $request_action ? 'Cancellation reason: ' . $reason : ( 'refund' === $request_action ? 'Manual refund recorded. ' . $reason : ucfirst( str_replace( '_', ' ', $request_action ) ) . '.' );
			$transition = WC_Status::change_status( $req_id, $target_status[ $request_action ], $note );
			if ( is_wp_error( $transition ) ) {
				$schedule_notice = $transition->get_error_message();
				$schedule_notice_type = 'error';
			} else {
				$status = $target_status[ $request_action ];
				$log_action = array(
					'cancel'   => 'request_cancelled',
					'complete' => 'consultation_completed',
					'no_show'  => 'consultation_no_show',
					'refund'   => 'manual_refund_recorded',
				);
				if ( 'cancel' === $request_action ) {
					update_post_meta( $req_id, '_wc_cancellation_reason', $reason );
				} elseif ( 'refund' === $request_action ) {
					$refund_amount = (float) wp_unslash( $_POST['refund_amount'] );
					update_post_meta( $req_id, '_wc_refund_amount', $refund_amount );
					update_post_meta( $req_id, '_wc_refund_reason', $reason );
					update_post_meta( $req_id, '_wc_refunded_by', $current_user_id );
					update_post_meta( $req_id, '_wc_refunded_at', current_time( 'mysql', true ) );
				}
				WC_Database::log( array(
					'request_id'   => $req_id,
					'reference_id' => $ref_id,
					'action'       => $log_action[ $request_action ],
					'from_status'  => $action_from_status,
					'to_status'    => $status,
					'note'         => $note,
				) );
				$schedule_notice = array(
					'cancel'   => esc_html__( 'Request cancelled.', 'wellness-consult' ),
					'complete' => esc_html__( 'Consultation marked completed.', 'wellness-consult' ),
					'no_show'  => esc_html__( 'Consultation marked as no-show.', 'wellness-consult' ),
					'refund'   => esc_html__( 'Manual refund recorded.', 'wellness-consult' ),
				)[ $request_action ];
				if ( in_array( $request_action, array( 'cancel', 'complete' ), true ) ) {
					$event = 'cancel' === $request_action ? 'cancelled' : 'completed';
					$email_result = WC_Booking_Handler::send_event_email( $event, array(
						'request_id'   => $req_id,
						'email'        => $email,
						'reference_id' => $ref_id,
						'name'         => $name,
						'reason'       => $reason,
						'track_url'    => home_url( '/track-my-request/?ref=' . rawurlencode( $ref_id ) ),
					) );
					if ( false === $email_result ) {
						$email_delivery_failed = true;
						$schedule_notice_type = 'error';
					}
				}
			}
		}
	}

	$proof_view_url = WC_Proof_Viewer::get_view_url( $req_id, $current_proof_index );
	global $wpdb;
	$activity_table = $wpdb->prefix . 'wc_activity_log';
	$request_activity = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT created_at, user_name, action, note FROM $activity_table WHERE request_id = %d ORDER BY id DESC LIMIT 20",
			$req_id
		)
	);
	foreach ( (array) $request_activity as $activity_entry ) {
		if ( 'email_delivery' === $activity_entry->action && preg_match( '/:\s*failed\.$/', (string) $activity_entry->note ) ) {
			$email_delivery_failed = true;
			break;
		}
	}
	$fraud_data = $can_verify ? WC_Fraud_Protection::get_request_flags( $req_id ) : array( 'flags' => array(), 'request_ids' => array() );
	?>

	<div class="wrap wc-admin-wrap">
		<h1 class="wp-heading-inline">
			<?php printf( esc_html__( 'Request Detail: %s', 'wellness-consult' ), esc_html( $ref_id ? $ref_id : $post->post_title ) ); ?>
		</h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests' ) ); ?>" class="page-title-action">
			&larr; <?php esc_html_e( 'Back to All Requests', 'wellness-consult' ); ?>
		</a>
		<hr class="wp-header-end">

		<?php if ( ! empty( $schedule_notice ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $schedule_notice_type ); ?> is-dismissible" style="margin:15px 0;">
				<p><?php echo esc_html( $schedule_notice ); ?></p>
			</div>
		<?php endif; ?>
		<?php if ( $email_delivery_failed ) : ?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'The email to the patient could not be sent. Please contact them manually.', 'wellness-consult' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $can_verify && ! empty( $fraud_data['flags'] ) ) : ?>
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Payment review warning', 'wellness-consult' ); ?></strong></p>
				<ul>
					<?php foreach ( $fraud_data['flags'] as $fraud_flag ) : ?>
						<li><?php echo esc_html( $fraud_flag ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( ! empty( $fraud_data['request_ids'] ) ) : ?>
					<p><strong><?php esc_html_e( 'Possible duplicate requests:', 'wellness-consult' ); ?></strong>
						<?php foreach ( $fraud_data['request_ids'] as $related_id ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests&action=view&id=' . absint( $related_id ) ) ); ?>"><?php echo esc_html( get_post_meta( $related_id, '_wc_reference_id', true ) ?: get_the_title( $related_id ) ); ?></a>
							<?php if ( end( $fraud_data['request_ids'] ) !== $related_id ) : ?>, <?php endif; ?>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
				<p><?php esc_html_e( 'These are warnings only. Check your own account before making a decision.', 'wellness-consult' ); ?></p>
			</div>
		<?php endif; ?>

		<?php
		$payment_mismatch = get_post_meta( $req_id, '_wc_payment_mismatch', true );
		if ( ! empty( $payment_mismatch ) && is_array( $payment_mismatch ) ) :
		?>
			<div class="notice notice-error" style="margin:15px 0;border-left:4px solid #dc2626;background:#fef2f2;padding:12px 16px;">
				<p style="margin:0;font-size:13px;color:#991b1b;">
					<strong style="font-size:14px;">⚠️ <?php esc_html_e( 'PAYMENT MISMATCH DETECTED:', 'wellness-consult' ); ?></strong><br>
					<?php
					printf(
						esc_html__( 'Webhook received amount of %1$s %2$s, which does not match the server-verified consultation fee ($%3$s USD). Request status remains "%4$s" and has not been confirmed.', 'wellness-consult' ),
						esc_html( number_format( (float) ( $payment_mismatch['paid_amount'] ?? 0.0 ), 2 ) ),
						esc_html( strtoupper( $payment_mismatch['paid_currency'] ?? 'USD' ) ),
						esc_html( number_format( (float) ( $payment_mismatch['expected_amount'] ?? $fee_usd ), 2 ) ),
						esc_html( WC_Status::get_label( $status ) )
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px;">
			<!-- Left Column: Patient & Consultation Details -->
			<div>
				<!-- Section 1: Overview Card -->
				<div class="postbox" style="padding:16px;">
					<h2 class="hndle" style="margin:0 0 12px 0;"><?php esc_html_e( 'Consultation Overview', 'wellness-consult' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Reference ID', 'wellness-consult' ); ?></th>
							<td><code><strong><?php echo esc_html( $ref_id ); ?></strong></code></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Current Status', 'wellness-consult' ); ?></th>
							<td>
								<span class="badge" style="background:#e0f2fe;color:#0369a1;padding:4px 10px;border-radius:4px;font-weight:700;">
									<?php echo esc_html( WC_Status::get_label( $status ) ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Consultation Package', 'wellness-consult' ); ?></th>
							<td><strong><?php echo esc_html( $type_name ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Consultation Mode', 'wellness-consult' ); ?></th>
							<td><strong style="color:#0f3b4c;font-size:15px;"><?php echo esc_html( $mode_icon . ' ' . $mode_label ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Fee (USD)', 'wellness-consult' ); ?></th>
							<td><strong style="color:#0f3b4c;font-size:16px;">$<?php echo esc_html( number_format( (float) $fee_usd, 2 ) ); ?> USD</strong></td>
						</tr>
						<?php if ( ! empty( $platform ) ) : ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'Scheduled Platform', 'wellness-consult' ); ?></th>
								<td><code><?php echo esc_html( $platform ); ?></code></td>
							</tr>
						<?php endif; ?>
						<?php if ( ! empty( $meeting_link ) ) : ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'Meeting URL / Contact', 'wellness-consult' ); ?></th>
								<td><code style="word-break:break-all;"><?php echo esc_html( $meeting_link ); ?></code></td>
							</tr>
						<?php endif; ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Primary Concern', 'wellness-consult' ); ?></th>
							<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $concern ) ) ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Preferred Time Window', 'wellness-consult' ); ?></th>
							<td><?php echo esc_html( ucfirst( $window ) ); ?> (in patient local timezone)</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Confidential Note', 'wellness-consult' ); ?></th>
							<td>
								<?php if ( ! empty( $message ) ) : ?>
									<div style="background:#f8fafc;padding:12px;border:1px solid #e2e8f0;border-radius:4px;line-height:1.5;">
										<?php echo nl2br( esc_html( $message ) ); ?>
									</div>
								<?php else : ?>
									<em style="color:#777;"><?php esc_html_e( 'No note provided by patient.', 'wellness-consult' ); ?></em>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>

				<!-- Section 2: Patient Demographic Info -->
				<div class="postbox" style="padding:16px;">
					<h2 class="hndle" style="margin:0 0 12px 0;"><?php esc_html_e( 'Patient Profile & Contact', 'wellness-consult' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Name or Alias', 'wellness-consult' ); ?></th>
							<td><strong><?php echo esc_html( $name ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Age', 'wellness-consult' ); ?></th>
							<td><?php echo esc_html( $age ); ?> years old</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Gender', 'wellness-consult' ); ?></th>
							<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $gender ) ) ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Country of Residence', 'wellness-consult' ); ?></th>
							<td><?php echo esc_html( $country_name . ' (' . $country . ')' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Patient Time Zone', 'wellness-consult' ); ?></th>
							<td><code><?php echo esc_html( $timezone ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Phone / WhatsApp', 'wellness-consult' ); ?></th>
							<td>
								<?php if ( ! empty( $phone ) ) : ?>
									<strong><?php echo esc_html( $phone ); ?></strong>
								<?php else : ?>
									<span style="color:#999;">—</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Email Address', 'wellness-consult' ); ?></th>
							<td>
								<?php if ( ! empty( $email ) ) : ?>
									<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
								<?php else : ?>
									<span style="color:#999;">—</span>
								<?php endif; ?>
							</td>
						</tr>
					</table>
					<?php if ( $can_verify && ( ! empty( $email ) || ! empty( $phone ) ) ) : ?>
						<form method="post" style="margin-top:12px;" onsubmit="return confirm('<?php echo esc_attr__( 'Block this email and phone so they cannot submit another booking?', 'wellness-consult' ); ?>');">
							<?php wp_nonce_field( 'wc_block_request_contact_action_' . $req_id, 'wc_block_request_contact_nonce' ); ?>
							<input type="hidden" name="wc_block_request_contact" value="1">
							<label for="block_contact_reason"><?php esc_html_e( 'Reason (activity log only) *', 'wellness-consult' ); ?></label>
							<textarea name="block_contact_reason" id="block_contact_reason" rows="2" class="large-text" required></textarea>
							<button type="submit" class="button button-secondary"><?php esc_html_e( 'Block this contact', 'wellness-consult' ); ?></button>
						</form>
					<?php endif; ?>
				</div>

				<?php if ( $can_schedule && in_array( $status, array( WC_Status::STATUS_PAID_AWAITING_SCHEDULING, WC_Status::STATUS_SCHEDULED ), true ) ) : ?>
					<?php
					$schedule_consultants = get_users( array(
						'role__in' => array( 'wc_consultant', 'administrator' ),
						'orderby'  => 'display_name',
						'order'    => 'ASC',
					) );
					$requested_consultant = isset( $_GET['schedule_consultant'] ) ? absint( $_GET['schedule_consultant'] ) : $assigned_consultant_id;
					if ( ! $requested_consultant && ! empty( $schedule_consultants ) ) {
						$requested_consultant = absint( $schedule_consultants[0]->ID );
					}
					$consultant_ids = wp_list_pluck( $schedule_consultants, 'ID' );
					if ( ! in_array( $requested_consultant, array_map( 'absint', $consultant_ids ), true ) ) {
						$requested_consultant = 0;
					}
					$requested_availability = $requested_consultant ? WC_Scheduling::get_availability( $requested_consultant ) : array();
					$requested_timezone = $requested_availability['base_timezone'] ?? 'UTC';
					if ( ! in_array( $requested_timezone, timezone_identifiers_list(), true ) ) {
						$requested_timezone = 'UTC';
					}
					$consultant_today = ( new DateTimeImmutable( 'now', new DateTimeZone( $requested_timezone ) ) )->format( 'Y-m-d' );
					$requested_date = isset( $_GET['schedule_date'] ) && is_string( $_GET['schedule_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['schedule_date'] )
						? sanitize_text_field( wp_unslash( $_GET['schedule_date'] ) )
						: ( $appointment_utc && $requested_consultant
							? DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $appointment_utc, new DateTimeZone( 'UTC' ) )->setTimezone( new DateTimeZone( $requested_timezone ) )->format( 'Y-m-d' )
							: $consultant_today );
					$requested_duration = isset( $_GET['schedule_duration'] ) ? absint( $_GET['schedule_duration'] ) : $appointment_duration;
					$requested_duration = $requested_duration >= 5 && $requested_duration <= 480 ? $requested_duration : $appointment_duration;
					$available_slots = $requested_consultant
						? WC_Scheduling::get_available_slots( $requested_consultant, $requested_date, $requested_duration, $req_id, $timezone )
						: array();
					$existing_time_labels = $appointment_utc && $assigned_consultant_id
						? WC_Scheduling::get_time_labels( $appointment_utc, $timezone, $assigned_consultant_id )
						: array( 'patient' => '', 'consultant' => '' );
					$active_platforms = WC_Options::get_platforms_for_mode( $consult_mode, true );
					?>
					<div class="postbox" style="padding:16px;">
						<h2 class="hndle" style="margin:0 0 12px 0;display:flex;align-items:center;gap:6px;">
							<span class="dashicons dashicons-calendar-alt"></span>
							<?php echo WC_Status::STATUS_SCHEDULED === $status ? esc_html__( 'Reschedule Appointment', 'wellness-consult' ) : esc_html__( 'Schedule Appointment', 'wellness-consult' ); ?>
						</h2>
						<p class="description"><?php printf( esc_html__( 'Mode: %1$s. Preferred patient time window: %2$s.', 'wellness-consult' ), esc_html( $mode_label ), esc_html( $window ? ucfirst( $window ) : __( 'Not specified', 'wellness-consult' ) ) ); ?></p>
						<?php if ( $appointment_utc && $existing_time_labels['patient'] ) : ?>
							<p><strong><?php esc_html_e( 'Current appointment:', 'wellness-consult' ); ?></strong><br>
								<?php printf( esc_html__( 'Patient: %1$s | Consultant: %2$s', 'wellness-consult' ), esc_html( $existing_time_labels['patient'] ), esc_html( $existing_time_labels['consultant'] ) ); ?>
							</p>
						<?php endif; ?>

						<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
							<input type="hidden" name="page" value="wc-all-requests">
							<input type="hidden" name="action" value="view">
							<input type="hidden" name="id" value="<?php echo esc_attr( $req_id ); ?>">
							<label><?php esc_html_e( 'Consultant', 'wellness-consult' ); ?><br>
								<select name="schedule_consultant" required>
									<option value=""><?php esc_html_e( 'Select consultant', 'wellness-consult' ); ?></option>
									<?php foreach ( $schedule_consultants as $schedule_consultant ) : ?>
										<option value="<?php echo esc_attr( $schedule_consultant->ID ); ?>" <?php selected( $requested_consultant, $schedule_consultant->ID ); ?>><?php echo esc_html( $schedule_consultant->display_name ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label><?php esc_html_e( 'Date', 'wellness-consult' ); ?><br>
								<input type="date" name="schedule_date" value="<?php echo esc_attr( $requested_date ); ?>" required>
							</label>
							<label><?php esc_html_e( 'Duration (minutes)', 'wellness-consult' ); ?><br>
								<input type="number" name="schedule_duration" min="5" max="480" step="5" value="<?php echo esc_attr( $requested_duration ); ?>" required>
							</label>
							<button type="submit" class="button"><?php esc_html_e( 'Show available times', 'wellness-consult' ); ?></button>
						</form>

						<?php if ( empty( $schedule_consultants ) ) : ?>
							<div class="notice notice-warning inline"><p><?php esc_html_e( 'No consultant accounts are available. Add a consultant and configure their availability first.', 'wellness-consult' ); ?></p></div>
						<?php elseif ( $requested_consultant && empty( $available_slots ) ) : ?>
							<div class="notice notice-info inline"><p><?php esc_html_e( 'No free slots are available for this consultant, date, and duration. Check availability settings or choose another date.', 'wellness-consult' ); ?></p></div>
						<?php endif; ?>
						<?php if ( empty( $active_platforms ) ) : ?>
							<div class="notice notice-warning inline">
								<p><?php esc_html_e( 'No active platform is configured for this consultation mode. Enable one under Consultations > Settings before scheduling.', 'wellness-consult' ); ?></p>
							</div>
						<?php endif; ?>

						<form method="post" action="">
							<?php wp_nonce_field( 'wc_schedule_action_' . $req_id, 'wc_schedule_nonce' ); ?>
							<input type="hidden" name="wc_schedule_request" value="1">
							<input type="hidden" name="schedule_consultant" value="<?php echo esc_attr( $requested_consultant ); ?>">
							<input type="hidden" name="schedule_date" value="<?php echo esc_attr( $requested_date ); ?>">
							<input type="hidden" name="schedule_duration" value="<?php echo esc_attr( $requested_duration ); ?>">
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="schedule_slot"><?php esc_html_e( 'Available start time (patient and consultant time zones)', 'wellness-consult' ); ?></label></th>
									<td>
										<select name="schedule_slot" id="schedule_slot" class="regular-text" required <?php disabled( empty( $available_slots ) ); ?>>
											<option value=""><?php esc_html_e( 'Select an available time', 'wellness-consult' ); ?></option>
											<?php foreach ( $available_slots as $slot_utc => $slot_label ) : ?>
												<option value="<?php echo esc_attr( $slot_utc ); ?>" <?php selected( $appointment_utc, $slot_utc ); ?>><?php echo esc_html( $slot_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="schedule_platform"><?php esc_html_e( 'Active platform', 'wellness-consult' ); ?></label></th>
									<td>
										<select name="schedule_platform" id="schedule_platform" class="regular-text" required>
											<option value=""><?php esc_html_e( 'Select platform', 'wellness-consult' ); ?></option>
											<?php foreach ( $active_platforms as $platform_key => $platform_info ) : ?>
												<option value="<?php echo esc_attr( $platform_key ); ?>" <?php selected( $platform, $platform_info['name'] ?? '' ); ?>><?php echo esc_html( $platform_info['name'] ?? $platform_key ); ?></option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php esc_html_e( 'Options are limited to active platforms for the selected consultation mode.', 'wellness-consult' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="meeting_link_or_handle"><?php echo 'video' === $consult_mode ? esc_html__( 'Meeting link (required)', 'wellness-consult' ) : esc_html__( 'Phone number or username (required)', 'wellness-consult' ); ?></label></th>
									<td><input type="text" name="meeting_link_or_handle" id="meeting_link_or_handle" value="<?php echo esc_attr( $meeting_link ); ?>" class="regular-text" required></td>
								</tr>
							</table>
							<p class="submit"><button type="submit" class="button button-primary" <?php disabled( empty( $available_slots ) || empty( $active_platforms ) ); ?>><?php echo WC_Status::STATUS_SCHEDULED === $status ? esc_html__( 'Save Reschedule', 'wellness-consult' ) : esc_html__( 'Schedule Appointment', 'wellness-consult' ); ?></button></p>
						</form>
					</div>
				<?php endif; ?>

				<?php
				$can_act_on_appointment = $can_verify || ( absint( $assigned_consultant_id ) === $current_user_id );
				$can_cancel_or_refund = $can_verify;
				?>
				<?php if ( $can_act_on_appointment && in_array( $status, array( WC_Status::STATUS_PAID_AWAITING_SCHEDULING, WC_Status::STATUS_SCHEDULED, WC_Status::STATUS_CANCELLED ), true ) ) : ?>
					<div class="postbox" style="padding:16px;margin-top:16px;">
						<h2 class="hndle"><?php esc_html_e( 'Request Actions', 'wellness-consult' ); ?></h2>
						<?php if ( WC_Status::STATUS_SCHEDULED === $status ) : ?>
							<?php foreach ( array( 'complete' => __( 'Mark completed', 'wellness-consult' ), 'no_show' => __( 'Mark no-show', 'wellness-consult' ) ) as $action_key => $action_label ) : ?>
								<form method="post" style="display:inline-block;margin:8px 8px 0 0;">
									<?php wp_nonce_field( 'wc_request_action_' . $req_id, 'wc_request_action_nonce' ); ?>
									<input type="hidden" name="wc_request_action" value="1">
									<input type="hidden" name="request_action" value="<?php echo esc_attr( $action_key ); ?>">
									<button type="submit" class="button button-secondary"><?php echo esc_html( $action_label ); ?></button>
								</form>
							<?php endforeach; ?>
						<?php endif; ?>
						<?php if ( $can_cancel_or_refund && in_array( $status, array( WC_Status::STATUS_PAID_AWAITING_SCHEDULING, WC_Status::STATUS_SCHEDULED ), true ) ) : ?>
							<form method="post" style="margin-top:12px;">
								<?php wp_nonce_field( 'wc_request_action_' . $req_id, 'wc_request_action_nonce' ); ?>
								<input type="hidden" name="wc_request_action" value="1">
								<input type="hidden" name="request_action" value="cancel">
								<label for="action_cancel_reason"><strong><?php esc_html_e( 'Cancellation reason', 'wellness-consult' ); ?></strong></label>
								<textarea id="action_cancel_reason" name="action_reason" class="large-text" rows="2" required></textarea>
								<button type="submit" class="button button-secondary" onclick="return confirm('<?php echo esc_attr__( 'Cancel this consultation request?', 'wellness-consult' ); ?>');"><?php esc_html_e( 'Cancel request', 'wellness-consult' ); ?></button>
							</form>
						<?php endif; ?>
						<?php if ( $can_cancel_or_refund && ! $is_online_payment && in_array( $status, array( WC_Status::STATUS_PAID_AWAITING_SCHEDULING, WC_Status::STATUS_SCHEDULED, WC_Status::STATUS_CANCELLED ), true ) ) : ?>
							<form method="post" style="margin-top:12px;">
								<?php wp_nonce_field( 'wc_request_action_' . $req_id, 'wc_request_action_nonce' ); ?>
								<input type="hidden" name="wc_request_action" value="1">
								<input type="hidden" name="request_action" value="refund">
								<label for="manual_refund_amount"><strong><?php esc_html_e( 'Manual refund amount (USD)', 'wellness-consult' ); ?></strong></label>
								<input type="number" id="manual_refund_amount" name="refund_amount" min="0.01" max="<?php echo esc_attr( $amount_received ?: $amount_expected ); ?>" step="0.01" value="<?php echo esc_attr( $refund_amount ? number_format( (float) $refund_amount, 2, '.', '' ) : '' ); ?>" required>
								<label for="manual_refund_reason"><strong><?php esc_html_e( 'Refund note / reason', 'wellness-consult' ); ?></strong></label>
								<textarea id="manual_refund_reason" name="action_reason" class="large-text" rows="2" required></textarea>
								<button type="submit" class="button button-secondary"><?php esc_html_e( 'Record manual refund', 'wellness-consult' ); ?></button>
							</form>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Right Column: Payment Proof & Security Records -->
			<div>
				<?php if ( $is_online_payment ) : ?>
					<!-- Online Gateway Verification Card (Phase 5: Stripe & PayPal) -->
					<div class="postbox" style="padding:16px;">
						<h2 class="hndle" style="margin:0 0 12px 0;display:flex;align-items:center;justify-content:space-between;">
							<span><?php esc_html_e( 'Online Payment Verification', 'wellness-consult' ); ?></span>
							<span class="badge" style="background:#0284c7;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700;">
								<?php echo esc_html( ( 'stripe' === $pay_method || 'stripe' === $pay_method_id ) ? 'Stripe Checkout' : 'PayPal Secure' ); ?>
							</span>
						</h2>

						<table style="width:100%;margin-bottom:14px;font-size:13px;line-height:1.6;">
							<tr>
								<td style="color:#64748b;width:140px;"><?php esc_html_e( 'Gateway Provider:', 'wellness-consult' ); ?></td>
								<td><strong><?php echo esc_html( $pay_method_name ? $pay_method_name : ucfirst( $pay_method ) ); ?></strong></td>
							</tr>
							<tr>
								<td style="color:#64748b;"><?php esc_html_e( 'Amount Expected:', 'wellness-consult' ); ?></td>
								<td><strong style="color:#0f3b4c;font-size:15px;">$<?php echo esc_html( number_format( (float) ( $amount_expected ? $amount_expected : $fee_usd ), 2 ) ); ?> USD</strong></td>
							</tr>
							<tr>
								<td style="color:#64748b;"><?php esc_html_e( 'Transaction Reference:', 'wellness-consult' ); ?></td>
								<td>
									<?php if ( ! empty( $payment_provider_id ) ) : ?>
										<code style="font-weight:700;font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:3px;word-break:break-all;"><?php echo esc_html( $payment_provider_id ); ?></code>
									<?php else : ?>
										<em style="color:#94a3b8;"><?php esc_html_e( 'Awaiting webhook confirmation', 'wellness-consult' ); ?></em>
									<?php endif; ?>
								</td>
							</tr>
							<?php if ( ! empty( $paid_at ) ) : ?>
								<tr>
									<td style="color:#64748b;"><?php esc_html_e( 'Payment Confirmed At:', 'wellness-consult' ); ?></td>
									<td><code><?php echo esc_html( $paid_at ); ?> UTC</code></td>
								</tr>
							<?php endif; ?>
							<?php if ( ! empty( $amount_received ) ) : ?>
								<tr>
									<td style="color:#64748b;"><?php esc_html_e( 'Amount Captured:', 'wellness-consult' ); ?></td>
									<td><strong style="color:#166534;font-size:14px;">$<?php echo esc_html( number_format( (float) $amount_received, 2 ) ); ?> USD</strong></td>
								</tr>
							<?php endif; ?>
						</table>

						<?php if ( WC_Status::STATUS_AWAITING_PAYMENT === $status ) : ?>
							<div class="notice notice-info inline" style="margin:10px 0;">
								<p>
									<span class="dashicons dashicons-clock" style="vertical-align:text-top;font-size:16px;"></span>
									<?php esc_html_e( 'Awaiting online checkout completion by the patient. Abandoned requests expire automatically after 24 hours.', 'wellness-consult' ); ?>
								</p>
							</div>
						<?php elseif ( WC_Status::STATUS_EXPIRED === $status ) : ?>
							<div class="notice notice-warning inline" style="margin:10px 0;">
								<p>
									<span class="dashicons dashicons-warning" style="vertical-align:text-top;font-size:16px;"></span>
									<?php esc_html_e( 'This request expired after 24 hours without completed checkout.', 'wellness-consult' ); ?>
								</p>
							</div>
						<?php elseif ( WC_Status::STATUS_REFUNDED === $status ) : ?>
							<div style="background:#fef2f2;border:1px solid #fecaca;padding:12px;border-radius:6px;margin-top:12px;">
								<div style="color:#991b1b;font-weight:700;font-size:13px;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
									<span class="dashicons dashicons-undo"></span>
									<?php esc_html_e( 'Online Payment Refunded', 'wellness-consult' ); ?>
								</div>
								<div style="font-size:12px;color:#7f1d1d;line-height:1.5;">
									<strong><?php esc_html_e( 'Refund Amount:', 'wellness-consult' ); ?></strong> $<?php echo esc_html( number_format( (float) ( $refund_amount ? $refund_amount : $amount_received ), 2 ) ); ?> USD<br>
									<?php if ( ! empty( $refund_id ) ) : ?>
										<strong><?php esc_html_e( 'Refund ID:', 'wellness-consult' ); ?></strong> <code><?php echo esc_html( $refund_id ); ?></code><br>
									<?php endif; ?>
									<?php if ( ! empty( $refund_reason ) ) : ?>
										<strong><?php esc_html_e( 'Reason:', 'wellness-consult' ); ?></strong> <?php echo esc_html( $refund_reason ); ?><br>
									<?php endif; ?>
									<?php if ( ! empty( $refunded_at ) ) : ?>
										<strong><?php esc_html_e( 'Refunded At:', 'wellness-consult' ); ?></strong> <code><?php echo esc_html( $refunded_at ); ?> UTC</code>
									<?php endif; ?>
								</div>
							</div>
						<?php elseif ( in_array( $status, array( WC_Status::STATUS_PAID_AWAITING_SCHEDULING, WC_Status::STATUS_SCHEDULED, WC_Status::STATUS_CANCELLED ), true ) && $is_admin ) : ?>
							<!-- Gateway Refund Action Box (FR-12) -->
							<div style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:6px;margin-top:14px;">
								<details>
									<summary style="cursor:pointer;color:#dc2626;font-weight:600;font-size:13px;">
										<span class="dashicons dashicons-undo" style="vertical-align:text-top;font-size:16px;"></span>
										<?php esc_html_e( 'Process Gateway Refund (Stripe/PayPal)', 'wellness-consult' ); ?>
									</summary>
									<form method="post" action="" style="margin-top:10px;">
										<?php wp_nonce_field( 'wc_refund_action_' . $req_id, 'wc_refund_nonce' ); ?>
										<input type="hidden" name="wc_gateway_refund" value="1" />

										<div style="margin-bottom:8px;">
											<label for="refund_amount" style="font-weight:600;display:block;margin-bottom:3px;font-size:12px;">
												<?php esc_html_e( 'Refund Amount (USD) *', 'wellness-consult' ); ?>
											</label>
											<input type="number" step="0.01" min="0.01" name="refund_amount" id="refund_amount" value="<?php echo esc_attr( number_format( (float) ( $amount_received ? $amount_received : $fee_usd ), 2, '.', '' ) ); ?>" class="regular-text" style="width:100%;font-size:13px;" required />
										</div>

										<div style="margin-bottom:8px;">
											<label for="refund_reason" style="font-weight:600;display:block;margin-bottom:3px;font-size:12px;">
												<?php esc_html_e( 'Refund Reason *', 'wellness-consult' ); ?>
											</label>
											<textarea name="refund_reason" id="refund_reason" rows="2" style="width:100%;font-size:12px;" placeholder="<?php esc_attr_e( 'e.g. Patient requested cancellation within refund window; doctor unavailable.', 'wellness-consult' ); ?>" required></textarea>
										</div>

										<button type="submit" class="button button-secondary" style="color:#b91c1c;border-color:#fca5a5;width:100%;font-weight:700;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to process this online refund through the payment gateway? This action cannot be undone.', 'wellness-consult' ); ?>');">
											<?php esc_html_e( 'Execute Online Refund via Gateway', 'wellness-consult' ); ?>
										</button>
									</form>
								</details>
							</div>
						<?php endif; ?>
					</div>
				<?php else : ?>
				<!-- Payment Proof Card (CR-2 Requirement 5) -->
				<div class="postbox" style="padding:16px;">
					<h2 class="hndle" style="margin:0 0 12px 0;display:flex;align-items:center;justify-content:space-between;">
						<span><?php esc_html_e( 'Payment Proof Verification', 'wellness-consult' ); ?></span>
						<span class="notice notice-info inline">
							<?php echo esc_html( WC_Options::get_manual_payment_type_badge( $pay_method_type ? $pay_method_type : 'bank' ) ); ?>
						</span>
					</h2>

					<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;align-items:start;">
					<table style="width:100%;margin-bottom:14px;font-size:13px;line-height:1.6;">
						<tr>
							<td style="color:#64748b;width:140px;"><?php esc_html_e( 'Payment Method:', 'wellness-consult' ); ?></td>
							<td>
								<strong><?php echo esc_html( $pay_method_name ? $pay_method_name : ucfirst( $pay_method ) ); ?></strong>
								<div class="description">
									<?php
									echo esc_html(
										$is_online_payment
											? __( 'Online payment', 'wellness-consult' )
											: WC_Options::get_manual_payment_type_label( $pay_method_type ? $pay_method_type : 'bank' )
									);
									?>
								</div>
							</td>
						</tr>
						<tr>
							<td style="color:#64748b;"><?php esc_html_e( 'Amount Expected:', 'wellness-consult' ); ?></td>
							<td><strong>$<?php echo esc_html( number_format( (float) ( $amount_expected ? $amount_expected : $fee_usd ), 2 ) ); ?> USD</strong></td>
						</tr>
						<tr>
							<td style="color:#64748b;"><?php esc_html_e( 'Amount Received (USD):', 'wellness-consult' ); ?></td>
							<td>
								<?php if ( '' !== (string) $amount_received ) : ?>
									<strong>$<?php echo esc_html( number_format( (float) $amount_received, 2 ) ); ?> USD</strong>
								<?php else : ?>
									<em style="color:#94a3b8;"><?php esc_html_e( 'Not recorded', 'wellness-consult' ); ?></em>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td style="color:#64748b;"><?php esc_html_e( 'Transaction ID / Sender:', 'wellness-consult' ); ?></td>
							<td>
								<?php if ( ! empty( $trans_id ) ) : ?>
									<code style="font-weight:700;font-size:13px;background:#f1f5f9;padding:2px 6px;border-radius:3px;"><?php echo esc_html( $trans_id ); ?></code>
								<?php else : ?>
									<em style="color:#94a3b8;"><?php esc_html_e( 'Not provided by patient', 'wellness-consult' ); ?></em>
								<?php endif; ?>
							</td>
						</tr>
					</table>

					<!-- Screenshot Proof Viewer -->
					<?php if ( ! empty( $proof_file ) ) : ?>
						<?php
						$proof_filename = ! empty( $current_proof_entry['filename'] )
							? $current_proof_entry['filename']
							: basename( $proof_file );
						?>
						<div class="notice notice-info inline wc-proof-viewer">
							<?php if ( in_array( $current_proof_mime, array( 'image/jpeg', 'image/png' ), true ) ) : ?>
								<a class="wc-proof-preview-link" href="<?php echo esc_url( $proof_view_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Open full-size submitted payment proof in a new tab', 'wellness-consult' ); ?>">
									<img class="wc-proof-preview" src="<?php echo esc_url( $proof_view_url ); ?>" alt="<?php esc_attr_e( 'Submitted payment proof preview', 'wellness-consult' ); ?>" onerror="this.hidden=true;this.parentNode.nextElementSibling.hidden=false;">
								</a>
								<p class="wc-proof-load-error" role="alert" hidden><?php esc_html_e( 'Proof file could not be loaded', 'wellness-consult' ); ?></p>
							<?php else : ?>
								<span class="dashicons dashicons-media-document wc-proof-pdf-icon" aria-hidden="true"></span>
								<p class="wc-proof-filename"><?php echo esc_html( $proof_filename ); ?></p>
							<?php endif; ?>
							<?php if ( in_array( $current_proof_mime, array( 'image/jpeg', 'image/png' ), true ) ) : ?>
								<p class="wc-proof-filename"><?php echo esc_html( $proof_filename ); ?></p>
							<?php endif; ?>
							<a href="<?php echo esc_url( $proof_view_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary button-small">
								<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
								<?php esc_html_e( 'Open Proof', 'wellness-consult' ); ?>
							</a>
						</div>
					<?php else : ?>
						<div class="notice notice-warning inline" style="margin:10px 0 16px 0;">
							<p><?php esc_html_e( 'No proof file uploaded for this request.', 'wellness-consult' ); ?></p>
						</div>
					<?php endif; ?>
					</div>

					<!-- Status: Pending Verification -> Approval Form -->
					<?php if ( $can_verify && WC_Status::STATUS_PENDING_VERIFICATION === $status ) : ?>
						<div class="postbox" style="padding:14px;margin-top:12px;">
							<h3 style="margin:0 0 10px 0;font-size:14px;display:flex;align-items:center;gap:6px;">
								<span class="dashicons dashicons-yes-alt"></span>
								<?php esc_html_e( 'Approve Payment Verification', 'wellness-consult' ); ?>
							</h3>

							<form method="post" action="">
								<?php wp_nonce_field( 'wc_verify_payment_action_' . $req_id, 'wc_verify_payment_nonce' ); ?>
								<input type="hidden" name="wc_verify_payment" value="1" />

								<div style="margin-bottom:10px;">
									<label for="wc-input-amount-received" style="font-weight:600;display:block;margin-bottom:3px;font-size:13px;">
										<?php esc_html_e( 'Amount received (USD) *', 'wellness-consult' ); ?>
									</label>
									<input type="number" step="0.01" min="0.01" name="amount_received" id="wc-input-amount-received" class="regular-text" style="width:100%;font-size:14px;font-weight:600;" value="<?php echo esc_attr( $amount_received ? number_format( (float) $amount_received, 2, '.', '' ) : '' ); ?>" placeholder="e.g. <?php echo esc_attr( number_format( (float) ( $amount_expected ? $amount_expected : $fee_usd ), 2, '.', '' ) ); ?>" required />
									
									<div id="wc-underpayment-alert" class="notice notice-warning inline" style="display:none;margin-top:6px;">
										⚠️ <?php printf( esc_html__( 'Warning: Amount received is lower than expected fee ($%s USD). An underpayment alert will be recorded in the permanent audit log.', 'wellness-consult' ), esc_html( number_format( (float) ( $amount_expected ? $amount_expected : $fee_usd ), 2 ) ) ); ?>
									</div>
								</div>

								<fieldset class="wc-verification-checklist">
									<legend><strong><?php esc_html_e( 'Required verification checklist', 'wellness-consult' ); ?></strong></legend>
									<?php foreach ( $checklist_labels as $check_key => $check_label ) : ?>
										<label style="display:block;margin:8px 0;">
											<input type="checkbox" class="wc-verification-check" name="verification_checklist[]" value="<?php echo esc_attr( $check_key ); ?>">
											<?php echo esc_html( $check_label ); ?>
										</label>
									<?php endforeach; ?>
								</fieldset>

								<div style="margin:12px 0;">
									<label for="underpayment_override_reason" style="font-weight:600;display:block;margin-bottom:3px;">
										<?php esc_html_e( 'Underpayment override reason (required only when amount is below expected)', 'wellness-consult' ); ?>
									</label>
									<textarea name="underpayment_override_reason" id="underpayment_override_reason" rows="2" class="large-text"></textarea>
								</div>

								<label for="verification_note"><?php esc_html_e( 'Optional verification note', 'wellness-consult' ); ?></label>
								<textarea name="verification_note" id="verification_note" rows="2" class="large-text"></textarea>

								<button type="submit" class="button button-primary" id="wc-approve-payment-button" disabled>
									<span class="dashicons dashicons-yes" style="vertical-align:text-top;font-size:16px;"></span>
									<?php esc_html_e( 'Verify & Approve Payment', 'wellness-consult' ); ?>
								</button>
							</form>

							<script>
							(function() {
								var expectedFee = <?php echo (float) ( $amount_expected ? $amount_expected : $fee_usd ); ?>;
								var input = document.getElementById('wc-input-amount-received');
								var alertBox = document.getElementById('wc-underpayment-alert');
								var override = document.getElementById('underpayment_override_reason');
								var checks = document.querySelectorAll('.wc-verification-check');
								var approve = document.getElementById('wc-approve-payment-button');
								function updateApprovalState() {
									var val = input ? parseFloat(input.value) : 0;
									var underpaid = !isNaN(val) && val > 0 && val < expectedFee;
									var allChecked = Array.prototype.every.call(checks, function(check) { return check.checked; });
									if (alertBox) {
										alertBox.style.display = underpaid ? 'block' : 'none';
									}
									if (approve) {
										approve.disabled = !(allChecked && val > 0 && (!underpaid || (override && override.value.trim() !== '')));
									}
								}
								if (input) {
									input.addEventListener('input', updateApprovalState);
								}
								if (override) {
									override.addEventListener('input', updateApprovalState);
								}
								Array.prototype.forEach.call(checks, function(check) {
									check.addEventListener('change', updateApprovalState);
								});
							})();
							</script>
						</div>

						<!-- Reject Payment -->
						<details style="margin-top:14px;">
							<summary style="cursor:pointer;font-weight:600;font-size:13px;">
								<span class="dashicons dashicons-dismiss" style="vertical-align:text-top;font-size:16px;"></span>
								<?php esc_html_e( 'Reject payment', 'wellness-consult' ); ?>
							</summary>
							<form method="post" action="" style="margin-top:10px;">
								<?php wp_nonce_field( 'wc_reject_payment_action_' . $req_id, 'wc_reject_payment_nonce' ); ?>
								<input type="hidden" name="wc_reject_payment" value="1" />

								<label for="rejection_reason" style="font-weight:600;display:block;margin-bottom:3px;font-size:12px;">
									<?php esc_html_e( 'Rejection Reason *', 'wellness-consult' ); ?>
								</label>
								<textarea name="rejection_reason" id="rejection_reason" rows="2" style="width:100%;font-size:12px;" placeholder="<?php esc_attr_e( 'e.g. Screenshot blurry; transaction amount does not match.', 'wellness-consult' ); ?>" required></textarea>

								<button type="submit" class="button button-secondary" style="margin-top:8px;" onclick="return confirm('<?php echo esc_attr__( 'Reject this payment? The patient will receive a rejection email with this reason.', 'wellness-consult' ); ?>');">
									<?php esc_html_e( 'Reject payment', 'wellness-consult' ); ?>
								</button>
							</form>
						</details>

						<details style="margin-top:12px;">
							<summary style="cursor:pointer;font-weight:600;"><?php esc_html_e( 'Request a new screenshot', 'wellness-consult' ); ?></summary>
							<form method="post" style="margin-top:10px;">
								<?php wp_nonce_field( 'wc_request_new_proof_action_' . $req_id, 'wc_request_new_proof_nonce' ); ?>
								<input type="hidden" name="wc_request_new_proof" value="1">
								<label for="new_proof_reason"><?php esc_html_e( 'Reason *', 'wellness-consult' ); ?></label>
								<textarea name="new_proof_reason" id="new_proof_reason" rows="2" class="large-text" required></textarea>
								<button type="submit" class="button button-secondary"><?php esc_html_e( 'Record screenshot request', 'wellness-consult' ); ?></button>
							</form>
						</details>
						<?php if ( $new_proof_reason ) : ?>
							<p class="description"><?php printf( esc_html__( 'Latest screenshot request: %s', 'wellness-consult' ), esc_html( $new_proof_reason ) ); ?></p>
						<?php endif; ?>

					<?php elseif ( ! empty( $amount_received ) ) : ?>
						<!-- Already Approved Summary -->
						<div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:12px;border-radius:6px;margin-top:12px;">
							<div style="color:#166534;font-weight:700;font-size:13px;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
								<span class="dashicons dashicons-yes-alt"></span>
								<?php esc_html_e( 'Payment Verified & Approved', 'wellness-consult' ); ?>
							</div>
							<div style="font-size:13px;margin-bottom:4px;">
								<strong><?php esc_html_e( 'Amount Received:', 'wellness-consult' ); ?></strong>
								<span style="color:#0f3b4c;font-weight:700;">$<?php echo esc_html( number_format( (float) $amount_received, 2 ) ); ?> USD</span>
								<?php if ( (float) $amount_received < (float) $amount_expected ) : ?>
									<span style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:1px 6px;border-radius:3px;font-size:11px;font-weight:700;margin-left:6px;">
										⚠️ <?php esc_html_e( 'Underpayment', 'wellness-consult' ); ?>
									</span>
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $verification_note ) ) : ?>
								<div style="font-size:12px;color:#334155;margin-top:4px;">
									<strong><?php esc_html_e( 'Note:', 'wellness-consult' ); ?></strong> <?php echo esc_html( $verification_note ); ?>
								</div>
							<?php endif; ?>
							<?php if ( is_array( $verification_checklist ) && ! empty( $verification_checklist['checked_by'] ) ) : ?>
								<div style="font-size:12px;margin-top:8px;">
									<strong><?php esc_html_e( 'Verification checklist completed by:', 'wellness-consult' ); ?></strong>
									<?php
									$verified_user = get_userdata( absint( $verification_checklist['checked_by'] ) );
									echo esc_html( $verified_user ? $verified_user->display_name : __( 'Unknown user', 'wellness-consult' ) );
									?>
									<?php if ( ! empty( $verification_checklist['checked_at'] ) ) : ?>
										<?php printf( esc_html__( ' on %s UTC', 'wellness-consult' ), esc_html( $verification_checklist['checked_at'] ) ); ?>
									<?php endif; ?>
								</div>
								<ul style="margin:6px 0 0 18px;font-size:12px;">
									<?php foreach ( (array) ( $verification_checklist['checks'] ?? array() ) as $check_key ) : ?>
										<li><?php echo esc_html( $checklist_labels[ $check_key ] ?? $check_key ); ?></li>
									<?php endforeach; ?>
								</ul>
								<?php if ( ! empty( $verification_checklist['override_reason'] ) ) : ?>
									<div style="font-size:12px;margin-top:6px;">
										<strong><?php esc_html_e( 'Underpayment override reason:', 'wellness-consult' ); ?></strong>
										<?php echo esc_html( $verification_checklist['override_reason'] ); ?>
									</div>
								<?php endif; ?>
							<?php endif; ?>
						</div>

					<?php elseif ( WC_Status::STATUS_REJECTED === $status ) : ?>
						<!-- Rejected Summary -->
						<div style="background:#fef2f2;border:1px solid #fecaca;padding:12px;border-radius:6px;margin-top:12px;">
							<div style="color:#991b1b;font-weight:700;font-size:13px;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
								<span class="dashicons dashicons-dismiss"></span>
								<?php esc_html_e( 'Proof Screenshot Rejected', 'wellness-consult' ); ?>
							</div>
							<p style="margin:0;font-size:12px;color:#7f1d1d;">
								<strong><?php esc_html_e( 'Reason:', 'wellness-consult' ); ?></strong> <?php echo esc_html( $rejection_reason ); ?>
							</p>
						</div>
					<?php endif; ?>
				</div>
				<?php endif; // End manual vs online payment card ?>

				<!-- Proof History -->
				<?php if ( ! empty( $proof_history ) && is_array( $proof_history ) ) : ?>
					<div class="postbox" style="padding:16px;">
						<h2 class="hndle" style="margin:0 0 12px 0;"><?php esc_html_e( 'Proof File Audit History', 'wellness-consult' ); ?></h2>
						<ul style="margin:0;padding-left:14px;font-size:12px;color:#475569;line-height:1.6;">
							<?php foreach ( $proof_history as $idx => $pentry ) : ?>
								<li style="margin-bottom:8px;">
									<strong>Upload #<?php echo esc_html( $idx + 1 ); ?>:</strong> <?php echo esc_html( $pentry['uploaded_at'] ?? '—' ); ?><br>
									<span style="font-family:monospace;font-size:11px;color:#64748b;">SHA-256: <?php echo esc_html( substr( $pentry['hash'] ?? '', 0, 16 ) ); ?>...</span>
									<?php
									$history_proof_url = WC_Proof_Viewer::get_view_url( $req_id, $idx );
									?>
									<a class="button button-small" href="<?php echo esc_url( $history_proof_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View proof', 'wellness-consult' ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<!-- Consent & Compliance Audit Record -->
				<div class="postbox" style="padding:16px;">
					<h2 class="hndle" style="margin:0 0 12px 0;"><?php esc_html_e( 'Consent & Privacy Record', 'wellness-consult' ); ?></h2>
					<table style="width:100%;font-size:12px;line-height:1.6;">
						<tr>
							<td style="color:#64748b;"><?php esc_html_e( 'Consent Given:', 'wellness-consult' ); ?></td>
							<td><strong>Yes (18+, Terms, Privacy, Refund, Health Data)</strong></td>
						</tr>
						<tr>
							<td style="color:#64748b;"><?php esc_html_e( 'Timestamp (UTC):', 'wellness-consult' ); ?></td>
							<td><code><?php echo esc_html( $consent_record['timestamp'] ?? get_the_date( 'Y-m-d H:i:s', $req_id ) ); ?></code></td>
						</tr>
						<tr>
							<td style="color:#64748b;"><?php esc_html_e( 'Anonymized IP Hash:', 'wellness-consult' ); ?></td>
							<td><code style="font-size:11px;"><?php echo esc_html( substr( $ip_hash, 0, 20 ) ); ?>...</code></td>
						</tr>
					</table>
				</div>

				<section class="postbox wc-request-activity" aria-labelledby="wc-request-activity-title">
					<div class="inside">
						<h2 id="wc-request-activity-title" class="hndle"><?php esc_html_e( 'Activity for this request', 'wellness-consult' ); ?></h2>
						<?php if ( ! empty( $request_activity ) ) : ?>
							<ol class="wc-request-activity-list">
								<?php foreach ( $request_activity as $activity_entry ) : ?>
									<li>
										<div class="wc-request-activity-meta">
											<time><?php echo esc_html( $activity_entry->created_at ); ?> UTC</time>
											<span><?php echo esc_html( $activity_entry->user_name ? $activity_entry->user_name : __( 'System', 'wellness-consult' ) ); ?></span>
										</div>
										<strong><?php echo esc_html( ucwords( str_replace( '_', ' ', sanitize_key( $activity_entry->action ) ) ) ); ?></strong>
										<?php if ( '' !== (string) $activity_entry->note ) : ?>
											<p><?php echo esc_html( $activity_entry->note ); ?></p>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ol>
						<?php else : ?>
							<p class="description"><?php esc_html_e( 'No activity has been recorded for this request.', 'wellness-consult' ); ?></p>
						<?php endif; ?>
					</div>
				</section>
			</div>
		</div>
	</div>

	<?php
	return;
endif;

// List View
$selected_status = isset( $_GET['status_filter'] ) ? sanitize_key( wp_unslash( $_GET['status_filter'] ) ) : '';
$selected_booking_filter = $is_admin && isset( $_GET['booking_filter'] ) ? sanitize_key( wp_unslash( $_GET['booking_filter'] ) ) : '';
$selected_mode   = isset( $_GET['mode_filter'] ) ? sanitize_key( wp_unslash( $_GET['mode_filter'] ) ) : '';
$selected_appointment_filter = isset( $_GET['appointment_filter'] ) ? sanitize_key( wp_unslash( $_GET['appointment_filter'] ) ) : '';
$search_term     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page = 20;

$query_args = array(
	'post_type'      => 'wc_request',
	'post_status'    => 'unpaid_drafts' === $selected_booking_filter ? 'draft' : 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

$meta_query = array();

if ( 'unpaid_drafts' === $selected_booking_filter ) {
	$meta_query[] = array(
		'key'   => '_wc_is_draft',
		'value' => '1',
	);
}

if ( ! empty( $selected_status ) ) {
	$meta_query[] = array(
		'key'   => '_wc_status',
		'value' => $selected_status,
	);
}

if ( ! empty( $selected_mode ) ) {
	$meta_query[] = array(
		'key'   => '_wc_consult_mode',
		'value' => $selected_mode,
	);
}

if ( in_array( $selected_appointment_filter, array( 'today', 'tomorrow', 'this_week' ), true ) ) {
	$site_timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( wp_timezone_string() ?: 'UTC' );
	$now_local = new DateTimeImmutable( 'now', $site_timezone );
	if ( 'today' === $selected_appointment_filter ) {
		$range_start_local = $now_local->setTime( 0, 0 );
		$range_end_local = $range_start_local->modify( '+1 day' );
	} elseif ( 'tomorrow' === $selected_appointment_filter ) {
		$range_start_local = $now_local->setTime( 0, 0 )->modify( '+1 day' );
		$range_end_local = $range_start_local->modify( '+1 day' );
	} else {
		$range_start_local = $now_local->setTime( 0, 0 )->modify( 'monday this week' );
		$range_end_local = $range_start_local->modify( '+1 week' );
	}
	$range_start = $range_start_local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	$range_end = $range_end_local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	$meta_query[] = array(
		'key'     => '_wc_appointment_utc',
		'value'   => $range_start,
		'compare' => '>=',
		'type'    => 'DATETIME',
	);
	$meta_query[] = array(
		'key'     => '_wc_appointment_utc',
		'value'   => $range_end,
		'compare' => '<',
		'type'    => 'DATETIME',
	);
}

// Role restriction: Consultant sees ONLY their own assigned requests (Rule 4)
if ( ! $is_admin ) {
	$meta_query[] = array(
		'key'   => '_wc_consultant_id',
		'value' => $current_user_id,
	);
}

if ( ! empty( $meta_query ) ) {
	$query_args['meta_query'] = $meta_query;
}

if ( ! empty( $search_term ) ) {
	$query_args['s'] = $search_term;
}

$requests_query = new WP_Query( $query_args );
$statuses       = WC_Status::get_all_statuses();
$all_countries  = WC_Options::get_all_iso_countries();
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Consultation Requests', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<?php $delete_log_errors = isset( $_GET['delete_log_errors'] ) ? absint( $_GET['delete_log_errors'] ) : 0; ?>
		<div class="notice notice-<?php echo $delete_log_errors ? 'warning' : 'success'; ?> is-dismissible"><p>
			<?php
			$deleted_count = isset( $_GET['deleted_count'] ) ? absint( $_GET['deleted_count'] ) : 0;
			if ( $delete_log_errors ) {
				printf(
					esc_html( _n( '%1$d request was deleted, but its audit entry could not be recorded.', '%1$d requests were deleted, but %2$d audit entries could not be recorded.', $deleted_count, 'wellness-consult' ) ),
					$deleted_count,
					$delete_log_errors
				);
			} else {
				printf(
					esc_html( _n( '%d request permanently deleted.', '%d requests permanently deleted.', $deleted_count, 'wellness-consult' ) ),
					$deleted_count
				);
			}
			?>
		</p></div>
	<?php endif; ?>

	<!-- Search & Filters Bar -->
	<form class="wc-request-filters" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="wc-all-requests" />

		<select name="booking_filter" aria-label="<?php esc_attr_e( 'Request visibility', 'wellness-consult' ); ?>">
			<option value="" <?php selected( $selected_booking_filter, '' ); ?>><?php esc_html_e( 'Submitted requests', 'wellness-consult' ); ?></option>
			<?php if ( $is_admin ) : ?>
				<option value="unpaid_drafts" <?php selected( $selected_booking_filter, 'unpaid_drafts' ); ?>><?php esc_html_e( 'Unpaid drafts', 'wellness-consult' ); ?></option>
			<?php endif; ?>
		</select>
		<select name="status_filter">
			<option value=""><?php esc_html_e( 'All Statuses', 'wellness-consult' ); ?></option>
			<?php foreach ( $statuses as $k => $label ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $selected_status, $k ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="mode_filter">
			<option value=""><?php esc_html_e( 'All Modes', 'wellness-consult' ); ?></option>
			<option value="chat" <?php selected( $selected_mode, 'chat' ); ?>>💬 <?php esc_html_e( 'Chat', 'wellness-consult' ); ?></option>
			<option value="voice" <?php selected( $selected_mode, 'voice' ); ?>>📞 <?php esc_html_e( 'Voice Call', 'wellness-consult' ); ?></option>
			<option value="video" <?php selected( $selected_mode, 'video' ); ?>>📹 <?php esc_html_e( 'Video Call', 'wellness-consult' ); ?></option>
		</select>

		<select name="appointment_filter">
			<option value=""><?php esc_html_e( 'All Appointment Dates', 'wellness-consult' ); ?></option>
			<option value="today" <?php selected( $selected_appointment_filter, 'today' ); ?>><?php esc_html_e( 'Today', 'wellness-consult' ); ?></option>
			<option value="tomorrow" <?php selected( $selected_appointment_filter, 'tomorrow' ); ?>><?php esc_html_e( 'Tomorrow', 'wellness-consult' ); ?></option>
			<option value="this_week" <?php selected( $selected_appointment_filter, 'this_week' ); ?>><?php esc_html_e( 'This week', 'wellness-consult' ); ?></option>
		</select>

		<input type="search" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by Reference ID / Alias...', 'wellness-consult' ); ?>" />
		<input type="submit" class="button" value="<?php esc_attr_e( 'Filter Requests', 'wellness-consult' ); ?>" />
		<?php if ( ! empty( $selected_status ) || ! empty( $selected_mode ) || ! empty( $selected_appointment_filter ) || ! empty( $search_term ) || ! empty( $selected_booking_filter ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests' ) ); ?>" class="button"><?php esc_html_e( 'Reset Filters', 'wellness-consult' ); ?></a>
		<?php endif; ?>
	</form>

	<?php if ( $requests_query->have_posts() ) : ?>
		<div class="wc-request-table-container">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wc-request-bulk-form" onsubmit="return confirm('<?php echo esc_attr__( 'Permanently delete the selected requests and their proof files?', 'wellness-consult' ); ?>');">
				<input type="hidden" name="action" value="wc_bulk_delete_requests">
				<?php wp_nonce_field( 'wc_bulk_delete_requests', 'wc_bulk_delete_nonce' ); ?>
				<div class="wc-request-bulk-actions">
					<?php if ( $can_delete_requests ) : ?>
						<label for="wc-bulk-request-action" class="screen-reader-text"><?php esc_html_e( 'Bulk action', 'wellness-consult' ); ?></label>
						<select id="wc-bulk-request-action" name="wc_bulk_request_action">
							<option value=""><?php esc_html_e( 'Bulk actions', 'wellness-consult' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete permanently', 'wellness-consult' ); ?></option>
						</select>
						<button type="submit" class="button"><?php esc_html_e( 'Apply', 'wellness-consult' ); ?></button>
					<?php endif; ?>
				</div>
				<table class="wp-list-table widefat striped wc-requests-table">
					<thead>
						<tr>
							<?php if ( $can_delete_requests ) : ?>
								<th scope="col" class="manage-column column-cb check-column"><input type="checkbox" class="wc-request-select-all" aria-label="<?php esc_attr_e( 'Select all requests', 'wellness-consult' ); ?>"></th>
							<?php endif; ?>
							<th scope="col" class="wc-request-col-request"><?php esc_html_e( 'Request', 'wellness-consult' ); ?></th>
							<th scope="col" class="wc-request-col-patient"><?php esc_html_e( 'Patient', 'wellness-consult' ); ?></th>
							<th scope="col" class="wc-request-col-consultation"><?php esc_html_e( 'Consultation', 'wellness-consult' ); ?></th>
							<th scope="col" class="wc-request-col-fee"><?php esc_html_e( 'Fee (USD)', 'wellness-consult' ); ?></th>
							<th scope="col" class="wc-request-col-appointment"><?php esc_html_e( 'Appointment', 'wellness-consult' ); ?></th>
							<th scope="col" class="wc-request-col-consultant"><?php esc_html_e( 'Consultant', 'wellness-consult' ); ?></th>
							<th scope="col" class="wc-request-col-status"><?php esc_html_e( 'Status', 'wellness-consult' ); ?></th>
							<th scope="col" class="wc-request-col-actions"><?php esc_html_e( 'Actions', 'wellness-consult' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						while ( $requests_query->have_posts() ) :
							$requests_query->the_post();
							$cur_id    = get_the_ID();
							$ref_id    = get_post_meta( $cur_id, '_wc_reference_id', true );
							$name      = get_post_meta( $cur_id, '_wc_name', true );
							$status    = get_post_meta( $cur_id, '_wc_status', true );
							$type_name = get_post_meta( $cur_id, '_wc_type_name', true );
							$cur_mode  = get_post_meta( $cur_id, '_wc_consult_mode', true );
							if ( empty( $cur_mode ) ) {
								$cur_mode = 'chat';
							}
							$fee_usd   = get_post_meta( $cur_id, '_wc_fee_usd', true );
							$country   = get_post_meta( $cur_id, '_wc_country', true );
							$row_appointment_utc = WC_Scheduling::get_request_utc( $cur_id );
							$row_consultant_id = absint( get_post_meta( $cur_id, '_wc_consultant_id', true ) );
							$row_consultant = $row_consultant_id ? get_userdata( $row_consultant_id ) : false;
							$row_platform = get_post_meta( $cur_id, '_wc_platform', true );
							$row_time_labels = $row_appointment_utc && $row_consultant_id
								? WC_Scheduling::get_time_labels( $row_appointment_utc, get_post_meta( $cur_id, '_wc_timezone', true ) ?: 'UTC', $row_consultant_id )
								: array( 'patient' => '', 'consultant' => '' );
							$p_method  = get_post_meta( $cur_id, '_wc_payment_method', true );
							$p_meth_id = get_post_meta( $cur_id, '_wc_payment_method_id', true );
							$is_online_row = ( 'stripe' === $p_method || 'stripe' === $p_meth_id || 'paypal' === $p_method || 'paypal' === $p_meth_id );
							$row_fraud_data = $can_verify ? WC_Fraud_Protection::get_request_flags( $cur_id ) : array( 'flags' => array() );
							?>
							<tr>
								<?php if ( $can_delete_requests ) : ?>
									<th scope="row" class="check-column"><input type="checkbox" name="request_ids[]" value="<?php echo esc_attr( $cur_id ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select request %s', 'wellness-consult' ), $ref_id ) ); ?>"></th>
								<?php endif; ?>
								<td class="wc-request-col-request">
									<code><strong><?php echo esc_html( $ref_id ? $ref_id : get_the_title() ); ?></strong></code>
									<div class="wc-request-secondary"><?php echo esc_html( get_the_date( 'Y-m-d H:i' ) ); ?></div>
								</td>
								<td class="wc-request-col-patient">
									<strong><?php echo esc_html( $name ? $name : '—' ); ?></strong>
									<div class="wc-request-secondary"><?php echo esc_html( $all_countries[ $country ] ?? ( $country ? $country : '—' ) ); ?></div>
								</td>
								<td class="wc-request-col-consultation">
									<strong><?php echo esc_html( $type_name ? $type_name : '—' ); ?></strong>
									<div><?php echo esc_html( WC_Options::get_mode_icon( $cur_mode ) . ' ' . WC_Options::get_mode_label( $cur_mode ) ); ?></div>
									<?php if ( $row_platform ) : ?><div class="wc-request-secondary"><?php echo esc_html( $row_platform ); ?></div><?php endif; ?>
								</td>
								<td class="wc-request-col-fee"><strong>$<?php echo esc_html( number_format( (float) $fee_usd, 2 ) ); ?></strong></td>
								<td class="wc-request-col-appointment">
									<?php if ( $row_time_labels['patient'] ) : ?>
										<div><?php echo esc_html( $row_time_labels['patient'] ); ?></div>
										<div class="wc-request-secondary"><?php echo esc_html( $row_time_labels['consultant'] ); ?></div>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td class="wc-request-col-consultant"><?php echo esc_html( $row_consultant ? $row_consultant->display_name : '—' ); ?></td>
								<td class="wc-request-col-status">
									<span class="wc-request-status-badge"><?php echo esc_html( WC_Status::get_label( $status ) ); ?></span>
									<?php if ( $is_online_row ) : ?>
										<div class="wc-request-secondary"><?php echo ( 'stripe' === $p_method || 'stripe' === $p_meth_id ) ? 'Stripe' : 'PayPal'; ?></div>
									<?php endif; ?>
									<?php if ( ! empty( $row_fraud_data['flags'] ) ) : ?>
										<span class="wc-request-warning-badge" title="<?php echo esc_attr( implode( '; ', $row_fraud_data['flags'] ) ); ?>"><?php esc_html_e( 'Review flags', 'wellness-consult' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="wc-request-col-actions">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests&action=view&id=' . $cur_id ) ); ?>" class="button button-small"><?php esc_html_e( 'View Details', 'wellness-consult' ); ?></a>
									<?php if ( $can_delete_requests ) : ?>
										<div class="row-actions">
											<span class="delete">
												<a class="submitdelete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wc_delete_request', 'request_id' => $cur_id ), admin_url( 'admin-post.php' ) ), 'wc_delete_request_' . $cur_id ) ); ?>" onclick="return confirm('<?php echo esc_attr( __( 'Permanently delete this request and its proof files?', 'wellness-consult' ) ); ?>');"><?php esc_html_e( 'Delete permanently', 'wellness-consult' ); ?></a>
											</span>
										</div>
									<?php endif; ?>
								</td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			</form>
		</div>
		<?php if ( $can_delete_requests ) : ?>
			<script>
			(function() {
				var selectAll = document.querySelector('.wc-request-select-all');
				if (!selectAll) {
					return;
				}
				selectAll.addEventListener('change', function() {
					document.querySelectorAll('.wc-requests-table input[name="request_ids[]"]').forEach(function(checkbox) {
						checkbox.checked = selectAll.checked;
					});
				});
			})();
			</script>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	<?php else : ?>
		<div class="card" style="padding: 30px; text-align: center; max-width: 600px; margin: 30px auto;">
			<div style="font-size: 32px; margin-bottom: 10px;">📋</div>
			<h2 style="margin-top: 0;"><?php esc_html_e( 'No Consultation Requests Found', 'wellness-consult' ); ?></h2>
			<p class="description" style="margin-bottom: 20px;">
				<?php
				if ( ! $is_admin ) {
					esc_html_e( 'You do not have any patient requests assigned to your consultant profile yet.', 'wellness-consult' );
				} else {
					esc_html_e( 'When patients submit consultation bookings online or via manual transfer, they will securely appear in this table for verification and scheduling.', 'wellness-consult' );
				}
				?>
			</p>
			<?php if ( ! empty( $selected_status ) || ! empty( $search_term ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Clear Search Filters', 'wellness-consult' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
