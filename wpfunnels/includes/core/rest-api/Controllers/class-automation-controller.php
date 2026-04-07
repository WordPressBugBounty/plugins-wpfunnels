<?php
/**
 * Automation REST API Controller
 *
 * @package WPFunnels
 * @since 3.9.5
 */

namespace WPFunnels\Rest\Controllers;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use MintMail\App\Internal\Automation\AutomationModel;
use Mint\MRM\DataBase\Tables\AutomationMetaSchema;

/**
 * Automation API Controller
 */
class WpfnlAutomationController extends WP_REST_Controller
{
    /**
     * Namespace
     *
     * @var string
     */
    protected $namespace = 'wpfnl/v1';

    /**
     * Rest base
     *
     * @var string
     */
    protected $rest_base = 'automation';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // Get all automations
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'create_item'],
                    'permission_callback' => [$this, 'create_item_permissions_check'],
                ],
            ]
        );

        // Get, update, delete single automation
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => [
                        'id' => [
                            'description' => __('Unique identifier for the automation.', 'wpfnl'),
                            'type' => 'integer',
                        ],
                    ],
                ],
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'update_item'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args' => [
                        'id' => [
                            'description' => __('Unique identifier for the automation.', 'wpfnl'),
                            'type' => 'integer',
                        ],
                    ],
                ],
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [$this, 'delete_item'],
                    'permission_callback' => [$this, 'delete_item_permissions_check'],
                    'args' => [
                        'id' => [
                            'description' => __('Unique identifier for the automation.', 'wpfnl'),
                            'type' => 'integer',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Check permissions for getting items
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Get all automations
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_items($request)
    {
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 10;

        // Get automations from Mail Mint if available
        if (class_exists('MintMail\\App\\Internal\\Automation\\AutomationModel')) {
            $offset      = ($page - 1) * $per_page;
            $automations = AutomationModel::get_all('created_at', 'DESC', $offset, $per_page, '', 'all');

            return new WP_REST_Response($automations, 200);
        }

        // Fallback if Mail Mint is not available
        return new WP_REST_Response([
            'data' => [],
            'total' => 0,
            'total_pages' => 0,
            'per_page' => $per_page
        ], 200);
    }

    /**
     * Check permissions for creating item
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function create_item_permissions_check($request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Create automation
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function create_item($request)
    {
        $data = $request->get_json_params();

        // Use Mail Mint automation creation if available
        if (class_exists('MintMail\\App\\Internal\\Automation\\AutomationModel')) {
            if (empty($data['author'])) {
                $data['author'] = get_current_user_id();
            }
            $automation_id = AutomationModel::get_instance()->create_or_update($data);

            if (!$automation_id) {
                return new WP_Error(
                    'wpfnl_automation_create_failed',
                    __('Failed to create automation.', 'wpfnl'),
                    ['status' => 500]
                );
            }

            $this->update_meta($automation_id, 'source', 'wpf');

            return new WP_REST_Response(['id' => $automation_id], 201);
        }

        // Fallback
        return new WP_Error(
            'wpfnl_automation_not_available',
            __('Mail Mint is required for automation functionality.', 'wpfnl'),
            ['status' => 400]
        );
    }

    /**
     * Check permissions for getting single item
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_item_permissions_check($request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Get single automation
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_item($request)
    {
        $id = $request->get_param('id');

        // Get automation from Mail Mint if available
        if (class_exists('MintMail\\App\\Internal\\Automation\\AutomationModel')) {
            $automation = AutomationModel::get_single($id);

            if (empty($automation)) {
                return new WP_Error(
                    'wpfnl_automation_not_found',
                    __('Automation not found.', 'wpfnl'),
                    ['status' => 404]
                );
            }

            return new WP_REST_Response($automation, 200);
        }

        return new WP_Error(
            'wpfnl_automation_not_found',
            __('Automation not found.', 'wpfnl'),
            ['status' => 404]
        );
    }

    /**
     * Check permissions for updating item
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Update automation
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_item($request)
    {
        $id = $request->get_param('id');
        $data = $request->get_json_params();

        // Update automation via Mail Mint if available
        if (class_exists('MintMail\\App\\Internal\\Automation\\AutomationModel')) {
            $data['id']    = $id;
            $automation_id = AutomationModel::get_instance()->create_or_update($data);

            if (!$automation_id) {
                return new WP_Error(
                    'wpfnl_automation_update_failed',
                    __('Failed to update automation.', 'wpfnl'),
                    ['status' => 500]
                );
            }

            return new WP_REST_Response(['id' => $automation_id], 200);
        }

        return new WP_Error(
            'wpfnl_automation_not_available',
            __('Mail Mint is required for automation functionality.', 'wpfnl'),
            ['status' => 400]
        );
    }

    /**
     * Check permissions for deleting item
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check($request)
    {
        return current_user_can('manage_options');
    }

    /**
     * Update/insert automation meta
     *
     * @param int    $automation_id
     * @param string $meta_key
     * @param string $meta_value
     * @return bool|int
     */
    public function update_meta($automation_id, $meta_key, $meta_value)
    {
        global $wpdb;
        $automation_meta_table = $wpdb->prefix . AutomationMetaSchema::$table_name;

        $select_query = $wpdb->prepare(
            "SELECT * FROM $automation_meta_table WHERE automation_id = %d AND meta_key = %s",
            array($automation_id, $meta_key)
        );
        $results = $wpdb->get_results($select_query);

        if ($results) {
            try {
                $payload = [
                    'id'         => isset($results[0]->id) ? $results[0]->id : '',
                    'meta_key'   => $meta_key,
                    'meta_value' => $meta_value,
                    'updated_at' => current_time('mysql'),
                ];
                $updated = $wpdb->update(
                    $automation_meta_table,
                    $payload,
                    array('id' => $payload['id'])
                );
                return $updated ? true : false;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            try {
                $wpdb->insert(
                    $automation_meta_table,
                    array(
                        'automation_id' => $automation_id,
                        'meta_key'      => $meta_key,
                        'meta_value'    => $meta_value,
                        'created_at'    => current_time('mysql'),
                        'updated_at'    => current_time('mysql'),
                    )
                );
                return $wpdb->insert_id;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * Delete automation
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_item($request)
    {
        $id = $request->get_param('id');

        // Delete automation via Mail Mint if available
        if (class_exists('MintMail\\App\\Internal\\Automation\\AutomationModel')) {
            $result = AutomationModel::destroy($id);

            if (!$result) {
                return new WP_Error(
                    'wpfnl_automation_not_found',
                    __('Automation not found or could not be deleted.', 'wpfnl'),
                    ['status' => 404]
                );
            }

            return new WP_REST_Response([
                'message' => __('Automation deleted successfully.', 'wpfnl'),
                'id' => $id
            ], 200);
        }

        return new WP_Error(
            'wpfnl_automation_not_available',
            __('Mail Mint is required for automation functionality.', 'wpfnl'),
            ['status' => 400]
        );
    }
}
