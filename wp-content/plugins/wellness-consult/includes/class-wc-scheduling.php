<?php
/**
 * Availability-aware appointment scheduling.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

class WC_Scheduling {

	/**
	 * Get availability for a consultant, using the existing shared defaults.
	 *
	 * @param int $consultant_id Consultant user ID.
	 * @return array
	 */
	public static function get_availability( $consultant_id ) {
		$default = get_option( 'wc_availability_settings', array() );
		$saved   = get_option( 'wc_consultant_availability_' . absint( $consultant_id ), $default );
		return is_array( $saved ) ? $saved : array();
	}

	/**
	 * Generate available start times for a consultant on a local date.
	 *
	 * @param int    $consultant_id Consultant user ID.
	 * @param string $date          Local date in Y-m-d format.
	 * @param int    $duration      Appointment duration in minutes.
	 * @param int    $exclude_id    Request to exclude while rescheduling.
	 * @param string $patient_tz    Patient timezone.
	 * @return array UTC timestamp => display label.
	 */
	public static function get_available_slots( $consultant_id, $date, $duration, $exclude_id = 0, $patient_tz = 'UTC' ) {
		$availability = self::get_availability( $consultant_id );
		$timezone     = self::get_timezone( $availability['base_timezone'] ?? 'UTC' );
		$patient_zone = self::get_timezone( $patient_tz );
		$duration     = absint( $duration );
		if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || $duration < 1 ) {
			return array();
		}

		$date_check = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $timezone );
		if ( ! $date_check || $date_check->format( 'Y-m-d' ) !== $date ) {
			return array();
		}

		$day_keys = array(
			'Mon' => 'mon',
			'Tue' => 'tue',
			'Wed' => 'wed',
			'Thu' => 'thu',
			'Fri' => 'fri',
			'Sat' => 'sat',
			'Sun' => 'sun',
		);
		$day_key = $day_keys[ $date_check->format( 'D' ) ];
		$weekly_hours = isset( $availability['weekly_hours'] ) && is_array( $availability['weekly_hours'] ) ? $availability['weekly_hours'] : array();
		$hours   = $weekly_hours[ $day_key ] ?? array();
		$hours   = is_array( $hours ) ? $hours : array();
		$active  = isset( $hours['active'] ) ? in_array( (string) $hours['active'], array( '1', 'yes', 'true' ), true ) : ! in_array( $day_key, array( 'sat', 'sun' ), true );
		$start   = $hours['start'] ?? ( in_array( $day_key, array( 'sat', 'sun' ), true ) ? '10:00' : '09:00' );
		$end     = $hours['end'] ?? ( in_array( $day_key, array( 'sat', 'sun' ), true ) ? '14:00' : '17:00' );
		$blocked_dates = isset( $availability['blocked_dates'] ) && is_array( $availability['blocked_dates'] ) ? $availability['blocked_dates'] : array();
		$breaks = isset( $availability['breaks'] ) && is_array( $availability['breaks'] ) ? $availability['breaks'] : array();
		if ( ! $active || in_array( $date, $blocked_dates, true ) || ! self::valid_time( $start ) || ! self::valid_time( $end ) ) {
			return array();
		}

		$start_at = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $start, $timezone );
		$end_at   = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $end, $timezone );
		if ( ! $start_at || ! $end_at || $start_at->format( 'Y-m-d H:i' ) !== $date . ' ' . $start || $end_at->format( 'Y-m-d H:i' ) !== $date . ' ' . $end || $end_at <= $start_at ) {
			return array();
		}

		$busy = self::get_booked_intervals( $consultant_id, $exclude_id );
		$slots = array();
		$now_utc = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		for ( $candidate = $start_at; $candidate->modify( '+' . $duration . ' minutes' ) <= $end_at; $candidate = $candidate->modify( '+15 minutes' ) ) {
			$candidate_end = $candidate->modify( '+' . $duration . ' minutes' );
			if ( ! self::is_valid_local_time( $candidate, $date ) || self::overlaps_break( $candidate, $candidate_end, $breaks ) ) {
				continue;
			}

			$utc_start = $candidate->setTimezone( new DateTimeZone( 'UTC' ) );
			$utc_end   = $candidate_end->setTimezone( new DateTimeZone( 'UTC' ) );
			if ( $utc_start <= $now_utc || self::overlaps_booked( $utc_start, $utc_end, $busy ) ) {
				continue;
			}
			$patient_time = $utc_start->setTimezone( $patient_zone );
			$slots[ $utc_start->format( 'Y-m-d H:i:s' ) ] = sprintf(
				'%s (%s) / %s (%s)',
				$candidate->format( 'D, M j, Y H:i' ),
				$timezone->getName(),
				$patient_time->format( 'D, M j, Y H:i' ),
				$patient_zone->getName()
			);
		}
		return $slots;
	}

	/**
	 * Format a stored UTC appointment for patient and consultant time zones.
	 *
	 * @param string $utc_start    UTC start time.
	 * @param string $patient_tz   Patient timezone.
	 * @param int    $consultant_id Consultant user ID.
	 * @return array
	 */
	public static function get_time_labels( $utc_start, $patient_tz, $consultant_id ) {
		$utc = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', (string) $utc_start, new DateTimeZone( 'UTC' ) );
		if ( ! $utc ) {
			return array( 'patient' => '', 'consultant' => '' );
		}
		$availability = self::get_availability( $consultant_id );
		$patient_zone = self::get_timezone( $patient_tz );
		$consult_zone = self::get_timezone( $availability['base_timezone'] ?? 'UTC' );
		return array(
			'patient'    => $utc->setTimezone( $patient_zone )->format( 'D, M j, Y H:i' ) . ' (' . $patient_zone->getName() . ')',
			'consultant' => $utc->setTimezone( $consult_zone )->format( 'D, M j, Y H:i' ) . ' (' . $consult_zone->getName() . ')',
		);
	}

	/**
	 * Read a UTC appointment, converting legacy local appointment values if needed.
	 *
	 * @param int $request_id Request ID.
	 * @return string
	 */
	public static function get_request_utc( $request_id ) {
		$utc = get_post_meta( absint( $request_id ), '_wc_appointment_utc', true );
		if ( is_string( $utc ) && '' !== $utc ) {
			$parsed_utc = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $utc, new DateTimeZone( 'UTC' ) );
			if ( $parsed_utc && $parsed_utc->format( 'Y-m-d H:i:s' ) === $utc ) {
				return $utc;
			}
		}
		$legacy_time = get_post_meta( absint( $request_id ), '_wc_appointment_time', true );
		$consultant_id = absint( get_post_meta( absint( $request_id ), '_wc_consultant_id', true ) );
		if ( ! is_string( $legacy_time ) || '' === $legacy_time || ! $consultant_id ) {
			return '';
		}
		$availability = self::get_availability( $consultant_id );
		$timezone = self::get_timezone( $availability['base_timezone'] ?? 'UTC' );
		foreach ( array( '!Y-m-d\TH:i', '!Y-m-d H:i:s', '!Y-m-d H:i' ) as $format ) {
			$local = DateTimeImmutable::createFromFormat( $format, $legacy_time, $timezone );
			if ( $local && $local->format( str_replace( '!', '', $format ) ) === $legacy_time ) {
				return $local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			}
		}
		return '';
	}

	/**
	 * Atomically reserve a free slot and transition the request to scheduled.
	 *
	 * @param int    $request_id   Request ID.
	 * @param int    $consultant_id Consultant user ID.
	 * @param string $date         Local date.
	 * @param string $utc_start    Selected UTC start.
	 * @param int    $duration     Duration in minutes.
	 * @param string $platform     Active platform name.
	 * @param string $access       Meeting URL or contact detail.
	 * @return true|WP_Error
	 */
	public static function reserve( $request_id, $consultant_id, $date, $utc_start, $duration, $platform, $access ) {
		global $wpdb;

		$request_id    = absint( $request_id );
		$consultant_id = absint( $consultant_id );
		$duration      = absint( $duration );
		$old_status    = get_post_meta( $request_id, '_wc_status', true );
		if ( ! in_array( $old_status, array( WC_Status::STATUS_PAID_AWAITING_SCHEDULING, WC_Status::STATUS_SCHEDULED ), true ) ) {
			return new WP_Error( 'wc_schedule_status', __( 'Only paid requests awaiting scheduling can be scheduled or rescheduled.', 'wellness-consult' ) );
		}
		if ( ! WC_Status::is_transition_allowed( $old_status, WC_Status::STATUS_SCHEDULED ) ) {
			return new WP_Error( 'wc_schedule_transition', __( 'This request cannot be scheduled from its current status.', 'wellness-consult' ) );
		}

		$availability = self::get_availability( $consultant_id );
		$timezone     = self::get_timezone( $availability['base_timezone'] ?? 'UTC' );
		$selected     = is_string( $utc_start ) ? DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $utc_start, new DateTimeZone( 'UTC' ) ) : false;
		if ( ! $selected || $selected->format( 'Y-m-d H:i:s' ) !== $utc_start || $duration < 1 ) {
			return new WP_Error( 'wc_schedule_time', __( 'Select a valid available appointment time.', 'wellness-consult' ) );
		}
		$local_start = $selected->setTimezone( $timezone );
		if ( $local_start->format( 'Y-m-d' ) !== $date ) {
			return new WP_Error( 'wc_schedule_time', __( 'The selected appointment date does not match the available slot.', 'wellness-consult' ) );
		}

		$lock_name = 'wc_schedule_' . $consultant_id;
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name ) ) ) {
			return new WP_Error( 'wc_schedule_busy', __( 'Scheduling is busy for this consultant. Please try again.', 'wellness-consult' ) );
		}

		$result = new WP_Error( 'wc_schedule_conflict', __( 'That slot was just taken or is no longer available. Choose another available time.', 'wellness-consult' ) );
		$transaction_started = false;
		try {
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				return new WP_Error( 'wc_schedule_database', __( 'Could not start the scheduling transaction.', 'wellness-consult' ) );
			}
			$transaction_started = true;

			$slots = self::get_available_slots(
				$consultant_id,
				$date,
				$duration,
				$request_id,
				get_post_meta( $request_id, '_wc_timezone', true ) ?: 'UTC'
			);
			if ( ! isset( $slots[ $selected->format( 'Y-m-d H:i:s' ) ] ) ) {
				$wpdb->query( 'ROLLBACK' );
				$transaction_started = false;
				return $result;
			}

			$old_start = self::get_request_utc( $request_id );
			$note = sprintf(
				'%s from %s to %s for %d minutes on %s. Previous appointment (UTC): %s.',
				WC_Status::STATUS_SCHEDULED === $old_status ? 'Rescheduled appointment' : 'Scheduled appointment',
				$old_start ? $old_start : 'no prior time',
				$selected->format( 'Y-m-d H:i:s' ),
				$duration,
				$platform,
				$old_start ? $old_start : 'none'
			);
			update_post_meta( $request_id, '_wc_appointment_utc', $selected->format( 'Y-m-d H:i:s' ) );
			update_post_meta( $request_id, '_wc_appointment_time', $local_start->format( 'Y-m-d\TH:i' ) );
			update_post_meta( $request_id, '_wc_duration', $duration );
			update_post_meta( $request_id, '_wc_consultant_id', $consultant_id );
			update_post_meta( $request_id, '_wc_platform', $platform );
			update_post_meta( $request_id, '_wc_meeting_link', $access );
			$transition = WC_Status::change_status( $request_id, WC_Status::STATUS_SCHEDULED, $note );
			if ( is_wp_error( $transition ) ) {
				$wpdb->query( 'ROLLBACK' );
				$transaction_started = false;
				return $transition;
			}
			WC_Database::log( array(
				'request_id'   => $request_id,
				'reference_id' => get_post_meta( $request_id, '_wc_reference_id', true ),
				'action'       => WC_Status::STATUS_SCHEDULED === $old_status ? 'appointment_rescheduled' : 'appointment_scheduled',
				'from_status'  => $old_status,
				'to_status'    => WC_Status::STATUS_SCHEDULED,
				'note'         => $note,
			) );
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				$wpdb->query( 'ROLLBACK' );
				$transaction_started = false;
				return new WP_Error( 'wc_schedule_database', __( 'Could not save the appointment. Please try again.', 'wellness-consult' ) );
			}
			$transaction_started = false;
			$result = true;
		} finally {
			if ( $transaction_started ) {
				$wpdb->query( 'ROLLBACK' );
			}
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		}

		return $result;
	}

	/**
	 * Read scheduled appointment intervals for a consultant.
	 *
	 * @param int $consultant_id Consultant user ID.
	 * @param int $exclude_id    Request to exclude.
	 * @return array
	 */
	private static function get_booked_intervals( $consultant_id, $exclude_id ) {
		$ids = get_posts( array(
			'post_type'      => 'wc_request',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => '_wc_status', 'value' => WC_Status::STATUS_SCHEDULED ),
				array( 'key' => '_wc_consultant_id', 'value' => absint( $consultant_id ) ),
			),
		) );
		$busy = array();
		foreach ( $ids as $id ) {
			if ( absint( $id ) === absint( $exclude_id ) ) {
				continue;
			}
			$start = self::get_request_utc( $id );
			$start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', (string) $start, new DateTimeZone( 'UTC' ) );
			if ( ! $start ) {
				continue;
			}
			$duration = absint( get_post_meta( $id, '_wc_duration', true ) );
			if ( ! $duration ) {
				$duration = 30;
			}
			$busy[] = array( $start, $start->modify( '+' . $duration . ' minutes' ) );
		}
		return $busy;
	}

	/**
	 * Check intervals for overlap.
	 *
	 * @param DateTimeImmutable $start Start time.
	 * @param DateTimeImmutable $end   End time.
	 * @param array             $busy  Existing intervals.
	 * @return bool
	 */
	private static function overlaps_booked( $start, $end, $busy ) {
		foreach ( $busy as $interval ) {
			if ( $start < $interval[1] && $end > $interval[0] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check whether an appointment intersects a configured break.
	 *
	 * @param DateTimeImmutable $start  Start time.
	 * @param DateTimeImmutable $end    End time.
	 * @param array             $breaks Configured breaks.
	 * @return bool
	 */
	private static function overlaps_break( $start, $end, $breaks ) {
		foreach ( $breaks as $break ) {
			if ( ! is_array( $break ) ) {
				continue;
			}
			$break_start = $break['start'] ?? '';
			$break_end   = $break['end'] ?? '';
			if ( ! self::valid_time( $break_start ) || ! self::valid_time( $break_end ) ) {
				continue;
			}
			$from = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $start->format( 'Y-m-d' ) . ' ' . $break_start, $start->getTimezone() );
			$to   = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $start->format( 'Y-m-d' ) . ' ' . $break_end, $start->getTimezone() );
			if ( $from && $to && $start < $to && $end > $from ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Validate parsed wall time to reject nonexistent daylight-saving times.
	 *
	 * @param DateTimeImmutable $value Local wall time.
	 * @param string            $date  Expected date.
	 * @return bool
	 */
	private static function is_valid_local_time( $value, $date ) {
		return $date === $value->format( 'Y-m-d' );
	}

	/**
	 * Validate an HH:MM time.
	 *
	 * @param string $value Time value.
	 * @return bool
	 */
	private static function valid_time( $value ) {
		return is_string( $value ) && (bool) preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value );
	}

	/**
	 * Return a valid timezone object or UTC.
	 *
	 * @param string $name Timezone identifier.
	 * @return DateTimeZone
	 */
	private static function get_timezone( $name ) {
		if ( ! is_string( $name ) || ! in_array( $name, timezone_identifiers_list(), true ) ) {
			$name = 'UTC';
		}
		return new DateTimeZone( $name );
	}
}
