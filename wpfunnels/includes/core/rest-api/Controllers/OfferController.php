<?php

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WPFunnels\Wpfnl_functions;
use \WC_Subscriptions_Product;
// use Wpfnl_Pro_OfferProduct_Factory; // Removed - checking class_exists() instead to support free plugin

class OfferController extends Wpfnl_REST_Controller
{

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wpfunnels/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'offer';

    /**
     * check if user has valid permission
     *
     * @param $request
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function update_items_permissions_check($request)
    {   
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'steps', 'edit' )) {
            return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot edit this resource.', 'wpfnl'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     * @since  3.0.0
     */
    public function get_items_permissions_check($request)
    {
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('settings')) {
            return new WP_Error('wpfunnels_rest_cannot_view', __('Sorry, you cannot list resources.', 'wpfnl'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }


    /**
     * register rest routes
     *
     * @since 1.0.0
     */
    public function register_routes()
    {

        register_rest_route($this->namespace, '/' . $this->rest_base . '/getUpsellData/', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_upsell_data'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/saveUpsellData/', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'save_upsell_data'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);
        
        register_rest_route($this->namespace, '/' . $this->rest_base . '/add-offer-product/', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'add_offer_product'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/getDownsellData/', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_downsell_data'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/saveDownsellData/', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'save_downsell_data'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);
    }


    /**
     * Save upsell data
     */
    public function save_upsell_data($request)
    {
        $step_id = $request['step_id'];
        $products = array();
        $data = json_decode($request['product'], true);
        $products[] = $data;
        update_post_meta($step_id, '_wpfnl_upsell_products', $products);
        return 'success';
    }
    
    
    /**
     * Save upsell data
     */
    public function add_offer_product($request)
    {
        $step_id    = isset($request['step_id']) ? sanitize_text_field($request['step_id']) : '';
        $id         = isset($request['product_id']) ? sanitize_text_field( $request['product_id'] ) : '';
        $quantity         = isset($request['quantity']) ? sanitize_text_field( $request['quantity'] ) : '';
        $offer_type         = isset($request['type']) ? sanitize_text_field( $request['type'] ) : 'upsell';
        if(!$step_id) {
            return [
                'success' => false,
            ];
        }
        $data = array(
            array(
                'id'        =>  $id,
                'quantity'  =>  $quantity
            )
        );
        $type = '';
        if( $request['isLms'] == 'true' ){
            $type = 'lms';
        }else{
            $type = 'wc';
        }

        // Check if pro plugin offer factory is available
        if ( class_exists( '\Wpfnl_Pro_OfferProduct_Factory' ) ) {
            $class_object = \Wpfnl_Pro_OfferProduct_Factory::build( $type );
            if( $class_object ){
                $function = 'add_'.$offer_type.'_items';
                $response = $class_object->$function( $id, $data, $step_id );
              
                if( $response ){
                    return $response;
                }
            }
        } else {
            // Fallback for free plugin - basic offer product addition
            $response = $this->add_basic_offer_product( $id, $data, $step_id, $offer_type );
            if( $response ){
                return $response;
            }
        }
        
        return array(
            'success'   => false,
            'message'   => __('Product Not Found', 'wpfnl')
        );
    }


    /**
     * Get upsell product data
     *
     * @param $request
     * @return WP_Error|\WP_REST_Response
     *
     * @since 1.0.0
     */
    public function get_upsell_data( $request )
    {
        $response = [];
        $step_id    = $request['step_id'];
        $_products   = get_post_meta( $step_id, '_wpfnl_upsell_products', true );
        $products   =  apply_filters( 'wpfunnels/upsell_product', $_products, $step_id );
        $funnel_id  = Wpfnl_functions::get_funnel_id_from_step($step_id);
        $type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
        if( 'lms' === $type ){
            $_class = 'lms';
        }else{
            $_class = 'wc';
        }
        
        // Check if pro plugin offer factory is available
        if ( class_exists( '\Wpfnl_Pro_OfferProduct_Factory' ) ) {
            $class_object = \Wpfnl_Pro_OfferProduct_Factory::build( $_class );
            if( $class_object ){
                $response = $class_object->get_upsell_items( $products, $step_id );
            }
        } else {
            // Fallback for free plugin - basic offer product data structure
            $response = $this->get_basic_upsell_items( $products, $step_id );
        }
        
        $response['priceConfig'] = Wpfnl_functions::get_wc_price_config();
        return $this->prepare_item_for_response( $response, $request );
    }

    /**
     * Save downsell data
     */
    public function save_downsell_data($request)
    {
        $step_id = $request['step_id'];
        $products = array();
        $data = json_decode($request['product'], true);
        $products[] = $data;
        update_post_meta($step_id, '_wpfnl_downsell_product', $products);
        return 'success';
    }


    /**
     * get downsell product data
     *
     * @param $request
     * @return WP_Error|\WP_REST_Response
     *
     * @since 1.0.0
     */
    public function get_downsell_data($request)
    {
        $response = [];
        $step_id        = $request['step_id'];
        $_products      = get_post_meta( $step_id, '_wpfnl_downsell_products', true );
        $products       =  apply_filters( 'wpfunnels/downsell_product', $_products, $step_id );
        $discount       = get_post_meta( $step_id, '_wpfnl_downsell_discount', true );
        $offer_settings = \WPFunnels\Wpfnl_functions::get_offer_settings();
        $funnel_id  = Wpfnl_functions::get_funnel_id_from_step($step_id);
        $type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
        if( 'lms' === $type ){
            $_class = 'lms';
        }else{
            $_class = 'wc';
        }

        // Check if pro plugin offer factory is available
        if ( class_exists( '\Wpfnl_Pro_OfferProduct_Factory' ) ) {
            $class_object = \Wpfnl_Pro_OfferProduct_Factory::build( $_class );
            
            if( $class_object ){
                $response = $class_object->get_downsell_items( $products, $step_id );
            }
        } else {
            // Fallback for free plugin - basic offer product data structure
            $response = $this->get_basic_downsell_items( $products, $step_id );
        }
        $response['priceConfig'] = Wpfnl_functions::get_wc_price_config();
        return $this->prepare_item_for_response( $response, $request );
    }


    /**
     * Prepare a single setting object for response.
     *
     * @param object $item Setting object.
     * @param WP_REST_Request $request Request object.
     * @return \WP_REST_Response $response Response data.
     * @since  1.0.0
     */
    public function prepare_item_for_response($item, $request)
    {
        $data = $this->add_additional_fields_to_object($item, $request);
        return rest_ensure_response($data);
    }


    /**
     * Get basic upsell items for free plugin
     * 
     * @param array $products
     * @param int $step_id
     * @return array
     */
    private function get_basic_upsell_items( $products, $step_id ) {
        $response = array();
        
        if( $products && count($products)) {
            $product_obj = $products[0];
            $product = wc_get_product($product_obj['id']);

            if( $product instanceof \WC_Product ) {
                $title = $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();
                $image = wp_get_attachment_image_src( $product->get_image_id(), 'single-post-thumbnail' );
                $price = $product->get_price();
                
                if($product->get_type() == 'variable' || $product->get_type() == 'variable-subscription') {
                    $regular_price = $product->get_variation_regular_price( 'min' ) ? $product->get_variation_regular_price( 'min' ) : $product->get_price();
                } else {
                    $regular_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
                }

                $sale_price = $product->get_type() == 'variable' ? $price : $product->get_sale_price();

                $response['products'][] = array(
                    'id'                => $product_obj['id'],
                    'title'             => $title,
                    'price'             => wc_price( $price ),
                    'numeric_price'     => $price,
                    'currency'          => get_woocommerce_currency_symbol(),
                    'quantity'          => $product_obj['quantity'],
                    'image'             => $image ? $image[0] : '',
                    'regular_price'     => $regular_price,
                    'sale_price'        => $sale_price ? $sale_price : $price,
                    'html_price'        => $product->get_price_html(),
                    'product_edit_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_edit_post_link( $product->get_parent_id() ) : get_edit_post_link( $product->get_id() ),
                    'product_view_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_permalink( $product->get_parent_id() ) : get_permalink( $product->get_id() ),
                    'is_trash'          => 'trash' === $product->get_status() ? 'yes' : 'no',
                    'is_deleted'        => 'no',
                );

                $discount = get_post_meta( $step_id, '_wpfnl_upsell_discount', true );
                $response['discount'] = $discount ? $discount : array(
                    'discountType'      => 'original',
                    'discountApplyTo'   => 'regular',
                    'discountValue'     => '0',
                    'discountPrice'     => floatval($price) * intval($product_obj['quantity']),
                    'discountPriceHtml' => wc_format_sale_price( floatval($price) * floatval($product_obj['quantity']), ( $sale_price ? $sale_price : floatval($product->get_price()) ) * floatval($product_obj['quantity']) ),
                );
            } else {
                $response['products'][] = array(
                    'is_trash'   => 'no',
                    'is_deleted' => 'yes',
                );
                $response['discount'] = array(
                    'discountType'      => 'original',
                    'discountApplyTo'   => 'regular',
                    'discountValue'     => '0',
                    'discountPrice'     => 0,
                    'discountPriceHtml' => wc_price(0),
                );
            }
        } else {
            $response['products'] = array();
            $response['discount'] = array(
                'discountType'      => 'original',
                'discountApplyTo'   => 'regular',
                'discountValue'     => '0',
                'discountPrice'     => 0,
                'discountPriceHtml' => wc_price(0),
            );
        }

        // Add additional fields required by Vue component
        $replaceSettings = get_post_meta( $step_id, '_wpfnl_upsell_replacement_settings', true );
        if( $replaceSettings == 'true' ) {
            $response['replaceSettings'] = $replaceSettings;
            $isOfferReplace = get_post_meta( $step_id, '_wpfnl_upsell_replacement', true );
            $response['isOfferReplace'] = array(
                'replacement_type' => isset($isOfferReplace['replacement_type']) ? $isOfferReplace['replacement_type'] : '',
                'value'            => isset($isOfferReplace['value']) ? $isOfferReplace['value'] : '',
            );
        } else {
            $response['replaceSettings'] = $replaceSettings;
            $response['isOfferReplace'] = array(
                'replacement_type' => '',
                'value'            => '',
            );
        }

        $offer_settings = Wpfnl_functions::get_offer_settings();
        $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $step_id );
        $prev_step = Wpfnl_functions::get_prev_step( $funnel_id, $step_id );
        $response['prevStep'] = isset( $prev_step['step_type'] ) && $prev_step['step_type'] ? $prev_step['step_type'] : '';
        $response['success'] = true;
        $response['isChildOrder'] = $offer_settings['offer_orders'] == 'child-order' ? true : false;
        $response['columns'] = Wpfnl_functions::get_checkout_columns( $step_id );
        
        $time_bound_discount_settings = get_post_meta($step_id, '_wpfnl_time_bound_discount_settings', true);
        if( !$time_bound_discount_settings ){
            $dateTime = new \DateTime();
            $time_bound_discount_settings = array(
                'isEnabled' => 'no',
                'fromDate' => $dateTime->format('M d, Y'),
                'toDate' => $dateTime->add(new \DateInterval('P1D'))->format('M d, Y')
            );
        }
        $response['time_bound_discount_settings'] = $time_bound_discount_settings;

        return $response;
    }


    /**
     * Get basic downsell items for free plugin
     * 
     * @param array $products
     * @param int $step_id
     * @return array
     */
    private function get_basic_downsell_items( $products, $step_id ) {
        $response = array();
        
        if( $products && count($products)) {
            $product_obj = $products[0];
            $product = wc_get_product($product_obj['id']);

            if( $product instanceof \WC_Product ) {
                $title = $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();
                $image = wp_get_attachment_image_src( $product->get_image_id(), 'single-post-thumbnail' );
                $price = $product->get_price();
                
                if($product->get_type() == 'variable' || $product->get_type() == 'variable-subscription') {
                    $regular_price = $product->get_variation_regular_price( 'min' ) ? $product->get_variation_regular_price( 'min' ) : $product->get_price();
                } else {
                    $regular_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
                }

                $sale_price = $product->get_type() == 'variable' ? $price : $product->get_sale_price();

                $response['products'][] = array(
                    'id'                => $product_obj['id'],
                    'title'             => $title,
                    'price'             => wc_price( $price ),
                    'numeric_price'     => $price,
                    'currency'          => get_woocommerce_currency_symbol(),
                    'quantity'          => $product_obj['quantity'],
                    'image'             => $image ? $image[0] : '',
                    'regular_price'     => $regular_price,
                    'sale_price'        => $sale_price ? $sale_price : $price,
                    'html_price'        => $product->get_price_html(),
                    'product_edit_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_edit_post_link( $product->get_parent_id() ) : get_edit_post_link( $product->get_id() ),
                    'product_view_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_permalink( $product->get_parent_id() ) : get_permalink( $product->get_id() ),
                    'is_trash'          => 'trash' === $product->get_status() ? 'yes' : 'no',
                    'is_deleted'        => 'no',
                );

                $discount = get_post_meta( $step_id, '_wpfnl_downsell_discount', true );
                $response['discount'] = $discount ? $discount : array(
                    'discountType'      => 'original',
                    'discountApplyTo'   => 'regular',
                    'discountValue'     => '0',
                    'discountPrice'     => floatval($price) * intval($product_obj['quantity']),
                    'discountPriceHtml' => wc_format_sale_price( floatval($price) * floatval($product_obj['quantity']), ( $sale_price ? $sale_price : floatval($product->get_price()) ) * floatval($product_obj['quantity']) ),
                );
            } else {
                $response['products'][] = array(
                    'is_trash'   => 'no',
                    'is_deleted' => 'yes',
                );
                $response['discount'] = array(
                    'discountType'      => 'original',
                    'discountApplyTo'   => 'regular',
                    'discountValue'     => '0',
                    'discountPrice'     => 0,
                    'discountPriceHtml' => wc_price(0),
                );
            }
        } else {
            $response['products'] = array();
            $response['discount'] = array(
                'discountType'      => 'original',
                'discountApplyTo'   => 'regular',
                'discountValue'     => '0',
                'discountPrice'     => 0,
                'discountPriceHtml' => wc_price(0),
            );
        }

        // Add additional fields required by Vue component
        $replaceSettings = get_post_meta( $step_id, '_wpfnl_downsell_replacement_settings', true );
        if( $replaceSettings == 'true' ) {
            $response['replaceSettings'] = $replaceSettings;
            $isOfferReplace = get_post_meta( $step_id, '_wpfnl_downsell_replacement', true );
            $response['isOfferReplace'] = array(
                'replacement_type' => isset($isOfferReplace['replacement_type']) ? $isOfferReplace['replacement_type'] : '',
                'value'            => isset($isOfferReplace['value']) ? $isOfferReplace['value'] : '',
            );
        } else {
            $response['replaceSettings'] = $replaceSettings;
            $response['isOfferReplace'] = array(
                'replacement_type' => '',
                'value'            => '',
            );
        }

        $offer_settings = Wpfnl_functions::get_offer_settings();
        $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $step_id );
        $prev_step = Wpfnl_functions::get_prev_step( $funnel_id, $step_id );
        $response['prevStep'] = isset( $prev_step['step_type'] ) && $prev_step['step_type'] ? $prev_step['step_type'] : '';
        $response['success'] = true;
        $response['isChildOrder'] = $offer_settings['offer_orders'] == 'child-order' ? true : false;
        $response['columns'] = Wpfnl_functions::get_checkout_columns( $step_id );
        
        $time_bound_discount_settings = get_post_meta($step_id, '_wpfnl_time_bound_discount_settings', true);
        if( !$time_bound_discount_settings ){
            $dateTime = new \DateTime();
            $time_bound_discount_settings = array(
                'isEnabled' => 'no',
                'fromDate' => $dateTime->format('M d, Y'),
                'toDate' => $dateTime->add(new \DateInterval('P1D'))->format('M d, Y')
            );
        }
        $response['time_bound_discount_settings'] = $time_bound_discount_settings;

        return $response;
    }


    /**
     * Add basic offer product for free plugin
     * 
     * @param int $product_id
     * @param array $data
     * @param int $step_id
     * @param string $offer_type
     * @return array
     */
    private function add_basic_offer_product( $product_id, $data, $step_id, $offer_type ) {
        $product = wc_get_product($product_id);
        
        if ( !$product instanceof \WC_Product ) {
            return array(
                'success' => false,
                'message' => __('Product Not Found', 'wpfnl')
            );
        }

        // Save product data based on offer type
        $meta_key = $offer_type === 'upsell' ? '_wpfnl_upsell_products' : '_wpfnl_downsell_products';
        update_post_meta($step_id, $meta_key, $data);

        $title = $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();
        $image = wp_get_attachment_image_src( $product->get_image_id(), 'single-post-thumbnail' );
        $price = $product->get_price();
        
        if($product->get_type() == 'variable' || $product->get_type() == 'variable-subscription') {
            $regular_price = $product->get_variation_regular_price( 'min' ) ? $product->get_variation_regular_price( 'min' ) : $product->get_price();
        } else {
            $regular_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
        }

        $sale_price = $product->get_type() == 'variable' ? $price : $product->get_sale_price();
        $quantity = isset($data[0]['quantity']) ? intval($data[0]['quantity']) : 1;

        return array(
            'success' => true,
            'message' => __('Product Added Successfully', 'wpfnl'),
            'products' => array(
                array(
                    'id'                => $product_id,
                    'title'             => $title,
                    'price'             => wc_price( $price ),
                    'numeric_price'     => $price,
                    'currency'          => get_woocommerce_currency_symbol(),
                    'quantity'          => $quantity,
                    'image'             => $image ? $image[0] : '',
                    'regular_price'     => $regular_price,
                    'sale_price'        => $sale_price ? $sale_price : $price,
                    'html_price'        => $product->get_price_html(),
                    'product_edit_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_edit_post_link( $product->get_parent_id() ) : get_edit_post_link( $product->get_id() ),
                    'product_view_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_permalink( $product->get_parent_id() ) : get_permalink( $product->get_id() ),
                    'is_trash'          => 'trash' === $product->get_status() ? 'yes' : 'no',
                    'is_deleted'        => 'no',
                )
            ),
            'discount' => array(
                'discountType'      => 'original',
                'discountApplyTo'   => 'regular',
                'discountValue'     => '0',
                'discountPrice'     => floatval($price) * intval($quantity),
                'discountPriceHtml' => wc_format_sale_price( floatval($price) * floatval($quantity), ( $sale_price ? $sale_price : floatval($product->get_price()) ) * floatval($quantity) ),
            ),
            'columns' => Wpfnl_functions::get_checkout_columns( $step_id )
        );
    }

    
}
