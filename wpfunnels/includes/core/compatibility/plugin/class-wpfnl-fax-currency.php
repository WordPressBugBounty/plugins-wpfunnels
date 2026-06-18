<?php

namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Traits\SingletonTrait;

class FoxCurrencySwitcher extends PluginCompatibility{
    use SingletonTrait;

    /**
     * Constructor: Initialize class and hook into price conversion.
     */
    public function init(){
        add_filter('wpfunnels/modify_order_bump_price_on_main_order', array($this,'maybe_convert_product_price'));
        add_filter('wpfunnels/modify_order_bump_product_price', array($this, 'maybe_convert_order_bump_price'));
        add_filter('wpfunnels/checkout_ajax_custom_price', array($this, 'maybe_skip_custom_price_on_review'), 10, 3);
    }

    /**
     * Prevent the funnel checkout from zeroing the order total on update_order_review.
     *
     * On every checkout refresh AJAX, WPFunnels overrides each cart item price with the
     * stored `custom_price` at priority 9999, after WOOCS has already converted the price.
     * For a currency without a fixed price (or an unsynced `_price` meta) the stored
     * `custom_price` can resolve to 0, so forcing `set_price(0)` zeroes the order total —
     * the payment methods disappear and the checkout gets stuck in an endless refresh loop
     * (the public JS re-fires `update_checkout` whenever `cart_total <= 0`).
     *
     * Returning `false` skips the override so the already-converted price the customer saw
     * on the initial page render is preserved. Only kicks in while WOOCS is active.
     *
     * @param float  $custom_price The stored custom price for the cart item.
     * @param array  $cart_item    The cart item.
     * @param object $cart         The cart object.
     *
     * @return float|false The price to apply, or false to skip the override.
     */
    public function maybe_skip_custom_price_on_review($custom_price, $cart_item, $cart){
        if ( $this->maybe_activate() && floatval($custom_price) <= 0 ) {
            return false;
        }

        return $custom_price;
    }

    /**
     * Check if the Currency (WOOCS) is active.
     * 
     * @return bool
     */
    public function maybe_activate(){
        return class_exists('WOOCS');
    }

    /**
     * Convert the price for the order bump using the current currency
     * 
     * @param float $price
     * @return float Converted price
     */
    public function maybe_convert_product_price($price){
        global $WOOCS;

        if ($this->maybe_activate()) {
            if ($WOOCS->is_multiple_allowed) {
                $current = $WOOCS->current_currency;
                if ($current != $WOOCS->default_currency) {
                    $currencies = $WOOCS->get_currencies();
                    $rate = $currencies[$current]['rate'];
                    $price = $price / $rate;
                }
            }
        }

        return $price;
    }

    public function maybe_convert_order_bump_price($price){
        global $WOOCS;

        if ($this->maybe_activate()) {
            if ($WOOCS->is_multiple_allowed) {
                $price = $WOOCS->woocs_exchange_value(floatval($price));
            }
        }

        return $price;
    }
}
