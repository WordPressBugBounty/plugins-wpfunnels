<?php
/**
 * View Role Management Settings
 *
 * @package WPFunnels
 */
?>
<div class="settings-tab-container">
    <ul class="wpfnl-general-settings-tab-nav">
        <li class="inner-tab active" data-tab="tab-role-manager"><?php esc_html_e('User Role Manager', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-analytics"><?php esc_html_e('Analytics', 'wpfnl'); ?></li>
    </ul>
</div>

<!-- User Role Manager Tab -->
<div class="wpfnl-tab-content active" id="tab-role-manager">
    <div class="wpfnl-user-role-container">
        <div class="wpfnl-box">
            <?php if ($this->user_roles_settings) { ?>
                <div class="wpfnl-role-list-wrapper">
                    <?php foreach ($this->user_roles_settings as $role => $setting) { ?>
                        <div class="wpfnl-role-row">
                            <div class="text-wrapper">
                                <div class="role-header">
                                    <span class="role-title"><?php echo str_replace("_", " ", ucfirst($role)); ?></span>
                                    <span class="wpfnl-tooltip">
										<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                                        <p><?php esc_html_e('Enable or disable WPFunnels access for this role.', 'wpfnl'); ?></p>
                                    </span>
                                </div>
                                <div class="role-description">
                                    <?php esc_html_e('Full Access', 'wpfnl'); ?>
                                </div>
                            </div>
                            <div class="switcher-wrapper">
                                <span class="wpfnl-switcher extra-sm no-title">
                                    <input type="checkbox"
                                           name="user_role[]"
                                           value="<?php echo $this->user_roles_settings[$role]; ?>"
                                           id="user-role-<?php echo $role; ?>"
                                           data-role="<?php echo $role; ?>"
                                        <?php checked('yes', $this->user_roles_settings[$role], true); ?> />
                                    <label for="user-role-<?php echo $role; ?>"></label>
                                </span>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Analytics Tab -->
<div class="wpfnl-tab-content" id="tab-analytics">
    <?php if (apply_filters('wpfunnels/is_wpfnl_pro_active', false)) { ?>
        <div class="wpfnl-analytics-settings-container">
            <div class="wpfnl-box analytics-settings">
                <div class="wpfnl-field-wrapper analytics-stats">
                    <label>
                        <?php esc_html_e('Disable Analytics Tracking For', 'wpfnl'); ?>
                        <span class="wpfnl-tooltip">
                            <?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
                            <p><?php esc_html_e('If you want WPFunnels not to track traffic, conversion, & revenue count for Analytics from certain user roles in your site, then you may do so using these options.', 'wpfnl'); ?></p>
                        </span>
                    </label>
                    <div class="wpfnl-fields disable-tracking-wrapper">
                        <?php foreach ($this->user_roles as $role) { ?>
                            <span class="wpfnl-checkbox">
                                <input type="checkbox"
                                       name="analytics-role[]"
                                       id="<?php echo $role; ?>-analytics-role"
                                       data-role="<?php echo $role; ?>"
                                    <?php if (isset($this->general_settings['disable_analytics'][$role])) {
                                        checked($this->general_settings['disable_analytics'][$role], 'true');
                                    } ?> />
                                <label for="<?php echo $role; ?>-analytics-role">
                                    <?php echo str_replace("_", " ", ucfirst($role)); ?>
                                </label>
                            </span>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
