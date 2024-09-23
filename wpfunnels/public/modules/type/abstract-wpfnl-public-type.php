<?php
/**
 * Abstract funnel type class
 * 
 * @package
 */
namespace WPFunnels\FunnelType;

abstract class Wpfnl_Public_Funnel_Type
{
    /**
     * Responsible for order bump accept or reject
     */
    abstract public function wpfnl_order_bump_trigger ( $step_id, $product_id, $quantity, $key, $user_id, $funnel_id, $checker );

}