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
				'first_name' 					=> false,
				'last_name' 					=> false,
				'phone' 						=> false,
				'website_url' 					=> false,
				'message' 						=> false,
				'data_to_checkout' 				=> false,
				'acceptance_checkbox' 			=> false,
				'notification_text' 			=> '',
				'post_action' 					=> '',
				'redirect_url' 					=> '',
				'admin_email' 					=> wp_get_current_user()->user_email,
				'admin_email_subject' 			=> 'Opt-in form submission',
				'btn_class' 					=> '',
				'register_as_subscriber' 		=> false,
				'subscription_permission' 		=> false,
				'subscription_permission_text' 	=> '',
				'first_name_label' 				=> '',
				'last_name_label' 				=> '',
				'email_label' 					=> '',
				'phone_label' 					=> '',
				'website_url_label' 			=> '',
				'message_label' 				=> '',
				'first_name_placeholder' 		=> 'First Name',
				'last_name_placeholder' 		=> 'Last Name',
				'email_placeholder' 			=> 'Email',
				'phone_placeholder' 			=> 'Phone',
				'website_url_placeholder' 		=> 'Website Url',
				'message_placeholder' 			=> 'Write your message here...',
				'enable_mm_contact' 			=> 'no',
				'mm_contact_status' 			=> 'pending',
				'mm_lists' 						=> '',
				'mm_tags' 						=> '',
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
