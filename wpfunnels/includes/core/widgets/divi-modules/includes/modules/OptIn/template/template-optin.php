<div class="wpfnl-optin-form wpfnl-shortcode-optin-form-wrapper" >
    <form method="post">
        <input type="hidden" name="post_id" value="<?php echo $step_id ?>" />
        <input type="hidden" name="admin_email" value="<?php echo $props[ 'admin_email' ] ?? ''; ?>" />
        <input type="hidden" name="admin_email_subject" value="<?php echo $props[ 'admin_email_subject' ] ?? ''; ?>" />
        <input type="hidden" name="redirect_url" value="<?php echo $props[ 'redirect_url' ] ?? ''; ?>" />
        <input type="hidden" name="notification_text" value="<?php echo $props[ 'notification_text' ] ?? ''; ?>" />
        <input type="hidden" name="post_action" value="<?php echo $props[ 'other_action' ] ?? ''; ?>" />

        <?php
        echo $is_recaptch_input ?? '';
        echo $token_input ?? '';
        echo $token_secret_key ?? '';
        ?>

        <div class="wpfnl-optin-form-wrapper <?php echo $props[ 'layout' ] ?? ''; ?>" >
            <?php if( 'on' === $props['first_name'] ) { ?>
                <div class="wpfnl-optin-form-group first-name">

                    <?php if( 'on' === $props['field_label'] ) { ?>
                        <label for="wpfnl-first-name">
                            <?php
                            echo $props['first_name_label'] ?? __('First Name','wpfnl');

                            if( 'on' === $props['field_required_mark'] && 'on' === $props['is_required_name'] ) { ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( 'on' === $props['input_fields_icon'] ) { ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $f_name_placeholder = $props['first_name_placeholder'] ?? '';
                        ?>
                        <input type="text" name="first_name" class="wpfnl-first-name" id="wpfnl-first-name" placeholder="<?php echo $f_name_placeholder; ?>" <?php echo 'on' === $props['is_required_name'] ? 'required' : ''; ?>/>
                    </span>
                </div>
            <?php } ?>

            <?php if( 'on' === $props['last_name'] ) { ?>
                <div class="wpfnl-optin-form-group last-name">

                    <?php if( 'on' === $props['field_label'] ) { ?>
                        <label for="wpfnl-last-name">
                            <?php
                            echo $props['last_name_label'] ?? __('Last Name','wpfnl');

                            if( 'on' === $props['field_required_mark'] && 'on' === $props['is_required_last_name'] ) { ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( 'on' === $props['input_fields_icon'] ) { ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $l_name_placeholder = $props['last_name_placeholder'] ?? '';
                        ?>
                        <input type="text" name="last_name" class="wpfnl-last-name" id="wpfnl-last-name" placeholder="<?php echo $l_name_placeholder; ?>" <?php echo 'on' === $props['is_required_last_name'] ? 'required' : ''; ?>/>
                    </span>
                </div>
            <?php } ?>

            <div class="wpfnl-optin-form-group email">
                <?php if( 'on' === $props['field_label'] ) { ?>
                    <label for="wpfnl-email">
                        <?php
                        echo $props['email_label'] ?? __('Email','wpfnl');

                        if( 'on' === $props['field_required_mark'] ) { ?>
                            <span class="required-mark">*</span>
                        <?php } ?>
                    </label>
                <?php } ?>
                <span class="input-wrapper">
                    <?php if( 'on' === $props['input_fields_icon'] ) { ?>
                        <span class="field-icon">
                            <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/email-open-icon.svg'; ?>" alt="icon">
                        </span>
                    <?php }
                    $email_placeholder = $props['email_placeholder'] ?? '';
                    ?>
                    <input type="email" name="email" class="wpfnl-email" id="wpfnl-email" placeholder="<?php echo $email_placeholder; ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" required />
                </span>
            </div>

            <?php if( 'on' === $props['phone'] ) { ?>
                <div class="wpfnl-optin-form-group phone">

                    <?php if( 'on' === $props['field_label'] ) { ?>
                        <label for="wpfnl-phone">
                            <?php
                            echo $props['phone_label'] ?? __('Phone','wpfnl');

                            if( 'on' === $props['field_required_mark'] && 'on' === $props['is_required_phn'] ) { ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( 'on' === $props['input_fields_icon'] ) { ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/phone.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $phone_placeholder = $props['phone_placeholder'] ?? '';
                        ?>
                        <input type="text" name="phone" class="wpfnl-phone" id="wpfnl-phone" placeholder="<?php echo $phone_placeholder; ?>" <?php echo 'on' === $props['is_required_phn'] ? 'required' : ''; ?>/>
                    </span>
                </div>
            <?php } ?>

            <?php if( 'on' === $props['website_url'] ) { ?>
                <div class="wpfnl-optin-form-group website-url">

                    <?php if( 'on' === $props['field_label'] ) { ?>
                        <label for="wpfnl-web-url">
                            <?php
                            echo $props['website_url_label'] ?? __('Website Url','wpfnl');

                            if( 'on' === $props['field_required_mark'] && 'on' === $props['is_required_website_url'] ) { ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( 'on' === $props['input_fields_icon'] ) { ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/web-url.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $weburl_placeholder = $props['website_url_placeholder'] ?? '';
                        ?>
                        <input type="text" name="web-url" class="wpfnl-web-url" id="wpfnl-web-url" pattern="(https?://)?.+" size="30" placeholder="<?php echo $weburl_placeholder; ?>" <?php echo 'on' === $props['is_required_website_url'] ? 'required' : ''; ?> />
                    </span>
                </div>
            <?php } ?>

            <?php if( 'on' === $props['message'] ) { ?>
                <div class="wpfnl-optin-form-group message">

                    <?php if( 'on' === $props['field_label'] ) { ?>
                        <label for="wpfnl-message">
                            <?php
                            echo $props['message_label'] ?? __('Message','wpfnl');

                            if( 'on' === $props['field_required_mark'] && 'on' === $props['is_required_message'] ) { ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php }
                    $message_placeholder = $props['message_placeholder'] ?? '';
                    ?>

                    <span class="input-wrapper">
                        <textarea name="message" id="wpfnl-message" class="wpfnl-message" cols="30" rows="3" placeholder="<?php echo $message_placeholder; ?>" <?php echo 'yes' === $props['is_required_message'] ? 'required' : ''; ?> ></textarea>
                    </span>
                </div>
            <?php } ?>

            <?php
            if( 'on' === $props['acceptance_checkbox'] ) {
                ?>
                <div class="wpfnl-optin-form-group acceptance-checkbox">
                    <input type="checkbox" name="acceptance_checkbox" class="wpfnl-acceptance_checkbox" id="wpfnl-acceptance_checkbox-<?php echo esc_attr( $step_id ); ?>" <?php echo 'on' === $props['is_required_acceptance'] ? 'required' : ''; ?> />
                    <label for="wpfnl-acceptance_checkbox-<?php echo esc_attr( $step_id ); ?>">
                        <span class="check-box"></span>
                        <?php
                        echo $props['acceptance_checkbox_text'];

                        if( 'on' === $props['field_required_mark'] && 'on' === $props['is_required_acceptance'] ) {
                            echo '<span class="required-mark">*</span>';
                        }
                        ?>
                    </label>
                </div>
                <?php
            }
            ?>


            <?php
            if( 'on' === $props['data_to_checkout'] ) {
                ?>
                <input type="hidden" name="data_to_checkout" value="<?php echo esc_attr( 'yes' ); ?>" />
                <?php
            }

            if( 'on' === $props['register_as_subscriber'] ) {
                ?>
                <input type="hidden" name="optin_allow_registration" value="<?php echo esc_attr( 'yes' ); ?>"/>
                <?php
                if ('on' === $props['subscription_permission']) {
                    ?>
                    <div class="wpfnl-optin-form-group user-registration-checkbox">
                        <input type="checkbox" name="user_registration_checkbox" class="wpfnl-registration_checkbox" id="wpfnl-registration_checkbox-<?php echo esc_attr( $step_id ); ?>" required/>
                        <label for="wpfnl-registration_checkbox-<?php echo esc_attr( $step_id ); ?>">
                            <span class="check-box"></span>
                            <?php
                            echo $props['subscription_permission_text'];
                            ?>
                            <span class="required-mark">*</span>
                        </label>
                    </div>
                    <?php
                }
            }
            ?>
            <div class="wpfnl-optin-form-group submit align-">
                <?php echo $button ?? ''; ?>
            </div>
        </div>
    </form>
    <?php
    if( 'on' === $is_recaptch && '' !== $site_key &&  '' !== $site_secret_key ) { ?>
        <script>
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo $site_key ?>', {action: 'homepage'}).then(function(token) {
                    document.getElementById("wpf-optin-g-token").value = token;
                });
            });
        </script>
        <?php
    }
    ?>
    <div class="response"></div>
</div>