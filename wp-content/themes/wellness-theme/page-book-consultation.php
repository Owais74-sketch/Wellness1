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

get_header();
?>

<main id="primary" class="site-main site-book-consultation" role="main">
	<div class="container">

		<!-- Intro Header (SRS 8.7: Who gives it, privacy promise) -->
		<div class="booking-intro text-center" style="max-width: 780px; margin: 0 auto 2.5rem;">
			<span class="badge badge-secondary" style="margin-bottom: 0.75rem;">
				<?php esc_html_e( 'Private and confidential • Worldwide time zone matching', 'wellness-theme' ); ?>
			</span>
			<h1><?php the_title(); ?></h1>
			<p class="lead text-muted">
				<?php esc_html_e( 'Request a private consultation from the comfort of your home. Use any name or alias you prefer. Your information is handled privately and confidentially.', 'wellness-theme' ); ?>
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
