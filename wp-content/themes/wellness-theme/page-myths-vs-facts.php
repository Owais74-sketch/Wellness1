<?php
/**
 * Template Name: Myths vs Facts
 *
 * Implements SRS v2.0 Section 8.5 Myths vs Facts specifications.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-myths-facts" role="main">
	<div class="container">

		<!-- Intro Header (SRS 8.5) -->
		<header class="myths-header text-center" style="margin-bottom: 3rem;">
			<span class="badge badge-secondary" style="margin-bottom: 0.5rem;"><?php esc_html_e( 'Evidence-Based Truths', 'wellness-theme' ); ?></span>
			<h1><?php esc_html_e( 'Sexual Wellness: Myths vs Facts', 'wellness-theme' ); ?></h1>
			<p class="text-muted" style="max-width: 680px; margin: 0 auto; font-size: var(--text-lg);">
				<?php esc_html_e( 'Misinformation can create unnecessary anxiety, guilt, and strain. Explore clear explanations of common sexual wellness topics.', 'wellness-theme' ); ?>
			</p>
		</header>

		<!-- Myths vs Facts Grid (SRS 8.5: Cards with Myth, Fact, explanation, link) -->
		<div class="myths-cards-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 3.5rem;">

			<!-- Card 1 -->
			<div class="myth-fact-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm); display:flex; flex-direction:column;">
				<div class="myth-side" style="background:var(--color-quick-exit-bg); border-left:4px solid var(--color-quick-exit); padding: 1.25rem 1.5rem;">
					<span class="badge badge-accent" style="background:var(--danger); color:var(--on-primary); font-size:0.7rem; margin-bottom:0.35rem;"><?php esc_html_e( 'MYTH', 'wellness-theme' ); ?></span>
					<h3 style="font-size:var(--text-base); color:var(--color-text); margin:0;">
						<?php esc_html_e( '"Intimacy concerns always indicate a loss of love or physical attraction."', 'wellness-theme' ); ?>
					</h3>
				</div>
				<div class="fact-side" style="background:var(--surface); padding: 1.5rem; flex-grow:1; display:flex; flex-direction:column; justify-content:space-between;">
					<div>
						<span class="badge badge-secondary" style="font-size:0.7rem; margin-bottom:0.5rem;"><?php esc_html_e( 'MEDICAL FACT', 'wellness-theme' ); ?></span>
						<p style="font-size:var(--text-sm); line-height:1.6; color:var(--color-text-secondary); margin-bottom:1rem;">
							<?php esc_html_e( 'Intimacy responses are heavily dictated by physical exhaustion, neurochemical balance, medications, and general life stress. Deep love can coexist with temporary intimacy challenges.', 'wellness-theme' ); ?>
						</p>
					</div>
					<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="text-xs" style="color:var(--color-secondary); font-weight:700;">
						<?php esc_html_e( 'Read Detailed Medical Analysis', 'wellness-theme' ); ?> &rarr;
					</a>
				</div>
			</div>

			<!-- Card 2 -->
			<div class="myth-fact-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm); display:flex; flex-direction:column;">
				<div class="myth-side" style="background:var(--color-quick-exit-bg); border-left:4px solid var(--color-quick-exit); padding: 1.25rem 1.5rem;">
					<span class="badge badge-accent" style="background:var(--danger); color:var(--on-primary); font-size:0.7rem; margin-bottom:0.35rem;"><?php esc_html_e( 'MYTH', 'wellness-theme' ); ?></span>
					<h3 style="font-size:var(--text-base); color:var(--color-text); margin:0;">
						<?php esc_html_e( '"Occasional performance issues require immediate herbal or prescription pills."', 'wellness-theme' ); ?>
					</h3>
				</div>
				<div class="fact-side" style="background:var(--surface); padding: 1.5rem; flex-grow:1; display:flex; flex-direction:column; justify-content:space-between;">
					<div>
						<span class="badge badge-secondary" style="font-size:0.7rem; margin-bottom:0.5rem;"><?php esc_html_e( 'MEDICAL FACT', 'wellness-theme' ); ?></span>
						<p style="font-size:var(--text-sm); line-height:1.6; color:var(--color-text-secondary); margin-bottom:1rem;">
							<?php esc_html_e( 'Occasional fluctuations are a completely normal human physiological variation. Self-medicating with unregulated supplements can cause severe cardiovascular side effects.', 'wellness-theme' ); ?>
						</p>
					</div>
					<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="text-xs" style="color:var(--color-secondary); font-weight:700;">
						<?php esc_html_e( 'Read Detailed Medical Analysis', 'wellness-theme' ); ?> &rarr;
					</a>
				</div>
			</div>

			<!-- Card 3 -->
			<div class="myth-fact-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm); display:flex; flex-direction:column;">
				<div class="myth-side" style="background:var(--color-quick-exit-bg); border-left:4px solid var(--color-quick-exit); padding: 1.25rem 1.5rem;">
					<span class="badge badge-accent" style="background:var(--danger); color:var(--on-primary); font-size:0.7rem; margin-bottom:0.35rem;"><?php esc_html_e( 'MYTH', 'wellness-theme' ); ?></span>
					<h3 style="font-size:var(--text-base); color:var(--color-text); margin:0;">
						<?php esc_html_e( '"Discomfort or pain during intimacy is something women just have to tolerate."', 'wellness-theme' ); ?>
					</h3>
				</div>
				<div class="fact-side" style="background:var(--surface); padding: 1.5rem; flex-grow:1; display:flex; flex-direction:column; justify-content:space-between;">
					<div>
						<span class="badge badge-secondary" style="font-size:0.7rem; margin-bottom:0.5rem;"><?php esc_html_e( 'MEDICAL FACT', 'wellness-theme' ); ?></span>
						<p style="font-size:var(--text-sm); line-height:1.6; color:var(--color-text-secondary); margin-bottom:1rem;">
							<?php esc_html_e( 'Pain during intimacy is never normal and should never be endured. Common causes like pelvic floor hypertonicity, hormonal shifts, and vaginismus are highly treatable.', 'wellness-theme' ); ?>
						</p>
					</div>
					<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="text-xs" style="color:var(--color-secondary); font-weight:700;">
						<?php esc_html_e( 'Read Detailed Medical Analysis', 'wellness-theme' ); ?> &rarr;
					</a>
				</div>
			</div>

		</div>

		<!-- Consultation Call To Action -->
		<div class="myths-cta text-center" style="background:var(--color-surface-subtle); border:1px solid var(--color-border); border-radius:var(--radius-lg); padding: 2.5rem; margin-bottom: 3.5rem;">
			<h2 style="font-size:var(--text-2xl); color:var(--color-primary); margin-bottom:0.75rem;">
				<?php esc_html_e( 'Need Personalized Answers to Your Specific Situation?', 'wellness-theme' ); ?>
			</h2>
			<p class="text-muted" style="max-width: 600px; margin:0 auto 1.5rem;">
				<?php esc_html_e( 'Our consultants specialize in compassionate, private discussions designed to relieve anxiety and offer clear medical next steps.', 'wellness-theme' ); ?>
			</p>
			<div style="display:flex; justify-content:center; gap: 1rem; flex-wrap:wrap;">
				<a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>" class="btn btn-secondary btn-pill">
					<?php esc_html_e( 'Book a Private Consultation', 'wellness-theme' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="btn btn-outline btn-pill">
					<?php esc_html_e( 'Ask Anonymously for Free', 'wellness-theme' ); ?>
				</a>
			</div>
		</div>

		<!-- Medical Disclaimer Partial (FR-4) -->
		<?php get_template_part( 'template-parts/disclaimer', 'box' ); ?>

	</div>
</main><!-- #primary -->

<?php
get_footer();
