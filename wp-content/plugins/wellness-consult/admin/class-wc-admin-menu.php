<?php
/**
 * Admin Menu and Submenu Registration
 *
 * Implements SRS v2.0 Section 6 and Phase 3 specifications.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Admin_Menu {

	/**
	 * Constructor. Registers admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin styles and scripts on plugin screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, 'wc-' ) && 'wc_request' !== get_post_type() ) {
			return;
		}

		wp_enqueue_style(
			'wc-admin-styles',
			plugins_url( 'css/admin.css', __FILE__ ),
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'wc-admin-scripts',
			plugins_url( 'js/admin.js', __FILE__ ),
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}

	/**
	 * Register the main 'Consultations' menu and 12 distinct submenus.
	 */
	public function register_menus() {
		// Main Top-level Menu
		add_menu_page(
			esc_html__( 'Consultations', 'wellness-consult' ),
			esc_html__( 'Consultations', 'wellness-consult' ),
			'wc_manage_assigned_requests',
			'wc-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-heart',
			26
		);

		// 1. Dashboard (replaces top-level default duplicate)
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Consultations Dashboard', 'wellness-consult' ),
			esc_html__( 'Dashboard', 'wellness-consult' ),
			'wc_manage_assigned_requests',
			'wc-dashboard',
			array( $this, 'render_dashboard' )
		);

		// 2. All Requests
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'All Consultation Requests', 'wellness-consult' ),
			esc_html__( 'All Requests', 'wellness-consult' ),
			'wc_manage_assigned_requests',
			'wc-all-requests',
			array( $this, 'render_all_requests' )
		);

		// 3. Calendar
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Consultation Calendar', 'wellness-consult' ),
			esc_html__( 'Calendar', 'wellness-consult' ),
			'wc_view_calendar',
			'wc-calendar',
			array( $this, 'render_calendar' )
		);

		// 4. Consultation Types
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Consultation Types & Fees', 'wellness-consult' ),
			esc_html__( 'Consultation Types', 'wellness-consult' ),
			'wc_manage_types',
			'wc-types',
			array( $this, 'render_types' )
		);

		// 5. Payments
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Payment Gateways & Accounts', 'wellness-consult' ),
			esc_html__( 'Payments', 'wellness-consult' ),
			'wc_manage_payments',
			'wc-payments',
			array( $this, 'render_payments' )
		);

		// 6. Availability
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Consultant Availability & Hours', 'wellness-consult' ),
			esc_html__( 'Availability', 'wellness-consult' ),
			'wc_manage_own_availability',
			'wc-availability',
			array( $this, 'render_availability' )
		);

		// 7. Countries
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Country Restrictions & Scope', 'wellness-consult' ),
			esc_html__( 'Countries', 'wellness-consult' ),
			'wc_manage_countries',
			'wc-countries',
			array( $this, 'render_countries' )
		);

		// 8. Anonymous Questions (Phase 9 preview)
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Anonymous Questions Moderation', 'wellness-consult' ),
			esc_html__( 'Anonymous Questions', 'wellness-consult' ),
			'wc_manage_settings',
			'wc-questions',
			array( $this, 'render_questions' )
		);

		// 9. Email Templates
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Notification Email Templates', 'wellness-consult' ),
			esc_html__( 'Email Templates', 'wellness-consult' ),
			'wc_manage_email_templates',
			'wc-email-templates',
			array( $this, 'render_email_templates' )
		);

		// 10. Settings
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Consultation Settings', 'wellness-consult' ),
			esc_html__( 'Settings', 'wellness-consult' ),
			'wc_manage_settings',
			'wc-settings',
			array( $this, 'render_settings' )
		);

		// 11. Reports (Phase 9 preview)
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Consultation Reports & Income', 'wellness-consult' ),
			esc_html__( 'Reports', 'wellness-consult' ),
			'wc_view_reports',
			'wc-reports',
			array( $this, 'render_reports' )
		);

		// 12. Activity Log
		add_submenu_page(
			'wc-dashboard',
			esc_html__( 'Consultation Activity & Audit Log', 'wellness-consult' ),
			esc_html__( 'Activity Log', 'wellness-consult' ),
			'wc_view_activity_log',
			'wc-activity-log',
			array( $this, 'render_activity_log' )
		);
	}

	/**
	 * Screen render callbacks
	 */
	public function render_dashboard() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-dashboard.php';
	}

	public function render_all_requests() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-all-requests.php';
	}

	public function render_calendar() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-calendar.php';
	}

	public function render_types() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-types.php';
	}

	public function render_payments() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-payments.php';
	}

	public function render_availability() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-availability.php';
	}

	public function render_countries() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-countries.php';
	}

	public function render_questions() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-questions.php';
	}

	public function render_email_templates() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-email-templates.php';
	}

	public function render_settings() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-settings.php';
	}

	public function render_reports() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-reports.php';
	}

	public function render_activity_log() {
		require_once plugin_dir_path( __FILE__ ) . 'screens/screen-activity-log.php';
	}
}
