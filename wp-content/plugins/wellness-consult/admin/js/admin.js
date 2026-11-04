/**
 * Wellness Consult Admin JavaScript
 *
 * Handles confirmations, UI helpers, and dynamic admin interactions.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Generic confirmation prompt for critical admin actions
		$('.wc-confirm-action').on('click', function(e) {
			var message = $(this).data('confirm') || 'Are you sure you want to proceed with this action?';
			if (!confirm(message)) {
				e.preventDefault();
				return false;
			}
		});
	});
})(jQuery);
