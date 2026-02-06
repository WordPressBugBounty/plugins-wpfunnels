<?php

/**
 * View settings
 *
 * @package
 */

$pro_url = add_query_arg('wpfnl-dashboard', '1', GETWPFUNNEL_PRICING_URL);
$is_pro_active = apply_filters('wpfunnels/is_pro_license_activated', false);
?>
<div class="wpfnl">

    <div class="wpfnl-dashboard">
        <nav class="wpfnl-dashboard__nav">
            <?php require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>
        </nav>

        <div class="dashboard-nav__content">
            <div id="templates-library"></div>
            <?php do_action('wpfunnels_before_settings'); ?>
            <div class="wpfnl-funnel-settings__inner-content">
                <h2 class="settings-heading"><?php esc_html_e('Settings', 'wpfnl'); ?></h2>

                <div class="wpfnl-funnel-settings__wrapper">
                    <nav class="wpfn-settings__nav">
                        <ul>
                            <li class="nav-li active" data-id="general-settings">
                                <?php include WPFNL_DIR . '/admin/partials/icons/settings-icon-2x.php'; ?>
                                <span><?php esc_html_e('General', 'wpfnl'); ?></span>
                            </li>

                            <li class="nav-li" data-id="permalink-settings">
                            <svg width="18" height="18" fill="none" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg"><g fill="#7A8B9A" stroke="#FAF9FF" stroke-width=".3" clip-path="url(#clip0_1920_6521)"><path d="M3.956 6.544A.975.975 0 015.402 7.85l-.067.074L3.18 10.08a3.534 3.534 0 00-1.06 2.1v.002a3.333 3.333 0 001.131 2.883l.003.003a3.444 3.444 0 004.66-.272l2.146-2.147a.976.976 0 011.38 1.379l-2.256 2.256A5.294 5.294 0 01.172 12.06a5.571 5.571 0 011.67-3.401l.003-.002 2.111-2.113zM8.69 1.81a5.42 5.42 0 017.09-.592v.001a5.298 5.298 0 01.525 7.941l-2.257 2.258a.976.976 0 01-1.38-1.38l2.258-2.256a3.345 3.345 0 00-.248-4.947l-.004-.003-.131-.097a3.438 3.438 0 00-4.442.424L7.943 5.314a.975.975 0 01-1.379-1.379l2.124-2.124.001-.002z"/><path d="M10.06 6.544a.975.975 0 011.38 1.38l-3.497 3.495a.975.975 0 11-1.379-1.379l3.496-3.496z"/></g><defs><clipPath id="clip0_1920_6521"><path fill="#fff" d="M0 0h18v18H0z"/></clipPath></defs></svg>

                                <span><?php esc_html_e('Permalink', 'wpfnl'); ?></span>
                            </li>

                            <li class="nav-li" data-id="optin-settings">
                                <svg width="20" height="16" fill="none" viewBox="0 0 20 16" xmlns="http://www.w3.org/2000/svg"><path fill="#7A8B9A" fill-rule="evenodd" stroke="#7A8B9A" stroke-width=".2" d="M17.852 11.007c0 1.59-1.334 2.881-2.973 2.881H5.12c-1.639 0-2.973-1.291-2.973-2.88V4.992c0-.53.15-1.03.41-1.457l4.76 4.612A3.825 3.825 0 0010 9.221a3.823 3.823 0 002.68-1.074l4.761-4.612c.26.428.41.926.41 1.457v6.014h0zM14.88 2.112H5.12c-.676 0-1.301.222-1.801.59l4.809 4.661A2.68 2.68 0 0010 8.11c.708 0 1.373-.266 1.87-.747l4.809-4.66a3.026 3.026 0 00-1.801-.591zm0-1.112H5.12C2.85 1 1 2.792 1 4.993v6.014C1 13.21 2.85 15 5.122 15h9.757C17.15 15 19 13.21 19 11.007V4.993C19 2.792 17.15 1 14.879 1z" clip-rule="evenodd"/></svg>
                                <span><?php esc_html_e('Opt-in Settings', 'wpfnl'); ?></span>
                            </li>

                            <?php if (\WPFunnels\Wpfnl_functions::is_lms_addon_active()) { ?>
                            <li class="nav-li" data-id="lms-settings">
                                <svg width="20" height="20" fill="none" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill="#7A8B9A" d="M10 2L2 7v6c0 4.97 3.5 9 8 10 4.5-1 8-5.03 8-10V7l-8-5zm0 2.18l5.5 3.3V13c0 3.5-2.45 6.58-5.5 7.71-3.05-1.13-5.5-4.21-5.5-7.71V7.48l5.5-3.3zm-1.5 5.32v5h2v-5h-2zm-1-3v2h4V6.5h-4z"/></svg>
                                <span><?php esc_html_e('LMS Settings', 'wpfnl'); ?></span>
                            </li>
                            <?php } ?>

                            <?php if (\WPFunnels\Wpfnl_functions::is_wc_active() && 'lead' !== $global_funnel_type) { ?>
                                <li class="nav-li <?php echo !$is_pro_active ? ' disabled' : '' ?>" <?php echo $is_pro_active ? ' data-id="offer-settings" ' : '' ?> <?php echo !$is_pro_active ? ' id="wpfnl-offer-settings" ' : '' ?>>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="8" width="18" height="4" rx="1" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M12 8V21" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M19 12V19C19 20.1046 18.1046 21 17 21H7C5.89543 21 5 20.1046 5 19V12" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M7.5 7.99994C6.11929 7.99994 5 6.88065 5 5.49994C5 4.11923 6.11929 2.99994 7.5 2.99994C9.474 2.96594 11.26 4.94894 12 7.99994C12.74 4.94894 14.526 2.96594 16.5 2.99994C17.8807 2.99994 19 4.11923 19 5.49994C19 6.88065 17.8807 7.99994 16.5 7.99994" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>

                                    <span><?php esc_html_e('Offer Settings', 'wpfnl'); ?></span>

                                    <?php
                                    if (!$is_pro_active) {
                                        echo '<span class="pro-tag-icon">';
                                        require WPFNL_DIR . '/admin/partials/icons/pro-icon.php';
                                        echo '</span>';
                                    }
                                    ?>

                                </li>
                            <?php } ?>

                            <li class="nav-li" data-id="event-tracking-setting">
                                <?php require WPFNL_DIR . '/admin/partials/icons/event-tracking-icon.php'; ?>
                                <span><?php esc_html_e('Events & Other Integrations', 'wpfnl'); ?></span>
                            </li>

                            <li class="nav-li" data-id="notifications-settings">
                                <?php require WPFNL_DIR . '/admin/partials/icons/notification-icon.php'; ?>
                                <span><?php esc_html_e('Notifications', 'wpfnl'); ?></span>
                            </li>

                            <li class="nav-li" data-id="advance-settings">
                                <?php require WPFNL_DIR . '/admin/partials/icons/advanced-settings.php'; ?>
                                <span><?php esc_html_e('Advanced Settings', 'wpfnl'); ?></span>
                            </li>
                            <?php if (current_user_can('manage_options') && $is_pro_activated ) { ?>
                                <li class="nav-li" data-id="user-role-manager">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/role-management-menu-icon.php'; ?>
                                    <span><?php esc_html_e('Role Management', 'wpfnl'); ?></span>
                                </li>
                            <?php } ?>
                            <!-- <//?php if (\WPFunnels\Wpfnl_functions::maybe_logger_enabled()) { ?> -->
                            <?php { ?>
                                <li class="nav-li" data-id="log-settings">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/log-settings.php'; ?>
                                    <span><?php esc_html_e('Logs', 'wpfnl'); ?></span>
                                </li>
                            <?php } ?>
                        </ul>
                    </nav>

                    <div class="wpfnl-funnel__single-settings general" id="general-settings">
                        <h4 class="settings-title"><?php esc_html_e('General', 'wpfnl'); ?></h4>
                        <?php do_action('wpfunnels_before_general_settings'); ?>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/general-settings.php'; ?>
                        <?php do_action('wpfunnels_after_general_settings'); ?>
                    </div>
                    <!-- /General Settings -->

                    <div class="wpfnl-funnel__single-settings offer" id="offer-settings">
                        
                        <?php if ($is_pro_activated) { ?>
                            <?php do_action('wpfunnels_before_offer_settings'); ?>
                            <h4 class="settings-title"><?php esc_html_e('Offer Settings', 'wpfnl'); ?></h4>
                            <?php require WPFNL_DIR . '/admin/modules/settings/views/offer-settings.php'; ?>
                            <?php do_action('wpfunnels_after_offer_settings'); ?>

                        <?php } else { ?>
                            <a href="<?php echo $pro_url; ?>" target="_blank" title="<?php _e('Click to Upgrade Pro', 'wpfnl'); ?>">
                                <span class="pro-tag"><?php esc_html_e('Get Pro', 'wpfnl'); ?></span>
                            </a>
                        <?php } ?>
                    </div>
                    <!-- /Offer Settings -->

                    <div class="wpfnl-funnel__single-settings permalink" id="permalink-settings">
                        <?php do_action('wpfunnels_before_permalink_settings'); ?>
                        <h4 class="settings-title"><?php esc_html_e('Permalink', 'wpfnl'); ?></h4>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/permalink-settings.php'; ?>
                        <?php do_action('wpfunnels_after_permalink_settings'); ?>
                    </div>

                    <div class="wpfnl-funnel__single-settings optin" id="optin-settings">
                        <?php do_action('wpfunnels_before_optin_settings'); ?>
                        <div class="email-settings">
                            <h4 class="settings-title"><?php esc_html_e('Opt-in Settings', 'wpfnl'); ?></h4>
                            <?php require WPFNL_DIR . '/admin/modules/settings/views/optin-settings.php'; ?>
                        </div>
                        <?php do_action('wpfunnels_after_optin_settings'); ?>
                    </div>

                    <!-- LMS Settings -->
                    <?php if (\WPFunnels\Wpfnl_functions::is_lms_addon_active()) { ?>
                        <div class="wpfnl-funnel__single-settings lms-settings" id="lms-settings">
                            <?php do_action('wpfunnels_before_lms_settings'); ?>

                            <h4 class="settings-title"><?php esc_html_e('LMS Settings', 'wpfnl'); ?></h4>

                            <?php require WPFNL_DIR . '/admin/modules/settings/views/lms-settings.php'; ?>

                            <?php do_action('wpfunnels_after_lms_settings'); ?>
                        </div>
                    <?php } ?>
                    <!-- /LMS Settings -->

                    <!-- Event and other integration Settings -->

                    <div class="wpfnl-funnel__single-settings event-tracking" id="event-tracking-setting">
                        <h4 class="settings-title"><?php esc_html_e('Events & Other Integrations', 'wpfnl'); ?></h4>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/other-integration-setting.php'; ?>

                        <!--					<div class="google-place-api-settings">-->
                        <!--						<h4 class="settings-title"> --><?php //esc_html_e('Google Maps API Integration', 'wpfnl'); 
                                                                                    ?><!-- </h4>-->
                        <!--						<div class="wpfnl-box">-->
                        <!--							<div class="wpfnl-field-wrapper google-place-api">-->
                        <!--								<label>--><?php //esc_html_e('Google Map API Key', 'wpfnl'); 
                                                                        ?>
                        <!--									<span class="wpfnl-tooltip">-->
                        <!--										--><?php //require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; 
                                                                        ?>
                        <!--										<p>--><?php //esc_html_e('Connect with Google Autocomplete to allow customers to go through checkout faster. When a customer types in the Street address, Google will suggest a full address that the customer can add with one click.', 'wpfnl'); 
                                                                            ?><!--</p>-->
                        <!--									</span>-->
                        <!--								</label>-->
                        <!--								<div class="wpfnl-fields">-->
                        <!--									<input type="password" name="wpfnl-google-map-api" id="wpfnl-google-map-api-key" value="--><?php //echo $this->google_map_api_key ; 
                                                                                                                                                            ?><!--" />-->
                        <!--									<div class="password-icon">-->
                        <!--										<span class="eye-regular toggle-eye-icon">-->
                        <!--											--><?php //require WPFNL_DIR . '/admin/partials/icons/eye-regular.php'; 
                                                                            ?>
                        <!--										</span>-->
                        <!--										<span class="eye-slash-regular toggle-eye-icon hide-eye-icon">-->
                        <!--											--><?php //require WPFNL_DIR . '/admin/partials/icons/eye-slash-regular.php'; 
                                                                            ?>
                        <!--										</span>-->
                        <!--									</div>-->
                        <!--								</div>-->
                        <!--							</div>-->
                        <!--						</div>-->
                        <!--					</div>-->
                    </div>

                    <!-- Notifications Settings -->
                    <div class="wpfnl-funnel__single-settings notifications" id="notifications-settings">
                        <h4 class="settings-title"><?php esc_html_e('Notifications', 'wpfnl'); ?></h4>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/notifications-settings.php'; ?>
                    </div>

                    <!-- Advanced setting -->
                    <div class="wpfnl-funnel__single-settings advance-settings" id="advance-settings">
                        <?php
                        $rollback_versions = $this->get_roll_back_versions();
                        ?>
                        <h4 class="settings-title"><?php esc_html_e('Advanced Settings', 'wpfnl'); ?></h4>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/advance-settings.php'; ?>
                    </div>
                    
                    <!-- Role management -->
                    <?php if (current_user_can('manage_options')) {
                    ?>
                        <div class="wpfnl-funnel__single-settings user-role-manager" id="user-role-manager">
                            <h4 class="settings-title"><?php esc_html_e('Role Management', 'wpfnl'); ?></h4>
                            <?php require WPFNL_DIR . '/admin/modules/settings/views/user-role-manager.php'; ?>
                        </div>
                    <?php } ?>
                    
                    <!-- Log settings -->
                    <div class="wpfnl-funnel__single-settings log-settings" id="log-settings">
                        <?php
                        $files = \Wpfnl_Logger::get_log_files();
                        ?>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/log-settings.php'; ?>
                    </div>

                </div>
                <!-- /funnel-settings__wrapper -->

                <div class="wpfnl-funnel-settings__footer">
                    <span class="wpfnl-alert box"></span>
                    <button class="btn-default update" id="wpfnl-update-global-settings">
                        <?php esc_html_e('Save', 'wpfnl'); ?>
                        <span class="wpfnl-loader"></span>
                    </button>
                </div>

            </div>
            <!-- /funnel-settings__inner-content -->
            <?php do_action('wpfunnels_after_settings'); ?>
        </div>

        <!-- Toaster Starts-->
        <div id="wpfnl-toaster-wrapper">
            <div class="quick-toastify-alert-toast">
                <div class="quick-toastify-alert-container">
                    <div class="quick-toastify-successfull-icon" id="wpfnl-toaster-icon"></div>
                    <p id="wpfnl-toaster-message"></p>
                    <div class="quick-toastify-cross-icon" id="wpfnl-toaster-close-btn">
                        <svg width="10" height="10" fill="none" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#686f7f" d="M.948 9.995a.94.94 0 01-.673-.23.966.966 0 010-1.352L8.317.278a.94.94 0 011.339.045c.323.35.342.887.044 1.258L1.611 9.765a.94.94 0 01-.663.23z" />
                            <path fill="#686f7f" d="M8.98 9.995a.942.942 0 01-.664-.278L.275 1.582A.966.966 0 01.378.23a.939.939 0 011.232 0L9.7 8.366a.966.966 0 010 1.399.94.94 0 01-.72.23z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <!-- Toaster End -->

        <!-- Pro Modal -->
        <div class="wpfnl-pro-modal-overlay" id="wpfnl-pro-modal">
            <div class="wpfnl-pro-modal-wrapper">
                <div class="wpfnl-pro-modal-close">
                    <span class="wpfnl-pro-modal-close-btn" id="wpfnl-pro-modal-close">
                        <?php require WPFNL_DIR . '/admin/partials/icons/cross-icon.php'; ?>
                    </span>
                </div>
                <div class="wpfnl-pro-modal-content">
                    <div class="wpfnl-pro-modal-header">
                        <span class="wpfnl-pro-modal-header-icon">
                            <?php require WPFNL_DIR . '/admin/partials/icons/unlock-icon.php'; ?>
                        </span>
                        <h3 class="wpfnl-pro-heading">Unlock with Premium</h3>
                        <p class="wpfnl-pro-sub-heading">This feature is only available in the Pro version. Upgrade Now to continue all these awesome features</p>
                    </div>
                    <div class="wpfnl-pro-modal-body">
                        <div  class="wpfnl-pro-modal-body_container">
                        <ul class="wpfnl-pro-features first-col">
                            <li>
                                <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                <span>Unlimited Contacts</span>
                            </li>
                            <li>
                                <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                <span>Conditional Branching</span>
                            </li>
                            <li>
                                <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                <span>360 Contacts view</span>
                            </li>
                        </ul>
                        <ul class="wpfnl-pro-features second-col">
                            <li>
                                <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                <span>Connect with Form Plugins</span>
                            </li>
                            <li>
                                <?php require WPFNL_DIR . '/admin/partials/icons/tic-icon.php'; ?>
                                <span>Over 60+ Integrations</span>
                            </li>
                        </ul>
                        
                        </div>
                        
                    </div>
                    <div class="wpfnl-pro-modal-footer">
                        <div class="wpfnl-pro-modal-footer_container">
                        <div  class="wpfnl-pro-modal-footer_packages">
                            <div class="wpfnl-pro-modal-footer_packages-type" id="pro-modal-package-type">
                                <strong>Starter</strong> <span>License for 1 site</span>
                            </div>
                            <div class="wpfnl-pro-modal-footer_packages-price" id="pro-modal-package-price">
                                <strong>$129.99</strong> <span>/year</span>
                            </div>

                            <button type="button" class="wpfnl-pro-modal-footer_packages-btn " id="pro-modal-dropdown-btn">
                                <?php require WPFNL_DIR . '/admin/partials/icons/down-arrow.php'; ?>
                            </button>

                            <div class="wpfnl-pro-modal-select-container" id="pro-modal-dropdown-body">
                                <ul class="wpfnl-pro-modal-dropdown wpfnl-pro-modal-select-dropdown">
                                    <li value="129.99" data-url="https://useraccount.getwpfunnels.com/wpf-small-annual-brand-new/steps/wpf-sa-checkout/"><strong>Starter</strong> <span>License for 1 site</span></li>
                                    <li value="199.99" data-url="https://useraccount.getwpfunnels.com/bundle-small-annual/steps/bsa-checkout/"><strong>Pro</strong> <span>License for 1 site with Automation</span></li>
                                    <li value="399.99" data-url="https://useraccount.getwpfunnels.com/bundle-medium-annual/steps/bma-checkout/"><strong>Business</strong> <span>License for 5 sites with Automation</span></li>
                                    <li value="599.99" data-url="https://useraccount.getwpfunnels.com/bundle-large-annual/steps/bla-checkout/"><strong>Agency</strong> <span>License for 50 sites with Automation</span></li>
                                </ul>
                            </div>
                        </div>
                        <div class="wpfnl-footer-btn-wrapper">
                            <a class="btn-default confirmed" target="_blank" href="https://useraccount.getwpfunnels.com/wpf-small-annual-brand-new/steps/wpf-sa-checkout/">
                                <span>Buy Now</span>
                            </a>
                        </div>
                        </div> <p class="wpfnl-pro-modal-footer-text">
                            <span>Easiest Funnel Builder : <strong>7000+</strong> Users, <strong>160+</strong> Five-Star Reviews</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.wpfnl -->