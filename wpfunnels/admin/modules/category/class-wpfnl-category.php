<?php
/**
 * Category module
 * 
 * @package
 */
namespace WPFunnels\Modules\Admin\Category;

use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
class Module extends Wpfnl_Admin_Module
{
    use SingletonTrait;

    public function init_ajax()
    {
        add_action('wp_ajax_wpfnl_category_search', [ $this, 'fetch_categories' ]);
        add_action('wp_ajax_wpfnl_tag_search', [ $this, 'fetch_tags' ]);
    }

	public function get_name()
	{
		return 'category';

	}

	public function get_view()
	{

	}


    /**
     * Category search by name
     */
    public function fetch_categories(){
		check_ajax_referer('wpfnl-admin', 'security');
		if (isset($_GET['term'])) {
			$cat_name = (string) esc_attr( wp_unslash($_GET['term']) );
		}
		if (empty($cat_name)) {
			wp_die();
		}
        global $wpdb;
        $cat_Args= "SELECT * FROM $wpdb->terms as t INNER JOIN $wpdb->term_taxonomy as tx ON t.term_id = tx.term_id WHERE tx.taxonomy = 'product_cat' AND t.name LIKE '%".$cat_name."%' ";
        $category = $wpdb->get_results($cat_Args, OBJECT);
        if(empty($category)){
            $data = [
                'status'  => 'success',
                'data'    => 'Category not found',
            ];
        }else{
            $data = [
                'status'  => 'success',
                'data'    => $category,
            ];
        }
        wp_send_json($data);

    }


    /**
     * Tag search by name for GBF enter condition
     * 
     * @since  2.5.2 
     * @return Json
     */
    public function fetch_tags(){
		check_ajax_referer('wpfnl-admin', 'security');
		if (isset($_GET['term'])) {
			$tag_name = (string) esc_attr( wp_unslash($_GET['term']) );
		}
		if (empty($tag_name)) {
			wp_die();
		}
        global $wpdb;
        $tag_Args= "SELECT * FROM $wpdb->terms as t INNER JOIN $wpdb->term_taxonomy as tx ON t.term_id = tx.term_id WHERE tx.taxonomy = 'product_tag' AND t.name LIKE '%".$tag_name."%' ";
        $tags = $wpdb->get_results($tag_Args, OBJECT);
        if(empty($tags)){
            $data = [
                'status'  => 'success',
                'data'    => 'Tag not found',
            ];
        }else{
            $data = [
                'status'  => 'success',
                'data'    => $tags,
            ];
        }
        wp_send_json($data);

    }
}
