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
        $class_name = "WPFunnels\\Admin\\FunnelType\\Wpfunnels_".ucfirst($module)."_Checkout";
        
        if (!class_exists(ucfirst($class_name))) {
            throw new \Exception('Invalid Condition Module.');
        }else {
            return new $class_name();
        }
    }
}