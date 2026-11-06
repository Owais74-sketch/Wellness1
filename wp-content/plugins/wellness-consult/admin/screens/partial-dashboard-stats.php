<?php
/**
 * Dashboard extras: new requests, today's appointments, weekly income, questions.
 * Included from screen-dashboard.php.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

$wc_tz        = wp_timezone();
$wc_now       = time();
$wc_today     = new DateTime( 'today', $wc_tz );
$wc_today_end = new DateTime( 'today 23:59:59', $wc_tz );
$wc_new_7d    = count( WC_Reports::rows_created_between( $wc_now - 7 * DAY_IN_SECONDS, $wc_now ) );
$wc_appts     = WC_Reports::appointments_between( $wc_today->getTimestamp(), $wc_today_end->getTimestamp() );
$wc_can_money = current_user_can( 'wc_view_reports' );
$wc_week_inc  = $wc_can_money ? WC_Reports::income_paid_between( $wc_now - 7 * DAY_IN_SECONDS, $wc_now ) : 0;
$wc_pending_q = class_exists( 'WC_Questions' ) ? WC_Questions::count_by_status( 'pending' ) : 0;
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px;margin:20px 0;">
	<div class="postbox" style="padding:16px;margin:0;">
		<div style="font-size:13px;color:#64748b;font-weight:600;text-transform:uppercase;"><?php esc_html_e( 'New requests (7 days)', 'wellness-consult' ); ?></div>
		<div style="font-size:32px;font-weight:800;color:#0f3b4c;margin-top:6px;"><?php echo esc_html( $wc_new_7d ); ?></div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests' ) ); ?>"><?php esc_html_e( 'Open all requests', 'wellness-consult' ); ?></a>
	</div>

	<div class="postbox" style="padding:16px;margin:0;">
		<div style="font-size:13px;color:#64748b;font-weight:600;text-transform:uppercase;"><?php esc_html_e( "Today's appointments", 'wellness-consult' ); ?></div>
		<div style="font-size:32px;font-weight:800;color:#0369a1;margin-top:6px;"><?php echo esc_html( count( $wc_appts ) ); ?></div>
		<?php foreach ( array_slice( $wc_appts, 0, 5 ) as $wc_a ) : ?>
			<div style="font-size:12px;color:#475569;margin-top:2px;">
				<?php echo esc_html( wp_date( 'H:i', $wc_a['appt_ts'] ) . ' - ' . $wc_a['reference'] ); ?>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $wc_can_money ) : ?>
		<div class="postbox" style="padding:16px;margin:0;">
			<div style="font-size:13px;color:#64748b;font-weight:600;text-transform:uppercase;"><?php esc_html_e( 'Net income (last 7 days)', 'wellness-consult' ); ?></div>
			<div style="font-size:26px;font-weight:800;color:#15803d;margin-top:10px;"><?php echo esc_html( WC_Reports::money( $wc_week_inc ) ); ?></div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-reports' ) ); ?>"><?php esc_html_e( 'Open reports', 'wellness-consult' ); ?></a>
		</div>
	<?php endif; ?>

	<div class="postbox" style="padding:16px;margin:0;">
		<div style="font-size:13px;color:#64748b;font-weight:600;text-transform:uppercase;"><?php esc_html_e( 'Questions waiting', 'wellness-consult' ); ?></div>
		<div style="font-size:32px;font-weight:800;color:#b45309;margin-top:6px;"><?php echo esc_html( $wc_pending_q ); ?></div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-questions' ) ); ?>"><?php esc_html_e( 'Open the queue', 'wellness-consult' ); ?></a>
	</div>
</div>
