<?php
/**
 * Product module
 * 
 * @package
 */
namespace WPFunnels\Modules\Admin\Product;

use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use \WC_Subscriptions_Product;
use Wpfnl_Type_Factory;
class Module extends Wpfnl_Admin_Module
{
    use SingletonTrait;

    protected $products = [];

    public function get_view()
    {
        require_once WPFNL_DIR . '/admin/modules/product/views/view.php';
    }


    public function set_products($products)
    {
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            if ($products) {
                foreach ($products as $saved_product) {
                    $product = wc_get_product($saved_product['id']);
                    if (is_object($product)) {
                        $product_name = $product->get_name();
                        $this->products[] = [
                            'id'            =>  $saved_product['id'],
                            'name'          =>  $product_name,
                            'quantity'      =>  $saved_product['quantity'],
                        ];
                    }
                }
            }
        }
    }

    public function get_products()
    {
        return $this->products;
    }

    public function init_ajax()
    {
        add_action('wp_ajax_wpfnl_product_search', [ $this, 'fetch_products' ]);
        add_action('wp_ajax_wpfnl_search_coupon', [ $this, 'fetch_coupons' ]);
        add_action('wp_ajax_wpfnl_product_search_gbf', [ $this, 'fetch_products_gbf' ]);
        add_action('wp_ajax_wpfnl_product_search_for_gbf_type', [ $this, 'fetch_specific_products_gbf' ]);
        add_action('wp_ajax_wpfnl_product_search_by_category_and_name', [ $this, 'wpfnl_product_search_by_category_and_name' ]);

        wp_ajax_helper()->handle('global-funnel-add-upsell-product')
            ->with_callback([ $this, 'global_funnel_add_upsell_product' ])
            ->with_validation($this->get_validation_data());
    }

    /**
     * Fetch product from WC data store
     *
     * @throws \Exception
     * @since  1.0.0
     */
    public function fetch_products()
    {
        check_ajax_referer('wpfnl-admin', 'security');
        if (isset($_GET['term'])) {
            $term = (string) esc_attr( wp_unslash($_GET['term']) );
        }
        if (empty($term)) {
            wp_die();
        }
        $products        = [];
        if( $_GET['isLms'] == 'true' ){
            $_class = 'lms';
        }else{
            $_class = 'wc';
        }
        $class_object = Wpfnl_Type_Factory::build($_class);
        if( $class_object ){
            $products = $class_object->retrieve_item( $term );
        }
        wp_send_json($products);
    }


    /**
     * Fetch Coupons from WooCommerce or LMS data store via AJAX.
     *
     * This function handles the AJAX request to fetch coupons from either the WooCommerce (wc)
     * or Learning Management System (LMS) data store based on the provided term. It performs security checks,
     * processes the term, retrieves relevant coupons, and sends back the JSON response with the coupon data.
     *
     * @since 2.8.6
     */
    public function fetch_coupons()
    {
        // Verify the security nonce.
        check_ajax_referer('wpfnl-admin', 'security');

        // Check if the 'term' parameter is set in the request.
        if (!isset($_GET['term'])) {
            wp_die();
        }

        // Sanitize and process the term parameter.
        $term = (string) esc_attr(wp_unslash($_GET['term']));

        // Check if the term is empty.
        if (empty($term)) {
            wp_die();
        }

        $coupons = [];

        // Determine the class based on the 'isLms' parameter.
        if (sanitize_text_field($_GET['isLms']) == 'true') {
            $_class = 'lms';
        } else {
            $_class = 'wc';
        }

        // Build the appropriate class object based on the determined class.
        $class_object = Wpfnl_Type_Factory::build($_class);

        // If the class object is not available, terminate.
        if (!$class_object) {
            wp_die();
        }

        // Retrieve coupons using the appropriate class object.
        $coupons = $class_object->retrieve_coupon($term);

        // Send JSON response with the fetched coupons.
        wp_send_json($coupons);
    }


    /**
     * Fetch product from WC data store
     *
     * @throws \Exception
     * @since  1.0.0
     */
    public function fetch_products_gbf()
    {
        check_ajax_referer('wpfnl-admin', 'security');
        if (isset($_GET['term'])) {
            $term = (string) esc_attr( wp_unslash($_GET['term']) );
        }
        if (empty($term)) {
            wp_die();
        }

        $products        = [];
        $data_store = \WC_Data_Store::load('product');
        $ids        = $data_store->search_products($term, '', false, false, 10);

        $product_objects = array_filter(array_map('wc_get_product', $ids), 'wc_products_array_filter_readable');
        if( is_array($product_objects) ){
            foreach ($product_objects as $product_object) {
                
                if( $product_object && (( $product_object->managing_stock() && $product_object->get_stock_quantity() > 0 ) || ( !$product_object->managing_stock() && $product_object->get_stock_status() !== 'outofstock' )) ){
                    $formatted_name = $product_object->get_name();
                    if($product_object->get_type() == 'variable' || $product_object->get_type() == 'variable-subscription') {
                        if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                            $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product_object );
                        }
                        $variations = $product_object->get_available_variations();
                        $isPro 		= Wpfnl_functions::is_wpfnl_pro_activated();
                        if($isPro){
                            $parent_id = $product_object->get_id();
                            $products[] = [
                                'name'  => $formatted_name,
                                'id'    => $parent_id,
                                'regular_price' => $product_object->get_regular_price(),
                                'sale_price' => $product_object->get_sale_price(),
                            ];
                        }
                        if( is_array($variations ) ){
                            foreach ($variations as $variation) {
                                $variation_product = wc_get_product($variation['variation_id']);
                                if( $variation_product ){
                                    if( ( $variation_product->managing_stock() && $variation_product->get_stock_quantity() > 0 ) || ( !$variation_product->managing_stock() && $variation_product->get_stock_status() !== 'outofstock' ) ){
                                        $products[] = [
            
                                            'name'  =>   Wpfnl_functions::get_formated_product_name( $variation_product ),
                                            'id'    => $variation['variation_id'],
                                            'regular_price' =>  $variation['display_price'],
                                            'sale_price' => $variation['display_regular_price'],
                                        ];
                                    }
                                }
                            }
                        }
    
                    }else {
                    $products[] = [
                        'name' => rawurldecode($formatted_name),
                        'id'    => $product_object->get_id(),
                        'regular_price' => $product_object->get_regular_price(),
                        'sale_price' => $product_object->get_sale_price(),
                    ];
                    }
                }
            }
        }
    
        wp_send_json($products);
    }
    
    /**
     * Fetch specific product from WC data store for gbf type
     * 
     * @return [type]
     */
    public function fetch_specific_products_gbf()
    {
        check_ajax_referer('wpfnl-admin', 'security');
        if (isset($_GET['term'])) {
            $term = (string) esc_attr( wp_unslash($_GET['term']) );
        }
        if (empty($term)) {
            wp_die();
        }

        $products        = [];
        $data_store = \WC_Data_Store::load('product');
        $ids        = $data_store->search_products($term, '', false, false, 10);

        $product_objects = array_filter(array_map('wc_get_product', $ids), 'wc_products_array_filter_readable');
        if( $product_objects ){
            foreach ($product_objects as $product_object) {
                if( ( $product_object->managing_stock() && $product_object->get_stock_quantity() > 0 ) || ( !$product_object->managing_stock() && $product_object->get_stock_status() !== 'outofstock' ) ){
                    $formatted_name = $product_object->get_name();
                    if($product_object->get_type() == 'variable' || $product_object->get_type() == 'variable-subscription') {
                        if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                            $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product_object );
                        }
                        $variations = $product_object->get_available_variations();
                        $parent_id = $product_object->get_id();
                        $products[] = [
                            'name'  => $formatted_name,
                            'id'    => $parent_id,
                            'regular_price' => $product_object->get_regular_price(),
                            'sale_price' => $product_object->get_sale_price(),
                        ];
                        if( !empty($variations) ){
                            foreach ($variations as $variation) {
                                $variation_product = wc_get_product($variation['variation_id']);
                                if( $variation_product ){
                                    if( ( $variation_product->managing_stock() && $variation_product->get_stock_quantity() > 0 ) || ( !$variation_product->managing_stock() && $variation_product->get_stock_status() !== 'outofstock' ) ){
                                        $products[] = [
        
                                            'name'  =>  Wpfnl_functions::get_formated_product_name( $variation_product ),
                                            'id'    => $variation['variation_id'],
                                            'regular_price' =>  $variation['display_price'],
                                            'sale_price' => $variation['display_regular_price'],
                                        ];
                                    }
                                } 
                            }
                        }
                        
                    }else {
                    $products[] = [
                        'name' => rawurldecode($formatted_name),
                        'id'    => $product_object->get_id(),
                        'regular_price' => $product_object->get_regular_price(),
                        'sale_price' => $product_object->get_sale_price(),
                    ];
                    }
                }
            }
        }
        wp_send_json($products);
    }

    public function get_name()
    {
        return 'product';
    }

    /**
     * Wpfnl_product_search_by_category_and_name
     */
    public function wpfnl_product_search_by_category_and_name(){
        check_ajax_referer('wpfnl-admin', 'security');
        $category_id    = $_GET['category'];
        $product_name   = $_GET['term'];
        $args = array(
            'post_status'       => 'publish',
            'posts_per_page'    => -1,
            'post_type'         => ['product','product_variation'],
            's'                 => $product_name,
        );
        $products = get_posts($args);
        if(empty($products)){
            $data = [
                'status'  => 'success',
                'data'    => 'Product not found',
            ];
        }else{
            foreach($products as $key => $product){
                $_product = wc_get_product($product->ID);
                if( $_product ){
                    $products[$key]->price = $_product->get_price();
                }
            }
            $data = [
                'status'  => 'success',
                'data'    => $products,
            ];
        }
        wp_send_json($data);
    }
}
