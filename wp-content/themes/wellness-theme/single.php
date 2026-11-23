<?php
/**
 * The template for displaying all single posts
 *
 * Implements SRS v2.0 Section 8.4 Single Article specifications.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-single-post" role="main">
	<div class="container container-narrow">

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-article' ); ?>>

				<!-- 1. Top Meta Header (SRS 8.4) -->
				<header class="entry-header" style="margin-bottom: 2rem;">
					<!-- Breadcrumb -->
					<nav class="breadcrumb-nav text-xs text-muted" aria-label="<?php esc_attr_e( 'Breadcrumb', 'wellness-theme' ); ?>" style="margin-bottom: 1rem;">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'wellness-theme' ); ?></a> &raquo;
						<span><?php the_category( ', ' ); ?></span> &raquo;
						<span aria-current="page"><?php the_title(); ?></span>
					</nav>

					<div class="article-category-badge" style="margin-bottom: 0.75rem;">
						<span class="badge badge-primary"><?php the_category( ', ' ); ?></span>
					</div>

					<h1 class="entry-title" style="margin-bottom: 1rem;"><?php the_title(); ?></h1>

					<div class="article-meta-row" style="display:flex; align-items:center; gap: 1rem; flex-wrap:wrap; font-size: var(--text-sm); color: var(--color-text-muted); border-bottom: 1px solid var(--color-border); padding-bottom: 1rem;">
						<span>
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle;" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
							<?php
							$content = get_post_field( 'post_content', get_the_ID() );
							$word_count = str_word_count( strip_tags( $content ) );
							$reading_time = max( 1, ceil( $word_count / 200 ) );
							printf( esc_html__( '%d min read', 'wellness-theme' ), $reading_time );
							?>
						</span>
						<span>&bull;</span>
						<time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
							<?php printf( esc_html__( 'Updated: %s', 'wellness-theme' ), esc_html( get_the_modified_date() ) ); ?>
						</time>
					</div>
				</header>

				<!-- Featured Image if available -->
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="single-featured-media" style="margin-bottom: 2rem; border-radius: var(--radius-lg); overflow:hidden;">
						<?php the_post_thumbnail( 'large', array( 'class' => 'featured-img' ) ); ?>
					</div>
				<?php endif; ?>

				<!-- Key-Points Box (SRS 8.4) -->
				<div class="key-points-box" style="background: var(--color-surface-subtle); border-left: 4px solid var(--color-secondary); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem;">
					<h4 style="margin-bottom: 0.5rem; color: var(--color-primary); font-size: var(--text-base);">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle;" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
						<?php echo esc_html( get_theme_mod( 'wellness_single_key_points_title', __( 'Key Takeaways at a Glance', 'wellness-theme' ) ) ); ?>
					</h4>
					<p class="text-sm" style="margin: 0; color: var(--color-text-secondary);">
						<?php echo esc_html( get_theme_mod( 'wellness_single_key_points_text', __( 'This educational article covers physiological and psychological factors, scientifically proven strategies, and clear signs of when you should consult a licensed doctor.', 'wellness-theme' ) ) ); ?>
					</p>
				</div>

				<!-- Article Content -->
				<div class="entry-content article-body" style="font-size: var(--text-base); line-height: 1.8; color: var(--color-text-secondary);">
					<?php the_content(); ?>
				</div>

				<!-- When to see a doctor box (SRS 8.4) -->
				<div class="doctor-alert-box" style="background: var(--surface-2); border: 1px solid var(--border); border-left: 4px solid var(--accent); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; margin: 2.5rem 0;">
					<h4 style="color: var(--heading); margin-bottom: 0.5rem; font-size: var(--text-base);">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle;" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
						<?php echo esc_html( get_theme_mod( 'wellness_single_doctor_box_title', __( 'When to Consult a Qualified Doctor', 'wellness-theme' ) ) ); ?>
					</h4>
					<p class="text-sm" style="margin:0; color: var(--text);">
						<?php echo esc_html( get_theme_mod( 'wellness_single_doctor_box_text', __( 'If symptoms persist for over 3 months, cause emotional or relationship distress, or are accompanied by severe pain or sudden changes, an individual private consultation is strongly recommended.', 'wellness-theme' ) ) ); ?>
					</p>
				</div>

				<!-- Medical Disclaimer Partial (FR-4 & SRS 8.4) -->
				<div style="margin: 2.5rem 0;">
					<?php get_template_part( 'template-parts/disclaimer', 'box' ); ?>
				</div>

				<!-- Author / Consultant Box (SRS 4.12 & 8.4) -->
				<div class="author-reviewer-card" style="background:var(--color-surface); border:1px solid var(--color-border); border-radius: var(--radius-lg); padding: 1.5rem; display:flex; gap: 1.25rem; align-items:center; margin-bottom: 2.5rem;">
					<div class="author-avatar" style="width: 60px; height:60px; border-radius:50%; background: var(--color-primary-light); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					</div>
					<div>
						<span class="text-xs text-muted" style="text-transform:uppercase; font-weight:700; letter-spacing:0.04em;"><?php echo esc_html( get_theme_mod( 'wellness_single_author_label', __( 'Consultant Author', 'wellness-theme' ) ) ); ?></span>
						<h4 style="margin: 0.15rem 0; font-size: var(--text-lg); color: var(--color-primary);"><?php the_author(); ?></h4>
						<p class="text-sm text-muted" style="margin:0;">
							<?php esc_html_e( 'Educational & wellness guidance based on evidence-based sources.', 'wellness-theme' ); ?>
						</p>
					</div>
				</div>

				<!-- Share Options: Copy Link and WhatsApp Only (SRS 8.4) -->
				<div class="article-share-strip" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap: 1rem; padding: 1rem 0; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); margin-bottom: 2.5rem;">
					<span class="text-sm" style="font-weight:600; color:var(--color-primary);"><?php echo esc_html( get_theme_mod( 'wellness_single_share_label', __( 'Share this article:', 'wellness-theme' ) ) ); ?></span>
					<div style="display:flex; gap: 0.5rem;">
						<!-- WhatsApp Share -->
						<a href="https://api.whatsapp.com/send?text=<?php echo rawurlencode( get_the_title() . ' - ' . get_permalink() ); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle;" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
							<?php esc_html_e( 'Share via WhatsApp', 'wellness-theme' ); ?>
						</a>
						<!-- Copy Link Button -->
						<button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(window.location.href); alert('<?php echo esc_js( __( 'Link copied to clipboard', 'wellness-theme' ) ); ?>');">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline; vertical-align:middle;" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
							<?php esc_html_e( 'Copy Link', 'wellness-theme' ); ?>
						</button>
					</div>
				</div>

				<!-- Call to Action Blocks (SRS 8.4) -->
				<?php
				$consult_on   = function_exists( 'wellness_consultations_enabled' ) ? wellness_consultations_enabled() : false;
				$questions_on = function_exists( 'wellness_questions_enabled' ) ? wellness_questions_enabled() : false;
				?>
				<div class="article-cta-box" style="background: linear-gradient(135deg, var(--color-surface) 0%, var(--color-primary-light) 100%); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 2rem; text-align:center; margin-bottom: 3rem;">
					<?php if ( $consult_on ) : ?>
						<h3 style="color: var(--color-primary); margin-bottom: 0.5rem;"><?php esc_html_e( 'Have Questions About What You Just Read?', 'wellness-theme' ); ?></h3>
						<p class="text-muted" style="max-width: 580px; margin: 0 auto 1.5rem;">
							<?php esc_html_e( 'Request a private consultation, or ask your question anonymously for free.', 'wellness-theme' ); ?>
						</p>
						<div style="display:flex; justify-content:center; gap: 1rem; flex-wrap:wrap;">
							<a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>" class="btn btn-secondary btn-pill">
								<?php esc_html_e( 'Book Private Consultation', 'wellness-theme' ); ?>
							</a>
							<?php if ( $questions_on ) : ?>
								<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="btn btn-outline btn-pill">
									<?php esc_html_e( 'Ask Anonymously (Free)', 'wellness-theme' ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php elseif ( $questions_on ) : ?>
						<h3 style="color: var(--color-primary); margin-bottom: 0.5rem;"><?php esc_html_e( 'Have Questions About What You Just Read?', 'wellness-theme' ); ?></h3>
						<p class="text-muted" style="max-width: 580px; margin: 0 auto 1.5rem;">
							<?php esc_html_e( 'Ask your intimate wellness question anonymously and free of charge.', 'wellness-theme' ); ?>
						</p>
						<div style="display:flex; justify-content:center; gap: 1rem; flex-wrap:wrap;">
							<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="btn btn-secondary btn-pill">
								<?php esc_html_e( 'Ask Anonymously (Free)', 'wellness-theme' ); ?>
							</a>
						</div>
					<?php else : ?>
						<h3 style="color: var(--color-primary); margin-bottom: 0.5rem;"><?php esc_html_e( 'Want to Learn More?', 'wellness-theme' ); ?></h3>
						<p class="text-muted" style="max-width: 580px; margin: 0 auto 1.5rem;">
							<?php esc_html_e( 'Browse our comprehensive library of medically reviewed articles, myths vs facts, and guides.', 'wellness-theme' ); ?>
						</p>
						<div style="display:flex; justify-content:center; gap: 1rem; flex-wrap:wrap;">
							<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-secondary btn-pill">
								<?php esc_html_e( 'Explore All Articles', 'wellness-theme' ); ?>
							</a>
							<a href="<?php echo esc_url( home_url( '/myths-vs-facts/' ) ); ?>" class="btn btn-outline btn-pill">
								<?php esc_html_e( 'Browse Myths vs Facts', 'wellness-theme' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>

			</article>
			<?php
		endwhile;
		?>

	</div>
</main><!-- #primary -->

<?php
get_footer();
