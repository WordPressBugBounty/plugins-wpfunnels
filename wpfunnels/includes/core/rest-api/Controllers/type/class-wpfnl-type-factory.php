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
class Wpfnl_Controller_Type_Factory {

    public static function build($module)
    {
        $class_name = "WPFunnels\\Controller\\Wpfnl_Controller_".ucfirst($module);
        if (!class_exists(ucfirst($class_name))) {
            throw new \Exception('Invalid Condition Module.');
        }else {
            return new $class_name();
        }
    }
}