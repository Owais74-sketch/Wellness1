<?php
/**
 * Request Status Management and Strict Transitions System
 *
 * Implements SRS v2.0 Section 5 Lifecycle and strict transition validation.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Status {

	// Exact status identifiers
	const STATUS_AWAITING_PAYMENT          = 'awaiting_payment';
	const STATUS_PENDING_VERIFICATION      = 'pending_verification';
	const STATUS_REJECTED                  = 'rejected';
	const STATUS_PAID_AWAITING_SCHEDULING  = 'paid_awaiting_scheduling';
	const STATUS_SCHEDULED                 = 'scheduled';
	const STATUS_COMPLETED                 = 'completed';
	const STATUS_CANCELLED                 = 'cancelled';
	const STATUS_NO_SHOW                   = 'no_show';
	const STATUS_REFUNDED                  = 'refunded';
	const STATUS_EXPIRED                   = 'expired';

	/**
	 * Get array of all statuses with display labels.
	 *
	 * @return array
	 */
	public static function get_all_statuses() {
		return array(
			self::STATUS_AWAITING_PAYMENT         => esc_html__( 'Awaiting Payment', 'wellness-consult' ),
			self::STATUS_PENDING_VERIFICATION     => esc_html__( 'Pending Verification', 'wellness-consult' ),
			self::STATUS_REJECTED                 => esc_html__( 'Rejected', 'wellness-consult' ),
			self::STATUS_PAID_AWAITING_SCHEDULING => esc_html__( 'Paid - Awaiting Scheduling', 'wellness-consult' ),
			self::STATUS_SCHEDULED                => esc_html__( 'Scheduled', 'wellness-consult' ),
			self::STATUS_COMPLETED                => esc_html__( 'Completed', 'wellness-consult' ),
			self::STATUS_CANCELLED                => esc_html__( 'Cancelled', 'wellness-consult' ),
			self::STATUS_NO_SHOW                  => esc_html__( 'No-show', 'wellness-consult' ),
			self::STATUS_REFUNDED                 => esc_html__( 'Refunded', 'wellness-consult' ),
			self::STATUS_EXPIRED                  => esc_html__( 'Expired', 'wellness-consult' ),
		);
	}

	/**
	 * Get label for a specific status.
	 *
	 * @param string $status
	 * @return string
	 */
	public static function get_label( $status ) {
		$statuses = self::get_all_statuses();
		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : esc_html( ucfirst( str_replace( '_', ' ', $status ) ) );
	}

	/**
	 * Define ONLY the allowed transitions (Rule 3).
	 *
	 * awaiting_payment -> paid_awaiting_scheduling | expired | cancelled
	 * pending_verification -> paid_awaiting_scheduling | rejected | cancelled
	 * rejected -> pending_verification | cancelled
	 * paid_awaiting_scheduling -> scheduled | cancelled | refunded
	 * scheduled -> completed | scheduled (reschedule) | cancelled | no_show | refunded
	 * completed, cancelled, no_show, refunded, expired -> closed (no change, except cancelled -> refunded)
	 *
	 * @return array
	 */
	public static function get_allowed_transitions_map() {
		return array(
			self::STATUS_AWAITING_PAYMENT => array(
				self::STATUS_PAID_AWAITING_SCHEDULING,
				self::STATUS_EXPIRED,
				self::STATUS_CANCELLED,
			),
			self::STATUS_PENDING_VERIFICATION => array(
				self::STATUS_PAID_AWAITING_SCHEDULING,
				self::STATUS_REJECTED,
				self::STATUS_CANCELLED,
			),
			self::STATUS_REJECTED => array(
				self::STATUS_PENDING_VERIFICATION,
				self::STATUS_CANCELLED,
			),
			self::STATUS_PAID_AWAITING_SCHEDULING => array(
				self::STATUS_SCHEDULED,
				self::STATUS_CANCELLED,
				self::STATUS_REFUNDED,
			),
			self::STATUS_SCHEDULED => array(
				self::STATUS_COMPLETED,
				self::STATUS_SCHEDULED, // Allows rescheduling
				self::STATUS_CANCELLED,
				self::STATUS_NO_SHOW,
				self::STATUS_REFUNDED,
			),
			self::STATUS_CANCELLED => array(
				self::STATUS_REFUNDED,
			),
			self::STATUS_COMPLETED => array(), // Closed
			self::STATUS_NO_SHOW   => array(), // Closed
			self::STATUS_REFUNDED  => array(), // Closed
			self::STATUS_EXPIRED   => array(), // Closed
		);
	}

	/**
	 * Check if a status transition is permitted.
	 *
	 * @param string $from_status
	 * @param string $to_status
	 * @return bool
	 */
	public static function is_transition_allowed( $from_status, $to_status ) {
		$map = self::get_allowed_transitions_map();

		if ( ! isset( $map[ $from_status ] ) ) {
			return false;
		}

		return in_array( $to_status, $map[ $from_status ], true );
	}

	/**
	 * Change status of a consultation request with strict enforcement and activity logging.
	 *
	 * @param int         $request_id Request post ID.
	 * @param string      $to_status  Target status.
	 * @param string      $note       Optional reason / explanation.
	 * @param int|null    $user_id    User performing the action (null for current user).
	 * @return true|WP_Error
	 */
	public static function change_status( $request_id, $to_status, $note = '', $user_id = null ) {
		$request_id = absint( $request_id );
		$post       = get_post( $request_id );

		if ( ! $post || 'wc_request' !== $post->post_type ) {
			return new WP_Error( 'wc_invalid_request', esc_html__( 'Invalid consultation request ID.', 'wellness-consult' ) );
		}

		$from_status = get_post_meta( $request_id, '_wc_status', true );
		if ( empty( $from_status ) ) {
			$from_status = self::STATUS_AWAITING_PAYMENT;
		}

		// Enforce transition rules
		if ( ! self::is_transition_allowed( $from_status, $to_status ) ) {
			$error_message = sprintf(
				/* translators: 1: from status label, 2: to status label */
				esc_html__( 'Illegal status transition from "%1$s" to "%2$s" is blocked by system rules.', 'wellness-consult' ),
				self::get_label( $from_status ),
				self::get_label( $to_status )
			);
			return new WP_Error( 'wc_illegal_status_transition', $error_message );
		}

		// Update post meta
		update_post_meta( $request_id, '_wc_status', $to_status );
		update_post_meta( $request_id, '_wc_status_updated_at', current_time( 'mysql', 1 ) );

		$reference_id = get_post_meta( $request_id, '_wc_reference_id', true );
		if ( empty( $reference_id ) ) {
			$reference_id = $post->post_title;
		}

		// Write to Activity Log table
		WC_Database::log( array(
			'request_id'   => $request_id,
			'reference_id' => $reference_id,
			'action'       => 'status_transition',
			'user_id'      => $user_id,
			'from_status'  => $from_status,
			'to_status'    => $to_status,
			'note'         => $note,
		) );

		// Fire action for notifications in later phases
		do_action( 'wc_request_status_changed', $request_id, $from_status, $to_status, $note );

		return true;
	}
}
