<?php
/**
 * Checkout module
 *
 * @package
 * @since 2.7.9
 */

namespace WPFunnels\Modules\Frontend\Checkout;

use Error;
use WC_Session_Handler;
use WPFunnels\Data_Store\Wpfnl_Steps_Store_Data;
use WPFunnels\Frontend\Module\Wpfnl_Frontend_Module;
use WPFunnels\Frontend\Wpfnl_Public;
use WPFunnels\Meta\Wpfnl_Default_Meta;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnels\Modules\Frontend\Checkout\Variable\Wpfnl_Variable_Product;
use WPFunnels\Modules\Frontend\Checkout\Single\Wpfnl_Single_Product;
use function cli\err;
use Wpfnl_Public_Type_Factory;
use WPFunnels\Conditions\Wpfnl_Condition_Checker;
use WPFunnels\Discount\WpfnlDiscount;
use WPFunnels\Modules\Frontend\CheckoutHelper\CheckoutHelper;

class Module extends Wpfnl_Frontend_Module
{
	public $funnel_id;

	public $step_id;

	// const CHECKOUT = "";

	public function __construct()
	{

		/* set checkout flag */
		add_filter('woocommerce_is_checkout', [$this, 'checkout_flag'], 9999);

		if( !defined('WCML_VERSION') ){
			add_action('woocommerce_checkout_update_order_meta', [$this, 'save_checkout_fields'], 10, 2);
		}

		/* initialize cart data */
		add_action( 'wp', [ $this, 'initialize_cart_data' ] );

		/* init WooCommerce action */
		add_action( 'wp', [$this, 'init_actions']);

		/* register checkout shortcode */
		add_shortcode('wpfunnels_checkout', array($this, 'render_checkout_shortcode'));
		add_action('wp_ajax_wpfnl_next_button_ajax', [$this, 'wpfnl_next_button_ajax']);
		add_action('wp_ajax_nopriv_wpfnl_next_button_ajax', [$this, 'wpfnl_next_button_ajax']);

		add_action( 'wp_ajax_wpfnl_order_bump_ajax', [$this, 'wpfnl_order_bump_ajax']);
		add_action( 'wp_ajax_nopriv_wpfnl_order_bump_ajax', [$this, 'wpfnl_order_bump_ajax']);
		add_action( 'wp_ajax_wpfnl_get_course_details', [$this, 'wpfnl_get_course_details']);
		add_action( 'wp_ajax_nopriv_wpfnl_get_course_details', [$this, 'wpfnl_get_course_details']);

		add_action( 'wp_ajax_wpfnl_update_variation', [$this, 'wpfnl_update_variation']);
		add_action( 'wp_ajax_nopriv_wpfnl_update_variation', [$this, 'wpfnl_update_variation']);

		add_action( 'woocommerce_before_calculate_totals', [$this, 'custom_price_to_cart_item'], 9999);
		add_action( 'woocommerce_before_checkout_form', [$this, 'apply_auto_coupon'], 9999);

		add_action( 'wp_ajax_nopriv_wpfnl_checkout_cart', [$this, 'wpfnl_checkout_cart']);
		add_filter('woocommerce_cart_product_out_of_stock_message', [$this,'wpfnl_out_of_stock_message_in_checkout'], 10, 2);

		add_action( 'wpfunnels/before_checkout_form', array($this, 'before_checkout_form_actions') );

		add_action( 'woocommerce_after_order_notes', array( $this, 'wpfnl_set_checkout_id' ), 999 );
		add_action( 'woocommerce_login_form_end', array( $this, 'wpfnl_set_checkout_id' ), 999 );
		add_filter( 'woocommerce_login_redirect', [$this,'wpfnl_redirect_after_login'], 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', [$this,'wpfnl_checkout_discount'], 10);
		add_action( 'woocommerce_cart_calculate_fees', [$this,'wpfnl_checkout_discount'], 10);

		add_action( 'wpfunnels/after_optin_submit', array( $this, 'get_optin_data_checkout' ), 10, 4 );
		add_filter( 'woocommerce_checkout_fields', array($this, 'set_option_data_in_checkout_filed'),10 );
		add_filter( 'woocommerce_available_payment_gateways', array($this, 'conditional_payment_gateways'),10 );


	}



	/**
	 * Set checkout flag to true if this is checkout step type
	 *
	 * @param $is_checkout
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function checkout_flag($is_checkout)
	{
		if (Wpfnl_functions::check_if_this_is_step_type('checkout')) {
			$is_checkout = true;
		}
		return $is_checkout;
	}


	/**
	 * Init necessary actions related Checkout step
	 */
	public function init_actions() {
		add_action( 'woocommerce_after_order_notes', [$this, 'checkout_shortcode_metas']);
		$this->display_coupon_field();
		$this->enable_checkout_hook();

		if ( Wpfnl_functions::check_if_this_is_step_type( 'checkout' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_checkout_step_scripts' ) );
		}
	}


	/**
	 * Load script/styles related to checkout step
	 *
	 * @since 3.1.3
	 */
	public function load_checkout_step_scripts() {
		$this->load_google_map_api_scripts();
	}


	/**
	 * Load google map places script on checkout page
	 *
	 * @since 3.1.3
	 */
	public function load_google_map_api_scripts() {
		$google_map_api_key = Wpfnl_functions::get_google_map_api_key();
		if ( !$google_map_api_key ) {
			return;
		}

		if( !Wpfnl_functions::validate_google_places_api_key($google_map_api_key) ){
			return;
		}

		wp_enqueue_script(
			'wpfnl-google-places-api',
			'https://maps.googleapis.com/maps/api/js?key=' . $google_map_api_key . '&libraries=places',
			array(),
			WPFNL_VERSION,
			true
		);

		wp_enqueue_script(
			'wpfnl-google-places',
			WPFNL_DIR_URL.'public/assets/js/google-places-auto-complete.js',
			array( 'wpfnl-google-places-api' ),
			WPFNL_VERSION,
			true
		);

	}


	/**
	 * Enable checkout hook
	 */
	private function enable_checkout_hook(){

		if ( Wpfnl_functions::check_if_this_is_step_type('checkout') ) {

			if( is_plugin_active( 'woocommerce-german-market/WooCommerce-German-Market.php' )){
				add_action( 'woocommerce_checkout_order_review','woocommerce_checkout_payment' );
			}
		}
	}


	/**
	 * Save checkout metas
	 *
	 * @param $order_id
	 * @param $posted
	 *
	 * @since 1.0.0
	 */
	public function save_checkout_fields( $order_id, $posted )
	{
		// Instantiate the CheckoutModuleHelper
		$helper = CheckoutHelper::getInstance();

		if (isset($_POST['_wpfunnels_checkout_id'])) {
			$helper->update_checkout_id_post_meta($order_id, sanitize_text_field( $_POST['_wpfunnels_checkout_id'] ) );
			if (isset($_POST['_wpfunnels_funnel_id'])) {
				$funnel_id = $helper->update_order_meta_for_funnel($order_id, sanitize_text_field($_POST['_wpfunnels_funnel_id']));
			}
			if (isset($_POST['_wpfunnels_order_unique_identifier'])) {
				$helper->update_unique_identifier_post_meta($order_id, sanitize_text_field($_POST['_wpfunnels_order_unique_identifier']));
			}
			$order_bump_products = get_post_meta( $_POST['_wpfunnels_checkout_id'], 'order-bump-settings' , true );
			$helper->update_order_bump_product( $order_bump_products, $order_id );
		}

		if (isset($funnel_id)) {
			$order     = Wpfnl_functions::is_wc_active() ? wc_get_order($order_id) : null;

			$funnel_id = $helper->update_order_meta_for_funnel($order_id, sanitize_text_field( $_POST['_wpfunnels_funnel_id']) );

			if( $funnel_id ){
				$discount_instance = new WpfnlDiscount();

				if( !$discount_instance->maybe_time_bound_discount( $_POST['_wpfunnels_checkout_id'] ) || ($discount_instance->maybe_time_bound_discount( $_POST['_wpfunnels_checkout_id'] ) && $discount_instance->maybe_validate_discount_time( $_POST['_wpfunnels_checkout_id'] )) ){
					$discount          = $discount_instance->get_discount_settings( $_POST['_wpfunnels_checkout_id'] );
					$main_products     = $helper->get_main_products( $_POST['_wpfunnels_checkout_id'], $funnel_id );

					$variable_product_check  = $helper->variable_product_check( $order->get_items(), $main_products, false );
					$main_products           = isset( $variable_product_check['main_products'] ) ? $variable_product_check['main_products'] : $main_products;

					$is_main_product_in_cart = isset( $variable_product_check['is_main_product_in_cart'] ) ? $variable_product_check['is_main_product_in_cart'] : false;
					$is_regular = isset( $discount['discountOptions'], $discount['discountapplyto']) && 'original' !== $discount['discountOptions'] && 'sale' !== $discount['discountapplyto'];

					$total                   = $helper->get_custom_subtotal($is_regular);
					$response = $helper->apply_discount_and_update_total( $order, $total, $discount, $is_main_product_in_cart);
				}
			}
    		$user_id = $order->get_user_id();
			$orders[sanitize_text_field($_POST['_wpfunnels_checkout_id'])] = $order_id;
			WC()->session->set('wpfnl_orders_'.$user_id.'_'.$funnel_id, $orders);

			$session_handler = new WC_Session_Handler();
			WC()->session->set('wpfnl_order_owner', $session_handler->generate_customer_id());

			/**
			 * Fires after a funnel order is placed.
			 *
			 * @since 1.0.0
			 *
			 * @param int    $order_id               The ID of the order.
			 * @param string $funnel_id              The ID of the funnel.
			 * @param string $_POST['_wpfunnels_checkout_id']  The checkout ID associated with the order.
			 */
			do_action( 'wpfunnels/funnel_order_placed', $order_id, $funnel_id, $_POST['_wpfunnels_checkout_id'] );
		}
	}


	/**
	 * Automatically add products to funnel checkout
	 *
	 * @throws \Exception
	 *
	 * @since 1.0.0
	 */
    public function initialize_cart_data() {
        global $post;
		if ( isset($_REQUEST['wc-ajax']) && ( 'apply_coupon' === $_REQUEST['wc-ajax'] || 'wc_stripe_get_cart_details' === $_REQUEST['wc-ajax'] ) ) {
			return;
		}

        if(
            is_admin()
            || isset( $_GET[ 'removed_item' ] )
            || !$post
        ) {
            return;
        }

		if(!Wpfnl_functions::is_wc_active() ){
			return;
		}
		
		$checkout_id = '';
		if( wp_doing_ajax()  ){
			$checkout_id = Wpfnl_functions::get_checkout_id_from_post($_POST);
		}

        $checkout_id = !$checkout_id ? $post->ID : $checkout_id;

        // Returns if the post id is not funnel checkout type.
        if(
            !Wpfnl_functions::check_if_this_is_step_type_by_id( $checkout_id, 'checkout' )
            || !Wpfnl_functions::check_if_this_is_step_type( 'checkout' )
			|| Wpfnl_functions::is_orderbump_clicked_from_post_data()
			|| Wpfnl_functions::is_checkoutify_orderbump_clicked_from_post_data()
			|| Wpfnl_functions::is_variation_selected_from_post_data()
			|| Wpfnl_functions::maybe_select_quantity_from_post_data()
			// || Wpfnl_functions::is_coupon_applied_from_post_data()
        ) {
            return;
        }


		if(  isset($_REQUEST['wc-ajax']) && Wpfnl_functions::is_wc_active() && !WC()->cart->is_empty() && WC()->cart->has_discount()){
			return;
		}

        $funnel_id = get_post_meta( $checkout_id, '_funnel_id', true );

        // Returns in case of invalid funnel id or if the funnel id is `lms` type.
        if( !$funnel_id || 'lms' === get_post_meta( $funnel_id, '_wpfnl_funnel_type', true ) ) {
            return;
        }

        $this->render_express_checkout( $checkout_id );

        /**
         * Filter hook to modify funnel products
         *
         * @param array $product_array Products selected for funnel checkout.
         * @param string|int $funnel_id Funnel ID.
         * @param string|int $checkout_id Checkout ID.
         *
         * @since 2.7.11
         */
        $product_array = apply_filters(
            'wpfunnels/funnel_products',
            get_post_meta( $checkout_id, '_wpfnl_checkout_products', true ),
            $funnel_id,
            $checkout_id
        );

		/**
		 * This line of code applies a filter to determine if initiating a funnel checkout is allowed.
		 *
		 * @param bool $is_allow The current value indicating if initiating a funnel checkout is allowed.
		 * @param int $funnel_id The ID of the funnel being checked.
		 * @return bool The filtered value indicating if initiating a funnel checkout is allowed.
		 *
		 * @since 3.3.2
		 */
		$is_allow = apply_filters('wpfunnels/allow_initiate_funnel_checkout', true, $funnel_id );
		if( !$is_allow ){
			return;
		}

        \WC()->cart->empty_cart();

        if( !is_array( $product_array ) || empty( $product_array[ 0 ][ 'id' ] ) ) {
            wc_clear_notices();
            wc_add_notice( __( 'No product is added to the funnel. Please add product from checkout step settings.', 'wpfnl' ), 'error' );
            return;
        }
		$price_type = $this->get_product_price_type( $funnel_id, $checkout_id );

        if( $product_array && is_array( $product_array )) {
            foreach( $product_array as $product ) {
				if( isset( $product[ "id" ] ) && isset( $product[ "quantity" ] ) && ( !isset($product['funnel_id']) ||  (isset($product['funnel_id']) && (int)$funnel_id === (int)$product['funnel_id']) ) ) {
                    $product_id = !empty($product['variation_id']) ? $product[ "variation_id" ] : $product[ "id" ];
                    $quantity   = $product[ "quantity" ];
                    $_product   = $product_id ? wc_get_product( $product_id ) : null;

                    if( $_product instanceof \WC_Product ) {
                        //check if product type is variable or not from $_product object.

                        if( 'variable' === $_product->get_type() ) {
                            $this->add_default_variation( $product_id, $_product, $quantity, $price_type );
                            continue;
                        }

                        $product_type = $_product->get_type();

                        if( 'variation' === $product_type || 'subscription_variation' === $product_type ) {
							$is_perfect_variation = Wpfnl_functions::is_perfect_variations( $product_id );
							$type = empty($is_perfect_variation['status']) ? 'single-variation' : 'variation';
                            $this->add_variaiton_product_in_cart( $product_id, $quantity, $price_type, $type, [], null );
                            continue;
                        }

						$product_object = wc_get_product( $product_id );

						if ( is_a( $product_object, 'WC_Product_Bundle' ) ) {

							if( $price_type == 'original' ||  $price_type == 'sale' ){
								$custom_data = [
									'custom_price' 	=> $product_object->get_bundle_price('min') || 0 == $product_object->get_bundle_price('min') ? $product_object->get_bundle_price('min') : $product_object->get_bundle_regular_price( 'min' ),
									'product_type'  => $product_type
								];
							}else{
								$custom_data = [
									'custom_price' 	=> $product_object->get_bundle_regular_price( 'min' ) ? $product_object->get_bundle_regular_price( 'min' ) : $product_object->get_bundle_price('min'),
									'product_type'  => $product_type
								];
							}
							$is_bundle_exist = true;

						}else{
							$is_bundle_exist = false;
							if( $price_type == 'original' ||  $price_type == 'sale' ){
								$custom_data = [
									'custom_price' 	=> $product_object->get_sale_price() ? $product_object->get_sale_price() : get_post_meta($product_id, '_price', true),
									'product_type'  => $product_type
								];
							}else{
								$custom_data = [
									'custom_price' 	=> get_post_meta($product_id, '_regular_price', true) ? get_post_meta($product_id, '_regular_price', true) : get_post_meta($product_id, '_price', true),
									'product_type'  => $product_type
								];
							}

						}



						/**
						 * Apply filters to modify the product price data.
						 *
						 * @param float $custom_price The custom price of the product.
						 * @return float The modified custom price.
						 *
						 * @since 3.1.2
						 */

						$custom_data['custom_price'] = apply_filters( 'wpfunnels/modify_main_product_price_data', $custom_data['custom_price'] );
						if( (wp_doing_ajax() && $is_bundle_exist) ){
							$custom_data = [];
						}
						if( 'composite' === $product_object->get_type() ){
							$is_regular = $price_type == 'original' ||  $price_type == 'sale' ? false : true;
							Wpfnl_functions::add_composite_product_to_cart_with_defaults( $product_id, $quantity,$is_regular );
						}else{
							WC()->cart->add_to_cart( $product_id, $quantity, '' , array(), $custom_data );
						}

                    }
                }
            }
        }

        //Add coupons for funnel checkout.
        $this->add_coupons( $checkout_id );

		/**
		 * After initialize funnels checkout
		 *
		 * This hook will trigger in checkout page
		 *
		 * @param init $funnel_id Funnel ID
		 *
		 * @since 2.7.19
		 */
		do_action( 'wpfunnels/after_init_checkout', $funnel_id );
    }


	public function add_bundle_product_in_cart( $product_id,$quantity, $custom_data , $bundled_items_data ){
		/**
		 * Apply filters to modify the product price data.
		 *
		 * @param float $custom_price The custom price of the product.
		 * @return float The modified custom price.
		 *
		 * @since 3.1.2
		 */
		$custom_data['custom_price'] = apply_filters( 'wpfunnels/modify_main_product_price_data', $custom_data['custom_price'] );


		WC()->cart->add_to_cart( $product_id, $quantity, '' , array(), $custom_data, $bundled_items_data );
	}


    /**
     * Checks if a variation is incomplete based on its attributes.
     *
     * @param int           $variation_id     The ID of the variation.
     * @param \WC_Product   $parent_product  (Optional) The parent product object. If not provided, it will be retrieved based on the variation ID.
     *
     * @return bool  True if the variation is incomplete, false otherwise.
     */
    private function is_variation_incomplete( $variation_id, $parent_product = null ) {
        if( !$parent_product ) {
            $parent_product = wc_get_product( $variation_id );
        }

        $var_product    = wc_get_product( $variation_id );
        $attributes     = $parent_product->get_attributes();
        $var_attributes = $var_product->get_attributes();

        return sizeof( $attributes ) !== sizeof( array_filter( $var_attributes ) );
    }


    /**
     * Adds coupons to the funnel checkout
     *
     * @param int|string $checkout_id Funnel checkout ID.
     *
     * @since 2.7.11
     */
    private function add_coupons( $checkout_id ) {
        $coupon = get_post_meta( $checkout_id, '_wpfnl_checkout_discount', true );
        if( is_array( $coupon ) && !empty( $coupon ) ) {
            $coupon = reset( $coupon );
        }
        WC()->session->set( 'order_bump_accepted', 'no' );

        if( !empty( $coupon ) ) {
            WC()->cart->add_discount( $coupon );
        }
    }

    /**
     * Renders express funnel checkout
     *
     * @param int|string $checkout_id Funnel checkout ID.
     *
     * @since 2.7.11
     */
    private function render_express_checkout( $checkout_id ) {
        // Only for express checkout
        if( \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && Wpfnl_functions::maybe_express_checkout( $checkout_id ) ) {
            remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review' );
            add_filter( 'woocommerce_update_order_review_fragments', [ $this, 'wpfnl_express_checkout_shipping_table_update' ], 10, 1 );
        }
    }

    /**
     * Get the first id of available product variations by parent id
     *
     * @param int|string $parent_id Parent product id.
     *
     * @return int|string|null
     * @since 2.7.11
     */
    private function get_first_variation_id( $parent_id ) {
        $product_id = get_posts( [
            'post_parent' => $parent_id,
            'fields'      => 'ids',
            'post_type'   => 'product_variation',
            'post_status' => [ 'private', 'publish' ],
            'numberposts' => 1,
            'orderby'     => 'menu_order',
            'order'       => 'asc'
        ] );
        return !empty( $product_id[ 0 ] ) ? $product_id[ 0 ] : null;
    }
    /**
     * Add default variation to funnel checkout
     *
     * @param string|int $product_id Variable parent ID.
     * @param \WC_Product $product Variable parent product.
     * @param string|int $quantity Product quantity.
     *
     * @return void
     * @since 2.7.11
     * @throws \Exception
     */
    private function add_default_variation( $product_id, \WC_Product $product, $quantity, $price_type ) {
        $attributes          = $product->get_attributes();
        $variation_id        = null;
        $default_variation   = $product->get_default_attributes();
        $formatted_variation = [];

        if( sizeof( $attributes ) === sizeof( $default_variation ) ) {
            $formatted_variation = $this->get_formatted_variation( $default_variation );
            if( !empty( $formatted_variation ) ) {
                $variation_id = ( new \WC_Product_Data_Store_CPT() )->find_matching_product_variation(
                    $product,
                    $formatted_variation
                );
            }
        }
        else {
            $variation_id = $this->get_first_variation_id( $product_id );
        }

        if( $variation_id ) {
            $this->add_variaiton_product_in_cart( $variation_id, $quantity, $price_type, 'variable', $formatted_variation, $product);
        }
    }

    /**
     * Adds a variation product to the cart.
     *
     * @param int           $variation_id        The ID of the variation.
     * @param int           $quantity            The quantity of the product to add to the cart.
     * @param array         $formatted_variation The formatted variation array with attribute keys and values.
     * @param \WC_Product   $parent_product      (Optional) The parent product object. If not provided, it will be retrieved based on the variation ID.
     *
     * @return void
     * @since 2.7.11
     */
    private function add_variaiton_product_in_cart( $variation_id, $quantity, $price_type, $product_type, $formatted_variation = [], $parent_product = null ) {
        if( !$quantity || !$variation_id ) {
            return;
        }

        $parent_id = wc_get_product( $variation_id )->get_parent_id();
        if( !$parent_product ) {
            $parent_product = wc_get_product( $parent_id );
        }

        if( empty( $formatted_variation ) ) {
            $is_var_incomplete   = $this->is_variation_incomplete( $variation_id, $parent_product );
            $formatted_variation = $is_var_incomplete ? $this->get_complete_variation_format( $variation_id, $parent_product ) : $formatted_variation;
        }
		$custom_data = [];
		if( $price_type == 'original' ||  $price_type == 'sale' ){
			$custom_data = [
				'custom_price' 	=> get_post_meta($variation_id, '_price', true) ? get_post_meta($variation_id, '_price', true) : get_post_meta($variation_id, '_regular_price', true),
				'product_type'  => $product_type
			];
		}else{
			$custom_data = [
				'custom_price' 	=> get_post_meta($variation_id, '_regular_price', true) ? get_post_meta($variation_id, '_regular_price', true) : get_post_meta($variation_id, '_price', true),
				'product_type'  => $product_type
			];

		}

		/**
		 * Apply filters to modify the product price data.
		 *
		 * @param float $custom_price The custom price of the product.
		 * @return float The modified custom price.
		 *
		 * @since 3.1.2
		 */
		$custom_data['custom_price'] = apply_filters( 'wpfunnels/modify_main_product_price_data', $custom_data['custom_price'] );

        WC()->cart->add_to_cart( $parent_id, $quantity, $variation_id, $formatted_variation, $custom_data );
    }



    /**
     * Formats the variation attributes into a formatted variation array.
     *
     * @param array  $var_attributes  The variation attributes.
     *
     * @return array  The formatted variation array with attribute keys and values.
     * @since 2.7.11
     */
    private function get_formatted_variation( array $var_attributes ) {
        $formatted_variation = [];

        foreach( $var_attributes as $key => $value ) {
            $formatted_variation[ "attribute_{$key}" ] = $value;
        }
        return $formatted_variation;
    }

    /**
     * Retrieves the complete variation format for a given variation ID and parent product.
     *
     * @param int           $variation_id     The variation ID.
     * @param \WC_Product   $parent_product  (Optional) The parent product object. If not provided, it will be retrieved based on the variation ID.
     *
     * @return array  The formatted variation array with attribute keys and values.
     * @since 2.7.11
     */
    private function get_complete_variation_format( $variation_id, $parent_product = null ) {
        if( !$variation_id ) {
            return [];
        }
        if( !$parent_product ) {
            $parent_product = wc_get_product( $variation_id );
        }
        $var_product    = wc_get_product( $variation_id );
        $var_attributes = $var_product instanceof \WC_Product ? $var_product->get_attributes() : [];
        $formatted_variation = [];

        foreach( $var_attributes as $key => $value ) {
            $value = $value ?: strtolower( $this->get_first_attribute_value( $key, $parent_product ) );
            $formatted_variation[ "attribute_{$key}" ] = $value;
        }
        return $formatted_variation;
    }

    /**
     * Retrieves the first value of a specified attribute from a WooCommerce product.
     *
     * @param string       $key      The attribute key.
     * @param \WC_Product  $product  The WooCommerce product object.
     *
     * @return string  The first attribute value, or an empty string if not found.
     * @since 2.7.11
     */
    private function get_first_attribute_value( $key, \WC_Product $product ) {
        $attribute = $product->get_attribute( $key );
        if( strpos( $attribute, '|' ) ) {
            $attribute = explode( '|', $attribute );
        }
        else {
            $attribute = explode( ',', $attribute );
        }
        return !empty( $attribute[ 0 ] ) ? trim( $attribute[ 0 ] ) : '';
    }

    /*============*/

	/**
	 * Update shiiping markup for express checkout
	 *
	 * @since 2.4.18
	 */
	public function wpfnl_express_checkout_shipping_table_update( $fragments ) {
		ob_start();
		require WPFNL_DIR.'/woocommerce/templates/checkout/review-order.php';
		$woocommerce_shipping_methods = ob_get_clean();
		$fragments['.woocommerce-checkout-review-order-table'] = $woocommerce_shipping_methods;
		return $fragments;
	}

	/**
	 * Render content for wpfunnels_checkout shortcode
	 *
	 * @param $atts
	 *
	 * @return string
	 *
	 * @since 2.0.3
	 */
	public function render_checkout_shortcode($atts)
	{
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts
		);
		$checkout_id = intval($atts['id']);
		if (is_admin()) {
			if (0 === $checkout_id && isset($_POST['id'])) {
				$checkout_id = intval($_POST['id']);
			}
		}

		if (empty($checkout_id)) {
			global $post;
			if(is_object($post)) {
				$checkout_id = intval($post->ID);
			}
		}
		if(!$checkout_id) {
			return '';
		}
		$checkout_layout = Wpfnl::get_instance()->meta->get_checkout_meta_value($checkout_id, 'wpfnl_checkout_layout', 'wpfnl-col-2');

		$floating_label = Wpfnl::get_instance()->meta->get_checkout_meta_value($checkout_id, 'wpfnl_floating_label', '');

		$output = '';
		ob_start();

		do_action('wpfunnels/before_gb_checkout_form', $checkout_id);

		$template_file = WPFNL_DIR . "public/modules/checkout/templates/checkout-template.php";
		include $template_file;

		do_action('wpfunnels/after_checkout_form', $checkout_id);

		$output .= ob_get_clean();
		return $output;

	}


	public function custom_price_to_cart_item($cart)
	{
		if ( wp_doing_ajax() && !WC()->session->__isset('reload_checkout')) {
			foreach ($cart->cart_contents as $key => $value) {
				if (isset($value['custom_price'])) {
					$custom_price = floatval($value['custom_price']);
					$value['data']->set_price($custom_price);
				}
			}
		}
	}

	/**
	 * Auto apply coupon on funnel checkout
	 *
	 * @param object $cart_object
	 *
	 * @since 2.7.9
	 * @return void
	 */
	public function apply_auto_coupon($cart_object)
	{
		global $post;
		if( !$post ){
			return false;
		}
		$checkout_id = '';
		if ( wp_doing_ajax() || isset( $_GET['wc-ajax'] ) ) {
			$checkout_id = Wpfnl_functions::get_checkout_id_from_post($_POST);
		} else {
			$checkout_id = $post->ID;
		}
		if( $checkout_id ){
			$coupon = Wpfnl_functions::get_applied_coupon( $checkout_id );
			if( $coupon ){
				if ( WC()->cart->has_discount( $coupon ) ) return;
				WC()->cart->apply_coupon( $coupon );
				add_action( 'woocommerce_after_order_notes', [$this, 'add_coupon_field']);
				wc_print_notices();
			}
		}

	}

	public function render_order_bump_popup()
	{
		$step_id = get_the_ID();
		$order_bump = get_post_meta($step_id, 'order-bump', true);
		$order_bump_settings = get_post_meta($step_id, 'order-bump-settings', true);
		require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-style3.php';
	}



	/**
	 * Insert helper hidden ids
	 *
	 * @since 1.0.0
	 */
	public function checkout_shortcode_metas()
	{
		if (Wpfnl_functions::check_if_this_is_step_type('checkout')) {
			global $post;
			$this->step_id = $post->ID;
			$step = new Wpfnl_Steps_Store_Data();
			$step->read($this->step_id);
			$this->funnel_id = $step->get_funnel_id();
			echo '<input type="hidden" class="input-hidden _wpfunnels_funnel_id" name="_wpfunnels_funnel_id" value="' . intval($this->funnel_id) . '">';
			echo '<input type="hidden" class="input-hidden _wpfunnels_checkout_id" name="_wpfunnels_checkout_id" value="' . intval($post->ID) . '">';
			echo '<input type="hidden" class="input-hidden _wpfunnels_order_unique_identifier" name="_wpfunnels_order_unique_identifier" value="' . uniqid() . '">';
			echo '<input type="hidden" class="input-hidden _wpfunnels_select_quantity" name="_wpfunnels_select_quantity" value="no">';
		}
	}


	/**
	 * Insert helper hidden ids
	 *
	 * @since 1.0.0
	 */
	public function add_coupon_field()
	{
		if (Wpfnl_functions::check_if_this_is_step_type('checkout')) {
			echo '<input type="hidden" class="input-hidden _wpfunnels_funnel_id" name="_wpfunnels_auto_coupon" value="yes">';
		}
	}


	/**
	 * Next step button ajax
	 *
	 * @since 1.0.0
	 */
	public function wpfnl_next_button_ajax()
	{

		$step_id 	= sanitize_text_field($_POST['step_id']);
		$funnel_id 	= get_post_meta($step_id, '_funnel_id', true);
		$parent_step_id = get_post_meta($step_id, '_parent_step_id', true);
		$step_id= $parent_step_id ? $parent_step_id : $step_id;
		$next_step 				= Wpfnl_functions::get_next_conditional_step( $funnel_id, $step_id );
		$url 	= isset($_POST['url']) ? $_POST['url'] : '';
		$parts = parse_url($url);
		$query = [];
		if( isset($parts['query']) ){
			parse_str($parts['query'], $query);
		}

		$next_step       = apply_filters( 'wpfunnels/next_step_data', $next_step );

		if ( $next_step ) {

			$custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step['step_id'] );
            if( $custom_url ){
                $response =  $custom_url;
            }else{
				$redirect_url = add_query_arg( $this->prepare_query_param($query), get_the_permalink($next_step['step_id']));
				if ($redirect_url) {
					$response = $redirect_url;
				} else {
					$response = 'error';
				}
			}

			ob_start();
			do_action( 'wpfunnels/trigger_cta', $step_id, $funnel_id );
			ob_get_clean();

			wp_send_json($response);
		}
		return false;
	}


