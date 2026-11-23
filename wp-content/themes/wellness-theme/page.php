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
			$_page_label   = wellness_get_page_extra( get_the_ID(), 'label' );
			$_page_sub     = wellness_get_page_extra( get_the_ID(), 'subtitle' );
			$_page_closing = wellness_get_page_extra( get_the_ID(), 'closing_line' );
			$_page_updated = wellness_get_page_extra( get_the_ID(), 'last_updated' );
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'standard-page' ); ?>>

				<header class="entry-header text-center" style="margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--color-border);">
					<?php if ( $_page_label ) : ?>
						<span class="badge badge-primary" style="margin-bottom: 0.75rem; display: inline-block;"><?php echo esc_html( $_page_label ); ?></span>
					<?php endif; ?>
					<h1 class="entry-title" style="margin-bottom: 0.5rem;"><?php the_title(); ?></h1>
					<?php if ( $_page_sub ) : ?>
						<p class="text-muted" style="max-width: 680px; margin: 0.5rem auto 0;"><?php echo esc_html( $_page_sub ); ?></p>
					<?php endif; ?>
					<?php if ( $_page_updated ) : ?>
						<p class="text-xs text-muted" style="margin-top: 0.5rem;"><?php echo esc_html( $_page_updated ); ?></p>
					<?php else : ?>
						<p class="text-xs text-muted">
							<?php printf( esc_html__( 'Last updated: %s', 'wellness-theme' ), esc_html( get_the_modified_date() ) ); ?>
						</p>
					<?php endif; ?>
				</header>

				<div class="entry-content" style="font-size: var(--text-base); line-height: 1.8; color: var(--color-text-secondary);">
					<?php the_content(); ?>
				</div>

				<?php if ( $_page_closing ) : ?>
					<p class="text-muted text-sm" style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--color-border);"><?php echo esc_html( $_page_closing ); ?></p>
				<?php endif; ?>

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
