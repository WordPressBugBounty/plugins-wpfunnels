<?php

namespace WPFunnels\Gateway;

use WpFluent\Exception;
use WPFunnels\Wpfnl_functions;

/**
 * Class Wpfnl_Pro_Gateway_Authorize_Net
 * @package WPFunnelsPro\Frontend\Gateways
 */
class Wpfnl_Gateway_Authorize_Net {

    /**
     * @var $key string
     */
    public $key = 'authorize_net_cim_credit_card';


    /**
     * @var $is_api_refund bool
     */
    public $refund_support;


    /**
     * @var $extra_data
     */
    public $extra_data;


    public $unset_opaque_value = false;


    public $offer_product;


    const MB_ENCODING = 'UTF-8';


    public function __construct() {

        $this->refund_support = true;

        /**
         * force tokenization and do not ask user for an option
         */
        add_filter( 'wc_payment_gateway_' . $this->key . '_tokenization_forced', array( $this, 'maybe_force_tokenization' ) );

        /**
         * create token for non logged in user and accept js is turned on.
         * force tokenization for guest when needed
         */
        add_filter( 'wc_payment_gateway_' . $this->key . '_process_payment', array( $this, 'create_token_process_payment' ), 10, 3 );

        /**
         * modify refund request data
         */
        add_filter( 'wc_authorize_net_cim_api_request_data', array( $this, 'modify_offer_refund_request_data' ), 10, 3 );

        add_action( 'wpfunnels/subscription_created', array( $this, 'add_offer_subscription_meta' ), 9999, 3 );
    }


    /**
     * force tokenization for upsell/downsell
     *
     * @param $force_tokenization
     * @return mixed
     */
    public function maybe_force_tokenization( $force_tokenization ) {
        if( isset( $_POST['post_data'] ) ) {
            $post_data = array();
            parse_str( $_POST['post_data'],$post_data );
            $checkout_id    = Wpfnl_functions::get_checkout_id_from_post_data( $post_data );
            $funnel_id      = Wpfnl_functions::get_funnel_id_from_post_data( $post_data );
            if ( $checkout_id && $funnel_id ) {
                if ( Wpfnl_functions::is_offer_exists_in_funnel($funnel_id) ) {
                    $force_tokenization = true;
                }
            }
        }
        return $force_tokenization;
    }


