<?php
/**
 * Admin Screen: Allowed / Blocked Countries Configuration
 *
 * Implements SRS v2.0 Section 6 and Section 10 Cross-Border Compliance Rules.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_manage_countries' ) ) {
	wp_die( esc_html__( 'You do not have permission to configure country restrictions.', 'wellness-consult' ) );
}

$notice = '';
$notice_type = 'success';

$countries_settings = get_option( 'wc_countries_settings', array(
	'mode'         => 'all_except_blocked',
	'allowed_list' => array(),
	'blocked_list' => array(),
) );

$all_countries = WC_Options::get_all_iso_countries();

// Save Handler
if ( isset( $_POST['wc_countries_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wc_countries_nonce'] ), 'wc_save_countries_action' ) ) {
	$mode = sanitize_text_field( wp_unslash( $_POST['country_mode'] ?? 'all_except_blocked' ) );
	if ( ! in_array( $mode, array( 'all_except_blocked', 'only_allowed' ), true ) ) {
		$mode = 'all_except_blocked';
	}

	$selected_countries = isset( $_POST['countries'] ) && is_array( $_POST['countries'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['countries'] ) ) : array();

	// Validate country codes exist in ISO list
	$valid_selection = array();
	foreach ( $selected_countries as $iso ) {
		if ( isset( $all_countries[ $iso ] ) ) {
			$valid_selection[] = $iso;
		}
	}

	if ( 'all_except_blocked' === $mode ) {
		$countries_settings['mode']         = 'all_except_blocked';
		$countries_settings['blocked_list'] = $valid_selection;
	} else {
		$countries_settings['mode']         = 'only_allowed';
		$countries_settings['allowed_list'] = $valid_selection;
	}

	update_option( 'wc_countries_settings', $countries_settings );
	$notice = esc_html__( 'Country compliance rules and restrictions updated successfully.', 'wellness-consult' );

	WC_Database::log( array(
		'action' => 'update_countries_policy',
		'note'   => sprintf( 'Mode set to "%s" with %d countries in list.', $mode, count( $valid_selection ) ),
	) );
}

$current_mode = $countries_settings['mode'] ?? 'all_except_blocked';
$active_selection = ( 'all_except_blocked' === $current_mode ) ? ( $countries_settings['blocked_list'] ?? array() ) : ( $countries_settings['allowed_list'] ?? array() );
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Country Restrictions & Geographic Scope', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<div class="notice notice-info" style="margin-top:16px;">
		<p>
			<strong><?php esc_html_e( 'Cross-Border Compliance Note (SRS Section 10):', 'wellness-consult' ); ?></strong>
			<?php esc_html_e( 'Consultations are delivered as counseling and wellness education. Patients attempting to book from disallowed countries are blocked with a clear legal notice prior to payment.', 'wellness-consult' ); ?>
		</p>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'wc_save_countries_action', 'wc_countries_nonce' ); ?>

		<!-- Operating Mode Card -->
		<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:20px;border-radius:4px;">
			<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<?php esc_html_e( 'Scope Mode', 'wellness-consult' ); ?>
			</h2>

			<fieldset style="margin-top:12px;">
				<label style="display:block;margin-bottom:12px;font-weight:600;font-size:14px;cursor:pointer;">
					<input type="radio" name="country_mode" value="all_except_blocked" <?php checked( 'all_except_blocked', $current_mode ); ?> onchange="document.getElementById('list-label').textContent = 'Select Blocked Countries:';">
					<?php esc_html_e( 'Worldwide Access (Accept all countries EXCEPT blocked list below)', 'wellness-consult' ); ?>
				</label>
				<p class="description" style="margin-left:24px;margin-top:-6px;margin-bottom:16px;">
					<?php esc_html_e( 'Recommended default for global availability. Patients worldwide can book unless specifically selected below.', 'wellness-consult' ); ?>
				</p>

				<label style="display:block;margin-bottom:12px;font-weight:600;font-size:14px;cursor:pointer;">
					<input type="radio" name="country_mode" value="only_allowed" <?php checked( 'only_allowed', $current_mode ); ?> onchange="document.getElementById('list-label').textContent = 'Select Allowed Countries:';">
					<?php esc_html_e( 'Strict Whitelist (ONLY allow countries selected below; all others are blocked)', 'wellness-consult' ); ?>
				</label>
				<p class="description" style="margin-left:24px;margin-top:-6px;">
					<?php esc_html_e( 'Use when your consultant license only permits practicing within specific approved jurisdictions.', 'wellness-consult' ); ?>
				</p>
			</fieldset>
		</div>

		<!-- Country Checklist Card -->
		<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:20px;border-radius:4px;">
			<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
				<h2 style="margin:0;display:flex;align-items:center;gap:8px;">
					<span class="dashicons dashicons-flag"></span>
					<span id="list-label"><?php echo ( 'all_except_blocked' === $current_mode ) ? esc_html__( 'Select Blocked Countries:', 'wellness-consult' ) : esc_html__( 'Select Allowed Countries:', 'wellness-consult' ); ?></span>
				</h2>
				<div>
					<input type="text" id="wc-country-search" placeholder="<?php esc_attr_e( 'Quick filter country name...', 'wellness-consult' ); ?>" class="regular-text" style="font-size:13px;padding:4px 8px;" onkeyup="wcFilterCountries(this.value)">
					<button type="button" class="button button-secondary" onclick="wcSelectAllCountries(false)"><?php esc_html_e( 'Clear All', 'wellness-consult' ); ?></button>
				</div>
			</div>

			<p class="description" style="margin-bottom:14px;">
				<?php esc_html_e( 'Standard ISO 3166-1 country catalog. Check the boxes corresponding to the active mode chosen above.', 'wellness-consult' ); ?>
			</p>

			<div id="wc-countries-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:8px;max-height:450px;overflow-y:auto;border:1px solid #ddd;padding:14px;background:#fcfcfc;border-radius:4px;">
				<?php foreach ( $all_countries as $iso => $country_name ) :
					$checked = in_array( $iso, $active_selection, true );
				?>
					<label class="wc-country-item" data-name="<?php echo esc_attr( strtolower( $country_name . ' ' . $iso ) ); ?>" style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:3px 4px;border-radius:3px;">
						<input type="checkbox" name="countries[]" value="<?php echo esc_attr( $iso ); ?>" <?php checked( $checked ); ?>>
						<span><strong><?php echo esc_html( $iso ); ?></strong> - <?php echo esc_html( $country_name ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<p class="submit" style="margin-top:20px;">
			<input type="submit" name="submit_countries" class="button button-primary" value="<?php esc_attr_e( 'Save Country Settings', 'wellness-consult' ); ?>">
		</p>
	</form>
</div>

<script>
function wcFilterCountries(val) {
	var term = val.toLowerCase().trim();
	var items = document.querySelectorAll('.wc-country-item');
	items.forEach(function(item) {
		var name = item.getAttribute('data-name');
		if (!term || name.indexOf(term) !== -1) {
			item.style.display = 'flex';
		} else {
			item.style.display = 'none';
		}
	});
}

function wcSelectAllCountries(check) {
	var checkboxes = document.querySelectorAll('#wc-countries-grid input[type="checkbox"]');
	checkboxes.forEach(function(cb) {
		cb.checked = check;
	});
}
</script>
