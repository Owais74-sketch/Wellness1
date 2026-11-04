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
$current_user_id = get_current_user_id();

$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
$req_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

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
	$mode_label       = WC_Options::get_mode_label( $consult_mode );
	$mode_icon        = WC_Options::get_mode_icon( $consult_mode );

	$schedule_notice      = '';
	$schedule_notice_type = 'success';

	// Handle Payment Approval Submission (CR-2 Requirement 5)
	if ( ! empty( $_POST['wc_verify_payment'] ) && check_admin_referer( 'wc_verify_payment_action_' . $req_id, 'wc_verify_payment_nonce' ) ) {
		$raw_received = isset( $_POST['amount_received'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['amount_received'] ) ) ) : '';
		$val_received = floatval( $raw_received );
		$val_note     = sanitize_textarea_field( wp_unslash( $_POST['verification_note'] ?? '' ) );

		if ( '' === $raw_received || $val_received <= 0 ) {
			$schedule_notice      = esc_html__( 'Error: "Amount received (USD)" must be filled with a valid positive amount when approving payment.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( empty( $val_note ) ) {
			$schedule_notice      = esc_html__( 'Error: A verification note is strictly required when approving payment.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} else {
			$expected_val = floatval( $amount_expected ? $amount_expected : $fee_usd );
			$underpayment_warning = '';
			if ( $val_received < $expected_val ) {
				$shortfall = $expected_val - $val_received;
				$underpayment_warning = sprintf( ' [WARNING: Received amount ($%0.2f) is lower than expected ($%0.2f). Shortfall: $%0.2f]', $val_received, $expected_val, $shortfall );
			}

			$full_log_note = sprintf(
				'Payment verified by admin. Received: $%0.2f USD (Expected: $%0.2f USD). Note: %s%s',
				$val_received,
				$expected_val,
				$val_note,
				$underpayment_warning
			);

			$transition_res = WC_Status::change_status( $req_id, WC_Status::STATUS_PAID_AWAITING_SCHEDULING, $full_log_note );
			if ( is_wp_error( $transition_res ) ) {
				$schedule_notice      = $transition_res->get_error_message();
				$schedule_notice_type = 'error';
			} else {
				update_post_meta( $req_id, '_wc_amount_received', $val_received );
				update_post_meta( $req_id, '_wc_verification_note', $val_note );
				update_post_meta( $req_id, '_wc_verified_by', $current_user_id );
				update_post_meta( $req_id, '_wc_verified_at', current_time( 'mysql', 1 ) );
				$status            = WC_Status::STATUS_PAID_AWAITING_SCHEDULING;
				$amount_received   = $val_received;
				$verification_note = $val_note;
				$schedule_notice   = esc_html__( 'Payment verified successfully! Request status updated to Paid - Awaiting Scheduling.', 'wellness-consult' );
				if ( ! empty( $underpayment_warning ) ) {
					$schedule_notice .= ' ' . esc_html__( 'Warning: Underpayment was detected and recorded in the permanent audit log.', 'wellness-consult' );
				}
			}
		}
	}

	// Handle Payment Rejection Submission
	if ( ! empty( $_POST['wc_reject_payment'] ) && check_admin_referer( 'wc_reject_payment_action_' . $req_id, 'wc_reject_payment_nonce' ) ) {
		$rej_reason = sanitize_textarea_field( wp_unslash( $_POST['rejection_reason'] ?? '' ) );
		if ( empty( $rej_reason ) ) {
			$schedule_notice      = esc_html__( 'Error: Rejection reason must be filled when rejecting payment.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} else {
			$transition_res = WC_Status::change_status( $req_id, WC_Status::STATUS_REJECTED, sprintf( 'Proof rejected: %s', $rej_reason ) );
			if ( is_wp_error( $transition_res ) ) {
				$schedule_notice      = $transition_res->get_error_message();
				$schedule_notice_type = 'error';
			} else {
				update_post_meta( $req_id, '_wc_rejection_reason', $rej_reason );
				$status           = WC_Status::STATUS_REJECTED;
				$rejection_reason = $rej_reason;
				$schedule_notice  = esc_html__( 'Payment proof rejected. Patient will be prompted to re-upload proof on their tracking link.', 'wellness-consult' );
			}
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

	// Handle Scheduling Form Submission (CR-1 Rule 4)
	if ( ! empty( $_POST['wc_schedule_request'] ) && check_admin_referer( 'wc_schedule_action_' . $req_id, 'wc_schedule_nonce' ) ) {
		$new_platform = sanitize_text_field( wp_unslash( $_POST['schedule_platform'] ?? '' ) );
		$new_access   = sanitize_text_field( wp_unslash( $_POST['meeting_link_or_handle'] ?? '' ) );
		$new_time     = sanitize_text_field( wp_unslash( $_POST['appointment_datetime'] ?? '' ) );

		if ( empty( $new_platform ) ) {
			$schedule_notice      = esc_html__( 'Error: Please select a delivery platform.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( 'video' === $consult_mode && empty( $new_access ) ) {
			$schedule_notice      = esc_html__( 'Error: A meeting room URL is strictly required for Video Call consultations.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} elseif ( in_array( $consult_mode, array( 'voice', 'chat' ), true ) && empty( $new_access ) ) {
			$schedule_notice      = esc_html__( 'Error: The phone number or username handle is required for voice/chat consultations.', 'wellness-consult' );
			$schedule_notice_type = 'error';
		} else {
			update_post_meta( $req_id, '_wc_platform', $new_platform );
			update_post_meta( $req_id, '_wc_meeting_link', $new_access );
			if ( ! empty( $new_time ) ) {
				update_post_meta( $req_id, '_wc_appointment_time', $new_time );
			}

			$from_status = $status;
			$to_status   = 'scheduled';
			if ( WC_Status::is_transition_allowed( $from_status, $to_status ) ) {
				update_post_meta( $req_id, '_wc_status', $to_status );
				$status = $to_status;
			}

			// Record platform in activity log (CR-1 Rule 4)
			WC_Database::log( array(
				'request_id'   => $req_id,
				'reference_id' => $ref_id,
				'action'       => 'appointment_scheduled',
				'from_status'  => $from_status,
				'to_status'    => $status,
				'note'         => sprintf(
					'Scheduled on platform: %s (Mode: %s). Access: %s.',
					$new_platform,
					$mode_label,
					$new_access
				),
			) );

			$platform         = $new_platform;
			$meeting_link     = $new_access;
			$appointment_time = $new_time;
			$schedule_notice  = esc_html__( 'Appointment successfully scheduled and platform recorded.', 'wellness-consult' );
		}
	}

	$proof_view_url = wp_nonce_url( admin_url( 'admin-post.php?action=wc_view_proof&request_id=' . $req_id ), 'wc_view_proof_' . $req_id );
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
				</div>

				<!-- Section 3: Scheduling Form (CR-1 Rule 4) -->
				<div class="postbox" style="padding:16px;">
					<h2 class="hndle" style="margin:0 0 12px 0; display:flex; align-items:center; gap:6px;">
						<span class="dashicons dashicons-calendar-alt"></span>
						<?php esc_html_e( 'Schedule Appointment (Assign Platform)', 'wellness-consult' ); ?>
					</h2>
					<p class="description" style="margin-top:0; margin-bottom:12px;">
						<?php printf( esc_html__( 'Patient selected mode: %s. Platform options below are filtered for this mode.', 'wellness-consult' ), '<strong>' . esc_html( $mode_icon . ' ' . $mode_label ) . '</strong>' ); ?>
					</p>

					<form method="post" action="">
						<?php wp_nonce_field( 'wc_schedule_action_' . $req_id, 'wc_schedule_nonce' ); ?>
						<input type="hidden" name="wc_schedule_request" value="1" />

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="schedule_platform"><?php esc_html_e( 'Delivery Platform *', 'wellness-consult' ); ?></label></th>
								<td>
									<select name="schedule_platform" id="schedule_platform" class="regular-text" required>
										<option value=""><?php esc_html_e( '— Select Delivery Platform —', 'wellness-consult' ); ?></option>
										<?php
										$filtered_platforms = WC_Options::get_platforms_for_mode( $consult_mode, false );
										foreach ( $filtered_platforms as $p_key => $p_info ) :
										?>
											<option value="<?php echo esc_attr( $p_info['name'] ); ?>" <?php selected( $platform, $p_info['name'] ); ?>>
												<?php echo esc_html( $p_info['name'] . ( empty( $p_info['is_active'] ) ? ' (Inactive)' : '' ) ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Communication channel used by doctor to deliver this consultation.', 'wellness-consult' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="meeting_link_or_handle">
										<?php
										if ( 'video' === $consult_mode ) {
											esc_html_e( 'Meeting URL Link *', 'wellness-consult' );
										} else {
											esc_html_e( 'Phone Number or Handle to Use *', 'wellness-consult' );
										}
										?>
									</label>
								</th>
								<td>
									<input type="text" name="meeting_link_or_handle" id="meeting_link_or_handle" value="<?php echo esc_attr( $meeting_link ); ?>" class="large-text" placeholder="<?php echo 'video' === $consult_mode ? 'https://meet.google.com/abc-defg-hij or Zoom room link' : '+15551234567 or @telegram_username'; ?>" required />
									<p class="description">
										<?php
										if ( 'video' === $consult_mode ) {
											esc_html_e( 'For video consultations, paste the video conference URL.', 'wellness-consult' );
										} else {
											esc_html_e( 'For voice and chat consultations, enter the phone number or username handle to connect.', 'wellness-consult' );
										}
										?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="appointment_datetime"><?php esc_html_e( 'Appointment Date & Time', 'wellness-consult' ); ?></label></th>
								<td>
									<input type="datetime-local" name="appointment_datetime" id="appointment_datetime" value="<?php echo esc_attr( $appointment_time ); ?>" />
									<p class="description"><?php esc_html_e( 'Session appointment time in UTC or local base timezone.', 'wellness-consult' ); ?></p>
								</td>
							</tr>
						</table>

						<p class="submit" style="margin-top:12px; margin-bottom:0;">
							<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save & Record Platform', 'wellness-consult' ); ?>" />
						</p>
					</form>
				</div>
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
						<span class="badge" style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700;">
							<?php echo esc_html( WC_Options::get_manual_payment_type_badge( $pay_method_type ? $pay_method_type : 'bank' ) ); ?>
						</span>
					</h2>

					<table style="width:100%;margin-bottom:14px;font-size:13px;line-height:1.6;">
						<tr>
							<td style="color:#64748b;width:140px;"><?php esc_html_e( 'Payment Method:', 'wellness-consult' ); ?></td>
							<td><strong><?php echo esc_html( $pay_method_name ? $pay_method_name : ucfirst( $pay_method ) ); ?></strong></td>
						</tr>
						<tr>
							<td style="color:#64748b;"><?php esc_html_e( 'Amount Expected:', 'wellness-consult' ); ?></td>
							<td><strong style="color:#0f3b4c;font-size:15px;">$<?php echo esc_html( number_format( (float) ( $amount_expected ? $amount_expected : $fee_usd ), 2 ) ); ?> USD</strong></td>
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
						<div style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:4px;margin-bottom:16px;text-align:center;">
							<span class="dashicons dashicons-media-document" style="font-size:32px;width:32px;height:32px;color:#2a838e;margin-bottom:4px;"></span>
							<p style="margin:2px 0 10px 0;font-size:12px;word-break:break-all;">
								<strong><?php echo esc_html( basename( $proof_file ) ); ?></strong>
							</p>
							<a href="<?php echo esc_url( $proof_view_url ); ?>" target="_blank" class="button button-secondary button-small">
								<span class="dashicons dashicons-visibility" style="vertical-align:text-top;font-size:15px;"></span>
								<?php esc_html_e( 'Open Proof (Secure Handler)', 'wellness-consult' ); ?>
							</a>
						</div>
					<?php else : ?>
						<div class="notice notice-warning inline" style="margin:10px 0 16px 0;">
							<p><?php esc_html_e( 'No proof file uploaded for this request.', 'wellness-consult' ); ?></p>
						</div>
					<?php endif; ?>

					<!-- Status: Pending Verification -> Approval Form -->
					<?php if ( WC_Status::STATUS_PENDING_VERIFICATION === $status ) : ?>
						<div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:14px;border-radius:6px;margin-top:12px;">
							<h3 style="margin:0 0 10px 0;font-size:14px;color:#166534;display:flex;align-items:center;gap:6px;">
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
									<input type="number" step="0.01" min="0.01" name="amount_received" id="wc-input-amount-received" class="regular-text" style="width:100%;font-size:14px;font-weight:600;" placeholder="e.g. <?php echo esc_attr( number_format( (float) ( $amount_expected ? $amount_expected : $fee_usd ), 2, '.', '' ) ); ?>" required />
									
									<!-- Client side warning if received < expected -->
									<div id="wc-underpayment-alert" style="display:none;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:6px 10px;border-radius:4px;margin-top:6px;font-size:12px;font-weight:600;">
										⚠️ <?php printf( esc_html__( 'Warning: Amount received is lower than expected fee ($%s USD). An underpayment alert will be recorded in the permanent audit log.', 'wellness-consult' ), esc_html( number_format( (float) ( $amount_expected ? $amount_expected : $fee_usd ), 2 ) ) ); ?>
									</div>
								</div>

								<div style="margin-bottom:12px;">
									<label for="verification_note" style="font-weight:600;display:block;margin-bottom:3px;font-size:13px;">
										<?php esc_html_e( 'Verification Note *', 'wellness-consult' ); ?>
									</label>
									<textarea name="verification_note" id="verification_note" rows="2" style="width:100%;font-size:12px;" placeholder="<?php esc_attr_e( 'e.g. Verified in wire account; MT103 reference matches.', 'wellness-consult' ); ?>" required></textarea>
									<p class="description" style="font-size:11px;margin-top:2px;">
										<?php esc_html_e( 'Required when approving. This note is logged in the permanent audit trail.', 'wellness-consult' ); ?>
									</p>
								</div>

								<button type="submit" class="button button-primary" style="background:#059669;border-color:#047857;width:100%;font-weight:700;height:34px;">
									<span class="dashicons dashicons-yes" style="vertical-align:text-top;font-size:16px;"></span>
									<?php esc_html_e( 'Verify & Approve Payment', 'wellness-consult' ); ?>
								</button>
							</form>

							<script>
							(function() {
								var expectedFee = <?php echo (float) ( $amount_expected ? $amount_expected : $fee_usd ); ?>;
								var input = document.getElementById('wc-input-amount-received');
								var alertBox = document.getElementById('wc-underpayment-alert');
								if (input && alertBox) {
									input.addEventListener('input', function() {
										var val = parseFloat(this.value);
										if (!isNaN(val) && val > 0 && val < expectedFee) {
											alertBox.style.display = 'block';
										} else {
											alertBox.style.display = 'none';
										}
									});
								}
							})();
							</script>
						</div>

						<!-- Rejection Toggle -->
						<details style="margin-top:14px;border-top:1px solid #e2e8f0;padding-top:10px;">
							<summary style="cursor:pointer;color:#dc2626;font-weight:600;font-size:13px;">
								<span class="dashicons dashicons-dismiss" style="vertical-align:text-top;font-size:16px;"></span>
								<?php esc_html_e( 'Reject Payment Screenshot', 'wellness-consult' ); ?>
							</summary>
							<form method="post" action="" style="margin-top:10px;">
								<?php wp_nonce_field( 'wc_reject_payment_action_' . $req_id, 'wc_reject_payment_nonce' ); ?>
								<input type="hidden" name="wc_reject_payment" value="1" />

								<label for="rejection_reason" style="font-weight:600;display:block;margin-bottom:3px;font-size:12px;">
									<?php esc_html_e( 'Rejection Reason *', 'wellness-consult' ); ?>
								</label>
								<textarea name="rejection_reason" id="rejection_reason" rows="2" style="width:100%;font-size:12px;" placeholder="<?php esc_attr_e( 'e.g. Screenshot blurry; transaction amount does not match.', 'wellness-consult' ); ?>" required></textarea>

								<button type="submit" class="button button-link-delete" style="margin-top:8px;" onclick="return confirm('<?php esc_attr_e( 'Reject this payment proof?', 'wellness-consult' ); ?>');">
									<?php esc_html_e( 'Reject Proof & Request New Upload', 'wellness-consult' ); ?>
								</button>
							</form>
						</details>

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
			</div>
		</div>
	</div>

	<?php
	return;
endif;

// List View
$selected_status = isset( $_GET['status_filter'] ) ? sanitize_key( wp_unslash( $_GET['status_filter'] ) ) : '';
$selected_mode   = isset( $_GET['mode_filter'] ) ? sanitize_key( wp_unslash( $_GET['mode_filter'] ) ) : '';
$search_term     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page = 20;

$query_args = array(
	'post_type'      => 'wc_request',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

$meta_query = array();

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

	<!-- Search & Filters Bar (CR-1: Status, Mode, Search) -->
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin: 15px 0; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
		<input type="hidden" name="page" value="wc-all-requests" />

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

		<input type="search" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by Reference ID / Alias...', 'wellness-consult' ); ?>" />
		<input type="submit" class="button" value="<?php esc_attr_e( 'Filter Requests', 'wellness-consult' ); ?>" />
		<?php if ( ! empty( $selected_status ) || ! empty( $selected_mode ) || ! empty( $search_term ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests' ) ); ?>" class="button"><?php esc_html_e( 'Reset Filters', 'wellness-consult' ); ?></a>
		<?php endif; ?>
	</form>

	<?php if ( $requests_query->have_posts() ) : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" style="width:160px;"><?php esc_html_e( 'Reference ID', 'wellness-consult' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Name or Alias', 'wellness-consult' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Package', 'wellness-consult' ); ?></th>
					<th scope="col" style="width:130px;"><?php esc_html_e( 'Mode', 'wellness-consult' ); ?></th>
					<th scope="col" style="width:100px;"><?php esc_html_e( 'Fee (USD)', 'wellness-consult' ); ?></th>
					<th scope="col" style="width:120px;"><?php esc_html_e( 'Country', 'wellness-consult' ); ?></th>
					<th scope="col" style="width:150px;"><?php esc_html_e( 'Status', 'wellness-consult' ); ?></th>
					<th scope="col" style="width:140px;"><?php esc_html_e( 'Submitted Date', 'wellness-consult' ); ?></th>
					<th scope="col" style="width:110px;"><?php esc_html_e( 'Actions', 'wellness-consult' ); ?></th>
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
					$p_method  = get_post_meta( $cur_id, '_wc_payment_method', true );
					$p_meth_id = get_post_meta( $cur_id, '_wc_payment_method_id', true );
					$is_online_row = ( 'stripe' === $p_method || 'stripe' === $p_meth_id || 'paypal' === $p_method || 'paypal' === $p_meth_id );
					?>
					<tr>
						<td><code><strong><?php echo esc_html( $ref_id ? $ref_id : get_the_title() ); ?></strong></code></td>
						<td><strong><?php echo esc_html( $name ? $name : '—' ); ?></strong></td>
						<td><?php echo esc_html( $type_name ? $type_name : '—' ); ?></td>
						<td>
							<strong><?php echo esc_html( WC_Options::get_mode_icon( $cur_mode ) . ' ' . WC_Options::get_mode_label( $cur_mode ) ); ?></strong>
						</td>
						<td><strong>$<?php echo esc_html( number_format( (float) $fee_usd, 2 ) ); ?></strong></td>
						<td><?php echo esc_html( $all_countries[ $country ] ?? ( $country ? $country : '—' ) ); ?></td>
						<td>
							<span class="badge" style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:4px;font-weight:700;font-size:11px;">
								<?php echo esc_html( WC_Status::get_label( $status ) ); ?>
							</span>
							<?php if ( $is_online_row ) : ?>
								<span style="display:block;font-size:10px;color:#0284c7;font-weight:600;margin-top:2px;">
									<?php echo ( 'stripe' === $p_method || 'stripe' === $p_meth_id ) ? '⚡ Stripe' : '⚡ PayPal'; ?>
								</span>
							<?php endif; ?>
						</td>
						<td><code><?php echo esc_html( get_the_date( 'Y-m-d H:i' ) ); ?></code></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests&action=view&id=' . $cur_id ) ); ?>" class="button button-small">
								<?php esc_html_e( 'View Details', 'wellness-consult' ); ?>
							</a>
						</td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
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
