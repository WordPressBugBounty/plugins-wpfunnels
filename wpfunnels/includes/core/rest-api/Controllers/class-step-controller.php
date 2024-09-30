<?php
/**
 * Step controller
 *
 * @package WPFunnels\Rest\Controllers
 */

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPFunnels\Metas\Wpfnl_Step_Meta_keys;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;
use WPFunnels\TemplateLibrary\Manager;
use Elementor\Plugin;
class StepController extends Wpfnl_REST_Controller
{

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wpfunnels/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'steps';

    /**
     * Check if user has valid permission
     *
     * @param $request
     *
     * @return bool|WP_Error
     * @since  1.0.0
     */
    public function update_items_permissions_check($request)
    {
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('steps', 'edit')) {
            return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot edit this resource.', 'wpfnl'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|boolean
     * @since  3.0.0
     */
    public function get_items_permissions_check($request)
    {
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('settings')) {
            return new WP_Error('wpfunnels_rest_cannot_view', __('Sorry, you cannot list resources.', 'wpfnl'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }


    /**
     * Register rest routes
     *
     * @since 1.0.0
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . 'funnels/(?P<funnel_id>[\d\.]+)/' . $this->rest_base . '/(?P<step_id>[\d\.]+)/metadata', array(
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'args' => array(
                    'funnel_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'step_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    )
                ),
                'callback' => [
                    $this,
                    'save_step_metadata'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
            )
        ));

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<step_id>[\d\.]+)/delete-step',
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array(
                    $this,
                    'delete_step',
                ),
                'permission_callback' => array(
                    $this,
                    'update_items_permissions_check',
                ),
            ),
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/create-step',
            array(
                array(
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => array(
                        $this,
                        'create_step',
                    ),
                    'permission_callback' => array(
                        $this,
                        'update_items_permissions_check',
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/wpfunnel-import-step',
            array(
                array(
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => array(
                        $this,
                        'import_step',
                    ),
                    'permission_callback' => array(
                        $this,
                        'update_items_permissions_check',
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/update-conditional-status',
            array(
                array(
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => array(
                        $this,
                        'update_conditional_status',
                    ),
                    'permission_callback' => array(
                        $this,
                        'update_items_permissions_check',
                    ),
                    'args' => array(
                        'stepId' => array(
                            'description' => __('Funnel step ID.', 'wpfnl'),
                            'type' => 'integer',
                            'required' => true
                        ),
                        'status' => array(
                            'description' => __('Condition status.', 'wpfnl'),
                            'type' => 'string',
                            'required' => true
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/get-conditions',
            array(
                array(
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => array(
                        $this,
                        'get_conditions',
                    ),
                    'permission_callback' => array(
                        $this,
                        'update_items_permissions_check',
                    ),
                    'args' => array(
                        'stepId' => array(
                            'description' => __('Funnel step ID.', 'wpfnl'),
                            'type' => 'integer',
                            'required' => true
                        )
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/save-condition',
            array(
                array(
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => array(
                        $this,
                        'save_condition',
                    ),
                    'permission_callback' => array(
                        $this,
                        'update_items_permissions_check',
                    ),
                    'args' => array(
                        'stepId' => array(
                            'description' => __('Funnel step ID.', 'wpfnl'),
                            'type' => 'integer',
                            'required' => true
                        ),
                        'conditions' => array(
                            'description' => __('Funnel step ID.', 'wpfnl'),
                            'type' => 'array',
                            'required' => true
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/paste-step',
            array(
                array(
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => array(
                        $this,
                        'paste_step',
                    ),
                    'permission_callback' => array(
                        $this,
                        'update_items_permissions_check',
                    ),
                    'args' => array(
                        'stepId' => array(
                            'description' => __('Funnel step ID.', 'wpfnl'),
                            'type' => 'integer',
                            'required' => true
                        ),
                        'funnelId' => array(
                            'description' => __('Funnel ID.', 'wpfnl'),
                            'type' => 'integer',
                            'required' => true
                        )
                    ),
                ),
            )
        );
    }

    /**
     * Responsible to create a single step of funnels
     *
     * @param WP_REST_Request $payload Data from request.
     *
     * @return array
     * @since  1.0.0
     */
    public function create_step($payload)
    {
        if (!isset($payload['funnel_id'], $payload['step_type'])) {
            return $this->prepare_wp_error_response(
                'rest_invalid_request',
                __('Invalid rest request.', 'wpfnl'),
                array('status' => 400)
            );
        }

        $funnel_id = $payload['funnel_id'];
        $step_type = $payload['step_type'];
        $step_name = isset($payload['step_name']) ? $payload['step_name'] : $step_type;
        $funnel = Wpfnl::get_instance()->funnel_store;
        $step = Wpfnl::get_instance()->step_store;
        $step_id = $step->create_step($funnel_id, $step_name, $step_type);

        $step->set_id($step_id);

        if (!$step_id || is_wp_error($step_id)) {
            return $this->prepare_wp_error_response(
                'rest_step_create_failed',
                __('Failed to create a step', 'wpfnl'),
                array('status' => 400)
            );
        }

        $funnel->set_id($funnel_id);
        $step_edit_link = get_edit_post_link($step_id);
        $step_view_link = get_post_permalink($step_id);
        $response = array(
            'success' => true,
            'step_id' => $step_id,
            'step_edit_link' => $step_edit_link,
            'step_view_link' => rtrim($step_view_link, '/'),
            'step_title' => $step->get_title(),
            'conversion' => 0,
            'visit' => 0,
            'shouldShowAnalytics' => false,
            'abTestingSettingsData' => [],
        );

        if (isset($payload['lastClickedAddStep'])) {
            $reconfigureSettings = get_post_meta($funnel->get_id(), '_wpfnl_reconfigurable_condition_data', true);
            if (is_array($reconfigureSettings) && !empty($reconfigureSettings)) {
                $key = array_search($payload['lastClickedAddStep'], array_column($reconfigureSettings, 'nodeId'));
                if (false !== $key) {
                    $reconfigureSettings[$key]['step_id'] = $step_id;
                    update_post_meta($funnel->get_id(), '_wpfnl_reconfigurable_condition_data', $reconfigureSettings);
                    $response['reconfigureSettings'] = $reconfigureSettings;
                    update_post_meta($step_id, '_wpfnl_maybe_enable_condition', $reconfigureSettings[$key]['is_enable']);
                }
            }
        }

        return rest_ensure_response($response);
    }


    /**
     * Update step meta fields
     *
     * @param WP_REST_Request $request
     * @return WP_Error|\WP_HTTP_Response|WP_REST_Response
     * @since 3.0.14
     */
    public function save_step_metadata(\WP_REST_Request $request)
    {
        $params     = $request->get_params();
        $funnel_id  = isset($params['funnel_id']) ? intval($params['funnel_id']) : 0;
        $step_id    = isset($params['step_id']) ? intval($params['step_id']) : 0;

        if (!$funnel_id) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Invalid funnel id is provided.', 'wpfnl')
            ));
        }

        if (WPFNL_STEPS_POST_TYPE !== get_post_type( $step_id )) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Invalid step id is provided.', 'wpfnl')
            ));
        }

		$is_slug_available = Wpfnl_functions::is_slug_available($params['slug'], $step_id, $params['title']);

		if (!$is_slug_available) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Slug must be unique', 'wpfnl')
            ));
        }

        $result         = $this->update_step_name( $funnel_id, $step_id, $params );
        $step_type      = Wpfnl_functions::get_step_type($step_id);
        $default_meta   = Wpfnl_functions::get_step_default_meta($step_type);

        Wpfnl_Step_Meta_keys::save_meta($step_id, $params, $default_meta);

        return rest_ensure_response(array(
            'success'           => true,
            'post_title'	    => $result['post_title'],
            'permalink'		    => $result['permalink'],
            'funnel_main_link'  => $result['funnel_main_link'],
            'slug'			    => $result['slug'],
			'message' 			=> __('Saved Successfully', 'wpfnl')
        ));
    }


    /**
     * Update the step name and step slug. This will also impact the drawflow data to update the step name
     * and slug which is saved under funnel.
     *
     * @param $funnel_id
     * @param $step_id
     * @param $metadata
     * @return array
     *
     * @since 3.0.14
     */
    public function update_step_name( $funnel_id, $step_id, $metadata ) {
        $step_title     = isset( $metadata['title'] ) ? sanitize_text_field( $metadata['title'] ) : '';
        $step_slug      = isset( $metadata['slug'] ) ? sanitize_title( $metadata['slug'] ) : '';
        $parent_id      = get_post_meta($step_id, '_parent_step_id', true);

        wp_update_post([
            "ID"            => $step_id,
            "post_title"    => $step_title,
            "post_name"     => $step_slug,
        ]);

        if ( defined('WPFNL_PRO_VERSION') ) {
            do_action('wpfunnels/before_update_step_meta', $step_id, $funnel_id, $metadata);
        }
        else {
            if (isset($metadata['title'], $metadata['slug'])) {
                $steps = Wpfnl_functions::get_steps($funnel_id);
                foreach ($steps as $key => $step) {
                    if ( $step['id'] == $step_id ) {
                        $steps[$key]['name'] = $metadata['title'];
                    }
                }
                update_post_meta( $funnel_id, '_steps_order', $steps );
            }
        }

        if ($step_id == $parent_id || !$parent_id) {
            $funnel_data = get_post_meta($funnel_id, '_funnel_data', true);
            if (isset($funnel_data['drawflow']['Home']['data']) and is_array($funnel_data['drawflow']['Home']['data'])) {

                foreach ($funnel_data['drawflow']['Home']['data'] as $key => $data) {
                    if (isset($data['data']['step_id']) && (int)$step_id === (int)$data['data']['step_id']) {
                        $edit_post_link = base64_encode(get_edit_post_link($step_id));
                        $view_link = base64_encode(get_the_permalink($step_id));
                        $funnel_data['drawflow']['Home']['data'][$key]['data']['step_view_link'] = $view_link;
                        $funnel_data['drawflow']['Home']['data'][$key]['data']['step_edit_link'] = $edit_post_link;
                    }
                }
                update_post_meta($funnel_id, '_funnel_data', $funnel_data);
            }

            $funnel_data = get_post_meta($funnel_id, 'funnel_data', true);
            if (isset($funnel_data['drawflow']['Home']['data']) and is_array($funnel_data['drawflow']['Home']['data'])) {
                foreach ($funnel_data['drawflow']['Home']['data'] as $key => $data) {
                    if (isset($data['data']['step_id']) && (int)$step_id === (int)$data['data']['step_id']) {
                        $edit_post_link = base64_encode(get_edit_post_link($step_id));
                        $view_link = base64_encode(get_the_permalink($step_id));
                        $funnel_data['drawflow']['Home']['data'][$key]['data']['step_view_link'] = $view_link;
                        $funnel_data['drawflow']['Home']['data'][$key]['data']['step_edit_link'] = $edit_post_link;
                    }
                }
                update_post_meta($funnel_id, 'funnel_data', $funnel_data);
            }
        }

        $first_step_id  = Wpfnl_functions::get_first_step($funnel_id);
        $utm_settings   = Wpfnl_functions::get_funnel_utm_settings($funnel_id);
        $view_link      = get_post_permalink($first_step_id);

        if (!empty($utm_settings['utm_enable']) && 'on' === $utm_settings['utm_enable']) {
            unset($utm_settings['utm_enable']);
            $view_link = add_query_arg($utm_settings, $view_link);
            $view_link = strtolower($view_link);
        }

        return array(
            'post_title'        => $step_title,
            'permalink'         => rtrim(get_the_permalink($step_id), '/'),
            'funnel_main_link'  => esc_url($view_link),
            'slug'              => sanitize_title($step_slug),
        );
    }



    /**
     * Common function to update step meta
     *
     * @param WP_REST_Request $request
     *
     * @return \WP_REST_Response
     *
     * @since 2.7.10
     */
    public static function update_step_meta_on_funnel_name_change($funnel_id, $step_id, $settings)
    {
        if (is_plugin_active('wpfunnels-pro/wpfnl-pro.php') && Wpfnl_functions::is_pro_license_activated()) {
            /**
             * Fires to update step slug on ab testing variation on funnel name change
             *
             * @param int $step_id The ID of the step.
             * @param int $funnel_id The ID of the funnel to which the step belongs.
             * @param array $settings An array of settings containing the title and slug for the step.
             * @since 2.7.0
             *
             */
            do_action('wpfunnels/before_update_step_meta_on_funnel_name_change', $step_id, $funnel_id, $settings);
        } else {
            if (isset($settings['funnel_id'], $settings['step_id'], $settings['title'], $settings['slug'])) {
                $steps = Wpfnl_functions::get_steps($settings['funnel_id']);
                if (!empty($steps)) {
                    foreach ($steps as $key => $step) {
                        if ($step['id'] == $settings['step_id']) {
                            $steps[$key]['name'] = $settings['title'];
                        }
                    }
                }
                update_post_meta($settings['funnel_id'], '_steps_order', $steps);
                wp_update_post([
                    "ID" => $step_id,
                    "post_title" => wp_strip_all_tags($settings['title']),
                    "post_name" => sanitize_title($settings['slug']),
                ]);
            }
        }
    }


    /**
     * Delete step and all its data
     *
     * @param WP_REST_Request $payload Data from request.
     *
     * @return array
     *
     * @since 2.7.9
     */
    public function delete_step($payload)
    {
        if (!isset ($payload['step_id'])) {
            return $this->prepare_wp_error_response(
                'rest_invalid_stepID',
                __('Invalid Step ID.', 'wpfnl'),
                array('status' => 404)
            );
        }


        $step_id = sanitize_text_field($payload['step_id']);
        $step_type = get_post_meta($step_id, '_step_type', true);

        $result = [
            'success' => true,
        ];

        if (!empty($payload['nodeId'])) {
            $is_enable = Wpfnl_functions::maybe_enable_condition($step_id);
            $condiitons = Wpfnl_functions::get_conditions($step_id);
            $next_step = Wpfnl_functions::get_conditional_next_step($step_id);
            $funnel_id = Wpfnl_functions::get_funnel_id_from_step($step_id);
            $data = get_post_meta($funnel_id, '_wpfnl_reconfigurable_condition_data', true);

            if (!is_array($data) || empty($data)) {
                $data = [];
            } else {
                $key = array_search($payload['nodeId'], array_column($data, 'nodeId'));
                if (false !== $key) {
                    unset($data[$key]);
                }
            }

            $is_enable_condition = get_post_meta($step_id, '_wpfnl_maybe_enable_condition', true);

            if (!empty($payload['stepId']) && !empty($payload['nodeType']) && 'addstep' !== $payload['nodeType']) {
                update_post_meta($payload['stepId'], '_wpfnl_maybe_enable_condition', $is_enable_condition);
            }

            $updatedData = [
                'step_id' => !empty($payload['nodeType']) && 'addstep' !== $payload['nodeType'] && !empty($payload['stepId']) ? $payload['stepId'] : '',
                'nodeId' => $payload['nodeId'],
                'is_enable' => $is_enable_condition,
                'conditions' => get_post_meta($step_id, '_wpfnl_step_conditions', true),
                'next_step' => get_post_meta($step_id, '_wpfnl_next_step_after_condition', true),
            ];

            array_push($data, $updatedData);

            update_post_meta($funnel_id, '_wpfnl_reconfigurable_condition_data', $data);
            $result['reconfigurable_condition_data'] = $data;
        }


        /**
         * Delete the automation data linked with the step
         *
         * @param int $step_id Step ID.
         * @since 2.7.0
         *
         */
        do_action('wpfunnels/before_delete_step', $step_id); //phpcs:ignore

        $response = $this->prepare_wp_error_response(
            'rest_invalid_stepID',
            __('Invalid Step ID.', 'wpfnl'),
            array('status' => 404)
        );

        if ($step_type) {
            $response = $this->delete_regular_step($step_id);
        } else if ($step_id) { // For conditional step.
            $response = $this->conditional_step_response($step_id);
        }

        $result['response'] = $response;
        return $result;
    }

    /**
     * Delete a regular step and all its data.
     *
     * @param int $step_id The ID of the regular step to be deleted.
     *
     * @return array The response containing the success status and message.
     *
     * @since 2.7.9
     */
    private function delete_regular_step($step_id)
    {
        if (!$step_id) {
            return $this->prepare_wp_error_response(
                'rest_invalid_stepID',
                __('Invalid Step ID.', 'wpfnl'),
                array('status' => 404)
            );
        }

        $step = new Wpfnl::$instance->step_store();
        $step->read($step_id);

        $funnel_id = $step->get_funnel_id();
        $delete_result = $step->delete($step_id);
        $funnel = Wpfnl::$instance->funnel_store;
        $funnel->read($funnel_id);

        if ($delete_result) {
            return $this->prepare_wp_success_response('Step deleted successfully', 200);
        }

        $response = $this->prepare_wp_error_response(
            'rest_delete_failure',
            __('Failed to delete step.', 'wpfnl'),
            array('status' => 500)
        );

        return $response;
    }

    /**
     * Response for deleting a conditional step.
     *
     * @param int $step_id The ID of the conditional step to be deleted.
     *
     * @return array The response indicating the success of the deletion.
     *
     * @since 2.7.9
     */
    private function conditional_step_response($step_id)
    {
        if (!$step_id) {
            return $this->prepare_wp_error_response(
                'rest_invalid_stepID',
                __('Invalid Step ID.', 'wpfnl'),
                array('status' => 404)
            );
        }

        return $this->prepare_wp_success_response('Conditional step deleted successfully', 200);
    }


    /**
     * Prepare a single setting object for response.
     *
     * @param object $item Setting object.
     * @param WP_REST_Request $request Request object.
     *
     * @return \WP_REST_Response $response Response data.
     * @since  1.0.0
     */
    public function prepare_item_for_response($item, $request)
    {
        $data = $this->add_additional_fields_to_object($item, $request);
        return rest_ensure_response($data);
    }

    /**
     * Import wp funnel steps from remote servers.
     *
     * @param array $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function import_step($payload)
    {
        $manager_class = new Manager();
        $source = $manager_class->get_source(!empty($payload['source']) ? $payload['source'] : 'remote');
        return $source->import_step($payload);
    }


    /**
     * Update conditional status for a step.
     *
     * This function updates the conditional status for a specific step based on the provided
     * step ID and status in the request. It checks for required parameters, sanitizes input,
     * and updates the corresponding post meta with the new status.
     *
     * @param WP_REST_Request $request The REST request object containing parameters.
     *
     * @return \WP_REST_Response The REST response indicating the success of the operation.
     * @since 2.9.0
     *
     */
    public function update_conditional_status($request)
    {
        $required_params = array('stepId', 'status');

        // Check if all required parameters are present in the request.
        foreach ($required_params as $param) {
            if (!$request->has_param($param)) {
                return rest_ensure_response($this->prepare_wp_error_response(
                    __("Required parameter '$param' is missing.", 'wpfnl'), 400
                ));
            }
        }

        // Get the step ID and status from the request.
        $step_id = sanitize_text_field($request['stepId']);
        $status = sanitize_text_field($request['status']);

        if (!$step_id) {
            return rest_ensure_response($this->prepare_wp_error_response(
                __("Required parameter step id is empty.", 'wpfnl'), 400
            ));
        }

        // Update the conditional status post meta.
        update_post_meta($step_id, '_wpfnl_maybe_enable_condition', $status);

        $funnel_id = get_post_meta($step_id, '_funnel_id', true);
        $reconfigureSettings = get_post_meta($funnel_id, '_wpfnl_reconfigurable_condition_data', true);
        if (is_array($reconfigureSettings) && !empty($reconfigureSettings)) {
            $key = array_search($step_id, array_column($reconfigureSettings, 'step_id'));
            if (false !== $key) {
                unset($reconfigureSettings[$key]);
                update_post_meta($funnel_id, '_wpfnl_reconfigurable_condition_data', $reconfigureSettings);
            }
        }

        // Return a REST response indicating success.
        return rest_ensure_response(['success' => true]);
    }


    /**
     * Get conditional status and conditions for a step.
     *
     * This function retrieves the conditional status and associated conditions for a specific step
     * based on the provided step ID in the request. It checks for required parameters, sanitizes input,
     * and returns the conditional status, along with the array of conditions.
     *
     * @param WP_REST_Request $request The REST request object containing parameters.
     *
     * @return \WP_REST_Response The REST response containing the conditional status and conditions.
     * @since 2.9.0
     *
     */
    public function get_conditions($request)
    {
        $required_params = array('stepId');

        // Check if all required parameters are present in the request.
        foreach ($required_params as $param) {
            if (!isset($request[$param])) {
                return rest_ensure_response($this->prepare_wp_error_response(
                    __("Required parameter '$param' is missing.", 'wpfnl'), 400
                ));
            }
        }

        // Get the step ID from the request.
        $step_id = sanitize_text_field($request['stepId']);

        if (!$step_id) {
            return rest_ensure_response($this->prepare_wp_error_response(
                __("Required parameter step id is empty.", 'wpfnl'), 400
            ));
        }

        // Retrieve the conditional status from the post meta.
        $status = get_post_meta($step_id, '_wpfnl_maybe_enable_condition', true);

        // Prepare the response array.
        $response = [
            'success' => true,
        ];

        // Set default status if not available.
        if (!$status) {
            $status = 'no';
        }
        $response['status'] = $status;

        // Retrieve step conditions from post meta.
        $conditions = get_post_meta($step_id, '_wpfnl_step_conditions', true);

        // Ensure conditions are available as an array.
        if (!is_array($conditions) || !$conditions) {
            $conditions = [];
        }
        $response['conditions'] = $conditions;


        $afterCondition = get_post_meta($step_id, '_wpfnl_next_step_after_condition', true);
        if (!is_array($afterCondition) || !$afterCondition) {
            $afterCondition = [];
        }

        $response['afterCondition'] = $afterCondition;

        // Return a REST response with the status and conditions.
        return rest_ensure_response($response);
    }


    /**
     * Save conditional status and conditions for a step.
     *
     * This function handles the saving of conditional status and associated conditions for a specific step
     * based on the provided step ID in the request. It checks for required parameters, sanitizes input,
     * and updates the post meta with the conditions and after-condition values.
     *
     * @param WP_REST_Request $request The REST request object containing parameters.
     *
     * @return \WP_REST_Response The REST response indicating the success of the operation.
     * @since 2.9.0
     *
     */
    public function save_condition($request)
    {
        $required_params = array('stepId', 'conditions');

        // Check if all required parameters are present in the request.
        foreach ($required_params as $param) {
            if (!isset($request[$param])) {
                return rest_ensure_response($this->prepare_wp_error_response(
                    __("Required parameter '$param' is missing.", 'wpfnl'), 400
                ));
            }
        }

        $step_id = sanitize_text_field($request['stepId']);
        $conditions = filter_var_array($request['conditions']);

        $afterCondition = filter_var_array(isset($request['afterCondition']) ? $request['afterCondition'] : []);

        if (!$step_id) {
            return rest_ensure_response($this->prepare_wp_error_response(
                __("Required parameter step id is empty.", 'wpfnl'), 400
            ));
        }

        // Update post meta with the conditions and after-condition values.
        update_post_meta($step_id, '_wpfnl_step_conditions', $conditions);
        update_post_meta($step_id, '_wpfnl_next_step_after_condition', $afterCondition);

        // Prepare the response array.
        $response = [
            'success' => true,
        ];

        $funnel_id = get_post_meta($step_id, '_funnel_id', true);
        $reconfigureSettings = get_post_meta($funnel_id, '_wpfnl_reconfigurable_condition_data', true);
        if (is_array($reconfigureSettings) && !empty($reconfigureSettings)) {
            $key = array_search($step_id, array_column($reconfigureSettings, 'step_id'));
            if (false !== $key) {
                unset($reconfigureSettings[$key]);
                update_post_meta($funnel_id, '_wpfnl_reconfigurable_condition_data', $reconfigureSettings);
            }
        }

        // Return a REST response indicating the success of the operation.
        return rest_ensure_response($response);
    }


    /**
     * Paste a step and all its data to create a new step.
     * This function creates a new step by duplicating an existing step and updating the post meta with the new step information.
     *
     * @param WP_REST_Request $request The REST request object containing parameters.
     *
     * @return \WP_REST_Response The REST response indicating the success of the operation.
     *
     * @since 3.4.16
     */
    public function paste_step($request){
        $required_params = array('stepId','funnelId');

        // Check if all required parameters are present in the request.
        foreach ($required_params as $param) {
            if (!isset($request[$param])) {
                return rest_ensure_response(
                    $this->prepare_wp_error_response( 400,
                        sprintf(__("Required parameter '%s' is missing.", 'wpfnl'), $param), ''
                    )
                 );
            }
        }
        $parent_step_id = sanitize_text_field($request['stepId']);
        $funnel_id = sanitize_text_field($request['funnelId']);
        // Clone the parent step to create a new step.
        $response = $this->clone_parent_step( $parent_step_id, $funnel_id );
        if( !$response ){
            return rest_ensure_response($this->prepare_wp_error_response( 400,
                __("Failed to duplicate a step", 'wpfnl'), ''
            ));
        }
        $step_id = isset($response['step_id']) ? $response['step_id'] : '';
        $step = isset($response['step']) ? $response['step'] : '';
        // Duplicate step metadata for this step.
        $this->duplicate_all_meta( $step, $parent_step_id, $step_id, $funnel_id );

        $builder = Wpfnl_functions::get_builder_type();
        // Clear elementor cache
        $this->clear_elementor_cache( $step_id, $builder );
        // Get the step view link with UTM
        $view_link = $this->get_step_view_link_with_utm( $step_id, $funnel_id );
        /**
         * Fires after duplicating an A/B testing step in WP Funnels.
         *
         * @param int    $step_id The ID of the duplicated step.
         * @param string $builder The name of the builder used for the duplication.
         */
        do_action('wpfunnels_after_ab_testing_duplicate', $step_id, $builder);
        /**
         * Fires after a step is duplicated in a funnel.
         *
         * @param int $funnel_id The ID of the funnel.
         * @param int $step_id   The ID of the duplicated step.
         */
        do_action('wpfunnels/after_step_duplicate', $funnel_id, $step_id );
        $response = $this->prepare_response_for_paste_step( $step_id, $response, $view_link );
        if( !$response ){
            return rest_ensure_response($this->prepare_wp_error_response( 400,
                __("Failed to duplicate a step", 'wpfnl'), ''
            ));
        }
        return $response;
    }

    /**
     * Prepare API response for paste step.
     *
     * @param int $step_id The ID of the step.
     * @param array $response The response data.
     * @param string $view_link The view link of the step.
     *
     * @return array The response data.
     *
     * @throws \WPFunnels\TemplateLibrary\Wpfnl_Source_Remote
     *
     * @since 3.4.16
     */
    public function prepare_response_for_paste_step( $step_id, $response, $view_link ){

        if( !$step_id ){
            return false;
        }

        // Create an instance of remote class.
        $remote = new \WPFunnels\TemplateLibrary\Wpfnl_Source_Remote();

        return [
            'success' 		=> true,
            'stepID' 		=> $step_id,
            'step_name' 	=> isset($response['title']) ? $response['title'] : '',
            'step_type' 	=> isset($response['step_type']) ? $response['step_type'] : '',
			'stepEditLink'	=> get_edit_post_link($step_id),
			'stepViewLink'	=> $view_link,
            'abTestingSettingsData'=> $remote->get_default_start_setting($step_id),
        ];
    }

    /**
     * Create a new step by duplicating an existing step.
     *
     * @param int $parent_step_id The ID of the parent step.
     * @param int $funnel_id The ID of the funnel.
     *
     * @return array The response data.
     *
     * @since 3.4.16
     */
    public function clone_parent_step( $parent_step_id, $funnel_id ) {
        if( !$parent_step_id || !$funnel_id ) {
            return false;
        }

        $title = get_the_title($parent_step_id);
        $page_template = get_post_meta($parent_step_id, '_wp_page_template', true);
        $step_type = get_post_meta($parent_step_id, '_step_type', true);
        $post_content = get_post_field('post_content', $parent_step_id);

        // Create an instance of the step store.
        $step = new \WPFunnels\Data_Store\Wpfnl_Steps_Store_Data();

        $step_id = $step->create_step($funnel_id, $title, $step_type, $post_content, true);

        if( !$step_id ){
            return false;
        }
        // Update the step meta data.
        $step->update_meta($step_id, '_funnel_id', $funnel_id);

        return [
            'step'          => $step,
            'step_id'       => $step_id,
            'title'         => $title,
            'step_type'     => $step_type,
            'page_template' => $page_template,
        ];
    }


    /**
     * Duplicate all the meta data for a step.
     *
     * @param object $step The step object.
     * @param int $parent_step_id The ID of the parent step.
     * @param int $step_id The ID of the step.
     * @param int $funnel_id The ID of the funnel.
     *
     * @since 3.4.16
     */
    public function duplicate_all_meta( $step, $parent_step_id, $step_id, $funnel_id ) {
        if( !$parent_step_id || !$step_id || !$funnel_id ) {
            return;
        }

        // Create an instance of the funnel store.
        $funnel = new \WPFunnels\Data_Store\Wpfnl_Funnel_Store_Data();
		$funnel->duplicate_all_meta( $parent_step_id, $step_id, array('_funnel_id','_is_duplicate','wpfnl_mint_automation_id') );

        /**
         * Save the new step information on funnel data and funnel identifier.
         * This is required to show steps in funnel canvas
         */

        $funnel->update_step_id_in_funnel_data_and_identifier($parent_step_id, $step_id, $funnel_id);
        $step->update_meta($step_id, '_is_duplicate', 'yes');
    }

    /**
     * Clear elememtor cache and remove the cache for the step.
     * This is required to clear the cache for the step when it is duplicated.
     *
     * @param int $step_id The ID of the step.
     * @param string $builder The name of the builder.
     *
     * @since 3.4.16
     */
    public function clear_elementor_cache( $step_id, $builder ) {
        if( !$step_id || 'elementor' === $builder) {
            return;
        }

        $step_edit_link =  get_edit_post_link($step_id);

        $step_edit_link = str_replace(['&amp;', 'edit'], ['&', 'elementor'], $step_edit_link);
        if ( is_plugin_active( 'elementor/elementor.php' ) && class_exists( '\Elementor\Plugin' ) ) {
            Plugin::$instance->files_manager->clear_cache(); // Clearing cache of Elementor CSS.
        }

    }

    /**
     * Get step view link with UTM.
     *
     * @param int $step_id The ID of the step.
     * @param int $funnel_id The ID of the funnel.
     *
     * @return string The view link of the step.
     *
     * @since 3.4.16
     */
    public function get_step_view_link_with_utm( $step_id, $funnel_id ) {
        if( !$step_id ||!$funnel_id ) {
            return '';
        }

        $view_link    = get_post_permalink( $step_id );
        $utm_settings   = Wpfnl_functions::get_funnel_utm_settings($funnel_id);

	    if ( is_array($utm_settings) && isset($utm_settings[ 'utm_enable' ]) && 'on' === $utm_settings[ 'utm_enable' ] ) {
		    unset( $utm_settings[ 'utm_enable' ] );
		    $view_link = add_query_arg( $utm_settings, $view_link );
		    $view_link = strtolower( $view_link );
	    }

        return $view_link;
    }
}
