<?php
/**
 * Flatsome Theme Compatibility
 * 
 * @package
 */
namespace WPFunnels\Compatibility\Theme;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * Flatsome Theme Compatibility
 * 
 * @package WPFunnels\Compatibility\Flatsome
 */
class Wpfnl_Flatsome_Compatibility{
	use SingletonTrait;

	/**
	 * Filters/Hook from Flatsome
	 *
	 * @since 2.5.7
	 */
	public function init() {
        
        add_action( 'init', [ $this, 'allow_custom_post_type' ], 10 );
		
	}


    /**
     * Allow custom post type
     */
    public function allow_custom_post_type(){
        if ( function_exists( 'add_ux_builder_post_type' ) ) {
            add_ux_builder_post_type( WPFNL_STEPS_POST_TYPE );
        }
    }

}
