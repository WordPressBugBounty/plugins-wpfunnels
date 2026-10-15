<?php
namespace WPFunnels\Gateway;

use WPFunnels\Wpfnl_functions;

/**
 * @author [WPFunnels Team]
 * @email [support@getwpfunnels.com]
 * @create date 2022-06-09 16:28:09
 * @modify date 2022-06-09 16:28:09
 * @desc [Supporting Direct Bank Transfer with WPFunnels one-click offer]
 */

class Wpfnl_Gateway_Bacs {

    /**
     * @var string
     * @since 1.6.7
     */
    public $key = 'bacs';

    /**
     * @var bool
     * @since 1.6.7
     */
    public $refund_support;

    /**
     * @var bool
     * @since 1.6.7
     */
    private $token = false;


    function __construct()
    {
        $this->token = true;
        add_filter('woocommerce_bacs_process_payment_order_status', array($this, 'maybe_setup_offer_for_bacs'), 10, 2);
    }


    /**
	 * Try and get the payment token saved by the gateway
	 *
	 * @param WC_Order $order
	 *
	 * @return true
     * @since 1.6.7
	 */
	public function has_token( $order ) {
		return $this->token;
	}

    /**
     * Possibly sets a custom order status for BACS payment method based on funnel settings.
     *
     * If the 'offer_orders' setting is set to 'main-order', a valid funnel ID exists
     * for the given order and the 'wc-wpfnl-main-order' status is registered (Pro),
     * the order status will be overridden with it. Otherwise, the original order
     * status will be returned unchanged.
     *
     * @param string        $order_status The current order status.
     * @param WC_Order      $order        The WooCommerce order object.
     *
     * @return string The modified or original order status.
     *  @since 2.5.10
     */
    public function maybe_setup_offer_for_bacs($order_status, $order){
        return Wpfnl_functions::get_offline_funnel_order_status( $order_status, $order );
    }


	/**
     * Process payment for one-click offer product
     * 
	 * @param mixed $order
	 * 
	 * @return array
     * @since 1.6.7
	 */
	public function process_payment( $order ) {

        if($this->key === $order->get_payment_method() && $this->has_token($order)){
            return array(
                'is_success' => true,
                'message' => 'Success'
            );
        }
		
	}

}