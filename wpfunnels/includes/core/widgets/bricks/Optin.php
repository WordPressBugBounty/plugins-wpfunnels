<?php
/**
 * Namespace for the Optin class.
 * This class is part of the WPFunnels\Widgets\Bricks namespace.
 */
namespace WPFunnels\Widgets\Bricks;

require_once get_template_directory() . '/includes/elements/base.php';

use \Bricks\Element;
use WPFunnels\Widgets\Wpfnl_Widgets_Manager;
use WPFunnels\Wpfnl_functions;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly


/**
 * Class Optin
 * 
 * Represents an optin element.
 * Extends the Element class.
 * 
 * @package WPFunnels\Widgets\Bricks
 */
class Optin extends Element
{

    // Element properties
    public $category     = 'wpfunnels'; // Use predefined element category 'general'
    public $name         = 'wpfnl_optin'; // Make sure to prefix your elements
    public $icon         = 'fa-solid fa-cart-shopping'; // Themify icon font class
    public $scripts      = []; // Script(s) run when element is rendered on frontend or updated in builder


     /**
     * Return localised element label
     * 
     * @return string
     * @since 3.1.0
     * @access public
     */
    public function get_label()
    {
        return esc_html__('Opt-in', 'wpfnl');
    }

    
    /**
     * Sets the control for the source of the opt-in form.
	 * 
	 * @since 3.1.0
     *
     * @access public
     */
    public function optin_form_source_control(){
        // Group: Opt-in form Source
        $this->controls['optin_form_source'] = [
			'tab' => 'content',
            'group' => 'optin_form_source_group',
			'label' => esc_html__( 'Form Source', 'wpfnl' ),
			'type' => 'select',
			'options' => [
			  'wpfnl_forms' => 'WPFunnels',
			  'mailmint_forms' => 'Mail Mint',
			],
			'inline' => false,
			'default' => 'wpfnl_forms',
		];

        $mailmint_forms = Wpfnl_Widgets_Manager::get_mailmint_forms();

        $this->controls['mailmint_form_id'] = [
			'tab' => 'content',
            'group' => 'optin_form_source_group',
			'label' => esc_html__( 'Choose form', 'wpfnl' ),
			'type' => 'select',
			'default' => array_key_first( $mailmint_forms ),
			'options' => $mailmint_forms,
			'inline' => false,
            'required'    => [ 'optin_form_source', '=', 'mailmint_forms' ],
		];

		if( \WPFunnels\Integrations\Helper::maybe_enabled() ) {
			$this->controls['enable_mm_contact'] = [
				'tab'   => 'content',
				'group' => 'optin_form_source_group',
				'label' => esc_html__( 'Send leads to Mail Mint', 'wpfnl' ),
				'type'  => 'checkbox',
			];

			$this->controls['mm_contact_status'] = [
				'tab' => 'content',
				'group' => 'optin_form_source_group',
				'label' => esc_html__( 'Contact Status', 'wpfnl' ),
				'type' => 'select',
				'options' => [
					'pending' => __('Pending', 'wpfnl'),
					'subscribed' => __('Subscribed', 'wpfnl'),
					'unsubscribed' => __('Unsubscribed', 'wpfnl'),
				],
				'inline' => true,
				'default' => 'subscribed',
				'required'    => [ 'enable_mm_contact'],
			];

			$this->controls['mm_lists'] = [
				'tab' => 'content',
				'group' => 'optin_form_source_group',
				'label' => esc_html__( 'Select List', 'wpfnl' ),
				'type' => 'select',
				'options' => \WPFunnels\Integrations\Helper::get_lists(),
				'inline' => true,
				'default' => '',
				'required'    => [ 'enable_mm_contact'],
			];

			$this->controls['mm_tags'] = [
				'tab' => 'content',
				'group' => 'optin_form_source_group',
				'label' => esc_html__( 'Select Tag', 'wpfnl' ),
				'type' => 'select',
				'options' => \WPFunnels\Integrations\Helper::get_tags(),
				'inline' => true,
				'default' => '',
				'required'    => [ 'enable_mm_contact'],
			];
		}else{
			$this->controls['mm_install'] = [
                'tab' => 'content',
                'group' => 'optin_form_source_group',
                'content' => sprintf( __( 'Manage your leads easily using Mail Mint. <a href="https://wordpress.org/plugins/mail-mint/" target="_blank" rel="noopener">Try it now</a>.', 'wpfnl' )),
                'type' => 'info',
                'required'    => [ 'optin_form_source', '=', 'wpfnl_forms' ],
            ];
		}
    }


    /**
     * Sets the control for the optin form type.
	 * 
	 * @since 3.1.0
     *
     * @access public
     */
    public function optin_form_type_control(){
        // Group: Opt-in form Type
        $this->controls['optin_form_type'] = [
			'tab' => 'content',
            'group' => 'form_layout_group',
			'label' => esc_html__( 'Opt-in Form Type', 'wpfnl' ),
			'type' => 'select',
			'options' => [
			  'general-optin' => 'General Opt-in',
			  'clickto-expand-optin' => 'Click To Expand',
			],
			'inline' => true,
			'default' => 'general-optin',
		];

        $this->controls['optin_form_layout'] = [
			'tab' => 'content',
            'group' => 'form_layout_group',
			'label' => esc_html__( 'Form Style', 'wpfnl' ),
			'type' => 'select',
			'options' => [
                '' => __( 'Default Style', 'wpfnl' ),
                'form-style1'  => __('Form Style-1', 'wpfnl'),
                'form-style2' => __('Form Style-2', 'wpfnl'),
                'form-style3' => __('Form Style-3', 'wpfnl'),
                'form-style4' => __('Form Style-4', 'wpfnl'),
            ],
			'inline' => true,
			'default' => '',
		];
    }


