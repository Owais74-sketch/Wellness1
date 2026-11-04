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

$default_templates = WC_Options::get_default_email_templates();
$saved_templates = get_option( 'wc_email_templates', array() );
$templates = array_merge( $default_templates, is_array( $saved_templates ) ? $saved_templates : array() );
foreach ( $default_templates as $template_key => $default_template ) {
	$templates[ $template_key ]['label'] = $default_template['label'];
}

// Active tab selection
$requested_event = isset( $_GET['event'] ) && is_string( $_GET['event'] ) ? sanitize_key( wp_unslash( $_GET['event'] ) ) : '';
$active_event = isset( $templates[ $requested_event ] ) ? $requested_event : 'request_submitted';
$notice_code = isset( $_GET['notice'] ) && is_string( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';
$notice_messages = array(
	'saved'          => array( 'success', __( 'Email template saved successfully.', 'wellness-consult' ) ),
	'reset'          => array( 'success', __( 'Email template reset to its default.', 'wellness-consult' ) ),
	'test_sent'      => array( 'success', __( 'Test email sent to your administrator address.', 'wellness-consult' ) ),
	'test_failed'    => array( 'error', __( 'The test email could not be sent. Check the site email configuration and try again.', 'wellness-consult' ) ),
	'rate_limited'   => array( 'error', __( 'Test email limit reached. You can send up to 5 test emails per hour.', 'wellness-consult' ) ),
	'empty_template' => array( 'error', __( 'Email subject and body cannot be empty.', 'wellness-consult' ) ),
	'invalid_event'  => array( 'error', __( 'The requested email template was not found.', 'wellness-consult' ) ),
);
if ( isset( $notice_messages[ $notice_code ] ) ) {
	$notice_type = $notice_messages[ $notice_code ][0];
	$notice = $notice_messages[ $notice_code ][1];
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

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wc_save_email_template">
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
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wc_reset_email_template', 'event_key' => $active_event ), admin_url( 'admin-post.php' ) ), 'wc_reset_email_' . $active_event ) ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Reset this template to the default text?', 'wellness-consult' ); ?>');">
							<?php esc_html_e( 'Reset to Default', 'wellness-consult' ); ?>
						</a>
					</div>
				</form>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
						<input type="hidden" name="action" value="wc_test_email_template">
						<input type="hidden" name="event_key" value="<?php echo esc_attr( $active_event ); ?>">
						<?php wp_nonce_field( 'wc_test_email_' . $active_event, 'wc_test_email_nonce' ); ?>
						<button type="submit" class="button button-secondary"><?php esc_html_e( 'Send test email to my admin address', 'wellness-consult' ); ?></button>
						<p class="description"><?php esc_html_e( 'Sends the currently saved template to your own administrator email. Unsaved edits are not included.', 'wellness-consult' ); ?></p>
					</form>
				<?php endif; ?>
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

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{site_name}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Site name used in the email sign-off', 'wellness-consult' ); ?></dd>

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

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{consult_mode}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Chat, voice, or video consultation mode', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{platform}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Selected consultation platform', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{appointment_local}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Appointment in the patient time zone, including its zone name', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{appointment_consultant}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Appointment in the consultant time zone, including its zone name', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{meeting_link}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Meeting URL or contact detail; included only in scheduled and rescheduled emails', 'wellness-consult' ); ?></dd>

					<dt style="font-family:monospace;font-weight:700;color:#0f3b4c;">{reason}</dt>
					<dd style="margin:0 0 8px 0;color:#475569;"><?php esc_html_e( 'Cancellation or action reason when included by the notification', 'wellness-consult' ); ?></dd>
				</dl>
			</div>
		</div>
	</div>
</div>
