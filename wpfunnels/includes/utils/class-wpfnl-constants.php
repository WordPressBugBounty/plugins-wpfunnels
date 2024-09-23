<?php
/**
 * Define all constant
 * 
 * @package WPFunnels\Constants
 */

namespace WPFunnels\Constants;

class Wpfnl_Constants {

    static $plugin_version = '1.0.0';

    static $plugin_name = 'WP Funnels';

    static $plugin_page_hooks = array(
        'toplevel_page_wp_funnels',
        'wp-funnels_page_wpf_templates',
        'wp-funnels_page_wp_funnel_settings',
        'wp-funnels_page_edit_funnel',
        'wp-funnels_page_create_funnel',
        'wp-funnels_page_settings',
    );
}
