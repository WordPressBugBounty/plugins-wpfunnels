<?php
/**
 * View rechaptcha settings
 * 
 * @package
 */
?>
<div class="wpfnl-box">
    <hr>
    <div class="wpfnl-field-wrapper analytics-stats">
        <label><?php esc_html_e('Connect reCAPTCHA (v3)', 'wpfnl'); ?></label>
        <div class="wpfnl-fields">
            <span class="wpfnl-checkbox no-title">
                <input type="checkbox" name="wpfnl-recapcha-enable" id="recapcha-pixel-enable" <?php if ($this->recaptcha_settings['enable_recaptcha'] == 'on') {
                                                                                                    echo 'checked';
                                                                                                } ?> />
                <label for="recapcha-pixel-enable"></label>
            </span>
        </div>
    </div>
    <div id="wpfnl-recapcha">
        <div class="wpfnl-field-wrapper recaptcha-tracking" id="recaptcha-tracking">
            <label>
                <?php esc_html_e('reCAPTCHA Site Key', 'wpfnl'); ?>
                <span class="wpfnl-tooltip">
                    <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                    <p><?php esc_html_e('Collect the Site Key from your Google reCAPTCHA site settings under the reCAPTCHA keys.', 'wpfnl'); ?></p>
                </span>
            </label>
            <div class="wpfnl-fields">
                <input type="text" name="wpfnl-recapcha-site-key" id="wpfnl-recapcha-site-key" value="<?php echo isset($this->recaptcha_settings['recaptcha_site_key']) ? $this->recaptcha_settings['recaptcha_site_key'] : ''; ?>" />
            </div>
        </div>
        <div class="wpfnl-field-wrapper analytics-stats">
            <label>
                <?php esc_html_e('reCAPTCHA Site Secret', 'wpfnl'); ?>
                <span class="wpfnl-tooltip">
                    <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                    <p><?php esc_html_e('Collect the Secrect Key from your Google reCAPTCHA site settings under the reCAPTCHA keys.', 'wpfnl'); ?></p>
                </span>
            </label>
            <div class="wpfnl-fields">
                <input type="text" name="wpfnl-recapcha-site-secret" id="wpfnl-recapcha-site-secret" value="<?php echo isset($this->recaptcha_settings['recaptcha_site_secret']) ? $this->recaptcha_settings['recaptcha_site_secret'] : ''; ?>" />
            </div>
        </div>
    </div>
</div>
<!-- /settings-box -->
