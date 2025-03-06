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
                    if ($(this).find('.field-required').length) {
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
                        if ($(this).find('.field-required').length) {
                            isValidate = false
                        }
                    })
                }
                //------end two step checkout billing field validation-----

                //----shipping validation---
                if (is_enabled_dirrerent_address == true) {
                    checkoutFieldValidation('#wpfnl_checkout_shipping')

                    $('#wpfnl_checkout_shipping .validate-required').each(function () {
                        if ($(this).find('.field-required').length) {
                            isValidate = false
                        }
                    })

                    if (isValidate == false) {
                        return false
                    }
                } else {
                    checkoutFieldValidation('.woocommerce-additional-fields')

                    $('.woocommerce-additional-fields .validate-required').each(function () {
                        if ($(this).find('.field-required').length) {
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
                    if ($(this).find('.field-required').length) {
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
                        if ($(this).find('.field-required').length) {
                            isValidate = false
                        }
                    })
                }
                //------end two step checkout billing field validation-----

                if (is_enabled_dirrerent_address == true) {
                    checkoutFieldValidation('#wpfnl_checkout_shipping')

                    $('#wpfnl_checkout_shipping .validate-required').each(function () {
                        if ($(this).find('.field-required').length) {
                            isValidate = false
                        }
                    })

                    if (isValidate == false) {
                        return false
                    }
                } else {
                    checkoutFieldValidation('.woocommerce-additional-fields')

                    $('.woocommerce-additional-fields .validate-required').each(function () {
                        if ($(this).find('.field-required').length) {
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

        function checkoutFieldValidation(step) {
            $(step + ' .validate-required input').each(function () {
                var fieldValue = $(this).val()
                if (!fieldValue) {
                    if (!maybeNeedAccount) {
                        if (
                            'account_username' === $(this).attr('name') ||
                            'account_password' === $(this).attr('name')
                        ) {
                            $(this).parent().find('.field-required').remove()
                            $(this).parents('.form-row').removeClass('required-field-appended')
                            return true
                        } else {
                            $(this).parent().find('.field-required').remove()
                            $(this)
                                .parent()
                                .append('<span class="field-required">Field required</span>')
                            $(this).parents('.form-row').addClass('required-field-appended')
                        }
                    } else {
                        $(this).parent().find('.field-required').remove()
                        $(this)
                            .parent()
                            .append('<span class="field-required">Field required</span>')
                        $(this).parents('.form-row').addClass('required-field-appended')
                    }
                } else if (fieldValue) {
                    $(this).parent().find('.field-required').remove()
                    $(this).parents('.form-row').removeClass('required-field-appended')
                }
            })

            $(step + ' .validate-required select').each(function () {
                var fieldValue = $(this).children('option:selected').val()
                if (!fieldValue) {
                    $(this).parent().find('.field-required').remove()
                    $(this).parent().append('<span class="field-required">Field required</span>')
                    $(this).parents('.form-row').addClass('required-field-appended')
                } else if (fieldValue) {
                    $(this).parent().find('.field-required').remove()
                    $(this).parents('.form-row').removeClass('required-field-appended')
                }
            })

            $(step + ' .validate-required input[type="checkbox"]').each(function () {
                var fieldValue = $(this).is(':checked')
                if (!fieldValue) {
                    $(this).parent().find('.field-required').remove()
                    $(this).parent().append('<span class="field-required">Field required</span>')
                } else if (fieldValue) {
                    $(this).parent().find('.field-required').remove()
                }
            })
        }

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

            if (replace === 'yes' && checker) {
                $('.wpfnl-order-bump-cb').each(function (index, checkbox) {
                    if ($(checkbox).attr('data-key') !== key) {
                        $(checkbox).prop('checked', false);
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

                    $('.wpfnl-order-bump-cb').each(function (index) {
                        if ( 'yes' === isLms ) {
                            if ($(this).val() != product) {
                                $(this).prop('checked', false)
                            }
                        }
                    })

                    $('.wpfnl-order-bump-cb').each(function() {
                        $(this).prop('disabled', false);
                    });

                    $('.wpfnl-order-bump__popup-wrapper')
                    .removeClass('show')
                    .css('top', '-' + inner_height + 'px')

                    $('.wpfnl-place-order').removeClass('wpfnl-place-order-add-overlay')
                },
            })
        })

        //----show order bump modal-----
        var inner_height = $('.wpfnl-order-bump__popup-wrapper').innerHeight() + 30
        $('.wpfnl-order-bump__popup-wrapper').css('top', '-' + inner_height + 'px')

        $(window).on('load', function () {
            if (!$('.wpfnl-pre-purchase').length ) { // check if 'wpfnl-pre-purchase' class exists
                setTimeout(function () {
                    $('.wpfnl-order-bump__popup-wrapper').addClass('show').css('top', '30px')
                }, 2000)
            }
        })

        $('.close-order-bump').on('click', function () {
            $('.wpfnl-order-bump__popup-wrapper')
                .removeClass('show')
                .css('top', '-' + inner_height + 'px')

            $('.wpfnl-place-order')
                .removeClass('wpfnl-place-order-add-overlay')

        })



        //--------woocommerce checkout page coupon toggle add class-----------
        $('.wpfnl-checkout .woocommerce-form-coupon-toggle .showcoupon').on('click', function () {
            $(this).parents('.woocommerce-form-coupon-toggle').toggleClass('show-form')
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
        })

        //----------optin form click to expand btn option-----------
        $('.clickto-expand-btn').on('click', function (e) {
            $(this).parents('.wpfnl-optin-clickto-expand').hide()
            $('.wpfnl-optin-form.clickto-expand-optin').show()
        })
    })
})(jQuery)
