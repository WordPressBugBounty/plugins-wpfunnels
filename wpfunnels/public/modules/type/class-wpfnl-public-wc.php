<?php
/**
 * Woocommerce dependent public class
 *
 * @package WPFunnels\FunnelType
 */
namespace WPFunnels\FunnelType;

use WPFunnels\Wpfnl_functions;
use WPFunnels\FunnelType\Wpfnl_Public_Funnel_Type;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnels\Meta\Wpfnl_Default_Meta;
use WPFunnels\Discount\WpfnlDiscount;

class Wpfnl_Public_Wc extends Wpfnl_Public_Funnel_Type
{

    /**
     * When order bump accept/reject for woocommerce
     *
     * @return Array
     */
    public function wpfnl_order_bump_trigger( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id , $checker ){

        //=== Custom price configured when you add sale price or apply coupon or add discount value ===//
        $ob_settings 			= get_post_meta($step_id, 'order-bump-settings', true);
		$ob_settings		 	= apply_filters( 'wpfunnels/order_bump_settings_on_frontend', $ob_settings, $funnel_id, $step_id );
        $replaceable_ob 		= $this->get_replaceable_ob_products( $ob_settings );
        $order_bump_settings 	= $ob_settings[$key];
        $_product 				= wc_get_product($product_id);
        $data = array();
        if( $_product ){
            $discount_type			= $order_bump_settings['discountOption'];
            $discount_apply_to  	= $_product->is_on_sale() ? 'sale' :  'regular' ;
            $product_price 			= $this->get_product_price( $_product, $discount_apply_to );

            if( 'discount-percentage' === $discount_type || 'discount-price' === $discount_type ) {
                $discount_apply_to 	= isset($order_bump_settings['discountapply']) ? $order_bump_settings['discountapply'] : 'regular';
                $discount_value 	= isset($order_bump_settings['discountValue']) ? $order_bump_settings['discountValue'] : 0;
                $product_price		= $this->get_product_price( $_product, $discount_apply_to );
				
				if( 'discount-price' === $discount_type && 1 < $quantity ){
					$discount_value = $this->get_percentage( $product_price, $discount_value, $quantity );
					$discount_type  = 'discount-percentage';
				}

                $product_price 		= $this->calculate_custom_price( $discount_type, $discount_value, $product_price );
            }

            $ob_cart_item_data = [
                'custom_price' 		=> preg_replace('/[^\d.]/', '', $product_price ),
                'wpfnl_order_bump' 	=> true,
            ];


			/**
			 * Apply filters to modify the orderbump product price data.
			 *
			 * @param float $custom_price The custom price of the product.
			 * @return float The modified custom price.
			 *
			 * @since 3.1.2
			 */
			$ob_cart_item_data['custom_price'] = apply_filters( 'wpfunnels/modify_orderbump_product_price_data', $ob_cart_item_data['custom_price'] );

            $should_replace_first_product = isset($order_bump_settings['isReplace']) ? $order_bump_settings['isReplace'] : 'no';
            $replace_settings = isset($order_bump_settings['replaceSettings']) ? $order_bump_settings['replaceSettings'] : [
				'isAllReplace' => 'yes',
				'selectedProducts' => [],
			];
            add_filter( 'woocommerce_checkout_cart_item_quantity', array($this, 'wpfnl_checkout_cart_item_quantity'), 10, 3 );
            if ($checker == "true") {
                $data = $this->wpfnl_order_bump_accept( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $replaceable_ob, $ob_cart_item_data, $should_replace_first_product, $replace_settings );
            }else{
                $data = $this->wpfnl_order_bump_reject( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $replaceable_ob, $ob_cart_item_data, $should_replace_first_product, $replace_settings );
            }
        }

        return $data;
    }


