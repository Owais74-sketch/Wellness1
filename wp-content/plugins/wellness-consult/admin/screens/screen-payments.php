<?php
/**
 * Admin Screen: Payments Configuration
 *
 * Handles Stripe & PayPal gateways and manual transfer accounts.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_manage_payments' ) ) {
	wp_die( esc_html__( 'You do not have permission to access payment settings.', 'wellness-consult' ) );
}

$notice = '';
$notice_type = 'success';

// Handle Form Submissions
if ( isset( $_POST['wc_payments_save_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wc_payments_save_nonce'] ), 'wc_save_payments_action' ) ) {
	$current_settings = get_option( 'wc_payment_settings', array() );

	// Gateway Settings
	$test_mode        = isset( $_POST['test_mode'] ) ? '1' : '0';
	$stripe_enabled   = isset( $_POST['stripe_enabled'] ) ? '1' : '0';
	$paypal_enabled   = isset( $_POST['paypal_enabled'] ) ? '1' : '0';

	// Keys update: only overwrite if non-empty submitted; encrypt secrets with AES-256 (Requirement 7)
	$stripe_test_pk   = ! empty( $_POST['stripe_test_pk'] ) ? sanitize_text_field( wp_unslash( $_POST['stripe_test_pk'] ) ) : ( $current_settings['stripe_test_pk'] ?? '' );
	$stripe_test_sk   = ! empty( $_POST['stripe_test_sk'] ) ? WC_Encryption::encrypt( sanitize_text_field( wp_unslash( $_POST['stripe_test_sk'] ) ) ) : ( $current_settings['stripe_test_sk'] ?? '' );
	$stripe_live_pk   = ! empty( $_POST['stripe_live_pk'] ) ? sanitize_text_field( wp_unslash( $_POST['stripe_live_pk'] ) ) : ( $current_settings['stripe_live_pk'] ?? '' );
	$stripe_live_sk   = ! empty( $_POST['stripe_live_sk'] ) ? WC_Encryption::encrypt( sanitize_text_field( wp_unslash( $_POST['stripe_live_sk'] ) ) ) : ( $current_settings['stripe_live_sk'] ?? '' );
	$stripe_webhook   = ! empty( $_POST['stripe_webhook_secret'] ) ? WC_Encryption::encrypt( sanitize_text_field( wp_unslash( $_POST['stripe_webhook_secret'] ) ) ) : ( $current_settings['stripe_webhook_secret'] ?? '' );

	$paypal_client_id = ! empty( $_POST['paypal_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['paypal_client_id'] ) ) : ( $current_settings['paypal_client_id'] ?? '' );
	$paypal_secret    = ! empty( $_POST['paypal_secret'] ) ? WC_Encryption::encrypt( sanitize_text_field( wp_unslash( $_POST['paypal_secret'] ) ) ) : ( $current_settings['paypal_secret'] ?? '' );
	$paypal_webhook   = ! empty( $_POST['paypal_webhook_id'] ) ? WC_Encryption::encrypt( sanitize_text_field( wp_unslash( $_POST['paypal_webhook_id'] ) ) ) : ( $current_settings['paypal_webhook_id'] ?? '' );

	$new_settings = array(
		'test_mode'             => $test_mode,
		'stripe_enabled'        => $stripe_enabled,
		'stripe_test_pk'        => $stripe_test_pk,
		'stripe_test_sk'        => $stripe_test_sk,
		'stripe_live_pk'        => $stripe_live_pk,
		'stripe_live_sk'        => $stripe_live_sk,
		'stripe_webhook_secret' => $stripe_webhook,
		'paypal_enabled'        => $paypal_enabled,
		'paypal_client_id'      => $paypal_client_id,
		'paypal_secret'         => $paypal_secret,
		'paypal_webhook_id'     => $paypal_webhook,
	);

	update_option( 'wc_payment_settings', $new_settings );
	$notice = esc_html__( 'Payment gateway settings updated successfully.', 'wellness-consult' );

	WC_Database::log( array(
		'action' => 'update_payment_settings',
		'note'   => sprintf( 'Gateway settings updated. Test mode: %s.', '1' === $test_mode ? 'ON' : 'OFF' ),
	) );
}

// Handle Manual Transfer CRUD
$manual_methods = get_option( 'wc_manual_methods', array() );
if ( ! is_array( $manual_methods ) ) {
	$manual_methods = array();
}

$edit_id     = isset( $_GET['action'] ) && 'edit_method' === $_GET['action'] && ! empty( $_GET['method_id'] ) ? sanitize_key( $_GET['method_id'] ) : '';
$edit_method = ( $edit_id && isset( $manual_methods[ $edit_id ] ) ) ? $manual_methods[ $edit_id ] : null;

// Add or edit manual method
if ( isset( $_POST['wc_manual_save_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wc_manual_save_nonce'] ), 'wc_save_manual_action' ) ) {
	$method_id       = ! empty( $_POST['method_id'] ) ? sanitize_key( $_POST['method_id'] ) : 'mm_' . wp_generate_password( 8, false );
	$name            = sanitize_text_field( wp_unslash( $_POST['method_name'] ?? '' ) );
	$method_type     = sanitize_key( wp_unslash( $_POST['method_type'] ?? '' ) );
	$active          = isset( $_POST['active'] ) ? '1' : '0';
	$is_sample       = isset( $_POST['is_sample'] ) && '1' === $_POST['is_sample'] ? '1' : '0';
	$countries       = isset( $_POST['allowed_countries'] ) && is_array( $_POST['allowed_countries'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['allowed_countries'] ) ) : array();

	// Type specific fields
	$account_holder     = sanitize_text_field( wp_unslash( $_POST['account_holder'] ?? '' ) );
	$bank_name          = sanitize_text_field( wp_unslash( $_POST['bank_name'] ?? '' ) );
	$account_number     = sanitize_text_field( wp_unslash( $_POST['account_number'] ?? '' ) );
	$swift_bic          = sanitize_text_field( wp_unslash( $_POST['swift_bic'] ?? '' ) );
	$bank_country       = sanitize_text_field( wp_unslash( $_POST['bank_country'] ?? '' ) );
	$currency           = sanitize_text_field( wp_unslash( $_POST['currency'] ?? '' ) );
	$notes              = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
	$account_identifier = sanitize_text_field( wp_unslash( $_POST['account_identifier'] ?? '' ) );
	$receiving_currency = sanitize_text_field( wp_unslash( $_POST['receiving_currency'] ?? '' ) );
	$payment_url        = esc_url_raw( wp_unslash( $_POST['payment_url'] ?? '' ) );
	$button_label       = sanitize_text_field( wp_unslash( $_POST['button_label'] ?? '' ) );
	$instructions       = sanitize_textarea_field( wp_unslash( $_POST['instructions'] ?? '' ) );

	$has_errors = false;

	if ( empty( $name ) ) {
		$notice = esc_html__( 'Method display name is required.', 'wellness-consult' );
		$notice_type = 'error';
		$has_errors = true;
	} elseif ( ! isset( WC_Options::get_manual_payment_types()[ $method_type ] ) ) {
		$notice = esc_html__( 'Please select a valid payment method type.', 'wellness-consult' );
		$notice_type = 'error';
		$has_errors = true;
	} elseif ( 'bank' === $method_type && ( empty( $account_holder ) || empty( $account_number ) ) ) {
		$notice = esc_html__( 'For Bank Transfers: Account holder and IBAN / Account Number are required.', 'wellness-consult' );
		$notice_type = 'error';
		$has_errors = true;
	} elseif ( 'wise_payoneer' === $method_type && empty( $account_identifier ) ) {
		$notice = esc_html__( 'For Payoneer / Wise: Account email or tag is required.', 'wellness-consult' );
		$notice_type = 'error';
		$has_errors = true;
	} elseif ( 'payment_link' === $method_type && ( empty( $payment_url ) || empty( $button_label ) ) ) {
		$notice = esc_html__( 'For Payment Links: Hosted payment URL and button label are required.', 'wellness-consult' );
		$notice_type = 'error';
		$has_errors = true;
	} elseif ( 'other' === $method_type && empty( $instructions ) ) {
		$notice = esc_html__( 'For Other payment methods: Transfer instructions are required.', 'wellness-consult' );
		$notice_type = 'error';
		$has_errors = true;
	}

	if ( ! $has_errors ) {
		$manual_methods[ $method_id ] = array(
			'id'                 => $method_id,
			'name'               => $name,
			'method_type'        => $method_type,
			'active'             => $active,
			'is_sample'          => $is_sample,
			'allowed_countries'  => $countries,
			// Bank transfer fields
			'account_holder'     => $account_holder,
			'bank_name'          => $bank_name,
			'account_number'     => $account_number,
			'swift_bic'          => $swift_bic,
			'bank_country'       => $bank_country,
			'currency'           => $currency,
			// Wise / Payoneer fields
			'account_identifier' => $account_identifier,
			'receiving_currency' => $receiving_currency,
			// Payment link fields
			'payment_url'        => $payment_url,
			'button_label'       => $button_label,
			// Shared notes & instructions
			'notes'              => $notes,
			'instructions'       => $instructions ? $instructions : $notes,
			// Backward compatibility aliases
			'account_title'      => $account_holder ? $account_holder : ( $account_identifier ? $account_identifier : $name ),
		);

		update_option( 'wc_manual_methods', $manual_methods );
		$notice = esc_html__( 'Manual payment method saved successfully.', 'wellness-consult' );
		$notice_type = 'success';

		WC_Database::log( array(
			'action' => 'save_manual_method',
			'note'   => sprintf( 'Manual method "%s" (%s, type: %s) saved.', $name, $method_id, $method_type ),
		) );

		// Reset edit state
		$edit_id     = '';
		$edit_method = null;
	}
}

// Delete manual method
if ( isset( $_GET['action'] ) && 'delete_method' === $_GET['action'] && ! empty( $_GET['method_id'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wc_delete_method_' . $_GET['method_id'] ) ) {
		$del_id = sanitize_key( $_GET['method_id'] );
		if ( isset( $manual_methods[ $del_id ] ) ) {
			$del_name = $manual_methods[ $del_id ]['name'];
			unset( $manual_methods[ $del_id ] );
			update_option( 'wc_manual_methods', $manual_methods );
			$notice = sprintf( esc_html__( 'Method "%s" has been removed.', 'wellness-consult' ), esc_html( $del_name ) );
			$notice_type = 'success';

			WC_Database::log( array(
				'action' => 'delete_manual_method',
				'note'   => sprintf( 'Manual method "%s" (%s) removed.', $del_name, $del_id ),
			) );
		}
	}
}

// Add Sample International Methods Handler (Clearly marked SAMPLE, inactive by default per Rule 6)
if ( isset( $_GET['action'] ) && 'add_sample_method' === $_GET['action'] && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wc_add_sample_method' ) ) {
		$sample_bank_id = 'sample_bank_' . wp_generate_password( 4, false );
		$manual_methods[ $sample_bank_id ] = array(
			'id'                => $sample_bank_id,
			'name'              => '[SAMPLE] International Bank Wire (SWIFT / IBAN)',
			'method_type'       => 'bank',
			'account_holder'    => 'SAMPLE MEDICAL HEALTHCARE LLC',
			'bank_name'         => 'SAMPLE INTERNATIONAL BANK NA',
			'account_number'    => 'US12SMPL00001234567890',
			'swift_bic'         => 'SMPLUS33',
			'bank_country'      => 'United States',
			'currency'          => 'USD',
			'notes'             => "Send the exact fee in USD. Sender covers all intermediary bank wire charges.\nInclude your Reference ID in the transfer payment note.",
			'instructions'      => "Send exact fee in USD. Sender covers all intermediary bank wire charges.",
			'account_title'     => 'SAMPLE MEDICAL HEALTHCARE LLC',
			'active'            => '0',
			'is_sample'         => '1',
			'allowed_countries' => array(),
		);

		$sample_wise_id = 'sample_wise_' . wp_generate_password( 4, false );
		$manual_methods[ $sample_wise_id ] = array(
			'id'                 => $sample_wise_id,
			'name'               => '[SAMPLE] Payoneer or Wise Direct',
			'method_type'        => 'wise_payoneer',
			'account_identifier' => 'sample-billing@wellness-consult.test',
			'receiving_currency' => 'USD',
			'notes'              => "Send payment to our Wise / Payoneer email tag.\nPlease ensure full amount is received.",
			'instructions'       => "Send payment to our Wise / Payoneer email tag.",
			'account_title'      => 'sample-billing@wellness-consult.test',
			'account_number'     => 'sample-billing@wellness-consult.test',
			'active'             => '0',
			'is_sample'          => '1',
			'allowed_countries'  => array(),
		);

		$sample_link_id = 'sample_link_' . wp_generate_password( 4, false );
		$manual_methods[ $sample_link_id ] = array(
			'id'                => $sample_link_id,
			'name'              => '[SAMPLE] Hosted Payment Link (Wise / Payoneer)',
			'method_type'       => 'payment_link',
			'payment_url'       => 'https://wise.com/pay/me/samplewellness',
			'button_label'      => 'Open Wise Payment Request',
			'notes'             => "Click the link to complete payment securely on the Wise hosted page. Return to this form and upload your payment confirmation receipt.",
			'instructions'      => "Pay on the hosted payment page, then upload screenshot here.",
			'account_title'     => 'Open Wise Payment Request',
			'account_number'    => 'https://wise.com/pay/me/samplewellness',
			'active'            => '0',
			'is_sample'         => '1',
			'allowed_countries' => array(),
		);

		$sample_uk_id = 'sample_uk_' . wp_generate_password( 4, false );
		$manual_methods[ $sample_uk_id ] = array(
			'id'                => $sample_uk_id,
			'name'              => '[SAMPLE] UK Faster Payments (UK Patients Only)',
			'method_type'       => 'bank',
			'account_holder'    => 'SAMPLE UK WELLNESS LTD',
			'bank_name'         => 'SAMPLE UK BANK PLC',
			'account_number'    => 'GB29SMPL20000012345678',
			'swift_bic'         => 'SMPLGB22',
			'bank_country'      => 'United Kingdom',
			'currency'          => 'GBP',
			'notes'             => "Available only for UK bank accounts. Use your Reference ID as payment reference.",
			'instructions'      => "Available only for UK bank accounts.",
			'account_title'     => 'SAMPLE UK WELLNESS LTD',
			'active'            => '0',
			'is_sample'         => '1',
			'allowed_countries' => array( 'GB' ),
		);

		update_option( 'wc_manual_methods', $manual_methods );
		$notice = esc_html__( 'Sample international methods added (all inactive by default and marked SAMPLE).', 'wellness-consult' );
		$notice_type = 'success';
	}
}

// Remove All Samples Handler
if ( isset( $_GET['action'] ) && 'remove_sample_methods' === $_GET['action'] && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wc_remove_sample_methods' ) ) {
		$removed_count = 0;
		foreach ( $manual_methods as $k => $m ) {
			if ( ! empty( $m['is_sample'] ) ) {
				unset( $manual_methods[ $k ] );
				$removed_count++;
			}
		}
		update_option( 'wc_manual_methods', $manual_methods );
		$notice = sprintf( esc_html__( 'Removed %d sample transfer method(s).', 'wellness-consult' ), $removed_count );
		$notice_type = 'success';
	}
}

$settings      = get_option( 'wc_payment_settings', array() );
$is_test_mode  = ! empty( $settings['test_mode'] ) && '1' === $settings['test_mode'];
$all_countries = WC_Options::get_all_iso_countries();

// Helper to mask secret strings safely without echoing secrets in HTML (Requirement 7)
function wc_mask_secret( $val ) {
	return class_exists( 'WC_Encryption' ) ? WC_Encryption::mask_secret( $val ) : '';
}
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Payment Settings', 'wellness-consult' ); ?></h1>

	<?php if ( $is_test_mode ) : ?>
		<span class="wc-badge-test-mode" style="background:#d97706;color:#ffffff;padding:4px 10px;border-radius:4px;font-weight:700;font-size:12px;margin-left:12px;display:inline-block;vertical-align:middle;">
			<span class="dashicons dashicons-warning" style="font-size:16px;line-height:1.2;vertical-align:text-top;"></span> <?php esc_html_e( 'TEST MODE ACTIVE', 'wellness-consult' ); ?>
		</span>
	<?php endif; ?>

	<hr class="wp-header-end">

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Section 1: Gateways -->
	<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:20px;margin-top:20px;border-radius:4px;">
		<form method="post" action="">
			<?php wp_nonce_field( 'wc_save_payments_action', 'wc_payments_save_nonce' ); ?>

			<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-shield-alt"></span>
				<?php esc_html_e( 'Online Payment Gateways (Stripe & PayPal)', 'wellness-consult' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Hosted checkout pages handle all payment processing. No raw card data ever touches this server.', 'wellness-consult' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<!-- Test Mode -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Environment Mode', 'wellness-consult' ); ?></th>
						<td>
							<fieldset>
								<label for="test_mode" style="font-weight:600;">
									<input type="checkbox" id="test_mode" name="test_mode" value="1" <?php checked( $is_test_mode ); ?>>
									<?php esc_html_e( 'Enable Test / Sandbox Mode', 'wellness-consult' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'When checked, test API keys are used and transactions use sandbox simulated payments.', 'wellness-consult' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>

					<!-- Stripe Settings -->
					<tr>
						<th scope="row" style="border-top:1px solid #eee;"><?php esc_html_e( 'Stripe Checkout', 'wellness-consult' ); ?></th>
						<td style="border-top:1px solid #eee;">
							<label for="stripe_enabled" style="font-weight:600;">
								<input type="checkbox" id="stripe_enabled" name="stripe_enabled" value="1" <?php checked( ! empty( $settings['stripe_enabled'] ) ); ?>>
								<?php esc_html_e( 'Enable Stripe Checkout', 'wellness-consult' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Stripe Test Publishable Key', 'wellness-consult' ); ?></th>
						<td>
							<input type="password" name="stripe_test_pk" class="regular-text" placeholder="<?php echo esc_attr( ! empty( $settings['stripe_test_pk'] ) ? 'Configured (' . wc_mask_secret( $settings['stripe_test_pk'] ) . ')' : 'pk_test_...' ); ?>" autocomplete="new-password">
							<p class="description"><?php esc_html_e( 'Starts with pk_test_. Leave blank to retain existing key.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Stripe Test Secret Key', 'wellness-consult' ); ?></th>
						<td>
							<input type="password" name="stripe_test_sk" class="regular-text" placeholder="<?php echo esc_attr( ! empty( $settings['stripe_test_sk'] ) ? 'Configured (' . wc_mask_secret( $settings['stripe_test_sk'] ) . ')' : 'sk_test_...' ); ?>" autocomplete="new-password">
							<p class="description"><?php esc_html_e( 'Starts with sk_test_. Leave blank to retain existing key.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Stripe Live Publishable Key', 'wellness-consult' ); ?></th>
						<td>
							<input type="password" name="stripe_live_pk" class="regular-text" placeholder="<?php echo esc_attr( ! empty( $settings['stripe_live_pk'] ) ? 'Configured (' . wc_mask_secret( $settings['stripe_live_pk'] ) . ')' : 'pk_live_...' ); ?>" autocomplete="new-password">
							<p class="description"><?php esc_html_e( 'Starts with pk_live_. Leave blank to retain existing key.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Stripe Live Secret Key', 'wellness-consult' ); ?></th>
						<td>
							<input type="password" name="stripe_live_sk" class="regular-text" placeholder="<?php echo esc_attr( ! empty( $settings['stripe_live_sk'] ) ? 'Configured (' . wc_mask_secret( $settings['stripe_live_sk'] ) . ')' : 'sk_live_...' ); ?>" autocomplete="new-password">
							<p class="description"><?php esc_html_e( 'Starts with sk_live_. Leave blank to retain existing key.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Stripe Webhook Secret', 'wellness-consult' ); ?></th>
						<td>
							<input type="password" name="stripe_webhook_secret" class="regular-text" placeholder="<?php echo esc_attr( ! empty( $settings['stripe_webhook_secret'] ) ? 'Configured (' . wc_mask_secret( $settings['stripe_webhook_secret'] ) . ')' : 'whsec_...' ); ?>" autocomplete="new-password">
							<p class="description">
								<?php esc_html_e( 'Stripe Webhook Endpoint URL:', 'wellness-consult' ); ?>
								<code><?php echo esc_url( home_url( '/wp-json/wc-consult/v1/stripe-webhook' ) ); ?></code>
							</p>
						</td>
					</tr>

					<!-- PayPal Settings -->
					<tr>
						<th scope="row" style="border-top:1px solid #eee;"><?php esc_html_e( 'PayPal Checkout', 'wellness-consult' ); ?></th>
						<td style="border-top:1px solid #eee;">
							<label for="paypal_enabled" style="font-weight:600;">
								<input type="checkbox" id="paypal_enabled" name="paypal_enabled" value="1" <?php checked( ! empty( $settings['paypal_enabled'] ) ); ?>>
								<?php esc_html_e( 'Enable PayPal Checkout', 'wellness-consult' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'PayPal Client ID', 'wellness-consult' ); ?></th>
						<td>
							<input type="password" name="paypal_client_id" class="regular-text" placeholder="<?php echo esc_attr( ! empty( $settings['paypal_client_id'] ) ? 'Configured (' . wc_mask_secret( $settings['paypal_client_id'] ) . ')' : 'Enter PayPal Client ID' ); ?>" autocomplete="new-password">
							<p class="description"><?php esc_html_e( 'PayPal REST App Client ID. Leave blank to retain existing.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'PayPal Secret', 'wellness-consult' ); ?></th>
						<td>
							<input type="password" name="paypal_secret" class="regular-text" placeholder="<?php echo esc_attr( ! empty( $settings['paypal_secret'] ) ? 'Configured (' . wc_mask_secret( $settings['paypal_secret'] ) . ')' : 'Enter PayPal Secret' ); ?>" autocomplete="new-password">
							<p class="description"><?php esc_html_e( 'PayPal REST App Secret. Leave blank to retain existing.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'PayPal Webhook ID', 'wellness-consult' ); ?></th>
						<td>
							<input type="password" name="paypal_webhook_id" class="regular-text" placeholder="<?php echo esc_attr( ! empty( $settings['paypal_webhook_id'] ) ? 'Configured (' . wc_mask_secret( $settings['paypal_webhook_id'] ) . ')' : 'Enter Webhook ID' ); ?>" autocomplete="new-password">
							<p class="description">
								<?php esc_html_e( 'PayPal Webhook Endpoint URL:', 'wellness-consult' ); ?>
								<code><?php echo esc_url( home_url( '/wp-json/wc-consult/v1/paypal-webhook' ) ); ?></code>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<input type="submit" name="submit_gateways" id="submit_gateways" class="button button-primary" value="<?php esc_attr_e( 'Save Gateway Settings', 'wellness-consult' ); ?>">
			</p>
		</form>
	</div>

	<!-- Section 2: Manual Transfer Methods -->
	<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:20px;margin-top:30px;border-radius:4px;">
		<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
			<div>
				<h2 style="margin:0;display:flex;align-items:center;gap:8px;">
					<span class="dashicons dashicons-money-alt"></span>
					<?php esc_html_e( 'Manual Transfer Methods (Bank, Wise, Local Wallets)', 'wellness-consult' ); ?>
				</h2>
				<p class="description" style="margin-top:4px;">
					<?php esc_html_e( 'Patients complete transfer in their banking app and upload screenshot proof on the booking form.', 'wellness-consult' ); ?>
				</p>
			</div>
			<div style="display:flex;gap:8px;">
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-payments&action=add_sample_method' ), 'wc_add_sample_method' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-plus-alt" style="vertical-align:text-top;font-size:16px;"></span> <?php esc_html_e( 'Add Sample Method (SAMPLE)', 'wellness-consult' ); ?>
				</a>
				<?php
				$has_sample = false;
				foreach ( $manual_methods as $m ) {
					if ( ! empty( $m['is_sample'] ) ) {
						$has_sample = true;
						break;
					}
				}
				if ( $has_sample ) :
					?>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-payments&action=remove_sample_methods' ), 'wc_remove_sample_methods' ) ); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Remove all sample methods with one click?', 'wellness-consult' ); ?>');">
						<?php esc_html_e( 'Remove All Sample Methods', 'wellness-consult' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Table of configured methods -->
		<table class="wp-list-table widefat fixed striped" style="margin-top:16px;">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Method Name', 'wellness-consult' ); ?></th>
					<th scope="col" style="width:140px;"><?php esc_html_e( 'Method Type', 'wellness-consult' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Account / Destination Details', 'wellness-consult' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Country Filter', 'wellness-consult' ); ?></th>
					<th scope="col" style="width:90px;"><?php esc_html_e( 'Status', 'wellness-consult' ); ?></th>
					<th scope="col" style="width:140px;"><?php esc_html_e( 'Actions', 'wellness-consult' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $manual_methods ) ) : ?>
					<tr>
						<td colspan="6" style="padding:24px;text-align:center;color:#666;">
							<span class="dashicons dashicons-info" style="font-size:28px;color:#999;display:block;margin-bottom:8px;"></span>
							<strong><?php esc_html_e( 'No manual transfer methods configured yet.', 'wellness-consult' ); ?></strong>
							<p style="margin:4px 0 0 0;"><?php esc_html_e( 'Use the form below to add your first bank wire, Wise, Payoneer, or payment link account.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $manual_methods as $mid => $method ) :
						$m_type = $method['method_type'] ?? 'bank';
					?>
						<tr>
							<td>
								<strong><?php echo esc_html( $method['name'] ); ?></strong>
								<?php if ( ! empty( $method['is_sample'] ) ) : ?>
									<span style="background:#ef4444;color:#fff;padding:2px 6px;border-radius:3px;font-size:10px;font-weight:700;margin-left:6px;"><?php esc_html_e( 'SAMPLE', 'wellness-consult' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<span class="badge" style="background:#e0f2fe;color:#0369a1;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:700;">
									<?php echo esc_html( WC_Options::get_manual_payment_type_badge( $m_type ) ); ?>
								</span>
							</td>
							<td>
								<?php if ( 'bank' === $m_type ) : ?>
									<div><strong><?php echo esc_html( $method['account_holder'] ?? ( $method['account_title'] ?? '—' ) ); ?></strong></div>
									<div><code style="font-size:12px;"><?php echo esc_html( $method['account_number'] ?? '—' ); ?></code></div>
									<?php if ( ! empty( $method['swift_bic'] ) || ! empty( $method['bank_name'] ) ) : ?>
										<div style="font-size:11px;color:#64748b;">
											<?php echo esc_html( ( $method['bank_name'] ?? '' ) . ( ! empty( $method['swift_bic'] ) ? ' • SWIFT: ' . $method['swift_bic'] : '' ) ); ?>
										</div>
									<?php endif; ?>
								<?php elseif ( 'wise_payoneer' === $m_type ) : ?>
									<div><strong><?php echo esc_html( $method['account_identifier'] ?? ( $method['account_title'] ?? '—' ) ); ?></strong></div>
									<div style="font-size:11px;color:#64748b;"><?php printf( esc_html__( 'Currency: %s', 'wellness-consult' ), esc_html( $method['receiving_currency'] ?? 'USD' ) ); ?></div>
								<?php elseif ( 'payment_link' === $m_type ) : ?>
									<div><strong><?php echo esc_html( $method['button_label'] ?? 'Open Payment Request' ); ?></strong></div>
									<div style="word-break:break-all;"><code style="font-size:11px;"><?php echo esc_html( $method['payment_url'] ?? '—' ); ?></code></div>
								<?php else : ?>
									<div style="font-size:12px;color:#475569;"><?php echo esc_html( wp_trim_words( $method['instructions'] ?? '', 10 ) ); ?></div>
								<?php endif; ?>
							</td>
							<td>
								<?php
								if ( empty( $method['allowed_countries'] ) ) {
									echo '<span style="color:#059669;font-weight:600;">' . esc_html__( 'Worldwide (All Countries)', 'wellness-consult' ) . '</span>';
								} else {
									$ct_names = array();
									foreach ( $method['allowed_countries'] as $code ) {
										$ct_names[] = $all_countries[ $code ] ?? $code;
									}
									echo '<strong>' . esc_html( implode( ', ', array_slice( $ct_names, 0, 3 ) ) ) . '</strong>';
									if ( count( $ct_names ) > 3 ) {
										echo esc_html( ' + ' . ( count( $ct_names ) - 3 ) . ' more' );
									}
								}
								?>
							</td>
							<td>
								<?php if ( ! empty( $method['active'] ) && '1' === (string) $method['active'] ) : ?>
									<span class="badge" style="background:#10b981;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;"><?php esc_html_e( 'Active', 'wellness-consult' ); ?></span>
								<?php else : ?>
									<span class="badge" style="background:#6b7280;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;"><?php esc_html_e( 'Disabled', 'wellness-consult' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-payments&action=edit_method&method_id=' . urlencode( $mid ) ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'wellness-consult' ); ?>
								</a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-payments&action=delete_method&method_id=' . urlencode( $mid ) ), 'wc_delete_method_' . $mid ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this payment method?', 'wellness-consult' ); ?>');">
									<?php esc_html_e( 'Delete', 'wellness-consult' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- Add / Edit Method Form -->
		<div id="wc-manual-method-form-box" style="background:#f9fafb;border:1px solid #e5e7eb;padding:20px;margin-top:24px;border-radius:6px;">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
				<h3 style="margin:0;font-size:16px;">
					<?php if ( $edit_method ) : ?>
						<?php printf( esc_html__( 'Edit Payment Method: %s', 'wellness-consult' ), esc_html( $edit_method['name'] ) ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Add International Payment Method', 'wellness-consult' ); ?>
					<?php endif; ?>
				</h3>
				<?php if ( $edit_method ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-payments' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Cancel Editing / Add New', 'wellness-consult' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<form method="post" action="">
				<?php wp_nonce_field( 'wc_save_manual_action', 'wc_manual_save_nonce' ); ?>
				<?php if ( $edit_id ) : ?>
					<input type="hidden" name="method_id" value="<?php echo esc_attr( $edit_id ); ?>">
				<?php endif; ?>

				<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px;">
					<div>
						<label for="method_name" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Method Display Name *', 'wellness-consult' ); ?></label>
						<input type="text" id="method_name" name="method_name" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['name'] ?? '' ); ?>" placeholder="e.g. International Bank Wire (SWIFT / IBAN)" required>
						<p class="description"><?php esc_html_e( 'Label shown to patients on Step 2 of the booking form.', 'wellness-consult' ); ?></p>
					</div>

					<div>
						<label for="method_type" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Payment Method Type *', 'wellness-consult' ); ?></label>
						<?php
						$current_type = $edit_method['method_type'] ?? '';
						$payment_types = WC_Options::get_manual_payment_types();
						?>
						<select id="method_type" name="method_type" class="regular-text" style="width:100%;" required>
							<option value="" <?php selected( $current_type, '' ); ?> disabled><?php esc_html_e( 'Select a method type', 'wellness-consult' ); ?></option>
							<?php foreach ( $payment_types as $pt_key => $pt_info ) : ?>
								<option value="<?php echo esc_attr( $pt_key ); ?>" <?php selected( $current_type, $pt_key ); ?>>
									<?php echo esc_html( $pt_info['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Select the type to display corresponding configuration fields.', 'wellness-consult' ); ?></p>
					</div>
				</div>

				<!-- TYPE 1: Bank Transfer Fields (SWIFT / IBAN) -->
				<div class="wc-method-fields-group" id="wc-group-bank" style="margin-top:20px;padding:16px;background:#ffffff;border:1px solid #e2e8f0;border-radius:4px;">
					<h4 style="margin:0 0 12px 0;color:#0f3b4c;display:flex;align-items:center;gap:6px;">
						<span class="dashicons dashicons-vault"></span>
						<?php esc_html_e( 'Bank Transfer (SWIFT / IBAN) Details', 'wellness-consult' ); ?>
					</h4>
					<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:14px;">
						<div>
							<label for="account_holder" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Account Holder / Beneficiary Name *', 'wellness-consult' ); ?></label>
							<input type="text" id="account_holder" name="account_holder" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['account_holder'] ?? ( $edit_method['account_title'] ?? '' ) ); ?>" placeholder="e.g. Wellness Healthcare Consultations LLC">
						</div>
						<div>
							<label for="bank_name" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Bank Name', 'wellness-consult' ); ?></label>
							<input type="text" id="bank_name" name="bank_name" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['bank_name'] ?? '' ); ?>" placeholder="e.g. JPMorgan Chase Bank NA">
						</div>
						<div>
							<label for="account_number" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'IBAN or Account Number *', 'wellness-consult' ); ?></label>
							<input type="text" id="account_number" name="account_number" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['account_number'] ?? '' ); ?>" placeholder="e.g. US12CHAS00001234567890">
						</div>
						<div>
							<label for="swift_bic" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'SWIFT / BIC Code', 'wellness-consult' ); ?></label>
							<input type="text" id="swift_bic" name="swift_bic" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['swift_bic'] ?? '' ); ?>" placeholder="e.g. CHASUS33">
						</div>
						<div>
							<label for="bank_country" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Bank Country', 'wellness-consult' ); ?></label>
							<input type="text" id="bank_country" name="bank_country" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['bank_country'] ?? '' ); ?>" placeholder="e.g. United States">
						</div>
						<div>
							<label for="currency" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Currency Code', 'wellness-consult' ); ?></label>
							<input type="text" id="currency" name="currency" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['currency'] ?? '' ); ?>" placeholder="e.g. USD, EUR, GBP">
						</div>
					</div>
				</div>

				<!-- TYPE 2: Payoneer or Wise Fields -->
				<div class="wc-method-fields-group" id="wc-group-wise_payoneer" style="margin-top:20px;padding:16px;background:#ffffff;border:1px solid #e2e8f0;border-radius:4px;display:none;">
					<h4 style="margin:0 0 12px 0;color:#0f3b4c;display:flex;align-items:center;gap:6px;">
						<span class="dashicons dashicons-money-alt"></span>
						<?php esc_html_e( 'Payoneer or Wise Account Details', 'wellness-consult' ); ?>
					</h4>
					<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:14px;">
						<div>
							<label for="account_identifier" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Account Email or Tag / Handle *', 'wellness-consult' ); ?></label>
							<input type="text" id="account_identifier" name="account_identifier" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['account_identifier'] ?? ( $edit_method['account_title'] ?? '' ) ); ?>" placeholder="e.g. payments@wellness-consult.com or @wellnesshealth">
							<p class="description"><?php esc_html_e( 'The email address or handle where patients send their Payoneer/Wise transfer.', 'wellness-consult' ); ?></p>
						</div>
						<div>
							<label for="receiving_currency" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Receiving Currency *', 'wellness-consult' ); ?></label>
							<input type="text" id="receiving_currency" name="receiving_currency" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['receiving_currency'] ?? '' ); ?>" placeholder="e.g. USD">
						</div>
					</div>
				</div>

				<!-- TYPE 3: Payment Link Fields -->
				<div class="wc-method-fields-group" id="wc-group-payment_link" style="margin-top:20px;padding:16px;background:#ffffff;border:1px solid #e2e8f0;border-radius:4px;display:none;">
					<h4 style="margin:0 0 12px 0;color:#0f3b4c;display:flex;align-items:center;gap:6px;">
						<span class="dashicons dashicons-external"></span>
						<?php esc_html_e( 'Hosted Payment Link Configuration', 'wellness-consult' ); ?>
					</h4>
					<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:14px;">
						<div>
							<label for="payment_url" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Hosted Payment Request URL *', 'wellness-consult' ); ?></label>
							<input type="url" id="payment_url" name="payment_url" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['payment_url'] ?? '' ); ?>" placeholder="e.g. https://wise.com/pay/me/yourlink or https://paypal.me/yourlink">
							<p class="description"><?php esc_html_e( 'Patient is redirected to this hosted page to complete payment, then returns to upload screenshot.', 'wellness-consult' ); ?></p>
						</div>
						<div>
							<label for="button_label" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Payment Button Label *', 'wellness-consult' ); ?></label>
							<input type="text" id="button_label" name="button_label" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $edit_method['button_label'] ?? '' ); ?>" placeholder="e.g. Pay on Wise / Open Payment Request">
						</div>
					</div>
				</div>

				<!-- Notes & Instructions Field (Shared) -->
				<div style="margin-top:16px;">
					<label for="notes" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Transfer Notes / Custom Instructions', 'wellness-consult' ); ?></label>
					<textarea id="notes" name="notes" rows="3" style="width:100%;" placeholder="e.g. Please send exact fee in USD. Sender pays all transfer fees."><?php echo esc_textarea( $edit_method['notes'] ?? ( $edit_method['instructions'] ?? '' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Visible to patient when this payment method is selected.', 'wellness-consult' ); ?></p>
				</div>

				<!-- TYPE 4: Other Free Text Instructions -->
				<div class="wc-method-fields-group" id="wc-group-other" style="margin-top:16px;display:none;">
					<label for="instructions" style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Free Text Transfer Instructions *', 'wellness-consult' ); ?></label>
					<textarea id="instructions" name="instructions" rows="4" style="width:100%;" placeholder="Provide complete step-by-step instructions for the patient..."><?php echo esc_textarea( $edit_method['instructions'] ?? '' ); ?></textarea>
				</div>

				<!-- Country Restrictions -->
				<div style="margin-top:16px;">
					<label style="font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( 'Country Restrictions (Optional)', 'wellness-consult' ); ?></label>
					<p class="description" style="margin-bottom:8px;"><?php esc_html_e( 'Leave all unchecked to show to patients worldwide, or select specific countries where this payment method is available.', 'wellness-consult' ); ?></p>
					<?php $selected_countries = $edit_method['allowed_countries'] ?? array(); ?>
					<div style="max-height:140px;overflow-y:auto;background:#fff;border:1px solid #ccc;padding:10px;border-radius:3px;display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:6px;">
						<?php foreach ( $all_countries as $iso => $cname ) : ?>
							<label style="font-size:12px;">
								<input type="checkbox" name="allowed_countries[]" value="<?php echo esc_attr( $iso ); ?>" <?php checked( in_array( $iso, $selected_countries, true ) ); ?>>
								<?php echo esc_html( $cname . ' (' . $iso . ')' ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Active Switch -->
				<div style="margin-top:16px;">
					<label for="method_active" style="font-weight:600;">
						<input type="checkbox" id="method_active" name="active" value="1" <?php checked( isset( $edit_method['active'] ) && '1' === (string) $edit_method['active'] ); ?>>
						<?php esc_html_e( 'Method Active (Available for bookings)', 'wellness-consult' ); ?>
					</label>
				</div>

				<p class="submit" style="margin-top:16px;margin-bottom:0;">
					<input type="submit" name="submit_manual" class="button button-primary" value="<?php echo $edit_method ? esc_attr__( 'Update Payment Method', 'wellness-consult' ) : esc_attr__( 'Save Payment Method', 'wellness-consult' ); ?>">
				</p>
			</form>
		</div>

		<script>
		(function() {
			function updateFields() {
				var type = document.getElementById('method_type').value;
				var groups = ['bank', 'wise_payoneer', 'payment_link', 'other'];
				groups.forEach(function(g) {
					var el = document.getElementById('wc-group-' + g);
					if (el) {
						el.style.display = (g === type) ? 'block' : 'none';
					}
				});
			}
			var select = document.getElementById('method_type');
			if (select) {
				select.addEventListener('change', updateFields);
				updateFields();
			}
		})();
		</script>
	</div>
</div>
