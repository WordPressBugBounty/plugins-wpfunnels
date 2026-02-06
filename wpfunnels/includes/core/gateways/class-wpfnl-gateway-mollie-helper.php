<?php

namespace WPFunnels\Gateway;

use WPFunnels\Wpfnl_functions;

/**
 * Class Wpfnl_Pro_Mollie_Helper
 * @package WPFunnelsPro\Frontend\Gateways
 */
class Wpfnl_Pro_Mollie_Helper {


    /**
	 * Plugin ID variable
	 *
	 * @var string $plugin_id The unique identifier for the Mollie Payments for WooCommerce plugin.
	 */
	public $plugin_id = 'mollie-payments-for-woocommerce';

	/**
	 * Create payment API variable.
	 *
	 * @var string $create_payment_api The URL for creating payments using Mollie API v2.
	 */
	public $create_payment_api = 'https://api.mollie.com/v2/payments';

	/**
	 * Create customer API variable.
	 *
	 * @var string $create_customer_api The URL for creating customers using Mollie API v2.
	 */
	public $create_customer_api = 'https://api.mollie.com/v2/customers';

	/**
	 * Get payment API variable.
	 *
	 * @var string $get_payment_api The base URL for fetching payment details using Mollie API v2.
	 */
	public $get_payment_api = 'https://api.mollie.com/v2/payments/';


	/**
	 * Get the Mollie customer ID associated with the order.
	 *
	 * This function retrieves the Mollie customer ID from the order object's meta data. It first tries to get
	 * the Mollie customer ID from the '_wpfnl_mollie_customer_id' meta key. If the Mollie customer ID is not
	 * found in the '_wpfnl_mollie_customer_id' key, it tries to get it from the '_mollie_customer_id' key.
	 *
	 * @param object $order The order object.
	 *
	 * @return false|string The Mollie customer ID if found, otherwise false.
	 * @since 1.9.4
	 */
	public function get_user_mollie_customer_id( $order ) {
		
		if( !$order ){
			return false;
		}

		$mollie_customer_id = $order->get_meta( '_wpfnl_mollie_customer_id' );
		if ( ! $mollie_customer_id ) {
			$mollie_customer_id = $order->get_meta( '_mollie_customer_id' );
			
		}

		return $mollie_customer_id ?? false;
	}


	/**
	 * Get the Mollie API key based on the selected mode (live or test).
	 *
	 * This function retrieves the Mollie API key based on the selected mode in the plugin settings. If the plugin
	 * is in live mode, it retrieves the live API key from the '_live_api_key' option. Otherwise, it retrieves the
	 * test API key from the '_test_api_key' option.
	 *
	 * @return string The Mollie API key.
	 * @since 1.9.4
	 */
	public function get_mollie_api_key() {
		$is_live_mode = 'yes' === get_option( 'mollie-payments-for-woocommerce_test_mode_enabled' ) ? false : true;

		if ( $is_live_mode ) {
			$api_key = get_option( 'mollie-payments-for-woocommerce_live_api_key' );
		} else {
			$api_key = get_option( 'mollie-payments-for-woocommerce_test_api_key' );
		}

		return $api_key;
	}

	/**
	 * Get the return URL for Mollie payment processing.
	 *
	 * This function generates the return URL to be used for Mollie payment processing. It takes the step ID, order ID,
	 * and order key as input parameters and constructs the URL using the step permalink. The function adds specific
	 * query arguments, such as 'wpfnl-order', 'wpfnl-key', and 'wpfnl-mollie-return', to the URL to pass essential data
	 * for processing the Mollie payment return.
	 *
	 * @param int    $step_id    The step ID.
	 * @param int    $order_id   The order ID.
	 * @param string $order_key  The order key.
	 *
	 * @return string|bool The generated return URL for Mollie payment processing. otherwise false
	 * @since 1.9.4
	 */
	public function get_return_url( $step_id, $order_id, $order_key ) {
		if( !$step_id || !$order_id || !$order_key ){
			return false;
		}

		$url = get_permalink( $step_id );
		
		if( !$url ){
			return false;
		}

		$args = array(
			'wpfnl-order'         => $order_id,
			'wpfnl-key'           => $order_key,
			'key'           => $order_key,
			'wpfnl-mollie-return' => true,
		);
		return add_query_arg( $args, $url );
	}

