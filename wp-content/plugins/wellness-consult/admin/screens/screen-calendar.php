<?php
/**
 * Admin Screen: Calendar
 *
 * Week and Day views of scheduled consultations, shown in the selected
 * consultant's time zone (or the site time zone when viewing everyone).
 * Admins can filter by consultant; consultants only ever see their own.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_view_calendar' ) && ! current_user_can( 'wc_manage_consultations' ) ) {
	wp_die( esc_html__( 'You do not have permission to view the calendar.', 'wellness-consult' ) );
}

$current_user_id = get_current_user_id();
$is_admin        = current_user_can( 'wc_manage_all_requests' ) || current_user_can( 'administrator' );
$can_verify      = current_user_can( 'wc_manage_requests' );

/* ---------------------------------------------------------------
 * Inputs
 * ------------------------------------------------------------- */
$view = isset( $_GET['view'] ) && is_string( $_GET['view'] ) && 'day' === sanitize_key( wp_unslash( $_GET['view'] ) ) ? 'day' : 'week';

$consultants = $is_admin ? get_users( array(
	'role__in' => array( 'wc_consultant', 'administrator' ),
	'orderby'  => 'display_name',
	'order'    => 'ASC',
) ) : array();

$consultant_names = array();
foreach ( $consultants as $consultant ) {
	$consultant_names[ absint( $consultant->ID ) ] = $consultant->display_name;
}

if ( $is_admin ) {
	$consultant_id = isset( $_GET['consultant'] ) ? absint( $_GET['consultant'] ) : 0;
	if ( $consultant_id && ! isset( $consultant_names[ $consultant_id ] ) ) {
		$consultant_id = 0;
	}
} else {
	$consultant_id = $current_user_id;
}

// Display time zone.
$tz = wp_timezone();
if ( $consultant_id ) {
	$consultant_availability = WC_Scheduling::get_availability( $consultant_id );
	// Same fallback as the scheduling screen: UTC when no base time zone is saved.
	$consultant_tz_name      = $consultant_availability['base_timezone'] ?? 'UTC';
	$tz                      = new DateTimeZone( 'UTC' );
	if ( is_string( $consultant_tz_name ) && in_array( $consultant_tz_name, timezone_identifiers_list(), true ) ) {
		$tz = new DateTimeZone( $consultant_tz_name );
	}
} else {
	$consultant_availability = array();
}
$utc = new DateTimeZone( 'UTC' );

// Anchor date.
$today    = new DateTimeImmutable( 'today', $tz );
$anchor   = $today;
$date_raw = isset( $_GET['date'] ) && is_string( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : '';
if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_raw ) ) {
	$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $date_raw, $tz );
	if ( $parsed && $parsed->format( 'Y-m-d' ) === $date_raw ) {
		$anchor = $parsed;
	}
}

if ( 'week' === $view ) {
	$range_start = $anchor->modify( '-' . ( (int) $anchor->format( 'N' ) - 1 ) . ' days' );
	$range_end   = $range_start->modify( '+7 days' );
	$prev_date   = $range_start->modify( '-7 days' );
	$next_date   = $range_start->modify( '+7 days' );
} else {
	$range_start = $anchor;
	$range_end   = $anchor->modify( '+1 day' );
	$prev_date   = $anchor->modify( '-1 day' );
	$next_date   = $anchor->modify( '+1 day' );
}

/**
 * Build a calendar URL that keeps the current filters.
 *
 * @param array $args Overrides.
 * @return string
 */
$cal_url = function ( $args = array() ) use ( $view, $anchor, $consultant_id, $is_admin ) {
	$base = array(
		'page' => 'wc-calendar',
		'view' => $view,
		'date' => $anchor->format( 'Y-m-d' ),
	);
	if ( $is_admin && $consultant_id ) {
		$base['consultant'] = $consultant_id;
	}
	return add_query_arg( array_merge( $base, $args ), admin_url( 'admin.php' ) );
};

/* ---------------------------------------------------------------
 * Load appointments in range
 * ------------------------------------------------------------- */
$start_utc_str = $range_start->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
$end_utc_str   = $range_end->setTimezone( $utc )->format( 'Y-m-d H:i:s' );

