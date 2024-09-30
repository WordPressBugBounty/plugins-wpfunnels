<?php
/**
 * Checkout helper class
 *
 * @package
 * @since 2.7.9
 */
namespace WPFunnels\Modules\Frontend\CheckoutHelper;

use Error;
use WPFunnels\Discount\WpfnlDiscount;
use WPFunnels\Modules\Frontend\Checkout\Module;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;
class CheckoutHelper extends Module
{

	use SingletonTrait;
    /**
     * Update order bump product and order item meta.
     *
     * @param array  $order_bump_products An array of order bump products.
     * @param int    $orderID             The ID of the order.
     *
     * @return void
     * @since 2.7.9
     */

    public function update_order_bump_product( $order_bump_products, $order_id ) {
        if ( is_array( $order_bump_products ) && $order_id ){
            foreach( $order_bump_products as $key => $orderBumpProduct ){
                if ( isset( $_POST['_wpfunnels_order_bump_product_'.$key] ) ) {
                    $ob_product_id = wc_clean( sanitize_text_field( wp_unslash( $_POST['_wpfunnels_order_bump_product_'.$key] ) ) );
                    $order = wc_get_order( $order_id );
                    if( $order && !is_wp_error( $order ) ){
                        $order->update_meta_data('_wpfunnels_order_bump_product', $ob_product_id);
                        foreach ($order->get_items() as $order_item_id => $order_item) {
                            $product_id = !empty($order_item['variation_id']) ? $order_item['variation_id'] :$order_item['product_id'];
                            if( $ob_product_id == $product_id ) {
								$ob_products[] = $ob_product_id;
								$order->update_meta_data('_wpfunnels_order_bump_products', $ob_products );
                                wc_add_order_item_meta($order_item_id, '_wpfunnels_order_bump', 'yes');
                            }
                        }
                        $order->save();
                    }
                }
            }
        }
    }

    /**
     * Check if the variable product is in the order items and update the main product flag.
     *
     * @param array   $order_items              An array of order items.
     * @param array   $main_products            An array of main products.
     * @param boolean $is_main_product_in_cart  Flag indicating if the main product is already in the cart.
     *
     * @return array  An array containing the updated main products and the flag indicating if the main product is in the cart.
     * @since 2.7.9
     */
    public function variable_product_check( $order_items, $main_products, $is_main_product_in_cart ) {
        if ( is_array($order_items) && is_array($main_products) ){
            foreach( $order_items as $order_item ){
                $default_product_id = isset( $order_item['product_id'] ) ? $order_item['product_id'] : 0;
                $product_id         = !empty( $order_item['variation_id'] ) ? $order_item['variation_id'] : $default_product_id;
                if( !empty($main_products) && is_array($main_products) ){
                    $key = array_search($product_id, array_column($main_products, 'id'));
                    foreach( $main_products as $key=>$main_product ){
                        if( isset($main_product['id']) ){
                            $product_obj = wc_get_product( $product_id );
                            if( $product_obj ){
                                $_product_id = 'variation' === $product_obj->get_type() ? $product_obj->get_parent_id() : $product_obj->get_id();
                                $main_product_obj = wc_get_product( $main_product['id'] );
                                $main_product_id = 'variation' === $main_product_obj->get_type() ? $main_product_obj->get_parent_id() : $main_product_obj->get_id();
                                if( (int)$main_product_id === $_product_id){
                                    $is_main_product_in_cart = true;
                                    $main_products[$key]['id'] = $product_id;
                                    break;
                                }
                            }
                        }

                    }
                }
                if( $is_main_product_in_cart ){
                    break;
                }
            }
        }
        return array(
            'main_products'           => $main_products,
            'is_main_product_in_cart' => $is_main_product_in_cart
        );
    }

    /**
     * Update tax total and amount for order items and calculate the updated total.
     *
     * @param WC_Order $order       The order object.
     * @param float    $cart_total  The cart total.
     *
     * @return float  The updated total after adding the tax.
     * @since 2.7.9
     */
    protected function update_tax_total_and_calculate_total( $order, $cart_total ) {
        $tax = 0;

        foreach ($order->get_items(array('tax')) as $item_id => $line_item) {
            $order_product_detail = $line_item->get_data();

            if( isset($order_product_detail['tax_total']) &&  isset($order_product_detail['rate_percent']) ){
                $prev_tax = $order_product_detail['tax_total'];
                $tax = ( $cart_total * $order_product_detail['rate_percent'] ) / 100;
                wc_update_order_item_meta($item_id, 'tax_total', $tax);
                wc_update_order_item_meta($item_id, 'tax_amount', $tax);
            }
        }
        $total = $cart_total + $tax;
        return $total;
    }

