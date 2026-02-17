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
            <?php if (!$is_pro_activated) { ?>
                <div class="upgrade-to-pro-hoverlay">
                    <a
                        class="btn-default"
                        target="_blank"
                        href="<?php echo $pro_url;?>"
                    >
                        <?php
                            require WPFNL_DIR . '/admin/partials/icons/pro-icon.php';
                        ?>
                        Upgrade To Pro Now
                    </a>
                </div>
            <?php } ?>

            <div class="wpfnl-role-list-wrapper">
                <?php if ($is_pro_activated && $this->user_roles_settings) { ?>
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
                <?php } else { ?>
                    <?php
                    // Dummy data for display when pro is not active
                    $dummy_roles = array('editor', 'author', 'contributor', 'shop_manager', 'group_leader');

                    foreach ($dummy_roles as $role) { ?>
                        <div class="wpfnl-role-row dummy-data">
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
                                        name=""
                                        value=""
                                        id="user-role-<?php echo $role; ?>"
                                        disabled
                                        />
                                    <label for="user-role-<?php echo $role; ?>" style="cursor: not-allowed"></label>
                                </span>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- Analytics Tab -->
<div class="wpfnl-tab-content" id="tab-analytics">
    <?php if ($is_pro_activated) { ?>
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
    <?php }else { ?>
        <div class="wpfnl-analytics-settings-container">
            <div class="upgrade-to-pro-hoverlay">
                <a
                    class="btn-default"
                    target="_blank"
                    href="<?php echo $pro_url;?>"
                >
                    <?php
                        require WPFNL_DIR . '/admin/partials/icons/pro-icon.php';
                    ?>
                    Upgrade To Pro Now
                </a>
            </div>

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
                        <?php
                        // Dummy data for display when pro is not active
                        $dummy_analytics_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager');
                        foreach ($dummy_analytics_roles as $role) { ?>
                            <span class="wpfnl-checkbox">
                                <input type="checkbox"
                                    name=""
                                    id="<?php echo $role; ?>-analytics-role"
                                    disabled
                                    />
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
