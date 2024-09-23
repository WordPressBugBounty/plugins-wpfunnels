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
    });

})(jQuery);