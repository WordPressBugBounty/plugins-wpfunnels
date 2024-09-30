<?php
/**
 * Checkout for woocommerce
 *
 * @package
 */

namespace WPFunnels\Admin\FunnelType;
use WPFunnels\Admin\FunnelType\Wpfnl_Funnel_Type;
use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Wpfnl;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use \WC_Subscriptions_Product;
use WPFunnels\Metas\Wpfnl_Step_Meta_keys;
use WPFunnels\Admin\Modules\Steps\Checkout\Module;
class Wpfunnels_Wc_Checkout extends Wpfnl_Funnel_Type
{

    /**
     * Fetch product when search product in canvas
     */
    public function retrieve_item( $term = '' ){
        $products        = [];
        if( $term && Wpfnl_functions::is_wc_active() ){
            $data_store = \WC_Data_Store::load('product');
            $ids        = $data_store->search_products($term, '', false, false, 10);
            $product_objects = array_filter(array_map('wc_get_product', $ids), 'wc_products_array_filter_readable');
            if( is_array($product_objects) ){
                foreach ($product_objects as $product_object) {

                    if( $product_object && (( $product_object->managing_stock() && $product_object->get_stock_quantity() > 0 ) || ( !$product_object->managing_stock() && $product_object->get_stock_status() !== 'outofstock' )) ){
                        $formatted_name = $product_object->get_name();

                        if($product_object->get_type() == 'variable' || $product_object->get_type() == 'variable-subscription') {
                            $variations = $product_object->get_available_variations();
                            $isPro 		= Wpfnl_functions::is_wpfnl_pro_activated();
                            if( $isPro ) {
                                $parent_id = $product_object->get_id();
                                $products[ $parent_id ] = [
                                    'name'       => $formatted_name,
                                    'price'      => $product_object->get_variation_regular_price( 'min' ) ? $product_object->get_variation_regular_price( 'min' ) : $product_object->get_regular_price(),
                                    'sale_price' => $product_object->get_variation_sale_price( 'min' ) ? $product_object->get_variation_sale_price( 'min' ) : $product_object->get_price(),
                                ];
                            }

                            if( !empty($variations) ){

                                foreach ($variations as $variation) {
                                    $variation_product = wc_get_product($variation['variation_id']);

                                    if( $variation_product ){
                                        if( ( $variation_product->managing_stock() && $variation_product->get_stock_quantity() > 0 ) || ( !$variation_product->managing_stock() && $variation_product->get_stock_status() !== 'outofstock' ) ){

                                            $products[$variation['variation_id']] = [
                                                'name'  =>   Wpfnl_functions::get_formated_product_name( $variation_product ),
                                                'price' =>   wc_price($variation_product->get_regular_price()),
                                                'sale_price' => $variation_product->get_sale_price() ? wc_price($variation_product->get_sale_price()) : wc_price($variation_product->get_regular_price()),
                                            ];
                                        }
                                    }

                                }
                            }
                        }else {

							if( 'composite' === $product_object->get_type() ){
								$products[$product_object->get_id()] = [
									'name' => rawurldecode($formatted_name),
									'price' => Wpfnl_functions::get_composite_product_price( $product_object->get_id(), true ),
									'sale_price' => Wpfnl_functions::get_composite_product_price( $product_object->get_id(), false ),
								];
							}else{
								$products[$product_object->get_id()] = [
									'name' => rawurldecode($formatted_name),
									'price' => $product_object->get_regular_price(),
									'sale_price' => $product_object->get_sale_price(),
								];
							}
                        }
                    }
                }
            }

        }

        return $products;
    }

    /**
     * Retrieve coupons that match the provided search term from the WooCommerce data store.
     *
     * This function queries the WordPress database to retrieve coupons with titles
     * that match the given search term. Coupons are retrieved only if the search term
     * has a length of at least 3 characters and if WooCommerce is active. The retrieved
     * coupon names are returned as an array.
     *
     * @param string $term The search term to match against coupon titles.
     * @return array An array of coupon names that match the search term.
     * @since 2.8.6
     */
    public function retrieve_coupon( $term = '' ){
        if( 3 > strlen( $term ) || !Wpfnl_functions::is_wc_active() ) {
            return [];
        }

        global $wpdb;
        $query = $wpdb->prepare( "SELECT `post_title` AS name FROM %i ", $wpdb->posts );
        $query .= $wpdb->prepare( "WHERE post_type = %s ", 'shop_coupon' );
        $query .= "AND `post_title` LIKE '%{$wpdb->esc_like($term)}%' ";
        $query .= $wpdb->prepare( " AND post_status = %s ", 'publish' );
        $query .= 'ORDER BY post_name ASC';

        return $wpdb->get_col( $query );
    }


