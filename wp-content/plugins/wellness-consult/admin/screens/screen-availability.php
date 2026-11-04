<?php
/**
 * Admin Screen: Consultant Availability & Scheduling Hours
 *
 * Configures weekly hours, breaks, blocked dates, and base timezone per consultant.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_manage_own_availability' ) && ! current_user_can( 'wc_manage_availability' ) ) {
	wp_die( esc_html__( 'You do not have permission to configure consultant availability.', 'wellness-consult' ) );
}

$notice = '';
$notice_type = 'success';

// Determine consultant to edit
$current_user_id = get_current_user_id();
$is_admin        = current_user_can( 'manage_options' ) || current_user_can( 'wc_manage_availability' );

// Get list of consultants
$consultants_query = get_users( array(
	'role__in' => array( 'wc_consultant', 'administrator' ),
	'orderby'  => 'display_name',
	'order'    => 'ASC',
) );

$selected_consultant_id = $current_user_id;
if ( $is_admin && isset( $_REQUEST['consultant_id'] ) ) {
	$selected_consultant_id = absint( $_REQUEST['consultant_id'] );
}

// Option key per consultant: wc_consultant_availability_{user_id}
$option_key = 'wc_consultant_availability_' . $selected_consultant_id;
$availability = get_option( $option_key, array() );

$days = array(
	'mon' => esc_html__( 'Monday', 'wellness-consult' ),
	'tue' => esc_html__( 'Tuesday', 'wellness-consult' ),
	'wed' => esc_html__( 'Wednesday', 'wellness-consult' ),
	'thu' => esc_html__( 'Thursday', 'wellness-consult' ),
	'fri' => esc_html__( 'Friday', 'wellness-consult' ),
	'sat' => esc_html__( 'Saturday', 'wellness-consult' ),
	'sun' => esc_html__( 'Sunday', 'wellness-consult' ),
);

// Form Save Handler
if ( isset( $_POST['wc_availability_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wc_availability_nonce'] ), 'wc_save_availability_action' ) ) {
	$base_tz = sanitize_text_field( wp_unslash( $_POST['base_timezone'] ?? 'UTC' ) );
	if ( ! in_array( $base_tz, timezone_identifiers_list(), true ) ) {
		$base_tz = 'UTC';
	}

	$weekly_hours = array();
	foreach ( $days as $day_key => $day_name ) {
		$is_active  = isset( $_POST[ 'day_active_' . $day_key ] ) ? '1' : '0';
		$start_time = sanitize_text_field( wp_unslash( $_POST[ 'day_start_' . $day_key ] ?? '09:00' ) );
		$end_time   = sanitize_text_field( wp_unslash( $_POST[ 'day_end_' . $day_key ] ?? '17:00' ) );

		$weekly_hours[ $day_key ] = array(
			'active' => $is_active,
			'start'  => $start_time,
			'end'    => $end_time,
		);
	}

	// Breaks
	$breaks_raw = isset( $_POST['breaks'] ) && is_array( $_POST['breaks'] ) ? wp_unslash( $_POST['breaks'] ) : array();
	$sanitized_breaks = array();
	foreach ( $breaks_raw as $b ) {
		$b_label = sanitize_text_field( $b['label'] ?? '' );
		$b_start = sanitize_text_field( $b['start'] ?? '' );
		$b_end   = sanitize_text_field( $b['end'] ?? '' );
		if ( ! empty( $b_start ) && ! empty( $b_end ) ) {
			$sanitized_breaks[] = array(
				'label' => $b_label,
				'start' => $b_start,
				'end'   => $b_end,
			);
		}
	}

	// Blocked Dates
	$blocked_raw = sanitize_textarea_field( wp_unslash( $_POST['blocked_dates'] ?? '' ) );
	$blocked_lines = explode( "\n", str_replace( "\r", '', $blocked_raw ) );
	$sanitized_blocked = array();
	foreach ( $blocked_lines as $line ) {
		$trimmed = trim( $line );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $trimmed ) ) {
			$sanitized_blocked[] = $trimmed;
		}
	}
	$sanitized_blocked = array_unique( $sanitized_blocked );
	sort( $sanitized_blocked );

	$availability = array(
		'base_timezone' => $base_tz,
		'weekly_hours'  => $weekly_hours,
		'breaks'        => $sanitized_breaks,
		'blocked_dates' => $sanitized_blocked,
		'updated_at'    => current_time( 'mysql' ),
	);

	update_option( $option_key, $availability );
	$notice = esc_html__( 'Consultant availability and working hours saved successfully.', 'wellness-consult' );

	WC_Database::log( array(
		'action' => 'update_availability',
		'note'   => sprintf( 'Availability updated for consultant user #%d.', $selected_consultant_id ),
	) );
}

// Defaults if not set
$base_tz       = $availability['base_timezone'] ?? 'UTC';
$weekly_hours  = $availability['weekly_hours'] ?? array();
$breaks        = $availability['breaks'] ?? array();
$blocked_dates = $availability['blocked_dates'] ?? array();

$all_timezones = timezone_identifiers_list();
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Consultant Availability & Working Hours', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $is_admin && count( $consultants_query ) > 1 ) : ?>
		<div style="background:#fff;border:1px solid #ccd0d4;padding:12px 16px;margin-top:16px;border-radius:4px;display:flex;align-items:center;gap:12px;">
			<label for="consultant_select" style="font-weight:600;"><?php esc_html_e( 'Select Consultant Profile:', 'wellness-consult' ); ?></label>
			<form method="get" action="" style="display:inline-flex;gap:8px;align-items:center;">
				<input type="hidden" name="page" value="wc-availability">
				<select name="consultant_id" id="consultant_select" onchange="this.form.submit();">
					<?php foreach ( $consultants_query as $c_user ) : ?>
						<option value="<?php echo esc_attr( $c_user->ID ); ?>" <?php selected( $selected_consultant_id, $c_user->ID ); ?>>
							<?php echo esc_html( $c_user->display_name . ' (' . $c_user->user_email . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</form>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'wc_save_availability_action', 'wc_availability_nonce' ); ?>
		<input type="hidden" name="consultant_id" value="<?php echo esc_attr( $selected_consultant_id ); ?>">

		<!-- Base Timezone Card -->
		<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:20px;border-radius:4px;">
			<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-clock"></span>
				<?php esc_html_e( 'Base Consultant Time Zone', 'wellness-consult' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'All working hours, calendar slots, and break periods are defined in this base time zone. Appointments are automatically converted to the patient’s local time zone on their confirmation and tracking cards.', 'wellness-consult' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="base_timezone"><?php esc_html_e( 'Time Zone', 'wellness-consult' ); ?></label></th>
						<td>
							<select name="base_timezone" id="base_timezone" class="regular-text">
								<?php foreach ( $all_timezones as $tz ) : ?>
									<option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $base_tz, $tz ); ?>>
										<?php echo esc_html( $tz ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Consultant local operating time zone.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Weekly Working Hours Card -->
		<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:20px;border-radius:4px;">
			<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-calendar-alt"></span>
				<?php esc_html_e( 'Weekly Operating Hours', 'wellness-consult' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Specify working windows when appointments can be scheduled. Days with unchecked boxes will be marked unavailable.', 'wellness-consult' ); ?>
			</p>

			<table class="wp-list-table widefat fixed striped" style="margin-top:12px;max-width:750px;">
				<thead>
					<tr>
						<th style="width:160px;"><?php esc_html_e( 'Day of Week', 'wellness-consult' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Available', 'wellness-consult' ); ?></th>
						<th><?php esc_html_e( 'Start Time', 'wellness-consult' ); ?></th>
						<th><?php esc_html_e( 'End Time', 'wellness-consult' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $days as $day_key => $day_name ) :
						$active = isset( $weekly_hours[ $day_key ]['active'] ) ? ( '1' === $weekly_hours[ $day_key ]['active'] ) : ( ! in_array( $day_key, array( 'sat', 'sun' ), true ) );
						$start  = $weekly_hours[ $day_key ]['start'] ?? '09:00';
						$end    = $weekly_hours[ $day_key ]['end'] ?? '17:00';
					?>
						<tr>
							<td><strong><?php echo esc_html( $day_name ); ?></strong></td>
							<td>
								<label>
									<input type="checkbox" name="day_active_<?php echo esc_attr( $day_key ); ?>" value="1" <?php checked( $active ); ?>>
									<?php esc_html_e( 'Open', 'wellness-consult' ); ?>
								</label>
							</td>
							<td>
								<input type="time" name="day_start_<?php echo esc_attr( $day_key ); ?>" value="<?php echo esc_attr( $start ); ?>">
							</td>
							<td>
								<input type="time" name="day_end_<?php echo esc_attr( $day_key ); ?>" value="<?php echo esc_attr( $end ); ?>">
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- Recurring Daily Breaks Card -->
		<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:20px;border-radius:4px;">
			<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-coffee"></span>
				<?php esc_html_e( 'Daily Break Windows', 'wellness-consult' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Slots during working days that must not be assigned for patient consultations (e.g., lunch, personal study).', 'wellness-consult' ); ?>
			</p>

			<div id="wc-breaks-container" style="max-width:600px;margin-top:12px;">
				<?php if ( empty( $breaks ) ) : ?>
					<div class="wc-break-row" style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">
						<input type="text" name="breaks[0][label]" placeholder="Break label (e.g. Lunch)" class="regular-text" style="flex:2;">
						<input type="time" name="breaks[0][start]" value="13:00" style="flex:1;">
						<span style="line-height:2.2;">to</span>
						<input type="time" name="breaks[0][end]" value="14:00" style="flex:1;">
					</div>
				<?php else : ?>
					<?php foreach ( $breaks as $idx => $brk ) : ?>
						<div class="wc-break-row" style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">
							<input type="text" name="breaks[<?php echo esc_attr( $idx ); ?>][label]" value="<?php echo esc_attr( $brk['label'] ?? '' ); ?>" placeholder="Break label (e.g. Lunch)" class="regular-text" style="flex:2;">
							<input type="time" name="breaks[<?php echo esc_attr( $idx ); ?>][start]" value="<?php echo esc_attr( $brk['start'] ?? '' ); ?>" style="flex:1;">
							<span style="line-height:2.2;">to</span>
							<input type="time" name="breaks[<?php echo esc_attr( $idx ); ?>][end]" value="<?php echo esc_attr( $brk['end'] ?? '' ); ?>" style="flex:1;">
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<!-- Blocked Dates Card -->
		<div class="wc-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-top:20px;border-radius:4px;">
			<h2 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-dismiss"></span>
				<?php esc_html_e( 'Blocked Dates / Leave / Holidays', 'wellness-consult' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Enter one date per line in YYYY-MM-DD format (e.g., 2026-12-25). No appointments can be booked on these dates.', 'wellness-consult' ); ?>
			</p>

			<textarea name="blocked_dates" id="blocked_dates" rows="5" class="large-text code" style="max-width:500px;" placeholder="2026-12-25&#10;2026-12-26&#10;2027-01-01"><?php echo esc_textarea( implode( "\n", $blocked_dates ) ); ?></textarea>
		</div>

		<p class="submit" style="margin-top:20px;">
			<input type="submit" name="submit_availability" class="button button-primary" value="<?php esc_attr_e( 'Save Availability Settings', 'wellness-consult' ); ?>">
		</p>
	</form>
</div>
