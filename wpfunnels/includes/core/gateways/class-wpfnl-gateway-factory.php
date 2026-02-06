<?php

namespace WPFunnels\Gateway;


use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class Payment_Gateways_Factory {

    use SingletonTrait;

    protected $payment_gateway;


    public function __construct() {
        
        if( Wpfnl_functions::is_wc_active() ){
            add_action( 'wp_loaded', array( $this, 'load_payment_gateway_integrations' ), 5 );
            add_action( 'wp_ajax_nopriv_wpfunnels_create_express_checkout_token', array( $this, 'create_wc_express_checkout_token' ), 9999 );
            add_action( 'wp_ajax_wpfunnels_create_express_checkout_token', array( $this, 'create_wc_express_checkout_token' ), 9999 );

            add_action( 'wp_ajax_nopriv_wpfunnels_create_express_checkout_token_wc', array( $this, 'create_wc_paypal_express_checkout_token' ), 9999 );
            add_action( 'wp_ajax_wpfunnels_create_express_checkout_token_wc', array( $this, 'create_wc_paypal_express_checkout_token' ), 9999 );

            add_action( 'wp_ajax_nopriv_wpfunnels_create_paypal_express_checkout_token', array( $this, 'create_paypal_express_checkout_token' ), 9999 );
            add_action( 'wp_ajax_wpfunnels_create_paypal_express_checkout_token', array( $this, 'create_paypal_express_checkout_token' ), 9999 );

            /**
             * Paypal API rtetreieving hook for creating billing agreement
             */
            add_action( 'woocommerce_api_wpfunnels_paypal', array( $this, 'maybe_handle_paypal_api_call' ),9999 );
            add_action( 'woocommerce_api_wpfunnels_paypal_express', array( $this, 'maybe_handle_paypal_express_api_call' ),9999 );

            /** Show only supported payment gateway in funnel checkout page */
            add_filter( 'woocommerce_available_payment_gateways', [$this, 'wpfnl_show_supported_payment_gateway'], 10, 1 );
        }
        
    }


    /**
     *
     */
    public function load_payment_gateway_integrations() {
        $available_gateways = \WC()->payment_gateways()->get_available_payment_gateways();
        if ( false === is_array( $available_gateways ) ) {
            return $available_gateways;
        }
        $supported = array_keys( $available_gateways );
        foreach ( $supported as $key ) {
            $this->build_gateway( $key );
        }
        return $available_gateways;
    }



    /**
     * build payment gateway object
     * based on user selection
     *
     * @param $gateway
     * @return bool
     *
     * @since 1.0.0
     */
    public function build_gateway( $gateway ){
        
        $gateways = $this->get_supported_payment_gateways();
        if(isset($gateways[$gateway])) {
            $gateway_class = "WPFunnels\\Gateway\\".$gateways[$gateway];
            $this->payment_gateway = new $gateway_class();
            
            return $this->payment_gateway;
        }

        return false;
    }


    /**
     * get all supported payment gateways of WPFunnels
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_supported_payment_gateways() {
        $gateways = array(
            'stripe'                        => 'Wpfnl_Stripe_payment_process',
            'paypal'                        => 'Wpfnl_Gateway_Paypal',
            'ppcp-card-button-gateway'      => 'Wpfnl_Gateway_Paypal',
            'ppcp-gateway'                  => 'Wpfnl_Gateway_Paypal_WooCommerce',
            'cod'                           => 'Wpfnl_Gateway_Cod',
            'mollie_wc_gateway_creditcard'  => 'Wpfnl_Gateway_Mollie',
            'mollie_wc_gateway_ideal'       => 'Wpfnl_Gateway_Mollie_Idea',
            'authorize_net_cim_credit_card' => 'Wpfnl_Gateway_Authorize_Net',
            'officeguy'                     => 'Wpfnl_Gateway_OfficeGuy',
            'payfast'                       => 'Wpfnl_Gateway_PayFast',
            'square_credit_card'            => 'Wpfnl_Gateway_Square',
            'bacs'                          => 'Wpfnl_Gateway_Bacs',
            'cheque'                        => 'Wpfnl_Gateway_Cheque',
            'woocommerce_payments'          => 'Wpfnl_Woocommerce_Payments'
        );
        return apply_filters('wpfunnels/supported_payment_gateways', $gateways);
    }


    /**
     * create paypal token
     * @since 1.0.0
     */
    public function create_wc_express_checkout_token() {
        $this->build_gateway( 'paypal' )->create_express_checkout_token();
    }


    /**
     * create paypal express token
     * @since 1.0.0
     */
    public function create_wc_paypal_express_checkout_token() {
        $this->build_gateway( 'ppec_paypal' )->create_express_checkout_token();
    }


    /**
     *
     */
    public function maybe_handle_paypal_api_call() {
        $this->build_gateway( 'paypal' )->maybe_create_billing();
        $this->build_gateway( 'paypal' )->handle_api_calls();
        
    }
    
    /**
     *
     */
    public function maybe_handle_paypal_express_api_call() {
        $this->build_gateway( 'ppec_paypal' )->maybe_create_billing();
        $this->build_gateway( 'ppec_paypal' )->handle_api_calls();
    }


    /**
     * Show supported payment gateway in funnel checkout page
     * 
     * @param Array $available_gateways
     * 
     * @return Array $available_gateways
     */
    public function wpfnl_show_supported_payment_gateway( $available_gateways ){
        
        $is_checkout = Wpfnl_functions::is_funnel_checkout_page();
        if( $is_checkout['status'] ){
            $is_funnel_checkout = Wpfnl_functions::check_if_this_is_step_type_by_id( $is_checkout['id'], 'checkout' );
            if( $is_funnel_checkout ){
                $offer_settings = Wpfnl_functions::get_offer_settings();
                if( isset($offer_settings['show_supported_payment_gateway']) && 'on' === $offer_settings['show_supported_payment_gateway'] ){
                    $supported_gateways     = $this->get_supported_payment_gateways();
                    foreach( $available_gateways as $key=>$gateway ){
                        if( !isset( $supported_gateways[$key] ) ){
                            unset( $available_gateways[$key] );
                        }
                    }
                }
            }
        }

        return $available_gateways;
    }

}
