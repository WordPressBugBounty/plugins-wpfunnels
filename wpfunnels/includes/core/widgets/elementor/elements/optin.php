<?php
/**
 * Opt-in
 *
 * @package
 */
namespace WPFunnels\Widgets\Elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use WPFunnels\Widgets\Wpfnl_Widgets_Manager;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

if (! defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

/**
 * Optin form widget
 *
 * @since 1.0.0
 */
class OptinForm extends Widget_Base {


	/**
     * Register the widget controls.
     *
     * Adds different input fields to allow the user to change and Wpvrize the widget settings.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function init_controls() {
        if ( version_compare(ELEMENTOR_VERSION, '3.1.0', '>=') ) {
            $this->register_controls();
        } else {
            $this->_register_controls();
        }
    }



	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'wpfnl-optin-form';
	}


	/**
	 * Retrieve the widget title.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return __('Optin Form', 'wpfnl');
	}


	/**
	 * Retrieve the widget icon.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-form-horizontal';
	}


	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories()
	{
		return [ 'wp-funnel' ];
	}


	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget scripts dependencies.
	 */
	public function get_script_depends()
	{
		return [ 'optin-form' ];
	}


	/**
	 * Get button sizes.
	 *
	 * Retrieve an array of button sizes.
	 *
	 * @return array An array containing button sizes.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_button_sizes()
	{
		return [
			'xs' => __('Extra Small', 'wpfnl'),
			'sm' => __('Small', 'wpfnl'),
			'md' => __('Medium', 'wpfnl'),
			'lg' => __('Large', 'wpfnl'),
			'xl' => __('Extra Large', 'wpfnl'),
		];
	}


	/**
	 * Register the widget controls.
  *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_controls()
	{
		$this->register_form_source_controls();
		$this->register_form_layout_controls();
		$this->register_form_field_controls();
		$this->register_form_button_controls();
		$this->register_clickto_expand_button_controls();
		

		$this->register_action_after_submit_controls();


		//-------style tab--------
		$this->register_form_style_controls();
		$this->register_input_fields_style_controls();
		$this->register_button_style_controls();
		$this->register_clickto_expand_style_controls();

	}


	/**
	 * Register the widget controls.
  *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function _register_controls()
	{
		$this->register_form_source_controls();
		$this->register_form_layout_controls();
		$this->register_form_field_controls();
		$this->register_form_button_controls();
		$this->register_clickto_expand_button_controls();
		
		$this->register_action_after_submit_controls();


		//-------style tab--------
		$this->register_form_style_controls();
		$this->register_input_fields_style_controls();
		$this->register_button_style_controls();
		$this->register_clickto_expand_style_controls();

	}

	/**
	 * Registers form source controls in Elementor.
	 * This function sets up controls for selecting the source of an opt-in form.
	 *
	 * @since 2.8.15
	 */
	protected function register_form_source_controls() {
		$this->start_controls_section(
			'wpfnl_optin_form_source_controls', array(
				'label' => __( 'Opt-in Form Source', 'wpfnl' ),
			)
		);

		$this->add_control(
			'optin_form_source',
			[
				'label'   => __( 'Form Source', 'wpfnl' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'wpfnl_forms',
				'options' => [
					'wpfnl_forms'    => __( 'WPFunnels', 'wpfnl' ),
					'mailmint_forms' => __( 'Mail Mint', 'wpfnl' ),
				],
			]
		);

		$mailmint_forms = Wpfnl_Widgets_Manager::get_mailmint_forms();

		$this->add_control(
			'mailmint_form_id',
			[
				'label'   => __( 'Choose form', 'wpfnl' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => array_key_first( $mailmint_forms ),
				'options' => $mailmint_forms,
				'condition' => [ 'optin_form_source' => 'mailmint_forms' ]
			]
		);

		if( \WPFunnels\Integrations\Helper::maybe_enabled() ){
			$this->add_control(
				'enable_mm_contact',
				[
					'label' => __('Collect Leads In Mail Mint', 'wpfnl'),
					'type' => Controls_Manager::SWITCHER,
					'label_on' => __( 'Yes', 'wpfnl' ),
					'label_off' => __( 'No', 'wpfnl' ),
					'return_value' => 'yes',
					'default' => '',
					'condition' => [
						'optin_form_source' => 'wpfnl_forms'
					]
				]
			);
	
			$this->add_control(
				'mm_contact_status',
				[
					'label' => __( 'Contact Status', 'wpfnl' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'default' => 'subscribed',
					'options' =>[
						'pending' => __('Pending', 'wpfnl'),
						'subscribed' => __('Subscribed', 'wpfnl'),
						'unsubscribed' => __('Unsubscribed', 'wpfnl'),
					],
					'condition' => [
						'enable_mm_contact' => 'yes',
					]
				]
			);
	
			$this->add_control(
				'mm_lists',
				[
					'label' => __( 'Select List', 'wpfnl' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'default' => '',
					'options' => \WPFunnels\Integrations\Helper::get_lists(),
					
					'condition' => [
						'enable_mm_contact' => 'yes',
					]
				]
			);
	
			$this->add_control(
				'mm_tags',
				[
					'label' => __( 'Select Tag', 'wpfnl' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'default' => '',
					'options' => \WPFunnels\Integrations\Helper::get_tags(),
					
					'condition' => [
						'enable_mm_contact' => 'yes',
					]
				]
			);
		}else{
			$this->add_control(
				'mailmaint_install',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => sprintf( __( 'Manage your leads easily using Mail Mint. <a href="https://wordpress.org/plugins/mail-mint/" target="_blank" rel="noopener">Try it now</a>.', 'wpfnl' )),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
					'condition' => [ 'optin_form_source' => 'wpfnl_forms' ]
				)
			);
		}

		

		$this->end_controls_section();
	}

	/**
     * Register Form Layout Controls.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function register_form_layout_controls(){
		$this->start_controls_section(
			'wpfnl_optin_form_layout_controls', array(
				'label' => __('Form Layout', 'wpfnl'),
				'condition' => [
					'optin_form_source' => 'wpfnl_forms'
				]
			)
		);

		$this->add_control(
			'optin_form_type',
			[
				'label' => __( 'Opt-in Form Type', 'wpfnl' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'general-optin',
				'options' => [
					'general-optin' => __( 'General Opt-in', 'wpfnl' ),
					'clickto-expand-optin'  => __('Click To Expand', 'wpfnl'),
				],
			]
		);

		$this->add_control(
			'optin_form_layout',
			[
				'label' => __( 'Form Style', 'wpfnl' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => __( 'Default Style', 'wpfnl' ),
					'form-style1'  => __('Form Style-1', 'wpfnl'),
					'form-style2' => __('Form Style-2', 'wpfnl'),
					'form-style3' => __('Form Style-3', 'wpfnl'),
					'form-style4' => __('Form Style-4', 'wpfnl'),
				],
			]
		);


		$this->end_controls_section();
	}


	/**
     * Register Form Controls.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function register_form_field_controls(){
		$this->start_controls_section(
			'wpfnl_optin_form_field_controls', array(
				'label' => __('Form Fields', 'wpfnl'),
				'condition' => [
					'optin_form_source' => 'wpfnl_forms'
				]
			)
		);

		//------Email------
		$this->add_control(
			'email_label',
			[
				'label' => __('Email Label', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Email', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'field_label' => 'yes',
				],
			]
		);

		$this->add_control(
			'email_placeholder',
			[
				'label' => __('Email Placeholder Text', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Email', 'wpfnl' ),
				'label_block' => true,
				'separator' => 'after'
			]
		);

		//----first name----
		$this->add_control(
			'first_name',
			[
				'label' => __('First Name', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);
		$this->add_control(
			'first_name_label',
			[
				'label' => __('First Name Label', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'First Name', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'first_name' => 'yes',
					'field_label' => 'yes',
				],
			]
		);
		$this->add_control(
			'first_name_placeholder',
			[
				'label' => __('First Name Placeholder Text', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'First Name', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'first_name' => 'yes',
				],
			]
		);
		$this->add_control(
			'is_required_name',
			[
				'label' => __('Mark First Name As Required', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'wpfnl' ),
				'label_off' => __( 'No', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'first_name' => 'yes',
				],
				'separator' => 'after'
			]
		);


		//---last name---
		$this->add_control(
			'last_name',
			[
				'label' => __('Last Name', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);
		$this->add_control(
			'last_name_label',
			[
				'label' => __('Last Name Label', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Last Name', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'last_name' => 'yes',
					'field_label' => 'yes',
				],
			]
		);
		$this->add_control(
			'last_name_placeholder',
			[
				'label' => __('Last Name Placeholder Text', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Last Name', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'last_name' => 'yes',
				],
			]
		);
		$this->add_control(
			'is_required_last_name',
			[
				'label' => __('Mark Last Name As Required', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'wpfnl' ),
				'label_off' => __( 'No', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'last_name' => 'yes',
				],
				'separator' => 'after'
			]
		);


		//------phone------
		$this->add_control(
			'phone',
			[
				'label' => __('Phone', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);
		$this->add_control(
			'phone_label',
			[
				'label' => __('Phone Label', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Phone', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'phone' => 'yes',
					'field_label' => 'yes',
				],
			]
		);
		$this->add_control(
			'phone_placeholder',
			[
				'label' => __('Phone Placeholder Text', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Phone', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'phone' => 'yes',
				],
			]
		);
		$this->add_control(
			'is_required_phone',
			[
				'label' => __('Mark Phone As Required', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'wpfnl' ),
				'label_off' => __( 'No', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'phone' => 'yes',
				],
				'separator' => 'after'
			]
		);


		//----website url----
		$this->add_control(
			'website_url',
			[
				'label' => __('Website Url', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);
		$this->add_control(
			'website_url_label',
			[
				'label' => __('Website Url Label', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Website Url', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'website_url' => 'yes',
					'field_label' => 'yes',
				],
			]
		);
		$this->add_control(
			'website_url_placeholder',
			[
				'label' => __('Website Url Placeholder Text', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Website Url', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'website_url' => 'yes',
				],
			]
		);
		$this->add_control(
			'is_required_website_url',
			[
				'label' => __('Mark Website Url As Required', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'wpfnl' ),
				'label_off' => __( 'No', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'website_url' => 'yes',
				],
				'separator' => 'after'
			]
		);


		//-----message------
		$this->add_control(
			'message',
			[
				'label' => __('Message', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);
		$this->add_control(
			'message_label',
			[
				'label' => __('Message Label', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Message', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'message' => 'yes',
					'field_label' => 'yes',
				],
			]
		);
		$this->add_control(
			'message_placeholder',
			[
				'label' => __('Message Placeholder Text', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Write your message here...', 'wpfnl' ),
				'label_block' => true,
				'condition' => [
					'message' => 'yes',
				],
			]
		);
		$this->add_control(
			'is_required_message',
			[
				'label' => __('Mark Message As Required', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'wpfnl' ),
				'label_off' => __( 'No', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'message' => 'yes',
				],
				'separator' => 'after'
			]
		);


		//-----acceptance checkbox-----
		$this->add_control(
			'acceptance_checkbox',
			[
				'label' => __('Acceptance Checkbox', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);
		$this->add_control(
			'acceptance_checkbox_text',
			[
				'label' => __('Acceptance Text', 'wpfnl'),
				'type' => \Elementor\Controls_Manager::WYSIWYG,
				'label_block' => true,
				'default' => __('I have read and agree the Terms & Condition.', 'wpfnl'),
				'condition' => [
					'acceptance_checkbox' => 'yes',
				],
			]
		);
		$this->add_control(
			'is_required_acceptance',
			[
				'label' => __('Mark Acceptance As Required', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'wpfnl' ),
				'label_off' => __( 'No', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'acceptance_checkbox' => 'yes',
				],
				'separator' => 'after'
			]
		);

		$this->add_control(
			'input_fields_icon',
			[
				'label' => __('Show Input Field Icon(s)', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'field_label',
			[
				'label' => __('Show Field Label(s)', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'required_mark',
			[
				'label' => __('Show Required Mark', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [
					'field_label' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}


	/**
     * Register Button Controls.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function register_form_button_controls(){
		$this->start_controls_section(
			'wpfnl_optin_form_button_controls', array(
				'label' => __('Submit Button', 'wpfnl'),
				'condition' => [
					'optin_form_source' => 'wpfnl_forms'
				]
			)
		);

		$this->add_control(
			'btn_text',
			[
				'label' => __('Text', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __('Submit', 'wpfnl'),
				'placeholder' => __('Submit', 'wpfnl'),
			]
		);

		$this->add_responsive_control(
			'btn_align',
			[
				'label' => __('Alignment', 'wpfnl'),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __('Left', 'wpfnl'),
						'icon' => 'fa fa-align-left',
					],
					'center' => [
						'title' => __('Center', 'wpfnl'),
						'icon' => 'fa fa-align-center',
					],
					'right' => [
						'title' => __('Right', 'wpfnl'),
						'icon' => 'fa fa-align-right',
					],
					'justify' => [
						'title' => __('Justified', 'wpfnl'),
						'icon' => 'fa fa-align-justify',
					],
				],
				'default' => '',
				'condition' => [
					'optin_form_layout[value]!' => 'form-style1',
				],
			]
		);

		$this->add_control(
			'btn_icon',
			[
				'label' => __('Icon', 'wpfnl'),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
			]
		);

		$this->add_control(
			'btn_icon_align',
			[
				'label' => __('Icon Position', 'wpfnl'),
				'type' => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left' => __('Before Text', 'wpfnl'),
					'right' => __('After Text', 'wpfnl'),
				],
				'condition' => [
					'btn_icon[value]!' => '',
				],
			]
		);

		$this->add_control(
			'btn_icon_indent',
			[
				'label' => __('Icon Spacing', 'wpfnl'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'max' => 50,
					],
				],
				'condition' => [
					'btn_icon!' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-button .elementor-align-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .elementor-button .elementor-align-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
			]
		);



		$this->end_controls_section();
	}


	/**
     * Register Click to Expand Button Controls.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function register_clickto_expand_button_controls(){
		$this->start_controls_section(
			'wpfnl_optin_clickto_expand_control', array(
				'label' => __('Click to Expand Button', 'wpfnl'),
				'condition' => [
					'optin_form_type' => 'clickto-expand-optin',
					'optin_form_source' => 'wpfnl_forms'
				],
			)
		);

		$this->add_control(
			'clickto_expand_btn_text',
			[
				'label' => __('Text', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => __('Click to Expand', 'wpfnl'),
				'placeholder' => __('Write the button title', 'wpfnl'),
			]
		);

		$this->add_responsive_control(
			'clickto_expand_btn_align',
			[
				'label' => __('Alignment', 'wpfnl'),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __('Left', 'wpfnl'),
						'icon' => 'fa fa-align-left',
					],
					'center' => [
						'title' => __('Center', 'wpfnl'),
						'icon' => 'fa fa-align-center',
					],
					'right' => [
						'title' => __('Right', 'wpfnl'),
						'icon' => 'fa fa-align-right',
					],
					'justify' => [
						'title' => __('Justified', 'wpfnl'),
						'icon' => 'fa fa-align-justify',
					],
				],
				'default' => '',
			]
		);

		$this->add_control(
			'clickto_expand_btn_icon',
			[
				'label' => __('Icon', 'wpfnl'),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
			]
		);

		$this->add_control(
			'clickto_expand_btn_icon_align',
			[
				'label' => __('Icon Position', 'wpfnl'),
				'type' => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left' => __('Before Text', 'wpfnl'),
					'right' => __('After Text', 'wpfnl'),
				],
				'condition' => [
					'clickto_expand_btn_icon[value]!' => '',
				],
			]
		);

		$this->add_control(
			'clickto_expand_btn_icon_indent',
			[
				'label' => __('Icon Spacing', 'wpfnl'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'max' => 50,
					],
				],
				'condition' => [
					'clickto_expand_btn_icon!' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .clickto-expand-btn.elementor-button .elementor-align-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .clickto-expand-btn.elementor-button .elementor-align-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
			]
		);


		$this->end_controls_section();
	}


	/**
     * Register Action After Submit Controls.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function register_action_after_submit_controls(){
		$this->start_controls_section(
			'wpfnl_action_after_submit_controls', array(
				'label' => __('Action After Submission', 'wpfnl'),
				'condition' => [
					'optin_form_source' => 'wpfnl_forms'
				]
			)
		);

		$this->add_control(
			'admin_email',
			[
				'label' => __('Admin Email', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default' => Wpfnl_functions::get_optin_settings('sender_email'),
				'separator' => 'before',
				'optin_form_source' => 'wpfnl_forms'
			]
		);

		$this->add_control(
			'admin_email_subject',
			[
				'label' => __('Admin Email Subject', 'wpfnl'),
				'type' => Controls_Manager::TEXT,
				'default' => Wpfnl_functions::get_optin_settings('email_subject'),
				'label_block' => true,
				'optin_form_source' => 'wpfnl_forms'
			]
		);

		$this->add_control(
			'notification_text',
			[
				'label' => __( 'Notification Text', 'wpfnl' ),
				'type' => Controls_Manager::TEXTAREA,
				'rows' => 5,
				'default' => 'Thank you! Your form was submitted successfully!',
				'placeholder' => __( 'Type notification texts here', 'wpfnl' ),
				'optin_form_source' => 'wpfnl_forms'
			]
		);

		$this->add_control(
			'post_action',
			[
				'label' => __( 'Other action', 'wpfnl' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'notification',
				'options' => [
					'notification'  => __( 'None', 'wpfnl' ),
					'redirect_to' 	=> __( 'Redirect to url', 'wpfnl' ),
					'next_step' => __( 'Next Step', 'wpfnl' ),
				],
				'optin_form_source' => 'wpfnl_forms'
			]
		);

		$this->add_control(
			'redirect_url',
			[
				'label' => __( 'Redirect url', 'wpfnl' ),
				'type' => Controls_Manager::URL,
				'placeholder' => __( 'https://your-link.com', 'wpfnl' ),
				'show_external' => true,
				'default' => [
					'url' => '',
					'is_external' => false,
					'nofollow' => true,
				],
				'condition' => [
					'post_action' => 'redirect_to',
				]
			]
		);

		$this->add_control(
			'data_to_checkout',
			[
				'label' => __('Carry data to the Next Form', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'wpfnl' ),
				'label_off' => __( 'No', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'optin_form_source' => 'wpfnl_forms'
			]
		);

		$this->add_control(
			'allow_registration',
			[
				'label' => __('Register User As Subscriber', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'optin_form_source' => 'wpfnl_forms'
			]
		);
		$this->add_control(
			'allow_user_permission',
			[
				'label' => __('Registration Permission', 'wpfnl'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'wpfnl' ),
				'label_off' => __( 'Hide', 'wpfnl' ),
				'return_value' => 'yes',
				'default' => '',
				'condition' => [
					'allow_registration' => 'yes',
				],
			]
		);
		$this->add_control(
			'allow_user_registration_permission_text',
			[
				'label' => __('Registration Permission Text', 'wpfnl'),
				'type' => \Elementor\Controls_Manager::WYSIWYG,
				'label_block' => true,
				'default' => __('I agree to be registered as a subscriber.', 'wpfnl'),
				'condition' => [
					'allow_user_permission' => 'yes',
					'allow_registration' => 'yes',
				],
			]
		);


		$this->end_controls_section();
	}

	/**
     * Register Label Style Controls.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function register_form_style_controls(){
	    $this->start_controls_section(
		    'form_section_style', [
			    'label'     => __( 'Form', 'wpfnl' ),
			    'tab'       => Controls_Manager::TAB_STYLE,
			    'condition' => [
				    'optin_form_source' => 'wpfnl_forms'
			    ]
		    ]
	    );

		$this->add_control(
			'row_spacing',
			[
				'label' => __('Row Spacing', 'wpfnl'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		//--------start label style------
		$this->add_control(
            'label_style',
            [
                'label' => __('Label', 'wpfnl'),
                'type' => Controls_Manager::HEADING,
                'label_block' => true,
				'separator' => 'before',
            ]
        );
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'label_typography',
				'label' => 'Typography',
				'selector' => '{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group > label',
			]
		);

		$this->add_control(
			'label_color',
			[
				'label' => __('Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group > label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'label_spacing',
			[
				'label' => __('Spacing', 'wpfnl'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group:not(.acceptance-checkbox):not(.user-registration-checkbox) > label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);
		//--------end label style------


		$this->add_control(
			'tnc_link_color',
			[
				'label' => __('Link Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group > label a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'checkbox_active_color',
			[
				'label' => __('Checkbox Active Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type="checkbox"]:checked + label .check-box' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'checkbox_size',
			[
				'label' => __('Checkbox Size', 'wpfnl'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 18,
						'max' => 40,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group > label .check-box' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'checkbox_spacing',
			[
				'label' => __('Checkbox Spacing', 'wpfnl'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'max' => 60,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group.acceptance-checkbox > label,
					 {{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group.user-registration-checkbox > label' => 'padding-left: {{SIZE}}{{UNIT}};',
				],
			]
		);
		//--------end terms and condition style------

		$this->end_controls_section();
	}


	/**
     * Register Input field Style Controls.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function register_input_fields_style_controls(){
		$this->start_controls_section(
			'inputs_section_style', [
				'label' => __('Input Fields', 'wpfnl'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'optin_form_source' => 'wpfnl_forms'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'inputs_typography',
				'label' => 'Typography',
				'selector' => '{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=text],
				{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=email],
				{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group textarea',
			]
		);

		$this->add_control(
			'input_text_color',
			[
				'label' => __('Text Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=text],
					 {{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=email],
					 {{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group textarea' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'input_bg_color',
			[
				'label' => __('Background Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=text],
					 {{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=email],
					 {{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group textarea' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'input_box_shadow',
				'selector' => '{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=text],
								{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=email],
								{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group textarea',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'input_border',
				'label' => __('Border', 'wpfnl'),
				'placeholder' => '1px',
				'default' => '1px',
				'selector' => '{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=text],
								{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=email],
								{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group textarea',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'input_border_radius',
			[
				'label' => __('Border Radius', 'wpfnl'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=text],
					 {{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=email],
					 {{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'input_padding',
			[
				'label' => __('Padding', 'wpfnl'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=text],
					 {{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group input[type=email],
					 {{WRAPPER}} .wpfnl-optin-form .wpfnl-optin-form-group textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}


	/**
     * Register Button Style Controls.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function register_button_style_controls(){
		$this->start_controls_section(
			'btn_section_style', [
				'label' => __('Submit Button', 'wpfnl'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'optin_form_source' => 'wpfnl_forms'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'btn_typography',
				'label' => 'Typography',
				'selector' => '{{WRAPPER}} button.elementor-button',
			]
		);

		$this->start_controls_tabs('btn_color_style');
		$this->start_controls_tab(
			'tab_button_normal',
			[
				'label' => __('Normal', 'wpfnl'),
			]
		);

		$this->add_control(
			'btn_text_color',
			[
				'label' => __('Text Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} button.elementor-button, {{WRAPPER}} .elementor-button' => 'color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'btn_bg_color',
			[
				'label' => __('Background Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} button.elementor-button, {{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'btn_box_shadow',
				'selector' => '{{WRAPPER}} button.elementor-button',
			]
		);

		$this->end_controls_tab();
		//---end normal style----

		$this->start_controls_tab(
			'tab_button_hover',
			[
				'label' => __('Hover', 'wpfnl'),
			]
		);

		$this->add_control(
			'btn_hover_text_color',
			[
				'label' => __('Text Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} button.elementor-button:hover, {{WRAPPER}} .elementor-button:hover' => 'color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'btn_hover_bg_color',
			[
				'label' => __('Background Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} button.elementor-button:hover, {{WRAPPER}} .elementor-button:hover' => 'background-color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'btn_hover_box_shadow',
				'selector' => '{{WRAPPER}} button.elementor-button:hover',
			]
		);

		$this->end_controls_tab();
		//---end hover style----

		$this->end_controls_tabs();
		//---end butotn color style tab----

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'btn_border',
				'label' => __('Border', 'wpfnl'),
				'placeholder' => '1px',
				'default' => '1px',
				'selector' => '{{WRAPPER}} button.elementor-button',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'border_radius',
			[
				'label' => __('Border Radius', 'wpfnl'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} button.elementor-button, {{WRAPPER}} .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'btn_padding',
			[
				'label' => __('Padding', 'wpfnl'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} button.elementor-button, {{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}


	/**
     * Register click to expand Button Style Controls.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function register_clickto_expand_style_controls(){
		$this->start_controls_section(
			'clickto_expand_btn_section_style',
			[
				'label' => __('Click to Expand Button', 'wpfnl'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'optin_form_type' => 'clickto-expand-optin',
					'optin_form_source' => 'wpfnl_forms'
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'clickto_expand_btn_typography',
				'label' => 'Typography',
				'selector' => '{{WRAPPER}} button.elementor-button.clickto-expand-btn',
			]
		);

		$this->start_controls_tabs('clickto_expand_btn_color_style');
		$this->start_controls_tab(
			'clickto_expand_tab_button_normal',
			[
				'label' => __('Normal', 'wpfnl'),
			]
		);

		$this->add_control(
			'clickto_expand_btn_text_color',
			[
				'label' => __('Text Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} button.elementor-button.clickto-expand-btn,
					 {{WRAPPER}} .elementor-button.clickto-expand-btn' => 'color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'clickto_expand_btn_bg_color',
			[
				'label' => __('Background Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} button.elementor-button.clickto-expand-btn,
					 {{WRAPPER}} .elementor-button.clickto-expand-btn' => 'background-color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'clickto_expand_btn_box_shadow',
				'selector' => '{{WRAPPER}} button.elementor-button.clickto-expand-btn',
			]
		);

		$this->end_controls_tab();
		//---end normal style----

		$this->start_controls_tab(
			'clickto_expand_tab_button_hover',
			[
				'label' => __('Hover', 'wpfnl'),
			]
		);

		$this->add_control(
			'clickto_expand_btn_hover_text_color',
			[
				'label' => __('Text Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} button.elementor-button.clickto-expand-btn:hover,
					 {{WRAPPER}} .elementor-button.clickto-expand-btn:hover' => 'color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'clickto_expand_btn_hover_bg_color',
			[
				'label' => __('Background Color', 'wpfnl'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} button.elementor-button.clickto-expand-btn:hover,
					 {{WRAPPER}} .elementor-button.clickto-expand-btn:hover' => 'background-color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'clickto_expand_btn_hover_box_shadow',
				'selector' => '{{WRAPPER}} button.elementor-button.clickto-expand-btn:hover',
			]
		);

		$this->end_controls_tab();
		//---end hover style----

		$this->end_controls_tabs();
		//---end butotn color style tab----

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'clickto_expand_btn_border',
				'label' => __('Border', 'wpfnl'),
				'placeholder' => '1px',
				'default' => '1px',
				'selector' => '{{WRAPPER}} button.elementor-button.clickto-expand-btn',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'clickto_expand_border_radius',
			[
				'label' => __('Border Radius', 'wpfnl'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} button.elementor-button.clickto-expand-btn,
					{{WRAPPER}} .elementor-button.clickto-expand-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'clickto_expand_btn_padding',
			[
				'label' => __('Padding', 'wpfnl'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} button.elementor-button.clickto-expand-btn,
					 {{WRAPPER}} .elementor-button.clickto-expand-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}


	/**
	 * Get wrapper classes
	 *
	 * @return array
	 */
	protected function get_wrapper_classes() {
		$settings 	= $this->get_settings_for_display();

		if( 'clickto-expand-optin' == $settings['optin_form_type'] ) {
			return array( 'wpfnl', 'wpfnl-optin-form', 'wpfnl-elementor-optin-form-wrapper', 'clickto-expand-optin');

		}else {
			return array( 'wpfnl', 'wpfnl-optin-form', 'wpfnl-elementor-optin-form-wrapper');

		}
	}


	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 2.1.8
	 *
	 * @access protected
	 */
	protected function render()
	{
		$settings 	= $this->get_settings_for_display();
		$classes 	= $this->get_wrapper_classes();
		$this->add_render_attribute(
			[
				'wrapper' => [
					'class' => [
						'elementor-form-fields-wrapper',
					],
				],
			]
		);

		if ( !isset( $settings[ 'optin_form_source' ] ) || ( !empty( $settings[ 'optin_form_source' ] ) && 'mailmint_forms' !== $settings[ 'optin_form_source' ] ) ) {
			$this->render_wpfnl_form( $settings, $classes );
		}
		elseif ( !empty( $settings[ 'optin_form_source' ] ) && !empty( $settings[ 'mailmint_form_id' ] ) ) {
			ob_start();
			Wpfnl_Widgets_Manager::render_mailmint_form( $settings[ 'mailmint_form_id' ] );
			$mailmint_form = ob_get_clean();
			$replace = 'id="mrm-form">';
			$replace .= '<input type="hidden" name="widget_id" value="'.$this->get_id().'"/>';
			if ( !empty( $settings[ 'data_to_checkout' ] ) && 'yes' == $settings[ 'data_to_checkout' ] ) {
				$replace .= '<input type="hidden" name="data_to_checkout" value="yes"/>';
			}
			if ( !empty( $settings[ 'allow_registration' ] ) && 'yes' == $settings[ 'allow_registration' ] ) {
				$replace .= '<input type="hidden" name="optin_allow_registration" value="yes"/>';
			}
			echo str_replace( 'id="mrm-form">', $replace, $mailmint_form );
		}
	}

	/**
	 * Render the widget output on the frontend for WPFunnels' default forms.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 2.8.15
	 *
	 * @access protected
	 */
	protected function render_wpfnl_form( $settings, $classes ) {
		$this->add_render_attribute('button', 'class', 'btn-optin elementor-button');
		$this->add_render_attribute('click-to-expand-button', 'class', 'elementor-button');

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
		?>
		<style>
			<?php if( '' == $settings['input_fields_icon'] ){ ?>
            .wpfnl-optin-form .wpfnl-optin-form-group input[type=text],
            .wpfnl-optin-form .wpfnl-optin-form-group input[type=email] {
                padding-right: 14px;
            }
			<?php } ?>
		</style>
		<?php echo $recaptch_script;  ?>

		<?php
		if( 'clickto-expand-optin' == $settings['optin_form_type'] ) {
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
				echo $is_recaptch_input;
				echo $token_input;
				echo $token_secret_key;
				?>
				<div class="wpfnl-optin-form-wrapper <?php echo $settings['optin_form_layout']; ?>" >
					<?php if( 'yes' == $settings['first_name'] ){ ?>
						<div class="wpfnl-optin-form-group first-name">

							<?php if( 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-first-name">
									<?php
									echo $settings['first_name_label'] ? $settings['first_name_label'] : __('First Name','wpfnl');

									if( 'yes' == $settings['required_mark'] && 'yes' == $settings['is_required_name'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
								<?php if( 'yes' == $settings['input_fields_icon'] ){ ?>
									<span class="field-icon">
										<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
									</span>
								<?php }
								$f_name_placeholder = isset($settings['first_name_placeholder']) ? $settings['first_name_placeholder'] : '';
								?>
								<input type="text" name="first_name" class="wpfnl-first-name" id="wpfnl-first-name" placeholder="<?php echo $f_name_placeholder; ?>" <?php echo 'yes' == $settings['is_required_name'] ? 'required' : ''; ?>/>
							</span>

						</div>
					<?php } ?>

					<?php if( 'yes' == $settings['last_name'] ){ ?>
						<div class="wpfnl-optin-form-group last-name">

							<?php if( 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-last-name">
									<?php
									echo $settings['last_name_label'] ? $settings['last_name_label'] : __('Last Name','wpfnl');

									if( 'yes' == $settings['required_mark'] && 'yes' == $settings['is_required_last_name'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
								<?php if( 'yes' == $settings['input_fields_icon'] ){ ?>
									<span class="field-icon">
										<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
									</span>
								<?php }
								$l_name_placeholder = isset($settings['last_name_placeholder']) ? $settings['last_name_placeholder'] : '';
								?>
								<input type="text" name="last_name" class="wpfnl-last-name" id="wpfnl-last-name" placeholder="<?php echo $l_name_placeholder;?>" <?php echo 'yes' == $settings['is_required_last_name'] ? 'required' : ''; ?>/>
							</span>
						</div>
					<?php } ?>

					<div class="wpfnl-optin-form-group email">
						<?php if( 'yes' == $settings['field_label'] ){ ?>
							<label for="wpfnl-email">
								<?php
								echo $settings['email_label'] ? $settings['email_label'] : __('Email','wpfnl');

								if( 'yes' == $settings['required_mark'] ){ ?>
									<span class="required-mark">*</span>
								<?php } ?>
							</label>
						<?php } ?>
						<span class="input-wrapper">
							<?php if( 'yes' == $settings['input_fields_icon'] ){ ?>
								<span class="field-icon">
									<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/email-open-icon.svg'; ?>" alt="icon">
								</span>
							<?php }
							$email_placeholder = isset($settings['email_placeholder']) ? $settings['email_placeholder'] : '';
							?>
							<input type="email" name="email" class="wpfnl-email" id="wpfnl-email" placeholder="<?php echo $email_placeholder; ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" required />
						</span>
					</div>

					<?php if( 'yes' == $settings['phone'] ){ ?>
						<div class="wpfnl-optin-form-group phone">

							<?php if( 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-phone">
									<?php
									echo $settings['phone_label'] ? $settings['phone_label'] : __('Phone','wpfnl');

									if( 'yes' == $settings['required_mark'] && 'yes' == $settings['is_required_phone'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
								<?php if( 'yes' == $settings['input_fields_icon'] ){ ?>
									<span class="field-icon">
										<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/phone.svg'; ?>" alt="icon">
									</span>
								<?php }
								$phone_placeholder = isset($settings['phone_placeholder']) ? $settings['phone_placeholder'] : '';
								?>
								<input type="text" name="phone" class="wpfnl-phone" id="wpfnl-phone" placeholder="<?php echo $phone_placeholder; ?>" <?php echo 'yes' == $settings['is_required_phone'] ? 'required' : ''; ?>/>
							</span>
						</div>
					<?php } ?>

					<?php if( 'yes' == $settings['website_url'] ){ ?>
						<div class="wpfnl-optin-form-group website-url">

							<?php if( 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-web-url">
									<?php
									echo $settings['website_url_label'] ? $settings['website_url_label'] : __('Website Url','wpfnl');

									if( 'yes' == $settings['required_mark'] && 'yes' == $settings['is_required_website_url'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
								<?php if( 'yes' == $settings['input_fields_icon'] ){ ?>
									<span class="field-icon">
										<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/web-url.svg'; ?>" alt="icon">
									</span>
								<?php }
								$weburl_placeholder = isset($settings['website_url_placeholder']) ? $settings['website_url_placeholder'] : '';
								?>
								<input type="text" name="web-url" class="wpfnl-web-url" id="wpfnl-web-url" pattern="(https?://)?.+" size="30" placeholder="<?php echo $weburl_placeholder; ?>" <?php echo 'yes' == $settings['is_required_website_url'] ? 'required' : ''; ?>/>
							</span>
						</div>
					<?php } ?>

					<?php if( 'yes' == $settings['message'] ){ ?>
						<div class="wpfnl-optin-form-group message">

							<?php if( 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-message">
									<?php
									echo $settings['message_label'] ? $settings['message_label'] : __('Message','wpfnl');

									if( 'yes' == $settings['required_mark'] && 'yes' == $settings['is_required_message'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php }

							$message_placeholder = isset($settings['message_placeholder']) ? $settings['message_placeholder'] : '';
							?>

							<span class="input-wrapper">
								<textarea name="message" class="wpfnl-message" id="wpfnl-message" cols="30" rows="3" placeholder="<?php echo $message_placeholder; ?>" <?php echo 'yes' == $settings['is_required_message'] ? 'required' : ''; ?> ></textarea>
							</span>
						</div>
					<?php } ?>

					<?php
					if( 'yes' == $settings['acceptance_checkbox'] ){
						?>
						<div class="wpfnl-optin-form-group acceptance-checkbox">
							<input type="checkbox" name="acceptance_checkbox" class="wpfnl-acceptance_checkbox" id="wpfnl-acceptance_checkbox-<?php echo esc_attr( $this->get_id() ); ?>" <?php echo 'yes' == $settings['is_required_acceptance'] ? 'required' : ''; ?> />
							<label for="wpfnl-acceptance_checkbox-<?php echo esc_attr( $this->get_id() ); ?>">
								<span class="check-box"></span>
								<?php
								echo $settings['acceptance_checkbox_text'] ? $settings['acceptance_checkbox_text'] : '';

								if( 'yes' == $settings['required_mark'] && 'yes' == $settings['is_required_acceptance'] ){
									echo '<span class="required-mark">*</span>';
								}
								?>
							</label>
						</div>
						<?php
					}
					?>

					<?php
					if( 'yes' == $settings['allow_registration']){
						?>
						<input type="hidden" name="optin_allow_registration" value="<?php echo esc_attr( 'yes' ); ?>"/>
						<?php
						if('yes' == $settings['allow_user_permission']){
							?>
							<div class="wpfnl-optin-form-group user-registration-checkbox">
								<input type="checkbox" name="user_registration_checkbox" class="wpfnl-registration_checkbox" id="wpfnl-registration_checkbox-<?php echo esc_attr( $this->get_id() ); ?>" required/>
								<label for="wpfnl-registration_checkbox-<?php echo esc_attr( $this->get_id() ); ?>">
									<span class="check-box"></span>
									<?php
									echo $settings['allow_user_registration_permission_text'] ? $settings['allow_user_registration_permission_text'] : '';
									?>
									<span class="required-mark">*</span>
								</label>
							</div>
							<?php
						}
					}
					?>
					<?php
					if( 'yes' == $settings['data_to_checkout']){
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
	}

	/**
	 * Render button text.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function render_text()
	{
		$settings = $this->get_settings();
		$migrated = isset($settings['__fa4_migrated']['btn_icon']);
		$is_new = empty($settings['icon']) && Icons_Manager::is_migration_allowed();

		if (!$is_new && empty($settings['btn_icon_align'])) {
			$settings['btn_icon_align'] = $this->get_settings('btn_icon_align');
		}

		$this->add_render_attribute([
			'content-wrapper' => [
				'class' => 'elementor-button-content-wrapper',
			],
			'icon-align' => [
				'class' => [
					'elementor-button-icon',
					'elementor-align-icon-' . $settings['btn_icon_align'],
				],
			],
			'text' => [
				'class' => 'elementor-button-text',
			],
		]);

		// $this->add_render_attribute('content-wrapper', 'class', 'elementor-button-content-wrapper');
		//$this->add_render_attribute('text', 'class', 'elementor-button-text');

		$this->add_inline_editing_attributes('text', 'none');
		?>
		<span <?php echo $this->get_render_attribute_string('content-wrapper'); ?>>

            <?php if (!empty($settings['icon']) || !empty($settings['btn_icon']['value'])) : ?>
				<span <?php echo $this->get_render_attribute_string('icon-align'); ?>>
                    <?php if ($is_new || $migrated) :
						Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']);
					else : ?>
						<i class="<?php echo esc_attr($settings['icon']); ?>" aria-hidden="true"></i>
					<?php endif; ?>
                </span>
			<?php endif; ?>

            <span <?php echo $this->get_render_attribute_string('text'); ?>><?php echo $settings['btn_text']; ?></span>
        </span>
		<?php
	}

	/**
	 * Render click to expand button text.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function render_clickto_expand_text()
	{
		$settings = $this->get_settings();
		$migrated = isset($settings['__fa4_migrated']['clickto_expand_btn_icon']);
		$is_new = empty($settings['icon']) && Icons_Manager::is_migration_allowed();

		if (!$is_new && empty($settings['clickto_expand_btn_icon_align'])) {
			$settings['clickto_expand_btn_icon_align'] = $this->get_settings('clickto_expand_btn_icon_align');
		}

		$this->add_render_attribute([
			'expand-btn-content-wrapper' => [
				'class' => 'elementor-button-content-wrapper',
			],
			'expand-btn-icon-align' => [
				'class' => [
					'elementor-button-icon',
					'elementor-align-icon-' . $settings['clickto_expand_btn_icon_align'],
				],
			],
			'expand-btn-text' => [
				'class' => 'elementor-button-text',
			],
		]);

		//$this->add_render_attribute('text', 'class', 'elementor-button-text');

		$this->add_inline_editing_attributes('text', 'none');
		?>
		<span <?php echo $this->get_render_attribute_string('expand-btn-content-wrapper'); ?>>

            <?php if (!empty($settings['icon']) || !empty($settings['clickto_expand_btn_icon']['value'])) : ?>
				<span <?php echo $this->get_render_attribute_string('expand-btn-icon-align'); ?>>
                    <?php if ($is_new || $migrated) :
						Icons_Manager::render_icon($settings['clickto_expand_btn_icon'], ['aria-hidden' => 'true']);
					else : ?>
						<i class="<?php echo esc_attr($settings['icon']); ?>" aria-hidden="true"></i>
					<?php endif; ?>
                </span>
			<?php endif; ?>

            <span <?php echo $this->get_render_attribute_string('expand-btn-text'); ?>><?php echo $settings['clickto_expand_btn_text']; ?></span>
        </span>
		<?php
	}
}
