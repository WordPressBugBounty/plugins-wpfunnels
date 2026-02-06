<?php

namespace WPFunnels\Gateway;

use WC_Stripe_API;
use WC_Stripe_Helper;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Stripe_payment_process {

    public $key = 'stripe';

    public $refund_support;

    public function __construct() {
        $this->refund_support = true;

        add_filter( 'wc_stripe_force_save_source', array( $this, 'should_tokenize_stripe' ), 9999);
        add_filter( 'wc_stripe_3ds_source', array( $this, 'may_be_modify_3ds_param' ), 9999, 2);
        add_action( 'wc_gateway_stripe_process_response', array( $this, 'handle_redirection' ), 9999, 2 );

        add_action( 'wp_ajax_wpfunnels_stripe_sca_check', array( $this, 'check_stripe_sca' ),9999);
        add_action( 'wp_ajax_nopriv_wpfunnels_stripe_sca_check', array( $this, 'check_stripe_sca' ),9999);

        add_action( 'wpfunnels/child_order_created_' . $this->key, array( $this, 'add_capture_meta_to_child_order' ), 9999, 3 );

        add_action( 'wpfunnels/subscription_created', array( $this, 'add_offer_subscription_meta' ), 9999, 3 );

        add_action( 'woocommerce_checkout_after_order_review', array( $this, 'add_stripe_hidden_field' ), 99 );

    }


    /** 
     * wpfnl_stripe_tokenization
     * If required then tokenize to save source of payment   
     *  
     * @param bool $save_source force save source.
     * 
    */
    public function should_tokenize_stripe( $save_source )
    {
        $checkout_id = Wpfnl_functions::get_checkout_id_from_post_data();
        // Get checkout id if not found in post data.
        $checkout_id = !$checkout_id ? get_the_ID() : $checkout_id;
        $funnel_id   = Wpfnl_functions::get_funnel_id_from_step( $checkout_id );
        
        if ( $checkout_id && $funnel_id ) {

            if ( Wpfnl_functions::is_offer_exists_in_funnel($funnel_id) ) {
                $save_source = true;
            }
        }
       
        return $save_source;
    }


    /**
     * @param $funnel_id
     * @param $node_found
     * @return bool
     */
    public function go_to_output_1($funnel_id, $node_found) {
        $funnel_json = get_post_meta($funnel_id, '_funnel_data', true);
        if ($funnel_json) {
            $node_data = $funnel_json['drawflow']['Home']['data'];
            foreach ($node_data as $node_key => $node_value) {
                if ($node_value['id'] == $node_found) {
                    $next_node = $node_value['outputs']['output_1']['connections'][0]['node'];
                    return $next_node;
                }
            }
            return false;
        }
    }


    /**
     * @param $funnel_id
     * @param $node_found
     * @return bool
     */
    public function go_to_output_2($funnel_id, $node_found) {
        $funnel_json = get_post_meta($funnel_id, '_funnel_data', true);
        if ($funnel_json) {
            $node_data = $funnel_json['drawflow']['Home']['data'];
            foreach ($node_data as $node_key => $node_value) {
                if ($node_value['id'] == $node_found) {
                    $next_node = $node_value['outputs']['output_2']['connections'][0]['node'];
                    return $next_node;
                }
            }
            return false;
        }
    }


    /**
     * save 3ds source data for offers
     *
     * @param $post_data
     * @param $order
     * @return mixed
     */
    public function may_be_modify_3ds_param( $post_data, $order ) {
        if ( $order && Wpfnl_functions::check_if_offer_exists($order) ) {
            $order->update_meta_data( '_wpfunnels_stripe_source_id', $post_data['three_d_secure']['card'] );
            $order->save();
        }
        return $post_data;
    }


    /**
     * Redirection to order received url
     *
     * @param $response
     * @param $order
     */
    public function handle_redirection( $response, $order ) {
        if ( 1 === did_action( 'wpfunnels/offer_funnel_started' ) && 1 === did_action( 'wc_gateway_stripe_process_redirect_payment' ) ) {
            $order_received_url = $order->get_checkout_order_received_url();
            wp_safe_redirect( $order_received_url );
            exit();
        }
    }

    /**
     * wpfnl_stripe_maybe_hide_save_payment
     */
    public function wpfnl_stripe_maybe_hide_save_payment( $is_show ) {
		return $is_show;
	}


    /**
     * wpfnl_stripe_verify_sca
     * Verify if payment type is SCA or not
     *
     * @throws \WC_Stripe_Exception
     */
    public function check_stripe_sca() {
        $security = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_STRING );
        if ( ! wp_verify_nonce( $security, 'wpfnl_stripe_sca_check_nonce' ) ) {
            return;
        }

        global $woocommerce;

        $step_id       = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $order_id      = isset( $_POST['order_id'] ) ? intval($_POST['order_id']) : 0;
        $offer_type    = isset( $_POST['offer_type'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_type'] ) ) : '';
        $offer_action  = isset( $_POST['offer_action'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_action'] ) ) : '';
        $product_id    = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : '';
        
        if( !empty($_POST['attr']) && Wpfnl_functions::is_wc_active() ){
            $variation_id = (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
                new \WC_Product($product_id),
                $_POST['attr']
            );
            if( $variation_id ){
                $product_id = $variation_id;
            }
        }
      
        $order         = wc_get_order( $order_id );
        
        // Get offer product data - use offer class method if available, otherwise use pro functions
        if ( class_exists( '\WPFunnels\Offer\Wpfnl_Offer' ) ) {
            $offer_product = \WPFunnels\Offer\Wpfnl_Offer::get_offer_product_data( $step_id, $product_id, 0, $order_id );
        } elseif ( class_exists( '\WPFunnelsPro\Wpfnl_Pro_functions' ) ) {
            $offer_product = \WPFunnelsPro\Wpfnl_Pro_functions::get_offer_product_data( $step_id, $product_id, 0, $order_id );
        } else {
            wp_send_json(array(
                'result'    => 'fail',
                'message'   => __('Offer functionality not available', 'wpfnl'),
            ));
            return;
        }
        
        if ( isset($offer_product['price']) && (floatval(0) === floatval( $offer_product['price'] ) || '' === trim($offer_product['price'])) ) {
            wp_send_json(array(
                'result'    => 'fail',
                'message'   => __('Product price is less than 0', 'wpfnl-pro'),
            ));
        } else {
            $gateways   = $woocommerce->payment_gateways->payment_gateways();
            $gateway    = $gateways['stripe'];
            if ( $gateway ) {
                $order_source   = $gateway->prepare_order_source($order);

                $is_3ds         = isset($order_source->source_object->card->three_d_secure) ? $order_source->source_object->card->three_d_secure : false;

                $_3ds_array = [
                    'optional',
                    'not_supported',
                ];

                // check if 3ds is active or not
                if ( isset($is_3ds) && !in_array( $is_3ds,$_3ds_array ) ) {
                    
                    $intent         = $this->create_intent($order, $order_source, $offer_product);
                   
                    $main_settings  = get_option('woocommerce_stripe_settings');
                    $testmode       = (!empty($main_settings['testmode']) && 'yes' === $main_settings['testmode']) ? true : false;
                    if ($testmode) {
                        $publishable_key = !empty($main_settings['test_publishable_key']) ? $main_settings['test_publishable_key'] : '';
                    } else {
                        $publishable_key = !empty($main_settings['publishable_key']) ? $main_settings['publishable_key'] : '';
                    }

                    $offer_settings = Wpfnl_functions::get_offer_settings();
                    // Confirm the intent after locking the order to make sure webhooks will not interfere.
                    if ( empty( $intent->error ) ) {
                        $intent = $this->confirm_stripe_intent( $intent, $order, $order_source );
                    }

                    $response = isset($intent->charges->data) ? end( $intent->charges->data ) : false;

                    if( !empty( $response->balance_transaction ) ){
                        $order->update_meta_data( '_stripe_balance_transaction_' . $step_id, $response->balance_transaction );
                        if ( isset($offer_settings['offer_orders']) && 'main-order' === $offer_settings['offer_orders'] ) {
                            self::update_stripe_fees( $order, $response->balance_transaction );
                            $this->store_offer_transaction( $order, $response, $offer_product );
                        }
                    }
                    

                    if ( $order ) {
                        $order->update_meta_data( '_stripe_intent_id_' . $step_id, $intent->id );
                        $order->save();
                    }

                    wp_send_json(array(
                        'result'        => 'success',
                        'redirect'      => $gateway->get_return_url( $order ),
                        'intent_secret' => $intent->client_secret,
                        'stripe_pk'     => $publishable_key,
                    ));
                    
                }
            }

            wp_send_json(array(
                'result' => 'fail',
                'message' => 'No 3ds payment',
            ));
        }
    }


    /**
	 * Confirms an intent if it is the `requires_confirmation` state.
	 *
	 * @param object   $intent          The intent to confirm.
	 * @param WC_Order $order           The order that the intent is associated with.
	 * @param object   $prepared_source The source that is being charged.
	 * @return object                   Either an error or the updated intent.
     * 
     * @since 2.2.3
	 */
	public function confirm_stripe_intent( $intent, $order, $prepared_source ) {
		if ( 'requires_confirmation' !== $intent->status ) {
			return $intent;
		}

		// Try to confirm the intent & capture the charge (if 3DS is not required).
		$confirm_request = WC_Stripe_Helper::add_payment_method_to_request_array( $prepared_source->source, [] );

		$level3_data      = $this->get_level3_data_from_order( $order );
		$confirmed_intent = WC_Stripe_API::request_with_level3_data(
			$confirm_request,
			"payment_intents/$intent->id/confirm",
			$level3_data,
			$order
		);

		if ( ! empty( $confirmed_intent->error ) ) {
			return $confirmed_intent;
		}

		return $confirmed_intent;
	}

    

    /**
	 * Create the level 3 data array to send to Stripe when making a purchase.
	 *
	 * @param WC_Order $order The order that is being paid for.
	 * @return array          The level 3 data to send to Stripe.
     * 
     * @since 2.2.3
	 */
	public function get_level3_data_from_order( $order ) {
		// Get the order items. Don't need their keys, only their values.
		// Order item IDs are used as keys in the original order items array.
		$order_items = array_values( $order->get_items( [ 'line_item', 'fee' ] ) );
		$currency    = $order->get_currency();

		$stripe_line_items = array_map(
			function( $item ) use ( $currency ) {
				if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
					$product_id = $item->get_variation_id()
						? $item->get_variation_id()
						: $item->get_product_id();
					$subtotal   = $item->get_subtotal();
				} else {
					$product_id = substr( sanitize_title( $item->get_name() ), 0, 12 );
					$subtotal   = $item->get_total();
				}
				$product_description = substr( $item->get_name(), 0, 26 );
				$quantity            = $item->get_quantity();
				$unit_cost           = WC_Stripe_Helper::get_stripe_amount( ( $subtotal / $quantity ), $currency );
				$tax_amount          = WC_Stripe_Helper::get_stripe_amount( $item->get_total_tax(), $currency );
				$discount_amount     = WC_Stripe_Helper::get_stripe_amount( $subtotal - $item->get_total(), $currency );

				return (object) [
					'product_code'        => (string) $product_id, // Up to 12 characters that uniquely identify the product.
					'product_description' => $product_description, // Up to 26 characters long describing the product.
					'unit_cost'           => $unit_cost, // Cost of the product, in cents, as a non-negative integer.
					'quantity'            => $quantity, // The number of items of this type sold, as a non-negative integer.
					'tax_amount'          => $tax_amount, // The amount of tax this item had added to it, in cents, as a non-negative integer.
					'discount_amount'     => $discount_amount, // The amount an item was discounted—if there was a sale,for example, as a non-negative integer.
				];
			},
			$order_items
		);

		$level3_data = [
			'merchant_reference' => $order->get_id(), // An alphanumeric string of up to  characters in length. This unique value is assigned by the merchant to identify the order. Also known as an “Order ID”.
			'shipping_amount'    => WC_Stripe_Helper::get_stripe_amount( (float) $order->get_shipping_total() + (float) $order->get_shipping_tax(), $currency ), // The shipping cost, in cents, as a non-negative integer.
			'line_items'         => $stripe_line_items,
		];

		// The customer’s U.S. shipping ZIP code.
		$shipping_address_zip = $order->get_shipping_postcode();
		if ( $this->is_valid_us_zip_code( $shipping_address_zip ) ) {
			$level3_data['shipping_address_zip'] = $shipping_address_zip;
		}

		// The merchant’s U.S. shipping ZIP code.
		$store_postcode = get_option( 'woocommerce_store_postcode' );
		if ( $this->is_valid_us_zip_code( $store_postcode ) ) {
			$level3_data['shipping_from_zip'] = $store_postcode;
		}

		return $level3_data;
	}

    
    /** 
	 * Verifies whether a certain ZIP code is valid for the US, incl. 4-digit extensions.
     * 
	 * @param string $zip The ZIP code to verify.
	 * @return boolean
     * 
     * @since 2.2.3
	 */
	public function is_valid_us_zip_code( $zip ) {
		return ! empty( $zip ) && preg_match( '/^\d{5,5}(-\d{4,4})?$/', $zip );
	}


    /**
     * @param $order
     * @param $order_source
     * @param $product
     * @return array|\stdClass
     * @throws \WC_Stripe_Exception
     */
    public function create_intent($order, $order_source, $product)
    {
        // The request for a charge contains metadata for the intent.
        $full_request = $this->generate_payment_request($order, $order_source, $product);
        $request = [
          
            'amount' => \WC_Stripe_Helper::get_stripe_amount($product['price']),
            'currency' => strtolower($order->get_currency()),
            'description' => $full_request['description'],
            'metadata' => $full_request['metadata'],
            'statement_descriptor' => \WC_Stripe_Helper::clean_statement_descriptor($full_request['statement_descriptor']),
            'capture_method' => (isset($full_request['capture']) && 'true' === $full_request['capture']) ? 'automatic' : 'manual',
            'payment_method_types' => [
                'card',
            ],
            'customer'             => $order_source->customer,
        ];

        $request = \WC_Stripe_Helper::add_payment_method_to_request_array( $order_source->source, $request );

        if ($order_source->customer) {
            $request['customer'] = $order_source->customer;
        }
        
        if (!empty($full_request['statement_descriptor_suffix'])) {
            $request['statement_descriptor_suffix'] = $full_request['statement_descriptor_suffix'];
        }
        // Create an intent that awaits an action.
        $intent = \WC_Stripe_API::request( $request, 'payment_intents' );

        if (!empty($intent->error)) {
            $intent_id = $order->get_meta('_stripe_intent_id');
            if ( $intent_id ) {
                $intent =  $this->get_intent( 'payment_intents', $intent_id );
                if( !empty($intent->error) ){
                    return $intent;
                }
            }
        }

        $order_id = $order->get_id();

        $step_id = filter_input(INPUT_POST, 'step_id', FILTER_VALIDATE_INT);
        // Save the intent ID to the order.
        update_post_meta($order_id, '_stripe_intent_id_' . $step_id, $intent->id);
        
        return $intent;
    }

    


    /**
	 * Retrieves intent from Stripe API by intent id.
	 *
	 * @param string $intent_type   Either 'payment_intents' or 'setup_intents'.
	 * @param string $intent_id     Intent id.
	 * @return object|bool          Either the intent object or `false`.
	 * @throws Exception            Throws exception for unknown $intent_type.
	 */
	public function get_intent( $intent_type, $intent_id ) {
		if ( ! in_array( $intent_type, [ 'payment_intents', 'setup_intents' ], true ) ) {
			throw new \Exception( "Failed to get intent of type $intent_type. Type is not allowed" );
		}

		$response = \WC_Stripe_API::request( [], "$intent_type/$intent_id?expand[]=payment_method", 'GET' );

		if ( $response && isset( $response->{ 'error' } ) ) {
			return false;
		}

		return $response;
	}


    /**
     * check if token is present in the order
     *
     * @param $order
     */
    private function has_token( $order ) {
        if( false === is_a( $order, 'WC_Order' ) ){
            return false;
        }
        $token      = $order->get_meta('_wpfunnels_stripe_source_id');
        if ( empty( $token ) ) {
            $token = $order->get_meta('_stripe_source_id');
        }
        if ( ! empty( $token ) ) {
            return true;
        }
        return false;
    }

    /**
     * process the offer payment
     *
     * @param $order
     * @param $offer_product
     * @return bool
     * @throws \WC_Stripe_Exception
     */
    public function process_payment( $order, $offer_product ) {
        $result = array(
            'is_success' => false,
            'message' => ''
        );
        if ( ! $this->has_token( $order ) ) {
            return $result;
        }
        $gateway = $this->get_wc_gateway();
        $order_source = $gateway->prepare_order_source( $order );
        $response = \WC_Stripe_API::request( $this->generate_payment_request( $order, $order_source, $offer_product ) );
        
        if ( ! is_wp_error( $response ) ) {
            if ( ! empty( $response->error ) ) {
                $result['message'] = $response->error->message;
            } else {
                $result['is_success'] = true;
                $this->update_stripe_payout_details( $order, $response );
                $this->store_offer_transaction( $order, $response, $offer_product );
            }
        }
        return $result;
    }

    /**
     * generate payment request post data
     *
     * @param $order
     * @param $order_source
     * @param $product
     * @return mixed|void
     */
    public function generate_payment_request($order, $order_source, $product) {
      
        $settings = get_option( 'woocommerce_stripe_settings', [] );
        $is_short_statement_descriptor_enabled = ! empty( $settings['is_short_statement_descriptor_enabled'] ) && 'yes' === $settings['is_short_statement_descriptor_enabled'];
        $capture                               = ! empty( $settings['capture'] ) && 'yes' === $settings['capture'] ? true : false;
        $post_data = [];
        
        if ( \WC_Stripe_Helper::payment_method_allows_manual_capture( $order->get_payment_method() ) ) {
			$post_data['capture'] = $capture ? 'true' : 'false';
			if ( $is_short_statement_descriptor_enabled ) {
				$post_data['statement_descriptor_suffix'] = WC_Stripe_Helper::get_dynamic_statement_descriptor_suffix( $order );
			}
		}

        $post_data['currency'] = strtolower($order ? $order->get_currency() : get_woocommerce_currency());
        $post_data['amount'] = \WC_Stripe_Helper::get_stripe_amount($product['price'], $post_data['currency']);
        /* translators: %1s site name */
        $post_data['description'] = sprintf(__('%1$s - Order %2$s - One Time offer', 'wpfnl'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $order->get_order_number());

        /* translators: %1s order number */
        $post_data['statement_descriptor'] = apply_filters( 'wpfunnels/stripe_descriptor_text_modifiaction', sprintf(__('Order %1$s-OTO', 'wpfnl'), $order->get_order_number()), $order );
       
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();
        $billing_email = $order->get_billing_email();

        if (!empty($billing_email) && apply_filters('wc_stripe_send_stripe_receipt', false)) {
            $post_data['receipt_email'] = $billing_email;
        }

        $metadata = [
            __('customer_name', 'wpfnl') => sanitize_text_field($billing_first_name) . ' ' . sanitize_text_field($billing_last_name),
            __('customer_email', 'wpfnl') => sanitize_email($billing_email),
            'order_id' => apply_filters( 'wpfunnels/stripe_descriptor_text_modifiaction', sprintf(__('Order %1$s-OTO', 'wpfnl'), $order->get_order_number()), $order ) . '_' . $product['id'],
        ];

        $post_data['expand[]'] = 'balance_transaction';
        $post_data['metadata'] = apply_filters('wc_stripe_payment_metadata', $metadata, $order, $order_source);

        if ($order_source->customer) {
            $post_data['customer'] = $order_source->customer;
        }

        if ($order_source->source) {
            $source_3ds = $order->get_meta('_wpfunnels_stripe_source_id', true);

            $post_data['source'] = ('' !== $source_3ds) ? $source_3ds : $order_source->source;
        }
        $post_data['source'] = $order_source->source;
        return $post_data;
    }


    /**
     * @param $order
     * @param $response
     */
    public function update_stripe_payout_details($order, $response)
    {
        $fee = !empty($response->balance_transaction->fee) ? \WC_Stripe_Helper::format_balance_fee($response->balance_transaction, 'fee') : 0;
        $net = !empty($response->balance_transaction->net) ? \WC_Stripe_Helper::format_balance_fee($response->balance_transaction, 'net') : 0;

        $fee = $fee + \WC_Stripe_Helper::get_stripe_fee($order);
        $net = $net + \WC_Stripe_Helper::get_stripe_net($order);

        \WC_Stripe_Helper::update_stripe_fee($order, $fee);
        \WC_Stripe_Helper::update_stripe_net($order, $net);
    }


    /**
	 * Updates Stripe fees/net.
	 * e.g usage would be after a refund.
	 *
	 * @since 4.0.0
	 * @version 4.0.6
	 * @param object $order The order object
	 * @param int    $balance_transaction_id
	 */
	public static function update_stripe_fees( $order, $balance_transaction_id ) {
		$balance_transaction = WC_Stripe_API::retrieve( 'balance/history/' . $balance_transaction_id );

		if ( empty( $balance_transaction->error ) ) {
			if ( isset( $balance_transaction ) && isset( $balance_transaction->fee ) ) {
				// Fees and Net needs to both come from Stripe to be accurate as the returned
				// values are in the local currency of the Stripe account, not from WC.
				$fee_refund = ! empty( $balance_transaction->fee ) ? WC_Stripe_Helper::format_balance_fee( $balance_transaction, 'fee' ) : 0;
				$net_refund = ! empty( $balance_transaction->net ) ? WC_Stripe_Helper::format_balance_fee( $balance_transaction, 'net' ) : 0;

				// Current data fee & net.
				$fee_current = WC_Stripe_Helper::get_stripe_fee( $order );
				$net_current = WC_Stripe_Helper::get_stripe_net( $order );

				// Calculation.
				$fee = (float) $fee_current + (float) $fee_refund;
				$net = (float) $net_current + (float) $net_refund;
              
				WC_Stripe_Helper::update_stripe_fee( $order, $fee );
				WC_Stripe_Helper::update_stripe_net( $order, $net );

				$currency = ! empty( $balance_transaction->currency ) ? strtoupper( $balance_transaction->currency ) : null;
				WC_Stripe_Helper::update_stripe_currency( $order, $currency );

				if ( is_callable( [ $order, 'save' ] ) ) {
					$order->save();
				}
			}
		}
	}


    /**
     * @param $order
     * @param $response
     * @param $product
     */
    public function store_offer_transaction( $order, $response, $product )
    {
        $order->update_meta_data('_wpfunnels_offer_txn_resp_' . $product['step_id'], $response->id);
        $order->save();
    }


    /**
     * create child order reference with the parent order
     *
     * @param $parent_order
     * @param $product_data
     * @param string $type
     * @return bool|\WC_Order|\WP_Error
     * @throws \WC_Data_Exception
     */
    public function create_child_order( $parent_order, $product_data, $type = 'upsell' )
    {
        $order = false;

        if (!empty($parent_order)) {
            $parent_order_id = $parent_order->get_id();
            $parent_order_billing = $parent_order->get_address('billing');
            $funnel_id = $parent_order->get_meta('_wpfunnels_funnel_id');

            if (!empty($parent_order_billing['email'])) {
                $customer_id = $parent_order->get_customer_id();

                $order = wc_create_order(
                    [
                        'customer_id' => $customer_id,
                        'status' => 'wc-pending',
                        'parent' => $parent_order_id,
                    ]
                );

                /* Set Order type */
                $order->update_meta_data('_wpfunnels_offer', 'yes' );
                $order->update_meta_data('_wpfunnels_offer_type', $type );
                $order->update_meta_data('_wpfunnels_parent_funnel_id', $funnel_id);
                $order->update_meta_data('_wpfunnels_offer_step_id', $product_data['step_id'] );
                $order->update_meta_data('_wpfunnels_offer_parent_id', $parent_order_id );

                $item_id = $order->add_product(wc_get_product($product_data['id']), $product_data['qty'], $product_data['args']);

                if( $item_id ){
                    wc_add_order_item_meta( $item_id, "_wpfunnels_{$type}", 'yes' );
                    wc_add_order_item_meta( $item_id, '_wpfunnels_step_id', $product_data['step_id']);
                }
                
                $order->set_address($parent_order->get_address('billing'), 'billing');
                $order->set_address($parent_order->get_address('shipping'), 'shipping');

                // Set shipping data.
                $order->set_payment_method($parent_order->get_payment_method());
                $order->set_payment_method_title($parent_order->get_payment_method_title());

                if (!wc_tax_enabled()) {
                    // Reports won't track orders fix.
                    $order->set_shipping_tax(0);
                    $order->set_cart_tax(0);
                }

                $order->calculate_totals();

                $offer_orders_meta = $parent_order->get_meta('_wpfunnels_offer_child_orders');

                if (!is_array($offer_orders_meta)) {
                    $offer_orders_meta = [];
                }

                $offer_orders_meta[$order->get_id()] = ['type' => $type];

                $parent_order->update_meta_data('_wpfunnels_offer_child_orders', $offer_orders_meta);

                // Save the order.
                $parent_order->save();
            }
        }

        if ($order) {
            $transaction_id = $parent_order->get_transaction_id();

            $this->payment_complete($order, $transaction_id);

            $order->set_transaction_id($transaction_id);
            $order->save();

            $transaction_id_note = '';

            if (!empty($transaction_id)) {
                $transaction_id_note = sprintf(' (Transaction ID: %s)', $transaction_id);
            }

            $order->add_order_note('Offer Accepted | ' . $type . ' | Step ID - ' . $product_data['step_id'] . ' | ' . $transaction_id_note);
            return $order;
        }
        return false;
    }


    /**
     * payment_complete
     * Complete the payment 
     * 
     * @param WC_Order $order             Parent order detail
     * @param String   $transaction_id    Transaction  id.
     * 
     */

    public function payment_complete($order, $transaction_id = '')
    {
        $payment_method = $order->get_payment_method();

        if ('cod' === $payment_method) {
            $order->set_status('processing');
            wc_reduce_stock_levels($order);
        } elseif ('bacs' === $payment_method) {
            $order->set_status('on-hold');
            wc_reduce_stock_levels($order);
        } else {
            $order->payment_complete($transaction_id);
        }
    }


    /**
     * Get WooCommerce payment geteways.
     *
     * @return array
     */
    public function get_wc_gateway() {

        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();

        return $gateways[ $this->key ];
    }


    /**
     * process refund offer
     *
     * @param $order
     * @param $data
     * @return bool
     * @throws \WC_Stripe_Exception
     */
    public function process_refund_offer( $order, $data ) {

        $transaction_id = $data['transaction_id'];
        $amount         = $data['amount'];
        $currency       = $order->get_currency( $order );

        $request     = array();
        $response_id = false;

        if ( ! is_null( $amount ) && class_exists( 'WC_Stripe_Helper' ) ) {
            $request['amount'] = WC_Stripe_Helper::get_stripe_amount( $amount, $currency );
        }
        if ( ! is_null( $amount ) && class_exists( 'WC_Stripe_API' ) ) {
            $request['charge'] = $transaction_id;
            $response          = WC_Stripe_API::request( $request, 'refunds' );
            if ( ! empty( $response->error ) || ! $response ) {
                $response_id = false;
            } else {
                $this->get_wc_gateway()->update_fees( $order, $response->balance_transaction );
                $response_id = isset( $response->id ) ? $response->id : true;
            }
        }
        return $response_id;
    }


    /**
     * add required meta for refund
     *
     * @param $parent_order
     * @param $child_order
     * @param $transaction_id
     */
    public function add_capture_meta_to_child_order( $parent_order, $child_order, $transaction_id ) {
        $child_order->update_meta_data("_stripe_charge_captured", "yes");

        $stripe_source_id   = $parent_order->get_meta( '_stripe_source_id', true );
        $stripe_customer_id = $parent_order->get_meta( '_stripe_customer_id', true );
        if (function_exists("wcs_order_contains_subscription") && wcs_order_contains_subscription($child_order)) {
            $subscription = wcs_get_subscriptions_for_order($child_order->get_id());
            if (!empty($subscription)) {
                foreach ( $subscription as $sub ) {
                    $sub->update_meta_data( '_stripe_source_id', $stripe_source_id );
                    $sub->update_meta_data( '_stripe_customer_id', $stripe_customer_id );
                    $sub->save();
                }
            }
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
        if ( 'stripe' === $order->get_payment_method() ) {
            // Use CRUD methods instead of update_post_meta().
            $stripe_source_id   = $order->get_meta( '_stripe_source_id', true );
            $stripe_customer_id = $order->get_meta( '_stripe_customer_id', true );

            if ( $stripe_source_id ) {
                $subscription->update_meta_data( '_stripe_source_id', $stripe_source_id );
            }

            if ( $stripe_customer_id ) {
                $subscription->update_meta_data( '_stripe_customer_id', $stripe_customer_id );
            }

            // Save the subscription after updating meta.
            $subscription->save();
        }
    }


    /**
     * Add hidden field for stripe
     * 
     * @return void
     * @since 2.2.3
     */
    public function add_stripe_hidden_field(){
        // Get funnel checkout ID from post data.
        $checkout_id = Wpfnl_functions::get_checkout_id_from_post($_POST);

        // Get checkout id if not found in post data.
        $checkout_id = !$checkout_id ? get_the_ID() : $checkout_id;

        // Get funnel ID from checkout ID.
        $funnel_id   = Wpfnl_functions::get_funnel_id_from_step( $checkout_id );

        // Check if checkout ID and funnel ID exists.
        if ( $checkout_id && $funnel_id ) {
            // Check if offer exists in funnel.
            if ( Wpfnl_functions::is_offer_exists_in_funnel($funnel_id) ) {
                // Add hidden field for stripe.
                echo '<input type="hidden" name="wc-stripe-new-payment-method" value="1">';
            }
        }
    }
}
