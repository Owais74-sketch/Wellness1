
<?php
/**
 * The template for displaying the footer
 *
 * Implements SRS v2.0 Section 8.1 & 4.1 FR-4.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	</div><!-- #content -->

	<!-- Site Footer -->
	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="footer-top">
			<div class="container">
				<div class="footer-grid">

					<!-- Col 1: About & Privacy Promise -->
					<div class="footer-col">
						<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
							<?php dynamic_sidebar( 'footer-1' ); ?>
						<?php else : ?>
							<h4><?php bloginfo( 'name' ); ?></h4>
							<p><?php echo esc_html( wellness_footer_about_text() ); ?></p>
							<p class="text-xs text-muted" style="margin-top:0.5rem;">
								<em><?php esc_html_e( 'Private, non-judgmental guidance for individuals and couples worldwide.', 'wellness-theme' ); ?></em>
							</p>
						<?php endif; ?>
					</div>

					<!-- Col 2: Explore Navigation -->
					<div class="footer-col">
						<?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
							<?php dynamic_sidebar( 'footer-2' ); ?>
						<?php else : ?>
							<h4><?php esc_html_e( 'Explore & Consult', 'wellness-theme' ); ?></h4>
							<?php
							if ( has_nav_menu( 'footer' ) ) {
								wp_nav_menu( array(
									'theme_location' => 'footer',
									'menu_class'     => 'footer-links',
									'container'      => false,
									'depth'          => 1,
								) );
							} else {
								?>
								<ul class="footer-links">
									<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'wellness-theme' ); ?></a></li>
									<li><a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>"><?php esc_html_e( 'Articles & Guides', 'wellness-theme' ); ?></a></li>
									<li><a href="<?php echo esc_url( home_url( '/myths-vs-facts/' ) ); ?>"><?php esc_html_e( 'Myths vs Facts', 'wellness-theme' ); ?></a></li>
									<li><a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>"><?php esc_html_e( 'Ask Anonymously (Free)', 'wellness-theme' ); ?></a></li>
									<li><a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>"><strong><?php esc_html_e( 'Book Consultation', 'wellness-theme' ); ?></strong></a></li>
									<li><a href="<?php echo esc_url( home_url( '/track-my-request/' ) ); ?>"><?php esc_html_e( 'Track My Request', 'wellness-theme' ); ?></a></li>
								</ul>
								<?php
							}
							?>
						<?php endif; ?>
					</div>

					<!-- Col 3: Categories & Guidance -->
					<div class="footer-col">
						<?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
							<?php dynamic_sidebar( 'footer-3' ); ?>
						<?php else : ?>
							<h4><?php esc_html_e( 'Health Categories', 'wellness-theme' ); ?></h4>
							<ul class="footer-links">
								<li><a href="<?php echo esc_url( home_url( '/category/intimacy/' ) ); ?>"><?php esc_html_e( 'Intimacy & Relationships', 'wellness-theme' ); ?></a></li>
								<li><a href="<?php echo esc_url( home_url( '/category/mens-health/' ) ); ?>"><?php esc_html_e( "Men's Health", 'wellness-theme' ); ?></a></li>
								<li><a href="<?php echo esc_url( home_url( '/category/womens-health/' ) ); ?>"><?php esc_html_e( "Women's Health", 'wellness-theme' ); ?></a></li>
								<li><a href="<?php echo esc_url( home_url( '/category/lifestyle/' ) ); ?>"><?php esc_html_e( 'Lifestyle & Wellness', 'wellness-theme' ); ?></a></li>
								<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About Our Consultant', 'wellness-theme' ); ?></a></li>
							</ul>
						<?php endif; ?>
					</div>

					<!-- Col 4: Legal & Policies -->
					<div class="footer-col">
						<?php if ( is_active_sidebar( 'footer-4' ) ) : ?>
							<?php dynamic_sidebar( 'footer-4' ); ?>
						<?php else : ?>
							<h4><?php esc_html_e( 'Legal & Compliance', 'wellness-theme' ); ?></h4>
							<?php
							if ( has_nav_menu( 'legal' ) ) {
								wp_nav_menu( array(
									'theme_location' => 'legal',
									'menu_class'     => 'footer-links',
									'container'      => false,
									'depth'          => 1,
								) );
							} else {
								?>
								<ul class="footer-links">
									<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy (GDPR/CCPA)', 'wellness-theme' ); ?></a></li>
									<li><a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>"><?php esc_html_e( 'Terms & Conditions (18+)', 'wellness-theme' ); ?></a></li>
									<li><a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>"><?php esc_html_e( 'Refund & Reschedule Policy', 'wellness-theme' ); ?></a></li>
									<li><a href="<?php echo esc_url( home_url( '/medical-disclaimer/' ) ); ?>"><?php esc_html_e( 'Medical Disclaimer', 'wellness-theme' ); ?></a></li>
									<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact & Support', 'wellness-theme' ); ?></a></li>
								</ul>
								<?php
							}
							?>
						<?php endif; ?>
					</div>

				</div>
			</div>
		</div>

		<!-- Mandatory Medical Disclaimer -->
		<div class="footer-disclaimer-wrap">
			<div class="container">
				<div class="medical-disclaimer-box" role="note" aria-label="<?php esc_attr_e( 'Medical Disclaimer', 'wellness-theme' ); ?>">
					<p>
						<strong>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<circle cx="12" cy="12" r="10"></circle>
								<line x1="12" y1="8" x2="12" y2="12"></line>
								<line x1="12" y1="16" x2="12.01" y2="16"></line>
							</svg>
							<?php esc_html_e( 'Important Notice:', 'wellness-theme' ); ?>
						</strong>
						<?php echo esc_html( wellness_get_medical_disclaimer_text() ); ?>
					</p>

					<div class="emergency-alert-strip">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86"></polygon>
							<line x1="12" y1="8" x2="12" y2="12"></line>
							<line x1="12" y1="16" x2="12.01" y2="16"></line>
						</svg>
						<span><?php esc_html_e( 'This website does not provide emergency medical services. If you are experiencing acute medical symptoms or distress, please reach out to your local emergency services (911, 112, 999) immediately.', 'wellness-theme' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Footer Bottom Bar -->
		<div class="footer-bottom">
			<div class="container">
				<div class="copyright">
					<?php echo wellness_footer_copyright_text(); ?>
				</div>

				<div class="footer-bottom-actions">
					<a href="#page" id="back-to-top" class="back-to-top" aria-label="<?php esc_attr_e( 'Back to top of page', 'wellness-theme' ); ?>">
						<span><?php esc_html_e( 'Back to Top', 'wellness-theme' ); ?></span>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<polyline points="18 15 12 9 6 15"></polyline>
						</svg>
					</a>
				</div>
			</div>
		</div>

	</footer><!-- #colophon -->

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
