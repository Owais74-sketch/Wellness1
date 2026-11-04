<?php
/**
 * Plugin Name: Wellness Consult
 * Plugin URI:  https://example.com/wellness-consult
 * Description: Private consultation scheduling, secure payments, and lifecycle management for sexual wellness counseling.
 * Version:     1.0.0
 * Author:      Wellness Team
 * Author URI:  https://example.com
 * Text Domain: wellness-consult
 * Domain Path: /languages
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants
define( 'WC_VERSION', '1.0.0' );
define( 'WC_PLUGIN_FILE', __FILE__ );
define( 'WC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Uploads and Storage Constants (Rule 2)
define( 'WC_MAX_UPLOAD_SIZE', 5 * 1024 * 1024 ); // 5 MB exactly
define( 'WC_PROOFS_DIR', WP_CONTENT_DIR . '/uploads/wc-proofs/' );
define( 'WC_PROOFS_URL', content_url( '/uploads/wc-proofs/' ) );

// Core Includes
require_once WC_PLUGIN_DIR . 'includes/class-wc-encryption.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-database.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-fraud-protection.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-roles.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-post-types.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-status.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-scheduling.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-options.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-activator.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-deactivator.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-security-headers.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-upload-handler.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-proof-viewer.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-atomic-reference.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-booking-form.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-booking-handler.php';
require_once WC_PLUGIN_DIR . 'includes/gateways/interface-wc-gateway.php';
require_once WC_PLUGIN_DIR . 'includes/gateways/class-wc-gateway-registry.php';
require_once WC_PLUGIN_DIR . 'includes/gateways/class-wc-gateway-stripe.php';
require_once WC_PLUGIN_DIR . 'includes/gateways/class-wc-gateway-paypal.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-rest-api.php';
require_once WC_PLUGIN_DIR . 'includes/class-wc-cron.php';

// Register core payment gateways
WC_Gateway_Registry::register( 'WC_Gateway_Stripe' );
WC_Gateway_Registry::register( 'WC_Gateway_PayPal' );

if ( is_admin() ) {
	require_once WC_PLUGIN_DIR . 'admin/class-wc-admin-actions.php';
	require_once WC_PLUGIN_DIR . 'admin/class-wc-admin-menu.php';
}

/**
 * Automatic migration runner for schema updates.
 */
add_action( 'plugins_loaded', function() {
	if ( ! get_option( 'wc_migration_cr1_done' ) ) {
		WC_Database::migrate_cr1();
	}
} );

/**
 * Register activation and deactivation hooks.
 */
register_activation_hook( WC_PLUGIN_FILE, array( 'WC_Activator', 'activate' ) );
register_deactivation_hook( WC_PLUGIN_FILE, array( 'WC_Deactivator', 'deactivate' ) );

/**
 * Initialize post types and taxonomies on init.
 */
add_action( 'init', array( 'WC_Post_Types', 'register_post_types' ) );

/**
 * Initialize Security Headers, Shortcodes, and Booking Handlers.
 */
add_action( 'init', array( 'WC_Security_Headers', 'init' ) );
add_action( 'init', array( 'WC_Booking_Form', 'init' ) );
add_action( 'init', array( 'WC_Booking_Handler', 'init' ) );
add_action( 'init', array( 'WC_Proof_Viewer', 'init' ) );
add_action( 'init', array( 'WC_Cron', 'init' ) );

/**
 * Initialize REST API routes for Stripe/PayPal webhooks and returns.
 */
add_action( 'rest_api_init', array( 'WC_REST_API', 'register_routes' ) );

/**
 * Initialize admin menus and screens.
 */
if ( is_admin() ) {
	new WC_Admin_Menu();
}

/**
 * Load plugin translation domain.
 */
function wc_consult_load_textdomain() {
	load_plugin_textdomain( 'wellness-consult', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'wc_consult_load_textdomain' );
