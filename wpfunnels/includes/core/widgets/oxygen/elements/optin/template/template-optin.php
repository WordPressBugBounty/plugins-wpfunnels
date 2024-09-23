<?php
$step_id             = $_GET[ 'post_id' ] ?? get_the_ID();
$layout              = $options[ 'layout' ] ?? '';
$admin_email         = $options[ 'admin_email' ]  ?? '';
$admin_email_subject = $options[ 'admin_email_subject' ] ?? '';
$notification_text   = $options[ 'notification_text' ] ?? '';
$other_action        = $options[ 'other_action' ] ?? '';
$redirect_url        = $options[ 'redirect_url' ] ?? '';
?>
<div class="wpfnl-optin-form wpfnl-shortcode-optin-form-wrapper" >
    <form method="post">
        <input type="hidden" name="post_id" value="<?php echo $step_id; ?>" />
        <input type="hidden" name="admin_email" value="<?php echo $admin_email; ?>" />
        <input type="hidden" name="admin_email_subject" value="<?php echo $admin_email_subject; ?>" />
        <input type="hidden" name="redirect_url" value="<?php echo $redirect_url; ?>" />
        <input type="hidden" name="notification_text" value="<?php echo $notification_text; ?>" />
        <input type="hidden" name="post_action" value="<?php echo $other_action; ?>" />
        <input type="hidden" name="enable_mm_contact" value="<?php echo $enable_mm_contact; ?>" />
        <input type="hidden" name="mm_contact_status" value="<?php echo $mm_contact_status; ?>" />
        <input type="hidden" name="mm_lists" value="<?php echo $mm_lists; ?>" />
        <input type="hidden" name="mm_tags" value="<?php echo $mm_tags; ?>" />

        <?php
        echo $is_recaptch_input ?? '';
        echo $token_input ?? '';
        echo $token_secret_key ?? '';
        ?>
        <div class="wpfnl-optin-form-wrapper <?php echo $layout; ?>" >
            <?php if( isset( $options['first_name'] ) && 'on' === $options['first_name'] ) { ?>
                <div class="wpfnl-optin-form-group first-name">

                    <?php if( isset( $options['field_label'] ) && 'on' === $options['field_label'] ){ ?>
                        <label for="wpfnl-first-name">
                            <?php
                            echo $options['first_name_label'] ?? __('First Name','wpfnl');

                            if( isset( $options['field_required_mark'], $options['is_required_name'] ) && 'on' === $options['field_required_mark'] && 'on' === $options['is_required_name'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                                <?php if( isset( $options['input_fields_icon'] ) && 'on' === $options['input_fields_icon'] ){ ?>
                                    <span class="field-icon">
                                        <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
                                    </span>
                                <?php }
                                $f_name_placeholder = $options['first_name_placeholder'] ?? '';
                                ?>
                                <input type="text" name="first_name" id="wpfnl-first-name" class="wpfnl-first-name" placeholder="<?php echo $f_name_placeholder; ?>" <?php echo 'on' === $options['is_required_name'] ? 'required' : ''; ?>/>
                            </span>

                </div>
            <?php } ?>

            <?php if( isset( $options['last_name'] ) && 'on' === $options['last_name'] ){ ?>
                <div class="wpfnl-optin-form-group last-name">

                    <?php if( isset( $options['field_label'] ) && 'on' === $options['field_label'] ){ ?>
                        <label for="wpfnl-last-name">
                            <?php
                            echo $options['last_name_label'] ?? __('Last Name','wpfnl');

                            if( isset( $options['field_required_mark'], $options['is_required_last_name'] ) && 'on' === $options['field_required_mark'] && 'on' === $options['is_required_last_name'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                                <?php if( 'on' == $options['input_fields_icon'] ){ ?>
                                    <span class="field-icon">
                                        <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
                                    </span>
                                <?php }
                                $l_name_placeholder = isset($options['last_name_placeholder']) ? $options['last_name_placeholder'] : '';
                                ?>
                                <input type="text" name="last_name" id="wpfnl-last-name" class="wpfnl-last-name" placeholder="<?php echo $l_name_placeholder; ?>" <?php echo 'on' == $options['is_required_last_name'] ? 'required' : ''; ?>/>
                            </span>
                </div>
            <?php } ?>

            <div class="wpfnl-optin-form-group email">
                <?php if( isset( $options['field_label'] ) && 'on' === $options['field_label'] ){ ?>
                    <label for="wpfnl-email">
                        <?php
                        echo $options['email_label'] ?? __('Email','wpfnl');
                        if( isset( $options['field_required_mark'] ) && 'on' === $options['field_required_mark'] ){ ?>
                            <span class="required-mark">*</span>
                        <?php } ?>
                    </label>
                <?php } ?>
                <span class="input-wrapper">
                    <?php if( isset( $options['input_fields_icon'] ) && 'on' === $options['input_fields_icon'] ){ ?>
                        <span class="field-icon">
                            <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/email-open-icon.svg'; ?>" alt="icon">
                        </span>
                    <?php }
                    $email_placeholder = $options['email_placeholder'] ?? '';
                    ?>
                    <input type="email" name="email" id="wpfnl-email" class="wpfnl-email" placeholder="<?php echo $email_placeholder; ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" required />
                </span>
            </div>

            <?php if( isset( $options['phone'] ) && 'on' === $options['phone'] ){ ?>
                <div class="wpfnl-optin-form-group phone">
                    <?php if( isset( $options['field_label'] ) && 'on' === $options['field_label'] ){ ?>
                        <label for="wpfnl-phone">
                            <?php
                            echo $options['phone_label'] ?? __('Phone','wpfnl');

                            if( isset( $options['field_required_mark'], $options['is_required_phone'] ) && 'on' === $options['field_required_mark'] && 'on' === $options['is_required_phone'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( isset( $options['input_fields_icon'] ) && 'on' === $options['input_fields_icon'] ){ ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/phone.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $phone_placeholder = $options['phone_placeholder'] ?? '';
                        ?>
                        <input type="text" name="phone" id="wpfnl-phone" class="wpfnl-phone" placeholder="<?php echo $phone_placeholder; ?>" <?php echo isset( $options['is_required_phone'] ) && 'on' === $options['is_required_phone'] ? 'required' : ''; ?> />
                    </span>
                </div>
            <?php } ?>

            <?php if( isset( $options['website_url'] ) && 'on' === $options['website_url'] ){ ?>
                <div class="wpfnl-optin-form-group web-url">

                    <?php if( isset( $options['field_label'] ) && 'on' === $options['field_label'] ){ ?>
                        <label for="web-url">
                            <?php
                            echo $options['website_url_label'] ?? __('Website Url','wpfnl');

                            if( isset( $options['field_required_mark'], $options['is_required_website_url'] ) && 'on' === $options['field_required_mark'] && 'on' === $options['is_required_website_url'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( isset( $options['input_fields_icon'] ) && 'on' === $options['input_fields_icon'] ){ ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/web-url.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $weburl_placeholder = $options['website_url_placeholder'] ?? '';
                        ?>
                        <input type="text" name="web-url" id="wpfnl-web-url" class="wpfnl-web-url" pattern="(https?://)?.+" size="30" placeholder="<?php echo $weburl_placeholder; ?>" <?php echo isset( $options['is_required_website_url'] ) && 'on' === $options['is_required_website_url'] ? 'required' : ''; ?>/>
                    </span>
                </div>
            <?php } ?>

            <?php if( isset( $options['message'] ) && 'on' === $options['message'] ){ ?>
                <div class="wpfnl-optin-form-group message">
                    <?php if( isset( $options['field_label'] ) && 'on' === $options['field_label'] ){ ?>
                        <label for="wpfnl-message">
                            <?php
                            echo $options['message_label'] ?? __('Message','wpfnl');

                            if( 'on' === $options['field_required_mark'] && 'on' == $options['is_required_message'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php }
                    $message_placeholder = $options['message_placeholder'] ?? '';
                    ?>
                    <span class="input-wrapper">
                        <textarea name="message" id="wpfnl-message" class="wpfnl-message" cols="30" rows="3" placeholder="<?php echo $message_placeholder; ?>" <?php echo isset( $options['is_required_message'] ) && 'on' === $options['is_required_message'] ? 'required' : ''; ?> ></textarea>
                    </span>
                </div>
            <?php } ?>

            <?php if( isset( $options['acceptance_checkbox'] ) && 'on' === $options['acceptance_checkbox'] ){
                ?>
                <div class="wpfnl-optin-form-group acceptance-checkbox">
                    <input type="checkbox" name="acceptance_checkbox" class="wpfnl-acceptance_checkbox" id="wpfnl-acceptance_checkbox-<?php echo esc_attr( $step_id ); ?>" <?php echo 'on' == $options['is_required_acceptance'] ? 'required' : ''; ?> />
                    <label for="wpfnl-acceptance_checkbox-<?php echo esc_attr( $step_id ); ?>">
                        <span class="check-box"></span>
                        <?php
                        echo $options['acceptance_checkbox_text'] ?? '';

                        if( isset( $options['field_required_mark'], $options['is_required_acceptance'] ) && 'on' == $options['field_required_mark'] && 'on' == $options['is_required_acceptance'] ){
                            echo '<span class="required-mark">*</span>';
                        }
                        ?>
                    </label>
                </div>
                <?php
            } ?>

            <?php
            if( isset( $options['data_to_checkout'] ) && 'on' === $options['data_to_checkout'] ){
                ?>
                <input type="hidden" name="data_to_checkout" value="<?php echo esc_attr( 'yes' ); ?>"/>
                <?php
            }

            if( isset( $options['register_as_subscriber'] ) && 'on' === $options['register_as_subscriber'] ){
                ?>
                <input type="hidden" name="optin_allow_registration" value="<?php echo esc_attr( 'yes' ); ?>"/>
                <?php
                if ( isset( $options['subscription_permission'] ) && 'on' == $options['subscription_permission']){
                    ?>
                    <div class="wpfnl-optin-form-group user-registration-checkbox">
                        <input type="checkbox" name="user_registration_checkbox" class="wpfnl-registration_checkbox" id="wpfnl-registration_checkbox-<?php echo esc_attr( $step_id ); ?>" required/>
                        <label for="wpfnl-registration_checkbox-<?php echo esc_attr( $step_id ); ?>">
                            <span class="check-box"></span>
                            <?php
                            echo $options['subscription_permission_text'] ?? '';
                            ?>
                            <span class="required-mark">*</span>
                        </label>
                    </div>
                    <?php
                }
            }
            ?>

            <div class="wpfnl-optin-form-group submit">
                <button type="submit" class="btn-optin-oxygen btn-optin">
                    <?php echo $options['button_text'] ?>
                    <span class="wpfnl-loader"></span>
                </button>
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