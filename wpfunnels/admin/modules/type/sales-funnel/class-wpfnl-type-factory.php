<?php
/**
 * Create class object of funnel type and return object
 *
 * @package
 */


/**
 * Create class object of funnel type and return object
 *
 * @package
 */
class Wpfnl_Type_Factory {

    public static function build($module)
    {
        // Store Checkout funnels behave like standard WooCommerce funnels
        if ( 'store_checkout' === $module ) {
            $module = 'wc';
        }

        $class_name = "WPFunnels\\Admin\\FunnelType\\Wpfunnels_".ucfirst($module)."_Checkout";

        // Types with no checkout class of their own ('lead', or 'lms' while the
        // LMS add-on is off) share the standard class; it already copes with
        // WooCommerce being inactive. Throwing here fataled the products REST call.
        if (!class_exists($class_name)) {
            $class_name = "WPFunnels\\Admin\\FunnelType\\Wpfunnels_Wc_Checkout";
        }

        if (!class_exists($class_name)) {
            throw new \Exception('Invalid Condition Module.');
        }

        return new $class_name();
    }
}