<?php
/**
 * Optin
 * 
 * @package
 */
namespace WPFunnels\Widgets\Oxygen;

use CT_Text_Block;
use WPFunnels\Widgets\Wpfnl_Widgets_Manager;
use WPFunnels\Wpfnl_functions;

/**
 * Class Optin
 *
 * @package WPFunnels\Widgets\Oxygen
 */
class Optin extends Elements {

    function init() {
        // Do some initial things here.
    }

    function afterInit() {
        // Do things after init, like remove apply params button and remove the add button.
        $this->removeApplyParamsButton();
        // $this->removeAddButton();
    }

    function name() {
        return 'WPF Optin';
    }

    function slug() {
        return "wpfnl-optin";
    }

    function icon() {
		return	plugin_dir_url(__FILE__) . 'icon/optin_form.svg';
    }

//    function button_place() {
//        // return "interactive";
//    }

    function button_priority() {
        // return 9;
    }

    /**
     * Renders an opt-in form from Oxygen builder  based on provided options and defaults in a WPFunnels Landing page.
     *
     * This function first checks the step type to determine if the element should be placed in a specific context.
     * If the step type is "checkout," "upsell," "downsell," or "thankyou," it displays an error message and returns.
     *
     * If the step type is not one of the restricted types, it proceeds to assign various variables from the given options and query parameters:
     * - $step_id: The current step's ID retrieved from the query parameter "post_id" or the current page's ID if the parameter is not provided.
     * - $layout: The layout option retrieved from the given options array.
     * - $admin_email: The admin email option retrieved from the given options array.
     * - $admin_email_subject: The admin email subject option retrieved from the given options array.
     * - $notification_text: The notification text option retrieved from the given options array.
     * - $other_action: The other action option retrieved from the given options array.
     * - $redirect_url: The redirect URL option retrieved from the given options array.
     *
     * Additionally, it retrieves the reCAPTCHA settings using the Wpfnl_functions::get_recaptcha_settings() method:
     * - $recaptcha_setting: The reCAPTCHA settings array.
     * - $is_recaptch: The "enable_recaptcha" option from the reCAPTCHA settings, or an empty string if the option is not provided.
     * - $site_key: The "recaptcha_site_key" option from the reCAPTCHA settings, or an empty string if the option is not provided.
     * - $site_secret_key: The "recaptcha_site_secret" option from the reCAPTCHA settings, or an empty string if the option is not provided.
     *
     * If reCAPTCHA is enabled and both the site key and secret key are not empty strings, the function generates the following HTML elements related to reCAPTCHA:
     * - $is_recaptch_input: A hidden input field for the "enable_recaptcha" option.
     * - $token_input: A hidden input field for the reCAPTCHA token.
     * - $token_secret_key: A hidden input field for the reCAPTCHA secret key.
     * - $recaptch_script: A script tag to load the reCAPTCHA API script from Google.
     *
     * Finally, the function echoes the reCAPTCHA script and includes the template for rendering the optin element,
     * which is fetched from a specific file location.
     *
     * @param array $options The provided options array.
     * @param array $defaults The default values array.
     * @param string $content The content to be rendered.
     * @return void
     */
	function render( $options, $defaults, $content ) {
		if( Wpfnl_functions::check_if_this_is_step_type( 'checkout' ) || Wpfnl_functions::check_if_this_is_step_type( 'upsell' ) || Wpfnl_functions::check_if_this_is_step_type( 'downsell' ) || Wpfnl_functions::check_if_this_is_step_type( 'thankyou' ) ) {
			echo __( 'Sorry, Please place the element in WPFunnels Landing or Custom page', 'wpfnl' );
		}
		else {
			if ( !isset( $options[ 'optin_form_source' ] ) || ( !empty( $options[ 'optin_form_source' ] ) && 'mailmint_forms' !== $options[ 'optin_form_source' ] ) ) {
				$recaptcha_setting = Wpfnl_functions::get_recaptcha_settings();
				$is_recaptch       = $recaptcha_setting[ 'enable_recaptcha' ] ?? '';
				$site_key          = $recaptcha_setting[ 'recaptcha_site_key' ] ?? '';
				$site_secret_key   = $recaptcha_setting[ 'recaptcha_site_secret' ] ?? '';

				if ( 'on' === $is_recaptch && '' !== $site_key && '' !== $site_secret_key ) {
					$is_recaptch_input = "<input type='hidden' id='wpf-is-recapcha' name='wpf-is-recapcha' value='{$is_recaptch}'/>";
					$token_input       = '<input type="hidden" id="wpf-optin-g-token" name="wpf-optin-g-token"/>';
					$token_secret_key  = "<input type='hidden' id='wpf-optin-g-secret-key' name='wpf-optin-g-secret-key' value='{$site_secret_key}'/>";
					$recaptch_script   = "<script src='https://www.google.com/recaptcha/api.js?render={$site_key}'></script>";
				}

				echo $recaptch_script ?? '';

                //mm contact
                $enable_mm_contact 			= isset($options['enable_mm_contact']) && $options['enable_mm_contact'] == 'on' ? 'yes' : 'no';
                $mm_contact_status 			= isset($options['mm_contact_status']) ? $options['mm_contact_status'] : 'pending';
                $mm_lists 					= isset($options['mm_lists']) ? $options['mm_lists'] : '';
                $mm_tags 					= isset($options['mm_tags']) ? $options['mm_tags'] : '';
				require WPFNL_DIR . 'includes/core/widgets/oxygen/elements/optin/template/template-optin.php';
			}
			elseif ( !empty( $options[ 'optin_form_source' ] ) && !empty( $options[ 'mailmint_form_id' ] ) ) {
				Wpfnl_Widgets_Manager::render_mailmint_form( $options[ 'mailmint_form_id' ], $options );
			}
		}
	}

