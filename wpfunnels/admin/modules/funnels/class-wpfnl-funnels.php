<?php
/**
 * Module 
 * 
 * @package
 */
namespace WPFunnels\Modules\Admin\Funnels;

use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Data_Store\Wpfnl_Funnel_Store_Data;
use WPFunnels\Traits\SingletonTrait;

class Module extends Wpfnl_Admin_Module
{
    use SingletonTrait;


    /**
     * List of published funnels
     *
     * @var
     * @since 1.0.0
     */
    protected $funnels;


    /**
     * Ff needs to show pagination
     *
     * @var   bool
     * @since 1.0.0
     */
    protected $pagination = false;


    /**
     * Total number of funnels
     *
     * @var
     * @since 1.0.0
     */
    protected $total_funnels;


    /**
     * Total number of pages
     *
     * @var
     * @since 1.0.0
     */
    protected $total_page = 1;


    /**
     * Current page number
     *
     * @var
     * @since 1.0.0
     */
    protected $current_page = 1;


    /**
     * Offset
     *
     * @var   int
     * @since 1.0.0
     */
    protected $offset = 1;

    protected $utm_settings;


    protected $page_name;


    protected $total_live_funnel = 0;

    protected $total_trash_funnel = 0;


    /**
     * Get view of the funnel list
     *
     * @since 1.0.0
     */
    public function get_view()
    {
        $this->current_page = isset( $_GET[ 'pageno' ] ) ? sanitize_text_field( $_GET[ 'pageno' ] ) : 1;
        $this->page_name    = isset( $_GET[ 'page' ] ) ? sanitize_text_field( $_GET[ 'page' ] ) : 'wp_funnels';
        $per_page           = isset( $_GET[ 'per_page' ] ) ? sanitize_text_field( $_GET[ 'per_page' ] ) : WPFNL_FUNNEL_PER_PAGE;
        $this->offset       = ( $this->current_page - 1 ) * $per_page;

        $this->init_all_funnels( $per_page, $this->offset );
        require_once WPFNL_DIR . '/admin/modules/funnels/views/view.php';
    }


    /**
     * Get arguments for funnel
     * query
     *
     * @return array
     * @since  1.0.0
     */
    public function get_args( $per_page = WPFNL_FUNNEL_PER_PAGE )
    {
        $args = [
            'post_type'         => WPFNL_FUNNELS_POST_TYPE,
            'posts_per_page'    => $per_page,
            'offset'            => $this->offset,
        ];

        if( $this->page_name == 'wp_funnels' ){
            $args['post_status'] = array('publish', 'draft');
        }else{
            $args['post_status']       = array('trash');
        }
        
        if (isset($_GET['s'])) {
            $args['s'] = sanitize_text_field($_GET['s']);

        }
        return $args;
    }


    /**
     * Get all funnel list
     *
     * @param int $limit
     * @param int $offset
     * 
     * @since 1.0.0
     */
    public function init_all_funnels($limit = 10, $offset = 0)
    {
        $args = [
            'post_type'         => WPFNL_FUNNELS_POST_TYPE, // Ensure this constant is correctly defined.
            'posts_per_page'    => -1,  // Fetch all posts.
            'suppress_filters'  => true,
            'fields'            => 'ids', // Fetch only the IDs for performance.
            'post_status'       =>  array('publish', 'draft')
        ];
        
        $total_live_funnel = get_posts($args);
        $this->total_live_funnel = count($total_live_funnel);


        $args = [
            'post_type'         => WPFNL_FUNNELS_POST_TYPE, // Ensure this constant is correctly defined.
            'posts_per_page'    => -1,  // Fetch all posts.
            'suppress_filters'  => true,
            'fields'            => 'ids', // Fetch only the IDs for performance.
            'post_status'       =>  array('trash')
        ];
        
        $total_trash_funnel = get_posts($args);
        $this->total_trash_funnel = count($total_trash_funnel);

        $args = [
            'post_type'         => WPFNL_FUNNELS_POST_TYPE,
            'posts_per_page'    => -1,
            'suppress_filters'  => true,
            'fields'            => 'ids'
        ];

        if( $this->page_name == 'wp_funnels' ){
            $args['post_status'] = array('publish', 'draft');
        }else{
            $args['post_status']       = array('trash');
        }
      
        if (isset($_GET['s'])) {
            $args['s'] = sanitize_text_field($_GET['s']);
        }
        
        $all_funnels = get_posts($args);
        $funnels = get_posts($this->get_args( $limit ));
        $this->funnels = $this->get_formatted_funnel_array($funnels);

        $this->total_funnels = count( $all_funnels );
        $this->pagination    = (bool)count( $this->funnels );
        if (count($this->funnels)) {
            $this->total_page = ceil($this->total_funnels / $limit);
        }
        $this->utm_settings = $this->get_utm_settings();
    }


    /**
     * Get all funnel list
     *
     * @return array
     * @since  1.0.0
     */
    public function get_formatted_funnel_array($funnels)
    {
        $_funnels = [];
        if ($funnels) {
            foreach ($funnels as $funnel) {
                $_funnel = new Wpfnl_Funnel_Store_Data();
                $_funnel->read($funnel->ID);
                $_funnel->set_data($funnel);
                $_funnels[] = $_funnel;
            }
        }
        return $_funnels;
    }


    /**
     * Get name
     * 
     * @return String
     * @since  2.0.4
     */
    public function get_name()
    {
        return __('funnels','wpfnl');
    }


    /**
     * Get GTM Settings
     *
     * @return array
     */
    public function get_utm_settings() {
		$default_settings = array(
			'utm_enable'	=> 'off',
			'utm_source' 	=> '',
			'utm_medium' 	=> '',
			'utm_campaign' 	=> '',
			'utm_content' 	=> '',
		);
        $utm_settings = get_option('_wpfunnels_utm_params', $default_settings);
        return wp_parse_args($utm_settings, $default_settings);
    }

    /**
     * Init ajax
     */
    public function init_ajax(){
        
    }
}
