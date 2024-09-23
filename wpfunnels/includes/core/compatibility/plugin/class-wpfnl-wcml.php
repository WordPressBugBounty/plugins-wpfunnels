<?php
/**
 * WooCommerce Multilingual & Multicurrency with WPML Compatibility
 *
 * @package
 */
namespace WPFunnels\Compatibility\Plugin;


use WPFunnels\Traits\SingletonTrait;
use WC_Session_Handler;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Discount\WpfnlDiscount;
use WPFunnels\Modules\Frontend\CheckoutHelper\CheckoutHelper;
/**
 * WooCommerce Multilingual & Multicurrency with WPML Compatibility
 *
 * @package WPFunnels\Compatibility\Plugin\Wcml;
 */
class Wcml {

    use SingletonTrait;


    /**
     * Filters/Hook from Twenty Twenty Two
     *
	 * @return void 
     * @since 3.1.8
     */
    public function init() {

        if ($this->maybe_activate() && Wpfnl_functions::is_funnel_checkout_page()) {
            add_action('plugins_loaded', array($this, 'remove_wcml_refresh_cart_total_hook'));

			// Hook to save checkout fields
			add_action('woocommerce_checkout_update_order_meta', array($this, 'save_checkout_fields'), 10, 2);

			add_action('wp', array($this, 'add_widget_hooks'));

			add_filter('wpfunnels/modify_offer_product_price_data_without_discount', [$this,'get_updated_price'], 10 );
			add_filter('wpfunnels/modify_offer_product_price_data_with_discount', [$this,'get_updated_price'], 10 );
		
        }
    }

	/**
	 * Add widget hooks
	 *
	 * @return void 
	 * @since 3.1.8
	 */
	public function add_widget_hooks() {
		global $post;

		if ( isset($_REQUEST['wc-ajax']) && ( 'apply_coupon' === $_REQUEST['wc-ajax'] || 'wc_stripe_get_cart_details' === $_REQUEST['wc-ajax'] ) ) {
			return;
		}

        if(
            is_admin()
            || isset( $_GET[ 'removed_item' ] )
            || wp_doing_ajax()
            || !$post
        ) {
            return;
        }


        $checkout_id = $post->ID;
		if( !$checkout_id ) {
			return;
		}

		// Returns if the post id is not funnel checkout type.
        if(
            !Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' )
            || !Wpfnl_functions::check_if_this_is_step_type( 'checkout' )
			|| Wpfnl_functions::is_orderbump_clicked_from_post_data()
			|| Wpfnl_functions::is_checkoutify_orderbump_clicked_from_post_data()
			|| Wpfnl_functions::is_variation_selected_from_post_data()
			|| Wpfnl_functions::maybe_select_quantity_from_post_data()
			// || Wpfnl_functions::is_coupon_applied_from_post_data()
        ) {
            return;
        }

		if(  isset($_REQUEST['wc-ajax']) && Wpfnl_functions::is_wc_active() && !WC()->cart->is_empty() && WC()->cart->has_discount()){
			return;
		}

        $funnel_id = get_post_meta( $checkout_id, '_funnel_id', true );

        // Returns in case of invalid funnel id or if the funnel id is `lms` type.
        if( !$funnel_id || 'lms' === get_post_meta( $funnel_id, '_wpfnl_funnel_type', true ) ) {
            return;
        }

		$widget_position = get_post_meta( $checkout_id, '_wpfnl_wpml_Widget_Position_on_checkout', true );
		if( $widget_position ) {
			add_action( $widget_position, array( $this, 'add_wpml_widget' ), 10 );
		}
	}

	/**
	 * Add WPML widget
	 *
	 * @return void 
	 * @since 3.1.8
	 */
	public function add_wpml_widget(){
		
		echo( do_shortcode( '[currency_switcher]' ) );
	}


 	/**
     * Remove wcml_refresh_cart_total action
	 * 
	 * @return void 
	 * @since 3.1.8
     */
    public function remove_wcml_refresh_cart_total_hook() {
        remove_action('woocommerce_before_checkout_process', array('\WCML_Cart', 'wcml_refresh_cart_total'));
    }




