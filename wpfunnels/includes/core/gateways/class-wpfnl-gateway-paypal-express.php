<?php
namespace WPFunnels\Gateway;

use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Gateway\API\Wpfnl_Gateway;
use WPFunnels\Gateway\Exception\Wpfnl_Payment_Gateway_Exception;

class Wpfnl_Gateway_Paypal_Express extends Wpfnl_Gateway {

    /**
     * Live API url
     */
    const PRODUCTION_ENDPOINT = 'https://api-3t.paypal.com/nvp';

    /**
     * Sandbox api url
     */
    const SANDBOX_ENDPOINT = 'https://api-3t.sandbox.paypal.com/nvp';

    protected $key = 'ppec_paypal';

    /**
     * The request parameters
     *
     * @var array
     */
    public $parameters = array();

    public $response_params = array();


    /**
     * List of locales supported by PayPal.
     *
     * @var array
     */
    protected $_supported_locales = array(
        'da_DK',
        'de_DE',
        'en_AU',
        'en_GB',
        'en_US',
        'es_ES',
        'fr_CA',
        'fr_FR',
        'he_IL',
        'id_ID',
        'it_IT',
        'ja_JP',
        'nl_NL',
        'no_NO',
        'pl_PL',
        'pt_BR',
        'pt_PT',
        'ru_RU',
        'sv_SE',
        'th_TH',
        'tr_TR',
        'zh_CN',
        'zh_HK',
        'zh_TW',
    );

    public function __construct() {
        add_filter( 'woocommerce_paypal_express_checkout_request_body', array( $this, 'modify_request_body' ), 999 );
        add_action( 'wp_enqueue_scripts', array( $this, 'load_payment_scripts' ), 9999 );
        // add_filter( 'woocommerce_paypal_refund_request', array( $this, 'wpfnl_offer_refund_request' ), 10, 3 );
    }


    /**
     * modify paypal express request body params
     *
     * @param $request_body
     * @return mixed
     *
     * @since 1.0.0
     */
    public function modify_request_body( $data ) {

        if(!$this->is_enabled()) return $data;

        


        if ( true === $this->is_reference_transaction_enabled() ) {

			// // translators: blog name.
			// $description = sprintf( _x( 'Orders with %s', 'data sent to PayPal', 'wpfunnels' ), get_bloginfo( 'name' ) );

			// $description = html_entity_decode( $description, ENT_NOQUOTES, 'UTF-8' );

			if ( $data && isset( $data['METHOD'] ) && 'SetExpressCheckout' == $data['METHOD'] && ! isset( $data['L_BILLINGTYPE0'] ) ) {
				$data['RETURNURL']                      = add_query_arg( array( 'create-billing-agreement' => true ), $data['RETURNURL'] );
				$data['L_BILLINGTYPE0']                 = 'MerchantInitiatedBillingSingleAgreement';
				$data['L_BILLINGAGREEMENTDESCRIPTION0'] = $this->get_billing_agreement_description();
				$data['L_BILLINGAGREEMENTCUSTOM0']      = '';
			}

			if ( $data && isset( $data['METHOD'] ) && 'DoReferenceTransaction' == $data['METHOD'] ) {

				$step_id  = isset( $_POST['step_id'] ) ? (int) $_POST['step_id'] : 0;
				$order_id = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;

				// Return if the step  id is not in the query string.
				if ( $step_id < 1 ) {
					return $data;
				}

				// Return if the order id is not in the query string.
				if ( $order_id < 1 ) {
					return $data;
				}

				$order         = wc_get_order( $order_id );
				$variation_id  = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : '';
				$input_qty     = isset( $_POST['input_qty'] ) ? intval( $_POST['input_qty'] ) : '';
				$offer_package = Wpfnl_functions::get_offer_product_data($step_id);

				$data['AMT']     = $offer_package['total'];
				$data['ITEMAMT'] = $offer_package['total'];

				// shippingamt shoud be 0.
				if ( ( isset( $offer_package['shipping'] ) && isset( $offer_package['shipping']['diff'] ) ) && 0 < $offer_package['shipping']['diff'] ) {
					$data['SHIPPINGAMT'] = 0;
					$data['SHIPDISCAMT'] = ( isset( $offer_package['shipping'] ) && isset( $offer_package['shipping']['diff'] ) ) ? $offer_package['shipping']['diff']['cost'] : 0;

				} else {
					$data['SHIPPINGAMT'] = ( isset( $offer_package['shipping'] ) && isset( $offer_package['shipping']['diff'] ) ) ? $offer_package['shipping']['diff']['cost'] : 0;
					$data['SHIPDISCAMT'] = 0;
				}

				$data['TAXAMT']       = ( isset( $offer_package['taxes'] ) ) ? $offer_package['taxes'] : 0;
				$data['INVNUM']       = 'WC-' . $order_id . '_' . $step_id;
				$data['INSURANCEAMT'] = 0;
				$data['HANDLINGAMT']  = 0;
				$data                 = $this->remove_previous_line_items( $data );

				$data['L_NAME0'] = $offer_package['name'];
				$data['L_DESC0'] = $offer_package['desc'];
				$data['L_AMT0']  = wc_format_decimal( $offer_package['unit_price_tax'], 2 );
				$data['L_QTY0']  = $offer_package['qty'];

				$item_amt = $offer_package['total'];

				$data['ITEMAMT'] = $item_amt;
			}

			if ( isset( $data['METHOD'] ) && 'DoExpressCheckoutPayment' == $data['METHOD'] ) {

				if ( isset( $data['PAYMENTREQUEST_0_CUSTOM'] ) ) {
					$get_custom_attrs = json_decode( $data['PAYMENTREQUEST_0_CUSTOM'] );
					if ( isset( $get_custom_attrs->order_id ) ) {
						$get_order = wc_get_order( $get_custom_attrs->order_id );

						try {
							$checkout         = wc_gateway_ppec()->checkout;
							$checkout_details = $checkout->get_checkout_details( $data['TOKEN'] );

							$checkout->create_billing_agreement( $get_order, $checkout_details );

							$token = $get_order->get_meta( '_ppec_billing_agreement_id' );

							if ( ! empty( $token ) ) {

								// Saving meta by our own.
								$get_order->update_meta_data('_ppec_billing_agreement_id', $token );
							}
						} catch ( \Exception $e ) {
							print_r($e->getMessage());
						}
					}
				}
			}
		}

        return $data;
    }


    /**
	 * Remove line items
	 *
	 * @since 1.0.0
	 *
	 * @param array $array object.
	 *
	 * @return array
	 */
	public function remove_previous_line_items( $array ) {

		if ( is_array( $array ) && count( $array ) > 0 ) {
			foreach ( $array as $key => $val ) {
				if ( false !== strpos( strtoupper( $key ), 'L_' ) ) {
					unset( $array[ $key ] );
				}
			}
		}

		return $array;
	}


    /**
	 * Get billing agreement description to be passed to PayPal.
	 *
	 * @return string Billing agreement description
	 * @since 1.0.0
	 *
	 */
	protected function get_billing_agreement_description() {
		/* translators: placeholder is blogname */
		$description = sprintf( _x( 'Orders with %s', 'data sent to PayPal', 'wpfunnels' ), get_bloginfo( 'name' ) );

		if ( strlen( $description ) > 127 ) {
			$description = substr( $description, 0, 124 ) . '...';
		}

		return html_entity_decode( $description, ENT_NOQUOTES, 'UTF-8' );
	}



