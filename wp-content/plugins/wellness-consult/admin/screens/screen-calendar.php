<?php
/**
 * Admin Screen: Calendar
 *
 * Implements SRS v2.0 Section 6 Calendar (Scheduled for Phase 6).
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Capability check
if ( ! current_user_can( 'wc_view_calendar' ) && ! current_user_can( 'wc_manage_consultations' ) ) {
	wp_die( esc_html__( 'You do not have permission to view the calendar.', 'wellness-consult' ) );
}
?>
<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Appointment Calendar', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<div class="card" style="padding: 40px 20px; text-align: center; max-width: 650px; margin: 40px auto; border-top: 4px solid #2a838e;">
		<div style="font-size: 40px; margin-bottom: 15px;" aria-hidden="true">📅</div>
		<span class="badge" style="background: #e6f6f7; color: #2a838e; font-weight: 700; padding: 4px 10px; border-radius: 999px; text-transform: uppercase; font-size: 11px;">
			<?php esc_html_e( 'Scheduled Feature', 'wellness-consult' ); ?>
		</span>
		<h2 style="margin: 15px 0 10px; color: #0f3b4c;"><?php esc_html_e( 'Calendar & Scheduling Screen Coming in Phase 6', 'wellness-consult' ); ?></h2>
		<p class="description" style="font-size: 14px; line-height: 1.6; max-width: 520px; margin: 0 auto 20px; color: #475569;">
			<?php esc_html_e( 'In accordance with the SRS v2.0 Build Plan (Section 11), interactive Day and Week calendar views, consultant slot allocation, and real-time double-booking prevention will be delivered in Phase 6.', 'wellness-consult' ); ?>
		</p>
		<div style="display: flex; justify-content: center; gap: 10px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-dashboard' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Return to Dashboard', 'wellness-consult' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-availability' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Configure Working Hours in Availability', 'wellness-consult' ); ?>
			</a>
		</div>
	</div>
</div>
