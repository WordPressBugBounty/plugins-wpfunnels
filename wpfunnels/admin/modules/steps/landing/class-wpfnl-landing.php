<?php
/**
 * Landing module
 * 
 * @package 
 */
namespace WPFunnels\Admin\Modules\Steps\Landing;

use WPFunnels\Admin\Modules\Steps\Module as Steps;

class Module extends Steps
{
    protected $_internal_keys = [

    ];

    /**
     * Get view of the landing page settings module
     *
     * @since 1.0.0
     */
    public function get_view()
    {
        $show_settings = filter_input(INPUT_GET, 'show_settings', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($show_settings == 1) {
            require_once WPFNL_DIR . '/admin/modules/steps/landing/views/settings.php';
        } else {
            require_once WPFNL_DIR . '/admin/modules/steps/landing/views/view.php';
        }
    }

    public function get_name()
    {
        return __('landing','wpfnl');
    }

    public function init_ajax()
    {
    }
}
