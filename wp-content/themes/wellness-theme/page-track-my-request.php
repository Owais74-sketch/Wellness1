<?php
/**
 * Template Name: Track My Request
 *
 * Implements SRS v2.0 Section 4.8 & 8.8 Track My Request specifications.
 *
 * @package Wellness_Theme
 * @version 1.0.0
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
				<?php esc_html_e( 'Enter your Reference ID (e.g. WC-2026-00123) and the contact detail provided during booking. No login or password required.', 'wellness-theme' ); ?>
			</p>
		</header>

		<!-- Tracking Lookup Card (SRS 4.8) -->
		<div class="track-card" style="background:#fff; border:1px solid var(--color-border); border-radius:var(--radius-lg); padding: 2.5rem; box-shadow:var(--shadow-sm); margin-bottom: 3rem;">
			<form id="track-request-form" method="post" action="" style="display:flex; flex-direction:column; gap: 1.25rem;">

				<!-- Security Protection Notice (FR-4.8: 5 failed attempts locked out for 15 min) -->
				<div class="security-privacy-notice" style="background:var(--color-surface-subtle); border-left:3px solid var(--color-secondary); padding: 0.75rem 1rem; border-radius:var(--radius-sm); font-size:var(--text-xs); color:var(--color-text-muted);">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle;" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
					<?php esc_html_e( 'For patient privacy, after 5 incorrect lookup attempts your IP address will be locked for 15 minutes. No sensitive medical messages are ever displayed here.', 'wellness-theme' ); ?>
				</div>

				<div class="form-group" style="display:flex; flex-direction:column; gap: 0.35rem;">
					<label for="track_ref_id" style="font-weight:700; font-size:var(--text-sm); color:var(--color-primary);">
						<?php esc_html_e( 'Reference ID *', 'wellness-theme' ); ?>
					</label>
					<input type="text" id="track_ref_id" name="ref_id" placeholder="e.g. WC-2026-00123" required style="border:1px solid var(--color-border-strong); border-radius:var(--radius-md); padding: 0.75rem 1rem; font-size:var(--text-base);" />
				</div>

				<div class="form-group" style="display:flex; flex-direction:column; gap: 0.35rem;">
					<label for="track_contact" style="font-weight:700; font-size:var(--text-sm); color:var(--color-primary);">
						<?php esc_html_e( 'Email Address or WhatsApp Number *', 'wellness-theme' ); ?>
					</label>
					<input type="text" id="track_contact" name="contact" placeholder="<?php esc_attr_e( 'Same email or phone entered in booking', 'wellness-theme' ); ?>" required style="border:1px solid var(--color-border-strong); border-radius:var(--radius-md); padding: 0.75rem 1rem; font-size:var(--text-base);" />
				</div>

				<button type="submit" class="btn btn-secondary btn-pill" style="min-height:48px; font-size:var(--text-base); margin-top: 0.5rem;">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					<?php esc_html_e( 'Check Request Status', 'wellness-theme' ); ?>
				</button>
			</form>

			<div id="track-results-area" style="margin-top: 2rem;">
				<?php the_content(); ?>
			</div>
		</div>

		<!-- Status Lifecycle Reference Explainer (SRS Section 5) -->
		<div class="status-explainer-card" style="background:#fff; border:1px solid var(--color-border); border-radius:var(--radius-lg); padding: 1.5rem; margin-bottom: 2.5rem;">
			<h4 style="font-size:var(--text-sm); text-transform:uppercase; color:var(--color-primary); letter-spacing:0.04em; margin-bottom: 1rem;">
				<?php esc_html_e( 'Understanding Consultation Statuses:', 'wellness-theme' ); ?>
			</h4>
			<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; font-size:var(--text-xs); color:var(--color-text-secondary);">
				<div><strong style="color:var(--color-warning);">&bull; Awaiting Payment:</strong> Checkout not finished.</div>
				<div><strong style="color:var(--color-info);">&bull; Pending Verification:</strong> Admin checking screenshot.</div>
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
