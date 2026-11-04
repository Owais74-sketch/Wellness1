<?php
/**
 * Public Booking Form & Confirmation Screen
 *
 * Implements Rule 1: Multi-step booking form [wc_booking], progress indicator,
 * accessible focus transitions, and single-use confirmation card.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Booking_Form {

	/**
	 * Initialize booking form hooks.
	 */
	public static function init() {
		add_shortcode( 'wc_booking', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 20 );
	}

	/**
	 * Enqueue frontend booking assets only when booking shortcode is present.
	 */
	public static function enqueue_assets() {
		global $post;
		$is_booking_page = ( $post && is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'wc_booking' ) || 'book-consultation' === $post->post_name ) );

		if ( $is_booking_page || isset( $_GET['wc_confirmed'] ) ) {
			wp_enqueue_style(
				'wc-booking-css',
				WC_PLUGIN_URL . 'assets/css/booking.css',
				array(),
				WC_VERSION
			);

			wp_enqueue_script(
				'wc-booking-js',
				WC_PLUGIN_URL . 'assets/js/booking.js',
				array( 'jquery' ),
				WC_VERSION,
				true
			);

			// Localize script data (Country rules, max upload limit, i18n strings)
			$countries_settings = get_option( 'wc_countries_settings', array(
				'mode'         => 'all_except_blocked',
				'allowed_list' => array(),
				'blocked_list' => array(),
			) );

			wp_localize_script( 'wc-booking-js', 'wcBookingData', array(
				'maxUploadSize' => WC_MAX_UPLOAD_SIZE,
				'countryMode'   => $countries_settings['mode'] ?? 'all_except_blocked',
				'blockedList'   => $countries_settings['blocked_list'] ?? array(),
				'allowedList'   => $countries_settings['allowed_list'] ?? array(),
				'i18n'          => array(
					'copied'            => esc_html__( 'Copied!', 'wellness-consult' ),
					'under18Error'      => esc_html__( 'Consultations are strictly for adults aged 18 and older. Submissions under 18 are blocked.', 'wellness-consult' ),
					'blockedCountry'    => esc_html__( 'Private consultations are not available in your selected country due to cross-border healthcare compliance regulations.', 'wellness-consult' ),
					'fileTooBig'        => sprintf( esc_html__( 'The selected file exceeds the %s MB size limit. Please choose a smaller image or PDF.', 'wellness-consult' ), round( WC_MAX_UPLOAD_SIZE / ( 1024 * 1024 ) ) ),
					'invalidFileType'   => esc_html__( 'Only real JPG, PNG, and PDF files are accepted.', 'wellness-consult' ),
					'missingScreenshot' => esc_html__( 'Please upload your payment screenshot proof before submitting.', 'wellness-consult' ),
					'contactRequired'   => esc_html__( 'Please provide either a WhatsApp phone number or an email address.', 'wellness-consult' ),
				),
			) );
		}
	}

	/**
	 * Helper: Return policy link if page exists, otherwise plain text fallback.
	 *
	 * @param string $slug
	 * @param string $label
	 * @return string HTML link or plain text.
	 */
	public static function get_policy_link( $slug, $label ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			return sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( get_permalink( $page->ID ) ), esc_html( $label ) );
		}
		return esc_html( $label );
	}

	/**
	 * Main Shortcode Render Entry
	 *
	 * @return string HTML output.
	 */
	public static function render_shortcode() {
		// 1. Check for PRG Confirmation Screen
		if ( isset( $_GET['wc_confirmed'] ) && ! empty( $_GET['token'] ) ) {
			return self::render_confirmation_screen( sanitize_text_field( wp_unslash( $_GET['token'] ) ) );
		}

		// 2. Query Active Consultation Types (CR-1: Filter out types with no enabled mode)
		$all_types = get_option( 'wc_consultation_types', array() );
		$active_types = array_filter( $all_types, function( $t ) {
			if ( empty( $t['is_active'] ) ) {
				return false;
			}
			return ( ! empty( $t['enable_chat'] ) || ! empty( $t['enable_voice'] ) || ! empty( $t['enable_video'] ) );
		} );

		// 3. Query Active Payment Methods
		$manual_methods = get_option( 'wc_manual_methods', array() );
		$active_manual  = array_filter( $manual_methods, function( $m ) {
			return ! empty( $m['active'] );
		} );

		$payment_settings = get_option( 'wc_payment_settings', array() );
		$has_stripe = class_exists( 'WC_Gateway_Stripe' ) && WC_Gateway_Stripe::is_available();
		$has_paypal = class_exists( 'WC_Gateway_PayPal' ) && WC_Gateway_PayPal::is_available();
		$has_any_payment = ! empty( $active_manual ) || $has_stripe || $has_paypal;

		// 4. Rule 1: If no active consultation type or no active payment method exists, show friendly message
		if ( empty( $active_types ) || ! $has_any_payment ) {
			return self::render_unavailable_notice();
		}

		// 5. Render Multi-step Booking Form
		return self::render_booking_form( $active_types, $active_manual, $has_stripe, $has_paypal, $payment_settings );
	}

	/**
	 * Friendly notice when booking is unavailable.
	 */
	protected static function render_unavailable_notice() {
		ob_start();
		?>
		<div class="wc-booking-unavailable-card" role="region" aria-label="<?php esc_attr_e( 'Booking Status', 'wellness-consult' ); ?>">
			<div class="wc-unavailable-icon">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
					<line x1="16" y1="2" x2="16" y2="6"></line>
					<line x1="8" y1="2" x2="8" y2="6"></line>
					<line x1="3" y1="10" x2="21" y2="10"></line>
				</svg>
			</div>
			<h2 class="wc-unavailable-title"><?php esc_html_e( 'Booking is not available yet', 'wellness-consult' ); ?></h2>
			<p class="wc-unavailable-desc">
				<?php esc_html_e( 'Our private consultation calendar is currently being prepared. Please check back shortly or explore our educational articles and myths vs facts.', 'wellness-consult' ); ?>
			</p>
			<div class="wc-unavailable-action">
				<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Explore Health Articles', 'wellness-consult' ); ?> &rarr;
				</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the 3-step booking form.
	 */
	protected static function render_booking_form( $active_types, $active_manual, $has_stripe, $has_paypal, $payment_settings ) {
		// Retrieve stored submission errors or flash data
		$form_errors = WC_Booking_Handler::get_flash_errors();
		$old_data    = WC_Booking_Handler::get_flash_data();

		$all_countries = WC_Options::get_all_iso_countries();
		$countries_settings = get_option( 'wc_countries_settings', array(
			'mode'         => 'all_except_blocked',
			'allowed_list' => array(),
			'blocked_list' => array(),
		) );

		// Filter country options based on setting
		$country_options = array();
		$mode = $countries_settings['mode'] ?? 'all_except_blocked';
		$blocked_list = $countries_settings['blocked_list'] ?? array();
		$allowed_list = $countries_settings['allowed_list'] ?? array();

		foreach ( $all_countries as $code => $cname ) {
			if ( 'all_except_blocked' === $mode ) {
				$is_blocked = in_array( $code, $blocked_list, true );
			} else {
				$is_blocked = ! in_array( $code, $allowed_list, true );
			}
			$country_options[ $code ] = array(
				'name'       => $cname,
				'is_blocked' => $is_blocked,
			);
		}

		$timezones = timezone_identifiers_list();

		ob_start();
		?>
		<div class="wc-booking-container" id="wc-booking-wrapper">

			<!-- Step Progress Indicator -->
			<div class="wc-progress-nav" role="tablist" aria-label="<?php esc_attr_e( 'Booking Progress', 'wellness-consult' ); ?>">
				<div class="wc-progress-step is-active" id="wc-step-pill-1" data-step="1">
					<div class="wc-step-circle">1</div>
					<div class="wc-step-label"><?php esc_html_e( 'Type & Mode', 'wellness-consult' ); ?></div>
				</div>
				<div class="wc-progress-line"></div>
				<div class="wc-progress-step" id="wc-step-pill-2" data-step="2">
					<div class="wc-step-circle">2</div>
					<div class="wc-step-label"><?php esc_html_e( 'Payment Method', 'wellness-consult' ); ?></div>
				</div>
				<div class="wc-progress-line"></div>
				<div class="wc-progress-step" id="wc-step-pill-3" data-step="3">
					<div class="wc-step-circle">3</div>
					<div class="wc-step-label"><?php esc_html_e( 'Confidential Form', 'wellness-consult' ); ?></div>
				</div>
			</div>

			<!-- Live Accessible Error Region -->
			<div class="wc-errors-live" aria-live="polite" id="wc-live-announcer">
				<?php if ( ! empty( $form_errors ) ) : ?>
					<div class="wc-error-summary" tabindex="-1" id="wc-form-error-summary">
						<h4><?php esc_html_e( 'Please correct the following errors:', 'wellness-consult' ); ?></h4>
						<ul>
							<?php foreach ( $form_errors as $err ) : ?>
								<li><?php echo esc_html( $err ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>

			<form id="wc-booking-multistep-form" method="post" action="" enctype="multipart/form-data" novalidate>
				<?php wp_nonce_field( 'wc_submit_booking_action', 'wc_booking_nonce' ); ?>
				<input type="hidden" name="wc_booking_submit" value="1" />
				<input type="hidden" name="wc_idempotency_token" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>" />

				<!-- Anti-spam Honeypot (tabindex -1, hidden) -->
				<div style="display:none!important;position:absolute!important;left:-9999px!important;" aria-hidden="true">
					<label for="wc_hp_gate">Do not fill this field</label>
					<input type="text" id="wc_hp_gate" name="wc_hp_gate" value="" tabindex="-1" autocomplete="off" />
				</div>

				<!-- ========================================== -->
				<!-- STEP 1: Package & Mode Selection (CR-1)   -->
				<!-- ========================================== -->
				<section class="wc-step-section is-visible" id="wc-step-1" aria-labelledby="wc-step-1-title">
					<h2 id="wc-step-1-title" class="wc-step-heading" tabindex="-1">
						<?php esc_html_e( '1. Select Consultation Package & Mode', 'wellness-consult' ); ?>
					</h2>
					<p class="wc-step-desc">
						<?php esc_html_e( 'First, select your consultation package. Then choose your preferred mode (Chat, Voice call, or Video call). Fees are based on your selected mode.', 'wellness-consult' ); ?>
					</p>

					<div class="wc-types-grid" role="radiogroup" aria-label="<?php esc_attr_e( 'Consultation Packages', 'wellness-consult' ); ?>">
						<?php
						$first = true;
						foreach ( $active_types as $tid => $type ) :
							$checked = ( ! empty( $old_data['consultation_type'] ) && $old_data['consultation_type'] === $tid ) || ( empty( $old_data['consultation_type'] ) && $first );
							$first = false;
						?>
							<label class="wc-type-card <?php echo $checked ? 'is-selected' : ''; ?>" for="type_<?php echo esc_attr( $tid ); ?>">
								<input type="radio" name="consultation_type" id="type_<?php echo esc_attr( $tid ); ?>" value="<?php echo esc_attr( $tid ); ?>"
									data-name="<?php echo esc_attr( $type['name'] ); ?>"
									data-duration="<?php echo esc_attr( $type['duration'] ?? 30 ); ?>"
									data-chat-enabled="<?php echo ! empty( $type['enable_chat'] ) ? '1' : '0'; ?>"
									data-chat-fee="<?php echo esc_attr( number_format( (float) ( $type['fee_chat'] ?? 0 ), 2, '.', '' ) ); ?>"
									data-voice-enabled="<?php echo ! empty( $type['enable_voice'] ) ? '1' : '0'; ?>"
									data-voice-fee="<?php echo esc_attr( number_format( (float) ( $type['fee_voice'] ?? 0 ), 2, '.', '' ) ); ?>"
									data-video-enabled="<?php echo ! empty( $type['enable_video'] ) ? '1' : '0'; ?>"
									data-video-fee="<?php echo esc_attr( number_format( (float) ( $type['fee_video'] ?? 0 ), 2, '.', '' ) ); ?>"
									<?php checked( $checked ); ?> required />
								<div class="wc-type-card-body">
									<div class="wc-type-card-header">
										<h3 class="wc-type-title"><?php echo esc_html( $type['name'] ); ?></h3>
										<span class="wc-type-duration-pill">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
											<?php printf( esc_html__( '%d min', 'wellness-consult' ), absint( $type['duration'] ?? 30 ) ); ?>
										</span>
									</div>
									<?php if ( ! empty( $type['description'] ) ) : ?>
										<p class="wc-type-desc"><?php echo esc_html( $type['description'] ); ?></p>
									<?php endif; ?>
									<div class="wc-type-modes-indicator">
										<span><?php esc_html_e( 'Modes available:', 'wellness-consult' ); ?></span>
										<?php if ( ! empty( $type['enable_chat'] ) ) : ?><span class="wc-mode-tag">💬 Chat</span><?php endif; ?>
										<?php if ( ! empty( $type['enable_voice'] ) ) : ?><span class="wc-mode-tag">📞 Voice</span><?php endif; ?>
										<?php if ( ! empty( $type['enable_video'] ) ) : ?><span class="wc-mode-tag">📹 Video</span><?php endif; ?>
									</div>
								</div>
							</label>
						<?php endforeach; ?>
					</div>

					<!-- CR-1: Consultation Mode Selection Section -->
					<div class="wc-modes-selection-container" id="wc-modes-container" aria-live="polite">
						<h3 class="wc-modes-title"><?php esc_html_e( 'Choose Consultation Mode', 'wellness-consult' ); ?></h3>
						<p class="wc-modes-subtitle"><?php esc_html_e( 'Select how you want to attend your private consultation session:', 'wellness-consult' ); ?></p>

						<div class="wc-modes-grid" role="radiogroup" aria-label="<?php esc_attr_e( 'Consultation Mode', 'wellness-consult' ); ?>">
							<!-- Chat Mode Card -->
							<label class="wc-mode-card" id="wc-mode-card-chat" for="mode_chat">
								<input type="radio" name="consult_mode" id="mode_chat" value="chat" <?php checked( ( $old_data['consult_mode'] ?? '' ) === 'chat' ); ?> required />
								<div class="wc-mode-card-body">
									<div class="wc-mode-card-top">
										<div class="wc-mode-icon-circle" aria-hidden="true">
											<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
										</div>
										<div class="wc-mode-fee-pill" id="wc-mode-fee-chat">$0.00 USD</div>
									</div>
									<h4 class="wc-mode-name"><?php esc_html_e( 'Chat', 'wellness-consult' ); ?></h4>
									<p class="wc-mode-desc"><?php esc_html_e( 'Text consultation, no voice or camera', 'wellness-consult' ); ?></p>
								</div>
							</label>

							<!-- Voice Call Mode Card -->
							<label class="wc-mode-card" id="wc-mode-card-voice" for="mode_voice">
								<input type="radio" name="consult_mode" id="mode_voice" value="voice" <?php checked( ( $old_data['consult_mode'] ?? '' ) === 'voice' ); ?> required />
								<div class="wc-mode-card-body">
									<div class="wc-mode-card-top">
										<div class="wc-mode-icon-circle" aria-hidden="true">
											<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
										</div>
										<div class="wc-mode-fee-pill" id="wc-mode-fee-voice">$0.00 USD</div>
									</div>
									<h4 class="wc-mode-name"><?php esc_html_e( 'Voice Call', 'wellness-consult' ); ?></h4>
									<p class="wc-mode-desc"><?php esc_html_e( 'Audio only, camera stays off', 'wellness-consult' ); ?></p>
								</div>
							</label>

							<!-- Video Call Mode Card -->
							<label class="wc-mode-card" id="wc-mode-card-video" for="mode_video">
								<input type="radio" name="consult_mode" id="mode_video" value="video" <?php checked( ( $old_data['consult_mode'] ?? '' ) === 'video' ); ?> required />
								<div class="wc-mode-card-body">
									<div class="wc-mode-card-top">
										<div class="wc-mode-icon-circle" aria-hidden="true">
											<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
										</div>
										<div class="wc-mode-fee-pill" id="wc-mode-fee-video">$0.00 USD</div>
									</div>
									<h4 class="wc-mode-name"><?php esc_html_e( 'Video Call', 'wellness-consult' ); ?></h4>
									<p class="wc-mode-desc"><?php esc_html_e( 'Face to face by video', 'wellness-consult' ); ?></p>
									<p class="wc-mode-camera-note"><?php esc_html_e( 'You can choose to keep your camera off.', 'wellness-consult' ); ?></p>
								</div>
							</label>
						</div>
					</div>

					<div class="wc-step-buttons">
						<div></div>
						<button type="button" class="button button-primary wc-btn-next" id="wc-btn-step-1-next" data-next="2">
							<?php esc_html_e( 'Proceed to Payment Method', 'wellness-consult' ); ?> &rarr;
						</button>
					</div>
				</section>

				<!-- ========================================== -->
				<!-- STEP 2: Payment Method Choice              -->
				<!-- ========================================== -->
				<section class="wc-step-section" id="wc-step-2" aria-labelledby="wc-step-2-title">
					<h2 id="wc-step-2-title" class="wc-step-heading" tabindex="-1">
						<?php esc_html_e( '2. Choose Payment Method', 'wellness-consult' ); ?>
					</h2>
					<p class="wc-step-desc">
						<?php esc_html_e( 'Select your preferred international transfer method below. Complete the payment in your banking or wallet app, then upload the receipt screenshot.', 'wellness-consult' ); ?>
					</p>

					<!-- International Transfer Processing Notice (Requirement 4) -->
					<div class="wc-intl-notice-card" role="status">
						<div class="wc-intl-notice-icon">⏱</div>
						<div class="wc-intl-notice-body">
							<strong><?php esc_html_e( 'International Transfer Settlement Notice:', 'wellness-consult' ); ?></strong>
							<p>
								<?php esc_html_e( 'Notice: International transfers can take 1 to 5 working days to settle. Your private consultation time is assigned after the payment is verified by our team.', 'wellness-consult' ); ?>
							</p>
						</div>
					</div>

					<!-- Selected Fee Summary Box (CR-1: Type, Mode, Fee) -->
					<div class="wc-fee-summary-strip">
						<div class="wc-summary-col">
							<span class="wc-summary-label"><?php esc_html_e( 'Package:', 'wellness-consult' ); ?></span>
							<strong id="wc-display-selected-type">—</strong>
						</div>
						<div class="wc-summary-col">
							<span class="wc-summary-label"><?php esc_html_e( 'Mode:', 'wellness-consult' ); ?></span>
							<strong id="wc-display-selected-mode">—</strong>
						</div>
						<div class="wc-summary-col wc-summary-total">
							<span class="wc-summary-label"><?php esc_html_e( 'Total Fee:', 'wellness-consult' ); ?></span>
							<strong class="wc-fee-strip-amount" id="wc-display-selected-fee">$0.00 USD</strong>
						</div>
					</div>

					<!-- Optional Country Filter for Payment Methods -->
					<div class="wc-step2-country-filter">
						<label for="wc-step2-country-select" class="wc-step2-country-label">
							<span class="dashicons dashicons-admin-site-alt3" style="font-size:16px;line-height:1.2;vertical-align:text-top;"></span>
							<?php esc_html_e( 'Showing methods for country:', 'wellness-consult' ); ?>
						</label>
						<select id="wc-step2-country-select" class="wc-select wc-step2-country-dropdown">
							<option value=""><?php esc_html_e( 'Worldwide (All Countries)', 'wellness-consult' ); ?></option>
							<?php foreach ( $country_options as $iso_code => $c_data ) : ?>
								<option value="<?php echo esc_attr( $iso_code ); ?>" <?php selected( $old_data['country'] ?? '', $iso_code ); ?>>
									<?php echo esc_html( $c_data['name'] . ' (' . $iso_code . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Empty state notice when all methods are filtered out by country -->
					<div class="wc-no-methods-alert" id="wc-no-methods-country-alert" style="display:none;">
						<div class="wc-no-methods-icon">🌐</div>
						<div class="wc-no-methods-content">
							<h4><?php esc_html_e( 'No transfer methods available for this country', 'wellness-consult' ); ?></h4>
							<p><?php esc_html_e( 'There are currently no manual payment accounts configured for the selected country. Please choose Worldwide above or contact our clinic support.', 'wellness-consult' ); ?></p>
						</div>
					</div>

					<div class="wc-payment-methods-grid" id="wc-payment-methods-grid">
						<!-- Manual Transfer Options -->
						<?php
						$first_manual = true;
						foreach ( $active_manual as $mid => $method ) :
							$checked = ( ! empty( $old_data['payment_method_id'] ) && $old_data['payment_method_id'] === $mid ) || ( empty( $old_data['payment_method_id'] ) && $first_manual );
							$first_manual = false;
							$m_type = $method['method_type'] ?? 'bank';
							$countries_attr = ! empty( $method['allowed_countries'] ) ? implode( ',', (array) $method['allowed_countries'] ) : '';
						?>
							<div class="wc-payment-option-card <?php echo $checked ? 'is-selected' : ''; ?>" data-method-type="manual" data-countries="<?php echo esc_attr( $countries_attr ); ?>" id="wc-method-card-<?php echo esc_attr( $mid ); ?>">
								<label class="wc-payment-option-label" for="method_<?php echo esc_attr( $mid ); ?>">
									<input type="radio" name="payment_method_id" id="method_<?php echo esc_attr( $mid ); ?>" value="<?php echo esc_attr( $mid ); ?>" data-kind="manual" <?php checked( $checked ); ?> />
									<div class="wc-payment-option-title-row">
										<div class="wc-method-header-title">
											<strong><?php echo esc_html( $method['name'] ); ?></strong>
											<span class="wc-badge-method wc-badge-<?php echo esc_attr( $m_type ); ?>">
												<?php echo esc_html( WC_Options::get_manual_payment_type_badge( $m_type ) ); ?>
											</span>
										</div>
										<?php if ( ! empty( $method['allowed_countries'] ) ) : ?>
											<span class="wc-badge-country-limit">
												<?php
												$c_limit_names = array();
												foreach ( $method['allowed_countries'] as $ccode ) {
													$c_limit_names[] = $all_countries[ $ccode ] ?? $ccode;
												}
												printf( esc_html__( 'Limited to: %s', 'wellness-consult' ), esc_html( implode( ', ', array_slice( $c_limit_names, 0, 2 ) ) . ( count( $c_limit_names ) > 2 ? ' +' . ( count( $c_limit_names ) - 2 ) : '' ) ) );
												?>
											</span>
										<?php endif; ?>
									</div>
								</label>

								<div class="wc-manual-details-box">
									<!-- Exact Amount to Pay Box with Copy Amount Button (Requirement 2) -->
									<div class="wc-method-amount-strip">
										<div class="wc-amount-info">
											<span class="wc-amount-label"><?php esc_html_e( 'Exact Amount to Send:', 'wellness-consult' ); ?></span>
											<strong class="wc-copy-amount-val wc-dynamic-amount">$0.00 USD</strong>
										</div>
										<button type="button" class="wc-copy-btn wc-copy-amount-btn" data-copy="" aria-label="<?php esc_attr_e( 'Copy exact amount', 'wellness-consult' ); ?>">
											<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy Amount', 'wellness-consult' ); ?>
										</button>
									</div>

									<!-- TYPE 1: Bank Transfer (SWIFT / IBAN) -->
									<?php if ( 'bank' === $m_type ) : ?>
										<div class="wc-copyable-fields-list">
											<?php
											$holder = $method['account_holder'] ?? ( $method['account_title'] ?? '' );
											if ( ! empty( $holder ) ) : ?>
												<div class="wc-copyable-item">
													<span class="wc-copyable-label"><?php esc_html_e( 'Account Holder / Beneficiary:', 'wellness-consult' ); ?></span>
													<span class="wc-copy-target"><?php echo esc_html( $holder ); ?></span>
													<button type="button" class="wc-copy-btn" aria-label="<?php esc_attr_e( 'Copy Account Holder', 'wellness-consult' ); ?>"><?php esc_html_e( 'Copy', 'wellness-consult' ); ?></button>
												</div>
											<?php endif; ?>

											<?php if ( ! empty( $method['bank_name'] ) ) : ?>
												<div class="wc-copyable-item">
													<span class="wc-copyable-label"><?php esc_html_e( 'Bank Name:', 'wellness-consult' ); ?></span>
													<span class="wc-copy-target"><?php echo esc_html( $method['bank_name'] ); ?></span>
													<button type="button" class="wc-copy-btn" aria-label="<?php esc_attr_e( 'Copy Bank Name', 'wellness-consult' ); ?>"><?php esc_html_e( 'Copy', 'wellness-consult' ); ?></button>
												</div>
											<?php endif; ?>

											<?php if ( ! empty( $method['account_number'] ) ) : ?>
												<div class="wc-copyable-item">
													<span class="wc-copyable-label"><?php esc_html_e( 'IBAN or Account Number:', 'wellness-consult' ); ?></span>
													<code class="wc-copy-target"><?php echo esc_html( $method['account_number'] ); ?></code>
													<button type="button" class="wc-copy-btn" aria-label="<?php esc_attr_e( 'Copy Account Number', 'wellness-consult' ); ?>"><?php esc_html_e( 'Copy', 'wellness-consult' ); ?></button>
												</div>
											<?php endif; ?>

											<?php if ( ! empty( $method['swift_bic'] ) ) : ?>
												<div class="wc-copyable-item">
													<span class="wc-copyable-label"><?php esc_html_e( 'SWIFT / BIC Code:', 'wellness-consult' ); ?></span>
													<code class="wc-copy-target"><?php echo esc_html( $method['swift_bic'] ); ?></code>
													<button type="button" class="wc-copy-btn" aria-label="<?php esc_attr_e( 'Copy SWIFT Code', 'wellness-consult' ); ?>"><?php esc_html_e( 'Copy', 'wellness-consult' ); ?></button>
												</div>
											<?php endif; ?>

											<?php if ( ! empty( $method['bank_country'] ) ) : ?>
												<div class="wc-copyable-item">
													<span class="wc-copyable-label"><?php esc_html_e( 'Bank Country:', 'wellness-consult' ); ?></span>
													<span class="wc-copy-target"><?php echo esc_html( $method['bank_country'] ); ?></span>
													<button type="button" class="wc-copy-btn" aria-label="<?php esc_attr_e( 'Copy Bank Country', 'wellness-consult' ); ?>"><?php esc_html_e( 'Copy', 'wellness-consult' ); ?></button>
												</div>
											<?php endif; ?>

											<?php if ( ! empty( $method['currency'] ) ) : ?>
												<div class="wc-copyable-item">
													<span class="wc-copyable-label"><?php esc_html_e( 'Receiving Currency:', 'wellness-consult' ); ?></span>
													<span class="wc-copy-target"><?php echo esc_html( $method['currency'] ); ?></span>
													<button type="button" class="wc-copy-btn" aria-label="<?php esc_attr_e( 'Copy Currency', 'wellness-consult' ); ?>"><?php esc_html_e( 'Copy', 'wellness-consult' ); ?></button>
												</div>
											<?php endif; ?>
										</div>

									<!-- TYPE 2: Payoneer or Wise -->
									<?php elseif ( 'wise_payoneer' === $m_type ) : ?>
										<div class="wc-copyable-fields-list">
											<?php
											$id_val = $method['account_identifier'] ?? ( $method['account_title'] ?? '' );
											if ( ! empty( $id_val ) ) : ?>
												<div class="wc-copyable-item">
													<span class="wc-copyable-label"><?php esc_html_e( 'Account Email or Tag / Handle:', 'wellness-consult' ); ?></span>
													<code class="wc-copy-target"><?php echo esc_html( $id_val ); ?></code>
													<button type="button" class="wc-copy-btn" aria-label="<?php esc_attr_e( 'Copy Account Tag', 'wellness-consult' ); ?>"><?php esc_html_e( 'Copy', 'wellness-consult' ); ?></button>
												</div>
											<?php endif; ?>

											<?php if ( ! empty( $method['receiving_currency'] ) ) : ?>
												<div class="wc-copyable-item">
													<span class="wc-copyable-label"><?php esc_html_e( 'Receiving Currency:', 'wellness-consult' ); ?></span>
													<span class="wc-copy-target"><?php echo esc_html( $method['receiving_currency'] ); ?></span>
													<button type="button" class="wc-copy-btn" aria-label="<?php esc_attr_e( 'Copy Currency', 'wellness-consult' ); ?>"><?php esc_html_e( 'Copy', 'wellness-consult' ); ?></button>
												</div>
											<?php endif; ?>
										</div>

									<!-- TYPE 3: Payment Link -->
									<?php elseif ( 'payment_link' === $m_type ) : ?>
										<div class="wc-payment-link-container">
											<div class="wc-payment-link-action">
												<a href="<?php echo esc_url( $method['payment_url'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary wc-btn-payment-hosted">
													<span class="dashicons dashicons-external" style="vertical-align:text-top;font-size:16px;"></span>
													<?php echo esc_html( ! empty( $method['button_label'] ) ? $method['button_label'] : __( 'Open Hosted Payment Request', 'wellness-consult' ) ); ?> &rarr;
												</a>
											</div>
											<?php if ( ! empty( $method['payment_url'] ) ) : ?>
												<div class="wc-copyable-item" style="margin-top:12px;">
													<span class="wc-copyable-label"><?php esc_html_e( 'Payment Request URL:', 'wellness-consult' ); ?></span>
													<span class="wc-copy-target wc-link-url-text"><?php echo esc_html( $method['payment_url'] ); ?></span>
													<button type="button" class="wc-copy-btn" aria-label="<?php esc_attr_e( 'Copy URL', 'wellness-consult' ); ?>"><?php esc_html_e( 'Copy URL', 'wellness-consult' ); ?></button>
												</div>
											<?php endif; ?>
											<p class="wc-payment-link-instruction">
												<?php esc_html_e( 'Click the button above to pay securely on the hosted page. After completing payment, return to this tab to submit your booking with the receipt screenshot.', 'wellness-consult' ); ?>
											</p>
										</div>

									<!-- TYPE 4: Other / Instructions -->
									<?php else : ?>
										<?php if ( ! empty( $method['instructions'] ) ) : ?>
											<div class="wc-manual-instructions">
												<?php echo nl2br( esc_html( $method['instructions'] ) ); ?>
											</div>
										<?php endif; ?>
									<?php endif; ?>

									<!-- Method Specific Notes (if provided) -->
									<?php if ( ! empty( $method['notes'] ) && 'other' !== $m_type ) : ?>
										<div class="wc-method-custom-notes">
											<?php echo nl2br( esc_html( $method['notes'] ) ); ?>
										</div>
									<?php endif; ?>

									<!-- The 3 MANDATORY Notes (Requirement 2) -->
									<div class="wc-method-mandatory-notes">
										<div class="wc-mandatory-note-item">
											<span class="wc-note-bullet">📌</span>
											<div class="wc-note-content">
												<strong><?php esc_html_e( 'Use your Reference ID as the payment note:', 'wellness-consult' ); ?></strong>
												<span><?php esc_html_e( 'Your unique Reference ID is generated immediately upon submitting this booking form. You will also receive it via confirmation email, and you can add or verify your transaction reference anytime on the Track My Request page.', 'wellness-consult' ); ?></span>
											</div>
										</div>
										<div class="wc-mandatory-note-item">
											<span class="wc-note-bullet">⚖️</span>
											<div class="wc-note-content">
												<span><?php esc_html_e( 'The sender pays all bank and transfer fees; the amount received must be the full amount shown.', 'wellness-consult' ); ?></span>
											</div>
										</div>
										<div class="wc-mandatory-note-item">
											<span class="wc-note-bullet">🧾</span>
											<div class="wc-note-content">
												<strong><?php esc_html_e( 'Pay first, then upload the screenshot or receipt.', 'wellness-consult' ); ?></strong>
											</div>
										</div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>

						<!-- Stripe Online Option -->
						<?php if ( $has_stripe ) : ?>
							<div class="wc-payment-option-card wc-card-online" data-method-type="online" data-countries="" id="wc-method-card-stripe">
								<label class="wc-payment-option-label" for="method_stripe">
									<input type="radio" name="payment_method_id" id="method_stripe" value="stripe" data-kind="online" <?php checked( ( $old_data['payment_method_id'] ?? '' ) === 'stripe' ); ?> />
									<div class="wc-payment-option-title-row">
										<div class="wc-method-header-title">
											<strong><?php esc_html_e( 'Credit / Debit Card (Stripe Checkout)', 'wellness-consult' ); ?></strong>
											<span class="wc-badge-online"><?php esc_html_e( 'Instant Online Checkout', 'wellness-consult' ); ?></span>
										</div>
									</div>
								</label>
								<div class="wc-payment-details-drawer">
									<div class="wc-online-details-content">
										<p class="wc-online-desc">
											<?php esc_html_e( 'Fast, secure card checkout via Stripe. Accepts Visa, Mastercard, American Express, Apple Pay, and Google Pay. You will be redirected to complete payment directly on Stripe\'s encrypted checkout page.', 'wellness-consult' ); ?>
										</p>
										<div class="wc-security-reassurance">
											<span class="wc-note-bullet">🔒</span>
											<span><?php esc_html_e( 'Zero card details touch our website. Payment status confirms automatically upon completion.', 'wellness-consult' ); ?></span>
										</div>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<!-- PayPal Online Option -->
						<?php if ( $has_paypal ) : ?>
							<div class="wc-payment-option-card wc-card-online" data-method-type="online" data-countries="" id="wc-method-card-paypal">
								<label class="wc-payment-option-label" for="method_paypal">
									<input type="radio" name="payment_method_id" id="method_paypal" value="paypal" data-kind="online" <?php checked( ( $old_data['payment_method_id'] ?? '' ) === 'paypal' ); ?> />
									<div class="wc-payment-option-title-row">
										<div class="wc-method-header-title">
											<strong><?php esc_html_e( 'PayPal Secure Checkout', 'wellness-consult' ); ?></strong>
											<span class="wc-badge-online"><?php esc_html_e( 'Instant Online Checkout', 'wellness-consult' ); ?></span>
										</div>
									</div>
								</label>
								<div class="wc-payment-details-drawer">
									<div class="wc-online-details-content">
										<p class="wc-online-desc">
											<?php esc_html_e( 'Pay conveniently using your PayPal balance, connected bank account, or cards. You will be redirected to PayPal\'s hosted checkout to approve payment.', 'wellness-consult' ); ?>
										</p>
										<div class="wc-security-reassurance">
											<span class="wc-note-bullet">🔒</span>
											<span><?php esc_html_e( 'Zero payment credentials touch our website. Payment status confirms automatically upon return.', 'wellness-consult' ); ?></span>
										</div>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<div class="wc-step-buttons">
						<button type="button" class="button button-secondary wc-btn-back" data-back="1">
							&larr; <?php esc_html_e( 'Back to Packages', 'wellness-consult' ); ?>
						</button>
						<button type="button" class="button button-primary wc-btn-next" id="wc-btn-step-2-next" data-next="3">
							<?php esc_html_e( 'Continue to Patient Details', 'wellness-consult' ); ?> &rarr;
						</button>
					</div>
				</section>

				<!-- ========================================== -->
				<!-- STEP 3: Confidential Patient Details Form  -->
				<!-- ========================================== -->
				<section class="wc-step-section" id="wc-step-3" aria-labelledby="wc-step-3-title">
					<h2 id="wc-step-3-title" class="wc-step-heading" tabindex="-1">
						<?php esc_html_e( '3. Confidential Booking Details & Screenshot', 'wellness-consult' ); ?>
					</h2>
					<p class="wc-step-desc">
						<?php esc_html_e( 'Your information is handled privately and confidentially. Pseudonyms or aliases are welcome.', 'wellness-consult' ); ?>
					</p>

					<!-- Selected Summary Strip for Step 3 (CR-1) -->
					<div class="wc-fee-summary-strip wc-step3-summary" style="margin-bottom:20px;">
						<div class="wc-summary-col">
							<span class="wc-summary-label"><?php esc_html_e( 'Package:', 'wellness-consult' ); ?></span>
							<strong id="wc-step3-display-type">—</strong>
						</div>
						<div class="wc-summary-col">
							<span class="wc-summary-label"><?php esc_html_e( 'Mode:', 'wellness-consult' ); ?></span>
							<strong id="wc-step3-display-mode">—</strong>
						</div>
						<div class="wc-summary-col wc-summary-total">
							<span class="wc-summary-label"><?php esc_html_e( 'Amount to Transfer:', 'wellness-consult' ); ?></span>
							<strong class="wc-fee-strip-amount" id="wc-step3-display-fee">$0.00 USD</strong>
						</div>
					</div>

					<div class="wc-form-grid">
						<!-- Name or Alias -->
						<div class="wc-field-group">
							<label for="wc_field_name"><?php esc_html_e( 'Name or Alias *', 'wellness-consult' ); ?></label>
							<input type="text" id="wc_field_name" name="name" value="<?php echo esc_attr( $old_data['name'] ?? '' ); ?>" class="wc-input" placeholder="<?php esc_attr_e( 'Any alias or preferred name', 'wellness-consult' ); ?>" required autocomplete="name" />
							<span class="wc-help-text"><?php esc_html_e( 'Feel free to use any name you feel comfortable with.', 'wellness-consult' ); ?></span>
						</div>

						<!-- Age (18+ only) -->
						<div class="wc-field-group">
							<label for="wc_field_age"><?php esc_html_e( 'Age * (18+ only)', 'wellness-consult' ); ?></label>
							<input type="number" id="wc_field_age" name="age" min="18" max="120" value="<?php echo esc_attr( $old_data['age'] ?? '' ); ?>" class="wc-input" placeholder="e.g. 28" required />
							<span class="wc-help-text" id="wc-age-warning" style="display:none;color:#dc2626;font-weight:600;">
								<?php esc_html_e( 'Consultations are strictly for adults aged 18 and older.', 'wellness-consult' ); ?>
							</span>
						</div>

						<!-- Gender -->
						<div class="wc-field-group">
							<label for="wc_field_gender"><?php esc_html_e( 'Gender *', 'wellness-consult' ); ?></label>
							<select id="wc_field_gender" name="gender" class="wc-select" required>
								<option value=""><?php esc_html_e( '— Select Gender —', 'wellness-consult' ); ?></option>
								<option value="female" <?php selected( $old_data['gender'] ?? '', 'female' ); ?>><?php esc_html_e( 'Female', 'wellness-consult' ); ?></option>
								<option value="male" <?php selected( $old_data['gender'] ?? '', 'male' ); ?>><?php esc_html_e( 'Male', 'wellness-consult' ); ?></option>
								<option value="non_binary" <?php selected( $old_data['gender'] ?? '', 'non_binary' ); ?>><?php esc_html_e( 'Non-binary', 'wellness-consult' ); ?></option>
								<option value="prefer_not_to_say" <?php selected( $old_data['gender'] ?? '', 'prefer_not_to_say' ); ?>><?php esc_html_e( 'Prefer not to say', 'wellness-consult' ); ?></option>
								<option value="other" <?php selected( $old_data['gender'] ?? '', 'other' ); ?>><?php esc_html_e( 'Other', 'wellness-consult' ); ?></option>
							</select>
						</div>

						<!-- Country -->
						<div class="wc-field-group">
							<label for="wc_field_country"><?php esc_html_e( 'Country of Residence *', 'wellness-consult' ); ?></label>
							<select id="wc_field_country" name="country" class="wc-select" required>
								<option value=""><?php esc_html_e( '— Select Country —', 'wellness-consult' ); ?></option>
								<?php foreach ( $country_options as $iso_code => $c_data ) : ?>
									<option value="<?php echo esc_attr( $iso_code ); ?>" data-blocked="<?php echo $c_data['is_blocked'] ? '1' : '0'; ?>" <?php selected( $old_data['country'] ?? '', $iso_code ); ?>>
										<?php echo esc_html( $c_data['name'] . ' (' . $iso_code . ')' ); ?>
										<?php echo $c_data['is_blocked'] ? ' — ' . esc_html__( 'Unavailable', 'wellness-consult' ) : ''; ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div class="wc-country-blocked-alert" id="wc-country-blocked-alert" style="display:none;background:#fef2f2;border:1px solid #fecaca;padding:8px 12px;border-radius:4px;margin-top:6px;color:#991b1b;font-size:13px;">
								<?php esc_html_e( 'Notice: Consultations are currently unavailable in this country due to medical licensing compliance.', 'wellness-consult' ); ?>
							</div>
						</div>

						<!-- Timezone -->
						<div class="wc-field-group">
							<label for="wc_field_timezone"><?php esc_html_e( 'Time Zone * (Auto-detected)', 'wellness-consult' ); ?></label>
							<select id="wc_field_timezone" name="timezone" class="wc-select" required>
								<?php foreach ( $timezones as $tz ) : ?>
									<option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $old_data['timezone'] ?? 'UTC', $tz ); ?>>
										<?php echo esc_html( $tz ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="wc-help-text"><?php esc_html_e( 'Your appointment time will be scheduled and displayed in this time zone.', 'wellness-consult' ); ?></span>
						</div>

						<!-- Contact Phone / WhatsApp -->
						<div class="wc-field-group">
							<label for="wc_field_phone"><?php esc_html_e( 'WhatsApp / Phone Number (with Country Code)', 'wellness-consult' ); ?></label>
							<input type="tel" id="wc_field_phone" name="phone" value="<?php echo esc_attr( $old_data['phone'] ?? '' ); ?>" class="wc-input" placeholder="+1 555 123 4567" />
							<span class="wc-help-text"><?php esc_html_e( 'Provide WhatsApp or Email (at least one contact is required).', 'wellness-consult' ); ?></span>
						</div>

						<!-- Contact Email -->
						<div class="wc-field-group">
							<label for="wc_field_email"><?php esc_html_e( 'Email Address (Needed for Calendar Reminders)', 'wellness-consult' ); ?></label>
							<input type="email" id="wc_field_email" name="email" value="<?php echo esc_attr( $old_data['email'] ?? '' ); ?>" class="wc-input" placeholder="you@example.com" />
							<span class="wc-help-text"><?php esc_html_e( 'Used to email your Reference ID, meeting invitation link, and reminders.', 'wellness-consult' ); ?></span>
						</div>

						<!-- Concern Category -->
						<div class="wc-field-group">
							<label for="wc_field_concern"><?php esc_html_e( 'Primary Concern Category *', 'wellness-consult' ); ?></label>
							<select id="wc_field_concern" name="concern" class="wc-select" required>
								<option value=""><?php esc_html_e( '— Select Primary Category —', 'wellness-consult' ); ?></option>
								<option value="intimacy" <?php selected( $old_data['concern'] ?? '', 'intimacy' ); ?>><?php esc_html_e( 'Intimacy & Relationship Wellness', 'wellness-consult' ); ?></option>
								<option value="mens_health" <?php selected( $old_data['concern'] ?? '', 'mens_health' ); ?>><?php esc_html_e( 'Men’s Sexual Health', 'wellness-consult' ); ?></option>
								<option value="womens_health" <?php selected( $old_data['concern'] ?? '', 'womens_health' ); ?>><?php esc_html_e( 'Women’s Sexual Health', 'wellness-consult' ); ?></option>
								<option value="myths_vs_facts" <?php selected( $old_data['concern'] ?? '', 'myths_vs_facts' ); ?>><?php esc_html_e( 'Anxiety, Myths & Misconceptions', 'wellness-consult' ); ?></option>
								<option value="lifestyle" <?php selected( $old_data['concern'] ?? '', 'lifestyle' ); ?>><?php esc_html_e( 'Lifestyle & Reproductive Wellness', 'wellness-consult' ); ?></option>
								<option value="other" <?php selected( $old_data['concern'] ?? '', 'other' ); ?>><?php esc_html_e( 'Other / General Inquiry', 'wellness-consult' ); ?></option>
							</select>
						</div>

						<!-- Preferred Time Window -->
						<div class="wc-field-group wc-span-full">
							<label><?php esc_html_e( 'Preferred Time Window * (in your local time)', 'wellness-consult' ); ?></label>
							<div class="wc-window-chips">
								<label class="wc-chip">
									<input type="radio" name="preferred_window" value="morning" <?php checked( $old_data['preferred_window'] ?? 'morning', 'morning' ); ?> required />
									<span><?php esc_html_e( 'Morning (08:00 - 12:00)', 'wellness-consult' ); ?></span>
								</label>
								<label class="wc-chip">
									<input type="radio" name="preferred_window" value="afternoon" <?php checked( $old_data['preferred_window'] ?? '', 'afternoon' ); ?> />
									<span><?php esc_html_e( 'Afternoon (12:00 - 17:00)', 'wellness-consult' ); ?></span>
								</label>
								<label class="wc-chip">
									<input type="radio" name="preferred_window" value="evening" <?php checked( $old_data['preferred_window'] ?? '', 'evening' ); ?> />
									<span><?php esc_html_e( 'Evening (17:00 - 21:00)', 'wellness-consult' ); ?></span>
								</label>
								<label class="wc-chip">
									<input type="radio" name="preferred_window" value="night" <?php checked( $old_data['preferred_window'] ?? '', 'night' ); ?> />
									<span><?php esc_html_e( 'Night (21:00 - 24:00)', 'wellness-consult' ); ?></span>
								</label>
							</div>
						</div>

						<!-- Confidential Message -->
						<div class="wc-field-group wc-span-full">
							<div class="wc-label-with-counter">
								<label for="wc_field_message"><?php esc_html_e( 'Confidential Note / Clinical Background (Optional)', 'wellness-consult' ); ?></label>
								<span class="wc-char-counter" id="wc-message-counter">0 / 1000</span>
							</div>
							<textarea id="wc_field_message" name="message" rows="4" maxlength="1000" class="wc-textarea" placeholder="<?php esc_attr_e( 'Briefly share what you would like to discuss with the consultant...', 'wellness-consult' ); ?>"><?php echo esc_textarea( $old_data['message'] ?? '' ); ?></textarea>
						</div>

						<!-- Optional Transaction ID or sender name for manual transfers (CR-2) -->
						<div class="wc-field-group wc-span-full" id="wc-trans-id-container">
							<label for="wc_field_transaction_id"><?php esc_html_e( 'Transaction ID or sender name (Optional)', 'wellness-consult' ); ?></label>
							<input type="text" id="wc_field_transaction_id" name="transaction_id" value="<?php echo esc_attr( $old_data['transaction_id'] ?? '' ); ?>" class="wc-input" placeholder="<?php esc_attr_e( 'e.g. Wire MT103 reference, Wise transaction ID, or sender account name', 'wellness-consult' ); ?>" />
							<span class="wc-help-text"><?php esc_html_e( 'Helps our accounting team match your transfer faster. If you do not have a transaction code yet, enter the sender account name.', 'wellness-consult' ); ?></span>
						</div>

						<!-- SCREENSHOT UPLOAD SECTION (Rule 2) -->
						<div class="wc-field-group wc-span-full" id="wc-screenshot-section">
							<label class="wc-upload-heading">
								<?php esc_html_e( 'Payment Proof Screenshot * (Required for manual transfer)', 'wellness-consult' ); ?>
							</label>
							<p class="wc-upload-subtext">
								<?php esc_html_e( 'Upload the transaction receipt or screenshot from your banking or wallet app. Accepted formats: JPG, PNG, PDF (Max 5 MB).', 'wellness-consult' ); ?>
							</p>

							<div class="wc-dropzone-box" id="wc-dropzone">
								<input type="file" id="wc_payment_proof" name="payment_proof" accept="image/jpeg,image/png,application/pdf" class="wc-file-input" />
								
								<div class="wc-dropzone-content" id="wc-dropzone-prompt">
									<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="wc-dropzone-icon">
										<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
										<polyline points="17 8 12 3 7 8"></polyline>
										<line x1="12" y1="3" x2="12" y2="15"></line>
									</svg>
									<div class="wc-dropzone-text">
										<strong><?php esc_html_e( 'Click to browse or drag & drop screenshot here', 'wellness-consult' ); ?></strong>
										<span><?php esc_html_e( 'JPG, PNG, or PDF up to 5 MB', 'wellness-consult' ); ?></span>
									</div>
								</div>

								<!-- Live Preview Area -->
								<div class="wc-dropzone-preview" id="wc-dropzone-preview" style="display:none;">
									<div class="wc-preview-thumb-wrap">
										<img id="wc-preview-img" src="" alt="<?php esc_attr_e( 'Proof preview', 'wellness-consult' ); ?>" style="display:none;" />
										<div id="wc-preview-pdf" style="display:none;" class="wc-pdf-preview-box">
											<span class="dashicons dashicons-media-document" style="font-size:32px;width:32px;height:32px;color:#dc2626;"></span>
											<span id="wc-pdf-name" class="wc-pdf-filename">document.pdf</span>
										</div>
									</div>
									<div class="wc-preview-meta">
										<div class="wc-preview-name" id="wc-file-display-name"></div>
										<div class="wc-preview-size" id="wc-file-display-size"></div>
										<div class="wc-preview-actions">
											<button type="button" class="button button-small" id="wc-replace-file-btn"><?php esc_html_e( 'Replace File', 'wellness-consult' ); ?></button>
											<button type="button" class="button button-small button-link-delete" id="wc-remove-file-btn"><?php esc_html_e( 'Remove', 'wellness-consult' ); ?></button>
										</div>
									</div>
								</div>
							</div>
							<div class="wc-upload-error" id="wc-upload-client-error" style="display:none;"></div>
						</div>

						<!-- Consent Checkbox (Rule 1 & FR-14) -->
						<div class="wc-field-group wc-span-full wc-consent-group">
							<label class="wc-checkbox-label">
								<input type="checkbox" name="consent_agreed" value="1" required />
								<span>
									<?php
									printf(
										/* translators: 1: Terms link, 2: Privacy Policy link, 3: Refund Policy link */
										esc_html__( 'I confirm I am 18 years of age or older and agree to the %1$s, %2$s, and %3$s, and give explicit consent to process health-related wellness consultation data.', 'wellness-consult' ),
										self::get_policy_link( 'terms', 'Terms of Service' ),
										self::get_policy_link( 'privacy-policy', 'Privacy Policy' ),
										self::get_policy_link( 'refund-policy', 'Refund & Reschedule Policy' )
									);
									?>
								</span>
							</label>
						</div>

						<!-- Cloudflare Turnstile Placeholder (If configured) -->
						<?php if ( ! empty( $payment_settings['turnstile_site_key'] ) ) : ?>
							<div class="wc-field-group wc-span-full">
								<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $payment_settings['turnstile_site_key'] ); ?>"></div>
							</div>
						<?php endif; ?>
					</div>

					<div class="wc-step-buttons">
						<button type="button" class="button button-secondary wc-btn-back" data-back="2">
							&larr; <?php esc_html_e( 'Back to Payment', 'wellness-consult' ); ?>
						</button>
						<button type="submit" class="button button-primary wc-btn-submit" id="wc-submit-booking-btn">
							<?php esc_html_e( 'Submit Consultation Request', 'wellness-consult' ); ?> &rarr;
						</button>
					</div>
				</section>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Single-Use Confirmation Screen (POST-Redirect-GET)
	 *
	 * @param string $token
	 * @return string HTML output.
	 */
	protected static function render_confirmation_screen( $token ) {
		$token_data = get_transient( 'wc_confirm_' . $token );

		if ( ! $token_data || ! is_array( $token_data ) ) {
			return sprintf(
				'<div class="wc-confirmation-expired-card"><div class="notice notice-warning inline"><p><strong>%s</strong> %s <a href="%s">%s</a>.</p></div></div>',
				esc_html__( 'Confirmation Link Expired:', 'wellness-consult' ),
				esc_html__( 'This single-use confirmation token has expired or has already been used. You can look up your request anytime on the', 'wellness-consult' ),
				esc_url( home_url( '/track-my-request/' ) ),
				esc_html__( 'Track My Request page', 'wellness-consult' )
			);
		}

		// Single-use token: purge immediately after reading
		delete_transient( 'wc_confirm_' . $token );

		$ref_id       = esc_html( $token_data['reference_id'] ?? '' );
		$status_label = esc_html( WC_Status::get_label( $token_data['status'] ?? 'pending_verification' ) );
		$fee          = esc_html( '$' . number_format( (float) ( $token_data['fee_usd'] ?? 0 ), 2 ) . ' USD' );
		$name         = esc_html( $token_data['name'] ?? 'Patient' );
		$consult_mode = $token_data['consult_mode'] ?? '';
		$mode_label   = WC_Options::get_mode_label( $consult_mode );
		$mode_icon    = WC_Options::get_mode_icon( $consult_mode );
		$track_url    = esc_url( home_url( '/track-my-request/?ref=' . urlencode( $ref_id ) ) );

		ob_start();
		?>
		<div class="wc-confirmation-card">
			<div class="wc-confirmation-icon-circle">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="20 6 9 17 4 12"></polyline>
				</svg>
			</div>

			<h2 class="wc-confirm-title"><?php esc_html_e( 'Consultation Request Received!', 'wellness-consult' ); ?></h2>
			<p class="wc-confirm-intro">
				<?php printf( esc_html__( 'Thank you, %s. Your private consultation request has been successfully registered.', 'wellness-consult' ), '<strong>' . $name . '</strong>' ); ?>
			</p>

			<div class="wc-ref-highlight-box">
				<span class="wc-ref-label"><?php esc_html_e( 'Your Reference ID:', 'wellness-consult' ); ?></span>
				<div class="wc-ref-value-row">
					<span class="wc-ref-code" id="wc-ref-code-value"><?php echo $ref_id; ?></span>
					<button type="button" class="button button-secondary button-small" id="wc-copy-ref-btn" onclick="wcCopyRefCode()">
						<span class="dashicons dashicons-admin-page" style="vertical-align:text-top;font-size:14px;"></span>
						<span id="wc-copy-ref-text"><?php esc_html_e( 'Copy ID', 'wellness-consult' ); ?></span>
					</button>
				</div>
				<span class="wc-ref-help"><?php esc_html_e( 'Save this Reference ID. You will need it to track your appointment details.', 'wellness-consult' ); ?></span>
			</div>

			<div class="wc-confirm-status-strip">
				<div>
					<strong><?php esc_html_e( 'Current Status:', 'wellness-consult' ); ?></strong>
					<span class="wc-status-pill"><?php echo $status_label; ?></span>
				</div>
				<?php if ( ! empty( $consult_mode ) ) : ?>
					<div>
						<strong><?php esc_html_e( 'Mode:', 'wellness-consult' ); ?></strong>
						<span><strong><?php echo esc_html( $mode_icon . ' ' . $mode_label ); ?></strong></span>
					</div>
				<?php endif; ?>
				<div>
					<strong><?php esc_html_e( 'Fee:', 'wellness-consult' ); ?></strong>
					<span><?php echo $fee; ?></span>
				</div>
			</div>

			<div class="wc-next-steps-card">
				<h3><?php esc_html_e( 'What Happens Next?', 'wellness-consult' ); ?></h3>
				<ol class="wc-next-steps-list">
					<li>
						<strong><?php esc_html_e( 'Payment Verification', 'wellness-consult' ); ?></strong>
						<p><?php esc_html_e( 'Our clinical administration team reviews your transfer screenshot. Verification usually takes 1 to 4 hours.', 'wellness-consult' ); ?></p>
					</li>
					<li>
						<strong><?php esc_html_e( 'Appointment Scheduling', 'wellness-consult' ); ?></strong>
						<p><?php esc_html_e( 'Once payment is verified, a consultant is assigned and your session time is confirmed in your local time zone.', 'wellness-consult' ); ?></p>
					</li>
					<li>
						<strong><?php esc_html_e( 'Consultation Meeting Link', 'wellness-consult' ); ?></strong>
						<p><?php esc_html_e( 'You will receive your meeting link (Google Meet / Zoom / WhatsApp) and 24-hour and 1-hour reminders.', 'wellness-consult' ); ?></p>
					</li>
				</ol>
			</div>

			<div class="wc-confirm-actions">
				<a href="<?php echo $track_url; ?>" class="button button-primary wc-btn-track">
					<?php esc_html_e( 'Track My Request Status', 'wellness-consult' ); ?> &rarr;
				</a>
			</div>
		</div>

		<script>
		function wcCopyRefCode() {
			var code = document.getElementById('wc-ref-code-value').textContent.trim();
			var btnText = document.getElementById('wc-copy-ref-text');
			if (navigator.clipboard) {
				navigator.clipboard.writeText(code).then(function() {
					btnText.textContent = 'Copied!';
					setTimeout(function(){ btnText.textContent = 'Copy ID'; }, 2500);
				});
			} else {
				var dummy = document.createElement('textarea');
				document.body.appendChild(dummy);
				dummy.value = code;
				dummy.select();
				document.execCommand('copy');
				document.body.removeChild(dummy);
				btnText.textContent = 'Copied!';
				setTimeout(function(){ btnText.textContent = 'Copy ID'; }, 2500);
			}
		}
		</script>
		<?php
		return ob_get_clean();
	}
}
