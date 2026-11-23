<?php
/**
 * Free anonymous Q&A (SRS v2.0 Section 4.10, FR-33 and FR-34).
 *
 * Shortcodes:
 *   [wc_ask_form]            Public question form (question, category, optional email).
 *   [wc_answered_questions]  Published answers only. Optional: limit="10".
 *
 * Questions are stored in a private post type (wc_question). Nothing is ever
 * shown publicly until a moderator publishes a cleaned-up version.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

class WC_Questions {

	const POST_TYPE    = 'wc_question';
	const MAX_PER_HOUR = 3;
	const MIN_LENGTH   = 20;
	const MAX_LENGTH   = 2000;

	/**
	 * Register hooks.
	 */
	public static function init() {
		self::register_post_type();

		add_shortcode( 'wc_ask_form', array( __CLASS__, 'render_form' ) );
		add_shortcode( 'wc_answered_questions', array( __CLASS__, 'render_answered' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 20 );

		add_action( 'admin_post_nopriv_wcq_submit', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_wcq_submit', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_wcq_moderate', array( __CLASS__, 'handle_moderate' ) );
	}

	/**
	 * Private post type. No front-end URLs.
	 */
	protected static function register_post_type() {
		register_post_type( self::POST_TYPE, array(
			'labels'              => array( 'name' => __( 'Anonymous Questions', 'wellness-consult' ) ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'query_var'           => false,
			'rewrite'             => false,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'supports'            => array( 'title' ),
		) );
	}

	/**
	 * Allowed categories (slug => label).
	 *
	 * @return array
	 */
	public static function categories() {
		return array(
			'intimacy'      => __( 'Intimacy & Relationships', 'wellness-consult' ),
			'mens-health'   => __( "Men's Health & Performance", 'wellness-consult' ),
			'womens-health' => __( "Women's Health & Comfort", 'wellness-consult' ),
			'lifestyle'     => __( 'Stress, Habits & Lifestyle', 'wellness-consult' ),
			'general'       => __( 'General Sexual Health Question', 'wellness-consult' ),
		);
	}

	/**
	 * Moderation statuses (slug => label).
	 *
	 * @return array
	 */
	public static function statuses() {
		return array(
			'pending'   => __( 'Pending', 'wellness-consult' ),
			'answered'  => __( 'Answered (not published)', 'wellness-consult' ),
			'published' => __( 'Published', 'wellness-consult' ),
			'rejected'  => __( 'Rejected', 'wellness-consult' ),
		);
	}

	/**
	 * Number of questions in a given moderation status.
	 *
	 * @param string $status Status slug, or '' for all.
	 * @return int
	 */
	public static function count_by_status( $status = '' ) {
		$args = array(
			'post_type'        => self::POST_TYPE,
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		);
		if ( '' !== $status ) {
			$args['meta_key']   = '_wcq_status';
			$args['meta_value'] = $status;
		}
		return count( get_posts( $args ) );
	}

	/* ------------------------------------------------------------------
	 * Front end
	 * ------------------------------------------------------------------ */

	protected static function has_form_on_page() {
		global $post;
		return $post && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'wc_ask_form' );
	}

	/**
	 * Load the Cloudflare Turnstile script only if a site key is configured.
	 */
	public static function enqueue_assets() {
		if ( ! self::has_form_on_page() ) {
			return;
		}
		$settings = get_option( 'wc_payment_settings', array() );
		if ( ! empty( $settings['turnstile_site_key'] ) ) {
			wp_enqueue_script( 'wc-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
		}
	}

	protected static function ip_key() {
		$raw = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		return substr( hash_hmac( 'sha256', $raw, wp_salt() ), 0, 32 );
	}

	/**
	 * Show the form.
	 *
	 * @return string
	 */
	public static function render_form() {
		if ( ! get_option( 'wc_questions_enabled', false ) ) {
			return '<div class="wc-unavailable-notice" style="background:var(--surface,#fff); border:1px solid var(--border,#ddd); padding:2rem; border-radius:8px; text-align:center; max-width:640px; margin:2rem auto;"><p style="margin:0; font-size:1.1rem; color:var(--text,#333); font-weight:500;">' . esc_html__( 'Anonymous questions are currently closed.', 'wellness-consult' ) . '</p></div>';
		}

		$errors = array();
		$values = array( 'category' => 'intimacy', 'question' => '', 'email' => '' );
		$done   = false;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['wcq'] ) && 'ok' === $_GET['wcq'] ) {
			$done = true;
		}
		if ( isset( $_GET['wcq_t'] ) ) {
			$token = preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['wcq_t'] ) );
			$flash = $token ? get_transient( 'wcq_flash_' . $token ) : false;
			if ( is_array( $flash ) ) {
				delete_transient( 'wcq_flash_' . $token );
				$errors = (array) $flash['errors'];
				$values = array_merge( $values, (array) $flash['values'] );
			}
		}

		$settings = get_option( 'wc_payment_settings', array() );
		$site_key = ! empty( $settings['turnstile_site_key'] ) ? $settings['turnstile_site_key'] : '';

		ob_start();
		?>
		<div class="ask-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:2.5rem; box-shadow:var(--shadow-sm); margin-bottom:3.5rem;">
			<?php if ( $done ) : ?>
				<div role="status" style="background:#ecfdf5; border:1px solid #6ee7b7; border-radius:8px; padding:1rem 1.25rem; margin-bottom:1.5rem; line-height:1.6;">
					<strong><?php esc_html_e( 'Thank you. Your question has been received.', 'wellness-consult' ); ?></strong><br>
					<?php esc_html_e( 'It will be reviewed by our team. Not every question can be answered. If yours is answered, it may be published below with all personal details removed.', 'wellness-consult' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $errors ) : ?>
				<div role="alert" style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:1rem 1.25rem; margin-bottom:1.5rem;">
					<ul style="margin:0; padding-left:1.1rem;">
						<?php foreach ( $errors as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<form id="anonymous-question-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex; flex-direction:column; gap:1.25rem;">
				<input type="hidden" name="action" value="wcq_submit">
				<?php wp_nonce_field( 'wcq_submit', 'wcq_nonce' ); ?>

				<div style="position:absolute; left:-9999px;" aria-hidden="true">
					<label for="wcq_hp"><?php esc_html_e( 'Leave this field empty', 'wellness-consult' ); ?></label>
					<input type="text" id="wcq_hp" name="wcq_hp" value="" tabindex="-1" autocomplete="off">
				</div>

				<div class="notice-privacy-strip" style="background:var(--color-surface-subtle); border-left:3px solid var(--color-accent); padding:0.85rem 1rem; border-radius:var(--radius-sm); font-size:var(--text-xs); color:var(--color-text-secondary); line-height:1.5;">
					<strong><?php esc_html_e( 'Privacy & Medical Notice:', 'wellness-consult' ); ?></strong>
					<?php esc_html_e( 'Questions go to a moderation queue. Answers may be published on this page with personal details removed. This service is not for emergencies. If you are in danger or need urgent care, contact your local emergency service.', 'wellness-consult' ); ?>
				</div>

				<div style="display:flex; flex-direction:column; gap:0.35rem;">
					<label for="qa_category" style="font-weight:700; font-size:var(--text-sm);"><?php esc_html_e( 'Topic Category *', 'wellness-consult' ); ?></label>
					<select id="qa_category" name="category" required style="border:1px solid var(--border); border-radius:var(--radius-md); padding:0.75rem 1rem; font-size:var(--text-base); background:var(--surface);">
						<?php foreach ( self::categories() as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $values['category'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="display:flex; flex-direction:column; gap:0.35rem;">
					<label for="qa_question" style="font-weight:700; font-size:var(--text-sm);"><?php esc_html_e( 'Your Question *', 'wellness-consult' ); ?></label>
					<textarea id="qa_question" name="question" rows="5" required minlength="<?php echo esc_attr( self::MIN_LENGTH ); ?>" maxlength="<?php echo esc_attr( self::MAX_LENGTH ); ?>" placeholder="<?php esc_attr_e( 'Describe your concern. There is no need to share your name or location.', 'wellness-consult' ); ?>" style="border:1px solid var(--color-border-strong); border-radius:var(--radius-md); padding:0.75rem 1rem; font-size:var(--text-base);"><?php echo esc_textarea( $values['question'] ); ?></textarea>
				</div>

				<div style="display:flex; flex-direction:column; gap:0.35rem;">
					<label for="qa_email" style="font-weight:700; font-size:var(--text-sm);">
						<?php esc_html_e( 'Email (optional)', 'wellness-consult' ); ?>
						<span style="font-weight:normal; color:var(--color-text-muted);">(<?php esc_html_e( 'Only if you want to be emailed when your question is published', 'wellness-consult' ); ?>)</span>
					</label>
					<input type="email" id="qa_email" name="email" value="<?php echo esc_attr( $values['email'] ); ?>" placeholder="you@example.com" autocomplete="off" style="border:1px solid var(--color-border-strong); border-radius:var(--radius-md); padding:0.75rem 1rem; font-size:var(--text-base);">
				</div>

				<?php if ( $site_key ) : ?>
					<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
				<?php endif; ?>

				<button type="submit" class="btn btn-primary btn-pill" style="min-height:48px; font-size:var(--text-base); margin-top:0.5rem;">
					<?php esc_html_e( 'Submit Question Anonymously', 'wellness-consult' ); ?>
				</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * List published answers only.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_answered( $atts = array() ) {
		if ( ! get_option( 'wc_questions_enabled', false ) ) {
			return '';
		}

		$atts  = shortcode_atts( array( 'limit' => 10 ), $atts, 'wc_answered_questions' );
		$limit = max( 1, min( 50, absint( $atts['limit'] ) ) );

		$items = get_posts( array(
			'post_type'   => self::POST_TYPE,
			'post_status' => 'any',
			'numberposts' => $limit,
			'meta_key'    => '_wcq_published_at',
			'orderby'     => 'meta_value_num',
			'order'       => 'DESC',
			'meta_query'  => array(
				array(
					'key'   => '_wcq_status',
					'value' => 'published',
				),
			),
		) );

		$cats = self::categories();

		ob_start();
		?>
		<section class="answered-qa-section" aria-labelledby="answered-qa-heading">
			<div style="margin-bottom:1.5rem;">
				<h2 id="answered-qa-heading" style="font-size:var(--text-2xl);"><?php esc_html_e( 'Recently Answered Questions', 'wellness-consult' ); ?></h2>
			</div>

			<?php if ( ! $items ) : ?>
				<p class="text-muted"><?php esc_html_e( 'No answered questions have been published yet.', 'wellness-consult' ); ?></p>
			<?php else : ?>
				<div class="qa-cards-list" style="display:flex; flex-direction:column; gap:1.25rem; margin-bottom:3rem;">
					<?php foreach ( $items as $item ) :
						$cat = (string) get_post_meta( $item->ID, '_wcq_category', true );
						?>
						<article class="qa-item-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-md); padding:1.5rem;">
							<?php if ( isset( $cats[ $cat ] ) ) : ?>
								<div style="margin-bottom:0.5rem;"><span class="badge badge-primary"><?php echo esc_html( $cats[ $cat ] ); ?></span></div>
							<?php endif; ?>
							<h3 style="font-size:var(--text-base); margin-bottom:0.75rem;">
								Q: <?php echo esc_html( get_post_meta( $item->ID, '_wcq_public_question', true ) ); ?>
							</h3>
							<div style="background:var(--color-surface-subtle); padding:1rem; border-radius:var(--radius-sm); font-size:var(--text-sm); line-height:1.6; border-left:3px solid var(--color-secondary);">
								<strong><?php esc_html_e( 'Consultant Response:', 'wellness-consult' ); ?></strong>
								<?php echo wp_kses_post( wpautop( esc_html( get_post_meta( $item->ID, '_wcq_answer', true ) ) ) ); ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	/* ------------------------------------------------------------------
	 * Submission
	 * ------------------------------------------------------------------ */

	protected static function back_url() {
		$ref = wp_get_referer();
		$url = $ref ? $ref : home_url( '/ask-a-question/' );
		return remove_query_arg( array( 'wcq', 'wcq_t' ), $url );
	}

	protected static function fail( $errors, $values ) {
		$token = wp_generate_password( 20, false, false );
		set_transient( 'wcq_flash_' . $token, array( 'errors' => $errors, 'values' => $values ), 300 );
		wp_safe_redirect( add_query_arg( 'wcq_t', $token, self::back_url() ) . '#anonymous-question-form' );
		exit;
	}

	protected static function verify_turnstile() {
		$settings = get_option( 'wc_payment_settings', array() );
		if ( empty( $settings['turnstile_site_key'] ) ) {
			return true;
		}
		$secret = defined( 'WC_TURNSTILE_SECRET_KEY' ) && WC_TURNSTILE_SECRET_KEY ? WC_TURNSTILE_SECRET_KEY : ( $settings['turnstile_secret_key'] ?? '' );
		$token  = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
		if ( empty( $secret ) || empty( $token ) ) {
			return false;
		}
		$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
			'timeout' => 10,
			'body'    => array(
				'secret'   => $secret,
				'response' => $token,
				'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
			),
		) );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$result = json_decode( wp_remote_retrieve_body( $response ), true );
		return 200 === wp_remote_retrieve_response_code( $response ) && ! empty( $result['success'] );
	}

	/**
	 * Handle the public form post.
	 */
	public static function handle_submit() {
		if ( ! get_option( 'wc_questions_enabled', false ) ) {
			wp_die( esc_html__( 'Anonymous questions are currently closed.', 'wellness-consult' ), 'Forbidden', array( 'response' => 403 ) );
		}

		if ( ! isset( $_POST['wcq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcq_nonce'] ) ), 'wcq_submit' ) ) {
			wp_die( esc_html__( 'Your session expired. Please go back, reload the page and try again.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		if ( ! empty( $_POST['wcq_hp'] ) ) {
			wp_die( esc_html__( 'Spam submission detected.', 'wellness-consult' ), '', array( 'response' => 400 ) );
		}

		$category = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : '';
		$question = isset( $_POST['question'] ) ? sanitize_textarea_field( wp_unslash( $_POST['question'] ) ) : '';
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$values   = array( 'category' => $category, 'question' => $question, 'email' => $email );
		$errors   = array();

		if ( ! isset( self::categories()[ $category ] ) ) {
			$errors[] = __( 'Please choose a topic category.', 'wellness-consult' );
		}
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $question ) : strlen( $question );
		if ( $length < self::MIN_LENGTH ) {
			$errors[] = sprintf( __( 'Please write at least %d characters so we can understand your question.', 'wellness-consult' ), self::MIN_LENGTH );
		}
		if ( $length > self::MAX_LENGTH ) {
			$errors[] = sprintf( __( 'Your question is too long. Please keep it under %d characters.', 'wellness-consult' ), self::MAX_LENGTH );
		}
		if ( ! empty( $_POST['email'] ) && ! is_email( $email ) ) {
			$errors[] = __( 'The email address does not look valid. You can leave it empty.', 'wellness-consult' );
		}
		if ( $email && WC_Fraud_Protection::is_blocked( $email, '', '' ) ) {
			$errors[] = __( 'This email address cannot be used.', 'wellness-consult' );
		}
		if ( $errors ) {
			self::fail( $errors, $values );
		}

		if ( ! self::verify_turnstile() ) {
			self::fail( array( __( 'Please complete the security check and try again.', 'wellness-consult' ) ), $values );
		}

		$ip_key   = self::ip_key();
		$rate_key = 'wcq_rate_' . $ip_key;
		$count    = (int) get_transient( $rate_key );
		if ( $count >= self::MAX_PER_HOUR ) {
			self::fail( array( __( 'You have sent several questions recently. Please try again in about an hour.', 'wellness-consult' ) ), $values );
		}

		$post_id = wp_insert_post( array(
			'post_type'   => self::POST_TYPE,
			'post_status' => 'private',
			'post_title'  => 'Anonymous question ' . current_time( 'Y-m-d H:i' ),
		), true );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			self::fail( array( __( 'Sorry, your question could not be saved. Please try again.', 'wellness-consult' ) ), $values );
		}

		update_post_meta( $post_id, '_wcq_status', 'pending' );
		update_post_meta( $post_id, '_wcq_category', $category );
		update_post_meta( $post_id, '_wcq_question', $question );
		update_post_meta( $post_id, '_wcq_ip_hash', $ip_key );
		if ( $email ) {
			update_post_meta( $post_id, '_wcq_email', WC_Encryption::encrypt( $email ) );
		}
		set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );

		// Tell the admin that something is waiting. The message never contains the question or the email.
		wp_mail(
			get_option( 'admin_email' ),
			__( 'New anonymous question waiting', 'wellness-consult' ),
			sprintf( __( 'A new anonymous question is waiting for review: %s', 'wellness-consult' ), admin_url( 'admin.php?page=wc-questions' ) )
		);

		wp_safe_redirect( add_query_arg( 'wcq', 'ok', self::back_url() ) . '#anonymous-question-form' );
		exit;
	}

	/* ------------------------------------------------------------------
	 * Moderation (admin)
	 * ------------------------------------------------------------------ */

	/**
	 * Save draft, publish, reject or delete a question.
	 */
	public static function handle_moderate() {
		$id = isset( $_POST['qid'] ) ? absint( $_POST['qid'] ) : 0;
		check_admin_referer( 'wcq_moderate_' . $id );

		if ( ! current_user_can( 'wc_manage_assigned_requests' ) ) {
			wp_die( esc_html__( 'You do not have permission to moderate questions.', 'wellness-consult' ), '', array( 'response' => 403 ) );
		}
		$post = $id ? get_post( $id ) : null;
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'Question not found.', 'wellness-consult' ), '', array( 'response' => 404 ) );
		}

		$list   = admin_url( 'admin.php?page=wc-questions' );
		$op     = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		$public = isset( $_POST['public_question'] ) ? sanitize_textarea_field( wp_unslash( $_POST['public_question'] ) ) : '';
		$answer = isset( $_POST['answer'] ) ? sanitize_textarea_field( wp_unslash( $_POST['answer'] ) ) : '';
		$cat    = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : '';
		if ( isset( self::categories()[ $cat ] ) ) {
			update_post_meta( $id, '_wcq_category', $cat );
		}

		if ( 'delete' === $op ) {
			wp_delete_post( $id, true );
			wp_safe_redirect( add_query_arg( 'wcq_msg', 'deleted', $list ) );
			exit;
		}

		if ( 'reject' === $op ) {
			update_post_meta( $id, '_wcq_status', 'rejected' );
			delete_post_meta( $id, '_wcq_email' );
			wp_safe_redirect( add_query_arg( 'wcq_msg', 'rejected', $list ) );
			exit;
		}

		// Save draft or publish: both store the cleaned question and the answer.
		update_post_meta( $id, '_wcq_public_question', $public );
		update_post_meta( $id, '_wcq_answer', $answer );
		update_post_meta( $id, '_wcq_answered_by', get_current_user_id() );

		if ( 'publish' === $op ) {
			if ( '' === trim( $public ) || '' === trim( $answer ) ) {
				wp_safe_redirect( add_query_arg( array( 'page' => 'wc-questions', 'qid' => $id, 'wcq_msg' => 'incomplete' ), admin_url( 'admin.php' ) ) );
				exit;
			}
			$was_published = 'published' === get_post_meta( $id, '_wcq_status', true );
			update_post_meta( $id, '_wcq_status', 'published' );
			if ( ! $was_published ) {
				update_post_meta( $id, '_wcq_published_at', time() );
				self::notify_published( $id );
			}
			wp_safe_redirect( add_query_arg( 'wcq_msg', 'published', $list ) );
			exit;
		}

		// Plain save.
		if ( 'published' !== get_post_meta( $id, '_wcq_status', true ) ) {
			update_post_meta( $id, '_wcq_status', '' !== trim( $answer ) ? 'answered' : 'pending' );
		}
		wp_safe_redirect( add_query_arg( 'wcq_msg', 'saved', $list ) );
		exit;
	}

	/**
	 * Email the asker once (if an email was given), then delete the stored address.
	 *
	 * @param int $id Question ID.
	 */
	protected static function notify_published( $id ) {
		$stored = get_post_meta( $id, '_wcq_email', true );
		if ( ! $stored ) {
			return;
		}
		$email = WC_Encryption::decrypt( $stored );
		if ( $email && is_email( $email ) ) {
			wp_mail(
				$email,
				__( 'Your question has been answered', 'wellness-consult' ),
				sprintf(
					__( "Your anonymous question has been answered and published without personal details.\n\nYou can read the answer here: %s\n\nThis is an automatic message. We do not keep your email address after sending it.", 'wellness-consult' ),
					home_url( '/ask-a-question/' )
				)
			);
		}
		delete_post_meta( $id, '_wcq_email' );
	}
}