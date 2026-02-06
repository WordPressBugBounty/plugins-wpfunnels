<?php

namespace WPFunnels\Offer;

use WPFunnels\Frontend\Module\Wpfnl_Frontend_Module;
use WPFunnels\Wpfnl_functions;

use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnels\Gateway\Payment_Gateways_Factory;
use WPFunnelsPro\Wpfnl_Pro;
use WPFunnels\Discount\WpfnlDiscount;
use WPFunnels\Wpfnl;

class Wpfnl_Offer extends Wpfnl_Frontend_Module
{
    public $stripe;
    public $offerStatus;
    public function __construct()
    {

        add_action( 'wp_enqueue_scripts', array( $this, 'load_offer_scripts' ) );
        add_action( 'wp_footer', array( $this, 'load_offer_loader' ) );

        /**
         * init upsell/downsell gateways
         * if user is in funnel
         */
        add_action( 'woocommerce_pre_payment_complete', array( $this, 'trigger_upsell_downsell_process' ), 99, 1 );

        /**
         * trigger the upsell/downsell process if this is not
         * triggered on previous hooks
         */
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'maybe_trigger_upsell_downsell_process' ), 100, 3 );

        add_shortcode('funnel_sell_accept', [$this, 'funnel_upsell_shortcode_button']);
        add_shortcode('funnel_sell_reject', [$this, 'funnel_upsell_reject_shortcode_button']);

        add_action('wp_ajax_wpfnl_upsell_accepted', [$this, 'process_upsell_accepted_action']);
        add_action('wp_ajax_nopriv_wpfnl_upsell_accepted', [$this, 'process_upsell_accepted_action']);

        add_action('wp_ajax_wpfnl_upsell_rejected', [$this, 'process_upsell_rejected_action']);
        add_action('wp_ajax_nopriv_wpfnl_upsell_rejected', [$this, 'process_upsell_rejected_action']);


        add_action('wp_ajax_wpfnl_downsell_accepted', [$this, 'process_downsell_accepted_action']);
        add_action('wp_ajax_nopriv_wpfnl_downsell_accepted', [$this, 'process_downsell_accepted_action']);

        add_action('wp_ajax_wpfnl_downsell_rejected', [$this, 'process_downsell_rejected_action']);
        add_action('wp_ajax_nopriv_wpfnl_downsell_rejected', [$this, 'process_downsell_rejected_action']);

        add_action('wp_ajax_wpfnl_after_process_with_auth_ajax', [$this, 'wpfnl_after_process_with_auth_ajax']);
        add_action('wp_ajax_nopriv_wpfnl_after_process_with_auth_ajax', [$this, 'wpfnl_after_process_with_auth_ajax']);


        //== Discount price support ==//
        add_filter('wpfnl_offer_custom_price', [$this, 'wpfnl_modify_product_price'], 10, 1);
        add_filter('wpfnl_offer_product_price', [$this, 'wpfnl_modify_product_price'], 10, 1);


        add_action('wpfunnels/after_offer_button', [$this, 'after_offer_button'], 10);
        add_filter('woocommerce_is_checkout', [$this, 'make_offer_page_as_checkout'],10);
        add_filter('wpfunnels/current_step_id', [$this, 'current_step_id'],10, 2);
        add_filter('wpfunnels/order_meta', [$this, 'update_order_key'],10, 2);


        add_action('woocommerce_checkout_order_processed', [$this, 'before_offer_checkout_process'], 10, 2);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_checkout_fields'], 10, 2);

        add_action('wp_ajax_wpfnl_load_payment', [$this, 'add_offer_product_to_cart']);
        add_action('wp_ajax_nopriv_wpfnl_load_payment', [$this, 'add_offer_product_to_cart']);
        add_action('woocommerce_after_checkout_validation', [$this, 'remove_checkout_validation'], 10, 2);

        wp_ajax_helper()->handle('wpfnl-get-variation-id')
            ->with_callback([ $this, 'wpfnl_get_variation_id' ]);

        // Register admin AJAX handler for saving offer settings
        wp_ajax_helper()->handle('update-offer-settings')
            ->with_callback([ $this, 'update_settings' ])
            ->with_validation([
                'logged_in' => true,
                'user_can' => 'wpf_manage_funnels',
            ]);

    }

    /**
     * Save offer (upsell/downsell) settings from admin
     *
     * @param array $payload The settings data from Vue component
     * @return array Response with success status and message
     * @since 3.1.0
     */
    public function update_settings($payload) {  
        $step_id        = sanitize_text_field($payload['step_id']);
        $products       = isset($payload['products']) ? $payload['products'] : '';
        $type           = sanitize_text_field($payload['type']);
        $discount       = isset($payload['discount']) ? $payload['discount'] : '';

        // Save products if provided
        if($products){
            update_post_meta( $step_id, '_wpfnl_'.$type.'_products', $products );
        }
        
        // Save discount settings
        update_post_meta( $step_id, '_wpfnl_'.$type.'_discount', $discount );
        
        $step_info = array(
            'step_id'   => $step_id,
            'step_type' => $type,
        );

        // Handle replacement settings (pro feature if available)
        if( isset($payload['replaceSettings']) && $payload['replaceSettings'] == 'true' ){
            update_post_meta( $step_info['step_id'], '_wpfnl_'.$step_info['step_type'].'_replacement_settings', 'true' );
            
            // Only handle OrderReplacement if pro plugin is available
            if( class_exists('WPFunnelsPro\Admin\Modules\Steps\Module\OrderReplacement') ){
                $offerReplacement = isset($payload['offerReplacement']) ? $payload['offerReplacement'] : array();
                $replacement_type = isset($offerReplacement['replacement_type']) && $offerReplacement['replacement_type'] ? $offerReplacement['replacement_type'] : '';
                $value = isset($offerReplacement['value']) && $offerReplacement['value'] ? $offerReplacement['value'] : '';
                \WPFunnelsPro\Admin\Modules\Steps\Module\OrderReplacement::save_replace_order_condition( $step_info, $replacement_type, $value );
            }
        }else{
            update_post_meta( $step_info['step_id'], '_wpfnl_'.$step_info['step_type'].'_replacement_settings', 'false' );
            delete_post_meta( $step_info['step_id'], '_wpfnl_'.$step_info['step_type'].'_replacement' );
        }

        // Save time-bound discount settings
        if( isset($payload['timeBoundDiscount'])){
            update_post_meta($step_id, '_wpfnl_time_bound_discount_settings', $payload['timeBoundDiscount']);
        }
        
        return array(
            'success'   => true,
            'step_id'   => $step_id,
            'message'   => __('Product Saved Successfully', 'wpfnl')
        );
    }

    /**
     * Removes the checkout validation for the upsell module.
     * This is required to prevent the checkout validation from running on the offer page.
     *
     * @param array $data The data submitted during checkout.
     * @param array $errors The validation errors.
     * @return array The modified data and errors.
     * @since 2.0.5
     */
    public function remove_checkout_validation($data, $errors) {
        if ( isset( $_POST['wpfnl_offer_page'] ) ) {
            $errors->errors = [];
            $errors->error_messages = [];
            $errors->error_data = [];
        }
    }


    /**
     * Adds the offer product to the cart.
     * This method is used to add the offer product to the cart when the user clicks on the offer button.
     *
     * @return void
     * @since 2.0.5
     */
    public function add_offer_product_to_cart(){
        $product_id     = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $quantity       = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 0;
        $variation_id   = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;
        $step_id        = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        if( isset( $_POST['is_variable'], $_POST['attr'] ) && $_POST['is_variable'] == 'true' ){
            $payload = [
                'product_id' => $product_id,
                'data'       => $_POST['attr'],
            ];
            $variation_id = $this->wpfnl_get_variation_id($payload);
        }


        $total = $this->get_discounted_price( $step_id, $product_id, $quantity );
        $total = floatval($total)/intval($quantity);
        $custom_data = [
            'custom_price' 	=> $total,
        ];

        // Make sure WC cart is loaded before using it
        if (!function_exists('WC') || !WC()->cart) {
            wc_load_cart();
        }

        // Check again to ensure cart is available
        if (function_exists('WC') && WC()->cart) {
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, [], $custom_data);
        }
        
        $result = [
            'success' => true,
        ];
        wp_send_json($result);
    }


    /**
     * Calculates the discounted price for a given step, product, and quantity.
     *
     * @param int $step_id The ID of the step.
     * @param int $product_id The ID of the product.
     * @param int $quantity The quantity of the product.
     * @return float The discounted price.
     * @since 2.0.5
     */
    public function get_discounted_price( $step_id, $product_id, $quantity ){
        $discount_instance = new WpfnlDiscount();


        $type = get_post_meta($step_id, '_step_type', true);
        $discount_data = get_post_meta($step_id, '_wpfnl_'.$type.'_discount', true);
        $product = wc_get_product( $product_id );
        $total = isset($discount_data['discountApplyTo']) && 'regular' === $discount_data['discountApplyTo'] ? $product->get_regular_price() : $product->get_sale_price();
        $total = !$total ? $product->get_price() : $total;
        $total = floatval($total) * intval($quantity);
        if( $discount_instance->maybe_time_bound_discount( $step_id ) && !$discount_instance->maybe_validate_discount_time( $step_id ) ) {
            return $total;
        }
        if( isset($discount_data['discountType']) && $discount_data['discountType'] && 'original' !== $discount_data['discountType'] ){

            $discount_amount = $discount_instance->get_discount_amount( $discount_data['discountType'], $discount_data['discountValue'], $total );
            $discount_amount = filter_var($discount_amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $discount_amount = floatval($discount_amount);
            $total = floatval($total) - floatval($discount_amount);
        }

        return $total;
    }


    /**
     * Saves the checkout fields for an order.
     * This method is used to save the offer details on the order.
     *
     * @param int   $order_id The ID of the order.
     * @param array $posted   The posted data from the checkout form.
     * @return void
     * @since 2.0.5
     */
    public function save_checkout_fields( $order_id, $posted ){

        if ( isset( $_POST['wpfnl_offer_page'] ) ) {
            $order = wc_get_order($order_id);
            $step_id = isset($_POST['wpfnl_step_id']) ? $_POST['wpfnl_step_id'] : 0;
            $funnel_id = isset($_POST['wpfnl_funnel_id']) ? $_POST['wpfnl_funnel_id'] : 0;
            $type = isset($_POST['wpfnl_offer_type']) ? $_POST['wpfnl_offer_type'] : '';
            $parent_order_id = isset($_POST['wpfnl_parent_order_id']) ? $_POST['wpfnl_parent_order_id'] : 0;
            $parent_order_key = isset($_POST['wpfnl_parent_order_key']) ? $_POST['wpfnl_parent_order_key'] : '';

            $order->update_meta_data('_wpfunnels_offer', 'yes');
            $order->update_meta_data('_wpfunnels_order', 'yes');
            $order->update_meta_data('_wpfunnels_offer_type', sanitize_text_field($type));
            $order->update_meta_data('_wpfunnels_parent_funnel_id', intval($funnel_id));
            $order->update_meta_data('_wpfunnels_funnel_id', intval($funnel_id));
            $order->update_meta_data('_wpfunnels_offer_step_id', intval($step_id));
            $order->update_meta_data('_wpfunnels_offer_parent_id', intval($parent_order_id));
            $parent_order = wc_get_order($parent_order_id);
            $order->update_meta_data('_wpfunnels_offer_parent_key', $parent_order_key);

            $offer_orders_meta = $parent_order->get_meta('_wpfunnels_offer_child_orders');
            if ( !is_array($offer_orders_meta) ) {
                $offer_orders_meta = [];
            }

            $offer_orders_meta[$order->get_id()] = ['type' => $type];
            $parent_order->update_meta_data('_wpfunnels_offer_child_orders', $offer_orders_meta);
            $parent_order->save();
            $order->calculate_totals();
            $order->save();

            $actual_product_id = '';
            $quantity = '';
            foreach ($order->get_items() as $item_id => $item) {
                $product_id = $item->get_product_id();
                $variation_id = $item->get_variation_id();
                $actual_product_id = $variation_id ? $variation_id : $product_id;
                $quantity = $item->get_quantity();
                break; // Exit the loop after the first iteration
            }

            $offer_product = self::update_variation_product_details( $step_id, $actual_product_id, $quantity, $order_id  );
            /**
             * $order /WC_Order object
             * $step_type String
             * $offer_product Array
             * $offer_product['step_id'] String represent the associate step id
             * $offer_product['id'] String represent the id of the product
             * $offer_product['name'] String represent the name of the product
             * $offer_product['desc'] String represent the description of the product
             * $offer_product['qty'] String represent the quantity of the product
             * $offer_product['original_price'] String represent the price of the product
             * $offer_product['unit_price'] String represent the unit_price of the product
             * $offer_product['unit_price_tax'] String represent the unit_price with tax of the product
             * $offer_product['args'] Array represent the extra arguments of the product
             * $offer_product['args']['subtotal'] String represent the subtotal of the product
             * $offer_product['args']['total'] String represent the total of the product
             * $offer_product['price'] String represent the price of the product
             * $offer_product['url'] String represent the url of the product
             * $offer_product['total_unit_price_amount'] String represent the $unit_price_tax * $product_qty of the product
             * $offer_product['total'] String represent the $custom_price of the product if any
             * $offer_product['cancel_main_order'] Bool checker if cancel main order is enabled/disabled from funnel settings
             */
            ob_start();
            do_action( 'wpfunnels/offer_accepted', $order, $offer_product );
            ob_get_clean();
        }
    }


    /**
     * Executes before the offer checkout process.
     * This method is used to set the billing and shipping address on the new order.
     *
     * @param int $order_id The ID of the order.
     * @param array $posted_data The data posted during checkout.
     * @return void
     * @since 2.0.5
     */
    public function before_offer_checkout_process($order_id, $posted_data) {
        if ( isset( $_POST['wpfnl_offer_page']) && isset( WC()->session )  ) {
            $parent_order_id = $_POST['wpfnl_parent_order_id'];
            if ($order_id) {
                $this->set_address_on_new_order($order_id, $parent_order_id);
            }
        }
    }


    /**
     * Sets the address on a new order based on the previous order.
     *
     * @param int $order_id The ID of the new order.
     * @param int $previous_order_id The ID of the previous order.
     * @return void
     * @since 2.0.5
     */
    private function set_address_on_new_order($order_id, $previous_order_id) {
        $order = wc_get_order($order_id);

        if ( false === is_a( $order, 'WC_Order' ) ) {
            return; // Order not found
        }

        $address = $this->get_address_from_previous_order($previous_order_id);

        if ($address) {
            // Set billing and shipping addresses
            $order->set_address($address['billing'], 'billing');
            $order->set_address($address['shipping'], 'shipping');

            // Save the order
            $order->save();
        }
    }


    /**
     * Retrieves the address from a previous order.
     *
     * @param int $previous_order_id The ID of the previous order.
     * @return array|null The address details if found, null otherwise.
     * @since 2.0.5
     */
    private function get_address_from_previous_order($previous_order_id) {
        $previous_order = wc_get_order($previous_order_id);
        if ( false === is_a( $previous_order, 'WC_Order' ) ) {
            return false; // Previous order not found
        }

        // Get address from previous order
        return array(
            'billing' => $previous_order->get_address('billing'),
            'shipping' => $previous_order->get_address('shipping')
        );
    }


    /**
     * Makes the offer page act as a checkout page.
     * This is required for some payment gateways to work properly.
     *
     * @param bool $isCheckout Whether the offer page should act as a checkout page.
     * @return void
     * @since 2.0.5
     */
    public function make_offer_page_as_checkout($isCheckout){
        if( self::is_upsell_downsell_step() && Wpfnl_functions::maybe_unsupported_payment_gateway() ){
            return true;
        }
        return $isCheckout;
    }


    /**
     * Sets the current step ID for the upsell module.
     * This is required for some payment gateways to work properly.
     *
     * @param int $step_id The ID of the current step.
     * @return int The ID of the current step.
     * @since 2.0.5
     */
    public function current_step_id( $step_id, $order ){
        $current_step_id = $order->get_meta('_wpfunnels_offer_step_id');
        if(  $current_step_id ){
            return $current_step_id;
        }
        return $step_id;
    }


    /**
     * Updates the order key.
     * This is required for some payment gateways to work properly.
     *
     * @param array $query_args The query arguments.
     * @param object $order The order object.
     * @return array The updated query arguments.
     * @since 2.0.5
     */
    public function update_order_key( $query_args, $order ){
        $parent_order_id = $order->get_meta('_wpfunnels_offer_parent_id');
        $parent_order_key = $order->get_meta('_wpfunnels_offer_parent_key');
        if(  $parent_order_id && $parent_order_key ){
            $query_args = array(
				'wpfnl-order' => $parent_order_id,
				'wpfnl-key' => $parent_order_key,
				'key' => $parent_order_key,
			);
        }
        return $query_args;
    }




    /**
     * This method is called after the offer button is displayed.
     * It can be used to perform additional actions or modifications.
     *
     * @return void
     * @since 2.0.5
     */
    public function after_offer_button(){
        global $post;
        $step_id                    = $post->ID;
        $funnel_id                  = get_post_meta( $step_id, '_funnel_id', true );
        $offer_type         = get_post_meta( $step_id, '_step_type', true );
        $order_id = ( isset( $_GET['wpfnl-order'] ) ) ? intval( $_GET['wpfnl-order'] ) : 0;
        $order_key = ( isset( $_GET['wpfnl-key'] ) ) ? sanitize_text_field( wp_unslash( $_GET['wpfnl-key'] ) ) : '';
        $offer_product              = self::get_offer_product_data( $step_id, '', '', $order_id );
        $quantity                   = 0;
        $product_id                 = '';


        if ( is_array($offer_product) && !empty($offer_product)) {
            $product_id = $offer_product['id'];
            $quantity   = $offer_product['qty'];
        }
        $is_variable = false;
        if( !$product_id ){
            return;
        }

        // Make sure WC cart is loaded before using it
        if (!function_exists('WC') || !WC()->cart) {
            wc_load_cart();
        }

        // Check if cart is available before using it
        if (function_exists('WC') && WC()->cart) {
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart( $product_id, $quantity, 0, []);
        }

        ?>
        <div class="wpfnl-offer-payment-form" style="display: none;">
            <div class="wpfnl-offer-payment-form-inner">
                <div class="wpfnl-offer-payment-form-wrapper">
                    <button class="close-modal" type="button">
                        <svg width="12" height="13" fill="none" viewBox="0 0 12 13" xmlns="http://www.w3.org/2000/svg"><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 1.969l-10 9.99m0-9.99l10 9.99"></path></svg>
                    </button>

                    <h3 class="wpfnl-offer-payment-title"><?php
                        echo __( 'Complete Payment', 'wpfnl-pro' );
                    ?></h3>
                    <div class="modal-overflow wpfnl-checkout">
                        <div class="modal-loader">
                            <span class="wpfnl-loader"></span>
                            <h4><?php
                                echo __( 'Loading', 'wpfnl-pro' );
                            ?></h4>
                            <p><?php
                                echo __( 'Please wait...', 'wpfnl-pro' );
                            ?></p>
                        </div>

                        <form name="checkout" method="post" class="checkout woocommerce-checkout rex-checkoutify-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" style="display: none">
                            <input type="hidden" name="wpfnl_offer_page" value="yes"/>
                            <input type="hidden" name="wpfnl_step_id" value="<?php echo $step_id;?>"/>
                            <input type="hidden" name="wpfnl_funnel_id" value="<?php echo $funnel_id;?>"/>
                            <input type="hidden" name="wpfnl_offer_type" value="<?php echo $offer_type;?>"/>
                            <input type="hidden" name="wpfnl_parent_order_id" value="<?php echo $order_id;?>"/>
                            <input type="hidden" name="wpfnl_parent_order_key" value="<?php echo $order_key;?>"/>
                        <?php
                        // Get checkout object
                        $checkout = WC()->checkout();
                        // Show billing country field
                        woocommerce_form_field( 'billing_country', $checkout->get_checkout_fields()['billing']['billing_country'], $checkout->get_value( 'billing_country' ) );
                        woocommerce_checkout_payment();
                        ?>
                        <form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * trigger upsell/downsell process
     *
     * @param $order_id
     * @param $posted_data
     * @param $order
     *
     * @since 1.0.0
     */
    public function maybe_trigger_upsell_downsell_process($order_id, $posted_data, $order) {
    }


    /**
     * setup
     *
     * @param string $order_id
     *
     * @since 1.0.0
     */
    public function trigger_upsell_downsell_process( $order_id = '' ) {

        if ( '' == $order_id ) {
            return;
        }
        $order = wc_get_order( $order_id );
        $this->start_upsell_downsell_process( $order );
    }


    /**
     * start the upsell/downsell process
     *
     * @param $order
     *
     * @since 1.0.0
     */
    private function start_upsell_downsell_process($order) {
        if ( ! is_object( $order ) ) {
            return;
        }

        $order_gateway = $order->get_payment_method();
        $payment_gateway_obj = Payment_Gateways_Factory::getInstance()->build_gateway($order_gateway);
    }


    /**
     * enqueue upsell/downsell offer
     * scripts
     *
     * @since 1.0.0
     */
    public function load_offer_scripts() {
        if( Wpfnl_functions::is_wc_active() && self::is_upsell_downsell_step() ) {
            // Enqueue the offer CSS file
            wp_enqueue_style(
                'wpfnl-offer',
                WPFNL_DIR_URL . 'public/assets/css/wpfnl-offer.css',
                array(),
                WPFNL_VERSION
            );

            // Enqueue the offer JavaScript file
            wp_enqueue_script(
                'wpfnl-offer',
                WPFNL_DIR_URL . 'public/assets/js/wpfnl-offer.js',
                array( 'jquery' ),
                WPFNL_VERSION,
                true
            );

            global $post;
            $step_id                    = $post->ID;
            $funnel_id                  = get_post_meta( $step_id, '_funnel_id', true );
            $step_type                  = get_post_meta( $step_id, '_step_type', true );
            $order_id                   = ( isset( $_GET['wpfnl-order'] ) ) ? intval( $_GET['wpfnl-order'] ) : 0;
            $order_key                  = ( isset( $_GET['wpfnl-key'] ) ) ? sanitize_text_field( wp_unslash( $_GET['wpfnl-key'] ) ) : '';
            $order                      = wc_get_order( $order_id );
            $payment_method             = '';
            $is_reference_transaction   = false;
            $skip_offer                 = 'no';
            $offer_product              = self::get_offer_product_data( $step_id, '', '', $order_id );
            $maybe_unsupported_payment  = Wpfnl_functions::maybe_unsupported_payment_gateway();
            $quantity                   = 0;
            $product_id                 = '';


            if ( is_array($offer_product) && !empty($offer_product)) {
                $product_id = $offer_product['id'];
                $quantity   = $offer_product['qty'];
            }
            $is_variable = false;
            if( $product_id ){
                $product = wc_get_product( $product_id );
                if( $product &&  ($product->get_type() === 'variable' || 'variable-subscription' === $product->get_type()) ){
                    $is_variable = true;
                }
            }

            if($order) {
                $gateways = array( 'paypal','ppec_paypal' );
                $payment_method = $order->get_payment_method();
                // for now we ignore the paypal reference transactions. In future there will be a settings
                // for paypal reference transaction. User can define if they want to use paypal reference transaction
                // or not.
                if ( ( in_array( $payment_method, $gateways ) ) && !$is_reference_transaction ) {
                    $skip_offer = 'yes';
                }
            }
            $currency_symbol = get_woocommerce_currency_symbol();

            $localize_data = array(
                'ajaxUrl'                       => admin_url('admin-ajax.php'),
                'step_id'                       => $step_id,
                'funnel_id'                     => $funnel_id,
                'is_lms'                        => get_post_meta($funnel_id, '_wpfnl_funnel_type', true) === 'lms' ? 'yes' : 'no',
                'product_id'                    => $product_id,
                'is_variable'                   => $is_variable,
                'quantity'                      => $quantity,
                'order_id'                      => $order_id,
                'order_key'                     => $order_key,
                'offer_type'                    => get_post_meta($step_id, '_step_type', true),
                'currency_symbol'               => $currency_symbol,
                'skip_offer'                    => $skip_offer,
                'wpfnl_offer_nonce'             => wp_create_nonce( 'wpfnl_offer_nonce' ),
                'wpfnl_upsell_accepted_nonce'   => wp_create_nonce( 'wpfnl_upsell_accepted_nonce' ),
                'wpfnl_upsell_rejected_nonce'   => wp_create_nonce( 'wpfnl_upsell_rejected_nonce' ),
                'wpfnl_downsell_accepted_nonce' => wp_create_nonce( 'wpfnl_downsell_accepted_nonce' ),
                'wpfnl_downsell_rejected_nonce' => wp_create_nonce( 'wpfnl_downsell_rejected_nonce' ),
                'wpfnl_create_paypal_order_nonce'=> wp_create_nonce( 'wpfnl_create_paypal_order_nonce' ),
                'wpfnl_capture_paypal_order_nonce'=> wp_create_nonce( 'wpfnl_capture_paypal_order_nonce' ),
                'payment_gateway'               => $payment_method,
                'maybe_unsupported_payment'     => $maybe_unsupported_payment ? 'yes' : 'no',
            );

            if ( 'stripe' === $payment_method ) {
                $localize_data['wpfnl_stripe_sca_check_nonce'] = wp_create_nonce( 'wpfnl_stripe_sca_check_nonce' );
                wp_register_script( 'stripe', 'https://js.stripe.com/v3/', '', '3.0', true );
                wp_enqueue_script( 'stripe' );
            }

            if ( 'woocommerce_payments' === $payment_method ) {
				$localize_data['wpfunnels_woop_create_payment_intent'] = wp_create_nonce( 'wpfunnels_woop_create_payment_intent' );
				wp_register_script( 'stripe', 'https://js.stripe.com/v3/', '', '3.0', true );
				wp_enqueue_script( 'stripe' );
			}

            if ( 'mollie_wc_gateway_creditcard' === $payment_method ) {
                $localize_data['wpfnl_mollie_cc_process_nonce'] = wp_create_nonce( 'wpfnl_mollie_cc_process_nonce' );
            }

            if ( 'mollie_wc_gateway_creditcard' === $payment_method ) {
                $localize_data['wpfnl_mollie_ideal_process_nonce'] = wp_create_nonce( 'wpfnl_mollie_ideal_process_nonce' );
            }

            $localize_script  = '<!-- script to print WPFunnels Offer variables -->';
            $localize_script .= '<script type="text/javascript">';
            $localize_script .= 'var WPFunnelsOfferVars = ' . wp_json_encode( $localize_data ) . ';';
            $localize_script .= '</script>';
            echo $localize_script;
        }else{
            // Enqueue the offer CSS file for non-offer steps
            wp_enqueue_style(
                'wpfnl-offer',
                WPFNL_DIR_URL . 'public/assets/css/wpfnl-offer.css',
                array(),
                WPFNL_VERSION
            );

            // Enqueue the offer JavaScript file for non-offer steps
            wp_enqueue_script(
                'wpfnl-offer',
                WPFNL_DIR_URL . 'public/assets/js/wpfnl-offer.js',
                array( 'jquery' ),
                WPFNL_VERSION,
                true
            );

            global $post;
            $step_id                    = '';
            if( $post ){
                $step_id                    = $post->ID;
            }

            $funnel_id                  = get_post_meta( $step_id, '_funnel_id', true );

            if( $funnel_id ){
                $localize_data = array(
                    'ajaxUrl'                       => admin_url('admin-ajax.php'),
                    'step_id'                       => $step_id,
                    'funnel_id'                     => $funnel_id,
                    'is_lms'                        => get_post_meta($funnel_id, '_wpfnl_funnel_type', true) === 'lms' ? 'yes' : 'no',
                    'wpfnl_offer_nonce'             => wp_create_nonce( 'wpfnl_offer_nonce' ),
                    'wpfnl_upsell_accepted_nonce'   => wp_create_nonce( 'wpfnl_upsell_accepted_nonce' ),
                    'wpfnl_upsell_rejected_nonce'   => wp_create_nonce( 'wpfnl_upsell_rejected_nonce' ),
                    'wpfnl_downsell_accepted_nonce' => wp_create_nonce( 'wpfnl_downsell_accepted_nonce' ),
                    'wpfnl_downsell_rejected_nonce' => wp_create_nonce( 'wpfnl_downsell_rejected_nonce' ),
                    'wpfnl_create_paypal_order_nonce'=> wp_create_nonce( 'wpfnl_create_paypal_order_nonce' ),
                    'wpfnl_capture_paypal_order_nonce'=> wp_create_nonce( 'wpfnl_capture_paypal_order_nonce' ),
                );

                $localize_script  = '<!-- script to print WPFunnels Offer variables -->';
                $localize_script .= '<script type="text/javascript">';
                $localize_script .= 'var WPFunnelsOfferVars = ' . wp_json_encode( $localize_data ) . ';';
                $localize_script .= '</script>';
                echo $localize_script;
            }

        }
    }


    /**
     * Load offer loader screen
     *
     * @since 1.0.0
     */
    public function load_offer_loader() {
        if( Wpfnl_functions::is_wc_active() && self::is_upsell_downsell_step() ) {
            ?>
            <div class="wpfunnels-offer-loader">
                <div class="wpfunnels-offer-loader-wrapper">
                    <div class="wpfnl-loader"></div>
                    <h2 class="loader-ittle">
                        Loading
                        <span class="dot-wrapper">
                            <span class="dot-one">.</span> 
                            <span class="dot-two">.</span> 
                            <span class="dot-three">.</span>
                        </span>
                    </h2>
                    <p class="description"><?php echo __('Please wait while processing your order', 'wpfnl'); ?></p>
                </div>
            </div>
            <?php
        }
    }


    /**
     * Process upsell accept ajax action
     *
     * @since 1.0.0
     */
    public function process_upsell_accepted_action() {

        $nonce = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_STRING );

        if ( $nonce && ! wp_verify_nonce( $nonce, 'wpfnl_upsell_accepted_nonce' ) ) {
            return array(
                'status'    => 'success',
                'message'   => __( 'Unauthorized request', 'wpfnl-pro' )
            );
        }



        $step_id        = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $funnel_id      = isset( $_POST['funnel_id'] ) ? intval( $_POST['funnel_id'] ) : 0;
        $order_id       = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $order_key      = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash($_POST['order_key']) ) : '';
        $offer_type     = isset( $_POST['offer_type'] ) ? sanitize_text_field( $_POST['offer_type'] ) : '';
        $offer_action   = isset( $_POST['offer_action'] ) ? sanitize_text_field( $_POST['offer_action'] ) : '';
        $product_id     = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $quantity       = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 0;
        $variation_id       = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;
        $step_type      = get_post_meta($step_id, '_step_type', true);
        $attr = [];
        if( isset( $_POST['is_variable'], $_POST['attr'] ) && $_POST['is_variable'] == 'true' ){
            $payload = [
                'product_id' => $product_id,
                'data'       => $_POST['attr'],
            ];
            $attr = $_POST['attr'];

            $product_id = $this->wpfnl_get_variation_id($payload);
        }
        if( $variation_id ){
            $product_id = $variation_id;
        }

        $order = wc_get_order($order_id);

        $wpfnl_functions_instance = new Wpfnl_functions();
        if( method_exists($wpfnl_functions_instance,'is_valid_order_owner')){
            if (!Wpfnl_functions::is_valid_order_owner($order)) {
                wp_send_json([
                    'status'  => 'error',
                    'message' => __( 'Unauthorized request', 'wpfnl-pro' ),
                ]);
            }
        }

        if ( $order_id && $product_id ) {
            $data = array(
                'order_id'      => $order_id,
                'product_id'    => $product_id,
                'quantity'      => $quantity,
                'order_key'     => $order_key,
                'step_type'     => $step_type,
                'step_id'       => $step_id,
                'funnel_id'     => $funnel_id,
            );

            $result =  $this->offer_accepted( $step_id, $data , $attr );
            wp_send_json($result);
        } else {
            wp_send_json([
                'status' => 'error',
                'message' => 'No product data found',
            ]);
        }
    }



    /**
     * Process downsell accept ajax action
     *
     * @since 1.0.0
     */
    public function process_downsell_accepted_action() {

        $nonce = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_STRING );
        if ( $nonce && ! wp_verify_nonce( $nonce, 'wpfnl_downsell_accepted_nonce' ) ) {
            return array(
                'status'    => 'success',
                'message'   => __( 'Unauthorized request', 'wpfnl-pro' )
            );
        }

        $step_id        = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $funnel_id      = isset( $_POST['funnel_id'] ) ? intval( $_POST['funnel_id'] ) : 0;
        $order_id       = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $order_key      = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash($_POST['order_key']) ) : '';
        $offer_type     = isset( $_POST['offer_type'] ) ? sanitize_text_field( $_POST['offer_type'] ) : '';
        $offer_action   = isset( $_POST['offer_action'] ) ? sanitize_text_field( $_POST['offer_action'] ) : '';
        $product_id     = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $quantity       = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 0;
        $variation_id   = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;
        $step_type      = get_post_meta($step_id, '_step_type', true);
        $attr = [];
        if( isset( $_POST['is_variable'], $_POST['attr'] ) && $_POST['is_variable'] == 'true' ){
            $payload = [
                'product_id' => $product_id,
                'data'       => $_POST['attr'],
            ];
            $attr = $_POST['attr'];

            $product_id = $this->wpfnl_get_variation_id($payload);
        }

        if( $variation_id ){
            $product_id = $variation_id;
        }

        $order = wc_get_order($order_id);

        $wpfnl_functions_instance = new Wpfnl_functions();
        if( method_exists($wpfnl_functions_instance,'is_valid_order_owner')){
            if (!Wpfnl_functions::is_valid_order_owner($order)) {
                wp_send_json([
                    'status'  => 'error',
                    'message' => __( 'Unauthorized request', 'wpfnl-pro' ),
                ]);
            }
        }

        $all_products   = [];
        $product_data   = array();

        if ( $order_id && $product_id ) {

            $data = array(
                'order_id'      => $order_id,
                'product_id'    => $product_id,
                'quantity'      => $quantity,
                'order_key'     => $order_key,
                'step_type'     => $step_type,
                'step_id'       => $step_id,
                'funnel_id'     => $funnel_id,
            );
            $result =  $this->offer_accepted( $step_id, $data , $attr );

            wp_send_json($result);
        } else {
            wp_send_json([
                'status' => 'error',
                'message' => 'No product data found',
            ]);
        }
    }



    /**
     * Process upsell reject ajax action
     *
     * @since 1.0.0
     */
    public function process_upsell_rejected_action() {
        $nonce = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_STRING );
        if ( ! wp_verify_nonce( $nonce, 'wpfnl_upsell_rejected_nonce' ) ) {
            return array(
                'status'    => 'success',
                'message'   => __( 'Unauthorized request', 'wpfnl-pro' )
            );
        }

        $step_id        = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $funnel_id      = isset( $_POST['funnel_id'] ) ? intval( $_POST['funnel_id'] ) : 0;
        $order_id       = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $order_key      = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash($_POST['order_key']) ) : '';
        $product_id     = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $quantity       = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 0;
        $step_type      = get_post_meta($step_id, '_step_type', true);

        $result = array(
            'status'   => 'failed',
            'redirect' => '#',
        );

        if ( $step_id ) {

            $type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
            if( $type === 'lms' ){

                /** Change lms order meta value */
                Wpfnl_lms_learndash_functions::update_lms_order_status( $funnel_id, $step_id, get_current_user_id() );

                $result['message'] = 'Redirecting...';
                $result['status'] = 'success';
                $result['redirect_url'] = $this->offer_rejected( $step_id, [] );
            }else{
                $result = array(
                    'status'        => 'failed',
                    'redirect_url'  => '#',
                    'message'       => __( 'Order does not exist', 'wpfnl-pro' ),
                );

                if ( $order_id ) {
                    $data = array(
                        'order_id'      => $order_id,
                        'product_id'    => $product_id,
                        'quantity'      => $quantity,
                        'order_key'     => $order_key,
                        'step_type'     => $step_type,
                        'step_id'       => $step_id,
                        'funnel_id'     => $funnel_id,
                    );
                    $result['message'] = 'Redirecting...';
                    $result['status'] = 'success';
                    $result['redirect_url'] = $this->offer_rejected( $step_id, $data );
                }
            }

        }

        // send json.
        wp_send_json( $result );
    }


    /**
     * Process downsell reject ajax action
     *
     * @since 1.0.0
     */
    public function process_downsell_rejected_action() {
        $nonce = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_STRING );
        if ( ! wp_verify_nonce( $nonce, 'wpfnl_downsell_rejected_nonce' ) ) {
            return array(
                'status'    => 'success',
                'message'   => __( 'Unauthorized request', 'wpfnl-pro' )
            );
        }
        $step_id        = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $funnel_id      = isset( $_POST['funnel_id'] ) ? intval( $_POST['funnel_id'] ) : 0;
        $order_id       = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $order_key      = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash($_POST['order_key']) ) : '';
        $product_id     = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $quantity       = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 0;
        $step_type      = get_post_meta($step_id, '_step_type', true);

        $result = array(
            'status'   => 'failed',
            'redirect' => '#',
        );

        if ( $step_id ) {
            $type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
            if( $type === 'lms' ){

                /** Change lms order meta value */
                Wpfnl_lms_learndash_functions::update_lms_order_status( $funnel_id, $step_id, get_current_user_id() );

                $result['message'] = 'Redirecting...';
                $result['status'] = 'success';
                $result['redirect_url'] = $this->offer_rejected( $step_id, [] );
            }else{

                $result = array(
                    'status'        => 'failed',
                    'redirect_url'  => '#',
                    'message'       => __( 'Order does not exist', 'wpfnl-pro' ),
                );

                if ( $order_id ) {
                    $data = array(
                        'order_id'      => $order_id,
                        'product_id'    => $product_id,
                        'quantity'      => $quantity,
                        'order_key'     => $order_key,
                        'step_type'     => $step_type,
                        'step_id'       => $step_id,
                        'funnel_id'     => $funnel_id,
                    );
                    $result['message']      = 'Redirecting...';
                    $result['status']       = 'success';
                    $result['redirect_url'] = $this->offer_rejected( $step_id, $data );
                }
            }
        }

        // send json.
        wp_send_json( $result );
    }


    /**
     * Process upsell accept ajax action with auth
     *
     * @since 1.0.0
     */
    public function wpfnl_after_process_with_auth_ajax()
    {
        $step_id = $_POST['step_id'];
        $order_id = $_POST['order_id'];
        $order_key = $_POST['order_key'];
        $step_type = get_post_meta($step_id, '_step_type', true);
        $intent_id = '';

        if (isset($_POST['intent_id'])) {
            $intent_id = $_POST['intent_id'];
        }

        $extra_data = [];
        $extra_data['step_id'] = $step_id;
        $extra_data['step_type'] = $step_type;
        $extra_data['order_id'] = $order_id;
        $extra_data['order_key'] = $order_key;
        $extra_data['intent_id'] = $intent_id;

        $step_meta = get_post_meta($step_id, '_wpfnl_'.$step_type.'_products', true);

        if ($step_meta[0]) {
            $step_meta = $step_meta[0];
        } else {
            wp_send_json([
                'status' => 'error',
                'message' => 'No attach product found',
            ]);
        }

        $product_id = $step_meta['id'];
        $quantity = $step_meta['quantity'];
        $extra_data['product_id'] = $product_id;
        $extra_data['input_qty'] = $quantity;

        $response = $this->offer_accepted($step_id, $extra_data);
        wp_send_json($response);
    }


    /**
     * offer accepted action
     *
     * @param $step_id
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function offer_accepted( $step_id, $data , $attr = null) {
        $order_id               = $data['order_id'];
        $funnel_id              = $data['funnel_id'];
        $order_key              = $data['order_key'];
        $input_qty              = $data['quantity'];
        $order                  = wc_get_order($order_id);
        $skip_payment           = filter_input(INPUT_POST, 'stripe_sca_payment', FILTER_VALIDATE_BOOLEAN);
        $order_gateway          = $order->get_payment_method();
        if( 'stripe_cc' ===  $order_gateway ){
            $order_gateway = 'stripe';
        }
        $payment_gateway_obj    = Payment_Gateways_Factory::getInstance()->build_gateway($order_gateway);
        $is_charge_success      = false;

        if ($skip_payment) {
            $_stripe_intent_id = get_post_meta($order->get_id(), '_stripe_intent_id_' . $step_id, true);
            $intent_id = filter_input(INPUT_POST, 'stripe_intent_id', FILTER_SANITIZE_STRING);
            $skip_payment = ($intent_id === $_stripe_intent_id) ? true : false;
        }

        $offer_product = self::update_variation_product_details( $step_id, $data['product_id'], $input_qty, $order_id  );

        $product       = wc_get_product($data['product_id']);

        if( $product && !$product->is_in_stock() || $product->managing_stock() && ( $product->get_stock_quantity() < intval($offer_product['qty']) || $product->get_stock_quantity() == 0 || intval($offer_product['qty']) == 0 ) ) {
            if( 'variation' === $product->get_type() ) {
                $message = __( 'The selected variation is out of stock. Please, try choosing different variation.', 'wpfnl-pro' );
            }
            else {
                $message = __( 'Product is out of stock', 'wpfnl-pro' );
            }
            return array(
                'status'   => 'failed',
                'message'  => $message,
            );
        }
        $no_payment_gateway = false;
        $shipping = [];

        if($offer_product['qty'] != 0){
            if ( (isset($offer_product['price']) && '' === trim($offer_product['price'])) || $skip_payment ) {
                $is_charge_success = array(
                    'is_success'    => true,
                    'message'       => '',
                );

                if ( $skip_payment ) {

                    $response       = \WC_Stripe_API::retrieve('charges?payment_intent=' . $intent_id);
                    $charge_data    = reset($response->data);
                    if ($charge_data->status === 'succeeded') {                        
                        $order->update_meta_data('_wpfunnels_offer_txn_resp_' . $step_id, $charge_data->id);
                        $order->save();
                        $is_charge_success = [
                            'is_success' => true,
                            'message' => 'Payment succeeded.',
                        ];
                    } else {
                        $is_charge_success = [
                            'is_success' => false,
                            'message' => 'Payment failed: ' . ($charge_data->failure_message ?? 'Unknown error'),
                        ];
                    }
                    
                }
            } else {
                if ($payment_gateway_obj) {
                    $tax_enabled            = get_option( 'woocommerce_calc_taxes' );
                    if ( 'yes' === $tax_enabled ) {
                        if ( !wc_prices_include_tax() ) {
                            $product = wc_get_product($offer_product['id']);
                            $offer_product['total'] = $product ? wc_get_price_including_tax( $product, array( 'price' => $offer_product['total'] ) ) : '';
                        }
                    }

                    $offer_product_id         = isset( $offer_product[ 'id' ] ) ? $offer_product[ 'id' ] : 0;
                    $offer_total_price        = isset( $offer_product[ 'total' ] ) ? $offer_product[ 'total' ] : 0;
                    $shipping                 = $this->calculate_offer_product_shipping_price( $order, $offer_product_id, $offer_total_price );
                    $shipping_price           = isset( $shipping[ 'total' ] ) ? $shipping[ 'total' ] : 0;
                    $shipping_tax             = isset( $shipping[ 'total_tax' ] ) ? $shipping[ 'total_tax' ] : 0;
                    $shipping_price           = $shipping_price + $shipping_tax;

                    $offer_product[ 'price' ] = $offer_total_price + $shipping_price;
                    $offer_product[ 'total' ] = $offer_total_price + $shipping_price;

                    $offer_product[ 'price' ] = $offer_total_price;
                    $offer_product[ 'total' ] = $offer_total_price;

                    // Handle chained products if pro plugin is active
                    $total_chained_product_price = 0;
                    if( class_exists( '\WPFunnelsPro\Compatibility\ChainedProduct' ) && Wpfnl_functions::is_wc_active() ){
                        $chained_product_class_instance = new \WPFunnelsPro\Compatibility\ChainedProduct();
                        $chained_products = $chained_product_class_instance->get_chain_product_details( $offer_product['id'] );

                        if ( !empty($chained_products) ){
                            foreach ($chained_products as $chained_product_id => $chianed_product){
                                $new_product_price = 0;
                                $product = wc_get_product( $chained_product_id );
                                $chained_product = wc_get_product( $chained_product_id );
                                if( 'yes' === $chianed_product['priced_individually'] ){
                                    $chained_product_price = $chained_product->get_price();
                                    if ( ! empty( $chained_product_price ) ) {
                                        $total_chained_product_price += $chained_product_price * $chianed_product['unit'];
                                    }
                                }
                            }
                        }
                    }
                    $offer_product[ 'price' ] = $offer_product[ 'price' ] + $total_chained_product_price;
                    $offer_product[ 'total' ] = $offer_product[ 'total' ] + $total_chained_product_price;

                    $is_charge_success        = $payment_gateway_obj->process_payment( $order, $offer_product );


                }else{
                    $no_payment_gateway = true;
                }
            }
        }

        if( isset($is_charge_success['is_success'] ) && $is_charge_success['is_success'] ) {
            $response           = self::after_offer_charged( $funnel_id, $step_id, $order_id, $order_key, $offer_product, true , $attr, '', '', $shipping );
            if( 'success' === $response['status'] ) {
                delete_option( 'wpfunnels_dynamic_offer_data' );
                $result = array(
                    'status'   => 'success',
                    'message'  => __( 'Offer product is added successfully', 'wpfnl-pro' ),
                );
            } else {
                $result = array(
                    'status'   => 'success',
                    'message'  => __( 'Offer product is not added', 'wpfnl-pro' ),
                );
            }
        } else{
            if( $no_payment_gateway ){
                $result = array(
                    'status'   => 'failed',
                    'message'  => __( 'No payment gateway found', 'wpfnl-pro' ),
                );
            }else{
                $result = array(
                    'status'   => 'failed',
                    'message'  => $is_charge_success['message'],
                );
            }

        }
        $this->offerStatus      = 'accept';

        $result['redirect_url'] = $this->get_next_step_url( $order, $step_id, $funnel_id );
        $next_step  = Wpfnl_functions::get_next_step( $funnel_id, $step_id );
        $next_step  = apply_filters( 'wpfunnels/next_step_data', $next_step );
        $custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step[ 'step_id' ] );

        if( $custom_url ){
            $result['redirect_url'] = $custom_url;
        }
        if( (isset($result['redirect_url']) && !$result['redirect_url']) ){
            $result['redirect_url'] = Wpfnl_functions::redirect_to_deafult_thankyou();
        }
        return $result;
    }


    /**
     * @param $step_id
     * @param $data
     * @return string
     */
    public function offer_rejected( $step_id, $data ) {

        if( $data ){
            $order              = wc_get_order($data['order_id']);
            $product_id         = $data['product_id'];
            $input_qty          = $data['quantity'];
            $this->offerStatus  = 'reject';
            $offer_product      = self::get_offer_product_data( $step_id, $product_id, $input_qty, $data['order_id'] );
            delete_option( 'wpfunnels_dynamic_offer_data' );

            ob_start();
            do_action( 'wpfunnels/offer_rejected', $order, $offer_product );
            ob_get_clean();

            $funnel_id  = Wpfnl_functions::get_funnel_id_from_step( $step_id );
            $next_step  = Wpfnl_functions::get_next_step( $funnel_id, $step_id );
            $next_step  = apply_filters( 'wpfunnels/next_step_data', $next_step );
            $custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step[ 'step_id' ] );

            if( $order ){
                $order->update_meta_data('_wpfunnels_order', 'yes');
                $order->update_meta_data('_wpfunnels_funnel_id', $funnel_id);
            }

            if( $custom_url ){
                return $custom_url;
            }
            if( $this->get_next_step_url( $order, $step_id, $funnel_id ) ){
                return $this->get_next_step_url( $order, $step_id, $funnel_id );
            }

            return Wpfnl_functions::redirect_to_deafult_thankyou();

        }else{
            $funnel_id     = Wpfnl_functions::get_funnel_id_from_step( $step_id );
            $step_type     = get_post_meta( $step_id, '_step_type', true );
            $offer_product = get_post_meta( $step_id, '_wpfnl_' . $step_type . '_products', true );

            if( isset($offer_product[0]) ){
                $offer_product[0]['step_id'] = $step_id;

                ob_start();
                do_action( 'wpfunnels/offer_rejected', '', $offer_product[0] );
                ob_get_clean();

            }

            $next_step  = Wpfnl_functions::get_next_step( $funnel_id, $step_id );
            $next_step  = apply_filters( 'wpfunnels/next_step_data', $next_step );
            $custom_url = Wpfnl_functions::custom_url_for_thankyou_page( $next_step[ 'step_id' ] );

            if( $custom_url ){
                return $custom_url;
            }
            return $this->get_next_step_url_for_lms( (object)[], $step_id, $funnel_id );
        }

    }


    /**
     * get next step url
     *
     * @param \WC_Order $order
     * @param $step_id
     * @param $funnel_id
     * @return string
     */
    public function get_next_step_url_for_lms( $order , $step_id, $funnel_id ) {
        $current_page_id = $step_id;
        $next_step       = Wpfnl_functions::get_next_step( $funnel_id, $current_page_id );
        $next_step       = apply_filters( 'wpfunnels/next_step_data', $next_step );

        if ( 'conditional' === $next_step['step_type'] ) {
            $condition				= \WPFunnels\Conditions\Wpfnl_Condition_Checker::getInstance();
            $condition_identifier = strval($next_step['step_id']);
            $condition_matched  = $condition->check_condition($funnel_id, $order, $condition_identifier, $current_page_id, $this->offerStatus);
            $node_found         = Wpfnl_functions::get_node_id($funnel_id, $next_step['step_id']);
            if ($condition_matched) {
                $next_node = $this->go_to_output_1($funnel_id, $node_found);
                $next_step = Wpfnl_functions::get_step_by_node($funnel_id, $next_node);
            } else {
                $next_node = $this->go_to_output_2($funnel_id, $node_found);
                $next_step = Wpfnl_functions::get_step_by_node($funnel_id, $next_node);
            }
        } else {
            $next_step = $next_step['step_id'];
        }
        $redirect_url = get_post_permalink($next_step);


        $query_args = array(
            'wpfnl-order' => 'lms_order',
            'wpfnl-key' => 'lms_order',
        );


        return add_query_arg($query_args, $redirect_url);
    }


    /**
     * get next step url
     *
     * @param \WC_Order $order
     * @param $step_id
     * @param $funnel_id
     * @return string
     */
    public function get_next_step_url( \WC_Order $order , $step_id, $funnel_id ) {
        $next_step       = Wpfnl_functions::get_next_conditional_step( $funnel_id, $step_id, $order, $this->offerStatus );
        $next_step       = apply_filters( 'wpfunnels/next_step_data', $next_step );
        $next_step_id = isset($next_step['step_id']) ? $next_step['step_id'] : '';

        if( $order ){
            $next_step              = apply_filters( 'wpfunnels/modify_next_step_based_on_order', $next_step, $order );
            $next_step_id = isset($next_step['step_id']) ? $next_step['step_id'] : '';
        }

        if( 'thankyou' === $next_step['step_type'] && Wpfnl_functions::is_global_thank_you_page_enabled() ){
            return Wpfnl_functions::redirect_to_deafult_thankyou();
        }

        $redirect_url = $next_step_id  ? get_post_permalink($next_step_id) : '';
        if( $order ){
            $query_args = array(
                'wpfnl-order' => $order->get_id(),
                'wpfnl-key' => $order->get_order_key(),
                'key' => $order->get_order_key(),
            );
        }else{
            $query_args = array(
                'wpfnl-order' => 'lms_order',
                'wpfnl-key' => 'lms_order',
            );
        }
        if( $redirect_url ){
            return add_query_arg($query_args, $redirect_url);
        }
        return false;

    }



    public function detect_upsell() {
        $type = '';
        if (get_post_type(get_the_ID()) == 'wpfunnel_steps') {
            $type = get_post_meta(get_the_ID(), '_step_type', true);
            if ($type == 'upsell' || $type == 'downsell') {
                if (isset($_GET['wpfnl-order']) && isset($_GET['wpfnl-key'])) {
                    return;
                } else {
                    $user = wp_get_current_user();
                    if (!in_array('administrator', (array)$user->roles)) {
                        echo '<p style="color:red;text-align:center;">You are not allowed to access this page directly</p>';
                        wp_die();
                    }
                }
            }
        }
    }


    public function wpfnl_modify_product_price($product_price) {
        if (class_exists('WOOMC\MultiCurrency\API')) {
            $api_obj = new WOOMC\MultiCurrency\API();
            $product_price = $api_obj->convert($product_price, get_woocommerce_currency(), get_option('woocommerce_currency'));
        }

        return $product_price;
    }

    public function funnel_upsell_shortcode_button() {
        global $post;
        return '
            <div class="wpfnl-upsell-btn-wrapper">
                <button type="button" id="upsell_funnel_target" data-id="' . $post->ID . '" name="button">
                    BUY OFFER
                    <span class="wpfnl-loader" id="wpfnl-loader-accept"></span>
                </button>
                <span class="wpfnl-alert" id="wpfnl-alert-accept"></span>
            </div>
        ';
        //wpfnl-success, wpfnl-error, box
    }

    public function funnel_upsell_reject_shortcode_button() {
        global $post;
        return '
            <div class="wpfnl-upsell-btn-wrapper">
                <button type="button" id="upsell_funnel_reject" data-id="' . $post->ID . '" name="button">
                    Not Interested
                    <span class="wpfnl-loader" id="wpfnl-loader-reject"></span>
                </button>
                <span class="wpfnl-alert" id="wpfnl-alert-reject"></span>
            </div>
        ';
        //wpfnl-success, wpfnl-error, box
    }

    public function go_to_output_1($funnel_id, $node_found) {
        $funnel_json = get_post_meta($funnel_id, '_funnel_data', true);
        if ($funnel_json) {
            // $funnel_data = json_decode($funnel_json, true);
            $node_data = $funnel_json['drawflow']['Home']['data'];

            foreach ($node_data as $node_key => $node_value) {
                if ($node_value['id'] == $node_found) {
                    $next_node = isset($node_value['outputs']['output_1']['connections'][0]['node']) ? $node_value['outputs']['output_1']['connections'][0]['node'] : '';
                    return $next_node;
                }
            }
            return false;
        }
    }

    public function go_to_output_2($funnel_id, $node_found) {
        $funnel_json = get_post_meta($funnel_id, '_funnel_data', true);
        if ($funnel_json) {
            // $funnel_data = json_decode($funnel_json, true);
            $node_data = $funnel_json['drawflow']['Home']['data'];

            foreach ($node_data as $node_key => $node_value) {
                if ($node_value['id'] == $node_found) {
                    $next_node = isset($node_value['outputs']['output_2']['connections'][0]['node']) ? $node_value['outputs']['output_2']['connections'][0]['node'] : '';
                    return $next_node;
                }
            }
            return false;
        }
    }

    public function get_conditional_redirect($step_id, $response, $type = 'accept') {
        $funnel_id = '';
        $condition_identifier = '';
        $group_conditions = get_post_meta($funnel_id, $condition_identifier, true);
        if ($group_conditions) {

            // Loop through group condition.
            foreach ($group_conditions as $group) {
                if (empty($group)) {
                    continue;
                }

                $match_group = true;
                // Loop over rules and determine if all rules match.
                foreach ($group as $rule) {
                    if (!$this->match_rule($rule, $order, $current_page_id)) {
                        $match_group = false;
                        break;
                    }
                }

                // If this group matches, show the field group.
                if ($match_group) {
                    return true;
                }
            }
        }

        // Return default.
        return false;

    }

    public function get_discount_data($step_id) {
        $data = [];
        $type = get_post_meta($step_id, '_step_type', true);
        if ($type == 'upsell') {
            $data['type'] = get_post_meta($step_id, '_wpfnl_upsell_discount_type', true);
            $data['value'] = get_post_meta($step_id, '_wpfnl_upsell_discount_value', true);
        } else {
            $data['type'] = get_post_meta($step_id, '_wpfnl_downsell_discount_type', true);
            $data['value'] = get_post_meta($step_id, '_wpfnl_downsell_discount_value', true);
        }
        return $data;
    }


    /**
     *
     */
    private function wpfnl_get_variation_id( $payload ){
        if( isset($payload['product_id'], $payload['data']) && is_array($payload['data']) ){
            $variation_id = (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
                    new \WC_Product($payload['product_id']),
                    $payload['data']
            );
            return $variation_id;
        }
        return false;
    }


    /**
     * @desc calculate shipping cost for offer steps (upsell/downsell)
     *
     * @param \WC_Order $order
     * @param $product_id
     * @param $total_price
     * @return int|mixed|void
     */
    private function calculate_offer_product_shipping_price( \WC_Order $order, $product_id, $total_price ) {
        if( !$product_id ) {
            return;
        }
        if( !WC()->shipping()->get_shipping_classes() ) {
            return;
        }

        $product = wc_get_product( $product_id );
        if( $product && $product->is_virtual() ) {
            return;
        }

        $shipping            = [];
        $shipping_methods    = $order->get_shipping_methods();
        $offer_settings      = Wpfnl_functions::get_offer_settings();
        $offer_orders_option = isset( $offer_settings[ 'offer_orders' ] ) ? $offer_settings[ 'offer_orders' ] : '';

        if( !empty( $shipping_methods ) ) {
            foreach( $shipping_methods as $key => $method ) {
                if( $method ){
                    $shipping = [
                        'name'           => $method->get_name(),
                        'method_title'   => $method->get_method_title(),
                        'method_id'      => $method->get_method_id(),
                        'instance_id'    => $method->get_instance_id(),
                        'total'          => $method->get_total(),
                        'total_tax'      => $method->get_total_tax(),
                        'taxes'          => $method->get_taxes(),
                        'tax_percentage' => ( $method->get_total() != 0 ) || ( $method->get_total() != 0.00 ) ? ( $method->get_total_tax() * 100 ) / $method->get_total() : 0,
                    ];
                }

            }
        }

        if( 'main-order' === $offer_orders_option ) {
            $wc_shipping_zones   = \WC_Shipping_Zones::get_zones();

            if( !empty( $wc_shipping_zones ) && isset( $shipping[ 'method_id' ] ) && 'flat_rate' === $shipping[ 'method_id' ] ) {
                foreach( $wc_shipping_zones as $zone ) {
                    if( isset( $zone[ 'shipping_methods' ] ) && !empty( $zone[ 'shipping_methods' ] ) ) {
                        foreach( $zone[ 'shipping_methods' ] as $method ) {
                            if( 'WC_Shipping_Flat_Rate' === get_class( $method ) ) {
                                if( isset( $method->instance_id ) && isset( $shipping[ 'instance_id' ] ) ) {
                                    if( $method->instance_id == $shipping[ 'instance_id' ] ) {
                                        $product_shipping_class_id = $product->get_shipping_class_id();
                                        $instance_settings         = isset( $method->instance_settings ) ? $method->instance_settings : [];
                                        $shipping_cost             = isset( $instance_settings[ 'class_cost_' . $product_shipping_class_id ] ) ? $instance_settings[ 'class_cost_' . $product_shipping_class_id ] : $instance_settings[ 'no_class_cost' ];
                                        $shipping_cost             = $shipping_cost ? $shipping_cost : 0;
                                        $tax_percentage          = isset( $shipping[ 'tax_percentage' ] ) ? $shipping[ 'tax_percentage' ] : 0;
                                        $total_tax               = ( $shipping_cost * $tax_percentage ) / 100;
                                        $shipping[ 'total' ]     = $shipping_cost;
                                        $shipping[ 'total_tax' ] = $total_tax;
                                        $shipping[ 'taxes' ]     = [
                                            'total' => [ $total_tax ]
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        unset( $shipping[ 'tax_percentage' ] );
        return $shipping;
    }

    /**
     * Check if current step is upsell or downsell
     *
     * @param bool $step_id
     * @return bool
     */
    public static function is_upsell_downsell_step( $step_id = false ) {
        $is_upsell_downsell = false;
        if ( Wpfnl_functions::is_funnel_step_page() ) {
            if ( ! $step_id ) {
                global $post;
                $step_id = $post->ID;
            }
            $step_type = get_post_meta( $step_id, '_step_type', true );
            if ( $step_type === 'upsell' || $step_type === 'downsell' ) {
                $is_upsell_downsell = true;
            }
        }
        return $is_upsell_downsell;
    }

    /**
     * Get offer product data
     *
     * @param $step_id
     * @param string $selected_product_id
     * @param string $input_qty
     * @param int $order_id
     * @return array
     */
    public static function get_offer_product_data( $step_id, $selected_product_id = '', $input_qty = '', $order_id = 0 ) {
        // If pro plugin is available, use its method for full compatibility
        if ( class_exists( '\WPFunnelsPro\Wpfnl_Pro_functions' ) && method_exists( '\WPFunnelsPro\Wpfnl_Pro_functions', 'get_offer_product_data' ) ) {
            return \WPFunnelsPro\Wpfnl_Pro_functions::get_offer_product_data( $step_id, $selected_product_id, $input_qty, $order_id );
        }
        
        $funnel_id = get_post_meta( $step_id, '_funnel_id', true );
        return apply_filters( 'wpfunnels/offer_product_data', self::get_offer_data( $step_id, $selected_product_id, $input_qty, $order_id ), $funnel_id, $step_id );
    }

    /**
     * Get offer data
     *
     * @param $step_id
     * @param string $selected_product_id
     * @param string $input_qty
     * @param int $order_id
     * @return array
     */
    private static function get_offer_data( $step_id, $selected_product_id = '', $input_qty = '', $order_id = 0 ) {
        $data = array();
        $product_id = 0;
        $product_qty = 0;

        $step_type = get_post_meta( $step_id, '_step_type', true );
        $offer_product = get_post_meta( $step_id, '_wpfnl_' . $step_type . '_products', true );

        if ( isset( $offer_product[0] ) && ! $selected_product_id ) {
            $product_id = $offer_product[0]['id'];
            $product_qty = $offer_product[0]['quantity'];
        } else {
            $product_id = $selected_product_id;
            $product_qty = ! empty( $input_qty ) ? intval( $input_qty ) : ( isset( $offer_product[0]['quantity'] ) ? $offer_product[0]['quantity'] : 1 );
        }

        if ( $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $data = self::prepare_offer_data( $step_id, $product_id, $product_qty, $step_type, array(), false, 0, $order_id );
            }
        }
        return $data;
    }

    /**
     * Prepare offer data
     *
     * @param $step_id
     * @param $product_id
     * @param $product_qty
     * @param $step_type
     * @param $discount
     * @param $cancel_main_order
     * @param $amount_diff
     * @param $order_id
     * @return array
     */
    private static function prepare_offer_data( $step_id, $product_id, $product_qty, $step_type, $discount, $cancel_main_order, $amount_diff, $order_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return array();
        }

        $original_price = $product->get_price( 'edit' );
        $custom_price = $original_price;
        $unit_price = $custom_price;
        $unit_price_tax = $custom_price;
        $product_price = floatval( $custom_price ) * intval( $product_qty );

        $data = array(
            'step_id' => $step_id,
            'id' => $product_id,
            'name' => $product->get_title(),
            'desc' => $product->get_short_description(),
            'qty' => $product_qty,
            'original_price' => $original_price,
            'unit_price' => $unit_price,
            'unit_price_tax' => $unit_price_tax,
            'args' => array(
                'subtotal' => $custom_price,
                'total' => $custom_price,
            ),
            'price' => $product_price,
            'url' => $product->get_permalink(),
            'total' => $product_price,
            'cancel_main_order' => $cancel_main_order,
        );

        return $data;
    }

    /**
     * Update variation product details
     *
     * @param $step_id
     * @param $selected_product_id
     * @param $input_qty
     * @param $order_id
     * @return array
     */
    public static function update_variation_product_details( $step_id, $selected_product_id, $input_qty, $order_id ) {
        // If pro plugin is available, use its method for full compatibility
        if ( class_exists( '\WPFunnelsPro\Wpfnl_Pro_functions' ) && method_exists( '\WPFunnelsPro\Wpfnl_Pro_functions', 'update_variation_product_details' ) ) {
            return \WPFunnelsPro\Wpfnl_Pro_functions::update_variation_product_details( $step_id, $selected_product_id, $input_qty, $order_id );
        }

        $step_type = get_post_meta( $step_id, '_step_type', true );
        $funnel_id = get_post_meta( $step_id, '_funnel_id', true );
        $discount = get_post_meta( $step_id, '_wpfnl_' . $step_type . '_discount', true );
        
        $data = '';
        if ( $selected_product_id ) {
            $data = self::prepare_offer_data( $step_id, $selected_product_id, $input_qty, $step_type, $discount, false, 0, $order_id );
        }
        
        return apply_filters( 'wpfunnels/offer_product_data', $data, $funnel_id, $step_id );
    }

    /**
     * After offer charged
     *
     * @param $funnel_id
     * @param $step_id
     * @param $order_id
     * @param $order_key
     * @param $offer_product
     * @param bool $is_charge_success
     * @param null $attr
     * @param string $variation_id
     * @param string $input_qty
     * @param array $shipping_data
     * @return array
     */
    public static function after_offer_charged( $funnel_id, $step_id, $order_id, $order_key, $offer_product, $is_charge_success = false, $attr = null, $variation_id = '', $input_qty = '', $shipping_data = array() ) {
        // If pro plugin is available, use its method for full compatibility
        if ( class_exists( '\WPFunnelsPro\Wpfnl_Pro_functions' ) && method_exists( '\WPFunnelsPro\Wpfnl_Pro_functions', 'after_offer_charged' ) ) {
            return \WPFunnelsPro\Wpfnl_Pro_functions::after_offer_charged( $funnel_id, $step_id, $order_id, $order_key, $offer_product, $is_charge_success, $attr, $variation_id, $input_qty, $shipping_data );
        }

        $result = array();
        
        if ( $is_charge_success ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                return array(
                    'status' => 'error',
                    'message' => __( 'Order not found', 'wpfnl' ),
                );
            }

            // Simple implementation - add product to main order
            if ( $attr ) {
                $offer_product['args']['variation'] = $attr;
            }

            $product = wc_get_product( $offer_product['id'] );
            if ( $product ) {
                $product->set_price( ( $offer_product['args']['total'] / $offer_product['qty'] ) );
                $item_id = $order->add_product( $product, $offer_product['qty'] );
                
                if ( $item_id ) {
                    $step_type = get_post_meta( $step_id, '_step_type', true );
                    wc_add_order_item_meta( $item_id, "_wpfunnels_{$step_type}", 'yes' );
                    wc_add_order_item_meta( $item_id, '_wpfunnels_step_id', $offer_product['step_id'] );
                    $order->update_meta_data( '_wpfunnels_offer_' . $step_id, $step_id );
                    $order->calculate_totals();
                    $order->save();
                    
                    $result = array(
                        'status' => 'success',
                        'message' => __( 'Product Added Successfully.', 'wpfnl' ),
                    );

                    ob_start();
                    do_action( 'wpfunnels/offer_accepted', $order, $offer_product );
                    ob_get_clean();
                } else {
                    $result = array(
                        'status' => 'error',
                        'message' => __( 'Failed to add product to order', 'wpfnl' ),
                    );
                }
            }
        }

        return $result;
    }
}
