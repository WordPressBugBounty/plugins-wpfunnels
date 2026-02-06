<?php
namespace WPFunnels\Gateway;

/**
 * @author [Getwpfunnels Team]
 * @email [support@getwpfunnels.com]
 * @create date 2022-06-09 16:28:09
 * @modify date 2022-06-09 16:28:09
 * @desc [Supporting Cheque payment with WPFunnels one-click offer]
 */

class Wpfnl_Gateway_Cheque {

    /**
     * @var string
     * @since 1.6.7
     */
    public $key = 'cheque';

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