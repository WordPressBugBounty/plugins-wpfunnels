<?php
/**
 * Abstract funnel 
 * 
 * @package
 */
namespace WPFunnels\Admin\FunnelType;

abstract class Wpfnl_Funnel_Type
{

    /**
     * Fetch product/course when search product/course in canvas for checkout/upsell/downsell
     */
    abstract public function retrieve_item( $term );

    /**
     * Fetch product/course when search product/course in canvas for orderbump
     */
    abstract public function retrieve_ob_item( $term );


    /**
     * Save checkout item to post meta 
     * 
     * @param Array $payload
     * @param String $product_id
     * @param Array $saved_products
     * 
     * @since 2.4.6
     */
    abstract public function save_items( $payload, $product_id, $saved_products );


    /**
     * Get items for checkout step
     * 
     * @param String $step_id
     * 
     * @return Array
     * @since  2.4.6
     */
    abstract public function get_items( $step_id );




}