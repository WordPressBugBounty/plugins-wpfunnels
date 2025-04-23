<?php
/**
 * Next step shortcode class
 * 
 * @package
 */
namespace WPFunnels\Shortcodes;

use WPFunnels\Wpfnl_functions;

/**
 * Class WC_Shortcode_Optin
 *
 * @package WPFunnels\Shortcodes
 */
class Wpfnl_Shortcode_NextStepButton {

	/**
	 * Attributes
	 *
	 * @var array
	 */
	protected $attributes = array();


	/**
	 * Wpfnl_Shortcode_Order_details constructor.
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
				'btn_text'         => '',
				'background_color' => '',
				'color'            => '',
				'padding'          => '',
				'class'            => '',
				'button_type'      => '',
				'data_url'         => '',
				'border_radius'    => '',
				'font_size'        => '',
				'width'            => '',
				'border'           => '',
				'align'            => '',
				'target'           => '',
				'icon'             => '',
				'icon_position'    => 'before',
				'font_family'      => '',
				'font_weight'      => '',
				'text_transform'   => '',
				'letter_spacing'   => '',
				'line_height'      => '',
				'box_shadow'       => '',
				'display'          => 'inline-block',
				'custom_id'        => '',
				'custom_attrs'     => '',
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
		$classes = array( 'wpfnl', 'wpfnl-order-details-wrapper' );
		return $classes;
	}


	/**
	 * Generates the HTML output for the customizable button shortcode in WPFunnels.
	 *
	 * This function checks if the current step type is 'landing' or 'custom'.
	 * If valid, it renders a next-step button with a variety of customizable
	 * style and data attributes based on shortcode inputs.
	 *
	 * @return string|false HTML markup for the button or false if step type is invalid.
	 * @since 3.5.19
	 */
	public function get_content(){
		if (Wpfnl_functions::check_if_this_is_step_type('landing') || Wpfnl_functions::check_if_this_is_step_type('custom')) {

			$products_array = get_post_meta(get_the_ID(), 'checkout_product_selector', true);
			$products = $products_array ? implode(",", $products_array) : '';

			// Build inline styles dynamically
			$style = 'cursor: pointer;';
			$style .= !empty($this->attributes['background_color']) ? 'background-color: ' . esc_html($this->attributes['background_color']) . ';' : '';
			$style .= !empty($this->attributes['color']) ? 'color: ' . esc_html($this->attributes['color']) . ';' : '';
			$style .= !empty($this->attributes['padding']) ? 'padding: ' . esc_html($this->attributes['padding']) . ';' : '';
			$style .= !empty($this->attributes['border_radius']) ? 'border-radius: ' . esc_html($this->attributes['border_radius']) . ';' : '';
			$style .= !empty($this->attributes['font_size']) ? 'font-size: ' . esc_html($this->attributes['font_size']) . ';' : '';
			$style .= !empty($this->attributes['width']) ? 'width: ' . esc_html($this->attributes['width']) . ';' : '';
			$style .= !empty($this->attributes['border']) ? 'border: ' . esc_html($this->attributes['border']) . ';' : '';
			$style .= !empty($this->attributes['font_family']) ? 'font-family: ' . esc_html($this->attributes['font_family']) . ';' : '';
			$style .= !empty($this->attributes['font_weight']) ? 'font-weight: ' . esc_html($this->attributes['font_weight']) . ';' : '';
			$style .= !empty($this->attributes['text_transform']) ? 'text-transform: ' . esc_html($this->attributes['text_transform']) . ';' : '';
			$style .= !empty($this->attributes['letter_spacing']) ? 'letter-spacing: ' . esc_html($this->attributes['letter_spacing']) . ';' : '';
			$style .= !empty($this->attributes['line_height']) ? 'line-height: ' . esc_html($this->attributes['line_height']) . ';' : '';
			$style .= !empty($this->attributes['box_shadow']) ? 'box-shadow: ' . esc_html($this->attributes['box_shadow']) . ';' : '';
			$style .= !empty($this->attributes['display']) ? 'display: ' . esc_html($this->attributes['display']) . ';' : '';

			// Alignment
			$align_style = !empty($this->attributes['align']) ? 'text-align: ' . esc_attr($this->attributes['align']) . ';' : '';

			// Button Text with optional icon
			$btn_text = isset($this->attributes['btn_text']) && $this->attributes['btn_text'] ? esc_html($this->attributes['btn_text']) : 'Get Now';
			$icon = isset($this->attributes['icon']) ? $this->attributes['icon'] : '';
			$icon_position = isset($this->attributes['icon_position']) ? $this->attributes['icon_position'] : 'before';

			$button_label = '';
			if (!empty($icon) && $icon_position === 'before') {
				$button_label .= $icon . ' ';
			}
			$button_label .= $btn_text;
			if (!empty($icon) && $icon_position === 'after') {
				$button_label .= ' ' . $icon;
			}

			$html = '';
			do_action('wpfunnels/before_next_step_button');

			$html .= '<div class="next-step-button-wrapper" style="' . $align_style . '">';
			$html .= '<a href="#" 
			data-button-type="' . esc_attr($this->attributes['button_type']) . '" 
			data-url="' . esc_url($this->attributes['data_url']) . '" 
			data-id="' . get_the_ID() . '" 
			data-products="' . esc_attr($products) . '" 
			id="' . esc_attr(!empty($this->attributes['custom_id']) ? $this->attributes['custom_id'] : 'wpfunnels_next_step_controller') . '"
			style="' . $style . '" 
			class="' . esc_attr($this->attributes['class']) . ' next-step-button btn-default" 
			target="' . esc_attr($this->attributes['target']) . '"'
				. (!empty($this->attributes['custom_attrs']) ? ' ' . $this->attributes['custom_attrs'] : '') . '>';

			$html .= $button_label;

			$html .= '</a></div><span class="wpfnl-alert" id="wpfnl-next-button-loader"></span>';

			do_action('wpfunnels/after_next_step_button');

			return $html;
		}
		return false;
	}
}
