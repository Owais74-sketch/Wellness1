<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main" role="main">

	<!-- Hero Foundation Banner -->
	<section class="hero-foundation">
		<div class="container">
			<span class="badge badge-secondary" style="margin-bottom: 1rem;">
				<?php esc_html_e( 'Practical • Evidence-informed • Everyday Wellness', 'wellness-theme' ); ?>
			</span>
			<h1><?php esc_html_e( 'Everyday Wellness, Clearly Explained', 'wellness-theme' ); ?></h1>
			<?php
			$consult_on   = function_exists( 'wellness_consultations_enabled' ) ? wellness_consultations_enabled() : false;
			$questions_on = function_exists( 'wellness_questions_enabled' ) ? wellness_questions_enabled() : false;
			?>
			<p class="lead">
				<?php
				if ( $consult_on ) {
					esc_html_e( 'Explore private consultations, health information, and free anonymous Q&A from anywhere in the world.', 'wellness-theme' );
				} else {
					esc_html_e( 'Explore medically reviewed health information, educational guides, and evidence-based wellness resources.', 'wellness-theme' );
				}
				?>
			</p>

			<div class="hero-actions">
				<?php if ( $consult_on ) : ?>
					<a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>" class="btn btn-secondary btn-pill">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
							<line x1="16" y1="2" x2="16" y2="6"></line>
							<line x1="8" y1="2" x2="8" y2="6"></line>
							<line x1="3" y1="10" x2="21" y2="10"></line>
						</svg>
						<?php esc_html_e( 'Book a Private Consultation', 'wellness-theme' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-secondary btn-pill">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
						<?php esc_html_e( 'Read our articles', 'wellness-theme' ); ?>
					</a>
				<?php endif; ?>

				<?php if ( $questions_on ) : ?>
					<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="btn btn-outline btn-pill">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<circle cx="12" cy="12" r="10"></circle>
							<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
							<line x1="12" y1="17" x2="12.01" y2="17"></line>
						</svg>
						<?php esc_html_e( 'Ask Anonymously (Free)', 'wellness-theme' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<!-- Trust Strip (FR-1 & Section 8.1) -->
	<section class="trust-strip" aria-label="<?php esc_attr_e( 'Trust and Security Features', 'wellness-theme' ); ?>">
		<div class="container">
			<div class="trust-items">
				<div class="trust-item">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
						<polyline points="22 4 12 14.01 9 11.01"></polyline>
					</svg>
					<span><?php esc_html_e( 'Private and confidential', 'wellness-theme' ); ?></span>
				</div>

				<div class="trust-item">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
						<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
					</svg>
					<span><?php esc_html_e( 'Private and confidential • No account required', 'wellness-theme' ); ?></span>
				</div>

				<div class="trust-item">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="12" cy="12" r="10"></circle>
						<path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
						<path d="M2 12h20"></path>
					</svg>
					<span><?php esc_html_e( 'Worldwide Time Zone Support', 'wellness-theme' ); ?></span>
				</div>

				<div class="trust-item">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
					</svg>
					<span><?php esc_html_e( 'Clear health education', 'wellness-theme' ); ?></span>
				</div>
			</div>
		</div>
	</section>

	<!-- Main Content / Posts Loop -->
	<div class="container">
		<?php if ( have_posts() ) : ?>

			<div class="section-header" style="margin-bottom: 2rem;">
				<h2><?php esc_html_e( 'Latest Medical & Wellness Articles', 'wellness-theme' ); ?></h2>
				<p class="text-muted"><?php esc_html_e( 'Health information presented in clear, accessible language.', 'wellness-theme' ); ?></p>
				
				<!-- Category Filter Chips (SRS 8.2) -->
				<div class="category-chips-row" style="display:flex; justify-content:center; gap: 0.5rem; flex-wrap:wrap; margin-top: 1.5rem;">
					<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-primary btn-sm btn-pill">
						<?php esc_html_e( 'All Articles', 'wellness-theme' ); ?>
					</a>
					<?php
					$categories = get_categories( array( 'hide_empty' => false ) );
					foreach ( $categories as $cat ) {
						if ( 'uncategorized' === $cat->slug ) {
							continue;
						}
						printf(
							'<a href="%s" class="btn btn-outline btn-sm btn-pill">%s</a>',
							esc_url( get_category_link( $cat->term_id ) ),
							esc_html( wp_specialchars_decode( $cat->name ) )
						);
					}
					?>
				</div>
			</div>

			<div class="posts-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
						<div class="card-body">
							<div class="card-meta">
								<span class="badge badge-primary"><?php the_category( ', ' ); ?></span>
								<span>&bull;</span>
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							</div>

							<h3 class="card-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>

							<div class="card-excerpt">
								<?php the_excerpt(); ?>
							</div>

							<div class="card-footer">
								<a href="<?php the_permalink(); ?>" class="btn btn-outline" style="min-height:36px; padding: 0.35rem 0.85rem; font-size: var(--text-xs);">
									<?php esc_html_e( 'Read Article', 'wellness-theme' ); ?> &rarr;
								</a>
							</div>
						</div>
					</article>
					<?php
				endwhile;
				?>
			</div>

			<div class="pagination-wrap" style="margin-top: 3rem; text-align: center;">
				<?php
				the_posts_pagination( array(
					'prev_text' => '&larr; ' . esc_html__( 'Previous', 'wellness-theme' ),
					'next_text' => esc_html__( 'Next', 'wellness-theme' ) . ' &rarr;',
				) );
				?>
			</div>

		<?php else : ?>

			<div class="empty-state-card" style="background:var(--surface); border:1px solid var(--border); border-radius: var(--radius-lg); padding: 3rem 2rem; text-align:center; max-width: 680px; margin: 2rem auto;">
				<div style="font-size: 2.5rem; margin-bottom: 1rem;" aria-hidden="true">🌱</div>
				<h3><?php esc_html_e( 'Theme Foundation Ready', 'wellness-theme' ); ?></h3>
				<p class="text-muted" style="margin-bottom: 1.5rem;">
					<?php esc_html_e( 'Phase 1 of the Wellness Theme foundation is active. Comprehensive templates for homepage sections, articles, categories, myths vs facts, anonymous Q&A, and booking will follow in subsequent phases.', 'wellness-theme' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url() ); ?>" class="btn btn-primary btn-pill">
					<?php esc_html_e( 'Go to WordPress Dashboard', 'wellness-theme' ); ?>
				</a>
			</div>

		<?php endif; ?>
	</div>

</main><!-- #primary -->

<?php
get_footer();