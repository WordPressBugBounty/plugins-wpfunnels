<?php
/**
 * View opt-in settings
 * 
 * @package
 */
?>
<div class="wpfnl-box">
    <hr/>
    <div class="wpfnl-field-wrapper" style="display: none;">
        <label>
            <?php esc_html_e('Admin Name', 'wpfnl'); ?>
        </label>
        <div class="wpfnl-fields">
            <input type="text" name="wpfnl-optin-sender-name" id="wpfunnels-optin-sender-name" value="<?php echo sanitize_text_field($this->optin_settings['sender_name']); ?>" />
        </div>
    </div>

    <div class="wpfnl-field-wrapper">
        <label>
            <?php esc_html_e('Admin Email', 'wpfnl'); ?>
            <span class="wpfnl-tooltip">
                <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                <p><?php esc_html_e('Enter the email address where you would like to receive leads from opt-in', 'wpfnl'); ?></p>
            </span>
        </label>
        <div class="wpfnl-fields">
            <input type="text" name="wpfnl-optin-sender-email" id="wpfunnels-optin-sender-email" value="<?php echo sanitize_text_field($this->optin_settings['sender_email']); ?>" />
        </div>
    </div>

    <div class="wpfnl-field-wrapper">
        <label>
            <?php esc_html_e('Email Subject', 'wpfnl'); ?>
            <span class="wpfnl-tooltip">
                <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                <p><?php esc_html_e('Enter the email subject for the opt-in email', 'wpfnl'); ?></p>
            </span>
        </label>
        <div class="wpfnl-fields">
            <input type="text" name="wpfnl-optin-email-subject" id="wpfunnels-optin-email-subject" value="<?php echo sanitize_text_field($this->optin_settings['email_subject']); ?>" />
        </div>
    </div>

</div>
<!-- /settings-box -->
