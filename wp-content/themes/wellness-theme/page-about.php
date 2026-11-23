<?php
/**
 * Template Name: About Us / Our Mission
 *
 * Implements clean editorial About page layout with a small header band,
 * comfortable 720px reading width, clear typography, and styled blockquote.
 *
 * @package Wellness_Theme
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$consult_on   = function_exists( 'wellness_consultations_enabled' ) ? wellness_consultations_enabled() : false;
$questions_on = function_exists( 'wellness_questions_enabled' ) ? wellness_questions_enabled() : false;
?>

<main id="primary" class="site-main site-about" role="main">

	<?php if ( ! $consult_on ) :
		// Fetch page extras.
		while ( have_posts() ) :
			the_post();
		endwhile;
		$_page_label   = wellness_get_page_extra( get_the_ID(), 'label' );
		$_page_sub     = wellness_get_page_extra( get_the_ID(), 'subtitle' );
		$_page_closing = wellness_get_page_extra( get_the_ID(), 'closing_line' );
		$_page_updated = wellness_get_page_extra( get_the_ID(), 'last_updated' );
		?>
		<!-- Editorial Clean Layout with Container -->
		<div class="container about-page-container">

			<!-- Plain Page Header -->
			<header class="about-plain-header">
				<?php if ( $_page_label ) : ?>
					<span class="about-header-label"><?php echo esc_html( $_page_label ); ?></span>
				<?php endif; ?>
				<h1 class="about-page-title"><?php the_title(); ?></h1>
				<?php if ( $_page_sub ) : ?>
					<p class="about-lead-paragraph"><?php echo esc_html( $_page_sub ); ?></p>
				<?php endif; ?>
				<?php if ( $_page_updated ) : ?>
					<p class="text-xs text-muted" style="margin-top: 0.5rem;"><?php echo esc_html( $_page_updated ); ?></p>
				<?php endif; ?>
			</header>

			<div class="about-layout-grid">
				<!-- Desktop Sticky "On this page" TOC (1024px and up) -->
				<aside class="about-sidebar-toc" aria-label="<?php esc_attr_e( 'Table of Contents', 'wellness-theme' ); ?>">
					<div class="about-toc-sticky-box">
						<span class="about-toc-heading"><?php esc_html_e( 'On this page', 'wellness-theme' ); ?></span>
						<nav class="about-toc-nav">
							<ul class="about-toc-list">
								<li><a href="#what-you-will-find" class="about-toc-link"><?php esc_html_e( 'What you will find here', 'wellness-theme' ); ?></a></li>
								<li><a href="#how-we-write" class="about-toc-link"><?php esc_html_e( 'How we write our articles', 'wellness-theme' ); ?></a></li>
								<li><a href="#editorial-standards" class="about-toc-link"><?php esc_html_e( 'Our editorial standards', 'wellness-theme' ); ?></a></li>
								<li><a href="#what-we-are-not" class="about-toc-link"><?php esc_html_e( 'What we are not', 'wellness-theme' ); ?></a></li>
								<li><a href="#your-privacy" class="about-toc-link"><?php esc_html_e( 'Your privacy', 'wellness-theme' ); ?></a></li>
								<li><a href="#who-this-is-for" class="about-toc-link"><?php esc_html_e( 'Who this website is for', 'wellness-theme' ); ?></a></li>
								<li><a href="#contact" class="about-toc-link"><?php esc_html_e( 'Contact', 'wellness-theme' ); ?></a></li>
							</ul>
						</nav>
					</div>
				</aside>

				<!-- Main Article Column (Max-width 720px) -->
				<div class="about-main-column">
					<!-- Mobile Collapsed TOC (Under 1024px) -->
					<details class="about-mobile-toc">
						<summary class="about-mobile-toc-summary">
							<span><?php esc_html_e( 'On this page', 'wellness-theme' ); ?></span>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
						</summary>
						<nav class="about-mobile-toc-nav">
							<ul class="about-toc-list">
								<li><a href="#what-you-will-find" class="about-toc-link"><?php esc_html_e( 'What you will find here', 'wellness-theme' ); ?></a></li>
								<li><a href="#how-we-write" class="about-toc-link"><?php esc_html_e( 'How we write our articles', 'wellness-theme' ); ?></a></li>
								<li><a href="#editorial-standards" class="about-toc-link"><?php esc_html_e( 'Our editorial standards', 'wellness-theme' ); ?></a></li>
								<li><a href="#what-we-are-not" class="about-toc-link"><?php esc_html_e( 'What we are not', 'wellness-theme' ); ?></a></li>
								<li><a href="#your-privacy" class="about-toc-link"><?php esc_html_e( 'Your privacy', 'wellness-theme' ); ?></a></li>
								<li><a href="#who-this-is-for" class="about-toc-link"><?php esc_html_e( 'Who this website is for', 'wellness-theme' ); ?></a></li>
								<li><a href="#contact" class="about-toc-link"><?php esc_html_e( 'Contact', 'wellness-theme' ); ?></a></li>
							</ul>
						</nav>
					</details>

					<article class="about-editorial-prose">
						<?php
						// Rewind and output the page content from the editor.
						rewind_posts();
						if ( have_posts() ) :
							the_post();
							the_content();
						endif;
						?>
						<?php if ( $_page_closing ) : ?>
							<p class="text-muted text-sm" style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--color-border);"><?php echo esc_html( $_page_closing ); ?></p>
						<?php endif; ?>
					</article>
				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var links = document.querySelectorAll('.about-toc-link');
			var headings = Array.from(document.querySelectorAll('.about-editorial-prose h2, .about-callout-card'));

			// Smooth scroll
			links.forEach(function(link) {
				link.addEventListener('click', function(e) {
					var targetId = this.getAttribute('href');
					if (targetId && targetId.startsWith('#')) {
						var target = document.querySelector(targetId);
						if (target) {
							e.preventDefault();
							var headerOffset = 90;
							var elementPosition = target.getBoundingClientRect().top;
							var offsetPosition = elementPosition + window.pageYOffset - headerOffset;
							window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
							// Close mobile details if open
							var details = link.closest('details');
							if (details) details.removeAttribute('open');
						}
					}
				});
			});

			// Active section highlight via IntersectionObserver
			if ('IntersectionObserver' in window && headings.length) {
				var observer = new IntersectionObserver(function(entries) {
					entries.forEach(function(entry) {
						if (entry.isIntersecting) {
							var id = entry.target.getAttribute('id');
							links.forEach(function(l) {
								if (l.getAttribute('href') === '#' + id) {
									l.classList.add('is-active');
								} else {
									l.classList.remove('is-active');
								}
							});
						}
					});
				}, { rootMargin: '-20% 0px -70% 0px' });

				headings.forEach(function(h) {
					if (h.getAttribute('id')) observer.observe(h);
				});
			}
		});
		</script>
	<?php else : ?>
		<!-- Full Consultant Profile & Clinical Credentials (Restored when consultations switch is ON) -->
		<div class="container about-main-content">
			<section class="about-section about-mission-section" aria-labelledby="mission-heading">
				<div class="about-card-primary">
					<h2 id="mission-heading" class="about-section-heading">
						<span class="about-heading-icon" aria-hidden="true">🌱</span>
						<?php esc_html_e( 'Our Mission & Philosophy', 'wellness-theme' ); ?>
					</h2>
					<div class="about-prose">
						<p><?php esc_html_e( 'Everyday wellness — nutrition, sleep, fitness and stress management — forms the foundation of a healthy, fulfilling life, yet it is often overshadowed by misinformation and conflicting advice. This site was founded to cut through the noise by providing clear, evidence-informed guidance in an accessible and supportive environment.', 'wellness-theme' ); ?></p>
						<p><?php esc_html_e( 'We believe wellness guidance should be practical, stigma-free, and respectful of individual needs. By combining evidence-based content with accessible expert consultations, we help you understand your body, build healthy habits, and make informed decisions about your wellbeing.', 'wellness-theme' ); ?></p>
					</div>
				</div>
			</section>

			<section class="about-section about-consultant-section" aria-labelledby="consultant-heading">
				<div class="section-intro text-center">
					<span class="badge badge-primary"><?php esc_html_e( 'Qualified Clinical Care', 'wellness-theme' ); ?></span>
					<h2 id="consultant-heading"><?php esc_html_e( 'Meet Your Consultant', 'wellness-theme' ); ?></h2>
					<p class="text-muted"><?php esc_html_e( 'Professional medical and psychological counseling tailored to your unique circumstances.', 'wellness-theme' ); ?></p>
				</div>
				<div class="consultant-profile-card">
					<div class="consultant-profile-grid">
						<div class="consultant-avatar-col">
							<div class="consultant-avatar-frame">
								<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="consultant-svg-avatar" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
							</div>
							<span class="badge badge-success consultant-verified-badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> <?php esc_html_e( 'Verified Practitioner', 'wellness-theme' ); ?></span>
						</div>
						<div class="consultant-details-col">
							<h3 class="consultant-name"><?php esc_html_e( 'Dr. M. A. Khan & Clinical Counseling Team', 'wellness-theme' ); ?></h3>
							<p class="consultant-credentials"><strong><?php esc_html_e( 'Certified Wellness Coach — Nutrition, Sleep & Fitness (Demo)', 'wellness-theme' ); ?></strong></p>
							<div class="consultant-meta-list">
								<div class="consultant-meta-item"><span class="meta-label">📋 <?php esc_html_e( 'Professional Registration:', 'wellness-theme' ); ?></span> <span class="meta-value"><?php esc_html_e( 'Licensed Medical Practitioner (PMDC / Reg. #74190-P)', 'wellness-theme' ); ?></span></div>
								<div class="consultant-meta-item"><span class="meta-label">🌍 <?php esc_html_e( 'Practice Scope & Jurisdictions:', 'wellness-theme' ); ?></span> <span class="meta-value"><?php esc_html_e( 'Wellness coaching (demo profile). Worldwide online sessions available.', 'wellness-theme' ); ?></span></div>
								<div class="consultant-meta-item"><span class="meta-label">⏳ <?php esc_html_e( 'Clinical Experience:', 'wellness-theme' ); ?></span> <span class="meta-value"><?php esc_html_e( 'Over 9 years providing evidence-based wellness coaching on nutrition, sleep and lifestyle habits.', 'wellness-theme' ); ?></span></div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<section class="about-cta-section text-center">
				<div class="about-cta-box">
					<h2><?php esc_html_e( 'Ready to Discuss Your Wellness with a Qualified Doctor?', 'wellness-theme' ); ?></h2>
					<p class="text-muted"><?php esc_html_e( 'Book a demo consultation or ask a free anonymous wellness question today.', 'wellness-theme' ); ?></p>
					<div class="about-cta-buttons">
						<a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>" class="btn btn-primary btn-pill"><?php esc_html_e( 'Book a Private Consultation', 'wellness-theme' ); ?> &rarr;</a>
						<?php if ( $questions_on ) : ?>
							<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="btn btn-secondary btn-pill"><?php esc_html_e( 'Ask Anonymously (Free)', 'wellness-theme' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</section>
		</div>
	<?php endif; ?>

</main><!-- #primary -->

<?php
get_footer();
