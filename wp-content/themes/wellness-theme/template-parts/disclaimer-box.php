<?php
/**
 * Template part for the medical disclaimer box
 *
 * Implements SRS v2.0 FR-4 & Section 8.4
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="medical-disclaimer-box" role="note" aria-label="<?php esc_attr_e( 'Educational & Medical Disclaimer', 'wellness-theme' ); ?>">
	<div class="disclaimer-header">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<circle cx="12" cy="12" r="10"></circle>
			<line x1="12" y1="8" x2="12" y2="12"></line>
			<line x1="12" y1="16" x2="12.01" y2="16"></line>
		</svg>
		<strong><?php esc_html_e( 'Educational Disclaimer & Scope of Guidance', 'wellness-theme' ); ?></strong>
	</div>
	<p class="disclaimer-text">
		<?php echo wellness_get_medical_disclaimer_text(); ?>
	</p>
	<div class="emergency-alert-strip">
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 2"></polygon>
			<line x1="12" y1="8" x2="12" y2="12"></line>
			<line x1="12" y1="16" x2="12.01" y2="16"></line>
		</svg>
		<span><?php esc_html_e( 'Emergency Note: If you or someone you know is in acute medical distress, please contact your local emergency medical services or nearest hospital immediately.', 'wellness-theme' ); ?></span>
	</div>
</div>
