<?php
namespace WPFunnels\Gateway;
/**
 * Wpfnl_Pro_Woocommerce_Payments class.
 *
 * @extends WFOCU_Gateway
 */

use WPFunnels\Wpfnl_functions;
use WCPay\Payment_Information;
use WCPay\Constants\Payment_Type;
use WCPay\Constants\Payment_Initiated_By;

class Wpfnl_Pro_Woocommerce_Payments {

  /**
   * Class instance
   *
   * @var instance
   */
  private static $instance;

  /**
   * Payment key
   *
   * @var key
   */
  public $key = 'woocommerce_payments';

  /**
   * Refund supported variable
   *
   * @var is_api_refund
   */
  public $is_api_refund = true;


  /**
   * Refund supported variable
   *
   * @var gateway_obj
   */
  public $gateway_obj = array();


  /**
   *  Create instance
   * 
   * 
   * @since 1.9.0
   * 
   * @return Obj
   */
  public static function get_instance() {
    if ( ! isset( self::$instance ) ) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Constructor
   */
  public function __construct() {

    add_filter( 'wc_payments_display_save_payment_method_checkbox', array( $this, 'should_tokenize_stripe' ), 11, 1 );
    // add_action( 'wpfunnels/offer_funnel_started', array( $this, 'save_token_for_3ds' ), 10, 1 );
    add_action( 'wp_ajax_wpfunnels_woop_create_payment_intent', array( $this, 'create_payment_intent' ) );
    add_action( 'wp_ajax_nopriv_wpfunnels_woop_create_payment_intent', array( $this, 'create_payment_intent' ) );
    add_action( 'wpfunnels/child_order_created_' . $this->key, array( $this, 'add_required_meta_to_child_order' ), 10, 3 );
    add_action( 'wpfunnels/subscription_created', array( $this, 'add_subscription_payment_meta' ), 10, 3 );
    $this->prepare_objects();
  }

  /**
   * Prepare the payment gateway object to use it.
   *
   * @since 1.9.0
   * 
   * @return void
   */
  public function prepare_objects() {

    $api_client          = '';
    $api_account_service = '';
    $database_cache      = '';
    $customer_services   = '';
    $token_service       = '';

    if ( class_exists( 'WC_Payments' ) && class_exists( 'WC_Payments_Customer_Service' ) && class_exists( 'WC_Payments_Token_Service' ) ) {
      $api_client          = \WC_Payments::get_payments_api_client();
      $api_account_service = \WC_Payments::get_account_service();
      $database_cache      = class_exists( 'WCPay\Database_Cache' ) ? \WC_Payments::get_database_cache() : null;
     
      /**
       * @modified_date 07-11-2023
       */
      if ( version_compare( '6.7.0', WCPAY_VERSION_NUMBER, '<=' ) && version_compare( '7.6.0', WCPAY_VERSION_NUMBER, '>=' ) ) {
        $order_service = new \WC_Payments_Order_Service( $api_client );
        $session_service = new \WC_Payments_Session_Service( $api_client );
        $customer_services = new \WC_Payments_Customer_Service( $api_client, $api_account_service, $database_cache, $session_service );
      } elseif ( version_compare( '7.7.0', WCPAY_VERSION_NUMBER, '<=' ) ) {
          $order_service = new \WC_Payments_Order_Service( $api_client );
          $session_service = new \WC_Payments_Session_Service( $api_client );
          $customer_services = new \WC_Payments_Customer_Service( $api_client, $api_account_service, $database_cache, $session_service, $order_service );
      } else {
          $customer_services = new \WC_Payments_Customer_Service( $api_client, $api_account_service, $database_cache );
      }
      
      $token_service       = new \WC_Payments_Token_Service( $api_client, $customer_services );
    }

    $this->gateway_obj = array(
      'api_client'          => $api_client ? $api_client : '',
      'api_account_service' => $api_account_service ? $api_account_service : '',
      'database_cache'      => $database_cache ? $database_cache : '',
      'customer_services'   => $customer_services ? $customer_services : '',
      'token_service'       => $token_service ? $token_service : '',
    );
  }


  /**
   * Create intent for the offer order.
   * Processes a payment intent by verifying the security nonce and handling the payment for an order.
   * 
   * @since 1.9.0
   * @return void
   */
  public function create_payment_intent() {
    $security = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_STRING );
        if ( ! wp_verify_nonce( $security, 'wpfunnels_woop_create_payment_intent' ) ) {
            return;
        }

    $variation_id = '';
    $input_qty    = '';

    if ( isset( $_POST['variation_id'] ) ) {
      $variation_id = intval( $_POST['variation_id'] );
    }

    if ( isset( $_POST['input_qty'] ) && ! empty( $_POST['input_qty'] ) ) {
      $input_qty = intval( $_POST['input_qty'] );
    }

    $step_id       = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $order_id      = isset( $_POST['order_id'] ) ? intval($_POST['order_id']) : 0;
        $offer_type    = isset( $_POST['offer_type'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_type'] ) ) : '';
        $offer_action  = isset( $_POST['offer_action'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_action'] ) ) : '';
        $product_id    = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : '';
        $quantity      = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : '';
        $order         = wc_get_order( $order_id );
        $offer_product = Wpfnl_functions::get_offer_product_data( $step_id, $product_id, 0, $order_id );
    global $woocommerce;

    if ( isset( $offer_product['price'] ) && ( floatval( 0 ) === floatval( $offer_product['price'] )
        || '' === trim( $offer_product['price'] ) ) ) {
      wp_send_json(
        array(
          'result'  => 'fail',
          'message' => 'Zero value product',
        )
      );
    } else {

      	$gateways = $woocommerce->payment_gateways->payment_gateways();
    	$gateway  = $gateways['woocommerce_payments'];
    
      if ( $gateway ) {
        try {
          $token = $this->get_token_from_order( $order );

          $payment_information = new Payment_Information( '', $order, Payment_Type::SINGLE(), $token, Payment_Initiated_By::CUSTOMER() );

          $response = $this->process_payment_against_intent( $order, $offer_product, $payment_information );


        } catch ( Exception $e ) {

          wp_send_json(
            array(
              'result'  => 'fail',
              'message' => 'Payment Failed',
            )
          );
        }

        // Process the response for the order.
        if ( ! empty( $response ) ) {

          $intent_id     = $response->get_id() ? $response->get_id() : 0;
          $status        = $response->get_status();
          $charge        = $response->get_charge();
          $charge_id     = ! empty( $charge ) ? $charge->get_id() : null;
          $client_secret = $response->get_client_secret();

          $payload = array(
            'step_id'   => $step_id,
            'order_id'  => $order_id,
            'intent_id' => $intent_id,
            'charge_id' => $charge_id,
          );

          $this->save_order_meta( $order, $payment_information, $payload, $gateway );

          wp_send_json(
            array(
              'result'        => 'success',
              'status'        => $status,
              'client_secret' => $client_secret,
              'client_intend' => $intent_id,
            )
          );

        }

      }

      wp_send_json(
        array(
          'result'  => 'fail',
          'message' => __( 'No payment. No gateway found', 'wpfnl-pro' ),
        )
      );
    }
  }


  /**
   * Save order meta 
   *
   * @param object $order Order object.
   * @param object $payment_information Object of payment information with all data.
   * @param array  $payload Any extra data that may require.
   * @param object $gateway Gateway object.
   * 
   * @since 1.9.0
   * @return void
   */
  public function save_order_meta( $order, $payment_information, $payload, $gateway = null ) {

    $payment_method = $payment_information->get_payment_method();
    $order_id       = $order->get_id();
    $step_id        = $payload['step_id'];
  
    $order->update_meta_data( '_wpfnl_woop_offer_stripe_intent_id_' . $step_id, $payload['intent_id'] );
    $order->update_meta_data( '_wpfnl_woop_offer_charge_id_' . $step_id . '_' . $order_id, $payload['charge_id'] );
    $order->update_meta_data( '_wpfnl_woop_offer_payment_method_id', $payment_method );
    
	// Determine test mode using standard WooCommerce gateway methods
    $is_test_mode = false;
    if ( $gateway ) {
		// Try various methods to check test mode
		if ( method_exists( $gateway, 'is_in_test_mode' ) ) {
			$is_test_mode = $gateway->is_in_test_mode();
		} elseif ( isset( $gateway->testmode ) ) {
			$is_test_mode = wc_string_to_bool( $gateway->testmode );
		} elseif ( method_exists( $gateway, 'get_option' ) ) {
			$is_test_mode = wc_string_to_bool( $gateway->get_option( 'testmode', 'no' ) );
		}
    }
    
    $order->update_meta_data( '_wpfnl_woop_offer_wcpay_mode', $is_test_mode ? 'test' : 'prod' );
    $order->save();
  }

  /**
   * Get the token from the main order.
   *
   * @param object $order Object of current order.
   * @param bool   $has_token Separator used to log the logs.
   *
   * @since 1.9.0
   * @return string $token  order token.  
   */
  public function get_token_from_order( $order, $has_token = false ) {

    $order_tokens = $order->get_payment_tokens();
    $token_id     = end( $order_tokens );
    $token        = $token_id ? \WC_Payment_Tokens::get( $token_id ) : null;
    return $token;

  }

  /** 
     * If required then tokenize to save source of payment   
     *  
     * @param bool $save_source force save source.
   * 
     * @since 1.9.0
   * @return bool
    */
   public function should_tokenize_stripe( $save_source ){
    global $post;
    if( !$post ){
      return $save_source;
    }
    if ( wp_doing_ajax() || isset( $_GET['wc-ajax'] ) ) {
      $checkout_id = Wpfnl_functions::get_checkout_id_from_post($_POST);
    } else {
      $checkout_id = $post->ID;
    }

    if( !$checkout_id  ){
      return $save_source;
    }
    $funnel_id   = Wpfnl_functions::get_funnel_id_from_step( $checkout_id );
    if( !$funnel_id  ){
      return $save_source;
    }
    $steps    = get_post_meta( $funnel_id, '_steps_order', true );
    $offer_steps = ['upsell', 'downsell'];
    if( is_array($steps) ){
      $step_types = wp_list_pluck($steps, 'step_type');
      $intersect = array_intersect($offer_steps, $step_types);
      if( !empty($intersect) ) {
        $save_source = false;
      }
    }
      return $save_source;
    }

  /**
   * Save token for 3ds.
   *
   * @param int $order_id order ID.
   * 
   * @since 1.9.0
   * @return void
   */
  public function save_token_for_3ds( $order ) {
    if ( $order && $this->key === $order->get_payment_method() ) {
      try {
        $payment_method_id = $order->get_meta( '_payment_method_id' );

        $token = $this->gateway_obj['token_service']->add_payment_method_to_user( $payment_method_id, wp_get_current_user() );

        $this->get_wc_gateway()->add_token_to_order( $order, $token );

        $order->update_meta_data( '_wpfnl_' . $this->key . '_source_id', $payment_method_id );
        $order->update_meta_data( '_wpfnl_' . $this->key . '_token', $token );
        $order->read_meta_data( true );
      } catch ( Exception $e ) {
        error_log(print_r($e->getMessage(),1));
      }
    }

  }


  /**
   * Check if token is present.
   *
   * @param object $order order data.
   */
  public function maybe_token( $order ) {

    $order_id = $order->get_id();

    $token = $this->get_token_from_order( $order, true );

    if ( empty( $token ) ) {
      $source_id = $order->get_meta( '_wpfnl_' . $this->key . '_source_id' );
      $token     = $order->get_meta( '_wpfnl_' . $this->key . '_token' );
    }

    if ( ! empty( $token ) ) {
      return true;
    }

    return false;
  }

  /**
   * Get Woocommerce payment geteways.
   *
   * @return object
   */
  public function get_wc_gateway() {

    global $woocommerce;

    $gateways = $woocommerce->payment_gateways->payment_gateways();

    return $gateways[ $this->key ];
  }

  /**
   * Process payment.
   *
   * @param object $order order data.
   * @param array  $product product data.
   * 
   * 
   * @since 1.9.0
   * 
   * @return array
   */
  public function process_payment( $order, $product ) {

    $result = array(
            'is_success' => false,
            'message' => ''
        );
    if ( ! $this->maybe_token( $order ) ) {
      return $result;
    }
    
    if ( isset( $_POST['woop_intent_id'] ) ) {
      
      $stored_intent_id = $order->get_meta( '_wpfnl_woop_offer_stripe_intent_id_' . $product['step_id'] );
      $payment_method   = $order->get_meta( '_wpfnl_' . $this->key . '_source_id' );
      $intent_id        = sanitize_text_field( wp_unslash( $_POST['woop_intent_id'] ) );
      $confirm_intent = ( $intent_id === $stored_intent_id ) ? true : false;
      
      if ( $confirm_intent ) {

        $result['is_success'] = true;
        $data          = array(
          'id'             => $intent_id,
          'payment_method' => $payment_method,
          'customer'       => $order->get_meta( '_stripe_customer_id', true ),
        );

        $this->save_offer_transaction_meta( $order, $data, $product );
      } 
    }
    return $result;
  }

  /**
   * Save offer transaction meta to order
   *
   * @param WC_Order $order    The order that is being paid for.
   * @param Object   $response The response that is send from the payment gateway.
   * @param array    $product  The product data.
   * 
   * @since 1.9.0
   * @return void
   */
  public function save_offer_transaction_meta( $order, $response, $product ) {

    $order->update_meta_data( '_wpfunnels_offer_txn_resp_' . $product['step_id'], $response['id'] );
    $order->update_meta_data( '_wpfnl_offer_txn_stripe_source_id_' . $product['step_id'], $response['payment_method'] );
    $order->update_meta_data( '_wpfnl_offer_txn_stripe_customer_id_' . $product['step_id'], $response['customer'] );
    $order->save();
  }

  /**
   * Create a new PaymentIntent against the new order.
   *
   * @param object $order                The order that is being paid for.
   * @param array  $offer_product        Offer product.
   * @param object $payment_information  The source that is used for the payment.
   * 
   * @since 1.9.0
   * @return mix object|bool         An intent or false.
   */
  public function process_payment_against_intent( $order, $offer_product, $payment_information ) {
    if( !$order && !isset($offer_product['step_id'], $offer_product['id'], $offer_product['price'] ) ){
      return false;
    }
    $f_name         = $order->get_billing_first_name();
    $l_name         = $order->get_billing_last_name();
    $email          = $order->get_billing_email();
    $customer_id    = $order->get_meta( '_stripe_customer_id' );

    $offer_amount   = \WC_Payments_Utils::prepare_amount( floatval($offer_product['price']), $order->get_currency() );
    $step_id        = $offer_product['step_id'];
    $product        = wc_get_product( $offer_product['id'] ); 
    $payment_source = \WC_Payments::get_gateway()->get_payment_method_ids_enabled_at_checkout( null, true );
    
    $metadata = array(
      'customer_name'        => sanitize_text_field( $f_name ) . ' ' . sanitize_text_field( $l_name ),
      'customer_email'       => sanitize_email( $email ),
      'site_url'             => esc_url( get_site_url() ),
      'payment_type'         => $payment_information->get_payment_type(),
      'order_number'         => $order->get_order_number() . '_' . $offer_product['id'] . '_' . $step_id,

      'statement_descriptor' => apply_filters(
        'wpfunnels/' . $this->key . '_offer_statement_descriptor',
        substr(
          trim(
            sprintf( __( 'Order %1$s-OTO', 'wpfnl-pro' ), $order->get_order_number() )
          ),
          0,
          22
        )
      ),
    );

    /**
     * Use this in case to add the backward compatibility of the payment gateway.
     * We don't need to add the backward compatibility as we are adding the support from scratch newly.
     */

    if ( version_compare( '5.8.0', WCPAY_VERSION_NUMBER, '<=' ) ) {
      $amount_array = [ 
        'shipping_price' => \WC_Payments_Utils::prepare_amount( (float) $offer_product['shipping_fee_tax'], $order->get_currency() ),
        'unit_price' => \WC_Payments_Utils::prepare_amount( (float) $offer_product['unit_price'], $order->get_currency() ),
        'discount_price' => \WC_Payments_Utils::prepare_amount( $offer_product['original_price'] - $offer_product['price'], $order->get_currency() ),
      ];
      

      $request = \WCPay\Core\Server\Request\Create_And_Confirm_Intention::create();

      $request->set_amount( $offer_amount );
      $request->set_currency_code( strtolower( $order->get_currency() ) );
      $request->set_payment_method( $payment_information->get_payment_method() );
      $request->set_customer( $customer_id );
      $request->set_capture_method( $payment_information->is_using_manual_capture() );
      $request->set_metadata( $metadata );
      $request->set_level3( $this->get_level3_data_for_offer_product( $order, $offer_product, $amount_array ) );
      $request->set_off_session( $payment_information->is_merchant_initiated() );
      $request->set_payment_methods( $payment_source );
      $request->set_cvc_confirmation( $payment_information->get_cvc_confirmation() );
      $request->set_fingerprint( $payment_information->get_fingerprint() );
      if( $product ){
        $is_subscription_product = $product->is_type( 'subscription' ) || $product->is_type( 'variable-subscription' ) || $product->is_type( 'subscription_variation' );
        if ( $is_subscription_product ) {
          $mandate = $order->get_meta( '_stripe_mandate_id', true );
          if ( $mandate ) {
            $request->set_mandate( $mandate );
          }
        }
      }
      
      /**
       * @modified_date 07-11-2023
       */
      if ( version_compare( '6.6.0', WCPAY_VERSION_NUMBER, '<=' ) ) {
        $request->assign_hook( 'wcpay_create_and_confirm_intent_request_api' );
        $intent = $request->send();
      }else{
        $intent = $request->send( 'wcpay_create_and_confirm_intent_request', $payment_information );
      }
      

    }else if ( version_compare( '3.9.0', WCPAY_VERSION_NUMBER, '<=' ) && version_compare( '5.8.0', WCPAY_VERSION_NUMBER, '>' ) ) {
      $save_payment_method = $payment_information->should_save_payment_method_to_store();
      $intent = $this->gateway_obj['api_client']->create_and_confirm_intention(
        $offer_amount,
        strtolower( $order->get_currency() ),
        $payment_source,
        $customer_id,
        $payment_information->is_using_manual_capture(),
        $payment_information->should_save_payment_method_to_store(),
        $payment_information->should_save_payment_method_to_platform(),
        $metadata
      );
    } else {
      // $save_payment_method = $payment_information->should_save_payment_method();
      $intent = $this->gateway_obj['api_client']->create_and_confirm_intention(
        $offer_amount,
        strtolower( $order->get_currency() ),
        $payment_source,
        $customer_id,
        $payment_information->is_using_manual_capture(),
        $payment_information->should_save_payment_method(),
        $metadata,
        array(),
        $payment_information->is_merchant_initiated()
      );
    }

    return $intent;
  }


  /**
   * Prepare offer data to sent in the payment request.
   *
   * @param object $order Current Order Object.
   * @param array  $offer_product Selected offer Product Data.
   *
   * @since 1.9.0
   * @return array $level3_data modified data for the payment request.
   */
  public function get_level3_data_for_offer_product( $order, $offer_product, $amount_array ) {
    $level3_data = [];
    if( $order && isset($offer_product['shipping_fee_tax'], $offer_product['name'], $offer_product['desc'] , $offer_product['unit_price'], $offer_product['unit_price_tax'], $offer_product['qty'], $offer_product['original_price'], $offer_product['price'] ) ){
      $level3_data = array(
        'merchant_reference' => (string) $order->get_id(),
        'customer_reference' => (string) $order->get_id(),
        'shipping_amount'    => isset($amount_array['shipping_amount']) ? $amount_array['shipping_amount'] : '',
        'line_items'         => (object) array(
          'product_code'        => (string) substr( $offer_product['name'], 0, 12 ),
          'product_description' => substr( $offer_product['desc'], 0, 26 ), 
          'unit_cost'           => isset($amount_array['unit_price']) ? $amount_array['unit_price'] : '',
          'quantity'            => $offer_product['qty'], 
          'tax_amount'          => ! empty( $offer_product['unit_price_tax'] ) ? ( $offer_product['unit_price_tax'] - $offer_product['unit_price'] ) : 0, 
          'discount_amount'     => isset($amount_array['discount_price']) ? $amount_array['discount_price'] : '',
        ),
      );
    }
    return $level3_data;
  }


  /**
   * Process offer refund
   *
   * @param object $order Order Object.
   * @param array  $offer_data offer data.
   *
   * @since 1.9.0
   * @return string|bool.
   */
  public function process_offer_refund( $order, $offer_data ) {

    $transaction_id = $offer_data['transaction_id'];
    $refund_amount  = $offer_data['refund_amount'];
    $order_currency = $order->get_currency( $order );

    $response_id = false;

    if ( ! is_null( $refund_amount ) && ! empty( $transaction_id ) ) {

      $intent    = $this->gateway_obj['api_client']->get_intent( $transaction_id );
      $charge_id = $intent->get_charge()->get_id();

      $response = $this->gateway_obj['api_client']->refund_charge(
        $charge_id,
        \WC_Payments_Utils::prepare_amount( $refund_amount, $order_currency )
      );

      if ( ! empty( $response->error ) || ! $response ) {
        $response_id = false;
      } else {
        $response_id = isset( $response->id ) ? $response->id : true;
      }
    }
    return $response_id;
  }

  /**
   * Allow gateways to declare whether they support offer refund
   *
   * @since 1.9.0
   * @return bool
   */
  public function is_api_refund() {
    return apply_filters( 'wpfunnels/enable_wc_payment_refund', $this->is_api_refund ) ;
  }

  /**
   * Setup the Payment data for Stripe's Automatic Subscription.
   *
   * @param WC_Subscription $subscription An instance of a subscription object.
   * @param object          $order Object of order.
   * @param array           $offer_product array of offer product.
   * 
   * @since 1.9.0
   * @return void
   */
  public function add_subscription_payment_meta( $subscription,  $offer_product, $order ) {

    if (  $subscription && $order && $this->key === $order->get_payment_method() ) {

      $txn_id    = $order->get_meta( '_wpfunnels_offer_txn_resp_' . $offer_product['step_id'], true );
      $intent_id = $order->get_meta( '_wpfnl_woop_offer_stripe_intent_id_' . $offer_product['step_id'], true );

      $subscription->update_meta_data( '_payment_method_id', $txn_id );
      $subscription->update_meta_data( '_payment_tokens', $order->get_payment_tokens() );
      $subscription->update_meta_data( '_stripe_customer_id', $order->get_meta( '_stripe_customer_id', true ) );
      $subscription->update_meta_data( '_charge_id', $order->get_meta( '_wpfnl_woop_offer_charge_id_' . $offer_product['step_id'] . '_' . $order->get_id(), true ) );
      $subscription->update_meta_data( '_intent_id', $intent_id );

      $subscription->save_meta_data();
      $subscription->save();

    }
  }


  /**
   * Save the parent payment meta to child order.
   *
   * @param object $parent_order Object of order.
   * @param object $child_order Object of order.
   * @param int    $transaction_id transaction id.
   * 
   * @since 1.9.0
   * @return void
   */
  public function add_required_meta_to_child_order( $parent_order, $child_order, $transaction_id ) {

    $offer_intent_id = array(
      'id' => $transaction_id,
    );

    // Get order/intent/payment data from the parent order object.
    $step_id           = $child_order->get_meta( '_wpfunnels_offer_step_id', true );
    $intent_id         = $parent_order->get_meta( '_wpfnl_woop_offer_stripe_intent_id_' . $step_id, true );
    $charge_id         = $parent_order->get_meta( '_wpfnl_woop_offer_charge_id_' . $step_id . '_' . $parent_order->get_id(), true );
    $payment_method_id = $parent_order->get_meta( '_wpfnl_woop_offer_payment_method_id', true );
    $payment_mode      = $parent_order->get_meta( '_wpfnl_woop_offer_wcpay_mode', true );
    $customer_id       = $parent_order->get_meta( '_wpfnl_offer_txn_stripe_customer_id_' . $step_id );
    
    $intent            = $this->gateway_obj['api_client']->get_intent( $intent_id );
    $intent_status     = $intent->get_status();


    $child_order->update_meta_data( '_wpfnl_woop_offer_stripe_intent_id_' . $step_id, $offer_intent_id );
    $child_order->set_transaction_id( $intent_id );
    $child_order->update_meta_data( '_intent_id', $intent_id );
    $child_order->update_meta_data( '_charge_id', $charge_id );
    $child_order->update_meta_data( '_intention_status', $intent_status );
    $child_order->update_meta_data( '_payment_method_id', $payment_method_id );
    $child_order->update_meta_data( '_stripe_customer_id', $customer_id );

    $child_order->save();
  }
}

if( Wpfnl_functions::is_wc_payment_active() ){
  Wpfnl_Pro_Woocommerce_Payments::get_instance();
}
