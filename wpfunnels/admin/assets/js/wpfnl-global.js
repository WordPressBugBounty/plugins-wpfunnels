(function($) {
    'use strict';
    
    // ---delete promotional-banner notice permanently ------
    $(document).on("click", ".gwpf-promotional-notice .close-promotional-banner", function(event) {
		event.preventDefault();
        $('.gwpf-promotional-notice').attr('style', 'display: none !important');
		wpAjaxHelperRequest("delete_promotional_banner")
	});

    $(document).on('click', '.wpfnl-notification-counter__btn-area', function (event) {
        event.preventDefault()
        $('.gwpf-promotional-notice').attr('style', 'display: none !important');
        wpAjaxHelperRequest('delete_promotional_banner')
    })

    // ---delete new UI notice permanently ------
    $(document).on("click", ".wpfunnels-newui-notice .close-newui-notice", function(event) {
		event.preventDefault();
        $('.wpfunnels-newui-notice.notice').css('display','none');
		wpAjaxHelperRequest("delete_new_ui_notice")
	});

})(jQuery);


