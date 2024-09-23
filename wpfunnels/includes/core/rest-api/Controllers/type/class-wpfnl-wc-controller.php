<?php
/**
 * Woocommerce controller
 *
 * @package WPFunnels\Controller
 */
namespace WPFunnels\Controller;
use WPFunnels\Controller\Wpfnl_Controller_Type;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Controller_Wc extends Wpfnl_Controller_Type
{

    /**
     * Get courses
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
            $quantityLimit 		=  get_post_meta($step_id, '_wpfnl_quantity_limit', true);

            $auto_coupon 		=  get_post_meta($step_id, '_wpfnl_checkout_auto_coupon', true);
            $discount 			=  '';

            if ($use_of_coupon == 'yes') {
                $use_of_coupon = true;
            }
            else {
                $use_of_coupon = false;
            }

            if( $isMultipleProduct == 'yes' ) {
                $isMultipleProduct = true;
            }
            else {
                $isMultipleProduct = false;
            }

            if( $isQuantity == 'yes' ) {

                $isQuantity = true;
            }
            else {
                $isQuantity = false;
            }
            if( Wpfnl_functions::is_wc_active() ) {
                if( $products ) {
                    foreach( $products as $value ) {
                        $product = wc_get_product( $value[ 'id' ] );
                        if( $product ){
                            $title   = $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();;
                            $image         = wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' ) ? wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' ) : wp_get_attachment_image_src( $product->get_image_id(), 'single-post-thumbnail' );
                            $price         = $product->get_price();
                            $sale_price    = $product->get_sale_price() ? $product->get_sale_price() : $product->get_price();
                            $regular_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
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
                            );
                        }

                    }
                }
                else {
                    $response[ 'products' ] = array();
                }
            }

            $response[ 'coupon' ]            = $use_of_coupon;
            $response[ 'autoCoupon' ]        = $auto_coupon;
            $response[ 'isMultipleProduct' ] = $isMultipleProduct;
            $response[ 'isQuantity' ]        = $isQuantity;
            $response[ 'quantityLimit' ]     = $quantityLimit;
            $response[ 'discount' ]          = $discount;
            $response[ 'success' ]           = true;
        }
        return $response;
    }


    /**
     * Get order bump settings for woocommerce
     *
     * @param Array all_settings
     *
     * @return Array|Bool
     */
    public function get_ob_settings( $all_settings ){

        if( $all_settings && Wpfnl_functions::is_wc_active() ){
            if( is_array($all_settings) ){
                foreach( $all_settings as $key=>$settings ){
                    if( !isset($all_settings[$key]['discountOption']) && 'original' !== $all_settings[$key]['discountOption'] ) {
                        $product	= wc_get_product( $all_settings[$key]['product'] );
                        $price = '';
                        if( $product ){
                            if( $product->is_on_sale() ) {
                                $price = wc_format_sale_price( $product->get_regular_price() * $all_settings[$key]['quantity'], $product->get_sale_price() ? $product->get_sale_price() * $all_settings[$key]['quantity'] :  $product->get_regular_price() * $all_settings[$key]['quantity']);
                                if( 'subscription' == $product->get_type() || 'simple-subscription' == $product->get_type() || 'variable-subscription' ==  $product->get_type() ){
                                    $price = $product->get_regular_price();
                                    if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                                        $signUpFee 	= \WC_Subscriptions_Product::get_sign_up_fee( $product );
                                        $price 		=	$price + $signUpFee;
                                    }
                                    $price = wc_price( $price * $all_settings[$key]['quantity'] );
                                }
                            }
                        }
                        $all_settings[$key]['htmlPrice']	= $price ? $price : $all_settings[$key]['htmlPrice'];
                    }

                    if( isset( $all_settings[$key]['productDescriptionText']) ){
                        $all_settings[$key]['productDescriptionText'] = $all_settings[$key]['productDescriptionText'];
                    }
                    if( !isset($all_settings[$key]['productSearchName']) ){
                        $all_settings[$key]['productSearchName'] = isset($all_settings[$key]['productName']) ? $all_settings[$key]['productName'] : '';
                    }
                }
            }
            return $all_settings;
        }
        return [];
    }


    /**
     * Update order bump settings for woocommerce
     *
     * @param $all_settings
     *
     * @return Array
     */
    public function update_ob_settings( $all_settings ){
        if( $all_settings && Wpfnl_functions::is_wc_active() ){
            foreach( $all_settings as $key=>$settings ){
                if (isset($settings['product']) && $settings['product'] != "") {
                    $custom_price = '';
                    $quantity = $settings['quantity'];
                    $_product = wc_get_product($settings['product']);
                    if( $_product ){
                        $regular_price = $_product->get_regular_price();
                        $regular_price = floatval($regular_price);
		                $regular_price = $regular_price * $quantity;
                        $sale_price = $_product->get_sale_price();
                        $sale_price = floatval($sale_price);
		                $sale_price = $sale_price * $quantity;
                        if ( $all_settings[$key]['discountapply'] == 'sale' ) {
                            if ($sale_price != "") {
                                $calculable_price = $sale_price;
                            } else {
                                $calculable_price = $regular_price;
                            }
                        } else {
                            if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                                $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $_product );
                                $regular_price = $signUpFee + $regular_price;
                            }

                            $calculable_price = $regular_price;
                        }
                        if ($all_settings[$key]['discountOption'] == 'discount-percentage' || $all_settings[$key]['discountOption'] == 'discount-price') {
                            $discountPrice = $this->calculate_custom_price($all_settings[$key]['discountOption'], $all_settings[$key]['discountValue'], $calculable_price);
                            $all_settings[$key]['discountPrice'] = $discountPrice;
                        }
                    }

                }
            }
            return $all_settings;
        }
        return [];
    }


    /**
	 * Calculate Discount Price.
	 *
	 * @param $discount_type
	 * @param $discount_value
	 * @param $product_price
	 * @param string $apply_to
     *
	 * @return string
	 */
	public function calculate_custom_price( $discount_type, $discount_value, $product_price, $apply_to = 'regular' )
	{
		$custom_price = $product_price;
		if (!empty($discount_type)) {
			if ('discount-percentage' === $discount_type) {
				if ($discount_value > 0) {
					$custom_price = $product_price - ( ( $product_price * $discount_value ) / 100);
				}
			} elseif ('discount-price' === $discount_type) {
				if ($discount_value < $product_price) {
					$custom_price = $product_price - $discount_value;
				}else{
                    $custom_price = $product_price;
                }
			}
		}

		return number_format((float)$custom_price, 2);
	}

}
