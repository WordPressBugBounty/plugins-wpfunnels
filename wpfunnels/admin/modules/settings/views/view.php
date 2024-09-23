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

            <div class="wpfnl-dashboard__header funnel-settings__header">
                <div class="title">
                    <h1><?php esc_html_e('Settings', 'wpfnl'); ?></h1>
                </div>

                <ul class="helper-link">
                    <li><a href="https://getwpfunnels.com/docs/getting-started-with-wpfunnels/" target="_blank"><?php esc_html_e('Need Help?', 'wpfnl'); ?></a></li>
                    <li><a href="https://getwpfunnels.com/contact-us/" target="_blank"><?php esc_html_e('Contact Us', 'wpfnl'); ?></a></li>
                    <li><a href="?page=wpfunnels-setup"><?php esc_html_e('Run Setup Wizard', 'wpfnl'); ?></a></li>
                </ul>
            </div>
            <!-- /funnel-settings__header -->

            <?php do_action('wpfunnels_before_settings'); ?>
            <div class="wpfnl-funnel-settings__inner-content">

                <div class="wpfnl-funnel-settings__wrapper">
                    <nav class="wpfn-settings__nav">
                        <ul>
                            <li class="nav-li active" data-id="general-settings">
                                <?php include WPFNL_DIR . '/admin/partials/icons/settings-icon-2x.php'; ?>
                                <span><?php esc_html_e('General Settings', 'wpfnl'); ?></span>
                            </li>

                            <li class="nav-li" data-id="permalink-settings">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10 14C10.6583 14.6719 11.5594 15.0505 12.5 15.0505C13.4406 15.0505 14.3417 14.6719 15 14L19 10C19.9175 9.11184 20.2848 7.79798 19.961 6.56274C19.6372 5.32751 18.6725 4.36284 17.4373 4.03901C16.202 3.71519 14.8882 4.08252 14 5.00002L13.5 5.50002" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M14 9.99997C13.3417 9.32809 12.4407 8.94946 11.5 8.94946C10.5594 8.94946 9.65836 9.32809 9.00004 9.99997L5.00004 14C3.65732 15.387 3.67524 17.5946 5.04031 18.9597C6.40538 20.3248 8.61299 20.3427 10 19L10.5 18.5" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                                <span><?php esc_html_e('Permalink', 'wpfnl'); ?></span>
                            </li>

                            <li class="nav-li" data-id="optin-settings">
                                <svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13.298 3.23536L13.3333 3.27071L13.3687 3.23536L16.302 0.302022C16.6381 -0.0340073 17.1397 -0.0340073 17.4758 0.302022C17.8118 0.638051 17.8118 1.13973 17.4758 1.47576L13.9202 5.03131C13.5842 5.36734 13.0825 5.36734 12.7465 5.03131L10.9687 3.25353C10.6327 2.9175 10.6327 2.41583 10.9687 2.0798C11.3047 1.74377 11.8064 1.74377 12.1424 2.0798L13.298 3.23536ZM1.81313 4.76464L1.72778 4.67929V4.8V13.3333C1.72778 13.6114 1.82067 13.8469 1.98687 14.0131C2.15306 14.1793 2.38864 14.2722 2.66667 14.2722H15.1111C15.3891 14.2722 15.6247 14.1793 15.7909 14.0131C15.9571 13.8469 16.05 13.6114 16.05 13.3333V5.33333C16.05 5.07803 16.1349 4.86916 16.2798 4.72424C16.4247 4.57933 16.6336 4.49444 16.8889 4.49444C17.1442 4.49444 17.3531 4.57933 17.498 4.72424C17.6429 4.86916 17.7278 5.07803 17.7278 5.33333V13.3333C17.7278 14.8168 16.5946 15.95 15.1111 15.95H2.66667C1.18317 15.95 0.05 14.8168 0.05 13.3333V4.44444C0.05 2.96095 1.18317 1.82778 2.66667 1.82778H8C8.25531 1.82778 8.46417 1.91266 8.60909 2.05758C8.75401 2.20249 8.83889 2.41136 8.83889 2.66667C8.83889 2.92197 8.75401 3.13084 8.60909 3.27576C8.46417 3.42067 8.25531 3.50556 8 3.50556H3.02222H2.90293L2.98657 3.59061L8.23102 8.92395L8.23131 8.92424C8.60639 9.29933 9.17138 9.29933 9.54647 8.92424L11.1465 7.32424C11.4825 6.98821 11.9842 6.98821 12.3202 7.32424C12.6562 7.66027 12.6562 8.16195 12.3202 8.49798L10.8091 10.0091C10.2857 10.5324 9.58763 10.7944 8.88889 10.7944C8.19191 10.7944 7.58217 10.5337 7.05758 10.0091L1.81313 4.76464Z" fill="#7A8B9A" stroke="#ECECF2" stroke-width="0.1"/>
                                </svg>
                                <span><?php esc_html_e('Opt-in Settings', 'wpfnl'); ?></span>
                            </li>

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

                            <li class="nav-li" data-id="advance-settings">
                                <?php require WPFNL_DIR . '/admin/partials/icons/advanced-settings.php'; ?>
                                <span><?php esc_html_e('Advanced Settings', 'wpfnl'); ?></span>
                            </li>
                            <?php if (current_user_can('manage_options') && $is_pro_activated ) { ?>
                                <li class="nav-li" data-id="user-role-manager">
                                    <svg width="26" height="26" viewBox="0 0 27 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <mask id="mask0_3796_2754" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="27" height="26">
                                            <path d="M0.667969 0H26.668V26H0.667969V0Z" fill="white" />
                                        </mask>
                                        <g mask="url(#mask0_3796_2754)">
                                            <path d="M17.2773 12.9492C17.2773 14.9124 15.6859 16.5039 13.7227 16.5039C11.7595 16.5039 10.168 14.9124 10.168 12.9492C10.168 10.986 11.7595 9.39453 13.7227 9.39453C15.6859 9.39453 17.2773 10.986 17.2773 12.9492Z" stroke="#7A8B9A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M21.3447 19.6431C22.3232 20.1945 23.5652 19.8512 24.1186 18.8765C24.6722 17.9017 24.3276 16.6645 23.3491 16.1131L23.2863 16.0775C22.6183 15.6981 22.2417 14.9515 22.346 14.1904C22.3999 13.797 22.4277 13.3955 22.4277 12.9873C22.4277 12.5573 22.3967 12.1346 22.3369 11.7212C22.2264 10.9582 22.606 10.2073 23.2764 9.82656L23.3491 9.78527C24.3276 9.23394 24.6722 7.99676 24.1186 7.02196C23.5652 6.04721 22.3232 5.70398 21.3447 6.25531L21.2556 6.30589C20.5775 6.69102 19.7373 6.61332 19.1285 6.12587C18.458 5.58906 17.7071 5.14854 16.8966 4.82516C16.1755 4.53743 15.6992 3.84366 15.6992 3.06727V3.0469C15.6992 1.92504 14.7898 1.01565 13.668 1.01565C12.5462 1.01565 11.6367 1.92504 11.6367 3.0469C11.6367 3.82685 11.1561 4.52301 10.4302 4.80835C9.61059 5.13051 8.85121 5.57241 8.17338 6.11236C7.56477 6.59727 6.72622 6.67274 6.04956 6.28842L5.99131 6.25531C5.01276 5.70398 3.7708 6.04721 3.21729 7.02196C2.66377 7.99676 3.00837 9.23394 3.98693 9.78527L4.00323 9.79456C4.68004 10.179 5.06298 10.9375 4.9503 11.7077C4.88916 12.1254 4.85742 12.5526 4.85742 12.9873C4.85742 13.4037 4.88647 13.8133 4.94258 14.2142C5.04922 14.9761 4.67151 15.7243 4.00257 16.1042L3.98693 16.1131C3.00837 16.6645 2.66377 17.9017 3.21729 18.8765C3.7708 19.8512 5.01271 20.1945 5.99126 19.6431" stroke="#7A8B9A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M8.13281 24.9824C8.13281 22.1365 10.4426 19.8027 13.2918 19.8027H14.2472C17.0965 19.8027 19.4063 22.1365 19.4063 24.9824" stroke="#7A8B9A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                        </g>
                                    </svg>

                                    <span><?php esc_html_e('Role Management', 'wpfnl'); ?></span>
                                </li>
                            <?php } ?>
                            <?php if (\WPFunnels\Wpfnl_functions::maybe_logger_enabled()) { ?>
                                <li class="nav-li" data-id="log-settings">
                                    <?php require WPFNL_DIR . '/admin/partials/icons/log-settings.php'; ?>
                                    <span><?php esc_html_e('Logs', 'wpfnl'); ?></span>
                                </li>
                            <?php } ?>
                        </ul>
                    </nav>

                    <div class="wpfnl-funnel__single-settings general" id="general-settings">
                        <h4 class="settings-title"><?php esc_html_e('General Settings', 'wpfnl'); ?></h4>
                        <?php do_action('wpfunnels_before_general_settings'); ?>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/general-settings.php'; ?>
                        <?php do_action('wpfunnels_after_general_settings'); ?>
                    </div>
                    <!-- /General Settings -->

                    <div class="wpfnl-funnel__single-settings offer" id="offer-settings">
                        <?php if ($is_pro_activated) { ?>
                            <?php do_action('wpfunnels_before_offer_settings'); ?>
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
                        <h4 class="settings-title"><?php esc_html_e('Permalink Settings', 'wpfnl'); ?></h4>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/permalink-settings.php'; ?>
                        <?php do_action('wpfunnels_after_permalink_settings'); ?>
                    </div>

                    <div class="wpfnl-funnel__single-settings optin" id="optin-settings">
                        <?php do_action('wpfunnels_before_optin_settings'); ?>
                        <div class="email-settings">
                            <h4 class="settings-title"><?php esc_html_e('Opt-in Email Settings', 'wpfnl'); ?></h4>
                            <?php require WPFNL_DIR . '/admin/modules/settings/views/optin-settings.php'; ?>
                        </div>
                        <div class="wpfnl-recaptcha-setting basic-tools-field">
                            <h4 class="settings-title"> <?php esc_html_e('reCAPTCHA Settings', 'wpfnl'); ?> </h4>
                            <?php require WPFNL_DIR . '/admin/modules/settings/views/rechaptcha-settings.php'; ?>
                        </div>
                        <?php do_action('wpfunnels_after_optin_settings'); ?>
                    </div>

                    <!-- /Permalink Settings -->

                    <div class="wpfnl-funnel__single-settings event-tracking" id="event-tracking-setting">
                        <div class="facebook-pixel">
                            <h4 class="settings-title">
                                <?php require WPFNL_DIR . '/admin/partials/icons/facebook-pixel-icon.php'; ?>
                                <?php esc_html_e('Facebook Pixel Integration', 'wpfnl'); ?>
                                <a href="https://getwpfunnels.com/docs/funnel-integrations/facebook-pixel-integration/" target="_blank" title="Guide On Facebook Pixel Integration">
                                    <?php include WPFNL_DIR . '/admin/partials/icons/doc-icon.php'; ?>
                                </a>
                            </h4>
                            <?php do_action('wpfunnels_before_facebook_pixel_settings'); ?>
                            <?php require WPFNL_DIR . '/admin/modules/settings/views/facebook-pixel-settings.php'; ?>
                            <?php do_action('wpfunnels_after_facebook_pixel_settings'); ?>
                        </div>
                        <div class="gtm">
                            <h4 class="settings-title">
                                <?php require WPFNL_DIR . '/admin/partials/icons/gtm-icon.php'; ?>
                                <?php esc_html_e('Google Tag Manager (GTM) Integration', 'wpfnl'); ?>
                                <a href="https://getwpfunnels.com/docs/funnel-integrations/google-tag-manager-integration/" target="_blank" title="Guide On Google Tag Manager Integration">
                                    <?php include WPFNL_DIR . '/admin/partials/icons/doc-icon.php'; ?>
                                </a>
                            </h4>
                            <?php do_action('wpfunnels_before_gtm_settings'); ?>
                            <?php require WPFNL_DIR . '/admin/modules/settings/views/gtm-settings.php'; ?>
                            <?php do_action('wpfunnels_after_gtm_settings'); ?>
                        </div>

                        <div class="google-place-api-settings">
                            <h4 class="settings-title"> 
                                <?php esc_html_e('Google Maps API Integration', 'wpfnl'); ?> 
                            </h4>
                            <div class="wpfnl-box">
                                <div class="wpfnl-field-wrapper google-place-api">
                                    <label>
                                        <?php esc_html_e('Google Map API Key', 'wpfnl'); ?>
                                        <span class="wpfnl-tooltip">
                                            <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
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

                    <div class="wpfnl-funnel__single-settings advance-settings" id="advance-settings">
                        <?php
                        $rollback_versions = $this->get_roll_back_versions();
                        ?>
                        <?php require WPFNL_DIR . '/admin/modules/settings/views/advance-settings.php'; ?>
                    </div>

                    <?php if (current_user_can('manage_options')) {
                    ?>
                        <div class="wpfnl-funnel__single-settings user-role-manager" id="user-role-manager">
                            <?php require WPFNL_DIR . '/admin/modules/settings/views/user-role-manager.php'; ?>
                        </div>
                    <?php } ?>

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
                                <strong>Small</strong> <span>License for 1 site</span>
                            </div>
                            <div class="wpfnl-pro-modal-footer_packages-price" id="pro-modal-package-price">
                                <strong>$97</strong> <span>/year</span>
                            </div>

                            <button type="button" class="wpfnl-pro-modal-footer_packages-btn " id="pro-modal-dropdown-btn">
                                <?php require WPFNL_DIR . '/admin/partials/icons/down-arrow.php'; ?>
                            </button>

                            <div class="wpfnl-pro-modal-select-container" id="pro-modal-dropdown-body">
                                <ul class="wpfnl-pro-modal-dropdown wpfnl-pro-modal-select-dropdown">
                                <li value="97" data-url="https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/"><strong>Small</strong> <span>License for 1 site</span></li>
                                <li value="147" data-url="https://useraccount.getwpfunnels.com/wpfunnels-annual-5-sites/steps/5-sites-annual-checkout/"><strong>Medium</strong> <span>License for 5 sites</span></li>
                                <li value="237" data-url="https://useraccount.getwpfunnels.com/wpfunnels-annual-unlimited/steps/annual-unlimited-checkout/"><strong>Large</strong> <span>License for 50 sites</span></li>
                                </ul>

                            </div>
                        </div>
                        <div class="wpfnl-footer-btn-wrapper">
                            <a class="btn-default confirmed" target="_blank" href="https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/">
                                <span>Buy Now</span>
                            </a>
                        </div>
                        </div> <p class="wpfnl-pro-modal-footer-text">
                            <span>Easiest Funnel Builder : <strong>7000+</strong> Users, <strong>85+</strong> Five-Star Reviews</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.wpfnl -->