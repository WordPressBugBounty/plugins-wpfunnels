<?php

namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Traits\SingletonTrait;

class FoxCurrencySwitcher extends PluginCompatibility{
    use SingletonTrait;

    /**
     * Constructor: Initialize class and hook into price conversion.
     */
    public function init(){
        add_filter('wpfunnels/modify_main_product_price_data', array($this,'maybe_convert_product_price'));
        add_filter('wpfunnels/modify_order_bump_product_price', array($this, 'maybe_convert_order_bump_price'));
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
