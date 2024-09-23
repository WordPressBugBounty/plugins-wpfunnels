<?php
/**
 * Theme Compatibility
 * 
 * @package
 */
namespace WPFunnels\Compatibility\Theme;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;


class Wpfnl_Theme_Compatibility {


    /**
     * Init all supported theme class
     *  
     */
    public function init_classes(){
        $themes = $this->get_all_compatible_themes();
        if( is_array( $themes ) ){
            foreach( $themes as $theme ){
                if( isset( $theme['class_name'] )  ){
                    $class_name = "WPFunnels\\Compatibility\\Theme\\".$theme['class_name'];
                    if (class_exists(ucfirst($class_name))) {
                        $class_name::getInstance()->init();
                    }
                }
            }
        }
    }



    /**
     * Compatible themes
     */
    public function get_all_compatible_themes(){
        $themes = [
            [
                'name'          => 'Flatsome',
                'class_name'    => 'Wpfnl_Flatsome_Compatibility'
            ],
            [
                'name'          => 'TwentyTwentyTwo',
                'class_name'    => 'Wpfnl_Twenty_Twenty_Two_Compatibility'
            ],
            [
                'name'          => 'TwentyTwentyThree',
                'class_name'    => 'Wpfnl_Twenty_Twenty_Three_Compatibility'
            ],
        ];
        return $themes;
    }
}