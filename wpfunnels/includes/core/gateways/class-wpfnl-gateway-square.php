<?php
namespace WPFunnels\Gateway;

/**
 * @author [Saiduzzaman Tohin]
 * @email [tohin@coderex.co]
 * @create date 2022-05-26 11:16:34
 * @modify date 2022-05-26 11:16:34
 * @desc [Supporting Square Payment Gateway with WPFunnels one-click offer]
 */

use Square\SquareClient;
use WooCommerce\Square\Framework\PaymentGateway\Payment_Gateway_Helper;
use WooCommerce\Square\Framework\Square_Helper;
use WPFunnels\Wpfnl_functions;
use WooCommerce\Square\Gateway\Customer_Helper;

class Wpfnl_Gateway_Square {

    /**
     * @var string
     * @since 1.6.4
     */
    public $key = 'square_credit_card';

	/**
	 * Guest token call 
	 * 
     * @var string
     * @since 1.9.1
     */
	public $maybe_guest_token_call = false;

    /**
     * @var bool
     * @since 1.6.4
     */
    public $refund_support;


    public function __construct() {

		
        $this->refund_support = true;
		add_filter( 'wc_' . $this->key . '_payment_form_tokenization_forced', array( $this, 'is_square_tokenization_allowed' ), 10, 2 );
		add_filter( 'wc_payment_gateway_' . $this->key . '_get_order', [ $this, 'get_order_by_hook' ], 10 );
		add_filter( 'wc_payment_gateway_' . $this->key . '_process_payment', [ $this, 'is_credit_card_process_payment' ], 10, 3 );
        add_action( 'wpfunnels/child_order_created_' . $this->key, array( $this, 'add_capture_meta_to_child_order' ), 9999, 3 );
        add_action( 'wpfunnels/subscription_created', array( $this, 'add_offer_subscription_meta' ), 9999, 3 );
    }