    /**
     * Sets the control for the optin form field.
	 * 
	 * @since 3.1.0
     *
     * @access public
     */
    public function optin_form_field_control(){
        // Group: Form fields

        // Label condition
        $this->controls['field_label'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Show Field Label', 'wpfnl' ),
			'type'  => 'checkbox',
		];

        //Icon condition
        $this->controls['input_fields_icon'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Show Input Field Icon', 'wpfnl' ),
			'type'  => 'checkbox',
            'default' => true
		];


        $this->controls['labelSeparator'] = [
			'type'  => 'separator',
            'group' => 'form_field_group',
		];


        //------Email------
        $this->controls['email_label'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Email Label', 'wpfnl' ),
            'type' => 'text',
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'Email',
            'required'    => [ 'field_label'],
        ];

        $this->controls['email_placeholder'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Email Placeholder Text', 'wpfnl' ),
            'type' => 'text',
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'Email',
            'placeholder' => 'Email Placeholder Text',
        ];

        $this->controls['emailSeparator'] = [
			'type'  => 'separator',
            'group' => 'form_field_group',
		];


        // First Name
        $this->controls['first_name'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'First Name', 'wpfnl' ),
			'type'  => 'checkbox',
		];
        $this->controls['first_name_label'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Label', 'wpfnl' ),
            'type' => 'text',
            'inline' => true, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'First Name',
            'placeholder' => 'First Name',
            'required'    => [ 'field_label'],
            'required'    => [ 'first_name'],
        ];
        $this->controls['first_name_placeholder'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Placeholder Text', 'wpfnl' ),
            'type' => 'text',
            'inline' => true, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'First Name',
            'placeholder' => 'First Name',
            'required'    => [ 'first_name'],
        ];
        $this->controls['is_required_name'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Mark First Name As Required', 'wpfnl' ),
			'type'  => 'checkbox',
            'required'    => [ 'first_name'],
		];
        // End First Name


        $this->controls['firstNameSeparator'] = [
			'type'  => 'separator',
            'group' => 'form_field_group',
		];


        // Last Name
        $this->controls['last_name'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Last Name', 'wpfnl' ),
			'type'  => 'checkbox',
		];
        $this->controls['last_name_label'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Label', 'wpfnl' ),
            'type' => 'text',
            'inline' => true, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'Last Name',
            'placeholder' => 'Last Name',
            'required'    => [ 'field_label'],
            'required'    => [ 'last_name'],
        ];
        $this->controls['last_name_placeholder'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Placeholder Text', 'wpfnl' ),
            'type' => 'text',
            'inline' => true, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'Last Name',
            'placeholder' => 'Last Name',
            'required'    => [ 'last_name'],
        ];
        $this->controls['is_required_last_name'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Mark Last Name As Required', 'wpfnl' ),
			'type'  => 'checkbox',
            'required'    => [ 'last_name'],
		];
        // End Last Name


        $this->controls['lastNameSeparator'] = [
			'type'  => 'separator',
            'group' => 'form_field_group',
		];


        // Phone
        $this->controls['phone'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Phone', 'wpfnl' ),
			'type'  => 'checkbox',
		];
        $this->controls['phone_label'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Label', 'wpfnl' ),
            'type' => 'text',
            'inline' => true, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'Phone',
            'placeholder' => 'Phone',
            'required'    => [ 'field_label'],
            'required'    => [ 'phone'],
        ];
        $this->controls['phone_placeholder'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Placeholder Text', 'wpfnl' ),
            'type' => 'text',
            'inline' => true, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'Phone',
            'placeholder' => 'Phone',
            'required'    => [ 'phone'],
        ];
        $this->controls['is_required_phone'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Mark Phone As Required', 'wpfnl' ),
			'type'  => 'checkbox',
            'required'    => [ 'phone'],
		];
        // End Phone


        $this->controls['phoneSeparator'] = [
			'type'  => 'separator',
            'group' => 'form_field_group',
		];


        // Website URL
        $this->controls['website_url'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Website Url', 'wpfnl' ),
			'type'  => 'checkbox',
		];
        $this->controls['website_url_label'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Label', 'wpfnl' ),
            'type' => 'text',
            'inline' => true, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'Website Url',
            'placeholder' => 'Website Url',
            'required'    => [ 'field_label'],
            'required'    => [ 'website_url'],
        ];
        $this->controls['website_url_placeholder'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Placeholder Text', 'wpfnl' ),
            'type' => 'text',
            'inline' => true, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'Website Url',
            'placeholder' => 'Website Url',
            'required'    => [ 'website_url'],
        ];
        $this->controls['is_required_website_url'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Mark Website Url As Required', 'wpfnl' ),
			'type'  => 'checkbox',
            'required'    => [ 'website_url'],
		];
        // End Website URL


        $this->controls['weburlSeparator'] = [
			'type'  => 'separator',
            'group' => 'form_field_group',
		];


        // Message
        $this->controls['message'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Message', 'wpfnl' ),
			'type'  => 'checkbox',
		];
        $this->controls['message_label'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Label', 'wpfnl' ),
            'type' => 'text',
            'inline' => true, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => 'Message',
            'placeholder' => 'Message',
            'required'    => [ 'field_label'],
            'required'    => [ 'message'],
        ];
        $this->controls['message_placeholder'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Placeholder Text', 'wpfnl' ),
            'type' => 'text',
            'inline' => false, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => false,
            'default' => '',
            'placeholder' => 'Write your message here...',
            'required'    => [ 'message'],
        ];
        $this->controls['is_required_message'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Mark Message As Required', 'wpfnl' ),
			'type'  => 'checkbox',
            'required'    => [ 'message'],
		];
        // End Message


        $this->controls['messageSeparator'] = [
			'type'  => 'separator',
            'group' => 'form_field_group',
		];


