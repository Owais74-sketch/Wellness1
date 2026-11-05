<?php
/**
 * Template Name: Track My Request
 *
 * Implements SRS v2.0 Section 4.8 & 8.8 Track My Request specifications.
 * The lookup form and status view are rendered by the Wellness Consult plugin
 * through the [wc_track_request] shortcode.
 *
 * @package Wellness_Theme
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-track-request" role="main">
	<div class="container container-narrow">

		<!-- Tracking Header (SRS 8.8) -->
		<header class="track-header text-center" style="margin-bottom: 2.5rem;">
			<span class="badge badge-primary" style="margin-bottom: 0.5rem;"><?php esc_html_e( 'Request Status & Appointments', 'wellness-theme' ); ?></span>
			<h1><?php esc_html_e( 'Track My Consultation Request', 'wellness-theme' ); ?></h1>
			<p class="text-muted" style="max-width: 600px; margin: 0 auto;">
				<?php esc_html_e( 'Enter your Reference ID (e.g. WC-2026-00123) and the email address you used when booking. No login or password required.', 'wellness-theme' ); ?>
			</p>
		</header>

		<!-- Security Protection Notice (FR-4.8: 5 failed attempts locked out for 15 min) -->
		<div class="security-privacy-notice" style="background:var(--color-surface-subtle); border-left:3px solid var(--color-secondary); padding: 0.75rem 1rem; border-radius:var(--radius-sm); font-size:var(--text-xs); color:var(--color-text-muted); margin-bottom: 1.5rem;">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle;" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
			<?php esc_html_e( 'For patient privacy, after 5 incorrect lookup attempts your IP address will be locked for 15 minutes. No sensitive medical messages are ever displayed here.', 'wellness-theme' ); ?>
		</div>

		<!-- Tracking lookup and status: rendered by the Wellness Consult plugin -->
		<div id="track-results-area" style="margin-bottom: 3rem;">
			<?php
			if ( shortcode_exists( 'wc_track_request' ) ) {
				echo do_shortcode( '[wc_track_request heading="no"]' );
			} else {
				echo '<p class="text-muted text-center">' . esc_html__( 'Request tracking is temporarily unavailable. Please try again later.', 'wellness-theme' ) . '</p>';
			}
			?>
		</div>

		<!-- Status Lifecycle Reference Explainer (SRS Section 5) -->
		<div class="status-explainer-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding: 1.5rem; margin-bottom: 2.5rem;">
			<h4 style="font-size:var(--text-sm); text-transform:uppercase; color:var(--color-primary); letter-spacing:0.04em; margin-bottom: 1rem;">
				<?php esc_html_e( 'Understanding Consultation Statuses:', 'wellness-theme' ); ?>
			</h4>
			<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; font-size:var(--text-xs); color:var(--color-text-secondary);">
				<div><strong style="color:var(--color-warning);">&bull; Awaiting Payment:</strong> Checkout not finished.</div>
				<div><strong style="color:var(--color-info);">&bull; Pending Verification:</strong> Admin checking screenshot.</div>
				<div><strong style="color:var(--color-warning);">&bull; Rejected:</strong> Upload a new, clear payment proof.</div>
				<div><strong style="color:var(--color-success);">&bull; Paid - Awaiting Scheduling:</strong> Time being assigned.</div>
				<div><strong style="color:var(--color-primary);">&bull; Scheduled:</strong> Time confirmed with meeting link.</div>
			</div>
		</div>

		<!-- Medical Disclaimer Partial in Footer (FR-4) -->
		<?php get_template_part( 'template-parts/disclaimer', 'box' ); ?>

	</div>
</main><!-- #primary -->

<?php
get_footer();