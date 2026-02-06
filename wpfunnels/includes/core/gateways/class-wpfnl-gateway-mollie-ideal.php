<?php

namespace WPFunnels\Gateway;

use WPFunnels\Wpfnl_functions;

/**
 * Class Wpfnl_Pro_Gateway_Mollie_Idea
 * @package WPFunnelsPro\Frontend\Gateways
 */
class Wpfnl_Gateway_Mollie_Idea extends Wpfnl_Pro_Mollie_Helper {

    /**
     * @var string
     */
    public $key = 'mollie_wc_gateway_ideal';


    /**
     * @var bool
     */
    public $refund_support;


    public function __construct() {
        $this->refund_support = true;
        add_filter( 'woocommerce_mollie_wc_gateway_ideal_args', array( $this, 'maybe_create_mollie_customer_id' ), 10, 2 );

        add_action( 'wp_ajax_wpf_mollie_ideal_payment_process', array( $this, 'process_ideal_payment' ) );
        add_action( 'wp_ajax_nopriv_wpf_mollie_ideal_payment_process', array( $this, 'process_ideal_payment' ) );

        // run this action after successful payment to mollie
        add_action( 'woocommerce_api_wpfnl_mollie_ideal_webhook', array( $this, 'maybe_process_mollie_webhook' ) );

        // hook for saving refund meta
        add_action( 'wpfunnels/child_order_created_' . $this->key, array( $this, 'store_mollie_meta_keys_for_refund' ), 10, 3 );

        // hook for saving subscription meta
        add_action( 'wpfunnels/subscription_created', array( $this, 'save_offer_subscription_meta' ), 10, 3 );
    }



    /**
     * process ajax for mollie ideal
     *
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function process_ideal_payment() {
        $response = $this->process_mollie_payment( 'ideal', 'ideal' );
        wp_send_json( $response );
    }

}

