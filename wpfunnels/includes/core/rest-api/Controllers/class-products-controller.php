<?php
/**
 * Product controller
 * 
 * @package WPFunnels\Rest\Controllers
 * @since 1.0.0
 */
namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;
use function cli\err;
use \WC_Subscriptions_Product;
use Wpfnl_Type_Factory;
use WPFunnels\Discount\WpfnlDiscount;
use WPFunnels\Wpfnl;

/**
 * This class has the functions which are responsible
 * to control the woo products
 *
 * @package /includes/core/rest-api/Controllers
 * @since 1.0.0
 */
class ProductsController extends Wpfnl_REST_Controller {
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
	protected $rest_base = 'products';

	/**
	 * Check if user has valid permission
	 *
	 * @param $request
	 * 
	 * @return bool|WP_Error
	 * @since  1.0.0
	 */
	public function update_items_permissions_check($request)
	{
		if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('steps', 'edit')) {
			return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot edit this resource.', 'wpfnl'), array('status' => rest_authorization_required_code()));
		}
		return true;
	}

	/**
	 * Makes sure the current user has access to READ the settings APIs.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * 
	 * @return WP_Error|boolean
	 * @since  3.0.0
	 */
	public function get_items_permissions_check($request)
	{
		if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('settings')) {
			return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot list resources.', 'wpfnl'), array('status' => rest_authorization_required_code()));
		}
		return true;
	}


	/**
	 * Register rest routes
	 *
	 * @since 1.0.0
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/get_products'. '/(?P<step_id>\d+)' , array(
				array(
					'methods'               => WP_REST_Server::READABLE,
					'callback'              => array( $this, 'get_products' ),
					'permission_callback'   => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route($this->namespace, '/getProducts/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_wc_products'
				],
				'permission_callback' => [
					$this,
					'get_items_permissions_check'
				],
			],
		]);


		register_rest_route($this->namespace, '/calculateDiscountPrice/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'calculate_discount_price'),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			],
		]);
		
		register_rest_route($this->namespace, '/calculateMainProductsDiscount/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'calculate_main_products_discount'),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			],
		]);

		register_rest_route(
			$this->namespace,
			'/delete-product',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_product' ),
					'args'				  => array(
						'step_id'         => array(
							'required'          => true,
							'type'              => array('integer', 'string'),
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg'
						),
						'step_type'		  => array(
							'type'			    => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'default'			=> 'checkout',
							'validate_callback' => 'rest_validate_request_arg'
						),
						'index'			  => array(
							'type'				=> array('integer', 'string'),
							'sanitize_callback' => 'absint',
							'default'			=> 'checkout',
							'validate_callback' => 'rest_validate_request_arg'
						)
					),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}


	/**
	 * Calculate Discount Price
	 *
	 * @param mixed $request
	 * 
	 * @return array|WP_Error
	 * @since 1.7.8
	 */
    public function calculate_discount_price( $request ) {

        $data     = $request->get_params();
        $response = [
            'success'           => true,
            'discountPrice'     => '',
            'discountPriceHtml' => '',
            'htmlPrice'         => '',
            'salePrice'         => '',
			'regularPrice'      => '',
        ];
        if( !empty( $data[ 'product' ] ) ) {
            $product = wc_get_product( $data[ 'product' ] );
            if( $product ) {
                $response = $this->calculate_wc_discount_price( $data, $product );
            }
            else {
                $response = $this->calculate_lms_discount_price( $data );
            }
        }
        return rest_ensure_response( $response );
    }

    /**
     * Calculate the discounted price for a WooCommerce product based on discount type and value.
     *
     * This function calculates the discounted price of a WooCommerce product based on the specified discount type and value.
     *
     * @param array $data    An associative array containing product data including:
     *                       - 'discount_type' (string): The type of discount ('percentage' or 'fixed').
     *                       - 'discount_value' (float): The discount value.
     *                       - 'applyto' (string): Where the discount should be applied ('original' or 'sale').
     *                       - 'quantity' (int): The quantity of the product.
     * @param object $product A WooCommerce product object.
     *
     * @return array An associative array containing the result of the discount calculation:
     *               - 'success' (bool): Indicates if the calculation was successful.
     *               - 'discountPrice' (string): The calculated discounted price in WooCommerce price format.
     *               - 'discountPriceHtml' (string): An HTML representation of the discounted price.
     *               - 'htmlPrice' (string): An HTML representation of the product price.
     */
    private function calculate_wc_discount_price($data, $product) {

		// Get the quantity of the product from data.
		$data_quantity = !empty($data['quantity']) ? intval($data['quantity']) : 1;
	
        // Determine the regular price based on product type.
        $regular_price = $product->get_type() == 'variable' ? $product->get_price() : $product->get_regular_price();
		
		$regular_price = floatval($regular_price);
		$regular_price = $regular_price * $data_quantity;


        // Check if WooCommerce Subscriptions plugin is active and adjust the regular price if needed.
        if (is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
            $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee($product);
            $regular_price = $signUpFee + $regular_price;
        }

        // Determine the sale price based on product type.
        $sale_price = $product->get_type() == 'variable' ? $product->get_price() : $product->get_sale_price();
		$sale_price = floatval($sale_price);
		$sale_price = $sale_price * $data_quantity;


        if ($data['discount_type'] === 'original') {
            $calculable_price = $regular_price;
            $discount_price = $sale_price ?: $regular_price;
        } else {
            if ($data['applyto'] == 'sale') {
                $calculable_price = $sale_price != "" ? $sale_price : $regular_price;
            } else {
                $calculable_price = $regular_price;
            }
            $discount_instance = new WpfnlDiscount();
            $discount_price = $discount_instance->calculate_discount($data['discount_type'], $data['discount_value'], $calculable_price);
        }

        // Remove non-numeric characters from prices.
        $calculable_price = preg_replace('/[^\d.]/', '', $calculable_price);
        $discount_price = preg_replace('/[^\d.]/', '', $discount_price);

        // Get the quantity of the product from data.
        $data_quantity = !empty($data['quantity']) ? $data['quantity'] : 1;
		
        return [
            'success'           => true,
            'discountPrice'     => wc_price($discount_price),
            'discountPriceHtml' => wc_format_sale_price($calculable_price, $discount_price),
            'htmlPrice'         => $sale_price ? wc_format_sale_price($regular_price, $sale_price) : wc_price($regular_price),
            'salePrice'         => wc_price(floatval($sale_price)),
            'regularPrice'      => wc_price(floatval($regular_price)),
        ];
    }

    /**
     * Calculate the discounted price for an LMS product based on discount type and value.
     *
     * This function calculates the discounted price of an LMS (Learning Management System) product
     * based on the specified discount type and value. If the discount type is applicable to the regular price,
     * it calculates the discount on the regular price; otherwise, it calculates it on the sale price.
     *
     * @param array $data An associative array containing product data including:
     *                    - 'discount_type' (string): The type of discount ('percentage' or 'fixed').
     *                    - 'discount_value' (float): The discount value.
     *                    - 'applyto' (string): Where the discount should be applied ('regular' or 'sale').
     *                    - 'regular_price' (float): The regular price of the product.
     *                    - 'sale_price' (float): The sale price of the product.
     *
     * @return array An associative array containing the result of the discount calculation:
     *               - 'success' (bool): Indicates if the calculation was successful.
     *               - 'discountPrice' (float): The calculated discounted price.
     *               - 'discountPriceHtml' (string): An optional HTML representation of the discounted price.
     *               - 'htmlPrice' (string): An optional HTML representation of the price.
     */
    private function calculate_lms_discount_price( $data ) {
        if( !empty( $data[ 'discount_value' ] ) && !empty( $data[ 'regular_price' ] ) && !empty( $data[ 'discount_type' ] ) ) {
            // Create a new instance of the WpfnlDiscount class.
            $discount_instance = new WpfnlDiscount();

            // Calculate the discount based on the specified parameters.
            $discounted_price = $discount_instance->calculate_discount(
                $data[ 'discount_type' ],
                $data[ 'discount_value' ],
                $data[ 'regular_price' ]
            );

            // Return the result of the discount calculation.
            return [
                'success'           => true,
                'discountPrice'     => $discounted_price,
                'discountPriceHtml' => learndash_get_currency_symbol() . $discounted_price,
                'htmlPrice'         => learndash_get_currency_symbol() . $data[ 'regular_price' ]
            ];
        }

        // If no discount value is provided, indicate that the calculation was not successful.
        return [ 'success' => false ];
    }

	/**
	 * Calculate Discount Price for main product
	 *
	 * @param string $data
	 * 
	 * @return array|WP_Error
	 */
	public function calculate_main_products_discount( $request )
	{
		$data = $request->get_params();
		$discounted_price = 0;
		$response = [
			'success' => false,
		];
		if( isset($data['data']) ){
			$discounted_price = isset($data['data']['apply_to']) && $data['data']['apply_to'] == 'regular' ?  $data['data']['regular_price'] : $data['data']['sale_price'];
			if( isset( $data['data']['step_id']) ){
				if( isset($data['data']['discount_amount'])){
					$discount_instance = new WpfnlDiscount();
				    $discounted_price = $discount_instance->calculate_discount( $data['data']['discount_type'], $data['data']['discount_amount'], $data['data']['apply_to'] == 'regular' ?  $data['data']['regular_price'] : $data['data']['sale_price'] );
					$response['success'] = true;
				}
				
			}
		}
		$response['data'] = $discounted_price;
		return rest_ensure_response( $response );
	}


	/**
	 * Get all Products.
	 *
	 * @param string $request Data.
	 * 
	 * @return array|WP_Error
	 */
	public function get_wc_products($request)
	{
		$data = [];
		$default = [
			'value' => null,
			'label' => 'Select a Product'
		];
		$data[] = $default;
		if (in_array('woocommerce/woocommerce.php', WPFNL_ACTIVE_PLUGINS)) {
			$all_ids = get_posts([
				'post_type' => 'product',
				'numberposts' => -1,
				'post_status' => 'publish',
				'fields' => 'ids',
			]);
			foreach ($all_ids as $id) {
				$product = wc_get_product($id);
				if( $product ){
					$type = $product->get_type();
					if ($type == 'variable') {
						$variations = $product->get_available_variations();
						foreach ($variations as $variation) {
							$product = wc_get_product($variation['variation_id']);
							$value = $variation['variation_id'];
							$label = $product->get_name();
							$result = [
								'value' => $value,
								'label' => $label,
							];
							$data[] = $result;
						}
					} else {
						$value = $id;
						$label = $product->get_name();
						$result = [
							'value' => $value,
							'label' => $label,
						];
						$data[] = $result;
					}
				}
				
			}
		}
		return $data;
	}


	/**
	 * Prepare a single setting object for response.
	 *
	 * @since 3.0.0
	 */
	public function get_products($request) {
		
		$step_id 	=  $request['step_id'];
		$funnel_id  = Wpfnl_functions::get_funnel_id_from_step($step_id);
		$type 	= get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
		$response = [];
		$type = !$type ? 'wc' : $type; 
		$class_object = Wpfnl_Type_Factory::build( $type );
		if( $class_object ){
			$response = $class_object->get_items( $step_id );
			
		}
		
		$response['priceConfig'] = Wpfnl_functions::get_wc_price_config();
		return $this->prepare_item_for_response( $response, $request );
	}



	public function fetch_products() {
		$products        = [];
		$data_store = \WC_Data_Store::load('product');
		$ids        = $data_store->search_products($term, '', false, false, 10);

		$product_objects = array_filter(array_map('wc_get_product', $ids), 'wc_products_array_filter_readable');
		if( $product_objects ){
			foreach ($product_objects as $product_object) {
				$formatted_name = $product_object->get_formatted_name();
				if($product_object->get_type() == 'variable') {
					$variations = $product_object->get_available_variations();
					if( !empty($variations) ){
						foreach ($variations as $variation) {
							$products[$variation['variation_id']] = [
								'name' => $formatted_name .'('. $variation['sku'].')',
								'price' => $variation['display_price'],
								'sale_price' => $variation['display_regular_price'],
							];
						}
					}
				}
				else {
					$products[$product_object->get_id()] = [
						'name' => rawurldecode($formatted_name),
						'price' => $product_object->get_regular_price(),
						'sale_price' => $product_object->get_sale_price(),
					];
				}
			}
		}
		
	}


	/**
	 * Prepare a single setting object for response.
	 *
	 * @param object          $item Setting object.
	 * @param WP_REST_Request $request Request object.
	 * 
	 * @return WP_REST_Response $response Response data.
	 * @since  3.0.0
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data     = $this->add_additional_fields_to_object( $item, $request );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Delete product
	 *
	 * @param WP_REST_Request $payload Request.
	 *
	 * @return Array
	 *
	 * @since 2.0.0
	 */
	public function delete_product( $payload ) {
		if ( empty( $payload['step_id'] ) ){
			return $this->prepare_wp_error_response(
				'rest_invalid_request',
				'Step ID is missing',
				array(
					'status'  => 400,
				)
			);
		}

		$step_id  = $payload['step_id'];
		$type     = isset ( $payload['type'] ) ? $payload['type'] : 'checkout';
		$index    = isset ( $payload['index'] ) ? $payload['index'] : 0;
		
		$meta_key = self::get_meta_key_by_step_type( $type );
		$products = get_post_meta( $step_id, $meta_key, true );
		$step     = Wpfnl::get_instance()->step_store;

		$remaining_products = array();

		if ( $products ) {
			unset( $products[ $index ] );
			$products = array_values( $products );
			if ( $products ) {
				$remaining_products = self::get_remaining_products( $products );
			}
			$step->update_meta( $step_id, $meta_key, $products );
		}
		$response = array(
			'success'  => true,
			'products' => $remaining_products,
		);

		return rest_ensure_response($response);
	}

	/**
	 * Get the meta key based on the provided type.
	 *
	 * @param string $type The type of the meta key.
	 *
	 * @return string The corresponding meta key.
	 * @since 2.7.10
	 */
	public function get_meta_key_by_step_type( $type ){
		$meta_key = '_wpfnl_checkout_products';

		switch ($type) {
			case 'upsell':
				$meta_key = '_wpfnl_upsell_products';
				break;
			case 'downsell':
				$meta_key = '_wpfnl_downsell_products';
				break;
			default:
				$meta_key = '_wpfnl_checkout_products';
				break;
		}

		return $meta_key;
	}

	/**
	 * Process products and return an array of formatted product data.
	 *
	 * @param array $products The array of products.
	 *
	 * @return array The formatted product data.
	 * @since 2.7.10
	 */
	public function get_remaining_products($products) {
		$remaining_products = [];

		foreach ($products as $value) {
			$product = wc_get_product($value['id']);
			
			if (!$product) {
				continue;
			}
			
			$title = $product->get_type() === 'variation' ? Wpfnl_functions::get_formated_product_name($product) : $product->get_name();
			$image = wp_get_attachment_image_src($product->get_image_id(), 'single-post-thumbnail');
			$price = $product->get_price();
			$sale_price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_price();
			$regular_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();

			// Handle variation products if WooCommerce Subscriptions plugin is active.
			if (is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
				if ('subscription_variation' === $product->get_type() || 'subscription' === $product->get_type()) {
					$sign_up_fee = \WC_Subscriptions_Product::get_sign_up_fee($product);
					$price += $sign_up_fee;
					$sale_price += $sign_up_fee;
					$regular_price += $sign_up_fee;
				}
			}

			$remaining_products[] = array(
				'id' => isset ( $value['id'] ) ? $value['id'] : 0,
				'title' => $title,
				'price' => wc_price($price),
				'numeric_price' => $price,
				'currency' => get_woocommerce_currency_symbol(),
				'sale_price' => $sale_price,
				'regular_price' => $regular_price,
				'quantity' => isset ( $value['quantity'] ) ? $value['quantity'] : 1,
				'image' => $image ? $image[0] : '',
				'product_edit_link' => in_array($product->get_type(), array('variation', 'subscription_variation')) ? get_edit_post_link($product->get_parent_id()) : get_edit_post_link($product->get_id()), //phpcs:ignore
				'product_view_link' => in_array($product->get_type(), array('variation', 'subscription_variation')) ? get_permalink($product->get_parent_id()) : get_permalink($product->get_id()), //phpcs:ignore
				'is_trash' => 'trash' === $product->get_status() ? 'yes' : 'no',
				'is_deleted' => 'no',
			);
			
		}

		return $remaining_products;
	}


}
