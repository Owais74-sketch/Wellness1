<?php
/**
 * Admin Screen: Consultation Types (CR-1 Multi-Mode: Chat, Voice, Video)
 *
 * Implements CR-1: For each type, configure three modes (Chat, Voice call, Video call),
 * each with on/off switch and fee in USD.
 *
 * @package Wellness_Consult
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_manage_types' ) && ! current_user_can( 'administrator' ) ) {
	wp_die( esc_html__( 'You do not have permission to manage consultation types.', 'wellness-consult' ) );
}

$types          = get_option( 'wc_consultation_types', array() );
$action         = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
$edit_id        = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';
$notice_message = '';
$notice_type    = 'success';

// Handle Form Submissions (Create / Update)
if ( ! empty( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wc_save_type'] ) ) {
	check_admin_referer( 'wc_save_type_action', 'wc_type_nonce' );

	$type_id     = ! empty( $_POST['type_id'] ) ? sanitize_key( wp_unslash( $_POST['type_id'] ) ) : 'type_' . time();
	$name        = isset( $_POST['type_name'] ) ? sanitize_text_field( wp_unslash( $_POST['type_name'] ) ) : '';
	$duration    = isset( $_POST['duration'] ) ? absint( wp_unslash( $_POST['duration'] ) ) : 30;
	$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
	$is_active   = isset( $_POST['is_active'] ) ? 1 : 0;

	// CR-1: 3 Mode on/off flags and USD fees
	$enable_chat  = ! empty( $_POST['enable_chat'] ) ? 1 : 0;
	$fee_chat     = isset( $_POST['fee_chat'] ) ? max( 0.0, round( (float) wp_unslash( $_POST['fee_chat'] ), 2 ) ) : 0.0;

	$enable_voice = ! empty( $_POST['enable_voice'] ) ? 1 : 0;
	$fee_voice    = isset( $_POST['fee_voice'] ) ? max( 0.0, round( (float) wp_unslash( $_POST['fee_voice'] ), 2 ) ) : 0.0;

	$enable_video = ! empty( $_POST['enable_video'] ) ? 1 : 0;
	$fee_video    = isset( $_POST['fee_video'] ) ? max( 0.0, round( (float) wp_unslash( $_POST['fee_video'] ), 2 ) ) : 0.0;

	// Preserve legacy fee if editing existing type
	$legacy_fee = isset( $types[ $type_id ]['legacy_fee'] ) ? (float) $types[ $type_id ]['legacy_fee'] : ( isset( $types[ $type_id ]['fee_usd'] ) ? (float) $types[ $type_id ]['fee_usd'] : 0.0 );

	if ( empty( $name ) ) {
		$notice_message = esc_html__( 'Error: Type name is required.', 'wellness-consult' );
		$notice_type    = 'error';
	} else {
		// Validation: Check ascending order among enabled modes (chat < voice < video)
		$ascending_warning = false;
		if ( $enable_chat && $enable_voice && $fee_chat >= $fee_voice ) {
			$ascending_warning = true;
		}
		if ( $enable_voice && $enable_video && $fee_voice >= $fee_video ) {
			$ascending_warning = true;
		}
		if ( $enable_chat && $enable_video && ! $enable_voice && $fee_chat >= $fee_video ) {
			$ascending_warning = true;
		}

		$has_enabled_mode = ( $enable_chat || $enable_voice || $enable_video );

		$types[ $type_id ] = array(
			'id'              => $type_id,
			'name'            => $name,
			'duration'        => $duration,
			'description'     => $description,
			'is_active'       => $is_active,
			'enable_chat'     => $enable_chat,
			'fee_chat'        => $fee_chat,
			'enable_voice'    => $enable_voice,
			'fee_voice'       => $fee_voice,
			'enable_video'    => $enable_video,
			'fee_video'       => $fee_video,
			'legacy_fee'      => $legacy_fee,
			'fee_usd'         => $legacy_fee, // Preserved as legacy field, not used for new bookings
			'needs_mode_fees' => $has_enabled_mode ? 0 : 1,
			'updated_at'      => current_time( 'mysql', 1 ),
		);

		update_option( 'wc_consultation_types', $types );

		WC_Database::log( array(
			'action' => 'type_saved',
			'note'   => sprintf(
				'Consultation type "%s" saved. Modes: Chat (%s, $%0.2f), Voice (%s, $%0.2f), Video (%s, $%0.2f).',
				$name,
				$enable_chat ? 'ON' : 'OFF',
				$fee_chat,
				$enable_voice ? 'ON' : 'OFF',
				$fee_voice,
				$enable_video ? 'ON' : 'OFF',
				$fee_video
			),
		) );

		if ( $ascending_warning ) {
			$notice_message = esc_html__( 'Consultation type saved. Note: Enabled fees are not in typical ascending order (Chat < Voice call < Video call), but your custom pricing has been saved.', 'wellness-consult' );
			$notice_type    = 'warning';
		} elseif ( ! $has_enabled_mode ) {
			$notice_message = esc_html__( 'Consultation type saved. Warning: All 3 consultation modes are disabled. This package will not appear on the booking page until at least one mode is enabled.', 'wellness-consult' );
			$notice_type    = 'warning';
		} else {
			$notice_message = esc_html__( 'Consultation type saved successfully.', 'wellness-consult' );
			$notice_type    = 'success';
		}

		$action = 'list';
	}
}

// Handle Delete Action
if ( 'delete' === $action && ! empty( $edit_id ) ) {
	check_admin_referer( 'wc_delete_type_' . $edit_id );
	if ( isset( $types[ $edit_id ] ) ) {
		$deleted_name = $types[ $edit_id ]['name'];
		unset( $types[ $edit_id ] );
		update_option( 'wc_consultation_types', $types );
		WC_Database::log( array(
			'action' => 'type_deleted',
			'note'   => sprintf( 'Consultation type "%s" was deleted.', $deleted_name ),
		) );
		$notice_message = esc_html__( 'Consultation type deleted.', 'wellness-consult' );
	}
	$action = 'list';
}

// Current editing item defaults
$current_item = array(
	'id'              => '',
	'name'            => '',
	'duration'        => 30,
	'description'     => '',
	'is_active'       => 1,
	'enable_chat'     => 0,
	'fee_chat'        => '',
	'enable_voice'    => 0,
	'fee_voice'       => '',
	'enable_video'    => 0,
	'fee_video'       => '',
	'legacy_fee'      => '',
	'needs_mode_fees' => 0,
);

if ( ( 'edit' === $action ) && ! empty( $edit_id ) && isset( $types[ $edit_id ] ) ) {
	$current_item = wp_parse_args( $types[ $edit_id ], $current_item );
}
?>
<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Consultation Packages & Modes', 'wellness-consult' ); ?>
	</h1>
	<?php if ( 'list' === $action ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-types&action=new' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Add New Type', 'wellness-consult' ); ?>
		</a>
	<?php endif; ?>
	<hr class="wp-header-end">

	<?php if ( ! empty( $notice_message ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible" style="margin: 15px 0;">
			<p><?php echo esc_html( $notice_message ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( 'new' === $action || 'edit' === $action ) : ?>
		<!-- Add / Edit Form -->
		<div class="card" style="max-width: 720px; padding: 24px; margin-top: 20px;">
			<h2><?php echo ( 'edit' === $action ) ? esc_html__( 'Edit Consultation Package', 'wellness-consult' ) : esc_html__( 'Add New Consultation Package', 'wellness-consult' ); ?></h2>

			<?php if ( ! empty( $current_item['legacy_fee'] ) ) : ?>
				<div style="background:#f1f5f9; border-left:4px solid #64748b; padding:10px 14px; margin-bottom:18px; border-radius:3px;">
					<strong><?php esc_html_e( 'Legacy Pricing Note:', 'wellness-consult' ); ?></strong>
					<?php printf( esc_html__( 'This package previously used a single flat fee of $%0.2f USD. Please enable and specify the fees for Chat, Voice Call, and Video Call below.', 'wellness-consult' ), (float) $current_item['legacy_fee'] ); ?>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wc-types' ) ); ?>">
				<?php wp_nonce_field( 'wc_save_type_action', 'wc_type_nonce' ); ?>
				<input type="hidden" name="type_id" value="<?php echo esc_attr( $current_item['id'] ); ?>" />
				<input type="hidden" name="wc_save_type" value="1" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="type_name"><?php esc_html_e( 'Package Name *', 'wellness-consult' ); ?></label></th>
						<td>
							<input name="type_name" type="text" id="type_name" value="<?php echo esc_attr( $current_item['name'] ); ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'e.g. Standard Consultation, Quick Chat, Couple Consultation', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="duration"><?php esc_html_e( 'Duration (Minutes) *', 'wellness-consult' ); ?></label></th>
						<td>
							<input name="duration" type="number" id="duration" min="5" step="5" value="<?php echo esc_attr( $current_item['duration'] ); ?>" class="small-text" required />
							<p class="description"><?php esc_html_e( 'Consultation duration in minutes (e.g. 15, 30, 45, 60).', 'wellness-consult' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="description"><?php esc_html_e( 'Description', 'wellness-consult' ); ?></label></th>
						<td>
							<textarea name="description" id="description" rows="3" class="large-text"><?php echo esc_textarea( $current_item['description'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Brief description shown to patients on the booking page.', 'wellness-consult' ); ?></p>
						</td>
					</tr>
				</table>

				<!-- CR-1: Modes & Fees Table -->
				<h3 style="margin-top:24px; padding-bottom:8px; border-bottom:1px solid #e2e8f0;">
					<?php esc_html_e( 'Consultation Modes & Fees (USD)', 'wellness-consult' ); ?>
				</h3>
				<p class="description" style="margin-bottom:16px;">
					<?php esc_html_e( 'Enable available consultation modes for this package and set their individual fees in USD. Typically, Chat is the cheapest, Voice call costs more, and Video call costs the most.', 'wellness-consult' ); ?>
				</p>

				<table class="widefat fixed striped" style="margin-bottom:20px;">
					<thead>
						<tr>
							<th style="width:40px;"><?php esc_html_e( 'Icon', 'wellness-consult' ); ?></th>
							<th style="width:140px;"><?php esc_html_e( 'Mode', 'wellness-consult' ); ?></th>
							<th style="width:110px;"><?php esc_html_e( 'Enabled?', 'wellness-consult' ); ?></th>
							<th style="width:170px;"><?php esc_html_e( 'Fee (USD)', 'wellness-consult' ); ?></th>
							<th><?php esc_html_e( 'Patient Description', 'wellness-consult' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<!-- Row 1: Chat -->
						<tr>
							<td style="font-size:20px; text-align:center;">💬</td>
							<td><strong><?php esc_html_e( 'Chat', 'wellness-consult' ); ?></strong></td>
							<td>
								<label for="enable_chat">
									<input type="checkbox" name="enable_chat" id="enable_chat" value="1" <?php checked( $current_item['enable_chat'], 1 ); ?> />
									<?php esc_html_e( 'Enable', 'wellness-consult' ); ?>
								</label>
							</td>
							<td>
								<div style="display:flex; align-items:center; gap:4px;">
									<span style="font-weight:700;">$</span>
									<input type="number" name="fee_chat" id="fee_chat" min="0" step="0.01" value="<?php echo esc_attr( $current_item['fee_chat'] !== '' ? $current_item['fee_chat'] : '' ); ?>" placeholder="0.00" class="regular-text" style="width:120px;" />
									<span style="color:#64748b; font-size:12px;">USD</span>
								</div>
							</td>
							<td>
								<span style="color:#475569; font-size:13px;"><?php esc_html_e( 'Text consultation, no voice or camera', 'wellness-consult' ); ?></span>
							</td>
						</tr>

						<!-- Row 2: Voice Call -->
						<tr>
							<td style="font-size:20px; text-align:center;">📞</td>
							<td><strong><?php esc_html_e( 'Voice Call', 'wellness-consult' ); ?></strong></td>
							<td>
								<label for="enable_voice">
									<input type="checkbox" name="enable_voice" id="enable_voice" value="1" <?php checked( $current_item['enable_voice'], 1 ); ?> />
									<?php esc_html_e( 'Enable', 'wellness-consult' ); ?>
								</label>
							</td>
							<td>
								<div style="display:flex; align-items:center; gap:4px;">
									<span style="font-weight:700;">$</span>
									<input type="number" name="fee_voice" id="fee_voice" min="0" step="0.01" value="<?php echo esc_attr( $current_item['fee_voice'] !== '' ? $current_item['fee_voice'] : '' ); ?>" placeholder="0.00" class="regular-text" style="width:120px;" />
									<span style="color:#64748b; font-size:12px;">USD</span>
								</div>
							</td>
							<td>
								<span style="color:#475569; font-size:13px;"><?php esc_html_e( 'Audio only, camera stays off', 'wellness-consult' ); ?></span>
							</td>
						</tr>

						<!-- Row 3: Video Call -->
						<tr>
							<td style="font-size:20px; text-align:center;">📹</td>
							<td><strong><?php esc_html_e( 'Video Call', 'wellness-consult' ); ?></strong></td>
							<td>
								<label for="enable_video">
									<input type="checkbox" name="enable_video" id="enable_video" value="1" <?php checked( $current_item['enable_video'], 1 ); ?> />
									<?php esc_html_e( 'Enable', 'wellness-consult' ); ?>
								</label>
							</td>
							<td>
								<div style="display:flex; align-items:center; gap:4px;">
									<span style="font-weight:700;">$</span>
									<input type="number" name="fee_video" id="fee_video" min="0" step="0.01" value="<?php echo esc_attr( $current_item['fee_video'] !== '' ? $current_item['fee_video'] : '' ); ?>" placeholder="0.00" class="regular-text" style="width:120px;" />
									<span style="color:#64748b; font-size:12px;">USD</span>
								</div>
							</td>
							<td>
								<span style="color:#475569; font-size:13px;"><?php esc_html_e( 'Face to face by video (Camera optional)', 'wellness-consult' ); ?></span>
							</td>
						</tr>
					</tbody>
				</table>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Active Package Status', 'wellness-consult' ); ?></th>
						<td>
							<label for="is_active">
								<input name="is_active" type="checkbox" id="is_active" value="1" <?php checked( $current_item['is_active'], 1 ); ?> />
								<?php esc_html_e( 'Active (Package available for patient booking when at least 1 mode is enabled)', 'wellness-consult' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Consultation Package', 'wellness-consult' ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-types' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'wellness-consult' ); ?></a>
				</p>
			</form>
		</div>

	<?php else : ?>
		<!-- Types Table View -->
		<?php if ( empty( $types ) ) : ?>
			<div class="card" style="padding: 40px 20px; text-align: center; max-width: 600px; margin: 30px auto;">
				<div style="font-size: 32px; margin-bottom: 10px;">🏷️</div>
				<h2 style="margin: 0 0 10px;"><?php esc_html_e( 'Add your first consultation package', 'wellness-consult' ); ?></h2>
				<p class="description" style="margin-bottom: 20px;">
					<?php esc_html_e( 'No consultation packages exist yet. Define session options with individual fees for Chat, Voice, and Video to allow patients to book.', 'wellness-consult' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-types&action=new' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Add your first consultation type', 'wellness-consult' ); ?> &rarr;
				</a>
			</div>
		<?php else : ?>
			<table class="widefat fixed striped" style="margin-top: 15px;">
				<thead>
					<tr>
						<th style="width:180px;"><?php esc_html_e( 'Package Name', 'wellness-consult' ); ?></th>
						<th style="width:90px;"><?php esc_html_e( 'Duration', 'wellness-consult' ); ?></th>
						<th style="width:130px;"><?php esc_html_e( '💬 Chat Fee', 'wellness-consult' ); ?></th>
						<th style="width:130px;"><?php esc_html_e( '📞 Voice Fee', 'wellness-consult' ); ?></th>
						<th style="width:130px;"><?php esc_html_e( '📹 Video Fee', 'wellness-consult' ); ?></th>
						<th><?php esc_html_e( 'Description', 'wellness-consult' ); ?></th>
						<th style="width:140px;"><?php esc_html_e( 'Status', 'wellness-consult' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Actions', 'wellness-consult' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $types as $id => $item ) :
						$has_any_mode = ( ! empty( $item['enable_chat'] ) || ! empty( $item['enable_voice'] ) || ! empty( $item['enable_video'] ) );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $item['name'] ); ?></strong>
								<?php if ( ! empty( $item['legacy_fee'] ) ) : ?>
									<div style="font-size:11px; color:#64748b;"><?php printf( esc_html__( '(Legacy: $%0.2f)', 'wellness-consult' ), (float) $item['legacy_fee'] ); ?></div>
								<?php endif; ?>
							</td>
							<td><?php printf( esc_html__( '%d min', 'wellness-consult' ), absint( $item['duration'] ?? 30 ) ); ?></td>

							<!-- Chat Mode -->
							<td>
								<?php if ( ! empty( $item['enable_chat'] ) ) : ?>
									<strong style="color:#0f3b4c;">$<?php echo esc_html( number_format( (float) ( $item['fee_chat'] ?? 0 ), 2 ) ); ?></strong>
								<?php else : ?>
									<span style="color:#94a3b8; font-size:12px;"><?php esc_html_e( 'Disabled', 'wellness-consult' ); ?></span>
								<?php endif; ?>
							</td>

							<!-- Voice Mode -->
							<td>
								<?php if ( ! empty( $item['enable_voice'] ) ) : ?>
									<strong style="color:#0f3b4c;">$<?php echo esc_html( number_format( (float) ( $item['fee_voice'] ?? 0 ), 2 ) ); ?></strong>
								<?php else : ?>
									<span style="color:#94a3b8; font-size:12px;"><?php esc_html_e( 'Disabled', 'wellness-consult' ); ?></span>
								<?php endif; ?>
							</td>

							<!-- Video Mode -->
							<td>
								<?php if ( ! empty( $item['enable_video'] ) ) : ?>
									<strong style="color:#0f3b4c;">$<?php echo esc_html( number_format( (float) ( $item['fee_video'] ?? 0 ), 2 ) ); ?></strong>
								<?php else : ?>
									<span style="color:#94a3b8; font-size:12px;"><?php esc_html_e( 'Disabled', 'wellness-consult' ); ?></span>
								<?php endif; ?>
							</td>

							<td><?php echo esc_html( $item['description'] ?? '' ); ?></td>

							<td>
								<?php if ( ! $has_any_mode || ! empty( $item['needs_mode_fees'] ) ) : ?>
									<span class="badge" style="background:#fef3c7; color:#b45309; padding:3px 8px; border-radius:4px; font-weight:700; font-size:11px;">
										⚠️ <?php esc_html_e( 'Set mode fees', 'wellness-consult' ); ?>
									</span>
								<?php elseif ( ! empty( $item['is_active'] ) ) : ?>
									<span class="badge" style="background:#f0fdf4; color:#15803d; padding:2px 8px; border-radius:4px; font-weight:700;">
										<?php esc_html_e( 'Active', 'wellness-consult' ); ?>
									</span>
								<?php else : ?>
									<span class="badge" style="background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:4px; font-weight:700;">
										<?php esc_html_e( 'Disabled', 'wellness-consult' ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-types&action=edit&id=' . urlencode( $id ) ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'wellness-consult' ); ?>
								</a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-types&action=delete&id=' . urlencode( $id ) ), 'wc_delete_type_' . $id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this consultation package?', 'wellness-consult' ) ); ?>');">
									<?php esc_html_e( 'Delete', 'wellness-consult' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	<?php endif; ?>
</div>