    /**
     * Update the checkout ID post meta for an order.
     *
     * @param int    $order_id      The order ID.
     * @param string $checkout_id   The checkout ID.
     * @since 2.7.9
     */
    public function update_checkout_id_post_meta($order_id, $checkout_id) {
        if ( $order_id && $checkout_id && Wpfnl_functions::is_wc_active()) {
            $checkout_id = wc_clean($checkout_id);
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_wpfunnels_checkout_id', $checkout_id);
                $order->save();
            }
        }
    }

    /**
     * Update order meta for a funnel order.
     *
     * This function updates the meta data for a given order to associate it with a funnel.
     * It takes the order ID and funnel ID as parameters and returns the updated funnel ID.
     *
     * @param int    $order_id  The ID of the order.
     * @param string $funnel_id The ID of the associated funnel.
     * @return int|false The updated funnel ID, or false if the update fails.
     * @since 2.7.9
     */
    public function update_order_meta_for_funnel($order_id, $funnel_id) {
        if (!$funnel_id || !$order_id || !Wpfnl_functions::is_wc_active()) {
            return false;
        }

        $funnel_id = sanitize_text_field($funnel_id);
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $order->update_meta_data('_wpfunnels_funnel_id', $funnel_id);
        $order->update_meta_data('_wpfunnels_order', 'yes');
        $order->save();
        return $funnel_id;
    }

    /**
     * Update the unique identifier post meta for an order.
     *
     * @param int    $order_id              The order ID.
     * @param string $unique_identifier     The unique identifier.
     * @since 2.7.9
     */
    public function update_unique_identifier_post_meta($order_id, $unique_identifier) {
        if( $order_id ){
            $order = wc_get_order($order_id);
            if ( false !== is_a( $order, 'WC_Order' ) ) {
                $unique_identifier = wc_clean($unique_identifier);
                $order->update_meta_data( '_wpfunnels_order_unique_identifier', $unique_identifier);
                $order->save();
            }
        }

    }

    /**
     * Apply discount and update order total based on discount settings.
     *
     * @param int    $order_id           The ID of the order.
     * @param array  $discount           The discount settings.
     * @param bool   $is_main_product    Flag indicating if the main product is in the cart.
     * @param array  $main_products      An array of main products.
     * @param string $checkout_id        The checkout ID.
     *
     * @return int|float $total
     * @since 2.7.9
     */
    public function apply_discount_and_update_total( $order, $total, $discount, $is_main_product_in_cart ){
        if ( false === is_a( $order, 'WC_Order' ) || !is_array($discount) || !$is_main_product_in_cart ) {
            return false;
        }

        // Fetch existing fees to check if your discount fee already exists
		$existing_fees = $order->get_items('fee');


		// Initialize a variable to track if the discount fee is found
		$discount_fee_found = false;

		foreach ($existing_fees as $item_id => $item_fee) {

			// if ('Discount' === $item_fee->get_name() || 'wpfnl_discount' === $item_fee->get_meta('discount_type') ) {
			// 	$discount_fee_found = true;
			// 	break;
			// }

            if ( $item_fee->get_name() ) {
				$discount_fee_found = true;
				break;
			}
		}

        if( $discount_fee_found ){
            return false;
        }

        if ( !isset($discount['discountOptions']) || $discount['discountOptions'] === 'original') {
            return false;
        }

        $discount_instance = new WpfnlDiscount();
        $discount_amount = $discount_instance->get_discount_amount($discount['discountOptions'], $discount['mutedDiscountValue'], $total);
		
        $discount_amount = filter_var($discount_amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if (!$discount_amount) {
            return false;
        }
        $discount_amount = apply_filters('wpfunnels/checkout_discount_amount', $discount_amount);
        $coupon_discount = $order->get_meta( '_cart_discount' );
        $cart_discount   = floatval($discount_amount) + floatval($coupon_discount);
        $fee = new \WC_Order_Item_Fee();
        $fee->set_name('Discount');
        $fee->set_total('-'.$cart_discount);
        $order->add_item($fee);

        $fees = $order->get_items('fee');
        $discount_fee = end($fees);
        // Add metadata to the discount fee
        $discount_fee->add_meta_data('discount_type', 'wpfnl_discount');
        $order->calculate_totals();
        $order->save();
        return true;

    }


    /**
     * Get the custom subtotal.
     * This is the subtotal without the order bump products.
     *
     * @return float The custom subtotal.
     * @since 3.0.5
     */
    public function get_custom_subtotal($is_regular = false)
	{
		$cart_subtotal = 0;
		if ( is_object( WC()->cart ) && method_exists( WC()->cart, 'get_cart' ) ) {
			foreach ( \WC()->cart->get_cart() as $cart_item ) {
				if( !isset($cart_item['wpfnl_order_bump']) && isset($cart_item['line_subtotal']) ){

					if( $is_regular && isset($cart_item['bundled_items']) ){
						$product_id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] : $cart_item['product_id'];
						$product = wc_get_product($product_id);
						$price = $product->get_bundle_regular_price( 'min' ) ? $product->get_bundle_regular_price( 'min' ) : $product->get_bundle_price( 'min' );
						$cart_subtotal += $price;

					}else{
						$cart_subtotal += $cart_item['line_subtotal'];
					}

				}
			}
		}
		return $cart_subtotal;
	}


    /**
     * Retrieve the main products based on the checkout ID and funnel ID.
     *
     * @param int $checkout_id The ID of the checkout.
     * @param int $funnel_id   The ID of the funnel.
     *
     * @return array The main products.
     * @since 2.7.9
     */
    public function get_main_products($checkout_id, $funnel_id) {
        $main_products = get_post_meta($checkout_id, '_wpfnl_checkout_products', true);
        $is_gbf = get_post_meta($funnel_id, 'is_global_funnel', true);

        if ('yes' === $is_gbf) {
            $main_products = WC()->session->get('wpfunnels_global_funnel_product');
        }

        return $main_products;
    }

}
