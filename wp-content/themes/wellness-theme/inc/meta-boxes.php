<?php
/**
 * Page Extras Meta Box
 *
 * Adds a "Page extras" meta box on Pages with four editable fields:
 * Label, Subtitle/meta line, Closing line, Last updated text.
 * Pre-fills the About page with its current hard-coded values on first save.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the "Page extras" meta box on the Page edit screen.
 */
function wellness_register_page_extras_meta_box() {
	add_meta_box(
		'wellness_page_extras',
		esc_html__( 'Page Extras', 'wellness-theme' ),
		'wellness_page_extras_meta_box_html',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'wellness_register_page_extras_meta_box' );

/**
 * Render the meta box HTML.
 *
 * @param WP_Post $post Current post object.
 */
function wellness_page_extras_meta_box_html( $post ) {
	wp_nonce_field( 'wellness_page_extras_save', 'wellness_page_extras_nonce' );

	$label        = get_post_meta( $post->ID, '_wellness_page_label', true );
	$subtitle     = get_post_meta( $post->ID, '_wellness_page_subtitle', true );
	$closing_line = get_post_meta( $post->ID, '_wellness_page_closing_line', true );
	$last_updated = get_post_meta( $post->ID, '_wellness_page_last_updated', true );

	// One-time migration: pre-fill About page defaults.
	if ( '' === $label && 'page-about' === get_page_template_slug( $post->ID ) ) {
		$label        = esc_html__( 'About us', 'wellness-theme' );
		$subtitle     = esc_html__( 'is a portfolio demo of an everyday wellness education site. Articles cover nutrition, sleep, fitness and stress — all backed by reputable public-health sources.', 'wellness-theme' );
		$closing_line = '';
		$last_updated = '';
	}
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="wellness_page_label"><?php esc_html_e( 'Label (small badge above title)', 'wellness-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="wellness_page_label"
					name="wellness_page_label"
					value="<?php echo esc_attr( $label ); ?>"
					class="regular-text"
				/>
				<p class="description"><?php esc_html_e( 'Shown as a small badge or eyebrow label above the page title. Leave empty to hide.', 'wellness-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="wellness_page_subtitle"><?php esc_html_e( 'Subtitle / Meta line', 'wellness-theme' ); ?></label>
			</th>
			<td>
				<textarea
					id="wellness_page_subtitle"
					name="wellness_page_subtitle"
					rows="3"
					class="large-text"
				><?php echo esc_textarea( $subtitle ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Introductory or descriptive line displayed below the title. Leave empty to hide.', 'wellness-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="wellness_page_closing_line"><?php esc_html_e( 'Closing line', 'wellness-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="wellness_page_closing_line"
					name="wellness_page_closing_line"
					value="<?php echo esc_attr( $closing_line ); ?>"
					class="regular-text"
				/>
				<p class="description"><?php esc_html_e( 'Optional sign-off sentence at the end of the page content. Leave empty to hide.', 'wellness-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="wellness_page_last_updated"><?php esc_html_e( 'Last updated text', 'wellness-theme' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="wellness_page_last_updated"
					name="wellness_page_last_updated"
					value="<?php echo esc_attr( $last_updated ); ?>"
					class="regular-text"
				/>
				<p class="description"><?php esc_html_e( 'e.g. "Last reviewed: January 2026". Leave empty to hide.', 'wellness-theme' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save the meta box values.
 *
 * @param int $post_id Post ID.
 */
function wellness_save_page_extras_meta( $post_id ) {
	// Nonce check.
	if (
		! isset( $_POST['wellness_page_extras_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wellness_page_extras_nonce'] ) ), 'wellness_page_extras_save' )
	) {
		return;
	}

	// Capability check.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Skip autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$fields = array(
		'wellness_page_label'        => '_wellness_page_label',
		'wellness_page_subtitle'     => '_wellness_page_subtitle',
		'wellness_page_closing_line' => '_wellness_page_closing_line',
		'wellness_page_last_updated' => '_wellness_page_last_updated',
	);

	foreach ( $fields as $post_key => $meta_key ) {
		if ( isset( $_POST[ $post_key ] ) ) {
			update_post_meta(
				$post_id,
				$meta_key,
				sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) )
			);
		}
	}
}
add_action( 'save_post_page', 'wellness_save_page_extras_meta' );

/**
 * Helper: get a Page Extras field value.
 *
 * @param int    $post_id  Post ID (defaults to current post).
 * @param string $field    One of: label, subtitle, closing_line, last_updated.
 * @return string
 */
function wellness_get_page_extra( $post_id, $field ) {
	$map = array(
		'label'        => '_wellness_page_label',
		'subtitle'     => '_wellness_page_subtitle',
		'closing_line' => '_wellness_page_closing_line',
		'last_updated' => '_wellness_page_last_updated',
	);
	if ( ! isset( $map[ $field ] ) ) {
		return '';
	}
	return (string) get_post_meta( $post_id, $map[ $field ], true );
}
