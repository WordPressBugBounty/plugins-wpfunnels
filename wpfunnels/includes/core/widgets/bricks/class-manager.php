<?php
/**
 * Namespace declaration for the WPFunnels\Widgets\Bricks class.
 */
namespace WPFunnels\Widgets\Bricks;
use WPFunnels\Wpfnl_functions;
require_once get_template_directory() . '/includes/helpers.php';

/**
 * Class Manager
 * 
 * This class represents the manager for the bricks widget in the WP Funnels plugin.
 * It handles the creation and management of bricks.
 * 
 * @package WPFunnels\Widgets\Bricks
 */
class Manager {


    /**
     * Instance
     *
     * @since 3.1.0
     *
     * @access private
     * @static
     *
     * @var Manager The single instance of the class.
     */
    private static $instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return Manager An instance of the class.
     * @since  1.0.0
     *
     * @access public
     * @static
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 3.1.0
     *
     * @access public
     */
    public function __construct() {
       
        add_action('init', array($this, 'init'),11);
        add_filter('wpfunnels/allow_to_change_page_template',[$this,'allow_to_change_page_template'],10);
    }
    

    /**
     * Allows or disallows changing the page template for bricks.
     *
     * @param bool $is_allow Whether to allow changing the page template.
     */
    public function allow_to_change_page_template( $is_allow ){
        if( Wpfnl_functions::maybe_bricks_theme()){
            $is_allow = false;
        }
        return $is_allow;
    }


     /**
     * Register checkout elements for bricks
     *
     * @since 3.1.0
     *
     * @access public
     */
    public function init() {
        $elements = [
            [
                'file'  => __DIR__. '/NextStep.php',
                'class' => '\WPFunnels\Widgets\Bricks\NextStep',
            ],
            [
                'file'  => __DIR__. '/OrderDetailsWidget.php',
                'class' => '\WPFunnels\Widgets\Bricks\OrderDetailsWidget',
            ],
            
            [
                'file'  => __DIR__. '/Optin.php',
                'class' => '\WPFunnels\Widgets\Bricks\Optin',
            ],
            [
                'file'  => __DIR__. '/Checkout.php',
                'class' => '\WPFunnels\Widgets\Bricks\Checkout',
            ]
        ];
        
        $elements = apply_filters('wpfunels/bricks_elements', $elements );
        if ( class_exists( '\Bricks\Elements' ) ) {
            foreach ( $elements as $element ) {
                \Bricks\Elements::register_element( $element['file'] );
            }
        }
        
    }

}

Manager::instance();