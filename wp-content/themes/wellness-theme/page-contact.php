<?php
/**
 * Template Name: Contact & Support
 *
 * Implements SRS v2.0 Section 8.10 Contact Specifications:
 * Form (email, message), support email, WhatsApp assistance, working hours,
 * emergency disclaimer, and quick links to booking and tracking.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$feedback_status  = '';
$feedback_message = '';

// Handle Contact Form Submission
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wc_contact_nonce'] ) ) {
	if ( wp_verify_nonce( sanitize_key( $_POST['wc_contact_nonce'] ), 'wc_submit_contact_form' ) ) {
		$name    = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
		$email   = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
		$subject = isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '';
		$message = isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '';

		if ( ! is_email( $email ) ) {
			$feedback_status  = 'error';
			$feedback_message = esc_html__( 'Please enter a valid email address.', 'wellness-theme' );
		} elseif ( empty( $message ) ) {
			$feedback_status  = 'error';
			$feedback_message = esc_html__( 'Please enter your message before sending.', 'wellness-theme' );
		} else {
			// Email notification to Admin
			$admin_email   = get_option( 'admin_email' );
			$email_subject = sprintf( '[%s] Support Inquiry: %s', get_bloginfo( 'name' ), $subject ? $subject : 'General Inquiry' );
			$email_body    = "Sender: " . ( $name ? $name : 'Anonymous / Not provided' ) . "\r\n";
			$email_body   .= "Email: $email\r\n\r\n";
			$email_body   .= "Message:\r\n$message\r\n";
			$headers       = array( 'Content-Type: text/plain; charset=UTF-8', "Reply-To: $email" );

			@wp_mail( $admin_email, $email_subject, $email_body, $headers );

			$feedback_status  = 'success';
			$feedback_message = esc_html__( 'Thank you. Your message has been received. Our support team typically replies within 4 to 12 hours.', 'wellness-theme' );
		}
	} else {
		$feedback_status  = 'error';
		$feedback_message = esc_html__( 'Security validation failed. Please refresh the page and try again.', 'wellness-theme' );
	}
}

get_header();
?>

<main id="primary" class="site-main site-contact" role="main">

	<!-- 1. Header Section -->
	<header class="contact-hero text-center" style="padding: 3rem 1rem 2rem; border-bottom: 1px solid var(--border); margin-bottom: 2.5rem;">
		<div class="container container-narrow">
			<span class="badge badge-primary" style="margin-bottom: 0.75rem;">
				<?php esc_html_e( 'Get in Touch', 'wellness-theme' ); ?>
			</span>
			<h1 class="entry-title" style="margin-bottom: 0.75rem; font-size: clamp(1.75rem, 3vw, 2.5rem); color: var(--heading);">
				<?php esc_html_e( 'Contact & Customer Support', 'wellness-theme' ); ?>
			</h1>
			<p class="text-muted" style="max-width: 680px; margin: 0 auto; font-size: var(--text-base); line-height: 1.6;">
				<?php esc_html_e( 'Have questions about how private consultations work, need help with payment verification, or looking for technical assistance? We are here to help.', 'wellness-theme' ); ?>
			</p>
		</div>
	</header>

	<div class="container container-narrow">

		<?php if ( wellness_consultations_enabled() ) : ?>
			<!-- 2. Quick Action Direct Links Strip -->
			<div class="contact-quick-links" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 3rem;">
				
				<!-- Booking Link Card -->
				<div class="quick-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg, 12px); padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--shadow-sm);">
					<div>
						<div style="width: 44px; height: 44px; border-radius: 8px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; color: var(--primary); margin-bottom: 1rem;">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
						</div>
						<h3 style="font-size: 1.15rem; color: var(--heading); margin-bottom: 0.5rem; font-weight: 700;">
							<?php esc_html_e( 'Book a Consultation', 'wellness-theme' ); ?>
						</h3>
						<p class="text-muted" style="font-size: 0.875rem; line-height: 1.55; margin-bottom: 1.25rem;">
							<?php esc_html_e( 'Looking for private medical advice or counseling with our doctor? Please use our secure booking system directly.', 'wellness-theme' ); ?>
						</p>
					</div>
					<a href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>" class="btn btn-secondary btn-sm btn-pill" style="align-self: flex-start; text-decoration: none;">
						<?php esc_html_e( 'Go to Booking Page', 'wellness-theme' ); ?> &rarr;
					</a>
				</div>

				<!-- Track Request Link Card -->
				<div class="quick-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg, 12px); padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--shadow-sm);">
					<div>
						<div style="width: 44px; height: 44px; border-radius: 8px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; color: var(--primary); margin-bottom: 1rem;">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
						</div>
						<h3 style="font-size: 1.15rem; color: var(--heading); margin-bottom: 0.5rem; font-weight: 700;">
							<?php esc_html_e( 'Track Existing Request', 'wellness-theme' ); ?>
						</h3>
						<p class="text-muted" style="font-size: 0.875rem; line-height: 1.55; margin-bottom: 1.25rem;">
							<?php esc_html_e( 'Already submitted a booking or uploaded payment proof? Check your status, schedule, or meeting link.', 'wellness-theme' ); ?>
						</p>
					</div>
					<a href="<?php echo esc_url( home_url( '/track-my-request/' ) ); ?>" class="btn btn-outline btn-sm btn-pill" style="align-self: flex-start; text-decoration: none;">
						<?php esc_html_e( 'Track My Request', 'wellness-theme' ); ?> &rarr;
					</a>
				</div>

			</div>
		<?php endif; ?>

		<!-- 3. Direct Contact Channels -->
		<div class="contact-channels" style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-lg, 12px); padding: 2rem; margin-bottom: 3rem;">
			<h2 style="font-size: 1.35rem; color: var(--heading); margin-bottom: 1.25rem; font-weight: 700;">
				<?php esc_html_e( 'Direct Support Channels', 'wellness-theme' ); ?>
			</h2>

			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">
				<!-- Email Channel -->
				<div style="display: flex; gap: 1rem; align-items: flex-start;">
					<div style="width: 40px; height: 40px; border-radius: 8px; background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0;">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
					</div>
					<div>
						<h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: var(--heading); font-weight: 700;">
							<?php esc_html_e( 'Email Support', 'wellness-theme' ); ?>
						</h4>
						<p style="margin: 0 0 0.35rem 0; font-size: 0.875rem; color: var(--text);">
							<a href="mailto:support@wellness.com" style="color: var(--primary); font-weight: 600; text-decoration: underline;">support@wellness.com</a>
						</p>
						<span class="text-xs text-muted"><?php esc_html_e( 'Typical reply: 4–12 hours', 'wellness-theme' ); ?></span>
					</div>
				</div>

				<!-- WhatsApp Channel -->
				<div style="display: flex; gap: 1rem; align-items: flex-start;">
					<div style="width: 40px; height: 40px; border-radius: 8px; background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #25D366; flex-shrink: 0;">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
					</div>
					<div>
						<h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: var(--heading); font-weight: 700;">
							<?php esc_html_e( 'WhatsApp Assistance', 'wellness-theme' ); ?>
						</h4>
						<p style="margin: 0 0 0.35rem 0; font-size: 0.875rem;">
							<a href="https://wa.me/15551234567?text=<?php echo rawurlencode( 'Hello, I have a question regarding the Wellness platform.' ); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--primary); font-weight: 600; text-decoration: underline;">
								+1 (555) 123-4567
							</a>
						</p>
						<span class="text-xs text-muted"><?php esc_html_e( 'Administrative inquiries only', 'wellness-theme' ); ?></span>
					</div>
				</div>

				<!-- Working Hours -->
				<div style="display: flex; gap: 1rem; align-items: flex-start;">
					<div style="width: 40px; height: 40px; border-radius: 8px; background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0;">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
					</div>
					<div>
						<h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: var(--heading); font-weight: 700;">
							<?php esc_html_e( 'Operating Hours', 'wellness-theme' ); ?>
						</h4>
						<p style="margin: 0 0 0.35rem 0; font-size: 0.875rem; color: var(--text);">
							<?php esc_html_e( 'Mon – Sat: 09:00 – 18:00 UTC', 'wellness-theme' ); ?>
						</p>
						<span class="text-xs text-muted"><?php esc_html_e( 'Worldwide Time Zone Coordination', 'wellness-theme' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- 4. Contact Form (SRS 8.10) -->
		<section class="contact-form-section" style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg, 12px); padding: 2.5rem; margin-bottom: 3rem; box-shadow: var(--shadow-sm);" aria-labelledby="form-heading">
			<h2 id="form-heading" style="font-size: 1.4rem; color: var(--heading); margin-bottom: 0.5rem; font-weight: 700;">
				<?php esc_html_e( 'Send a Direct Message', 'wellness-theme' ); ?>
			</h2>
			<p class="text-muted" style="font-size: 0.9rem; margin-bottom: 1.75rem;">
				<?php esc_html_e( 'Fill out this form for questions or feedback about the demo.', 'wellness-theme' ); ?>
			</p>

			<!-- Feedback Notice -->
			<?php if ( ! empty( $feedback_message ) ) : ?>
				<div class="alert alert-<?php echo esc_attr( $feedback_status ); ?>" style="padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; background: <?php echo 'success' === $feedback_status ? 'var(--surface-2)' : 'rgba(220, 38, 38, 0.1)'; ?>; border: 1px solid <?php echo 'success' === $feedback_status ? 'var(--primary)' : 'var(--danger)'; ?>; color: var(--heading);">
					<strong><?php echo 'success' === $feedback_status ? '✔ ' : '⚠ '; ?></strong>
					<?php echo esc_html( $feedback_message ); ?>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( get_permalink() ); ?>" class="contact-form" novalidate>
				<?php wp_nonce_field( 'wc_submit_contact_form', 'wc_contact_nonce' ); ?>

				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
					<!-- Name or Alias -->
					<div>
						<label for="contact_name" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.35rem; color: var(--heading);">
							<?php esc_html_e( 'Name or Alias (Optional)', 'wellness-theme' ); ?>
						</label>
						<input type="text" id="contact_name" name="contact_name" placeholder="<?php esc_attr_e( 'e.g. John or Alias', 'wellness-theme' ); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; background: var(--surface); color: var(--text);" />
					</div>

					<!-- Email Address -->
					<div>
						<label for="contact_email" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.35rem; color: var(--heading);">
							<?php esc_html_e( 'Your Email Address *', 'wellness-theme' ); ?>
						</label>
						<input type="email" id="contact_email" name="contact_email" required placeholder="<?php esc_attr_e( 'you@example.com', 'wellness-theme' ); ?>" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; background: var(--surface); color: var(--text);" />
					</div>
				</div>

				<!-- Subject -->
				<div style="margin-bottom: 1.25rem;">
					<label for="contact_subject" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.35rem; color: var(--heading);">
						<?php esc_html_e( 'Topic / Subject', 'wellness-theme' ); ?>
					</label>
					<select id="contact_subject" name="contact_subject" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; background: var(--surface); color: var(--text);">
						<option value="General Question"><?php esc_html_e( 'General Question about Consultations', 'wellness-theme' ); ?></option>
						<option value="Payment Verification"><?php esc_html_e( 'Payment or Screenshot Verification', 'wellness-theme' ); ?></option>
						<option value="Rescheduling Assistance"><?php esc_html_e( 'Rescheduling or Appointment Help', 'wellness-theme' ); ?></option>
						<option value="Technical Issue"><?php esc_html_e( 'Technical Website Issue', 'wellness-theme' ); ?></option>
						<option value="Feedback"><?php esc_html_e( 'Anonymous Feedback or Suggestion', 'wellness-theme' ); ?></option>
					</select>
				</div>

				<!-- Message -->
				<div style="margin-bottom: 1.5rem;">
					<label for="contact_message" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.35rem; color: var(--heading);">
						<?php esc_html_e( 'Your Message *', 'wellness-theme' ); ?>
					</label>
					<textarea id="contact_message" name="contact_message" rows="5" required placeholder="<?php esc_attr_e( 'How can we help you?', 'wellness-theme' ); ?>" style="width: 100%; padding: 0.75rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; background: var(--surface); color: var(--text);"></textarea>
				</div>

				<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
					<span class="text-xs text-muted">
						<?php esc_html_e( '🔒 Your details are never shared with third parties.', 'wellness-theme' ); ?>
					</span>
					<button type="submit" class="btn btn-secondary btn-pill" style="padding: 0.75rem 2rem; font-weight: 700; cursor: pointer;">
						<?php esc_html_e( 'Send Message', 'wellness-theme' ); ?>
					</button>
				</div>
			</form>
		</section>

		<!-- 5. Emergency Warning Notice (SRS Section 10) -->
		<div class="emergency-notice-box" style="display: flex; gap: 1rem; align-items: flex-start; background: var(--surface-2); border: 1px solid var(--border); border-left: 4px solid var(--danger); border-radius: 8px; padding: 1.25rem 1.5rem; margin-bottom: 3rem;">
			<div style="color: var(--danger); font-size: 1.5rem; line-height: 1;" aria-hidden="true">⚠️</div>
			<div>
				<h4 style="margin: 0 0 0.35rem 0; font-size: 1rem; color: var(--danger); font-weight: 700;">
					<?php esc_html_e( 'Not for Medical Emergencies', 'wellness-theme' ); ?>
				</h4>
				<p class="text-muted" style="margin: 0; font-size: 0.875rem; line-height: 1.55;">
					<?php esc_html_e( 'This general contact form is monitored during standard operating hours. If you or someone you know is in acute medical distress, experiencing severe physical pain, or facing a mental health crisis, please contact your local emergency medical services or nearest hospital immediately.', 'wellness-theme' ); ?>
				</p>
			</div>
		</div>

		<!-- 6. Mandatory Medical Disclaimer Partial (FR-4) -->
		<div style="margin-bottom: 3rem;">
			<?php get_template_part( 'template-parts/disclaimer', 'box' ); ?>
		</div>

	</div>
</main><!-- #primary -->

<?php
get_footer();
