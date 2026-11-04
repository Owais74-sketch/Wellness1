<?php
/**
 * The template for displaying all pages
 *
 * Implements SRS v2.0 Section 8.9 About, 8.10 Contact, and 8.11 Policy Pages.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-page" role="main">
	<div class="container container-narrow">

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'standard-page' ); ?>>

				<header class="entry-header text-center" style="margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--color-border);">
					<h1 class="entry-title" style="margin-bottom: 0.5rem;"><?php the_title(); ?></h1>
					<p class="text-xs text-muted">
						<?php printf( esc_html__( 'Last updated: %s', 'wellness-theme' ), esc_html( get_the_modified_date() ) ); ?>
					</p>
				</header>

				<div class="entry-content" style="font-size: var(--text-base); line-height: 1.8; color: var(--color-text-secondary);">
					<?php the_content(); ?>
				</div>

				<!-- Medical Disclaimer for policy & info pages (FR-4) -->
				<div style="margin-top: 3rem;">
					<?php get_template_part( 'template-parts/disclaimer', 'box' ); ?>
				</div>

			</article>
			<?php
		endwhile;
		?>

	</div>
</main><!-- #primary -->

<?php
get_footer();
