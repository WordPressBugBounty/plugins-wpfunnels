<?php
namespace WPFunnels\Gateway;
/**
 * @author [Saiduzzaman Tohin]
 * @email [tohin@coderex.co]
 * @create date 2022-05-26 11:12:24
 * @modify date 2022-05-26 11:12:24
 * @desc [Supporting PayFast Payment Gateway with WPFunnels one-click offer]
 */

use WPFunnels\Wpfnl_functions;

class Wpfnl_Gateway_PayFast {
    
    public $key = 'payfast';

    function __construct()
    {
		// Convert offer product to subscription type
        add_filter('woocommerce_gateway_payfast_payment_data_to_send', array( $this, 'make_subscription_type_true' ), 10 ,2);
		// Store token for offer product
        add_action('woocommerce_payfast_handle_itn_payment_complete', array($this, 'store_tokenization_on_meta_post'), 10, 3);        
    }


    /**
     * Making a product as subscription type
     * @param mixed $result 
     * @param mixed $order
     * 
     * @return bool
	 * @since 1.3.3
     */
    public function make_subscription_type_true($result, $order_id)
    {
		
        $checkout_id = Wpfnl_functions::get_checkout_id_from_order($order_id);
        $funnel_id   = Wpfnl_functions::get_funnel_id_from_step( $checkout_id );
        if($checkout_id && $funnel_id && $order_id) {
            $result['subscription_type'] = '2';
        }
        return apply_filters( 'wpfunnels/maybe_enable_payfast_tokenization', $result);
    }


    /**
     * Store the PayFast tokenization information
     * 
     * @param mixed $data payfast payment information
     * @param mixed $order_id order id
     * 
     * @return void
	 * @since 1.3.3
     */
    public function store_tokenization_on_meta_post($data, $order, $subscriptions)
    {
		$order_id = $order->get_id();
        $token = sanitize_text_field( $data['token'] );
        $merchant_id = sanitize_text_field( $data['merchant_id'] );
        $this->_set_order_token($token, $order);
        $this->_set_order_merchant_d($merchant_id, $order);
		
    }


    /**
	 * Store the PayFast token
	 *
	 * @param string   $token
	 * @param WC_Order $order
	 */
	protected function _set_order_token( $token, $order ) {
		update_post_meta( $order->get_id(), '_payfast_subscription_token', $token );
        $order->update_meta_data('_payfast_subscription_token', $token );

	}

	
    /**
	 * Store the PayFast Merchant ID
	 *
	 * @param string   $merchant_id
	 * @param WC_Order $order
	 */
	protected function _set_order_merchant_d( $merchant_id, $order ) {
		update_post_meta( $order->get_id(), '_payfast_merchant_id', $merchant_id );
        $order->update_meta_data('_payfast_merchant_id', $merchant_id );
	}


    /**
	 * Retrieve the PayFast token for a given order id.
	 *
	 * @param WC_Order $order
	 * @return mixed
	 */
	protected function _get_order_token( $order ) {
		return $order->get_meta('_payfast_subscription_token' ) ? $order->get_meta('_payfast_subscription_token' ) : get_post_meta( $order->get_id(), '_payfast_subscription_token', true );
	}


	/**
	 * Retrieve the PayFast merchant ID for a given order id.
	 *
	 * @param WC_Order $order
	 * @return mixed
	 */
	protected function _get_order_merchant_id( $order ) {
		return $order->get_meta('_payfast_merchant_id' ) ? $order->get_meta('_payfast_merchant_id' ) :  get_post_meta( $order->get_id(), '_payfast_merchant_id', true );
	}



	/**
	 * Getting woocommerce payfast settings information
	 * @param mixed $key payment setting key
	 * 
	 * @return mixed
	 * @since 1.3.3
	 */
	public function get_payfast_settings($key)
	{
		$payfast_settings = get_option('woocommerce_payfast_settings');
		return isset($payfast_settings[$key]) ? $payfast_settings[$key] : '';
	}


