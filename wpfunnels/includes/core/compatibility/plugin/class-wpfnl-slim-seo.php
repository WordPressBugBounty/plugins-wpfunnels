<?php
/**
 * SlimSeo Compatibility
 * 
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * Slim seo Compatibility
 * 
 * @package WPFunnels\Compatibility\SlimSeo
 */
class SlimSeo extends PluginCompatibility{
	use SingletonTrait;

	/**
	 * Filters/Hook from Slim Seo
	 * to initiate the necessary updates.
	 *
	 * @since 2.5.7
	 */
	public function init() {
		add_filter( 'slim_seo_meta_description', [ $this, 'wpfnl_no_description' ] );
	}


    /**
     * Compatibility with Slim seo by retuning false of this filter hook
     * 
     * @return Bool
     * @since  2.5.7
     */
    public function wpfnl_no_description(){
        return false;
    }

	/**
	 * Check if slim seo is activated
	 *
	 * @return bool
	 * @since  2.7.7
	 */
	public function maybe_activate()
	{
		if (in_array('slim-seo/slim-seo.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			return true;
		}elseif( function_exists('is_plugin_active') ){
			if( is_plugin_active( 'slim-seo/slim-seo.php' )){
				return true;
			}
		}
		return false;
	}
}
