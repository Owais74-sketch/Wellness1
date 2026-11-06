<?php
/**
 * Admin Screen: Anonymous Questions Moderation Queue
 *
 * SRS v2.0 Section 4.10 (FR-33, FR-34). Phase 9.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'wc_manage_assigned_requests' ) ) {
	wp_die( esc_html__( 'You do not have permission to view anonymous questions.', 'wellness-consult' ) );
}

$statuses = WC_Questions::statuses();
$cats     = WC_Questions::categories();
$base_url = admin_url( 'admin.php?page=wc-questions' );

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$qid     = isset( $_GET['qid'] ) ? absint( $_GET['qid'] ) : 0;
$filter  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'pending';
$msg     = isset( $_GET['wcq_msg'] ) ? sanitize_key( wp_unslash( $_GET['wcq_msg'] ) ) : '';
// phpcs:enable
if ( 'all' !== $filter && ! isset( $statuses[ $filter ] ) ) {
	$filter = 'pending';
}

$messages = array(
	'saved'      => __( 'Saved.', 'wellness-consult' ),
	'published'  => __( 'Published. It now appears on the Ask a Question page.', 'wellness-consult' ),
	'rejected'   => __( 'Question rejected. The stored email, if any, was deleted.', 'wellness-consult' ),
	'deleted'    => __( 'Question deleted permanently.', 'wellness-consult' ),
	'incomplete' => __( 'To publish, both the public version of the question and the answer must be filled in.', 'wellness-consult' ),
);
?>

<div class="wrap wc-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Anonymous Questions', 'wellness-consult' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $msg && isset( $messages[ $msg ] ) ) : ?>
		<div class="notice <?php echo 'incomplete' === $msg ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $messages[ $msg ] ); ?></p></div>
	<?php endif; ?>

<?php
/* ------------------------------------------------------------------
 * Review screen for one question
 * ------------------------------------------------------------------ */
$post = $qid ? get_post( $qid ) : null;
if ( $post && WC_Questions::POST_TYPE === $post->post_type ) :
	$status   = (string) get_post_meta( $qid, '_wcq_status', true );
	$category = (string) get_post_meta( $qid, '_wcq_category', true );
	$original = (string) get_post_meta( $qid, '_wcq_question', true );
	$public   = (string) get_post_meta( $qid, '_wcq_public_question', true );
	$answer   = (string) get_post_meta( $qid, '_wcq_answer', true );
	$has_mail = (bool) get_post_meta( $qid, '_wcq_email', true );
	if ( '' === $public ) {
		$public = $original;
	}
	?>
	<p><a href="<?php echo esc_url( $base_url ); ?>">&larr; <?php esc_html_e( 'Back to the list', 'wellness-consult' ); ?></a></p>

	<div style="background:#fff;border:1px solid #ccd0d4;border-radius:6px;padding:24px;max-width:860px;">
		<p style="margin-top:0;color:#555;">
			<strong><?php esc_html_e( 'Received:', 'wellness-consult' ); ?></strong> <?php echo esc_html( get_date_from_gmt( $post->post_date_gmt, 'j M Y, H:i' ) ); ?> &nbsp;|&nbsp;
			<strong><?php esc_html_e( 'Status:', 'wellness-consult' ); ?></strong> <?php echo esc_html( $statuses[ $status ] ?? $status ); ?> &nbsp;|&nbsp;
			<strong><?php esc_html_e( 'Notification email:', 'wellness-consult' ); ?></strong>
			<?php echo $has_mail ? esc_html__( 'provided (sent once when published, then deleted)', 'wellness-consult' ) : esc_html__( 'none', 'wellness-consult' ); ?>
		</p>

		<h3><?php esc_html_e( 'Original question (private)', 'wellness-consult' ); ?></h3>
		<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:12px 14px;white-space:pre-wrap;"><?php echo esc_html( $original ); ?></div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:20px;">
			<input type="hidden" name="action" value="wcq_moderate">
			<input type="hidden" name="qid" value="<?php echo esc_attr( $qid ); ?>">
			<?php wp_nonce_field( 'wcq_moderate_' . $qid ); ?>

			<p>
				<label for="wcq-cat"><strong><?php esc_html_e( 'Category', 'wellness-consult' ); ?></strong></label><br>
				<select id="wcq-cat" name="category">
					<?php foreach ( $cats as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $category, $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="wcq-public"><strong><?php esc_html_e( 'Public version of the question', 'wellness-consult' ); ?></strong></label><br>
				<span class="description"><?php esc_html_e( 'This is what visitors will see. Remove names, places, ages, phone numbers and any detail that could identify the person.', 'wellness-consult' ); ?></span>
				<textarea id="wcq-public" name="public_question" rows="4" class="large-text"><?php echo esc_textarea( $public ); ?></textarea>
			</p>

			<p>
				<label for="wcq-answer"><strong><?php esc_html_e( 'Answer', 'wellness-consult' ); ?></strong></label>
				<textarea id="wcq-answer" name="answer" rows="9" class="large-text"><?php echo esc_textarea( $answer ); ?></textarea>
			</p>

			<p style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
				<button type="submit" name="op" value="publish" class="button button-primary"><?php esc_html_e( 'Publish', 'wellness-consult' ); ?></button>
				<button type="submit" name="op" value="save" class="button"><?php esc_html_e( 'Save draft', 'wellness-consult' ); ?></button>
				<button type="submit" name="op" value="reject" class="button" onclick="return confirm('<?php echo esc_js( __( 'Reject this question? It will not be published.', 'wellness-consult' ) ); ?>');"><?php esc_html_e( 'Reject', 'wellness-consult' ); ?></button>
				<button type="submit" name="op" value="delete" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete permanently? This cannot be undone.', 'wellness-consult' ) ); ?>');"><?php esc_html_e( 'Delete', 'wellness-consult' ); ?></button>
			</p>
		</form>
	</div>

