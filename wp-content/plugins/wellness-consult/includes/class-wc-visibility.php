<?php
/**
 * Consultation & Questions Public Visibility Controller
 *
 * Implements the master kill-switches for consultations and anonymous questions.
 * Syncs page post_status, blocks direct visitor access with 404s, and shows
 * an informative banner to logged-in administrators.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Visibility {

	/**
	 * Slugs governed by consultation switch.
	 */
	const CONSULTATION_SLUGS = array(
		'book-consultation',
		'track-my-request',
		'refund-policy',
	);

	/**
	 * Slugs governed by anonymous questions switch.
	 */
	const QUESTIONS_SLUGS = array(
		'ask-a-question',
	);

	/**
	 * Hook registrations.
	 */
	public static function init() {
		// Enforce access control on front-end
		add_action( 'template_redirect', array( __CLASS__, 'handle_front_redirect' ), 1 );

		// Admin notice on disabled pages
		add_filter( 'the_content', array( __CLASS__, 'inject_admin_notice' ), 5 );

		// Sync page statuses when switches change
		add_action( 'update_option_wc_consultations_enabled', array( __CLASS__, 'sync_page_statuses' ) );
		add_action( 'update_option_wc_questions_enabled', array( __CLASS__, 'sync_page_statuses' ) );

		// Support [wc_anonymous_question] alias shortcode
		add_shortcode( 'wc_anonymous_question', array( __CLASS__, 'render_anonymous_question_shortcode' ) );
	}

	/**
	 * Check if consultations are active.
	 *
	 * @return bool
	 */
	public static function is_consultations_enabled() {
		return (bool) get_option( 'wc_consultations_enabled', false );
	}

	/**
	 * Check if anonymous questions are active.
	 *
	 * @return bool
	 */
	public static function is_questions_enabled() {
		return (bool) get_option( 'wc_questions_enabled', false );
	}

	/**
	 * Sync WordPress page statuses (publish vs private).
	 */
	public static function sync_page_statuses() {
		$consult_enabled   = self::is_consultations_enabled();
		$questions_enabled = self::is_questions_enabled();

		$consult_target = $consult_enabled ? 'publish' : 'private';
		foreach ( self::CONSULTATION_SLUGS as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && $page->post_status !== $consult_target ) {
				wp_update_post( array(
					'ID'          => $page->ID,
					'post_status' => $consult_target,
				) );
			}
		}

		$questions_target = $questions_enabled ? 'publish' : 'private';
		foreach ( self::QUESTIONS_SLUGS as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && $page->post_status !== $questions_target ) {
				wp_update_post( array(
					'ID'          => $page->ID,
					'post_status' => $questions_target,
				) );
			}
		}
	}

	/**
	 * Intercept front-end page requests.
	 */
	public static function handle_front_redirect() {
		if ( is_admin() ) {
			return;
		}

		$is_consult_page   = is_page( self::CONSULTATION_SLUGS );
		$is_questions_page = is_page( self::QUESTIONS_SLUGS );

		// Check by URI path as well (covers direct page requests before rewrite matches)
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$parsed_path = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
		$path_segments = explode( '/', $parsed_path );
		$current_slug = end( $path_segments );

		if ( ! $is_consult_page && in_array( $current_slug, self::CONSULTATION_SLUGS, true ) ) {
			$is_consult_page = true;
		}
		if ( ! $is_questions_page && in_array( $current_slug, self::QUESTIONS_SLUGS, true ) ) {
			$is_questions_page = true;
		}

		if ( $is_consult_page && ! self::is_consultations_enabled() ) {
			if ( current_user_can( 'manage_options' ) ) {
				// Admin can preview the page
				return;
			}
			self::send_404();
		}

		if ( $is_questions_page && ! self::is_questions_enabled() ) {
			if ( current_user_can( 'manage_options' ) ) {
				// Admin can preview the page
				return;
			}
			self::send_404();
		}
	}

	/**
	 * Send 404 response to unauthorized visitors.
	 */
	protected static function send_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		$template_404 = get_query_template( '404' );
		if ( $template_404 && file_exists( $template_404 ) ) {
			include $template_404;
		} else {
			wp_die( esc_html__( 'Page not found.', 'wellness-consult' ), 'Page Not Found', array( 'response' => 404 ) );
		}
		exit;
	}

	/**
	 * Inject admin preview banner for logged-in administrators.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function inject_admin_notice( $content ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return $content;
		}

		if ( is_page( self::CONSULTATION_SLUGS ) && ! self::is_consultations_enabled() ) {
			$banner = '<div class="wc-admin-preview-notice" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-left:4px solid #ffc107; padding:12px 18px; border-radius:6px; margin: 0 0 24px 0; font-weight:600; font-size:14px; display:flex; align-items:center; gap:8px;">'
				. '⚠️ ' . esc_html__( 'This page is hidden from visitors because consultations are disabled in Settings.', 'wellness-consult' )
				. '</div>';
			return $banner . $content;
		}

		if ( is_page( self::QUESTIONS_SLUGS ) && ! self::is_questions_enabled() ) {
			$banner = '<div class="wc-admin-preview-notice" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-left:4px solid #ffc107; padding:12px 18px; border-radius:6px; margin: 0 0 24px 0; font-weight:600; font-size:14px; display:flex; align-items:center; gap:8px;">'
				. '⚠️ ' . esc_html__( 'This page is hidden from visitors because anonymous questions are disabled in Settings.', 'wellness-consult' )
				. '</div>';
			return $banner . $content;
		}

		return $content;
	}

	/**
	 * Alias shortcode for anonymous question.
	 *
	 * @return string
	 */
	public static function render_anonymous_question_shortcode() {
		if ( ! self::is_questions_enabled() ) {
			return '<div class="wc-unavailable-notice" style="background:var(--surface,#fff); border:1px solid var(--border,#ddd); padding:2rem; border-radius:8px; text-align:center;"><p style="margin:0; font-size:1.1rem; color:var(--text,#333);">' . esc_html__( 'Anonymous questions are currently closed.', 'wellness-consult' ) . '</p></div>';
		}
		if ( class_exists( 'WC_Questions' ) ) {
			return WC_Questions::render_form();
		}
		return '';
	}
}
