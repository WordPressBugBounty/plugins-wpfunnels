(function ($) {
    'use strict';
    $(function () {
        if (typeof wc_add_to_cart_variation_params !== 'undefined') {
            $('.variations_form').each(function () {
                $(this).wc_variation_form();
            });
        }

        function init_custom_gallery() {
            $('.ob-modal .woocommerce-product-gallery__image').on('click', function(e) {
                e.preventDefault();
            });

            $('.ob-modal .flex-control-thumbs img').on('click', function() {
                var $this = $(this);
                var $gallery = $this.closest('.woocommerce-product-gallery');
                var $main_image = $gallery.find('.wp-post-image');
                var src = $this.data('full_src');

                $main_image.attr('src', src);
                $main_image.attr('srcset', '');
                $main_image.attr('sizes', '');

                $gallery.find('.flex-control-thumbs img').removeClass('flex-active');
                $this.addClass('flex-active');
            });
        }

        $('.option-selector').on('click', function() {
            var modalId = '#ob-modal-' + $(this).attr('id').replace('option-selector-', '');
            $(modalId).show();
            init_custom_gallery();
        });

        $('.ob-close').on('click', function() {
            $(this).closest('.ob-modal').hide();
        });

        $(window).on('click', function(event) {
            if ($(event.target).is('.ob-modal')) {
                $(event.target).hide();
            }
        });

        $('.variations_form').on('show_variation', function (event, variation) {
            var $product = $(this).closest('.product');
            var $price = $product.find('.price');
            if (variation.price_html) {
                $price.html(variation.price_html);
            }
            $(this).find('.single_add_to_cart_button').removeClass('disabled');

            var $gallery = $product.find('.woocommerce-product-gallery');
            if (variation.image && variation.image.src) {
                $gallery.find('.wp-post-image').attr('src', variation.image.src);
                $gallery.find('.wp-post-image').attr('srcset', variation.image.srcset);
                $gallery.find('.wp-post-image').attr('sizes', variation.image.sizes);
            }
        });

        $('.variations_form').on('hide_variation', function (event) {
            var $product = $(this).closest('.product');
            var $price = $product.find('.price');
            var $regular_price = $product.data('regular-price');
            if ($regular_price) {
                $price.html($regular_price);
            }
            $(this).find('.single_add_to_cart_button').addClass('disabled');
        });

        $('.single_add_to_cart_button').on('click', function (e) {
            e.preventDefault();
            if ($(this).hasClass('disabled')) {
                return;
            }
            var $form = $(this).closest('form.cart');
            var $this = $(this);
            
            // Don't post to WooCommerce - let the order bump handler do the cart management
            // Just close modal and trigger the order bump change event
            var key = $this.closest('.ob-modal').attr('id').replace('ob-modal-', '');
            $form.closest('.ob-modal').hide();
            $('#wpfnl-order-bump-cb-' + key).prop('checked', true).trigger('change');
        });

        $('.wpfnl-order-bump-cb').on('click', function(e) {
            var $this = $(this);
            if ($this.data('is-variable') && $this.is(':checked')) {
                e.preventDefault();
                var key = $this.data('key');
                $('#option-selector-' + key).trigger('click');
            }
        });
    });
})(jQuery);