    /**
     * check if paypal scripts should load or not
     *
     * @return bool
     *
     * @since 1.0.0
     */
    private function may_be_load_paypal_scripts() {
       
        if ( (Wpfnl_functions::check_if_this_is_step_type('upsell') || Wpfnl_functions::check_if_this_is_step_type('downsell')) && $this->has_paypal_gateway()) {
            return true;
        }
        return false;
    }


    /**
     * load paypal payment js
     */
    public function load_payment_scripts() {
        
        if ( $this->may_be_load_paypal_scripts() ) {
            wp_enqueue_script(
                'wpfnl-paypal-express-script',
                'https://www.paypalobjects.com/api/checkout.js',
                array( 'jquery' ),
                WPFNL_PRO_VERSION,
                true
            );
            $script = $this->paypal_script();
            wp_add_inline_script( 'wpfnl-paypal-express-script', $script );
        }
    }

    /**
     * paypal script
     *
     */
    public function paypal_script(){

        ob_start();
        ?>

        (function($){ $( function($) {
            var $wpfnl_paypal_checkout = {
                init: function () {
                    var getButtons = [
                        'upsell_funnel_target',
                    ];
                    window.paypalCheckoutReady = function () {
                        paypal.checkout.setup(
                            '<?php echo esc_js($this->get_payer_id()); ?>',
                            {
                                environment: 'sandbox',
                                buttons: getButtons,
                                container: 'myContainer',
                                locale: '<?php echo esc_js( $this->get_paypal_locale() ); ?>',
                                click: function () {
                                    var variation_id = 0;
                                    var postData = {
                                        step_id: window.WPFunnelsOfferVars.step_id,
                                        funnel_id: window.WPFunnelsOfferVars.funnel_id,
                                        order_id: window.WPFunnelsOfferVars.order_id,
                                        order_key: window.WPFunnelsOfferVars.order_key,
                                        variation_id: 0,
                                        input_qty: 0,
                                        action: 'wpfunnels_create_paypal_express_checkout_token'
                                    };

                                    paypal.checkout.initXO();
                                    var action = $.post(window.WPFunnelsOfferVars.ajaxUrl, postData);
                                    
                                    action.done(function (data) {
                                        paypal.checkout.startFlow(data.token);
                                    });

                                    action.fail(function () {
                                        paypal.checkout.closeFlow();
                                    });
                                }
                            });
                    }
                }
            };

            $wpfnl_paypal_checkout.init();
        });
        })(jQuery);
        <?php return ob_get_clean();
    }


    /**
     * Get locale data for PayPal.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_paypal_locale() {
        $locale = get_locale();
        if ( ! in_array( $locale, $this->_supported_locales, true ) ) {
            $locale = 'en_US';
        }

        return $locale;
    }

    /**
     * check id reference transaction is enabled
     *
     * @return bool
     */
    public function is_reference_transaction_enabled() {
        return false;
    }


    /**
     * check if api creds are saved or not
     *
     * @return bool
     */
    public function has_api_credentials_set() {
        $credentials_are_set = false;
        $environment = ('sandbox' === $this->get_wc_gateway()->environment) ? 'sandbox' : 'production';

        $api_creds_prefix = '';
        if ('sandbox' === $environment) {
            $api_creds_prefix = 'sandbox_';
        }

        if ('' !== $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_username') && '' !== $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_password') && '' !== $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_signature')) {
            $credentials_are_set = true;
        }