	/**
	 * Prepare query param for redirect url
	 *
	 * @param String $name
	 * @param Array  $query
	 *
	 * @return Array
	 * @since  2.5.9
	 */
	private function prepare_query_param( $query ){
        $query_param = [];
		if( isset( $query['optin'], $query['uname'] ) ){
			$query_param['optin'] = $query['optin'];
			$query_param['uname'] = $query['uname'];
		}

		if( isset($query['wpfnl-order'], $query['wpfnl-key']) ){
			$query_param['wpfnl-order'] = $query['wpfnl-order'];
			$query_param['wpfnl-key'] = $query['wpfnl-key'];
		}

		/**
		 * Filter to change the query parameter of the redirect URL of funnel's steps.
		 * Returns the query parameter after applying the 'wpfunnels/prepare_query_param' filter.
		 *
		 * @param array $query_param
		 * @return array $query_param
		 * 
		 * @since 3.4.13
		 */
		return apply_filters( 'wpfunnels/prepare_query_param', $query_param );
	}


	/**
	 * Calculate Discount.
     *
	 * @since 1.1.5
	 */
	public function calculate_custom_price($discount_type, $discount_value, $product_price)
	{
		$custom_price = $product_price;

		if (!empty($discount_type)) {
			if ('discount-percentage' === $discount_type) {
				if ( $discount_value > 0 && $discount_value <= 100) {
					$custom_price = $product_price - (($product_price * $discount_value) / 100);
				}else{
					$custom_price = $product_price;
				}
			} elseif ('discount-price' === $discount_type) {
				if ($discount_value > 0 && $product_price >= $discount_value ) {
					$custom_price = $product_price - $discount_value;
				}else{
					$custom_price = $product_price;
				}
			}
		}

		return number_format($custom_price, 2);
	}

