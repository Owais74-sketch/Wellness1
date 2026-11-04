<?php
/**
 * Atomic Reference ID Generator
 *
 * Implements Rule 3: Generates Reference IDs formatted as WC-YYYY-00001 atomically
 * without collisions under simultaneous concurrent requests.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Atomic_Reference {

	/**
	 * Generate next Reference ID atomically using a MySQL named lock.
	 *
	 * @return string Generated Reference ID (e.g. WC-2026-00001).
	 */
	public static function generate() {
		global $wpdb;

		$year        = gmdate( 'Y' );
		$option_name = 'wc_reference_sequence_' . $year;

		// Acquire application-level mutex in MySQL (10 second timeout)
		$wpdb->query( "SELECT GET_LOCK('wc_reference_sequence_lock', 10)" );

		// Query current sequence directly from options table
		$current_seq = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				$option_name
			)
		);

		if ( null === $current_seq ) {
			// Initialize sequence for the year
			$next_seq = 1;
			$wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $option_name,
					'option_value' => $next_seq,
					'autoload'     => 'no',
				),
				array( '%s', '%d', '%s' )
			);
		} else {
			$next_seq = absint( $current_seq ) + 1;
			$wpdb->update(
				$wpdb->options,
				array( 'option_value' => $next_seq ),
				array( 'option_name' => $option_name ),
				array( '%d' ),
				array( '%s' )
			);
		}

		// Release lock
		$wpdb->query( "SELECT RELEASE_LOCK('wc_reference_sequence_lock')" );

		return sprintf( 'WC-%s-%05d', $year, $next_seq );
	}
}
