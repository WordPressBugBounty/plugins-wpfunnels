<?php

namespace WPFunnels\Widgets\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Icons_Manager;
use WPFunnels\Wpfnl_functions;
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Funnel sell accept button
 *
 * @since 1.0.0
 */
class Offer_Button extends Widget_Base
{
    

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
     * @return string Widget name.
     * @since 1.0.0
     *
     * @access public
     *
     */
    public function get_name()
    {
        return 'wpfnl-offer';
    }

    /**
     * Retrieve the widget title.
     *
     * @return string Widget title.
     * @since 1.0.0
     *
     * @access public
     *
     */
    public function get_title()
    {
        return __('Offer Button', 'wpfnl');
    }

    /**
     * Retrieve the widget icon.
     *
     * @return string Widget icon.
     * @since 1.0.0
     *
     * @access public
     *
     */
    public function get_icon()
    {
        return 'icon-wpfnl sell-accept';
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * Note that currently Elementor supports only one category.
     * When multiple categories passed, Elementor uses the first one.
     *
     * @return array Widget categories.
     * @since 1.0.0
     *
     * @access public
     *
     */
    public function get_categories()
    {
        return ['wp-funnel'];
    }

    /**
     * Retrieve the list of scripts the widget depended on.
     *
     * Used to set scripts dependencies required to run the widget.
     *
     * @return array Widget scripts dependencies.
     * @since 1.0.0
     *
     * @access public
     *
     */
    public function get_script_depends()
    {
        return ['upsell-downsell-widget'];
    }

    /**
     * Get button sizes.
     *
     * Retrieve an array of button sizes for the button widget.
     *
     * @return array An array containing button sizes.
     * @since 1.0.0
     * @access public
     * @static
     *
     */
    public static function get_button_sizes()
    {
        return [
            'xs' => __('Extra Small', 'wpfnl-pro'),
            'sm' => __('Small', 'wpfnl-pro'),
            'md' => __('Medium', 'wpfnl-pro'),
            'lg' => __('Large', 'wpfnl-pro'),
            'xl' => __('Extra Large', 'wpfnl-pro'),
        ];
    }

    /**
     * Register the widget controls.
     * @since 1.0.0
     *
     * @access protected
     */
    protected function _register_controls()
    {
        //----content funtion----
        
        $this->register_dynamic_data_template_content();
        $this->register_offer_button_content();

        //----style funtion----
        $this->register_dynamic_data_template_style();
        $this->register_offer_button_style();

    }


    /**
     * Register the widget controls.
     * @since 1.0.0
     *
     * @access protected
     */
    protected function register_controls()
    {
        //----content funtion----
        $this->register_offer_button_content();
        $this->register_dynamic_data_template_content();

        //----style funtion----
        $this->register_dynamic_data_template_style();
        $this->register_offer_button_style();
    }


    /**
     * Register Dynamic Data Template content Control.
     *
     * @since x.x.x
     * @access protected
     */
    protected function register_dynamic_data_template_content(){


        $this->start_controls_section(
            'dynamic_data_template_content',
            array(
                'label' => __('Template Content', 'wpfnl-pro'),
                'condition' => array(
                    'show_product_data' => 'yes',
                    'offer_type' => 'accept',
                ),
            )
        );

        $this->add_control(
            'dynamic_data_template_layout',
            [
                'label' => __('Select Template Style ', 'wpfnl-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'style1',
                'options' => [
                    'style1' => __('Left Image Right Content', 'wpfnl-pro'),
                    'style2' => __('Left Content Right Image', 'wpfnl-pro'),
                    'style3' => __('Top Image Bottom Content', 'wpfnl-pro'),
                ],
                
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Dynamic Data Template Style Control.
     *
     * @since x.x.x
     * @access protected
     */
    protected function register_dynamic_data_template_style(){
        
        $this->start_controls_section(
            'dynamic_data_template_style',
            [
                'label' => __('Template Style', 'wpfnl-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_product_data' => 'yes',
                    'offer_type' => 'accept',
                ),
            ]
        );

        // ----Template Layout Style-----
        $this->add_control(
            'template_layout_style',
            [
                'label' => __('Layout Style', 'wpfnl-pro'),
                'type' => Controls_Manager::HEADING,
                'label_block' => true,
            ]
        );


        //-----left column for style-1------
        $this->add_responsive_control(
			'template_left_col_width',
			[
				'label' => esc_html__( 'Image Column Width', 'wpfnl-pro' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1920,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => '%',
					'size' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .dynamic-offer-template-default .template-left' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'template_right_col_width',
			[
				'label' => esc_html__( 'Content Column Width', 'wpfnl-pro' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1920,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => '%',
					'size' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .dynamic-offer-template-default .template-right' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'template_col_gutter_width',
			[
				'label' => esc_html__( 'Column Gutter Width', 'wpfnl-pro' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .dynamic-offer-template-default .template-right' => 'padding-left: {{SIZE}}{{UNIT}};',
				],
                'condition' => array(
                    'dynamic_data_template_layout[value]' => 'style1',
                ),
			]
		);

        //--when template style-2 select then this control will show---
        $this->add_responsive_control(
			'template_col_right_gutter_width',
			[
				'label' => esc_html__( 'Column Gutter Width', 'wpfnl-pro' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .dynamic-offer-template-default .template-right' => 'padding-right: {{SIZE}}{{UNIT}};',
				],
                'condition' => array(
                    'dynamic_data_template_layout[value]' => 'style2',
                ),
			]
		);

        //--when template style-3 select then this control will show---
        $this->add_responsive_control(
			'template_col_top_gutter_width',
			[
				'label' => esc_html__( 'Column Gutter Width', 'wpfnl-pro' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .dynamic-offer-template-default .template-right' => 'padding-top: {{SIZE}}{{UNIT}};',
				],
                'condition' => array(
                    'dynamic_data_template_layout[value]' => 'style3',
                ),
			]
		);

        $this->add_responsive_control(
            'template_layout_radius',
            [
                'label' => __('Radius', 'wpfnl-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                
            ]
        );

        $this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'template_layout_shadow',
				'label' => __( 'Box Shadow', 'wpfnl-pro' ),
				'selector' => '{{WRAPPER}} .dynamic-offer-template-default',
			]
		);

        $this->add_responsive_control(
            'template_layout_padding',
            [
                'label' => __('Padding', 'wpfnl-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'template_layout_margin',
            [
                'label' => __('Margin', 'wpfnl-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'after',
            ]
        );

        // ----Template Content Style-----
        $this->add_control(
            'template_content_heading',
            [
                'label' => __('Template Image Style', 'wpfnl-pro'),
                'type' => Controls_Manager::HEADING,
                'label_block' => true,
            ]
        );

        $this->add_responsive_control(
			'template_image_width',
			[
				'label' => esc_html__( 'Image Width', 'wpfnl-pro' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1920,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => '%',
					'size' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .dynamic-offer-template-default .product-img img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
            'template_image_radius',
            [
                'label' => __('Image Radius', 'wpfnl-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default .product-img img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'after',
                
            ]
        );

        $this->add_control(
            'template_heading_style',
            [
                'label' => __('Heading Style', 'wpfnl-pro'),
                'type' => Controls_Manager::HEADING,
                'label_block' => true,
            ]
        );
        $this->add_control(
            'template_heading_color',
            [
                'label' => __('Color', 'wpfnl-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default .template-content .template-product-title' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'template_heading_typography',
                'label' => 'Typography',
                'selector' => '{{WRAPPER}} .dynamic-offer-template-default .template-content .template-product-title',
            ]
        );
        $this->add_responsive_control(
            'template_heading_margin',
            [
                'label' => __('Margin', 'wpfnl-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default .template-content .template-product-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'after',
            ]
        );


        // ----Template description Style-----
        $this->add_control(
            'template_description_style',
            [
                'label' => __('Description Style', 'wpfnl-pro'),
                'type' => Controls_Manager::HEADING,
                'label_block' => true,
            ]
        );

        $this->add_control(
            'template_description_color',
            [
                'label' => __('Color', 'wpfnl-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default .template-content .template-product-description' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'template_description_typography',
                'label' => 'Typography',
                'selector' => '{{WRAPPER}} .dynamic-offer-template-default .template-content .template-product-description',
            ]
        );
        $this->add_responsive_control(
            'template_description_margin',
            [
                'label' => __('Margin', 'wpfnl-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default .template-content .template-product-description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'after',
            ]
        );


        // ----Template price Style-----
        $this->add_control(
            'template_price_style',
            [
                'label' => __('Price Style', 'wpfnl-pro'),
                'type' => Controls_Manager::HEADING,
                'label_block' => true,
            ]
        );

        $this->add_control(
            'template_price_color',
            [
                'label' => __('Regular Price Color', 'wpfnl-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del bdi, .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'template_discount_price_color',
            [
                'label' => __('Discount Price Color', 'wpfnl-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price bdi' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'template_discount_price_typography',
                'label' => 'Discount Price Typography',
                'selector' => '{{WRAPPER}} .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price bdi',
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'template_price_typography',
                'label' => 'Regular Price Typography',
                'selector' => '{{WRAPPER}} .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del bdi, 
                                {{WRAPPER}} .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price',
            ]
        );
        $this->add_responsive_control(
            'template_price_margin',
            [
                'label' => __('Margin', 'wpfnl-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'after',
            ]
        );

        $this->end_controls_section();
    }

    
    /**
     * Register Offer Button Content Control.
     *
     * @since x.x.x
     * @access protected
     */
    protected function register_offer_button_content(){
        $this->start_controls_section(
            'section_button',
            array(
                'label' => __('Upsell/Downsell', 'wpfnl-pro'),
            )
        );

        $this->add_control(
            'offer_button_type',
            [
                'label' => __('Select button type', 'wpfnl-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'upsell',
                'options' => [
                    'upsell' => __('Upsell', 'wpfnl-pro'),
                    'downsell' => __('Downsell', 'wpfnl-pro'),
                ],
            ]
        );

        $this->add_control(
            'offer_type',
            [
                'label' => __('Select button action', 'wpfnl-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'accept',
                'options' => array(
                    'accept' => __('Accept', 'wpfnl-pro'),
                    'reject' => __('Reject', 'wpfnl-pro'),
                )
            ]
        );

        $this->add_control(
            'text',
            [
                'label' => __('Text', 'wpfnl-pro'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Accept Offer', 'wpfnl-pro'),
                'placeholder' => __('Accept Offer', 'wpfnl-pro'),
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'align',
            [
                'label' => __('Alignment', 'wpfnl-pro'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'wpfnl-pro'),
                        'icon' => 'fa fa-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'wpfnl-pro'),
                        'icon' => 'fa fa-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'wpfnl-pro'),
                        'icon' => 'fa fa-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justified', 'wpfnl-pro'),
                        'icon' => 'fa fa-align-justify',
                    ],
                ],
                'prefix_class' => 'elementor%s-align-',
                'default' => '',
            ]
        );

        $this->add_responsive_control(
            'size',
            [
                'label' => __('Size', 'wpfnl-pro'),
                'type' => Controls_Manager::SELECT,
                'default' => 'sm',
                'options' => self::get_button_sizes(),
            ]
        );

        $this->add_control(
            'upsell_downsell_button_icon',
            [
                'label' => __('Icon', 'wpfnl'),
                'type' => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
            ]
        );

        $this->add_control(
            'upsell_downsell_button_icon_align',
            [
                'label' => __('Icon Position', 'wpfnl-pro'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => array(
                    'left' => __('Before', 'wpfnl-pro'),
                    'right' => __('After', 'wpfnl-pro'),
                ),
                'condition' => array(
                    'upsell_downsell_button_icon[value]!' => '',
                ),
            ]
        );

        $this->add_control(
            'upsell_downsell_button_icon_indent',
            [
                'label' => __('Icon Spacing', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 50,
                    ],
                ],
                'condition' => [
                    'upsell_downsell_button_icon!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button .elementor-align-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .elementor-button .elementor-align-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'view',
            [
                'label' => __('View', 'wpfnl-pro'),
                'type' => Controls_Manager::HIDDEN,
                'default' => 'traditional',
            ]
        );

        $get_product_type = Wpfnl_functions::get_offer_product_type( get_the_ID() );

        if( $get_product_type == 'variable' || $get_product_type == 'variable-subscription' ){
            $this->add_control(
                'variation_tbl_title',
                [
                    'label' => __('Variation Table Title', 'wpfnl-pro'),
                    'type' => Controls_Manager::TEXTAREA,
                    'separator' => 'before',
                    'condition' => [
                        'offer_type' => 'accept',
                    ],
                ]
            );
        }

        $this->add_control(
            'show_product_price',
            [
                'label' => esc_html__( 'Show Product Price', 'wpfnl-pro' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'wpfnl-pro' ),
                'label_off' => esc_html__( 'Hide', 'wpfnl-pro' ),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'offer_type' => 'accept',
                ],
            ]
        );

        $this->add_control(
            'product_price_alignment',
            [
                'label' => __('Price Alignment ', 'wpfnl-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    ''              => __('On The Left Of Button', 'wpfnl-pro'),
                    'price-right'   => __('On The Right Of Button', 'wpfnl-pro'),
                    'price-top'     => __('Above The Button', 'wpfnl-pro'),
                    'price-bottom'  => __('Below The Button', 'wpfnl-pro'),
                ],
                'condition' => [
                    'show_product_price' => 'yes',
                ],
                
            ]
        );

        $funnel_id  = get_post_meta( get_the_ID(), '_funnel_id', true );
        $is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true );
        
        if( 'yes' === $is_gbf ){
            $this->add_control(
                'show_product_data',
                [
                    'label' => esc_html__( 'Show Product Data', 'wpfnl-pro' ),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => esc_html__( 'Show', 'wpfnl-pro' ),
                    'label_off' => esc_html__( 'Hide', 'wpfnl-pro' ),
                    'return_value' => 'yes',
                    'default' => 'off',
                    'condition' => [
                        'offer_type' => 'accept',
                    ],
                ]
            );
        }
        
        $this->end_controls_section();

    }


    /**
     * Register Offer Button Style Control.
     *
     * @since x.x.x
     * @access protected
     */
    protected function register_offer_button_style(){

        $this->start_controls_section(
            'button_section_style',
            [
                'label' => __('Button', 'wpfnl-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'upsell_downsell_button_typography',
                'label' => 'Typography',
                'selector' => '{{WRAPPER}} a.elementor-button',
            ]
        );

        $this->add_group_control(
            Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'upsell_downsell_button_text_shadow',
                'selector' => '{{WRAPPER}} a.elementor-button',
            ]
        );

        $this->start_controls_tabs('tabs_button_style');

        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => __('Normal', 'wpfnl-pro'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'wpfnl-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button, {{WRAPPER}} .elementor-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => __('Background Color', 'wpfnl-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#61CE70',
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button, {{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => __('Hover', 'wpfnl-pro'),
            ]
        );

        $this->add_control(
            'hover_color',
            [
                'label' => __('Text Color', 'wpfnl-pro'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button:hover, {{WRAPPER}} .elementor-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_hover_color',
            [
                'label' => __('Background Color', 'wpfnl-pro'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button:hover, {{WRAPPER}} .elementor-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => __('Border Color', 'wpfnl-pro'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'border_border!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button:hover, {{WRAPPER}} .elementor-button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'border',
                'label' => __('Border', 'wpfnl-pro'),
                'placeholder' => '1px',
                'default' => '1px',
                'selector' => '{{WRAPPER}} .elementor-button',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => __('Border Radius', 'wpfnl-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button, {{WRAPPER}} .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .elementor-button',
            ]
        );

        $this->add_responsive_control(
            'upsell_downsell_padding',
            [
                'label' => __('Padding', 'wpfnl-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button, {{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();

    }


    public function get_prev_next_link_options()
    {
        $associate_funnel_id = get_post_meta(get_the_ID(), '_funnel_id', true);
        $steps_array = [
            'upsell' => 'Upsell',
            'downsell' => 'Downsell',
            'thankyou' => 'Thankyou'
        ];
        $option_group = [];
        foreach ($steps_array as $key => $value) {
            $args = [
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_type' => WPFNL_STEPS_POST_TYPE,
                'post_status' => 'publish',
                'post__not_in' => [$this->get_id()],
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => '_step_type',
                        'value' => $key,
                        'compare' => '=',
                    ],
                    [
                        'key' => '_funnel_id',
                        'value' => $associate_funnel_id,
                        'compare' => '=',
                    ],
                ],
            ];
            $query = new \WP_Query($args);
            $steps = $query->posts;
            if ($steps) {
                foreach ($steps as $s) {
                    $option_group[$key][] = [
                        'id' => $s->ID,
                        'title' => $s->post_title,
                    ];
                }
            }
        }
        return $option_group;
    }

    /**
     * Get all WC products
     * @since 1.0.0
     *
     * @access protected
     */
    protected function get_products_array()
    {
        $products = array();
        if (in_array('woocommerce/woocommerce.php', WPFNL_ACTIVE_PLUGINS)) {
            $ids = wc_get_products(array('return' => 'ids', 'limit' => -1));
            if( !empty($ids) ){
                foreach ($ids as $id) {
                    $title = get_the_title($id);
                    $products[$id] = $title;
                }
            }
        }
        return $products;
    }

    /**
     * Get all funnel steps
     * @since 1.0.0
     *
     * @access protected
     */
    protected function get_steps_array($type = 'upsell')
    {
        $options = $this->get_prev_next_link_options();
        $response = array();
        if (isset($options[$type])) {
            $prime_data = $options[$type];
            foreach ($prime_data as $data) {
                $response[$data['id']] = $data['title'];
            }
        }

        return $response;
    }

    /**
     * Render the widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function render()
    {
        
        $settings = $this->get_settings();
        $this->add_render_attribute('wrapper', 'class', 'elementor-button-wrapper');

        $this->add_render_attribute('button', 'class', 'elementor-button');
        $this->add_render_attribute(
            'button',
            array(
                'id' => ['wpfunnels_'.$settings['offer_button_type'].'_'.$settings['offer_type']],
                'class' => [ 'wpfunnels-elementor-widget', 'wpfunnels_offer_button' ],
            )
        );

        if (!empty($settings['size'])) {
            $this->add_render_attribute('button', 'class', 'elementor-size-' . $settings['size']);
        }

        if ( isset($settings['hover_animation']) && $settings['hover_animation']) {
            $this->add_render_attribute('button', 'class', 'elementor-animation-' . $settings['hover_animation']);
        }
        $response = Wpfnl_functions::get_product_data_for_widget( get_the_ID() );
        $offer_product       = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';
        $get_product_type    = isset($response['get_product_type']) && $response['get_product_type'] ? $response['get_product_type'] : '';
        $is_gbf              = isset($response['is_gbf']) && $response['is_gbf'] ? $response['is_gbf'] : '';
        $builder = 'elementor';
       
        if( 'yes' == $is_gbf && $settings['show_product_data'] == 'yes' && $settings['offer_type'] == 'accept' ){ 
            require WPFNL_DIR . 'public/modules/dynamic-offer-templates/styles/offer-' . $settings['dynamic_data_template_layout'] . '.php';
        }else{
            require WPFNL_DIR . 'public/modules/dynamic-offer-templates/elementor/offer-button.php';
        }
        
    }

    /**
     * Render button text.
     *
     * Render button widget text.
     *
     * @since 1.5.0
     * @access protected
     */
    protected function render_text()
    {
        $settings = $this->get_settings();

        $migrated = isset($settings['__fa4_migrated']['upsell_downsell_button_icon']);
        $is_new = empty($settings['icon']) && Icons_Manager::is_migration_allowed();

        if (!$is_new && empty($settings['upsell_downsell_button_icon_align'])) {

            $settings['upsell_downsell_button_icon_align'] = $this->get_settings('upsell_downsell_button_icon_align');
        }

        $this->add_render_attribute([
            'content-wrapper' => [
                'class' => 'elementor-button-content-wrapper',
            ],
            'icon-align' => [
                'class' => [
                    'elementor-button-icon',
                    'elementor-align-icon-' . $settings['upsell_downsell_button_icon_align'],
                ],
            ],
            'text' => [
                'class' => 'elementor-button-text',
            ],
        ]);


        $this->add_render_attribute('content-wrapper', 'class', 'elementor-button-content-wrapper');
        $this->add_render_attribute('icon-align', 'class', 'elementor-button-icon');

        $this->add_render_attribute('text', 'class', 'elementor-button-text');
        $this->add_inline_editing_attributes('text', 'none');
        ?>
        <span <?php echo $this->get_render_attribute_string('content-wrapper'); ?>>
            <?php if (!empty($settings['icon']) || !empty($settings['upsell_downsell_button_icon']['value'])) : ?>
                <span <?php echo $this->get_render_attribute_string('icon-align'); ?>>
                    <?php if ($is_new || $migrated) :
                        Icons_Manager::render_icon($settings['upsell_downsell_button_icon'], ['aria-hidden' => 'true']);
                    else : ?>
                        <i class="<?php echo esc_attr($settings['icon']); ?>" aria-hidden="true"></i>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
            <span <?php echo $this->get_render_attribute_string('text'); ?>><?php echo $settings['text']; ?></span>
        </span>
        <?php
    }
}