	function controls() {
		$form_source = $this->addControlSection( "optin_source", __( "Form Source", "wpfnl" ), "assets/icon.png", $this );
		$form_source->addOptionControl(
			array(
				"type"    => 'dropdown',
				"name"    => __( "Source", "wpfnl" ),
				"slug"    => 'optin_form_source',
				"default" => "wpfnl_forms"
			)
		)->setValue( [
			'wpfnl_forms'    => 'WPFunnels',
			'mailmint_forms' => 'Mail Mint'
		] )->rebuildElementOnChange();

		$mailmint_forms = Wpfnl_Widgets_Manager::get_mailmint_forms();

		$form_source->addOptionControl(
			array(
				"type"    => 'dropdown',
				"name"    => __( "Mail Mint Form", "wpfnl" ),
				"slug"    => 'mailmint_form_id',
				"default" => array_key_first( $mailmint_forms ),
				"condition" => 'optin_form_source=mailmint_forms'
			)
		)->setValue( $mailmint_forms )->rebuildElementOnChange();


        if( \WPFunnels\Integrations\Helper::maybe_enabled() ){
          
            $form_source->addOptionControl(
                array(
                    "type" => 'dropdown',
                    "name" => __("Send leads to Mail Mint","wpfnl"),
                    "slug" => 'enable_mm_contact',
                    "default" => "off",
                    "condition" => 'optin_form_source=wpfnl_forms'
                )
            )->setValue(array(
                'on'       => __('Yes',"wpfnl" ),
                'off'       => __('No',"wpfnl" ),
            ))->rebuildElementOnChange();

            $form_source->addOptionControl(
                array(
                    "type" => 'dropdown',
                    "name" => __("Contact Status","wpfnl"),
                    "slug" => 'mm_contact_status',
                    "default" => "subscribed",
                    "condition" => 'enable_mm_contact=on'
                )
            )->setValue([
                'pending' => __('Pending', 'wpfnl'),
                'subscribed' => __('Subscribed', 'wpfnl'),
                'unsubscribed' => __('Unsubscribed', 'wpfnl'),
            ])->rebuildElementOnChange();

            $form_source->addOptionControl(
                array(
                    "type" => 'dropdown',
                    "name" => __("Select List","wpfnl"),
                    "slug" => 'mm_lists',
                    "default" => "",
                    "condition" => 'enable_mm_contact=on'
                )
            )->setValue(\WPFunnels\Integrations\Helper::get_lists())->rebuildElementOnChange();

            $form_source->addOptionControl(
                array(
                    "type" => 'dropdown',
                    "name" => __("Select Tag","wpfnl"),
                    "slug" => 'mm_tags',
                    "default" => "",
                    "condition" => 'enable_mm_contact=on'
                )
            )->setValue(\WPFunnels\Integrations\Helper::get_tags())->rebuildElementOnChange();
        }

        // Layout
        $layout = $this->addControlSection("optin_layout", __("Layout","wpfnl"), "assets/icon.png", $this);
        $layout->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Layout","wpfnl"),
                "slug" => 'layout',
                "default" => "",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            ''       			=> __( 'Default Style',"wpfnl" ),
            'form-style1'       => __( 'Form Style-1',"wpfnl" ),
            'form-style2'       => __( 'Form Style-2',"wpfnl" ),
            'form-style3'   	=> __( 'Form Style-3',"wpfnl" ),
            'form-style4'   	=> __( 'Form Style-4',"wpfnl" ),
        ))->rebuildElementOnChange();

        $form_field = $this->addControlSection("optin_form_field", __("Form Fields","wpfnl"), "assets/icon.png", $this);

        //first name
        $field_first_name = $form_field->addControlSection("field_first_name", __("First Name","wpfnl"), "assets/icon.png", $this);
        $field_first_name->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Enable First  Name","wpfnl"),
                "slug" => 'first_name',
                "default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes' ,"wpfnl"),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();

        //First Name label
        $field_first_name->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("First Name Label","wpfnl"),
                "slug" => 'first_name_label',
                "default" => __("First Name","wpfnl"),
                "condition" => 'first_name=on',
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->rebuildElementOnChange();

        $field_first_name->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("First Name Placeholder Text","wpfnl"),
                "slug" => 'first_name_placeholder',
                "default" => __("First Name","wpfnl"),
                "condition" => 'first_name=on',
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->rebuildElementOnChange();

        $field_first_name->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Mark First Name As Required","wpfnl"),
                "slug" => 'is_required_name',
                "default" => "off",
                "condition" => 'first_name=on',
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes' ,"wpfnl"),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();


        //Last Name
        $field_last_name = $form_field->addControlSection("field_last_name", __("Last Name","wpfnl"), "assets/icon.png", $this);
        $field_last_name->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Enable Last Name","wpfnl"),
                "slug" => 'last_name',
                "default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes',"wpfnl" ),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();

        //Last Name label
        $field_last_name->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Last Name Label","wpfnl"),
                "slug" => 'last_name_label',
                "default" => __("Last Name","wpfnl"),
                "condition" => 'last_name=on',
            )
        )->rebuildElementOnChange();

        $field_last_name->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Last Name Placeholder Text","wpfnl"),
                "slug" => 'last_name_placeholder',
                "default" => __("Last Name","wpfnl"),
                "condition" => 'last_name=on',
            )
        )->rebuildElementOnChange();

        $field_last_name->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Mark Last Name As Required","wpfnl"),
                "slug" => 'is_required_last_name',
                "default" => "off",
                "condition" => 'last_name=on',
            )
        )->setValue(array(
            'on'       => __('Yes' ,"wpfnl"),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();


        //Email label
        $field_email = $form_field->addControlSection("field_email", __("Email","wpfnl"), "assets/icon.png", $this);
        $field_email->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Email Label","wpfnl"),
                "slug" => 'email_label',
                "default" => __("Email","wpfnl"),
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->rebuildElementOnChange();

        $field_email->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Email Placeholder Text","wpfnl"),
                "slug" => 'email_placeholder',
                "default" => __("Email","wpfnl"),
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->rebuildElementOnChange();

        //phone
        $field_phone = $form_field->addControlSection("field_phone", __("Phone","wpfnl"), "assets/icon.png", $this);
        $field_phone->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Enable Phone","wpfnl"),
                "slug" => 'phone',
                "default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes',"wpfnl" ),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();

        //phone label
        $field_phone->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Phone Label","wpfnl"),
                "slug" => 'phone_label',
                "default" => __("Phone","wpfnl"),
                "condition" => 'phone=on',
            )
        )->rebuildElementOnChange();

        $field_phone->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Phone Placeholder Text","wpfnl"),
                "slug" => 'phone_placeholder',
                "default" => __("Phone","wpfnl"),
                "condition" => 'phone=on',
            )
        )->rebuildElementOnChange();

        $field_phone->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Mark Phone As Required","wpfnl"),
                "slug" => 'is_required_phone',
                "default" => "off",
                "condition" => 'phone=on',
            )
        )->setValue(array(
            'on'       => __('Yes' ,"wpfnl"),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();


        //website url
        $field_website_url = $form_field->addControlSection("field_website_url", __("Website Url","wpfnl"), "assets/icon.png", $this);
        $field_website_url->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Website Url","wpfnl"),
                "slug" => 'website_url',
                "default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes',"wpfnl" ),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();

        //website label
        $field_website_url->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Website Url Label","wpfnl"),
                "slug" => 'website_url_label',
                "default" => __("Website Url","wpfnl"),
                "condition" => 'website_url=on',
            )
        )->rebuildElementOnChange();

        $field_website_url->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Website Url Placeholder Text","wpfnl"),
                "slug" => 'website_url_placeholder',
                "default" => __("Website Url","wpfnl"),
                "condition" => 'website_url=on',
            )
        )->rebuildElementOnChange();

        $field_website_url->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Mark Website Url As Required","wpfnl"),
                "slug" => 'is_required_website_url',
                "default" => "off",
                "condition" => 'website_url=on',
            )
        )->setValue(array(
            'on'       => __('Yes' ,"wpfnl"),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();


        //message
        $field_message = $form_field->addControlSection("field_message", __("Message","wpfnl"), "assets/icon.png", $this);
        $field_message->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Message","wpfnl"),
                "slug" => 'message',
                "default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes',"wpfnl" ),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();


        //Message label
        $field_message->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Message Label","wpfnl"),
                "slug" => 'message_label',
                "default" => __("Message","wpfnl"),
                "condition" => 'message=on',
            )
        )->rebuildElementOnChange();

        $field_message->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Message Placeholder Text","wpfnl"),
                "slug" => 'message_placeholder',
                "default" => __("Message","wpfnl"),
                "condition" => 'message=on',
            )
        )->rebuildElementOnChange();

        $field_message->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Mark Message As Required","wpfnl"),
                "slug" => 'is_required_message',
                "default" => "off",
                "condition" => 'message=on',
            )
        )->setValue(array(
            'on'       => __('Yes' ,"wpfnl"),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();


        //Acceptance
        $field_acceptance = $form_field->addControlSection("field_acceptance", __("Acceptance","wpfnl"), "assets/icon.png", $this);
        $field_acceptance->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Acceptance Checkbox","wpfnl"),
                "slug" => 'acceptance_checkbox',
                "default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes',"wpfnl" ),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();

        //Acceptance Text
        $field_acceptance->addOptionControl(
            array(
                "type" => 'content',
                "name" => __("Acceptance Text","wpfnl"),
                "slug" => 'acceptance_checkbox_text',
                "default" => __("I have read and agree the Terms & Condition.","wpfnl"),
                "condition" => 'acceptance_checkbox=on',
            )
        )->rebuildElementOnChange();

        $field_acceptance->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Mark Acceptance As Required","wpfnl"),
                "slug" => 'is_required_acceptance',
                "default" => "off",
                "condition" => 'acceptance_checkbox=on',
            )
        )->setValue(array(
            'on'       => __('Yes' ,"wpfnl"),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();


        //Input field Icon
        $field_icon = $form_field->addControlSection("field_icon", __("Fiels Icon","wpfnl"), "assets/icon.png", $this);
        $field_icon->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Show Input Field Icon(s)","wpfnl"),
                "slug" => 'input_fields_icon',
                "default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes' ,"wpfnl"),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();

        //Input field Label
        $field_label = $form_field->addControlSection("field_label", __("Field Label","wpfnl"), "assets/icon.png", $this);
        $field_label->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Show Field Label(s)","wpfnl"),
                "slug" => 'field_label',
                "default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes',"wpfnl" ),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();

        //Input field Require Mark
        $field_label->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Show Field Required Mark","wpfnl"),
                "slug" => 'field_required_mark',
                "default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->setValue(array(
            'on'       => __('Yes',"wpfnl" ),
            'off'       => __('No',"wpfnl" ),
        ))->rebuildElementOnChange();


        //Button Text
        $button = $this->addControlSection("optin_form_button", __("Form Button","wpfnl"), "assets/icon.png", $this);
        $button_selector = '.wpfnl-optin-form-group .btn-optin-oxygen';

        $button->addOptionControl(
            array(
                "type" => 'textfield',
                "name" => __("Button Text","wpfnl"),
                "slug" => 'button_text',
                "default" => "Submit",
                "condition" => 'optin_form_source=wpfnl_forms'
            )
        )->rebuildElementOnChange();

		$button->typographySection(
			__("Typography","wpfnl"),
			$button_selector."",
			$this
		);
		$button->borderSection(
			__("Button Border","wpfnl"),
			$button_selector."",
			$this
		);
		$button->addPreset(
			"padding",
			"menu_item_padding",
			__("Button Padding","wpfnl"),
			$button_selector
		)->whiteList();

		$button->addPreset(
			"margin",
			"menu_item_margin",
			__("Button Margin","wpfnl"),
			$button_selector
		)->whiteList();

		$button->addStyleControls(
			array(
				array(
					"name" => __('Background Color',"wpfnl"),
					"selector" => $button_selector."",
					"property" => 'background-color',
				),
				array(
					"name" => __('Background Hover Color',"wpfnl"),
					"selector" => $button_selector.":hover",
					"property" => 'background-color',
				),

			)
		);

		//Form style
		$form_style = $this->addControlSection("optin_form_style", __("Form Label Style","wpfnl"), "assets/icon.png", $this);
		$form_selector = '.wpfnl-optin-form form';

		$form_style->typographySection(
			__("Typography","wpfnl"),
			".wpfnl-optin-form .wpfnl-optin-form-group > label",
			$this
		);
		$form_style->addStyleControls(
			array(
				array(
					"name" => __('Checkbox Size',"wpfnl"),
					"selector" => ".wpfnl-optin-form .wpfnl-optin-form-group > label .check-box",
					"property" => 'width|height',
					"control_type" => 'slider-measurebox',
					"unit" => 'px'
				),

				array(
					"name" => __('Checkbox Spacing',"wpfnl"),
					"selector" => ".wpfnl-optin-form .wpfnl-optin-form-group.acceptance-checkbox > label, .wpfnl-optin-form .wpfnl-optin-form-group.user-registration-checkbox > label",
					"property" => 'padding-left',
					"control_type" => 'slider-measurebox',
					"unit" => 'px'
				),
				array(
					"name" => __('Row Spacing',"wpfnl"),
					"selector" => ".wpfnl-optin-form .wpfnl-optin-form-group:not(:last-child)",
					"property" => 'margin-bottom',
					"control_type" => 'slider-measurebox',
					"unit" => 'px'
				),
			)
		);

		$input_style = $this->addControlSection("optin_form_input_style", __("Form Input Style","wpfnl"), "assets/icon.png", $this);

		$input_style->typographySection(
			__("Typography","wpfnl"),
			".wpfnl-optin-form .wpfnl-optin-form-group input, .wpfnl-optin-form .wpfnl-optin-form-group textarea",
			$this
		);
		$input_style->borderSection(
			__("Button Border","wpfnl"),
			".wpfnl-optin-form .wpfnl-optin-form-group input, .wpfnl-optin-form .wpfnl-optin-form-group textarea",
			$this
		);
		$input_style->addStyleControls(
			array(
				array(
					"name" => __('Background Color',"wpfnl"),
					"selector" => ".wpfnl-optin-form .wpfnl-optin-form-group input, .wpfnl-optin-form .wpfnl-optin-form-group textarea",
					"property" => 'background-color',
				),

			)
		);




		//Admin Email
		$ac_submit = $this->addControlSection("optin_after_submit", __("Action After Submission","wpfnl"), "assets/icon.png", $this);
		$admin_email = Wpfnl_functions::get_optin_settings('sender_email');
		$email_subject = Wpfnl_functions::get_optin_settings('email_subject');
		$ac_submit->addOptionControl(
			array(
				"type" => 'textfield',
				"name" => __("Admin Email","wpfnl"),
				"slug" => 'admin_email',
				"default" => $admin_email,
                "condition" => 'optin_form_source=wpfnl_forms'
			)
		)->rebuildElementOnChange();

		//Admin Email Subject
		$ac_submit->addOptionControl(
			array(
				"type" => 'textfield',
				"name" => __("Admin Email Subject","wpfnl"),
				"slug" => 'admin_email_subject',
				"default" => $email_subject,
                "condition" => 'optin_form_source=wpfnl_forms'
			)
		)->rebuildElementOnChange();

		//Notification Text
		$ac_submit->addOptionControl(
			array(
				"type" => 'textfield',
				"name" => __("Notification Text","wpfnl"),
				"slug" => 'notification_text',
				"default" => "Thank you! Your form was submitted successfully!",
                "condition" => 'optin_form_source=wpfnl_forms'
			)
		)->rebuildElementOnChange();

		// Other action
		$ac_submit->addOptionControl(
			array(
				"type" => 'dropdown',
				"name" => __("Other action","wpfnl"),
				"slug" => 'other_action',
				"default" => "notification",
                "condition" => 'optin_form_source=wpfnl_forms'
			)
		)->setValue(array(
			'notification'    => __( 'None','wpfnl' ),
			'redirect_to'     => __( 'Redirect to url','wpfnl' ),
			'next_step'       => __( 'Next Step','wpfnl' ),
            
		))->rebuildElementOnChange();

		//Redirect URL
		$ac_submit->addOptionControl(
			array(
				"type" => 'textfield',
				"name" => __("Redirect url","wpfnl"),
				"slug" => 'redirect_url',
				"default" => "",
				"condition" => 'other_action=redirect_to',
			)
		)->rebuildElementOnChange();


		//Send data to Checkout
		$ac_submit->addOptionControl(
			array(
				"type" => 'dropdown',
				"name" => __("Carry data to the Next Form","wpfnl"),
				"slug" => 'data_to_checkout',
				"default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
			)
		)->setValue(array(
			'off'       => __('No',"wpfnl" ),
			'on'       => __('Yes',"wpfnl" ),
		))->rebuildElementOnChange();

		//Optin Registration
		$ac_submit->addOptionControl(
			array(
				"type" => 'dropdown',
				"name" => __("Register User As Subscriber","wpfnl"),
				"slug" => 'register_as_subscriber',
				"default" => "off",
                "condition" => 'optin_form_source=wpfnl_forms'
			)
		)->setValue(array(
			'off'       => __('No',"wpfnl" ),
			'on'       => __('Yes',"wpfnl" ),
		))->rebuildElementOnChange();

		// Subscribed permission
		$ac_submit->addOptionControl(
			array(
				"type" => 'dropdown',
				"name" => __("Registration Permission","wpfnl"),
				"slug" => 'subscription_permission',
				"default" => "off",
				"condition" => 'register_as_subscriber=on',
			)
		)->setValue(array(
			'off'       => __('No',"wpfnl" ),
			'on'       => __('Yes',"wpfnl" ),
		))->rebuildElementOnChange();
		// Subscribed permission Text
		$ac_submit->addOptionControl(
			array(
				"type" => 'textfield',
				"name" => __("Registration Permission Text","wpfnl"),
				"slug" => 'subscription_permission_text',
				"default" => __("I agree to be registered as a subscriber.","wpfnl"),
				"condition" => 'subscription_permission=on',
			)
		)->rebuildElementOnChange();


    }

    function defaultCSS() {

    }


}
