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
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="card-media">
			<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<?php the_post_thumbnail( 'wellness-card', array( 'class' => 'card-img', 'alt' => the_title_attribute( array( 'echo' => false ) ) ) ); ?>
			</a>
		</div>
	<?php endif; ?>

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
