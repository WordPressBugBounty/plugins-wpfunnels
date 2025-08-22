<?php
/**
 * View opt-in settings
 * 
 * @package
 */
?>
<div class="settings-tab-container">
    <ul class="wpfnl-general-settings-tab-nav">
        <li class="inner-tab active" data-tab="tab-optin-email"><?php esc_html_e('Opt-in Email', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-recaptcha"><?php esc_html_e('reCAPTCHA', 'wpfnl'); ?></li>
    </ul>
</div>

<!-- Tab: Opt-in Email -->
<div class="wpfnl-tab-content active" id="tab-optin-email">
    <div class="wpfnl-box opt-in-email">

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
                    <?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                    <p><?php esc_html_e('Enter the email address where you would like to receive leads from opt-in.', 'wpfnl'); ?></p>
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
                    <?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                    <p><?php esc_html_e('Enter the email subject for the opt-in email.', 'wpfnl'); ?></p>
                </span>
            </label>
            <div class="wpfnl-fields">
                <input type="text" name="wpfnl-optin-email-subject" id="wpfunnels-optin-email-subject" value="<?php echo sanitize_text_field($this->optin_settings['email_subject']); ?>" />
            </div>
        </div>
    </div>
</div>

<!-- Tab: reCAPTCHA -->
<div class="wpfnl-tab-content" id="tab-recaptcha">
    <?php require WPFNL_DIR . '/admin/modules/settings/views/rechaptcha-settings.php'; ?>
</div>
