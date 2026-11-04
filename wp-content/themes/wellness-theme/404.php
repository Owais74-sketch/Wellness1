<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * Implements SRS v2.0 Section 8.12 404 specifications.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-404" role="main">
	<div class="container container-narrow text-center">

		<div class="error-404-card" style="background:var(--surface); border:1px solid var(--border); border-radius: var(--radius-lg); padding: 3.5rem 2rem; margin: 2rem auto; box-shadow: var(--shadow-sm);">
			<span class="badge badge-accent" style="font-size: var(--text-sm); margin-bottom: 1rem;"><?php esc_html_e( 'Page Not Found', 'wellness-theme' ); ?></span>
			<h1 style="font-size: var(--text-4xl); color: var(--color-primary); margin-bottom: 1rem;"><?php esc_html_e( '404 - Page Not Found', 'wellness-theme' ); ?></h1>
			<p class="text-muted" style="font-size: var(--text-lg); max-width: 580px; margin: 0 auto 2rem;">
				<?php esc_html_e( 'The page or article you are looking for might have been moved, renamed, or is temporarily unavailable.', 'wellness-theme' ); ?>
			</p>

			<!-- Search Bar (SRS 8.12) -->
			<div style="max-width: 480px; margin: 0 auto 2.5rem;">
				<form role="search" method="get" class="search-form-wrap" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<input type="search" placeholder="<?php esc_attr_e( 'Search wellness topics or articles...', 'wellness-theme' ); ?>" value="" name="s" autocomplete="off" />
					<button type="submit"><?php esc_html_e( 'Search', 'wellness-theme' ); ?></button>
				</form>
			</div>

			<!-- Quick Links (SRS 8.12) -->
			<div style="display:flex; justify-content:center; gap: 1rem; flex-wrap:wrap; margin-bottom: 2rem;">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary btn-pill">
					<?php esc_html_e( 'Return Home', 'wellness-theme' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-outline btn-pill">
					<?php esc_html_e( 'Browse Articles', 'wellness-theme' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="btn btn-outline btn-pill">
					<?php esc_html_e( 'Ask Anonymously', 'wellness-theme' ); ?>
				</a>
			</div>

			<!-- Quick Exit Button in 404 (SRS 8.12) -->
			<div style="border-top: 1px solid var(--color-border); padding-top: 1.5rem; display:flex; flex-direction:column; align-items:center; gap: 0.5rem;">
				<span class="text-xs text-muted"><?php esc_html_e( 'Need immediate privacy?', 'wellness-theme' ); ?></span>
				<?php wellness_render_quick_exit_button(); ?>
			</div>
		</div>

	</div>
</main><!-- #primary -->

<?php
get_footer();
