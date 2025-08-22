<?php
/**
 * View Events & Other Integrations
 * 
 * @package
 */
?>
<div class="settings-tab-container">
    <ul class="wpfnl-general-settings-tab-nav">
        <li class="inner-tab active" data-tab="tab-facebook-pixel"><?php esc_html_e('Facebook Pixel', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-gtm"><?php esc_html_e('Google Tag Manager', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-google-maps"><?php esc_html_e('Google Maps API', 'wpfnl'); ?></li>
    </ul>
</div>

<!-- Facebook Pixel Tab -->
<div class="wpfnl-tab-content active" id="tab-facebook-pixel">
    <?php do_action('wpfunnels_before_facebook_pixel_settings'); ?>
    <?php require WPFNL_DIR . '/admin/modules/settings/views/facebook-pixel-settings.php'; ?>
    <?php do_action('wpfunnels_after_facebook_pixel_settings'); ?>
</div>

<!-- Google Tag Manager Tab -->
<div class="wpfnl-tab-content" id="tab-gtm">
    <?php do_action('wpfunnels_before_gtm_settings'); ?>
    <?php require WPFNL_DIR . '/admin/modules/settings/views/gtm-settings.php'; ?>
    <?php do_action('wpfunnels_after_gtm_settings'); ?>
</div>

<!-- Google Maps API Tab -->
<div class="wpfnl-tab-content" id="tab-google-maps">
    <div class="wpfnl-box google-place-api-settings">
        <div class="wpfnl-field-wrapper google-place-api">
            <label>
                <?php esc_html_e('Google Map API Key', 'wpfnl'); ?>
                <span class="wpfnl-tooltip">
                    <?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                    <p>
                        <?php esc_html_e('Connect with Google Autocomplete to allow customers to go through checkout faster. When a customer types in the Street address, Google will suggest a full address that the customer can add with one click.', 'wpfnl'); ?>
                    </p>
                </span>
            </label>
            <div class="wpfnl-fields">
                <input type="password" name="wpfnl-google-map-api" id="wpfnl-google-map-api-key" value="<?php echo $this->google_map_api_key; ?>" />
                <div class="password-icon">
                    <span class="eye-regular toggle-eye-icon">
                        <?php require WPFNL_DIR . '/admin/partials/icons/eye-regular.php'; ?>
                    </span>
                    <span class="eye-slash-regular toggle-eye-icon hide-eye-icon">
                        <?php require WPFNL_DIR . '/admin/partials/icons/eye-slash-regular.php'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
