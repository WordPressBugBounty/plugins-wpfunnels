<style>
    <?php if( empty( $settings['input_fields_icon'] ) ) { ?>
    .wpfnl-optin-form .wpfnl-optin-form-group input[type=text],
    .wpfnl-optin-form .wpfnl-optin-form-group input[type=email] {
        padding-right: 14px;
    }
    <?php } ?>
</style>

<?php
if( isset( $settings['optin_form_type'] ) && 'clickto-expand-optin' === $settings['optin_form_type'] ) {
    ?>
    <div class="wpfnl-optin-clickto-expand align-<?php echo $settings['clickto_expand_btn_align'] ?>">
        <button class="btn-default clickto-expand-btn elementor-button" type="button" <?php echo $this->get_render_attribute_string('click-to-expand-button'); ?> >
            <?php $this->render_clickto_expand_text(); ?>
        </button>
    </div>
    <?php
}
?>

<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" <?php $this->print_render_attribute_string( 'wrapper' ); ?> >
    <form method="post" <?php $this->print_render_attribute_string( 'form' ); ?>>
        <input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>" />
        <input type="hidden" name="form_id" value="<?php echo esc_attr( $this->get_id() ); ?>"/>
        <?php
        echo $is_recaptch_input ?? '';
        echo $token_input ?? '';
        echo $token_secret_key ?? '';
        ?>
        <div class="wpfnl-optin-form-wrapper <?php echo $settings['optin_form_layout']; ?>" >
            <?php if( isset( $settings['first_name'] ) && 'yes' === $settings['first_name'] ){ ?>
                <div class="wpfnl-optin-form-group first-name">

                    <?php if( isset( $settings['field_label'] ) && 'yes' === $settings['field_label'] ){ ?>
                        <label for="wpfnl-first-name">
                            <?php
                            echo $settings['first_name_label'] ?? __('First Name','wpfnl');

                            if( isset( $settings['required_mark'], $settings['is_required_name'] ) && 'yes' === $settings['required_mark'] && 'yes' === $settings['is_required_name'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( isset( $settings['input_fields_icon'] ) && 'yes' === $settings['input_fields_icon'] ){ ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $f_name_placeholder = $settings['first_name_placeholder'] ?? '';
                        ?>
                        <input type="text" name="first_name" class="wpfnl-first-name" id="wpfnl-first-name" placeholder="<?php echo $f_name_placeholder; ?>" <?php echo 'yes' === $settings['is_required_name'] ? 'required' : ''; ?>/>
                    </span>
                </div>
            <?php } ?>

            <?php if( 'yes' === $settings['last_name'] ){ ?>
                <div class="wpfnl-optin-form-group last-name">

                    <?php if( 'yes' === $settings['field_label'] ){ ?>
                        <label for="wpfnl-last-name">
                            <?php
                            echo $settings['last_name_label'] ?? __('Last Name','wpfnl');

                            if( isset( $settings['required_mark'], $settings['is_required_last_name'] ) && 'yes' === $settings['required_mark'] && 'yes' === $settings['is_required_last_name'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( isset( $settings['input_fields_icon'] ) && 'yes' === $settings['input_fields_icon'] ){ ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $l_name_placeholder = $settings['last_name_placeholder'] ?? '';
                        ?>
                        <input type="text" name="last_name" class="wpfnl-last-name" id="wpfnl-last-name" placeholder="<?php echo $l_name_placeholder;?>" <?php echo 'yes' === $settings['is_required_last_name'] ? 'required' : ''; ?>/>
                    </span>
                </div>
            <?php } ?>

            <div class="wpfnl-optin-form-group email">
                <?php if( isset( $settings['field_label'] ) && 'yes' === $settings['field_label'] ){ ?>
                    <label for="wpfnl-email">
                        <?php
                        echo $settings['email_label'] ?? __('Email','wpfnl');

                        if( isset( $settings['required_mark'] ) && 'yes' === $settings['required_mark'] ){ ?>
                            <span class="required-mark">*</span>
                        <?php } ?>
                    </label>
                <?php } ?>
                <span class="input-wrapper">
                    <?php if( isset( $settings['input_fields_icon'] ) && 'yes' === $settings['input_fields_icon'] ){ ?>
                        <span class="field-icon">
                            <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/email-open-icon.svg'; ?>" alt="icon">
                        </span>
                    <?php }
                    $email_placeholder = $settings['email_placeholder'] ?? '';
                    ?>
                    <input type="email" name="email" class="wpfnl-email" id="wpfnl-email" placeholder="<?php echo $email_placeholder; ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" required />
                </span>
            </div>

            <?php if( isset( $settings['phone'] ) && 'yes' === $settings['phone'] ){ ?>
                <div class="wpfnl-optin-form-group phone">

                    <?php if( isset( $settings['field_label'] ) && 'yes' === $settings['field_label'] ){ ?>
                        <label for="wpfnl-phone">
                            <?php
                            echo $settings['phone_label'] ?? __('Phone','wpfnl');

                            if( isset( $settings[ 'required_mark' ], $settings[ 'is_required_phone' ] ) && 'yes' === $settings['required_mark'] && 'yes' === $settings['is_required_phone'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( isset( $settings['input_fields_icon'] ) && 'yes' === $settings['input_fields_icon'] ){ ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/phone.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $phone_placeholder = $settings['phone_placeholder'] ?? '';
                        ?>
                        <input type="text" name="phone" class="wpfnl-phone" id="wpfnl-phone" placeholder="<?php echo $phone_placeholder; ?>" <?php echo 'yes' === $settings['is_required_phone'] ? 'required' : ''; ?>/>
                    </span>
                </div>
            <?php } ?>

            <?php if( 'yes' === $settings['website_url'] ){ ?>
                <div class="wpfnl-optin-form-group website-url">

                    <?php if( isset( $settings['field_label'] ) && 'yes' === $settings['field_label'] ){ ?>
                        <label for="wpfnl-web-url">
                            <?php
                            echo $settings['website_url_label'] ?? __('Website Url','wpfnl');

                            if( 'yes' === $settings['required_mark'] && 'yes' === $settings['is_required_website_url'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <span class="input-wrapper">
                        <?php if( isset( $settings['input_fields_icon'] ) && 'yes' === $settings['input_fields_icon'] ){ ?>
                            <span class="field-icon">
                                <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/web-url.svg'; ?>" alt="icon">
                            </span>
                        <?php }
                        $weburl_placeholder = $settings['website_url_placeholder'] ?? '';
                        ?>
                        <input type="text" name="web-url" class="wpfnl-web-url" id="wpfnl-web-url" pattern="(https?://)?.+" size="30" placeholder="<?php echo $weburl_placeholder; ?>" <?php echo 'yes' === $settings['is_required_website_url'] ? 'required' : ''; ?>/>
                    </span>
                </div>
            <?php } ?>

            <?php if( isset( $settings['message'] ) && 'yes' === $settings['message'] ){ ?>
                <div class="wpfnl-optin-form-group message">

                    <?php if( 'yes' === $settings['field_label'] ){ ?>
                        <label for="wpfnl-message">
                            <?php
                            echo $settings['message_label'] ?? __('Message','wpfnl');

                            if( isset( $settings['required_mark'], $settings['is_required_message'] ) && 'yes' === $settings['required_mark'] && 'yes' === $settings['is_required_message'] ){ ?>
                                <span class="required-mark">*</span>
                            <?php } ?>
                        </label>
                    <?php }

                    $message_placeholder = $settings['message_placeholder'] ?? '';
                    ?>

                    <span class="input-wrapper">
                        <textarea name="message" class="wpfnl-message" id="wpfnl-message" cols="30" rows="3" placeholder="<?php echo $message_placeholder; ?>" <?php echo 'yes' === $settings['is_required_message'] ? 'required' : ''; ?> ></textarea>
                    </span>
                </div>
            <?php } ?>

            <?php
            if( isset( $settings['acceptance_checkbox'] ) && 'yes' === $settings['acceptance_checkbox'] ){
                ?>
                <div class="wpfnl-optin-form-group acceptance-checkbox">
                    <input type="checkbox" name="acceptance_checkbox" class="wpfnl-acceptance_checkbox" id="wpfnl-acceptance_checkbox-<?php echo esc_attr( $this->get_id() ); ?>" <?php echo 'yes' === $settings['is_required_acceptance'] ? 'required' : ''; ?> />
                    <label for="wpfnl-acceptance_checkbox-<?php echo esc_attr( $this->get_id() ); ?>">
                        <span class="check-box"></span>
                        <?php
                        echo $settings['acceptance_checkbox_text'] ?? '';

                        if( isset( $settings['required_mark'], $settings['is_required_acceptance'] ) && 'yes' === $settings['required_mark'] && 'yes' === $settings['is_required_acceptance'] ){
                            echo '<span class="required-mark">*</span>';
                        }
                        ?>
                    </label>
                </div>
                <?php
            }
            ?>

            <?php
            if( isset( $settings['allow_registration'] ) && 'yes' === $settings['allow_registration'] ){
                ?>
                <input type="hidden" name="optin_allow_registration" value="<?php echo esc_attr( 'yes' ); ?>"/>
                <?php
                if( isset( $settings['allow_user_permission'] ) && 'yes' === $settings['allow_user_permission'] ){
                    ?>
                    <div class="wpfnl-optin-form-group user-registration-checkbox">
                        <input type="checkbox" name="user_registration_checkbox" class="wpfnl-registration_checkbox" id="wpfnl-registration_checkbox-<?php echo esc_attr( $this->get_id() ); ?>" required/>
                        <label for="wpfnl-registration_checkbox-<?php echo esc_attr( $this->get_id() ); ?>">
                            <span class="check-box"></span>
                            <?php
                            echo $settings['allow_user_registration_permission_text'] ?? '';
                            ?>
                            <span class="required-mark">*</span>
                        </label>
                    </div>
                    <?php
                }
            }
            ?>
            <?php
            if( isset( $settings['data_to_checkout'] ) && 'yes' === $settings['data_to_checkout'] ){
                ?>
                <input type="hidden" name="data_to_checkout" value="<?php echo esc_attr( 'yes' ); ?>"/>
                <?php
            }
            ?>

            <div class="wpfnl-optin-form-group submit align-<?php echo $settings['btn_align'] ?>">
                <button type="submit" <?php echo $this->get_render_attribute_string('button'); ?>>
                    <?php $this->render_text(); ?>
                    <span class="wpfnl-loader"></span>
                </button>
            </div>
        </div>
    </form>

    <?php
    if( 'on' === $is_recaptch && '' !== $site_key &&  '' !== $site_secret_key ) { ?>
        <script>
            grecaptcha.ready( function() {
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