    /**
     * Fetch product when search course in canvas for orderbump
     *
     * @param $term
     */
    public function retrieve_ob_item( $term = '' ){
        $products        = [];
        if( $term && Wpfnl_functions::is_wc_active() ){
            $data_store = \WC_Data_Store::load('product');
			$ids = $data_store->search_products($term, '', false, false, 10);
			$product_objects = array_filter(array_map('wc_get_product', $ids), 'wc_products_array_filter_readable');
            if( is_array($product_objects) ){

                foreach ($product_objects as $product_object) {
                    if( $product_object && ($product_object->get_type() !== 'grouped') ){
                        if( ( $product_object->managing_stock() && $product_object->get_stock_quantity() > 0 ) || ( !$product_object->managing_stock() && $product_object->get_stock_status() !== 'outofstock' ) ){
                            $formatted_name = $product_object->get_name();
                            $product_image_id = $product_object->get_image_id();
                            $product_image_src = $product_image_id ? wp_get_attachment_image_src($product_image_id, 'large')[0] : '';

                            if ($product_object->get_type() == 'variable' || $product_object->get_type() == 'variable-subscription') {
                                $variations = $product_object->get_available_variations();
                                if( !empty($variations) ){
                                    foreach ($variations as $variation) {
                                        $variation_product = wc_get_product($variation['variation_id']);
                                        if( $variation_product ){
                                            if( ( $variation_product->managing_stock() && $variation_product->get_stock_quantity() > 0 ) || ( !$variation_product->managing_stock() && $variation_product->get_stock_status() !== 'outofstock' ) ){
                                                $price = $variation['display_regular_price'];
                                                if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                                                    $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $variation_product );
                                                    $price = $signUpFee + $variation['display_regular_price'];
                                                }

                                                $products[$variation['variation_id']] = [
                                                    'name' 	=> Wpfnl_functions::get_formated_product_name( $variation_product ),
                                                    'price' => wc_price($variation_product->get_regular_price()),
                                                    'sale_price' => $variation_product->get_sale_price() ? wc_price($variation_product->get_sale_price()) : wc_price($variation_product->get_regular_price()),
                                                    // 'html_price' => $product_object->get_price_html(),
                                                    'html_price' => wc_format_sale_price( $variation_product->get_regular_price(), $variation_product->get_sale_price() ? $variation_product->get_sale_price() :  $variation_product->get_price() ),
                                                    'title' => $product_object->get_title(),
                                                    'img' => array(
                                                        'id' => $product_image_id,
                                                        'url' => $product_image_src,
                                                    ),
                                                    'description' => $product_object->get_short_description()
                                                ];
                                            }
                                        }
                                    }
                                }
                            }else {
                                $sale_price = $product_object->get_sale_price();
                                if ($sale_price != "") {
                                    $sale_price = $product_object->get_sale_price() ? wc_price($product_object->get_sale_price()) : wc_price($product_object->get_regular_price());
                                }

                                $product_image_id = $product_object->get_image_id();
                                $product_image_src = $product_image_id ? wp_get_attachment_image_src($product_image_id, 'large')[0] : '';
                                $products[$product_object->get_id()] = [
                                    'name' => rawurldecode($formatted_name),
                                    'price' => wc_price($product_object->get_regular_price()),
                                    'sale_price' => $sale_price,
                                    'html_price' => $product_object->get_price_html(),
                                    'title' => $product_object->get_title(),
                                    'img' => array(
                                        'id' => $product_image_id,
                                        'url' => $product_image_src,
                                    ),
                                    'description' => $product_object->get_short_description()
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $products;
    }


    /**
     * Fetch product when search course in canvas for orderbump
     *
     * @param $term
     */
    public function retrieve_replace_ob_item( $term = '' ){
        $products        = [];
        if( $term && Wpfnl_functions::is_wc_active() ){
            $data_store = \WC_Data_Store::load('product');
			$ids = $data_store->search_products($term, '', false, false, 10);
			$product_objects = array_filter(array_map('wc_get_product', $ids), 'wc_products_array_filter_readable');
            if( is_array($product_objects) ){

                foreach ($product_objects as $product_object) {
                    if( $product_object && ($product_object->get_type() !== 'grouped') ){
                        if( ( $product_object->managing_stock() && $product_object->get_stock_quantity() > 0 ) || ( !$product_object->managing_stock() && $product_object->get_stock_status() !== 'outofstock' ) ){
                            $formatted_name = $product_object->get_name();
                            $product_image_id = $product_object->get_image_id();
                            $product_image_src = $product_image_id ? wp_get_attachment_image_src($product_image_id, 'large')[0] : '';

                            if ($product_object->get_type() == 'variable' || $product_object->get_type() == 'variable-subscription') {
                                $variations = $product_object->get_available_variations();
                                if( !empty($variations) ){
                                    foreach ($variations as $variation) {
                                        $variation_product = wc_get_product($variation['variation_id']);
                                        if( $variation_product ){
                                            if( ( $variation_product->managing_stock() && $variation_product->get_stock_quantity() > 0 ) || ( !$variation_product->managing_stock() && $variation_product->get_stock_status() !== 'outofstock' ) ){
                                                $price = $variation['display_regular_price'];
                                                if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                                                    $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $variation_product );
                                                    $price = $signUpFee + $variation['display_regular_price'];
                                                }

                                                $products[$variation['variation_id']] = [
                                                    'name' 	=> Wpfnl_functions::get_formated_product_name( $variation_product ),
                                                    'title' => $product_object->get_title(),
                                                ];
                                            }
                                        }
                                    }
                                }
                            }else {
                                $sale_price = $product_object->get_sale_price();
                                if ($sale_price != "") {
                                    $sale_price = $product_object->get_sale_price() ? wc_price($product_object->get_sale_price()) : wc_price($product_object->get_regular_price());
                                }

                                $product_image_id = $product_object->get_image_id();
                                $product_image_src = $product_image_id ? wp_get_attachment_image_src($product_image_id, 'large')[0] : '';
                                $products[$product_object->get_id()] = [
                                    'name' => rawurldecode($formatted_name),
                                    'title' => $product_object->get_title(),
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $products;
    }

    /**
     * Save checkout item to post meta
     *
     * @param Array $payload
     * @param String $product_id
     * @param Array $saved_products
     *
     * @since 2.4.6
     */
    public function save_items( $payload, $product_id, $saved_products ){

        if( isset($payload['step_id']) ){
            $step_id = $payload['step_id'];
            if ($saved_products) {
                foreach ($saved_products as $saved_products_key => $saved_products_value) {
                    if ($saved_products_value['id'] == $product_id) {
                        return [
                            'success' => false,
                            'message' => 'Product already exists',
                            'products' => [],
                        ];
                    }
                }
            }
            $product = wc_get_product($product_id);
            $_payload = [
                'step_id' => $step_id,
                'products' => [
                    'id' 				=> $product_id,
                    'quantity' 			=> $payload['quantity'],
                    'discount_type' 	=> $payload['discount_type'],
                    'discount_value' 	=> $payload['discount_value'],
                ]
            ];
			$disallow_regular_price = 'no';
            $this->checkout_update_product_tab_options($_payload);
            if ($product) {
                $title 	= $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();;
                $price 	= $product->get_price();
                $sale_price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_price();
                
                if($product->get_type() == 'variable' || $product->get_type() == 'variable-subscription') {
                    $regular_price = $product->get_variation_regular_price( 'min' ) ? $product->get_variation_regular_price( 'min' ) : $product->get_price();
                }else{
                    $regular_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
                }
                

                if ( is_a( $product, 'WC_Product_Bundle' ) ) {
					$disallow_regular_price = 'yes';
                    $price = $product->get_bundle_price('min');
                    $sale_price = $product->get_bundle_price('min');
                    $regular_price = $product->get_bundle_regular_price( 'min' ) ? $product->get_bundle_regular_price( 'min' ) : $product->get_bundle_price( 'min' );
                }

				if( 'composite' === $product->get_type() ){
					$disallow_regular_price = 'yes';
					$price = Wpfnl_functions::get_composite_product_price( $product->get_id(), false );
                    $sale_price = Wpfnl_functions::get_composite_product_price( $product->get_id(), false );
                    $regular_price = Wpfnl_functions::get_composite_product_price( $product->get_id(), true );
				}

                // for variation products
                if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                    if( 'subscription_variation' === $product->get_type() || 'subscription' === $product->get_type() ){
                        $signUpFee 	= \WC_Subscriptions_Product::get_sign_up_fee( $product );
                        $price 		= $price + $signUpFee;
                        $sale_price= $sale_price + $signUpFee;
                        $regular_price= $regular_price + $signUpFee;
                    }
                }

                $subtext 				= "";
                $text_highlight 		= "";
                $text_highlight_enabler = "";
                $description 			= substr($product->get_description(), 0, 20);
                $pr_image 				= wp_get_attachment_image_src($product->get_image_id(), 'single-post-thumbnail');
                $qty 					= 1;
                return [
                    'success' => true,
					'disallow_regular_price' => $disallow_regular_price,
                    'products' => [
                        'id' 				=> $product_id,
                        'title' 			=> $title,
                        'price' 			=> wc_price($price),
                        'numeric_price'     => $price,
                        'currency' 			=> get_woocommerce_currency_symbol(),
                        'sale_price' 		=> $sale_price,
                        'regular_price' 	=> $regular_price,
                        'quantity' 			=> $qty,
                        'image' 			=> $pr_image ? $pr_image[0] : '',
                        'discount_type' 	=> $payload['discount_type'],
                        'discount_value' 	=> $payload['discount_value'],
                        'product_edit_link' => in_array($product->get_type(), array( 'variation', 'subscription_variation' )) ? get_edit_post_link($product->get_parent_id()) : get_edit_post_link($product->get_id()),
                        'product_view_link' => in_array($product->get_type(), array( 'variation', 'subscription_variation' )) ? get_permalink($product->get_parent_id()) : get_permalink($product->get_id()),
                        'is_trash'          => 'trash' === $product->get_status() ? 'yes' : 'no',
                        'is_deleted'        => 'no',
                    ],
                ];
            }
        }

    }


    /**
	 * Save checkout product tab
	 *
	 * @param $payload
     *
	 * @return array
	 */
	private function checkout_update_product_tab_options($payload)
	{

		$step_id = sanitize_text_field($payload['step_id']);
		unset($payload['step_id']);
		$step = Wpfnl::get_instance()->step_store;
		$step->set_id($step_id);
        $type = 'checkout';
		$internal_keys = Wpfnl_Step_Meta_keys::get_meta_keys($type);
        $prefix = '_wpfnl_checkout_';
		foreach ($payload as $key => $value) {
			if (array_key_exists($prefix . $key, $internal_keys)) {
				switch ($key) {
					case 'products':
						$products = [];
						if (!empty($value)) {
							$saved_products = get_post_meta($step_id, '_wpfnl_checkout_products', true);
							if ($saved_products) {
								$saved_products[] = $value;
								$products = $saved_products;
							} else {
								$products[] = $value;
							}
						}
						$step->update_meta($step_id, $prefix . $key, $products);
						break;
					case 'discount':
						$discount[] = $value;
						$step->update_meta( $step_id, $prefix . $key, $discount );
						break;
					case 'coupon':
						$coupon = $value;
						$step->update_meta($step_id, $prefix . $key, $coupon);
						break;
					default:
						$step->update_meta($step_id, $prefix . $key, $value);
						break;
				}
			}
		}
		return [
			'success' => true,
		];
	}


    /**
     * Get products for checkout step
     *
     * @param $term
     *
     * @return Array
     */
    public function get_items( $step_id = '' ){
        $response = [];
        if( $step_id ){
            $products 			=  get_post_meta($step_id, '_wpfnl_checkout_products', true);
            $use_of_coupon 		=  get_post_meta($step_id, '_wpfnl_checkout_coupon', true);
            $isMultipleProduct 	=  get_post_meta($step_id, '_wpfnl_multiple_product', true);
            $isQuantity 		=  get_post_meta($step_id, '_wpfnl_quantity_support', true);
            $discount 			=  get_post_meta($step_id, '_wpfnl_checkout_discount_main_product', true);
            $auto_coupon 		=  get_post_meta($step_id, '_wpfnl_checkout_auto_coupon', true);
            $time_bound_discount_settings 		=  get_post_meta($step_id, '_wpfnl_time_bound_discount_settings', true);
			$quantityLimit 		=  get_post_meta($step_id, '_wpfnl_quantity_limit', true);
			$wpmlWidgetPosition 		=  get_post_meta($step_id, '_wpfnl_wpml_Widget_Position_on_checkout', true);
			$disableGateways 		=  get_post_meta($step_id, '_wpfnl_disabled_payemnts', true);
            if( !$time_bound_discount_settings ){
                $dateTime = new \DateTime();
                $time_bound_discount_settings = [
                    'isEnabled' => 'no',
                    'fromDate' => $dateTime->format('M d, Y'),
                    'toDate' => $dateTime->add(new \DateInterval('P1D'))->format('M d, Y')
                ];
            }
            if ( 'yes' === $use_of_coupon ) {
                $use_of_coupon = true;
            }
            else {
                $use_of_coupon = false;
            }

            if( 'yes' === $isMultipleProduct ) {
                $isMultipleProduct = true;
            }
            else {
                $isMultipleProduct = false;
            }

            if( 'yes' === $isQuantity ) {

                $isQuantity = true;
            }
            else {
                $isQuantity = false;
            }

			$disallow_regular_price = 'no';
            if( Wpfnl_functions::is_wc_active() ) {
                if( is_array($products) ) {
                    foreach( $products as $value ) {
                        $product = wc_get_product( $value[ 'id' ] );
                        if( $product ){
                            $title   = $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();;
                            $image         = wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' ) ? wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' ) : wp_get_attachment_image_src( $product->get_image_id(), 'single-post-thumbnail' );
                            $price         = $product->get_price();
                            $sale_price    = $product->get_sale_price() ? $product->get_sale_price() : $product->get_price();
                            
                            if($product->get_type() == 'variable' || $product->get_type() == 'variable-subscription') {
                                $regular_price = $product->get_variation_regular_price( 'min' ) ? $product->get_variation_regular_price( 'min' ) : $product->get_price();
                            }else{
                                $regular_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
                            }

                            if ( is_a( $product, 'WC_Product_Bundle' ) ) {
								$disallow_regular_price = 'yes';
                                $price = $product->get_bundle_price('min');
                                $sale_price = $product->get_bundle_price('min');
                                $regular_price = $product->get_bundle_regular_price( 'min' ) ? $product->get_bundle_regular_price( 'min' ) : $product->get_bundle_price( 'min' );
                            }

							if( 'composite' === $product->get_type() ){
								$disallow_regular_price = 'yes';
								$price = Wpfnl_functions::get_composite_product_price( $product->get_id(), false );
								$sale_price = Wpfnl_functions::get_composite_product_price( $product->get_id(), false );
								$regular_price = Wpfnl_functions::get_composite_product_price( $product->get_id(), true );
							}

                            if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
                                $signUpFee     = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                                $price         = $price + $signUpFee;
                                $sale_price    = $sale_price + $signUpFee;
                                $regular_price = $regular_price + $signUpFee;
                            }
                            $response[ 'products' ][] = array(
                                'id'                => $value[ 'id' ],
                                'title'             => $title,
                                'price'             => wc_price( $price ),
                                'numeric_price'     => $price,
                                'currency'          => get_woocommerce_currency_symbol(),
                                'sale_price'        => $sale_price,
                                'regular_price'     => $regular_price,
                                'quantity'          => $value[ 'quantity' ],
                                'image'             => $image ? $image[ 0 ] : '',
                                'discount_type'     => '',
                                'discount_value'    => '',
                                'product_edit_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_edit_post_link( $product->get_parent_id() ) : get_edit_post_link( $product->get_id() ),
                                'product_view_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_permalink( $product->get_parent_id() ) : get_permalink( $product->get_id() ),
                                'is_trash'          => 'trash' === $product->get_status() ? 'yes' : 'no',
                                'is_deleted'        => 'no',
                            );
                        }
                    }
                }
                else {
                    $response[ 'products' ] = array();
                }
            }else{
                $response[ 'products' ] = array();
            }

            $response[ 'coupon' ]            = $use_of_coupon;
            $response[ 'autoCoupon' ]        = $auto_coupon;
            $response[ 'isMultipleProduct' ] = $isMultipleProduct;
            $response[ 'isQuantity' ]        = $isQuantity;
            $response[ 'quantityLimit' ]        = $quantityLimit;
            $response[ 'wpmlWidgetPosition' ]        = $wpmlWidgetPosition;
            $response[ 'disallow_regular_price' ]        = $disallow_regular_price;
            $response[ 'disableGateways' ]        = $disableGateways;
            $response[ 'discount' ]          = $discount;
            $response[ 'time_bound_discount_settings' ]          = $time_bound_discount_settings;
            $response[ 'success' ]           = true;
            $response['columns'] = Wpfnl_functions::get_checkout_columns( $step_id );
        }

        return $response;
    }


}
