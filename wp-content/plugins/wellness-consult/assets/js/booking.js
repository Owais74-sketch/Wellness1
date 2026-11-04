/**
 * Wellness Consult Multi-Step Booking JavaScript
 *
 * Implements accessible step navigation, timezone auto-detection,
 * drag-and-drop screenshot validation, sessionStorage persistence,
 * and submit button double-click prevention.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		var $wrapper    = $('#wc-booking-wrapper');
		if (!$wrapper.length) {
			return;
		}

		var currentStep = 1;

		// ==========================================
		// 1. SessionStorage Form Progress (Rule 3)
		// ==========================================
		var STORAGE_KEY = 'wc_booking_form_state';

		function saveFormProgress() {
			try {
				var state = {
					name: $('#wc_field_name').val(),
					age: $('#wc_field_age').val(),
					gender: $('#wc_field_gender').val(),
					country: $('#wc_field_country').val(),
					timezone: $('#wc_field_timezone').val(),
					phone: $('#wc_field_phone').val(),
					email: $('#wc_field_email').val(),
					concern: $('#wc_field_concern').val(),
					message: $('#wc_field_message').val(),
					consult_mode: $('input[name="consult_mode"]:checked').val(),
					preferred_window: $('input[name="preferred_window"]:checked').val()
				};
				sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
			} catch (e) {
				// Gracefully handle disabled sessionStorage in strict privacy modes
			}
		}

		function restoreFormProgress() {
			try {
				var saved = sessionStorage.getItem(STORAGE_KEY);
				if (saved) {
					var state = JSON.parse(saved);
					if (state.name && !$('#wc_field_name').val()) $('#wc_field_name').val(state.name);
					if (state.age && !$('#wc_field_age').val()) $('#wc_field_age').val(state.age);
					if (state.gender && !$('#wc_field_gender').val()) $('#wc_field_gender').val(state.gender);
					if (state.country && !$('#wc_field_country').val()) $('#wc_field_country').val(state.country);
					if (state.timezone && !$('#wc_field_timezone').val()) $('#wc_field_timezone').val(state.timezone);
					if (state.phone && !$('#wc_field_phone').val()) $('#wc_field_phone').val(state.phone);
					if (state.email && !$('#wc_field_email').val()) $('#wc_field_email').val(state.email);
					if (state.concern && !$('#wc_field_concern').val()) $('#wc_field_concern').val(state.concern);
					if (state.message && !$('#wc_field_message').val()) $('#wc_field_message').val(state.message);
					if (state.consult_mode) {
						$('input[name="consult_mode"][value="' + state.consult_mode + '"]').prop('checked', true);
					}
					if (state.preferred_window) {
						$('input[name="preferred_window"][value="' + state.preferred_window + '"]').prop('checked', true);
					}
					updateCharCounter();
				}
			} catch (e) {}
		}

		restoreFormProgress();

		// Save on input change (text fields only, NEVER file)
		$wrapper.on('change input', 'input:not([type="file"]), select, textarea', function() {
			saveFormProgress();
		});

		// ==========================================
		// 2. Timezone Auto-Detection (Rule 1)
		// ==========================================
		try {
			if (window.Intl && typeof window.Intl.DateTimeFormat === 'function') {
				var detectedTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
				var $tzSelect  = $('#wc_field_timezone');
				if (detectedTz && $tzSelect.find('option[value="' + detectedTz + '"]').length) {
					if (!$tzSelect.data('user-touched')) {
						$tzSelect.val(detectedTz);
					}
				}
			}
		} catch (e) {}

		$('#wc_field_timezone').on('change', function() {
			$(this).data('user-touched', true);
		});

		// ==========================================
		// 3. Message Character Counter (max 1000)
		// ==========================================
		function updateCharCounter() {
			var msg = $('#wc_field_message').val() || '';
			$('#wc-message-counter').text(msg.length + ' / 1000');
		}

		$('#wc_field_message').on('input', updateCharCounter);
		updateCharCounter();

		// ==========================================
		// 4. Consultation Type & Mode (CR-1)
		// ==========================================
		function updateModesForSelectedType() {
			var $type = $('input[name="consultation_type"]:checked');
			if (!$type.length) {
				$('#wc-modes-container').hide();
				return;
			}

			$('#wc-modes-container').show();

			var chatEnabled  = $type.data('chat-enabled') == '1';
			var chatFee      = $type.data('chat-fee') || '0.00';
			var voiceEnabled = $type.data('voice-enabled') == '1';
			var voiceFee     = $type.data('voice-fee') || '0.00';
			var videoEnabled = $type.data('video-enabled') == '1';
			var videoFee     = $type.data('video-fee') || '0.00';

			// Update prices on cards
			$('#wc-mode-fee-chat').text('$' + parseFloat(chatFee).toFixed(2) + ' USD');
			$('#wc-mode-fee-voice').text('$' + parseFloat(voiceFee).toFixed(2) + ' USD');
			$('#wc-mode-fee-video').text('$' + parseFloat(videoFee).toFixed(2) + ' USD');

			// Show/hide based on enabled flags
			if (chatEnabled) {
				$('#wc-mode-card-chat').show();
			} else {
				$('#wc-mode-card-chat').hide();
				if ($('#mode_chat').is(':checked')) {
					$('#mode_chat').prop('checked', false);
					$('#wc-mode-card-chat').removeClass('is-selected');
				}
			}

			if (voiceEnabled) {
				$('#wc-mode-card-voice').show();
			} else {
				$('#wc-mode-card-voice').hide();
				if ($('#mode_voice').is(':checked')) {
					$('#mode_voice').prop('checked', false);
					$('#wc-mode-card-voice').removeClass('is-selected');
				}
			}

			if (videoEnabled) {
				$('#wc-mode-card-video').show();
			} else {
				$('#wc-mode-card-video').hide();
				if ($('#mode_video').is(':checked')) {
					$('#mode_video').prop('checked', false);
					$('#wc-mode-card-video').removeClass('is-selected');
				}
			}

			// If no mode is currently checked among visible ones, check the first visible mode
			var $checkedMode = $('input[name="consult_mode"]:checked:visible');
			if (!$checkedMode.length) {
				var $firstVisible = $('input[name="consult_mode"]:visible').first();
				if ($firstVisible.length) {
					$firstVisible.prop('checked', true);
				}
			}

			updateSelectedModeDisplay();
		}

		function updateSelectedModeDisplay() {
			var $type = $('input[name="consultation_type"]:checked');
			var $mode = $('input[name="consult_mode"]:checked');

			$('.wc-mode-card').removeClass('is-selected');
			if ($mode.length) {
				$mode.closest('.wc-mode-card').addClass('is-selected');
			}

			if ($type.length && $mode.length) {
				var typeName = $type.data('name') || 'Consultation Package';
				var modeVal  = $mode.val();
				var fee      = '0.00';
				var modeName = 'Chat';

				if (modeVal === 'chat') {
					fee      = $type.data('chat-fee') || '0.00';
					modeName = '💬 Chat';
				} else if (modeVal === 'voice') {
					fee      = $type.data('voice-fee') || '0.00';
					modeName = '📞 Voice Call';
				} else if (modeVal === 'video') {
					fee      = $type.data('video-fee') || '0.00';
					modeName = '📹 Video Call';
				}

				var formattedFee = '$' + parseFloat(fee).toFixed(2) + ' USD';

				// Update Step 2 Summary
				$('#wc-display-selected-type').text(typeName);
				$('#wc-display-selected-mode').text(modeName);
				$('#wc-display-selected-fee').text(formattedFee);

				// Update dynamic exact fee and copy amount buttons on all payment cards (CR-2)
				$('.wc-dynamic-amount').text(formattedFee);
				$('.wc-copy-amount-btn').attr('data-copy', formattedFee).data('copy', formattedFee);

				// Update Step 3 Summary
				$('#wc-step3-display-type').text(typeName);
				$('#wc-step3-display-mode').text(modeName);
				$('#wc-step3-display-fee').text(formattedFee);
			}
		}

		$('input[name="consultation_type"]').on('change', function() {
			$('.wc-type-card').removeClass('is-selected');
			$(this).closest('.wc-type-card').addClass('is-selected');
			updateModesForSelectedType();
		});

		$('input[name="consult_mode"]').on('change', function() {
			updateSelectedModeDisplay();
		});

		// Keyboard interaction on mode cards
		$('.wc-mode-card').on('keydown', function(e) {
			if (e.key === ' ' || e.key === 'Enter') {
				e.preventDefault();
				$(this).find('input[name="consult_mode"]').prop('checked', true).trigger('change');
			}
		});

		updateModesForSelectedType();

		// ==========================================
		// 5. Payment Method Switcher (Online & Manual)
		// ==========================================
		function updatePaymentMethodUI() {
			var $selected = $('input[name="payment_method_id"]:checked');
			if (!$selected.length) return;

			var kind = $selected.data('kind');
			$('.wc-payment-option-card').removeClass('is-selected');
			$selected.closest('.wc-payment-option-card').addClass('is-selected');

			if (kind === 'online') {
				$('#wc-screenshot-section').slideUp(150);
				$('#wc-trans-id-container').slideUp(150);
				$('#wc-submit-booking-btn').text('Proceed to Secure Checkout →');
			} else {
				$('#wc-screenshot-section').slideDown(150);
				$('#wc-trans-id-container').slideDown(150);
				$('#wc-submit-booking-btn').text('Submit Consultation Request →');
			}
			$('#wc-btn-step-2-next').prop('disabled', false).removeClass('button-disabled');
		}

		$('input[name="payment_method_id"]').on('change', function() {
			updatePaymentMethodUI();
		});

		// Initialize payment method UI state
		updatePaymentMethodUI();

		// ==========================================
		// 5b. Copy to Clipboard Handler (CR-2)
		// ==========================================
		function copyFallback(text) {
			var $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val(text).select();
			try {
				document.execCommand('copy');
			} catch (err) {}
			$temp.remove();
		}

		$wrapper.on('click', '.wc-copy-btn', function(e) {
			e.preventDefault();
			var textToCopy = $(this).attr('data-copy');
			if (!textToCopy) {
				var $target = $(this).siblings('.wc-copy-target');
				if ($target.length) {
					textToCopy = $target.text().trim();
				}
			}
			if (!textToCopy) {
				return;
			}

			var $btn = $(this);
			var origHtml = $btn.html();

			function showSuccess() {
				$btn.addClass('is-copied').html('<span class="dashicons dashicons-yes"></span> Copied!');
				setTimeout(function() {
					$btn.removeClass('is-copied').html(origHtml);
				}, 2000);
			}

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(textToCopy).then(showSuccess).catch(function() {
					copyFallback(textToCopy);
					showSuccess();
				});
			} else {
				copyFallback(textToCopy);
				showSuccess();
			}
		});

		// ==========================================
		// 5c. Country Filtering on Step 2 (CR-2)
		// ==========================================
		function filterPaymentMethodsByCountry(countryCode) {
			var visibleCount = 0;
			$('.wc-payment-option-card[data-method-type="manual"]').each(function() {
				var allowed = $(this).data('countries');
				if (!allowed || !allowed.toString().length) {
					$(this).show();
					visibleCount++;
				} else {
					var list = allowed.toString().split(',');
					if (!countryCode || list.indexOf(countryCode) !== -1) {
						$(this).show();
						visibleCount++;
					} else {
						$(this).hide();
						if ($(this).find('input[name="payment_method_id"]').is(':checked')) {
							$(this).find('input[name="payment_method_id"]').prop('checked', false);
							$(this).removeClass('is-selected');
						}
					}
				}
			});

			if (visibleCount === 0) {
				$('#wc-no-methods-country-alert').slideDown(150);
				$('#wc-btn-step-2-next').prop('disabled', true).addClass('button-disabled');
			} else {
				$('#wc-no-methods-country-alert').slideUp(150);
				// If no visible radio is checked, check the first visible manual radio
				var $checkedVisible = $('input[name="payment_method_id"]:visible:checked');
				if (!$checkedVisible.length) {
					var $firstVis = $('input[name="payment_method_id"]:visible').first();
					if ($firstVis.length) {
						$firstVis.prop('checked', true).trigger('change');
					}
				}
				var $currChecked = $('input[name="payment_method_id"]:visible:checked');
				if ($currChecked.length && $currChecked.data('kind') !== 'online') {
					$('#wc-btn-step-2-next').prop('disabled', false).removeClass('button-disabled');
				}
			}
		}

		// Step 2 Country Selector Change
		$('#wc-step2-country-select').on('change', function() {
			var c = $(this).val();
			if ($('#wc_field_country').val() !== c) {
				$('#wc_field_country').val(c).trigger('change');
			}
			filterPaymentMethodsByCountry(c);
		});

		// Step 3 Country Selector Change
		$('#wc_field_country').on('change', function() {
			var c = $(this).val();
			if ($('#wc-step2-country-select').val() !== c) {
				$('#wc-step2-country-select').val(c);
			}
			filterPaymentMethodsByCountry(c);
		});

		// Initial filter based on current selection
		var initCountry = $('#wc-step2-country-select').val() || $('#wc_field_country').val();
		if (initCountry) {
			filterPaymentMethodsByCountry(initCountry);
		}

		// ==========================================
		// 6. Step Navigation & Focus Management (Rule 1)
		// ==========================================
		function goToStep(step) {
			currentStep = step;

			// Update visible section
			$('.wc-step-section').removeClass('is-visible');
			var $targetSection = $('#wc-step-' + step);
			$targetSection.addClass('is-visible');

			// Update progress nav pills
			for (var i = 1; i <= 3; i++) {
				var $pill = $('#wc-step-pill-' + i);
				$pill.removeClass('is-active is-completed');
				if (i < step) {
					$pill.addClass('is-completed');
				} else if (i === step) {
					$pill.addClass('is-active');
				}
			}

			// Accessible focus transition: move focus to step heading
			var $heading = $targetSection.find('.wc-step-heading');
			$heading.attr('tabindex', '-1').focus();
		}

		$('.wc-btn-next').on('click', function() {
			var nextStep = $(this).data('next');

			if (currentStep === 1) {
				if (!$('input[name="consultation_type"]:checked').length) {
					alert('Please select a consultation package to proceed.');
					return;
				}
				if (!$('input[name="consult_mode"]:checked:visible').length) {
					alert('Please select an available consultation mode (Chat, Voice Call, or Video Call) to proceed.');
					return;
				}
				updateSelectedModeDisplay();
			}

			if (currentStep === 2) {
				var $selMethod = $('input[name="payment_method_id"]:checked');
				if (!$selMethod.length) {
					alert('Please select a payment method.');
					return;
				}
				updatePaymentMethodUI();
			}

			goToStep(nextStep);
		});

		$('.wc-btn-back').on('click', function() {
			var backStep = $(this).data('back');
			goToStep(backStep);
		});

		// ==========================================
		// 7. Client-Side Country & Age Checks
		// ==========================================
		$('#wc_field_country').on('change', function() {
			var $opt = $(this).find('option:selected');
			var isBlocked = $opt.data('blocked') == '1';

			if (isBlocked) {
				$('#wc-country-blocked-alert').slideDown(150);
				$('#wc-submit-booking-btn').prop('disabled', true).addClass('button-disabled');
			} else {
				$('#wc-country-blocked-alert').slideUp(150);
				$('#wc-submit-booking-btn').prop('disabled', false).removeClass('button-disabled');
			}
		});

		$('#wc_field_age').on('input change', function() {
			var val = parseInt($(this).val(), 10);
			if (val > 0 && val < 18) {
				$('#wc-age-warning').slideDown(150);
			} else {
				$('#wc-age-warning').slideUp(150);
			}
		});

		// ==========================================
		// 8. Screenshot Drag & Drop + Preview (Rule 2)
		// ==========================================
		var $fileInput = $('#wc_payment_proof');
		var $dropzone  = $('#wc-dropzone');
		var $prompt    = $('#wc-dropzone-prompt');
		var $preview   = $('#wc-dropzone-preview');
		var $previewImg= $('#wc-preview-img');
		var $previewPdf= $('#wc-preview-pdf');
		var $errBox    = $('#wc-upload-client-error');

		function showUploadError(msg) {
			$errBox.text(msg).slideDown(150);
		}

		function clearUploadError() {
			$errBox.text('').slideUp(150);
		}

		function formatFileSize(bytes) {
			if (bytes < 1024) return bytes + ' B';
			if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
			return (bytes / 1048576).toFixed(2) + ' MB';
		}

		function renderFilePreview(file) {
			clearUploadError();

			// Size check (max 5 MB)
			var maxBytes = (window.wcBookingData && window.wcBookingData.maxUploadSize) ? window.wcBookingData.maxUploadSize : 5242880;
			if (file.size > maxBytes) {
				showUploadError((window.wcBookingData && window.wcBookingData.i18n && window.wcBookingData.i18n.fileTooBig) || 'File exceeds the 5 MB limit.');
				resetDropzone();
				return;
			}

			// Extension / MIME check
			var validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
			var ext = file.name.split('.').pop().toLowerCase();
			if (['jpg', 'jpeg', 'png', 'pdf'].indexOf(ext) === -1) {
				showUploadError((window.wcBookingData && window.wcBookingData.i18n && window.wcBookingData.i18n.invalidFileType) || 'Only JPG, PNG, and PDF files are accepted.');
				resetDropzone();
				return;
			}

			$('#wc-file-display-name').text(file.name);
			$('#wc-file-display-size').text(formatFileSize(file.size));

			if (file.type === 'application/pdf' || ext === 'pdf') {
				$previewImg.hide();
				$previewPdf.show();
				$('#wc-pdf-name').text(file.name);
				$prompt.hide();
				$preview.show();
			} else if (file.type.indexOf('image/') === 0) {
				var reader = new FileReader();
				reader.onload = function(e) {
					$previewPdf.hide();
					$previewImg.attr('src', e.target.result).show();
					$prompt.hide();
					$preview.show();
				};
				reader.readAsDataURL(file);
			}
		}

		function resetDropzone() {
			$fileInput.val('');
			$previewImg.attr('src', '').hide();
			$previewPdf.hide();
			$preview.hide();
			$prompt.show();
		}

		$fileInput.on('change', function() {
			if (this.files && this.files[0]) {
				renderFilePreview(this.files[0]);
			}
		});

		// Drag and drop interaction
		$dropzone.on('dragover dragenter', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).addClass('is-dragover');
		});

		$dropzone.on('dragleave dragend drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).removeClass('is-dragover');
		});

		$dropzone.on('drop', function(e) {
			if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files.length) {
				var file = e.originalEvent.dataTransfer.files[0];
				$fileInput[0].files = e.originalEvent.dataTransfer.files;
				renderFilePreview(file);
			}
		});

		$('#wc-replace-file-btn').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$fileInput.trigger('click');
		});

		$('#wc-remove-file-btn').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			resetDropzone();
			clearUploadError();
		});

		// ==========================================
		// 9. Submit Double-Click Prevention (Rule 3)
		// ==========================================
		$('#wc-booking-multistep-form').on('submit', function(e) {
			var $btn = $('#wc-submit-booking-btn');

			// Check client-side age
			var age = parseInt($('#wc_field_age').val(), 10);
			if (age < 18) {
				e.preventDefault();
				alert('Consultations are strictly for adults aged 18 and older.');
				$('#wc_field_age').focus();
				return false;
			}

			// Check at least one contact
			var phone = $('#wc_field_phone').val().trim();
			var email = $('#wc_field_email').val().trim();
			if (!phone && !email) {
				e.preventDefault();
				alert('Please provide either a WhatsApp phone number or an email address.');
				$('#wc_field_phone').focus();
				return false;
			}

			// Check screenshot if manual
			var selKind = $('input[name="payment_method_id"]:checked').data('kind');
			if (selKind === 'manual' && (!$fileInput[0].files || !$fileInput[0].files.length)) {
				e.preventDefault();
				alert('Please upload your payment transfer screenshot proof before submitting.');
				$('#wc-dropzone').focus();
				return false;
			}

			// Disable submit button on first click
			var loadingText = (selKind === 'online') ? 'Redirecting to secure checkout...' : 'Submitting...';
			$btn.prop('disabled', true).addClass('button-disabled').text(loadingText);
			return true;
		});

	});
})(jQuery);
