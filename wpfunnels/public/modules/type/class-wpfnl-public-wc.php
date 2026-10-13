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
    public function wpfnl_order_bump_trigger( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id , $checker, $variation_data = array() ){

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
            $original_price         = $product_price; // Store original price before discount

            if( 'discount-percentage' === $discount_type || 'discount-price' === $discount_type ) {
                $discount_apply_to 	= isset($order_bump_settings['discountapply']) ? $order_bump_settings['discountapply'] : 'regular';
                $discount_value 	= isset($order_bump_settings['discountValue']) ? $order_bump_settings['discountValue'] : 0;
                $product_price		= $this->get_product_price( $_product, $discount_apply_to );
                $original_price     = $product_price; // Store price before applying order bump discount
				if( 'discount-price' === $discount_type && 1 < $quantity ){
					$discount_value = $this->get_percentage( $product_price, $discount_value, $quantity );
					$discount_type  = 'discount-percentage';
				}

                $product_price = $this->calculate_custom_price( $discount_type, $discount_value, $product_price );
                // Now $original_price has the price before order bump discount, and $product_price has the discounted price
            }

            // Get custom product title from order bump settings
            // For auto-match mode, use actual product name instead of placeholder
            $is_auto_match = isset($order_bump_settings['offerSelectionMode']) && $order_bump_settings['offerSelectionMode'] === 'auto-match';
            if( $is_auto_match ) {
                $custom_product_title = $_product->get_name();
            } else {
                $custom_product_title = isset($order_bump_settings['productName']) ? $order_bump_settings['productName'] : '';
            }
            
            // Clean and store prices for cart and display
            $discounted_price = preg_replace('/[^\d.]/', '', $product_price );
            $original_price_clean = preg_replace('/[^\d.]/', '', $original_price );
            
            $ob_cart_item_data = [
                'custom_price' 		=> $discounted_price,
                'wpfnl_order_bump' 	=> true,
                'wpfnl_ob_title' 	=> $custom_product_title,
                'wpfnl_ob_quantity' => $quantity,
                'wpfnl_ob_original_price' => $original_price_clean, // Store original price for display
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
                $data = $this->wpfnl_order_bump_accept( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $replaceable_ob, $ob_cart_item_data, $should_replace_first_product, $replace_settings, $order_bump_settings, $variation_data );
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
     * Add the order bump product to the cart.
     *
     * A variation has to be added through its parent product together with its
     * attributes, a simple product is added as is.
     *
     * @param int   $product_id        Product or variation id to add.
     * @param int   $quantity          Quantity to add.
     * @param array $ob_cart_item_data Order bump cart item data.
     * @param array $variation_data    Resolved variation attributes (attribute_* => value).
     *
     * @return string|false Cart item key, or false when WooCommerce refused the item.
     */
    private function add_order_bump_to_cart( $product_id, $quantity, $ob_cart_item_data, $variation_data = array() ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return false;
        }

        if ( $product->is_type( 'variation' ) ) {
            $attributes = ! empty( $variation_data ) ? $variation_data : $product->get_variation_attributes();
            return \WC()->cart->add_to_cart( $product->get_parent_id(), $quantity, $product_id, $attributes, $ob_cart_item_data );
        }

        return \WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $ob_cart_item_data );
    }


    /**
     * Remove the cart lines this order bump added earlier.
     *
     * @param int $configured_product_id The product or variation configured on the bump.
     *
     * @return bool True when at least one line was removed.
     */
    private function remove_existing_order_bump_lines( $configured_product_id ) {
        $configured_product_id = absint( $configured_product_id );
        $removed               = false;

        foreach ( \WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( empty( $cart_item['wpfnl_order_bump'] ) ) {
                continue;
            }

            $line_variation_id = ! empty( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0;
            if ( absint( $cart_item['product_id'] ) === $configured_product_id || ( $line_variation_id && $line_variation_id === $configured_product_id ) ) {
                \WC()->cart->remove_cart_item( $cart_item_key );
                $removed = true;
            }
        }

        return $removed;
    }


    /**
     * Put the cart back the way it was and report that the bump was not added.
     *
     * @param array $cart_backup Cart contents captured before the bump was touched.
     *
     * @return array
     */
    private function rollback_order_bump_accept( $cart_backup ) {
        \WC()->cart->set_cart_contents( $cart_backup );
        \WC()->cart->calculate_totals();

        return array(
            'status'  => 'fail',
            'message' => __( 'This product could not be added to your order. Please choose another option.', 'wpfnl' ),
        );
    }


    /**
     * When order bump accept for woocommerce
     *
     * @param $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $replaceable_ob, $ob_cart_item_data, $should_replace_first_product, $replace_settings, $order_bump_settings
     *
     * @return Array
     */
    private function wpfnl_order_bump_accept( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $replaceable_ob, $ob_cart_item_data, $should_replace_first_product, $replace_settings, $order_bump_settings = array(), $variation_data = array() ){

        if( Wpfnl_functions::is_wc_active() ){

			// Keep a copy of the cart so a failed add never costs the customer what was already in it.
			$cart_backup = \WC()->cart->get_cart_contents();

			// Picking another variation of a bump that is already in the cart swaps the line instead of stacking a second one.
			$removed_existing = $this->remove_existing_order_bump_lines( isset( $order_bump_settings['product'] ) ? $order_bump_settings['product'] : $product_id );

            // if order bump is checked we will empty the cart in the first place. The we have to check if isReplace is yes or no.
            // if replace first product is enabled, then we will only add the order bump product
            // else we will add all the checkout product along with the order bump product
            if ( $should_replace_first_product == 'yes' ) {

                $is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true);

				if(( isset($replace_settings['isAllReplace']) && 'yes' === $replace_settings['isAllReplace'] ) || 'yes' === $is_gbf ){
					// When isAllReplace is 'yes', replace ALL products (main products + other order bumps)
					// Empty the cart completely
					\WC()->cart->empty_cart();

					// Add only the new order bump product
					$added = $this->add_order_bump_to_cart( $product_id, $quantity, $ob_cart_item_data, $variation_data );
					if ( ! $added ) {
						return $this->rollback_order_bump_accept( $cart_backup );
					}
				}else{
					$added = false;
					if( isset($replace_settings['selectedProducts']) ){
						foreach ( \WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
							// Skip if this cart item is an order bump - we only want to replace main products
							if ( isset($cart_item['wpfnl_order_bump']) && $cart_item['wpfnl_order_bump'] ) {
								continue;
							}

							$car_product_id =  isset($cart_item['variation_id']) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
							$key = array_search( $car_product_id, array_column($replace_settings['selectedProducts'], 'id'));
							if( $key !== false ){
								$added = $this->add_order_bump_to_cart( $product_id, $quantity, $ob_cart_item_data, $variation_data );
								if ( ! $added ) {
									return $this->rollback_order_bump_accept( $cart_backup );
								}
								\WC()->cart->remove_cart_item($cart_item_key);
								break;
							}
						}
					}

					// The main product was already replaced by this bump earlier, so there is nothing left to swap. Put the bump back.
					if ( ! $added && $removed_existing ) {
						if ( ! $this->add_order_bump_to_cart( $product_id, $quantity, $ob_cart_item_data, $variation_data ) ) {
							return $this->rollback_order_bump_accept( $cart_backup );
						}
					}
				}
            } else {
				if ( ! $this->add_order_bump_to_cart( $product_id, $quantity, $ob_cart_item_data, $variation_data ) ) {
					return $this->rollback_order_bump_accept( $cart_backup );
				}
            }

			$cookie_name = 'wpfnl_order_bump';
			$cookie      = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();

			$cookie['order_bump_accepted'] = 'yes';

			// Check if 'order_bump_product' exists and is an array, if not initialize it as an array
			if (!isset($cookie['order_bump_product']) || !is_array($cookie['order_bump_product'])) {
				$cookie['order_bump_product'] = array();
			}

			// Append the product_id to the 'order_bump_product' array if it doesn't already exist
			if (!in_array($product_id, $cookie['order_bump_product'])) {
				$cookie['order_bump_product'][] = $product_id;
			}

			ob_start();
            setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
            ob_end_flush();

            WC()->session->set('order_bump_accepted', 'yes');

			// Generate Product Object and Name.
			$product      = wc_get_product( $product_id );
			// Use custom title if available, otherwise use product name
			$product_name = !empty($ob_cart_item_data['wpfnl_ob_title']) ? $ob_cart_item_data['wpfnl_ob_title'] : ($product ? $product->get_name() : '');

			// Calculate display price considering quantity and discount
			$unit_price = floatval($ob_cart_item_data['custom_price']);
			$original_unit_price = floatval($ob_cart_item_data['wpfnl_ob_original_price']);
			$quantity_int = intval($quantity);
			
			$total_price = $unit_price * $quantity_int;
			$original_total_price = $original_unit_price * $quantity_int;
			
			// Format price display (show as sale price if there's a discount)
			// Round to 2 decimal places for accurate comparison
			$total_price = round($total_price, 2);
			$original_total_price = round($original_total_price, 2);
			
			if ($original_total_price > $total_price) {
				$formatted_price = wc_format_sale_price($original_total_price, $total_price);
			} else {
				$formatted_price = wc_price($total_price);
			}

            // Get custom description from order bump settings, fallback to WooCommerce product description
            // For auto-match mode, always use actual product description
            $product_description = '';
            $is_auto_match = isset($order_bump_settings['offerSelectionMode']) && $order_bump_settings['offerSelectionMode'] === 'auto-match';
            
            if( $is_auto_match && $product ) {
                // For auto-match, use actual product description
                $product_description = $product->get_description() ?: $product->get_short_description();
            } else {
                // For manual mode, use custom description from settings (keep empty if not set)
                $product_description = isset($order_bump_settings['productDescriptionText']) ? $order_bump_settings['productDescriptionText'] : '';
            }

            $data = [
                'status' 		      => 'success',
                'message' 		      => __('Successfully added', 'wpfnl'),
                'order_bump'	      => true,
				'product_name'	      => $product_name,
				'product_description' => $product_description,
				'product_price'       => $formatted_price,
				'product_image'       => $product ? wp_get_attachment_url( $product->get_image_id() ) : '',
				'isAllReplace'        => ($should_replace_first_product == 'yes' && isset($replace_settings['isAllReplace']) && 'yes' === $replace_settings['isAllReplace']) ? true : false,
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
            $checkout_meta = new Wpfnl_Default_Meta();
            $main_products = $checkout_meta->get_main_products( $funnel_id, $step_id );

            $cookie_name = 'wpfnl_order_bump';
            $cookie      = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();

            $cookie['order_bump_accepted']   = 'no';
            if( !isset($cookie['order_bump_accepted']) ){
                $cookie['order_bump_accepted'] = 'no';
            }

			// The cookie stores the id that was added to the cart, which is the variation id for a variable bump
			// while the reject request only names the configured product. Clear both.
			$ids_to_clear = array( $product_id );
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( ! empty( $cart_item['wpfnl_order_bump'] ) && ! empty( $cart_item['variation_id'] ) && $cart_item['product_id'] == $product_id ) {
					$ids_to_clear[] = $cart_item['variation_id'];
				}
			}

			// Check if 'order_bump_product' exists and is an array
			if (isset($cookie['order_bump_product']) && is_array($cookie['order_bump_product'])) {
				foreach ( $ids_to_clear as $id_to_clear ) {
					// Find the index of the product_id in the array
					$index = array_search($id_to_clear, $cookie['order_bump_product']);
					if ($index !== false) {
						// Remove the product_id from the array
						unset($cookie['order_bump_product'][$index]);
					}
				}
				// Re-index the array to remove gaps
				$cookie['order_bump_product'] = array_values($cookie['order_bump_product']);
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
                // Store existing order bumps before clearing (except the one being removed)
                $existing_order_bumps = array();
                foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                    if ( isset($cart_item['wpfnl_order_bump']) && $cart_item['wpfnl_order_bump'] ) {
                        // Check if this is the order bump being removed
                        $cart_product_id = isset($cart_item['variation_id']) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
                        if( $cart_product_id != $product_id ) {
                            // This is a different order bump - keep it
                            $existing_order_bumps[$cart_item_key] = $cart_item;
                        }
                    }
                }
                
                \WC()->cart->empty_cart();
                $is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true);
                if( 'yes' === $is_gbf ){
                    if(isset( $_COOKIE['wpfunnels_global_funnel_product'] ) ){
						$main_products  = json_decode( wp_unslash( $_COOKIE['wpfunnels_global_funnel_product'] ), true );
						$main_products  = json_decode( $main_products );
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
                
                // Re-add existing order bumps that were preserved
                foreach ( $existing_order_bumps as $cart_item ) {
                    $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
                    $variation = isset($cart_item['variation']) ? $cart_item['variation'] : array();
                    \WC()->cart->add_to_cart( $cart_item['product_id'], $cart_item['quantity'], $variation_id, $variation, $cart_item );
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
						$variation_object = wc_get_product( $variation_id );
						$cart_item_data = [
							'custom_price' 	=> $variation_object->is_on_sale() ? $variation_object->get_sale_price() : $variation_object->get_regular_price()
						];
					}else{
						$cart_item_data = [
							'custom_price' 	=> get_post_meta($variation_id, '_regular_price', true) ? get_post_meta($variation_id, '_regular_price', true) : get_post_meta($variation_id, '_price', true)
						];
					}
					
					$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $formatted_variation, $cart_item_data);
					
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
		if ($product->get_type() == 'variable' || $product->get_type() == 'variable-subscription') {
			$price = $this->get_min_variation_base_regular_price( $product );
			$price = $price ? $price : $product->get_price( 'edit' );
		} else {
			// Read in 'edit' context (store base currency): the price is re-applied with set_price()
			// and converted by multi-currency switchers on read, so a 'view' value converts twice.
			$price = $product->get_regular_price( 'edit' ) ? $product->get_regular_price( 'edit' ) : $product->get_price( 'edit' );
		}

		$final_price = $discount_apply === 'sale' && $product->get_sale_price( 'edit' ) ? $product->get_sale_price( 'edit' ) : $price;

		return apply_filters('wpfunnels/modify_order_bump_price_on_main_order', $final_price);
	}


	/**
	 * Get the lowest regular price of a variable product's variations in store base currency.
	 *
	 * get_variation_regular_price() goes through the `woocommerce_variation_prices` filter,
	 * which multi-currency switchers use to convert prices, so read each variation instead.
	 *
	 * @param \WC_Product $product Variable product.
	 *
	 * @return string Empty string when no variation has a regular price.
	 * @since 3.13.4
	 */
	private function get_min_variation_base_regular_price( \WC_Product $product ) {
		$prices = [];
		foreach ( $product->get_visible_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( $variation && '' !== $variation->get_regular_price( 'edit' ) ) {
				$prices[] = (float) $variation->get_regular_price( 'edit' );
			}
		}
		return $prices ? wc_format_decimal( min( $prices ) ) : '';
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

		// Check if this is an order bump product - if so, don't modify the quantity display
		if( isset($cart_item['wpfnl_order_bump']) && $cart_item['wpfnl_order_bump'] ){
			return $quantity;
		}

		// When Pro is active, its own `woocommerce_checkout_cart_item_quantity` filter
		// (registered unconditionally) already builds the current quantity selector markup.
		// Rebuilding it here with this legacy markup would overwrite/corrupt that output
		// whenever this filter gets dynamically added during the order bump accept flow.
		if ( Wpfnl_functions::is_wpfnl_pro_activated() ) {
			return $quantity;
		}

		$isQuantity = get_post_meta($step_id, '_wpfnl_quantity_support',true);
		$order_bump_product = get_post_meta($step_id,'order-bump-settings',true);
		$quantityLimit 	=  get_post_meta($step_id, '_wpfnl_quantity_limit', true);
		if($isQuantity === 'yes'){
			// Check if this is a legacy single order bump structure
			if(isset($order_bump_product['product']) && isset($order_bump_product['isEnabled'])){

				if( ($order_bump_product['product'] == $cart_item["product_id"]) && $order_bump_product['isEnabled'] == 'yes' ){
					return $quantity;
				}
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
				$quantity = "× <input type='number' min='1' step='1' max='".$set_quantity."'  value='".$quantity."' class='wpfnl-quantity-setect' data-product-id='".$product_id."' data-variation='".$variations."' data-variation-id='".$variation_id."' data-quantity-limit='".$set_quantity."' data-set-quantity='yes'/>";
			}else{
				$quantity = "× <input type='number' min='1' step='1' value='".$quantity."' class='wpfnl-quantity-setect' data-product-id='".$product_id."' data-variation='".$variations."' data-variation-id='".$variation_id."' data-set-quantity='no' />";
			}
		}
		return $quantity;

	}

}
