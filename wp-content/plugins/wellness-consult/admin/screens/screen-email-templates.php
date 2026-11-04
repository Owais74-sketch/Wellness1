<?php
/**
 * Admin Screen: Notification Email Templates
 *
 * Configures notification templates for all consultation lifecycle events.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_manage_email_templates' ) ) {
	wp_die( esc_html__( 'You do not have permission to manage email templates.', 'wellness-consult' ) );
}

$notice = '';
$notice_type = 'success';

$templates = get_option( 'wc_email_templates', WC_Options::get_default_email_templates() );
$default_templates = WC_Options::get_default_email_templates();

// Active tab selection
$active_event = isset( $_GET['event'] ) && isset( $templates[ $_GET['event'] ] ) ? sanitize_key( $_GET['event'] ) : 'request_submitted';

// Save Template Handler
if ( isset( $_POST['wc_email_save_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wc_email_save_nonce'] ), 'wc_save_email_action' ) ) {
	$event_key = sanitize_key( $_POST['event_key'] ?? '' );
	if ( isset( $templates[ $event_key ] ) ) {
		$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
		$body    = sanitize_textarea_field( wp_unslash( $_POST['body'] ?? '' ) );

		if ( empty( $subject ) || empty( $body ) ) {
			$notice = esc_html__( 'Email subject and body cannot be empty.', 'wellness-consult' );
			$notice_type = 'error';
		} else {
			$templates[ $event_key ]['subject'] = $subject;
			$templates[ $event_key ]['body']    = $body;

			update_option( 'wc_email_templates', $templates );
			$notice = sprintf( esc_html__( 'Email template for "%s" saved successfully.', 'wellness-consult' ), esc_html( $templates[ $event_key ]['label'] ) );

			WC_Database::log( array(
				'action' => 'update_email_template',
				'note'   => sprintf( 'Email template updated for event: %s.', $event_key ),
			) );
		}
	}
}

// Reset Template Handler
if ( isset( $_GET['action'] ) && 'reset_template' === $_GET['action'] && ! empty( $_GET['event_key'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wc_reset_email_' . $_GET['event_key'] ) ) {
		$r_key = sanitize_key( $_GET['event_key'] );
		if ( isset( $default_templates[ $r_key ] ) ) {
			$templates[ $r_key ] = $default_templates[ $r_key ];
			update_option( 'wc_email_templates', $templates );
			$notice = sprintf( esc_html__( 'Email template for "%s" has been reset to system default.', 'wellness-consult' ), esc_html( $default_templates[ $r_key ]['label'] ) );
		}
	}
}

$current_template = $templates[ $active_event ] ?? $default_templates['request_submitted'];
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Notification Email Templates', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<div style="margin-top:16px;">
		<!-- Event Navigation Tabs -->
		<h2 class="nav-tab-wrapper" style="margin-bottom:0;">
			<?php foreach ( $templates as $key => $data ) :
				$is_active = ( $key === $active_event );
			?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-email-templates&event=' . $key ) ); ?>" class="nav-tab <?php echo $is_active ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $data['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</h2>

		<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;border-top:none;padding:24px;border-radius:0 0 4px 4px;display:grid;grid-template-columns:1fr 320px;gap:24px;">
			<!-- Editor Column -->
			<div>
				<h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
					<span class="dashicons dashicons-email-alt"></span>
					<?php echo esc_html( $current_template['label'] ); ?>
				</h3>
				<p class="description" style="margin-bottom:18px;">
					<?php esc_html_e( 'Dispatched automatically when this consultation milestone is reached.', 'wellness-consult' ); ?>
				</p>

				<form method="post" action="">
					<?php wp_nonce_field( 'wc_save_email_action', 'wc_email_save_nonce' ); ?>
					<input type="hidden" name="event_key" value="<?php echo esc_attr( $active_event ); ?>">

					<div style="margin-bottom:16px;">
						<label for="email_subject" style="font-weight:600;display:block;margin-bottom:6px;"><?php esc_html_e( 'Email Subject Line *', 'wellness-consult' ); ?></label>
						<input type="text" id="email_subject" name="subject" value="<?php echo esc_attr( $current_template['subject'] ); ?>" class="large-text" required>
					</div>

					<div style="margin-bottom:16px;">
						<label for="email_body" style="font-weight:600;display:block;margin-bottom:6px;"><?php esc_html_e( 'Email Message Body *', 'wellness-consult' ); ?></label>
						<textarea id="email_body" name="body" rows="12" class="large-text code" style="line-height:1.6;" required><?php echo esc_textarea( $current_template['body'] ); ?></textarea>
					</div>

					<div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;">
						<input type="submit" name="submit_email" class="button button-primary" value="<?php esc_attr_e( 'Save Template', 'wellness-consult' ); ?>">
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-email-templates&action=reset_template&event_key=' . $active_event ), 'wc_reset_email_' . $active_event ) ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Reset this template to the default text?', 'wellness-consult' ); ?>');">
							<?php esc_html_e( 'Reset to Default', 'wellness-consult' ); ?>
						</a>
					</div>
				</form>
			</div>

			<!-- Dynamic Placeholders Column -->
			<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:16px;">
				<h4 style="margin-top:0;color:#0f3b4c;display:flex;align-items:center;gap:6px;">
					<span class="dashicons dashicons-tag"></span>
					<?php esc_html_e( 'Available Placeholders', 'wellness-consult' ); ?>
				</h4>
				<p style="font-size:12px;color:#64748b;margin-bottom:12px;">
					<?php esc_html_e( 'Insert any of these tags into your subject or body. They will be substituted with live patient data upon delivery:', 'wellness-consult' ); ?>
				</p>

				<dl style="font-size:12px;margin:0;line-height:1.5;">
					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{name}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Patient alias or given name', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{reference_id}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Tracking ID (e.g. WC-2026-00123)', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{consultation_type}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Consultation package name', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{fee_usd}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Fee amount (e.g. $45 USD)', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{track_url}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Direct Track My Request link', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{rejection_reason}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Admin reason if screenshot is rejected', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{date_time_patient}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Appointment in patient local time zone', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{date_time_consultant}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Appointment in consultant time zone', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{mode}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Google Meet, Zoom, WhatsApp, etc.', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{meeting_link}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Secure meeting invitation link', 'wellness-consult' ); ?></dd>
				</dl>
			</div>
		</div>
	</div>
</div>