	/**
	 * Get percentage of product price that should be replaced by product price when discount type is discount-price option.
	 * 
	 * @param float $product_price The price of the product.
	 * @param float $discount_value The discount value.
	 * @param int $quantity The quantity of the product.
	 * 
	 * @return float The percentage of product price that should be replaced.
	 * 
	 * @since 3.4.15
	 */
	public function get_percentage( $product_price, $discount_value, $quantity ) {
		$percentage = 0;
		$product_price = floatval( $product_price );
		$quantity = intval( $quantity );
		$discount_value = floatval( $discount_value );

		if( $discount_value > 0 && ( $product_price * $quantity ) > 0 ){
			$percentage = ( $discount_value * 100 ) / ( $product_price * $quantity );
		}
		return $percentage;
	}


    /**
     * When order bump accept for woocommerce
     *
     * @param $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $replaceable_ob, $ob_cart_item_data, $should_replace_first_product
     *
     * @return Array
     */
    private function wpfnl_order_bump_accept( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $replaceable_ob, $ob_cart_item_data, $should_replace_first_product, $replace_settings ){

        if( Wpfnl_functions::is_wc_active() ){
            $cookie_name        = 'wpfnl_order_bump';
            $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
            $cookie['order_bump_accepted']   = 'yes';

			

			ob_start();
            setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
            ob_end_flush();

            WC()->session->set('order_bump_accepted', 'yes');
            // if order bump is checked we will empty the cart in the first place. The we have to check if isReplace is yes or no.
            // if replace first product is enabled, then we will only add the order bump product
            // else we will add all the checkout product along with the order bump product
            if ( $should_replace_first_product == 'yes' ) {
				
                $checkout_products = get_post_meta( $step_id, '_wpfnl_checkout_products', true);
                $is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true);
               
				if(( isset($replace_settings['isAllReplace']) && 'yes' === $replace_settings['isAllReplace'] ) || 'yes' === $is_gbf ){
					\WC()->cart->empty_cart();
					if( 'yes' === $is_gbf ){
						if(isset( $_COOKIE['wpfunnels_global_funnel_specific_product'] ) ){
							$checkout_products  = json_decode( wp_unslash( $_COOKIE['wpfunnels_global_funnel_specific_product'] ), true );
						}
					}
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$key = array_search( isset($cart_item['variation_id']) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'], array_column($checkout_products, 'id'));
						if( $key !== false ){
							\WC()->cart->remove_cart_item($cart_item_key);
						}
	
						$index = array_search( $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'], array_column($replaceable_ob, 'id'));
						if( $index !== false ){
							\WC()->cart->remove_cart_item($cart_item_key);
						}
					}
					
					\WC()->cart->add_to_cart($product_id, $quantity, 0, [], $ob_cart_item_data);
				}else{
					if( isset($replace_settings['selectedProducts']) ){
						foreach ( \WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
							$car_product_id =  isset($cart_item['variation_id']) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
							$key = array_search( $car_product_id, array_column($replace_settings['selectedProducts'], 'id'));
							if( $key !== false ){
								\WC()->cart->remove_cart_item($cart_item_key);
								\WC()->cart->add_to_cart( $product_id, $quantity, 0, [], $ob_cart_item_data);
								break;
							}
						}
					}
				}
            } else {
                \WC()->cart->add_to_cart( $product_id, $quantity, 0, [], $ob_cart_item_data);
            }

            $data = [
                'status' 		=> 'success',
                'message' 		=> __('Successfully added', 'wpfnl'),
                'order_bump'	=> true
            ];
            do_action( 'wpfunnels/order_bump_accepted', $step_id, $product_id );

            return $data;
        }
        return [];
    }


