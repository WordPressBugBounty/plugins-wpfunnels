/**
 * Product Selection for Checkout
 * 
 * @package WPFunnels
 * @since 3.2.0
 */

(function ($) {
	'use strict';

	var WpfnlProductSelection = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
		},

	/**
	 * Bind events
	 */
	bindEvents: function() {
		$(document).on('change', '.wpfnl-product-checkbox', this.handleProductSelection);
		$(document).on('click', '.wpfnl-remove-product', this.handleProductRemove);
	},

	/**
	 * Handle product removal via X button
	 */
	handleProductRemove: function(e) {
		e.preventDefault();
		var $button = $(this);
		var $row = $button.closest('.wpfnl-product-row');
		var $checkbox = $row.find('.wpfnl-product-checkbox');
		var productId = $button.data('product-id');
		var index = $button.data('index');
		var stepId = $('input[name="wpfnl_checkout_id"]').val() || wpfnl_checkout_product_selection.step_id;

		// Uncheck the checkbox
		$checkbox.prop('checked', false);
		$row.removeClass('selected');

		// Show loading state
		WpfnlProductSelection.showLoading();

		// Make AJAX request
		$.ajax({
			url: wpfnl_checkout_product_selection.ajax_url,
			type: 'POST',
			data: {
				action: 'wpfnl_update_product_selection',
				security: wpfnl_checkout_product_selection.nonce,
				product_id: productId,
				index: index,
				action_type: 'remove',
				step_id: stepId
			},
			success: function(response) {
				if (response.success) {
					// Hide the remove button
					$button.fadeOut();

					// Trigger WooCommerce checkout update
					$('body').trigger('update_checkout');
				} else {
					// Revert state on error
					$checkbox.prop('checked', true);
					$row.addClass('selected');
					
					WpfnlProductSelection.showMessage(response.data.message, 'error');
				}
				
				WpfnlProductSelection.hideLoading();
			},
			error: function() {
				// Revert state on error
				$checkbox.prop('checked', true);
				$row.addClass('selected');
				
				WpfnlProductSelection.showMessage('An error occurred. Please try again.', 'error');
				WpfnlProductSelection.hideLoading();
			}
		});
	},		/**
		 * Handle product selection/deselection
		 */
		handleProductSelection: function(e) {
			var $checkbox = $(this);
			var $row = $checkbox.closest('.wpfnl-product-row');
			var productId = $checkbox.data('product-id');
			var index = $checkbox.data('index');
			var isChecked = $checkbox.prop('checked');
			var actionType = isChecked ? 'add' : 'remove';
			var stepId = $('input[name="wpfnl_checkout_id"]').val() || wpfnl_checkout_product_selection.step_id;

			// If radio button, handle select_one logic
			if ($checkbox.attr('type') === 'radio') {
				$('.wpfnl-product-row').removeClass('selected');
				if (isChecked) {
					$row.addClass('selected');
				}
			} else {
				// Checkbox logic
				if (isChecked) {
					$row.addClass('selected');
				} else {
				$row.removeClass('selected');
			}
		}

		// Show loading state
		WpfnlProductSelection.showLoading();
			// Make AJAX request
			$("input[name='_wpfunnels_product_option']").val('selected');
			$.ajax({
				url: wpfnl_checkout_product_selection.ajax_url,
				type: 'POST',
				data: {
					action: 'wpfnl_update_product_selection',
					security: wpfnl_checkout_product_selection.nonce,
					product_id: productId,
					index: index,
					action_type: actionType,
					step_id: stepId
				},
				success: function(response) {
					
					if (response.success) {
						// Show/hide remove button based on selection
						if (isChecked) {
							var removeBtn = '<button type="button" class="wpfnl-remove-product" data-product-id="' + productId + '" data-index="' + index + '" title="Remove product">' +
								'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">' +
								'<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>' +
								'</svg></button>';
							$row.find('.product-remove').html(removeBtn);
						} else {
							$row.find('.product-remove').empty();
						}

						// Trigger WooCommerce checkout update
						$('body').trigger('update_checkout');
						WpfnlProductSelection.hideLoading();
					} else {
						// Revert checkbox state on error
						if ($checkbox.attr('type') === 'radio') {
							$checkbox.prop('checked', false);
							$row.removeClass('selected');
						} else {
							$checkbox.prop('checked', !isChecked);
							if (isChecked) {
								$row.removeClass('selected');
							} else {
								$row.addClass('selected');
							}
						}
						$('body').trigger('update_checkout');
						WpfnlProductSelection.showMessage(response.data.message, 'error');
					}
					
					WpfnlProductSelection.hideLoading();
				},
				error: function() {
					// Revert checkbox state on error
					if ($checkbox.attr('type') === 'radio') {
						$checkbox.prop('checked', false);
						$row.removeClass('selected');
					} else {
						$checkbox.prop('checked', !isChecked);
						if (isChecked) {
							$row.removeClass('selected');
						} else {
							$row.addClass('selected');
						}
					}
					$("input[name='_wpfunnels_product_option']").val('unselected');
					WpfnlProductSelection.showMessage('An error occurred. Please try again.', 'error');
					WpfnlProductSelection.hideLoading();
				}
			});
		},

	/**
	 * Show loading state
	 */
	showLoading: function() {
		$('.wpfnl-product-selection-wrapper').addClass('loading');
		$('.wpfnl-product-checkbox').prop('disabled', true);
		$('.wpfnl-remove-product').prop('disabled', true);
	},

	/**
	 * Hide loading state
	 */
	hideLoading: function() {
		$('.wpfnl-product-selection-wrapper').removeClass('loading');
		$('.wpfnl-product-checkbox').prop('disabled', false);
		$('.wpfnl-remove-product').prop('disabled', false);
	},		/**
		 * Show message
		 */
		showMessage: function(message, type) {
			// Remove existing messages
			$('.wpfnl-product-selection-message').remove();
			
			// Create and show new message
			var $message = $('<div class="wpfnl-product-selection-message ' + type + '">' + message + '</div>');
			$('.wpfnl-product-selection-wrapper').prepend($message);
			
			// Auto-hide after 3 seconds
			setTimeout(function() {
				$message.fadeOut(function() {
					$(this).remove();
				});
			}, 3000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.wpfnl-product-selection-wrapper').length) {
			WpfnlProductSelection.init();
		}
	});

	// Re-initialize after checkout update
	$(document.body).on('updated_checkout', function() {
		if ($('.wpfnl-product-selection-wrapper').length) {
			WpfnlProductSelection.init();
		}
	});

})(jQuery);