	public function wpfnl_update_variation(){

		$variations 	= $_POST['variations'];
		$product_id 	= $_POST['variations'][0]['product_id'];
		$variation_id 	= $_POST['variations'][0]['variation_id'];
		$quantity 		= $_POST['variations'][0]['quantity'];

		$_product = wc_get_product($variation_id);
		if( $_product ){
			$attribute = $_product->get_attributes();
			foreach ( $attribute as $key => $value) {
				if( $value ){
					$formatted_variation['attribute_'.$key] = $value;
				}else{
					foreach( $variations as $variation ){
						if( $variation['attr'] == $key ){
							$formatted_variation['attribute_'.$key] = $variation['value'];
						}
					}
				}
			}
		}


		$variation_id = (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
			new \WC_Product($product_id),
			$formatted_variation
		);

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if( !isset($cart_item['product_type'])|| (isset($cart_item['product_type']) && 'single-variation' === $cart_item['product_type']) ){
				if ($cart_item['product_id'] == $product_id) {
					WC()->cart->remove_cart_item($cart_item_key);
				}
			}
		}
		$cart_item_data = [
			'custom_price' 	=> get_post_meta($variation_id, '_price', true) ? get_post_meta($variation_id, '_price', true) : get_post_meta($variation_id, '_regular_price', true),
			'product_type'  => 'single-variation'
		];

		WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $formatted_variation, $cart_item_data);
	}





	/**
	 * Order Bump Ajax Initialized
	 * When you click on add order bump button it will add the product to cart and click again to recover cart
	 *
	 * @since 1.1.
	 */
	public function wpfnl_get_course_details()
	{
		$course_id 		= filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
		$course_meta = Wpfnl_lms_learndash_functions::get_course_details_by_id($course_id);
		// ld_update_course_access( 1, $course_id, false );
		$data = [
			'status'  => 'success',
			'course'  => $course_meta,
		];

		wp_send_json( $data );

	}


	/**
	 * Order Bump Ajax Initialized
	 * When you click on add order bump button it will add the product to cart and click again to recover cart
	 *
	 * @since 1.1.
	 */
	public function wpfnl_order_bump_ajax() {
		$step_id      = filter_input( INPUT_POST, 'step_id', FILTER_VALIDATE_INT );
		$product_id   = filter_input( INPUT_POST, 'product', FILTER_VALIDATE_INT );
		$checker      = filter_input( INPUT_POST, 'checker', FILTER_SANITIZE_SPECIAL_CHARS );
		$quantity     = filter_input( INPUT_POST, 'quantity', FILTER_SANITIZE_SPECIAL_CHARS );
		$key          = filter_input( INPUT_POST, 'key', FILTER_SANITIZE_SPECIAL_CHARS );
		$is_lms       = filter_input( INPUT_POST, 'is_lms', FILTER_SANITIZE_SPECIAL_CHARS );
		$user_id      = filter_input( INPUT_POST, 'user_id', FILTER_SANITIZE_SPECIAL_CHARS );
		$funnel_id    = Wpfnl_functions::get_funnel_id_from_step( $step_id );
		$type         = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
		$type         = !$type ? 'wc' : $type;
		$class_object = Wpfnl_Public_Type_Factory::build( $type );
		if( $class_object ) {
			$data = $class_object->wpfnl_order_bump_trigger( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $checker );
			if( $type === 'lms' ) {
				wp_send_json( $data );
			}
			elseif( $type === 'wc' ) {
				add_filter( 'woocommerce_locate_template', [ new Wpfnl_Public(), 'wpfunnels_woocommerce_locate_template' ], 20, 3 );
				wp_send_json( Wpfnl_functions::get_wc_fragments( $data ) );
			}
		}
	}


	/**
	 * Get replaceable orderbump products
	 */
	private function get_replaceable_ob_products( $ob_settings ){


		$replaceable_ob = [];
		foreach( $ob_settings as $key=>$settings ){
			if( $settings['isReplace'] ===  'yes' ){
				$replaceable_ob[] = [
					'id'       => $settings['product'],
					'quantity' => $settings['quantity'],
				];
			}
		}

		return $replaceable_ob;

	}


	/**
	 * Get calculable price
	 *
	 * @param \WC_Product $product
	 * @param $discount_apply
	 *
	 * @return string
	 *
	 * @since 2.0.5
	 */
	private function get_product_price( \WC_Product $product, $discount_apply = 'regular' ) {
		$price = $product->get_regular_price();
//		if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
//			$signUpFee 	= \WC_Subscriptions_Product::get_sign_up_fee( $product );
//			$price 		= $price + $signUpFee;
//		}
		return $discount_apply === 'sale' && $product->get_sale_price() ? $product->get_sale_price() : $price;
	}


	/**
	 * Add checkout products to cart
	 *
	 * @param $checkout_id
	 *
	 * @throws \Exception
	 */
	private function add_checkout_products($checkout_id)
	{
		$checkout_products = get_post_meta( $checkout_id, '_wpfnl_checkout_products', true );
		foreach ($checkout_products as $product) {
			$_product_id = $product['id'];
			$_qty = $product['quantity'];
			WC()->cart->add_to_cart($_product_id, $_qty);
		}
	}

	/**
	 * Add last updated checkout products to cart
	 *
	 * @param $updated_cart
	 *
	 * @throws \Exception
	 */
	private function update_checkout_with_last_updated_cart($updated_cart){
		foreach ($updated_cart as $cart) {
			WC()->cart->add_to_cart( $cart['product_id'], $cart['quantity'], $cart['variation_id'], $cart['variation'], $cart['line_total']);
		}
	}


	public function wpfnl_checkout_cart()
	{
		$values = [];
		parse_str(sanitize_text_field($_POST['post_data']), $values);
		$cart = $values['cart'];
		foreach ($cart as $cart_key => $cart_value) {
			WC()->cart->set_quantity($cart_key, $cart_value['qty'], false);
			WC()->cart->calculate_totals();
			woocommerce_cart_totals();
		}
		wp_die();
	}


	/**
	 * Order Bump Templates
	 *
	 * @param $settings
	 *
	 * @since 1.0.0
	 */
	public function render_order_bump_template($settings)
	{
		if (!empty($settings['selectedStyle'])) {
			require_once WPFNL_DIR . 'public/modules/checkout/templates-style/order-bump-template-' . $settings['selectedStyle'] . '.php';
		}
	}


	/**
	 * Display coupon field or not
	 *
	 * @since 2.0.5
	 */
	public function display_coupon_field() {
		if ( Wpfnl_functions::check_if_this_is_step_type('checkout') ) {
			global $post;
			$checkout_id = $post->ID;
			$show_coupon = Wpfnl::get_instance()->meta->get_checkout_meta_value( $checkout_id, '_wpfnl_checkout_coupon' );
			if( 'no' === $show_coupon ) {
				remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form' );
			}
		}
	}

	/**
	 * Out of Stock message in checkout page
     *
	 * @param $message
	 * @param $product_data
	 *
	 * @return string
	 */

	public function wpfnl_out_of_stock_message_in_checkout( $message, $product_data ){
		$message = __( 'Sorry, the following product(s) that you are willing to purchase is out of stock at the moment.', 'wpfnl' );
		$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_data->get_id() ), array( 100, 100) );
		$message .= '<div class="wpfnl-checkout-stock-message"><img src="'.$image[0].'"><h4>'.$product_data->get_title().' </h4></div>';
		return $message;
	}


	/**
	 * Remove Astra theme checkout shipping hook
	 */
	public function remove_astra_theme_checkout_shipping_hook(){
		return true;
	}


	/**
	 * Update quantity from checkout page
	 *
	 * @param $quantity, $cart_item, $cart_item_key
	 */
	public function wpfnl_checkout_cart_item_quantity( $quantity, $cart_item, $cart_item_key ) {

		$step_id = 0;
		$isQuantity = 'no';

		if( wp_doing_ajax() ) {
			$step_id        = isset($_POST['step_id']) ? $_POST['step_id'] : 0;
		} else {
			$step_id = get_the_ID();
		}


		$isQuantity = get_post_meta($step_id, '_wpfnl_quantity_support',true);
		$quantityLimit 	=  get_post_meta($step_id, '_wpfnl_quantity_limit', true);
		$order_bump_product = get_post_meta($step_id,'order-bump-settings',true);

		if($isQuantity === 'yes'){
			if(isset($order_bump_product['product']) && isset($order_bump_product['isEnabled'])){

				if( ($order_bump_product['product'] == $cart_item["product_id"]) && $order_bump_product['isEnabled'] == 'yes' ){
					return $quantity;
				}
				$variations = json_encode($cart_item['variation']);
				$product_id = $cart_item["product_id"];
				$quantity = $cart_item["quantity"];
				$variation_id = $cart_item["variation_id"];
				$isQuantityLimit = false;
				$set_quantity = 0;

				if( isset($quantityLimit['isEnabled']) && $quantityLimit['isEnabled'] === 'yes' ){
					$set_quantity = $quantityLimit['quantity'];
					$isQuantityLimit = true;
				}

				if( $isQuantityLimit ){
					$quantity = "× <input type='number' min='1' max='".$set_quantity."'  value='".$quantity."' class='wpfnl-quantity-setect' data-product-id='".$product_id."' data-variation='".$variations."' data-variation-id='".$variation_id."' data-quantity-limit='".$set_quantity."' data-set-quantity='yes'/>";
				}else{
					$quantity = "× <input type='number' min='1' value='".$quantity."' class='wpfnl-quantity-setect' data-product-id='".$product_id."' data-variation='".$variations."' data-variation-id='".$variation_id."' data-set-quantity='no' />";
				}

			}
		}
		return $quantity;

	}


	public function before_checkout_form_actions( $checkout_id ) {

		if( Wpfnl_functions::is_wc_active() ){

			remove_all_actions( 'woocommerce_checkout_billing' );
			remove_all_actions( 'woocommerce_checkout_shipping' );

			add_action( 'woocommerce_checkout_billing', array( WC()->checkout, 'checkout_form_billing' ) );
			add_action( 'woocommerce_checkout_shipping', array( WC()->checkout, 'checkout_form_shipping' ) );
		}

	}


	/**
	 * Set checkout ID hidden field in checkout page.
	 *
	 * @param array $checkout
	 *
	 * @return void
	 */
	public function wpfnl_set_checkout_id( $checkout ) {
		global $post;
		$checkout_id = 0;
		if ( Wpfnl_functions::is_funnel_checkout_page() ) {
			if( isset($post->ID) ){
				$checkout_id = $post->ID;
			}else{
				$checkout_id = isset($_POST['step_id']) ? $_POST['step_id'] : '';
			}

		}
		if( $checkout_id ){
			echo '<input type="hidden" class="input-hidden _wpfunnels_checkout_id" name="_wpfunnels_checkout_id" value="' . intval( $checkout_id ) . '">';
		}
	}


	/**
	 * Redirect funnel checkout after login in checkout page
	 *
	 * @param $redirect_url
	 * @param $user
	 *
	 * @return false|string
	 */
	public function wpfnl_redirect_after_login( $redirect_url, $user ) {

		if (isset($_POST['_wpfunnels_checkout_id'])) {
			$funnel_checkout_id = $_POST['_wpfunnels_checkout_id'];
			$redirect_url = get_the_permalink($funnel_checkout_id);
		}
		return $redirect_url;

	}


	/**
     * Get Custom  Woocommerce template
     *
     * @param $template
     * @param $template_name
     * @param $template_path
	 *
     * @return mixed|string
     */

    public static function wpfunnels_woocommerce_locate_template($template, $template_name, $template_path)
    {
		/***
		 * Fires when change the wc template
		 *
		 * @since 2.8.21
		 */
		if( apply_filters( 'wpfunnels/maybe_locate_template', true ) ){
			global $woocommerce;
			$_template 		= $template;
			$plugin_path 	= WPFNL_DIR . '/woocommerce/templates/';

			if (file_exists($plugin_path . $template_name)) {
				$template = $plugin_path . $template_name;
			}

			if ( ! $template ) {
				$template = $_template;
			}
		}
        return $template;
    }


	/**
	 * Discount on checkout
	 *
	 * @param $cart_object
	 */
	public function wpfnl_checkout_discount( $cart_object ){

		if( !$this->is_funnel_checkout_product_available_on_cart() ){
			return;
		}

		if ( is_admin() && wp_doing_ajax() ){
			return;
		}
		if( isset( $_POST['wpfnl_offer_page'] ) ){
			return;
		}

		$step_id = Wpfnl_functions::get_checkout_id_from_post( $_POST );

		if( !$step_id ){
			$step_id = isset($_POST['step_id']) ? $_POST['step_id'] : '';
		}

		if( !$step_id ){
			$step_id = isset($_POST['_wpfunnels_checkout_id']) ? $_POST['_wpfunnels_checkout_id'] : '';
		}

		if( !$step_id ) {
			return;
		}

		$funnel_id = Wpfnl_functions::get_funnel_id_from_step( $step_id );

		if( !$funnel_id ){
			return;
		}

		$discount_instance = new WpfnlDiscount();

		if( $discount_instance->maybe_time_bound_discount( $step_id ) && !$discount_instance->maybe_validate_discount_time( $step_id )) {
			return;
		}

		$discount = $discount_instance->get_discount_settings( $step_id );

		if( !is_array($discount) ){
			return;
		}
		
		if( (isset($discount['discountOptions']) &&  $discount['discountOptions'] !== 'original') ){
			if( 'regular' === $discount['discountapplyto'] ){
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					$product = $cart_item['data'];
					if (isset($cart_item['bundled_by']) || $product->is_type('bundle') ) { // 'bundled_by' is a common meta key used for bundled items
						$regular_price = $product->get_regular_price();
						$regular_price = $regular_price ? $regular_price : $product->get_price();
						$cart_item['data']->set_price($regular_price);
					}
				}
			}

			$helper = CheckoutHelper::getInstance();
			$total = $helper->get_custom_subtotal();
			$discount_amount = $discount_instance->get_discount_amount( $discount['discountOptions'], $discount['mutedDiscountValue'], $total );
			$discount_amount = filter_var($discount_amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
			$discount_amount = apply_filters('wpfunnels/checkout_discount_amount', $discount_amount);
			$discount_amount = floatval($discount_amount);
			$cart_object->add_fee( __('Discount','wpfnl'), -$discount_amount,  true,'standard');
		}
	}


	/**
	 * Check whether funnel checkout product is available on cart or not
	 * 
	 * @return boolean true if funnel checkout product is available on cart, false otherwise
	 * 
	 * @access public
	 * @since 3.4.15
	 */
	public function is_funnel_checkout_product_available_on_cart() {
		if( WC()->cart->is_empty() ){
			return false;
		}
		$is_available = false;
		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			if( !isset( $cart_item['wpfnl_order_bump']) ){
				$is_available = true;
				break;
			}
		}
		return $is_available;
	}


	/**
	 * Get price type
	 */
	public function get_product_price_type( $funnel_id, $step_id ){

		$is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true );
		if( $is_gbf == 'no' || !$is_gbf ){
			$discount_instance = new WpfnlDiscount();
			if( $discount_instance->maybe_time_bound_discount( $step_id ) && !$discount_instance->maybe_validate_discount_time( $step_id ) ) {
				return 'original';
			}

			$discount = $discount_instance->get_discount_settings( $step_id );
			if( is_array($discount) ){
				if( $discount['discountOptions'] !== 'original' ){
					if( $discount['discountapplyto'] == 'regular' ){
						return 'regular';
					}else{
						return 'sale';
					}
				}
			}
		}
		return 'original';
	}

	/**
	 * After optin form submition set cookie data
	 * Use cookes data for checkout billings fields
     *
	 * @param $step_id
	 * @param $post_action
	 * @param $action_type
	 * @param $record
	 */

	public function get_optin_data_checkout($step_id, $post_action, $action_type, $record){
		if(isset($record->form_data['data_to_checkout']) && 'yes' == $record->form_data['data_to_checkout']){
			/**
			 * Set Cookie Data
			*/
			$cookie_name        = 'wpfunnels_send_data_checkout';
			$cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
			$cookie['after_optin_submit_send_for_checkout']   = ( isset($record->form_data['email']) && $record->form_data['email'] ) ? $record->form_data : false;
			ob_start();
			setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
			ob_end_flush();
		}
	}

	/**
	 * Get Optin cookes data in checkout fields
  	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function set_option_data_in_checkout_filed($fields)
	{
		$cookie_name        = 'wpfunnels_send_data_checkout';
		$cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
		if(!empty($cookie)){
			if(isset($cookie['after_optin_submit_send_for_checkout'])){
				$first_name  	= isset($cookie['after_optin_submit_send_for_checkout']['first_name']) ? $cookie['after_optin_submit_send_for_checkout']['first_name'] : '';
				$last_name  	= isset($cookie['after_optin_submit_send_for_checkout']['last_name']) ? $cookie['after_optin_submit_send_for_checkout']['last_name'] : '';
				$phone   		= isset($cookie['after_optin_submit_send_for_checkout']['phone']) ? $cookie['after_optin_submit_send_for_checkout']['phone'] : '';
				$email   		= isset($cookie['after_optin_submit_send_for_checkout']['email']) ? $cookie['after_optin_submit_send_for_checkout']['email'] : '';
				$fields['billing']['billing_first_name']['default'] = $first_name;
				$fields['billing']['billing_last_name']['default'] 	= $last_name;
				$fields['billing']['billing_phone']['default'] 		= $phone;
				$fields['billing']['billing_email']['default'] 		= $email;
			}
		}
		return $fields;
	}



	/**
	 * Modifies the available payment gateways based on certain conditions.
	 *
	 * @param array $available_gateways The array of available payment gateways.
	 * @return array The modified array of available payment gateways.
	 *
	 * @since 3.1.8
	 */
	public function conditional_payment_gateways( $available_gateways ){
		if( defined('WPFNL_PRO_VERSION') ){
			$is_checkout = Wpfnl_functions::is_funnel_checkout_page();
			if( $is_checkout['status'] ){
				$is_funnel_checkout = Wpfnl_functions::check_if_this_is_step_type_by_id( $is_checkout['id'], 'checkout' );
				if( $is_funnel_checkout ){
					$disableGateways 		=  get_post_meta($is_checkout['id'], '_wpfnl_disabled_payemnts', true);
					if( is_array($disableGateways) && !empty($disableGateways ) ){
						foreach( $available_gateways as $key=>$gateway ){
							if (in_array($key, $disableGateways)){
								unset($available_gateways[$key]);
							}
						}
					}

				}
			}
		}
		return $available_gateways;
	}
}