    /**
     * create token and process payment for non-logged in user
     *
     * @param $result
     * @param $order_id
     * @param $auth_net_obj
     * @return mixed
     */
    public function create_token_process_payment( $result, $order_id, $auth_net_obj ) {
        $create_token   = false;
        $checkout_id    = Wpfnl_functions::get_checkout_id_from_post_data();
        $funnel_id      = Wpfnl_functions::get_funnel_id_from_post_data();
        if ( $checkout_id && $funnel_id ) {
            if ( Wpfnl_functions::is_offer_exists_in_funnel($funnel_id) ) {
                $create_token = true;
            }
        }
        $order = $this->get_wc_gateway()->get_order( $order_id );

        if( $create_token && empty( $order->get_user_id() ) ) {
            try {
                // check if token is already exists for the order
                if( isset( $order->payment->token ) && $order->payment->token ) {
                    $this->get_wc_gateway()->add_transaction_data( $order );
                } else {
                    // create new token
                    $order_for_shipping = $order;
                    try {
                        $order = $this->get_wc_gateway()->get_payment_tokens_handler()->create_token($order);
                    } catch ( \Exception $e ) {
                        $re  = '/[0-9]+/';
                        $str = $e->getMessage();
                        preg_match_all( $re, $str, $matches, PREG_SET_ORDER, 0 );

                        if ( $matches && is_array( $matches ) && isset( $matches[0][0] ) && '00039' === $matches[0][0] ) {
                            $get_order_by_meta = new \WP_Query( array(
                                'post_type'   => 'shop_order',
                                'post_status' => 'any',
                                'meta_query'  => array(
                                    array(
                                        'key'     => '_wc_authorize_net_cim_credit_card_customer_id',
                                        'value'   => $matches[1][0],
                                        'compare' => '=',
                                    ),
                                ),
                                'fields'      => 'ids',
                                'order'       => 'ASC',
                            ) );

                            if ( is_array( $get_order_by_meta->posts ) && count( $get_order_by_meta->posts ) > 0 ) {
                                $this->extra_data['authorize_net_cim_order_id'] = $get_order_by_meta->posts[0];
                                $order_for_shipping = $this->get_wc_gateway()->get_order( $get_order_by_meta->posts[0] );
                                $this->extra_data['authorize_net_cim_customer_id'] = $matches[1][0];
                            }
                        }
                    }
                    $this->unset_opaque_value = true;
                    $order                    = $this->get_order( $order );
                    $this->get_wc_gateway()->add_transaction_data( $order );

                    /**
                     * We need to create shipping ID for the current user on Authorize.Net CIM API
                     * As ShippingAddressID is important for the cases when business owner has shipping-filters enabled in their merchant account.
                     *
                     */

                    try {

                        /**
                         * When we are in a case when there is a returning user & not logged in then in this case there are chances that shipping API request might fail.
                         * In this case we need to try and get shipping ID from the order meta and set this up for further.
                         */
                        $response = $this->get_wc_gateway()->get_api()->create_shipping_address( $order );

                    } catch ( \Exception $e) {
                        $response = intval( $order_for_shipping->get_meta( '_authorize_cim_shipping_address_id', true ) );
                    }
                    $shipping_address_id                 = is_numeric( $response ) ? $response : $response->get_shipping_address_id();
                    $order->payment->shipping_address_id = $shipping_address_id;
                    WC()->session->set( 'authorize_net_cim_shipping_id', $order->payment->shipping_address_id );
                    $this->get_wc_gateway()->add_transaction_data( $order );
                    $this->do_main_transaction( $order );
                }
                $result = array(
                    'result'   => 'success',
                    'redirect' => $this->get_wc_gateway()->get_return_url( $order ),
                );
            }
            catch ( \Exception $e ) {
                $result = array(
                    'result'  => 'failure',
                    'message' => $e->getMessage(),
                );
            }
        }
        return $result;
    }