    /**
     * When order bump reject for woocommerce
     *
     * @param $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $replaceable_ob, $ob_cart_item_data, $should_replace_first_product
     *
     * @return Array
     */
    private function wpfnl_order_bump_reject( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $replaceable_ob, $ob_cart_item_data, $should_replace_first_product, $replace_settings ){

        if( Wpfnl_functions::is_wc_active() ){

            // fetch main products
            $checkout_meta 		= new Wpfnl_Default_Meta();
            $main_products		= $checkout_meta->get_main_products( $funnel_id, $step_id );

            $cookie_name        = 'wpfnl_order_bump';
            $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
            $cookie['order_bump_accepted']   = 'no';
            if( !isset($cookie['order_bump_accepted']) ){
                $cookie['order_bump_accepted'] = 'no';
            }

			ob_start();
            setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
			ob_end_flush();

            WC()->session->set('order_bump_accepted', 'no');

            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                if ( isset($cart_item['wpfnl_order_bump']) && $cart_item['wpfnl_order_bump'] &&  $cart_item['product_id'] == $product_id) {
                    //remove single product
                    // if( $cart_item['quantity'] == $quantity ){
                        WC()->cart->remove_cart_item($cart_item_key);
                    // }
                }
                else if ( isset($cart_item['wpfnl_order_bump']) && $cart_item['wpfnl_order_bump'] && $cart_item['variation_id'] == $product_id ) {
                    // if( $cart_item['quantity'] == $quantity ){

                        WC()->cart->remove_cart_item($cart_item_key);

                    // }
                }
            }
            if ( $should_replace_first_product == 'yes' ) {
                \WC()->cart->empty_cart();
                $is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true);
                if( 'yes' === $is_gbf ){
                    if(isset( $_COOKIE['wpfunnels_global_funnel_product'] ) ){
						$main_products  = json_decode( wp_unslash( $_COOKIE['wpfunnels_global_funnel_product'] ), true );
						$main_products   = maybe_unserialize( $main_products );
					}
                }
				$discount_instance = new WpfnlDiscount();
				$discount = $discount_instance->get_discount_settings( $step_id );

                foreach ( $main_products as $main_product ) {
                    $custom_price = [];
					$product_id = isset($main_product['variation_id']) ? $main_product['variation_id'] : $main_product['id'];
                    $product = wc_get_product($product_id);
                    if( isset($discount['discountOptions'], $discount['discountapplyto']) ){
						if( !$discount_instance->maybe_time_bound_discount( $step_id ) || ($discount_instance->maybe_time_bound_discount( $step_id ) && $discount_instance->maybe_validate_discount_time( $step_id )) ){
							if( 'original' !==  $discount['discountOptions'] ){
								$regular_price = $product->get_regular_price();
								$sale_price = $product->get_sale_price();
								if( 'sale' === $discount['discountapplyto']  ){
									$custom_price = [
										'custom_price' 	=> $sale_price ? $sale_price : $regular_price,
									];
								}else{
									$custom_price = [
										'custom_price' 	=> $regular_price ? $regular_price : $product->get_the_price(),
									];
								}
							}
						}
                    }

                    if( 'variable' === $product->get_type() ){
                        $variations = $product->get_available_variations();
                        $quantity = $main_product['quantity'];
						$this->add_default_variation($funnel_id, $step_id, $product_id, $product, $variations, $quantity);
                    }else{
						if( 'variation' === $product->get_type() ){
							WC()->cart->add_to_cart( $main_product['id'], $main_product['quantity'], isset($main_product['variation_id']) ? $main_product['variation_id'] : '' , isset($main_product['variations']) ?$main_product['variations'] : [], $custom_price);
						}else{
							WC()->cart->add_to_cart( $main_product['id'], $main_product['quantity'],0, [], $custom_price);
						}
                    }
                }
            }
            $data = [
                'status' => 'success',
                'message' => __('Successfully removed', 'wpfnl'),
            ];
            do_action( 'wpfunnels/order_bump_rejected', $step_id, $product_id );

