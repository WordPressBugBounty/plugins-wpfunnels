;(function ($) {
    'use strict'

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    $(document).on('click', '#wpfunnels_next_step_controller', function (e) {
        e.preventDefault()
        var ajaxurl = window.wpfnl_obj.ajaxurl

        // === Detect editor ===//
        var sPageURL = ''
        var sURLVariables = ''
        sPageURL = window.location.search.substring(1)
        sURLVariables = sPageURL.split('=')
        if (sURLVariables[0] == 'elementor-preview') {
            console.log('elementor')
        } else {
            $(this).addClass('disabled show-loader')
            $(this).find('.wpfnl-loader').css('display', 'inline-block')
            $('.et_pb_button.wpfnl_next_step_button').addClass('show-loader')

            var products = $(this).attr('data-products')
            var button_type = $(this).attr('data-button-type')
            var url = $(this).attr('data-url')
            let that = this;
            if ('url-path' === button_type || 'another-funnel' === button_type) {
                window.location.href = url
            } else {
                var step_id = window.wpfnl_obj.step_id
                jQuery.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'wpfnl_next_button_ajax',
                        step_id: step_id,
                        url: window.location.href,
                        products: products,
                    },
                    success: function (response) {
                        $(that).removeClass('disabled')
                        $(that).removeClass('show-loader')

                        $('#wpfnl-next-button-loader').hide()
                        if (response == 'error') {
                            console.log(response)
                        } else {
                            console.log(response)
                            window.location.href = response
                        }
                    },
                })
            }
        }
        // === Detect editor ===//
    })

    jQuery(document).ready(function () {
        /**
         * Carry data to next optin
         *
         * @return void
         * @since 2.7.17
         */
        function carryDataToNextStep() {
            const cookieData = getCookie('wpfunnels_send_data_checkout'),
                checkoutData = cookieData?.after_optin_submit_send_for_checkout
            if (undefined !== checkoutData) {
                if ('yes' === checkoutData?.data_to_checkout) {
                    const fieldMappings = {
                        first_name: '#billing_first_name',
                        last_name: '#billing_last_name',
                        email: '#billing_email',
                        phone: '#billing_phone',
                        message: '.wpfnl-message',
                    }

                    Object.entries(checkoutData).forEach(([key, value]) => {
                        const fieldSelector = fieldMappings[key]
                        if (fieldSelector) {
                            const field = $(fieldSelector)
                            if (field[0]) {
                                field.val(value)
                            }
                        }
                    })

                    // Get optin data fields and set the classes value.
                    const optinData = {
                        first_name: '.wpfnl-first-name',
                        last_name: '.wpfnl-last-name',
                        email: '.wpfnl-email',
                        phone: '.wpfnl-phone',
                        message: '.wpfnl-message',
                    }

                    // Set the value of the optin fields from the previous step data.
                    Object.entries(checkoutData).forEach(([key, value]) => {
                        const optinDataSelector = optinData[key]
                        if (optinDataSelector) {
                            const optinField = $(optinDataSelector)
                            if (optinField[0]) {
                                optinField.val(value)
                            }
                        }
                    })

                }
            }
        }

        carryDataToNextStep()

        /**
         * Get cookie by name
         *
         * @return mix bool|object
         * @since 2.7.17
         */
        function getCookie(name) {
            var cookieArr = document.cookie.split(';')
            // Loop through the array elements
            for (var i = 0; i < cookieArr.length; i++) {
                var cookiePair = cookieArr[i].split('=')
                if (name == cookiePair[0].trim()) {
                    return JSON.parse(decodeURIComponent(cookiePair[1]))
                }
            }
            return false
        }

        //--------start floating label script-------
        $(
            '.floating-label #customer_details .form-row .input-text, .floating-label form.woocommerce-form-login .form-row-first .input-text, .floating-label form.woocommerce-form-login .form-row-last .input-text',
        ).each(function () {
            $(this).attr('placeholder', '')

            if ($(this).val().length > 0) {
                $(this).parents('.form-row').find('label').addClass('floated')
            }
        })

        $(document).on(
            'focus',
            '.floating-label #customer_details .form-row .input-text, .floating-label form.woocommerce-form-login .form-row-first .input-text, .floating-label form.woocommerce-form-login .form-row-last .input-text',
            function () {
                $(this).parents('.form-row').find('label').addClass('floated')
            },
        )

        $(document).on(
            'blur',
            '.floating-label #customer_details .form-row .input-text, .floating-label form.woocommerce-form-login .form-row-first .input-text, .floating-label form.woocommerce-form-login .form-row-last .input-text',
            function () {
                if ($(this).val().length == 0) {
                    $(this).parents('.form-row').find('label').removeClass('floated')
                }
            },
        )

        //------floating label for select-2----------
        $(document).on(
            'click',
            '.floating-label #customer_details .form-row label[for="billing_state"], .floating-label #customer_details .form-row label[for="shipping_state"]',
            function () {
                $(this).addClass('floated')
            },
        )

        if ($('#billing_country').length && $('#billing_country').val().length > 0) {
            $('#billing_country').parents('.form-row').find('label').addClass('floated')
        }

        if ($('#billing_state').length && $('#billing_state').val().length > 0) {
            $('#billing_state').parents('.form-row').find('label').addClass('floated')
        }

        if ($('#shipping_country').length && $('#shipping_country').val().length > 0) {
            $('#shipping_country').parents('.form-row').find('label').addClass('floated')
        }
        if ($('#shipping_state').length && $('#shipping_state').val().length > 0) {
            $('#shipping_state').parents('.form-row').find('label').addClass('floated')
        }



        $('.wpfnl-checkout input, .wpfnl-checkout select').on('change', function() {
            var fieldType = $(this).attr('type');

            var newValue;

            // Handle different input types
            if (fieldType === 'checkbox') {

                // Check if data-step attribute exists
                var dataStep = $(this).data('step');
                if (typeof dataStep === 'undefined') {
                    newValue = $(this).is(':checked') ? $(this).val() : '';
                    $(this).val(newValue);
                    $(this).attr('value', newValue);
                }
            } else if (fieldType === 'radio') {
                newValue = $('input[name="' + $(this).attr('name') + '"]:checked').val();
                $(this).val(newValue);
                 $(this).attr('value', newValue);
            } else {
                newValue = $(this).val();
                $(this).val(newValue);
                $(this).attr('value', newValue);
            }

        });

        // if (
        //     $('#shipping_state').length ||
        //     $('#shipping_country').length ||
        //     $('#billing_state').length ||
        //     $('#billing_country').length
        // ) {
        //     if ('undefined' !== typeof $.fn.select2) {

        //         $('#billing_country, #billing_state, #shipping_country, #shipping_state')
        //             .select2()
        //             .on('select2:open', (elm) => {
        //                 const targetLabel = $(elm.target).parents('.form-row').find('label')
        //                 targetLabel.addClass('floated')
        //             })
        //             .on('select2:close', (elm) => {
        //                 const target = $(elm.target)
        //                 const targetLabel = target.parents('.form-row').find('label')
        //                 const targetOptions = $(elm.target.selectedOptions)
        //                 if (!targetOptions.length) {
        //                     targetLabel.removeAttr('class')
        //                 }
        //             })
        //     }
        // }
        //--------end floating label script-------

        //-------multistep checkout------
        var is_user_logged_in = window.wpfnl_obj.is_user_logged_in
        var is_login_reminder = window.wpfnl_obj.is_login_reminder

        function scroll_to_top() {
            $('html, body').animate(
                {
                    scrollTop:
                        $('.wpfnl-multistep, .wpfnl-checkout-form-wpfnl-multistep').offset().top -
                        100,
                },
                800,
            )
        }

        function show_checkout_step(targetID) {
            if ('login' == targetID) {
                //------for Elementor widget-------
                $('.wpfnl-multistep .woocommerce-form-login-toggle').show()

                $('.wpfnl-multistep .woocommerce-form-coupon').fadeOut()
                $('.wpfnl-multistep #customer_details.col2-set').fadeOut()
                $('.wpfnl-multistep #wpfnl_checkout_billing').fadeOut()
                $('.wpfnl-multistep #wpfnl_checkout_shipping').fadeOut()
                $('.wpfnl-multistep #order_review').fadeOut()
                $('.wpfnl-multistep .woocommerce-form-coupon-toggle')
                    .fadeOut()
                    .removeClass('show-form')

                //------for Gutenberg block-------
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-login-toggle').show()

                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-coupon').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #customer_details.col2-set').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #wpfnl_checkout_billing').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #wpfnl_checkout_shipping').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #order_review').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-coupon-toggle')
                    .fadeOut()
                    .removeClass('show-form')
            } else if ('billing' == targetID) {
                //------for Elementor widget-------
                $('.wpfnl-multistep #customer_details.col2-set').fadeIn()
                $('.wpfnl-multistep #wpfnl_checkout_billing').fadeIn()

                $('.wpfnl-multistep .woocommerce-form-login').fadeOut()
                $('.wpfnl-multistep .woocommerce-form-login-toggle').fadeOut()
                $('.wpfnl-multistep .woocommerce-form-coupon-toggle')
                    .fadeOut()
                    .removeClass('show-form')
                $('.wpfnl-multistep .woocommerce-form-coupon').fadeOut()
                $('.wpfnl-multistep #wpfnl_checkout_shipping').fadeOut()
                $('.wpfnl-multistep.wpfnl-2-step #wpfnl_checkout_shipping').fadeIn()
                $('.wpfnl-multistep #order_review').fadeOut()

                //------for Gutenberg block-------
                $('.wpfnl-checkout-form-wpfnl-multistep #customer_details.col2-set').fadeIn()
                $('.wpfnl-checkout-form-wpfnl-multistep #wpfnl_checkout_billing').fadeIn()

                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-login').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-login-toggle').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-coupon-toggle')
                    .fadeOut()
                    .removeClass('show-form')
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-coupon').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #wpfnl_checkout_shipping').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #order_review').fadeOut()
            } else if ('shipping' == targetID) {
                //------for Elementor widget-------
                $('.wpfnl-multistep #customer_details.col2-set').fadeIn()
                $('.wpfnl-multistep #wpfnl_checkout_shipping').fadeIn()

                $('.wpfnl-multistep .woocommerce-form-login-toggle').fadeOut()
                $('.wpfnl-multistep .woocommerce-form-login').fadeOut()
                $('.wpfnl-multistep .woocommerce-form-coupon-toggle')
                    .fadeOut()
                    .removeClass('show-form')
                $('.wpfnl-multistep .woocommerce-form-coupon').fadeOut()
                $('.wpfnl-multistep #wpfnl_checkout_billing').fadeOut()
                $('.wpfnl-multistep #order_review').fadeOut()

                //------for Gutenberg block-------
                $('.wpfnl-checkout-form-wpfnl-multistep #customer_details.col2-set').fadeIn()
                $('.wpfnl-checkout-form-wpfnl-multistep #wpfnl_checkout_shipping').fadeIn()

                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-login-toggle').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-login').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-coupon-toggle')
                    .fadeOut()
                    .removeClass('show-form')
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-coupon').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #wpfnl_checkout_billing').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #order_review').fadeOut()
            } else if ('order-review' == targetID) {
                //------for Elementor widget-------
                $('.wpfnl-multistep #order_review').fadeIn()
                $('.wpfnl-multistep .woocommerce-form-coupon-toggle').fadeIn()

                $('.wpfnl-multistep .woocommerce-form-login-toggle').fadeOut()
                $('.wpfnl-multistep .woocommerce-form-login').fadeOut()
                $('.wpfnl-multistep .woocommerce-form-coupon').fadeOut()
                $('.wpfnl-multistep #customer_details.col2-set').fadeOut()
                $('.wpfnl-multistep #wpfnl_checkout_billing').fadeOut()
                $('.wpfnl-multistep #wpfnl_checkout_shipping').fadeOut()

                //------for Gutenberg block-------
                $('.wpfnl-checkout-form-wpfnl-multistep #order_review').fadeIn()
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-coupon-toggle').fadeIn()

                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-login-toggle').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-login').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep .woocommerce-form-coupon').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #customer_details.col2-set').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #wpfnl_checkout_billing').fadeOut()
                $('.wpfnl-checkout-form-wpfnl-multistep #wpfnl_checkout_shipping').fadeOut()
            }
        }

        //-------when wizard button click------
        $('.wpfnl-multistep-wizard > li > button').on('click', function () {
            var targetID = $(this).attr('data-target')

            checkoutFieldValidation()
            var isValidate = true

            if ('billing' == targetID) {
                // Login validation goes here if needed
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                //-----for gutenberg------
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()
            } else if ('shipping' == targetID) {
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                //-------for gutenberg-----
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                checkoutFieldValidation('#wpfnl_checkout_billing')

                $('#wpfnl_checkout_billing .validate-required').each(function () {
                    if ($(this).hasClass('woocommerce-invalid-required-field')) {
                        isValidate = false
                    }
                })

                if (isValidate == false) {
                    return false
                }
            } else if ('order-review' == targetID) {
                var is_enabled_dirrerent_address = $('input[name="ship_to_different_address"]').is(
                    ':checked',
                )

                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).show()

                //---for gutenberg---
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).show()

                //------start two step checkout billing field validation-----
                if ($(this).hasClass('two-step')) {
                    checkoutFieldValidation('#wpfnl_checkout_billing')

                    $('#wpfnl_checkout_billing .validate-required').each(function () {
                        if ($(this).hasClass('woocommerce-invalid-required-field')) {
                            isValidate = false
                        }
                    })
                }
                //------end two step checkout billing field validation-----

                //----shipping validation---
                if (is_enabled_dirrerent_address == true) {
                    checkoutFieldValidation('#wpfnl_checkout_shipping')

                    $('#wpfnl_checkout_shipping .validate-required').each(function () {
                        if ($(this).hasClass('woocommerce-invalid-required-field')) {
                            isValidate = false
                        }
                    })

                    if (isValidate == false) {
                        return false
                    }
                } else {
                    checkoutFieldValidation('.woocommerce-additional-fields')

                    $('.woocommerce-additional-fields .validate-required').each(function () {
                        if ($(this).hasClass('woocommerce-invalid-required-field')) {
                            isValidate = false
                        }
                    })

                    if (isValidate == false) {
                        return false
                    }
                }
            }

            $(this).parent('li').addClass('current')
            $(this).parent('li').prevAll().addClass('completed').removeClass('current')
            $(this).parent('li').nextAll().removeClass('completed current')

            show_checkout_step(targetID)

            if ('login' == targetID) {
                $('.wpfnl-multistep-navigation button.previous')
                    .attr('data-target', '')
                    .prop('disabled', true)
                $('.wpfnl-multistep-navigation button.next')
                    .attr('data-target', 'billing')
                    .prop('disabled', false)
            } else if ('billing' == targetID) {
                if (is_user_logged_in) {
                    $('.wpfnl-multistep-navigation button.previous')
                        .attr('data-target', 'login')
                        .prop('disabled', true)
                } else {
                    if ('yes' === is_login_reminder) {
                        $('.wpfnl-multistep-navigation button.previous')
                            .attr('data-target', 'login')
                            .prop('disabled', false)
                    } else {
                        $('.wpfnl-multistep-navigation button.previous')
                            .attr('data-target', 'login')
                            .prop('disabled', true)
                    }
                }

                if ($(this).hasClass('two-step')) {
                    $('.wpfnl-multistep-navigation button.next')
                        .attr('data-target', 'order-review')
                        .prop('disabled', false)
                } else {
                    $('.wpfnl-multistep-navigation button.next')
                        .attr('data-target', 'shipping')
                        .prop('disabled', false)
                }
            } else if ('shipping' == targetID) {
                $('.wpfnl-multistep-navigation button.previous')
                    .attr('data-target', 'billing')
                    .prop('disabled', false)
                $('.wpfnl-multistep-navigation button.next')
                    .attr('data-target', 'order-review')
                    .prop('disabled', false)
            } else if ('order-review' == targetID) {
                if ($(this).hasClass('two-step')) {
                    $('.wpfnl-multistep-navigation button.previous')
                        .attr('data-target', 'billing')
                        .prop('disabled', false)
                } else {
                    $('.wpfnl-multistep-navigation button.previous')
                        .attr('data-target', 'shipping')
                        .prop('disabled', false)
                }

                $('.wpfnl-multistep-navigation button.next')
                    .attr('data-target', '')
                    .prop('disabled', true)
            }
        })

        $('#get-qrcode').on('click', function() {
            var inputVal = $('#pix_emv').val();

            // Create a temporary input field
            var tempInput = $("<input>");
            $("body").append(tempInput);

            // Set its value to the input value
            tempInput.val(inputVal).select();

            // Copy the value to the clipboard
            document.execCommand("copy");

            // Remove the temporary input field
            tempInput.remove();

			// Create a message element
			// Create a message element
            var message = $('<div class="copied-message">Copied</div>');

            // Append the message element to the body
            $("body").append(message);

            // Position the message element
            var buttonOffset = $(this).offset();
            var messageTop = buttonOffset.top - message.outerHeight() - 10;
            var messageLeft = buttonOffset.left + ($(this).outerWidth() - message.outerWidth()) / 2;
            message.css({top: messageTop, left: messageLeft});

            // Show the message
            message.fadeIn();

            // Hide the message after 2 seconds
            setTimeout(function() {
                message.fadeOut(function() {
                    // Remove the message element after fading out
                    $(this).remove();
                });
            }, 2000);
        });

        //-------when next step button click------
        $('.wpfnl-multistep-navigation button.next').on('click', function () {
            var targetID = $(this).attr('data-target')

            var isValidate = true

            if ('billing' == targetID) {
                // Login validation goes here if needed
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                //-----for gutenberg------
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()
            } else if ('shipping' == targetID) {
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                //-----for gutenberg------
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                checkoutFieldValidation('#wpfnl_checkout_billing')

                $('#wpfnl_checkout_billing .validate-required').each(function () {
                    if ($(this).hasClass('woocommerce-invalid-required-field')) {
                        isValidate = false
                    }
                })

                if (isValidate == false) {
                    return false
                }
            } else if ('order-review' == targetID) {
                var is_enabled_dirrerent_address = $('input[name="ship_to_different_address"]').is(
                    ':checked',
                )

                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).show()

                //-----gutenberg-----
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).show()

                //------start two step checkout billing field validation-----
                if ($(this).hasClass('two-step')) {
                    checkoutFieldValidation('#wpfnl_checkout_billing')

                    $('#wpfnl_checkout_billing .validate-required').each(function () {
                        if ($(this).hasClass('woocommerce-invalid-required-field')) {
                            isValidate = false
                        }
                    })
                }
                //------end two step checkout billing field validation-----

                if (is_enabled_dirrerent_address == true) {
                    checkoutFieldValidation('#wpfnl_checkout_shipping')

                    $('#wpfnl_checkout_shipping .validate-required').each(function () {
                        if ($(this).hasClass('woocommerce-invalid-required-field')) {
                            isValidate = false
                        }
                    })

                    if (isValidate == false) {
                        return false
                    }
                } else {
                    checkoutFieldValidation('.woocommerce-additional-fields')

                    $('.woocommerce-additional-fields .validate-required').each(function () {
                        if ($(this).hasClass('woocommerce-invalid-required-field')) {
                            isValidate = false
                        }
                    })

                    if (isValidate == false) {
                        return false
                    }
                }
            }

            scroll_to_top()

            $('.wpfnl-multistep-wizard > li.' + targetID).addClass('current')
            $('.wpfnl-multistep-wizard > li.' + targetID)
                .prevAll()
                .addClass('completed')
                .removeClass('current')
            $('.wpfnl-multistep-wizard > li.' + targetID)
                .nextAll()
                .removeClass('completed current')

            show_checkout_step(targetID)

            if ('billing' == targetID) {
                $(this).siblings().attr('data-target', 'login').prop('disabled', false)

                if ($(this).hasClass('two-step')) {
                    $(this).attr('data-target', 'order-review')
                } else {
                    $(this).attr('data-target', 'shipping')
                }
            } else if ('shipping' == targetID) {
                $(this).siblings().attr('data-target', 'billing').prop('disabled', false)
                $(this).attr('data-target', 'order-review')
            } else if ('order-review' == targetID) {
                if ($(this).hasClass('two-step')) {
                    $(this).siblings().attr('data-target', 'billing').prop('disabled', false)
                } else {
                    $(this).siblings().attr('data-target', 'shipping')
                }

                $(this).prop('disabled', true)
            }
        })

        let maybeNeedAccount = false
        $('#createaccount').on('change', function () {
            if ($(this).is(':checked')) {
                maybeNeedAccount = true
            } else {
                maybeNeedAccount = false
            }
        })

        function ensureCheckoutValidationStyles() {
            if ($('#wpfnl-checkout-validation-style').length) {
                return
            }

            $('<style id="wpfnl-checkout-validation-style">' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid > label{color:#a00 !important;}' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid input.input-text,' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid input[type="email"],' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid input[type="tel"],' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid input[type="password"],' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid input[type="number"],' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid input[type="text"],' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid select,' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid textarea{border-color:#a00 !important;}' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid .select2-container--default .select2-selection--single,' +
                '.wpfnl-checkout .form-row.wpfnl-js-invalid .select2-container--default .select2-selection--multiple{border-color:#a00 !important;}' +
                '</style>').appendTo('head')
        }

        function updateCheckoutFieldState($field, isInvalid) {
            var $row = $field.closest('.form-row')

            if (!$row.length) {
                return
            }

            if (isInvalid) {
                $row
                    .removeClass('woocommerce-validated')
                    .addClass(
                        'required-field-appended woocommerce-invalid woocommerce-invalid-required-field wpfnl-js-invalid',
                    )
            } else {
                $row.removeClass(
                    'required-field-appended woocommerce-invalid woocommerce-invalid-required-field wpfnl-js-invalid',
                )
            }
        }

        function checkoutFieldValidation(step) {
            ensureCheckoutValidationStyles()

            let msgEnabled = window.wpfnl_obj && window.wpfnl_obj.field_validation_enabled === 'yes';
            let msgTemplate = (window.wpfnl_obj && window.wpfnl_obj.field_validation_message) ? window.wpfnl_obj.field_validation_message : '{field} is required';

            function appendFieldMessage($element, message) {
                $element.parent().find('.field-required').remove()
                if (msgEnabled) {
                    $element.parent().append('<span class="field-required">' + message + '</span>')
                }
            }

            function getErrorMessage($element) {
                if (!msgEnabled) return 'Field required';
                let $label = $element.closest('.form-row').find('label').clone();
                $label.find('*').remove();
                let fieldName = $.trim($label.text()).replace(/\*+$/, '').trim();
                return msgTemplate.replace('{field}', fieldName);
            }

            $(step + ' .validate-required input').each(function () {
                var fieldValue = $(this).val()
                if (!fieldValue) {
                    var errorMsg = getErrorMessage($(this));
                    if (!maybeNeedAccount) {
                        if (
                            'account_username' === $(this).attr('name') ||
                            'account_password' === $(this).attr('name')
                        ) {
                            appendFieldMessage($(this), '')
                            updateCheckoutFieldState($(this), false)
                            return true
                        } else {
                            appendFieldMessage($(this), errorMsg)
                            updateCheckoutFieldState($(this), true)
                        }
                    } else {
                        appendFieldMessage($(this), errorMsg)
                        updateCheckoutFieldState($(this), true)
                    }
                } else if (fieldValue) {
                    appendFieldMessage($(this), '')
                    updateCheckoutFieldState($(this), false)
                }
            })

            $(step + ' .validate-required select').each(function () {
                var fieldValue = $(this).children('option:selected').val()
                if (!fieldValue) {
                    var errorMsg = getErrorMessage($(this));
                    appendFieldMessage($(this), errorMsg)
                    updateCheckoutFieldState($(this), true)
                } else if (fieldValue) {
                    appendFieldMessage($(this), '')
                    updateCheckoutFieldState($(this), false)
                }
            })

            $(step + ' .validate-required input[type="checkbox"]').each(function () {
                var fieldValue = $(this).is(':checked')
                if (!fieldValue) {
                    var errorMsg = getErrorMessage($(this));
                    appendFieldMessage($(this), errorMsg)
                    updateCheckoutFieldState($(this), true)
                } else if (fieldValue) {
                    appendFieldMessage($(this), '')
                    updateCheckoutFieldState($(this), false)
                }
            })
        }

        $('form.checkout').on('checkout_place_order', function () {
            var isValidate = true;
            checkoutFieldValidation('form.checkout');

            $('form.checkout .validate-required').each(function () {
                if ($(this).hasClass('woocommerce-invalid-required-field') && $(this).is(':visible')) {
                    isValidate = false;
                    $(this).removeClass('woocommerce-validated').addClass('woocommerce-invalid woocommerce-invalid-required-field');
                }
            });

            if (!isValidate) {
                var $firstErrorField = $('.field-required:visible').first();
                var $firstInvalidRow = $('form.checkout .validate-required.woocommerce-invalid-required-field:visible').first();
                var scrollTarget = $firstErrorField.length ? $firstErrorField.offset().top : $firstInvalidRow.offset().top;

                if (scrollTarget) {
                    $('html, body').animate({
                        scrollTop: scrollTarget - 100
                    }, 800);
                }

                return false;
            }
        });

        $(document).on('input change focusout', 'form.checkout .validate-required input, form.checkout .validate-required select, form.checkout .validate-required textarea', function(e) {
            ensureCheckoutValidationStyles();
            var msgEnabled = window.wpfnl_obj && window.wpfnl_obj.field_validation_enabled === 'yes';
            var isCheckable = $(this).is('[type="checkbox"], [type="radio"]');
            var val = isCheckable ? $(this).is(':checked') : $.trim($(this).val());

            if(val) {
                $(this).parent().find('.field-required').remove();
                updateCheckoutFieldState($(this), false);
            } else if (e.type === 'focusout' || (isCheckable && e.type === 'change')) {
                // Ignore account_password and account_username if maybeNeedAccount is false
                if (!maybeNeedAccount && ('account_username' === $(this).attr('name') || 'account_password' === $(this).attr('name'))) {
                    return;
                }

                let msgTemplate = (window.wpfnl_obj && window.wpfnl_obj.field_validation_message) || '{field} is required';
                let $label = $(this).parents('.form-row').find('label').clone();
                $label.find('*').remove();
                let fieldName = $.trim($label.text()).replace(/\*+$/, '').trim();
                let errorMsg = msgTemplate.replace('{field}', fieldName);

                $(this).parent().find('.field-required').remove();
                if (msgEnabled) {
                    $(this).parent().append('<span class="field-required">' + errorMsg + '</span>');
                }

                var $row = $(this).parents('.form-row');
                setTimeout(function() {
                    updateCheckoutFieldState($row.find('input, select, textarea').first(), true);
                }, 10);
            }
        });

        //-------when previous button click------
        $('.wpfnl-multistep-navigation button.previous').on('click', function () {
            var targetID = $(this).attr('data-target')

            scroll_to_top()

            $('.wpfnl-multistep-wizard > li.' + targetID).addClass('current')
            $('.wpfnl-multistep-wizard > li.' + targetID)
                .prevAll()
                .addClass('completed')
                .removeClass('current')
            $('.wpfnl-multistep-wizard > li.' + targetID)
                .nextAll()
                .removeClass('completed current')

            show_checkout_step(targetID)

            if ('login' == targetID) {
                $(this).attr('data-target', '').prop('disabled', true)
                $(this).siblings().attr('data-target', 'billing').prop('disabled', false)
            } else if ('billing' == targetID) {
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                //------for gutenberg------
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                if (is_user_logged_in) {
                    $(this).attr('data-target', 'login').prop('disabled', true)
                } else {
                    if ('yes' === is_login_reminder) {
                        $(this).attr('data-target', 'login').prop('disabled', false)
                    } else {
                        $(this).attr('data-target', 'login').prop('disabled', true)
                    }
                }
                if ($(this).hasClass('two-step')) {
                    $(this).siblings().attr('data-target', 'order-review').prop('disabled', false)
                } else {
                    $(this).siblings().attr('data-target', 'shipping').prop('disabled', false)
                }
            } else if ('shipping' == targetID) {
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout.wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                //-------for gutenberg--------
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content',
                ).hide()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-content[data-step="first"]',
                ).show()
                $(
                    '.theme-woostify.checkout-layout-2 .wpfnl-checkout-form-wpfnl-multistep .multi-step-checkout-wrapper .multi-step-checkout-button-wrapper',
                ).hide()

                $(this).attr('data-target', 'billing')
                $(this).siblings().attr('data-target', 'order-review').prop('disabled', false)
            }
        })
        //-------end multistep checkout------

        $(".wpfnl-learndash-pay form input[type='submit']").on('click', function () {
            var ajaxurl = window.wpfnl_obj.ajaxurl
            let step_id = $('.wpfnl-learndash-pay').data('id')
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'wpfnl_set_learndash_session',
                    step_id: step_id,
                },
                success: function (response) {
                    console.log(response)
                },
            })
        })

        /**
         *
         * @param response
         */
        var wpf_remove_spinner = function (response) {
            if ($('.wc_payment_methods').length) {
                if (response.hasOwnProperty('wc_custom_fragments')) {
                    // update the fragments
                    if (response.hasOwnProperty('fragments')) {
                        $.each(response.fragments, function (key, value) {
                            $(key).replaceWith(value)
                        })
                    }

                    if (parseFloat(response.cart_total) <= 0) {
                        $('body').trigger('update_checkout')
                    }
                }
            } else {
                $('body').trigger('update_checkout')
            }
        }
        $(document).on('change', '.wpfnl-update-variation', function (e) {
            e.preventDefault()
            var ajaxurl = window.wpfnl_obj.ajaxurl

            let variations = [],
                i = 0,
                thisProductID = $(this).data('product-id')
            $('.wpfnl-update-variation').each(function () {
                if (thisProductID == $(this).data('product-id')) {
                    variations[i] = {
                        attr: $(this).data('attr'),
                        product_id: $(this).data('product-id'),
                        variation_id: $(this).data('variation-id'),
                        quantity: $(this).data('quantity'),
                        value: $(this).val().trim(),
                    }
                    i++
                }
            })
            $("input[name='_wpfunnels_variable_product']").val('selected')
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'wpfnl_update_variation',
                    variations: variations,
                },
                success: function (response) {
                    $('body').trigger('update_checkout')
                },
            })
        })

        // $(document).on("click", ".learndash_checkout_button", function (e) {
        // 	var dropDownId = $(this).data('jq-dropdown');
        // 	$(mainCourseId).attr("id", dropDownId.replace('#',''));
        // 	$(dropDownId).css('display','block');

        // 	// console.log(mainCourseId);
        // 	// $(dropDownId).show();

        // 	var ajaxurl = window.wpfnl_obj.ajaxurl;
        // 	jQuery.ajax({
        // 		type: "POST",
        // 		url: ajaxurl,
        // 		data: {
        // 			action			: "wpfnl_get_course_details",
        // 			course_id		: dropDownId.replace('#jq-dropdown-',''),
        // 		},
        // 		success: function (response) {
        // 			$('input[name=item_number]').val(response.course.id);
        // 			$('input[name=amount]').val(response.course.price);
        // 			$('input[name=item_name]').val(response.course.title);
        // 			$('input[name=custom]').val(window.wpfnl_obj.step_id);
        // 			$('input[name=stripe_course_id]').val(response.course.id);
        // 			$('input[name=stripe_plan_id]').val('learndash-course-'+response.course.id);
        // 			$('input[name=stripe_name]').val(response.course.title);
        // 			$('input[name=stripe_price]').val(response.course.price*100);
        // 		}
        // 	});

        // });

        $(document).on('click', '.wpfnl-place-order-add-overlay', function (e) {
            $('.wpfnl-order-bump__popup-wrapper').addClass('show').css('top', '30px')
        })

        $(document).on('change', '.wpfnl-order-bump-cb', function (e) {
            e.preventDefault()
            $(this).parents('.wpfnl-reset').find('.oderbump-loader').css('display', 'flex')
            var ajaxurl = window.wpfnl_obj.ajaxurl
            var user_id = window.wpfnl_obj.user_id
            var step_id = $(this).attr('data-step')
            var quantity = $(this).attr('data-quantity')
            var replace = $(this).attr('data-replace')
            var key = $(this).attr('data-key')
            var isLms = $(this).attr('data-lms')
            var product = $(this).val()
            let checker = false,
                main_products = $(this).attr('data-main-products')

            // Validate quantity - ensure it's a positive integer
            quantity = parseInt(quantity, 10);
            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
            }

            var specificLabel = $('#wpfnl-order-bump-add-btn-' + key);
            if ($(this).prop('checked') == true) {
                checker = true
                specificLabel.text('Remove');
            } else if ($(this).prop('checked') == false) {
                checker = false
                specificLabel.text('Add');
            }

            if (checker) {
                $("input[name='_wpfunnels_order_bump_product_" + key + "']").val(product)
            } else {
                $("input[name='_wpfunnels_order_bump_product_" + key + "']").val('')
            }

            $("input[name='_wpfunnels_order_bump_clicked']").val('yes')
            $('.wpfnl-order-bump-cb').each(function() {
                $(this).prop('disabled', true);
            });

            // Note: We don't uncheck other order bumps when replace is enabled
            // because the replace logic only replaces main products, not other order bumps

            // --- Capture variation data if exists ---
            let variation_id = '';
            let variation_data = {};

            // Find variation form within the modal for this product
            var $variationForm = $('.variations_form[data-product_id="' + product + '"]');
            if ($variationForm.length) {
                variation_id = $variationForm.find('input[name="variation_id"]').val();

                // Loop through all attribute selects and store their values
                $variationForm.find('select[name^="attribute_"]').each(function () {
                    var name = $(this).attr('name');
                    var value = $(this).val();
                    if (value) {
                        variation_data[name] = value;
                    }
                });
            }

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'wpfnl_order_bump_ajax',
                    step_id: step_id,
                    quantity: quantity,
                    product: product,
                    checker: checker,
                    is_lms: isLms,
                    user_id: user_id,
                    key: key,
                    main_products: main_products,
                    variation_id: variation_id,
                    variation_data: variation_data,
                },
                success: function (response) {
                    $('.wpfnl-lms-access-course-message').text('')
                    wpf_remove_spinner(response)
                    if($('.gwpf-acrrodion-total').length){
                        $('.gwpf-acrrodion-total').html(response.cart_total_currency);
                    }
                    $('.oderbump-loader').css('display', 'none')
                    if (isLms === 'wc') {
                        jQuery('body').trigger('update_checkout')
                    } else {
                        $('.wpfnl-order-bump-cb').each(function (index) {
                            if ($(this).val() != product) {
                                $(this).prop('checked', false)
                            }
                        })
                        $('.wpfnl-lms-checkout').empty().append(response.html)
                    }

                    // Only update if the data exists in response
                    if (response.wpfunnels_data && response.wpfunnels_data.product_name) {
                        var titleLabel = $('#wpfnl-order-bump-title-' + key);
                        titleLabel.text(response.wpfunnels_data.product_name);
                    }

                    if (response.wpfunnels_data && response.wpfunnels_data.product_description) {
                        var descriptionLabel = $('#wpfnl-order-bump-description-' + key);
                        descriptionLabel.html(response.wpfunnels_data.product_description);
                    }

                    if (response.wpfunnels_data && response.wpfunnels_data.product_price) {
                        var priceLabel = $('#wpfnl-order-bump-price-' + key);
                        var labelText = priceLabel.find('strong').text();
                        priceLabel.html('<strong>' + labelText + '</strong> ' + response.wpfunnels_data.product_price);
                    }

                    if (response.wpfunnels_data && response.wpfunnels_data.product_image) {
                        var imageWrapper = $('#wpfnl-order-bump-image-' + key);
                        imageWrapper.css('background-image', 'url(' + response.wpfunnels_data.product_image + ')');
                        imageWrapper.find('img.for-mobile').attr('src', response.wpfunnels_data.product_image);
                    }

                    $('.wpfnl-order-bump-cb').each(function (index) {
                        if ( 'yes' === isLms ) {
                            if ($(this).val() != product) {
                                $(this).prop('checked', false)
                            }
                        }
                    })

                    // If isAllReplace is true, uncheck all other order bumps
                    if (response.wpfunnels_data && response.wpfunnels_data.isAllReplace === true && checker) {
                        $('.wpfnl-order-bump-cb').each(function (index, checkbox) {
                            if ($(checkbox).attr('data-key') !== key) {
                                $(checkbox).prop('checked', false);
                            }
                        });
                    }

                    $('.wpfnl-order-bump-cb').each(function() {
                        $(this).prop('disabled', false);
                    });

                    var $popupWrapper = $('.wpfnl-order-bump__popup-wrapper');
                    var inner_height = $popupWrapper.innerHeight() + 30;

                    $popupWrapper
                    .removeClass('show')
                    .css('top', '-' + inner_height + 'px')

                    $('.wpfnl-place-order').removeClass('wpfnl-place-order-add-overlay')
                },
            })
        })

        //----show order bump modal-----
        function wpfnlInitOrderBumpPopup() {
            setTimeout(function () {
                var $popupWrapper = $('.wpfnl-order-bump__popup-wrapper');
                if ($popupWrapper.length) {
                    var inner_height = $popupWrapper.innerHeight() + 30;
                    $popupWrapper.css('top', '-' + inner_height + 'px');

                    // Show popup automatically for position='popup' order bumps (not for pre-purchase)
                    if (!$popupWrapper.hasClass('wpfnl-pre-purchase')) {
                        $popupWrapper.addClass('show').css('top', '30px');
                    }
                }
            }, 500);
        }

        // Use robust load detection that works in Firefox even if
        // window.load has already fired before this script runs.
        if (document.readyState === 'complete') {
            wpfnlInitOrderBumpPopup();
        } else {
            $(window).on('load', wpfnlInitOrderBumpPopup);
        }

        $(document).on('click', '.close-order-bump', function () {
            var $popupWrapper = $('.wpfnl-order-bump__popup-wrapper');
            var inner_height = $popupWrapper.innerHeight() + 30;

            $popupWrapper
                .removeClass('show')
                .css('top', '-' + inner_height + 'px')

            $('.wpfnl-place-order')
                .removeClass('wpfnl-place-order-add-overlay')

        })



        //--------woocommerce checkout page coupon toggle add class-----------
        $('.wpfnl-checkout .woocommerce-form-coupon-toggle .showcoupon').on('click', function () {
            $(this).parents('.woocommerce-form-coupon-toggle').toggleClass('show-form')
        })

        // Collapsible coupon field toggle
        $(document).on('click', '.wpfnl-coupon-toggle-link', function (e) {
            e.preventDefault()
            var $wrapper = $(this).closest('.wpfnl-coupon-toggle-wrapper')
            $wrapper.find('.wpfnl-coupon-collapsible').slideToggle(300)
            $(this).closest('.wpfnl-coupon-toggle-notice').toggleClass('wpfnl-coupon-expanded')
        })

        function initCollapsibleCheckoutFields() {
            var $rows = $('.wpfnl-checkout .form-row.wpfnl-field-collapsible')
            if (!$rows.length) {
                return
            }

            $rows.each(function () {
                var $row = $(this)
                if ($row.data('wpfnlCollapsibleReady')) {
                    return
                }

                var $inputWrapper = $row.children('.woocommerce-input-wrapper').first()
                if (!$inputWrapper.length) {
                    $inputWrapper = $row.find('.woocommerce-input-wrapper').first()
                }
                if (!$inputWrapper.length) {
                    return
                }

                var $label = $row.children('label').first()
                var labelText = ''
                if ($label.length) {
                    var $labelClone = $label.clone()
                    $labelClone.find('*').remove()
                    labelText = $.trim($labelClone.text()).replace(/\*/g, '')
                    $label.addClass('wpfnl-field-original-label')
                }

                if (!labelText) {
                    labelText = 'Field'
                }

                $row.attr('data-collapse-label', labelText)

                var $toggle = $(
                    '<button type="button" class="wpfnl-field-collapse-toggle" aria-expanded="false">' +
                        '<span class="wpfnl-field-collapse-icon" aria-hidden="true"></span>' +
                        '<span class="wpfnl-field-collapse-text"></span>' +
                    '</button>'
                )

                $toggle.find('.wpfnl-field-collapse-text').text('Add ' + labelText)
                $row.prepend($toggle)

                $row.addClass('wpfnl-field-collapsible-ready wpfnl-field-collapsed')
                $row.data('wpfnlCollapsibleReady', true)
                $inputWrapper.hide()
            })
        }

        $(document).on('click', '.wpfnl-checkout .wpfnl-field-collapse-toggle', function (e) {
            e.preventDefault()
            var $button = $(this)
            var $row = $button.closest('.form-row.wpfnl-field-collapsible')
            var $inputWrapper = $row.children('.woocommerce-input-wrapper').first()
            if (!$inputWrapper.length) {
                $inputWrapper = $row.find('.woocommerce-input-wrapper').first()
            }
            if (!$inputWrapper.length) {
                return
            }

            if (!$row.hasClass('wpfnl-field-collapsed')) {
                return
            }

            $row.removeClass('wpfnl-field-collapsed').addClass('wpfnl-field-expanded')
            $button.attr('aria-expanded', 'true').hide()
            $inputWrapper.stop(true, true).slideDown(200)
        })

        $(document.body).on('checkout_error', function () {

            $('.wpfnl-checkout .form-row.wpfnl-field-collapsible.woocommerce-invalid').each(function () {
                var $row = $(this)
                if ($row.hasClass('wpfnl-field-expanded')) {
                    return
                }

                var $inputWrapper = $row.children('.woocommerce-input-wrapper').first()
                if (!$inputWrapper.length) {
                    $inputWrapper = $row.find('.woocommerce-input-wrapper').first()
                }
                if (!$inputWrapper.length) {
                    return
                }

                $row.removeClass('wpfnl-field-collapsed').addClass('wpfnl-field-expanded')
                $row.find('.wpfnl-field-collapse-toggle').attr('aria-expanded', 'true').hide()
                $inputWrapper.stop(true, true).slideDown(200)
            })
        })

        /**
         * Modern checkout custom coupon flow.
         */
        function initModernCheckoutCouponFlow() {
            var $checkoutWrapper = $('.wpfnl-checkout.wpfnl-modern-checkout, .wpfnl-checkout.wpfnl-modern-one-column')
            if (!$checkoutWrapper.length) {
                return
            }

            function renderCouponMessages(messages) {
                $checkoutWrapper.find('.wpfnl-custom-coupon-messages').remove()
                if (!messages) {
                    return
                }

                var $container = $('<div class="wpfnl-custom-coupon-messages"></div>').html(messages)
                var $target = $checkoutWrapper.find('.wpfnl-modern-checkout-right .wpfnl-modern-order-summary').first()
                if (!$target.length) {
                    $target = $checkoutWrapper.find('.wpfnl-modern-section--order-summary .wpfnl-modern-section__content').first()
                }
                if ($target.length) {
                    $target.prepend($container)
                }
            }

            function applyCoupon(couponCode, $button) {
                if (!couponCode) {
                    renderCouponMessages('<ul class="woocommerce-error" role="alert"><li>Please enter a coupon code.</li></ul>')
                    return
                }

                $button.prop('disabled', true).addClass('wpfnl-loading')

                $.ajax({
                    type: 'POST',
                    url: window.wpfnl_obj.ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'wpfnl_apply_checkout_coupon',
                        coupon_code: couponCode,
                        security: window.wpfnl_obj.ajax_nonce,
                    },
                    success: function (response) {
                        renderCouponMessages(response && response.data ? response.data.messages : '')
                        $('body').trigger('update_checkout')
                    },
                    error: function () {
                        renderCouponMessages('<ul class="woocommerce-error" role="alert"><li>Unable to apply coupon right now.</li></ul>')
                    },
                    complete: function () {
                        $button.prop('disabled', false).removeClass('wpfnl-loading')
                    },
                })
            }

            $(document)
                .off('click.wpfnlCouponFlow', '.wpfnl-submit-coupon')
                .on('click.wpfnlCouponFlow', '.wpfnl-submit-coupon', function (e) {
                    e.preventDefault()
                    var $button = $(this)
                    var couponCode = $button
                        .closest('.wpfnl-custom-coupon-field')
                        .find('.wpfnl-coupon-code-input')
                        .val()

                    applyCoupon($.trim(couponCode), $button)
                })

            $(document)
                .off('keypress.wpfnlCouponFlow', '.wpfnl-coupon-code-input')
                .on('keypress.wpfnlCouponFlow', '.wpfnl-coupon-code-input', function (e) {
                    if (13 !== e.which) {
                        return
                    }

                    e.preventDefault()
                    var $input = $(this)
                    var $button = $input.closest('.wpfnl-custom-coupon-field').find('.wpfnl-submit-coupon')
                    applyCoupon($.trim($input.val()), $button)
                })

            $(document)
                .off('click.wpfnlCouponRemove', '.wpfnl-modern-checkout-right .woocommerce-remove-coupon, .wpfnl-modern-one-column-wrapper .woocommerce-remove-coupon')
                .on('click.wpfnlCouponRemove', '.wpfnl-modern-checkout-right .woocommerce-remove-coupon, .wpfnl-modern-one-column-wrapper .woocommerce-remove-coupon', function (e) {
                    e.preventDefault()
                    var couponCode = $(this).attr('data-coupon')

                    $.ajax({
                        type: 'POST',
                        url: window.wpfnl_obj.ajaxurl,
                        dataType: 'json',
                        data: {
                            action: 'wpfnl_remove_checkout_coupon',
                            coupon_code: couponCode,
                            security: window.wpfnl_obj.ajax_nonce,
                        },
                        success: function (response) {
                            renderCouponMessages(response && response.data ? response.data.messages : '')
                            $('body').trigger('update_checkout')
                        },
                    })
                })
        }

        initModernCheckoutCouponFlow()
        initCollapsibleCheckoutFields()

        $(document.body).on('updated_checkout', function () {
            initModernCheckoutCouponFlow()
            initCollapsibleCheckoutFields()
        })

        /**
         * Elementor optin form submission ajax
         */
        $('.wpfnl-elementor-optin-form-wrapper form').on('submit', function (e) {
            e.preventDefault()
            var thisParents = $(this).parents('.wpfnl-elementor-optin-form-wrapper')

            var thisEmail = thisParents.find('.wpfnl-email')
            var thisFirstName = thisParents.find('.wpfnl-first-name')
            var thisLastName = thisParents.find('.wpfnl-last-name')
            var thisAcceptance = thisParents.find('.wpfnl-acceptance_checkbox')
            var thisPhone = thisParents.find('.wpfnl-phone')

            $('.wpfnl-elementor-optin-form-wrapper .response').css('display', 'none')
            if (
                (thisEmail.val() == '' && thisEmail.prop('required')) ||
                (thisLastName.val() == '' && thisLastName.prop('required')) ||
                (thisFirstName.val() == '' && thisFirstName.prop('required')) ||
                (thisAcceptance.val() == '' && thisAcceptance.prop('required')) ||
                (thisPhone.val() == '' && thisPhone.prop('required'))
            ) {
                thisParents.find('.response').css('color', 'red')
                thisParents.find('.response').text('Please fill all the required fields')
                thisParents.find('.response').css('display', 'flex')
                return false
            }

            var ajaxurl = wpfnl_obj.ajaxurl,
                security = wpfnl_obj.optin_form_nonce,
                step_id = wpfnl_obj.step_id,
                email = '',
                data = {
                    action: 'wpfnl_optin_submission',
                    security: security,
                    step_id: step_id,
                    url: window.location.href,
                    postData: $(this).serialize(),
                },
                postData = data.postData.split('&'),
                form = $(this)
            form.find('.wpfnl-loader').show()
            form.find('button[type="submit"]').prop('disabled', true)
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: data,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        form.hide()
                        form.find('button[type="submit"]').prop('disabled', false)
                        thisParents.find('.response').fadeIn('fast')
                        thisParents.find('.response').css('color', 'green')
                        thisParents.find('.response').text(response.notification_text)
                        if (response.redirect) {
                            setTimeout(function () {
                                window.location.href = response.redirect_url
                            }, 1000)
                        }
                    } else {
                        thisParents.find('.response').fadeIn('fast')
                        form.find('button[type="submit"]').prop('disabled', false)
                        form.find('.wpfnl-loader').css('display', 'none')
                        thisParents.find('.response').css('color', 'red')
                        thisParents.find('.response').text(response.notification_text)
                    }
                },
            })
        })

        /**
         * Shortcode optin form submission ajax
         */
        $('.wpfnl-shortcode-optin-form-wrapper form').on('submit', function (e) {
            e.preventDefault()
            var thisParents = $(this).parents('.wpfnl-shortcode-optin-form-wrapper')
            optinSubmit(thisParents)
        })

        /**
         * Divi optin form submission ajax
         */
        $('.wpfnl-shortcode-optin-form-wrapper form #wpfunnels_optin-button').on(
            'click',
            function (e) {
                e.preventDefault()
                var thisParents = $(this).parents('.wpfnl-shortcode-optin-form-wrapper')
                optinSubmit(thisParents)
            },
        )

        /**
         * Divi optin form submission ajax
         */
        $('.wpfnl-bricks-optin-form-wrapper form button').on(
            'click',
            function (e) {
                e.preventDefault()
                var thisParents = $(this).parents('.wpfnl-bricks-optin-form-wrapper')
                optinSubmit(thisParents)
            },
        )

        /**
         * Optin form submission for Shortcode and Divi
         */
        function optinSubmit(thisParents) {
            var thisEmail = thisParents.find('.wpfnl-email')
            var thisFirstName = thisParents.find('.wpfnl-first-name')
            var thisLastName = thisParents.find('.wpfnl-last-name')
            var thisAcceptance = thisParents.find('.wpfnl-acceptance_checkbox')
            var thisPhone = thisParents.find('.wpfnl-phone')

            thisParents.find('response').css('display', 'none')
            if (
                (thisEmail.val() == '' && thisEmail.prop('required')) ||
                (thisLastName.val() == '' && thisLastName.prop('required')) ||
                (thisFirstName.val() == '' && thisFirstName.prop('required')) ||
                (thisAcceptance.val() == '' && thisAcceptance.prop('required')) ||
                (thisPhone.val() == '' && thisPhone.prop('required'))
            ) {
                thisParents.find('.response').css('color', 'red')
                if (thisEmail.val() == '') {
                    thisParents.find('.response').text('Email field is required')
                } else {
                    thisParents.find('.response').text('Please fill all the required fields')
                }
                thisParents.find('.response').css('display', 'flex')
                return false
            }

            var ajaxurl = wpfnl_obj.ajaxurl,
                security = wpfnl_obj.optin_form_nonce,
                step_id = wpfnl_obj.step_id,
                funnel_id = wpfnl_obj.funnel_id,
                email = '',
                data = {
                    action: 'wpfnl_shortcode_optin_submission',
                    security: security,
                    step_id: step_id,
                    funnel_id: funnel_id,
                    url: window.location.href,
                    postData: thisParents.find('form').serialize(),
                },
                postData = data.postData.split('&'),
                form = thisParents.find('form')

            form.find('.et_pb_button.btn-optin').addClass('disabled')
            form.find('.btn-optin').prop('disabled', true)
            form.find('.wpfnl-loader').css('display', 'inline-block')
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: data,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        form.hide()
                        form.find('.et_pb_button.btn-optin').removeClass('disabled')
                        form.find('.btn-optin').prop('disabled', false)
                        thisParents.find('.response').fadeIn('fast')
                        thisParents.find('.response').css('color', 'green')
                        thisParents.find('.response').text(response.notification_text)
                        if (response.redirect) {
                            setTimeout(function () {
                                window.location.href = response.redirect_url
                            }, 1000)
                        }
                    } else {
                        form.find('.et_pb_button.btn-optin').removeClass('disabled')
                        form.find('.btn-optin').prop('disabled', false)
                        thisParents.find('.response').fadeIn('fast')
                        form.find('.wpfnl-loader').css('display', 'none')
                        thisParents.find('.response').css('color', 'red')
                        thisParents.find('.response').text(response.notification_text)
                    }
                },
            })
        }

        /**
         * Gutenberg optin form submission ajax
         */

        $(document).ready(function () {
            var get_optin = $('#wpf-optin-g-guternburg').val()
            if (get_optin != undefined) {
                if (get_optin != '' || get_optin != NUll) {
                    grecaptcha.ready(function () {
                        grecaptcha
                            .execute(get_optin, { action: 'homepage' })
                            .then(function (token) {
                                document.getElementById('wpf-optin-g-guternburg').value = token
                            })
                    })
                }
            }
        })

        $('.wpfnl-gutenberg-optin-form-wrapper form').on('submit', function (e) {
            e.preventDefault()
            var thisParents = $(this).parents('.wpfnl-gutenberg-optin-form-wrapper')
            var thisEmail = thisParents.find('.wpfnl-email')
            var thisFirstName = thisParents.find('.wpfnl-first-name')
            var thisLastName = thisParents.find('.wpfnl-last-name')
            var thisAcceptance = thisParents.find('.wpfnl-acceptance_checkbox')
            var thisPhone = thisParents.find('.wpfnl-phone')

            thisParents.find('.response').css('display', 'none')
            if (
                (thisEmail.val() == '' && thisEmail.prop('required')) ||
                (thisLastName.val() == '' && thisLastName.prop('required')) ||
                (thisFirstName.val() == '' && thisFirstName.prop('required')) ||
                (thisAcceptance.val() == '' && thisAcceptance.prop('required')) ||
                (thisPhone.val() == '' && thisPhone.prop('required'))
            ) {
                thisParents.find('.response').css('color', 'red')
                thisParents.find('.response').text('Please fill all the required fields')
                thisParents.find('.response').css('display', 'flex')
                // setTimeout(function() {
                // 	$('.wpfnl-gutenberg-optin-form-wrapper .response').css('display','none');
                //  }, 2000);

                return false
            }
            $('.wpfnl-optin-form .wpfnl-optin-form-group .btn-optin')
                .prop('disabled', true)
                .addClass('show-loader')

            var ajaxurl = wpfnl_obj.ajaxurl,
                security = wpfnl_obj.optin_form_nonce,
                step_id = wpfnl_obj.step_id,
                email = '',
                data = {
                    action: 'wpfnl_gutenberg_optin_submission',
                    security: security,
                    step_id: step_id,
                    url: window.location.href,
                    postData: $(this).serialize(),
                },
                form = $(this)
            form.find('.wpfnl-loader').show()
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: data,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        form.hide()
                        let post_action = response.post_action
                        $('.wpfnl-optin-form .wpfnl-optin-form-group .btn-optin')
                            .prop('disabled', false)
                            .removeClass('show-loader')
                        thisParents.find('.response').fadeIn('fast')
                        thisParents.find('.response').css('color', 'green')
                        thisParents.find('.response').text(response.notification_text)
                        if ('notification' !== post_action) {
                            setTimeout(function () {
                                window.location.href = response.redirect_url
                            }, 1000)
                        }
                    } else {
                        thisParents.find('.response').fadeIn('fast')
                        form.find('.wpfnl-loader').css('display', 'none')
                        thisParents.find('.response').css('color', 'red')
                        thisParents.find('.response').text(response.notification_text)
                    }
                },
            })
        })

        $(document).on('click', '#wpfnl-lms-access-course', function (e) {
            e.preventDefault()
            var next_step_url = $(this).attr('href')
            var ajaxurl = window.wpfnl_obj.ajaxurl

            var data = {
                action: 'wpfnl_learndash_already_enroll_course',
                step_id: window.wpfnl_obj.step_id,
                user_id: window.wpfnl_obj.user_id,
            }

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: data,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('.wpfnl-lms-access-course-message').text(
                            'You are already enrolled in this course.',
                        )
                        setTimeout(function () {
                            window.location = next_step_url
                        }, 2500)
                    }
                },
            })
        })

        $(document).ready(function () {
            // window.onbeforeunload = doAjaxBeforeUnload;
            // $(window).unload(doAjaxBeforeUnload);

            // Add validation for product quantity inputs on checkout page
            $(document).on('keypress', '.wpfnl-quantity-setect', function(e) {
                // Prevent typing minus sign, plus sign, 'e', and decimal point
                if (['-', '+', 'e', 'E', '.'].includes(e.key)) {
                    e.preventDefault();
                }
            });

            $(document).on('input', '.wpfnl-quantity-setect', function(e) {
                // Validate and sanitize quantity value
                let quantity = parseInt($(this).val(), 10);
                if (isNaN(quantity) || quantity < 1) {
                    $(this).val(1);
                    quantity = 1;
                }
            });

            $(document).on('blur', '.wpfnl-quantity-setect', function(e) {
                // On blur, ensure the value is valid
                let quantity = parseInt($(this).val(), 10);
                if (isNaN(quantity) || quantity < 1) {
                    $(this).val(1);
                }
            });
        })

        /**
         * Modern checkout customer login/account toggle flow.
         * - Initially shows only email for logged-out users.
         * - Click "Log in" => reveal password section with slide animation.
         * - If user does not click login, check email existence via AJAX and toggle sections.
         */
        function initModernCheckoutCustomerFlow() {
            var $wrapper = $('.wpfnl-modern-checkout-wrapper .wpfnl-customer-info, .wpfnl-modern-one-column-wrapper .wpfnl-customer-info')
            if (!$wrapper.length) {
                return
            }

            var $email = $wrapper.find('input[name="billing_email"]')
            var $passwordSection = $wrapper.find('.wpfnl-customer-login-section')
            var $createAccountSection = $wrapper.find('.wpfnl-create-account-section')
            var $inlineError = $wrapper.find('.wpfnl-email-inline-error')
            var hasClickedLogin = false
            var lastCheckedEmail = ''
            var checkTimer = null

            if (!$inlineError.length && $email.length) {
                $inlineError = $('<p class="wpfnl-email-inline-error" style="display:none;color:#c62828;font-size:12px;margin-top:6px;"></p>')
                $email.first().closest('.form-row').append($inlineError)
            }

            if ($passwordSection.length) {
                $passwordSection.stop(true, true).hide()
            }

            if ($createAccountSection.length) {
                $createAccountSection.attr('hidden', true).stop(true, true).hide()
            }

            $(document)
                .off('click.wpfnlModernCustomerFlow', '.wpfnl-customer-login-url')
                .on('click.wpfnlModernCustomerFlow', '.wpfnl-customer-login-url', function (e) {
                    e.preventDefault()
                    hasClickedLogin = true
                    if ($createAccountSection.length) {
                        $createAccountSection.stop(true, true).slideUp(180).attr('hidden', true)
                    }
                    if ($passwordSection.length) {
                        $passwordSection.stop(true, true).slideDown(220)
                    }
                })

            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
            }

            function showInlineEmailError(message) {
                if (!$inlineError.length) {
                    return
                }
                $inlineError.text(message).stop(true, true).slideDown(140)
            }

            function hideInlineEmailError() {
                if (!$inlineError.length) {
                    return
                }
                $inlineError.stop(true, true).slideUp(120)
            }

            function updateSectionsForEmailCheck(result) {
                if (hasClickedLogin) {
                    return
                }

                if (!result || !result.data) {
                    return
                }

                var payload = result.data
                var emailExists = !!payload.success
                var loginAllowed = !!payload.is_login_allowed

                if (emailExists && loginAllowed) {
                    if ($createAccountSection.length) {
                        $createAccountSection.stop(true, true).slideUp(180).attr('hidden', true)
                    }
                    if ($passwordSection.length) {
                        $passwordSection.stop(true, true).slideDown(220)
                    }
                    return
                }

                if ($passwordSection.length) {
                    $passwordSection.stop(true, true).slideUp(180)
                }

                if ($createAccountSection.length) {
                    $createAccountSection.removeAttr('hidden').stop(true, true).slideDown(220)
                }
            }

            function checkEmailExistence() {
                if (!$email.length || hasClickedLogin) {
                    return
                }

                var email = ($email.val() || '').trim()
                if (!email.length) {
                    hideInlineEmailError()
                    return
                }

                if (!isValidEmail(email)) {
                    showInlineEmailError('Entered email address is not a valid email.')
                    return
                }

                hideInlineEmailError()

                if (email === lastCheckedEmail) {
                    return
                }

                lastCheckedEmail = email

                $.ajax({
                    type: 'POST',
                    url: window.wpfnl_obj.ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'wpf_check_email_exists',
                        email: email,
                        security: window.wpfnl_obj.ajax_nonce,
                    },
                    success: function (response) {
                        updateSectionsForEmailCheck(response)
                    },
                    error: function () {
                        // Keep UI quiet on network/server error to avoid noisy checkout UX.
                    },
                })
            }

            $(document)
                .off('input.wpfnlModernCustomerFlow keyup.wpfnlModernCustomerFlow paste.wpfnlModernCustomerFlow blur.wpfnlModernCustomerFlow change.wpfnlModernCustomerFlow', '.wpfnl-modern-checkout-wrapper input[name="billing_email"], .wpfnl-modern-one-column-wrapper input[name="billing_email"]')
                .on('input.wpfnlModernCustomerFlow keyup.wpfnlModernCustomerFlow paste.wpfnlModernCustomerFlow blur.wpfnlModernCustomerFlow change.wpfnlModernCustomerFlow', '.wpfnl-modern-checkout-wrapper input[name="billing_email"], .wpfnl-modern-one-column-wrapper input[name="billing_email"]', function () {
                    clearTimeout(checkTimer)
                    checkTimer = setTimeout(checkEmailExistence, 250)
                })
        }

        initModernCheckoutCustomerFlow()

        $(document.body).on('updated_checkout', function () {
            initModernCheckoutCustomerFlow()
        })

        //----------optin form click to expand btn option-----------
        $('.clickto-expand-btn').on('click', function (e) {
            $(this).parents('.wpfnl-optin-clickto-expand').hide()
            $('.wpfnl-optin-form.clickto-expand-optin').show()
        })

        // Preserve custom Place Order button HTML after WooCommerce's payment_method_selected
        // handler calls .text() which strips all child elements (icon, price, sub-text spans).
        var wpfnlPlaceOrderHTML = null

        function wpfnlCachePlaceOrderBtn() {
            var $btn = $('#place_order')
            if ($btn.find('.wpfnl-place-order-icon, .wpfnl-place-order-price, .wpfnl-place-order-sub-text').length) {
                wpfnlPlaceOrderHTML = $btn.html()
            }
        }

        function wpfnlRestorePlaceOrderBtn() {
            if (wpfnlPlaceOrderHTML) {
                $('#place_order').html(wpfnlPlaceOrderHTML)
            }
        }

        $(document.body).on('updated_checkout', function () {
            wpfnlCachePlaceOrderBtn()
        })

        $(document.body).on('payment_method_selected', function () {
            wpfnlRestorePlaceOrderBtn()
        })

        // Also restore on initial load after a short delay to catch early WC init
        $(document.body).on('init_checkout', function () {
            wpfnlCachePlaceOrderBtn()
        })

        // Enhanced Phone Field Implementation
        function initEnhancedPhoneField() {
            if (window.wpfnl_obj && window.wpfnl_obj.enhanced_phone_field_enabled === 'yes') {
                if ($('#wpfnl-iti-styles').length === 0) {
                    $('<style id="wpfnl-iti-styles">.iti { width: 100%; } .wpfnl-invalid-phone { border-color: #a00 !important; }</style>').appendTo('head');
                }

                var phoneInputs = document.querySelectorAll('#billing_phone, .wpfnl-phone, input[type="tel"]');

                phoneInputs.forEach(function(input) {
                    if (!input.classList.contains('iti__tel-input') && window.intlTelInput) {
                        input.classList.add('iti__tel-input');

                        var iti = window.intlTelInput(input, {
                            initialCountry: "auto",
                            separateDialCode: true,
                            geoIpLookup: function(success, failure) {
                                $.get('https://ipinfo.io', function() {}, "jsonp").always(function(resp) {
                                    var countryCode = (resp && resp.country) ? resp.country : "us";
                                    success(countryCode);
                                });
                            },
                            utilsScript: window.wpfnl_obj.phone_utils_url,
                        });

                        if (window.wpfnl_obj.phone_help_text) {
                            var helpText = document.createElement('small');
                            helpText.className = 'wpfnl-phone-help-text';
                            helpText.style.display = 'block';
                            helpText.style.color = '#686F7F';
                            helpText.style.marginTop = '4px';
                            helpText.style.fontSize = '12px';
                            helpText.innerText = window.wpfnl_obj.phone_help_text;
                            input.parentNode.parentNode.appendChild(helpText);
                        }

                        if (window.wpfnl_obj.validate_phone_number === 'yes') {
                            input.addEventListener('blur', function() {
                                if (input.value.trim()) {
                                    if (iti.isValidNumber()) {
                                        input.classList.remove('wpfnl-invalid-phone');
                                        $(input).parents('.form-row').removeClass('woocommerce-invalid');
                                    } else {
                                        input.classList.add('wpfnl-invalid-phone');
                                        $(input).parents('.form-row').addClass('woocommerce-invalid');
                                    }
                                }
                            });
                        }

                        if (window.wpfnl_obj.save_phone_number_format === 'with_country_code') {
                            var $form = $(input).closest('form.checkout');
                            if ($form.length) {
                                $form.on('checkout_place_order', function() {
                                    if (iti.isValidNumber()) {
                                        input.value = iti.getNumber();
                                    }
                                });
                            }
                        }
                    }
                });
            }
        }

        initEnhancedPhoneField();

        function initCollapsibleOrderSummary() {
            if (window.wpfnl_obj && window.wpfnl_obj.collapsible_order_summary_enabled !== 'no') {
                var $table = $('.woocommerce-checkout-review-order-table');
                if ($table.length > 0 && $('.wpfnl-mobile-order-summary-toggle').length === 0) {
                    var toggleHtml = '<div class="wpfnl-mobile-order-summary-toggle">' +
                                     '<span class="wpfnl-mos-text">Show order summary</span>' +
                                     '<span class="wpfnl-mos-icon">▼</span>' +
                                     '</div>';
                    $(toggleHtml).insertBefore($table);

                    $('.wpfnl-mobile-order-summary-toggle').on('click', function() {
                        var $content = $(this).next('.woocommerce-checkout-review-order-table');
                        var $icon = $(this).find('.wpfnl-mos-icon');
                        var $text = $(this).find('.wpfnl-mos-text');

						$(this).toggleClass('active-toggle');

                        $content.slideToggle(300, function() {
                            var isVisible = $content.is(':visible');
                            $text.text(isVisible ? 'Hide order summary' : 'Show order summary');
                            $icon.css('transform', isVisible ? 'rotate(180deg)' : 'rotate(0deg)');
                        });

                    });
                }

                var handleResize = function() {
                    if ($(window).width() <= 768) {
                        $('.wpfnl-mobile-order-summary-toggle').css('display', 'flex');
                        if (!$('.woocommerce-checkout-review-order-table').hasClass('wpfnl-mos-collapsed') && !$('.woocommerce-checkout-review-order-table').is(':visible')) {
                            // Already handled
                        } else if (!$('.woocommerce-checkout-review-order-table').hasClass('wpfnl-mos-collapsed')) {
                            $('.woocommerce-checkout-review-order-table').hide().addClass('wpfnl-mos-collapsed');
                            $('.wpfnl-mobile-order-summary-toggle .wpfnl-mos-text').text('Show order summary');
                            $('.wpfnl-mobile-order-summary-toggle .wpfnl-mos-icon').css('transform', 'rotate(0deg)');
                        }
                    } else {
                        $('.wpfnl-mobile-order-summary-toggle').hide();
                        $('.woocommerce-checkout-review-order-table').show().removeClass('wpfnl-mos-collapsed');
                    }
                };

                $(window).on('resize', handleResize);
                handleResize();
            }
        }

        initCollapsibleOrderSummary();

        $(document.body).on('updated_checkout', function () {
            initEnhancedPhoneField();
            if ($('.wpfnl-mobile-order-summary-toggle').length === 0) {
                initCollapsibleOrderSummary();
            } else {
                if ($(window).width() <= 768 && $('.woocommerce-checkout-review-order-table').hasClass('wpfnl-mos-collapsed')) {
                    $('.woocommerce-checkout-review-order-table').hide();
                } else if ($(window).width() > 768) {
                    $('.woocommerce-checkout-review-order-table').show();
                }
            }
        });

		// ----start modern multistep checkout toggle----
		var wpfnlMultistepOrder = ['information', 'shipping', 'payment'];

		/**
		 * Returns the jQuery set of containers to validate for a given step.
		 */
		function wpfnlGetStepContainers(step) {
			var $containers = $();
			if ('information' === step) {
				$containers = $('.wpfnl-modern-multistep .wpfnl-modern-section--customer-information')
					.add('.wpfnl-modern-multistep .wpfnl-billing-fields');
			} else if ('shipping' === step) {
				$containers = $('.wpfnl-modern-multistep .woocommerce-additional-fields');
				if ($('#ship-to-different-address-checkbox').is(':checked')) {
					$containers = $containers.add('.wpfnl-modern-multistep .wpfnl-shipping-fields');
				}
			}
			return $containers;
		}

		/**
		 * Returns true if all visible required fields in the given step are filled.
		 */
		function wpfnlIsStepValid(step) {
			var isValid = true;
			wpfnlGetStepContainers(step).find('.form-row.validate-required').each(function () {
				if (!isValid) return false; // break early

				var $row = $(this);
				if (!$row.is(':visible')) return; // skip hidden rows

				var $select    = $row.find('select').first();
				var $checkbox  = $row.find('input[type="checkbox"]').first();
				var $textInput = $row.find('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), textarea').first();

				if ($select.length) {
					if (!$select.val()) isValid = false;
				} else if ($checkbox.length) {
					if (!$checkbox.is(':checked')) isValid = false;
				} else if ($textInput.length) {
					if (!$.trim($textInput.val())) isValid = false;
				}
			});
			return isValid;
		}

		function wpfnlSyncNextStepBtn(activeStep) {
			var $btn = $('.wpfnl-modern-multistep-navigation .next-step-btn');
			if (!$btn.length) return;
			var currentIndex = wpfnlMultistepOrder.indexOf(activeStep);
			var nextIndex = currentIndex + 1;
			if (nextIndex < wpfnlMultistepOrder.length) {
				$btn.attr('current-step', activeStep);
				$btn.attr('next-step', wpfnlMultistepOrder[nextIndex]);
			} else {
				$btn.prop('disabled', true);
				return;
			}
			wpfnlValidateCurrentStep();
		}

		function wpfnlValidateCurrentStep() {
			var $btn = $('.wpfnl-modern-multistep-navigation .next-step-btn');
			if (!$btn.length) return;
			var currentStep = $btn.attr('current-step') || 'information';
			$btn.prop('disabled', !wpfnlIsStepValid(currentStep));
		}

		// Live validation — re-check whenever a field value changes
		$(document).on(
			'input change',
			'.wpfnl-modern-multistep .wpfnl-modern-section--customer-information input, ' +
			'.wpfnl-modern-multistep .wpfnl-billing-fields input, ' +
			'.wpfnl-modern-multistep .wpfnl-billing-fields select, ' +
			'.wpfnl-modern-multistep .wpfnl-shipping-fields input, ' +
			'.wpfnl-modern-multistep .wpfnl-shipping-fields select, ' +
			'.wpfnl-modern-multistep .woocommerce-additional-fields input, ' +
			'.wpfnl-modern-multistep .woocommerce-additional-fields textarea',
			wpfnlValidateCurrentStep
		);

		// Re-check when ship-to-different-address toggle changes
		$(document).on('change', '#ship-to-different-address-checkbox', wpfnlValidateCurrentStep);

		// Re-check after WooCommerce refreshes the checkout fragments
		$(document.body).on('updated_checkout', wpfnlValidateCurrentStep);

		// Initial check on page load
		wpfnlValidateCurrentStep();

		function initModernMultistepCheckoutToggle(target) {
			if ( 'information' == target ) {
				$('.wpfnl-modern-multistep .wpfnl-modern-section--customer-information').show();
				$('.wpfnl-modern-multistep .wpfnl-billing-fields').show();

				$('.wpfnl-modern-multistep .wpfnl-shipping-fields').hide();
				$('.wpfnl-modern-multistep .woocommerce-additional-fields').hide();
				$('.wpfnl-modern-multistep .wpfnl-modern-section--payment').hide();
				$('.wpfnl-modern-multistep .wpfnl-modern-multistep-navigation').show();
				$('.wpfnl-modern-multistep .money-back-guarantee-text').show();

			}else if ( 'shipping' == target ) {
				$('.wpfnl-modern-multistep .wpfnl-modern-section--customer-information').hide();
				$('.wpfnl-modern-multistep .wpfnl-billing-fields').hide();

				$('.wpfnl-modern-multistep .wpfnl-shipping-fields').show();
				$('.wpfnl-modern-multistep .woocommerce-additional-fields').show();
				$('.wpfnl-modern-multistep .wpfnl-modern-section--payment').hide();
				$('.wpfnl-modern-multistep .wpfnl-modern-multistep-navigation').show();
				$('.wpfnl-modern-multistep .money-back-guarantee-text').show();

			}else if ( 'payment' == target ) {
				$('.wpfnl-modern-multistep .wpfnl-modern-section--customer-information').hide();
				$('.wpfnl-modern-multistep .wpfnl-billing-fields').hide();

				$('.wpfnl-modern-multistep .wpfnl-shipping-fields').hide();
				$('.wpfnl-modern-multistep .woocommerce-additional-fields').hide();
				$('.wpfnl-modern-multistep .wpfnl-modern-section--payment').show();
				$('.wpfnl-modern-multistep .wpfnl-modern-multistep-navigation').hide();
				$('.wpfnl-modern-multistep .money-back-guarantee-text').hide();
			}
		}

		$(document).on('click', '.wpfnl-modern-multistep-nav .wpfnl-modern-multistep-nav-step-btn', function (e) {
			e.preventDefault();
			var target = $(this).data('step');

			// Determine the currently active step
			var $activeBtn = $('.wpfnl-modern-multistep-nav .wpfnl-modern-multistep-nav-step.active .wpfnl-modern-multistep-nav-step-btn');
			var currentStep = $activeBtn.length ? $activeBtn.data('step') : wpfnlMultistepOrder[0];
			var currentIndex = wpfnlMultistepOrder.indexOf(currentStep);
			var targetIndex  = wpfnlMultistepOrder.indexOf(target);

			// Navigating forward — block if current step is invalid
			if (targetIndex > currentIndex && !wpfnlIsStepValid(currentStep)) {
				return;
			}

			var $btn = $(this);
			var $nav = $btn.closest('.wpfnl-modern-multistep-nav');
			var $li = $btn.closest('li');
			$li.addClass('active').siblings().removeClass('active');
			$li.prevAll('li[data-step]').addClass('completed');
			$li.nextAll('li[data-step]').removeClass('completed');

			$nav.find('.wpfnl-modern-multistep-nav-step-btn')
				.attr('aria-selected', 'false')
				.attr('tabindex', '-1');
			$btn.attr('aria-selected', 'true').attr('tabindex', '0');
			initModernMultistepCheckoutToggle(target);
			wpfnlSyncNextStepBtn(target);
		});

		$(document).on('click', '.wpfnl-modern-multistep-navigation .next-step-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var nextStep = $btn.attr('next-step');
			if (!nextStep) return;

			// Trigger nav tab click to update classes and panel visibility
			$('.wpfnl-modern-multistep-nav-step-btn[data-step="' + nextStep + '"]').trigger('click');

			// Sync button attributes to the new active step
			wpfnlSyncNextStepBtn(nextStep);
		});
		// ----end modern multistep checkout toggle----

        // ---- Next Step Button Viewport Animation ----
        function wpfnlInitButtonAnimations() {
            var $buttons = $('.wpfunnels-block-next-step-button.wpfnl-animation, .bricks-button.wpfnl-animation');
            if (!$buttons.length) return;

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            $(entry.target).addClass('wpfnl-animated');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.2 });

                $buttons.each(function() {
                    observer.observe(this);
                });
            } else {
                // Fallback for older browsers
                function wpfnlCheckButtonsInView() {
                    $buttons.each(function() {
                        if ($(this).hasClass('wpfnl-animated')) return;
                        var top = $(this).offset().top;
                        var bottom = top + $(this).outerHeight();
                        var viewTop = $(window).scrollTop();
                        var viewBottom = viewTop + $(window).height();
                        if (bottom > viewTop && top < viewBottom) {
                            $(this).addClass('wpfnl-animated');
                        }
                    });
                }
                $(window).on('scroll.wpfnlAnim resize.wpfnlAnim', wpfnlCheckButtonsInView);
                wpfnlCheckButtonsInView();
            }
        }

        wpfnlInitButtonAnimations();
        // ---- End Next Step Button Viewport Animation ----

		// -----svg checkbox icon append in checkout page-----
		var checkboxIcon = '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x=".5" y=".5" width="21" height="21" rx="4.5" fill="#fff" stroke="#dedede"></rect><path d="M8.87459 15.3313C8.53396 15.3315 8.20728 15.1961 7.96662 14.955L5.22153 12.2109C4.92616 11.9155 4.92616 11.4365 5.22153 11.141C5.517 10.8457 5.99595 10.8457 6.29142 11.141L8.87459 13.7242L15.7086 6.89023C16.004 6.59486 16.483 6.59486 16.7785 6.89023C17.0738 7.1857 17.0738 7.66465 16.7785 7.96012L9.78256 14.955C9.5419 15.1961 9.21523 15.3315 8.87459 15.3313Z" fill="#fff"></path></svg>';

		function initLoginCheckboxIcon() {
			$('.wpfnl-checkout .woocommerce-form-login.login .woocommerce-form-login__rememberme>span').prepend(checkboxIcon);
		}

		function initTermsCheckboxIcon() {
			$('.woocommerce-terms-and-conditions-wrapper .woocommerce-form__label-for-checkbox .woocommerce-terms-and-conditions-checkbox-text').prepend(checkboxIcon);
			$('.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew>label').prepend(checkboxIcon);
		}

		$(document).ready(function () {
			initLoginCheckboxIcon();
			initTermsCheckboxIcon();
		});

		$(document.body).on('updated_checkout', function () {
			initTermsCheckboxIcon();
		});





    })
})(jQuery)