	/**
	 * Get the redirect location after successfully completing the process_payment.
	 *
	 * This function takes the payment object as input and returns the URL where the user should be redirected after
	 * successfully completing the process_payment for the payment object. The function retrieves the payment URL from the
	 * payment object's _links property, specifically the checkout.href attribute, to determine the redirection location.
	 *
	 * @param object $payment_object The payment object.
	 *
	 * @return string The redirect URL after successfully completing the process_payment.
	 * @since 1.9.4
	 */
	public function get_process_payment_redirect( $payment_object ) {
		/*
		* Redirect to the payment URL
		*/
		return $payment_object->_links->checkout->href;
	}


	/**
	 * Get the API response body from a given API URL with provided arguments.
	 *
	 * This function performs a GET request to the specified API URL with the provided arguments and retrieves the response body.
	 * It returns the decoded JSON response body or false if an error occurs during the API request.
	 *
	 * @param string $url  The API URL to request.
	 * @param array  $args The arguments to include in the API request.
	 *
	 * @return mixed|false The decoded JSON response body or false if an error occurs during the API request.
	 * @since 1.9.4
	 */
	public function get_mollie_api_response_body( $url, $args ) {

		$result = wp_remote_get( $url, $args );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		$retrieved_body = wp_remote_retrieve_body( $result );

		return json_decode( $retrieved_body );

	}


	/**
	 * Get the WooCommerce payment gateway for the specified key.
	 *
	 * This function retrieves the list of WooCommerce payment gateways and returns the payment gateway object
	 * associated with the provided key.
	 *
	 * @return array The WooCommerce payment gateway object for the specified key.
	 * @since 1.9.4
	 */
	public function get_wc_gateway() {

		global $woocommerce;

		$gateways = $woocommerce->payment_gateways->payment_gateways();

		return $gateways[ $this->key ];
	}


	/**
	 * Save the Mollie customer ID for the given order.
	 *
	 * This function sets the Mollie customer ID as metadata for the provided order object.
	 *
	 * @param object $order The order object.
	 * @param string $customer_id The Mollie customer ID to be associated with the order.
	 * 
	 * @return void
	 * @since 1.9.4
	 */
	public function set_mollie_customer_id( $order, $customer_id ) {

		if ( ! empty( $customer_id ) ) {

			try {
				// Save the Mollie customer ID as metadata for the order.
				$order->update_meta_data( '_mollie_customer_id', $customer_id );
				$order->update_meta_data( '_wpfnl_mollie_customer_id', $customer_id );
				$order->save();
			} catch ( Exception $e ) {
				throw $e;
			}
		}
	}


