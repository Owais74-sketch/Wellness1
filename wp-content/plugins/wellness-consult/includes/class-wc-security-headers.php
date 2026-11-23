<?php
/**
 * Security and Cache Headers for Booking & Sensitive Pages
 *
 * Implements Rule 1: Add noindex, nofollow and no-cache headers (DONOTCACHEPAGE, nocache_headers)
 * to booking, tracking and confirmation pages.
 *
 * Phase 11 additions:
 * - Site-wide hardening headers (nosniff, clickjacking, referrer, permissions, HSTS on HTTPS)
 * - Track My Request page is now treated as a private page
 * - Strict no-store + no-referrer on private pages (tokens in URLs must not leak)
 *
 * @package Wellness_Consult
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Security_Headers {

	/**
	 * Initialize security header hooks.
	 */
	public static function init() {
		add_action( 'send_headers', array( __CLASS__, 'send_global_headers' ) );
		add_action( 'template_redirect', array( __CLASS__, 'apply_security_headers' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_robots_meta' ), 99 );
	}

	/**
	 * Shortcodes whose pages show private patient data or booking state.
	 * Unknown shortcode names are harmless (has_shortcode just returns false).
	 *
	 * @return array
	 */
	public static function private_shortcodes() {
		return apply_filters(
			'wc_consult_private_shortcodes',
			array( 'wc_booking', 'wc_track_request', 'wc_track', 'wc_track_my_request' )
		);
	}

	/**
	 * Check if current request is a booking, tracking or confirmation page.
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

		// Extra private page slugs (defined in wellness-consult.php).
		if ( function_exists( 'wc_consult_private_page_slugs' ) && is_page( wc_consult_private_page_slugs() ) ) {
			return true;
		}

		global $post;
		if ( $post && is_a( $post, 'WP_Post' ) ) {
			foreach ( self::private_shortcodes() as $shortcode ) {
				if ( has_shortcode( $post->post_content, $shortcode ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Hardening headers sent on every front-end request.
	 */
	public static function send_global_headers() {
		if ( is_admin() || headers_sent() ) {
			return;
		}

		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );

		// HSTS only makes sense (and is only honoured) over HTTPS, so it stays off on local http.
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000' );
		}
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

		if ( ! headers_sent() ) {
			// Stronger than nocache_headers(): never stored by browsers or shared caches.
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );

			// Tokens can appear in the URL, so never send this URL as a Referer to other sites.
			header( 'Referrer-Policy: no-referrer', true );

			// Send explicit HTTP header to prevent search engine indexing of confidential views
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