<?php
/**
 * Tutor Compatibility
 * 
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * Tutor Compatibility
 * 
 * @package WPFunnels\Compatibility\Tutor
 */
class Tutor extends PluginCompatibility{
	use SingletonTrait;

	/**
	 * Filters/Hook from Tutor
	 * to initiate the necessary updates.
	 *
	 * @since 3.4.10
	 */
	public function init() {
		
		add_action( 'wp', array( $this, 'remove_tutor_redirection_for_wpfnl_step'), 9999 );
        add_action( 'wpfunnels/funnel_order_placed', array( $this, 'funnel_order_placed' ), 10, 3 );
        add_action( 'wpfunnels/offer_accepted', array( $this, 'enroll_to_tutor_course' ), 10, 2 );
		add_filter( 'tutor_woocommerce_redirect_url', array( $this, 'tutor_maybe_allow_to_redirect' ), 99 );
	}


	/**
	 * Remove tutor redirection for WPFunnels steps
	 * 
	 * @since 3.4.10
	 * @return void
	 */
	public function remove_tutor_redirection_for_wpfnl_step() {
		
		if(  Wpfnl_functions::is_funnel_step_page() && $this->maybe_activate() ){
            if ( class_exists( '\TUTOR\WooCommerce' ) ) {
                $instance = new \TUTOR\WooCommerce();
                remove_action( 'woocommerce_thankyou', array( $instance, 'redirect_to_enrolled_courses' ) );
            }
        }
	}


	/**
	 * Save order meta data for tutor course
	 * 
	 * @param int $order_id
	 * @param int $funnel_id
	 * @param int $checkout_id	
	 * 
	 * @since 3.4.10
	 */
	public function funnel_order_placed( $order_id, $funnel_id, $checkout_id ){
		if( $this->maybe_activate() && $funnel_id && $checkout_id && $order_id ){
			$order = \wc_get_order( $order_id );
			$order->update_meta_data( '_is_tutor_order_for_course', tutor_time() );
			$order->save();
		}
	}


	/**
	 * Enroll user to tutor course
	 * 
	 * @param WC_Order $order
	 * @param array $offer_product
	 * 
	 * @since 3.4.10
	 */
    public function enroll_to_tutor_course( $order, $offer_product ){
        if( $this->maybe_activate() && function_exists( 'tutor_utils' ) ){
            if ( is_array($offer_product) && !empty($offer_product)) {
				$customer_id = $order->get_customer_id();
				do_action( 'woocommerce_new_order', $order->get_id(), $order );
                $product_id = $offer_product['id'];
                $order_id      = $order->get_id();
				$order->update_meta_data( '_is_tutor_order_for_course', tutor_time() );
				$order->update_status('completed');
				$order->save();
				
				$if_has_course = tutor_utils()->product_belongs_with_course( $product_id );
				if ( $if_has_course ) {
					$course_id   = $if_has_course->post_id;
					
					$is_enrolled = tutor_utils()->is_enrolled( $course_id, $customer_id );
					if( !$is_enrolled ){
						tutor_utils()->do_enroll( $course_id, $order_id, $customer_id );
						foreach ( $order->get_items() as $item_id => $item ) {
							do_action( 'woocommerce_new_order_item', $item_id, $item, $order_id );
						}
					}
				}
            }
        }
    }


	/**
	 * Determines whether to allow redirection for the tutor.
	 *
	 * @param bool $is_allow Whether redirection is allowed.
	 * 
	 * @return bool
	 * @since 3.4.10
	 */
	public function tutor_maybe_allow_to_redirect( $is_allow ){
		if (Wpfnl_functions::is_funnel_step_page()) {
			return false;
		}
		return $is_allow;
	}


	/**
	 * Check if Tutor is activated
	 *
	 * @return bool
	 * @since 3.4.10
	 */
	public function maybe_activate()
	{
		return defined('TUTOR_VERSION');
	}
}