        // Acceptance Checkbox
        $this->controls['acceptance_checkbox'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Acceptance Checkbox', 'wpfnl' ),
			'type'  => 'checkbox',
		];
        $this->controls['acceptance_checkbox_text'] = [
            'tab' => 'content',
            'group' => 'form_field_group',
            'label' => esc_html__( 'Acceptance Text', 'wpfnl' ),
            'type' => 'editor',
            'inline' => false, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => [
                'selector' => '.text-editor', // Mount inline editor to this CSS selector
                'toolbar' => true, // Enable/disable inline editing toolbar
            ],
            'default' => 'I have read and agree the Terms & Condition.',
            'required'    => [ 'field_label'],
            'required'    => [ 'acceptance_checkbox'],
        ];
        $this->controls['is_required_acceptance'] = [
			'tab'   => 'content',
			'group' => 'form_field_group',
			'label' => esc_html__( 'Mark Acceptance As Required', 'wpfnl' ),
			'type'  => 'checkbox',
            'required'    => [ 'acceptance_checkbox'],
		];
        // End Acceptance Checkbox
    }


	/**
     * Sets the control for the optin form click to expand button.
	 *
	 * @since 3.1.0
     *
     * @access public
     * 
     */
    public function optin_form_expand_button_control(){
        // Group: Expand Button
		$this->controls['expandButtonText'] = [
			'tab'         => 'content',
			'group'       => 'expand_button_group',
			'label'       => esc_html__( 'Text', 'wpfnl' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Send', 'wpfnl' ),
		];

		$this->controls['expandButtonSize'] = [
			'tab'     => 'content',
			'group'   => 'expand_button_group',
			'label'   => esc_html__( 'Size', 'wpfnl' ),
			'type'    => 'select',
			'default'    => 'xl',
			'inline'  => true,
			'options' => $this->control_options['buttonSizes'],
		];

		$this->controls['expandButtonMargin'] = [
			'tab'   => 'content',
			'group' => 'expand_button_group',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.expand-bricks-button',
				],
			],
		];

		$this->controls['expandButtonPadding'] = [
			'tab'   => 'content',
			'group' => 'expand_button_group',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.expand-bricks-button',
				],
			],
		];

		$this->controls['expandButtonTypography'] = [
			'tab'   => 'content',
			'group' => 'expand_button_group',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.expand-bricks-button',
				]
			],
		];

		$this->controls['expandButtonBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'expand_button_group',
			'label' => esc_html__( 'Background', 'wpfnl' ),
			'type'  => 'color',
			'default'  => '#6E42D3',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.expand-bricks-button',
				]
			],
		];

		$this->controls['expandButtonBorder'] = [
			'tab'   => 'content',
			'group' => 'expand_button_group',
			'label' => esc_html__( 'Border', 'wpfnl' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.expand-bricks-button',
				],
			],
		];

		$this->controls['expandButtonIcon'] = [
			'tab'   => 'content',
			'group' => 'expand_button_group',
			'label' => esc_html__( 'Icon', 'wpfnl' ),
			'type'  => 'icon',
		];

		$this->controls['expandButtonIconPosition'] = [
			'tab'         => 'content',
			'group'       => 'expand_button_group',
			'label'       => esc_html__( 'Icon position', 'wpfnl' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'wpfnl' ),
			'required'    => [ 'expandButtonIcon', '!=', '' ],
		];
        
    }


    /**
     * Sets the control for the optin form submit button.
	 * 
	 * @since 3.1.0
     *
     * @access public
     * 
     */
    public function optin_form_submit_button_control(){
        // Group: Submit Button
		$this->controls['submitButtonText'] = [
			'tab'         => 'content',
			'group'       => 'submit_button_group',
			'label'       => esc_html__( 'Text', 'wpfnl' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Send', 'wpfnl' ),
		];

		$this->controls['submitButtonSize'] = [
			'tab'     => 'content',
			'group'   => 'submit_button_group',
			'label'   => esc_html__( 'Size', 'wpfnl' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->control_options['buttonSizes'],
		];

		$this->controls['submitButtonWidth'] = [
			'tab'   => 'content',
			'group' => 'submit_button_group',
			'label' => esc_html__( 'Width', 'wpfnl' ) . ' (%)',
			'type'  => 'number',
			'unit'  => '%',
			'css'   => [
				[
					'property' => 'width',
					'selector' => '.submit-button-wrapper',
				],
			],
		];

		$this->controls['submitButtonMargin'] = [
			'tab'   => 'content',
			'group' => 'submit_button_group',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.submit-button-wrapper',
				],
			],
		];

		$this->controls['submitButtonPadding'] = [
			'tab'   => 'content',
			'group' => 'submit_button_group',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.bricks-button',
				],
			],
		];

		$this->controls['submitButtonTypography'] = [
			'tab'   => 'content',
			'group' => 'submit_button_group',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-button',
				]
			],
		];

		$this->controls['submitButtonBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'submit_button_group',
			'label' => esc_html__( 'Background', 'wpfnl' ),
			'type'  => 'color',
			'default'  => '#6E42D3',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-button',
				]
			],
		];

		$this->controls['submitButtonBorder'] = [
			'tab'   => 'content',
			'group' => 'submit_button_group',
			'label' => esc_html__( 'Border', 'wpfnl' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => 'button[type=submit].bricks-button',
				],
			],
		];

		$this->controls['submitButtonIcon'] = [
			'tab'   => 'content',
			'group' => 'submit_button_group',
			'label' => esc_html__( 'Icon', 'wpfnl' ),
			'type'  => 'icon',
		];

		$this->controls['submitButtonIconPosition'] = [
			'tab'         => 'content',
			'group'       => 'submit_button_group',
			'label'       => esc_html__( 'Icon position', 'wpfnl' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'wpfnl' ),
			'required'    => [ 'submitButtonIcon', '!=', '' ],
		];
        
    }


    /**
     * Sets the control for "after submission of opt-in form".
	 * 
	 * @since 3.1.0
     *
     * @access public
     */
    public function optin_form_after_submission_control(){
        // Group: Action After Submission
        $this->controls['admin_email'] = [
            'tab' => 'content',
            'group' => 'action_after_submission_group',
            'label' => esc_html__( 'Admin Email', 'wpfnl' ),
            'type' => 'text',
            'inlineEditing' => false,
            'default' => Wpfnl_functions::get_optin_settings('sender_email'),
            'placeholder' => wp_get_current_user()->user_email,
        ];
        $this->controls['admin_email_subject'] = [
            'tab' => 'content',
            'group' => 'action_after_submission_group',
            'label' => esc_html__( 'Admin Email Subject', 'wpfnl' ),
            'type' => 'text',
            'inlineEditing' => false,
            'default' => Wpfnl_functions::get_optin_settings('email_subject'),
            'placeholder' => Wpfnl_functions::get_optin_settings('email_subject'),
        ];
        $this->controls['notification_text'] = [
            'tab' => 'content',
            'group' => 'action_after_submission_group',
            'label' => esc_html__( 'Notification Text', 'wpfnl' ),
            'type' => 'textarea',
            'inlineEditing' => false,
            'default' => 'Thank you! Your form was submitted successfully!',
            'placeholder' => 'Type notification texts here',
        ];

        $this->controls['post_action'] = [
			'tab' => 'content',
            'group' => 'action_after_submission_group',
			'label' => esc_html__( 'Other action', 'wpfnl' ),
			'type' => 'select',
			'options' => [
			  'notification' => 'None',
			  'redirect_to' => 'Redirect to url',
			  'next_step' => 'Next Step',
			],
			'inline' => true,
			'default' => 'notification',
		];
        $this->controls['redirect_url'] = [
			'tab'         => 'content',
            'group'       => 'action_after_submission_group',
			'label'       => esc_html__( 'Redirect url', 'wpfnl' ),
			'type'        => 'link',
			'pasteStyles' => false,
			'placeholder' => esc_html__( 'https://your-link.com', 'wpfnl' ),
            'default' => '',
			'required'    => [ 'post_action', '=', 'redirect_to' ],
		];

        $this->controls['data_to_checkout'] = [
			'tab'   => 'content',
			'group' => 'action_after_submission_group',
			'label' => esc_html__( 'Carry data to the Next Form', 'wpfnl' ),
			'type'  => 'checkbox',
		];

        $this->controls['allow_registration'] = [
			'tab'   => 'content',
			'group' => 'action_after_submission_group',
			'label' => esc_html__( 'Register User As Subscriber', 'wpfnl' ),
			'type'  => 'checkbox',
		];
        $this->controls['allow_user_permission'] = [
			'tab'   => 'content',
			'group' => 'action_after_submission_group',
			'label' => esc_html__( 'Registration Permission', 'wpfnl' ),
			'type'  => 'checkbox',
            'required'    => [ 'allow_registration'],
		];
        $this->controls['allow_user_registration_permission_text'] = [
            'tab' => 'content',
            'group' => 'action_after_submission_group',
            'label' => esc_html__( 'Registration Permission Text', 'wpfnl' ),
            'type' => 'editor',
            'inline' => false, 
            'spellcheck' => true, // Default: false
            'inlineEditing' => [
                'selector' => '.text-editor', // Mount inline editor to this CSS selector
                'toolbar' => true, // Enable/disable inline editing toolbar
            ],
            'default' => 'I agree to be registered as a subscriber.',
            'required'    => [ 'allow_user_permission'],
        ];
    }



	/**
	 * Sets the style control for the optin form's field label style.
	 * 
	 * @since 3.1.0
     *
     * @access public
	 */
	public function optin_form_field_label_style_control(){
		$this->controls['fieldLabelTypography'] = [
			'tab'   => 'content',
			'group' => 'form_field_label_style_group',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-optin-form-group:not(.acceptance-checkbox):not(.user-registration-checkbox) > label',
				],
			],
		];

		$this->controls['fieldLabelMargin'] = [
			'tab'         => 'content',
			'group'       => 'form_field_label_style_group',
			'label'       => esc_html__( 'Margin', 'wpfnl' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-optin-form-group:not(.acceptance-checkbox):not(.user-registration-checkbox) > label',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => '7px',
				'left'   => 0,
			],
		];
	}


	/**
	 * Sets the style control for the optin form field.
	 * 
	 * @since 3.1.0
     *
     * @access public
	 */
	public function optin_form_field_style_control(){
		$this->controls['fieldTypography'] = [
			'tab'   => 'style',
			'group' => 'form_field_style_group',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-optin-form-group input:not([type=checkbox]):not([type=radio])',
				],
				[
					'property' => 'font',
					'selector' => '.wpfnl-optin-form-group select',
				],
				[
					'property' => 'font',
					'selector' => '.wpfnl-optin-form-group textarea',
				],
			],
		];

		$this->controls['fieldBackgroundColor'] = [
			'tab'   => 'style',
			'group' => 'form_field_style_group',
			'label' => esc_html__( 'Background color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-optin-form-group input:not([type=checkbox]):not([type=radio])',
				],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-optin-form-group select',
				],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-optin-form-group textarea',
				],
			],
		];

		$this->controls['fieldBorder'] = [
			'tab'   => 'style',
			'group' => 'form_field_style_group',
			'label' => esc_html__( 'Border', 'wpfnl' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.wpfnl-optin-form-group input:not([type=checkbox]):not([type=radio])',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-optin-form-group select',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-optin-form-group textarea',
				],
				
			],
		];

		$this->controls['fieldShadow'] = [
			'tab' => 'style',
			'group' => 'form_field_style_group',
			'label' => esc_html__( 'BoxShadow', 'wpfnl' ),
			'type' => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.wpfnl-optin-form-group input:not([type=checkbox]):not([type=radio])',
				],
				[
					'property' => 'box-shadow',
					'selector' => '.wpfnl-optin-form-group select',
				],
				[
					'property' => 'box-shadow',
					'selector' => '.wpfnl-optin-form-group textarea',
				],
				
			],
			'inline' => true,
			'small' => true,
			'default' => [
			  'values' => [
				'offsetX' => 0,
				'offsetY' => 0,
				'blur' => 2,
				'spread' => 0,
			  ],
			  'color' => [
				'rgb' => 'rgba(0, 0, 0, .1)',
			  ],
			],
		];

		$this->controls['fieldMargin'] = [
			'tab'         => 'content',
			'group'       => 'form_field_style_group',
			'label'       => esc_html__( 'Margin', 'wpfnl' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-optin-form-group:not(.submit-button-wrapper)',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => '12px',
				'left'   => 0,
			],
		];

		$this->controls['fieldPadding'] = [
			'tab'   => 'style',
			'group' => 'form_field_style_group',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-optin-form-group input:not([type=checkbox]):not([type=radio])',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-optin-form-group select',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-optin-form-group textarea',
				],
			],
		];
	}


	/**
	 * Sets the style control for the optin form's checkbox style.
	 * 
	 * @since 3.1.0
     *
     * @access public
	 */
	public function optin_form_field_checkbox_style_control(){
		$this->controls['fieldCheckboxTypography'] = [
			'tab'   => 'style',
			'group' => 'form_field_checkbox_style_group',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-optin-form-group.acceptance-checkbox > label',
				],
				[
					'property' => 'font',
					'selector' => '.wpfnl-optin-form-group.user-registration-checkbox > label',
				],
			],
		];

		$this->controls['fieldCheckboxBorderColor'] = [
			'tab' => 'style',
			'group' => 'form_field_checkbox_style_group',
			'label' => esc_html__( 'Checkbox border color', 'wpfnl' ),
			'type' => 'color',
			'inline' => true,
			'css' => [
			  [
				'property' => 'border-color',
				'selector' => '.wpfnl-optin-form-group.acceptance-checkbox > label .check-box',
			  ],
			  [
				'property' => 'border-color',
				'selector' => '.wpfnl-optin-form-group.user-registration-checkbox > label .check-box',
			  ],
			],
			'default' => [
			  'hex' => '#3ce77b',
			  'rgb' => 'rgba(60, 231, 123, 0.9)',
			],
		];

		$this->controls['fieldCheckboxActiveBorderColor'] = [
			'tab' => 'style',
			'group' => 'form_field_checkbox_style_group',
			'label' => esc_html__( 'Active checkbox border color', 'wpfnl' ),
			'type' => 'color',
			'inline' => true,
			'css' => [
			  [
				'property' => 'border-color',
				'selector' => '.wpfnl-optin-form-group.acceptance-checkbox input[type=checkbox]:checked + label .check-box',
			  ],
			  [
				'property' => 'border-color',
				'selector' => '.wpfnl-optin-form-group.user-registration-checkbox input[type=checkbox]:checked + label .check-box',
			  ],
			  [
				'property' => 'background-color',
				'selector' => '.wpfnl-optin-form-group.acceptance-checkbox input[type=checkbox]:checked + label .check-box',
			  ],
			  [
				'property' => 'background-color',
				'selector' => '.wpfnl-optin-form-group.user-registration-checkbox input[type=checkbox]:checked + label .check-box',
			  ],
			],
			'default' => [
			  'hex' => '#3ce77b',
			  'rgb' => 'rgba(60, 231, 123, 0.9)',
			],
		];

		$this->controls['fieldCheckboxTickColor'] = [
			'tab' => 'style',
			'group' => 'form_field_checkbox_style_group',
			'label' => esc_html__( 'Checkbox Tick color', 'wpfnl' ),
			'type' => 'color',
			'inline' => true,
			'css' => [
			  [
				'property' => 'border-color',
				'selector' => '.wpfnl-optin-form-group.acceptance-checkbox > label .check-box::after',
			  ],
			  [
				'property' => 'border-color',
				'selector' => '.wpfnl-optin-form-group.user-registration-checkbox > label .check-box::after',
			  ],
			],
			'default' => [
			  'hex' => '#3ce77b',
			  'rgb' => 'rgba(60, 231, 123, 0.9)',
			],
		];
	}


    
    /**
     * Set builder control groups
     * 
     * @since 3.1.0
     *
     * @access public
     */
    public function set_control_groups()
    {

		//form source group
        $this->control_groups['optin_form_source_group'] = [
            'title' => esc_html__('Opt-in Form Source', 'wpfnl'),
            'tab' => 'content',
        ];

		//form layout group
        $this->control_groups['form_layout_group'] = [
            'title' => esc_html__('Form Layout', 'wpfnl'),
            'tab' => 'content',
            'required'    => [ 'optin_form_source', '=', 'wpfnl_forms' ],
        ];

		//form field group
        $this->control_groups['form_field_group'] = [
            'title' => esc_html__('Form Fields', 'wpfnl'),
            'tab' => 'content',
            'required'    => [ 'optin_form_source', '=', 'wpfnl_forms' ],
        ];

		//expand button group
		$this->control_groups['expand_button_group'] = [
			'title' => esc_html__('Expand Button', 'wpfnl'),
			'tab' => 'content',
			'required'    => [ 'optin_form_type', '=', 'clickto-expand-optin' ],
		];

		//submit button group
        $this->control_groups['submit_button_group'] = [
            'title' => esc_html__('Submit Button', 'wpfnl'),
            'tab' => 'content',
            'required'    => [ 'optin_form_source', '=', 'wpfnl_forms' ],
        ];

		//after sumbission group
        $this->control_groups['action_after_submission_group'] = [
            'title' => esc_html__('Action After Submission', 'wpfnl'),
            'tab' => 'content',
            'required'    => [ 'optin_form_source', '=', 'wpfnl_forms' ],
        ];
		
		if( \WPFunnels\Integrations\Helper::maybe_enabled() ) {
			//MM contact
			$this->control_groups['mm_contact_group'] = [
				'title' => esc_html__('MailMint Contact Control', 'wpfnl'),
				'tab' => 'content',
				'required'    => [ 'optin_form_source', '=', 'wpfnl_forms' ],
			];
		}
		

		//field label style group
        $this->control_groups['form_field_label_style_group'] = [
            'title' => esc_html__('Form Fields Label Style', 'wpfnl'),
            'tab' => 'style',
            'required'    => [ 'optin_form_source', '=', 'wpfnl_forms' ],
        ];

		//field style group
        $this->control_groups['form_field_style_group'] = [
            'title' => esc_html__('Form Fields Style', 'wpfnl'),
            'tab' => 'style',
            'required'    => [ 'optin_form_source', '=', 'wpfnl_forms' ],
        ];

		//field checkbox style group
        $this->control_groups['form_field_checkbox_style_group'] = [
            'title' => esc_html__('Checkbox Fields Style', 'wpfnl'),
            'tab' => 'style',
            'required'    => [ 'optin_form_source', '=', 'wpfnl_forms' ],
        ];

    }


    /**
     * Set builder controls
     * 
     * @since 3.1.0
     *
     * @access public
     */
    public function set_controls(){
        
        $this->optin_form_source_control();
        $this->optin_form_type_control();
        $this->optin_form_field_control();
        $this->optin_form_expand_button_control();
        $this->optin_form_submit_button_control();
        $this->optin_form_after_submission_control();


		//----style controls----
        $this->optin_form_field_label_style_control();
        $this->optin_form_field_style_control();
        $this->optin_form_field_checkbox_style_control();
        
    }


    /**
	 * Get wrapper classes
	 * @since 3.1.0
     *
     * @access public
	 * @return array
	 */
	public function get_wrapper_classes() {
		$settings = $this->settings;

		if( 'clickto-expand-optin' == $settings['optin_form_type'] ) {
			return array( 'wpfnl', 'wpfnl-optin-form', 'wpfnl-bricks-optin-form-wrapper', 'clickto-expand-optin');
			
		}else {
			return array( 'wpfnl', 'wpfnl-optin-form', 'wpfnl-bricks-optin-form-wrapper');

		}
	}

	/**
	 * Render Expand buton.
	 *
	 */
	public function render_wpfnl_optin_expand_btn() {
		$settings = $this->settings;

		// We need the form element ID to recover the element settings on form expand button
		$this->set_attribute( '_root', 'data-element-id', $this->id );

		// expand button
		$expand_button_icon_position = ! empty( $settings['expandButtonIconPosition'] ) ? $settings['expandButtonIconPosition'] : 'right';

		$this->set_attribute( 'expand-wrapper', 'class', ['submit-button-wrapper', 'wpfnl-optin-clickto-expand']);

		$expand_button_classes[] = 'expand-bricks-button bricks-button clickto-expand-btn';

		if ( ! empty( $settings['expandButtonSize'] ) ) {
			$expand_button_classes[] = $settings['expandButtonSize'];
		}

		if ( isset( $settings['expandButtonCircle'] ) ) {
			$expand_button_classes[] = 'circle';
		}

		if ( ! empty( $settings['expandButtonIcon'] ) ) {
			$expand_button_classes[] = "icon-$expand_button_icon_position";
		}

		$this->set_attribute( 'expand-button', 'class', $expand_button_classes );

        // expand button icon
		$expand_button_icon = isset( $settings['expandButtonIcon'] ) ? self::render_icon( $settings['expandButtonIcon'] ) : false;

		?>
		<div <?php echo $this->render_attributes( 'expand-wrapper' ); echo $this->render_attributes( '_root' ); ?> >
			<button type="button" <?php echo $this->render_attributes( 'expand-button' ); ?>>
				<?php
				if ( $expand_button_icon_position === 'left' && $expand_button_icon ) {
					echo $expand_button_icon;
				}

				if ( ! isset( $settings['expandButtonIcon'] ) || ( isset( $settings['expandButtonIcon'] ) && isset( $settings['expandButtonText'] ) ) ) {
					$this->set_attribute( 'expandButtonText', 'class', 'text' );

					$expand_button_text = isset( $settings['expandButtonText'] ) ? esc_html( $settings['expandButtonText'] ) : esc_html__( 'Click to Expand', 'wpfnl' );

					echo "<span {$this->render_attributes( 'expandButtonText' )}>$expand_button_text</span>";
				}

				if ( $expand_button_icon_position === 'right' && $expand_button_icon ) {
					echo $expand_button_icon;
				}
				?>
			</button>
		</div>
		<?php
	}


    /**
	 * Render the widget output on the frontend for WPFunnels' default forms.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 3.1.0
     *
     * @access public 
	 */
	public function render_wpfnl_form( $settings, $classes ) {
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


		//mm contact
		$enable_mm_contact 			= isset($settings['enable_mm_contact']) && $settings['enable_mm_contact'] == 'on' ? 'yes' : 'no';
		$mm_contact_status 			= isset($settings['mm_contact_status']) ? $settings['mm_contact_status'] : 'pending';
		$mm_lists 					= isset($settings['mm_lists']) ? $settings['mm_lists'] : '';
		$mm_tags 					= isset($settings['mm_tags']) ? $settings['mm_tags'] : '';

		// We need the form element ID to recover the element settings on form submit
		$this->set_attribute( '_root', 'data-element-id', $this->id );


		// Submit button
		$submit_button_icon_position = ! empty( $settings['submitButtonIconPosition'] ) ? $settings['submitButtonIconPosition'] : 'right';

		$this->set_attribute( 'submit-wrapper', 'class', [ 'wpfnl-optin-form-group submit', 'submit-button-wrapper' ] );

		$submit_button_classes[] = 'bricks-button btn-optin';

		if ( ! empty( $settings['submitButtonSize'] ) ) {
			$submit_button_classes[] = $settings['submitButtonSize'];
		}

		if ( isset( $settings['submitButtonCircle'] ) ) {
			$submit_button_classes[] = 'circle';
		}

		if ( ! empty( $settings['submitButtonIcon'] ) ) {
			$submit_button_classes[] = "icon-$submit_button_icon_position";
		}

		$this->set_attribute( 'submit-button', 'class', $submit_button_classes );

        // Submit button icon
		$submit_button_icon = isset( $settings['submitButtonIcon'] ) ? self::render_icon( $settings['submitButtonIcon'] ) : false;

		$randomId = rand();
        

		echo $recaptch_script;

		//-----expand button-------
		if( isset($settings['optin_form_type']) && 'clickto-expand-optin' === $settings['optin_form_type'] ) {
			$this->render_wpfnl_optin_expand_btn();
		}
		?>

        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" <?php echo $this->render_attributes( '_root' ); ?> >
			<form method="post">
				<input type="hidden" name="post_id" value="<?php echo $this->post_id; ?>" />
				<input type="hidden" name="enable_mm_contact" value="<?php echo $enable_mm_contact; ?>" />
				<input type="hidden" name="mm_contact_status" value="<?php echo $mm_contact_status; ?>" />
				<input type="hidden" name="mm_lists" value="<?php echo $mm_lists; ?>" />
				<input type="hidden" name="mm_tags" value="<?php echo $mm_tags; ?>" />
				<?php
				echo $is_recaptch_input;
				echo $token_input;
				echo $token_secret_key;

				if( isset($settings['submitButtonWidth']) && null != $settings['submitButtonWidth'] ){
					?>
					<style id="submit-btn-style">
						.wpfnl-bricks-optin-form-wrapper .wpfnl-optin-form-group.submit {
							width: auto;
							display: flex;
							flex-direction: column;
						}
					</style>
					<?php
				}
				?>
				<div class="wpfnl-optin-form-wrapper <?php echo isset($settings['optin_form_layout']) ? $settings['optin_form_layout'] : ''; ?>" >
					<?php if( isset($settings['first_name']) && 'yes' == $settings['first_name'] ){ ?>
						<div class="wpfnl-optin-form-group first-name">

							<?php if( isset($settings['field_label']) && 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-first-name">
									<?php
									echo isset($settings['first_name_label']) && $settings['first_name_label'] ? $settings['first_name_label'] : __('First Name','wpfnl');

									if( isset($settings['is_required_name']) && 'yes' == $settings['is_required_name'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
								<?php if( isset($settings['input_fields_icon']) && 'yes' == $settings['input_fields_icon'] ){ ?>
									<span class="field-icon">
										<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
									</span>
								<?php }
								$f_name_placeholder = isset($settings['first_name_placeholder']) ? $settings['first_name_placeholder'] : '';
								?>
								<input type="text" name="first_name" class="wpfnl-first-name" id="wpfnl-first-name" placeholder="<?php echo $f_name_placeholder; ?>" <?php echo isset($settings['is_required_name']) && 'yes' == $settings['is_required_name'] ? 'required' : ''; ?> >
							</span>

						</div>
					<?php } ?>

					<?php if( isset($settings['last_name']) && 'yes' == $settings['last_name'] ){ ?>
						<div class="wpfnl-optin-form-group last-name">

							<?php if( isset($settings['field_label']) & 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-last-name">
									<?php
									echo isset($settings['last_name_label']) && $settings['last_name_label'] ? $settings['last_name_label'] : __('Last Name','wpfnl');

									if( isset($settings['is_required_last_name']) && 'yes' == $settings['is_required_last_name'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
								<?php if( isset($settings['input_fields_icon']) && 'yes' == $settings['input_fields_icon'] ){ ?>
									<span class="field-icon">
										<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/user-icon.svg'; ?>" alt="icon">
									</span>
								<?php }
								$l_name_placeholder = isset($settings['last_name_placeholder']) ? $settings['last_name_placeholder'] : '';
								?>
								<input type="text" name="last_name" class="wpfnl-last-name" id="wpfnl-last-name" placeholder="<?php echo $l_name_placeholder;?>" <?php echo isset($settings['is_required_last_name']) && 'yes' == $settings['is_required_last_name'] ? 'required' : ''; ?> >
							</span>
						</div>
					<?php } ?>

					<div class="wpfnl-optin-form-group email">
						<?php if( isset($settings['field_label']) && 'yes' == $settings['field_label'] ){ ?>
							<label for="wpfnl-email">
								<?php
								echo isset($settings['email_label']) && $settings['email_label'] ? $settings['email_label'] : __('Email','wpfnl');

								if( isset($settings['required_mark']) && 'yes' == $settings['required_mark'] ){ ?>
									<span class="required-mark">*</span>
								<?php } ?>
							</label>
						<?php } ?>
						<span class="input-wrapper">
							<?php if( isset($settings['input_fields_icon']) && 'yes' == $settings['input_fields_icon'] ){ ?>
								<span class="field-icon">
									<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/email-open-icon.svg'; ?>" alt="icon">
								</span>
							<?php }
							$email_placeholder = isset($settings['email_placeholder']) ? $settings['email_placeholder'] : '';
							?>
							<input type="email" name="email" class="wpfnl-email" id="wpfnl-email" placeholder="<?php echo $email_placeholder; ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" required >
						</span>
					</div>

					<?php if( isset($settings['phone']) && 'yes' == $settings['phone'] ){ ?>
						<div class="wpfnl-optin-form-group phone">

							<?php if( isset($settings['field_label']) && 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-phone">
									<?php
									echo isset($settings['phone_label']) && $settings['phone_label'] ? $settings['phone_label'] : __('Phone','wpfnl');

									if( isset($settings['is_required_phone']) && 'yes' == $settings['is_required_phone'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
								<?php if( isset($settings['input_fields_icon']) && 'yes' == $settings['input_fields_icon'] ){ ?>
									<span class="field-icon">
										<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/phone.svg'; ?>" alt="icon">
									</span>
								<?php }
								$phone_placeholder = isset($settings['phone_placeholder']) && $settings['phone_placeholder'] ? $settings['phone_placeholder'] : '';
								?>
								<input type="text" name="phone" class="wpfnl-phone" id="wpfnl-phone" placeholder="<?php echo $phone_placeholder; ?>" <?php echo isset($settings['is_required_phone']) && 'yes' == $settings['is_required_phone'] ? 'required' : ''; ?> >
							</span>
						</div>
					<?php } ?>

					<?php if( isset($settings['website_url']) && 'yes' == $settings['website_url'] ){ ?>
						<div class="wpfnl-optin-form-group website-url">

							<?php if( isset($settings['field_label']) && 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-web-url">
									<?php
									echo isset($settings['website_url_label']) && $settings['website_url_label'] ? $settings['website_url_label'] : __('Website Url','wpfnl');

									if( isset($settings['is_required_website_url']) && 'yes' == $settings['is_required_website_url'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php } ?>

							<span class="input-wrapper">
								<?php if( isset($settings['input_fields_icon']) && 'yes' == $settings['input_fields_icon'] ){ ?>
									<span class="field-icon">
										<img src="<?php echo WPFNL_DIR_URL.'/public/assets/images/web-url.svg'; ?>" alt="icon">
									</span>
								<?php }
								$weburl_placeholder = isset($settings['website_url_placeholder']) && $settings['website_url_placeholder'] ? $settings['website_url_placeholder'] : '';
								?>
								<input type="text" name="web-url" class="wpfnl-web-url" id="wpfnl-web-url" pattern="(https?://)?.+" size="30" placeholder="<?php echo $weburl_placeholder; ?>" <?php echo isset($settings['is_required_website_url']) && 'yes' == $settings['is_required_website_url'] ? 'required' : ''; ?> >
							</span>
						</div>
					<?php } ?>

					<?php if( isset($settings['message']) && 'yes' == $settings['message'] ){ ?>
						<div class="wpfnl-optin-form-group message">

							<?php if( isset($settings['field_label']) && 'yes' == $settings['field_label'] ){ ?>
								<label for="wpfnl-message">
									<?php
									echo isset($settings['message_label']) && $settings['message_label'] ? $settings['message_label'] : __('Message','wpfnl');

									if( isset($settings['is_required_message']) && 'yes' == $settings['is_required_message'] ){ ?>
										<span class="required-mark">*</span>
									<?php } ?>
								</label>
							<?php }

							$message_placeholder = isset($settings['message_placeholder']) && $settings['message_placeholder'] ? $settings['message_placeholder'] : '';
							?>

							<span class="input-wrapper">
								<textarea name="message" class="wpfnl-message" id="wpfnl-message" cols="30" rows="3" placeholder="<?php echo $message_placeholder; ?>" <?php echo isset($settings['is_required_message']) && 'yes' == $settings['is_required_message'] ? 'required' : ''; ?> ></textarea>
							</span>
						</div>
					<?php } ?>

					<?php
					if( isset($settings['acceptance_checkbox']) && 'yes' == $settings['acceptance_checkbox'] ){
						?>
						<div class="wpfnl-optin-form-group acceptance-checkbox">
							<input type="checkbox" name="acceptance_checkbox" class="wpfnl-acceptance_checkbox" id="wpfnl-acceptance_checkbox-<?php echo $randomId; ?>" <?php echo isset($settings['is_required_acceptance']) && 'yes' == $settings['is_required_acceptance'] ? 'required' : ''; ?> >

							<label for="wpfnl-acceptance_checkbox-<?php echo $randomId; ?>">
								<span class="check-box"></span>
								<?php
								echo isset($settings['acceptance_checkbox_text']) && $settings['acceptance_checkbox_text'] ? $settings['acceptance_checkbox_text'] : '';

								if( isset($settings['is_required_acceptance']) && 'yes' == $settings['is_required_acceptance'] ){
									echo '<span class="required-mark">*</span>';
								}
								?>
							</label>
						</div>
						<?php
					}
					?>

					<?php
					if( isset($settings['allow_registration']) && 'yes' == $settings['allow_registration']){
						?>
						<input type="hidden" name="optin_allow_registration" value="<?php echo esc_attr( 'yes' ); ?>"/>
						<?php
						if(isset($settings['allow_user_permission']) && 'yes' == $settings['allow_user_permission']){
							?>
							<div class="wpfnl-optin-form-group user-registration-checkbox">
								<input type="checkbox" name="user_registration_checkbox" class="wpfnl-registration_checkbox" id="wpfnl-registration_checkbox-<?php echo $randomId; ?>" required >

								<label for="wpfnl-registration_checkbox-<?php echo $randomId; ?>">
									<span class="check-box"></span>
									<?php
									echo isset($settings['allow_user_registration_permission_text']) && $settings['allow_user_registration_permission_text'] ? $settings['allow_user_registration_permission_text'] : '';
									?>
									<span class="required-mark">*</span>
								</label>
							</div>
							<?php
						}
					}
					?>
					<?php
					if( isset($settings['data_to_checkout']) && 'yes' == $settings['data_to_checkout']){
						?>
						<input type="hidden" name="data_to_checkout" value="<?php echo esc_attr( 'yes' ); ?>"/>
						<?php
					}
					?>

                    <div <?php echo $this->render_attributes( 'submit-wrapper' ); ?>>
                        <button type="submit" <?php echo $this->render_attributes( 'submit-button' ); ?>>
                            <?php
                            if ( $submit_button_icon_position === 'left' && $submit_button_icon ) {
                                echo $submit_button_icon;
                            }

                            if ( ! isset( $settings['submitButtonIcon'] ) || ( isset( $settings['submitButtonIcon'] ) && isset( $settings['submitButtonText'] ) ) ) {
                                $this->set_attribute( 'submitButtonText', 'class', 'text' );

                                $submit_button_text = isset( $settings['submitButtonText'] ) ? esc_html( $settings['submitButtonText'] ) : esc_html__( 'Send', 'wpfnl' );

                                echo "<span {$this->render_attributes( 'submitButtonText' )}>$submit_button_text</span>";
                            }

                            echo '<span class="wpfnl-loader"></span>';

                            if ( $submit_button_icon_position === 'right' && $submit_button_icon ) {
                                echo $submit_button_icon;
                            }
                            ?>
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
     * Render the widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 3.1.0
     *
     * @access public
     */
    public function render()
    {
        $settings = $this->settings;
        $classes = $this->get_wrapper_classes();

		if ( !isset( $settings[ 'optin_form_source' ] ) || ( !empty( $settings[ 'optin_form_source' ] ) && 'mailmint_forms' !== $settings[ 'optin_form_source' ] ) ) {
			$this->render_wpfnl_form( $settings, $classes );
		}
		elseif ( !empty( $settings[ 'optin_form_source' ] ) && !empty( $settings[ 'mailmint_form_id' ] ) ) {
			ob_start();
			Wpfnl_Widgets_Manager::render_mailmint_form( $settings[ 'mailmint_form_id' ] );
			$mailmint_form = ob_get_clean();
			$replace = 'id="mrm-form">';
			$replace .= '<input type="hidden" name="widget_id" value="'.$settings[ 'mailmint_form_id' ].'"/>';
			if ( !empty( $settings[ 'data_to_checkout' ] ) && 'yes' == $settings[ 'data_to_checkout' ] ) {
				$replace .= '<input type="hidden" name="data_to_checkout" value="yes"/>';
			}
			if ( !empty( $settings[ 'allow_registration' ] ) && 'yes' == $settings[ 'allow_registration' ] ) {
				$replace .= '<input type="hidden" name="optin_allow_registration" value="yes"/>';
			}
			echo str_replace( 'id="mrm-form">', $replace, $mailmint_form );
		}

    }

}
