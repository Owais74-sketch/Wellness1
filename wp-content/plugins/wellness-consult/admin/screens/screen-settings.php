<?php
/**
 * Admin Screen: System Settings & JSON Export/Import
 *
 * Configures refund/reschedule, retention, spam limits, uninstall, and backup.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_manage_settings' ) ) {
	wp_die( esc_html__( 'You do not have permission to manage consultation settings.', 'wellness-consult' ) );
}

$notice = '';
$notice_type = 'success';

$blocked_contacts = get_option( 'wc_blocked_contacts', array() );
if ( ! is_array( $blocked_contacts ) ) {
	$blocked_contacts = array();
}

if ( isset( $_POST['wc_block_contacts_submit'] ) ) {
	check_admin_referer( 'wc_block_contacts_action', 'wc_block_contacts_nonce' );
	$reason = isset( $_POST['block_reason'] ) && is_string( $_POST['block_reason'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['block_reason'] ) ) ) : '';
	$added  = array();
	if ( '' !== $reason ) {
		foreach ( array( 'email', 'phone', 'country' ) as $type ) {
			$value = isset( $_POST[ 'block_' . $type ] ) && is_string( $_POST[ 'block_' . $type ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'block_' . $type ] ) ) : '';
			if ( '' !== $value && WC_Fraud_Protection::add_block( $type, $value ) ) {
				$added[] = $type;
			}
		}
	}

	if ( $added ) {
		$blocked_contacts = get_option( 'wc_blocked_contacts', array() );
		$notice = esc_html__( 'Block rule saved.', 'wellness-consult' );
		WC_Database::log( array(
			'action' => 'add_booking_block',
			'note'   => sprintf( 'Blocked %s. Reason: %s', implode( ', ', array_unique( $added ) ), $reason ),
		) );
	} else {
		$notice = '' === $reason
			? esc_html__( 'Enter a reason and at least one valid email, phone, or country.', 'wellness-consult' )
			: esc_html__( 'No valid block value was added.', 'wellness-consult' );
		$notice_type = 'error';
	}
}

if ( isset( $_POST['wc_remove_block_submit'] ) ) {
	check_admin_referer( 'wc_remove_block_action', 'wc_remove_block_nonce' );
	$key = sanitize_text_field( wp_unslash( $_POST['block_key'] ?? '' ) );
	if ( isset( $blocked_contacts[ $key ] ) ) {
		unset( $blocked_contacts[ $key ] );
		update_option( 'wc_blocked_contacts', $blocked_contacts, false );
		$notice = esc_html__( 'Block rule removed.', 'wellness-consult' );
		WC_Database::log( array(
			'action' => 'remove_booking_block',
			'note'   => 'Removed a booking block rule.',
		) );
	} else {
		$notice = esc_html__( 'The selected block rule was not found.', 'wellness-consult' );
		$notice_type = 'error';
	}
}

$general_settings = get_option( 'wc_general_settings', array(
	'min_reschedule_hours' => 6,
	'max_reschedules'      => 1,
	'reminder_24h'         => '1',
	'reminder_1h'          => '1',
	'proof_retention_days' => 90,
	'rate_limit_per_hour'  => 3,
	'track_fail_limit'     => 5,
	'delete_on_uninstall'  => '0',
) );

// Handle Settings Form Save
if ( isset( $_POST['wc_settings_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wc_settings_nonce'] ), 'wc_save_general_settings' ) ) {
	$min_reschedule_hours = max( 1, absint( $_POST['min_reschedule_hours'] ?? 6 ) );
	$max_reschedules      = max( 0, absint( $_POST['max_reschedules'] ?? 1 ) );
	$reminder_24h         = isset( $_POST['reminder_24h'] ) ? '1' : '0';
	$reminder_1h          = isset( $_POST['reminder_1h'] ) ? '1' : '0';
	$proof_retention_days = max( 7, absint( $_POST['proof_retention_days'] ?? 90 ) );
	$rate_limit_per_hour  = max( 1, absint( $_POST['rate_limit_per_hour'] ?? 3 ) );
	$track_fail_limit     = max( 3, absint( $_POST['track_fail_limit'] ?? 5 ) );
	$delete_on_uninstall  = isset( $_POST['delete_on_uninstall'] ) ? '1' : '0';

	$general_settings = array(
		'min_reschedule_hours' => $min_reschedule_hours,
		'max_reschedules'      => $max_reschedules,
		'reminder_24h'         => $reminder_24h,
		'reminder_1h'          => $reminder_1h,
		'proof_retention_days' => $proof_retention_days,
		'rate_limit_per_hour'  => $rate_limit_per_hour,
		'track_fail_limit'     => $track_fail_limit,
		'delete_on_uninstall'  => $delete_on_uninstall,
	);

	update_option( 'wc_general_settings', $general_settings );
	$notice = esc_html__( 'Consultation settings updated successfully.', 'wellness-consult' );

	WC_Database::log( array(
		'action' => 'update_general_settings',
		'note'   => sprintf( 'Settings updated. Delete-on-uninstall: %s.', '1' === $delete_on_uninstall ? 'ON' : 'OFF' ),
	) );
}

// Handle Platforms Save (CR-1)
$platforms = get_option( 'wc_platforms', WC_Options::get_default_platforms() );

if ( isset( $_POST['wc_platforms_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wc_platforms_nonce'] ), 'wc_save_platforms_action' ) ) {
	$submitted_platforms = isset( $_POST['platforms'] ) && is_array( $_POST['platforms'] ) ? $_POST['platforms'] : array();

	$updated_platforms = array(
		'chat'  => array(),
		'voice' => array(),
		'video' => array(),
	);

	foreach ( array( 'chat', 'voice', 'video' ) as $mode ) {
		if ( ! empty( $submitted_platforms[ $mode ] ) && is_array( $submitted_platforms[ $mode ] ) ) {
			foreach ( $submitted_platforms[ $mode ] as $pid => $pdata ) {
				$pid   = sanitize_key( $pid );
				$pname = sanitize_text_field( $pdata['name'] ?? '' );
				if ( ! empty( $pname ) ) {
					$updated_platforms[ $mode ][ $pid ] = array(
						'id'           => $pid,
						'name'         => $pname,
						'instructions' => sanitize_text_field( $pdata['instructions'] ?? '' ),
						'is_active'    => ! empty( $pdata['is_active'] ) ? 1 : 0,
					);
				}
			}
		}

		// Handle Add New Platform for this mode
		if ( ! empty( $_POST['new_platform'][ $mode ]['name'] ) ) {
			$new_name = sanitize_text_field( $_POST['new_platform'][ $mode ]['name'] );
			$new_id   = sanitize_key( str_replace( ' ', '_', strtolower( $new_name ) ) . '_' . time() );
			$updated_platforms[ $mode ][ $new_id ] = array(
				'id'           => $new_id,
				'name'         => $new_name,
				'instructions' => sanitize_text_field( $_POST['new_platform'][ $mode ]['instructions'] ?? '' ),
				'is_active'    => ! empty( $_POST['new_platform'][ $mode ]['is_active'] ) ? 1 : 0,
			);
		}
	}

	update_option( 'wc_platforms', $updated_platforms );
	$platforms = $updated_platforms;
	$notice    = esc_html__( 'Consultation delivery platforms updated successfully.', 'wellness-consult' );
	WC_Database::log( array(
		'action' => 'update_platforms',
		'note'   => 'Admin updated consultation delivery platforms per mode.',
	) );
}

// Handle JSON Import
if ( isset( $_POST['wc_import_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wc_import_nonce'] ), 'wc_import_settings_action' ) ) {
	if ( ! empty( $_FILES['wc_import_file']['tmp_name'] ) ) {
		$json_content = file_get_contents( sanitize_text_field( $_FILES['wc_import_file']['tmp_name'] ) );
		$imported = json_decode( $json_content, true );

		if ( is_array( $imported ) && isset( $imported['schema_version'] ) ) {
			if ( isset( $imported['general_settings'] ) && is_array( $imported['general_settings'] ) ) {
				update_option( 'wc_general_settings', $imported['general_settings'] );
				$general_settings = $imported['general_settings'];
			}
			if ( isset( $imported['consultation_types'] ) && is_array( $imported['consultation_types'] ) ) {
				update_option( 'wc_consultation_types', $imported['consultation_types'] );
			}
			if ( isset( $imported['countries_settings'] ) && is_array( $imported['countries_settings'] ) ) {
				update_option( 'wc_countries_settings', $imported['countries_settings'] );
			}
			if ( isset( $imported['email_templates'] ) && is_array( $imported['email_templates'] ) ) {
				update_option( 'wc_email_templates', $imported['email_templates'] );
			}
			if ( isset( $imported['platforms'] ) && is_array( $imported['platforms'] ) ) {
				update_option( 'wc_platforms', $imported['platforms'] );
			}

			$notice = esc_html__( 'Settings successfully imported from JSON archive.', 'wellness-consult' );
			WC_Database::log( array(
				'action' => 'import_settings',
				'note'   => 'Imported configuration from JSON file.',
			) );
		} else {
			$notice = esc_html__( 'Invalid JSON settings file format.', 'wellness-consult' );
			$notice_type = 'error';
		}
	}
}

// Handle JSON Export Request
if ( isset( $_GET['action'] ) && 'export_settings' === $_GET['action'] && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wc_export_settings_nonce' ) ) {
		// NEVER export secrets (Stripe / PayPal keys)
		$export_data = array(
			'site_url'           => home_url(),
			'export_timestamp'   => current_time( 'mysql' ),
			'schema_version'     => '1.1.0',
			'general_settings'   => $general_settings,
			'consultation_types' => get_option( 'wc_consultation_types', array() ),
			'platforms'          => get_option( 'wc_platforms', WC_Options::get_default_platforms() ),
			'countries_settings' => get_option( 'wc_countries_settings', array() ),
			'email_templates'    => get_option( 'wc_email_templates', array() ),
		);

		$filename = 'wellness-consult-settings-' . gmdate( 'Y-m-d' ) . '.json';
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		echo wp_json_encode( $export_data, JSON_PRETTY_PRINT );
		exit;
	}
}
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Consultation & System Settings', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'wc_save_general_settings', 'wc_settings_nonce' ); ?>

		<!-- Reschedule & Reminders Card -->
		<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:20px;border-radius:4px;">
			<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Rescheduling & Reminder Rules', 'wellness-consult' ); ?>
			</h2>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="min_reschedule_hours"><?php esc_html_e( 'Reschedule Notice Window (Hours)', 'wellness-consult' ); ?></label></th>
						<td>
							<input type="number" id="min_reschedule_hours" name="min_reschedule_hours" value="<?php echo esc_attr( $general_settings['min_reschedule_hours'] ); ?>" min="1" max="168" class="small-text">
							<span><?php esc_html_e( 'hours prior to appointment (Default: 6)', 'wellness-consult' ); ?></span>
							<p class="description"><?php esc_html_e( 'Patients can only request a reschedule on their tracking page at least this many hours in advance.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="max_reschedules"><?php esc_html_e( 'Max Free Reschedules', 'wellness-consult' ); ?></label></th>
						<td>
							<input type="number" id="max_reschedules" name="max_reschedules" value="<?php echo esc_attr( $general_settings['max_reschedules'] ); ?>" min="0" max="5" class="small-text">
							<span><?php esc_html_e( 'time(s) per consultation booking (Default: 1)', 'wellness-consult' ); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Automated Reminders', 'wellness-consult' ); ?></th>
						<td>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="reminder_24h" value="1" <?php checked( ! empty( $general_settings['reminder_24h'] ) ); ?>>
								<?php esc_html_e( 'Send 24-hour reminder email in patient’s local time zone', 'wellness-consult' ); ?>
							</label>
							<label style="display:block;">
								<input type="checkbox" name="reminder_1h" value="1" <?php checked( ! empty( $general_settings['reminder_1h'] ) ); ?>>
								<?php esc_html_e( 'Send 1-hour reminder email in patient’s local time zone', 'wellness-consult' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Retention & Privacy Card -->
		<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:20px;border-radius:4px;">
			<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-lock"></span>
				<?php esc_html_e( 'Data Retention & GDPR Compliance', 'wellness-consult' ); ?>
			</h2>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="proof_retention_days"><?php esc_html_e( 'Payment Proof Retention (FR-21)', 'wellness-consult' ); ?></label></th>
						<td>
							<input type="number" id="proof_retention_days" name="proof_retention_days" value="<?php echo esc_attr( $general_settings['proof_retention_days'] ); ?>" min="7" max="365" class="small-text">
							<span><?php esc_html_e( 'days after consultation completion (Default: 90)', 'wellness-consult' ); ?></span>
							<p class="description"><?php esc_html_e( 'Payment screenshots and receipts are permanently purged from secure storage after this duration.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rate_limit_per_hour"><?php esc_html_e( 'Spam Submission Rate Limit (FR-15)', 'wellness-consult' ); ?></label></th>
						<td>
							<input type="number" id="rate_limit_per_hour" name="rate_limit_per_hour" value="<?php echo esc_attr( $general_settings['rate_limit_per_hour'] ); ?>" min="1" max="20" class="small-text">
							<span><?php esc_html_e( 'submissions per IP per hour (Default: 3)', 'wellness-consult' ); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="track_fail_limit"><?php esc_html_e( 'Track Page Security Lockout (FR-4.8)', 'wellness-consult' ); ?></label></th>
						<td>
							<input type="number" id="track_fail_limit" name="track_fail_limit" value="<?php echo esc_attr( $general_settings['track_fail_limit'] ?? 5 ); ?>" min="3" max="10" class="small-text">
							<span><?php esc_html_e( 'failed attempts before 15-minute lock (Default: 5)', 'wellness-consult' ); ?></span>
						</td>
					</tr>
				</tbody>
			</table>

			<div style="margin-top:20px;padding:16px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;">
				<h4 style="margin:0 0 8px 0;display:flex;align-items:center;gap:6px;color:#0f3b4c;">
					<span class="dashicons dashicons-shield"></span>
					<?php esc_html_e( 'Web Server Proof Directory Protection (Apache & Nginx)', 'wellness-consult' ); ?>
				</h4>
				<p style="font-size:13px;color:#475569;margin:0 0 10px 0;line-height:1.5;">
					<?php esc_html_e( 'On Apache servers, patient payment screenshots inside wp-content/uploads/wc-proofs/ are automatically locked down by an auto-generated .htaccess file (Require all denied).', 'wellness-consult' ); ?>
					<br>
					<strong><?php esc_html_e( 'For Nginx servers:', 'wellness-consult' ); ?></strong>
					<?php esc_html_e( 'Add the following block to your Nginx site configuration to completely block direct web browser downloads:', 'wellness-consult' ); ?>
				</p>
				<pre style="background:#1e293b;color:#f8fafc;padding:12px;border-radius:4px;font-size:12px;overflow-x:auto;margin:0;">location ^~ /wp-content/uploads/wc-proofs/ {
    deny all;
    return 403;
}</pre>
			</div>
		</div>

		<!-- Uninstallation Card -->
		<div class="wc-card" style="background:#fff;border:1px solid #fecaca;padding:20px;margin-top:20px;border-radius:4px;">
			<h2 style="margin-top:0;color:#dc2626;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Uninstallation Data Policy', 'wellness-consult' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Controls database action when deleting this plugin via the WordPress Plugins menu (Rule 2).', 'wellness-consult' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Delete All Data on Uninstall', 'wellness-consult' ); ?></th>
						<td>
							<label for="delete_on_uninstall" style="font-weight:600;color:#991b1b;">
								<input type="checkbox" id="delete_on_uninstall" name="delete_on_uninstall" value="1" <?php checked( ! empty( $general_settings['delete_on_uninstall'] ) ); ?>>
								<?php esc_html_e( 'Delete all consultation requests, tables, files, and settings upon uninstall', 'wellness-consult' ); ?>
							</label>
							<p class="description" style="color:#b91c1c;">
								<strong><?php esc_html_e( 'Default is OFF.', 'wellness-consult' ); ?></strong>
								<?php esc_html_e( 'If left unchecked, your consultation requests and activity history are safely preserved if the plugin is ever removed.', 'wellness-consult' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<p class="submit" style="margin-top:20px;">
			<input type="submit" name="submit_general" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'wellness-consult' ); ?>">
		</p>
	</form>

	<div class="postbox" style="padding:16px;margin-top:24px;">
		<h2 class="hndle"><?php esc_html_e( 'Booking Block List', 'wellness-consult' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Email addresses and phone numbers are stored as hashes. The reason is recorded only in the activity log.', 'wellness-consult' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'wc_block_contacts_action', 'wc_block_contacts_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="block_email"><?php esc_html_e( 'Email address', 'wellness-consult' ); ?></label></th>
					<td><input type="email" id="block_email" name="block_email" class="regular-text" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><label for="block_phone"><?php esc_html_e( 'Phone number', 'wellness-consult' ); ?></label></th>
					<td><input type="text" id="block_phone" name="block_phone" class="regular-text" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><label for="block_country"><?php esc_html_e( 'Country', 'wellness-consult' ); ?></label></th>
					<td>
						<select id="block_country" name="block_country">
							<option value=""><?php esc_html_e( 'Do not block a country', 'wellness-consult' ); ?></option>
							<?php foreach ( WC_Options::get_all_iso_countries() as $code => $country_name ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $country_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="block_reason"><?php esc_html_e( 'Reason (activity log only)', 'wellness-consult' ); ?></label></th>
					<td><textarea id="block_reason" name="block_reason" rows="2" class="large-text" required></textarea></td>
				</tr>
			</table>
			<p><button type="submit" class="button button-primary" name="wc_block_contacts_submit" value="1"><?php esc_html_e( 'Add Block Rule', 'wellness-consult' ); ?></button></p>
		</form>
		<?php if ( $blocked_contacts ) : ?>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Type', 'wellness-consult' ); ?></th><th><?php esc_html_e( 'Stored value', 'wellness-consult' ); ?></th><th><?php esc_html_e( 'Added', 'wellness-consult' ); ?></th><th><?php esc_html_e( 'Action', 'wellness-consult' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $blocked_contacts as $key => $block ) : ?>
						<tr>
							<td><?php echo esc_html( ucfirst( $block['type'] ?? '' ) ); ?></td>
							<td><code><?php echo esc_html( 'country' === ( $block['type'] ?? '' ) ? $block['value'] : substr( $block['value'] ?? '', 0, 16 ) . '…' ); ?></code></td>
							<td><?php echo esc_html( $block['created_at'] ?? '—' ); ?></td>
							<td>
								<form method="post">
									<?php wp_nonce_field( 'wc_remove_block_action', 'wc_remove_block_nonce' ); ?>
									<input type="hidden" name="block_key" value="<?php echo esc_attr( $key ); ?>">
									<button type="submit" class="button button-secondary" name="wc_remove_block_submit" value="1" onclick="return confirm('<?php echo esc_attr__( 'Remove this booking block rule?', 'wellness-consult' ); ?>');"><?php esc_html_e( 'Remove', 'wellness-consult' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<!-- Delivery Platforms Configuration Card (CR-1) -->
	<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:30px;border-radius:4px;">
		<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
			<span class="dashicons dashicons-networking"></span>
			<?php esc_html_e( 'Consultation Delivery Platforms (Per Mode)', 'wellness-consult' ); ?>
		</h2>
		<p class="description">
			<?php esc_html_e( 'Manage the available communication platforms for each consultation mode. Enable or disable platforms and add custom ones. Per CR-1, no defaults are active until you enable them.', 'wellness-consult' ); ?>
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'wc_save_platforms_action', 'wc_platforms_nonce' ); ?>

			<?php
			$modes_info = array(
				'chat'  => array( 'title' => __( 'Chat Platforms', 'wellness-consult' ), 'icon' => '💬', 'desc' => __( 'Text consultation channels (e.g. WhatsApp, Telegram)', 'wellness-consult' ) ),
				'voice' => array( 'title' => __( 'Voice Call Platforms', 'wellness-consult' ), 'icon' => '📞', 'desc' => __( 'Audio phone/voice channels (e.g. WhatsApp Audio, Direct Phone Call)', 'wellness-consult' ) ),
				'video' => array( 'title' => __( 'Video Call Platforms', 'wellness-consult' ), 'icon' => '📹', 'desc' => __( 'Video conference rooms (e.g. Google Meet, Zoom, WhatsApp Video)', 'wellness-consult' ) ),
			);
			?>

			<?php foreach ( $modes_info as $m_key => $m_data ) :
				$mode_platforms = $platforms[ $m_key ] ?? array();
				?>
				<div style="margin-top:24px; padding-bottom:16px; border-bottom:1px solid #e2e8f0;">
					<h3 style="margin-bottom:6px; display:flex; align-items:center; gap:6px;">
						<span style="font-size:20px;"><?php echo esc_html( $m_data['icon'] ); ?></span>
						<?php echo esc_html( $m_data['title'] ); ?>
					</h3>
					<p class="description" style="margin-top:0; margin-bottom:12px;"><?php echo esc_html( $m_data['desc'] ); ?></p>

					<table class="widefat fixed striped" style="max-width:850px;">
						<thead>
							<tr>
								<th style="width:70px; text-align:center;"><?php esc_html_e( 'Active', 'wellness-consult' ); ?></th>
								<th style="width:200px;"><?php esc_html_e( 'Platform Name', 'wellness-consult' ); ?></th>
								<th><?php esc_html_e( 'Instructions / Note', 'wellness-consult' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $mode_platforms as $pid => $pentry ) : ?>
								<tr>
									<td style="text-align:center;">
										<input type="checkbox" name="platforms[<?php echo esc_attr( $m_key ); ?>][<?php echo esc_attr( $pid ); ?>][is_active]" value="1" <?php checked( ! empty( $pentry['is_active'] ) ); ?> />
									</td>
									<td>
										<input type="text" name="platforms[<?php echo esc_attr( $m_key ); ?>][<?php echo esc_attr( $pid ); ?>][name]" value="<?php echo esc_attr( $pentry['name'] ); ?>" class="regular-text" style="width:100%;" required />
									</td>
									<td>
										<input type="text" name="platforms[<?php echo esc_attr( $m_key ); ?>][<?php echo esc_attr( $pid ); ?>][instructions]" value="<?php echo esc_attr( $pentry['instructions'] ?? '' ); ?>" class="large-text" style="width:100%;" />
									</td>
								</tr>
							<?php endforeach; ?>
							<!-- Add New Row for this mode -->
							<tr style="background:#f8fafc;">
								<td style="text-align:center;">
									<input type="checkbox" name="new_platform[<?php echo esc_attr( $m_key ); ?>][is_active]" value="1" />
								</td>
								<td>
									<input type="text" name="new_platform[<?php echo esc_attr( $m_key ); ?>][name]" placeholder="<?php esc_attr_e( '+ Add New Platform...', 'wellness-consult' ); ?>" class="regular-text" style="width:100%;" />
								</td>
								<td>
									<input type="text" name="new_platform[<?php echo esc_attr( $m_key ); ?>][instructions]" placeholder="<?php esc_attr_e( 'Instructions for consultant/patient...', 'wellness-consult' ); ?>" class="large-text" style="width:100%;" />
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>

			<p class="submit" style="margin-top:20px;">
				<input type="submit" name="submit_platforms" class="button button-primary" value="<?php esc_attr_e( 'Save Consultation Platforms', 'wellness-consult' ); ?>">
			</p>
		</form>
	</div>

	<!-- Export / Import Card -->
	<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:30px;border-radius:4px;">
		<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
			<span class="dashicons dashicons-database-export"></span>
			<?php esc_html_e( 'Settings Backup & Migration (JSON)', 'wellness-consult' ); ?>
		</h2>
		<p class="description">
			<?php esc_html_e( 'Export or import your consultation configuration. Sensitive API credentials and payment secrets are strictly excluded from all export archives.', 'wellness-consult' ); ?>
		</p>

		<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:20px;margin-top:16px;">
			<div style="background:#f8fafc;padding:16px;border:1px solid #e2e8f0;border-radius:4px;">
				<h4 style="margin-top:0;"><?php esc_html_e( 'Export Settings', 'wellness-consult' ); ?></h4>
				<p style="font-size:13px;color:#555;"><?php esc_html_e( 'Download a JSON archive of consultation types, country restrictions, email templates, and general rules.', 'wellness-consult' ); ?></p>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-settings&action=export_settings' ), 'wc_export_settings_nonce' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-download" style="vertical-align:text-top;font-size:16px;"></span> <?php esc_html_e( 'Export Configuration JSON', 'wellness-consult' ); ?>
				</a>
			</div>

			<div style="background:#f8fafc;padding:16px;border:1px solid #e2e8f0;border-radius:4px;">
				<h4 style="margin-top:0;"><?php esc_html_e( 'Import Settings', 'wellness-consult' ); ?></h4>
				<form method="post" action="" enctype="multipart/form-data">
					<?php wp_nonce_field( 'wc_import_settings_action', 'wc_import_nonce' ); ?>
					<input type="file" name="wc_import_file" accept=".json" required style="margin-bottom:10px;display:block;">
					<input type="submit" name="submit_import" class="button button-secondary" value="<?php esc_attr_e( 'Import from JSON', 'wellness-consult' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to overwrite existing settings with this imported file?', 'wellness-consult' ); ?>');">
				</form>
			</div>
		</div>
	</div>
</div>
