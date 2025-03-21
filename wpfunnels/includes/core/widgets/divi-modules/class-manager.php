<?php
/**
 * Divi manager
 *
 * @package
 */
namespace WPFunnels\Widgets\DiviModules;

use function cli\err;

final class Manager {

    private static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function __construct()
    {
        $this->init();
        new Wpfnl_Divi_Editor();
    }

    /**
     * Initialize divi modules
     */
    private function init() {
		add_action( 'divi_extensions_init', array( $this, 'wpfnl_initialize_extension' ) );
	}


    public function wpfnl_initialize_extension() {
        WPFNL_DiviModules::get_instance();
    }

}
