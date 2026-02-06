<?php

namespace WPFunnels\Gateway\API;

use WPFunnels\Wpfnl_functions;

abstract class Wpfnl_Gateway extends Wpfnl_API_Base {

    protected $key;

    public function get_key() {
        return $this->key;
    }

    /**
     * This function checks for the need to do the tokenization.
     * We have to fetch the funnel to decide whether to tokenize the user or not.
     * @return int|false funnel ID on success false otherwise
     *
     */
    public function should_tokenize($funnel_id) {

        return Wpfnl_functions::is_funnel_exists($funnel_id);
    }


    /**
     * Get WooCommerce payment geteways.
     *
     * @return array
     */
    public function get_wc_gateway() {

        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();
       
        return $gateways[ $this->key ];
    }


    /**
     * Round a float number
     *
     * @param float $number
     * @param int $precision Optional. The number of decimal digits to round to.
     *
     * @since 1.0.0
     *
     */
    public function round( $number, $precision = 2 ) {
        return round( (float) $number, $precision );
    }


    /**
     * Helper method to return the item description, which is composed of item
     * meta flattened into a comma-separated string, if available. Otherwise the
     * product SKU is included.
     *
     * The description is automatically truncated to the 127 char limit.
     *
     * @param array $item cart or order item
     * @param \WC_Product $product product data
     *
     * @return string
     * @since 2.0
     */
    public function get_item_description( $product ) {

        // Check if pro function exists, otherwise use basic description
        if ( class_exists('\WPFunnelsPro\Wpfnl_Pro_functions') && method_exists('\WPFunnelsPro\Wpfnl_Pro_functions', 'get_item_description') ) {
            return \WPFunnelsPro\Wpfnl_Pro_functions::get_item_description($product);
        }

        // Fallback implementation
        if ( $product && is_object($product) ) {
            $description = $product->get_name();
            if ( $product->get_sku() ) {
                $description .= ' (' . $product->get_sku() . ')';
            }
            return substr( $description, 0, 127 );
        }

        return '';
    }


    /**
     * Format prices.
     *
     * @param float|int $price
     * @param int $decimals Optional. The number of decimal points.
     *
     * @return string
     * @since 2.2.12
     *
     */
    public function price_format( $price, $decimals = 2 ) {
        return number_format( $price, $decimals, '.', '' );
    }


    /**
     * get order number
     *
     * @param \WC_Order $order
     * @return int|mixed|void
     *
     * @since 1.0.0
     */
    public function get_order_number( \WC_Order $order, $step_id ) {

        if ( ! empty( $step_id ) ) {
            return apply_filters( 'wpfunnels/payments_get_order_number', $order->get_id() . '_' . $step_id, $this );
        } else {
            return $order->get_id();
        }

    }


    /**
     * @param $response
     * @param $key
     * @return mixed
     */
    public function get_value_from_response($response, $key) {

        if ($response && isset($response[$key])) {

            return $response[$key];
        }
    }

    /**
     * check if payment gateway is enabled
     *
     * @param \WC_Order $order
     *
     * @return bool
     * @since 1.0.0
     */
    public function is_enabled( $order = false ) {
        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();
        if ( is_array( $gateways ) && in_array( $this->key, $gateways, true ) ) {
            return true;
        }

        return false;
    }

    public function to_string() {
        return http_build_query($this->get_parameters(), '', '&');
    }

}
