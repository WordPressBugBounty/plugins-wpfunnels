<?php
/**
 * Next step button
 * 
 * @package
 */
namespace WPFunnels\Widgets\DiviModules\Modules;

use ET_Builder_Element;
use ET_Builder_Module;
use WPFunnels\Wpfnl_functions;

class WPFNL_Next_Step_Button extends ET_Builder_Module {

	public $slug       = 'wpfnl_next_step_button';
	public $vb_support = 'on';

	// Module Credits (Appears at the bottom of the module settings modal)
	protected $module_credits = array(
		'module_uri' => '',
		'author'     => '',
		'author_uri' => '',
	);

	/**
	 * Module properties initialization
	 */
	function init() {

		$this->name             = esc_html__( 'WPF Next Step Button', 'wpfnl' );

		$this->icon_path        =  plugin_dir_path( __FILE__ ) . 'next_steps.svg';

		$this->main_css_element = '%%order_class%%';

		$this->wrapper_settings = array(
			// Flag that indicates that this module's wrapper where order class is declared
			// has another wrapper (mostly for button alignment purpose).
			'order_class_wrapper' => true,
		);

		$this->custom_css_fields = array(
			'main_element' => array(
				'label'                    => __( 'Main Element','wpfnl'),
				'no_space_before_selector' => true,
			),
		);

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content' => __( 'Text','wpfnl' ),
					'link'         => __( 'Link','wpfnl' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'alignment' => esc_html__( 'Alignment', 'wpfnl' ),
					'text'      => array(
						'title'    => __( 'Alignment' ,'wpfnl'),
						'priority' => 49,
					),
				),
			),
		);
		

		$this->advanced_fields = array(
			'text' =>array(
				'use_text_orientation'  => true, // default
				'css' => array(
					'text_orientation' => '%%order_class%%',
				)
			),
			'borders'         => array(
				'default' => false,
			),
			'button'          => array(
				'button' => array(
					'label'          => __( 'Button','wpfnl' ),
					'css'            => array(
						'main'         => $this->main_css_element.'.et_pb_button' ,
//						'limited_main' => "{$this->main_css_element}.et_pb_button",
					),
					'box_shadow'     => false,
					'text_shadow'     => false,
					'margin_padding'  => array(
						'css' => array(
							'main'    => ".et_pb_button{$this->main_css_element}",
							'important' => 'all',
						),
					),
				),
			),
			'box_shadow'     => false,
			'margin_padding' => false,
			'text_shadow'     => array(
				'default' => false,
			),
			'background'      => false,
			'fonts'           => false,
			'max_width'       => false,
			'height'          => false,
			'link_options'    => false,
			'position_fields' => array(
				'css' => array(
					'main' => "{$this->main_css_element}_wrapper,",
				),
			),
			'transform'       => array(
				'css' => array(
					'main' => "{$this->main_css_element}_wrapper,",
				),
			),
		);

		$this->help_videos = array(
			array(
				'id'   => 'XpM2G7tQQIE',
				'name' => esc_html__( 'An introduction to the Button module', 'wpfnl' ),
			),
		);
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
	function get_fields() {
		$basic_fields = array(
			'button_type_selector'             => array(
				'label'            => __( 'Select Button Type', 'wpfnl' ),
				'description'      => __( 'Select Button Type', 'wpfnl' ),
				'option_category'  => 'basic_option',
				'type'             => 'select',
				'options'          => array(
					'checkout'       	  => __( 'Next Step' ,'wpfnl'),
					'url-path'       	  => __( 'Go To URL Path' ,'wpfnl'),
					'another-funnel'      => __( 'Another Funnel' ,'wpfnl'),
				),
				'priority'         => 10,
				'default'          => 'checkout',
				'default_on_front' => 'checkout',
				'toggle_slug'      => 'button',
				'sub_toggle'       => 'ul',
				'mobile_options'   => true,
			),

			'url_path_field' => array(
				'label'           => __( 'URL Path', 'wpfnl' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'toggle_slug'     => 'button',
				'default'         => '',
				'show_if'          => array(
					'button_type_selector' => 'url-path',
				),
			),

			'another_funnel_field' => array(
				'label'            => __( 'Choose funnel', 'wpfnl' ),
				'description'      => __( 'Choose funnel', 'wpfnl' ),
				'option_category'  => 'basic_option',
				'type'             => 'select',
				'options'          => Wpfnl_functions::get_funnel_list(),
				'priority'         => 20,
				'default'          => '',
				'default_on_front' => '',
				'toggle_slug'      => 'button',
				'sub_toggle'       => 'ul',
				'mobile_options'   => true,
				'show_if'          => array(
					'button_type_selector' => 'another-funnel',
				),
			),

			'button_text' => array(
				'label'           => __( 'Button Text', 'wpfnl' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => __( 'Input your desired button text, or leave blank for no button.', 'wpfnl' ),
				'toggle_slug'     => 'button',
				'default'         => 'Buy Now',
			),
		);


		return $basic_fields;
	}

	/**
	 * Get button alignment.
	 *
	 * @param string $device Current device name.
	 * 
	 * @return string         Alignment value, rtl or not.
	 * @since  3.23 Add responsive support by adding device parameter.
	 */
	public function get_button_alignment( $device = 'desktop' ) {
		$suffix           = 'desktop' !== $device ? "_{$device}" : '';
		$text_orientation = isset( $this->props[ "button_alignment{$suffix}" ] ) ? $this->props[ "button_alignment{$suffix}" ] : '';

		return et_pb_get_alignment( $text_orientation );
	}

	/**
	 * Renders the module output.
	 *
	 * @param array  $attrs       List of attributes.
	 * @param string $content     Content being processed.
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ) {

		$multi_view     = et_pb_multi_view_options( $this );
		$button_rel     = $this->props['button_rel'];
		$button_text    = $this->_esc_attr( 'button_text', 'limited' );
		$button_custom  = $this->props['custom_button'];

		$button_alignment              = $this->get_button_alignment();
		$is_button_aligment_responsive = et_pb_responsive_options()->is_responsive_enabled( $this->props, 'button_alignment' );
		$button_alignment_tablet       = $is_button_aligment_responsive ? $this->get_button_alignment( 'tablet' ) : '';
		$button_alignment_phone        = $is_button_aligment_responsive ? $this->get_button_alignment( 'phone' ) : '';

		$custom_icon_values = et_pb_responsive_options()->get_property_values( $this->props, 'button_icon' );
		$custom_icon        = isset( $custom_icon_values['desktop'] ) ? $custom_icon_values['desktop'] : '';
		$custom_icon_tablet = isset( $custom_icon_values['tablet'] ) ? $custom_icon_values['tablet'] : '';
		$custom_icon_phone  = isset( $custom_icon_values['phone'] ) ? $custom_icon_values['phone'] : '';

		// Button Alignment.
		$button_alignments = array();
		if ( ! empty( $button_alignment ) ) {
			array_push( $button_alignments, sprintf( 'et_pb_button_alignment_%1$s', esc_attr( $button_alignment ) ) );
		}

		if ( ! empty( $button_alignment_tablet ) ) {
			array_push( $button_alignments, sprintf( 'et_pb_button_alignment_tablet_%1$s', esc_attr( $button_alignment_tablet ) ) );
		}

		if ( ! empty( $button_alignment_phone ) ) {
			array_push( $button_alignments, sprintf( 'et_pb_button_alignment_phone_%1$s', esc_attr( $button_alignment_phone ) ) );
		}

		$button_alignment_classes = join( ' ', $button_alignments );

		// Background layout data attributes.
		$data_background_layout = et_pb_background_layout_options()->get_background_layout_attrs( $this->props );

		// Background layout class names.
		$background_layout_class_names = et_pb_background_layout_options()->get_background_layout_class( $this->props );
		$this->add_classname( $background_layout_class_names );

		// Module classnames
		$this->remove_classname( 'et_pb_module' );
	
		$url = '';
		if( isset($this->props['button_type_selector']) && 'another-funnel' === $this->props['button_type_selector'] ){
			$url = isset($this->props['another_funnel_field']) ? $this->props['another_funnel_field'] : '';
		}elseif( isset($this->props['button_type_selector']) && 'url-path' === $this->props['button_type_selector'] ){
			$url = isset($this->props['url_path_field']) ? $this->props['url_path_field'] : '';
		}
		
		$button_type = isset($this->props['button_type_selector']) ? $this->props['button_type_selector'] : '';
		

		// Render Button
		$button = $this->render_button(
			array(
				'button_id'           => 'wpfunnels_next_step_controller',
				'button_classname'    => explode( ' ', $this->module_classname( $render_slug ) ),
				'button_custom'       => $button_custom,
				'button_rel'          => $button_rel,
				'button_text'         => $button_text,
				'button_text_escaped' => true,
				'custom_icon'         => $custom_icon,
				'custom_icon_tablet'  => $custom_icon_tablet,
				'custom_icon_phone'   => $custom_icon_phone,
				'has_wrapper'         => false,
				'multi_view_data'     => $multi_view->render_attrs(
					array(
						'content'        => '{{button_text}}',
						'hover_selector' => '%%order_class%% .et_pb_button',
						'visibility'     => array(
							'button_text' => '__not_empty',
						),
					)
				),
				'data_url'			=> $url,
				'data_button_type'	=> $button_type,
			)
		);

		// Render module output
		$output = sprintf(
			'<div class="et_pb_button_module_wrapper %3$s_wrapper %2$s et_pb_module "%4$s>
				%1$s
			</div>',
			et_core_esc_previously( $button ),
			esc_attr( $button_alignment_classes ),
			esc_attr( ET_Builder_Element::get_module_order_class( $this->slug ) ),
			et_core_esc_previously( $data_background_layout )
		);

		$transition_style = $this->get_transition_style( array( 'all' ) );
		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%%, %%order_class%%:after',
				'declaration' => esc_html( $transition_style ),
			)
		);

		// Tablet.
		$transition_style_tablet = $this->get_transition_style( array( 'all' ), 'tablet' );
		if ( $transition_style_tablet !== $transition_style ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%%, %%order_class%%:after',
					'declaration' => esc_html( $transition_style_tablet ),
					'media_query' => ET_Builder_Element::get_media_query( 'max_width_980' ),
				)
			);
		}

		// Phone.
		$transition_style_phone = $this->get_transition_style( array( 'all' ), 'phone' );
		if ( $transition_style_phone !== $transition_style || $transition_style_phone !== $transition_style_tablet ) {
			$el_style = array(
				'selector'    => '%%order_class%%, %%order_class%%:after',
				'declaration' => esc_html( $transition_style_phone ),
				'media_query' => ET_Builder_Element::get_media_query( 'max_width_767' ),
			);
			self::set_style( $render_slug, $el_style );
		}

		return $output;
	}


	/**
	 * Override Helper method for rendering button markup which works compatible with advanced options' button
	 *
	 * @param array $args button settings.
	 *
	 * @return string rendered button HTML
	 * @since  2.5.6
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
			'data_url'     		  => '',
			'data_button_type'    => '',
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

		// Add multi view CSS hidden helper class when button text is empty on desktop mode.
		if ( '' === $button_text && $args['multi_view_data'] ) {
			$button_classname[] = 'et_multi_view_hidden';
		}

		if ( ! empty( $args['button_classname'] ) ) {
			$button_classname = array_merge( $button_classname, $args['button_classname'] );
		}

		// Custom icon data attribute.
		$use_data_icon = '' !== $args['custom_icon'] && 'on' === $args['button_custom'];
		if ( $use_data_icon && et_pb_maybe_extended_icon( $args['custom_icon'] ) ) {
			$args['custom_icon'] = esc_attr( et_pb_get_extended_font_icon_value( $args['custom_icon'] ) );
		}
		$data_icon = $use_data_icon ? sprintf(
			' data-icon="%1$s"',
			esc_attr( et_pb_process_font_icon( $args['custom_icon'] ) )
		) : '';

		$use_data_icon_tablet = '' !== $args['custom_icon_tablet'] && 'on' === $args['button_custom'];
		if ( $use_data_icon_tablet && et_pb_maybe_extended_icon( $args['custom_icon_tablet'] ) ) {
			$args['custom_icon_tablet'] = esc_attr( et_pb_get_extended_font_icon_value( $args['custom_icon_tablet'] ) );
		}
		$data_icon_tablet = $use_data_icon_tablet ? sprintf(
			' data-icon-tablet="%1$s"',
			esc_attr( et_pb_process_font_icon( $args['custom_icon_tablet'] ) )
		) : '';

		$use_data_icon_phone = '' !== $args['custom_icon_phone'] && 'on' === $args['button_custom'];
		if ( $use_data_icon_phone && et_pb_maybe_extended_icon( $args['custom_icon_phone'] ) ) {
			$args['custom_icon_phone'] = esc_attr( et_pb_get_extended_font_icon_value( $args['custom_icon_phone'] ) );
		}
		$data_icon_phone = $use_data_icon_phone ? sprintf(
			' data-icon-phone="%1$s"',
			esc_attr( et_pb_process_font_icon( $args['custom_icon_phone'] ) )
		) : '';

		$data_url = sprintf(
			'data-url="%1$s"',
			$args['data_url']
		);
		
		$data_button_type = sprintf(
			'data-button-type="%1$s"',
			esc_attr( et_pb_process_font_icon( $args['data_button_type'] ) )
		);


		
		// Render button.
		return sprintf(
			'%7$s<a%9$s class="%5$s" href="%1$s"%3$s%4$s%6$s%10$s%11$s%12$s%13$s%14$s>%2$s</a>%8$s',
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
			$data_url,
			$data_button_type
		);
	}


	/**
	 * Filter multi view value.
	 *
	 * @param mixed                                     $raw_value Props raw value.
	 * @param array                                     $args 
	 * @param ET_Builder_Module_Helper_MultiViewOptions $multi_view Multiview object instance.
	 *
	 * @return mixed
	 * @since  3.27.1
	 */
	public function multi_view_filter_value( $raw_value, $args, $multi_view ) {
		$name    = isset( $args['name'] ) ? $args['name'] : '';
		$mode    = isset( $args['mode'] ) ? $args['mode'] : '';
		$context = isset( $args['context'] ) ? $args['context'] : '';

		$fields_need_escape = array(
			'title',
		);

		if ( $raw_value && 'content' === $context && in_array( $name, $fields_need_escape, true ) ) {
			return $this->_esc_attr( $multi_view->get_name_by_mode( $name, $mode ), 'none', $raw_value );
		}

		return $raw_value;
	}
}

new WPFNL_Next_Step_Button;
