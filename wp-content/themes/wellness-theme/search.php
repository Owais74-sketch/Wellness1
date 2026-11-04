<?php
/**
 * The template for displaying search results pages
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-search-results" role="main">
	<div class="container">

		<header class="page-header text-center" style="margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--color-border);">
			<span class="badge badge-primary" style="margin-bottom: 0.5rem;"><?php esc_html_e( 'Search Results', 'wellness-theme' ); ?></span>
			<h1 class="page-title">
				<?php
				/* translators: %s: search query. */
				printf( esc_html__( 'Search Results for: %s', 'wellness-theme' ), '<span>' . get_search_query() . '</span>' );
				?>
			</h1>
			<p class="text-muted">
				<?php
				global $wp_query;
				printf(
					esc_html( _n( 'Found %d article matching your query.', 'Found %d articles matching your query.', (int) $wp_query->found_posts, 'wellness-theme' ) ),
					(int) $wp_query->found_posts
				);
				?>
			</p>

			<!-- Inline Search Refinement Form -->
			<div style="max-width: 500px; margin: 1.5rem auto 0;">
				<form role="search" method="get" class="search-form-wrap" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<input type="search" placeholder="<?php esc_attr_e( 'Try another search term...', 'wellness-theme' ); ?>" value="<?php echo get_search_query(); ?>" name="s" autocomplete="off" />
					<button type="submit"><?php esc_html_e( 'Search', 'wellness-theme' ); ?></button>
				</form>
			</div>
		</header>

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
			<div class="empty-state-card text-center" style="background:#fff; border:1px solid var(--color-border); border-radius: var(--radius-lg); padding: 3rem; max-width: 600px; margin: 2rem auto;">
				<div style="font-size: 2.5rem; margin-bottom: 1rem;" aria-hidden="true">🔍</div>
				<h3><?php esc_html_e( 'No Matches Found', 'wellness-theme' ); ?></h3>
				<p class="text-muted" style="margin-bottom: 1.5rem;">
					<?php esc_html_e( 'We could not find anything matching your search term. You can ask our doctor a confidential question directly, or browse by health category.', 'wellness-theme' ); ?>
				</p>
				<div style="display:flex; justify-content:center; gap: 0.75rem; flex-wrap:wrap;">
					<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="btn btn-secondary btn-pill">
						<?php esc_html_e( 'Ask Anonymously', 'wellness-theme' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-outline btn-pill">
						<?php esc_html_e( 'Back to Home', 'wellness-theme' ); ?>
					</a>
				</div>
			</div>
		<?php endif; ?>

	</div>
</main><!-- #primary -->

<?php
get_footer();
