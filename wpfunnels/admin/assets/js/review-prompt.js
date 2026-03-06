(function ($, window) {
    'use strict';

    var data = window.wpfnlReviewPromptData || {};
    var minChars = parseInt(data.minChars, 10);

    if (isNaN(minChars) || minChars <= 0) {
        minChars = 50;
    }

    var errorMessage = data.errorMessage || ('Please share at least ' + minChars + ' characters so we can understand what needs improvement and make things better for you and other users.');
    var ajaxUrl = data.ajaxUrl || (typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '');

    $(function () {
        var selectedFeedbackType = '';

        setTimeout(function () {
            $('#wpfnl-review-prompt').fadeIn();
        }, 2000);

        function sendAction(actionType, feedback, feedbackType) {
            if (!ajaxUrl) {
                return;
            }

            $.post(ajaxUrl, {
                action: 'wpfnl_review_action',
                wpfnl_action_type: actionType,
                feedback: feedback || '',
                feedback_type: feedbackType || '',
                nonce: data.nonce || ''
            });
        }

        $('#wpfnl-review-close').on('click', function () {
            $('#wpfnl-review-prompt').fadeOut();
            sendAction('snooze');
        });

        $('#wpfnl-review-yes').on('click', function () {
            $('#wpfnl-review-prompt').fadeOut();
            sendAction('completed');
        });

        $('#wpfnl-review-okay, #wpfnl-review-no').on('click', function () {
            $(this).addClass('selected').siblings().removeClass('selected');
            $('#wpfnl-review-text').text('Sorry to hear that! What could we do better?');
            $('#wpfnl-feedback-form').fadeIn();
            selectedFeedbackType = $(this).attr('data-feedback-type') || '';
        });

        $('#wpfnl-feedback-cancel').on('click', function () {
            $('#wpfnl-feedback-form').hide();
            $('#wpfnl-review-options').fadeIn();
            $('#wpfnl-review-text').text('Is WPFunnels helping you grow your WooCommerce store?');
            $('#wpfnl-feedback-text').val('');
            $('#wpfnl-char-count').text('0');
            $('#wpfnl-char-counter').css('color', '#58151c');
            $('#wpfnl-feedback-error').remove();
            $('.wpfnl-review-btn').removeClass('selected');
            selectedFeedbackType = '';
        });

        $('#wpfnl-feedback-text').on('input', function () {
            var len = $(this).val().trim().length;
            $('#wpfnl-char-count').text(len);
            $('#wpfnl-feedback-error').hide();

            if (len >= minChars) {
                $('#wpfnl-char-counter').css('color', '#00a32a');
            } else {
                $('#wpfnl-char-counter').css('color', '#d14957');
            }
        });

        $('#wpfnl-feedback-submit').on('click', function () {
            var feedback = $('#wpfnl-feedback-text').val();

            if (feedback.trim().length < minChars) {
                if ($('#wpfnl-feedback-error').length === 0) {
                    $('#wpfnl-char-counter').after('<div id="wpfnl-feedback-error" style="background-color: #fcf0f1; border-left: 3px solid #d63638; padding: 10px 12px; font-size: 12px; color: #d63638; line-height: 1.4; margin-bottom: 12px; border-radius: 0 3px 3px 0; margin-top: 3px;">' + errorMessage + '</div>');
                } else {
                    $('#wpfnl-feedback-error').text(errorMessage).show();
                }

                return;
            }

            $(this).text('Submitting...').prop('disabled', true);
            sendAction('feedback', feedback, selectedFeedbackType);

            setTimeout(function () {
                $('#wpfnl-review-prompt').fadeOut();
            }, 500);
        });
    });
})(jQuery, window);
