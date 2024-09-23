<?php
/**
 * Order details shortcode class
 * 
 * @package
 */
namespace WPFunnels\Shortcodes;

use WPFunnels\Meta\Wpfnl_Default_Meta;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Wpfnl;

/**
 * Class WC_Shortcode_Optin
 *
 * @package WPFunnels\Shortcodes
 */
class Wpfnl_Shortcode_Checkout {

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
				'type' 					=> 'two-column',
				'order_bump' 			=> 'yes',
				'class'					=> ''
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
	 * Content of optin form
	 *
	 * @return string
	 */
	public function get_content() {

		if( Wpfnl_functions::check_if_this_is_step_type('checkout') ) {

            //===Coupon Enabler===//
            $coupon_enabler = get_post_meta(get_the_ID(), '_wpfnl_checkout_coupon', true);
            if ( $coupon_enabler != 'yes' ) {
                remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
            }

            $checkout_layout = '';
            $session_value = '';
            if( esc_html($this->attributes['type']) == 'one-column' ){
                $checkout_layout = 'wpfnl-col-1';
                $session_value = 'wpfnl-col-1';
            }elseif( esc_html($this->attributes['type']) == 'two-column' ){
                $checkout_layout = 'wpfnl-col-2';
                $session_value = 'wpfnl-col-2';
            }else{
                if( Wpfnl_functions::is_wpfnl_pro_activated() && 'multistep' === esc_html($this->attributes['type']) ){
                    $checkout_layout = 'wpfnl-multistep';
                    $session_value = 'wpfnl-multistep';
                }if( Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-two-step' === esc_html($this->attributes['type']) ){
                    $checkout_layout = 'wpfnl-2-step wpfnl-multistep ';
                    $session_value = 'wpfnl-2-step';
                }elseif( !Wpfnl_functions::is_wpfnl_pro_activated() && 'multistep' === esc_html($this->attributes['type']) ) {
                    $checkout_layout = 'wpfnl-col-2';
                    $session_value = 'wpfnl-col-2';
                }elseif( Wpfnl_functions::is_wpfnl_pro_activated() &&  'wpfnl-express-checkout' === esc_html($this->attributes['type']) ) {
                    $checkout_layout = 'wpfnl-express-checkout';
                    $session_value = 'wpfnl-express-checkout';
                }
            }
			if( PHP_SESSION_DISABLED == session_status() ) {
				session_start();
			}
			$_SESSION[ 'checkout_layout' ] = $session_value;
			if( Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-express-checkout' === $checkout_layout ) {
				$checkout_layout .= ' wpfnl-multistep';
			}

			query_posts('post_type="checkout"');
			if(sanitize_text_field($this->attributes['order_bump']) == 'yes'){
				do_action( 'wpfunnels/before_checkout_form', get_the_ID() );
			}
			$class = '';
			if (isset($this->attributes['class']) && NUll != (esc_html($this->attributes['class']))){
				$class = esc_html($this->attributes['class']);
			}
			
            $html = '';
            $html .= '<div class="wpfnl-checkout '.$checkout_layout.' '.$class.'">';
            $html .=  do_shortcode('[woocommerce_checkout]');
            $html .= '</div>';

            return $html;
		}
		return false;
	}


	/**
	 * Render order bump
	 */
	public function render_order_bump(){
		add_action( 'wpfunnels/before_checkout_form', array( $this, 'load_actions' ), 10, 2 );
	}

	public function load_actions( $checkout_id, $settings = array() ) {

		$checkout_meta 			= new Wpfnl_Default_Meta();
		// $is_order_bump_enabled 	= $checkout_meta->get_checkout_meta_value($checkout_id, 'order-bump');
		$funnel_id				= get_post_meta( $checkout_id, '_funnel_id', true );
		// if ( 'yes' !== $is_order_bump_enabled ) {
		// 	return;
		// }


		$this->ob_settings = $checkout_meta->get_checkout_meta_value($checkout_id, 'order-bump-settings', wpfnl()->meta->get_default_order_bump_meta());
		$this->ob_settings = apply_filters( 'wpfunnels/order_bump_settings', $this->ob_settings, $funnel_id, $checkout_id );
		$this->trigger_ob_actions();
	}


	/**
	 * Trigger WC action for order bump
	 */
	private function trigger_ob_actions() {

		foreach( $this->ob_settings as $key=>$settings ){
			$position = $this->get_order_bump_attribute( $settings, 'position' );

			if( !$position ) {
				return;
			}

			switch ($position) {
				case 'before-checkout':
					add_action('woocommerce_before_checkout_form', [$this, 'render_order_bump'], 10);
					break;
				case 'after-order':
					add_action('woocommerce_checkout_order_review', [$this, 'render_order_bump'], 8);
					break;
				case 'after-customer-details':
					add_action('woocommerce_after_order_notes', [$this, 'render_order_bump'], 8);
					break;
				case 'before-payment':
					add_action('woocommerce_review_order_before_payment', [$this, 'render_order_bump'], 8);
					break;
				case 'after-payment':
					add_action('woocommerce_review_order_after_payment', [$this, 'render_order_bump'], 8);
					break;
				case 'popup':
					$settings['selectedStyle'] = 'popup';
					$this->render_popup_in_elementor_editor();
					break;
			}
		}

	}


	/**
     * Get order bump attribute from order bump settings data
     *
     * @param $order_bump_data
     * @param $key
	 * 
     * @return bool|mixed
     */
    private function get_order_bump_attribute( $order_bump_data, $key ) {
        if( !isset($order_bump_data[$key]) ) {
            return false;
        }
        return $order_bump_data[$key];
    }


	/**
     * Render popup style for order bump in elementor
     * builder preview
     *
     * @since 2.0.3
     */
    public function render_popup_in_elementor_editor() {
        if(Wpfnl_functions::is_elementor_active()) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_admin()) {
                add_action( 'woocommerce_before_checkout_form', [ $this, 'render_order_bump' ], 10 );
            } else {
				add_action( 'woocommerce_before_checkout_form', [ $this, 'render_order_bump' ], 10 );
			}
        } else {
			add_action( 'woocommerce_before_checkout_form', [ $this, 'render_order_bump' ], 10 );
		}
    }

}