	/**
	 * Get order
	 * 
	 * @param $order
	 * @param WC_Payment_Gateway $gateway
	 *
	 * @since 1.9.1
	 * @return mixed
	 */
	public function get_order_by_hook( $order ) {
		$this->set_square_client_environment();
		if ( ! isset( $order->payment->token ) ) {
			$order->payment->token = $order->get_meta('_wc_square_credit_card_payment_token');
		}
		if ( $this->maybe_guest_token_call && isset( $order->payment->verification_token ) && ! empty( $order->payment->verification_token ) ) {
			$order->payment->verification_token = null;
			if ( isset( $_POST[ 'wc-' . $this->get_wc_gateway()->get_id_dasherized() . '-buyer-verification-token' ] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
				unset( $_POST[ 'wc-' . $this->get_wc_gateway()->get_id_dasherized() . '-buyer-verification-token' ] ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}
		return $order;
	}


	/**
     * Credit card payment processing
     *
     * @param bool   $process_payment The current process payment status.
     * @param int    $order_id        The ID of the order.
     * @param object $gateway         The WooCommerce payment gateway object.
	 * 
     * @return bool Returns the updated process payment status.
	 * 
	 * @since 1.6.4
     */
    public function is_credit_card_process_payment($process_payment, $order_id, $gateway) {
        $order = wc_get_order( $order_id );
		if ( $order ) {
			$this->create_token_for_offer_payment( $order );
		}
		return $process_payment;
    }


    /**
     * Create payment token for offer product charge
     *
	 * Creates a payment token for processing the payment of an offer product charge. 
	 * 
     * @param object $order full order object.
	 * 
     * @return void
	 * 
	 * @since 1.9.1
     */
    private function create_token_for_offer_payment( $order ) {
        $this->set_square_client_environment();
		$order = $this->get_wc_gateway()->get_order( $order );
		$is_checkout_nonce_present_in_request = isset( $_REQUEST['wc_square_credit_card_checkout_validate_nonce'] ) ? wc_clean( $_REQUEST['wc_square_credit_card_checkout_validate_nonce'] ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $order->payment->token ) && empty( $is_checkout_nonce_present_in_request ) && 1 > $order->get_customer_id() ) {
			$create_token = true;
			if ( class_exists( '\WooCommerce\Square\Gateway\Customer_Helper' ) ) {
				$indexed_customers = Customer_Helper::get_customers_by_email( $order->get_billing_email() );
				if ( is_array( $indexed_customers ) && count( $indexed_customers ) > 1 ) {
					$create_token = false;
				}
			}
			if ( $create_token ) {
				$order = $this->get_wc_gateway()->get_payment_tokens_handler()->create_token( $order );
			}
			$this->maybe_guest_token_call = true;
			if ( isset( $_POST[ 'wc-' . $this->get_wc_gateway()->get_id_dasherized() . '-buyer-verification-token' ] ) ) { 
				unset( $_POST[ 'wc-' . $this->get_wc_gateway()->get_id_dasherized() . '-buyer-verification-token' ] );
			}
		}
    }


    /**
	 * Remove underscore and add dash inside a string
	 * 
     * @return string|string[]
	 * @since 1.6.4
     */
    public function get_id_dasherized() {
        return str_replace( '_', '-', $this->key );
    }


    /**
     * Return order information for creating customer response
     *
     * @param $order
	 * 
     * @return bool|\WC_Order|\WC_Order_Refund
	 * @since 1.6.4
     */
    public function get_order( $order ) {

        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }

        $order->payment = new \stdClass();

        if ( empty( $order->payment->token ) ) {
            $order->payment->nonce          = Square_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-nonce' );
            $order->payment->card_type      = Payment_Gateway_Helper::normalize_card_type( Square_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-card-type' ) );
            $order->payment->account_number = $order->payment->last_four = substr( Square_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-last-four' ), -4 );
            $order->payment->exp_month      = Square_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-exp-month' );
            $order->payment->exp_year       = Square_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-exp-year' );
            $order->payment->postcode       = Square_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-postcode' );
        }
        return $order;
    }


    /**
	 * Payment Gateway Payment Form Tokenization Forced.
	 *
	 * Filters whether tokenization is forced for the payment form.
	 *
	 * @param bool $tokenization_forced The current tokenization forced status.
	 *
	 * @return bool Returns the updated tokenization forced status.
	 * 
	 * @since 1.6.4
	 */
	public function is_square_tokenization_allowed($tokenization_forced)
	{
		return true;
		if(get_the_ID() === 1){
			$post = Wpfnl_functions::get_sanitized_get_post();
			$checkout_id = Wpfnl_functions::get_checkout_id_from_post($post);
		}else{
			$checkout_id = get_the_ID();
		}
        $funnel_id   = Wpfnl_functions::get_funnel_id_from_step( $checkout_id );
        if ( $checkout_id && $funnel_id ) {
            $next_step  = Wpfnl_functions::get_next_step($funnel_id, $checkout_id);

            if ( $next_step && Wpfnl_functions::is_offer_page($next_step['step_id']) ) {
                $tokenization_forced = true;
            }
        }
        return $tokenization_forced;
	}


    /**
     * Processing of the Upsell or Downsell offers
     * 
     * @param mixed $order
     * @param mixed $offer_product
     * 
     * @return array 
     * @since 1.6.4
     */
    public function process_payment($order, $offer_product) {
		$currency = $order->get_currency();
		// Setup square payment configuration 
        $client = $this->set_square_client_environment();
		
        if ( isset($offer_product['price']) && (floatval(0) === floatval( $offer_product['price'] ) || '' === trim($offer_product['price'])) ) {
            wp_send_json(array(
                'result'    => 'fail',
                'message'   => __('Product price is less than 0', 'wpfnl-pro'),
            ));
        } else {
            $money_utility_object = new \WooCommerce\Square\Utilities\Money_Utility();
            $amount_money = $money_utility_object::amount_to_money($offer_product['total'], $currency);
            
            $credit_card_payment_token = $order->get_meta('_wc_square_credit_card_payment_token');
            $credit_card_customer_id = $order->get_meta('_wc_square_credit_card_customer_id');

			// Prepare data structure for request
            $body = new \Square\Models\CreatePaymentRequest(
                $credit_card_payment_token,
                uniqid()
            );
            $body->setAutocomplete(true);
            $body->setCustomerId($credit_card_customer_id);
            $body->setAmountMoney($amount_money);

			// Handle payment request API responses
            $api_response = $client->getPaymentsApi()->createPayment($body);
			
            if ($api_response->isSuccess()) {
				$transaction_id = $api_response->getResult()->getPayment()->getId();

				// Store transaction ID for offer products
				$this->store_offer_transaction( $order, $transaction_id, $offer_product );
				// Reduce offer products from stock
				$this->reduce_the_offer_product_from_stock($order, $offer_product);

                return array(
                    'is_success' => true,
                    'message' => 'Success'
                );
            } else {
				$errors = $api_response->getErrors();
                return array(
					'is_success' => false,
					'message' => sprintf( __( 'Square payment transaction failed (%1$s:%2$s)', 'wpfnl-pro' ), $errors )
				);
            }
                        
        }
    }


	/**
	 * Store transaction ID for offer products
	 * 
     * @param $order
     * @param $response
     * @param $product
	 * 
	 * @since 1.6.4
     */
    public function store_offer_transaction( $order, $transaction_id, $product )
    {
        $order->update_meta_data('_wpfunnels_offer_txn_resp_' . $product['step_id'], $transaction_id);
        $order->save();
    }


	/**
	 * Set up the required configuration for payment.
	 * 
	 * @return object
	 * 
	 * @since 1.6.4
	 */
	public function set_square_client_environment() {

		// Set the access tokan.
		$this->access_token = $this->get_wc_gateway()->get_plugin()->get_settings_handler()->get_access_token();
		$this->access_token = empty( $this->access_token ) ? $this->get_wc_gateway()->get_plugin()->get_settings_handler()->get_option( 'sandbox_token' ) : $this->access_token;

		// Set the location id.
		$this->location_id = $this->get_wc_gateway()->get_plugin()->get_settings_handler()->get_location_id();
		$client = new SquareClient([
            'accessToken' => $this->access_token,
            'environment' => $this->get_environment(),
        ]);

		return $client;
	}


	/**
	 * Get WooCommerce payment geteways.
	 *
	 * @return array
	 * 
	 * @since 1.6.4
	 */
	public function get_wc_gateway() {

		global $woocommerce;

		$gateways = $woocommerce->payment_gateways->payment_gateways();

		return $gateways[ $this->key ];
	}


	/**
	 * Gets the configured environment.
	 *
	 * @since 1.6.4
	 *
	 * @return string
	 */
	public function get_environment() {
		$sanboxed = ( defined( 'WC_SQUARE_SANDBOX' ) && WC_SQUARE_SANDBOX ) || $this->is_sandbox_setting_enabled();
		return $sanboxed ? 'sandbox' : 'production';
	}


	/**
	 * Tells is if the setting for enabling sandbox is checked.
	 *
	 * @since 1.6.4
	 *
	 * @return boolean
	 */
	public function is_sandbox_setting_enabled() {
		return 'yes' === $this->get_enable_sandbox();
	}


	/**
	 * Gets setting enabled sandbox.
	 *
	 * @since 1.6.4
	 *
	 * @return string
	 */
	public function get_enable_sandbox() {
        $setting = get_option('wc_square_settings');
		return $setting['enable_sandbox'];
	}


	/**
	 * Determines if configured in the sandbox environment.
	 *
	 * @since 1.6.4
	 *
	 * @return bool
	 */
	public function is_sandbox() {

		return 'sandbox' === $this->get_environment();
	}


    /**
     * Update post meta for refund process on upsell or downsell
     *
     * @param $parent_order
     * @param $child_order
     * @param $transaction_id
     * 
     * @since 1.6.4
     */
    public function add_capture_meta_to_child_order( $parent_order, $child_order, $transaction_id ) {
        $child_order->update_meta_data('_wc_square_credit_card_trans_id', $parent_order->data['transaction_id'] );
		$child_order->update_meta_data('_wc_square_credit_card_square_location_id', $parent_order->get_meta( '_wc_square_credit_card_square_location_id', true ) );
		$child_order->update_meta_data('_wc_square_credit_card_authorization_code', $parent_order->data['transaction_id'] );
		$child_order->update_meta_data('_wc_square_credit_card_customer_id', $parent_order->get_meta( '_wc_square_credit_card_customer_id', true ) );
		$child_order->update_meta_data('_wc_square_credit_card_square_order_id', $parent_order->data['transaction_id'] );
		$child_order->update_meta_data('_wc_square_credit_card_charge_captured', 'yes' );
    }


	/**
	 * Process offer refund
	 *
	 * @param object $order Order Object.
	 * @param array  $offer_data offer data.
	 *
	 * @return string/bool.
	 * 
	 * @since 1.6.4
	 */
	public function process_refund_offer( $order, $offer_data ) {
		$payment_id = $offer_data['transaction_id'];

		// set up the square payment configuration.
		$client = $this->set_square_client_environment();
		$money_utility_object = new \WooCommerce\Square\Utilities\Money_Utility();
        $amount_money = $money_utility_object::amount_to_money($offer_data['amount'],'USD');

		$body = new \Square\Models\RefundPaymentRequest(
			uniqid(), 
			$amount_money,
			$payment_id
		);

		$api_response = $client->getRefundsApi()->refundPayment($body);
	
		if ($api_response->isSuccess()) {
			$response_id = $api_response->getResult()->getRefund()->getId();
			return $response_id;
		} else {
			$errors = $api_response->getErrors();
			return false;
		}

	}


	/**
     * add subscription offer meta to order
     *
     * @param $subscription
     * @param $offer_product
     * @param $order
	 * 
	 * @since 1.6.4
     */
    public function add_offer_subscription_meta( $subscription, $offer_product, $order ) {
        if ( $this->key === $order->get_payment_method() ) {
            $subscription_id = $subscription->get_id();
            update_post_meta( $subscription_id, '_wc_square_credit_card_payment_token', $order->get_meta( '_wc_square_credit_card_payment_token', true ) );
            update_post_meta( $subscription_id, '_wc_square_credit_card_customer_id', $order->get_meta( '_wc_square_credit_card_customer_id', true ) );
        }
    }



	/**
	 * Reduce the offer product from the stock after upsell or downsell offer has been accepted
	 *
	 * @param object $order Object of order.
	 * @param array  $offer_product array of offer product.
	 * 
	 * @since 1.6.4
	 */
	public function reduce_the_offer_product_from_stock( $order, $offer_product ) {

		$product = wc_get_product( $offer_product['id'] );
		if( $product ){
			$new_stock = wc_update_product_stock( $offer_product['id'], $offer_product['qty'], 'decrease' );
			$changes[] = array(
				'product' => $product,
				'from'    => $new_stock + intval( $offer_product['qty'] ),
				'to'      => $new_stock,
			);
			wc_trigger_stock_change_notifications( $order, $changes );
		}
		

	}

}