<?php
/**
 * Twenty Twenty Two Compatibility
 * 
 * @package
 */
namespace WPFunnels\Compatibility\Theme;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * Twenty Twenty Two Compatibility
 * 
 * @package WPFunnels\Compatibility\TwentyTwentyTwo
 */
class Wpfnl_Twenty_Twenty_Two_Compatibility{
	
	use SingletonTrait;

	/**
	 * Filters/Hook from Twenty Twenty Two
	 *
	 * @since 2.5.7
	 */
	public function init() {
        
        if( Wpfnl_functions::is_funnel_checkout_page() && 'twentytwentytwo'  === wp_get_theme()->get('TextDomain') ){

			remove_action( 'woocommerce_checkout_after_order_review', [ 'WC_Twenty_Twenty_Two','after_order_review']);
			remove_action( 'woocommerce_checkout_before_order_review_heading', [ 'WC_Twenty_Twenty_Two', 'before_order_review']);

		}
		
	}

}
