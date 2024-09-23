<?php

/**
 * Product addon Compatibility
 * 
 * @package WPFunnels\Compatibility\Plugin
 */

namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * Product addon Compatibility
 * 
 * @package WPFunnels\Compatibility\Plugin
 */
class ProductAddon extends PluginCompatibility{
	use SingletonTrait;

	/**
	 * Initiate Filters/Hook
	 *
	 * @since 3.3.2
	 */
	public function init() {
        add_filter('wpfunnels/allow_initiate_funnel_checkout', array($this, 'allow_initiate_funnel_checkout'), 10, 2 );
	}

    
    /**
     * Determines whether to allow initiating the funnel checkout.
     *
     * @param bool   $allow     Whether to allow initiating the funnel checkout.
     * @param int    $funnel_id The ID of the funnel.
     * @return bool  Whether to allow initiating the funnel checkout.
     * 
     * @since 3.3.2
     */
    public function allow_initiate_funnel_checkout($allow, $funnel_id ){
        $is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true );
        if( 'yes' === $is_gbf ){
            $allow = false;
        }
        return $allow;
    }

   
    /**
	 * Check if Product addon is activated
	 *
	 * @return bool
	 * @since  3.3.2
	 */
	public function maybe_activate()
	{
		return defined('WCPA_VERSION');
	}
}
