<?php
/**
 * Theme Compatibility
 * 
 * @package
 */
namespace WPFunnels\Compatibility;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Compatibility\Theme\Wpfnl_Theme_Compatibility;
use WPFunnels\Compatibility\Plugin\Wpfnl_Plugin_Compatibility;

class Wpfnl_Compatibility {


    /**
     * Init
     */
    public function __construct(){
        $this->init_theme_compatiblity_class();
        $this->init_plugin_compatiblity_class();
    }

    /**
     * Init theme compatiblity class
     * 
     * @since 2.7.4
     */
    public function init_theme_compatiblity_class(){
        $theme_compatibility  = new Wpfnl_Theme_Compatibility();
        $theme_compatibility->init_classes();
    }


    /**
     * Init plugin compatiblity class
     * 
     * @since 2.7.4
     */
    public function init_plugin_compatiblity_class(){
        $plugin_compatibility  = new Wpfnl_Plugin_Compatibility();
        $plugin_compatibility->init_classes();
    }

}