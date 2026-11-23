<?php
/**
 * The template for displaying the front page / homepage
 *
 * Implements SRS v2.0 Section 8.1 Home Page Content Plan.
 * Features a classic, dignified medical and educational aesthetic.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$consult_on   = function_exists( 'wellness_consultations_enabled' ) ? wellness_consultations_enabled() : false;
$questions_on = function_exists( 'wellness_questions_enabled' ) ? wellness_questions_enabled() : false;

get_header();
?>

<main id="primary" class="site-main site-front-page" role="main">

	<!-- 1. Home Hero Banner -->
	<?php
	$hero_title = get_theme_mod( 'wellness_hero_title', __( 'Everyday wellness, clearly explained', 'wellness-theme' ) );
	if ( ! empty( trim( $hero_title ) ) ) :
		$hero_img_id       = get_theme_mod( 'wellness_home_hero_image', 134 );
		$hero_label        = get_theme_mod( 'wellness_hero_label', __( 'Wellness guidance, explained', 'wellness-theme' ) );
		$hero_subtext      = get_theme_mod( 'wellness_hero_subtitle', __( 'Clear, well-sourced, judgement-free articles on nutrition, sleep, fitness and stress.', 'wellness-theme' ) );
		$hero_btn1_text    = get_theme_mod( 'wellness_hero_btn1_text', __( 'Read our articles', 'wellness-theme' ) );
		$hero_btn1_url     = get_theme_mod( 'wellness_hero_btn1_url', '/articles/' );
		$hero_btn2_text    = get_theme_mod( 'wellness_hero_btn2_text', __( 'Browse topics', 'wellness-theme' ) );
		$trust_1           = get_theme_mod( 'wellness_trust_1', __( 'Evidence-based', 'wellness-theme' ) );
		$trust_2           = get_theme_mod( 'wellness_trust_2', __( 'Practical and actionable', 'wellness-theme' ) );
		$trust_3           = get_theme_mod( 'wellness_trust_3', __( 'Judgement-free', 'wellness-theme' ) );
		$hero_chip_show    = get_theme_mod( 'wellness_hero_chips_on', true );
		$hero_caption_show = get_theme_mod( 'wellness_hero_caption_on', true );
		$hero_caption_text = get_theme_mod( 'wellness_hero_caption_text', __( 'Illustrative photo', 'wellness-theme' ) );

		// Verify attachment exists; fall back to text-only hero if ID is invalid.
		$has_any_image = ! empty( $hero_img_id ) && wp_attachment_is_image( $hero_img_id );

		// Alt: use attachment alt if set, otherwise our default.
		$img_alt = '';
		if ( $has_any_image ) {
			$img_alt = get_post_meta( $hero_img_id, '_wp_attachment_image_alt', true );
			if ( empty( $img_alt ) ) {
				$img_alt = __( 'Friendly consultant ready to help you online', 'wellness-theme' );
			}
		}

		// WebP companion (ID 135) for the default image (ID 134).
		$webp_id  = ( 134 === (int) $hero_img_id ) ? 135 : 0;
		$webp_src = $webp_id ? wp_get_attachment_url( $webp_id ) : '';
	?>
	<section class="home-hero<?php echo $has_any_image ? ' home-hero--has-image' : ''; ?>" aria-labelledby="hero-heading">
		<div class="container hero-container">
			<?php if ( $has_any_image ) : ?>
				<div class="home-hero-grid">

					<!-- Text column -->
					<div class="home-hero-text">
						<span class="hero-kicker-label"><?php echo esc_html( $hero_label ); ?></span>

						<h1 id="hero-heading" class="home-hero-title">
							<?php echo esc_html( $hero_title ); ?>
						</h1>

						<p class="home-hero-subtext"><?php echo wp_kses_post( $hero_subtext ); ?></p>

						<div class="home-hero-cta">
							<a href="<?php echo esc_url( home_url( $hero_btn1_url ) ); ?>" class="btn btn-primary btn-pill btn-lg">
								<?php echo esc_html( $hero_btn1_text ); ?>
							</a>
							<a href="#topics" class="btn btn-outline btn-pill btn-lg">
								<?php echo esc_html( $hero_btn2_text ); ?>
							</a>
						</div>

						<form role="search" method="get" class="hero-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<input type="hidden" name="post_type" value="post" />
							<span class="hero-search-icon" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
							</span>
							<input type="search" class="hero-search-input" placeholder="<?php esc_attr_e( 'Search articles, topics, guides…', 'wellness-theme' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
							<button type="submit" class="hero-search-submit"><?php esc_html_e( 'Search', 'wellness-theme' ); ?></button>
						</form>

						<div class="home-hero-trust-row">
							<div class="home-hero-trust-item">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
								<span><?php echo esc_html( $trust_1 ); ?></span>
							</div>
							<div class="home-hero-trust-item">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
								<span><?php echo esc_html( $trust_2 ); ?></span>
							</div>
							<div class="home-hero-trust-item">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
								<span><?php echo esc_html( $trust_3 ); ?></span>
							</div>
						</div>
					</div><!-- /.home-hero-text -->

					<!-- Image column -->
					<div class="home-hero-media">
						<div class="hero-img-frame">
							<?php
							// Build the fallback <img> with srcset/sizes via wp_get_attachment_image().
							$_img_attrs = array(
								'class'         => 'hero-doctor-img',
								'alt'           => esc_attr( $img_alt ),
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'decoding'      => 'async',
							);
							$_img_tag = wp_get_attachment_image( $hero_img_id, 'large', false, $_img_attrs );
							if ( $webp_src ) :
								$_webp_meta = wp_get_attachment_metadata( $webp_id );
								$_webp_w    = ! empty( $_webp_meta['width'] ) ? $_webp_meta['width'] : 1023;
								$_webp_h    = ! empty( $_webp_meta['height'] ) ? $_webp_meta['height'] : 1537;
						?>
						<picture>
							<source type="image/webp" srcset="<?php echo esc_url( $webp_src ); ?>" width="<?php echo (int) $_webp_w; ?>" height="<?php echo (int) $_webp_h; ?>" />
							<?php echo $_img_tag; ?>
						</picture>
						<?php else : ?>
							<?php echo $_img_tag; ?>
						<?php endif; ?>

							<?php if ( $hero_chip_show ) : ?>
								<!-- Floating chip: top-right -->
								<div class="hero-chip hero-chip--tr" aria-hidden="true">
									<span class="hero-chip-icon">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
									</span>
									<div class="hero-chip-text">
										<strong><?php esc_html_e( 'Book in minutes', 'wellness-theme' ); ?></strong>
										<span><?php esc_html_e( 'No wait, no paperwork', 'wellness-theme' ); ?></span>
									</div>
								</div>
								<!-- Floating chip: bottom-left -->
								<div class="hero-chip hero-chip--bl" aria-hidden="true">
									<span class="hero-chip-icon">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
									</span>
									<div class="hero-chip-text">
										<strong><?php esc_html_e( 'Video &amp; chat', 'wellness-theme' ); ?></strong>
										<span><?php esc_html_e( 'Online consultations', 'wellness-theme' ); ?></span>
									</div>
								</div>
							<?php endif; ?>
						</div><!-- /.hero-img-frame -->

						<?php if ( $hero_caption_show && $hero_caption_text ) : ?>
							<p class="hero-img-caption"><?php echo esc_html( $hero_caption_text ); ?></p>
						<?php endif; ?>
					</div><!-- /.home-hero-media -->

				</div><!-- /.home-hero-grid -->

			<?php else : // No image — centered text fallback ?>

				<div class="home-hero-inner">
					<span class="hero-kicker-label"><?php echo esc_html( $hero_label ); ?></span>

					<h1 id="hero-heading" class="home-hero-title">
						<?php echo esc_html( $hero_title ); ?>
					</h1>

					<p class="home-hero-subtext"><?php echo wp_kses_post( $hero_subtext ); ?></p>

					<div class="home-hero-cta">
						<a href="<?php echo esc_url( home_url( $hero_btn1_url ) ); ?>" class="btn btn-primary btn-pill btn-lg">
							<?php echo esc_html( $hero_btn1_text ); ?>
						</a>
						<a href="#topics" class="btn btn-outline btn-pill btn-lg">
							<?php echo esc_html( $hero_btn2_text ); ?>
						</a>
					</div>

					<form role="search" method="get" class="hero-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<input type="hidden" name="post_type" value="post" />
						<span class="hero-search-icon" aria-hidden="true">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
						</span>
						<input type="search" class="hero-search-input" placeholder="<?php esc_attr_e( 'Search articles, topics, guides…', 'wellness-theme' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
						<button type="submit" class="hero-search-submit"><?php esc_html_e( 'Search', 'wellness-theme' ); ?></button>
					</form>

					<div class="home-hero-trust-row">
						<div class="home-hero-trust-item">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
							<span><?php echo esc_html( $trust_1 ); ?></span>
						</div>
						<div class="home-hero-trust-item">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
							<span><?php echo esc_html( $trust_2 ); ?></span>
						</div>
						<div class="home-hero-trust-item">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
							<span><?php echo esc_html( $trust_3 ); ?></span>
						</div>
					</div>
				</div><!-- /.home-hero-inner -->

			<?php endif; ?>
		</div><!-- /.container -->
	</section>
	<?php endif; // End hero check ?>

	<?php
	// Query existing categories that have posts (hide empty, exclude Uncategorized)
	$categories_with_posts = get_categories( array(
		'hide_empty' => true,
		'exclude'    => array( get_cat_ID( 'Uncategorized' ) ),
	) );

	$topics_title = get_theme_mod( 'wellness_topics_title', __( 'Browse by topic', 'wellness-theme' ) );
	if ( ! empty( $categories_with_posts ) && ! empty( trim( $topics_title ) ) ) :
		$topics_label    = get_theme_mod( 'wellness_topics_label', __( 'Explore Health Topics', 'wellness-theme' ) );
		$topics_subtitle = get_theme_mod( 'wellness_topics_subtitle', __( 'Respectful, scientifically grounded articles on the subjects that matter most.', 'wellness-theme' ) );
	?>
	<!-- 2. Browse by Topic Section -->
	<section id="topics" class="section-categories py-8" aria-labelledby="topics-heading">
		<div class="container">
			<div class="section-intro text-center">
				<span class="badge badge-primary"><?php echo esc_html( $topics_label ); ?></span>
				<h2 id="topics-heading"><?php echo esc_html( $topics_title ); ?></h2>
				<p class="text-muted"><?php echo wp_kses_post( $topics_subtitle ); ?></p>
			</div>

			<div class="categories-grid">
				<?php
				$cat_icons = array(
					'nutrition-eating' => 'heart',
					'myths-vs-facts' => 'check-circle',
					'fitness-movement' => 'activity',
					'stress-mind'    => 'smile',
					'sleep-recovery' => 'moon',
				);

				foreach ( $categories_with_posts as $cat ) {
					$icon     = isset( $cat_icons[ $cat->slug ] ) ? $cat_icons[ $cat->slug ] : 'activity';
					$cat_desc = $cat->description ? $cat->description : sprintf( __( 'Explore articles in %s.', 'wellness-theme' ), wp_specialchars_decode( $cat->name ) );
					$cat_desc = str_ireplace(
						array( 'medical insights into', 'clinical evidence', 'clinical guidance', 'clinical care', 'clinical', 'counseling' ),
						array( 'Evidence-based insights into', 'scientific evidence', 'educational guidance', 'supportive care', 'evidence-based', 'support' ),
						$cat_desc
					);
					$cat_data = array(
						'title' => wp_specialchars_decode( $cat->name ),
						'slug'  => $cat->slug,
						'desc'  => $cat_desc,
						'icon'  => $icon,
					);
					get_template_part( 'template-parts/card', 'category', $cat_data );
				}
				?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $consult_on ) : ?>
		<!-- 4. How It Works (SRS 8.1 - 4 Steps) -->
		<section class="section-how-it-works py-8" aria-labelledby="how-heading">
			<div class="container">
				<div class="section-intro text-center">
					<span class="badge badge-accent"><?php esc_html_e( 'Simple & Clear', 'wellness-theme' ); ?></span>
					<h2 id="how-heading"><?php esc_html_e( 'How a Wellness Consultation Works', 'wellness-theme' ); ?></h2>
					<p class="text-muted"><?php esc_html_e( 'From booking to your online session in 4 easy steps.', 'wellness-theme' ); ?></p>
				</div>

				<div class="steps-grid">
					<div class="step-card">
						<div class="step-number">01</div>
						<h3><?php esc_html_e( 'Choose Consultation', 'wellness-theme' ); ?></h3>
						<p><?php esc_html_e( 'Select Quick Chat, Standard, Couple, or Follow-up consultation with transparent USD fees.', 'wellness-theme' ); ?></p>
					</div>
					<div class="step-card">
						<div class="step-number">02</div>
						<h3><?php esc_html_e( 'Complete Payment', 'wellness-theme' ); ?></h3>
						<p><?php esc_html_e( 'Pay securely with Card / PayPal or submit manual transfer with payment screenshot.', 'wellness-theme' ); ?></p>
					</div>
					<div class="step-card">
						<div class="step-number">03</div>
						<h3><?php esc_html_e( 'We Confirm & Schedule', 'wellness-theme' ); ?></h3>
						<p><?php esc_html_e( 'Our doctor coordinates the exact time in your local time zone and emails your private link.', 'wellness-theme' ); ?></p>
					</div>
					<div class="step-card">
						<div class="step-number">04</div>
						<h3><?php esc_html_e( 'Private Consultation', 'wellness-theme' ); ?></h3>
						<p><?php esc_html_e( 'Meet via secure Google Meet, Zoom, WhatsApp call, or chat with zero judgment.', 'wellness-theme' ); ?></p>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- 5. Latest Articles (SRS 8.1 - 6 newest posts) -->
	<?php
	$articles_title = get_theme_mod( 'wellness_articles_title', __( 'Latest Articles', 'wellness-theme' ) );
	if ( ! empty( trim( $articles_title ) ) ) :
		$articles_label    = get_theme_mod( 'wellness_articles_label', __( 'Clear Health Education', 'wellness-theme' ) );
		$articles_btn_text = get_theme_mod( 'wellness_articles_btn_text', __( 'View All Articles', 'wellness-theme' ) );
	?>
	<section class="section-latest-articles py-8" aria-labelledby="articles-heading">
		<div class="container">
			<div class="section-header-flex">
				<div>
					<span class="badge badge-primary"><?php echo esc_html( $articles_label ); ?></span>
					<h2 id="articles-heading"><?php echo esc_html( $articles_title ); ?></h2>
				</div>
				<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-outline">
					<?php echo esc_html( $articles_btn_text ); ?> &rarr;
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
					for ( $i = 1; $i <= 3; $i++ ) :
						?>
						<article class="card">
							<div class="card-media">
								<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" aria-hidden="true" tabindex="-1">
									<div class="card-media-placeholder">
										<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
									</div>
								</a>
							</div>
							<div class="card-body">
								<div class="card-meta">
									<span class="badge badge-primary"><?php esc_html_e( 'Wellness', 'wellness-theme' ); ?></span>
									<span>&bull;</span>
									<span><?php esc_html_e( '5 min read', 'wellness-theme' ); ?></span>
								</div>
								<h3 class="card-title">
									<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>">
										<?php
										if ( 1 === $i ) {
											esc_html_e( 'Understanding Sleep Cycles: What Happens While You Rest', 'wellness-theme' );
										} elseif ( 2 === $i ) {
											esc_html_e( 'Building a Balanced Plate: Simple Nutrition Rules That Work', 'wellness-theme' );
										} else {
											esc_html_e( 'Starting a Walking Routine: A Simple Evidence-Based Approach', 'wellness-theme' );
										}
										?>
									</a>
								</h3>
								<p class="card-excerpt">
									<?php esc_html_e( 'Explore health information and practical steps to support confidence and emotional connection.', 'wellness-theme' ); ?>
								</p>
								<div class="card-footer">
									<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'Read', 'wellness-theme' ); ?> &rarr;</a>
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
	<?php endif; // End articles section check ?>

	<!-- 6. Myths vs Facts Spotlight (SRS 8.1 & 8.5) -->
	<?php
	$myths_title = get_theme_mod( 'wellness_myths_title', __( 'Common Myths vs Scientific Facts', 'wellness-theme' ) );
	if ( ! empty( trim( $myths_title ) ) ) :
		$myths_label    = get_theme_mod( 'wellness_myths_label', __( 'MYTHS AND FACTS', 'wellness-theme' ) );
		$myths_subtitle = get_theme_mod( 'wellness_myths_subtitle', __( 'Replacing common misconceptions with evidence-based clarity.', 'wellness-theme' ) );
		$myth_1_text    = get_theme_mod( 'wellness_myth_1_text', __( '"You need to exercise for hours every day to stay healthy."', 'wellness-theme' ) );
		$fact_1_text    = get_theme_mod( 'wellness_fact_1_text', __( 'Even 20–30 minutes of moderate movement most days delivers significant health benefits. Short walks, stretching and low-impact activity all count.', 'wellness-theme' ) );
		$myth_2_text    = get_theme_mod( 'wellness_myth_2_text', __( '"Eating healthy is expensive and time-consuming."', 'wellness-theme' ) );
		$fact_2_text    = get_theme_mod( 'wellness_fact_2_text', __( 'Staples like legumes, oats, frozen vegetables and seasonal produce are affordable and nutritious. Meal planning and batch cooking save both time and money.', 'wellness-theme' ) );
	?>
	<section class="section-myths py-8" aria-labelledby="myths-heading">
		<div class="container">
			<div class="section-intro text-center">
				<span class="badge badge-secondary"><?php echo esc_html( $myths_label ); ?></span>
				<h2 id="myths-heading"><?php echo esc_html( $myths_title ); ?></h2>
				<p class="text-muted"><?php echo wp_kses_post( $myths_subtitle ); ?></p>
			</div>

			<div class="myths-preview-grid">
				<div class="myth-fact-card">
					<div class="myth-side">
						<span class="myth-tag"><?php esc_html_e( 'MYTH', 'wellness-theme' ); ?></span>
						<p><?php echo wp_kses_post( $myth_1_text ); ?></p>
					</div>
					<div class="fact-side">
						<span class="fact-tag"><?php esc_html_e( 'THE FACTS', 'wellness-theme' ); ?></span>
						<p><?php echo wp_kses_post( $fact_1_text ); ?></p>
					</div>
				</div>

				<div class="myth-fact-card">
					<div class="myth-side">
						<span class="myth-tag"><?php esc_html_e( 'MYTH', 'wellness-theme' ); ?></span>
						<p><?php echo wp_kses_post( $myth_2_text ); ?></p>
					</div>
					<div class="fact-side">
						<span class="fact-tag"><?php esc_html_e( 'THE FACTS', 'wellness-theme' ); ?></span>
						<p><?php echo wp_kses_post( $fact_2_text ); ?></p>
					</div>
				</div>
			</div>

			<div class="text-center" style="margin-top: 2.25rem;">
				<a href="<?php echo esc_url( home_url( '/myths-vs-facts/' ) ); ?>" class="btn btn-outline btn-pill">
					<?php esc_html_e( 'Browse All Myths vs Facts', 'wellness-theme' ); ?> &rarr;
				</a>
			</div>
		</div>
	</section>
	<?php endif; // End myths section check ?>

	<?php if ( $consult_on ) : ?>
		<!-- 7. Consultant Preview (SRS 8.1 & 4.12) -->
		<section class="section-consultant py-8" aria-labelledby="consultant-heading">
			<div class="container container-narrow">
				<div class="consultant-card">
					<div class="consultant-avatar" aria-hidden="true">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
							<circle cx="12" cy="7" r="4"></circle>
						</svg>
					</div>
					<div class="consultant-details">
						<span class="badge badge-primary"><?php esc_html_e( 'Certified Wellness Consultant (Demo)', 'wellness-theme' ); ?></span>
						<h3 id="consultant-heading" style="margin-top: 0.5rem; margin-bottom: 0.25rem; font-family: var(--font-serif); font-size: 1.4rem;">
							<?php esc_html_e( 'Demo Consultant', 'wellness-theme' ); ?>
						</h3>
						<p class="consultant-credentials" style="color: var(--primary); font-weight: 600; font-size: 0.9rem; margin-bottom: 0.75rem;">
							<?php esc_html_e( 'Wellness Coach — Nutrition, Sleep & Fitness (Demo Profile)', 'wellness-theme' ); ?>
						</p>
						<p class="consultant-bio" style="font-size: 0.925rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 1.25rem;">
							<?php esc_html_e( 'Experienced wellness coach specialising in nutrition, sleep and everyday fitness. This is a demo profile showing the consultant preview card.', 'wellness-theme' ); ?>
						</p>
						<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline btn-sm">
							<?php esc_html_e( 'Read Full Profile & Credentials', 'wellness-theme' ); ?> &rarr;
						</a>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- 8. FAQ Section with Schema Support (SRS 8.1) -->
	<?php
	$faq_title = get_theme_mod( 'wellness_faq_title', __( 'Clear Answers to Common Questions', 'wellness-theme' ) );
	if ( ! empty( trim( $faq_title ) ) ) :
		$faq_label = get_theme_mod( 'wellness_faq_label', __( 'Frequently Asked Questions', 'wellness-theme' ) );
		$faq_intro = get_theme_mod( 'wellness_faq_intro', $consult_on ? __( 'Everything you need to know about privacy, consultations, and payment.', 'wellness-theme' ) : __( 'Everything you need to know about our content and privacy.', 'wellness-theme' ) );
		$faq_q1    = get_theme_mod( 'wellness_faq_q1', __( 'Is this website a substitute for a doctor?', 'wellness-theme' ) );
		$faq_a1    = get_theme_mod( 'wellness_faq_a1', __( 'No. All articles, answers, and educational materials provided on this website are for educational and informational purposes only. They do not constitute formal medical diagnosis, individualized treatment, or personal care. If you are experiencing persistent or severe symptoms, please consult a qualified healthcare provider.', 'wellness-theme' ) );
		$faq_q2    = get_theme_mod( 'wellness_faq_q2', __( 'Who writes the content?', 'wellness-theme' ) );
		$faq_a2    = get_theme_mod( 'wellness_faq_a2', __( 'Our editorial team, using reputable public-health and medical sources.', 'wellness-theme' ) );
		$faq_q3    = get_theme_mod( 'wellness_faq_q3', __( 'How is my privacy handled?', 'wellness-theme' ) );
		$faq_a3    = get_theme_mod( 'wellness_faq_a3', __( 'Your privacy is paramount. We do not require accounts to read our articles, personal identifying data is never harvested, and quick exit tools are available on every page for immediate privacy.', 'wellness-theme' ) );
	?>
	<section class="section-faq py-8" aria-labelledby="faq-heading">
		<div class="container container-narrow">
			<div class="section-intro text-center">
				<span class="badge badge-primary"><?php echo esc_html( $faq_label ); ?></span>
				<h2 id="faq-heading"><?php echo esc_html( $faq_title ); ?></h2>
				<p class="text-muted">
					<?php echo wp_kses_post( $faq_intro ); ?>
				</p>
			</div>

			<div class="faq-accordion">
				<?php if ( $consult_on ) : ?>
					<details class="faq-item">
						<summary class="faq-question">
							<span><?php esc_html_e( 'Is my information kept private?', 'wellness-theme' ); ?></span>
							<span class="faq-icon" aria-hidden="true">+</span>
						</summary>
						<div class="faq-answer">
							<p><?php esc_html_e( 'Yes. You may use an alias. No account is required. Your contact details are encrypted and never shared with third parties.', 'wellness-theme' ); ?></p>
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
							<p><?php esc_html_e( 'You will work 1-on-1 with a certified wellness coach. This is a portfolio demo — no real consultations or payments occur.', 'wellness-theme' ); ?></p>
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
				<?php else : ?>
					<details class="faq-item">
						<summary class="faq-question">
							<span><?php echo esc_html( $faq_q1 ); ?></span>
							<span class="faq-icon" aria-hidden="true">+</span>
						</summary>
						<div class="faq-answer">
							<p><?php echo wp_kses_post( $faq_a1 ); ?></p>
						</div>
					</details>

					<details class="faq-item">
						<summary class="faq-question">
							<span><?php echo esc_html( $faq_q2 ); ?></span>
							<span class="faq-icon" aria-hidden="true">+</span>
						</summary>
						<div class="faq-answer">
							<p><?php echo wp_kses_post( $faq_a2 ); ?></p>
						</div>
					</details>

					<details class="faq-item">
						<summary class="faq-question">
							<span><?php echo esc_html( $faq_q3 ); ?></span>
							<span class="faq-icon" aria-hidden="true">+</span>
						</summary>
						<div class="faq-answer">
							<p><?php echo wp_kses_post( $faq_a3 ); ?></p>
						</div>
					</details>

					<?php if ( $questions_on ) : ?>
						<details class="faq-item">
							<summary class="faq-question">
								<span><?php esc_html_e( 'Can I ask a question for free anonymously?', 'wellness-theme' ); ?></span>
								<span class="faq-icon" aria-hidden="true">+</span>
							</summary>
							<div class="faq-answer">
								<p><?php esc_html_e( 'Yes! Our Ask Anonymously page allows visitors to submit questions free of charge. Answers are moderated, anonymized, and published to help educate the community.', 'wellness-theme' ); ?></p>
							</div>
						</details>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php endif; // End FAQ check ?>

	<!-- 9. Classic Bottom Call to Action Banner -->
	<?php
	$cta_title_default = $consult_on ? __( 'Ready to try the demo booking?', 'wellness-theme' ) : __( 'Explore Our Health & Wellness Articles', 'wellness-theme' );
	$cta_title         = get_theme_mod( 'wellness_cta_title', $cta_title_default );
	if ( ! empty( trim( $cta_title ) ) ) :
		$cta_text_default     = $consult_on ? __( 'Experience the full booking flow with demo consultants and test payments.', 'wellness-theme' ) : __( 'Thoughtful, evidence-based articles on nutrition, sleep, fitness and stress — all in one place.', 'wellness-theme' );
		$cta_btn_text_default = $consult_on ? __( 'Try the Demo Booking', 'wellness-theme' ) : __( 'Read Our Articles', 'wellness-theme' );
		$cta_text             = get_theme_mod( 'wellness_cta_text', $cta_text_default );
		$cta_btn_text         = get_theme_mod( 'wellness_cta_btn_text', $cta_btn_text_default );
	?>
	<section class="section-cta-banner py-8">
		<div class="container container-narrow">
			<?php if ( $consult_on ) : ?>
				<h2><?php echo esc_html( $cta_title ); ?></h2>
				<p>
					<?php echo wp_kses_post( $cta_text ); ?>
				</p>
				<a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>" class="btn btn-secondary btn-pill btn-lg">
					<?php echo esc_html( $cta_btn_text ); ?>
				</a>
			<?php else : ?>
				<h2><?php echo esc_html( $cta_title ); ?></h2>
				<p>
					<?php echo wp_kses_post( $cta_text ); ?>
				</p>
				<a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>" class="btn btn-secondary btn-pill btn-lg">
					<?php echo esc_html( $cta_btn_text ); ?>
				</a>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; // End bottom CTA check ?>

</main><!-- #primary -->

<?php
get_footer();