        return $credentials_are_set;
    }


    /**
     * Check if current order has paypal gatway
     *
     * @return bool
     */
    public function has_paypal_gateway() {

        $order_id = isset( $_GET['wpfnl-order'] ) ? $_GET['wpfnl-order'] : '';

        if ( empty( $order_id ) ) {
            return false;
        }

        $order   = wc_get_order( $order_id );

        $gateway = $order->get_payment_method();

        if ( $this->get_key() === $gateway ) {
            return true;
        }

        return false;
    }


    public function process_payment( $offer_product, $order_id, $order_key ) {

    }

    /**
     * get payer id
     *
     * @return bool|mixed|void
     */
    public function get_payer_id() {
        $environment = ('sandbox' === $this->get_wc_gateway()->environment) ? 'sandbox' : 'production';

        $api_creds_prefix = '';
        if ('sandbox' === $environment) {
            $api_creds_prefix = 'sandbox_';
        }

        $option_key = 'woocommerce_ppec_payer_id_' . $environment . '_' . md5($this->get_wc_gateway()->get_option($api_creds_prefix . 'api_username') . ':' . $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_password'));
        $payer_id = get_option($option_key);
        if ($payer_id) {
            return $payer_id;
        } else {
            $result = $this->get_pal_details();

            if (!empty($result['PAL'])) {
                update_option($option_key, wc_clean($result['PAL']));
                return $payer_id;
            }
        }

        return false;
    }


    /**
     * get the paypal id, including the merchant account number
     *
     * @return object|API\SV_WC_API_Response
     */
    public function get_pal_details() {

        $environment = ('sandbox' === $this->get_wc_gateway()->environment) ? 'sandbox' : 'production';
        $api_creds_prefix = '';
        if ('sandbox' === $environment) {
            $api_creds_prefix = 'sandbox_';
        }
        $this->set_api_credentials(
                $this->get_key(),
                $environment,
                $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_username'),
                $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_password'),
                $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_signature')
        );
        $this->add_parameter('METHOD', 'GetPalDetails');
        $this->set_credentials_params(
                $this->api_username,
                $this->api_password,
                $this->api_signature,
                124
        );


        $request = new \stdClass();
        $request->path = '';
        $request->method = 'POST';
        $request->body = $this->to_string();
        return $this->perform_request($request);

    }


    /**
     * Create express checkout token ajax action.
     * It will return checkout token for express checkout
     */
    public function create_express_checkout_token() {

        $step_id      = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $funnel_id    = isset( $_POST['funnel_id'] ) ? intval( $_POST['funnel_id'] ) : 0;
        $order_id     = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $order_key    = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';
        $session_key  = isset( $_POST['session_key'] ) ? sanitize_text_field( wp_unslash( $_POST['session_key'] ) ) : '';
        $variation_id = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : '';
        $input_qty    = isset( $_POST['input_qty'] ) ? intval( $_POST['input_qty'] ) : '';

        $is_valid_order = true;

        if ( $is_valid_order ) {
            $order = wc_get_order( $order_id );
            $response = $this->init_express_checkout(
                array(
                    'currency'     => $order ? $order->get_currency() : get_woocommerce_currency(),
                    'return_url' => $this->get_callback_url(
                        array(
                            'action'    => 'wpfunnels_paypal_express_return',
                            'step_id'   => $step_id,
                            'order_id'  => $order->get_id(),
                            'funnel_id' => $funnel_id,
                            'order_key' => $order_key,
                        )
                    ),
                    'cancel_url' => $this->get_callback_url(
                        array(
                            'action'    => 'wpfunnels_paypal_express_cancel',
                            'step_id'   => $step_id,
                            'funnel_id' => $funnel_id,
                            'order_id'  => $order->get_id(),
                        )
                    ),
                    'notify_url'   => $this->get_callback_url( 'wpfunnels_notify_url' ),
                    'order'        => $order,
                    'step_id'      => $step_id,
                    'variation_id' => $variation_id,
                    'input_qty'    => $input_qty,
                ),
                true
            );

            
            if ( isset( $response['TOKEN'] ) && '' !== $response['TOKEN'] ) {
                wp_send_json(
                    array(
                        'result' => 'success',
                        'token'  => $response['TOKEN'],
                    )
                );
            }
        }

        wp_send_json(
            array(
                'result'   => 'error',
                'response' => $response,
            )
        );
    }


    /**
     * Initiate express checkout request
     *
     * @param $args
     * @param bool $is_upsell
     * @return object|API\SV_WC_API_Response
     */
    public function init_express_checkout( $args, $is_upsell = false ) {

        $environment = ('sandbox' === $this->get_wc_gateway()->environment) ? 'sandbox' : 'production';
        
        $api_creds_prefix = '';
        if ('sandbox' === $environment) {
            $api_creds_prefix = 'sandbox_';
        }

        $this->set_api_credentials(
            $this->key,
            $environment,
            $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_username'),
            $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_password'),
            $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_signature')
        );

        $this->set_express_checkout_args( $args, $is_upsell );

        $this->set_credentials_params(
            $this->api_username,
            $this->api_password,
            $this->api_signature,
            124
        );
        $request = new \stdClass();
        $request->path = '';
        $request->method = 'POST';
        $request->body = $this->to_string();
        
        WC()->session->set( 'paypal_request' , $this->get_parameters() );

        return $this->perform_request($request);
    }


    /**
     * Sets up DoExpressCheckoutPayment API Call arguments
     *
     * @param string $token Unique token of the payment initiated
     * @param \WC_Order $order
     * @param array $args
     */
    public function set_do_express_checkout_args($token, $order, $args)
    {
        $this->set_method('DoExpressCheckoutPayment');

        // set base params
        $this->add_parameters(array(
            'TOKEN' => $token,
            'PAYERID' => $args['payer_id'],
            'BUTTONSOURCE' => 'WooThemes_Cart',
            'RETURNFMFDETAILS' => 1,
        ));

        $this->add_payment_details_parameters($order, $args['payment_action']);
    }


    /**
     * Do express checkout
     *
     * @param $token
     * @param $order
     * @param $args
     * @return object|string
     *
     * @since 1.0.0
     */
    private function do_express_checkout($token, $order, $args) {
        $environment = ('sandbox' === $this->get_wc_gateway()->environment) ? 'sandbox' : 'production';

        $api_creds_prefix = '';
        if ('sandbox' === $environment) {
            $api_creds_prefix = 'sandbox_';
        }

        $this->set_api_credentials(
            $this->key,
            $environment,
            $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_username'),
            $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_password'),
            $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_signature')
        );

        $this->set_do_express_checkout_args($token, $order, $args);

        $this->set_credentials_params(
            $this->api_username,
            $this->api_password,
            $this->api_signature,
            124
        );
        $request = new \stdClass();
        $request->path = '';
        $request->method = 'POST';
        $request->body = $this->to_string();
        return $this->perform_request($request);
    }


    /**
     * Sets up the API credentials for API request
     *
     * @param $gateway_id
     * @param $api_environment
     * @param $api_username
     * @param $api_password
     * @param $api_signature
     *
     * @since 1.0.0
     */
    private function set_api_credentials( $gateway_id, $api_environment, $api_username, $api_password, $api_signature ) {
        // tie API to gateway
        $this->gateway_id = $gateway_id;

        // request URI does not vary per-request
        $this->request_uri = ('production' === $api_environment) ? self::PRODUCTION_ENDPOINT :  self::SANDBOX_ENDPOINT;

        // PayPal requires HTTP 1.1
        $this->request_http_version = '1.1';

        $this->api_username = $api_username;
        $this->api_password = $api_password;
        $this->api_signature = $api_signature;
    }


    /**
     * Sets up the express checkout transaction
     *
     * @link https://developer.paypal.com/docs/classic/express-checkout/integration-guide/ECGettingStarted/#id084RN060BPF
     * @link https://developer.paypal.com/webapps/developer/docs/classic/api/merchant/SetExpressCheckout_API_Operation_NVP/
     *
     * @param array $args {
     *     @type string 'currency'              (Optional) A 3-character currency code (default is store's currency).
     *     @type string 'billing_type'          (Optional) Type of billing agreement for reference transactions. You must have permission from PayPal to use this field. This field must be set to one of the following values: MerchantInitiatedBilling - PayPal creates a billing agreement for each transaction associated with buyer. You must specify version 54.0 or higher to use this option; MerchantInitiatedBillingSingleAgreement - PayPal creates a single billing agreement for all transactions associated with buyer. Use this value unless you need per-transaction billing agreements. You must specify version 58.0 or higher to use this option.
     *     @type string 'billing_description'   (Optional) Description of goods or services associated with the billing agreement. This field is required for each recurring payment billing agreement if using MerchantInitiatedBilling as the billing type, that means you can use a different agreement for each subscription/order. PayPal recommends that the description contain a brief summary of the billing agreement terms and conditions (but this only makes sense when the billing type is MerchantInitiatedBilling, otherwise the terms will be incorrectly displayed for all agreements). For example, buyer is billed at "9.99 per month for 2 years".
     *     @type string 'maximum_amount'        (Optional) The expected maximum total amount of the complete order and future payments, including shipping cost and tax charges. If you pass the expected average transaction amount (default 25.00). PayPal uses this value to validate the buyer's funding source.
     *     @type string 'no_shipping'           (Optional) Determines where or not PayPal displays shipping address fields on the PayPal pages. For digital goods, this field is required, and you must set it to 1. It is one of the following values: 0 – PayPal displays the shipping address on the PayPal pages; 1 – PayPal does not display shipping address fields whatsoever (default); 2 – If you do not pass the shipping address, PayPal obtains it from the buyer's account profile.
     *     @type string 'page_style'            (Optional) Name of the Custom Payment Page Style for payment pages associated with this button or link. It corresponds to the HTML variable page_style for customizing payment pages. It is the same name as the Page Style Name you chose to add or edit the page style in your PayPal Account profile.
     *     @type string 'brand_name'            (Optional) A label that overrides the business name in the PayPal account on the PayPal hosted checkout pages. Default: store name.
     *     @type string 'landing_page'          (Optional) Type of PayPal page to display. It is one of the following values: 'login' – PayPal account login (default); 'Billing' – Non-PayPal account.
     *     @type string 'payment_action'        (Optional) How you want to obtain payment. If the transaction does not include a one-time purchase, this field is ignored. Default 'Sale' – This is a final sale for which you are requesting payment (default). Alternative: 'Authorization' – This payment is a basic authorization subject to settlement with PayPal Authorization and Capture. You cannot set this field to Sale in SetExpressCheckout request and then change the value to Authorization or Order in the DoExpressCheckoutPayment request. If you set the field to Authorization or Order in SetExpressCheckout, you may set the field to Sale.
     *     @type string 'return_url'            (Required) URL to which the buyer's browser is returned after choosing to pay with PayPal.
     *     @type string 'cancel_url'            (Required) URL to which the buyer is returned if the buyer does not approve the use of PayPal to pay you.
     *     @type string 'custom'                (Optional) A free-form field for up to 256 single-byte alphanumeric characters
     * }
     * @since 1.0.0
     * @source https://github.com/wp-premium/woocommerce-subscriptions/blob/master/includes/gateways/paypal/includes/class-wcs-paypal-reference-transaction-api-request.php#L68
     */
    public function set_express_checkout_args( $args, $is_upsell = false ) {

        // translators: placeholder is blogname
        $default_description = sprintf( _x( 'Orders with %s', 'data sent to paypal', 'wpfnl-pro' ), get_bloginfo( 'name' ) );

        $defaults = array(
            'currency'            => get_woocommerce_currency(),
            'billing_type'        => 'MerchantInitiatedBillingSingleAgreement',
            // translators: placeholder is for blog name
            'billing_description' => html_entity_decode( apply_filters( 'woocommerce_subscriptions_paypal_billing_agreement_description', $default_description, $args ), ENT_NOQUOTES, 'UTF-8' ),
            'maximum_amount'      => null,
            'no_shipping'         => 1,
            'page_style'          => null,
            'brand_name'          => html_entity_decode( get_bloginfo( 'name' ), ENT_NOQUOTES, 'UTF-8' ),
            'landing_page'        => 'login',
            'payment_action'      => 'Sale',
            'custom'              => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $this->set_method( 'SetExpressCheckout' );

        $this->add_parameters( array(
            'RETURNURL'                      => $args['return_url'],
            'CANCELURL'                      => $args['cancel_url'],
            'PAGESTYLE'                      => $args['page_style'],
            'BRANDNAME'                      => $args['brand_name'],
            'LANDINGPAGE'                    => ( 'login' == $args['landing_page'] ) ? 'Login' : 'Billing',
            'NOSHIPPING'                     => $args['no_shipping'],
            'MAXAMT'                         => $args['maximum_amount'],
        ) );

        if ( false === $is_upsell ) {
            $this->add_parameter( 'L_BILLINGTYPE0', $args['billing_type'] );
            $this->add_parameter( 'L_BILLINGAGREEMENTDESCRIPTION0', get_bloginfo( 'name' ) );
            $this->add_parameter( 'L_BILLINGAGREEMENTCUSTOM0', '' );
        }

        // if we have an order, the request is to create a subscription/process a payment (not just check if the PayPal account supports Reference Transactions)
        if ( isset( $args['order'] ) ) {

            if ( true === $is_upsell ) {
                $this->add_payment_details_parameters( $args['order'], $args['step_id'], $args['payment_action'], false, true, $args['variation_id'], $args['input_qty'] );

            } else {
                $this->add_payment_details_parameters( $args['order'], $args['payment_action'], false, false );

            }
        }

        $args['no_shipping'] = 0;
        if ( empty( $args['no_shipping'] ) ) {
            $this->maybe_add_shipping_address_params( $args['order'] );

        }
        $set_express_checkout_params = apply_filters( 'wpfunnels/paypal_param_setexpresscheckout', $this->get_parameters(), $is_upsell );
        if ( isset( $set_express_checkout_params['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] ) && 2 === strlen( $set_express_checkout_params['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] ) ) {
            $set_express_checkout_params['ADDRESSOVERRIDE'] = '1';
        }

        $this->clean_params();
        $this->add_parameters( $set_express_checkout_params );
    }


    /**
     * Set the method for the request, currently using:
     *
     * + `SetExpressCheckout` - setup transaction
     * + `GetExpressCheckout` - gets buyers info from PayPal
     * + `DoExpressCheckoutPayment` - completes the transaction
     * + `DoCapture` - captures a previously authorized transaction
     *
     * @param string $method
     * @since 1.0.0
     */
    private function set_method( $method ) {
        $this->add_parameter( 'METHOD', $method );
    }


    /**
     * Set up the payment details for a DoExpressCheckoutPayment or DoReferenceTransaction request
     *
     * @param \WC_Order $order
     * @param $step_id
     * @param $type
     * @param bool $use_deprecated_params whether to use deprecated PayPal NVP parameters (required for DoReferenceTransaction API calls)
     * @param false $is_offer_charge
     * @param string $variation_id
     * @param string $input_qty
     *
     * @since 1.0.0
     */
    protected function add_payment_details_parameters( \WC_Order $order, $step_id, $type, $use_deprecated_params = false, $is_offer_charge = false, $variation_id = '', $input_qty = '' ) {

        $calculated_total = 0;
        $order_subtotal   = 0;
        $item_count       = 0;
        $order_items      = array();

        $offer_data       = Wpfnl_functions::get_offer_product_data( $step_id );
        if ( true === $is_offer_charge ) {
            $order_items[] = array(
                'NAME'    => $offer_data['name'],
                'DESC'    => $offer_data['desc'],
                'AMT'     => $this->round( $offer_data['price'] ),
                'QTY'     => 1,
                'ITEMURL' => $offer_data['url'],
            );
            $order_subtotal += $offer_data['total_unit_price_amount'];
        }
        else {
            // add line items
            foreach ( $order->get_items() as $item ) {

                $product = new \WC_Product( $item['product_id'] );

                $order_items[] = array(
                    'NAME'    => $product->get_title(),
                    'DESC'    => $this->get_item_description( $product ),
                    'AMT'     => $this->round( $order->get_item_subtotal( $item ) ),
                    'QTY'     => ( ! empty( $item['qty'] ) ) ? absint( $item['qty'] ) : 1,
                    'ITEMURL' => $product->get_permalink(),
                );

                $order_subtotal += $item['line_total'];
            }

            // add fees
            foreach ( $order->get_fees() as $fee ) {

                $order_items[] = array(
                    'NAME' => ( $fee['name'] ),
                    'AMT'  => $this->round( $fee['line_total'] ),
                    'QTY'  => 1,
                );

                $order_subtotal += $fee['line_total'];
            }
            if ( $order->get_total_discount() > 0 ) {

                $order_items[] = array(
                    'NAME' => __( 'Total Discount', 'wpfnl' ),
                    'QTY'  => 1,
                    'AMT'  => - $this->round( $order->get_total_discount() ),
                );
            }
        }




        /**Do things for the main order **/
        if ( false === $is_offer_charge ) {
            if ( $this->skip_line_items( $order ) ) {

                $total_amount = $this->round( $order->get_total() );

                // calculate the total as PayPal would
                $calculated_total += $this->round( $order_subtotal + $order->get_cart_tax() ) + $this->round( $order->get_total_shipping() + $order->get_shipping_tax() );

                // offset the discrepancy between the WooCommerce cart total and PayPal's calculated total by adjusting the order subtotal
                if ( $this->price_format( $total_amount ) !== $this->price_format( $calculated_total ) ) {
                    $order_subtotal = $order_subtotal - ( $calculated_total - $total_amount );
                }

                $item_names = array();

                foreach ( $order_items as $item ) {
                    $item_names[] = sprintf( '%1$s x %2$s', $item['NAME'], $item['QTY'] );
                }

                // add a single item for the entire order
                $this->add_line_item_parameters( array(
                    // translators: placeholder is blogname
                    'NAME' => sprintf( __( '%s - Order', 'wpfnl' ), get_option( 'blogname' ) ),
                    'DESC' => $this->get_item_description( implode( ', ', $item_names ) ),
                    'AMT'  => $this->round( $order_subtotal + $order->get_cart_tax() ),
                    'QTY'  => 1,
                ), 0, $use_deprecated_params );

                // add order-level parameters
                //  - Do not send the TAXAMT due to rounding errors
                if ( $use_deprecated_params ) {
                    $this->add_parameters( array(
                        'AMT'              => $total_amount,
                        'CURRENCYCODE'     => $order ? $order->get_currency() : get_woocommerce_currency(),
                        'ITEMAMT'          => $this->round( $order_subtotal + $order->get_cart_tax() ),
                        'SHIPPINGAMT'      => $this->round( $order->get_total_shipping() + $order->get_shipping_tax() ),
                        'INVNUM'           => $this->get_wc_gateway()->get_option( 'invoice_prefix' ) . Wpfnl_functions::str_to_ascii( ltrim( $order->get_order_number(), _x( '#', 'hash before the order number. Used as a character to remove from the actual order number', 'wpfnl-pro' ) ) ),
                        'PAYMENTACTION'    => $type,
                        'PAYMENTREQUESTID' => $order->get_id(),
                        'CUSTOM'           => wp_json_encode( array(
                            '_wpfunnels_o_id'       => $order->get_id(),
                        ) ),
                    ) );
                } else {
                    $this->add_payment_parameters( array(
                        'AMT'              => $total_amount,
                        'CURRENCYCODE'     => $order ? $order->get_currency() : get_woocommerce_currency(),
                        'ITEMAMT'          => $this->round( $order_subtotal + $order->get_cart_tax() ),
                        'SHIPPINGAMT'      => $this->round( $order->get_total_shipping() + $order->get_shipping_tax() ),
                        'INVNUM'           => $this->get_wc_gateway()->get_option( 'invoice_prefix' ) . Wpfnl_functions::str_to_ascii( ltrim( $order->get_order_number(), _x( '#', 'hash before the order number. Used as a character to remove from the actual order number', 'wpfnl' ) ) ),
                        'PAYMENTACTION'    => $type,
                        'PAYMENTREQUESTID' => $order->get_id(),
                        'CUSTOM'           => wp_json_encode( array(
                            '_wpfunnels_o_id'       => $order->get_id(),

                        ) ),
                    ) );
                }
            } else {

                // add individual order items
                foreach ( $order_items as $item ) {
                    $this->add_line_item_parameters( $item, $item_count ++, $use_deprecated_params );
                }

                $total_amount = $this->round( $order->get_total() );
                // add order-level parameters
                if ( $use_deprecated_params ) {
                    $this->add_parameters( array(
                        'AMT'              => $total_amount,
                        'CURRENCYCODE'     => $order ? $order->get_currency() : get_woocommerce_currency(),
                        'ITEMAMT'          => $this->round( $order_subtotal ),
                        'SHIPPINGAMT'      => $this->round( $order->get_total_shipping() ),
                        'TAXAMT'           => $this->round( $order->get_total_tax() ),
                        'INVNUM'           => $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        'PAYMENTACTION'    => $type,
                        'PAYMENTREQUESTID' => $order->get_id(),

                    ) );
                } else {
                    $this->add_payment_parameters( array(
                        'AMT'              => $total_amount,
                        'CURRENCYCODE'     => $order ? $order->get_currency() : get_woocommerce_currency(),
                        'ITEMAMT'          => $this->round( $order_subtotal ),
                        'SHIPPINGAMT'      => $this->round( $order->get_total_shipping() ),
                        'TAXAMT'           => $this->round( $order->get_total_tax() ),
                        'INVNUM' => $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        'PAYMENTACTION'    => $type,
                        'PAYMENTREQUESTID' => $order->get_id(),
                        'CUSTOM'           => wp_json_encode( array(
                            '_wpfunnels_o_id'       => $order->get_id(),
                        ) ),
                    ) );
                }

            }
        } /** Handle paypal data setup for the offers */

        else {

            /**
             * Code for reference transaction
             */
            $total_amount = $offer_data['total'];

            $item_names = array();

            foreach ( $order_items as $item ) {
                $item_names[] = sprintf( '%1$s x %2$s', $item['NAME'], $item['QTY'] );
            }
            $item_count = 0;
            // add individual order items
            foreach ( $order_items as $item ) {
                $this->add_line_item_parameters( $item, $item_count ++, $use_deprecated_params );
            }

            /**
             * Check if this is a referencetransaction call then send depreceated params
             */
            if ( true === $use_deprecated_params && true === $is_offer_charge ) {
                /**
                 * When shipping amount is a negative number, means user opted for free shipping offer
                 * In this case we setup shippingamt as 0 and shipping discount amount is that negative amount that is coming.
                 */
                if ( ( isset( $offer_data['shipping'] ) && isset( $offer_data['shipping']['diff'] ) ) && 0 > $offer_data['shipping']['diff']['cost'] ) {
                    $this->add_parameters( array(
                        'AMT'              => $total_amount,
                        'CURRENCYCODE'     => $order ? $order->get_currency() : get_woocommerce_currency(),
                        'ITEMAMT'          => $this->round( $order_subtotal ),
                        'SHIPPINGAMT'      => 0,
                        'SHIPDISCAMT'      => ( isset( $offer_data['shipping'] ) && isset( $offer_data['shipping']['diff'] ) ) ? $offer_data['shipping']['diff']['cost'] : 0,
                        'INVNUM'           => $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        'PAYMENTACTION'    => $type,
                        'PAYMENTREQUESTID' => $order->get_id(),
                        'TAXAMT'           => ( isset( $offer_data['taxes'] ) ) ? $offer_data['taxes'] : 0,
                        'CUSTOM'           => wp_json_encode( array(
                            '_wpfunnels_o_id'       =>  $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        ) ),
                    ) );
                } else {
                    $this->add_parameters( array(
                        'AMT'              => $total_amount,
                        'CURRENCYCODE'     => $order ? $order->get_currency() : get_woocommerce_currency(),
                        'ITEMAMT'          => $this->round( $order_subtotal ),
                        'SHIPPINGAMT'      => ( isset( $offer_data['shipping'] ) && isset( $offer_data['shipping']['diff'] ) ) ? $offer_data['shipping']['diff']['cost'] : 0,
                        'INVNUM'           => $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        'PAYMENTACTION'    => $type,
                        'PAYMENTREQUESTID' => $order->get_id(),
                        'TAXAMT'           => ( isset( $offer_data['taxes'] ) ) ? $offer_data['taxes'] : 0,
                        'CUSTOM'           => wp_json_encode( array(
                            '_wpfunnels_o_id'       =>  $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        ) ),
                    ) );
                }
            } else {
                if ( ( isset( $offer_data['shipping'] ) && isset( $offer_data['shipping']['diff'] ) ) && 0 > $offer_data['shipping']['diff']['cost'] ) {
                    $this->add_payment_parameters( array(
                        'AMT'              => $total_amount,
                        'CURRENCYCODE'     => $order ? $order->get_currency() : get_woocommerce_currency(),
                        'ITEMAMT'          => $this->round( $order_subtotal ),
                        'SHIPPINGAMT'      => 0,
                        'SHIPDISCAMT'      => ( isset( $offer_data['shipping'] ) && isset( $offer_data['shipping']['diff'] ) ) ? $offer_data['shipping']['diff']['cost'] : 0,
                        'INVNUM'           => $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        'PAYMENTACTION'    => $type,
                        'PAYMENTREQUESTID' => $order->get_id(),
                        'TAXAMT'           => ( isset( $offer_data['taxes'] ) ) ? $offer_data['taxes'] : 0,
                        'NOTIFYURL'        => WC()->api_request_url( 'WC_Gateway_Paypal' ),
                        'CUSTOM'           => wp_json_encode( array(
                            '_wpfunnels_o_id'       => $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        ) ),
                    ) );
                } else {
                    $this->add_payment_parameters( array(
                        'AMT'              => $total_amount,
                        'CURRENCYCODE'     => $order ? $order->get_currency() : get_woocommerce_currency(),
                        'ITEMAMT'          => $this->round( $order_subtotal ),
                        'SHIPPINGAMT'      => ( isset( $offer_data['shipping'] ) && isset( $offer_data['shipping']['diff'] ) ) ? $offer_data['shipping']['diff']['cost'] : 0,
                        'INVNUM'           => $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        'PAYMENTACTION'    => $type,
                        'NOTIFYURL'        => WC()->api_request_url( 'WC_Gateway_Paypal' ),
                        'PAYMENTREQUESTID' => $order->get_id(),
                        'TAXAMT'           => ( isset( $offer_data['taxes'] ) ) ? $offer_data['taxes'] : 0,
                        'CUSTOM'           => wp_json_encode( array(
                            '_wpfunnels_o_id'       => $this->get_wc_gateway()->get_option('invoice_prefix') . $this->get_order_number($order, $offer_data['step_id']),
                        ) ),
                    ) );
                }
            }
        }
        

    }


    /**
     * Adds a line item parameters to the request, auto-prefixes the parameter key
     * with `L_PAYMENTREQUEST_0_` for convenience and readability
     *
     * @param array $params
     * @param int $item_count current item count
     *
     * @since 2.0
     */
    private function add_line_item_parameters( array $params, $item_count, $use_deprecated_params = false ) {
        foreach ( $params as $key => $value ) {
            if ( $use_deprecated_params ) {
                $this->add_parameter( "L_{$key}{$item_count}", $value );
            } else {
                $this->add_parameter( "L_PAYMENTREQUEST_0_{$key}{$item_count}", $value );
            }
        }
    }


    /**
     * Add payment parameters, auto-prefixes the parameter key with `PAYMENTREQUEST_0_`
     * for convenience and readability
     *
     * @param array $params
     *
     * @since 1.0.0
     */
    private function add_payment_parameters( array $params ) {
        foreach ( $params as $key => $value ) {
            $this->add_parameter( "PAYMENTREQUEST_0_{$key}", $value );
        }
    }



    /**
     * PayPal cannot properly calculate order totals when prices include tax (due
     * to rounding issues), so line items are skipped and the order is sent as
     * a single item
     *
     * @since 2.0.9
     * @param \WC_Order $order Optional. The WC_Order object. Default null.
     * @return bool true if line items should be skipped, false otherwise
     */
    private function skip_line_items( $order = null, $order_items = null ) {

        $skip_line_items = false;

        // Also check actual totals add up just in case totals have been manually modified to amounts that can not round correctly, see https://github.com/Prospress/woocommerce-subscriptions/issues/2213
        if ( ! is_null( $order ) ) {

            $rounded_total = 0;
            $items              = array();
            foreach ( $order_items as $item ) {
                $amount        = round( $item['line_subtotal'] / $item['qty'], 2 );
                $rounded_total += round( $amount * $item['qty'], 2 );
                $calculated_total += $this->round( $item['AMT'] * $item['QTY'] );

                $amount = round( $item['line_subtotal'] / $item['qty'], 2 );
                $item   = array(
                    'name'     => $item['name'],
                    'quantity' => $item['qty'],
                    'amount'   => $amount,
                );

                $items[] = $item;
            }

            $discounts = $order->get_total_discount();

            $details                = array(
                'total_item_amount' => round( $order->get_subtotal(), 2 ) + $discounts,
                'order_tax'         => round( $order->get_total_tax(), 2 ),
                'shipping'          => round( $order->get_shipping_total(), 2 ),
                'items'             => $items,
            );
            $details['order_total'] = round( $details['total_item_amount'] + $details['order_tax'] + $details['shipping'], 2 );
            if ( (float) $details['order_total'] !== (float) $order->get_total() ) {
                $skip_line_items = true;
            }
            if ( $details['total_item_amount'] !== $rounded_total ) {
                $skip_line_items = true;
            }
        }

        /**
         * Filter whether line items should be skipped or not
         *
         * @since 3.3.0
         * @param bool $skip_line_items True if line items should be skipped, false otherwise
         * @param \WC_Order/null $order The WC_Order object or null.
         */
        return apply_filters( 'wpfunnels/paypal_skip_line_items', $skip_line_items, $order );
    }


    /**
     * @param \WC_Order $order
     */
    function maybe_add_shipping_address_params( \WC_Order $order, $prefix = 'PAYMENTREQUEST_0_SHIPTO' ) {

        if ( $order->has_shipping_address() ) {
            $shipping_first_name = $order->get_shipping_first_name();
            $shipping_last_name  = $order->get_shipping_last_name();
            $shipping_address_1  = $order->get_shipping_address_1();
            $shipping_address_2  = $order->get_shipping_address_2();
            $shipping_city       = $order->get_shipping_city();
            $shipping_state      = $order->get_shipping_state();
            $shipping_postcode   = $order->get_shipping_postcode();
            $shipping_country    = $order->get_shipping_country();
        } else {
            $shipping_first_name = $order->get_billing_first_name();
            $shipping_last_name  = $order->get_billing_last_name();
            $shipping_address_1  = $order->get_billing_address_1();
            $shipping_address_2  = $order->get_billing_address_2();
            $shipping_city       = $order->get_billing_city();
            $shipping_state      = $order->get_billing_state();
            $shipping_postcode   = $order->get_billing_postcode();
            $shipping_country    = $order->get_billing_country();
        }
        if ( empty( $shipping_country ) ) {
            $shipping_country = WC()->countries->get_base_country();
        }

        $shipping_phone = $order->get_billing_phone();

        $params = array(
            $prefix . 'NAME'        => $shipping_first_name . ' ' . $shipping_last_name,
            $prefix . 'STREET'      => $shipping_address_1,
            $prefix . 'STREET2'     => $shipping_address_2,
            $prefix . 'CITY'        => $shipping_city,
            $prefix . 'STATE'       => $shipping_state,
            $prefix . 'ZIP'         => $shipping_postcode,
            $prefix . 'COUNTRYCODE' => $this->get_country( $shipping_country ),
            $prefix . 'PHONENUM'    => $shipping_phone,
        );
        foreach ( $params as $key => $val ) {
            $this->add_parameter( $key, $val );
        }

    }


    private function get_country( $country ) {
        if ( 2 === strlen( $country ) ) {
            return $country;
        }

        return substr( $country, 0, 2 );
    }


    /**
     * Add api credentials parameters
     *
     * @param string $api_username API username.
     * @param string $api_password API password.
     * @param string $api_signature API signature.
     * @param string $api_version API version.
     * @return void
     */
    public function set_credentials_params( $api_username, $api_password, $api_signature, $api_version ) {

        $this->add_parameters(
            array(
                'USER'      => $api_username,
                'PWD'       => $api_password,
                'SIGNATURE' => $api_signature,
                'VERSION'   => $api_version,
            )
        );
    }


    /**
     * Add multiple parameters
     *
     * @param array $params
     *
     * @since 2.0
     */
    public function add_parameters(array $params)
    {
        foreach ($params as $key => $value) {
            $this->add_parameter($key, $value);
        }
    }


    /**
     * Add a parameter
     *
     * @param string $key
     * @param string|int $value
     *
     * @since 2.0
     */
    public function add_parameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }


    /**
     * get call back url of paypal request
     *
     * @param $args
     * @return string
     *
     * @since 1.0.0
     */
    public function get_callback_url($args)
    {
        $api_request_url = WC()->api_request_url( 'wpfunnels_paypal_express' );
        if ( is_array( $args ) ) {
            return add_query_arg( $args, $api_request_url );
        } else {
            return add_query_arg( 'action', $args, $api_request_url );
        }
    }


    /**
     * get paypal payment params
     *
     * @return array|mixed|void
     * @throws \Exception
     */
    public function get_parameters() {

        $this->parameters = apply_filters('wcs_paypal_request_params', $this->parameters, $this);
        // validate parameters.
        foreach ( $this->parameters as $key => $value ) {

            // remove unused params.
            if ( '' === $value || is_null( $value ) ) {
                unset( $this->parameters[ $key ] );
            }

            // format and check amounts.
            if ( false !== strpos( $key, 'AMT' ) ) {

                // amounts must be 10,000.00 or less for USD.
                if ( isset( $this->parameters['PAYMENTREQUEST_0_CURRENCYCODE'] ) && 'USD' == $this->parameters['PAYMENTREQUEST_0_CURRENCYCODE'] && $value > 10000 ) {

                    throw new \Exception( sprintf( '%s amount of %s must be less than $10,000.00', $key, $value ) );
                }

                $this->parameters[ $key ] = $this->price_format( $value );
            }
        }
        return $this->parameters;
    }


    /**
     * Return the parsed response object for the request
     *
     * @since 1.0.0
     *
     * @param string $raw_response_body response body.
     *
     * @return object
     */
    protected function get_parsed_response( $raw_response_body ) {

        wp_parse_str( urldecode( $raw_response_body ), $this->response_params );

        return $this->response_params;
    }


    /**
     * Get Express Checkout Details
     *
     * @param $token
     * @return object
     *
     * @since 1.0.0
     */
    public function get_express_checkout_details( $token ) {

        $environment = ('sandbox' === $this->get_wc_gateway()->environment) ? 'sandbox' : 'production';
        $api_creds_prefix = '';
        if ('sandbox' === $environment) {
            $api_creds_prefix = 'sandbox_';
        }
        $this->set_api_credentials(
                $this->get_key(),
                $environment,
                $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_username'),
                $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_password'),
                $this->get_wc_gateway()->get_option($api_creds_prefix . 'api_signature')
        );
        $this->set_express_checkout_details_args($token);
        $this->set_credentials_params(
                $this->api_username,
                $this->api_password,
                $this->api_signature,
                124
        );
        $request = new \stdClass();
        $request->path = '';
        $request->method = 'POST';
        $request->body = $this->to_string();
        return $this->perform_request($request);
    }


    /**
     * Sets up GetExpressCheckoutDetails API call arguments
     *
     * @param string $token
     *
     * @since 1.0.0
     */
    public function set_express_checkout_details_args($token)
    {

        $this->set_method('GetExpressCheckoutDetails');
        $this->add_parameter('TOKEN', $token);
    }


    /**
     * @hooked woocommerce_api_wpfunnels_paypal_express
     */
    public function maybe_create_billing() {
        if (!isset($_GET['action'])) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        $token      = esc_attr( sanitize_text_field( wp_unslash( $_GET['token'] ) ) );
        $order_id   = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
        $step_id    = isset( $_GET['step_id'] ) ? intval( $_GET['step_id'] ) : 0;
        $funnel_id  = isset( $_GET['funnel_id'] ) ? intval( $_GET['funnel_id'] ) : 0;

        switch ($_GET['action']) {

            // called when the customer is returned from PayPal after authorizing their payment, used for retrieving the customer's checkout details
            case 'wpfunnels_paypal_express_create_billing_agreement':

                // return if no token
                if (!isset($_GET['token'])) {
                    return;
                }

                // get token to retrieve checkout details with
                $token = wc_clean( $_GET['token'] );
                $order = null;
                try {
                    $express_checkout_details_response = $this->get_express_checkout_details($token);
                    $order = wc_get_order($order_id);

                    // Make sure the billing agreement was accepted
                    if (1 === $express_checkout_details_response['BILLINGAGREEMENTACCEPTEDSTATUS'] || '1' === $express_checkout_details_response['BILLINGAGREEMENTACCEPTEDSTATUS']) {

                        if (is_null($order)) {
                            throw new Wpfnl_Payment_Gateway_Exception(__('Unable to find order for PayPal billing agreement.', 'wpfnl-pro'), 101, $this->get_key());
                        }

                        // we need to process an initial payment
                        if (Wpfnl_functions::get_amount_for_comparisons($order->get_total()) > 0) {
                            $billing_agreement_response = $this->do_express_checkout($token, $order, array(
                                'payment_action'    => 'Sale',
                                'payer_id'          => $this->get_value_from_response($express_checkout_details_response, 'PAYERID'),
                            ));
                        } else {
                            throw new Wpfnl_Payment_Gateway_Exception(__('Order total is not greater than zero.', 'wpfnl'), 101, $this->get_key());
                        }

                        if ($this->has_api_error($billing_agreement_response)) {
                            throw new Wpfnl_Payment_Gateway_Exception($this->get_api_error($billing_agreement_response), 101, $this->get_key());
                        }

                        $order->set_payment_method('paypal');
                        $order->update_meta_data('_paypal_subscription_id', $this->get_value_from_response($billing_agreement_response, 'BILLINGAGREEMENTID'));

                        /**
                         * mark primary payment as completed
                         */
                        $order->payment_complete($billing_agreement_response['PAYMENTINFO_0_TRANSACTIONID']);

                        $next_step = Wpfnl_functions::get_next_step( $funnel_id, $step_id );
                        $custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step['step_id'] );
                        if( $custom_url ){
                            $redirect_url =  $custom_url;
                        }else{
                            $redirect_url = add_query_arg('utm_nooverride', '1', get_permalink($next_step['step_id']));
                        }

                        //redirect customer to order received page
                        wp_safe_redirect(esc_url_raw($redirect_url));
                        exit;

                    }
                    else {
                        $this->handle_api_failures($order);
                    }
                }
                catch (\Exception $e) {
                    $this->handle_api_failures($order, $e);
                }
        }
    }

    /**
     * check if api response has any error
     *
     * @param $response
     * @return bool
     */
    public function has_api_error($response) {
        // assume something went wrong if ACK is missing
        if (!isset($response['ACK'])) {
            return true;
        }

        // any non-success ACK is considered an error, see
        // https://developer.paypal.com/docs/classic/api/NVPAPIOverview/#id09C2F0K30L7
        return ('Success' !== $this->get_value_from_response($response, 'ACK') && 'SuccessWithWarning' !== $this->get_value_from_response($response, 'ACK'));

    }


    /**
     * get order from response
     *
     * @param $response
     * @return bool|\WC_Order|\WC_Order_Refund
     */
    public function get_order_from_response($response) {

        if ($response && isset($response['CUSTOM'])) {
            $getjson = json_decode($response['CUSTOM'], true);
            return wc_get_order($getjson['_wpfunnels_o_id']);
        }
    }


    /**
     * Handles Payment Gateway API error
     *
     * @param $order
     * @param $e
     */
    protected function handle_api_failures($order, $e = '') {
        if ($order instanceof \WC_Order) {

            if ($e instanceof Wpfnl_Payment_Gateway_Exception) {

                $order->add_order_note($e->getMessage());
            }
            $redirect = $order->get_checkout_order_received_url();
            wp_redirect($redirect);
            exit;
        }
        wp_die('Unable to process further. Please contact to the store admin & enquire about the status of your order.');
        exit;
    }


    public function handle_api_calls() {

        if (!isset($_GET['action'])) {
            return;
        }

        $step_id      = isset( $_GET['step_id'] ) ? intval( $_GET['step_id'] ) : 0;
        $funnel_id    = isset( $_GET['funnel_id'] ) ? intval( $_GET['funnel_id'] ) : 0;
        $order_id     = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
        $order_key    = isset( $_GET['order_key'] ) ? sanitize_text_field( wp_unslash( $_GET['order_key'] ) ) : '';
        $variation_id = isset( $_GET['variation_id'] ) ? intval( $_GET['variation_id'] ) : '';
        $input_qty    = isset( $_GET['input_qty'] ) ? intval( $_GET['input_qty'] ) : '';
       
        $order = null;
        try {
            switch ($_GET['action']) {
                case 'wpfunnels_paypal_express_return':

                    $offer_product = Wpfnl_functions::get_offer_product_data($step_id);

                    $order = wc_get_order( $order_id );

                    
                    if ( isset( $_GET['token'] ) && ! empty( $_GET['token'] ) ) {
                        $api_response_result = false;
                        $express_checkout_details_response = $this->get_express_checkout_details( wp_unslash( $_GET['token'] ) ); //phpcs:ignore

                        $get_paypal_data = WC()->session->get('paypal_request');

                        /**
                         * Check if product total is greater than 0.
                         */
                        if ( $offer_product['total'] > 0 ) {
                            /**
                             * Prepare DoExpessCheckout Call to finally charge the user.
                             */
                            $do_express_checkout_data = array(
                                'TOKEN'   => $express_checkout_details_response['TOKEN'],
                                'PAYERID' => $express_checkout_details_response['PAYERID'],
                                'METHOD'  => 'DoExpressCheckoutPayment',
                            );

                            $do_express_checkout_data = wp_parse_args( $do_express_checkout_data, $get_paypal_data );
                            $environment = ( 'sandbox' === $this->get_wc_gateway()->environment ) ? 'sandbox' : 'live';

                            $api_prefix = '';

                            if ( 'sandbox' === $environment ) {
                                $api_prefix = 'sandbox_';
                            }

                            /**
                             * Setup & perform DoExpressCheckout API Call.
                             */
                            $this->set_api_credentials(
                                $this->key,
                                $environment,
                                $this->get_wc_gateway()->get_option( $api_prefix . 'api_username' ),
                                $this->get_wc_gateway()->get_option( $api_prefix . 'api_password' ),
                                $this->get_wc_gateway()->get_option( $api_prefix . 'api_signature' )
                            );

                            $this->add_parameters( $do_express_checkout_data );
                            $this->set_credentials_params( $this->api_username, $this->api_password, $this->api_signature, 124 );

                            $request         = new \stdClass();
                            $request->path   = '';
                            $request->method = 'POST';
                            $request->body   = $this->to_string();
                            $response_checkout = $this->perform_request( $request );

                            if ( false === $this->has_api_error( $response_checkout ) ) {
                                $api_response_result = true;
                                $data = $this->handle_offer_charge($order, $api_response_result, $offer_product);
                            }
                        }
                        else {
                            $api_response_result = true;
                            $data = $this->handle_offer_charge($order, $api_response_result, $offer_product);
                        }

                        $result = Wpfnl_functions::after_offer_charged( $step_id, $order_id, $order_key, $api_response_result, $variation_id, $input_qty, $offer_product );
                        $next_step = Wpfnl_functions::get_next_step($funnel_id, $step_id);
                        
                        $next_step_url = get_permalink($next_step['step_id']);
                        $custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step['step_id'] );
                        if( $custom_url ){
                            $redirect_url =  $custom_url;
                        }else{
                            $redirect_url = add_query_arg(
                                array(
                                    'wpfnl-order' => $order_id,
                                    'wpfnl-key' => $order_key,
                                    'key' => $order_key,
                                ),
                                $next_step_url
                            );
                        }
                        
                        wp_safe_redirect( $redirect_url );
                        exit;

                    } else {

                        $result =  Wpfnl_functions::after_offer_charged( $step_id, $order_id, $order_key, $api_response_result, $variation_id, $input_qty, $offer_product );
                        $next_step = Wpfnl_functions::get_next_step($funnel_id, $step_id);

                        $custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step['step_id'] );
                        if( $custom_url ){
                            $redirect_url =  $custom_url;
                        }else{
                            $next_step_url = get_permalink($next_step['step_id']);
                            $redirect_url = add_query_arg(
                                array(
                                    'wpfnl-order' => $order_id,
                                    'wpfnl-key' => $order_key,
                                    'key' => $order_key,
                                ),
                                $next_step_url
                            );
                        }
                        

                        wp_safe_redirect( $redirect_url );
                        exit;
                    }

                    break;

                case 'cancel_url':

                    /**
                     * Getting the current URL from the session and loading the same offer again.
                     * User needs to chose "no thanks" if he want to move to upsell/order received.
                     */
                    $url = get_permalink( $step_id );

                    $args = array(
                        'wpfnl-order' => $order_id,
                        'wpfnl-key' => $order_key,
                        'key' => $order_key,
                    );

                    $url = add_query_arg( $args, $url );
                    wp_redirect($url);
                    exit;

            }

        } catch (Exception $e) {
            $this->handle_api_failures($order, $e);
        }
    }


    /**
     * Store Offer Trxn Charge.
     *
     * @param \WC_Order $order    The order that is being paid for.
     * @param Object   $response The response that is send from the payment gateway.
     * @param array    $product  Product data.
     */
    public function handle_offer_charge( \WC_Order $order, $response, $product ) {

        $order_id = $order->get_id();
        $txn_id   = '';

        if ( ! isset( $response['PAYMENTINFO_0_TRANSACTIONID'] ) ) {
            if(is_array($response)){
                $txn_id = $response['TRANSACTIONID'];
            }
            
        } else {
            if(is_array($response)){
                $txn_id = $response['PAYMENTINFO_0_TRANSACTIONID'];
            }
        }
        $order->update_meta_data( '_wpfunnels_offer_txn_resp_' . $product['step_id'], $txn_id );
        $order->save();
    }


    /**
	 * Modify offer refund data.
	 *
	 * @param array  $request request.
	 * @param object $order the order object.
	 * @param float  $amount refund amount.
	 *
	 * @return object
	 */
	public function wpfnl_offer_refund_request( $request, $order, $amount ) {
        
		$payment_method = $order->get_payment_method();

		if ( $this->key !== $payment_method ) {
			return $request;
		}

		if ( isset( $order->data['transaction_id'] ) && ! empty( $order->data['transaction_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$request['TRANSACTIONID'] = wc_clean( $order->data['transaction_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Missing

			$environment = ('sandbox' === $this->get_wc_gateway()->environment) ? 'sandbox' : 'live';

			if ( 'live' === $environment ) {
				$request['USER']      = $this->get_wc_gateway()->get_option( 'api_username' );
				$request['PWD']       = $this->get_wc_gateway()->get_option( 'api_password' );
				$request['SIGNATURE'] = $this->get_wc_gateway()->get_option( 'api_signature' );
			} else {
				$request['USER']      = $this->get_wc_gateway()->get_option( 'sandbox_api_username' );
				$request['PWD']       = $this->get_wc_gateway()->get_option( 'sandbox_api_password' );
				$request['SIGNATURE'] = $this->get_wc_gateway()->get_option( 'sandbox_api_signature' );

			}
		}
		return $request;
	}

    
}
