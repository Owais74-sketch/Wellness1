<?php
/**
 * Fired during plugin activation
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Activator {

	/**
	 * Run activation tasks:
	 * - Register CPT
	 * - Create activity log database table
	 * - Create custom roles and capabilities
	 * - Initialize default options
	 * - Create Book Consultation page with shortcode
	 * - Flush rewrite rules
	 */
	public static function activate() {
		// 1. Register post types so rewrite rules can be flushed
		WC_Post_Types::register_post_types();

		// 2. Create custom DB tables (Activity Log)
		WC_Database::create_tables();

		// 3. Create custom roles and capabilities
		WC_Roles::create_roles();

		// 4. Initialize default options (empty types, safe payment defaults)
		WC_Options::init_defaults();

		// 4.1. Run data migration (CR-1: Modes and platforms)
		WC_Database::migrate_cr1();

		// 5. Ensure Book Consultation page exists (Rule 1)
		self::create_booking_page();

		// 6. Ensure secure upload directory and .htaccess exist (Rule 2)
		WC_Upload_Handler::get_storage_dir();

		// 7. Log activation event
		WC_Database::log( array(
			'action' => 'plugin_activated',
			'note'   => 'Wellness Consult plugin activated successfully.',
		) );

		// 8. Flush rewrite rules once
		flush_rewrite_rules();
	}

	/**
	 * Create "Book Consultation" page with [wc_booking] and page-book-consultation.php template.
	 */
	public static function create_booking_page() {
		$page_path = 'book-consultation';
		$existing  = get_page_by_path( $page_path );

		if ( ! $existing ) {
			$page_id = wp_insert_post( array(
				'post_title'     => 'Book Consultation',
				'post_name'      => $page_path,
				'post_content'   => '[wc_booking]',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'comment_status' => 'closed',
			) );

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_post_meta( $page_id, '_wp_page_template', 'page-book-consultation.php' );
			}
		} else {
			// Ensure template and shortcode are assigned
			if ( false === strpos( $existing->post_content, '[wc_booking]' ) ) {
				wp_update_post( array(
					'ID'           => $existing->ID,
					'post_content' => '[wc_booking]',
				) );
			}
			update_post_meta( $existing->ID, '_wp_page_template', 'page-book-consultation.php' );
		}
	}
}
