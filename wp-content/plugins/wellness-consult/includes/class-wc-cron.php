<?php
/**
 * Cron Job Tasks and Automated Request Expiration
 *
 * Implements FR-8: 24-hour expiration for abandoned online checkout requests.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Cron {

	const CRON_HOOK = 'wc_hourly_cleanup_cron';

	/**
	 * Initialize cron hooks on init.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'process_expired_requests' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 3600, 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Query and expire abandoned awaiting_payment requests older than 24 hours (FR-8).
	 */
	public static function process_expired_requests() {
		$cutoff_ts = time() - ( 24 * HOUR_IN_SECONDS );

		$query = new WP_Query( array(
			'post_type'      => 'wc_request',
			'post_status'    => 'any',
			'posts_per_page' => 100,
			'meta_query'     => array(
				array(
					'key'   => '_wc_status',
					'value' => WC_Status::STATUS_AWAITING_PAYMENT,
				),
			),
			'fields'         => 'ids',
		) );

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				$created_at_meta = get_post_meta( $post_id, '_wc_created_at', true );
				$created_ts      = $created_at_meta ? strtotime( $created_at_meta ) : get_post_time( 'U', true, $post_id );

				if ( $created_ts && $created_ts <= $cutoff_ts ) {
					WC_Status::change_status(
						$post_id,
						WC_Status::STATUS_EXPIRED,
						__( 'Online checkout abandoned. Request expired automatically after 24 hours.', 'wellness-consult' )
					);
				}
			}
		}
	}

	/**
	 * Clear scheduled cron jobs on deactivation.
	 */
	public static function clear_scheduled_events() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
