<?php
/**
 * Security and Cache Headers for Booking & Sensitive Pages
 *
 * Implements Rule 1: Add noindex, nofollow and no-cache headers (DONOTCACHEPAGE, nocache_headers)
 * to booking and confirmation pages.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Security_Headers {

	/**
	 * Initialize security header hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'apply_security_headers' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_robots_meta' ), 99 );
	}

	/**
	 * Check if current request is the booking or confirmation page.
	 *
	 * @return bool
	 */
	public static function is_booking_or_confirmation_page() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		if ( isset( $_GET['wc_confirmed'] ) || isset( $_GET['token'] ) ) {
			return true;
		}

		if ( is_page( 'book-consultation' ) ) {
			return true;
		}

		global $post;
		if ( $post && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'wc_booking' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Apply no-cache and anti-indexing headers.
	 */
	public static function apply_security_headers() {
		if ( ! self::is_booking_or_confirmation_page() ) {
			return;
		}

		// Prevent caching by plugins / reverse proxies (e.g., WP Super Cache, LiteSpeed, Cloudflare)
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( 'DONOTCACHEDB', true );
		}
		if ( ! defined( 'DONOTMINIFY' ) ) {
			define( 'DONOTMINIFY', true );
		}

		// Send WordPress standard nocache headers
		nocache_headers();

		// Send explicit HTTP header to prevent search engine indexing of confidential booking views
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
		}
	}

	/**
	 * Inject noindex and nofollow into WordPress wp_robots tag.
	 *
	 * @param array $robots Current robots directives.
	 * @return array
	 */
	public static function filter_robots_meta( $robots ) {
		if ( self::is_booking_or_confirmation_page() ) {
			$robots['noindex']   = true;
			$robots['nofollow']  = true;
			$robots['noarchive'] = true;
		}
		return $robots;
	}
}