	/**
	 * Maybe get the Mollie customer ID from a previous order for a non-logged-in user.
	 *
	 * This function attempts to retrieve the Mollie customer ID associated with the provided billing email
	 * from a previous order for a non-logged-in user. It queries the shop orders with the provided billing email
	 * and checks for the existence of Mollie customer IDs in the metadata. If found, it returns the first Mollie
	 * customer ID from the most recent order, or null if not found.
	 *
	 * @param string $billing_email The billing email of the user.
	 *
	 * @return null|string The Mollie customer ID associated with the billing email from a previous order,
	 *                    or null if not found.
	 * @since 1.9.4
	 */
	public function maybe_get_mollie_customer_id_from_order( $billing_email ) {

		$mollie_customer_id = null;

		// Query shop orders with the provided billing email and existing Mollie customer IDs in metadata.
		$prev_orders_by_meta = new \WP_Query(
			array(
				'post_type'   => 'shop_order',
				'post_status' => 'any',
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => '_billing_email',
						'value'   => $billing_email,
						'compare' => '=',
					),
					array(
						'key'     => '_mollie_customer_id',
						'compare' => 'EXISTS',
					),
				),
				'fields'      => 'ids',
				'order'       => 'ASC',
			)
		);

		if ( is_array( $prev_orders_by_meta->posts ) && count( $prev_orders_by_meta->posts ) > 0 ) {

			// Get the ID of the most recent order with a Mollie customer ID.
			$prev_order_id = $prev_orders_by_meta->posts[0];

			// Get the order object.
			$prev_order = wc_get_order( $prev_order_id );

			// Get the Mollie customer ID from the order's metadata.
			$mollie_customer_id = $prev_order->get_meta( '_mollie_customer_id' );
		}

		return $mollie_customer_id;
	}


	/**
	 * Format the currency value according to Mollie's requirements.
	 *
	 * This function takes an order value and its currency as input and returns the formatted
	 * currency value as required by Mollie. For some specific currencies, such as JPY and ISK,
	 * no decimal places are used, while for others, two decimal places are used.
	 *
	 * @param string $value    The order value to be formatted.
	 * @param string $currency The currency code of the order value.
	 *
	 * @return string The formatted currency value.
	 * @since 1.9.4
	 */
	public function format_currency_value( $value, $currency ) {
		$currencies_with_no_decimals = array( 'JPY', 'ISK' );

		if ( in_array( $currency, $currencies_with_no_decimals, true ) ) {
			// For currencies like JPY and ISK, format the value without decimals.
			return number_format( $value, 0, '.', '' );
		}

		// For other currencies, format the value with two decimal places.
		return number_format( $value, 2, '.', '' );
	}


	/**
	 * Set the HTTP response code for the current request.
	 *
	 * This function sets the HTTP response code for the current request. It first checks if the PHP SAPI is not 'cli' and
	 * if the headers have not been sent. If the function `http_response_code` exists, it uses it to set the status code;
	 * otherwise, it sets the status code using the `header` function.
	 *
	 * @param int $status_code The HTTP status code to set.
	 * 
	 * @return void
	 * @since 1.9.4
	 */
	public function set_http_response_code( $status_code ) {
		if ( 'cli' !== PHP_SAPI && ! headers_sent() ) {
			if ( function_exists( 'http_response_code' ) ) {
				// Use http_response_code if available (PHP >= 5.4.0).
				http_response_code( $status_code );
			} else {
				// Fallback for older PHP versions.
				header( ' ', true, $status_code );
			}
		}
	}

	/**
	 * Store required meta keys for refund in a separate order.
	 *
	 * This function stores the required meta keys for a refund in a separate order. It takes the parent order object,
	 * child order object, and the Mollie transaction ID as parameters. If the transaction ID is not empty, it retrieves
	 * the payment mode from the parent order's meta data. Then, it updates the child order's meta data with the Mollie
	 * order ID, Mollie payment ID, and the payment mode obtained from the parent order. Finally, it saves the child order.
	 *
	 * @param object $parent_order The parent order object.
	 * @param object $child_order The child order object for the refund.
	 * @param string $transaction_id The Mollie transaction ID for the refund.
	 * 
	 * @return void
	 * @since 1.9.4
	 */
	public function store_mollie_meta_keys_for_refund( $parent_order, $child_order, $transaction_id ) {
		if ( ! empty( $transaction_id ) ) {
			
			// Retrieve the payment mode from the parent order's meta data.
			$payment_mode = $parent_order->get_meta( '_mollie_payment_mode' );
			// Update the child order's meta data with Mollie order ID, Mollie payment ID, and payment mode from the parent order.
			$child_order->update_meta_data( '_mollie_order_id', $transaction_id );
			$child_order->update_meta_data( '_mollie_payment_id', $transaction_id );
			$child_order->update_meta_data( '_mollie_payment_mode', $payment_mode );

			// Save the child order.
			$child_order->save();
		}
	}


	/**
	 * Process offer refund for an order.
	 *
	 * This function processes the offer refund for an order. It takes the order object and offer data as parameters,
	 * including the transaction ID, refund amount, and refund reason. The function checks if the refund amount is not null
	 * and if the transaction ID is set. It then retrieves the API key using the `get_mollie_api_key` function and prepares
	 * the API request to get the payment details from Mollie. If the payment is successfully retrieved and the remaining
	 * amount to refund matches the order's currency and is greater than or equal to the refund amount, it proceeds to create
	 * a refund using the Mollie API. The refund details are sent to the Mollie API, and the response ID is obtained.
	 *
	 * @param object $order The order object.
	 * @param array $offer_data The offer data, including transaction_id, refund_amount, and refund_reason.
	 *
	 * @return string|bool The refund response ID on success, or false on failure.
	 * @since 1.9.4
	 */
	public function process_offer_refund( $order, $offer_data ) {
		$transaction_id = $offer_data['transaction_id'];
		$refund_amount = number_format( $offer_data['refund_amount'], 2 );
		$refund_reason = $offer_data['refund_reason'];
		$order_currency = $order->get_currency( $order );

		$response_id = false;

		if ( ! is_null( $refund_amount ) && isset( $transaction_id ) ) {
			// Retrieve the Mollie API key.
			$api_key = $this->get_mollie_api_key();

			// Prepare the API request to get payment details from Mollie.
			$get_payment = $this->get_payment_api . $transaction_id;

			$arguments = array(
				'method' => 'GET',
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
			);

			// Get the payment details from Mollie.
			$payment = $this->get_mollie_api_response_body( $get_payment, $arguments );

			if ( $payment && null !== $payment->amountRemaining && $payment->amountRemaining->currency === $order_currency && $payment->amountRemaining->value >= $refund_amount ) {
				// Proceed to create a refund using the Mollie API.
				$refund_api_url = 'https://api.mollie.com/v2/payments/' . $transaction_id . '/refunds';

				$refund_args = array(
					'amount' => array(
						'currency' => $order_currency,
						'value' => $refund_amount,
					),
				);

				$arguments = array(
					'method' => 'POST',
					'headers' => array(
						'Content-Type' => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
					),
					'body' => wp_json_encode( $refund_args ),
				);

				// Send the refund details to the Mollie API.
				$refund_response = $this->get_mollie_api_response_body( $refund_api_url, $arguments );

				if ( $refund_response ) {
					$response_id = $refund_response->id;
				}
			}
		}

		return $response_id;
	}

	/**
	 * Setup the Payment data for Mollie Automatic Subscription.
	 *
	 * This function is responsible for adding subscription payment meta data for Mollie when the payment method used for
	 * the order is either 'mollie_wc_gateway_creditcard' or 'mollie_wc_gateway_ideal'. It takes the subscription object, 
	 * the order object, and an array of offer product as parameters. If the order's payment method is supported by Mollie,
	 * the function updates the subscription's meta data with relevant payment information obtained from the order, including
	 * the Mollie payment ID, payment mode, and customer ID. The updated subscription meta data is then saved.
	 *
	 * @param WC_Subscription $subscription An instance of a subscription object.
	 * @param object $order Object of the order.
	 * @param array $offer_product Array of offer product data.
	 * 
	 * @return void
	 * @since 1.9.4
	 */
	public function add_subscription_payment_meta_for_mollie( $subscription, $offer_product, $order ) {
		// Check if the order's payment method is supported by Mollie.
		if ( in_array( $order->get_payment_method(), array( 'mollie_wc_gateway_creditcard', 'mollie_wc_gateway_ideal' ), true ) ) {
			// Update subscription meta data with relevant payment information.
			$subscription->update_meta_data( '_mollie_payment_id', $order->get_meta( '_mollie_payment_id' ) );
			$subscription->update_meta_data( '_mollie_payment_mode', $order->get_meta( '_mollie_payment_mode' ) );
			$subscription->update_meta_data( '_mollie_customer_id', $order->get_meta( '_mollie_customer_id' ) );
			$subscription->save();
		}
	}


	/**
	 * Create a Mollie customer ID for non-logged-in users.
	 *
	 * This function is responsible for creating a Mollie customer ID for non-logged-in users during the checkout process.
	 * It takes an array of payment arguments and the order data as parameters. If the provided payment arguments already 
	 * contain a customer ID or if the checkout ID is not available, the function simply returns the input data. Otherwise,
	 * it attempts to create a new Mollie customer using the billing information from the order. If a customer with the same 
	 * billing email address is found, the function uses the existing customer ID. If no customer is found, a new Mollie 
	 * customer is created with the customer's name and email from the order. The Mollie customer ID is then stored in the
	 * order's meta data using the set_mollie_customer_id function. The updated payment arguments with the customer ID are
	 * then returned.
	 *
	 * @param array $data Payment arguments.
	 * @param object $order Order data.
	 * 
	 * @return array The updated payment arguments with the Mollie customer ID.
	 * @since 1.9.4
	 */
	public function maybe_create_mollie_customer_id( $data, $order ) {
		// Check if the payment arguments already contain a customer ID.
		if ( isset( $data['payment']['customerId'] ) && null !== $data['payment']['customerId'] ) {
			return $data;
		}

		// Get the checkout ID from the post data.
		$checkout_id = \WPFunnels\Wpfnl_functions::get_checkout_id_from_post_data();

		if ( $checkout_id ) {
			try {
				// Get the billing information from the order.
				$billing_first_name = $order->get_billing_first_name();
				$billing_last_name  = $order->get_billing_last_name();
				$billing_email      = $order->get_billing_email();

				// Check if the customer with the same billing email already exists.
				$customer_id = $this->maybe_get_mollie_customer_id_from_order( $billing_email );

				if ( null === $customer_id ) {
					// Get the best name for use as Mollie Customer name.
					$user_full_name = $billing_first_name . ' ' . $billing_last_name;

					$api_key = $this->get_mollie_api_key();

					// Create data for the new Mollie customer.
					$customer_data = array(
						'name'     => trim( $user_full_name ),
						'email'    => trim( $billing_email ),
						'metadata' => array( 'order_id' => $order->get_id() ),
					);

					$arguments = array(
						'method'  => 'POST',
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => 'Bearer ' . $api_key,
						),
						'body'    => wp_json_encode( $customer_data ),
					);

					// Attempt to create a new Mollie customer.
					$response = $this->get_mollie_api_response_body( $this->create_customer_api, $arguments );

					if ( $response && isset( $response->id ) ) {
						$customer_id = $response->id;
					}
				}

				// Store the Mollie customer ID in the order's meta data.
				$this->set_mollie_customer_id( $order, $customer_id );
			} catch ( Exception $e ) {
				// Handle any exceptions or errors that may occur during the process.
				throw $e;
			}
		}

		return $data;
	}


    /**
     * Get webhook URL for Mollie credit card payments.
     *
     * @param int    $step_id   Step ID.
     * @param int    $order_id  Order ID.
     * @param string $order_key Order key.
     *
     * @return string The generated webhook URL.
     * @since 1.9.4
     */
    public function get_webhook_url( $step_id, $order_id, $order_key, $type ) {
        $url = WC()->api_request_url( 'wpfnl_mollie_'.$type.'_webhook' );
        $args = array(
            'step_id'   => $step_id,
            'order_id'  => $order_id,
            'order_key' => $order_key,
        );

        return add_query_arg( $args, $url );
    }


    /**
     * Process webhook from Mollie for credit card payments.
     */
    public function maybe_process_mollie_webhook() {
        if ( empty( $_GET['step_id'] ) || empty( $_GET['order_id'] ) || empty( $_GET['order_key'] ) ) {
            return;
        }

        $step_id   = sanitize_text_field( wp_unslash( $_GET['step_id'] ) );
        $order_id  = sanitize_text_field( wp_unslash( $_GET['order_id'] ) );
        $order_key = sanitize_text_field( wp_unslash( $_GET['order_key'] ) );

        $order = wc_get_order( $order_id );

        if( false === is_a( $order, 'WC_Order' ) ){
            $this->set_http_response_code( 404 );
            return;
        }

        if ( ! $order->key_is_valid( $order_key ) ) {
            $this->set_http_response_code( 401 );
            return;
        }

        // No Mollie payment ID provided.
        if ( empty( $_POST['id'] ) ) {
            $this->set_http_response_code( 400 );
            return;
        }

        $payment_object_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );

        $get_payment = $this->get_payment_api . $payment_object_id;

        $api_key = $this->get_mollie_api_key();

        $arguments = array(
            'method'  => 'GET',
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
        );

        // Load the payment from Mollie, do not use cache.
        try {
            $payment = $this->get_mollie_api_response_body( $get_payment, $arguments );
        } catch ( Exception $e ) {
            $this->set_http_response_code( 400 );
            return;
        }

        // Payment not found.
        if ( ! $payment ) {
            $this->set_http_response_code( 404 );
            return;
        }

        if ( $order_id != $payment->metadata->order_id ) {
            $this->set_http_response_code( 400 );
            return;
        }

        // Update the order with the transaction ID from Mollie.
        $order->update_meta_data( '_' . $step_id . '_tr_id', $payment_object_id );
        $order->save();
        $order->add_order_note( __( 'Payment processed for Mollie.', 'wpfnl-pro' ) );
    }


	/**
     * Process payment after a successful transaction.
     *
     * @param WC_Order $order The WooCommerce order object.
     * @param array    $offer_product An array containing offer product details.
     *
     * @return array An array containing the processing result with 'is_success' and 'message' keys.
     * @since 1.9.4
     */
    public function process_payment( $order, $offer_product ) {
        $result = array(
            'is_success' => false,
            'message'    => ''
        );

        // Get the transaction ID from the order meta.
        $tr_id = $order->get_meta( '_mollie_payment_id' );

        // Update the offer transaction response for the specific step.
        $order->update_meta_data( '_wpfunnels_offer_txn_resp_' . $offer_product['step_id'], $tr_id );
		$order->save();
        if ( '' !== $tr_id ) {
            // Payment processing was successful.
            $result['is_success'] = true;
        }

        return $result;
    }



	/**
     * process ajax for mollie ideal
     *
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function process_mollie_payment( $type, $method ) {
        $nonce = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_STRING );
        if ( ! wp_verify_nonce( $nonce, 'wpfnl_mollie_'.$type.'_process_nonce' ) ) {
            return array(
				'result'  => 'fail',
				'message' => __( 'nonce is not verified', 'wpfnl-pro' ),
			);
        }
        
        if( !isset($_POST['step_id']) || !isset( $_POST['order_id'] ) || !isset( $_POST['order_key'] ) ){
            return array(
				'result'  => 'fail',
				'message' => __( 'data not found', 'wpfnl-pro' ),
			);
        }

        $step_id = intval( $_POST['step_id'] );
        $order_id = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
        $order_key = sanitize_text_field( wp_unslash( $_POST['order_key'] ) );

        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : '';
        $quantity = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : '';

        $offer_product = Wpfnl_functions::get_offer_product_data( $step_id, $product_id, 0, $order_id );

        if ( isset( $offer_product['price'] ) && ( floatval( 0 ) === floatval( $offer_product['price'] )
                || '' === trim( $offer_product['price'] ) ) ) {
			return array(
				'result'  => 'fail',
				'message' => __( '0 value product', 'wpfnl-pro' ),
			);
        } 

        $order = wc_get_order( $order_id );
        $customer_id = $this->get_user_mollie_customer_id( $order );

        if( !$customer_id || false === is_a( $order, 'WC_Order' )){
			return array(
				'result'  => 'fail',
				'message' => __( 'Order or Customer ID not found. Payment failed', 'wpfnl-pro' ),
			);
        }

        $product_data = array(
            'variation_id' => $product_id,
            'input_qty'    => $quantity,
        );

        // Update the order metadata with the offer product data for the specific step.
        $order->update_meta_data( 'wpfnl_offer_product_data_' . $step_id, $product_data );
        $order->save();
        $api_key = $this->get_mollie_api_key();
        
            
        if ( wc_tax_enabled() ) {
            if ( !wc_prices_include_tax() ) {
                $tax = 0;
                foreach ($order->get_items(array('tax')) as $item_id => $line_item) {
                    $order_product_detail = $line_item->get_data();
                    if( isset($order_product_detail['tax_total']) &&  isset($order_product_detail['rate_percent']) ){
                        $tax = ($offer_product['total']*$order_product_detail['rate_percent'])/100;
                    }	
                }  
                $offer_product['price'] = $offer_product['price'] + $tax;
            }
        }

        $data = array(
            'amount'      => array(
                'currency' => $order->get_currency(),
                'value'    => $this->format_currency_value( $offer_product['price'], $order->get_currency() ),
            ),
            'description' => "One-click payment {$order_id}_{$step_id}",
            'redirectUrl' => $this->get_return_url( $step_id, $order_id, $order_key ),
            'webhookUrl'  => $this->get_webhook_url( $step_id, $order_id, $order_key, $type ),
            'method'      => $method,
            'metadata'    => array(
                'order_id' => $order_id,
            ),
            'customerId'  => $customer_id,
        );

        $arguments = array(
            'method'  => 'POST',
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => wp_json_encode( $data ),
        );

        // Make the API request to create the payment object.
        $payment_object = $this->get_mollie_api_response_body( $this->create_payment_api, $arguments );

        if ( !$payment_object ) {
            // Payment failed due to missing customer ID.
			return array(
				'result'  => 'fail',
				'message' => __( 'Payment failed', 'wpfnl-pro' ),
			);
        }
        
        // The payment object was successfully created. Return the response to the front-end.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_process_payment_redirect( $payment_object ),
		);
    }
}