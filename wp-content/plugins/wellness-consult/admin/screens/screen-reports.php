<?php
/**
 * Admin Screen: Reports (SRS v2.0 Section 6, Phase 9)
 *
 * Requests created in a date range: counts, income in USD, by status,
 * by country and by consultation type, plus CSV export.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_view_reports' ) ) {
	wp_die( esc_html__( 'You do not have permission to view consultation reports.', 'wellness-consult' ) );
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$range = WC_Reports::parse_range(
	isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '',
	isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : ''
);
// phpcs:enable

$rows     = WC_Reports::rows_created_between( $range['start'], $range['end'] );
$sum      = WC_Reports::summarise( $rows );
$statuses = WC_Status::get_all_statuses();
$count    = function ( $key ) use ( $sum ) {
	return isset( $sum['by_status'][ $key ] ) ? (int) $sum['by_status'][ $key ] : 0;
};
$pct      = function ( $n ) use ( $sum ) {
	return $sum['total'] > 0 ? round( $n / $sum['total'] * 100 ) . '%' : '0%';
};

$export_url = wp_nonce_url(
	add_query_arg(
		array( 'action' => 'wc_export_report', 'from' => $range['from'], 'to' => $range['to'] ),
		admin_url( 'admin-post.php' )
	),
	'wc_export_report'
);

$card = function ( $label, $value, $note = '', $color = '#0f3b4c' ) {
	?>
	<div class="postbox" style="padding:16px;margin:0;">
		<div style="font-size:13px;color:#64748b;font-weight:600;text-transform:uppercase;"><?php echo esc_html( $label ); ?></div>
		<div style="font-size:28px;font-weight:800;color:<?php echo esc_attr( $color ); ?>;margin-top:6px;"><?php echo esc_html( $value ); ?></div>
		<?php if ( $note ) : ?>
			<div style="font-size:12px;color:#94a3b8;margin-top:4px;"><?php echo esc_html( $note ); ?></div>
		<?php endif; ?>
	</div>
	<?php
};
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Reports', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<form method="get" style="margin:16px 0;display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
		<input type="hidden" name="page" value="wc-reports">
		<label>
			<?php esc_html_e( 'From', 'wellness-consult' ); ?><br>
			<input type="date" name="from" value="<?php echo esc_attr( $range['from'] ); ?>">
		</label>
		<label>
			<?php esc_html_e( 'To', 'wellness-consult' ); ?><br>
			<input type="date" name="to" value="<?php echo esc_attr( $range['to'] ); ?>">
		</label>
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply', 'wellness-consult' ); ?></button>
		<a href="<?php echo esc_url( $export_url ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'wellness-consult' ); ?></a>
	</form>

	<p class="description" style="max-width:760px;">
		<?php esc_html_e( 'This report covers requests created in the date range above. Income is counted on those requests, in USD. The CSV has no names, emails or phone numbers.', 'wellness-consult' ); ?>
	</p>

	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin:20px 0;">
		<?php
		$card( __( 'Requests', 'wellness-consult' ), (string) $sum['total'] );
		$card( __( 'Income received', 'wellness-consult' ), WC_Reports::money( $sum['gross'] ), '', '#15803d' );
		$card( __( 'Refunds', 'wellness-consult' ), WC_Reports::money( $sum['refunds'] ), '', '#b45309' );
		$card( __( 'Net income', 'wellness-consult' ), WC_Reports::money( $sum['net'] ), '', '#0369a1' );
		$card( __( 'Completed', 'wellness-consult' ), (string) $count( 'completed' ), $pct( $count( 'completed' ) ) . ' ' . __( 'of requests', 'wellness-consult' ) );
		$card( __( 'Cancelled', 'wellness-consult' ), (string) $count( 'cancelled' ), $pct( $count( 'cancelled' ) ) . ' ' . __( 'of requests', 'wellness-consult' ) );
		$card( __( 'No-show', 'wellness-consult' ), (string) $count( 'no_show' ), $pct( $count( 'no_show' ) ) . ' ' . __( 'of requests', 'wellness-consult' ) );
		?>
	</div>

	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;align-items:start;">

		<div>
			<h2><?php esc_html_e( 'By status', 'wellness-consult' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Status', 'wellness-consult' ); ?></th><th style="width:70px;"><?php esc_html_e( 'Count', 'wellness-consult' ); ?></th></tr></thead>
				<tbody>
					<?php if ( ! $sum['by_status'] ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No requests in this range.', 'wellness-consult' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $sum['by_status'] as $key => $n ) : ?>
						<tr>
							<td><?php echo esc_html( isset( $statuses[ $key ] ) ? wp_strip_all_tags( $statuses[ $key ] ) : $key ); ?></td>
							<td><?php echo esc_html( $n ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div>
			<h2><?php esc_html_e( 'By country', 'wellness-consult' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Country', 'wellness-consult' ); ?></th><th style="width:70px;"><?php esc_html_e( 'Requests', 'wellness-consult' ); ?></th><th style="width:130px;"><?php esc_html_e( 'Net income', 'wellness-consult' ); ?></th></tr></thead>
				<tbody>
					<?php if ( ! $sum['by_country'] ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'No requests in this range.', 'wellness-consult' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $sum['by_country'] as $code => $d ) : ?>
						<tr>
							<td><?php echo esc_html( WC_Reports::country_name( $code ) ); ?></td>
							<td><?php echo esc_html( $d['count'] ); ?></td>
							<td><?php echo esc_html( WC_Reports::money( $d['net'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div>
			<h2><?php esc_html_e( 'By consultation type', 'wellness-consult' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Type', 'wellness-consult' ); ?></th><th style="width:70px;"><?php esc_html_e( 'Requests', 'wellness-consult' ); ?></th><th style="width:130px;"><?php esc_html_e( 'Net income', 'wellness-consult' ); ?></th></tr></thead>
				<tbody>
					<?php if ( ! $sum['by_type'] ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'No requests in this range.', 'wellness-consult' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $sum['by_type'] as $name => $d ) : ?>
						<tr>
							<td><?php echo esc_html( $name ); ?></td>
							<td><?php echo esc_html( $d['count'] ); ?></td>
							<td><?php echo esc_html( WC_Reports::money( $d['net'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
