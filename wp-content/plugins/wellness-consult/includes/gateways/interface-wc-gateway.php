<?php
/**
 * Gateway Contract Interface
 *
 * Enforces uniform gateway implementation across Stripe, PayPal, and future gateways (Requirement 8).
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

interface WC_Gateway_Interface {

	/**
	 * Unique gateway identifier (e.g., 'stripe', 'paypal').
	 *
	 * @return string
	 */
	public static function get_id();

	/**
	 * Human-readable gateway display label.
	 *
	 * @return string
	 */
	public static function get_name();

	/**
	 * Check whether the gateway is enabled and has valid API credentials configured.
	 *
	 * @return bool
	 */
	public static function is_available();

	/**
	 * Create hosted checkout session or order and return the redirect URL.
	 *
	 * @param int   $post_id Consultation Request post ID.
	 * @param array $args    Submission data (name, email, phone, etc.).
	 * @return array|WP_Error Array with 'id' and 'url', or WP_Error.
	 */
	public static function create_checkout( $post_id, $args );

	/**
	 * Process and verify incoming provider webhook notifications.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function handle_webhook( $request );

	/**
	 * Execute an online refund through the provider API.
	 *
	 * @param int        $post_id Consultation Request post ID.
	 * @param float|null $amount  Refund amount in USD.
	 * @param string     $reason  Refund reason note.
	 * @return true|WP_Error
	 */
	public static function refund( $post_id, $amount = null, $reason = '' );
}
