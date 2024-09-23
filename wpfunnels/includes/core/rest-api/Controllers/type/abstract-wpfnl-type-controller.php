<?php
/**
 * WPFunnels type abstract controller
 * 
 * @package WPFunnels\Controller
 */
namespace WPFunnels\Controller;
abstract class Wpfnl_Controller_Type
{
    /**
     * Get products/courses from steps
     */
    abstract public function get_items( $step_id );


    /**
     * Get products/courses from steps
     */
    abstract public function get_ob_settings( $all_settings );


    /**
     * Get products/courses from steps
     */
    abstract public function update_ob_settings( $all_settings );

    

}