$meta_query = array(
	'relation' => 'AND',
	array(
		'key'     => '_wc_status',
		'value'   => array( WC_Status::STATUS_SCHEDULED, WC_Status::STATUS_COMPLETED, WC_Status::STATUS_NO_SHOW ),
		'compare' => 'IN',
	),
	array(
		'relation' => 'OR',
		array(
			'key'     => '_wc_appointment_utc',
			'value'   => array( $start_utc_str, $end_utc_str ),
			'compare' => 'BETWEEN',
			'type'    => 'CHAR',
		),
		array(
			'key'     => '_wc_appointment_utc',
			'compare' => 'NOT EXISTS',
		),
	),
);
if ( $consultant_id ) {
	$meta_query[] = array( 'key' => '_wc_consultant_id', 'value' => $consultant_id );
}

$request_ids = get_posts( array(
	'post_type'      => 'wc_request',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
	'meta_query'     => $meta_query,
) );

$appointments = array();
foreach ( $request_ids as $request_id ) {
	$request_id = absint( $request_id );
	$utc_value  = WC_Scheduling::get_request_utc( $request_id );
	$start_obj  = $utc_value ? DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $utc_value, $utc ) : false;
	if ( ! $start_obj || $start_obj < $range_start || $start_obj >= $range_end ) {
		continue;
	}
	$duration = absint( get_post_meta( $request_id, '_wc_duration', true ) );
	$duration = $duration ? $duration : 30;
	$local    = $start_obj->setTimezone( $tz );
	$owner_id = absint( get_post_meta( $request_id, '_wc_consultant_id', true ) );
	if ( $owner_id && ! isset( $consultant_names[ $owner_id ] ) ) {
		$owner = get_userdata( $owner_id );
		$consultant_names[ $owner_id ] = $owner ? $owner->display_name : '';
	}
	$appointments[] = array(
		'id'         => $request_id,
		'start'      => $local,
		'end'        => $local->modify( '+' . $duration . ' minutes' ),
		'name'       => (string) get_post_meta( $request_id, '_wc_name', true ),
		'ref'        => (string) get_post_meta( $request_id, '_wc_reference_id', true ),
		'mode'       => (string) get_post_meta( $request_id, '_wc_consult_mode', true ),
		'platform'   => (string) get_post_meta( $request_id, '_wc_platform', true ),
		'status'     => (string) get_post_meta( $request_id, '_wc_status', true ),
		'consultant' => $owner_id ? ( $consultant_names[ $owner_id ] ?? '' ) : '',
	);
}
usort( $appointments, function ( $a, $b ) {
	return $a['start'] <=> $b['start'];
} );

$by_day = array();
foreach ( $appointments as $appointment ) {
	$by_day[ $appointment['start']->format( 'Y-m-d' ) ][] = $appointment;
}

// Requests waiting for a time (only for people who can schedule).
$awaiting_count = 0;
if ( $can_verify ) {
	$awaiting_count = count( get_posts( array(
		'post_type'      => 'wc_request',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array(
			array( 'key' => '_wc_status', 'value' => WC_Status::STATUS_PAID_AWAITING_SCHEDULING ),
		),
	) ) );
}

/**
 * Working-hours caption for one day (only shown for a single consultant).
 *
 * @param DateTimeImmutable $day Local day.
 * @return string
 */
$hours_caption = function ( $day ) use ( $consultant_id, $consultant_availability ) {
	if ( ! $consultant_id ) {
		return '';
	}
	$keys    = array( 'Mon' => 'mon', 'Tue' => 'tue', 'Wed' => 'wed', 'Thu' => 'thu', 'Fri' => 'fri', 'Sat' => 'sat', 'Sun' => 'sun' );
	$key     = $keys[ $day->format( 'D' ) ];
	$weekend = in_array( $key, array( 'sat', 'sun' ), true );
	$blocked = isset( $consultant_availability['blocked_dates'] ) && is_array( $consultant_availability['blocked_dates'] ) ? $consultant_availability['blocked_dates'] : array();
	if ( in_array( $day->format( 'Y-m-d' ), $blocked, true ) ) {
		return __( 'Blocked', 'wellness-consult' );
	}
	$weekly = isset( $consultant_availability['weekly_hours'] ) && is_array( $consultant_availability['weekly_hours'] ) ? $consultant_availability['weekly_hours'] : array();
	$hours  = isset( $weekly[ $key ] ) && is_array( $weekly[ $key ] ) ? $weekly[ $key ] : array();
	$active = isset( $hours['active'] ) ? in_array( (string) $hours['active'], array( '1', 'yes', 'true' ), true ) : ! $weekend;
	if ( ! $active ) {
		return __( 'Day off', 'wellness-consult' );
	}
	$start = $hours['start'] ?? ( $weekend ? '10:00' : '09:00' );
	$end   = $hours['end'] ?? ( $weekend ? '14:00' : '17:00' );
	return $start . ' - ' . $end;
};

