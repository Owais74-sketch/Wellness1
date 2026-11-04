<?php
/**
 * Template Name: Ask a Question
 *
 * Implements SRS v2.0 Section 4.10 & 8.6 Free Anonymous Q&A specifications.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-ask-question" role="main">
	<div class="container container-narrow">

		<!-- Intro Header (SRS 8.6) -->
		<header class="ask-header text-center" style="margin-bottom: 2.5rem;">
			<span class="badge badge-secondary" style="margin-bottom: 0.5rem;"><?php esc_html_e( '100% Free • Anonymous • Safe', 'wellness-theme' ); ?></span>
			<h1><?php esc_html_e( 'Ask a Question Anonymously', 'wellness-theme' ); ?></h1>
			<p class="text-muted" style="max-width: 620px; margin: 0 auto;">
				<?php esc_html_e( 'Have a private question about intimacy, physical symptoms, or emotional connection? Ask without sharing your identity.', 'wellness-theme' ); ?>
			</p>
		</header>

		<!-- Question Submission Card (SRS 4.10 FR-33 & 8.6) -->
		<div class="ask-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding: 2.5rem; box-shadow:var(--shadow-sm); margin-bottom: 3.5rem;">
			<form id="anonymous-question-form" method="post" action="" style="display:flex; flex-direction:column; gap: 1.25rem;">

				<!-- Emergency & Moderation Notice (SRS 8.6) -->
				<div class="notice-privacy-strip" style="background:var(--color-surface-subtle); border-left:3px solid var(--color-accent); padding: 0.85rem 1rem; border-radius:var(--radius-sm); font-size:var(--text-xs); color:var(--color-text-secondary); line-height:1.5;">
					<strong><?php esc_html_e( 'Privacy & Medical Notice:', 'wellness-theme' ); ?></strong>
					<?php esc_html_e( 'Questions go to our moderation queue and may be published on this educational board with identifying information removed. This service is not for acute medical emergencies.', 'wellness-theme' ); ?>
				</div>

				<div class="form-group" style="display:flex; flex-direction:column; gap: 0.35rem;">
					<label for="qa_category" style="font-weight:700; font-size:var(--text-sm); color:var(--color-primary);">
						<?php esc_html_e( 'Topic Category *', 'wellness-theme' ); ?>
					</label>
					<select id="qa_category" name="category" required style="border:1px solid var(--border); border-radius:var(--radius-md); padding: 0.75rem 1rem; font-size:var(--text-base); background:var(--surface);">
						<option value="intimacy"><?php esc_html_e( 'Intimacy & Relationships', 'wellness-theme' ); ?></option>
						<option value="mens-health"><?php esc_html_e( "Men's Health & Performance", 'wellness-theme' ); ?></option>
						<option value="womens-health"><?php esc_html_e( "Women's Health & Comfort", 'wellness-theme' ); ?></option>
						<option value="lifestyle"><?php esc_html_e( 'Stress, Habits & Lifestyle', 'wellness-theme' ); ?></option>
						<option value="general"><?php esc_html_e( 'General Sexual Health Question', 'wellness-theme' ); ?></option>
					</select>
				</div>

				<div class="form-group" style="display:flex; flex-direction:column; gap: 0.35rem;">
					<label for="qa_question" style="font-weight:700; font-size:var(--text-sm); color:var(--color-primary);">
						<?php esc_html_e( 'Your Question *', 'wellness-theme' ); ?>
					</label>
					<textarea id="qa_question" name="question" rows="5" required placeholder="<?php esc_attr_e( 'Describe your concern honestly. No need to share your name or location.', 'wellness-theme' ); ?>" style="border:1px solid var(--color-border-strong); border-radius:var(--radius-md); padding: 0.75rem 1rem; font-size:var(--text-base);"></textarea>
				</div>

				<div class="form-group" style="display:flex; flex-direction:column; gap: 0.35rem;">
					<label for="qa_email" style="font-weight:700; font-size:var(--text-sm); color:var(--color-primary);">
						<?php esc_html_e( 'Optional Notification Email', 'wellness-theme' ); ?>
						<span style="font-weight:normal; color:var(--color-text-muted);">(<?php esc_html_e( 'Optional - only if you wish to be emailed when answered', 'wellness-theme' ); ?>)</span>
					</label>
					<input type="email" id="qa_email" name="email" placeholder="you@example.com" style="border:1px solid var(--color-border-strong); border-radius:var(--radius-md); padding: 0.75rem 1rem; font-size:var(--text-base);" />
				</div>

				<button type="submit" class="btn btn-primary btn-pill" style="min-height:48px; font-size:var(--text-base); margin-top: 0.5rem;">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
					<?php esc_html_e( 'Submit Question Anonymously', 'wellness-theme' ); ?>
				</button>
			</form>
		</div>

		<!-- Recently Answered Questions Feed (SRS 8.6 & 8.13 - 5 answered Q&A at launch) -->
		<section class="answered-qa-section" aria-labelledby="answered-qa-heading">
			<div class="section-header-compact" style="margin-bottom: 1.5rem;">
				<h2 id="answered-qa-heading" style="font-size: var(--text-2xl); color: var(--color-primary);">
					<?php esc_html_e( 'Recently Answered Questions', 'wellness-theme' ); ?>
				</h2>
				<p class="text-sm text-muted"><?php esc_html_e( 'Community questions and responses.', 'wellness-theme' ); ?></p>
			</div>

			<div class="qa-cards-list" style="display:flex; flex-direction:column; gap: 1.25rem; margin-bottom: 3rem;">
				<div class="qa-item-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-md); padding: 1.5rem;">
					<div class="qa-tag-row" style="display:flex; align-items:center; gap: 0.5rem; margin-bottom: 0.5rem;">
						<span class="badge badge-accent" style="font-size:0.65rem;"><?php esc_html_e( 'SAMPLE PREVIEW', 'wellness-theme' ); ?></span>
						<span class="badge badge-primary"><?php esc_html_e( 'Intimacy Health', 'wellness-theme' ); ?></span>
					</div>
					<h3 style="font-size: var(--text-base); color: var(--color-primary); margin-bottom: 0.75rem;">
						Q: <?php esc_html_e( 'Is it normal for desire to decrease during periods of heavy workplace stress?', 'wellness-theme' ); ?>
					</h3>
					<div style="background:var(--color-surface-subtle); padding: 1rem; border-radius:var(--radius-sm); font-size:var(--text-sm); line-height:1.6; color:var(--color-text-secondary); border-left:3px solid var(--color-secondary);">
						<strong><?php esc_html_e( 'Consultant Response:', 'wellness-theme' ); ?></strong> <?php esc_html_e( 'Yes. Cortisol directly suppresses intimacy pathways. In clinical practice, addressing stress management, sleep hygiene, and non-demanding touch restores balance for most individuals.', 'wellness-theme' ); ?>
					</div>
				</div>

				<div class="qa-item-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-md); padding: 1.5rem;">
					<div class="qa-tag-row" style="display:flex; align-items:center; gap: 0.5rem; margin-bottom: 0.5rem;">
						<span class="badge badge-accent" style="font-size:0.65rem;"><?php esc_html_e( 'SAMPLE PREVIEW', 'wellness-theme' ); ?></span>
						<span class="badge badge-secondary"><?php esc_html_e( "Men's Health", 'wellness-theme' ); ?></span>
					</div>
					<h3 style="font-size: var(--text-base); color: var(--color-primary); margin-bottom: 0.75rem;">
						Q: <?php esc_html_e( 'Does frequent performance anxiety mean I have an incurable physical issue?', 'wellness-theme' ); ?>
					</h3>
					<div style="background:var(--color-surface-subtle); padding: 1rem; border-radius:var(--radius-sm); font-size:var(--text-sm); line-height:1.6; color:var(--color-text-secondary); border-left:3px solid var(--color-secondary);">
						<strong><?php esc_html_e( 'Consultant Response:', 'wellness-theme' ); ?></strong> <?php esc_html_e( 'Not at all. Performance anxiety is an adrenaline response that redirects blood flow. It is common, temporary, and can be resolved through counseling and cognitive reframing techniques.', 'wellness-theme' ); ?>
					</div>
				</div>
			</div>
		</section>

		<!-- Medical Disclaimer Partial (FR-4) -->
		<?php get_template_part( 'template-parts/disclaimer', 'box' ); ?>

	</div>
</main><!-- #primary -->

<?php
get_footer();
