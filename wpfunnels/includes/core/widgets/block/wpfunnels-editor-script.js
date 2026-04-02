(function ($) {

    jQuery(document).ready(function () {
        //--------start floating label script-------
        $('.floating-label #customer_details .form-row .input-text, .floating-label form.woocommerce-form-login .form-row-first .input-text, .floating-label form.woocommerce-form-login .form-row-last .input-text').each(function(){
            $(this).attr('placeholder', '');
            console.log('default load');

            if( $(this).val().length > 0 ) {
                $(this).parents('.form-row').find('label').addClass('floated');
            }
        });

        $(document).on('focus','.floating-label #customer_details .form-row .input-text, .floating-label #customer_details .form-row select, .floating-label form.woocommerce-form-login .form-row-first .input-text, .floating-label form.woocommerce-form-login .form-row-last .input-text', function(){
            $(this).parents('.form-row').find('label').addClass('floated');
        });

        $(document).on('blur','.floating-label #customer_details .form-row .input-text, .floating-label #customer_details .form-row select, .floating-label form.woocommerce-form-login .form-row-first .input-text, .floating-label form.woocommerce-form-login .form-row-last .input-text', function(){
            if( $(this).val().length == 0 ) {
                $(this).parents('.form-row').find('label').removeClass('floated');
            }
        });

        //--------end floating label script-------

        // Initialize Enhanced Phone Field in editor
        function initEditorPhoneField() {
            if (typeof window.intlTelInput !== 'function' || !window.wpfnl_obj) {
                return;
            }
            
            // Wait for phone fields that haven't been initialized yet
            $('.wpfnl-phone, #billing_phone, input[type="tel"]').not('.wpfnl-iti-initialized').each(function() {
                var input = this;
                $(input).addClass('wpfnl-iti-initialized');

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

                if (window.wpfnl_obj.phone_help_text && !$(input).next('.wpfnl-phone-help-text').length) {
                    $('<p class="wpfnl-phone-help-text" style="font-size: 12px; margin-top: 5px; color: #666;">' + window.wpfnl_obj.phone_help_text + '</p>').insertAfter($(input).closest('.iti'));
                }
            });
        }
        
        // Polling to handle Gutenberg's dynamic rendering of components
        setInterval(initEditorPhoneField, 1000);
        
    });

})(jQuery);