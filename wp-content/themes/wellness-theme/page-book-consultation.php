<?php
/**
 * Template Name: Book Consultation
 *
 * Implements SRS v2.0 Section 8.7 Book Consultation specifications.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Noindex: booking page should not appear in search results.
add_filter( 'wp_robots', 'wp_robots_no_robots' );

get_header();
?>

<main id="primary" class="site-main site-book-consultation" role="main">
	<div class="container">

		<!-- Intro Header (SRS 8.7: Who gives it, privacy promise) -->
		<div class="booking-intro text-center" style="max-width: 780px; margin: 0 auto 2.5rem;">
			<span class="badge badge-secondary" style="margin-bottom: 0.75rem;">
				<?php esc_html_e( 'Demo booking • No real payments • Worldwide time zone support', 'wellness-theme' ); ?>
			</span>
			<h1><?php the_title(); ?></h1>
			<p class="lead text-muted">
				<?php esc_html_e( 'This is a portfolio demo. No real consultations or payments occur. Use any name or alias you prefer.', 'wellness-theme' ); ?>
			</p>
		</div>

		<!-- Dynamic Booking Container (renders shortcode [wc_booking]) -->
		<div class="booking-layout">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>

		<!-- Medical Disclaimer Partial in Booking Footer (FR-4 & Section 10) -->
		<div style="margin-top: 3rem;">
			<?php get_template_part( 'template-parts/disclaimer', 'box' ); ?>
		</div>

	</div>
</main><!-- #primary -->

<?php
get_footer();
