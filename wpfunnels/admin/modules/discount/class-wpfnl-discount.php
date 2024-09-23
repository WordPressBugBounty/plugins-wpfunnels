<?php
/**
 * Discount module
 * 
 * @package
 */
namespace WPFunnels\Modules\Admin\Discount;
use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Traits\SingletonTrait;

class Module extends Wpfnl_Admin_Module
{
    use SingletonTrait;


    protected $coupons = [];

    /**
     * Implement init_ajax() method.
     * 
     * @return void
     */
    public function init_ajax()
    {
        add_action('wp_ajax_wpfnl_coupon_search', [$this, 'fetch_coupon']);
    }


    /**
     * Get view
     * 
     * @return File
     */
    public function get_view()
    {
        require_once WPFNL_DIR . '/admin/modules/discount/views/view.php';
    }


    /**
     * Fetch coupon list by coupon code
     *
     * @since 1.0.0
     */
    public function fetch_coupon()
    {
        check_ajax_referer('wpfnl-admin', 'security');
        global $wpdb;
        if (isset($_GET['term'])) {
            $term = sanitize_text_field(wp_unslash($_GET['term']));
        }
        if (empty($term)) {
            wp_die();
        }
        $coupon_list = [];
        $discount_types = wc_get_coupon_types();
        $coupons = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}posts WHERE post_type = %s AND post_title LIKE %s AND post_status = %s",
                'shop_coupon',
                $wpdb->esc_like($term) . '%',
                'publish'
            )
        );
        if ($coupons) {
            foreach ($coupons as $post) {
                $discount_type = get_post_meta($post->ID, 'discount_type', true);
                if (!empty($discount_types[ $discount_type ])) {
                    $coupon_list[$post->post_title] = $post->post_title . ' (Type: ' . $discount_types[ $discount_type ] . ')';
                }
            }
        }
        wp_send_json($coupon_list);
    }


    /**
     * Set coupon
     * 
     * @param Array $coupons
     * 
     * @return void
     */
    public function set_coupon($coupons)
    {
        if ($coupons) {
            $discount_types = wc_get_coupon_types();
            foreach ($coupons as $coupon_title) {
                $coupon = new \WC_Coupon($coupon_title);
                $discount_type = $coupon->get_discount_type();
                if (isset($discount_type) && $discount_type) {
                    $discount_type = ' ( Type: ' . $discount_types[ $discount_type ] . ' )';
                }
                $this->coupons[$coupon_title] = $coupon_title . $discount_type;
            }
        }
    }

    /**
     * Get coupon
     * 
     * @return String
     */
    public function get_coupons()
    {
        return $this->coupons;
    }

    /**
     * Get module name
     * 
     * @return String
     */
    public function get_name()
    {
        return __('discount','wpfnl');
    }
}
