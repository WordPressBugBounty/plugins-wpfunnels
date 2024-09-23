<?php
/**
 * Theme Compatibility
 *
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;


class Wpfnl_Plugin_Compatibility {


    /**
     * Init all supported theme class
     *
     */
    public function init_classes(){
        $plugins = $this->get_all_compatible_plugins();
        if( is_array( $plugins ) ){
            foreach( $plugins as $plugin ){
                if( isset( $plugin['class_name'] )  ){
                    $class_name = "WPFunnels\\Compatibility\\Plugin\\".$plugin['class_name'];
                    if (class_exists(ucfirst($class_name))) {
                        if( $class_name::getInstance()->maybe_activate() ){
                            $class_name::getInstance()->init();
                        }
                    }
                }
            }
        }
    }



    /**
     * List of compatible plugins
     *
     * @since 2.7.7
     * @return Array
     */
    public function get_all_compatible_plugins(){
        $plugins = [
            [
                'name'          => 'Cart Lift',
                'class_name'    => 'CartLift'
            ],
            [
                'name'          => 'Slim Seo',
                'class_name'    => 'SlimSeo'
            ],
            [
                'name'          => 'Electro Extension',
                'class_name'    => 'ElectroExtension'
            ],
			[
				'name'          => 'WooCommerce Multilingual',
				'class_name'    => 'Wcml'
            ],
            [
				'name'          => 'Product Addon',
				'class_name'    => 'ProductAddon'
			],
            [
				'name'          => 'Astra Pro',
				'class_name'    => 'AstraPro'
			],
            [
				'name'          => 'Elementor Pro',
				'class_name'    => 'ElementorPro'
			],
            [
				'name'          => 'Tutor LMS',
				'class_name'    => 'Tutor'
			],
            [
				'name'          => 'Google Site Kit',
				'class_name'    => 'GoogleSiteKit'
			]
        ];
        return $plugins;
    }
}
