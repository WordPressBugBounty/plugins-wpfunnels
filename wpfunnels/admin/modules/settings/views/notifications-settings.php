<?php
/**
 * View notification settings
 * 
 * @package
 */
?>
<div class="settings-tab-container">
    <ul class="wpfnl-general-settings-tab-nav">
        <li class="inner-tab active" data-tab="tab-revenue-report"><?php esc_html_e('Store Revenue Report', 'wpfnl'); ?></li>
    </ul>
</div>

<!-- Tab: Store Revenue Report -->
<div class="wpfnl-tab-content active" id="tab-revenue-report">
    <div class="wpfnl-box revenue-report-notification">

        <div class="wpfnl-box">
            <div class="wpfnl-field-wrapper">
                <label>
                    <?php esc_html_e('Enable Store Revenue Report Emails', 'wpfnl'); ?>
                    <span class="wpfnl-tooltip">
                        <?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                        <p><?php esc_html_e('Enable or disable automated store revenue report emails.', 'wpfnl'); ?></p>
                    </span>
                </label>

                <div class="wpfnl-fields">
                    <span class="wpfnl-switcher extra-sm no-title">
                        <input 
                            type="checkbox" 
                            name="wpfnl-enable-revenue-report" 
                            id="wpfnl-enable-revenue-report" 
                            <?php checked('yes', isset($this->notification_settings['enable_revenue_report']) ? $this->notification_settings['enable_revenue_report'] : 'no'); ?> 
                        />
                        <label for="wpfnl-enable-revenue-report"></label>
                    </span>
                </div>
            </div>
        </div>

        <div class="wpfnl-revenue-report-settings-outer" id="wpfnl-revenue-report-settings">

        <div class="wpfnl-box">
        <div class="wpfnl-revenue-report-settings">

        <div class="wpfnl-field-wrapper report-frequency">
            <label>
                <?php esc_html_e('Report Frequency', 'wpfnl'); ?>
                <span class="wpfnl-tooltip">
                    <?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                    <p><?php esc_html_e('Select how often you want to receive revenue reports.', 'wpfnl'); ?></p>
                </span>
            </label>

            <div class="wpfnl-fields">
                <div class="wpfnl-radio-wrapper">
                    <label class="wpfnl-radio-field">
                        <input 
                            type="radio" 
                            name="wpfnl-revenue-report-frequency" 
                            value="weekly" 
                            <?php checked('weekly', isset($this->notification_settings['revenue_report_frequency']) ? $this->notification_settings['revenue_report_frequency'] : 'weekly'); ?>
                        />
                        <span><?php esc_html_e('Weekly', 'wpfnl'); ?></span>
                    </label>

                    <label class="wpfnl-radio-field">
                        <input 
                            type="radio" 
                            name="wpfnl-revenue-report-frequency" 
                            value="monthly" 
                            <?php checked('monthly', isset($this->notification_settings['revenue_report_frequency']) ? $this->notification_settings['revenue_report_frequency'] : 'weekly'); ?>
                        />
                        <span><?php esc_html_e('Monthly', 'wpfnl'); ?></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="wpfnl-field-wrapper">
            <label>
                <?php esc_html_e('Recipient Email Address', 'wpfnl'); ?>
                <span class="wpfnl-tooltip">
                    <?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                    <p><?php esc_html_e('Enter the email address where you want to receive store revenue reports. You can add multiple emails separated by commas.', 'wpfnl'); ?></p>
                </span>
            </label>
            <div class="wpfnl-fields">
                <input 
                    type="text" 
                    name="wpfnl-revenue-report-recipient" 
                    id="wpfnl-revenue-report-recipient" 
                    value="<?php echo isset($this->notification_settings['revenue_report_recipient']) ? sanitize_text_field($this->notification_settings['revenue_report_recipient']) : get_option('admin_email'); ?>" 
                    placeholder="<?php esc_attr_e('admin@example.com, manager@example.com', 'wpfnl'); ?>"
                />
            </div>
        </div>

        <div class="wpfnl-field-wrapper">
            <label>
                <?php esc_html_e('Report Subject', 'wpfnl'); ?>
                <span class="wpfnl-tooltip">
                    <?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                    <p><?php esc_html_e('Customize the subject line for your revenue report emails.', 'wpfnl'); ?></p>
                </span>
            </label>
            <div class="wpfnl-fields">
                <input 
                    type="text" 
                    name="wpfnl-revenue-report-subject" 
                    id="wpfnl-revenue-report-subject" 
                    value="<?php echo isset($this->notification_settings['revenue_report_subject']) ? sanitize_text_field($this->notification_settings['revenue_report_subject']) : 'Store Revenue Report - {period}'; ?>" 
                    placeholder="<?php esc_attr_e('Store Revenue Report - {period}', 'wpfnl'); ?>"
                />
                <p class="hints"><?php esc_html_e('Use {period} to show the date range (e.g., January 6, 2026 - January 12, 2026)', 'wpfnl'); ?></p>
            </div>
        </div>

        <div class="wpfnl-field-wrapper">
            <label>
                <?php esc_html_e('Send Time', 'wpfnl'); ?>
                <span class="wpfnl-tooltip">
                    <?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                    <p><?php esc_html_e('Select the time when you want to receive the revenue report emails.', 'wpfnl'); ?></p>
                </span>
            </label>
            <div class="wpfnl-fields">
                <?php
                $send_time = isset($this->notification_settings['send_time']) ? $this->notification_settings['send_time'] : '10:00';
                ?>
                <input 
                    type="time" 
                    name="wpfnl-send-time" 
                    id="wpfnl-send-time" 
                    value="<?php echo esc_attr($send_time); ?>"
                    class="wpfnl-time-input"
                />
            </div>
        </div>

        </div>
        <!-- /wpfnl-revenue-report-settings -->
        </div>
        <!-- /wpfnl-box -->

        <!-- Test Notification Section -->
        <div class="wpfnl-box test-notification-wrapper">
        <div class="wpfnl-field-wrapper">
            <div class="test-notification-content">
                <div class="test-notification-icon">
                    <svg width="48" height="48" viewBox="0 0 49 48" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="icon-email-deliver"><path id="Shape" d="M44.5 16.4211C44.5 22.1764 39.8097 26.8421 34.0238 26.8421C28.238 26.8421 23.5476 22.1764 23.5476 16.4211C23.5476 10.6657 28.238 6 34.0238 6C39.8097 6 44.5 10.6657 44.5 16.4211ZM33.0714 13.024V22.1053C33.0714 22.6285 33.4978 23.0526 34.0238 23.0526C34.5498 23.0526 34.9762 22.6285 34.9762 22.1053V13.024L38.1123 16.1436C38.4842 16.5135 39.0872 16.5135 39.4591 16.1436C39.8311 15.7736 39.8311 15.1738 39.4591 14.8038L34.6972 10.067C34.3253 9.69698 33.7223 9.69698 33.3504 10.067L28.5885 14.8038C28.2165 15.1738 28.2165 15.7736 28.5885 16.1436C28.9604 16.5135 29.5634 16.5135 29.9353 16.1436L33.0714 13.024ZM39.7381 35.8421V27.3495C40.7877 26.8054 41.749 26.116 42.5952 25.3082V35.8421C42.5952 39.1257 40.0114 41.809 36.756 41.9903L36.4048 42H10.6905C7.38947 42 4.6919 39.4298 4.50979 36.1915L4.5 35.8421V17.8421C4.5 14.5585 7.08371 11.8751 10.3392 11.6939L10.6905 11.6842H22.5917C22.2151 12.5826 21.9424 13.535 21.7885 14.5263H10.6905C8.94158 14.5263 7.50726 15.8661 7.36819 17.5702L7.35714 17.8421V18.6549L23.5476 27.1311L26.0337 25.8292C26.869 26.5319 27.7991 27.1264 28.8022 27.5912L24.213 29.9944C23.856 30.1812 23.4392 30.2079 23.0653 30.0745L22.8823 29.9944L7.35714 21.8665V35.8421C7.35714 37.5819 8.70402 39.0086 10.4171 39.1469L10.6905 39.1579H36.4048C38.1537 39.1579 39.588 37.8181 39.727 36.114L39.7381 35.8421Z" fill="#353030"></path></g></svg>
                </div>
                <div class="test-notification-text">
                    <h4><?php esc_html_e('Test Notification', 'wpfnl'); ?></h4>
                    <p><?php esc_html_e('Test the notifications to ensure that emails are arriving in your inbox.', 'wpfnl'); ?></p>
                </div>
            </div>
            <div class="test-notification-action">
                <input 
                    type="email" 
                    name="wpfnl-test-notification-email" 
                    id="wpfnl-test-notification-email" 
                    placeholder="<?php esc_attr_e('dev@test.com', 'wpfnl'); ?>"
                    value="<?php echo esc_attr(get_option('admin_email')); ?>"
                    class="wpfnl-test-email-input"
                />
                <button type="button" class="btn-default" id="wpfnl-send-test-notification">
                    <?php esc_html_e('Send', 'wpfnl'); ?>
                    <span class="wpfnl-loader"></span>
                </button>
            </div>
        </div>
    </div>
    <!-- /test-notification-wrapper -->

        </div>
        <!-- /wpfnl-revenue-report-settings-outer -->

    </div>
</div>
