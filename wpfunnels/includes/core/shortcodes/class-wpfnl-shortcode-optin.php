<?php
/**
 * Optin shortcode class
 * 
 * @package
 */
namespace WPFunnels\Shortcodes;


use ElementorPro\Modules\Forms\Module;
use ElementorPro\Plugin;
use WPFunnels\Wpfnl_functions;

class WC_Shortcode_Optin {

	/**
	 * Attributes
	 *
	 * @var array
	 */
	protected $attributes = array();


	/**
	 * WC Shortcode Optin constructor.
  *
	 * @param array $attributes
	 */
	public function __construct( $attributes = array() ) {
		$this->attributes = $this->parse_attributes( $attributes );
	}


	/**
	 * Get shortcode attributes.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes;
	}


	/**
	 * Parse attributes
	 *
	 * @param $attributes
	 * 
	 * @return array
	 */
	protected function parse_attributes( $attributes ) {
		$attributes = shortcode_atts(
			array(
				// First Name Customization Attributes.
				'first_name' 					=> false,
				'first_name_label' 				=> '',
				'first_name_placeholder' 		=> 'First Name',
				'first_name_required' 			=> false,
				'first_name_icon_url' 		    => WPFNL_DIR_URL . '/public/assets/images/user-icon.svg',
				'first_name_autocomplete' 		=> '',
				// Last Name Customization Attributes.
				'last_name' 					=> false,
				'last_name_label' 				=> '',
				'last_name_placeholder' 		=> 'Last Name',
				'last_name_required' 			=> false,
				'last_name_icon_url' 		    => WPFNL_DIR_URL . '/public/assets/images/user-icon.svg',
				'last_name_autocomplete' 		=> '',
				// Phone Number Customization Attributes.
				'phone' 						=> false,
				'phone_label' 					=> '',
				'phone_placeholder' 			=> 'Phone',
				'phone_required' 				=> false,
				'phone_icon_url' 			    => WPFNL_DIR_URL . '/public/assets/images/phone.svg',
				'phone_autocomplete' 			=> '',
				// Website URL Customization Attributes.
				'website_url' 					=> false,
				'website_url_label' 			=> '',
				'website_url_placeholder' 		=> 'Website Url',
				'website_url_required' 			=> false,
				'website_url_icon_url' 		    => WPFNL_DIR_URL . '/public/assets/images/web-url.svg',
				'website_url_autocomplete' 		=> '',
				// Email Customization Attributes.
				'email_label' 					=> '',
				'email_placeholder' 			=> 'Email',
				'show_required_mark' 			=> false,
				'email_icon_url' 		        => WPFNL_DIR_URL . '/public/assets/images/email-open-icon.svg',
				'show_input_fields_icon' 		=> 'true',
				// Message Customization Attributes.
				'message' 						=> false,
				'message_label' 				=> '',
				'message_placeholder' 			=> 'Write your message here...',
				'message_required' 				=> false,
				'data_to_checkout' 				=> false,
				// Acceptance Checkbox Customization Attributes.
				'acceptance_checkbox' 			=> false,
				'acceptance_checkbox_text' 	    => 'I have read and agree the Terms & Condition.',
				'acceptance_checkbox_required' 	=> false,
				'notification_text' 			=> '',
				'post_action' 					=> '',
				'redirect_url' 					=> '',
				'admin_email' 					=> wp_get_current_user()->user_email,
				'admin_email_subject' 			=> 'Opt-in form submission',
				'register_as_subscriber' 		=> false,
				'subscription_permission' 		=> false,
				'subscription_permission_text' 	=> '',
				'enable_mm_contact' 			=> 'no',
				'mm_contact_status' 			=> 'pending',
				'mm_lists' 						=> '',
				'mm_tags' 						=> '',
				// Submit Button Customization Attributes.
				'btn_class' 					=> '',
				'button_text' 					=> 'Submit',
				'button_text_color' 			=> '',
				'button_bg_color' 				=> '',
				'button_hover_color' 			=> '',
				'button_hover_text_color' 		=> '',
				'button_border_radius' 			=> '',
				'button_font_size' 				=> '',
				'button_font_weight' 			=> '',
				'button_padding' 				=> '',
				'button_width' 					=> '',
				'button_icon' 					=> '',
				'button_icon_position' 			=> 'before',
				'button_id' 					=> '',
				'button_style' 					=> '',
				'button_align' 					=> 'center',
				'button_loader_type' 			=> 'default',
			),
			$attributes
		);
		return $attributes;
	}

	/**
	 * Get wrapper classes
	 *
	 * @return array
	 */
	protected function get_wrapper_classes() {
		$classes = array( 'wpfnl', 'wpfnl-optin-form-wrapper', 'wpfnl-shortcode-optin-form-wrapper');
		return $classes;
	}


	/**
	 * Content of optin form
	 *
	 * @return string
	 */
	public function get_content() {
		$classes 				= $this->get_wrapper_classes();
		$recaptcha_setting		= Wpfnl_functions::get_recaptcha_settings();
		$is_recaptch 			= isset($recaptcha_setting['enable_recaptcha']) ? $recaptcha_setting['enable_recaptcha'] : '';
		$site_key 				= isset($recaptcha_setting['recaptcha_site_key']) ? $recaptcha_setting['recaptcha_site_key'] : '';
		$site_secret_key 		= isset($recaptcha_setting['recaptcha_site_secret']) ? $recaptcha_setting['recaptcha_site_secret'] : '';
		$token_input 			= '';
		$recaptch_script 		= '';
		$is_recaptch_input 		= '';
		$token_secret_key 		= '';
		if('on' == $is_recaptch && '' != $site_key &&  '' != $site_secret_key){
			$is_recaptch_input 	= '<input type="hidden" id="wpf-is-recapcha" name="wpf-is-recapcha" value="'.$is_recaptch.'"/>';
			$token_input 		= '<input type="hidden" id="wpf-optin-g-token" name="wpf-optin-g-token" />';
			$token_secret_key 	= '<input type="hidden" id="wpf-optin-g-secret-key" name="wpf-optin-g-secret-key" value="'.$site_secret_key.'" />';
			$recaptch_script 	= '<script src="https://www.google.com/recaptcha/api.js?render='.$site_key.'"></script>';
		}
		ob_start();
		do_action( 'wpfunnels/before_optin_form' );
		require WPFNL_DIR.'/includes/core/shortcodes/templates/optin/form.php';
		do_action( 'wpfunnels/after_optin_form' );
		return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . ob_get_clean() . '</div>';
	}



}
