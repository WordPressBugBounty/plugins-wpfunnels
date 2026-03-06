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
                                <li class="nav-li" data-id="offer-settings" >
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="8" width="18" height="4" rx="1" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M12 8V21" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M19 12V19C19 20.1046 18.1046 21 17 21H7C5.89543 21 5 20.1046 5 19V12" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M7.5 7.99994C6.11929 7.99994 5 6.88065 5 5.49994C5 4.11923 6.11929 2.99994 7.5 2.99994C9.474 2.96594 11.26 4.94894 12 7.99994C12.74 4.94894 14.526 2.96594 16.5 2.99994C17.8807 2.99994 19 4.11923 19 5.49994C19 6.88065 17.8807 7.99994 16.5 7.99994" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>

                                    <span><?php esc_html_e('Offer Settings', 'wpfnl'); ?></span>
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
                            <?php if (current_user_can('manage_options') ) { ?>
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
                        <?php do_action('wpfunnels_before_offer_settings'); ?>
                        <h4 class="settings-title"><?php esc_html_e('Offer Settings', 'wpfnl'); ?></h4>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/offer-settings.php'; ?>
                        <?php do_action('wpfunnels_after_offer_settings'); ?>

                        <?php if(!$is_pro_activated){ ?>
                            <div class="upgrade-to-pro-hoverlay">
                                <a
                                    class="btn-default"
                                    target="_blank"
                                    href="<?php echo $pro_url;?>"
                                    title="<?php esc_html_e('Click to Upgrade Pro', 'wpfnl'); ?>"
                                >
                                    <?php
                                        require WPFNL_DIR . '/admin/partials/icons/pro-icon.php';
                                    ?>
                                    Upgrade To Pro Now
                                </a>
                            </div>
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
                        <h3 class="wpfnl-pro-heading">Unlock Advanced WooCommerce Funnel</h3>
                    </div>

                    <div class="wpfnl-pro-modal-body">
                        <div class="wpfnl-pro-modal-body_container">
                            <h4 class="feature-title">Features</h4>

                            <ul class="wpfnl-pro-features first-col">
                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4.59954 0.0999756H2.34958C1.10887 0.0999756 0.0996094 1.10922 0.0996094 2.34991V4.59984C0.0996094 5.84053 1.10887 6.84977 2.34958 6.84977H4.59954C5.84025 6.84977 6.84951 5.84053 6.84951 4.59984V2.34991C6.84951 1.10922 5.84025 0.0999756 4.59954 0.0999756ZM5.34953 4.59984C5.34953 5.01328 5.01335 5.34982 4.59954 5.34982H2.34958C1.93577 5.34982 1.59959 5.01328 1.59959 4.59984V2.34991C1.59959 1.93647 1.93577 1.59993 2.34958 1.59993H4.59954C5.01335 1.59993 5.34953 1.93647 5.34953 2.34991V4.59984Z" fill="#7a8b9a" stroke="#fff" stroke-width=".2"/><path d="M12.8495 0.0999756H10.5996C9.35887 0.0999756 8.34961 1.10922 8.34961 2.34991V4.59984C8.34961 5.84053 9.35887 6.84977 10.5996 6.84977H12.8495C14.0902 6.84977 15.0995 5.84053 15.0995 4.59984V2.34991C15.0995 1.10922 14.0902 0.0999756 12.8495 0.0999756ZM13.5995 4.59984C13.5995 5.01328 13.2634 5.34982 12.8495 5.34982H10.5996C10.1858 5.34982 9.84959 5.01328 9.84959 4.59984V2.34991C9.84959 1.93647 10.1858 1.59993 10.5996 1.59993H12.8495C13.2634 1.59993 13.5995 1.93647 13.5995 2.34991V4.59984Z" fill="#7a8b9a" stroke="#fff" stroke-width=".2"/><path d="M4.59954 8.35016H2.34958C1.10887 8.35016 0.0996094 9.3594 0.0996094 10.6001V12.85C0.0996094 14.0907 1.10887 15.1 2.34958 15.1H4.59954C5.84025 15.1 6.84951 14.0907 6.84951 12.85V10.6001C6.84951 9.3594 5.84025 8.35016 4.59954 8.35016ZM5.34953 12.85C5.34953 13.2635 5.01335 13.6 4.59954 13.6H2.34958C1.93577 13.6 1.59959 13.2635 1.59959 12.85V10.6001C1.59959 10.1866 1.93577 9.85011 2.34958 9.85011H4.59954C5.01335 9.85011 5.34953 10.1866 5.34953 10.6001V12.85Z" fill="#7a8b9a" stroke="#fff" stroke-width=".2"/><path d="M12.8495 8.35016H10.5996C9.35887 8.35016 8.34961 9.3594 8.34961 10.6001V12.85C8.34961 14.0907 9.35887 15.1 10.5996 15.1H12.8495C14.0902 15.1 15.0995 14.0907 15.0995 12.85V10.6001C15.0995 9.3594 14.0902 8.35016 12.8495 8.35016ZM13.5995 12.85C13.5995 13.2635 13.2634 13.6 12.8495 13.6H10.5996C10.1858 13.6 9.84959 13.2635 9.84959 12.85V10.6001C9.84959 10.1866 10.1858 9.85011 10.5996 9.85011H12.8495C13.2634 9.85011 13.5995 10.1866 13.5995 10.6001V12.85Z" fill="#7a8b9a" stroke="#fff" stroke-width=".2"/></svg>
                                    Unlimited Funnels
                                </li>

                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M3.70086 11.7798L2.05474 13.4265C1.52539 13.956 1.52539 14.8145 2.05474 15.3441C2.5841 15.8736 3.44237 15.8736 3.97172 15.3441L5.61784 13.6974L6.56106 15.8822C6.62701 16.0349 6.72237 16.178 6.84717 16.3028C7.37653 16.8324 8.2348 16.8324 8.76415 16.3028C8.9442 16.1227 9.06301 15.9046 9.12058 15.6742L11.0376 8.00391C11.1493 7.55702 11.0304 7.06438 10.6811 6.71496C10.3318 6.36553 9.83933 6.24669 9.3926 6.35841L1.72468 8.27601C1.49441 8.3336 1.27631 8.45245 1.09624 8.63255C0.56688 9.16207 0.56688 10.0206 1.09624 10.5501C1.22101 10.675 1.36408 10.7703 1.51678 10.8363L3.70086 11.7798Z" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.9883 7.1239H16.6993" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.7969 10.0002L14.7139 11.9178" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.43945 2.68567L7.35643 4.60326" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.2324 0.700012V3.41187" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M15.0244 2.68567L13.1074 4.60326" stroke="#7a8b9a" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Advanced One-Click Upsells
                                </li>

                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.99845 13.7986H2.79479C2.60888 13.7985 2.43063 13.7222 2.29922 13.5865C2.16781 13.4508 2.094 13.2668 2.094 13.0749V5.89545C2.094 5.74949 2.03781 5.6095 1.93779 5.50629C1.83777 5.40308 1.70212 5.34509 1.56067 5.34509C1.41922 5.34509 1.28357 5.40308 1.18355 5.50629C1.08353 5.6095 1.02734 5.74949 1.02734 5.89545V13.0749C1.0272 13.3145 1.07282 13.5518 1.16159 13.7731C1.25035 13.9945 1.38053 14.1957 1.54467 14.3651C1.70881 14.5346 1.90371 14.6689 2.11821 14.7606C2.33271 14.8523 2.56262 14.8994 2.79479 14.8994H7.99845C8.13989 14.8994 8.27555 14.8414 8.37557 14.7382C8.47558 14.6349 8.53177 14.495 8.53177 14.349C8.53177 14.203 8.47558 14.063 8.37557 13.9598C8.27555 13.8566 8.13989 13.7986 7.99845 13.7986Z" fill="#7a8b9a"/><path fill-rule="evenodd" clip-rule="evenodd" d="M13.371 7.42987C13.371 7.57584 13.4272 7.71582 13.5272 7.81903C13.6272 7.92225 13.7629 7.98023 13.9043 7.98023C14.0458 7.98023 14.1814 7.92225 14.2815 7.81903C14.3815 7.71582 14.4377 7.57584 14.4377 7.42987V4.28511H14.9331C15.0746 4.28511 15.2102 4.22712 15.3102 4.12391C15.4103 4.0207 15.4665 3.88071 15.4665 3.73475V1.61201C15.4665 1.18448 15.3019 0.774456 15.0089 0.472146C14.716 0.169836 14.3186 0 13.9043 0H1.56211C1.14781 0 0.750485 0.169836 0.457532 0.472146C0.164579 0.774456 0 1.18448 0 1.61201V3.73475C0 3.88071 0.0561895 4.0207 0.156208 4.12391C0.256226 4.22712 0.391879 4.28511 0.533326 4.28511H13.371V7.42987ZM1.06665 3.18439V1.61201C1.06665 1.47641 1.11885 1.34636 1.21177 1.25047C1.30469 1.15459 1.43071 1.10072 1.56211 1.10072H13.9043C14.0357 1.10072 14.1618 1.15459 14.2547 1.25047C14.3476 1.34636 14.3998 1.47641 14.3998 1.61201V3.18439H1.06665Z" fill="#7a8b9a"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3908 8.55042C11.6769 8.55042 10.979 8.76887 10.3854 9.17816C9.79186 9.58745 9.32921 10.1692 9.05601 10.8498C8.78281 11.5304 8.71133 12.2794 8.85061 13.0019C8.98988 13.7245 9.33366 14.3882 9.83846 14.9091C10.3433 15.43 10.9864 15.7848 11.6866 15.9285C12.3868 16.0722 13.1126 15.9985 13.7721 15.7166C14.4317 15.4346 14.9954 14.9572 15.392 14.3447C15.7887 13.7321 16.0004 13.012 16.0004 12.2753C15.9998 11.2875 15.6193 10.3405 14.9425 9.64204C14.2657 8.94362 13.3479 8.551 12.3908 8.55042ZM12.3908 14.8994C11.8879 14.8994 11.3962 14.7455 10.978 14.4571C10.5599 14.1688 10.2339 13.759 10.0415 13.2795C9.849 12.8 9.79865 12.2723 9.89676 11.7633C9.99488 11.2543 10.2371 10.7867 10.5927 10.4197C10.9483 10.0527 11.4014 9.80281 11.8947 9.70156C12.388 9.60031 12.8993 9.65227 13.3639 9.85089C13.8286 10.0495 14.2257 10.3858 14.5051 10.8174C14.7846 11.2489 14.9337 11.7563 14.9337 12.2753C14.9333 12.9711 14.6652 13.6383 14.1884 14.1303C13.7116 14.6223 13.0651 14.8989 12.3908 14.8994Z" fill="#7a8b9a"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.923 11.7249V10.7788C12.923 10.6329 12.8668 10.4929 12.7668 10.3897C12.6668 10.2864 12.5311 10.2285 12.3897 10.2285C12.2482 10.2285 12.1126 10.2864 12.0126 10.3897C11.9126 10.4929 11.8564 10.6329 11.8564 10.7788V11.7249H10.9396C10.7981 11.7249 10.6625 11.7829 10.5625 11.8861C10.4624 11.9893 10.4062 12.1293 10.4062 12.2752C10.4062 12.4212 10.4624 12.5612 10.5625 12.6644C10.6625 12.7676 10.7981 12.8256 10.9396 12.8256H11.8564V13.7717C11.8564 13.9176 11.9126 14.0576 12.0126 14.1608C12.1126 14.2641 12.2482 14.322 12.3897 14.322C12.5311 14.322 12.6668 14.2641 12.7668 14.1608C12.8668 14.0576 12.923 13.9176 12.923 13.7717V12.8256H13.8398C13.9812 12.8256 14.1169 12.7676 14.2169 12.6644C14.3169 12.5612 14.3731 12.4212 14.3731 12.2752C14.3731 12.1293 14.3169 11.9893 14.2169 11.8861C14.1169 11.7829 13.9812 11.7249 13.8398 11.7249H12.923Z" fill="#7a8b9a"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.13294 4.99286C3.99149 4.99286 3.85583 5.05084 3.75582 5.15406C3.6558 5.25727 3.59961 5.39725 3.59961 5.54322V8.51132C3.59959 8.61095 3.62579 8.70873 3.67541 8.79421C3.72502 8.87968 3.7962 8.94966 3.88133 8.99666C3.96647 9.04367 4.06237 9.06594 4.15881 9.06111C4.25525 9.05628 4.34861 9.02451 4.42893 8.96922L5.67585 8.1101L6.92276 8.96812C7.00308 9.02341 7.09644 9.05518 7.19288 9.06001C7.28932 9.06484 7.38523 9.04257 7.47036 8.99556C7.5555 8.94856 7.62667 8.87858 7.67629 8.79311C7.7259 8.70763 7.7521 8.60985 7.75208 8.51021V5.54322C7.75208 5.39725 7.69589 5.25727 7.59588 5.15406C7.49586 5.05084 7.36021 4.99286 7.21876 4.99286C7.07731 4.99286 6.94166 5.05084 6.84164 5.15406C6.74162 5.25727 6.68543 5.39725 6.68543 5.54322V7.48269L5.97291 6.99177C5.88527 6.93143 5.78227 6.89923 5.67691 6.89923C5.57156 6.89923 5.46856 6.93143 5.38092 6.99177L4.66626 7.48269V5.54322C4.66626 5.39725 4.61007 5.25727 4.51005 5.15406C4.41004 5.05084 4.27438 4.99286 4.13294 4.99286Z" fill="#7a8b9a"/></svg>
                                    Smart Order Bump Rules
                                </li>

                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x=".7" y=".7" width="8.45" height="5.98" rx="2.3" stroke="#7a8b9a" stroke-width="1.4"/><rect x="6.85" y="9.32" width="8.45" height="5.98" rx="2.3" stroke="#7a8b9a" stroke-width="1.4"/><rect x="11.78" y=".7" width="3.52" height="5.98" rx="1.3" stroke="#7a8b9a" stroke-width="1.4"/><rect x=".7" y="9.32" width="3.52" height="5.98" rx="1.3" stroke="#7a8b9a" stroke-width="1.4"/></svg>
                                    Premium Checkout Templates
                                </li>

                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M0.699219 4.70001L4.22722 1.17201C4.37606 1.02228 4.55307 0.903483 4.74804 0.822474C4.94301 0.741466 5.15209 0.699845 5.36322 0.700013H12.0352C12.2463 0.699845 12.4554 0.741466 12.6504 0.822474C12.8454 0.903483 13.0224 1.02228 13.1712 1.17201L16.6992 4.70001" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.29883 8.70001V15.1C2.29883 15.5244 2.4674 15.9313 2.76746 16.2314C3.06752 16.5314 3.47448 16.7 3.89883 16.7H13.4988C13.9232 16.7 14.3301 16.5314 14.6302 16.2314C14.9303 15.9313 15.0988 15.5244 15.0988 15.1V8.70001" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.0988 16.7V13.5C11.0988 13.0757 10.9303 12.6687 10.6302 12.3687C10.3301 12.0686 9.92317 11.9 9.49883 11.9H7.89883C7.47448 11.9 7.06752 12.0686 6.76746 12.3687C6.4674 12.6687 6.29883 13.0757 6.29883 13.5V16.7" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M0.699219 4.70001H16.6992" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M16.6992 4.70001V7.10001C16.6992 7.52436 16.5306 7.93132 16.2306 8.23138C15.9305 8.53144 15.5236 8.70001 15.0992 8.70001C14.6318 8.67429 14.1854 8.49741 13.8272 8.19601C13.7318 8.12703 13.617 8.0899 13.4992 8.0899C13.3814 8.0899 13.2667 8.12703 13.1712 8.19601C12.813 8.49741 12.3666 8.67429 11.8992 8.70001C11.4318 8.67429 10.9854 8.49741 10.6272 8.19601C10.5318 8.12703 10.417 8.0899 10.2992 8.0899C10.1814 8.0899 10.0667 8.12703 9.97122 8.19601C9.61303 8.49741 9.16663 8.67429 8.69922 8.70001C8.23181 8.67429 7.7854 8.49741 7.42722 8.19601C7.33176 8.12703 7.21699 8.0899 7.09922 8.0899C6.98145 8.0899 6.86667 8.12703 6.77122 8.19601C6.41303 8.49741 5.96663 8.67429 5.49922 8.70001C5.03181 8.67429 4.5854 8.49741 4.22722 8.19601C4.13176 8.12703 4.01699 8.0899 3.89922 8.0899C3.78145 8.0899 3.66667 8.12703 3.57122 8.19601C3.21303 8.49741 2.76663 8.67429 2.29922 8.70001C1.87487 8.70001 1.46791 8.53144 1.16785 8.23138C0.86779 7.93132 0.699219 7.52436 0.699219 7.10001V4.70001" stroke="#7a8b9a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Storewide Checkout
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="wpfnl-pro-modal-footer">
                        <a href="https://getwpfunnels.com/pricing/" target="_blank" title="Upgrade to Pro" aria-label="Upgrade to Pro" class="btn-default">Upgrade to Pro</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.wpfnl -->