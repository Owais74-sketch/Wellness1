<?php
/**
 * Template part for displaying an article card in grids
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'card post-card' ); ?>>
	<div class="card-media">
		<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'wellness-card', array( 'class' => 'card-img', 'alt' => the_title_attribute( array( 'echo' => false ) ) ) ); ?>
			<?php else : ?>
				<div class="card-media-placeholder">
					<?php
					$categories = get_the_category();
					$cat_slug   = ! empty( $categories ) ? $categories[0]->slug : '';
					if ( 'nutrition-eating' === $cat_slug ) :
						?>
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
					<?php elseif ( 'myths-vs-facts' === $cat_slug ) : ?>
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
					<?php elseif ( 'fitness-movement' === $cat_slug ) : ?>
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					<?php elseif ( 'stress-mind' === $cat_slug ) : ?>
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
					<?php else : ?>
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</a>
	</div>

	<div class="card-body">
		<div class="card-meta">
			<span class="badge badge-primary"><?php the_category( ', ' ); ?></span>
			<span>&bull;</span>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</div>

		<h3 class="card-title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>

		<div class="card-excerpt">
			<?php the_excerpt(); ?>
		</div>

		<div class="card-footer">
			<a href="<?php the_permalink(); ?>" class="btn btn-outline btn-sm">
				<?php esc_html_e( 'Read More', 'wellness-theme' ); ?> &rarr;
			</a>
		</div>
	</div>
</article>
