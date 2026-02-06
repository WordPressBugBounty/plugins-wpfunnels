<?php

namespace WPFunnels\Gateway;

use WPFunnels\Gateway\API\Wpfnl_Gateway;
use WPFunnels\Wpfnl_functions;
class Wpfnl_Gateway_Paypal_WooCommerce extends Wpfnl_Gateway {

    public $key = 'ppcp-gateway';

    public $refund_support;

    public function __construct() {

        $this->refund_support = true;

        add_action( 'wp_ajax_wpfnl_create_paypal_order', array( $this, 'create_wc_paypal_order' ),9999 );
        add_action( 'wp_ajax_nopriv_wpfnl_create_paypal_order', array( $this, 'create_wc_paypal_order' ),9999 );

        add_action( 'wp_ajax_wpfnl_capture_paypal_order', array( $this, 'capture_paypal_order' ),9999 );
        add_action( 'wp_ajax_nopriv_wpfnl_capture_paypal_order', array( $this, 'capture_paypal_order' ),9999 );

        add_filter( 'woocommerce_paypal_refund_request', array( $this, 'offer_refund_request_data' ), 9999, 4 );

        add_action( 'wpfunnels/child_order_created_' . $this->key, array( $this, 'add_capture_meta_to_child_order' ), 9999, 3 );

        add_action( 'wpfunnels/subscription_created', array( $this, 'add_offer_subscription_meta' ), 9999, 3 );
    }


    /**
     * process upsell payment
     *
     * @param $order
     * @param $offer_product
     * @return bool
     */
    public function process_payment( $order, $offer_product ) {
        $result = array(
            'is_success'    => false,
            'message'       => ''
        );
        $txn_id             = $order->get_meta( '_wpfunnels_capture_paypal_txn_id_' . $offer_product['step_id']. '_' .$order->get_id() );
        if ( empty( $txn_id ) ) {
            $result['is_success'] = false;
        } else {
            $is_charge_success = true;
            $response = array(
                'id' => $txn_id,
            );
            $order->update_meta_data( '_wpfunnels_offer_txn_resp_' . $offer_product['step_id'], $response['id'] );
            $order->save();
            $result['is_success'] = true;
        }

        return $result;
    }