	/**
	 * Save checkout metas
	 *
	 * @param $order_id
	 * @param $posted
	 *
	 * @since 3.1.8
	 */
	public function save_checkout_fields( $order_id, $posted )
	{
		// Instantiate the CheckoutModuleHelper
		$helper = CheckoutHelper::getInstance();

		if (isset($_POST['_wpfunnels_checkout_id'])) {
			$helper->update_checkout_id_post_meta($order_id, sanitize_text_field( $_POST['_wpfunnels_checkout_id'] ) );
			if (isset($_POST['_wpfunnels_funnel_id'])) {
				$funnel_id = $helper->update_order_meta_for_funnel($order_id, sanitize_text_field($_POST['_wpfunnels_funnel_id']));
			}
			if (isset($_POST['_wpfunnels_order_unique_identifier'])) {
				$helper->update_unique_identifier_post_meta($order_id, sanitize_text_field($_POST['_wpfunnels_order_unique_identifier']));
			}
			$order_bump_products = get_post_meta( $_POST['_wpfunnels_checkout_id'], 'order-bump-settings' , true );
			$helper->update_order_bump_product( $order_bump_products, $order_id );
		}

		if (isset($funnel_id)) {
			$order     = Wpfnl_functions::is_wc_active() ? wc_get_order($order_id) : null;
			$funnel_id = $helper->update_order_meta_for_funnel($order_id, sanitize_text_field( $_POST['_wpfunnels_funnel_id']) );

			if( $funnel_id ){
				$discount_instance = new WpfnlDiscount();

				if( !$discount_instance->maybe_time_bound_discount( $_POST['_wpfunnels_checkout_id'] ) || ($discount_instance->maybe_time_bound_discount( $_POST['_wpfunnels_checkout_id'] ) && $discount_instance->maybe_validate_discount_time( $_POST['_wpfunnels_checkout_id'] )) ){
					$discount          = $discount_instance->get_discount_settings( $_POST['_wpfunnels_checkout_id'] );
					$main_products     = $helper->get_main_products( $_POST['_wpfunnels_checkout_id'], $funnel_id );

					$variable_product_check  = $helper->variable_product_check( $order->get_items(), $main_products, false );
					$main_products           = isset( $variable_product_check['main_products'] ) ? $variable_product_check['main_products'] : $main_products;

					$is_main_product_in_cart = isset( $variable_product_check['is_main_product_in_cart'] ) ? $variable_product_check['is_main_product_in_cart'] : false;
					$total                   = $helper->get_custom_subtotal();
					$response = $helper->apply_discount_and_update_total( $order, $total, $discount, $is_main_product_in_cart);
				}
			}
    		$user_id = $order->get_user_id();
			$orders[sanitize_text_field($_POST['_wpfunnels_checkout_id'])] = $order_id;
			WC()->session->set('wpfnl_orders_'.$user_id.'_'.$funnel_id, $orders);

			$session_handler = new WC_Session_Handler();
			WC()->session->set('wpfnl_order_owner', $session_handler->generate_customer_id());

			/**
			 * Fires after a funnel order is placed.
			 *
			 * @since 1.0.0
			 *
			 * @param int    $order_id               The ID of the order.
			 * @param string $funnel_id              The ID of the funnel.
			 * @param string $_POST['_wpfunnels_checkout_id']  The checkout ID associated with the order.
			 */
			do_action( 'wpfunnels/funnel_order_placed', $order_id, $funnel_id, $_POST['_wpfunnels_checkout_id'] );
		}
	}


	/**
	 * Retrieves the updated price of a product.
	 *
	 * @param float $product_price The original price of the product.
	 * @return float The updated price of the product.
	 * 
	 * @since 3.1.8
	 */
	public function get_updated_price( $product_price ) {
		global $woocommerce_wpml;
		$multi_currency = new \WCML_Multi_Currency();
		$price_instance       = new \WCML_Multi_Currency_Prices( $multi_currency, $woocommerce_wpml->get_setting( 'currency_options' ) );
		$new_price = $price_instance->raw_price_filter( $product_price );
		return $new_price;
	}


    /**
     * Check if WooCommerce Multilingual plugin is activated
     *
     * @return bool
     * @since  3.1.8
     */
    public function maybe_activate() {
        return defined('WCML_VERSION');
    }
}
