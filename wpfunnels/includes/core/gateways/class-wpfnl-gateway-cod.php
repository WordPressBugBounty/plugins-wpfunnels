<?php

/**
 * Wpfnl_Pro_Gateway_Cod
 */

namespace WPFunnels\Gateway;

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Gateway\API\Wpfnl_Gateway;


/**
 * Class Wpfnl_Pro_Gateway_Cod
 * @package WPFunnelsPro\Frontend\Gateways
 */
class Wpfnl_Gateway_Cod extends Wpfnl_Gateway {

    use SingletonTrait;

    public $key = 'cod';


    /**
     * @var bool $refund_support
     */
    public $refund_support;


    public function __construct() {
        $this->refund_support = false;
        add_filter( 'woocommerce_cod_process_payment_order_status', array( $this, 'maybe_setup_offer_for_cod' ), 9999, 2 );
    }


    /**
     * set new status for orders based on offer
     *
     * @param $order_status
     * @param $order
     * @return string
     */
    public function maybe_setup_offer_for_cod( $order_status, $order ) {
        return Wpfnl_functions::get_offline_funnel_order_status( $order_status, $order );
    }


    /**
     * process payment for offer
     *
     * @param $order
     * @param $offer_product
     * @return bool
     */
    public function process_payment( $order, $offer_product ) {
        $result['is_success'] = true;
        return $result;
    }
}