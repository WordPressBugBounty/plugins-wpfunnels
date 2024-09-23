<?php
/**
 * Order details shortcode class
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
class Wpfnl_Shortcode_Order_details {

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
				'class'							=> '',
				'child_order_class'				=> '',
				'step_id'						=> '',
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
		$output = '';
		$show_dummy_order_details = false;

		// allow to show the order details markup on preview
		if ( is_admin() ) {
			$show_dummy_order_details = apply_filters( 'wpfunnels/show_dummy_order_details', true );
		}

		if(Wpfnl_functions::is_elementor_active()) {
			$elementor_preview_active = \Elementor\Plugin::$instance->preview->is_preview_mode();
			if( $elementor_preview_active ) {
				$show_dummy_order_details = apply_filters( 'wpfunnels/show_dummy_order_details', false );
			}
		}
		
		$is_funnel_step = $this->attributes['step_id'] ? Wpfnl_functions::maybe_funnel_step( $this->attributes['step_id'] ) : false;
		
		if( Wpfnl_functions::is_wc_active() && Wpfnl_functions::check_if_this_is_step_type('thankyou') || $show_dummy_order_details || $is_funnel_step ) {
			add_filter( 'woocommerce_order_item_permalink', '__return_false' );

			$order = false;

			$id_param  		= 'wpfnl-order';
			$key_param 		= 'wpfnl-key';
			$child_orders 	= null;
			if ( !isset($_GET[$id_param]) && $show_dummy_order_details ) {
				$args = array(
					'limit'     => 1,
					'order'     => 'DESC',
					'post_type' => 'shop_order',
					'status'    => array( 'completed', 'processing' ),
				);

				$latest_order = wc_get_orders( $args );

				$order_id = ( ! empty( $latest_order ) ) ? current( $latest_order )->get_id() : 0;
				if ( $order_id > 0 ) {
					/**
					 * Fires when the page is the "Thank You" page.
					 *
					 * This hook allows you to perform actions specific to the "Thank You" page,
					 * typically triggered if there is at least one order created
					 *
					 * @param int $order_id The ID of the completed order.
					 * @since 2.0.0
					 */
					do_action('wpfunnels/page_is_thankyou',$order_id);
					$order = wc_get_order( $order_id );
					if ( ! $order ) {
						$order = false;
					}
				} else {
					return '<p class="woocommerce-notice">' . __( 'No completed orders found in your shop for demo.', 'wpfnl' ) . '</p>';
				}
			}
			else {

				if (!isset($_GET[$id_param])) { //phpcs:ignore
					return '<p class="woocommerce-notice">' . __( 'Order not found. You cannot access this page directly.', 'wpfnl' ) . '</p>';
				}

				$order_id  = empty($_GET[$id_param]) ? 0 : intval($_GET[$id_param]);
				$order_key = empty($_GET[$key_param]) ? '' : wc_clean(wp_unslash($_GET[$key_param]));

				if ( $order_id > 0 ) {
					/**
					 * Fires when the page is the "Thank You" page.
					 *
					 * This hook allows you to perform actions specific to the "Thank You" page,
					 * typically triggered if there is at least one order created
					 *
					 * @param int $order_id The ID of the completed order.
					 * @since 2.0.0
					 */
					do_action('wpfunnels/page_is_thankyou',$order_id);
					$order 			= wc_get_order( $order_id );

					if (!Wpfnl_functions::is_valid_order_owner( $order )){
						return '<p class="woocommerce-notice">' . __( 'You are not authorized to view the order details.', 'wpfnl' ) . '</p>';
					}

					$child_orders 	= get_post_meta($order_id, '_wpfnl_offer_child_orders', true);
					if ( ! $order || $order->get_order_key() !== $order_key ) {
						$order = false;
					}
				}
			}

			if( is_a( $order, 'WC_Order' ) ) {
				$class 				= isset($this->attributes['class']) ? esc_html($this->attributes['class']) : '';
				$child_order_class 	= isset($this->attributes['child_order_class']) ? esc_html($this->attributes['child_order_class']) : '';
				// Empty awaiting payment session.
				unset( WC()->session->order_awaiting_payment );
				ob_start();
				echo "<div class='woocommerce'>";
				echo "<div class='wpfnl-thankyou-wrapper ".$class."' id='wpfnl-thankyou-wrapper'>";
				wc_get_template( 'checkout/thankyou.php',
					array(
						'order' => $order
					)
				);
			}
			

			if ( $child_orders ) {
				echo "<div class='wpfnl-child-order-wrapper ".$child_order_class."' id='wpfnl-child-order-wrapper'>";
				foreach ( $child_orders as $order_id => $value) {
					wc_get_template( 'checkout/thankyou.php',
						array(
							'order' => $order
						)
					);
				}
				echo '</div>';
			}
			echo '</div>';
			echo '</div>';
			$output = ob_get_clean();
		}

		return $output;
	}
}
