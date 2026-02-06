jQuery(function ($) {

    'use strict';
    $(document).ready(function ($) {
        /**
         * toggle password functionality on settings page
         */
        $(".toggle-eye-icon").click(function() {
            $('.toggle-eye-icon').removeClass('hide-eye-icon');
            $(this).addClass('hide-eye-icon');
            let input = $('#wpfnl-google-map-api-key');
            if ( "password" === input.attr("type") ) {
                input.attr("type", "text");
            } else {
                input.attr("type", "password");
            }
        });

        function toggleLogOptions() {
            const $checkbox = $('#enable-log-status');
            const $logOptionsWrapper = $('.log-options-wrapper');

            if (!$checkbox.length || !$logOptionsWrapper.length) return;

            $checkbox.on('change', function () {
                if ($(this).is(':checked')) {
                    $logOptionsWrapper.show();
                } else {
                    $logOptionsWrapper.hide();
                }
            });
        }
        toggleLogOptions();

        function enable_disable_recapcha() {
            if ($("input[name='wpfnl-recapcha-enable']").prop("checked")) {
                $("#wpfnl-recapcha").show()
            } else {
                $("#wpfnl-recapcha").hide()
            }
        }

        // Toggle revenue report settings
        function toggleRevenueReportSettings() {
            if ($("#wpfnl-enable-revenue-report").is(':checked')) {
                $("#wpfnl-revenue-report-settings").show();
            } else {
                $("#wpfnl-revenue-report-settings").hide();
            }
        }
        toggleRevenueReportSettings();
        
        $("#wpfnl-enable-revenue-report").on('change', function() {
            toggleRevenueReportSettings();
        });

        $('#wpfunnels-page-builder').on('click', '.wpfnl-single-item', function() {
            $('#wpfunnels-page-builder .wpfnl-single-item').removeClass('checked');
            $(this).addClass('checked');
            $('#clear-template').trigger('click');
        });

        $('#wpfunnels-funnel-type').on('click', '.wpfnl-single-item', function() {
            $('#wpfunnels-funnel-type .wpfnl-single-item').removeClass('checked');
            $(this).addClass('checked');
            $('#clear-template').trigger('click');
        });

        if ($("input[name='wpfnl-utm-enable']").prop("checked")) {
            $("#wpfnl-utm").show()
        } else {
            $("#wpfnl-utm").hide()
        }
        $('#utm-enable').on('click', function () {
            if ($("input[name='wpfnl-utm-enable']").prop("checked")) {
                $("#wpfnl-utm").show()
            } else {
                $("#wpfnl-utm").hide()
            }
        })

        if ($("input[name='wpfnl-gtm-enable']").prop("checked")) {
            $("#wpfnl-gtm").show()
        } else {
            $("#wpfnl-gtm").hide()
        }
        $('#gtm-enable').on('click', function () {
            if ($("input[name='wpfnl-gtm-enable']").prop("checked")) {
                $("#wpfnl-gtm").show()
            } else {
                $("#wpfnl-gtm").hide()
            }
        })


        if ($("input[name='enable-skip-cart']").prop("checked")) {
            $(".wpfnl-skip-cart").show()
        } else {
            $(".wpfnl-skip-cart").hide()
        }
        $('#enable-skip-cart').on('click', function () {
            if ($("input[name='enable-skip-cart']").prop("checked")) {
                $(".wpfnl-skip-cart").show()
            } else {
                $(".wpfnl-skip-cart").hide()
            }
        })


        if ($("input[name='wpfnl-facebook-pixel-enable']").prop("checked")) {
            $("#wpfnl-facebook-pixel").show()
        } else {
            $("#wpfnl-facebook-pixel").hide()
        }
        $('#facebook-pixel-enable').on('click', function () {
            if ($("input[name='wpfnl-facebook-pixel-enable']").prop("checked")) {
                $("#wpfnl-facebook-pixel").show()
            } else {
                $("#wpfnl-facebook-pixel").hide()
            }
        })

        enable_disable_recapcha()

        $('#recapcha-pixel-enable').on('click', function () {
            enable_disable_recapcha()
        })

        $('.wpfnl-log-view').on('click', function (e) {
            e.preventDefault();
            var thisElement = $(this);
            show_log(thisElement);
        })

        $('.wpfnl-log-delete').on('click', function (e) {
            e.preventDefault();
            var thisElement = $(this);
            delete_log(thisElement);
        })

        /**
         * Show WPFunnels log
         */
        function show_log(thisElement) {
            thisElement.find('.wpfnl-loader').css('display', 'inline-block');

            var log_key = $('#wpfnl-log option:selected').val();
            var payload = {
                'logKey': log_key
            };
            if (!log_key) {
                $('#log-viewer pre').html('');
            } else {
                wpAjaxHelperRequest('wpfnl-show-log', payload)
                    .success(function (response) {
                        thisElement.find('.wpfnl-loader').hide();

                        if (response.success) {
                            $('#log-viewer pre').html(response.content);
                        }
                    })
                    .error(function (response) {
                    });
            }

        }

        /**
         * Show WPFunnels log
         */
        function delete_log(thisElement) {
            thisElement.find('.wpfnl-loader').css('display', 'inline-block');
            localStorage.setItem("buttonClicked", true);

            var log_key = $('#wpfnl-log option:selected').val();
            var payload = {
                'logKey': log_key
            };
            if (!log_key) {
                $('#log-viewer').html('');
            } else {
                wpAjaxHelperRequest('wpfnl-delete-log', payload)
                    .success(function (response) {
                        if (response.success) {
                            $('#wpfnl-log option:selected').remove();
                            $('#log-viewer').empty();
                        }

                        thisElement.find('.wpfnl-loader').hide();

                    })
                    .error(function (response) {
                    });
            }

        }

        var GeneralSettingsHandler = function () {
            $(document.body)
                .on('click', '#wpfnl-update-global-settings', this.updateGeneralSettings)
                .on('click', '#clear-template', this.clearTemplates)
                .on('click', '#clear-transients', this.clearTransients)

            /**
             * settings page Permalink Settings permalink
             * change on keyup
             *
             * @since 1.0.0
             */
            $('code.stepbase').text($('input[name="wpfnl-permalink-step-base"]').val());
            $('code.funnelbase').text($('input[name="wpfnl-permalink-funnel-base"]').val());

            $('input[name="wpfnl-permalink-step-base"]').keyup(function () {
                if ($(this).val() != '') {
                    $('code.stepbase').text($(this).val());
                } else {
                    $('code.stepbase').text('wpfunnels');
                }
            });

            $('input[name="wpfnl-permalink-funnel-base"]').keyup(function () {
                if ($(this).val() != '') {
                    $('code.funnelbase').text($(this).val());
                } else {
                    $('code.funnelbase').text('step');
                }
            });

        };


        /**
         * update general settings
         *
         * @param e
         * @since 1.0.0
         */
        GeneralSettingsHandler.prototype.updateGeneralSettings = function (e) {
            e.preventDefault();
            var userRole = {};
            var permittedRole = {};
            var gtmEvents = {};
            var fbTrackEvent = {};
            var advancedSettings = {};
            $("input[name='analytics-role[]']").map(function () {
                if ($(this).prop("checked")) {
                    userRole[$(this).data('role')] = 'true';
                }
                return $(this).prop("checked") ? 1 : 0;
            }).get();

            $("input[name='permission-role[]']").map(function () {
                if ($(this).prop("checked")) {
                    permittedRole[$(this).data('role')] = 'true';
                }
                return $(this).prop("checked") ? 1 : 0;
            }).get();


            $("input[name='wpfnl-gtm-events[]']").map(function () {
                if ($(this).prop("checked")) {
                    gtmEvents[$(this).data('role')] = 'true';
                }
                return $(this).prop("checked") ? 1 : 0;
            }).get();

            $("input[name='wpfnl-facebook_pixel_events[]']").map(function () {
                if ($(this).prop("checked")) {
                    fbTrackEvent[$(this).data('role')] = 'true';
                }
                return $(this).prop("checked") ? 1 : 0;
            }).get();


            // get the role management values from settings
            var checkboxes          = $('input[name="user_role[]"]'),
                userRoleManagement  = window.WPFunnelVars.user_role_manager_data;

            checkboxes.each(function(checkbox) {
                var role                    = $(this).attr('data-role');
                userRoleManagement[role]    = $(this).is(":checked") ? 'yes' : 'no';
            });

            var payload = {
                'funnel_type': $('#wpfunnels-funnel-type .wpfnl-single-item.checked').attr('data-value'),
                'builder': $('#wpfunnels-page-builder .wpfnl-single-item.checked').attr('data-value'),
                'uninstall_cleanup': $('#wpfnl-data-cleanup').is(':checked') ? 'on' : 'off',
                'paypal_reference': $('#wpfunnels-paypal-reference').is(':checked') ? 'on' : 'off',
                'analytics_roles': userRole,
                'permission_role': permittedRole,
                'order_bump': $('#wpfunnels-order-bump').is(':checked') ? 'on' : 'off',
                'ab_testing': $('#wpfunnels-ab-testing').is(':checked') ? 'on' : 'off',
                'permalink_settings': $("input[name='wpfunnels-set-permalink']:checked").val(),
                'permalink_step_base': $('#wpfunnels-permalink-step-base').val(),
                'permalink_funnel_base': $('#wpfunnels-permalink-funnel-base').val(),
                'sender_email': $('#wpfunnels-optin-sender-email').val(),
                'sender_name': $('#wpfunnels-optin-sender-name').val(),
                'email_subject': $('#wpfunnels-optin-email-subject').val(),
                'set_permalink': $('input[name="wpfunnels-set-permalink"]:checked').val(),
                'offer_orders': $('input[name="offer-orders"]:checked').val(),
                'skip_offer_step': $('#wpfnl-skip-offer-step').is(':checked') ? 'on' : 'off',
                'skip_offer_step_for_free': $('#wpfnl-skip-offer-step-for-free').is(':checked') ? 'on' : 'off',
                'skip_offer_for_recurring_buyer': $('#wpfnl-skip-offer-for-recurring-buyer').is(':checked') ? 'on' : 'off',
                'skip_offer_for_recurring_buyer_within_year': $('#wpfnl-skip-offer-for-recurring-buyer-within-year').is(':checked') ? 'on' : 'off',
                'show_supported_payment_gateway': $('#wpfnl-show-supported-payment-gateway').is(':checked') ? 'on' : 'off',
                'gtm_enable': $('input[name="wpfnl-gtm-enable"]:checked').val(),
                'gtm_container_id': $('#wpfnl-gtm-container-id').val(),
                'gtm_events': gtmEvents,
                'enable_fb_pixel': $('input[name="wpfnl-facebook-pixel-enable"]:checked').val(),
                'fb_tracking_code': $('#wpfnl-facebook-tracking-code').val(),
                'fb_tracking_events': fbTrackEvent,
                'utm_enable': $('input[name="wpfnl-utm-enable"]:checked').val(),
                'utm_source': $('#wpfnl-utm-source').val(),
                'utm_medium': $('#wpfnl-utm-medium').val(),
                'utm_campaign': $('#wpfnl-utm-campaign').val(),
                'utm_content': $('#wpfnl-utm-content').val(),
                'disable_theme_style': $('#disable-theme-style').is(':checked') ? 'on' : 'off',
                'enable_log_status': $('#enable-log-status').is(':checked') ? 'on' : 'off',
                'enable_skip_cart': $('#enable-skip-cart').is(':checked') ? 'on' : 'off',
                'skip_cart_for': $('input[name="skip-cart"]:checked').val(),
                'enable_recaptcha': $('input[name="wpfnl-recapcha-enable"]:checked').val(),
                'recaptcha_site_key': $('#wpfnl-recapcha-site-key').val(),
                'recaptcha_site_secret': $('#wpfnl-recapcha-site-secret').val(),
                'user_role_management': { roles: userRoleManagement },
                'google_map_api_key'        : $('#wpfnl-google-map-api-key').val(),
                'enable_global_thankyou'    : $('#wpfnl-enable-global-thankyou').is(':checked') ? 'on' : 'off',
                'lms_provider'              : $('#lms-provider-select').val(),
                'enable_revenue_report'     : $('#wpfnl-enable-revenue-report').is(':checked') ? 'yes' : 'no',
                'revenue_report_frequency'  : $('input[name="wpfnl-revenue-report-frequency"]:checked').val(),
                'revenue_report_recipient'  : $('#wpfnl-revenue-report-recipient').val(),
                'revenue_report_subject'    : $('#wpfnl-revenue-report-subject').val(),
                'send_time'                 : $('#wpfnl-send-time').val(),
            };

            var thisLoader  = $(this).find('.wpfnl-loader');
            var thisAlert   = $(this).siblings('.wpfnl-alert');

            thisLoader.fadeIn(); //show loader

            wpAjaxHelperRequest("update-general-settings", payload)
                .success(function (response) {
                    thisLoader.fadeOut();
                    $('#wpfnl-toaster-wrapper').addClass('quick-toastify-successful-notification');

                    $('#wpfnl-toaster-icon').html('<svg width="26" height="26" fill="none" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><path fill="#4BAE4F" fill-rule="evenodd" d="M13 0C5.83 0 0 5.83 0 13s5.83 13 13 13 13-5.83 13-13S20.17 0 13 0z" clip-rule="evenodd"/><path fill="#fff" fill-rule="evenodd" d="M19.287 8.618a.815.815 0 010 1.148l-7.617 7.617a.812.812 0 01-1.148 0l-3.808-3.809a.815.815 0 010-1.147.815.815 0 011.147 0l3.235 3.234 7.044-7.043a.806.806 0 011.147 0z" clip-rule="evenodd"/></svg>');
                    
                    $('#wpfnl-toaster-message').html('Saved Successfully')
                    $('#wpfnl-toaster-wrapper').show();

                    setTimeout(function() {
                        $('#wpfnl-toaster-wrapper').removeClass('quick-toastify-successful-notification');
                        $('#wpfnl-toaster-wrapper').hide(); 
                    }, 2000);

                })
                .error(function (response) {
                    thisLoader.fadeOut();
                    $('#wpfnl-toaster-wrapper').addClass('quick-toastify-warn-notification');

                    $('#wpfnl-toaster-icon').html('<svg width="26" height="26" fill="none" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><path fill="#EC5956" fill-rule="evenodd" d="M26 13c0 7.18-5.82 13-13 13S0 20.18 0 13 5.82 0 13 0s13 5.82 13 13zm-11.375 6.5a1.625 1.625 0 11-3.25 0 1.625 1.625 0 013.25 0zM13 4.875c-.898 0-1.625.728-1.625 1.625V13a1.625 1.625 0 103.25 0V6.5c0-.897-.727-1.625-1.625-1.625z" clip-rule="evenodd"/></svg>');
                    
                    $('#wpfnl-toaster-message').html('Erorr occurred')
                    $('#wpfnl-toaster-wrapper').show();
                    setTimeout(function() {
                        $('#wpfnl-toaster-wrapper').removeClass('quick-toastify-warn-notification');
                        $('#wpfnl-toaster-wrapper').hide(); 
                    }, 2000);
                });

        };


        GeneralSettingsHandler.prototype.clearTemplates = function (e) {
            e.preventDefault();

            var $btn = $(this);
            var sync_icon = $btn.find('.icon-sync');
            var check_icon = $btn.find('.check-icon');
            var $textSpan = $btn.find('.sync-btn-text');
            var thisAlert = $btn.siblings('.wpfnl-alert');

            sync_icon.addClass('sync-icon');

            var payload = {};
            wpAjaxHelperRequest("clear-templates", payload)
                .success(function (response) {
                    sync_icon.hide();
                    check_icon.fadeIn();

                    // Change text and add success style
                    $textSpan.addClass('success');
                    $textSpan.text('Templates synced successfully');
                    $btn.css({
                        'border-color': '#239654',
                    });

                    setTimeout(function () {
                        sync_icon.removeClass('sync-icon').show();
                        check_icon.hide();

                        // Restore original text and style
                        $textSpan.text('Sync Templates');
                        $textSpan.removeClass('success');
                        $btn.css({
                            'border-color': '',
                        });
                    }, 2000);

                    $("#wpfnl-update-global-settings").trigger("click");
                })

                .error(function (response) {
                    setTimeout(function () {
                        thisAlert.fadeOut().text('').removeClass('wpfnl-error');
                    }, 2000);
                    console.log('error');
                });
        };



        /**
         * clear all transients related to wpfunnels
         *
         * @param e
         */
        GeneralSettingsHandler.prototype.clearTransients = function (e) {
            e.preventDefault();
            if (confirm('Are you sure you want to run this tool?')) {

                var sync_icon = $(this).find('.sync-icon');
                var check_icon = $(this).find('.check-icon');
                var thisAlert = $(this).siblings('.wpfnl-alert');

                sync_icon.show();

                var payload = {};
                wpAjaxHelperRequest("clear-transient", payload)
                    .success(function (response) {
                        sync_icon.hide();
                        check_icon.fadeIn();
                        thisAlert.text('Successful').addClass('wpfnl-success').fadeIn();

                        setTimeout(function () {
                            check_icon.hide();
                            thisAlert.fadeOut().text('').removeClass('wpfnl-success');
                        }, 4000);
                        $("#wpfnl-update-global-settings").trigger("click");
                    })

                    .error(function (response) {
                        thisAlert.text('Erorr occurred').addClass('wpfnl-error').fadeIn();

                        setTimeout(function () {
                            thisAlert.fadeOut().text('').removeClass('wpfnl-error');
                        }, 2000);
                        console.log('error');
                    });
            }
        };


        GeneralSettingsHandler.prototype.clearTransients = function (e) {
            e.preventDefault();
            if (confirm('Are you sure you want to run this tool?')) {

                var sync_icon = $(this).find('.sync-icon');
                var check_icon = $(this).find('.check-icon');
                var thisAlert = $(this).siblings('.wpfnl-alert');

                sync_icon.show();

                var payload = {};
                wpAjaxHelperRequest("clear-transient", payload)
                    .success(function (response) {
                        sync_icon.hide();
                        check_icon.fadeIn();
                        thisAlert.text('Successful').addClass('wpfnl-success').fadeIn();

                        setTimeout(function () {
                            check_icon.hide();
                            thisAlert.fadeOut().text('').removeClass('wpfnl-success');
                        }, 4000);
                        $("#wpfnl-update-global-settings").trigger("click");
                    })

                    .error(function (response) {
                        thisAlert.text('Erorr occurred').addClass('wpfnl-error').fadeIn();

                        setTimeout(function () {
                            thisAlert.fadeOut().text('').removeClass('wpfnl-error');
                        }, 2000);

                        console.log('error');
                    });
            }
        };

        new GeneralSettingsHandler();

        $('.inner-tab').on('click', function () {
            $('.inner-tab').removeClass('active');
            $('.wpfnl-tab-content').removeClass('active');

            $(this).addClass('active');
            var tabId = $(this).data('tab');
            $('#' + tabId).addClass('active');
        });

        // Send test notification
        $('#wpfnl-send-test-notification').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $loader = $button.find('.wpfnl-loader');
            var email = $('#wpfnl-test-notification-email').val();
            
            if (!email || !isValidEmail(email)) {
                showToaster('error', 'Please enter a valid email address');
                return;
            }
            
            $button.prop('disabled', true);
            $loader.fadeIn();
            
            var payload = {
                'email': email
            };
            
            wpAjaxHelperRequest('wpfnl-send-test-notification', payload)
                .success(function(response) {
                    $loader.fadeOut();
                    $button.prop('disabled', false);
                    
                    if (response.success) {
                        showToaster('success', response.message);
                    } else {
                        showToaster('error', response.message);
                    }
                })
                .error(function(response) {
                    $loader.fadeOut();
                    $button.prop('disabled', false);
                    showToaster('error', 'Error occurred while sending test email');
                });
        });
        
        function isValidEmail(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
        
        function showToaster(type, message) {
            var iconHtml = type === 'success' 
                ? '<svg width="26" height="26" fill="none" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><path fill="#4BAE4F" fill-rule="evenodd" d="M13 0C5.83 0 0 5.83 0 13s5.83 13 13 13 13-5.83 13-13S20.17 0 13 0z" clip-rule="evenodd"/><path fill="#fff" fill-rule="evenodd" d="M19.287 8.618a.815.815 0 010 1.148l-7.617 7.617a.812.812 0 01-1.148 0l-3.808-3.809a.815.815 0 010-1.147.815.815 0 011.147 0l3.235 3.234 7.044-7.043a.806.806 0 011.147 0z" clip-rule="evenodd"/></svg>'
                : '<svg width="26" height="26" fill="none" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><path fill="#EC5956" fill-rule="evenodd" d="M26 13c0 7.18-5.82 13-13 13S0 20.18 0 13 5.82 0 13 0s13 5.82 13 13zm-11.375 6.5a1.625 1.625 0 11-3.25 0 1.625 1.625 0 013.25 0zM13 4.875c-.898 0-1.625.728-1.625 1.625V13a1.625 1.625 0 103.25 0V6.5c0-.897-.727-1.625-1.625-1.625z" clip-rule="evenodd"/></svg>';
            
            var notificationClass = type === 'success' ? 'quick-toastify-successful-notification' : 'quick-toastify-warn-notification';
            
            $('#wpfnl-toaster-wrapper').addClass(notificationClass);
            $('#wpfnl-toaster-icon').html(iconHtml);
            $('#wpfnl-toaster-message').html(message);
            $('#wpfnl-toaster-wrapper').show();
            
            setTimeout(function() {
                $('#wpfnl-toaster-wrapper').removeClass(notificationClass);
                $('#wpfnl-toaster-wrapper').hide();
            }, 3000);
        }

    });

});
