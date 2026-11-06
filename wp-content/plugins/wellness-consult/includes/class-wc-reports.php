<?php
/**
 * Dashboard figures, reports and CSV export (SRS v2.0 Section 6, Phase 9).
 *
 * All money is in USD (SRS FR-7, FR-11). Reports use only data that already
 * exists on wc_request posts. No personal data (name, email, phone) is ever
 * included in a report or in the CSV.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

class WC_Reports {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_post_wc_export_report', array( __CLASS__, 'handle_export' ) );
	}

	/**
	 * Parse a UTC date-time string safely.
	 *
	 * @param string $value Date-time.
	 * @return int|null Unix timestamp.
	 */
	protected static function to_ts( $value ) {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}
		try {
			$dt = new DateTime( $value, new DateTimeZone( 'UTC' ) );
			return $dt->getTimestamp();
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Convert a site-time date range (Y-m-d) to UTC timestamps.
	 *
	 * @param string $from Y-m-d.
	 * @param string $to   Y-m-d.
	 * @return array from, to (strings), start, end (timestamps).
	 */
	public static function parse_range( $from, $to ) {
		$tz   = wp_timezone();
		$now  = new DateTime( 'now', $tz );
		$ok   = function ( $s ) {
			return is_string( $s ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s ) && checkdate( (int) substr( $s, 5, 2 ), (int) substr( $s, 8, 2 ), (int) substr( $s, 0, 4 ) );
		};
		if ( ! $ok( $to ) ) {
			$to = $now->format( 'Y-m-d' );
		}
		if ( ! $ok( $from ) ) {
			$d = clone $now;
			$d->modify( '-29 days' );
			$from = $d->format( 'Y-m-d' );
		}
		if ( $from > $to ) {
			list( $from, $to ) = array( $to, $from );
		}
		$start = new DateTime( $from . ' 00:00:00', $tz );
		$end   = new DateTime( $to . ' 23:59:59', $tz );
		return array(
			'from'  => $from,
			'to'    => $to,
			'start' => $start->getTimestamp(),
			'end'   => $end->getTimestamp(),
		);
	}

	/**
	 * Build one row per submitted request (drafts are ignored).
	 *
	 * @return array
	 */
	public static function all_rows() {
		static $rows = null;
		if ( null !== $rows ) {
			return $rows;
		}
		$rows = array();
		$ids  = get_posts( array(
			'post_type'        => 'wc_request',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		) );
		foreach ( $ids as $id ) {
			$created = get_post_meta( $id, '_wc_created_at', true );
			if ( ! $created ) {
				$created = get_post_field( 'post_date_gmt', $id );
			}
			$received = (float) get_post_meta( $id, '_wc_amount_received', true );
			$refund   = (float) get_post_meta( $id, '_wc_refund_amount', true );
			$paid_raw = get_post_meta( $id, '_wc_paid_at', true );
			if ( ! $paid_raw ) {
				$paid_raw = get_post_meta( $id, '_wc_verified_at', true );
			}
			if ( ! $paid_raw ) {
				$paid_raw = $created;
			}
			$rows[] = array(
				'id'         => (int) $id,
				'reference'  => (string) get_post_meta( $id, '_wc_reference_id', true ),
				'created_ts' => self::to_ts( (string) $created ),
				'paid_ts'    => $received > 0 ? self::to_ts( (string) $paid_raw ) : null,
				'status'     => (string) get_post_meta( $id, '_wc_status', true ),
				'type'       => (string) get_post_meta( $id, '_wc_type_name', true ),
				'country'    => strtoupper( (string) get_post_meta( $id, '_wc_country', true ) ),
				'fee'        => (float) get_post_meta( $id, '_wc_fee_usd', true ),
				'received'   => $received,
				'refund'     => $refund,
				'net'        => $received - $refund,
				'appt_ts'    => self::to_ts( (string) get_post_meta( $id, '_wc_appointment_utc', true ) ),
			);
		}
		return $rows;
	}

	/**
	 * Requests created inside a range.
	 *
	 * @param int $start UTC timestamp.
	 * @param int $end   UTC timestamp.
	 * @return array
	 */
	public static function rows_created_between( $start, $end ) {
		return array_values( array_filter( self::all_rows(), function ( $r ) use ( $start, $end ) {
			return null !== $r['created_ts'] && $r['created_ts'] >= $start && $r['created_ts'] <= $end;
		} ) );
	}

	/**
	 * Summarise rows.
	 *
	 * @param array $rows Rows.
	 * @return array
	 */
	public static function summarise( $rows ) {
		$s = array(
			'total'      => count( $rows ),
			'gross'      => 0.0,
			'refunds'    => 0.0,
			'net'        => 0.0,
			'by_status'  => array(),
			'by_country' => array(),
			'by_type'    => array(),
		);
		foreach ( $rows as $r ) {
			$s['gross']   += $r['received'];
			$s['refunds'] += $r['refund'];
			$s['net']     += $r['net'];

			$st = '' !== $r['status'] ? $r['status'] : 'unknown';
			$s['by_status'][ $st ] = ( $s['by_status'][ $st ] ?? 0 ) + 1;

			$co = '' !== $r['country'] ? $r['country'] : '--';
			if ( ! isset( $s['by_country'][ $co ] ) ) {
				$s['by_country'][ $co ] = array( 'count' => 0, 'net' => 0.0 );
			}
			$s['by_country'][ $co ]['count']++;
			$s['by_country'][ $co ]['net'] += $r['net'];

			$ty = '' !== $r['type'] ? $r['type'] : '--';
			if ( ! isset( $s['by_type'][ $ty ] ) ) {
				$s['by_type'][ $ty ] = array( 'count' => 0, 'net' => 0.0 );
			}
			$s['by_type'][ $ty ]['count']++;
			$s['by_type'][ $ty ]['net'] += $r['net'];
		}
		uasort( $s['by_country'], function ( $a, $b ) { return $b['count'] <=> $a['count']; } );
		uasort( $s['by_type'], function ( $a, $b ) { return $b['count'] <=> $a['count']; } );
		return $s;
	}

	/**
	 * Net income by payment date.
	 *
	 * @param int $start UTC timestamp.
	 * @param int $end   UTC timestamp.
	 * @return float
	 */
	public static function income_paid_between( $start, $end ) {
		$sum = 0.0;
		foreach ( self::all_rows() as $r ) {
			if ( null !== $r['paid_ts'] && $r['paid_ts'] >= $start && $r['paid_ts'] <= $end ) {
				$sum += $r['net'];
			}
		}
		return $sum;
	}

	/**
	 * Scheduled appointments inside a range, soonest first.
	 *
	 * @param int $start UTC timestamp.
	 * @param int $end   UTC timestamp.
	 * @return array
	 */
	public static function appointments_between( $start, $end ) {
		$list = array_filter( self::all_rows(), function ( $r ) use ( $start, $end ) {
			return 'scheduled' === $r['status'] && null !== $r['appt_ts'] && $r['appt_ts'] >= $start && $r['appt_ts'] <= $end;
		} );
		usort( $list, function ( $a, $b ) { return $a['appt_ts'] <=> $b['appt_ts']; } );
		return $list;
	}

	/**
	 * Country name for an ISO code.
	 *
	 * @param string $code ISO code.
	 * @return string
	 */
	public static function country_name( $code ) {
		if ( '--' === $code || '' === $code ) {
			return __( 'Not given', 'wellness-consult' );
		}
		$all = class_exists( 'WC_Options' ) ? WC_Options::get_all_iso_countries() : array();
		return isset( $all[ $code ] ) ? $all[ $code ] : $code;
	}

	public static function money( $amount ) {
		return '$' . number_format( (float) $amount, 2 ) . ' USD';
	}

	/**
	 * Stop spreadsheet formulas from running when a cell starts with = + - or @.
	 *
	 * @param string $value Cell value.
	 * @return string
	 */
	protected static function csv_safe( $value ) {
		$value = (string) $value;
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}
		return $value;
	}

	/**
	 * Download a CSV for a date range (requests created in the range).
	 */
	public static function handle_export() {
		check_admin_referer( 'wc_export_report' );
		if ( ! current_user_can( 'wc_view_reports' ) ) {
			wp_die( esc_html__( 'You do not have permission to export reports.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}

		$range = self::parse_range(
			isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '',
			isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : ''
		);
		$rows     = self::rows_created_between( $range['start'], $range['end'] );
		$statuses = WC_Status::get_all_statuses();
		$tz       = wp_timezone();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="wellness-report-' . $range['from'] . '_to_' . $range['to'] . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM so Excel reads it correctly.
		fputcsv( $out, array( 'Reference ID', 'Created (site time)', 'Consultation type', 'Country', 'Status', 'Fee USD', 'Received USD', 'Refunded USD', 'Net USD' ) );
		foreach ( $rows as $r ) {
			$created = null !== $r['created_ts'] ? ( new DateTime( '@' . $r['created_ts'] ) )->setTimezone( $tz )->format( 'Y-m-d H:i' ) : '';
			fputcsv( $out, array(
				self::csv_safe( $r['reference'] ),
				$created,
				self::csv_safe( $r['type'] ),
				self::csv_safe( $r['country'] ),
				self::csv_safe( isset( $statuses[ $r['status'] ] ) ? wp_strip_all_tags( $statuses[ $r['status'] ] ) : $r['status'] ),
				number_format( $r['fee'], 2, '.', '' ),
				number_format( $r['received'], 2, '.', '' ),
				number_format( $r['refund'], 2, '.', '' ),
				number_format( $r['net'], 2, '.', '' ),
			) );
		}
		fclose( $out );
		exit;
	}
}