	/**
     * Processing of the offer payment
     *
     * @param $order
     * @return bool|WP_Error
	 * 
	 * @since 1.3.3
     */
    public function process_payment( $order )
    {
        $result = array(
            'is_success' => false,
            'message' => ''
        );
        if ( ! $this->has_token( $order ) ) {
            return $result;
        }

		// Get Token from  the database
        $token = $this->_get_order_token( $order );
        $step_id       = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $product_id    = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : '';
        $order_id      = isset( $_POST['order_id'] ) ? intval($_POST['order_id']) : 0;

		// Get Offer Product Information
        $offer_product = Wpfnl_functions::get_offer_product_data( $step_id, $product_id, 0, $order_id );
        if ( isset($offer_product['price']) && (floatval(0) === floatval( $offer_product['price'] ) || '' === trim($offer_product['price'])) ) {
            wp_send_json(array(
                'result'    => 'fail',
                'message'   => __('Product price is less than 0', 'wpfnl-pro'),
            ));
        } else {
			// Submition of tokenization offer product payment
            $response =  $this->submit_tokenization_payment($token, $offer_product, $order);
			// Success response check
            if($response){
				return array(
								'is_success' => true,
								'message' => 'Success'
							);
			}

			// Error response check
            if ( is_wp_error( $response ) ) {
				$result = array(
					'is_success' => false,
					'message' => sprintf( __( 'PayFast Pre-Order payment transaction failed (%1$s:%2$s)', 'woocommerce-gateway-payfast' ), $response->get_error_code() ,$response->get_error_message() )
				);
                
                return $result;
            }
                        
        }

    }



    /**
	 * Submission of offer product  tokenization payment
	 * 
     * @param mixed $token
     * @param mixed $offer_product
     * @param mixed $order
     * 
     * @return bool|WP_Error
	 * @since 1.3.3
     */
    public function submit_tokenization_payment($token, $offer_product, $order)
    {
        $price 		= (int)$offer_product['price'];
        $product_id = (int)$offer_product['id'];
        $product 	= Wpfnl_functions::is_wc_active() ? wc_get_product( $product_id ) : null;
		$description = '';
		if( $product ){
			if ( $product->get_type() == 'variation' ) {
				$description = $product->get_description();     
			} else {
				$description = $product->get_short_description();       
			}
		}
		
		$merchant_id = $this->get_payfast_settings( 'merchant_id' );
        $args = array(
			'body' => array(
				'amount'           => $price * 100, // convert to cents
				'item_name'        => $offer_product['name'],
				'item_description' => $description,
			),
		);
		return $this->api_request( 'adhoc', $token, $args, $merchant_id );
    }



    /**
     * Check if token is present in the order
     *
     * @param $order
	 * @since 1.3.3
     */
    private function has_token( $order ) {
        $token = $order->get_meta('_payfast_subscription_token');

        if ( empty( $token ) ) {
            $token = get_post_meta($order->get_id(), '_payfast_subscription_token', true);
        }
        if ( ! empty( $token ) ) {
            return true;
        }
        return false;
    }

