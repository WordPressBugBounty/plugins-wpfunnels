<?php
/**
 * Namespace declaration for the Checkout class.
 * This class is part of the WPFunnels\Widgets\Bricks namespace.
 * 
 * @package WPFunnels\Widgets\Bricks
 */
namespace WPFunnels\Widgets\Bricks;

require_once get_template_directory() . '/includes/elements/base.php';

use \Bricks\Element;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl;


if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly


/**
 * Class Checkout
 * 
 * Represents a checkout element.
 * 
 */
class Checkout extends Element
{

    // Element properties
    public $category     = 'wpfunnels'; // Use predefined element category 'general'
    public $name         = 'wpfnl_checkout'; // Make sure to prefix your elements
    public $icon         = 'fa-solid fa-cart-shopping'; // Themify icon font class
    public $scripts      = []; // Script(s) run when element is rendered on frontend or updated in builder


     /**
     * Return localised element label
     * 
     * @return string
     * @since 3.1.0
     */
    public function get_label()
    {
        return esc_html__('Checkout', 'wpfnl');
    }

    /**
	 * Get layout types
	 *
	 * @return array
	 */
    private function get_layout_types() {
    	$layouts = array(
			'wpfnl-col-1' 		        => __('1 Column Checkout', 'wpfnl'),
			'wpfnl-col-2' 		        => __('2 Column Checkout', 'wpfnl'),
			'wpfnl-2-step' 	            => __('2 Step Checkout', 'wpfnl'),
			'wpfnl-multistep' 	        => __('Multistep Checkout', 'wpfnl'),
			'wpfnl-express-checkout' 	=> __('Express Checkout', 'wpfnl'),
		);
    	return $layouts;
	}


    /**
     * Order Bump Settings
     *
     * @since 3.1.0
     *
     * @access public
     */
    public function order_bump_primary_settings()
    {
        $default = [
        ];

        $settings = get_post_meta($this->post_id, 'order-bump-settings', true);

        if ($settings) {
            return $settings;
        }

        return $default;
    }


    /**
     * Controls the checkout content for the Checkout widget.
     * 
     * @since 3.1.0
     * 
     * @access public
     */
    public function checkout_content_control() {
        // Group: Checkout Content
        $this->controls['checkout_layout'] = [
			'tab' => 'content',
            'group' => 'wpfunnels_checkout_content',
			'label' => esc_html__( 'Select Layout', 'wpfnl' ),
			'type' => 'select',
			'options' 	=> $this->get_layout_types(),
			'inline' => false,
			'default' => 'wpfnl-col-2',
		];

        if ( ! Wpfnl_functions::is_wpfnl_pro_activated() ) {
            $this->controls['layout_upgrade_pro'] = [
                'tab' => 'content',
                'group' => 'wpfunnels_checkout_content',
                'content' => sprintf( __( 'This is a pro feature. <a href="https://getwpfunnels.com/pricing/" target="_blank" rel="noopener">Upgrade Now!</a>.', 'wpfnl' ) ),
                'type' => 'info',
                'required'    => [ 'checkout_layout', '=', [ 'wpfnl-2-step', 'wpfnl-multistep', 'wpfnl-express-checkout' ] ],
            ];

		}

        $this->controls['checkout_floating_label'] = [
			'tab' => 'content',
            'group' => 'wpfunnels_checkout_content',
			'label' => esc_html__( 'Floating Label', 'wpfnl' ),
			'type' => 'select',
			'options' => [
                '' => __('Select option', 'wpfnl'),
                'floating-label' => __('Floating Label', 'wpfnl'),
              ],
			'inline' => true,
		];


    }


    /**
     * Registers the ob control.
     * 
     * @since 3.1.0
     * 
     * @access public
     */
    public function ob_register_control(){
        $ob_settings = $this->order_bump_primary_settings();
        if( count($ob_settings) > 0 ){
            foreach( $ob_settings as $key=>$settings ){
                $key = (int) $key;
                $index = ((int)($key) + 1);

                // Group: Checkout Content
                $this->controls['order_bump_'.$key] = [
                    'tab' => 'content',
                    'group' => 'wpfunnels_checkout_content',
                    'label' => sprintf( __("Enable Order bump %s", "wpfnl"),$index ),
                    'type' => 'select',
                    'options' => [
                        'yes' => __('Yes', 'wpfnl'),
                        'no' => __('No', 'wpfnl'),
                    ],
                    'inline' => false,
                    'default' => $settings['isEnabled'] ? 'yes' : 'no',
                ];

                $this->controls['order_bump_position_'.$key] = [
                    'tab' => 'content',
                    'group' => 'wpfunnels_checkout_content',
                    'label' => sprintf( __( "Order bump %s Position", "wpfnl"), $index ),
                    'type' => 'select',
                    'options' => [
                        'before-order' 				=> __('Before Order Details', 'wpfnl'),
                        'after-order' 				=> __('After Order Details', 'wpfnl'),
                        'before-checkout' 			=> __('Before Checkout Details', 'wpfnl'),
                        'after-customer-details' 	=> __('After Customer Details', 'wpfnl'),
                        'after-payment' 			=> __('After Payment Options', 'wpfnl'),
                        'before-payment'	 		=> __('Before Payment Options', 'wpfnl'),
                        'popup' 					=> __('Pop-up offer', 'wpfnl'),
                    ],
                    'inline' => false,
                    'default' => $ob_settings[$key]['position'],
                    'required'    => [ 'order_bump_'.$key, '=', 'yes' ],
                ];
            }
        }
    }


