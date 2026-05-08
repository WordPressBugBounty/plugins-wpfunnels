<?php
namespace WPFunnels\Widgets\DiviModules\D5\OptIn\OptInTrait;

if ( ! defined( 'ABSPATH' ) ) die();

use WPFunnels\Widgets\DiviModules\D5\OptIn\OptIn;
use WPFunnels\Widgets\DiviModules\D5\SharedTrait\ModuleRenderHelperTrait;

trait RenderCallbackTrait {

	use ModuleRenderHelperTrait;

	public static function render_callback(
		array $attrs,
		string $_content,
		\WP_Block $block,
		mixed $elements,
		array $_default_printed_style_attrs = []
	): string {
		$attrs = static::merge_defaults( $attrs, 'wpfnl/opt-in' );
		$props = static::build_props( $attrs );

		$html = static::render_optin_html( $props );

		return static::wrap_with_module_render(
			$html,
			$attrs,
			$elements,
			$block,
			'wpfnl/opt-in',
			[ OptIn::class, 'module_classnames' ],
			[ OptIn::class, 'module_styles' ],
			[ OptIn::class, 'module_script_data' ]
		);
	}

	private static function render_optin_html( array $props ): string {
		$form_source = $props['form_source'] ?? 'wpfnl_forms';

		// Mail Mint form source: delegate to existing Wpfnl_Widgets_Manager helper.
		if ( 'mailmint_forms' === $form_source && ! empty( $props['mailmint_form_id'] ) ) {
			if ( class_exists( 'WPFunnels\Widgets\Wpfnl_Widgets_Manager' ) ) {
				ob_start();
				\WPFunnels\Widgets\Wpfnl_Widgets_Manager::render_mailmint_form(
					$props['mailmint_form_id'],
					$props
				);
				return ob_get_clean();
			}
			return '';
		}

		// WPFunnels default form — render via shared template (no D4 class dependency).
		return static::render_wpfnl_form( $props );
	}

	/**
	 * Render the WPFunnels default optin form using the shared template file.
	 * Generates the button with default inline styles so it is always visible
	 * even when D4 Divi CSS is not available (Divi 5 standalone context).
	 */
	private static function render_wpfnl_form( array $props ): string {
		// Resolve step ID (mirrors D4 behaviour).
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$step_id = get_the_ID();
		if ( ! $step_id ) {
			$step_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
		}
		// phpcs:enable

		// reCAPTCHA.
		$is_recaptch_input = '';
		$token_input       = '';
		$token_secret_key  = '';
		$is_recaptch       = 'off';
		$site_key          = '';
		$site_secret_key   = '';

		if ( class_exists( 'WPFunnels\Wpfnl_functions' ) ) {
			$recaptcha_setting = \WPFunnels\Wpfnl_functions::get_recaptcha_settings();
			$is_recaptch       = $recaptcha_setting['enable_recaptcha'] ?? 'off';
			$site_key          = $recaptcha_setting['recaptcha_site_key'] ?? '';
			$site_secret_key   = $recaptcha_setting['recaptcha_site_secret'] ?? '';

			if ( 'on' === $is_recaptch && '' !== $site_key && '' !== $site_secret_key ) {
				$is_recaptch_input = '<input type="hidden" id="wpf-is-recapcha" name="wpf-is-recapcha" value="' . esc_attr( $is_recaptch ) . '"/>';
				$token_input       = '<input type="hidden" id="wpf-optin-g-token" name="wpf-optin-g-token" />';
				$token_secret_key  = '<input type="hidden" id="wpf-optin-g-secret-key" name="wpf-optin-g-secret-key" value="' . esc_attr( $site_secret_key ) . '" />';
			}
		}

		// Button with default inline styles.
		$btn_text   = esc_html( $props['button_text'] ?? 'Submit' );
		$btn_styles = implode( ';', [
			'display:inline-block',
			'padding:10px 20px',
			'background-color:#6c5ce7',
			'color:#ffffff',
			'border:none',
			'border-radius:4px',
			'font-size:14px',
			'font-weight:600',
			'cursor:pointer',
			'text-decoration:none',
			'line-height:1.3',
		] );
		$button = sprintf(
			'<div class="et_pb_button_wrapper"><a id="wpfunnels_optin-button" class="et_pb_button btn-optin" style="%s">%s <span class="wpfnl-loader"></span></a></div>',
			esc_attr( $btn_styles ),
			$btn_text
		);

		$template = dirname( __DIR__, 4 ) . '/includes/modules/OptIn/template/template-optin.php';

		if ( file_exists( $template ) ) {
			ob_start();
			include $template;
			return ob_get_clean();
		}

		// Ultra-minimal fallback if template file is missing.
		return '<div class="wpfnl-optin-form wpfnl-shortcode-optin-form-wrapper">'
			. '<form method="post">'
			. '<input type="hidden" name="post_id" value="' . absint( $step_id ) . '" />'
			. '<div class="wpfnl-optin-form-wrapper">'
			. '<div class="wpfnl-optin-form-group email">'
			. '<span class="input-wrapper"><input type="email" name="email" class="wpfnl-email" placeholder="' . esc_attr__( 'Email Address', 'wpfnl' ) . '" required /></span>'
			. '</div>'
			. '<div class="wpfnl-optin-form-group submit">' . $button . '</div>'
			. '</div>'
			. '</form></div>';
	}

