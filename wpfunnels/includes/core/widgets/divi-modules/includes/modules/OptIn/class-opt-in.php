<?php
/**
 * Opt-in 
 * 
 * @package WPFunnels\Widgets\DiviModules\Modules
 */
namespace WPFunnels\Widgets\DiviModules\Modules;

use ET_Builder_Element;
use ET_Builder_Module;
use WPFunnels\Widgets\Wpfnl_Widgets_Manager;
use WPFunnels\Wpfnl_functions;

class WPFNL_OptIN extends ET_Builder_Module {

	public $slug       = 'wpfnl_optin';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => '',
		'author'     => '',
		'author_uri' => '',
	);
	/**
	 * Module properties initialization
	 */
	public function init() {

		$this->name = esc_html__( 'WPF Optin Form', 'wpfnl' );

		$this->icon_path        =  plugin_dir_path( __FILE__ ) . 'optin_form.svg';


		$this->settings_modal_toggles  = array(
			'general'  => array(
				'toggles' => array(
					'optin_form_source' => __( 'Opt-in Form Source', 'wpfnl' ),
					'main_content' 		=> __( 'Form Layout', 'wpfnl' ),
					'form_field'     	=> __( 'Field Label', 'wpfnl' ),
					'field_first_name'  => __( 'First Name', 'wpfnl' ),
					'field_last_name'  	=> __( 'Last Name', 'wpfnl' ),
					'field_email'  		=> __( 'Email', 'wpfnl' ),
					'field_phone'  		=> __( 'Phone', 'wpfnl' ),
					'field_website_url'	=> __( 'Website Url', 'wpfnl' ),
					'field_message'		=> __( 'Message', 'wpfnl' ),
					'field_acceptance'	=> __( 'Acceptance', 'wpfnl' ),
					'button'     		=> __( 'Button', 'wpfnl' ),
					'ac_submit'     	=> __( 'Action After Submission', 'wpfnl' ),
				),
			),
		);
		$this->main_css_element = '%%order_class%%';

	}

	function get_advanced_fields_config() {

		$advanced_fields = array();
		$advanced_fields['background'] = array(
			'has_background_color_toggle'   => false, // default. Warning: to be deprecated
			'use_background_color'          => true, // default
			'use_background_color_gradient' => true, // default
			'use_background_image'          => true, // default
			'use_background_video'          => true, // default
		);

		$advanced_fields['fonts'] = array(
			'text'   => array(
				'label'    => __( 'Text', 'wpfnl' ),
				'toggle_slug' => 'body',
				'sub_toggle'  => 'p',
			),
		);

		$advanced_fields['fonts']['link'] = array(
			'label'    => __( 'Link', 'wpfnl' ),
			'css'      => array(
				'main' => "{$this->main_css_element} a",
			),
			'toggle_slug' => 'body',
			'sub_toggle'  => 'a',
		);

		$advanced_fields['fonts']['quote'] = array(
			'label'    => __( 'Blockquote', 'wpfnl' ),
			'css'      => array(
				'main' => "{$this->main_css_element} blockquote",
			),
			'line_height' => array(
				'default' => '1em',
			),
			'font_size' => array(
				'default' => '16px',
			),
			'toggle_slug' => 'body',
			'sub_toggle'  => 'quote',
		);

		$advanced_fields['borders'] = array(
			'default' => array(), // default
		);

		$advanced_fields['borders']['title'] = array(
			'css'             => array(
				'main' => array(
					'border_radii' => "%%order_class%% .et-demo-title",
					'border_styles' => "%%order_class%% .et-demo-title",
				)
			),
			'label_prefix'    => __( 'Title', 'wpfnl' ),
			'tab_slug'        => 'advanced',
			'toggle_slug'     => 'title',
		);

		$advanced_fields['text'] = array(
			'use_text_orientation'  => true, // default
			'css' => array(
				'text_orientation' => '%%order_class%%',
			),
		);

		$advanced_fields['max_width'] = array(
			'use_max_width'        => true, // default
			'use_module_alignment' => true, // default
		);

		$advanced_fields['margin_padding'] = false;


		$advanced_fields['button'] = array(
			'button' => array(
				'label' => __( 'Button', 'wpfnl' ),
				'css'   => array(
					'alignment'   => "%%order_class%% .btn-optin",
					'important' => 'all',
				),
				'margin_padding'  => array(
					'css' => array(
						'padding'    => ".et_pb_button",
						'important' => 'all',
					),
				),
			),
		);

		$advanced_fields['filters'] = array(
			'child_filters_target' => array(
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'title',
			),
		);

		$advanced_fields['text_shadow'] = array(
			'default' => array(), // default
		);

		return $advanced_fields;
	}
	/**
	 * Module's specific fields
	 *
	 * The following modules are automatically added regardless being defined or not:
	 *   Tabs     | Toggles          | Fields
	 *   --------- ------------------ -------------
	 *   Content  | Admin Label      | Admin Label
	 *   Advanced | CSS ID & Classes | CSS ID
	 *   Advanced | CSS ID & Classes | CSS Class
	 *   Advanced | Custom CSS       | Before
	 *   Advanced | Custom CSS       | Main Element
	 *   Advanced | Custom CSS       | After
	 *   Advanced | Visibility       | Disable On
  *
	 * @return array
	 */

	public function get_fields() {
		$mailmint_forms = Wpfnl_Widgets_Manager::get_mailmint_forms();
		$first_mailmint_form_key = array_key_first( $mailmint_forms );

		$settings =  array(
			'form_source' => [
				'label'            => esc_html__( 'Opt-in Form Source', 'wpfnl' ),
				'description'      => esc_html__( 'Opt-in Form Source', 'wpfnl' ),
				'type'             => 'select',
				'options'          => [
					'wpfnl_forms'    => __( 'WPFunnels', 'wpfnl' ),
					'mailmint_forms' => __( 'Mail Mint', 'wpfnl' )
				],
				'priority'         => 80,
				'default'          => 'wpfnl_forms',
				'default_on_front' => 'wpfnl_forms',
				'toggle_slug'      => 'optin_form_source',
				'sub_toggle'       => 'ul',
				'mobile_options'   => true,
				'computed_affects' => [ '__optinForm' ]
			],

			'mailmint_form_id' => [
				'label'            => esc_html__( 'Mail Mint Form', 'wpfnl' ),
				'description'      => esc_html__( 'Mail Mint Form', 'wpfnl' ),
				'type'             => 'select',
				'options'          => $mailmint_forms,
				'priority'         => 80,
				'default'          => $first_mailmint_form_key,
				'default_on_front' => $first_mailmint_form_key,
				'toggle_slug'      => 'optin_form_source',
				'sub_toggle'       => 'ul',
				'mobile_options'   => true,
				'computed_affects' => [ '__optinForm' ],
				'show_if'          => [ 'form_source' => 'mailmint_forms' ]
			],

			

			'layout' => array(
				'label'            => esc_html__( 'Layout', 'wpfnl' ),
				'description'      => esc_html__( 'Checkout layout', 'wpfnl' ),
				'type'             => 'select',
				'options'          => array(
					''            => __( 'Default Style', 'wpfnl' ),
					'form-style1' => __( 'Form Style-1', 'wpfnl' ),
					'form-style2' => __( 'Form Style-2', 'wpfnl' ),
					'form-style3' => __( 'Form Style-3', 'wpfnl' ),
					'form-style4' => __( 'Form Style-4', 'wpfnl' ),
				),
				'priority'         => 80,
				'default'          => '',
				'default_on_front' => '',
				'toggle_slug'      => 'main_content',
				'sub_toggle'       => 'ul',
				'mobile_options'   => true,
				'computed_affects' => [ '__optinForm' ],
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),


			//-------first name----
			'first_name'       => array(
				'label'            => __( 'Enable First Name', 'wpfnl' ),
				'description'      => __( 'Enable First  Name', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes' ,'wpfnl'),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_first_name',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'first_name_label'       => array(
				'label'            => __( 'First Name Label', 'wpfnl' ),
				'description'      => __( 'First Name Label', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'First Name',
				'default_on_front' => 'First Name',
				'toggle_slug'      => 'field_first_name',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'first_name' => 'on',
					'field_label' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'first_name_placeholder'       => array(
				'label'            => __( 'First Name Placeholder Text', 'wpfnl' ),
				'description'      => __( 'First Name Placeholder Text', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'First Name',
				'default_on_front' => 'First Name',
				'toggle_slug'      => 'field_first_name',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'first_name' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'is_required_name'       => array(
				'label'            => __( 'Mark First Name As Required', 'wpfnl' ),
				'description'      => __( 'Mark First Name As Required', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes' ,'wpfnl'),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_first_name',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'first_name' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),

			//------last name-------
			'last_name'       => array(
				'label'            => __( 'Enable Last Name', 'wpfnl' ),
				'description'      => __( 'Enable Last Name', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_last_name',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'last_name_label'       => array(
				'label'            => __( 'Last Name Label', 'wpfnl' ),
				'description'      => __( 'Last Name Label', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Last Name',
				'default_on_front' => 'Last Name',
				'toggle_slug'      => 'field_last_name',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'last_name' => 'on',
					'field_label' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'last_name_placeholder'       => array(
				'label'            => __( 'Last Name Placeholder Text', 'wpfnl' ),
				'description'      => __( 'Last Name Placeholder Text', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Last Name',
				'default_on_front' => 'Last Name',
				'toggle_slug'      => 'field_last_name',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'last_name' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'is_required_last_name'       => array(
				'label'            => __( 'Mark Last Name As Required', 'wpfnl' ),
				'description'      => __( 'Mark Last Name As Required', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_last_name',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'last_name' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),

			//-------email-------
			'email_label'       => array(
				'label'            => __( 'Email Label', 'wpfnl' ),
				'description'      => __( 'Email Label', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Email',
				'default_on_front' => 'Email',
				'toggle_slug'      => 'field_email',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'field_label' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'email_placeholder'       => array(
				'label'            => __( 'Email Placeholder Text', 'wpfnl' ),
				'description'      => __( 'Email Placeholder Text', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Email',
				'default_on_front' => 'Email',
				'toggle_slug'      => 'field_email',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),

			//-------phone-------
			'phone'       => array(
				'label'            => __( 'Enable Phone', 'wpfnl' ),
				'description'      => __( 'Enable Phone', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_phone',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'phone_label'       => array(
				'label'            => __( 'Phone Label', 'wpfnl' ),
				'description'      => __( 'Phone Label', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Phone',
				'default_on_front' => 'Phone',
				'toggle_slug'      => 'field_phone',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'phone' => 'on',
					'field_label' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'phone_placeholder'       => array(
				'label'            => __( 'Phone Placeholder Text', 'wpfnl' ),
				'description'      => __( 'Phone Placeholder Text', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Phone',
				'default_on_front' => 'Phone',
				'toggle_slug'      => 'field_phone',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'phone' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'is_required_phn'       => array(
				'label'            => __( 'Mark Phone As Required', 'wpfnl' ),
				'description'      => __( 'Mark Phone As Required', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_phone',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'phone' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),

			//-------website url--------
			'website_url'       => array(
				'label'            => __( 'Enable Website Url', 'wpfnl' ),
				'description'      => __( 'Enable Website Url', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_website_url',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'website_url_label'       => array(
				'label'            => __( 'Website Url Label', 'wpfnl' ),
				'description'      => __( 'Website Url Label', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Website Url',
				'default_on_front' => 'Website Url',
				'toggle_slug'      => 'field_website_url',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'website_url' => 'on',
					'field_label' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'website_url_placeholder'       => array(
				'label'            => __( 'Website Url Placeholder Text', 'wpfnl' ),
				'description'      => __( 'Website Url Placeholder Text', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Website Url',
				'default_on_front' => 'Website Url',
				'toggle_slug'      => 'field_website_url',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'website_url' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'is_required_website_url'       => array(
				'label'            => __( 'Mark Website Url As Required', 'wpfnl' ),
				'description'      => __( 'Mark Website Url As Required', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_website_url',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'website_url' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),

			//--------message--------
			'message'       => array(
				'label'            => __( 'Enable Message', 'wpfnl' ),
				'description'      => __( 'Enable Message', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_message',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'message_label'       => array(
				'label'            => __( 'Message Label', 'wpfnl' ),
				'description'      => __( 'Message Label', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Message',
				'default_on_front' => 'Message',
				'toggle_slug'      => 'field_message',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'message' => 'on',
					'field_label' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'message_placeholder'       => array(
				'label'            => __( 'Message Placeholder Text', 'wpfnl' ),
				'description'      => __( 'Message Placeholder Text', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Message',
				'default_on_front' => 'Message',
				'toggle_slug'      => 'field_message',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'message' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'is_required_message'       => array(
				'label'            => __( 'Mark Message As Required', 'wpfnl' ),
				'description'      => __( 'Mark Message As Required', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_message',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'message' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),

			

			//--------Acceptance--------
			'acceptance_checkbox'       => array(
				'label'            => __( 'Acceptance checkbox', 'wpfnl' ),
				'description'      => __( 'Acceptance checkbox', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_acceptance',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'acceptance_checkbox_text'       => array(
				'label'            => __( 'Acceptance Text', 'wpfnl' ),
				'description'      => __( 'Acceptance Text', 'wpfnl' ),
				'type'             => 'tiny_mce',
				'default'          => 'I have read and agree the Terms & Condition.',
				'default_on_front' => 'I have read and agree the Terms & Condition.',
				'toggle_slug'      => 'field_acceptance',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'acceptance_checkbox' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),
			'is_required_acceptance'       => array(
				'label'            => __( 'Mark Acceptance As Required', 'wpfnl' ),
				'description'      => __( 'Mark Acceptance As Required', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'field_acceptance',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'acceptance_checkbox' => 'on',
					'form_source' => 'wpfnl_forms'
				),
			),

			'input_fields_icon'       => array(
				'label'            => __( 'Show Input Field Icon(s)', 'wpfnl' ),
				'description'      => __( 'Show Input Field Icon(s)', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'off' => __( 'No','wpfnl' ),
					'on'  => __( 'Yes','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'form_field',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'field_label'       => array(
				'label'            => __( 'Show Field Label(s)', 'wpfnl' ),
				'description'      => __( 'Show Field Label(s)', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'off' => __( 'No','wpfnl' ),
					'on'  => __( 'Yes','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'form_field',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'field_required_mark'       => array(
				'label'            => __( 'Show Field Required Mark', 'wpfnl' ),
				'description'      => __( 'Show Field Required Mark', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'off' => __( 'No','wpfnl' ),
					'on'  => __( 'Yes','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'form_field',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'button_text' => array(
				'label'           => __( 'Button Text', 'wpfnl' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => __( 'Input your desired button text, or leave blank for no button.', 'wpfnl' ),
				'toggle_slug'     => 'button',
				'default'         => 'Submit',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			),
			'admin_email'       => array(
				'label'            => __( 'Admin Email', 'wpfnl' ),
				'description'      => __( 'Admin Email', 'wpfnl' ),
				'type'             => 'text',
				'default'          => Wpfnl_functions::get_optin_settings('sender_email'),
				'default_on_front' => Wpfnl_functions::get_optin_settings('sender_email'),
				'toggle_slug'      => 'ac_submit',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),
			'admin_email_subject'       => array(
				'label'            => __( 'Admin Email Subject', 'wpfnl' ),
				'description'      => __( 'Admin Email Subject', 'wpfnl' ),
				'type'             => 'text',
				'default'          => Wpfnl_functions::get_optin_settings('email_subject'),
				'default_on_front' => Wpfnl_functions::get_optin_settings('email_subject'),
				'toggle_slug'      => 'ac_submit',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),

			'notification_text'       => array(
				'label'            => __( 'Notification Text', 'wpfnl' ),
				'description'      => __( 'Notification Text', 'wpfnl' ),
				'type'             => 'text',
				'default'          => 'Thank you! Your form was submitted successfully!',
				'default_on_front' => 'Thank you! Your form was submitted successfully!',
				'toggle_slug'      => 'ac_submit',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),
			'other_action'             => array(
				'label'            => __( 'Other action', 'wpfnl' ),
				'description'      => __( 'Other action', 'wpfnl' ),
				'type'             => 'select',
				'options'          => array(
					'notification'    => __( 'None','wpfnl' ),
					'redirect_to'     => __( 'Redirect to url','wpfnl' ),
					'next_step'       => __( 'Next Step','wpfnl' ),
				),
				'priority'         => 80,
				'default'          => 'notification',
				'default_on_front' => 'notification',
				'toggle_slug'      => 'ac_submit',
				'sub_toggle'       => 'ul',
				'mobile_options'   => true,
				'computed_affects' => array(
					'__optinForm',
				),
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),
			'redirect_url'       => array(
				'label'            => __( 'Redirect url', 'wpfnl' ),
				'description'      => __( 'Redirect url', 'wpfnl' ),
				'type'             => 'text',
				'default'          => '',
				'default_on_front' => '',
				'toggle_slug'      => 'ac_submit',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'other_action' => 'redirect_to',
				),
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),
			'data_to_checkout'       => array(
				'label'            => __( 'Carry data to the Next Form', 'wpfnl' ),
				'description'      => __( 'Carry data to the Next Form', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'off' => __( 'No','wpfnl' ),
					'on'  => __( 'Yes','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'ac_submit',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),
			'register_as_subscriber'       => array(
				'label'            => __( 'Register User As Subscriber', 'wpfnl' ),
				'description'      => __( 'Register User As Subscriber', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'off' => __( 'No','wpfnl' ),
					'on'  => __( 'Yes','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'ac_submit',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),
			'subscription_permission'       => array(
				'label'            => __( 'Registration Permission', 'wpfnl' ),
				'description'      => __( 'Registration Permission', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'configuration',
				'options'          => array(
					'off' => __( 'No','wpfnl' ),
					'on'  => __( 'Yes','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'ac_submit',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'register_as_subscriber' => 'on',
				),
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),
			'subscription_permission_text'       => array(
				'label'            => __( 'Registration Permission Text', 'wpfnl' ),
				'description'      => __( 'Registration Permission Text', 'wpfnl' ),
				'type'             => 'tiny_mce',
				'default'          => 'I agree to be registered as a subscriber.',
				'default_on_front' => 'I agree to be registered as a subscriber.',
				'toggle_slug'      => 'ac_submit',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if'          => array(
					'subscription_permission' => 'on',
				),
				'show_if'          => [ 'form_source' => 'wpfnl_forms' ]
			),
			'__optinForm'        => array(
				'type'                => 'computed',
				'computed_callback'   => array(
					'WPFunnels\Widgets\DiviModules\Modules\WPFNL_OptIN',
					'get_optin_form',
				),
				'computed_depends_on' => array(
					'form_source',
					'mailmint_form_id',
					'layout',
					'last_name',
					'first_name',
					'phone',
					'acceptance_checkbox',
					'acceptance_checkbox_text',
					'input_fields_icon',
					'field_label',
					'field_required_mark',
					'admin_email',
					'admin_email_subject',
					'notification_text',
					'other_action',
					'redirect_url',
					'button_text',
					'register_as_subscriber',
					'subscription_permission',
					'subscription_permission_text',
					'website_url',
					'message',
					'data_to_checkout',
					'email_label',
					'first_name_label',
					'last_name_label',
					'phone_label',
					'website_url_label',
					'message_label',
					'is_required_name',
					'is_required_last_name',
					'is_required_phn',
					'is_required_website_url',
					'is_required_message',
					'is_required_acceptance',
					'enable_recaptcha ',
					'recaptcha_site_key',
					'recaptcha_secret_key',
					'first_name_placeholder',
					'last_name_placeholder',
					'email_placeholder',
					'phone_placeholder',
					'website_url_placeholder',
					'message_placeholder',

				)
			),
		);

		if( \WPFunnels\Integrations\Helper::maybe_enabled() ){
			
			$settings['enable_mm_contact'] = array(
				'label'            => __( 'Send leads to Mail Mint', 'wpfnl' ),
				'type'             => 'yes_no_button',
				'priority'         => 80,
				'mobile_options'   => true,
				'options'          => array(
					'on'  => __( 'Yes','wpfnl' ),
					'off' => __( 'No','wpfnl' ),
				),
				'default'          => 'off',
				'default_on_front' => 'off',
				'toggle_slug'      => 'optin_form_source',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'form_source' => 'wpfnl_forms' ]
			);

			$settings['mm_contact_status'] = array(
				'label'            => __( 'Contact Status', 'wpfnl' ),
				'type'             => 'select',
				'priority'         => 80,
				'mobile_options'   => true,
				'options'          => [
					'pending' => __('Pending', 'wpfnl'),
					'subscribed' => __('Subscribed', 'wpfnl'),
					'unsubscribed' => __('Unsubscribed', 'wpfnl'),
				],
				'default'          => 'subscribed',
				'default_on_front' => 'subscribed',
				'toggle_slug'      => 'optin_form_source',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'enable_mm_contact' => 'on' ]
			);

			$settings['mm_lists'] = array(
				'label'            => __( 'Select List', 'wpfnl' ),
				'type'             => 'select',
				'priority'         => 80,
				'mobile_options'   => true,
				'options'          => \WPFunnels\Integrations\Helper::get_lists(),
				'default'          => '',
				'default_on_front' => '',
				'toggle_slug'      => 'optin_form_source',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'enable_mm_contact' => 'on' ]
			);

			$settings['mm_tags'] = array(
				'label'            => __( 'Select Tag', 'wpfnl' ),
				'type'             => 'select',
				'priority'         => 80,
				'mobile_options'   => true,
				'options'          => \WPFunnels\Integrations\Helper::get_tags(),
				'default'          => '',
				'default_on_front' => '',
				'toggle_slug'      => 'optin_form_source',
				'computed_affects' => array(
					'layout',
					'__optinForm'
				),
				'show_if' => [ 'enable_mm_contact' => 'on' ]
			);
		}
		
		return $settings;
	}


	/**
	 * Generate and get the opt-in form
     *
	 * @param $props
	 * 
	 * @return string
	 * @since 2.8.15
	 */
	public static  function get_optin_form( $props ) {


		if( !isset( $props[ 'form_source' ] ) || (!empty( $props[ 'form_source' ] ) && 'mailmint_forms' !== $props[ 'form_source' ]) ) {
			return self::get_wpfnl_default_form( $props );
		}
		elseif ( !empty( $props[ 'form_source' ] ) && !empty( $props[ 'mailmint_form_id' ] ) ) {
			ob_start();
			Wpfnl_Widgets_Manager::render_mailmint_form( $props[ 'mailmint_form_id' ], $props );
			return ob_get_clean();
		}
		return '';
	}


	/**
	 * Generate and get the WPFunnels' default opt-in forms
	 *
	 * @param $props
	 *
	 * @return string
	 * @since 2.8.15
	 */
	protected static function get_wpfnl_default_form( $props ) {
		$step_id = isset($_POST['current_page']['id']) ? $_POST['current_page']['id'] : get_the_ID();

		$layout 					= $props['layout'] ;
		$first_name 				= $props['first_name'] == 'on' ? 'true' : false;
		$last_name 					= $props['last_name'] == 'on' ? 'true' : false;
		$phone 						= $props['phone'] == 'on' ? 'true' : false;
		$acceptance_checkbox 		= $props['acceptance_checkbox'] == 'on' ? 'true' : false;
		$input_field_icon			= $props['input_fields_icon'] == 'on' ? 'true' : false;
		$field_label 				= $props['field_label'] == 'on' ? 'true' : false;
		$admin_email 				= $props['admin_email'];
		$admin_email_subject 		= $props['admin_email_subject'];
		$notification_text 			= $props['notification_text'];
		$other_action 				= $props['other_action'];
		$redirect_url 				= $props['redirect_url'];
		$button_text           		= $props['button_text'];

		//mm contact
		$enable_mm_contact 			= isset($props['enable_mm_contact']) && $props['enable_mm_contact'] == 'on' ? 'yes' : 'no';
		$mm_contact_status 			= isset($props['mm_contact_status']) ? $props['mm_contact_status'] : 'pending';
		$mm_lists 					= isset($props['mm_lists']) ? $props['mm_lists'] : '';
		$mm_tags 					= isset($props['mm_tags']) ? $props['mm_tags'] : '';
		
		// Design related props are added via $this->advanced_options['button']['button']

		// Render button
		$name = new WPFNL_OptIN;
		$button = $name->render_button( array(
			'button_id'        => 'wpfunnels_optin-button',
			'button_text'      => $button_text,
			'button_classname'    => array(
				'btn-optin',
			),
		) );
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
		?>
		<?php echo $recaptch_script;  ?>
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
				echo $is_recaptch_input;
				echo $token_input;
				echo $token_secret_key;
				?>

				<div class="wpfnl-optin-form-wrapper <?php echo $layout; ?>" >
					<?php if( 'on' == $props['first_name'] ){ ?>
						<div class="wpfnl-optin-form-group first-name">

							<?php if( 'on' == $props['field_label'] ){ ?>
								<label for="wpfnl-first-name">
									<?php
									echo $props['first_name_label'] ?$props['first_name_label'] : __('First Name','wpfnl');

									if( 'on' == $props['field_required_mark'] && 'on' == $props['is_required_name'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
                                <?php if( 'on' == $props['input_fields_icon'] ){ ?>
	                                <span class="field-icon">
                                        <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
                                    </span>
                                <?php }
                                $f_name_placeholder = isset($props['first_name_placeholder']) ? $props['first_name_placeholder'] : '';
                                ?>
                                <input type="text" name="first_name" class="wpfnl-first-name" id="wpfnl-first-name" placeholder="<?php echo $f_name_placeholder; ?>" <?php echo 'on' == $props['is_required_name'] ? 'required' : ''; ?>/>
                            </span>

						</div>
					<?php } ?>

					<?php if( 'on' == $props['last_name'] ){ ?>
						<div class="wpfnl-optin-form-group last-name">

							<?php if( 'on' == $props['field_label'] ){ ?>
								<label for="wpfnl-last-name">
									<?php
									echo $props['last_name_label'] ? $props['last_name_label'] : __('Last Name','wpfnl');

									if( 'on' == $props['field_required_mark'] && 'on' == $props['is_required_last_name'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
                                <?php if( 'on' == $props['input_fields_icon'] ){ ?>
	                                <span class="field-icon">
                                        <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
                                    </span>
                                <?php }
                                $l_name_placeholder = isset($props['last_name_placeholder']) ? $props['last_name_placeholder'] : '';
                                ?>
                                <input type="text" name="last_name" class="wpfnl-last-name" id="wpfnl-last-name" placeholder="<?php echo $l_name_placeholder; ?>" <?php echo 'on' == $props['is_required_last_name'] ? 'required' : ''; ?>/>
                            </span>
						</div>
					<?php } ?>

					<div class="wpfnl-optin-form-group email">
						<?php if( 'on' == $props['field_label'] ){ ?>
							<label for="wpfnl-email">
								<?php
								echo $props['email_label'] ? $props['email_label'] : __('Email','wpfnl');

								if( 'on' == $props['field_required_mark'] ){ ?>
									<span class="required-mark">*</span>
								<?php } ?>
							</label>
						<?php } ?>
						<span class="input-wrapper">
                            <?php if( 'on' == $props['input_fields_icon'] ){ ?>
	                            <span class="field-icon">
                                    <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/email-open-icon.svg'; ?>" alt="icon">
                                </span>
                            <?php }
                            $email_placeholder = isset($props['email_placeholder']) ? $props['email_placeholder'] : '';
                            ?>
                            <input type="email" name="email" class="wpfnl-email" id="wpfnl-email" placeholder="<?php echo $email_placeholder; ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" required />
                        </span>
					</div>

					<?php if( 'on' == $props['phone'] ){ ?>
						<div class="wpfnl-optin-form-group phone">

							<?php if( 'on' == $props['field_label'] ){ ?>
								<label for="wpfnl-phone">
									<?php
									echo $props['phone_label'] ? $props['phone_label'] : __('Phone','wpfnl');

									if( 'on' == $props['field_required_mark'] && 'on' == $props['is_required_phn'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
                                <?php if( 'on' == $props['input_fields_icon'] ){ ?>
	                                <span class="field-icon">
                                        <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/phone.svg'; ?>" alt="icon">
                                    </span>
                                <?php }
                                $phone_placeholder = isset($props['phone_placeholder']) ? $props['phone_placeholder'] : '';
                                ?>
                                <input type="text" name="phone" class="wpfnl-phone" id="wpfnl-phone" placeholder="<?php echo $phone_placeholder; ?>" <?php echo 'on' == $props['is_required_phn'] ? 'required' : ''; ?>/>
                            </span>
						</div>
					<?php } ?>

					<?php if( 'on' == $props['website_url'] ){ ?>
						<div class="wpfnl-optin-form-group website-url">

							<?php if( 'on' == $props['field_label'] ){ ?>
								<label for="wpfnl-web-url">
									<?php
									echo $props['website_url_label'] ? $props['website_url_label'] : __('Website Url','wpfnl');

									if( 'on' == $props['field_required_mark'] && 'on' == $props['is_required_website_url'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
								<?php if( 'on' == $props['input_fields_icon'] ){ ?>
									<span class="field-icon">
                                        <img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/web-url.svg'; ?>" alt="icon">
                                    </span>
								<?php }
								$weburl_placeholder = isset($props['website_url_placeholder']) ? $props['website_url_placeholder'] : '';
								?>
                                <input type="text" name="web-url" class="wpfnl-web-url" id="wpfnl-web-url" pattern="(https?://)?.+" size="30" placeholder="<?php echo $weburl_placeholder; ?>" <?php echo 'on' == $props['is_required_website_url'] ? 'required' : ''; ?> />
                            </span>
						</div>
					<?php } ?>

					<?php if( 'on' == $props['message'] ){ ?>
						<div class="wpfnl-optin-form-group message">

							<?php if( 'on' == $props['field_label'] ){ ?>
								<label for="wpfnl-message">
									<?php
									echo $props['message_label'] ? $props['message_label'] : __('Message','wpfnl');

									if( 'on' == $props['field_required_mark'] && 'on' == $props['is_required_message'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php }
							$message_placeholder = isset($props['message_placeholder']) ? $props['message_placeholder'] : '';
							?>

							<span class="input-wrapper">
								<textarea name="message" id="wpfnl-message" class="wpfnl-message" cols="30" rows="3" placeholder="<?php echo $message_placeholder; ?>" <?php echo 'yes' == $props['is_required_message'] ? 'required' : ''; ?> ></textarea>
                            </span>
						</div>
					<?php } ?>

					<?php
					if( 'on' == $props['acceptance_checkbox'] ){
						?>
						<div class="wpfnl-optin-form-group acceptance-checkbox">
							<input type="checkbox" name="acceptance_checkbox" class="wpfnl-acceptance_checkbox" id="wpfnl-acceptance_checkbox-<?php echo esc_attr( $step_id ); ?>" <?php echo 'on' == $props['is_required_acceptance'] ? 'required' : ''; ?> />
							<label for="wpfnl-acceptance_checkbox-<?php echo esc_attr( $step_id ); ?>">
								<span class="check-box"></span>
								<?php
								echo $props['acceptance_checkbox_text'];

								if( 'on' == $props['field_required_mark'] && 'on' == $props['is_required_acceptance'] ){
									echo '<span class="required-mark">*</span>';
								}
								?>
							</label>
						</div>
						<?php
					}
					?>


					<?php
					if( 'on' == $props['data_to_checkout'] ){
						?>
						<input type="hidden" name="data_to_checkout" value="<?php echo esc_attr( 'yes' ); ?>" />
						<?php
					}

					if( 'on' == $props['register_as_subscriber'] ){
						?>
						<input type="hidden" name="optin_allow_registration" value="<?php echo esc_attr( 'yes' ); ?>"/>
						<?php
						if ('on' == $props['subscription_permission']){
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

						<?php echo $button; ?>

					</div>
				</div>
			</form>
			<?php
			if('on' == $is_recaptch && '' != $site_key &&  '' != $site_secret_key){?>
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
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Optin form
     *
	 * @param array $attrs
	 * @param null $content
	 * @param string $render_slug
	 * 
	 * @return bool|string|null
	 */

	public function render( $attrs, $content, $render_slug ) {
		return self::get_optin_form( $this->props );
	}


	/**
	 * Helper method for rendering button markup which works compatible with advanced options' button
     *
	 * @param array $args button settings.
	 *
	 * @return string rendered button HTML
	 */
	public function render_button( $args = array() ) {
		// Prepare arguments.
		$defaults = array(
			'button_id'           => '',
			'button_classname'    => array(),
			'button_custom'       => '',
			'button_rel'          => '',
			'button_text'         => '',
			'button_text_escaped' => false,
			'button_url'          => '',
			'custom_icon'         => '',
			'custom_icon_tablet'  => '',
			'custom_icon_phone'   => '',
			'display_button'      => true,
			'has_wrapper'         => true,
			'url_new_window'      => '',
			'multi_view_data'     => '',
			'button_data'         => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Do not proceed if display_button argument is false.
		if ( ! $args['display_button'] ) {
			return '';
		}

		$button_text = $args['button_text_escaped'] ? $args['button_text'] : esc_html( $args['button_text'] );

		// Do not proceed if button_text argument is empty and not having multi view value.
		if ( '' === $button_text && ! $args['multi_view_data'] ) {
			return '';
		}

		// Button classname.
		$button_classname = array( 'et_pb_button' );

		if ( ( '' !== $args['custom_icon'] || '' !== $args['custom_icon_tablet'] || '' !== $args['custom_icon_phone'] ) && 'on' === $args['button_custom'] ) {
			$button_classname[] = 'et_pb_custom_button_icon';
		}

		// Add multi view CSS hidden helper class when button text is empty on desktop mode.
		if ( '' === $button_text && $args['multi_view_data'] ) {
			$button_classname[] = 'et_multi_view_hidden';
		}

		if ( ! empty( $args['button_classname'] ) ) {
			$button_classname = array_merge( $button_classname, $args['button_classname'] );
		}

		// Custom icon data attribute.
		$use_data_icon = '' !== $args['custom_icon'] && 'on' === $args['button_custom'];
		$data_icon     = $use_data_icon ? sprintf(
			' data-icon="%1$s"',
			esc_attr( et_pb_process_font_icon( $args['custom_icon'] ) )
		) : '';

		$use_data_icon_tablet = '' !== $args['custom_icon_tablet'] && 'on' === $args['button_custom'];
		$data_icon_tablet     = $use_data_icon_tablet ? sprintf(
			' data-icon-tablet="%1$s"',
			esc_attr( et_pb_process_font_icon( $args['custom_icon_tablet'] ) )
		) : '';

		$use_data_icon_phone = '' !== $args['custom_icon_phone'] && 'on' === $args['button_custom'];
		$data_icon_phone     = $use_data_icon_phone ? sprintf(
			' data-icon-phone="%1$s"',
			esc_attr( et_pb_process_font_icon( $args['custom_icon_phone'] ) )
		) : '';
		$button_data = '' !== $args['button_data'];
		$button_data_type     = $button_data ? sprintf(
			' data-offertype="%1$s"',
			esc_attr( et_pb_process_font_icon( $args['button_data'] ) )
		) : '';


		// Render button.
		return sprintf(
			'%7$s<a%9$s class="%5$s" %13$s href="%1$s"%3$s%4$s%6$s%10$s%11$s%12$s>%2$s <span class="wpfnl-loader"></span></a>%8$s',
			esc_url( $args['button_url'] ),
			et_core_esc_previously( $button_text ),
			( 'on' === $args['url_new_window'] ? ' target="_blank"' : '' ),
			et_core_esc_previously( $data_icon ),
			esc_attr( implode( ' ', array_unique( $button_classname ) ) ), // #5
			et_core_esc_previously( $this->get_rel_attributes( $args['button_rel'] ) ),
			$args['has_wrapper'] ? '<div class="et_pb_button_wrapper">' : '',
			$args['has_wrapper'] ? '</div>' : '',
			'' !== $args['button_id'] ? sprintf( ' id="%1$s"', esc_attr( $args['button_id'] ) ) : '',
			et_core_esc_previously( $data_icon_tablet ), // #10
			et_core_esc_previously( $data_icon_phone ),
			et_core_esc_previously( $args['multi_view_data'] ),
			$button_data_type
		);
	}


}

new WPFNL_OptIN;