            return $data;
        }
        return [];
    }


    /**
	 * Add default variation to cart
	 *
	 * @param $product
	 * @param $variations
	 * @param $quantity
	 *
	 * @throws \Exception
	 */
	private function add_default_variation($funnel_id, $checkout_id,$product_id,$product,$variations,$quantity){
		$i = 0;
		$formatted_variation = [];
		$is_default_variation = false;
		foreach ($variations as $variation) {

			if($variation['is_in_stock'] ){

				if($product->get_default_attributes()){
					$attributes = $product->get_attributes();
					$def_attributes = $product->get_default_attributes();
					foreach($attributes as $attribute_key=>$attribute_value){

						$def_attr = $product->get_default_attributes();
						if(isset($def_attr[$attribute_key])){
							$attribute_name = str_replace( 'attribute_', '', $attribute_key );
							$default_value = $product->get_variation_default_attribute($attribute_name);
							$formatted_variation['attribute_'.$attribute_name] = $default_value;
							$is_default_variation = true;
							// $default_attr[] = $default_value;

						}
						else{
							$is_default_variation = true;
							$attribute_name = str_replace( 'attribute_', '', $attribute_key );
							$attr_value = $product->get_attribute( $attribute_name );
							$attr_value = strtolower($attr_value);
							if (strpos($attr_value, '|')) {
								$attr_array = explode("|",$attr_value);
							}else{
								$attr_array = explode(",",$attr_value);
							}
							$formatted_variation['attribute_'.$attribute_name] = $attr_array[0];
							// $default_attr[] = $attr_array[0];
						}

					}
				}else{
					if($i==0){
						$attributes = $product->get_attributes();
						foreach ( $attributes as $key => $value) {
							$attr_value = $product->get_attribute( $key );
							$attr_value = strtolower($attr_value);
							if (strpos($attr_value, '|')) {
								$attr_array = explode("|",$attr_value);
							}else{
								$attr_array = explode(",",$attr_value);
							}
							$is_custom_default_variation = true;
							$formatted_variation['attribute_'.$key] = $attr_array[0];
						}
					}


					// WC()->cart->add_to_cart($variation['variation_id'], $quantity);
				}

				if( $is_default_variation || $is_custom_default_variation){
					$variation_id = (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
						new \WC_Product($product_id),
						$formatted_variation
					);

					if( !$variation_id ){
						$_product = wc_get_product($product_id);
						if( $_product ){
							$variations = $_product->get_available_variations();
							$variations_id = wp_list_pluck( $variations, 'variation_id' );
							$variation_id = $variations_id[0];
							$formatted_variation = wc_get_product_variation_attributes($variation_id);
						}
					}

					$price_type = $this->get_product_price_type( $funnel_id, $checkout_id );

					if( $price_type == 'original' ||  $price_type == 'sale' ){
						$cart_item_data = [
							'custom_price' 	=> get_post_meta($variation_id, '_price', true) ? get_post_meta($variation_id, '_price', true) : get_post_meta($variation_id, '_regular_price', true)
						];
					}else{
						$cart_item_data = [
							'custom_price' 	=> get_post_meta($variation_id, '_regular_price', true) ? get_post_meta($variation_id, '_regular_price', true) : get_post_meta($variation_id, '_price', true)
						];
					}

					WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $formatted_variation, $cart_item_data);
					break; // Stop the main loop
				}
				$i++;
			}
		}
	}


    /**
	 * Get price type
	 */
	public function get_product_price_type( $funnel_id, $step_id ){

		$is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true );
		if( $is_gbf == 'no' || !$is_gbf ){

			$discount_instance = new WpfnlDiscount();
			$discount = $discount_instance->get_discount_settings( $step_id );

			if( is_array($discount) ){
				if( $discount['discountOptions'] !== 'original' ){
					if( $discount['discountapplyto'] == '' || $discount['discountapplyto'] == 'regular' ){
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
		return $discount_apply === 'sale' && $product->get_sale_price() ? $product->get_sale_price() : $price;
	}


    /**
	 * Calculate Discount.
     *
	 * @since 1.1.5
	 */
	private function calculate_custom_price($discount_type, $discount_value, $product_price)
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
		$order_bump_product = get_post_meta($step_id,'order-bump-settings',true);
		$quantityLimit 	=  get_post_meta($step_id, '_wpfnl_quantity_limit', true);
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

}
