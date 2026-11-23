<?php
/**
 * Template Name: Ask a Question
 *
 * The form and the list of published answers come from the Wellness Consult
 * plugin (SRS v2.0 Section 4.10 and 8.6).
 *
 * @package Wellness_Theme
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-ask-question" role="main">
	<div class="container container-narrow">

		<header class="ask-header text-center" style="margin-bottom: 2.5rem;">
			<span class="badge badge-secondary" style="margin-bottom: 0.5rem;"><?php esc_html_e( 'Free • Anonymous • Private', 'wellness-theme' ); ?></span>
			<h1><?php esc_html_e( 'Ask a Question Anonymously', 'wellness-theme' ); ?></h1>
			<p class="text-muted" style="max-width: 620px; margin: 0 auto;">
				<?php esc_html_e( 'Have a question about nutrition, sleep, fitness or stress? Ask anonymously — no account required.', 'wellness-theme' ); ?>
			</p>
		</header>

		<?php if ( shortcode_exists( 'wc_ask_form' ) ) : ?>
			<?php echo do_shortcode( '[wc_ask_form]' ); ?>
			<?php echo do_shortcode( '[wc_answered_questions limit="10"]' ); ?>
		<?php else : ?>
			<div class="ask-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:2rem; text-align:center; margin-bottom:3rem;">
				<p><?php esc_html_e( 'Anonymous questions are opening soon.', 'wellness-theme' ); ?></p>
				<?php if ( wellness_consultations_enabled() ) : ?>
					<a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>" class="btn btn-outline btn-pill"><?php esc_html_e( 'Book a Private Consultation', 'wellness-theme' ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-outline btn-pill"><?php esc_html_e( 'Read Our Articles', 'wellness-theme' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php get_template_part( 'template-parts/disclaimer', 'box' ); ?>

	</div>
</main><!-- #primary -->

<?php
get_footer();