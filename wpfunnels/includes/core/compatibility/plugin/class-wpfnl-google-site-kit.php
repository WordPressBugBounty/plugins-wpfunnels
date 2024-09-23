<?php
/**
 * GoogleSiteKit Compatibility
 * Google Site Kit is doing conversion tracking using query parameters
 * Keys are different in Google Site Kit and WPFunnels 
 * This class is responsible for updating the query parameters for Google Site Kit
 * 
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * Google Site Kit Compatibility
 * 
 * @package WPFunnels\Compatibility	
 */
class GoogleSiteKit extends PluginCompatibility{
	use SingletonTrait;

	/**
	 * Implement Filters/Hooks
	 * to initiate the necessary updates.
	 *
	 * @since 3.4.13
	 */
	public function init() {
		
		add_filter( 'wpfunnels/update_query_param', array( $this, 'update_query_param'), 10 );
		add_filter( 'wpfunnels/prepare_query_param', array( $this, 'update_query_param'), 10 );
		add_filter( 'wpfunnels/order_meta', array( $this, 'update_query_param'), 10 );
	}


	/**
	 * Updates the query parameters 
	 * This function checks if the 'wpfnl-key' parameter is set in the given array
	 * If it is, the function add new param 'key' with the same value of 'wpfnl-key' and returns the updated array
	 * If 'wpfnl-key' is not set, the function returns the original array without any changes.
	 * 
	 * @param array $query_param
	 * 
	 * @return array
	 * 
	 * @since 3.4.13
	 */
	public function update_query_param( $query_param ) {
		if( isset( $query_param['wpfnl-key'] )){
			$query_param['key'] = $query_param['wpfnl-key'];
		}
		return $query_param;
	}



	/**
	 * Check if Google Site Kit is activated
	 * Checked out the constant 'GOOGLESITEKIT_PLUGIN_MAIN_FILE' is defined or not
	 * If not defined, the plugin is not activated and will return false
	 * If defined, the plugin is activated and will return true
	 * 
	 * @return bool
	 * @since 3.4.13
	 */
	public function maybe_activate()
	{
		return defined('GOOGLESITEKIT_PLUGIN_MAIN_FILE');
	}
}