/**
 * Print one appointment card.
 *
 * @param array $appointment Appointment data.
 * @param bool  $show_consultant Whether to print the consultant name.
 */
$render_card = function ( $appointment, $show_consultant ) {
	$status_class = sanitize_html_class( $appointment['status'] );
	$view_url     = admin_url( 'admin.php?page=wc-all-requests&action=view&id=' . absint( $appointment['id'] ) );
	?>
	<a class="wc-cal-appt is-<?php echo esc_attr( $status_class ); ?>" href="<?php echo esc_url( $view_url ); ?>">
		<span class="wc-cal-time"><?php echo esc_html( $appointment['start']->format( 'H:i' ) . ' - ' . $appointment['end']->format( 'H:i' ) ); ?></span>
		<span class="wc-cal-name"><?php echo esc_html( '' !== $appointment['name'] ? $appointment['name'] : $appointment['ref'] ); ?></span>
		<span class="wc-cal-meta">
			<?php echo esc_html( WC_Options::get_mode_icon( $appointment['mode'] ) . ' ' . WC_Options::get_mode_label( $appointment['mode'] ) ); ?>
			<?php if ( '' !== $appointment['platform'] ) : ?>
				&middot; <?php echo esc_html( $appointment['platform'] ); ?>
			<?php endif; ?>
		</span>
		<?php if ( $show_consultant && '' !== $appointment['consultant'] ) : ?>
			<span class="wc-cal-meta"><?php echo esc_html( $appointment['consultant'] ); ?></span>
		<?php endif; ?>
		<span class="wc-cal-status"><?php echo esc_html( WC_Status::get_label( $appointment['status'] ) ); ?></span>
	</a>
	<?php
};

