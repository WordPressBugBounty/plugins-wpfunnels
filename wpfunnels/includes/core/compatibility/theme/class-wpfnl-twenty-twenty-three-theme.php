<?php
/**
 * Twenty Twenty Three Compatibility
 * 
 * @package
 */
namespace WPFunnels\Compatibility\Theme;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * Twenty Twenty Three Compatibility
 * 
 * @package WPFunnels\Compatibility\TwentyTwentyThree
 */
class Wpfnl_Twenty_Twenty_Three_Compatibility{
	
	use SingletonTrait;

	/**
	 * Filters/Hook from Twenty Twenty Three
	 *
	 * @since 2.5.7
	 */
	public function init() {
		$theme = wp_get_theme();
		
        if( Wpfnl_functions::is_funnel_checkout_page() && 'Twenty Twenty-Three'  === $theme->name ){
            
            remove_action( 'woocommerce_checkout_before_order_review_heading', array( 'WC_Twenty_Twenty_Three', 'before_order_review' ) );
            remove_action( 'woocommerce_checkout_after_order_review', array( 'WC_Twenty_Twenty_Three', 'after_order_review' ) );
            remove_filter( 'woocommerce_enqueue_styles', array( 'WC_Twenty_Twenty_Three', 'enqueue_styles' ) );

		}
		
	}

}
