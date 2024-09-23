<?php
/**
 * This class is responsible for all discount calculation
 * 
 * @package WPFunnels\Discount
 */
namespace WPFunnels\Discount;

class WpfnlDiscount{


    /**
	 * Calculate price after discount
	 *
	 * @param $discount_type Discount type can be percentage and flat amount.
	 * @param $discount_value Discount amount.
	 * @param $price Product price where you want to add discount
	 * 
     * @since 2.7.6
	 * @return Number
	 */
	public function calculate_discount( $discount_type, $discount_value, $price ) {
		$custom_price = $price;
		if (!empty($discount_type)) {
            $price          = (float)$price;
            $discount_value = (float)$discount_value;
            if( is_numeric($price) && is_numeric($discount_value) ){
                if ('discount-percentage' === $discount_type) {
                    if ( $discount_value > 0 && $discount_value <= 100) {
                        $custom_price = $price - (($price * $discount_value) / 100);
                        $custom_price = number_format((float)$custom_price,2, '.', '');
                    }
                } elseif ('discount-price' === $discount_type) {
                    if ($discount_value > 0 && $price >= $discount_value ) {
                        $custom_price = $price - $discount_value;
                        $custom_price = number_format($custom_price, 2);
                    }
                }
            }
		}
		return $custom_price;
	}


    /**
	 * Get discount amount
	 *
	 * @param $discount_type Discount type can be percentage and flat amount.
	 * @param $discount_value Discount amount.
	 * @param $product_price Product price where you want to add discount
	 * 
     * @since 2.7.6
	 * @return Mix Number or Boolean 
	 */
	public static function get_discount_amount( $discount_type, $discount_value, $price ) {
        $discount = false;
		if (!empty($discount_type) && 'original' !== $discount_type ) {
            $price          = (float)$price;
            $discount_value = (float)$discount_value;
            if( is_numeric($price) && is_numeric($discount_value) ){
                if ('discount-percentage' === $discount_type) {
                    if ( $discount_value > 0 && $discount_value <= 100) {
                        $discount = (($price * $discount_value) / 100);
                    }
                } elseif ('discount-price' === $discount_type) {
                    if ($discount_value > 0 && $price >= $discount_value ) {
                        $discount = $discount_value;
                    }
                }
                if( $discount ){
                    $discount = number_format($discount, 2);
                }
            }
		}
		return $discount;
	}


    /**
     * Get discount settings by step id from postmeta
     * 
     * @param $step_id Step ID.
     * 
     * @since 2.7.6
     * @return Mix Array or boolean
     */
    public function get_discount_settings( $step_id ){
        $discount = false;
        if( $step_id ){
            $step_type =  get_post_meta( $step_id, '_step_type', true );
            if( 'checkout' ===  $step_type ){
                $discount = get_post_meta( $step_id, '_wpfnl_checkout_discount_main_product', true );
            }elseif( 'upsell' ===  $step_type || 'downsell' === $step_type ){
                $discount = get_post_meta( $step_id, '_wpfnl_'.$step_type.'_discount', true );
            }
        }
        return $discount;
    }


    /**
     * Checks if the discount is time-bound for a specific step.
     *
     * @param int $step_id The ID of the step.
     * @return bool True if the discount is time-bound, false otherwise.
     * @since 3.1.0
     */
    public function maybe_time_bound_discount( $step_id ){
        if( !$step_id ){
            return false;
        }

        $discount = $this->get_time_bound_discount_settings( $step_id );
        if( !$discount || !isset( $discount['isEnabled'] ) || 'yes' !== $discount['isEnabled'] ){
            return false;
        }

        return true;
    }


    /**
     * Retrieves the time-bound discount settings for a specific step.
     *
     * @param int $step_id The ID of the step.
     * @return array The time-bound discount settings for the specified step.
     * 
     * @since 3.1.0
     */
    public function get_time_bound_discount_settings( $step_id ){
        if( !$step_id ){
            return false;
        }
        return get_post_meta( $step_id, '_wpfnl_time_bound_discount_settings', true );
    }


    /**
     * Validates the discount time for a given step ID.
     *
     * @param int $step_id The ID of the step to validate the discount time for.
     * @return bool True if the discount is valid, false otherwise.
     * 
     * @since 3.1.0
     */
    public function maybe_validate_discount_time( $step_id ){
        if( !$step_id ){
            return false;
        }

        $discount = $this->get_time_bound_discount_settings( $step_id );
        if( !$discount || !isset( $discount['isEnabled'] ) || 'yes' !== $discount['isEnabled'] ){
            return false;
        }

        $from_date    = date('Y-m-d', strtotime($discount['fromDate']));
        $to_date      = date('Y-m-d', strtotime($discount['toDate']));
        
        $date_created = new \DateTime();
        $current_date = $date_created->format( "Y-m-d" );

        if( $current_date >= $from_date && $current_date <= $to_date ){
            return true;
        }

        return false;
    }
}