    /**
	 * Send off API request.
	 *
	 * @param $command
	 * @param $token
	 * @param $api_args
	 * @param string $method POST
	 *
	 * @return bool|WP_Error
	 * @since 1.3.3
	 */
	public function api_request( $command, $token, $api_args, $merchant_id, $method = 'POST' ) {
		if ( empty( $token ) ) {
			$this->log( "Error posting API request: No token supplied", true );
			return new \WP_Error( '404', __( 'Can not submit PayFast request with an empty token', 'woocommerce-gateway-payfast' ), $results );
		}
		$settings = get_option( 'woocommerce_payfast_settings', [] );
		$api_endpoint  = "https://api.payfast.co.za/subscriptions/$token/$command";
		$api_endpoint .= ( isset($settings['testmode']) && 'yes' === $settings['testmode'] ) ? '?testing=true' : '';
		$timestamp = current_time( rtrim( \DateTime::ATOM, 'P' ) ) . '+02:00';
		$api_args['timeout'] = 45;
		$api_args['headers'] = array(
			'merchant-id' => $merchant_id,
			'timestamp'   => $timestamp,
			'version'     => 'v1',
		);

		// generate signature
		$all_api_variables                = array_merge( $api_args['headers'], (array) $api_args['body'] );
		$api_args['headers']['signature'] = md5( $this->_generate_parameter_string( $all_api_variables ) );
		$api_args['method']               = strtoupper( $method );

		$results = wp_remote_request( $api_endpoint, $api_args );
		
		// Check PayFast server response
		if ( 200 !== $results['response']['code'] ) {
			$this->log( "Error posting API request:\n" . print_r( $results['response'], true ) );
			return new \WP_Error( $results['response']['code'], json_decode( $results['body'] )->data->response, $results );
		}

		// Check adhoc bank charge response
		$results_data = json_decode( $results['body'], true )['data'];

		if ( $command == 'adhoc' && (true !== $results_data['response'] && 'true' !== $results_data['response']) ) {

			$this->log( "Error posting API request:\n" . print_r( $results_data , true ) );

			$code         = is_array( $results_data['response'] ) ? $results_data['response']['code'] : $results_data['response'];
			$message      = is_array( $results_data['response'] ) ? $results_data['response']['reason'] : $results_data['message'];
			// Use trim here to display it properly e.g. on an order note, since PayFast can include CRLF in a message.
			return new \WP_Error( $code, trim( $message ), $results );
		}

		$maybe_json = json_decode( $results['body'], true );

		if ( ! is_null( $maybe_json ) && isset( $maybe_json['status'] ) && 'failed' === $maybe_json['status'] ) {
			$this->log( "Error posting API request:\n" . print_r( $results['body'], true ) );

			// Use trim here to display it properly e.g. on an order note, since PayFast can include CRLF in a message.
			return new \WP_Error( $maybe_json['code'], trim( $maybe_json['data']['message'] ), $results['body'] );
		}

		return true;
	}



    /**
	 * Generate signature for api request
	 * 
	 * @param      $api_data
	 * @param bool $sort_data_before_merge? default true.
	 * @param bool $skip_empty_values Should key value pairs be ignored when generating signature?  Default true.
	 *
	 * @return string
	 * @since 1.3.3
	 */
	protected function _generate_parameter_string( $api_data, $sort_data_before_merge = true, $skip_empty_values = true ) {
		
		// if sorting is required the passphrase should be added in before sort.
		if ( ! empty( $this->get_payfast_settings('pass_phrase') ) && $sort_data_before_merge ) {
			$api_data['passphrase'] = $this->get_payfast_settings('pass_phrase');
		}

		if ( $sort_data_before_merge ) {
			ksort( $api_data );
		}

		// concatenate the array key value pairs.
		$parameter_string = '';
		foreach ( $api_data as $key => $val ) {

			if ( $skip_empty_values && empty( $val ) ) {
				continue;
			}

			if ( 'signature' !== $key ) {
				$val = urlencode( $val );
				$parameter_string .= "$key=$val&";
			}
		}
		// when not sorting passphrase should be added to the end before md5
		if ( $sort_data_before_merge ) {
			$parameter_string = rtrim( $parameter_string, '&' );
		} elseif ( ! empty( $this->get_payfast_settings('pass_phrase') ) ) {
			$parameter_string .= 'passphrase=' . urlencode( $this->get_payfast_settings('pass_phrase') );
		} else {
			$parameter_string = rtrim( $parameter_string, '&' );
		}

		return $parameter_string;
	}


    /**
	 * Log system processes.
	 * @since 1.3.3
	 */
	public function log( $message ) {
		if ( 'yes' === 'yes' || $this->enable_logging ) {
			if ( empty( $this->logger ) ) {
				$this->logger = new \WC_Logger();
			}
			$this->logger->add( 'payfast', $message );
		}
	}

}