<?php
/**
 * Namespace for the NextStep class.
 * This class is part of the WPFunnels\Widgets\Bricks namespace.
 */
namespace WPFunnels\Widgets\Bricks;

require_once get_template_directory() . '/includes/elements/base.php';

use \Bricks\Element;
use WPFunnels\Wpfnl_functions;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Class NextStep
 * 
 * Represents a NextStep element in the WP Funnels plugin.
 * This class extends the Element class.
 * 
 * @package WPFunnels\Widgets\Bricks
 */
class NextStep extends Element {

    // Element properties
    public $category     = 'wpfunnels'; // Use predefined element category 'general'
    public $name         = 'wpfnl_next_step'; // Make sure to prefix your elements
    public $icon         = 'fa-solid fa-cart-shopping'; // Themify icon font class
    public $scripts      = []; // Script(s) run when element is rendered on frontend or updated in builder
    public $tag         = 'button';


     /**
     * Return localised element label
     * 
     * @return string
     * @since 3.1.0
     */
    public function get_label()
    {
        return esc_html__('Next Step', 'wpfnl');
    }


    /**
     * Set builder controls
     * 
     * @since 3.1.0
     */
    public function set_controls() {
		$this->controls['button_type_selector'] = [
			'tab' => 'content',
			'label' => esc_html__( 'Select Button Type', 'wpfnl' ),
			'type' => 'select',
			'options' => [
			  'checkout' => 'Next Step',
			  'url-path' => 'Go To URL Path',
			  'another-funnel' => 'Another Funnel',
			],
			'inline' => false,
			'placeholder' => esc_html__( 'Select Type', 'wpfnl' ),
			'default' => '',
		];

		$this->controls['url_path_field'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'URL Path', 'wpfnl' ),
			'type'        => 'link',
			'pasteStyles' => false,
			'placeholder' => esc_html__( 'http://yoursite.com', 'wpfnl' ),
			'required'    => [ 'button_type_selector', '=', 'url-path' ],
		];

		$this->controls['another_funnel_field'] = [
			'tab' => 'content',
			'label' => esc_html__( 'Choose Funnel', 'wpfnl' ),
			'type' => 'select',
			'options' => Wpfnl_functions::get_funnel_list(),
			'inline' => false,
			'placeholder' => esc_html__( 'Select a Funnel', 'wpfnl' ),
			'default' => '',
			'required'    => [ 'button_type_selector', '=', 'another-funnel' ],
		];


		$this->controls['buttonTypeSeparator'] = [
			'type'  => 'separator',
		];
		  
		//---text---
		$this->controls['text'] = [
			'label'       => esc_html__( 'Button Title', 'wpfnl' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Next Step', 'wpfnl' ),
			'placeholder' => esc_html__( 'Next Step', 'wpfnl' ),
		];

		$this->controls['size'] = [
			'label'       => esc_html__( 'Size', 'wpfnl' ),
			'type'        => 'select',
			'options'     => $this->control_options['buttonSizes'],
			'inline'      => true,
			'reset'       => true,
			'placeholder' => esc_html__( 'Default', 'wpfnl' ),
		];

		$this->controls['style'] = [
			'label'       => esc_html__( 'Style', 'wpfnl' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'reset'       => true,
			'default'     => 'primary',
			'placeholder' => esc_html__( 'None', 'wpfnl' ),
		];

		$this->controls['circle'] = [
			'label' => esc_html__( 'Circle', 'wpfnl' ),
			'type'  => 'checkbox',
			'reset' => true,
		];

		$this->controls['outline'] = [
			'label' => esc_html__( 'Outline', 'wpfnl' ),
			'type'  => 'checkbox',
			'reset' => true,
		];

		// Icon
		$this->controls['iconSeparator'] = [
			'label' => esc_html__( 'Icon', 'wpfnl' ),
			'type'  => 'separator',
		];

		$this->controls['icon'] = [
			'label' => esc_html__( 'Icon', 'wpfnl' ),
			'type'  => 'icon',
		];

		$this->controls['iconTypography'] = [
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'i',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'line-height',
				'letter-spacing',
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconPosition'] = [
			'label'       => esc_html__( 'Position', 'wpfnl' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'wpfnl' ),
			'required'    => [ 'icon', '!=', '' ],
		];

		$this->controls['iconGap'] = [
			'label'    => esc_html__( 'Gap', 'wpfnl' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'gap',
				],
			],
			'required' => [ 'icon', '!=', '' ],
		];

		$this->controls['iconSpace'] = [
			'label'    => esc_html__( 'Space between', 'wpfnl' ),
			'type'     => 'checkbox',
			'css'      => [
				[
					'property' => 'justify-content',
					'value'    => 'space-between',
				],
			],
			'required' => [ 'icon', '!=', '' ],
		];
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
    public function render() {
		$settings = $this->settings;

		$this->set_attribute( '_root', 'class', 'bricks-button' );

		if ( ! empty( $settings['size'] ) ) {
			$this->set_attribute( '_root', 'class', $settings['size'] );
		}

		if ( ! empty( $settings['style'] ) ) {
			// Outline
			if ( isset( $settings['outline'] ) ) {
				$this->set_attribute( '_root', 'class', 'outline' );
				$this->set_attribute( '_root', 'class', "bricks-color-{$settings['style']}" );
			}

			// Fill (= default)
			else {
				$this->set_attribute( '_root', 'class', "bricks-background-{$settings['style']}" );
			}
		}

		// Button circle
		if ( isset( $settings['circle'] ) ) {
			$this->set_attribute( '_root', 'class', 'circle' );
		}

		if ( isset( $settings['block'] ) ) {
			$this->set_attribute( '_root', 'class', 'block' );
		}

		$output = "<a href='#' class='wpfnl-next-step-button' id='wpfunnels_next_step_controller' role='button'><span {$this->render_attributes( '_root' )} >";

			$icon          = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;
			$icon_position = ! empty( $settings['iconPosition'] ) ? $settings['iconPosition'] : 'right';

			if ( $icon && $icon_position === 'left' ) {
				$output .= $icon;
			}

			if ( ! empty( $settings['text'] ) ) {
				$output .= trim( $settings['text'] );
			}

			if ( $icon && $icon_position === 'right' ) {
				$output .= $icon;
			}

		$output .= "</span></a>";

		echo $output;
	}

    

}