	private static function build_props( array $attrs ): array {
		return [
			'form_source'                  => static::get_text_value( $attrs, 'form_source' ) ?? 'wpfnl_forms',
			'enable_mm_contact'            => static::get_text_value( $attrs, 'enable_mm_contact' ) ?? 'off',
			'mm_contact_status'            => static::get_text_value( $attrs, 'mm_contact_status' ) ?? 'subscribed',
			'mm_lists'                     => static::get_text_value( $attrs, 'mm_lists' ) ?? '',
			'mm_tags'                      => static::get_text_value( $attrs, 'mm_tags' ) ?? '',
			'mailmint_form_id'             => static::get_text_value( $attrs, 'mailmint_form_id' ) ?? '',
			'layout'                       => static::get_text_value( $attrs, 'layout' ) ?? '',
			'field_label'                  => static::get_text_value( $attrs, 'field_label' ) ?? 'off',
			'field_required_mark'          => static::get_text_value( $attrs, 'field_required_mark' ) ?? 'off',
			'input_fields_icon'            => static::get_text_value( $attrs, 'input_fields_icon' ) ?? 'off',
			'first_name'                   => static::get_text_value( $attrs, 'first_name' ) ?? 'off',
			'first_name_label'             => static::get_text_value( $attrs, 'first_name_label' ) ?? 'First Name',
			'first_name_placeholder'       => static::get_text_value( $attrs, 'first_name_placeholder' ) ?? 'First Name',
			'is_required_name'             => static::get_text_value( $attrs, 'is_required_name' ) ?? 'off',
			'last_name'                    => static::get_text_value( $attrs, 'last_name' ) ?? 'off',
			'last_name_label'              => static::get_text_value( $attrs, 'last_name_label' ) ?? 'Last Name',
			'last_name_placeholder'        => static::get_text_value( $attrs, 'last_name_placeholder' ) ?? 'Last Name',
			'is_required_last_name'        => static::get_text_value( $attrs, 'is_required_last_name' ) ?? 'off',
			'email_label'                  => 'Email',
			'email_placeholder'            => static::get_text_value( $attrs, 'email_placeholder' ) ?? 'Email',
			'phone'                        => static::get_text_value( $attrs, 'phone' ) ?? 'off',
			'phone_label'                  => static::get_text_value( $attrs, 'phone_label' ) ?? 'Phone',
			'phone_placeholder'            => static::get_text_value( $attrs, 'phone_placeholder' ) ?? 'Phone',
			'is_required_phn'              => static::get_text_value( $attrs, 'is_required_phn' ) ?? 'off',
			'website_url'                  => static::get_text_value( $attrs, 'website_url' ) ?? 'off',
			'website_url_label'            => static::get_text_value( $attrs, 'website_url_label' ) ?? 'Website Url',
			'website_url_placeholder'      => static::get_text_value( $attrs, 'website_url_placeholder' ) ?? 'Website Url',
			'is_required_website_url'      => static::get_text_value( $attrs, 'is_required_website_url' ) ?? 'off',
			'message'                      => static::get_text_value( $attrs, 'message' ) ?? 'off',
			'message_label'                => static::get_text_value( $attrs, 'message_label' ) ?? 'Message',
			'message_placeholder'          => static::get_text_value( $attrs, 'message_placeholder' ) ?? 'Message',
			'is_required_message'          => static::get_text_value( $attrs, 'is_required_message' ) ?? 'off',
			'acceptance_checkbox'          => static::get_text_value( $attrs, 'acceptance_checkbox' ) ?? 'off',
			'acceptance_checkbox_text'     => static::get_text_value( $attrs, 'acceptance_checkbox_text' ) ?? 'I have read and agree the Terms & Condition.',
			'is_required_acceptance'       => static::get_text_value( $attrs, 'is_required_acceptance' ) ?? 'off',
			'button_text'                  => static::get_text_value( $attrs, 'button_text' ) ?? 'Submit',
			'admin_email'                  => static::get_text_value( $attrs, 'admin_email' ) ?? '',
			'admin_email_subject'          => static::get_text_value( $attrs, 'admin_email_subject' ) ?? '',
			'notification_text'            => static::get_text_value( $attrs, 'notification_text' ) ?? 'Thank you! Your form was submitted successfully!',
			'other_action'                 => static::get_text_value( $attrs, 'other_action' ) ?? 'notification',
			'redirect_url'                 => static::get_text_value( $attrs, 'redirect_url' ) ?? '',
			'data_to_checkout'             => static::get_text_value( $attrs, 'data_to_checkout' ) ?? 'off',
			'register_as_subscriber'       => static::get_text_value( $attrs, 'register_as_subscriber' ) ?? 'off',
			'subscription_permission'      => static::get_text_value( $attrs, 'subscription_permission' ) ?? 'off',
			'subscription_permission_text' => static::get_text_value( $attrs, 'subscription_permission_text' ) ?? 'I agree to be registered as a subscriber.',
		];
	}
}