<?php
/* ------------------------------------------------------------------
 * List
 * ------------------------------------------------------------------ */
else :
	$args = array(
		'post_type'   => WC_Questions::POST_TYPE,
		'post_status' => 'any',
		'numberposts' => 100,
		'orderby'     => 'date',
		'order'       => 'DESC',
	);
	if ( 'all' !== $filter ) {
		$args['meta_key']   = '_wcq_status';
		$args['meta_value'] = $filter;
	}
	$items = get_posts( $args );
	?>

	<ul class="subsubsub">
		<?php
		$links = array();
		foreach ( $statuses as $slug => $label ) {
			$links[] = sprintf(
				'<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>',
				esc_url( add_query_arg( 'status', $slug, $base_url ) ),
				$filter === $slug ? ' class="current"' : '',
				esc_html( $label ),
				WC_Questions::count_by_status( $slug )
			);
		}
		$links[] = sprintf(
			'<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>',
			esc_url( add_query_arg( 'status', 'all', $base_url ) ),
			'all' === $filter ? ' class="current"' : '',
			esc_html__( 'All', 'wellness-consult' ),
			WC_Questions::count_by_status()
		);
		echo implode( ' | ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</ul>
	<br class="clear">

	<table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
		<thead>
			<tr>
				<th style="width:140px;"><?php esc_html_e( 'Received', 'wellness-consult' ); ?></th>
				<th style="width:190px;"><?php esc_html_e( 'Category', 'wellness-consult' ); ?></th>
				<th><?php esc_html_e( 'Question', 'wellness-consult' ); ?></th>
				<th style="width:170px;"><?php esc_html_e( 'Status', 'wellness-consult' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $items ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No questions here yet.', 'wellness-consult' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $items as $item ) :
				$st  = (string) get_post_meta( $item->ID, '_wcq_status', true );
				$cat = (string) get_post_meta( $item->ID, '_wcq_category', true );
				$txt = wp_trim_words( (string) get_post_meta( $item->ID, '_wcq_question', true ), 22 );
				?>
				<tr>
					<td><?php echo esc_html( get_date_from_gmt( $item->post_date_gmt, 'j M Y, H:i' ) ); ?></td>
					<td><?php echo esc_html( $cats[ $cat ] ?? $cat ); ?></td>
					<td>
						<a href="<?php echo esc_url( add_query_arg( 'qid', $item->ID, $base_url ) ); ?>"><strong><?php echo esc_html( $txt ); ?></strong></a>
					</td>
					<td><?php echo esc_html( $statuses[ $st ] ?? $st ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
</div>