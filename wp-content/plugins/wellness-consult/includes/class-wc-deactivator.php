<?php
/**
 * Fired during plugin deactivation
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Deactivator {

	/**
	 * Run deactivation tasks:
	 * Clear scheduled cron jobs only (Rule 2).
	 * Does NOT delete database tables or options.
	 */
	public static function deactivate() {
		// Clear reminder, cleanup and expiration crons
		wp_clear_scheduled_hook( 'wc_hourly_cron' );
		wp_clear_scheduled_hook( 'wc_daily_cleanup_cron' );
		if ( class_exists( 'WC_Cron' ) ) {
			WC_Cron::clear_scheduled_events();
		} else {
			wp_clear_scheduled_hook( 'wc_hourly_cleanup_cron' );
		}

		// Log deactivation
		if ( class_exists( 'WC_Database' ) ) {
			WC_Database::log( array(
				'action' => 'plugin_deactivated',
				'note'   => 'Wellness Consult plugin deactivated. Cron jobs cleared.',
			) );
		}

		flush_rewrite_rules();
	}
}
