<?php
/**
 * Admin Screen: System Activity & Audit Log
 *
 * Implements SRS v2.0 Section 6 Activity Log requirements.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_view_activity_log' ) ) {
	wp_die( esc_html__( 'You do not have permission to view the activity log.', 'wellness-consult' ) );
}

$notice = '';
$notice_type = 'success';

// Clear Log action (admin only)
if ( isset( $_GET['action'] ) && 'clear_logs' === $_GET['action'] && isset( $_GET['_wpnonce'] ) && current_user_can( 'manage_options' ) ) {
	if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wc_clear_activity_logs' ) ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_activity_log';
		$wpdb->query( "TRUNCATE TABLE $table_name" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$notice = esc_html__( 'Activity log cleared successfully.', 'wellness-consult' );

		WC_Database::log( array(
			'action' => 'clear_log',
			'note'   => 'Audit history truncated by administrator.',
		) );
	}
}

// Filter and Pagination parameters
$action_filter = ! empty( $_GET['filter_action'] ) ? sanitize_key( $_GET['filter_action'] ) : '';
$current_page  = max( 1, absint( $_GET['paged'] ?? 1 ) );
$per_page      = 25;
$offset        = ( $current_page - 1 ) * $per_page;

$total_logs = WC_Database::count_logs( $action_filter );
$logs       = WC_Database::get_logs( $per_page, $offset, $action_filter );
$total_pages = ceil( $total_logs / $per_page );
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'System Activity & Audit Log', 'wellness-consult' ); ?></h1>

	<?php if ( current_user_can( 'manage_options' ) && $total_logs > 0 ) : ?>
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-activity-log&action=clear_logs' ), 'wc_clear_activity_logs' ) ); ?>" class="button button-secondary" style="margin-left:10px;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to permanently clear the entire activity log history?', 'wellness-consult' ); ?>');">
			<span class="dashicons dashicons-trash" style="vertical-align:text-top;font-size:16px;"></span> <?php esc_html_e( 'Clear Audit Log', 'wellness-consult' ); ?>
		</a>
	<?php endif; ?>

	<hr class="wp-header-end">

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Filter Bar -->
	<div class="tablenav top" style="margin-top:16px;">
		<form method="get" action="">
			<input type="hidden" name="page" value="wc-activity-log">
			<div class="alignleft actions">
				<select name="filter_action">
					<option value=""><?php esc_html_e( 'All Actions', 'wellness-consult' ); ?></option>
					<option value="status_transition" <?php selected( $action_filter, 'status_transition' ); ?>><?php esc_html_e( 'Status Transition', 'wellness-consult' ); ?></option>
					<option value="save_type" <?php selected( $action_filter, 'save_type' ); ?>><?php esc_html_e( 'Consultation Types', 'wellness-consult' ); ?></option>
					<option value="update_payment_settings" <?php selected( $action_filter, 'update_payment_settings' ); ?>><?php esc_html_e( 'Payment Settings', 'wellness-consult' ); ?></option>
					<option value="save_manual_method" <?php selected( $action_filter, 'save_manual_method' ); ?>><?php esc_html_e( 'Manual Transfer Methods', 'wellness-consult' ); ?></option>
					<option value="update_availability" <?php selected( $action_filter, 'update_availability' ); ?>><?php esc_html_e( 'Availability', 'wellness-consult' ); ?></option>
					<option value="update_countries_policy" <?php selected( $action_filter, 'update_countries_policy' ); ?>><?php esc_html_e( 'Countries Scope', 'wellness-consult' ); ?></option>
					<option value="update_email_template" <?php selected( $action_filter, 'update_email_template' ); ?>><?php esc_html_e( 'Email Templates', 'wellness-consult' ); ?></option>
					<option value="update_general_settings" <?php selected( $action_filter, 'update_general_settings' ); ?>><?php esc_html_e( 'General Settings', 'wellness-consult' ); ?></option>
				</select>
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wellness-consult' ); ?>">
				<?php if ( ! empty( $action_filter ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-activity-log' ) ); ?>" class="button button-link"><?php esc_html_e( 'Reset Filter', 'wellness-consult' ); ?></a>
				<?php endif; ?>
			</div>

			<div class="tablenav-pages">
				<span class="displaying-num"><?php echo esc_html( sprintf( _n( '%s audit entry', '%s audit entries', $total_logs, 'wellness-consult' ), number_format_i18n( $total_logs ) ) ); ?></span>
				<?php if ( $total_pages > 1 ) : ?>
					<span class="pagination-links">
						<?php if ( $current_page > 1 ) : ?>
							<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>">&lsaquo;</a>
						<?php endif; ?>
						<span class="paging-input">
							<span class="current-page"><?php echo esc_html( $current_page ); ?></span>
							<span class="total-pages">of <?php echo esc_html( $total_pages ); ?></span>
						</span>
						<?php if ( $current_page < $total_pages ) : ?>
							<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>">&rsaquo;</a>
						<?php endif; ?>
					</span>
				<?php endif; ?>
			</div>
		</form>
	</div>

	<!-- Audit Log Table -->
	<table class="wp-list-table widefat fixed striped" style="margin-top:8px;">
		<thead>
			<tr>
				<th scope="col" style="width:160px;"><?php esc_html_e( 'Timestamp (UTC)', 'wellness-consult' ); ?></th>
				<th scope="col" style="width:140px;"><?php esc_html_e( 'User', 'wellness-consult' ); ?></th>
				<th scope="col" style="width:160px;"><?php esc_html_e( 'Action', 'wellness-consult' ); ?></th>
				<th scope="col" style="width:130px;"><?php esc_html_e( 'Reference ID', 'wellness-consult' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Details / Transition', 'wellness-consult' ); ?></th>
				<th scope="col" style="width:130px;"><?php esc_html_e( 'IP Address', 'wellness-consult' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $logs ) ) : ?>
				<tr>
					<td colspan="6" style="padding:28px;text-align:center;color:#666;">
						<span class="dashicons dashicons-shield" style="font-size:28px;color:#999;display:block;margin-bottom:8px;"></span>
						<strong><?php esc_html_e( 'No activity log entries recorded yet.', 'wellness-consult' ); ?></strong>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $logs as $entry ) : ?>
					<tr>
						<td><code><?php echo esc_html( $entry['created_at'] ); ?></code></td>
						<td>
							<strong><?php echo esc_html( $entry['user_name'] ); ?></strong>
							<?php if ( ! empty( $entry['user_id'] ) ) : ?>
								<span style="color:#777;font-size:11px;">(#<?php echo esc_html( $entry['user_id'] ); ?>)</span>
							<?php endif; ?>
						</td>
						<td>
							<code style="background:#f1f5f9;padding:2px 6px;border-radius:3px;font-size:11px;color:#0f3b4c;font-weight:600;">
								<?php echo esc_html( $entry['action'] ); ?>
							</code>
						</td>
						<td>
							<?php if ( ! empty( $entry['reference_id'] ) ) : ?>
								<strong><?php echo esc_html( $entry['reference_id'] ); ?></strong>
							<?php else : ?>
								<span style="color:#999;">—</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $entry['from_status'] ) || ! empty( $entry['to_status'] ) ) : ?>
								<div style="margin-bottom:4px;font-size:12px;">
									<span style="background:#e2e8f0;padding:2px 6px;border-radius:3px;"><?php echo esc_html( WC_Status::get_label( $entry['from_status'] ) ); ?></span>
									<span class="dashicons dashicons-arrow-right-alt" style="font-size:14px;line-height:1.2;color:#64748b;vertical-align:text-bottom;"></span>
									<span style="background:#bfdbfe;color:#1e3a8a;font-weight:600;padding:2px 6px;border-radius:3px;"><?php echo esc_html( WC_Status::get_label( $entry['to_status'] ) ); ?></span>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $entry['note'] ) ) : ?>
								<div style="color:#334155;font-size:13px;"><?php echo esc_html( $entry['note'] ); ?></div>
							<?php endif; ?>
						</td>
						<td><code><?php echo esc_html( ! empty( $entry['ip_address'] ) ? $entry['ip_address'] : '127.0.0.1' ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