$range_ts_start = $range_start->getTimestamp();
$range_ts_last  = $range_end->modify( '-1 day' )->getTimestamp();
if ( 'week' === $view ) {
	$range_label = wp_date( 'M j', $range_ts_start, $tz ) . ' - ' . wp_date( 'M j, Y', $range_ts_last, $tz );
} else {
	$range_label = wp_date( 'l, M j, Y', $range_ts_start, $tz );
}
$show_consultant_on_cards = ! $consultant_id;
?>
<div class="wrap wc-admin-wrap wc-cal-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Appointment Calendar', 'wellness-consult' ); ?></h1>
	<?php if ( $awaiting_count > 0 ) : ?>
		<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-all-requests&status_filter=' . WC_Status::STATUS_PAID_AWAITING_SCHEDULING ) ); ?>">
			<?php echo esc_html( sprintf( /* translators: %d: number of requests */ _n( '%d request waiting for a time', '%d requests waiting for a time', $awaiting_count, 'wellness-consult' ), $awaiting_count ) ); ?>
		</a>
	<?php endif; ?>
	<hr class="wp-header-end">

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="wc-cal-toolbar">
		<input type="hidden" name="page" value="wc-calendar">
		<input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>">

		<div class="wc-cal-nav">
			<a class="button" href="<?php echo esc_url( $cal_url( array( 'date' => $prev_date->format( 'Y-m-d' ) ) ) ); ?>">&lsaquo; <?php esc_html_e( 'Prev', 'wellness-consult' ); ?></a>
			<a class="button" href="<?php echo esc_url( $cal_url( array( 'date' => $today->format( 'Y-m-d' ) ) ) ); ?>"><?php esc_html_e( 'Today', 'wellness-consult' ); ?></a>
			<a class="button" href="<?php echo esc_url( $cal_url( array( 'date' => $next_date->format( 'Y-m-d' ) ) ) ); ?>"><?php esc_html_e( 'Next', 'wellness-consult' ); ?> &rsaquo;</a>
		</div>

		<strong class="wc-cal-range"><?php echo esc_html( $range_label ); ?></strong>

		<div class="wc-cal-views" role="group" aria-label="<?php esc_attr_e( 'Calendar view', 'wellness-consult' ); ?>">
			<a class="button <?php echo 'day' === $view ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $cal_url( array( 'view' => 'day' ) ) ); ?>"><?php esc_html_e( 'Day', 'wellness-consult' ); ?></a>
			<a class="button <?php echo 'week' === $view ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $cal_url( array( 'view' => 'week' ) ) ); ?>"><?php esc_html_e( 'Week', 'wellness-consult' ); ?></a>
		</div>

		<label class="wc-cal-field">
			<span class="screen-reader-text"><?php esc_html_e( 'Go to date', 'wellness-consult' ); ?></span>
			<input type="date" name="date" value="<?php echo esc_attr( $anchor->format( 'Y-m-d' ) ); ?>">
		</label>

		<?php if ( $is_admin ) : ?>
			<label class="wc-cal-field">
				<span class="screen-reader-text"><?php esc_html_e( 'Consultant', 'wellness-consult' ); ?></span>
				<select name="consultant">
					<option value="0"><?php esc_html_e( 'All consultants', 'wellness-consult' ); ?></option>
					<?php foreach ( $consultants as $consultant ) : ?>
						<option value="<?php echo esc_attr( $consultant->ID ); ?>" <?php selected( $consultant_id, absint( $consultant->ID ) ); ?>><?php echo esc_html( $consultant->display_name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<button type="submit" class="button"><?php esc_html_e( 'Apply', 'wellness-consult' ); ?></button>
	</form>

	<p class="description wc-cal-tz">
		<?php
		echo esc_html( sprintf(
			/* translators: 1: time zone, 2: number of appointments */
			_n( 'Times shown in %1$s. %2$d appointment in this range.', 'Times shown in %1$s. %2$d appointments in this range.', count( $appointments ), 'wellness-consult' ),
			$tz->getName(),
			count( $appointments )
		) );
		?>
	</p>

	<div class="wc-cal-grid is-<?php echo esc_attr( $view ); ?>">
		<?php
		$days_to_show = 'week' === $view ? 7 : 1;
		for ( $i = 0; $i < $days_to_show; $i++ ) :
			$day      = $range_start->modify( '+' . $i . ' days' );
			$day_key  = $day->format( 'Y-m-d' );
			$day_apps = $by_day[ $day_key ] ?? array();
			$caption  = $hours_caption( $day );
			$is_today = $day_key === $today->format( 'Y-m-d' );
			?>
			<section class="wc-cal-day <?php echo $is_today ? 'is-today' : ''; ?>">
				<header class="wc-cal-day-head">
					<strong><?php echo esc_html( wp_date( 'D j', $day->getTimestamp(), $tz ) ); ?></strong>
					<?php if ( '' !== $caption ) : ?>
						<span class="wc-cal-hours"><?php echo esc_html( $caption ); ?></span>
					<?php endif; ?>
				</header>
				<?php if ( empty( $day_apps ) ) : ?>
					<p class="wc-cal-empty"><?php esc_html_e( 'No appointments', 'wellness-consult' ); ?></p>
				<?php else : ?>
					<?php foreach ( $day_apps as $appointment ) : ?>
						<?php $render_card( $appointment, $show_consultant_on_cards ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>
		<?php endfor; ?>
	</div>

	<p class="description wc-cal-legend">
		<span class="wc-cal-dot is-scheduled"></span> <?php esc_html_e( 'Scheduled', 'wellness-consult' ); ?>
		<span class="wc-cal-dot is-completed"></span> <?php esc_html_e( 'Completed', 'wellness-consult' ); ?>
		<span class="wc-cal-dot is-no_show"></span> <?php esc_html_e( 'No-show', 'wellness-consult' ); ?>
		&middot; <?php esc_html_e( 'Click an appointment to open the request. To book or change a time, open the request and use Schedule / Reschedule.', 'wellness-consult' ); ?>
	</p>
</div>