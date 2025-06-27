<?php

/**
 * Class PluginInstallerController
 *
 * Handles the installation, activation, and management of plugins within the application.
 * Provides methods for installing new plugins, activating/deactivating existing plugins,
 * and managing plugin-related actions via HTTP requests.
 *
 * @package YourPluginNamespace\Controllers
 */

namespace WPFunnels\Rest\Controllers;

/**
 * Class PluginInstallerController
 * 
 * @since 3.5.25
 */
class PluginInstallerController extends Wpfnl_REST_Controller{

    /**
     * Endpoint namespace.
     *
     * @var string
     * @since 3.5.25
     */
    protected $namespace = 'wpfunnels/v1';


    /**
     * Route base.
     *
     * @var string
     * @since 3.5.25
     */
    protected $rest_base = 'activate-plugin';


    /**
     * Register rest routes
     *
     * @since 3.5.25
     */
    public function register_routes(){

        register_rest_route(
            $this->namespace,
            $this->rest_base,
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'activate_plugin'),
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    },
                ),
            )
        );
    }

    /**
     * Activate a plugin by its basename and slug.
     *
     * This method checks if the plugin is already installed. If not, it attempts to install it
     * from the WordPress.org repository. After installation, it activates the plugin.
     *
     * @param \WP_REST_Request $request The REST request object containing parameters.
     * @return \WP_REST_Response The response object containing the result of the activation.
     * 
     * @since 3.5.25
     */
    public function activate_plugin(\WP_REST_Request $request) {
        $basename = $request->get_param('basename');
        $slug     = $request->get_param('slug');
        $response = array();

        if (empty($basename) || empty($slug)) {
            return new \WP_REST_Response(array('error' => 'Plugin basename or slug is missing.'), 400);
        }

        // Check if plugin file exists
        if (!file_exists(WP_PLUGIN_DIR . '/' . $basename)) {
            // Try to install the plugin from WordPress.org
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';

            $api = plugins_api('plugin_information', array('slug' => $slug, 'fields' => array('sections' => false)));
            if (is_wp_error($api)) {
                return new \WP_REST_Response(array('error' => 'Plugin not found in WordPress.org repository.'), 400);
            }

            $upgrader       = new \Plugin_Upgrader(new \Automatic_Upgrader_Skin());
            $install_result = $upgrader->install($api->download_link);

            if (is_wp_error($install_result)) {
                return new \WP_REST_Response(array('error' => $install_result->get_error_message()), 400);
            }
        }

        // Activate plugin
        if (!is_plugin_active($basename)) {
            $result = activate_plugin($basename);
            if (is_wp_error($result)) {
                return new \WP_REST_Response(array('error' => $result->get_error_message()), 400);
            }
            $response['message'] = sprintf(__('Plugin %s installed and activated successfully.', 'wpfnl'), $slug);
        } else {
            $response['message'] = sprintf(__('Plugin %s is already active.', 'wpfnl'), $slug);
        }

        $response['success']  = true;
        $response['basename'] = $basename;
        $response['slug']     = $slug;
        return new \WP_REST_Response($response, 200);
    }

}
