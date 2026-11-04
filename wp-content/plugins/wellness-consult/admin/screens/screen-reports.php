<?php
/**
 * Admin Screen: Reports & Financial Analytics
 *
 * Placeholder for Phase 9 implementation per SRS v2.0 Section 6 and Build Plan.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_view_reports' ) ) {
	wp_die( esc_html__( 'You do not have permission to view consultation reports.', 'wellness-consult' ) );
}
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Consultation Reports & Analytics', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:32px;margin-top:20px;border-radius:6px;max-width:800px;text-align:center;">
		<span class="dashicons dashicons-chart-bar" style="font-size:48px;width:48px;height:48px;color:#2a838e;margin-bottom:12px;"></span>
		<h2 style="margin:8px 0 12px 0;font-size:20px;color:#0f3b4c;">
			<?php esc_html_e( 'Coming in Phase 9 - Reports & Financial Analytics', 'wellness-consult' ); ?>
		</h2>
		<p style="font-size:14px;line-height:1.6;color:#555;max-width:620px;margin:0 auto 20px auto;">
			<?php esc_html_e( 'Per SRS v2.0 Section 6, this reporting hub provides comprehensive aggregate metrics and analytics across consultations, revenue in USD, completion rates, cancellation logs, geographic distribution, and date-range CSV exports.', 'wellness-consult' ); ?>
		</p>

		<div style="background:#f8fafc;border:1px solid #e2e8f0;padding:16px;border-radius:4px;text-align:left;display:inline-block;max-width:540px;">
			<h4 style="margin:0 0 8px 0;color:#0f3b4c;"><?php esc_html_e( 'Planned Report Features:', 'wellness-consult' ); ?></h4>
			<ul style="margin:0;padding-left:20px;list-style:disc;color:#475569;font-size:13px;line-height:1.8;">
				<li><?php esc_html_e( 'Total requests & revenue breakdown (Gross USD, refunds, net income)', 'wellness-consult' ); ?></li>
				<li><?php esc_html_e( 'Consultation completion vs cancellation vs no-show percentages', 'wellness-consult' ); ?></li>
				<li><?php esc_html_e( 'Breakdown by country of origin & preferred consultation category', 'wellness-consult' ); ?></li>
				<li><?php esc_html_e( 'One-click CSV data export for tax compliance and accounting records', 'wellness-consult' ); ?></li>
			</ul>
		</div>
	</div>
</div>
