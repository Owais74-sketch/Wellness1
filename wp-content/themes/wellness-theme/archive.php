<?php
/**
 * The template for displaying archive and category pages
 *
 * Implements SRS v2.0 Section 8.2 Articles & 8.3 Category Pages.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-archive" role="main">
	<div class="container">

		<!-- Archive Header & Category Intro (SRS 8.3) -->
		<header class="archive-header text-center" style="margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--color-border);">
			<span class="badge badge-primary" style="margin-bottom: 0.5rem;">
				<?php
				if ( is_category() ) {
					esc_html_e( 'Category Archive', 'wellness-theme' );
				} elseif ( is_tag() ) {
					esc_html_e( 'Tag Topic', 'wellness-theme' );
				} else {
					esc_html_e( 'Medical & Wellness Articles', 'wellness-theme' );
				}
				?>
			</span>

			<h1 class="archive-title" style="margin-bottom: 0.75rem;">
				<?php the_archive_title(); ?>
			</h1>

			<div class="archive-description text-muted" style="max-width: 720px; margin: 0 auto; font-size: var(--text-base);">
				<?php
				if ( get_the_archive_description() ) {
					the_archive_description();
				} else {
					echo esc_html( get_theme_mod( 'wellness_archive_fallback_desc', __( 'Explore health information and compassionate guidance in clear, accessible language.', 'wellness-theme' ) ) );
				}
				?>
			</div>

			<!-- Category Filter Chips (SRS 8.2) -->
			<div class="category-chips-row" style="display:flex; justify-content:center; gap: 0.5rem; flex-wrap:wrap; margin-top: 1.5rem;">
				<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-outline btn-sm btn-pill <?php echo ! is_category() ? 'btn-primary' : ''; ?>">
					<?php echo esc_html( get_theme_mod( 'wellness_archive_badge_all', __( 'All Articles', 'wellness-theme' ) ) ); ?>
				</a>
				<?php
				$categories = get_categories( array( 'hide_empty' => false ) );
				foreach ( $categories as $cat ) {
					if ( 'uncategorized' === $cat->slug ) {
						continue;
					}
					$is_active = is_category( $cat->term_id ) ? 'btn-primary' : 'btn-outline';
					printf(
						'<a href="%s" class="btn %s btn-sm btn-pill">%s</a>',
						esc_url( get_category_link( $cat->term_id ) ),
						esc_attr( $is_active ),
						esc_html( wp_specialchars_decode( $cat->name ) )
					);
				}
				?>
			</div>
		</header>

		<!-- Posts Grid (SRS 8.2 - 9 per page with pagination) -->
		<?php if ( have_posts() ) : ?>
			<div class="posts-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'card' );
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
			<div class="empty-state-card text-center" style="background:var(--surface); border:1px solid var(--border); border-radius: var(--radius-lg); padding: 3rem; max-width: 600px; margin: 2rem auto;">
				<h3><?php echo esc_html( get_theme_mod( 'wellness_archive_empty_title', __( 'No Articles Found', 'wellness-theme' ) ) ); ?></h3>
				<p class="text-muted"><?php echo esc_html( get_theme_mod( 'wellness_archive_empty_text', __( 'There are currently no articles in this category. Check back soon as our team regularly publishes new educational content.', 'wellness-theme' ) ) ); ?></p>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-secondary btn-pill" style="margin-top: 1rem;">
					<?php echo esc_html( get_theme_mod( 'wellness_archive_return_home', __( 'Return Home', 'wellness-theme' ) ) ); ?>
				</a>
			</div>
		<?php endif; ?>

		<!-- Medical Disclaimer Partial in Footer of Archive (FR-4) -->
		<div style="margin-top: 3.5rem;">
			<?php get_template_part( 'template-parts/disclaimer', 'box' ); ?>
		</div>

	</div>
</main><!-- #primary -->

<?php
get_footer();
