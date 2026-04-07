<?php
/**
 * Initialize Automation REST API
 *
 * @package WPFunnels
 * @since 3.9.5
 */

namespace WPFunnels\Modules\Admin\Automations;

use WPFunnels\Rest\Controllers\AutomationController;

/**
 * Initialize REST API for Automations
 */
function init_automation_rest_api()
{
    // Register REST API controller
    add_action('rest_api_init', function () {
        // Load the controller file
        require_once WPFNL_DIR . '/includes/core/rest-api/Controllers/class-automation-controller.php';
        
        $controller = new AutomationController();
        $controller->register_routes();
    });
}

add_action('init', __NAMESPACE__ . '\init_automation_rest_api');
