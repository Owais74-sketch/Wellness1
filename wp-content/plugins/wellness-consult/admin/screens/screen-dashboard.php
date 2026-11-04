<?php
/**
 * Admin Screen: Dashboard
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Capability check
if ( ! current_user_can( 'wc_manage_requests' ) && ! current_user_can( 'wc_manage_consultations' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this screen.', 'wellness-consult' ) );
}

// Fetch real counts from wc_request posts (no invented numbers)
$total_requests_count = (int) wp_count_posts( 'wc_request' )->publish;

$pending_verification_query = new WP_Query( array(
	'post_type'      => 'wc_request',
	'post_status'    => 'any',
	'posts_per_page' => 1,
	'meta_key'       => '_wc_status',
	'meta_value'     => 'pending_verification',
) );
$pending_verification_count = (int) $pending_verification_query->found_posts;

$scheduled_query = new WP_Query( array(
	'post_type'      => 'wc_request',
	'post_status'    => 'any',
	'posts_per_page' => 1,
	'meta_key'       => '_wc_status',
	'meta_value'     => 'scheduled',
) );
$scheduled_count = (int) $scheduled_query->found_posts;

$types = get_option( 'wc_consultation_types', array() );
$payment_settings = get_option( 'wc_payment_settings', array() );
$is_test_mode = ! empty( $payment_settings['test_mode'] );
?>
<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Consultations Dashboard', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $is_test_mode ) : ?>
		<div class="notice notice-warning inline" style="margin: 15px 0; border-left-color: #f59e0b; padding: 10px 14px;">
			<span class="wc-badge-test-mode" style="background: #f59e0b; color: #fff; font-weight: 700; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; font-size: 11px; margin-right: 8px;">TEST MODE</span>
			<strong><?php esc_html_e( 'Payment Gateway is in Test Mode.', 'wellness-consult' ); ?></strong>
			<span><?php esc_html_e( 'No real customer charges will occur. Configure keys in Payments.', 'wellness-consult' ); ?></span>
		</div>
	<?php endif; ?>

	<?php if ( empty( $types ) && current_user_can( 'wc_manage_types' ) ) : ?>
		<div class="notice notice-info inline" style="margin: 15px 0;">
			<p>
				<strong><?php esc_html_e( 'Getting Started:', 'wellness-consult' ); ?></strong>
				<?php esc_html_e( 'No consultation types have been created yet.', 'wellness-consult' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-types&action=new' ) ); ?>" class="button button-primary" style="margin-left: 8px;">
					<?php esc_html_e( 'Add your first consultation type', 'wellness-consult' ); ?> &rarr;
				</a>
			</p>
		</div>
	<?php endif; ?>

	<!-- Real Metrics Grid -->
	<div class="wc-dashboard-metrics" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin: 20px 0;">
		<div class="postbox" style="padding: 16px; margin: 0;">
			<div class="wc-metric-label" style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Total Requests', 'wellness-consult' ); ?></div>
			<div class="wc-metric-value" style="font-size: 32px; font-weight: 800; color: #0f3b4c; margin-top: 6px;"><?php echo esc_html( $total_requests_count ); ?></div>
			<div style="font-size: 12px; color: #94a3b8; margin-top: 4px;"><?php esc_html_e( 'Lifetime submitted requests', 'wellness-consult' ); ?></div>
		</div>

		<div class="postbox" style="padding: 16px; margin: 0;">
			<div class="wc-metric-label" style="font-size: 13px; color: #b45309; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Pending Verification', 'wellness-consult' ); ?></div>
			<div class="wc-metric-value" style="font-size: 32px; font-weight: 800; color: #b45309; margin-top: 6px;"><?php echo esc_html( $pending_verification_count ); ?></div>
			<div style="font-size: 12px; color: #94a3b8; margin-top: 4px;"><?php esc_html_e( 'Manual transfers needing review', 'wellness-consult' ); ?></div>
		</div>

		<div class="postbox" style="padding: 16px; margin: 0;">
			<div class="wc-metric-label" style="font-size: 13px; color: #0369a1; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Scheduled', 'wellness-consult' ); ?></div>
			<div class="wc-metric-value" style="font-size: 32px; font-weight: 800; color: #0369a1; margin-top: 6px;"><?php echo esc_html( $scheduled_count ); ?></div>
			<div style="font-size: 12px; color: #94a3b8; margin-top: 4px;"><?php esc_html_e( 'Upcoming booked sessions', 'wellness-consult' ); ?></div>
		</div>

		<div class="postbox" style="padding: 16px; margin: 0;">
			<div class="wc-metric-label" style="font-size: 13px; color: #15803d; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Active Types', 'wellness-consult' ); ?></div>
			<div class="wc-metric-value" style="font-size: 32px; font-weight: 800; color: #15803d; margin-top: 6px;"><?php echo esc_html( count( $types ) ); ?></div>
			<div style="font-size: 12px; color: #94a3b8; margin-top: 4px;"><?php esc_html_e( 'Consultation offerings', 'wellness-consult' ); ?></div>
		</div>
	</div>

	<!-- Quick Action Shortcuts -->
	<div class="postbox" style="padding: 16px; margin-bottom: 20px;">
		<h2 class="hndle" style="padding: 0 0 10px; margin: 0; font-size: 16px;"><?php esc_html_e( 'Quick Workflow Shortcuts', 'wellness-consult' ); ?></h2>
		<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'View All Requests', 'wellness-consult' ); ?>
			</a>
			<?php if ( current_user_can( 'wc_manage_types' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-types' ) ); ?>" class="button">
					<?php esc_html_e( 'Manage Consultation Types', 'wellness-consult' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( current_user_can( 'wc_manage_payments' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-payments' ) ); ?>" class="button">
					<?php esc_html_e( 'Payment Gateway & Accounts', 'wellness-consult' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( current_user_can( 'wc_manage_availability' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-availability' ) ); ?>" class="button">
					<?php esc_html_e( 'Consultant Availability', 'wellness-consult' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Recent Activity Stream -->
	<div class="postbox" style="padding: 16px;">
		<h2 class="hndle" style="padding: 0 0 10px; margin: 0; font-size: 16px;"><?php esc_html_e( 'Recent System Activity', 'wellness-consult' ); ?></h2>
		<?php
		$recent_logs = WC_Database::get_logs( 5 );
		if ( ! empty( $recent_logs ) ) :
			?>
			<table class="widefat striped" style="margin-top: 10px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time (UTC)', 'wellness-consult' ); ?></th>
						<th><?php esc_html_e( 'User', 'wellness-consult' ); ?></th>
						<th><?php esc_html_e( 'Action', 'wellness-consult' ); ?></th>
						<th><?php esc_html_e( 'Reference ID', 'wellness-consult' ); ?></th>
						<th><?php esc_html_e( 'Note', 'wellness-consult' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_logs as $log ) : ?>
						<tr>
							<td><code><?php echo esc_html( $log['created_at'] ); ?></code></td>
							<td><?php echo esc_html( $log['user_name'] ); ?></td>
							<td><span class="badge" style="font-weight:600;"><?php echo esc_html( $log['action'] ); ?></span></td>
							<td><strong><?php echo esc_html( $log['reference_id'] ? $log['reference_id'] : '—' ); ?></strong></td>
							<td><?php echo esc_html( $log['note'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p style="margin-top: 10px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-activity-log' ) ); ?>"><?php esc_html_e( 'View Full Activity Log', 'wellness-consult' ); ?> &rarr;</a>
			</p>
		<?php else : ?>
			<p class="description" style="margin-top: 10px;"><?php esc_html_e( 'No activity logged yet.', 'wellness-consult' ); ?></p>
		<?php endif; ?>
	</div>
</div>
