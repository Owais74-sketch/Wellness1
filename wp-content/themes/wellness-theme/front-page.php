<?php
/**
 * The template for displaying the front page / homepage
 *
 * Implements SRS v2.0 Section 8.1 Home Page Content Plan.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-front-page" role="main">

	<!-- 1. Hero Section (SRS 8.1) -->
	<section class="hero-section" aria-labelledby="hero-heading">
		<div class="container">
			<div class="hero-content text-center">
				<?php
				$hero_badge = get_theme_mod( 'wellness_hero_badge', __( 'Private and confidential • No account required • Worldwide time zone matching', 'wellness-theme' ) );
				$hero_title = get_theme_mod( 'wellness_hero_headline', __( 'Honest, private answers about intimacy and sexual health.', 'wellness-theme' ) );
				$hero_sub   = get_theme_mod( 'wellness_hero_subtext', __( 'Private consultations and clear health education, with appointments shown in your time zone.', 'wellness-theme' ) );
				$cta1_text  = get_theme_mod( 'wellness_hero_cta1_text', __( 'Book a Private Consultation', 'wellness-theme' ) );
				$cta1_url   = get_theme_mod( 'wellness_hero_cta1_url', '/book-consultation/' );
				$cta2_text  = get_theme_mod( 'wellness_hero_cta2_text', __( 'Ask Anonymously (Free)', 'wellness-theme' ) );
				$cta2_url   = get_theme_mod( 'wellness_hero_cta2_url', '/ask-a-question/' );
				?>
				<span class="badge badge-secondary hero-badge">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
					<?php echo esc_html( $hero_badge ); ?>
				</span>
				<h1 id="hero-heading" class="hero-title">
					<?php echo esc_html( $hero_title ); ?>
				</h1>
				<p class="hero-subtext">
					<?php echo esc_html( $hero_sub ); ?>
				</p>

				<div class="hero-cta-group">
					<a href="<?php echo esc_url( home_url( $cta1_url ) ); ?>" class="btn btn-secondary btn-pill btn-lg">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
						<?php echo esc_html( $cta1_text ); ?>
					</a>

					<a href="<?php echo esc_url( home_url( $cta2_url ) ); ?>" class="btn btn-outline btn-pill btn-lg">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
						<?php echo esc_html( $cta2_text ); ?>
					</a>
				</div>
			</div>
		</div>
	</section>

	<!-- 2. Trust Strip (SRS 8.1) -->
	<section class="trust-strip" aria-label="<?php esc_attr_e( 'Trust and Assurance Standards', 'wellness-theme' ); ?>">
		<div class="container">
			<div class="trust-items">
				<div class="trust-item">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
					<span><?php esc_html_e( 'Private and confidential', 'wellness-theme' ); ?></span>
				</div>
				<div class="trust-item">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
					<span><?php esc_html_e( 'Private and confidential • Alias allowed', 'wellness-theme' ); ?></span>
				</div>
				<div class="trust-item">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
					<span><?php esc_html_e( 'Clear health education', 'wellness-theme' ); ?></span>
				</div>
				<div class="trust-item">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
					<span><?php esc_html_e( 'Worldwide Time Zone Matching', 'wellness-theme' ); ?></span>
				</div>
				<div class="trust-item">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
					<span><?php esc_html_e( 'Secure Online & Manual Payments', 'wellness-theme' ); ?></span>
				</div>
			</div>
		</div>
	</section>

	<!-- 3. Categories Grid (SRS 4.1 FR-3 & 8.1) -->
	<section class="section-categories py-8" aria-labelledby="cat-heading">
		<div class="container">
			<div class="section-intro text-center">
				<span class="badge badge-primary"><?php esc_html_e( 'Explore Health Topics', 'wellness-theme' ); ?></span>
				<h2 id="cat-heading"><?php esc_html_e( 'Carefully Curated Wellness Areas', 'wellness-theme' ); ?></h2>
				<p class="text-muted"><?php esc_html_e( 'Clear, compassionate information on topics that matter most.', 'wellness-theme' ); ?></p>
			</div>

			<div class="categories-grid">
				<?php
				$categories_data = array(
					array( 'title' => __( 'Intimacy & Relationships', 'wellness-theme' ), 'slug' => 'intimacy', 'desc' => __( 'Emotional connection, desire discrepancies, and mutual communication.', 'wellness-theme' ), 'icon' => 'heart' ),
					array( 'title' => __( 'Myths vs Facts', 'wellness-theme' ), 'slug' => 'myths-vs-facts', 'desc' => __( 'Busting common misconceptions with medical and scientific truths.', 'wellness-theme' ), 'icon' => 'check-circle' ),
					array( 'title' => __( "Men's Health", 'wellness-theme' ), 'slug' => 'mens-health', 'desc' => __( 'Performance concerns, hormones, stamina, and physical wellness.', 'wellness-theme' ), 'icon' => 'user' ),
					array( 'title' => __( "Women's Health", 'wellness-theme' ), 'slug' => 'womens-health', 'desc' => __( 'Pain-free intimacy, hormonal balance, libido, and postpartum care.', 'wellness-theme' ), 'icon' => 'smile' ),
					array( 'title' => __( 'Lifestyle & Habit Health', 'wellness-theme' ), 'slug' => 'lifestyle', 'desc' => __( 'Stress reduction, sleep, nutrition, and psychological well-being.', 'wellness-theme' ), 'icon' => 'activity' ),
				);

				foreach ( $categories_data as $cat ) {
					get_template_part( 'template-parts/card', 'category', $cat );
				}
				?>
			</div>
		</div>
	</section>

	<!-- 4. How It Works (SRS 8.1 - 4 Steps) -->
	<section class="section-how-it-works py-8" aria-labelledby="how-heading">
		<div class="container">
			<div class="section-intro text-center">
				<span class="badge badge-accent"><?php esc_html_e( 'Simple & Confidential', 'wellness-theme' ); ?></span>
				<h2 id="how-heading"><?php esc_html_e( 'How Your Private Consultation Works', 'wellness-theme' ); ?></h2>
				<p class="text-muted"><?php esc_html_e( 'From booking to private session in 4 easy, safe steps.', 'wellness-theme' ); ?></p>
			</div>

			<div class="steps-grid">
				<div class="step-card">
					<div class="step-number">1</div>
					<h3><?php esc_html_e( 'Choose Consultation', 'wellness-theme' ); ?></h3>
					<p><?php esc_html_e( 'Select Quick Chat, Standard, Couple, or Follow-up consultation with transparent USD fees.', 'wellness-theme' ); ?></p>
				</div>
				<div class="step-card">
					<div class="step-number">2</div>
					<h3><?php esc_html_e( 'Complete Payment', 'wellness-theme' ); ?></h3>
					<p><?php esc_html_e( 'Pay securely with Stripe/PayPal or submit manual transfer with payment screenshot.', 'wellness-theme' ); ?></p>
				</div>
				<div class="step-card">
					<div class="step-number">3</div>
					<h3><?php esc_html_e( 'We Confirm & Schedule', 'wellness-theme' ); ?></h3>
					<p><?php esc_html_e( 'Our doctor coordinates the exact time in your local time zone and emails your private link.', 'wellness-theme' ); ?></p>
				</div>
				<div class="step-card">
					<div class="step-number">4</div>
					<h3><?php esc_html_e( 'Private Consultation', 'wellness-theme' ); ?></h3>
					<p><?php esc_html_e( 'Meet via secure Google Meet, Zoom, WhatsApp call, or chat with zero judgment.', 'wellness-theme' ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<!-- 5. Latest Articles (SRS 8.1 - 6 newest posts) -->
	<section class="section-latest-articles py-8" aria-labelledby="articles-heading">
		<div class="container">
			<div class="section-header-flex">
				<div>
					<span class="badge badge-primary"><?php esc_html_e( 'Clear Health Education', 'wellness-theme' ); ?></span>
					<h2 id="articles-heading"><?php esc_html_e( 'Latest Medical & Wellness Articles', 'wellness-theme' ); ?></h2>
				</div>
				<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-outline">
					<?php esc_html_e( 'View All Articles', 'wellness-theme' ); ?> &rarr;
				</a>
			</div>

			<div class="posts-grid">
				<?php
				$latest_posts = new WP_Query( array(
					'posts_per_page'      => 6,
					'post_status'         => 'publish',
					'ignore_sticky_posts' => true,
				) );

				if ( $latest_posts->have_posts() ) :
					while ( $latest_posts->have_posts() ) :
						$latest_posts->the_post();
						get_template_part( 'template-parts/content', 'card' );
					endwhile;
					wp_reset_postdata();
				else :
					// Demo article cards for theme preview when fresh install has no posts yet
					for ( $i = 1; $i <= 3; $i++ ) :
						?>
						<article class="card">
							<div class="card-body">
								<div class="card-meta">
									<span class="badge badge-primary"><?php esc_html_e( 'Intimacy Health', 'wellness-theme' ); ?></span>
									<span>&bull;</span>
									<span><?php esc_html_e( '5 min read', 'wellness-theme' ); ?></span>
								</div>
								<h3 class="card-title">
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
										<?php
										if ( 1 === $i ) {
											esc_html_e( 'Understanding Performance Anxiety: Causes & Psychological Tools', 'wellness-theme' );
										} elseif ( 2 === $i ) {
											esc_html_e( 'Communication in the Bedroom: Overcoming Embarrassment', 'wellness-theme' );
										} else {
											esc_html_e( 'Hormonal Fluctuations and Libido: A Clinical Guide', 'wellness-theme' );
										}
										?>
									</a>
								</h3>
								<p class="card-excerpt">
									<?php esc_html_e( 'Explore health information and practical steps to support confidence and emotional connection.', 'wellness-theme' ); ?>
								</p>
								<div class="card-footer">
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'Read', 'wellness-theme' ); ?> &rarr;</a>
								</div>
							</div>
						</article>
						<?php
					endfor;
				endif;
				?>
			</div>
		</div>
	</section>

	<!-- 6. Myths vs Facts Spotlight (SRS 8.1 & 8.5) -->
	<section class="section-myths py-8" aria-labelledby="myths-heading">
		<div class="container">
			<div class="section-intro text-center">
				<span class="badge badge-secondary"><?php esc_html_e( 'Fact-Checked', 'wellness-theme' ); ?></span>
				<h2 id="myths-heading"><?php esc_html_e( 'Common Myths vs Scientific Facts', 'wellness-theme' ); ?></h2>
				<p class="text-muted"><?php esc_html_e( 'Replacing shame and folklore with accurate medical clarity.', 'wellness-theme' ); ?></p>
			</div>

			<div class="myths-preview-grid">
				<div class="myth-fact-card">
					<div class="myth-side">
						<span class="myth-tag"><?php esc_html_e( 'MYTH', 'wellness-theme' ); ?></span>
						<p><?php esc_html_e( '"Sexual problems are always purely physical and permanent."', 'wellness-theme' ); ?></p>
					</div>
					<div class="fact-side">
						<span class="fact-tag"><?php esc_html_e( 'FACT', 'wellness-theme' ); ?></span>
						<p><?php esc_html_e( 'Intimacy concerns frequently involve psychological factors like stress, performance pressure, or fatigue, and counseling can provide practical tools and clarity.', 'wellness-theme' ); ?></p>
					</div>
				</div>

				<div class="myth-fact-card">
					<div class="myth-side">
						<span class="myth-tag"><?php esc_html_e( 'MYTH', 'wellness-theme' ); ?></span>
						<p><?php esc_html_e( '"Desire should always happen naturally and spontaneously in long-term relationships."', 'wellness-theme' ); ?></p>
					</div>
					<div class="fact-side">
						<span class="fact-tag"><?php esc_html_e( 'FACT', 'wellness-theme' ); ?></span>
						<p><?php esc_html_e( 'Responsive desire is clinically normal and common. Intimacy can be nurtured through relaxation, context, and emotional security.', 'wellness-theme' ); ?></p>
					</div>
				</div>
			</div>

			<div class="text-center" style="margin-top: 2rem;">
				<a href="<?php echo esc_url( home_url( '/myths-vs-facts/' ) ); ?>" class="btn btn-outline btn-pill">
					<?php esc_html_e( 'Browse All 15+ Myths vs Facts', 'wellness-theme' ); ?> &rarr;
				</a>
			</div>
		</div>
	</section>

	<!-- 7. Consultant Preview (SRS 8.1 & 4.12) -->
	<section class="section-consultant py-8" aria-labelledby="consultant-heading">
		<div class="container container-narrow">
			<div class="consultant-card">
				<div class="consultant-avatar">
					<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
						<circle cx="12" cy="7" r="4"></circle>
					</svg>
				</div>
				<div class="consultant-details">
					<span class="badge badge-primary"><?php esc_html_e( 'Consultant & Educator', 'wellness-theme' ); ?></span>
					<h3 id="consultant-heading"><?php esc_html_e( 'Healthcare & Counseling Specialist', 'wellness-theme' ); ?></h3>
					<p class="consultant-credentials">
						<?php esc_html_e( 'Consultant Profile & Professional Registration (Configured in Administrator Settings)', 'wellness-theme' ); ?>
					</p>
					<p class="consultant-bio">
						<?php esc_html_e( 'Dedicated to providing a safe, strictly confidential environment for individuals and couples seeking evidence-based guidance worldwide.', 'wellness-theme' ); ?>
					</p>
					<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline btn-sm">
						<?php esc_html_e( 'Read Full Profile & Credentials', 'wellness-theme' ); ?> &rarr;
					</a>
				</div>
			</div>
		</div>
	</section>

	<!-- 8. FAQ Section with Schema Support (SRS 8.1 - 6 questions) -->
	<section class="section-faq py-8" aria-labelledby="faq-heading">
		<div class="container container-narrow">
			<div class="section-intro text-center">
				<span class="badge badge-primary"><?php esc_html_e( 'Frequently Asked Questions', 'wellness-theme' ); ?></span>
				<h2 id="faq-heading"><?php esc_html_e( 'Clear Answers to Common Questions', 'wellness-theme' ); ?></h2>
				<p class="text-muted"><?php esc_html_e( 'Everything you need to know about privacy, consultations, and payment.', 'wellness-theme' ); ?></p>
			</div>

			<div class="faq-accordion">
				<details class="faq-item">
					<summary class="faq-question">
						<span><?php esc_html_e( 'Is my identity and consultation 100% confidential?', 'wellness-theme' ); ?></span>
						<span class="faq-icon" aria-hidden="true">+</span>
					</summary>
					<div class="faq-answer">
						<p><?php esc_html_e( 'Yes. You are completely welcome to use an alias or nickname. No account creation is required on this website. Your contact details and medical messages are encrypted, never shared with third parties, and direct URLs to payment proofs are locked down.', 'wellness-theme' ); ?></p>
					</div>
				</details>

				<details class="faq-item">
					<summary class="faq-question">
						<span><?php esc_html_e( 'How do online checkout and manual transfer payments work?', 'wellness-theme' ); ?></span>
						<span class="faq-icon" aria-hidden="true">+</span>
					</summary>
					<div class="faq-answer">
						<p><?php esc_html_e( 'We support secure online checkout via Stripe and PayPal (where no card data ever touches our server) as well as direct bank transfer, Wise, and approved local wallets with a screenshot upload verified by our team.', 'wellness-theme' ); ?></p>
					</div>
				</details>

				<details class="faq-item">
					<summary class="faq-question">
						<span><?php esc_html_e( 'How are appointments coordinated across different time zones?', 'wellness-theme' ); ?></span>
						<span class="faq-icon" aria-hidden="true">+</span>
					</summary>
					<div class="faq-answer">
						<p><?php esc_html_e( 'During booking, your local time zone is detected and confirmed. All scheduled appointments, reminders, and notifications are sent and tracked in your local time zone as well as the consultant time zone.', 'wellness-theme' ); ?></p>
					</div>
				</details>

				<details class="faq-item">
					<summary class="faq-question">
						<span><?php esc_html_e( 'What is the refund and reschedule policy?', 'wellness-theme' ); ?></span>
						<span class="faq-icon" aria-hidden="true">+</span>
					</summary>
					<div class="faq-answer">
						<p><?php esc_html_e( 'You are entitled to one free reschedule at least 6 hours prior to the scheduled time. Cancellations requested before scheduling or within eligible policy terms receive refunds according to our clear Refund & Reschedule Policy.', 'wellness-theme' ); ?></p>
					</div>
				</details>

				<details class="faq-item">
					<summary class="faq-question">
						<span><?php esc_html_e( 'Who will I be speaking with?', 'wellness-theme' ); ?></span>
						<span class="faq-icon" aria-hidden="true">+</span>
					</summary>
					<div class="faq-answer">
						<p><?php esc_html_e( 'Consultation availability and scope depend on the countries supported. Check the consultant information before booking.', 'wellness-theme' ); ?></p>
					</div>
				</details>

				<details class="faq-item">
					<summary class="faq-question">
						<span><?php esc_html_e( 'Can I ask a question for free anonymously?', 'wellness-theme' ); ?></span>
						<span class="faq-icon" aria-hidden="true">+</span>
					</summary>
					<div class="faq-answer">
						<p><?php esc_html_e( 'Yes! Our Ask Anonymously page allows any visitor to submit questions free of charge. Answers are moderated, anonymized, and published to help educate the community.', 'wellness-theme' ); ?></p>
					</div>
				</details>
			</div>
		</div>
	</section>

	<!-- 9. Bottom Call to Action Banner -->
	<section class="section-cta-banner py-8" style="background: linear-gradient(135deg, var(--heading) 0%, var(--primary) 100%); color: var(--on-primary); text-align: center;">
		<div class="container container-narrow">
			<h2 style="color: var(--on-primary); margin-bottom: 1rem;"><?php esc_html_e( 'Ready to ask a private question?', 'wellness-theme' ); ?></h2>
			<p style="color: var(--on-primary); font-size: var(--text-lg); margin-bottom: 2rem;">
				<?php esc_html_e( 'No judgment, no embarrassing waiting rooms. Just thoughtful answers in a private setting.', 'wellness-theme' ); ?>
			</p>
			<a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>" class="btn btn-secondary btn-pill btn-lg">
				<?php esc_html_e( 'Book Your Private Consultation', 'wellness-theme' ); ?>
			</a>
		</div>
	</section>

</main><!-- #primary -->

<?php
get_footer();