    /**
     * return array
     */
    public function create_wc_paypal_order() {
       
        $nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'wpfnl_create_paypal_order_nonce' ) ) {
            return;
        }
        $settings = get_option( 'woocommerce-ppcp-settings', true );
        $funnel_id   = isset( $_POST['funnel_id'] ) ? intval( $_POST['funnel_id'] ) : 0;
        $step_id     = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $order_id    = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : 0;
        $order_key   = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';
        $step_type   = get_post_meta( $step_id, '_step_type', true );

        $order        = wc_get_order( $order_id );
        $variation_id = '';
        $quantity     = '';

        $args = array(
            'funnel_id' => $funnel_id,
            'step_id'   => $step_id,
            'order_id'  => $order_id,
            'order_key' => $order_key,
            'ppcp_data' => $this->get_ppcp_meta( $order ),
        );

        /** get the authorization token */
        $token = $this->get_token( $order );

        if ( isset( $_POST['variation_id'] ) && ! empty( $_POST['variation_id'] ) ) {
            $variation_id = intval( $_POST['variation_id'] );
        }
        if ( isset( $_POST['input_qty'] ) && ! empty( $_POST['input_qty'] ) ) {
            $quantity = intval( $_POST['input_qty'] );
        }
        $offer_product  = get_post_meta( $step_id, '_wpfnl_'.$step_type.'_products', true );
        
        if ( isset( $offer_product[0]['id'] ) ) {
            $vr_product = wc_get_product($offer_product[0]['id']);
            if( $vr_product  && 'variable' == $vr_product->get_type() ){
                $payload = [
                    'product_id' => $offer_product[0]['id'],
                    'data'        => $_POST['attr'],
                ];
                $variation_id = Wpfnl_functions::modify_offer_product($payload);
            }
            
        }
        
        $offer_product = Wpfnl_functions::get_offer_product_data( $step_id, $variation_id, $quantity, $order_id ); 
        
        $return_arg = [
            'wpfnl-order'           => $order_id,
            'wpfnl-key'             => $order_key,
            'key'             => $order_key,
            'wpfnl-paypal-return'   => true,
        ];
        if( $variation_id ){
            $return_arg['wpfnl-variation-id'] = $variation_id;
        }
        /** data for generating order */
        $data = array(
            'intent'              => $args['ppcp_data']['intent'],
            'purchase_units'      => $this->generate_purchase_unit( $order, $offer_product, $args ),
            'application_context' => array(
                'brand_name'   => isset($settings['brand_name']) ? $settings['brand_name'] : '',
                'landing_page' => isset($settings['landing_page']) ? $settings['landing_page'] : 'LOGIN',
                'user_action'  => 'CONTINUE',
                "shipping_preference" => "GET_FROM_FILE",
                'return_url'   => add_query_arg( $return_arg, get_the_permalink($step_id)),
                'cancel_url'   => add_query_arg(array(
                    'wpfnl-order'           => $order_id,
                    'wpfnl-key'             => $order_key,
                    'key'             => $order_key,
                    'wpfnl-paypal-cancel'   => true,
                ), get_the_permalink($step_id)),
                
            ),
            'payment_method'      => array(
                'payee_preferred' => 'UNRESTRICTED',
                'payer_selected'  => 'PAYPAL',
            ),
            'payment_instruction' => array(
                'disbursement_mode' => 'INSTANT',
                'platform_fees'     => array(
                    array(
                        'amount' => array(
                            'currency_code' => $this->get_currency( $order ),
                            'value'         => $offer_product['unit_price_tax'],
                        ),
                    ),
                ),
            ),

        );

        $arguments = array(
            'method'  => 'POST',
            'headers' => array(
                'Accept'                        => 'application/json',
                'Content-Type'                  => 'application/json',
                'Authorization'                 => 'Bearer ' . $token,
                'PayPal-Partner-Attribution-Id' => 'WPF_WC_PAYPAL',
            ),
            'body'    => wp_json_encode( $data ),
        );
        $is_sandbox     = isset($settings['sandbox_on']) ? $settings['sandbox_on'] : false;
        $payment_env    = $is_sandbox ? 'sandbox' : 'production';
        $host           = 'https://api-m.'.$payment_env.'.';
        if($payment_env === 'production') {
            $host           = 'https://api-m.';
        }
        $url            = $host. 'paypal.com/v2/checkout/orders/';

        $response       = wp_remote_get( $url, $arguments );
      
        if ( is_wp_error( $response ) ) {
           
            $token = $this->get_token( $order, true );
            $arguments = array(
                'method'  => 'POST',
                'headers' => array(
                    'Accept'                        => 'application/json',
                    'Content-Type'                  => 'application/json',
                    'Authorization'                 => 'Bearer ' . $token,
                    'PayPal-Partner-Attribution-Id' => 'WPF_WC_PAYPAL',
                ),
                'body'    => wp_json_encode( $data ),
            );
            $is_sandbox     = isset($settings['sandbox_on']) ? $settings['sandbox_on'] : false;
            $payment_env    = $is_sandbox ? 'sandbox' : 'production';
            $host           = 'https://api-m.'.$payment_env.'.';
            if($payment_env === 'production') {
                $host           = 'https://api-m.';
            }
            $url            = $host. 'paypal.com/v2/checkout/orders/';
    
            $response       = wp_remote_get( $url, $arguments );
            if ( is_wp_error( $response ) ) {
                $json_response = array(
                    'status'          => false,
                    'message'         => $response->get_error_message(),
                );
                return wp_send_json( $json_response );
            }            
            
        }
        $json           = json_decode( $response['body'] );
        $json_response = array(
            'result'    => false,
            'message'   => __( 'PayPal order is not created', 'wpfnl-pro' ),
            'response'  => $response,
        );
        
        if ( $json->status ?? false && 'CREATED' === $json->status ) {
            $order->update_meta_data( '_wpfunnels_paypal_'.$step_type.'_'.$step_id.'_order_id_'.$order_id, $json->id );
            $order->save();
            $json_response = array(
                'status'          => 'success',
                'message'         => __( 'Order created successfully', 'wpfnl-pro' ),
                'paypal_order_id' => $json->id,
                'redirect'        => $json->links[1]->href,
                'response'        => $json,
            );
            
        }else{
            $token = $this->get_token( $order, true );
            $arguments = array(
                'method'  => 'POST',
                'headers' => array(
                    'Accept'                        => 'application/json',
                    'Content-Type'                  => 'application/json',
                    'Authorization'                 => 'Bearer ' . $token,
                    'PayPal-Partner-Attribution-Id' => 'WPF_WC_PAYPAL',
                ),
                'body'    => wp_json_encode( $data ),
            );
            $is_sandbox     = isset($settings['sandbox_on']) ? $settings['sandbox_on'] : false;
            $payment_env    = $is_sandbox ? 'sandbox' : 'production';
            $host           = 'https://api-m.'.$payment_env.'.';
            if($payment_env === 'production') {
                $host           = 'https://api-m.';
            }
            $url            = $host. 'paypal.com/v2/checkout/orders/';
    
            $response       = wp_remote_get( $url, $arguments );
            if ( is_wp_error( $response ) ) {
                $json_response = array(
                    'status'          => false,
                    'message'         => $response->get_error_message(),
                );
                return wp_send_json( $json_response );
            }else{
                $json           = json_decode( $response['body'] );
                $json_response = array(
                    'result'    => false,
                    'message'   => __( 'PayPal order is not created', 'wpfnl-pro' ),
                    'response'  => $response,
                );
                $order->update_meta_data( '_wpfunnels_paypal_'.$step_type.'_'.$step_id.'_order_id_'.$order_id, $json->id );
                $order->save();
                $json_response = array(
                    'status'          => 'success',
                    'message'         => __( 'Order created successfully', 'wpfnl-pro' ),
                    'paypal_order_id' => $json->id,
                    'redirect'        => $json->links[1]->href,
                    'response'        => $json,
                );
            }    
        }
        return wp_send_json( $json_response );
    }


    /**
     * @throws \WooCommerce\PayPalCommerce\WcGateway\Exception\NotFoundException
     */
    public function capture_paypal_order() {
        $nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'wpfnl_capture_paypal_order_nonce' ) ) {
            return;
        }
        $order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : 0;
        $step_id    = isset( $_POST['step_id'] ) ? sanitize_text_field( wp_unslash( $_POST['step_id'] ) ) : 0;

        $order = wc_get_order( $order_id );
        $token              = $this->get_token( $order );
        $step_type          = isset( $_POST['step_type'] ) ? sanitize_text_field( wp_unslash( $_POST['step_type'] ) ) : '';
        $paypal_order_id    = $order->get_meta('_wpfunnels_paypal_'.$step_type.'_'.$step_id.'_order_id_' . $order->get_id());
        $settings = get_option( 'woocommerce-ppcp-settings', true );
        $is_sandbox     = isset($settings['sandbox_on']) ? $settings['sandbox_on'] : false;
        $payment_env    = $is_sandbox ? 'sandbox' : 'production';
        $host           = 'https://api-m.'.$payment_env.'.';
        if($payment_env === 'production') {
            $host           = 'https://api-m.';
        }

        $url = $host. 'paypal.com/v2/checkout/orders/'.$paypal_order_id.'/capture';
        if ( 'AUTHORIZE'  === $order->get_meta( '_ppcp_paypal_intent' ) ) {
            $url            = $host. 'paypal.com/v2/checkout/orders/'.$paypal_order_id.'/authorize';
        }

        $args = array(
            'method'  => 'POST',
            'headers' => array(
                'Authorization'                 => 'Bearer ' . $token,
                'Content-Type'                  => 'application/json',
                'Prefer'                        => 'return=representation',
                'PayPal-Partner-Attribution-Id' => 'WPF_WC_PAYPAL',
            ),
        );
        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            $token = $this->get_token( $order, true );
            $args = array(
                'method'  => 'POST',
                'headers' => array(
                    'Authorization'                 => 'Bearer ' . $token,
                    'Content-Type'                  => 'application/json',
                    'Prefer'                        => 'return=representation',
                    'PayPal-Partner-Attribution-Id' => 'WPF_WC_PAYPAL',
                ),
            );
            $response = wp_remote_get( $url, $args );
            if ( is_wp_error( $response ) ) {
                $json_response = array(
                    'status'          => false,
                    'message'         => $response->get_error_message(),
                    'response'        => $response,
                );
                return wp_send_json( $json_response );
            }

        } 
        $json           = json_decode( $response['body'] );
        $json_response = array(
            'status'          => false,
            'message'         => __( 'PayPal order is not created', 'wpfnl-pro' ),
            'paypal_order_id' => '',
            'redirect_url'    => '',
            'response'        => $json,
        );
        if ( isset($json->status) &&  $json->status ?? false && ('CREATED' === $json->status || 'COMPLETED' === $json->status ) ) {
            
            if ( 'AUTHORIZE'  === $order->get_meta( '_ppcp_paypal_intent' ) ) {
                $txn_id = isset($json->purchase_units[0]->payments->authorizations[0]->id) ? $json->purchase_units[0]->payments->authorizations[0]->id : '';
            } else {
                $txn_id = isset($json->purchase_units[0]->payments->captures[0]->id) ? $json->purchase_units[0]->payments->captures[0]->id : '';
            }

            $order->update_meta_data( '_wpfunnels_capture_paypal_txn_id_' . $step_id. '_' .$order->get_id(), $txn_id );
            $order->save();
            
            if( !$this->maybe_child_order() ){
                $prev_fees = $order->get_meta('_ppcp_paypal_fees');
                if( $prev_fees && isset($prev_fees['paypal_fee']['value'],$prev_fees['net_amount']['value'])){
                    if( isset($json->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value, $json->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->net_amount->value)){
                        $prev_fees['paypal_fee']['value'] = $prev_fees['paypal_fee']['value'] + ($json->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value);                
                        $prev_fees['net_amount']['value'] = $prev_fees['net_amount']['value'] + ($json->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->net_amount->value);
                        $order->update_meta_data('_ppcp_paypal_fees', $prev_fees);
                    }
                }
            }
            
            $json_response = array(
                'status'          => 'success',
                'message'         => __('Order Captured successfully', 'wpfnl-pro' ),
                'paypal_order_id' => $json->id,
                'redirect_url'    => '',
                'response'        => $json,
            );
        }else{
            $token = $this->get_token( $order, true );
            $args = array(
                'method'  => 'POST',
                'headers' => array(
                    'Authorization'                 => 'Bearer ' . $token,
                    'Content-Type'                  => 'application/json',
                    'Prefer'                        => 'return=representation',
                    'PayPal-Partner-Attribution-Id' => 'WPF_WC_PAYPAL',
                ),
            );
            $response = wp_remote_get( $url, $args );
            if ( is_wp_error( $response ) ) {
                $json_response = array(
                    'status'          => false,
                    'message'         => $response->get_error_message(),
                    'response'        => $response,
                );
                return wp_send_json( $json_response );
            }else{
                $json           = json_decode( $response['body'] );
                $json_response = array(
                    'status'          => false,
                    'message'         => __( 'PayPal order is not created', 'wpfnl-pro' ),
                    'paypal_order_id' => '',
                    'redirect_url'    => '',
                    'response'        => $json,
                );
                if ( 'AUTHORIZE'  === $order->get_meta( '_ppcp_paypal_intent' ) ) {
                    $txn_id = isset($json->purchase_units[0]->payments->authorizations[0]->id) ? $json->purchase_units[0]->payments->authorizations[0]->id : '';
                } else {
                    $txn_id = isset($json->purchase_units[0]->payments->captures[0]->id) ? $json->purchase_units[0]->payments->captures[0]->id : '';
                }
    
                $order->update_meta_data( '_wpfunnels_capture_paypal_txn_id_' . $step_id. '_' .$order->get_id(), $txn_id );
                $order->save();
                
                if( !$this->maybe_child_order() ){
                    $prev_fees = $order->get_meta('_ppcp_paypal_fees');
                    if( $prev_fees && isset($prev_fees['paypal_fee']['value'],$prev_fees['net_amount']['value'])){
                        if( isset($json->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value, $json->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->net_amount->value)){
                            $prev_fees['paypal_fee']['value'] = $prev_fees['paypal_fee']['value'] + ($json->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value);                
                            $prev_fees['net_amount']['value'] = $prev_fees['net_amount']['value'] + ($json->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->net_amount->value);
                            $order->update_meta_data('_ppcp_paypal_fees', $prev_fees);
                        }
                    }
                }
                
                $json_response = array(
                    'status'          => 'success',
                    'message'         => __('Order Captured successfully', 'wpfnl-pro' ),
                    'paypal_order_id' => $json->id,
                    'redirect_url'    => '',
                    'response'        => $json,
                );
            }
        }
        return wp_send_json( $json_response );
    }


    /**
     * @param $order
     * @return array
     */
    public function get_ppcp_meta( $order ) {
        $settings = get_option( 'woocommerce-ppcp-settings', true );
        return array(
            'environment'    => $order->get_meta( '_ppcp_paypal_payment_mode' ),
            'intent'         => $order->get_meta( '_ppcp_paypal_intent' ),
            'merchant_email' => isset($settings['merchant_email']) ?  $settings['merchant_email'] : '',
            'merchant_id'    => isset($settings['merchant_id']) ?  $settings['merchant_id'] : '',
            'invoice_prefix' => isset($settings['prefix']) ?  $settings['prefix'] : '',
        );
    }


    /**
     * get access token
     *
     * @param $order
     * @return string
     * @throws \WooCommerce\PayPalCommerce\WcGateway\Exception\NotFoundException
     */
    public function get_token( $order,$is_new = false) {
        $token = '';
        if( !$is_new ){
            $bearer = get_option( '_transient_ppcp-paypal-bearerppcp-bearer' );
            if ( !empty( $bearer ) ) {
                $bearer = json_decode( $bearer );
                $token  = $bearer->access_token;
            }
        }else{
            $token = '';
        }
        
        if ( empty( $token ) ) {
            $settings = get_option( 'woocommerce-ppcp-settings', true );
            $is_sandbox     = isset($settings['sandbox_on']) ? $settings['sandbox_on'] : false;
            $payment_env    = $is_sandbox ? 'sandbox' : 'production';
            $key            = isset($settings['client_id']) ? $settings['client_id'] : '';
            $secret         = isset($settings['client_secret']) ? $settings['client_secret'] : '';
            $host           = 'https://api-m.'.$payment_env.'.';
            $host           = 'https://api-m.'.$payment_env.'.';
            if($payment_env === 'production') {
                $host           = 'https://api-m.';
            }

            $url            = $host. 'paypal.com/v1/oauth2/token?grant_type=client_credentials';
            $args     = array(
                'method'  => 'POST',
                'headers' => array(
                    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
                    'Authorization' => 'Basic ' . base64_encode( $key . ':' . $secret ),
                ),
            );
            $response = wp_remote_get( $url, $args );
            if ( ! is_wp_error( $response ) ) {
                $res_body = json_decode( $response['body'] );
                $token    = $res_body->access_token;
            }
        }

        return $token;
    }


    /**
     * generate purchase unit data
     *
     * @param $order
     * @param $offer_product
     * @param $args
     * @return array
     */
    public function generate_purchase_unit( $order, $offer_product, $args ) {
        $invoice_id  = $args['ppcp_data']['invoice_prefix'] . '-wpfnl-' . $args['order_id'] . '_' . $args['funnel_id'] . '_' . $args['step_id'];
        $unit_value = round($offer_product['unit_price_tax']/$offer_product['qty'],2);
        $value = round($unit_value * $offer_product['qty'],2);
        return array(
            array(
                'reference_id'  => 'default',
                'amount'        => array(
                    'currency_code' => $this->get_currency($order),
                    'value'         => strval($value),
                    'breakdown'     => $this->get_item_breakdown( $order, $offer_product )
                ),
                'description'  => $offer_product['desc'],
                'items'         => array( $this->get_items($order, $offer_product) ),
                'payee'         => array(
                    'email_address' => $args['ppcp_data']['merchant_email'],
                    'merchant_id'   => $args['ppcp_data']['merchant_id'],
                ),
                'invoice_id'    => $invoice_id,
                'custom_id'     => $invoice_id,
                'shipping'      => array(
                    'name' => array(
                        'full_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    ),
                ),
            )
        );
    }


    /**
     * generate item breakdown data
     *
     * @param $order
     * @param $offer_product
     * @return mixed
     */
    public function get_item_breakdown( $order, $offer_product ) {
        $unit_value = round($offer_product['unit_price_tax']/$offer_product['qty'],2);
        $value = round($unit_value * $offer_product['qty'],2);
        $breakdown['item_total'] = array(
            'currency_code' => $this->get_currency($order),
            'value'         => strval($value),
        );
//        if ( ! empty( $offer_product['shipping_fee'] ) ) {
//            $breakdown['shipping'] = array(
//                'currency_code' => $this->get_currency($order),
//                'value'         => $offer_product['shipping_fee_incl_tax'],
//            );
//        }
        return $breakdown;
    }


    /**
     * prepare order items
     *
     * @param $order
     * @param $offer_product
     * @return array
     */
    public function get_items($order, $offer_product) {
        
        $value = round($offer_product['unit_price_tax']/$offer_product['qty'],2);

        return array(
            'name'        => $offer_product['name'],
            'unit_amount' => array(
                'currency_code' => $this->get_currency($order),
                'value'         => strval($value),
            ),
            'quantity'      => $offer_product['qty'],
            'id'            => $offer_product['id'],
            'description' => wp_strip_all_tags( $offer_product['desc'] ),
        );
    }


    /**
     * get the currency
     *
     * @param \WC_Order $order
     * @return string
     */
    public function get_currency( \WC_Order $order) {
        return $order->get_currency() ? $order->get_currency() : get_woocommerce_currency();
    }


    /**
     * replace the transaction id of the offer instead of the parent order
     * transaction id
     *
     * @param $request
     * @param $order
     * @param $amount
     * @param $reason
     * @return mixed
     */
    public function offer_refund_request_data( $request, $order, $amount, $reason ) {
        $payment_method = $order->get_payment_method();
        if ($this->key !== $payment_method) {
            return $request;
        }

        if (isset($_POST['txn_id']) && !empty($_POST['txn_id'])) {
            $request['TRANSACTIONID'] = wc_clean($_POST['txn_id']);
        }

        return $request;
    }


    /**
     * filter paypal order id for offer refund
     *
     * @param $value
     * @param $order
     * @return mixed
     */
    public function paypal_order_id_for_offer( $value, $order ) {
        if($this->is_offer_exits_in_order( $order )) {
            $step_id    = $this->get_refund_processing_step_from_session();
            if( $step_id ) {
                $step_type = get_post_meta( $step_id, '_step_type', true );
                if( $step_type && $order->get_meta('_wpfunnels_paypal_'.$step_type.'_'.$step_id.'_order_id_'.$order->get_id() )) {
                    $value = $order->get_meta('_wpfunnels_paypal_'.$step_type.'_'.$step_id.'_order_id_'.$order->get_id());
                }
            }
        }
        return $value;
    }


    /**
     * get refund procession step id from
     * session
     *
     * @return string|null
     */
    private function get_refund_processing_step_from_session() {
        return WC()->session->get('wpfunnels_refund_processing_step_id');
    }


    /**
     * check if offer exits in order
     *
     * @param \WC_Order $order
     * @return bool
     */
    private function is_offer_exits_in_order( \WC_Order $order ) {
        $is_offer   = false;
        $line_items = $order->get_items();
        foreach ($line_items as $item_id => $item) {
            $is_upsell_offer    = wc_get_order_item_meta( $item_id, '_wpfunnels_upsell', true );
            $is_downsell_offer  = wc_get_order_item_meta( $item_id, '_wpfunnels_downsell', true );
            $txn_id             = wc_get_order_item_meta( $item_id, '_wpfunnels_offer_txn_id', true );
            if ( 'yes' == $is_upsell_offer || 'yes' == $is_downsell_offer && ! empty( $txn_id ) ) {
                $is_offer = true;
                break;
            }
        }
        return $is_offer;
    }


    /**
     * process refund offer
     *
     * @param $order
     * @param $data
     * @return false
     */
    public function process_refund_offer( $order, $data ) {
        $transaction_id = $data['transaction_id'];
        $amount         = $data['amount'];
        $refund_reason  = $data['reason'];
        $offer_id       = $data['offer_id'];
        $step_id        = $data['step_id'];
        $response = false;
        $gateway = $this->get_wc_gateway();
        if ( $this->refund_support ) {
            WC()->session->set( 'wpfunnels_refund_processing_step_id', null );
            WC()->session->set( 'wpfunnels_refund_processing_step_id', $step_id );
            add_filter( 'woocommerce_order_get__ppcp_paypal_order_id', array( $this, 'paypal_order_id_for_offer' ), 10, 4 );
//            WC()->session->set( 'wpfunnels_refund_processing_step_id', null );
            $result = $gateway->process_refund( $order->get_id(), $amount, $refund_reason );
            if ( is_wp_error( $result ) ) {
            } elseif ( $result ) {
                $response = $result;
            }
        }

        return $response;
    }


    /**
     * add required meta for refund
     *
     * @param $parent_order
     * @param $child_order
     * @param $transaction_id
     */
    public function add_capture_meta_to_child_order( $parent_order, $child_order, $transaction_id ) {
        if ( ! empty( $transaction_id ) ) {
            $offer_type         = $child_order->get_meta('_wpfunnels_offer_type');
            $step_id            = $child_order->get_meta('_wpfunnels_offer_step_id');
            $paypal_order_id    = $parent_order->get_meta( '_wpfunnels_paypal_'.$offer_type.'_'.$step_id.'_order_id_'.$parent_order->get_id() );
            $child_order->update_meta_data( '_ppcp_paypal_order_id', $paypal_order_id );
            $child_order->update_meta_data( '_ppcp_paypal_intent', 'CAPTURE' );
            $child_order->save();
        }
    }


    /**
     * add subscription offer meta to order
     *
     * @param $subscription
     * @param $offer_product
     * @param $order
     */
    public function add_offer_subscription_meta( $subscription, $offer_product, $order ) {
        if ( 'ppcp-gateway' === $order->get_payment_method() ) {
            $subscription_id = $subscription->get_id();
            update_post_meta( $subscription_id, '_ppcp_paypal_order_id', $order->get_meta( '_ppcp_paypal_order_id', true ) );
            update_post_meta( $subscription_id, 'payment_token_id', $order->get_meta( 'payment_token_id', true ) );
        }
    }

    /**
     * Check cild order or not
     * @since 1.6.25
     */
    private function maybe_child_order(){
        $offer_settings = get_option('_wpfunnels_offer_settings');
        if( isset($offer_settings['offer_orders']) && $offer_settings['offer_orders'] ){
            return 'child-order' == $offer_settings['offer_orders'] ? true : false;
        }
        return false;
    }
}