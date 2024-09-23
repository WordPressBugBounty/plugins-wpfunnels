<?php
/**
 * Create class object of funnel type and return object
 *
 * @package
 * 
 * @since 2.4.5
 */



/**
 * Wpfnl Public Type Factory
 *
 * @package Wpfnl_Public_Type_Factory
 * 
 * @since 2.4.5
 */
class Wpfnl_Public_Type_Factory {

    public static function build($module)
    {
        $class_name = "WPFunnels\\FunnelType\\Wpfnl_Public_".ucfirst($module);
        if (!class_exists(ucfirst($class_name))) {
            throw new \Exception('Invalid Condition Module.');
        }else {
            return new $class_name();
        }
    }
}