    /**
     * process the offer payment
     *
     * @param $order
     * @param $offer_product
     * @return array
     */
    public function process_payment( $order, $offer_product ) {
        $_response = array(
            'is_success' => false,
            'message' => ''
        );

        $this->offer_product = $offer_product;
        try {
            $gateway     = $this->get_wc_gateway();
            $api         = $gateway->get_api();
            $environment = $gateway->get_environment();
            $url         = ( 'production' === $environment ) ? $api::PRODUCTION_ENDPOINT : $api::TEST_ENDPOINT;

            /**
             * modify order object and populate it as per the scenario (offer transaction)
             */
            add_filter( 'wc_payment_gateway_' . $this->key . '_get_order', array( $this, 'get_modified_order' ), 999 );
            $new_order  = $gateway->get_order( $order );
            $request    = $this->create_transaction_request( 'capture', $new_order );
            $response   = wp_safe_remote_request( $url, $this->get_request_attributes( $request ) );
            $body       = wp_remote_retrieve_body( $response );
            $body       = preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $body );
            $result     = json_decode( $body, true );

            if ( is_wp_error( $response ) ) {
                $result['is_success'] = false;
            }
            else {
                if ( isset( $result['messages'] ) && isset( $result['messages']['message'][0]['code'] ) && 'I00001' === $result['messages']['message'][0]['code'] ) {
                    $_response['is_success']   = true;
                    $transaction_id         = $this->get_transaction_id( $result['directResponse'] );
                    $response_data = array(
                        'id' => $transaction_id,
                    );
                    $this->store_offer_transaction( $order, $response_data, $offer_product );
                } else {
                    $order_note             = sprintf( __( 'Authorize.net CIM Transaction Failed (%s)', 'wpfnl-pro' ), $result['messages']['message'][0]['text'] );
                    $_response['is_success'] = false;
                    $_response['message']    = $order_note;
                    $order->add_order_note( $order_note );
                }
            }
        } catch (\Exception $e) {
            $order_note             = sprintf( __( 'Authorize.net CIM Transaction Failed (%s)', 'wpfnl-pro' ), $e->getMessage() );
            $_response['is_success'] = false;
            $_response['message']    = $order_note;
            $order->add_order_note( $order_note );
        }
        return $_response;
    }


    /**
     * create transaction request for offer
     * product
     *
     * @param $type
     * @param $new_order
     * @return array[]
     */
    private function create_transaction_request( $type, $new_order ) {
        $order              = $new_order;
        $transaction_type   = ( 'auth_only' === $type ) ? 'profileTransAuthOnly' : 'profileTransAuthCapture';
        $offer_product      = $this->offer_product;

        /**
         * We need to create shipping ID for the current user on Authorize.Net CIM API
         * As ShippingAddressID is important for the cases when business owner has shipping-filters enabled in their merchant account.
         */
        $maybe_get_shipping_id_from_session = WC()->session->get( 'authorize_net_cim_shipping_id' );
        if ( isset( $order->payment ) && isset( $order->payment->shipping_address_id ) && ! empty( $order->payment->shipping_address_id ) ) {
            $shipping_address_id = $order->payment->shipping_address_id;
        } elseif ( ! empty( $maybe_get_shipping_id_from_session ) ) {
            $shipping_address_id = $maybe_get_shipping_id_from_session;
        } else {
            $response = $this->get_wc_gateway()->get_api()->create_shipping_address( $order );
            $shipping_address_id = is_numeric( $response ) ? $response : $response->get_shipping_address_id();
        }
        return array(
            'createCustomerProfileTransactionRequest' => array(
                'merchantAuthentication' => array(
                    'name'           => wc_clean( $this->get_wc_gateway()->get_api_login_id() ),
                    'transactionKey' => wc_clean( $this->get_wc_gateway()->get_api_transaction_key() ),
                ),
                'refId'                  => $order->get_id() . '_' . $offer_product['step_id'],
                'transaction'            => array(
                    $transaction_type => array(
                        'amount'                    => $offer_product['total'],
                        'tax'                       => array(),
                        'shipping'                  => array(),
                        'lineItems'                 => $this->get_line_items(),
                        'customerProfileId'         => $this->get_customer_id( $order ),
                        'customerPaymentProfileId'  => $this->get_token( $order ),
                        'customerShippingAddressId' => $shipping_address_id,
                        'order'                     => array(
                            'invoiceNumber'       => $order->get_id() . '_' . $offer_product['step_id'],
                            'description'         => $this->string_truncate( $offer_product['desc'], 255 ),
                            'purchaseOrderNumber' => $this->string_truncate( preg_replace( '/\W/', '', $order->payment->po_number ), 25 ),
                        ),
                    ),
                ),
            )
        );
    }


    /**
     * do main transaction
     *
     * @param \WC_Order $order
     */
    private function do_main_transaction( \WC_Order $order ) {

        try{
            $order->description = sprintf( __( '%1$s - Release Payment for Order %2$s', 'wpfnl-pro' ), esc_html( $this->get_current_site_name() ), $order->get_order_number() );

            // token is required.
            if ( ! $order->payment->token ) {
                throw new Exception( __( 'Payment token missing/invalid.', 'wpfnl-pro' ) );
            }

            // perform the main transaction
            if ( $this->get_wc_gateway()->is_credit_card_gateway() ) {
                if ( $this->get_wc_gateway()->perform_credit_card_charge( $order ) ) {
                    $response = $this->get_wc_gateway()->get_api()->credit_card_charge( $order );
                } else {
                    $response = $this->get_wc_gateway()->get_api()->credit_card_authorization( $order );
                }
            } elseif ( $this->get_wc_gateway()->is_echeck_gateway() ) {
                $response = $this->get_wc_gateway()->get_api()->check_debit( $order );
            }

            // success! update order record
            if ( $response->transaction_approved() ) {
                $last_four = substr( $order->payment->account_number, - 4 );
                // order note based on gateway type
                if ( $this->get_wc_gateway()->is_credit_card_gateway() ) {
                    $message = sprintf( __( '%1$s %2$s Release Payment Approved: %3$s ending in %4$s (expires %5$s)', 'wpfnl-pro' ), $this->get_wc_gateway()->get_method_title(), $this->get_wc_gateway()->perform_credit_card_authorization( $order ) ? 'Authorization' : 'Charge', ! isset( $order->payment->card_type ) ? $order->payment->card_type : 'card', $last_four, (  isset( $order->payment->exp_month ) &&  isset( $order->payment->exp_year ) ? $order->payment->exp_month . '/' . substr( $order->payment->exp_year, - 2 ) : 'n/a' ) );
                }
                // adds the transaction id (if any) to the order note
                if ( $response->get_transaction_id() ) {
                    $message .= ' ' . sprintf( __( '(Transaction ID %s)', 'wpfnl-pro' ), $response->get_transaction_id() );
                }
                $order->add_order_note( $message );
            }


            if ( $response->transaction_approved() || $response->transaction_held() ) {
                // add the standard transaction data.
                $this->get_wc_gateway()->add_transaction_data( $order, $response );

                // allow the concrete class to add any gateway-specific transaction data to the order.
                $this->get_wc_gateway()->add_payment_gateway_transaction_data( $order, $response );

                // if the transaction was held (ie fraud validation failure) mark it as such.
                if ( $response->transaction_held() || ( $this->get_wc_gateway()->supports( 'authorization' ) && $this->get_wc_gateway()->perform_credit_card_authorization( $order ) ) ) {
                    $this->get_wc_gateway()->mark_order_as_held( $order, $this->get_wc_gateway()->supports( 'authorization' ) && $this->get_wc_gateway()->perform_credit_card_authorization( $order ) ? __( 'Authorization only transaction', 'wpfnl-pro' ) : $response->get_status_message(), $response );
                    wc_reduce_stock_levels( $order->get_id() );
                } else {
                    // otherwise complete the order.
                    $order->payment_complete();
                }
            } else {
                // failure.
                throw new Exception( sprintf( '%s: %s', $response->get_status_code(), $response->get_status_message() ) );
            }
        } catch ( \Exception $e ) {
            if ( isset( $response ) ) {
                $this->get_wc_gateway()->mark_order_as_failed( $order, sprintf( __( 'Release Payment Failed: %s', 'wpfnl-pro' ), $e->getMessage() ), $response );
            } else {
                $this->get_wc_gateway()->mark_order_as_failed( $order, sprintf( __( 'Release Payment Failed: %s', 'wpfnl-pro' ), $e->getMessage() ) );
            }
        }
    }


    /**
     * get modified order
     *
     * @param $order
     * @return mixed|\WC_Order
     */
    public function get_modified_order( $order ) {
        if ( $order instanceof \WC_Order && $this->key === $order->get_payment_method() ) {

            if ( $this->has_token( $order ) && ! is_checkout_pay_page() ) {

                $order_id = $order->get_id();

                // retrieve the payment token.
                $order->payment->token = $this->get_wc_gateway()->get_order_meta( $order_id, 'payment_token' );

                $token_from_gateway = $this->get_token( $order );
                if ( empty( $order->payment->token ) && ! empty( $token_from_gateway ) ) {
                    $order->payment->token = $token_from_gateway;
                }

                // retrieve the optional customer id.
                $order->customer_id = $this->get_wc_gateway()->get_order_meta( $order_id, 'customer_id' );

                // set token data on order.
                if ( $this->get_wc_gateway()->get_payment_tokens_handler()->user_has_token( $order->get_user_id(), $order->payment->token ) ) {

                    // an existing registered user with a saved payment token.
                    $token = $this->get_wc_gateway()->get_payment_tokens_handler()->get_token( $order->get_user_id(), $order->payment->token );

                    // account last four.
                    $order->payment->account_number = $token->get_last_four();

                    if ( $this->get_wc_gateway()->is_credit_card_gateway() ) {

                        // card type.
                        $order->payment->card_type = $token->get_card_type();

                        // exp month/year.
                        $order->payment->exp_month = $token->get_exp_month();
                        $order->payment->exp_year  = $token->get_exp_year();

                    } elseif ( $this->get_wc_gateway()->is_echeck_gateway() ) {

                        // account type (checking/savings).
                        $order->payment->account_type = $token->get_account_type();
                    }
                } else {

                    // a guest user means that token data must be set from the original order.

                    // account number.
                    $order->payment->account_number = $this->get_wc_gateway()->get_order_meta( $order_id, 'account_four' );

                    if ( $this->get_wc_gateway()->is_credit_card_gateway() ) {

                        // card type.
                        $order->payment->card_type = $this->get_wc_gateway()->get_order_meta( $order_id, 'card_type' );

                        // expiry date.
                        $expiry_date = $this->get_wc_gateway()->get_order_meta( $order_id, 'card_expiry_date' );

                        if ( ! empty( $expiry_date ) ) {
                            list( $exp_year, $exp_month ) = explode( '-', $expiry_date );
                            $order->payment->exp_month    = $exp_month;
                            $order->payment->exp_year     = $exp_year;
                        }
                    } elseif ( $this->get_wc_gateway()->is_echeck_gateway() ) {

                        // account type.
                        $order->payment->account_type = $this->get_wc_gateway()->get_order_meta( $order_id, 'account_type' );
                    }
                }
            }

            $response = intval( $order->get_meta( '_authorize_cim_shipping_address_id', true ) );
            if ( ! empty( $response ) ) {
                $order->payment->shipping_address_id = $response;
            }
            if ( true === $this->unset_opaque_value && isset( $order->payment->opaque_value ) ) {
                unset( $order->payment->opaque_value );
            }
        }
        return $order;
    }


    /**
     * modify offer refund request data
     *
     * @param $request_data
     * @param $order
     * @param $gateway
     * @return mixed
     */
    public function modify_offer_refund_request_data( $request_data, $order, $gateway ) {
        if ( isset( $_POST['wpfnl_refund'] ) ) {
            $refund_data    = $_POST;
            $order_id       = $order->get_id();
            $step_id        = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
            if ( isset( $request_data['createCustomerProfileTransactionRequest'] ) && isset( $request_data['createCustomerProfileTransactionRequest']['refId'] ) ) {
                $request_data['createCustomerProfileTransactionRequest']['refId'] = $order_id . '_' . $step_id;
            }

            if ( isset( $request_data['createCustomerProfileTransactionRequest'] ) && isset( $request_data['createCustomerProfileTransactionRequest']['transaction'] ) && isset( $request_data['createCustomerProfileTransactionRequest']['transaction']['profileTransRefund'] ) && isset( $request_data['createCustomerProfileTransactionRequest']['transaction']['profileTransRefund']['order'] ) && isset( $request_data['createCustomerProfileTransactionRequest']['transaction']['profileTransRefund']['order']['invoiceNumber'] ) ) {
                $request_data['createCustomerProfileTransactionRequest']['transaction']['profileTransRefund']['order']['invoiceNumber'] = $order_id . '_' . $step_id;
            }
        }
        return $request_data;
    }


    /**
     * @param $order
     * @param $data
     * @return mixed
     */
    public function process_refund_offer( $order, $data ) {
        $transaction_id             = $data['transaction_id'];
        $refund_amount              = $data['refund_amount'];
        $refund_reason              = $data['refund_reason'];
        $gateway                    = $this->get_wc_gateway();
        $api                        = $gateway->get_api();
        $order->refund              = new \stdClass();
        $order->refund->trans_id    = $transaction_id;
        $order->refund->amount      = number_format( $refund_amount, 2, '.', '' );
        $order->refund->reason      = $refund_reason;
        $order->refund->customer_profile_id         = $gateway->get_order_meta( $order, 'customer_id' );
        $order->refund->customer_payment_profile_id = $gateway->get_order_meta( $order, 'payment_token' );

        $response = $api->refund( $order );

        $response_id = $response->get_transaction_id();

        if ( ! $response_id ) {
            $response    = $api->void( $order );
            $response_id = $response->get_transaction_id();
        }

        return $response_id;
    }

    /**
     * update the meta data for WooCommerce subscription
     *
     * @param $subscription
     * @param $offer_product
     * @param $order
     */
    public function add_offer_subscription_meta( $subscription, $offer_product, $order ) {
        if ( 'authorize_net_cim_credit_card' === $order->get_payment_method() ) {
            $subscription_id = $subscription->get_id();
            update_post_meta( $subscription_id, '_wc_authorize_net_cim_credit_card_customer_id', $order->get_meta( '_wc_authorize_net_cim_credit_card_customer_id', true ) );
            update_post_meta( $subscription_id, '_wc_authorize_net_cim_credit_card_payment_token', $order->get_meta( '_wc_authorize_net_cim_credit_card_payment_token', true ) );
        }
    }



    /**
     * gert order
     *
     * @param $order
     * @return mixed
     */
    public function get_order( $order ) {

        if ( $order instanceof \WC_Order && $this->key === $order->get_payment_method() ) {

            if ( $this->has_token( $order ) && ! is_checkout_pay_page() ) {

                $order_id = $order->get_id();

                // retrieve the payment token.
                $order->payment->token = $this->get_wc_gateway()->get_order_meta( $order_id, 'payment_token' );
                $token_from_gateway    = $this->get_token( $order );
                if ( empty( $order->payment->token ) && ! empty( $token_from_gateway ) ) {
                    $order->payment->token = $token_from_gateway;
                }
                // retrieve the optional customer id.
                $order->customer_id = $this->get_wc_gateway()->get_order_meta( $order_id, 'customer_id' );

                /* May be we need customer id from session */
                $customer_id_from_session = isset( $this->extra_data['authorize_net_cim_customer_id'] ) ? $this->extra_data['authorize_net_cim_customer_id'] : '';

                if ( empty( $order->customer_id ) && ! empty( $customer_id_from_session ) ) {
                    $order->customer_id = $customer_id_from_session;
                }

                // set token data on order.
                if ( $this->get_wc_gateway()->get_payment_tokens_handler()->user_has_token( $order->get_user_id(), $order->payment->token ) ) {

                    // an existing registered user with a saved payment token.
                    $token = $this->get_wc_gateway()->get_payment_tokens_handler()->get_token( $order->get_user_id(), $order->payment->token );

                    // account last four.
                    $order->payment->account_number = $token->get_last_four();

                    if ( $this->get_wc_gateway()->is_credit_card_gateway() ) {

                        // card type.
                        $order->payment->card_type = $token->get_card_type();

                        // exp month/year.
                        $order->payment->exp_month = $token->get_exp_month();
                        $order->payment->exp_year  = $token->get_exp_year();

                    } elseif ( $this->get_wc_gateway()->is_echeck_gateway() ) {

                        // account type (checking/savings).
                        $order->payment->account_type = $token->get_account_type();
                    }
                } else {

                    // a guest user means that token data must be set from the original order.

                    // account number.
                    $order->payment->account_number = $this->get_wc_gateway()->get_order_meta( $order_id, 'account_four' );

                    if ( $this->get_wc_gateway()->is_credit_card_gateway() ) {

                        // card type.
                        $order->payment->card_type = $this->get_wc_gateway()->get_order_meta( $order_id, 'card_type' );

                        // expiry date.
                        $expiry_date = $this->get_wc_gateway()->get_order_meta( $order_id, 'card_expiry_date' );

                        if ( ! empty( $expiry_date ) ) {
                            list( $exp_year, $exp_month ) = explode( '-', $expiry_date );
                            $order->payment->exp_month    = $exp_month;
                            $order->payment->exp_year     = $exp_year;
                        }
                    } elseif ( $this->get_wc_gateway()->is_echeck_gateway() ) {

                        // account type.
                        $order->payment->account_type = $this->get_wc_gateway()->get_order_meta( $order_id, 'account_type' );
                    }
                }
            }

            $response = intval( $order->get_meta( '_authorize_cim_shipping_address_id' ) );
            if ( ! empty( $response ) ) {
                $order->payment->shipping_address_id = $response;
            }

            if ( true === $this->unset_opaque_value && isset( $order->payment->opaque_value ) ) {
                unset( $order->payment->opaque_value );
            }
        }

        return $order;
    }


    /**
     * check if the order has any payment
     * token
     *
     * @param $order
     * @return bool
     */
    private function has_token( $order ) {
        $order_id   = $order->get_id();
        $token      = $order->get_meta('_wc_' . $this->key . '_payment_token' );
        if ( ! empty( $token ) ) {
            return true;
        }

        /**
         * if token is not present in order fallback
         */
        if ( isset( $this->extra_data['authorize_net_cim_order_id'] ) ) {
            $fallback_order_id  = $this->extra_data['authorize_net_cim_order_id'];
            $token              = $order->get_meta('_wc_' . $this->key . '_payment_token' );
            if ( ! empty( $token ) ) {
                update_post_meta( $order_id, '_wc_' . $this->key . '_payment_token', $token );
                return true;
            }
        }
        return false;
    }


    /**
     * get token from order
     *
     * @param $order
     * @return array|false|mixed|string
     */
    private function get_token( $order ) {
        if( false === is_a( $order, 'WC_Order' ) ){
            return false;
        }

        $token = $order->get_meta('_wc_' . $this->key . '_payment_token' );
        if ( ! empty( $token ) ) {
            return $token;
        }
        return false;
    }

    /**
     * get next step id
     *
     * @param $funnel_id
     * @param $step_id
     * @return false|int
     */
    public function get_next_step( $funnel_id, $step_id ) {
        $next_step_id = false;
        if( $step_id ) {
            $steps = Wpfnl_functions::get_steps($funnel_id);
            if ( is_array( $steps ) ) {
                foreach ( $steps as $index => $step ) {
                    if ( intval( $step['id'] ) === $step_id ) {
                        $next_step_index = $index + 1;
                        if ( isset( $steps[ $next_step_index ] ) ) {
                            $next_step_id = intval( $steps[ $next_step_index ]['id'] );
                        }
                        break;
                    }
                }
            }
        }
        return $next_step_id;
    }


    /**
     * get wc payment gateway
     *
     * @return mixed
     */
    private function get_wc_gateway() {
        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();
        return $gateways[ $this->key ];
    }


    /**
     * get line items of the order
     *
     * @return array
     */
    private function get_line_items() {
        $line_items     = array();
        $offer_product  = $this->offer_product;
        if ( isset( $offer_product['id'] ) && $offer_product['id'] > 0 ) {

            $line_items[] = array(
                'itemId'      => $this->string_truncate( $offer_product['id'], 31 ),
                'name'        => $this->string_truncate( $offer_product['name'], 31 ),
                'description' => $this->string_truncate( $offer_product['desc'], 255 ),
                'quantity'    => $offer_product['qty'],
                'unitPrice'   => number_format( (float) $offer_product['total'], 2, '.', '' ),
            );
        }

        return $line_items;
    }


    /**
     * Truncates a given string. The total length of return string will not exceed the given length.
     * The last characters will be replaced with the $omission string
     * for a total length not exceeding $length
     *
     * @param $string
     * @param $length
     * @param string $omission
     * @return mixed|string
     */
    private function string_truncate( $string, $length, $omission = '...' ) {
        if ( extension_loaded( 'mbstring' ) ) {
            if ( mb_strlen( $string, self::MB_ENCODING ) <= $length ) {
                return $string;
            }
            $length -= mb_strlen( $omission, self::MB_ENCODING );
            return mb_substr( $string, 0, $length, self::MB_ENCODING ) . $omission;
        } else {
            $string = $this->str_to_ascii( $string );
            if ( strlen( $string ) <= $length ) {
                return $string;
            }
            $length -= strlen( $omission );
            return substr( $string, 0, $length ) . $omission;
        }
    }


    /**
     * returns a string with all non ascii characters
     * removed
     *
     * @param $string
     * @return mixed
     */
    private function str_to_ascii( $string ) {
        // strip ASCII chars 32 and under
        $string = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );

        // strip ASCII chars 127 and higher
        return filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );
    }


    /**
     * get customer id from order
     *
     * @param $order
     * @return array|false|mixed|string
     */
    private function get_customer_id( $order ) {
        $customer_id = $order->get_meta('_wc_' . $this->key . '_customer_id', );
        if ( ! empty( $customer_id ) ) {
            return $customer_id;
        }
        return '';
    }


    /**
     * get request attributes
     *
     * @param $request
     * @return array
     */
    public function get_request_attributes( $request ) {
        return array(
            'method'      => 'POST',
            'timeout'     => MINUTE_IN_SECONDS,
            'redirection' => 0,
            'httpversion' => '1.0',
            'sslverify'   => true,
            'blocking'    => true,
            'headers'     => array(
                'content-type' => 'application/json',
                'accept'       => 'application/json',
            ),
            'body'        => wp_json_encode( $request ),
            'cookies'     => array(),
        );
    }


    /**
     * get transaction id
     *
     * @param $response
     * @return mixed|string
     */
    private function get_transaction_id( $response ) {

        // parse response
        $response = explode( ',', $response );

        if ( empty( $response ) ) {
            return '';
        }

        // offset array by 1 to match Authorize.Net's order, mainly for readability
        array_unshift( $response, null );

        $new_direct_response = array();

        // direct response fields are URL encoded, but we currently do not use any fields
        // (e.g. billing/shipping details) that would be affected by that
        $response_fields = array(
            'response_code'        => 1,
            'response_subcode'     => 2,
            'response_reason_code' => 3,
            'response_reason_text' => 4,
            'authorization_code'   => 5,
            'avs_response'         => 6,
            'transaction_id'       => 7,
            'amount'               => 10,
            'account_type'         => 11, // CC or ECHECK
            'transaction_type'     => 12, // AUTH_ONLY or AUTH_CAPTUREVOID probably
            'csc_response'         => 39,
            'cavv_response'        => 40,
            'account_last_four'    => 51,
            'card_type'            => 52,
        );

        foreach ( $response_fields as $field => $order ) {
            $new_direct_response[ $field ] = ( isset( $response[ $order ] ) ) ? $response[ $order ] : '';
        }
        return isset( $new_direct_response['transaction_id'] ) && '' !== $new_direct_response['transaction_id'] ? $new_direct_response['transaction_id'] : '';
    }


    /**
     * store offer transaction data
     *
     * @param $order
     * @param $response
     * @param $product
     */
    public function store_offer_transaction( $order, $response, $product ) {
        $order->update_meta_data( '_wpfunnels_offer_txn_resp_' . $product['step_id'], $response['id'] );
        $order->save();
    }



    /**
     * Get current site name.
     *
     * @return string
     */
    public function get_current_site_name() {
        return ( is_multisite() ) ? get_blog_details()->blogname : get_bloginfo( 'name' );
    }
}