    /**
     * Register Billing Fields Style Controls.
     *
     * @since  3.1.0
     * @access public
     */
    public function register_billing_style_controls(){
        $this->controls['billing_heading_style'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Heading Style', 'wpfnl' ),
			'type'  => 'separator',
		];
        $this->controls['billing_heading_typography'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields h3',
                ],
				[
					'property' => 'font',
					'selector' => '.woocommerce.woocommerce-checkout #customer_details .woocommerce-billing-fields h3',
                ],
				[
					'property' => 'font',
					'selector' => '.woocommerce-page.woocommerce-checkout #customer_details .woocommerce-billing-fields h3',
				]
			],
		];
        $this->controls['billing_heading_margin'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields h3',
				],
			],
		];
        $this->controls['billing_heading_padding'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields h3',
				],
			],
		];


        // ----label style------
        $this->controls['billing_label_style'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Label Style', 'wpfnl' ),
			'type'  => 'separator',
		];
        $this->controls['billing_label_typography'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields p.form-row label',
                ],
			],
		];
        $this->controls['billing_label_margin'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields p.form-row label',
				],
			],
		];
        // ----end label style------


        // ----input field style------
        $this->controls['billing_input_field_style'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Input Field Style', 'wpfnl' ),
			'type'  => 'separator',
		];
        $this->controls['billing_input_text_typography'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row input.input-text',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-account-fields .form-row input.input-text',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row textarea',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .select2-container--default .select2-selection--single',
                ],
				[
					'property' => 'font',
					'selector' => '.woocommerce-billing-fields .select2-container--default .select2-selection--single .select2-selection__rendered',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row select.select',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row select',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_billing .form-row:not(.create-account) input.input-text:not(#billing_address_2, #shipping_address_2)',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_billing .form-row:not(.create-account) textarea',
                ],
			],
		];
        $this->controls['billing_input_bgcolor'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'default'  => '',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row input.input-text',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-account-fields .form-row input.input-text',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row textarea',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .select2-container--default .select2-selection--single',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row select.select',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row select',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_billing .form-row:not(.create-account) > label',
                ],
			],
		];
        $this->controls['billing_input_border_style'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Border', 'wpfnl' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row input.input-text',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-account-fields .form-row input.input-text',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row textarea',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .select2-container--default .select2-selection--single',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row select.select',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .form-row select',
				],
			],
		];
        $this->controls['billing_input_padding'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout form .woocommerce-billing-fields .form-row input.input-text',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-account-fields .form-row input.input-text',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout form .woocommerce-billing-fields .form-row textarea',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout form .woocommerce-billing-fields select.select',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout form .woocommerce-billing-fields .form-row select',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_billing .form-row:not(.create-account) input.input-text:not(#billing_address_2, #shipping_address_2)',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_billing .form-row:not(.create-account) textarea',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-billing-fields .select2-container .select2-selection--single .select2-selection__rendered',
				],
			],
		];
        $this->controls['billing_input_margin'] = [
			'group' => 'wpfunnels_checkout_billing_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout form .woocommerce-billing-fields p.form-row:not(#billing_address_1_field)',
				],
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-account-fields .form-row',
				],
			],
		];
        // ----end input field style------

    }

    
    /**
     * Register Shipping Fields Style Controls.
     *
     * @since  3.1.0
     * @access public
     */
    public function register_shipping_style_controls(){
        $this->controls['shipping_heading_style'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Heading Style', 'wpfnl' ),
			'type'  => 'separator',
		];
        $this->controls['shipping_heading_typography'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields #ship-to-different-address span',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-additional-fields > h3',
                ]
			],
		];
        $this->controls['shipping_heading_margin'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields #ship-to-different-address',
				],
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-additional-fields > h3',
				],
			],
		];
        $this->controls['shipping_heading_padding'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-additional-fields > h3',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields #ship-to-different-address',
				],
			],
		];


        // ----label style------
        $this->controls['shipping_label_style'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Label Style', 'wpfnl' ),
			'type'  => 'separator',
		];
        $this->controls['shipping_label_typography'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields p.form-row label',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-additional-fields p.form-row label',
                ],
			],
		];
        $this->controls['shipping_label_margin'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields p.form-row label',
				],
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-additional-fields p.form-row label',
				],
			],
		];
        // ----end label style------


        // ----input field style------
        $this->controls['shipping_input_field_style'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Input Field Style', 'wpfnl' ),
			'type'  => 'separator',
		];
        $this->controls['shipping_input_text_typography'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .form-row input.input-text',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-additional-fields .form-row textarea',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .select2-container--default .select2-selection--single',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .select2-container--default .select2-selection--single .select2-selection__rendered',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .form-row select.select',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .form-row select',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_shipping .form-row:not(.create-account) input.input-text:not(#billing_address_2, #shipping_address_2)',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_shipping .form-row:not(.create-account) textarea',
                ],				
			],
		];
        $this->controls['shipping_input_bgcolor'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'default'  => '',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .form-row input.input-text',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-additional-fields .form-row textarea',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .select2-container--default .select2-selection--single',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .form-row select.select',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .form-row select',
                ],
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_shipping .form-row:not(.create-account) > label',
                ],				
			],
		];
        $this->controls['shipping_input_border_style'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Border', 'wpfnl' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .form-row input.input-text',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-additional-fields .form-row textarea',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .select2-container--default .select2-selection--single',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .form-row select.select',
				],
				[
					'property' => 'border',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .form-row select',
				],				
			],
		];
        $this->controls['shipping_input_padding'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout form .woocommerce-shipping-fields .form-row input.input-text',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout form .woocommerce-additional-fields .form-row textarea',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout form .woocommerce-shipping-fields select.select',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout form .woocommerce-shipping-fields .form-row select',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_shipping .form-row:not(.create-account) input.input-text:not(#billing_address_2, #shipping_address_2)',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_shipping .form-row:not(.create-account) textarea',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-shipping-fields .select2-container .select2-selection--single .select2-selection__rendered',
				],				
			],
		];
        $this->controls['shipping_input_margin'] = [
			'group' => 'wpfunnels_checkout_shipping_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout form .woocommerce-shipping-fields p.form-row:not(#shipping_address_1_field)',
				],				
			],
		];
        // ----end input field style------

    }

    
    /**
     * Register Order table Style Controls.
     *
     * @since  3.1.0
     * @access public
     */
    public function register_order_tbl_style_controls(){
        $this->controls['order_heading_style'] = [
			'group' => 'wpfunnels_checkout_order_tbl_style',
			'label' => esc_html__( 'Heading Style', 'wpfnl' ),
			'type'  => 'separator',
		];
        $this->controls['order_heading_typography'] = [
			'group' => 'wpfunnels_checkout_order_tbl_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #order_review_heading',
                ],				
			],
		];
        $this->controls['order_heading_margin'] = [
			'group' => 'wpfunnels_checkout_order_tbl_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #order_review_heading',
				],				
			],
		];
        $this->controls['order_heading_padding'] = [
			'group' => 'wpfunnels_checkout_order_tbl_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #order_review_heading',
				],				
			],
		];
		// ----order table heading style------

        // ----order table style------
        $this->controls['order_table_style'] = [
			'group' => 'wpfunnels_checkout_order_tbl_style',
			'label' => esc_html__( 'Table Style', 'wpfnl' ),
			'type'  => 'separator',
		];
        $this->controls['your_order_text_typography'] = [
			'group' => 'wpfunnels_checkout_order_tbl_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-checkout table.woocommerce-checkout-review-order-table td',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-checkout table.woocommerce-checkout-review-order-table th',
                ],
			],
		];
        $this->controls['order_table_border_color'] = [
			'group' => 'wpfunnels_checkout_order_tbl_style',
			'label' => esc_html__( 'Border Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table',
				],
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table thead th',
				],
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table thead td',
				],
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table tbody th',
				],
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table tbody td',
				],				
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table tfoot td',
				],				
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table tfoot th',
				],				
			],
		];		
        $this->controls['order_table_radius'] = [
            'group' => 'wpfunnels_checkout_order_tbl_style',
            'label' => esc_html__( 'Border Radius', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border-radius',
                'selector' => '.wpfnl-checkout .woocommerce table.shop_table',
			  ]
            ],
			'exclude'  => [
				'width',
				'style',
				'color',
			],
            
        ];
        $this->controls['order_table_cell_padding'] = [
			'group' => 'wpfunnels_checkout_order_tbl_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table thead th',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table thead td',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table tbody th',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table tbody td',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table tfoot td',
				],
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table tfoot th',
				],
			],
		];
        $this->controls['order_table_margin'] = [
			'group' => 'wpfunnels_checkout_order_tbl_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce table.shop_table',
				],
				
			],
		];
        // ----end order table style------

    }

    
    /**
     * Register Payment Section Style Controls.
     *
     * @since  3.1.0
     * @access public
     */
    public function register_payment_section_style_controls(){
		$this->controls['payment_section_text_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Text Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout #payment .place-order',
				],
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout #payment .place-order p',
				],			
			],
		];	
		$this->controls['payment_section_link_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Link Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout #payment .place-order a',
				],
			],
		];	
		$this->controls['payment_section_bg_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment',
				],
			],
		];	
        $this->controls['payment_section_typography'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout #payment .place-order',
                ],				
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout #payment .place-order p',
                ],				
			],
		];
		$this->controls['payment_section_radius'] = [
            'group' => 'wpfunnels_checkout_payment_style',
            'label' => esc_html__( 'Border Radius', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border-radius',
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment',
			  ]
            ],
			'exclude'  => [
				'width',
				'style',
				'color',
			],
            
        ];
		// ----end payment section global style------


        // ----payment method style------
        $this->controls['payment_method_heading_style'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Payment Method Style', 'wpfnl' ),
			'type'  => 'separator',
		];
        $this->controls['payment_method_typography'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods li',
                ],
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box',
                ],
			],
		];	
        $this->controls['payment_method_bg_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods',
				],								
			],
		];	
		
		$this->controls['radio_button_separator'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'type'  => 'separator',
		];

		$this->controls['payment_method_radio_default_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Radio Button Default Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > label:before',
				],								
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > label:before',
				],								
			],
		];
		$this->controls['payment_method_radio_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Radio Button Active Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > input[type=radio]:checked + label:before',
				],								
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > input[type=radio]:checked + label:before',
				],								
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > input[type=radio]:checked + label:after',
				],								
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > input[type=radio]:checked + label:after',
				],								
			],
		];

		$this->controls['saved_payment_checkbox_separator'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'type'  => 'separator',
		];

		$this->controls['saved_payment_checkbox_default_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Checkbox Default Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > label:before',
				],								
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout #mailpoet_woocommerce_checkout_optin_field #mailpoet_woocommerce_checkout_optin',
				],								
			],
		];
		$this->controls['saved_payment_checkbox_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Checkbox Active Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > input[type=checkbox]:checked + label:before',
				],								
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout #mailpoet_woocommerce_checkout_optin_field #mailpoet_woocommerce_checkout_optin:checked',
				],																			
			],
		];
		$this->controls['saved_payment_checkbox_tic_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Checkbox Tic Sign Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > label:after',
				],								
			],
		];

		$this->controls['payment_method_border_separator'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'type'  => 'separator',
		];

        $this->controls['payment_method_border'] = [
            'group' => 'wpfunnels_checkout_payment_style',
            'label' => esc_html__( 'Border', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border-radius',
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods',
			  ]
            ],
        ];
        $this->controls['payment_method_padding'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods',
				],
				
			],
		];
        $this->controls['payment_method_margin'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods',
				],
				
			],
		];
        // ----end payment method style------


        // ----payment box style------
		$this->controls['payment_box_heading_style'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Payment Box Style', 'wpfnl' ),
			'type'  => 'separator',
		];
		$this->controls['payment_box_txt_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Text Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box',
				],								
			],
		];
		$this->controls['payment_box_bg_color'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box',
				],								
				[
					'property' => 'border-bottom-color',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box:before',
				],								
			],
		];
		$this->controls['payment_box_typography'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box',
                ]
			],
		];
		$this->controls['payment_box_radius'] = [
            'group' => 'wpfunnels_checkout_payment_style',
            'label' => esc_html__( 'Radius', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border-radius',
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box',
			  ]
            ],
			'exclude'  => [
				'width',
				'style',
				'color',
			],
            
        ];
		$this->controls['payment_box_padding'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box',
				],
				
			],
		];
        $this->controls['payment_box_margin'] = [
			'group' => 'wpfunnels_checkout_payment_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box',
				],
				
			],
		];
        // ----end payment box style------

    }


	/**
     * Register Order Button Style Controls.
     *
     * @since  3.1.0
     * @access public
     */
    public function register_order_btn_style_controls(){
		$this->controls['order_button_typography'] = [
			'group' => 'wpfunnels_checkout_order_btn_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce #payment #place_order',
                ],				
			],
		];
		$this->controls['order_button_bg_color'] = [
			'group' => 'wpfunnels_checkout_order_btn_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce #payment #place_order',
				],
			],
		];
		$this->controls['order_button_shadow'] = [
			'group' => 'wpfunnels_checkout_order_btn_style',
			'label' => esc_html__( 'Box Shadow', 'wpfnl' ),
			'type' => 'box-shadow',
			'css' => [
			  [
				'property' => 'box-shadow',
				'selector' => '.wpfnl-checkout .woocommerce #payment #place_order',
			  ],
			],
			'inline' => true,
			'small' => true,
		];

		// $this->controls['order_button_hover_style'] = [
		// 	'group' => 'wpfunnels_checkout_order_btn_style',
		// 	'label' => esc_html__( 'Button Hover Style', 'wpfnl' ),
		// 	'type'  => 'separator',
		// ];

		// $this->controls['order_button_radius_separator'] = [
		// 	'group' => 'wpfunnels_checkout_order_btn_style',
		// 	'type'  => 'separator',
		// ];

		$this->controls['order_button_radius'] = [
            'group' => 'wpfunnels_checkout_order_btn_style',
            'label' => esc_html__( 'Border Radius', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border-radius',
                'selector' => '.wpfnl-checkout .woocommerce #payment #place_order',
			  ]
            ],
        ];
		$this->controls['order_button_padding'] = [
			'group' => 'wpfunnels_checkout_order_btn_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce #payment #place_order',
				],
				
			],
		];
        $this->controls['order_button_margin'] = [
			'group' => 'wpfunnels_checkout_order_btn_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce #payment #place_order',
				],
				
			],
		];
	}


	/**
     * Register Coupon Form Style Controls. 
     *
     * @since  3.1.0
     * @access public
     */
    public function register_coupon_form_style_controls(){
		$this->controls['coupon_toggle_area_style'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Coupon Toggle Area', 'wpfnl' ),
			'type'  => 'separator',
		];
		$this->controls['coupon_toggle_color'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Text Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout .woocommerce-form-coupon-toggle .wc-block-components-notice-banner__content',
				],
			],
		];
		$this->controls['coupon_toggle_link_color'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Link Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout .woocommerce-form-coupon-toggle .wc-block-components-notice-banner__content a',
				],
			],
		];
		$this->controls['coupon_toggle_bgcolor'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce-form-coupon-toggle .wc-block-components-notice-banner',
				],
			],
		];
		$this->controls['coupon_toggle_typography'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce-form-coupon-toggle .wc-block-components-notice-banner__content',
                ],				
			],
		];
		$this->controls['coupon_toggle_border'] = [
            'group' => 'wpfunnels_checkout_coupon_style',
            'label' => esc_html__( 'Border', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border',
                'selector' => '.wpfnl-checkout .woocommerce-form-coupon-toggle .wc-block-components-notice-banner',
			  ]
            ],
        ];
		$this->controls['coupon_toggle_padding'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce-form-coupon-toggle .wc-block-components-notice-banner',
				],								
			],
		];
        $this->controls['coupon_toggle_margin'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce-form-coupon-toggle .wc-block-components-notice-banner',
				],
				
			],
		];

		$this->controls['coupon_form_box_style'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Coupon Form Box', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['coupon_form_box_color'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Text Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon p',
				],
			],
		];
		$this->controls['coupon_form_box_bgcolor'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon',
				],
			],
		];
		$this->controls['coupon_form_box_typography'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon p',
                ],				
			],
		];
		$this->controls['coupon_form_box_border'] = [
            'group' => 'wpfunnels_checkout_coupon_style',
            'label' => esc_html__( 'Border', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border',
                'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon',
			  ]
            ],
        ];
		$this->controls['coupon_form_box_padding'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon',
				],								
			],
		];
        $this->controls['coupon_form_box_margin'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Margin', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon',
				],
				
			],
		];

		$this->controls['coupon_input_field_style'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Coupon Input field', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['coupon_input_field_color'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon .input-text',
				],
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon ::placeholder',
				],
				[
					'property' => 'color',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon ::-webkit-input-placeholder',
				],
			],
		];
		$this->controls['coupon_input_field_bgcolor'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon .input-text',
				],
			],
		];
		$this->controls['coupon_input_field_typography'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon .input-text',
                ],				
			],
		];
		$this->controls['coupon_input_field_border'] = [
            'group' => 'wpfunnels_checkout_coupon_style',
            'label' => esc_html__( 'Border', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border',
                'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon .input-text',
			  ]
            ],
        ];
		$this->controls['coupon_input_field_padding'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon .input-text',
				],								
			],
		];

		$this->controls['coupon_button_style'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Coupon Button', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['coupon_button_typography'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon button.button',
                ],				
			],
		];
		$this->controls['coupon_button_bg_color'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon button.button',
				],				
			],
		];
		$this->controls['coupon_button_box_shadow'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Box Shadow', 'wpfnl' ),
			'type' => 'box-shadow',
			'css' => [
			  [
				'property' => 'box-shadow',
				'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon button.button',
			  ],
			],
			'inline' => true,
			'small' => true,
		];
		$this->controls['coupon_button_radius'] = [
            'group' => 'wpfunnels_checkout_coupon_style',
            'label' => esc_html__( 'Border Radius', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border-radius',
                'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon button.button',
			  ]
            ],
			'exclude'  => [
				'width',
				'style',
				'color',
			],
        ];
		$this->controls['coupon_button_padding'] = [
			'group' => 'wpfunnels_checkout_coupon_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-checkout .woocommerce form.woocommerce-form-coupon button.button',
				],								
			],
		];

	}


	/**
     * Register Form valiation info Style Controls. 
     *
     * @since  3.1.0
     * @access public
     */
    public function register_error_style_controls(){
		$this->controls['error_fields_text'] = [
			'group' => 'wpfunnels_checkout_error_style',
			'label' => esc_html__( 'Field Validation', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['error_label_color'] = [
			'group' => 'wpfunnels_checkout_error_style',
			'label' => esc_html__( 'Label Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.woocommerce form .form-row.woocommerce-invalid label',
				],
			],
		];
		$this->controls['error_field_border_color'] = [
			'group' => 'wpfunnels_checkout_error_style',
			'label' => esc_html__( 'Field Border Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.wpfunnels-checkout-form .select2-container--default.field-required .select2-selection--single',
				],
				[
					'property' => 'border-color',
					'selector' => 'form .form-row input.input-text.field-required',
				],
				[
					'property' => 'border-color',
					'selector' => 'form .form-row textarea.input-text.field-required',
				],
				[
					'property' => 'border-color',
					'selector' => '#order_review .input-text.field-required',
				],
				[
					'property' => 'border-color',
					'selector' => 'form .form-row.woocommerce-invalid .select2-container',
				],
				[
					'property' => 'border-color',
					'selector' => 'form .form-row.woocommerce-invalid input.input-text',
				],
				[
					'property' => 'border-color',
					'selector' => 'form .form-row.woocommerce-invalid select',
				],
			],
		];

		$this->controls['error_fields_section'] = [
			'group' => 'wpfunnels_checkout_error_style',
			'label' => esc_html__( 'Error Messages', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['error_text_color'] = [
			'group' => 'wpfunnels_checkout_error_style',
			'label' => esc_html__( 'Error Message Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.woocommerce-error',
				],
				[
					'property' => 'color',
					'selector' => '.woocommerce-NoticeGroup .woocommerce-error',
				],
				[
					'property' => 'color',
					'selector' => '.woocommerce-notices-wrapper .woocommerce-error',
				],
			],
		];

		$this->controls['error_bg_color'] = [
			'group' => 'wpfunnels_checkout_error_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.woocommerce-error',
				],
				[
					'property' => 'color',
					'selector' => '.woocommerce-NoticeGroup .woocommerce-error',
				],
				[
					'property' => 'color',
					'selector' => '.woocommerce-NoticeGroup .wc-block-components-notice-banner.is-error',
				],
				[
					'property' => 'color',
					'selector' => '.woocommerce-notices-wrapper .woocommerce-error',
				],
			],
		];

		$this->controls['error_border_color'] = [
			'group' => 'wpfunnels_checkout_error_style',
			'label' => esc_html__( 'Border Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.woocommerce-error',
				],
				[
					'property' => 'border-color',
					'selector' => '.woocommerce-NoticeGroup .woocommerce-error',
				],
				[
					'property' => 'border-color',
					'selector' => '.woocommerce-NoticeGroup .wc-block-components-notice-banner.is-error',
				],
				[
					'property' => 'border-color',
					'selector' => '.woocommerce-notices-wrapper .woocommerce-error',
				],
				[
					'property' => 'color',
					'selector' => '.woocommerce-error::before',
				],
				[
					'property' => 'background-color',
					'selector' => '.wc-block-components-notice-banner.is-error>svg',
				],
			],
		];
	}


	/**
     * Register Multistep Style Controls. 
     *
     * @since  3.1.0
     * @access public
     */
    public function register_multistep_style_controls(){
		$this->controls['step_title_heading'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Step Title', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['step_title_color'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Color', 'wpfnl' ),
			'type'  => 'color',
			'default' => '#363B4E',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li .step-title',
				],
			],
		];
		$this->controls['step_title_typography'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li .step-title',
                ],				
			],
		];

		$this->controls['multistep_checkout_state'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Step Default State', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['step_box_shadow'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Box Shadow', 'wpfnl' ),
			'type' => 'box-shadow',
			'css' => [
				[
					'property' => 'box-shadow',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li .step-icon',
				],
			],
			'inline' => true,
			'small' => true,
		];
		$this->controls['step_normal_line_color'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Line Color', 'wpfnl' ),
			'type'  => 'color',
			'default' => '#eee',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard:before',
				],				
			],
		];
		$this->controls['step_normal_box_bgcolor'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Box Background Color', 'wpfnl' ),
			'type'  => 'color',
			'default' => '#e8e8ed',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li .step-icon',
				],				
			],
		];
		$this->controls['step_normal_icon_color'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Icon Color', 'wpfnl' ),
			'type'  => 'color',
			'default' => '#6E42D3',
			'css'   => [
				[
					'property' => 'fill',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li .step-icon svg path',
				],				
			],
		];
		$this->controls['step_normal_box_border_style'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Border', 'wpfnl' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li .step-icon',
				],				
			],
			'exclude' => [
				'radius'
			],
		];

		$this->controls['multistep_checkout_active'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Step Active / Completed State', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['step_active_line_color'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Line Color', 'wpfnl' ),
			'type'  => 'color',
			'default' => '#6E42D3',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard > li.completed:before',
				],				
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard > li.current:before',
				],				
			],
		];
		$this->controls['step_active_box_bgcolor'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Box Background Color', 'wpfnl' ),
			'type'  => 'color',
			'default' => '#6E42D3',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li.completed .step-icon',
				],				
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li.current .step-icon',
				],				
			],
		];
		$this->controls['step_active_icon_color'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Icon Color', 'wpfnl' ),
			'type'  => 'color',
			'default' => '#ffffff',
			'css'   => [
				[
					'property' => 'fill',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li.completed .step-icon svg path',
				],				
				[
					'property' => 'fill',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li.current .step-icon svg path',
				],				
			],
		];
		$this->controls['step_active_box_border_style'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Border', 'wpfnl' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li.completed .step-icon',
				],				
				[
					'property' => 'border',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li.current .step-icon',
				],				
			],
			'exclude' => [
				'radius'
			],
		];

		$this->controls['step_navigation_btn'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Navigation Button', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['step_navigation_btn_color'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]',
				],												
			],
		];
		$this->controls['step_navigation_btn_bgcolor'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]',
				],												
			],
		];
		$this->controls['step_navigation_btn_typography'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Typography', 'wpfnl' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]',
                ],				
			],
		];
		$this->controls['step_navigation_btn_radius'] = [
            'group' => 'wpfunnels_checkout_multistep_style',
            'label' => esc_html__( 'Border Radius', 'wpfnl' ),
            'type' => 'border',
            'css' => [
              [
                'property' => 'border-radius',
                'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]',
			  ]
            ],
			'exclude' => [
				'width',
				'style',
				'color',
			]
        ];
		$this->controls['step_navigation_btn_padding'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Padding', 'wpfnl' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]',
				],								
			],
		];

		$this->controls['step_navigation_btn_hover'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Navigation Button Hover', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['step_navigation_btn_hover_color'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]:not(:disabled):hover',
				],												
			],
		];
		$this->controls['step_navigation_btn_hover_bgcolor'] = [
			'group' => 'wpfunnels_checkout_multistep_style',
			'label' => esc_html__( 'Background Color', 'wpfnl' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]:not(:disabled):hover',
				],												
			],
		];

	}

    
    /**
     * Set builder control groups
     * 
     * @since 3.1.0
      * @access public
     */
    public function set_control_groups()
    {
        $this->control_groups['wpfunnels_checkout_content'] = [
            'title' => esc_html__('Funnel Checkout', 'wpfnl'), // Localized control group title
            'tab' => 'content', // Set to either "content" or "style"
        ];

        $this->control_groups['wpfunnels_checkout_billing_style'] = [
            'title' => esc_html__('Billing Section', 'wpfnl'),
            'tab' => 'style', // Set to either "content" or "style"
        ];

        $this->control_groups['wpfunnels_checkout_shipping_style'] = [
            'title' => esc_html__('Additional Info / Shipping Section', 'wpfnl'),
            'tab' => 'style', // Set to either "content" or "style"
        ];

        $this->control_groups['wpfunnels_checkout_order_tbl_style'] = [
            'title' => esc_html__('Order Table Section', 'wpfnl'),
            'tab' => 'style', // Set to either "content" or "style"
        ];

        $this->control_groups['wpfunnels_checkout_payment_style'] = [
            'title' => esc_html__('Payment Section', 'wpfnl'),
            'tab' => 'style', // Set to either "content" or "style"
        ];

        $this->control_groups['wpfunnels_checkout_order_btn_style'] = [
            'title' => esc_html__('Order button', 'wpfnl'),
            'tab' => 'style', // Set to either "content" or "style"
        ];

        $this->control_groups['wpfunnels_checkout_coupon_style'] = [
            'title' => esc_html__('Coupon Section', 'wpfnl'),
            'tab' => 'style', // Set to either "content" or "style"
        ];

        $this->control_groups['wpfunnels_checkout_error_style'] = [
            'title' => esc_html__('Field Validation & Error Messages', 'wpfnl'),
            'tab' => 'style', // Set to either "content" or "style"
        ];

        $this->control_groups['wpfunnels_checkout_multistep_style'] = [
            'title' => esc_html__('Multistep Section', 'wpfnl'),
            'tab' => 'style', // Set to either "content" or "style"
			'required'    => [ 'checkout_layout', '=', [ 'wpfnl-2-step', 'wpfnl-multistep', 'wpfnl-express-checkout' ] ],
        ];
    }


    /**
     * Set builder controls
     * 
     * @since 3.1.0
     */
    public function set_controls(){
        $this->checkout_content_control();
        // $this->ob_register_control();

        //---------style controls---------
        $this->register_billing_style_controls();
        $this->register_shipping_style_controls();
        $this->register_order_tbl_style_controls();
        $this->register_payment_section_style_controls();
        $this->register_order_btn_style_controls();
        $this->register_coupon_form_style_controls();
        $this->register_error_style_controls();
        $this->register_multistep_style_controls();
        
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
    public function render(){
        // We need the form element ID to recover the element settings on form submit
		$this->set_attribute( '_root', 'data-element-id', $this->id );

        $settings = $this->settings;
        $checkout = WC()->checkout();
        $checkout_id  = $this->post_id;

        //----Start Coupon Enabler----//
        $coupon_enabler = get_post_meta($this->post_id, '_wpfnl_checkout_coupon', true);
        if ( $coupon_enabler != 'yes' ) {
            remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
        }
        //----End Coupon Enabler----//

        $checkout_layout = isset($settings['checkout_layout']) ? $settings['checkout_layout'] : 'two-column';
		$floating_label = isset($settings['checkout_floating_label']) ? $settings['checkout_floating_label'] : '';

        if( PHP_SESSION_DISABLED == session_status() ) {
			session_start();
		}
		$_SESSION[ 'checkout_layout' ] = $checkout_layout;

        /**
         * Check if pro is activated or not 
        */
        if( !Wpfnl_functions::is_wpfnl_pro_activated() && ('wpfnl-multistep' === $checkout_layout || 'wpfnl-express-checkout' === $checkout_layout) ) {
			$checkout_layout = 'wpfnl-col-2';
		}

        /**
         * Check if pro is activated and wpfnl-express-checkout selected 
        */
        if( Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-express-checkout' === $checkout_layout ) {
			$checkout_layout .= ' wpfnl-multistep';
		}

        /**
         * Check if pro is activated and wpfnl-express-checkout selected 
        */
        if( Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-2-step' === $checkout_layout ) {
			$checkout_layout .= ' wpfnl-multistep';
		}
        query_posts('post_type="checkout"');

        // load woo templates from wpf plugin
		$frontend = Wpfnl::get_instance()->frontend;
		add_filter('woocommerce_locate_template', array($frontend, 'wpfunnels_woocommerce_locate_template'), 20, 3);


		$ob_settings = $this->get_order_bump_settings_for_preview($checkout_id);
		do_action('wpfunnels/bricks_render_order_bump', $checkout_id, $ob_settings);
	
		do_action( 'wpfunnels/before_bricks_checkout_form', $settings );
		do_action( 'wpfunnels/before_checkout_form', $checkout_id );

        ?>
		<div <?php echo $this->render_attributes( '_root' ); ?> >
            <div class="wpfnl-checkout <?php echo $checkout_layout .' '. $floating_label ?>">

                <?php echo do_shortcode('[woocommerce_checkout]'); ?>

                <!-- when checkout layout Express Checkout selected then show -->
                <?php if( Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-express-checkout' === $settings['checkout_layout'] ){
                    ?>
                <?php } ?>
            </div>
		</div>
	    <?php
    }


	/**
	 * Get order bump settings for preview
	 *
	 * @param $post_id
	 * @param $widget_settings
	 * 
	 * @return mixed
	 *
	 * @since 2.0.4
	 */
	private function get_order_bump_settings_for_preview( $post_id ) {
		$order_bump_settings 	= get_post_meta( $post_id, 'order-bump-settings', true );
		return $order_bump_settings;
	}

	/**
	 * Replace ob settings with widget data
	 *
	 * @param $order_bump_settings
	 * @param $widget_settings
	 * 
	 * @return mixed
	 *
	 * @since 2.0.4
	 */
	private function replace_ob_settings_with_widget_data( $order_bump_settings, $widget_settings ) {
		foreach( $order_bump_settings as $key=>$settings ){
			$order_bump_settings[$key]['checkBoxLabel'] 			= isset( $widget_settings['order_bump_checkbox_label_'.$key] ) ? $widget_settings['order_bump_checkbox_label_'.$key] : $order_bump_settings[$key]['checkBoxLabel'];
			$order_bump_settings[$key]['highLightText'] 			= isset( $widget_settings['order_bump_product_detail_header_'.$key] ) ? $widget_settings['order_bump_product_detail_header_'.$key] : $order_bump_settings[$key]['highLightText'];
			$order_bump_settings[$key]['productDescriptionText'] 	= isset( $widget_settings['order_bump_product_detail_'.$key] ) ? $widget_settings['order_bump_product_detail_'.$key] : $order_bump_settings[$key]['productDescriptionText'];
			$order_bump_settings[$key]['position'] 				    = isset( $widget_settings['order_bump_position_'.$key] ) ? $widget_settings['order_bump_position_'.$key] : $order_bump_settings[$key]['position'];
			$order_bump_settings[$key]['selectedStyle'] 			= isset( $widget_settings['order_bump_layout_'.$key] ) ? $widget_settings['order_bump_layout_'.$key] : $order_bump_settings[$key]['selectedStyle'];
			$order_bump_settings[$key]['productImage'] 			    = isset( $widget_settings['order_bump_image_'.$key] ) ? $widget_settings['order_bump_image_'.$key] : $order_bump_settings[$key]['productImage'];
			$order_bump_settings[$key]['isEnabled'] 				= isset( $widget_settings['order_bump_'.$key] ) ? $widget_settings['order_bump_'.$key] : $order_bump_settings[$key]['isEnabled'];
		}
		return $order_bump_settings;
	}



    /**
     * Render order bump data with hooks.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    public function render_order_bump()
    {
        $step_id = $this->post_id;
        $order_bump = get_post_meta($step_id, 'order-bump', true);
        $order_bump_settings = get_post_meta($step_id, 'order-bump-settings', true);
        foreach( $order_bump_settings as $key=>$ob_settings ){
            if ( $ob_settings['isEnabled'] && isset($ob_settings['product']) && $ob_settings['product'] != '' ) {
                $this->render_order_bump_template($ob_settings);
            }
        }
    }

    public function render_order_bump_template($settings)
    {
        if (!empty($settings['selectedStyle'])) {

            if ($settings['position'] == 'popup') {
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    echo '<h5 style="margin-bottom: 15px;"><strong>' . __('To see the pop-up offer in action, please preview or view the page.', 'wpfnl') . '</strong></h5>';
                } else {
                    require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
                }

            } else {
                require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
            }
        }
    }

}
