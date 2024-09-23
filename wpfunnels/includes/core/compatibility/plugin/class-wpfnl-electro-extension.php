<?php

/**
 * Electro Extension Compatibility
 * 
 * @package WPFunnels\Compatibility\Plugin
 */

namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * Electro Extension Compatibility
 * 
 * @package WPFunnels\Compatibility\Plugin
 */
class ElectroExtension extends PluginCompatibility{
	use SingletonTrait;

	/**
	 * Initiate Filters/Hook
	 *
	 * @since 2.7.7
	 */
	public function init() {
		add_filter( 'template_redirect', [ $this, 'set_cookie' ] );
	}


    /**
     * Set post id in cookie variable
     * 
     * @return void
     * @since  2.7.7
     */
    public function set_cookie(){
        if ( Wpfnl_functions::is_funnel_step_page() ) {
            global $post;
            if( !$post ){
                return;
            }
            $step_id            = isset($post->ID) ? $post->ID : '';
            if( !$step_id ){
                return;
            }

            $cookie_name    = 'wpfunnels_current_post_id';
			ob_start();
            setcookie( $cookie_name, wp_json_encode( $step_id ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
			ob_end_flush();

        }
    }
    


    /**
	 * Check if electro extensions is activated
	 *
	 * @return bool
	 * @since  2.7.7
	 */
	public function maybe_activate()
	{
		if (in_array('electro-extension/electro-extensions.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			return true;
		}elseif( function_exists('is_plugin_active') ){
			if( is_plugin_active( 'electro-extensions/electro-extensions.php' )){
				return true;
			}
		}
		return false;
	}
}
