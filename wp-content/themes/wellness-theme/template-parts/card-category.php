<?php
/**
 * Template part for displaying a category card
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title = ! empty( $args['title'] ) ? $args['title'] : '';
$slug  = ! empty( $args['slug'] ) ? $args['slug'] : '';
$desc  = ! empty( $args['desc'] ) ? $args['desc'] : '';
$icon  = ! empty( $args['icon'] ) ? $args['icon'] : 'activity';

$url = home_url( '/category/' . sanitize_title( $slug ) . '/' );
?>
<div class="category-card">
	<a href="<?php echo esc_url( $url ); ?>" class="category-card-link">
		<div class="category-icon-box" aria-hidden="true">
			<?php if ( 'heart' === $icon ) : ?>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
			<?php elseif ( 'check-circle' === $icon ) : ?>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
			<?php elseif ( 'user' === $icon ) : ?>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
			<?php elseif ( 'smile' === $icon ) : ?>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
			<?php else : ?>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
			<?php endif; ?>
		</div>
		<div class="category-card-content">
			<h3 class="category-card-title"><?php echo esc_html( $title ); ?></h3>
			<p class="category-card-desc"><?php echo esc_html( $desc ); ?></p>
			<span class="category-card-action">
				<?php esc_html_e( 'Explore Topic', 'wellness-theme' ); ?> &rarr;
			</span>
		</div>
	</a>
</div>
