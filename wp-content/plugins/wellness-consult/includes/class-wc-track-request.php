<?php
/**
 * Patient-facing "Track My Request" page.
 *
 * Shortcode: [wc_track_request]
 *
 * A patient proves ownership of a request with Reference ID + the email used
 * when booking. A short-lived session (random token in a transient plus an
 * HttpOnly cookie) then lets them see status, appointment details and, when a
 * payment proof was rejected, upload a replacement.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

class WC_Track_Request {

	const COOKIE            = 'wc_track_session';
	const SESSION_TTL       = 1800; // 30 minutes, refreshed on each page view.
	const MAX_FAILED_LOOKUP = 5;    // Failed lookups per IP before a 15 minute lock.
	const MAX_PROOF_FILES   = 6;    // Original proof plus up to five replacements.

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_shortcode( 'wc_track_request', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_disable_caching' ), 1 );

		add_action( 'admin_post_nopriv_wc_track_lookup', array( __CLASS__, 'handle_lookup' ) );
		add_action( 'admin_post_wc_track_lookup', array( __CLASS__, 'handle_lookup' ) );
		add_action( 'admin_post_nopriv_wc_track_logout', array( __CLASS__, 'handle_logout' ) );
		add_action( 'admin_post_wc_track_logout', array( __CLASS__, 'handle_logout' ) );
		add_action( 'admin_post_nopriv_wc_track_upload', array( __CLASS__, 'handle_proof_upload' ) );
		add_action( 'admin_post_wc_track_upload', array( __CLASS__, 'handle_proof_upload' ) );
	}

	/**
	 * Does the current page contain the tracking shortcode?
	 *
	 * @return bool
	 */
	protected static function is_track_page() {
		global $post;
		return $post && is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'wc_track_request' ) || 'track-my-request' === $post->post_name );
	}

	/**
	 * Private patient data must never be cached or indexed.
	 */
	public static function maybe_disable_caching() {
		if ( ! is_singular() || ! self::is_track_page() ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}

	/**
	 * Enqueue styles only on the tracking page.
	 */
	public static function enqueue_assets() {
		if ( self::is_track_page() ) {
			wp_enqueue_style( 'wc-track-css', WC_PLUGIN_URL . 'assets/css/track.css', array(), WC_VERSION );
		}
	}

	/* ------------------------------------------------------------------
	 * Session helpers
	 * ------------------------------------------------------------------ */

	/**
	 * Hashed IP used for rate limiting (never stores the raw address).
	 *
	 * @return string
	 */
	protected static function get_ip_key() {
		$raw_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		return substr( hash_hmac( 'sha256', $raw_ip, wp_salt() ), 0, 32 );
	}

	/**
	 * Read the cookie token if it has the expected shape.
	 *
	 * @return string
	 */
	protected static function get_cookie_token() {
		if ( empty( $_COOKIE[ self::COOKIE ] ) || ! is_string( $_COOKIE[ self::COOKIE ] ) ) {
			return '';
		}
		$token = wp_unslash( $_COOKIE[ self::COOKIE ] );
		return preg_match( '/^[A-Za-z0-9]{40}$/', $token ) ? $token : '';
	}

	/**
	 * Return the request ID for the current session, or 0.
	 *
	 * @param bool $refresh Extend the session lifetime.
	 * @return int
	 */
	protected static function get_session_request_id( $refresh = false ) {
		$token = self::get_cookie_token();
		if ( '' === $token ) {
			return 0;
		}
		$key        = 'wc_track_s_' . hash( 'sha256', $token );
		$request_id = absint( get_transient( $key ) );
		if ( ! $request_id ) {
			return 0;
		}
		$post = get_post( $request_id );
		if ( ! $post || 'wc_request' !== $post->post_type || 'publish' !== $post->post_status ) {
			delete_transient( $key );
			return 0;
		}
		if ( $refresh ) {
			set_transient( $key, $request_id, self::SESSION_TTL );
		}
		return $request_id;
	}

	/**
	 * Store a one-shot message for the next page view of this session.
	 *
	 * @param string $type    success|error.
	 * @param string $message Plain text message.
	 */
	protected static function set_flash( $type, $message ) {
		$token = self::get_cookie_token();
		if ( '' === $token ) {
			return;
		}
		set_transient(
			'wc_track_f_' . hash( 'sha256', $token ),
			array( 'type' => 'success' === $type ? 'success' : 'error', 'message' => (string) $message ),
			120
		);
	}

	/**
	 * Read and clear the flash message.
	 *
	 * @return array|null
	 */
	protected static function pull_flash() {
		$token = self::get_cookie_token();
		if ( '' === $token ) {
			return null;
		}
		$key   = 'wc_track_f_' . hash( 'sha256', $token );
		$flash = get_transient( $key );
		if ( ! is_array( $flash ) ) {
			return null;
		}
		delete_transient( $key );
		return $flash;
	}

	/**
	 * Safe return URL for redirects: the page that hosts the shortcode.
	 *
	 * @return string
	 */
	protected static function get_return_url() {
		$fallback = home_url( '/track-my-request/' );
		$posted   = isset( $_POST['wc_return_to'] ) && is_string( $_POST['wc_return_to'] ) ? esc_url_raw( wp_unslash( $_POST['wc_return_to'] ) ) : '';
		$url      = $posted ? wp_validate_redirect( $posted, $fallback ) : $fallback;
		return remove_query_arg( array( 'wc_track_notice' ), $url );
	}

	/**
	 * Redirect and stop.
	 *
	 * @param string $url URL.
	 */
	protected static function redirect( $url ) {
		wp_safe_redirect( $url );
		exit;
	}

	/* ------------------------------------------------------------------
	 * Form handlers
	 * ------------------------------------------------------------------ */

	/**
	 * Verify Reference ID + email and start a session.
	 */
	public static function handle_lookup() {
		$return_url = self::get_return_url();

		if ( ! isset( $_POST['wc_track_lookup_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wc_track_lookup_nonce'] ) ), 'wc_track_lookup' ) ) {
			self::redirect( add_query_arg( 'wc_track_notice', 'expired', $return_url ) );
		}

		$fail_key = 'wc_track_fail_' . self::get_ip_key();
		if ( (int) get_transient( $fail_key ) >= self::MAX_FAILED_LOOKUP ) {
			self::redirect( add_query_arg( 'wc_track_notice', 'rate', $return_url ) );
		}

		$ref   = isset( $_POST['reference_id'] ) && is_string( $_POST['reference_id'] ) ? strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['reference_id'] ) ) ) ) : '';
		$email = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? strtolower( trim( sanitize_email( wp_unslash( $_POST['email'] ) ) ) ) : '';

		if ( '' === $ref || '' === $email ) {
			self::redirect( add_query_arg( 'wc_track_notice', 'missing', $return_url ) );
		}

		$ids        = get_posts( array(
			'post_type'      => 'wc_request',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array( 'key' => '_wc_reference_id', 'value' => $ref ),
			),
		) );
		$request_id = $ids ? absint( $ids[0] ) : 0;
		$stored     = $request_id ? strtolower( trim( (string) get_post_meta( $request_id, '_wc_contact_email', true ) ) ) : '';

		// Always run the comparison so a wrong reference and a wrong email look the same.
		$matches = hash_equals( '' !== $stored ? $stored : 'no-such-request@invalid', $email ) && $request_id && '' !== $stored;

		if ( ! $matches ) {
			set_transient( $fail_key, (int) get_transient( $fail_key ) + 1, 15 * MINUTE_IN_SECONDS );
			self::redirect( add_query_arg( 'wc_track_notice', 'invalid', $return_url ) );
		}

		$token = wp_generate_password( 40, false, false );
		set_transient( 'wc_track_s_' . hash( 'sha256', $token ), $request_id, self::SESSION_TTL );
		self::set_cookie( $token, 0 );

		self::redirect( $return_url );
	}

	/**
	 * End the session.
	 */
	public static function handle_logout() {
		if ( isset( $_POST['wc_track_logout_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wc_track_logout_nonce'] ) ), 'wc_track_logout' ) ) {
			$token = self::get_cookie_token();
			if ( '' !== $token ) {
				delete_transient( 'wc_track_s_' . hash( 'sha256', $token ) );
				delete_transient( 'wc_track_f_' . hash( 'sha256', $token ) );
			}
			self::set_cookie( '', time() - DAY_IN_SECONDS );
		}
		self::redirect( self::get_return_url() );
	}

	/**
	 * Set or clear the session cookie.
	 *
	 * @param string $value  Cookie value.
	 * @param int    $expires Expiry timestamp (0 = browser session).
	 */
	protected static function set_cookie( $value, $expires ) {
		$options = array(
			'expires'  => $expires,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		);
		if ( COOKIE_DOMAIN ) {
			$options['domain'] = COOKIE_DOMAIN;
		}
		setcookie( self::COOKIE, $value, $options );
	}

	/**
	 * Accept a replacement payment proof after a rejection.
	 */
	public static function handle_proof_upload() {
		$return_url = self::get_return_url();

		if ( ! isset( $_POST['wc_track_upload_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wc_track_upload_nonce'] ) ), 'wc_track_upload' ) ) {
			self::set_flash( 'error', __( 'This page expired. Please try again.', 'wellness-consult' ) );
			self::redirect( $return_url );
		}

		$request_id = self::get_session_request_id( true );
		if ( ! $request_id ) {
			self::redirect( add_query_arg( 'wc_track_notice', 'session', $return_url ) );
		}

		$status = get_post_meta( $request_id, '_wc_status', true );
		if ( WC_Status::STATUS_REJECTED !== $status || 'manual' !== get_post_meta( $request_id, '_wc_payment_method', true ) ) {
			self::set_flash( 'error', __( 'A new payment proof cannot be uploaded for this request right now.', 'wellness-consult' ) );
			self::redirect( $return_url );
		}

		$history = get_post_meta( $request_id, '_wc_proof_history', true );
		$history = is_array( $history ) ? $history : array();
		if ( count( $history ) >= self::MAX_PROOF_FILES ) {
			self::set_flash( 'error', __( 'The maximum number of proof uploads for this request has been reached. Please contact us.', 'wellness-consult' ) );
			self::redirect( $return_url );
		}

		// Block double submits while a replacement is being processed.
		$lock_key = 'wc_track_upload_lock_' . $request_id;
		if ( get_transient( $lock_key ) ) {
			self::set_flash( 'error', __( 'Your upload is already being processed. Please wait a moment.', 'wellness-consult' ) );
			self::redirect( $return_url );
		}
		set_transient( $lock_key, 1, 30 );

		if ( empty( $_FILES['payment_proof'] ) || empty( $_FILES['payment_proof']['name'] ) ) {
			delete_transient( $lock_key );
			self::set_flash( 'error', __( 'Please choose a screenshot or PDF to upload.', 'wellness-consult' ) );
			self::redirect( $return_url );
		}

		$upload = WC_Upload_Handler::handle_upload( $_FILES['payment_proof'] );
		if ( is_wp_error( $upload ) ) {
			delete_transient( $lock_key );
			self::set_flash( 'error', $upload->get_error_message() );
			self::redirect( $return_url );
		}

		$old_file  = get_post_meta( $request_id, '_wc_proof_file', true );
		$old_hash  = get_post_meta( $request_id, '_wc_proof_hash', true );
		$old_trans = get_post_meta( $request_id, '_wc_transaction_id', true );

		$history[] = array(
			'relative_path' => $upload['relative_path'],
			'filename'      => $upload['filename'],
			'hash'          => $upload['hash'],
			'size'          => $upload['size'],
			'mime'          => $upload['mime'],
			'uploaded_at'   => $upload['uploaded_at'],
		);
		update_post_meta( $request_id, '_wc_proof_history', $history );
		update_post_meta( $request_id, '_wc_proof_file', $upload['relative_path'] );
		update_post_meta( $request_id, '_wc_proof_hash', $upload['hash'] );

		$new_trans = isset( $_POST['transaction_id'] ) && is_string( $_POST['transaction_id'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) ) : '';
		if ( '' !== $new_trans && strlen( $new_trans ) <= 100 ) {
			update_post_meta( $request_id, '_wc_transaction_id', $new_trans );
			update_post_meta( $request_id, '_wc_transaction_id_hash', WC_Fraud_Protection::hash_value( 'transaction', $new_trans ) );
		}

		$transition = WC_Status::change_status(
			$request_id,
			WC_Status::STATUS_PENDING_VERIFICATION,
			'Patient uploaded a replacement payment proof for verification.',
			0
		);
		if ( is_wp_error( $transition ) ) {
			// Roll back so the request is left exactly as it was.
			array_pop( $history );
			update_post_meta( $request_id, '_wc_proof_history', $history );
			update_post_meta( $request_id, '_wc_proof_file', $old_file );
			update_post_meta( $request_id, '_wc_proof_hash', $old_hash );
			update_post_meta( $request_id, '_wc_transaction_id', $old_trans );
			update_post_meta( $request_id, '_wc_transaction_id_hash', WC_Fraud_Protection::hash_value( 'transaction', (string) $old_trans ) );
			WC_Upload_Handler::delete_proof( $upload['relative_path'] );
			delete_transient( $lock_key );
			self::set_flash( 'error', __( 'Your upload could not be saved. Please try again.', 'wellness-consult' ) );
			self::redirect( $return_url );
		}

		$ref_id = get_post_meta( $request_id, '_wc_reference_id', true );
		WC_Database::log( array(
			'request_id'   => $request_id,
			'reference_id' => $ref_id,
			'action'       => 'proof_reuploaded',
			'user_name'    => 'Patient',
			'from_status'  => WC_Status::STATUS_REJECTED,
			'to_status'    => WC_Status::STATUS_PENDING_VERIFICATION,
			'note'         => sprintf( 'Replacement proof #%d uploaded by patient (SHA-256 %s).', count( $history ), substr( $upload['hash'], 0, 16 ) ),
		) );

		// Tell the admin there is something to review. A mail failure must not block the patient.
		$sent = wp_mail(
			get_option( 'admin_email' ),
			sprintf( '[Consultation Alert] Replacement payment proof - %s', $ref_id ),
			sprintf(
				"A patient uploaded a replacement payment proof.\n\nReference ID: %s\n\nReview it in the Admin Dashboard:\n%s\n",
				$ref_id,
				admin_url( 'admin.php?page=wc-all-requests&action=view&id=' . $request_id )
			)
		);
		WC_Database::log( array(
			'request_id'   => $request_id,
			'reference_id' => $ref_id,
			'action'       => 'email_delivery',
			'note'         => 'Event proof_reuploaded_admin_alert: ' . ( $sent ? 'sent.' : 'failed.' ),
		) );

		delete_transient( $lock_key );
		self::set_flash( 'success', __( 'Thank you. Your new payment proof was received and is now being reviewed.', 'wellness-consult' ) );
		self::redirect( $return_url );
	}

	/* ------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------ */

	/**
	 * Shortcode output.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		// Make sure styles load even when the shortcode is used inside a builder block.
		wp_enqueue_style( 'wc-track-css', WC_PLUGIN_URL . 'assets/css/track.css', array(), WC_VERSION );

		$request_id = self::get_session_request_id( true );

		ob_start();
		echo '<div class="wc-track-wrap">';
		if ( $request_id ) {
			self::render_status( $request_id );
		} else {
			self::render_lookup_form();
		}
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * URL of the page that holds this shortcode.
	 *
	 * @return string
	 */
	protected static function current_page_url() {
		$permalink = get_permalink();
		return $permalink ? $permalink : home_url( '/track-my-request/' );
	}

	/**
	 * Lookup form.
	 */
	protected static function render_lookup_form() {
		$prefill = isset( $_GET['ref'] ) && is_string( $_GET['ref'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['ref'] ) ) ) : '';
		$notice  = isset( $_GET['wc_track_notice'] ) && is_string( $_GET['wc_track_notice'] ) ? sanitize_key( wp_unslash( $_GET['wc_track_notice'] ) ) : '';
		$notices = array(
			'invalid' => __( 'We could not find a request with that Reference ID and email. Please check both and try again.', 'wellness-consult' ),
			'missing' => __( 'Please enter both your Reference ID and the email you used when booking.', 'wellness-consult' ),
			'rate'    => __( 'Too many unsuccessful attempts. Please wait 15 minutes and try again.', 'wellness-consult' ),
			'expired' => __( 'This page expired. Please try again.', 'wellness-consult' ),
			'session' => __( 'Your session has ended. Please look up your request again.', 'wellness-consult' ),
		);
		?>
		<div class="wc-track-card">
			<h2 class="wc-track-title"><?php esc_html_e( 'Track My Request', 'wellness-consult' ); ?></h2>
			<p class="wc-track-intro"><?php esc_html_e( 'Enter the Reference ID from your confirmation email and the email address you used when booking.', 'wellness-consult' ); ?></p>

			<?php if ( isset( $notices[ $notice ] ) ) : ?>
				<div class="wc-track-notice is-error" role="alert"><?php echo esc_html( $notices[ $notice ] ); ?></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wc-track-form">
				<input type="hidden" name="action" value="wc_track_lookup">
				<input type="hidden" name="wc_return_to" value="<?php echo esc_url( self::current_page_url() ); ?>">
				<?php wp_nonce_field( 'wc_track_lookup', 'wc_track_lookup_nonce' ); ?>

				<label for="wc-track-ref"><?php esc_html_e( 'Reference ID', 'wellness-consult' ); ?></label>
				<input type="text" id="wc-track-ref" name="reference_id" value="<?php echo esc_attr( $prefill ); ?>" placeholder="WC-2026-00001" autocomplete="off" required>

				<label for="wc-track-email"><?php esc_html_e( 'Email address', 'wellness-consult' ); ?></label>
				<input type="email" id="wc-track-email" name="email" autocomplete="email" required>

				<button type="submit" class="wc-track-btn"><?php esc_html_e( 'Find my request', 'wellness-consult' ); ?></button>
			</form>
			<p class="wc-track-privacy"><?php esc_html_e( 'For your privacy, we only show request details after both fields match.', 'wellness-consult' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Patient-friendly explanation for each status.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	protected static function get_status_message( $status ) {
		$messages = array(
			WC_Status::STATUS_AWAITING_PAYMENT        => __( 'We are waiting for your payment to be completed.', 'wellness-consult' ),
			WC_Status::STATUS_PENDING_VERIFICATION    => __( 'We received your payment proof and our team is checking it. This usually takes 1 to 4 hours.', 'wellness-consult' ),
			WC_Status::STATUS_REJECTED                => __( 'We could not verify your payment proof. Please upload a new, clear proof below.', 'wellness-consult' ),
			WC_Status::STATUS_PAID_AWAITING_SCHEDULING => __( 'Your payment is verified. We are assigning a consultant and choosing your appointment time. You will be emailed once it is confirmed.', 'wellness-consult' ),
			WC_Status::STATUS_SCHEDULED               => __( 'Your consultation is confirmed. Your appointment details are below.', 'wellness-consult' ),
			WC_Status::STATUS_COMPLETED               => __( 'Your consultation has been completed. Thank you for trusting us.', 'wellness-consult' ),
			WC_Status::STATUS_CANCELLED               => __( 'This request was cancelled.', 'wellness-consult' ),
			WC_Status::STATUS_NO_SHOW                 => __( 'This consultation was marked as missed. Please contact us if you believe this is a mistake.', 'wellness-consult' ),
			WC_Status::STATUS_REFUNDED                => __( 'This request has been refunded.', 'wellness-consult' ),
			WC_Status::STATUS_EXPIRED                 => __( 'This request expired because payment was not completed in time.', 'wellness-consult' ),
		);
		return $messages[ $status ] ?? '';
	}

	/**
	 * Render the verified request view.
	 *
	 * @param int $request_id Request ID.
	 */
	protected static function render_status( $request_id ) {
		$status       = get_post_meta( $request_id, '_wc_status', true );
		$status       = $status ? $status : WC_Status::STATUS_AWAITING_PAYMENT;
		$ref_id       = get_post_meta( $request_id, '_wc_reference_id', true );
		$name         = get_post_meta( $request_id, '_wc_name', true );
		$type_name    = get_post_meta( $request_id, '_wc_type_name', true );
		$mode         = get_post_meta( $request_id, '_wc_consult_mode', true );
		$fee          = (float) get_post_meta( $request_id, '_wc_fee_usd', true );
		$patient_tz   = get_post_meta( $request_id, '_wc_timezone', true );
		$patient_tz   = $patient_tz ? $patient_tz : 'UTC';
		$pay_method   = get_post_meta( $request_id, '_wc_payment_method', true );
		$method_name  = get_post_meta( $request_id, '_wc_payment_method_name', true );
		$flash        = self::pull_flash();
		$status_msg   = self::get_status_message( $status );
		$return_url   = self::current_page_url();

		// Progress steps shown for the normal path only.
		$steps = array(
			__( 'Payment', 'wellness-consult' ),
			__( 'Scheduling', 'wellness-consult' ),
			__( 'Appointment', 'wellness-consult' ),
			__( 'Completed', 'wellness-consult' ),
		);
		$current_step = null;
		$step_map     = array(
			WC_Status::STATUS_AWAITING_PAYMENT         => 0,
			WC_Status::STATUS_PENDING_VERIFICATION     => 0,
			WC_Status::STATUS_REJECTED                 => 0,
			WC_Status::STATUS_PAID_AWAITING_SCHEDULING => 1,
			WC_Status::STATUS_SCHEDULED                => 2,
			WC_Status::STATUS_COMPLETED                => 3,
		);
		if ( isset( $step_map[ $status ] ) ) {
			$current_step = $step_map[ $status ];
		}
		?>
		<div class="wc-track-card">
			<div class="wc-track-header">
				<div>
					<h2 class="wc-track-title"><?php esc_html_e( 'Your Consultation Request', 'wellness-consult' ); ?></h2>
					<p class="wc-track-ref"><?php esc_html_e( 'Reference ID:', 'wellness-consult' ); ?> <strong><?php echo esc_html( $ref_id ); ?></strong></p>
				</div>
				<span class="wc-track-pill is-<?php echo esc_attr( sanitize_html_class( $status ) ); ?>"><?php echo esc_html( WC_Status::get_label( $status ) ); ?></span>
			</div>

			<?php if ( $flash ) : ?>
				<div class="wc-track-notice is-<?php echo esc_attr( $flash['type'] ); ?>" role="status"><?php echo esc_html( $flash['message'] ); ?></div>
			<?php endif; ?>

			<?php if ( null !== $current_step ) : ?>
				<ol class="wc-track-steps" aria-label="<?php esc_attr_e( 'Request progress', 'wellness-consult' ); ?>">
					<?php foreach ( $steps as $index => $label ) : ?>
						<?php
						$class = $index < $current_step ? 'is-done' : ( $index === $current_step ? 'is-current' : '' );
						// A finished consultation shows every step as done.
						if ( WC_Status::STATUS_COMPLETED === $status ) {
							$class = 'is-done';
						}
						?>
						<li class="<?php echo esc_attr( $class ); ?>"><span class="wc-track-step-dot"><?php echo esc_html( $index + 1 ); ?></span><span><?php echo esc_html( $label ); ?></span></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<?php if ( '' !== $status_msg ) : ?>
				<p class="wc-track-status-msg"><?php echo esc_html( $status_msg ); ?></p>
			<?php endif; ?>

			<?php if ( WC_Status::STATUS_SCHEDULED === $status ) : ?>
				<?php self::render_appointment( $request_id, $mode, $patient_tz ); ?>
			<?php endif; ?>

			<?php if ( WC_Status::STATUS_REJECTED === $status ) : ?>
				<?php
				$reason = get_post_meta( $request_id, '_wc_rejection_reason', true );
				if ( $reason ) :
					?>
					<div class="wc-track-box is-warning">
						<strong><?php esc_html_e( 'Reason from our team:', 'wellness-consult' ); ?></strong>
						<p><?php echo esc_html( $reason ); ?></p>
					</div>
				<?php endif; ?>
				<?php self::render_upload_form( $request_id, $pay_method, $return_url ); ?>
			<?php endif; ?>

			<?php if ( WC_Status::STATUS_CANCELLED === $status ) : ?>
				<?php $cancel_reason = get_post_meta( $request_id, '_wc_cancellation_reason', true ); ?>
				<?php if ( $cancel_reason ) : ?>
					<div class="wc-track-box">
						<strong><?php esc_html_e( 'Reason:', 'wellness-consult' ); ?></strong>
						<p><?php echo esc_html( $cancel_reason ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<dl class="wc-track-details">
				<?php if ( $name ) : ?>
					<dt><?php esc_html_e( 'Name', 'wellness-consult' ); ?></dt>
					<dd><?php echo esc_html( $name ); ?></dd>
				<?php endif; ?>
				<?php if ( $type_name ) : ?>
					<dt><?php esc_html_e( 'Consultation', 'wellness-consult' ); ?></dt>
					<dd><?php echo esc_html( $type_name ); ?></dd>
				<?php endif; ?>
				<?php if ( $mode ) : ?>
					<dt><?php esc_html_e( 'Mode', 'wellness-consult' ); ?></dt>
					<dd><?php echo esc_html( WC_Options::get_mode_icon( $mode ) . ' ' . WC_Options::get_mode_label( $mode ) ); ?></dd>
				<?php endif; ?>
				<dt><?php esc_html_e( 'Fee', 'wellness-consult' ); ?></dt>
				<dd><?php echo esc_html( '$' . number_format( $fee, 2 ) . ' USD' ); ?></dd>
				<?php if ( $method_name ) : ?>
					<dt><?php esc_html_e( 'Payment method', 'wellness-consult' ); ?></dt>
					<dd><?php echo esc_html( $method_name ); ?></dd>
				<?php endif; ?>
			</dl>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wc-track-logout">
				<input type="hidden" name="action" value="wc_track_logout">
				<input type="hidden" name="wc_return_to" value="<?php echo esc_url( $return_url ); ?>">
				<?php wp_nonce_field( 'wc_track_logout', 'wc_track_logout_nonce' ); ?>
				<button type="submit" class="wc-track-link"><?php esc_html_e( 'Look up a different request', 'wellness-consult' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Appointment details for a scheduled request.
	 *
	 * @param int    $request_id Request ID.
	 * @param string $mode       Consultation mode.
	 * @param string $patient_tz Patient time zone.
	 */
	protected static function render_appointment( $request_id, $mode, $patient_tz ) {
		$utc           = WC_Scheduling::get_request_utc( $request_id );
		$consultant_id = absint( get_post_meta( $request_id, '_wc_consultant_id', true ) );
		$duration      = absint( get_post_meta( $request_id, '_wc_duration', true ) );
		$platform      = get_post_meta( $request_id, '_wc_platform', true );
		$access        = get_post_meta( $request_id, '_wc_meeting_link', true );
		$labels        = $utc ? WC_Scheduling::get_time_labels( $utc, $patient_tz, $consultant_id ) : array( 'patient' => '' );
		$is_url        = $access && filter_var( $access, FILTER_VALIDATE_URL ) && in_array( strtolower( (string) wp_parse_url( $access, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true );
		?>
		<div class="wc-track-box is-appointment">
			<h3><?php esc_html_e( 'Your appointment', 'wellness-consult' ); ?></h3>
			<dl class="wc-track-details">
				<?php if ( ! empty( $labels['patient'] ) ) : ?>
					<dt><?php esc_html_e( 'When (your time)', 'wellness-consult' ); ?></dt>
					<dd><strong><?php echo esc_html( $labels['patient'] ); ?></strong></dd>
				<?php endif; ?>
				<?php if ( $duration ) : ?>
					<dt><?php esc_html_e( 'Duration', 'wellness-consult' ); ?></dt>
					<dd><?php echo esc_html( sprintf( /* translators: %d minutes */ __( '%d minutes', 'wellness-consult' ), $duration ) ); ?></dd>
				<?php endif; ?>
				<?php if ( $platform ) : ?>
					<dt><?php esc_html_e( 'Platform', 'wellness-consult' ); ?></dt>
					<dd><?php echo esc_html( $platform ); ?></dd>
				<?php endif; ?>
				<?php if ( $access ) : ?>
					<dt><?php echo 'video' === $mode ? esc_html__( 'Meeting link', 'wellness-consult' ) : esc_html__( 'We will contact you on', 'wellness-consult' ); ?></dt>
					<dd>
						<?php if ( $is_url ) : ?>
							<a href="<?php echo esc_url( $access ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $access ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $access ); ?>
						<?php endif; ?>
					</dd>
				<?php endif; ?>
			</dl>
		</div>
		<?php
	}

	/**
	 * Replacement proof upload form.
	 *
	 * @param int    $request_id Request ID.
	 * @param string $pay_method Stored payment method type.
	 * @param string $return_url Page URL.
	 */
	protected static function render_upload_form( $request_id, $pay_method, $return_url ) {
		if ( 'manual' !== $pay_method ) {
			return;
		}
		$history = get_post_meta( $request_id, '_wc_proof_history', true );
		$count   = is_array( $history ) ? count( $history ) : 0;
		if ( $count >= self::MAX_PROOF_FILES ) {
			echo '<div class="wc-track-box is-warning"><p>' . esc_html__( 'You have reached the maximum number of proof uploads for this request. Please contact us for help.', 'wellness-consult' ) . '</p></div>';
			return;
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="wc-track-form wc-track-upload">
			<h3><?php esc_html_e( 'Upload a new payment proof', 'wellness-consult' ); ?></h3>
			<input type="hidden" name="action" value="wc_track_upload">
			<input type="hidden" name="wc_return_to" value="<?php echo esc_url( $return_url ); ?>">
			<?php wp_nonce_field( 'wc_track_upload', 'wc_track_upload_nonce' ); ?>

			<label for="wc-track-proof"><?php esc_html_e( 'Screenshot or PDF (JPG, PNG or PDF, up to 5 MB)', 'wellness-consult' ); ?></label>
			<input type="file" id="wc-track-proof" name="payment_proof" accept="image/jpeg,image/png,application/pdf,.jpg,.jpeg,.png,.pdf" required>

			<label for="wc-track-trans"><?php esc_html_e( 'New transaction ID (only if it changed)', 'wellness-consult' ); ?></label>
			<input type="text" id="wc-track-trans" name="transaction_id" maxlength="100" autocomplete="off">

			<button type="submit" class="wc-track-btn"><?php esc_html_e( 'Send new proof', 'wellness-consult' ); ?></button>
		</form>
		<?php
	}
}
