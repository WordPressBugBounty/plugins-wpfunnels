<?php
/**
 * AstraPro Compatibility
 * 
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * Astra Pro Compatibility
 * 
 * @package WPFunnels\Compatibility\AstraPro
 */
class AstraPro extends PluginCompatibility{
	use SingletonTrait;

	/**
	 * Filters/Hook from Astra Pro
	 * to initiate the necessary updates.
	 *
	 * @since 3.4.8
	 */
	public function init() {
		
		add_action( 'wp', array( $this, 'remove_astra_modern_checkout_for_wpfnl_step'), 5 );
	}


	/**
	 * Remove astra pro mordern checkout for WPFunnels steps
	 * 
	 * @since 3.4.8
	 * @return void
	 */
	public function remove_astra_modern_checkout_for_wpfnl_step() {
		$theme = wp_get_theme();
		$parent_theme = $theme->parent();
		$isAstra = 'Astra' === $theme->name;
		if ($parent_theme && 'Astra' === $parent_theme->get('Name')) {
			$isAstra = true;
		}

		if(  Wpfnl_functions::is_funnel_step_page() && $isAstra && defined('ASTRA_EXT_DIR') ){
			require_once ASTRA_EXT_DIR . '/addons/woocommerce/classes/class-astra-ext-woocommerce-markup.php';
			if( class_exists('ASTRA_Ext_WooCommerce_Markup')){
				
				if( !class_exists( 'Astra_Woocommerce' ) ){
					require_once get_template_directory() . '/inc/compatibility/woocommerce/class-astra-woocommerce.php';
				}
				
				$astra_woo_markup = \ASTRA_Ext_WooCommerce_Markup::get_instance();
				// Remove the 'modern_checkout' action hook
				remove_action( 'wp', array( $astra_woo_markup, 'modern_checkout' ) );
				remove_action( 'astra_addon_get_css_files', array( $astra_woo_markup, 'add_styles' ) );
				remove_action( 'astra_addon_get_js_files', array( $astra_woo_markup, 'add_scripts' ) );
				remove_action( 'wp', array( $astra_woo_markup, 'multistep_checkout' ) );
				remove_action( 'body_class', array( $astra_woo_markup, 'body_class' ) );
				remove_action( 'astra_addon_js_localize', array( $astra_woo_markup, 'checkout_js_localize' ) );
				remove_action( 'wp_enqueue_scripts', array( $astra_woo_markup, 'enqueue_frontend_scripts' ) );
				remove_action( 'woocommerce_get_country_locale_default', array( $astra_woo_markup, 'default_fields_customization' ) );
			}
		}
	}


	/**
	 * Check if Astra Pro is activated
	 *
	 * @return bool
	 * @since 3.4.8
	 */
	public function maybe_activate()
	{
		return defined('ASTRA_EXT_DIR');
	}
}
