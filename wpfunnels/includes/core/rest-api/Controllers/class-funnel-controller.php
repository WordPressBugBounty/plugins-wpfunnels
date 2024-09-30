<?php
/**
 * Funnel controller
 *
 * @package WPFunnels\Rest\Controllers
 */

namespace WPFunnels\Rest\Controllers;

use Error;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPFunnels\Wpfnl_functions;
use Wpfnl_Controller_Type_Factory;
use WPFunnels\Modules\Admin\Funnel\Module;
use WPFunnels\Wpfnl;
use WPFunnels\Migration\Migration;

class FunnelController extends Wpfnl_REST_Controller
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
	protected $rest_base = 'funnel-control';

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
		register_rest_route($this->namespace, '/' . $this->rest_base . '/saveFunnel/', [
			[
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [
					$this,
					'save_funnel_data'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/getThankyouData/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_thankyou_data'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/saveConditionalNode/', [
			[
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [
					$this,
					'save_conditional_node'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);
		register_rest_route($this->namespace, '/' . $this->rest_base . '/getConditionalNode/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_conditional_node'
				],
				'permission_callback' => [
					$this,
					'get_items_permissions_check'
				],
			],
		]);
		register_rest_route($this->namespace, '/' . $this->rest_base . '/getFunnel/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_funnel_data'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/getStepType/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_step_type'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/getFunnelInfo/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_funnel_info'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);


		register_rest_route($this->namespace, '/' . $this->rest_base . '/exportFunnel/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'export_funnel'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/getallfunnels/', array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_all_funnels'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			),
		));

		register_rest_route($this->namespace, '/' . $this->rest_base . '/getStepGeneralInfo/', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_step_general_info'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/steps/', array(
			'args' => array(
				'funnel_id' => array(
					'description' => __('Funnel ID.', 'wpfnl'),
					'type' => 'string',
				),
				'step_id' => array(
					'description' => __('Step ID.', 'wpfnl'),
					'type' => 'string',
				)
			),
			array(
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [
					$this,
					'update_step_meta'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			)
		));


		register_rest_route($this->namespace, '/' . $this->rest_base . '/get_gbf_data/(?P<funnel_id>\d+)', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_GBF_data'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/get-settings/(?P<step_id>[\d]+)', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [
					$this,
					'get_ab_settings'
				],
				'permission_callback' => [
					$this,
					'update_items_permissions_check'
				],
			],
		]);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<funnel_id>\d+)/funnel-name-change',
			array(
				array(
					'methods' => \WP_REST_Server::EDITABLE,
					'callback' => array(
						$this,
						'funnel_name_change',
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
			'/' . $this->rest_base . '/wpfnl-get-funnel-settings',
			array(
				array(
					'methods' => \WP_REST_Server::EDITABLE,
					'callback' => array(
						$this,
						'wpfnl_get_funnel_settings',
					),
					'args' => array(
						'funnel_id' => array(
							'required' => true,
							'type' => array('integer', 'string'),
							'sanitize_callback' => 'absint',
						),
					),
					'permission_callback' => array(
						$this,
						'update_items_permissions_check',
					)
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk-delete-funnel',
			array(
				array(
					'methods' => \WP_REST_Server::EDITABLE,
					'callback' => array(
						$this,
						'delete_marked_funnels',
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
			'/' . $this->rest_base . '/bulk-restore-funnel',
			array(
				array(
					'methods' => \WP_REST_Server::EDITABLE,
					'callback' => array(
						$this,
						'restore_marked_funnels',
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
			'/' . $this->rest_base . '/bulk-trash-funnel',
			array(
				array(
					'methods' => \WP_REST_Server::EDITABLE,
					'callback' => array(
						$this,
						'trash_marked_funnels',
					),
					'permission_callback' => array(
						$this,
						'update_items_permissions_check',
					),
				),
			)
		);

	}

	/**
	 * Check if funnel data exists or not
	 **/
	public function get_all_funnels()
	{
		$args = array(
			'post_type' => 'wpfunnel_steps',
			'numberposts' => -1
		);
		$funnels = get_posts($args);

		if ($funnels) {
			if (count($funnels) > 0) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Save thankyou data
	 */
	public function get_thankyou_data($request)
	{
		$step_id = $request['step_id'];
		$data = array();
		$data['_wpfnl_thankyou_order_overview'] = get_post_meta($step_id, '_wpfnl_thankyou_order_overview', true) ? get_post_meta($step_id, '_wpfnl_thankyou_order_overview', true) : 'on';
		$data['_wpfnl_thankyou_order_details'] = get_post_meta($step_id, '_wpfnl_thankyou_order_details', true) ? get_post_meta($step_id, '_wpfnl_thankyou_order_details', true) : 'on';
		$data['_wpfnl_thankyou_billing_details'] = get_post_meta($step_id, '_wpfnl_thankyou_billing_details', true) ? get_post_meta($step_id, '_wpfnl_thankyou_billing_details', true) : 'on';
		$data['_wpfnl_thankyou_shipping_details'] = get_post_meta($step_id, '_wpfnl_thankyou_shipping_details', true) ? get_post_meta($step_id, '_wpfnl_thankyou_shipping_details', true) : 'on';
		$data['_wpfnl_thankyou_is_custom_redirect'] = get_post_meta($step_id, '_wpfnl_thankyou_is_custom_redirect', true) ? get_post_meta($step_id, '_wpfnl_thankyou_is_custom_redirect', true) : 'off';
		$data['_wpfnl_thankyou_is_direct_redirect'] = get_post_meta($step_id, '_wpfnl_thankyou_is_direct_redirect', true) ? get_post_meta($step_id, '_wpfnl_thankyou_is_direct_redirect', true) : 'off';
		$data['_wpfnl_thankyou_set_time'] = get_post_meta($step_id, '_wpfnl_thankyou_set_time', true) ? get_post_meta($step_id, '_wpfnl_thankyou_set_time', true) : '';
		$data['_wpfnl_thankyou_custom_redirect_url'] = get_post_meta($step_id, '_wpfnl_thankyou_custom_redirect_url', true) ? get_post_meta($step_id, '_wpfnl_thankyou_custom_redirect_url', true) : '';
		return $data;
	}

	/**
	 * Get conditional node
	 *
	 * @param WP_REST_Request $request request.
	 *
	 * @return array|WP_Error
	 */
	public function get_conditional_node($request)
	{
		$funnel_id = $request['funnel_id'];

		$steps = Wpfnl_functions::get_steps($funnel_id);
		$optin_step = [];
		if (is_array($steps)) {
			foreach ($steps as $step) {
				if ('landing' === $step['step_type'] || 'custom' === $step['step_type']) {
					$optin_step[] = [
						'name' => __('Optin form (', 'wpfnl') . $step['name'] . ')',
						'value' => __('optin_', 'wpfnl') . $step['id'],
					];
				}
			}
		}
		$response = array(
			'status' => 'error',
		);
		if (!empty($optin_step)) {
			$response['status'] = 'success';
			$response['optinStep'] = $optin_step;
		}

		return $this->prepare_item_for_response($response, $request);
	}

	/**
	 * Save conditional node
	 *
	 * @param string $request request.
	 *
	 * @return array|WP_Error
	 */
	public function save_conditional_node($request)
	{
		$funnel_id = $request['funnel_id'];
		$condition_data = $request['condition_data'];
		$node_identifier = $request['node_identifier'];
		update_post_meta($funnel_id, $node_identifier, $condition_data);
		$response = array(
			'status' => true,
		);
		return $this->prepare_item_for_response($response, $request);
	}

	/**
	 * Get step_type.
	 *
	 * @param string $request request.
	 *
	 * @return array|WP_Error
	 */
	public function get_step_type($request)
	{
		$step_type = '';
		$step_id = $request['step_id'];
		$step_type = get_post_meta($step_id, '_step_type', true);
		return $step_type;
	}


	/**
	 * Get step_type.
	 *
	 * @param string $request request.
	 *
	 * @return array|WP_Error
	 */
	public function get_step_general_info($request)
	{
		$step_type 			= '';
		$step_id 			= isset($request['step_id']) ? $request['step_id'] : null;
		$step_type 			= get_post_meta($step_id, '_step_type', true);
		$custom_script 		= get_post_meta($step_id, '_wpfnl_custom_script', true);

		$response = array(
			'step_type' 		=> $step_type,
			'step_title' 		=> get_the_title($step_id),
			'step_view_link' 	=> get_post_permalink($step_id),
			'custom_script' 	=> html_entity_decode($custom_script)
		);
		return $this->prepare_item_for_response($response, $request);
	}


	/**
	 * Get the funnel title and link
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_funnel_info($request)
	{
		$funnel_id = $request['funnel_id'];
		$title = html_entity_decode(get_the_title($funnel_id));
		$steps = get_post_meta($funnel_id, '_steps_order', true);
		$response['success'] = false;
		if ($steps) {
			if (isset($steps[0]) && $steps[0]['id']) {
				$response['link'] = get_post_permalink($steps[0]['id']);
				$response['title'] = $title;
				$utm_params = $this->get_utm_params($funnel_id);
				if ($utm_params != '') {
					$response['link'] = $response['link'] . $utm_params;
					$response['title'] = $title;
				}
				$response['success'] = true;
			}
		}
		return $this->prepare_item_for_response($response, $request);
	}

	/**
	 * Retrieves the funnel data based on the provided request.
	 *
	 * @param mixed $request The request data.
	 * @return WP_Rest_Response The prepared funnel data response.
	 *
	 * @since 2.7.17
	 */
	public function get_funnel_data($request)
	{
		$funnel_id = isset($request['funnel_id']) ? $request['funnel_id'] : null;
		$response = [
			'status' => 'error',
			'success' => false
		];

		if (!$funnel_id) {
			return $this->prepare_item_for_response($response, $request);
		}

		if( version_compare(WPFNL_VERSION, '3.0.0', '>=') && 'yes' !== get_post_meta( $funnel_id, 'wpfnls_is_newui_migrated', true ) ){
			$migration = new Migration();
			$funnel_data = get_post_meta($funnel_id, 'funnel_data', true) ? get_post_meta($funnel_id, 'funnel_data', true) : get_post_meta($funnel_id, '_funnel_data', true);
			$migration->update_funnel_data($funnel_id, $funnel_data);
			update_post_meta($funnel_id, 'wpfnls_is_newui_migrated', 'yes');
		}
		$response = $this->prepare_funnel_data_response($funnel_id);
		return $this->prepare_item_for_response($response, $request);
	}


	/**
	 * Prepares the response data for the funnel data based on the provided parameters.
	 *
	 * @param int $funnel_id The ID of the funnel.
	 * @param mixed $request The request data.
	 * @return array The prepared response data.
	 *
	 * @since 2.7.17
	 */
	private function prepare_funnel_data_response($funnel_id)
	{
		$response = [
			'status' => 'error',
			'success' => false
		];

		$is_new_ui_data = get_post_meta($funnel_id, 'wpfnls_is_newui_migrated', true);
		$funnel_data = 'yes' === $is_new_ui_data ? get_post_meta($funnel_id, '_funnel_data', true) : get_post_meta($funnel_id, 'funnel_data', true);
		$funnel_identifier = get_post_meta($funnel_id, 'funnel_identifier', true);
		$_steps_order = get_post_meta($funnel_id, '_steps', true);
		$_steps_order = is_array($_steps_order) ? $_steps_order : [];
		$status = get_post_status($funnel_id);
		$title = html_entity_decode(get_the_title($funnel_id));
		$first_step_id = Wpfnl_functions::get_first_step($funnel_id);

		// Notice: We recently updated the architecture of our funnel template showcase site to version 3.1.3.
		// If user's site's template data isn't synchronized with the latest data from our showcase site,
		// user may encounter issues importing templates. Unfortunately, this situation is unavoidable.
		// To ensure a smooth experience, we'll display a notice if user's site's template data needs updating.
		$is_imported_funnel = get_post_meta($funnel_id, '_is_imported', true);
		$show_template_sync_notice = false;
		if ('yes' === $is_new_ui_data && 'yes' === $is_imported_funnel && !$funnel_data) {
			$show_template_sync_notice = true;
		}

		/**
		 * Fires to modify funnel view link for A/B testing
		 *
		 * @param string $link
		 * @param int $step_id
		 * @param int $funnel_id
		 *
		 * @return string $link
		 * @since 1.7.8
		 */
		$link = apply_filters('wpfunnels/modify_funnel_view_link', get_post_permalink($first_step_id), $first_step_id, $funnel_id);
		$response['title'] = $title;

		$utm_settings = Wpfnl_functions::get_funnel_utm_settings($funnel_id);
		$view_link = !empty($response['link']) ? $response['link'] : $link;

		if ($utm_settings['utm_enable'] == 'on') {
			unset($utm_settings['utm_enable']);
			$view_link = add_query_arg($utm_settings, $view_link);
			$view_link = strtolower($view_link);
			$utm_settings['utm_enable'] = 'on';
		}

		$response['success'] = true;
		$step_order_data = self::get_steps_order_data($_steps_order);
		$response['steps_order'] = $step_order_data['steps_order'];
		$response['is_ob'] = $step_order_data['is_ob'];

		$init_position = [
			'pos_x' => 383,
			'pos_y' => 143
		];

		$funnel_data = Wpfnl_functions::remove_disconnected_addstep_node($funnel_data, $funnel_id);

		if ($funnel_data) {
			$response = [
				'status' => 'success',
				'funnel_data' => $funnel_data,
				'funnel_identifier' => $funnel_identifier,
				'steps_order' => isset ($response['steps_order']) ? $response['steps_order'] : [],
				'funnel_status' => $status,
				'title' => $title,
				'link' => $view_link,
				'reset_funnel' => get_post_meta($funnel_id, '_wpfnl_is_reset', true),
				'is_ob' => isset ($response['is_ob']) ? $response['is_ob'] : false
			];

			if (empty($funnel_data['drawflow']['Home']['data'])) {
				$data = $this->get_default_add_step_node_data($init_position);
				$response['status'] = 'scratch-funnel';
				$response['funnel_data'] = $data['data'];
				$response['node_identifier'] = $data['node_identifier'];
			}

			$response['funnel_data'] = $this->update_step_id_in_funnel_data_and_identifier($response['funnel_data'], $utm_settings);

			/**
			 * Fires to update funnel data response in case of A/B Testing
			 *
			 * @param array $response
			 *
			 * @return array $response
			 **@since 1.7.8
			 */
			$response = apply_filters('wpfunnels/update_funnel_data_response', $response);
			if (!empty($response['ab_data'])) {
				$response['ab_data'] = $this->update_step_view_link_with_utm_params($response['ab_data'], $utm_settings);
			}
		} elseif ($title && $status) {

			$data = $this->get_default_add_step_node_data($init_position);
			$response = array(
				'status' => 'scratch-funnel',
				'title' => $title,
				'funnel_status' => $status,
				'funnel_data' => $data['data'],
				'reset_funnel' => get_post_meta($funnel_id, '_wpfnl_is_reset', true),
				'node_identifier' => $data['node_identifier'],
				'show_template_sync_notice' => $show_template_sync_notice,
			);
		}


		$response['first_node_id'] = $this->get_first_step_node($first_step_id, $response['funnel_data']);
		$response['conditional_data'] = $this->get_conditional_step($response['funnel_data']);
		return $response;
	}


	/**
	 * Update funnel identifier
	 *
	 * @param $remote_step
	 * @param $new_step
	 * @param $args
	 *
	 * @return void
	 */
	public function update_step_id_in_funnel_data_and_identifier($funnel_flow_array, $utm_settings)
	{

		if (!isset($funnel_flow_array['drawflow']['Home']['data']) || !is_array($funnel_flow_array['drawflow']['Home']['data'])) {
			return $funnel_flow_array;
		}
		$isUtm = false;
		if (isset($utm_settings['utm_enable']) && $utm_settings['utm_enable'] == 'on') {
			$isUtm = true;
			unset($utm_settings['utm_enable']);
		}

		// Loop through each step in funnel
		foreach ($funnel_flow_array['drawflow']['Home']['data'] as &$step) {

			// Find corresponding step in step id data
			if ('conditional' !== $step['data']['step_type'] && 'addstep' !== $step['data']['step_type'] ) {

				$step_view_link = get_post_permalink($step['data']['step_id']);
				$step_view_link = $isUtm ? add_query_arg($utm_settings, $step_view_link) : $step_view_link;
				// Check if step id is not in used_step_types array
				$post_edit_link = base64_encode(get_edit_post_link($step['data']['step_id']));
				$post_view_link = base64_encode($step_view_link);

				$step['data']['step_edit_link'] = $post_edit_link;
				$step['data']['step_view_link'] = $post_view_link;
			}
		}
		return $funnel_flow_array;
	}


	/**
	 * get conditional steps with validity checking
	 *
	 * @param $funnel_flow_array
	 *
	 * @return array
	 *
	 * @since 3.4.13
	 */
	public function get_conditional_step($funnel_flow_array){
		if (!isset($funnel_flow_array['drawflow']['Home']['data']) || !is_array($funnel_flow_array['drawflow']['Home']['data'])) {
			return [];
		}

		$condition_data = [];
		foreach ($funnel_flow_array['drawflow']['Home']['data'] as &$step) {

			// Find corresponding step in step id data
			if ('conditional' !== $step['data']['step_type'] && 'addstep' !== $step['data']['step_type'] ) {
				$step_id = $step['data']['step_id'];
				$is_condition_enabled = get_post_meta($step_id, '_wpfnl_maybe_enable_condition', true);
				if( 'yes' === $is_condition_enabled ){
					$data = [
						'step_id' => $step_id,
						'data'	  => 'no'
					];
					$conditions = get_post_meta($step_id, '_wpfnl_step_conditions', true);
					if( is_array($conditions) && !empty($conditions) ){
						$data['data'] = 'yes';
					}
					array_push($condition_data, $data);
				}
			}
		}
		return $condition_data;
	}

	/**
	 * Updates A/B test variations' step view links with UTM parameters.
	 *
	 * This method takes A/B test data, specifically variations, and appends UTM parameters
	 * to the step view links of each variation. It does so based on the provided UTM settings
	 * if UTM tracking is enabled.
	 *
	 * @param array $ab_data An array containing A/B test data with variations.
	 * @param array $utm_settings An array of UTM parameters and their values.
	 *
	 * @return array The modified A/B test data with UTM parameters appended to step view links.
	 * @since 2.8.14
	 */
	private function update_step_view_link_with_utm_params($ab_data, $utm_settings)
	{
		if (!empty($ab_data) && !empty($utm_settings['utm_enable']) && 'on' === $utm_settings['utm_enable']) {
			unset($utm_settings['utm_enable']);
			foreach ($ab_data as $ab_key => $data) {
				$variations = $data['data']['start_settings']['variations'] ?? [];
				if (!empty($variations)) {
					foreach ($variations as $var_key => $variation) {
						if (!empty($variation['step_view_link'])) {
							$ab_data[$ab_key]['data']['start_settings']['variations'][$var_key]['step_view_link'] = add_query_arg($utm_settings, $variation['step_view_link']);
						}
					}
				}
			}
		}
		return $ab_data;
	}

	/**
	 * Get the first step Node
	 */
	public function get_first_step_node($first_step_id, $funnel_data)
	{
		$data = isset($funnel_data['drawflow']['Home']['data']) ? $funnel_data['drawflow']['Home']['data'] : [];
		foreach ($data as $step_data) {
			if (isset($step_data['data']['step_id']) && $step_data['data']['step_id'] == $first_step_id) {
				return $step_data['id'];
			}
		}
	}


	/**
	 * Get default add step node data and identifire for canvas
	 *
	 * @return Array Returns an array containing the default add step node data and identifier
	 * @since 2.8.0
	 *
	 */
	public function get_default_add_step_node_data($init_position)
	{
		$node_identifier = rand() * (500 - 100) + 100;
		$data = [
			'drawflow' => [
				'Home' => [
					'data' => [
						1 => [
							'id' => 1,
							'name' => 'addstep',
							'data' => [
								'step_type' => 'addstep',
								'node_identifier' => $node_identifier
							],
							'class' => 'addstep',
							'html' => 'addstep' . $node_identifier,
							'typenode' => 'vue',
							'inputs' => [
								'input_1' => [
									'connections' => []
								]
							],
							'outputs' => [],
							'pos_x' => isset($init_position['pos_x']) ? $init_position['pos_x'] : 383,
							'pos_y' => isset($init_position['pos_y']) ? $init_position['pos_y'] : 143,
						]
					]
				]
			]
		];
		return [
			'data' => $data,
			'node_identifier' => $node_identifier,
		];
	}

	/**
	 * Get formatted steps order data.
	 *
	 * Formats the steps order data by populating additional fields and determining if any step is an order bump.
	 *
	 * @param array $steps_order The array of funnel steps order.
	 * @return array             Formatted steps order data with additional fields and order bump flag.
	 *
	 * @since 2.7.17
	 */
	private static function get_steps_order_data($steps_order)
	{
		$is_order_bump = false;
		$formatted_steps_order = [];
		foreach ($steps_order as $step) {
			$step_id = isset($step['id']) ? $step['id'] : null;
			$_temp_step = $step;
			$_temp_step['visit'] = 0;
			$_temp_step['conversion'] = 0;
			$_temp_step['name'] = get_the_title($step_id);
			$_step_type = get_post_meta($step_id, '_step_type', true);
			$should_assign_product = in_array($_step_type, ['checkout', 'upsell', 'downsell']) && !get_post_meta($step_id, '_wpfnl_' . $_step_type . '_products', true);

			if ('checkout' === $_step_type) {
				$is_order_bump = self::is_order_bump($step_id);
			}

			$_temp_step['should_assign_product'] = $should_assign_product;

			/**
			 * Fires to add Mail Mint automation data
			 *
			 * @param array $step
			 * @param int $step_id
			 * @since 2.7.0
			 */
			$formatted_steps_order[] = apply_filters('wpfunnels/step_data', $_temp_step, $step_id);
		}

		return [
			'steps_order' => $formatted_steps_order,
			'is_ob' => $is_order_bump
		];
	}

	/**
	 * Checks if a step in the funnel is an order bump.
	 * Retrieves the order bump settings for the specified step and determines if it is an order bump.
	 * @param array $steps_order The array of funnel steps.
	 * @param int $step_id The ID of the step to check.
	 *
	 *
	 * @return bool True if the step is an order bump, false otherwise.
	 * @since 2.7.17
	 *
	 */
	public static function is_order_bump($step_id)
	{
		$all_settings = get_post_meta($step_id, 'order-bump-settings', true) ?: [];
		$is_multiple = Wpfnl_functions::check_array_is_multidimentional($all_settings);

		if (!$is_multiple && $all_settings) {
			$all_settings['name'] = 'Order bump';
			$all_settings = Wpfnl_functions::migrate_order_bump($all_settings, $step_id);
		}

		if (is_array($all_settings) && count($all_settings) > 0) {
			$funnel_id = get_post_meta($step_id, '_funnel_id', true);
			$type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true) ?: 'wc';
			$class_object = Wpfnl_Controller_Type_Factory::build($type);
			if ($class_object) {
				$all_settings = $class_object->get_ob_settings($all_settings);
			}
		}


		if (count($all_settings)) {
			return true;
		}
		return false;
	}

	/**
	 * Get formatted funnel data
	 *
	 * @param $drawflow
	 *
	 * @return mixed
	 *
	 * @since 2.0.5
	 */
	private function get_formatted_funnel_data($drawflow)
	{
		if (isset($drawflow['drawflow']['Home']['data'])) {
			$drawflow_data = $drawflow['drawflow']['Home']['data'];
			foreach ($drawflow_data as $key => $data) {
				$step_data = $data['data'];
				$step_type = $step_data['step_type'];
				if ('conditional' !== $step_type) {
					$step_id = $step_data['step_id'];
					$funnel_id = Wpfnl_functions::get_funnel_id_from_step($step_id);
					if ('conditional' !== $step_type) {
						$edit_post_link = get_edit_post_link($step_id);
						$view_link = get_the_permalink($step_id);
						$utm_params = $this->get_utm_params($funnel_id);
						if ($utm_params != '') {
							$view_link = $view_link . $utm_params;
						}
						$title = get_the_title($step_id);
						$drawflow['drawflow']['Home']['data'][$key]['data'] = array(
							'step_edit_link' => base64_encode($edit_post_link),
							'step_id' => $step_id,
							'step_type' => $step_data['step_type'],
							'step_view_link' => base64_encode(rtrim($view_link, '/')),
							'step_name' => $title,
						);
					}
				}
			}
		}
		return $drawflow;
	}

	/**
	 * Save funnel data.
	 *
	 * @param string $request request.
	 *
	 * @return array|WP_Error
	 */
	public function save_funnel_data($request)
	{
		$funnel_id = $request['funnel_id'] ?? null;
		$funnel_json = $request['funnel_data'] ?? [];
		$funnel_identifier = $request['funnel_identifier'] ?? [];
		$should_update_steps_order = $request['should_update_steps_order'] ?? false;
		$should_update_steps = $request['should_update_steps'] ?? false;
		$funnel_data = [];
		$_steps = [];
		$response = ['success' => true, 'link' => home_url()];

		if ($funnel_json) {
			$funnel_data = $funnel_json;
			$steps = $funnel_data['drawflow']['Home']['data'];

			if (is_array($steps)) {
				foreach ($steps as $key => $step) {
					if ($step) {
						$node_data = $step['data'];
						if (isset($node_data["step_name"])) unset($node_data["step_name"]);
						$step['data'] = $node_data;
						$_steps[$key] = $step;

					}
				}
			}

		}
		$funnel_data['drawflow']['Home']['data'] = apply_filters('wpfunnels/modify_funnel_data', $_steps);
		update_post_meta($funnel_id, '_funnel_data', $funnel_data);
		update_post_meta($funnel_id, 'funnel_identifier', $funnel_identifier);

		if ($should_update_steps) {
			$steps = $this->get_steps($funnel_data);
			update_post_meta($funnel_id, '_steps', $steps);
			Wpfnl_functions::generate_first_step($funnel_id, $steps);
		}

		$_steps_order = $this->get_steps_order($funnel_data);
		$key = array_search('checkout', array_column($_steps_order, 'step_type'));
		if (false !== $key) {
			$funnel_type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
			if ('lead' === $funnel_type) {
				if (Wpfnl_functions::is_wc_active()) {
					update_post_meta($funnel_id, '_wpfnl_funnel_type', 'wc');
				} else {
					if (Wpfnl_functions::is_lms_addon_active()) {
						update_post_meta($funnel_id, '_wpfnl_funnel_type', 'lms');
					}
				}
			}
		}
		if ($should_update_steps_order) {
			$steps_order = array();
			foreach ($_steps_order as $_step) {
				if (count($_step)) {
					$steps_order[] = $_step;
				}
			}

			if (count($steps_order)) {
				update_post_meta($funnel_id, '_steps_order', $steps_order);

			} else {
				delete_post_meta($funnel_id, '_steps_order');
			}
		}

		$response['step_id'] = Wpfnl_functions::get_first_step($funnel_id);

		// Fallback for existing users' existing funnel
		// For new funnel, this condition should not trigger
		if (!$response['step_id']) {
			Wpfnl_functions::generate_first_step($funnel_id);
			$response['step_id'] = Wpfnl_functions::get_first_step($funnel_id);
		}

		$utm_settings = Wpfnl_functions::get_funnel_utm_settings($funnel_id);
		$view_link = get_post_permalink($response['step_id']);

		if (!empty($utm_settings['utm_enable']) && 'on' === $utm_settings['utm_enable']) {
			unset($utm_settings['utm_enable']);
			$view_link = add_query_arg($utm_settings, $view_link);
			$view_link = strtolower($view_link);
		}

		$response['link'] = esc_url($view_link);

		$response['funnel_id'] = $funnel_id;
		$response['success'] = true;
		$response['funnel_type'] = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
		$response = apply_filters('wpfunnels/update_funnel_link', $response);
		do_action('wpfunnels/after_save_funnel_data', $funnel_id);
		if (isset($request['mintSteps'])) {
			do_action('wpfunnels/save_mint_automation', $funnel_id, $request['mintSteps']);
		}
		Wpfnl_functions::generate_first_step($funnel_id);

		return rest_ensure_response($response);
	}


	/**
	 * Get steps
	 *
	 * @param $funnel_flow_data
	 *
	 * @return array
	 *
	 * @since 2.0.5
	 */
	private function get_steps($funnel_flow_data)
	{
		$drawflow = $funnel_flow_data['drawflow'];
		$steps = array();
		if (isset($drawflow['Home']['data'])) {
			$drawflow_data = $drawflow['Home']['data'];
			foreach ($drawflow_data as $key => $data) {
				$step_data = $data['data'];
				if ('conditional' !== $step_data['step_type'] && 'addstep' !== $step_data['step_type']) {
					$step_id = $step_data['step_id'];
					$step_type = $step_data['step_type'];
					$step_name = sanitize_text_field(get_the_title($step_data['step_id']));
					$steps[] = array(
						'id' => $step_id,
						'step_type' => $step_type,
						'name' => $step_name,
					);
				}
			}
		}
		return $steps;
	}


	/**
	 * Get steps order
	 *
	 * @param $funnel_flow_data
	 *
	 * @return array
	 *
	 * @since 2.0.5
	 */
	public function get_steps_order($funnel_flow_data)
	{
		$drawflow = $funnel_flow_data['drawflow'];
		$nodes = array();
		$step_order = array();
		$first_node_id = '';
		$start_node = array();


		if (isset($drawflow['Home']['data'])) {
			$drawflow_data = $drawflow['Home']['data'];

			/**
			 * If has only one step, that only step will be the first step, no conditions should be checked.
			 * just return the step order
			 */
			if (1 === count($drawflow_data)) {
				$node_id = array_keys($drawflow_data)[0];
				$data = $drawflow_data[$node_id];
				$step_data = isset($data['data']) ? $data['data'] : array();
				$step_type = isset($step_data['step_type']) ? $step_data['step_type'] : '';
				$step_id = isset($step_data['step_id']) ? $step_data['step_id'] : 0;
				$step_order[] = array(
					'id' => $step_id,
					'step_type' => $step_type,
					'name' => sanitize_text_field(get_the_title($step_id)),
				);
				return $step_order;

			}

			/**
			 * First we will find the first node (the node which has only output connection but no input connection will be considered as first node) and the list of nodes array which has the
			 * step information includes output connection and input connection and it will be stored on $nodes
			 */
			foreach ($drawflow_data as $key => $data) {
				$step_data = $data['data'];

				$step_type = $step_data['step_type'];
				$step_id = 'conditional' !== $step_type && 'addstep' !== $step_type ? $step_data['step_id'] : 0;
				if (
					(isset($data['outputs']['output_1']['connections']) && count($data['outputs']['output_1']['connections'])) ||
					(isset($data['inputs']['input_1']['connections']) && count($data['inputs']['input_1']['connections']))
				) {


					if ('conditional' === $step_type || 'addstep' === $step_type) {
						continue;
					}

					/**
					 * A starting node is a node which has only output connection but not any input connection.
					 * if the step is landing, then there should not be any input connection for this step. so we will only consider the output connection for landing only.
					 * for other step types (checkout, offer, thankyou), we will check if the step has any output connection and no input connection.
					 */
					if ('landing' === $step_type) {
						if (
							isset($data['outputs']['output_1']['connections']) && count($data['outputs']['output_1']['connections']) &&
							(isset($data['inputs']) && (count($data['inputs']) == 0 || (isset($data['inputs']['input_1']['connections']) && count($data['inputs']['input_1']['connections']) == 0)))
						) {
							$start_node = array(
								'id' => $step_id,
								'step_type' => $step_type,
								'name' => sanitize_text_field(get_the_title($step_id)),
							);
						}
					} else {
						if (
							isset($data['outputs']['output_1']['connections']) && count($data['outputs']['output_1']['connections']) &&
							(isset($data['inputs']['input_1']['connections']) && count($data['inputs']['input_1']['connections']) === 0)
						) {
							$start_node = array(
								'id' => $step_id,
								'step_type' => $step_type,
								'name' => sanitize_text_field(get_the_title($step_id)),
							);
						} else {
							$step_order[] = array(
								'id' => $step_id,
								'step_type' => $step_type,
								'name' => sanitize_text_field(get_the_title($step_id)),
							);
						}
					}
				}
			}

			$step_order = $this->array_insert($step_order, $start_node, 0);
		}
		return $step_order;
	}


	/**
	 * Array insert element on position
	 *
	 * @param $original
	 * @param $inserted
	 * @param int $position
	 *
	 * @return mixed
	 */
	private function array_insert(&$original, $inserted, $position)
	{
		array_splice($original, $position, 0, array($inserted));
		return $original;
	}


	/**
	 * Export funnel data
	 *
	 * @param $request
	 *
	 * @return false|string
	 *
	 * @since 1.0.0
	 */
	public function export_funnel($request)
	{
		$funnel_id = $request['funnel_id'];

		//===main array of data which will be downloaded as json file===//
		$data = array();

		$funnel_title = get_the_title($funnel_id);
		$funnel_meta = get_post_meta($funnel_id);

		//=== Added title and meta of current funnel post===//
		$data['title'] = $funnel_title;
		$data['meta'] = $funnel_meta;

		//=== Find list of steps and ther data===//
		//== Getting steps from identifier meta==//
		if (isset($data['meta']['funnel_identifier'])) {
			$identifier_meta = $data['meta']['funnel_identifier'];
			$all_steps_array = array();
			foreach ($identifier_meta as $identifier_meta_key => $identifier_meta_value) {
				$node_step_pair = json_decode($identifier_meta_value, true);
				foreach ($node_step_pair as $node_step_pair_key => $node_step_pair_value) {
					$current_steps_array = array();
					$step_id = $node_step_pair_value;
					$step_title = get_the_title($step_id);
					$step_meta = get_post_meta($step_id);
					$content_post = get_post($step_id);
					$content = json_encode($content_post->post_content);
					$current_steps_array['step_id'] = $step_id;
					$current_steps_array['title'] = $step_title;
					$current_steps_array['meta'] = $step_meta;
					$current_steps_array['content'] = $content;
					$all_steps_array[] = $current_steps_array;
				}
			}
			$data['steps'] = $all_steps_array;
		}

		return json_encode($data);
	}

	/**
	 * Get UTM Params URL
	 */

	public function get_utm_params($funnel_id)
	{
		$utm_params = '';
		$utm_settings = $this->get_utm_settings($funnel_id);
		$utm_params = '?utm_source=' . 'fgddffd' . '&utm_medium=' . 'ghfds' . '&utm_campaign=' . 'dsaghfds';
		return $utm_params;
		if ($utm_settings['utm_enable'] == 'on') {
			$utm_params = '?utm_source=' . $utm_settings['utm_source'] . '&utm_medium=' . $utm_settings['utm_medium'] . '&utm_campaign=' . $utm_settings['utm_campaign'];
			$utm_params .= ((!empty($utm_settings['utm_content'])) ? '&utm_content=' . $utm_settings['utm_content'] : '');
			$utm_params = strtolower($utm_params);
		}
		return $utm_params;
	}

	/**
	 * Get GTM Settings
	 *
	 * @return array
	 */
	public function get_utm_settings($funnel_id)
	{
		$default_settings = array(
			'utm_enable' => 'off',
			'utm_source' => '',
			'utm_medium' => '',
			'utm_campaign' => '',
			'utm_content' => '',
		);
		$utm_settings = get_post_meta($funnel_id, '_wpfunnels_utm_params', true);
		return wp_parse_args($utm_settings, $default_settings);
	}


	/**
	 * Prepare a single setting object for response.
	 *
	 * @param object|array $item Setting object.
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
	 * Get GBF data
	 *
	 * @param $request
	 *
	 * @return WP_Error|\WP_REST_Response
	 */
	public function get_GBF_data($request)
	{

		$funnel_id = $request['funnel_id'];
		$steps = Wpfnl_functions::get_steps($funnel_id);

		if (!is_plugin_active('wpfunnels-pro-gbf/wpfnl-pro-gb.php')) {
			$response = array(
				'success' => false,
				'data' => 'Global Funnel is not activated'
			);
			return rest_ensure_response($response);
		}

		$is_gbf = get_post_meta($funnel_id, 'is_global_funnel', true);
		if ('yes' === $is_gbf) {
			$start_condition = get_post_meta($funnel_id, 'global_funnel_start_condition', true);
			$step_ids = array();
			foreach ($steps as $step) {
				if ($step['step_type'] == 'checkout') {
					if (!empty($start_condition)) {
						array_push($step_ids, $step['id']);
					}
				} elseif ($step['step_type'] == 'upsell') {
					$upsell_rules = get_post_meta($step['id'], 'global_funnel_upsell_rules', true);
					if (!empty($upsell_rules)) {
						array_push($step_ids, $step['id']);
					}
				} elseif ($step['step_type'] == 'downsell') {
					$downsell_rules = get_post_meta($step['id'], 'global_funnel_downsell_rules', true);
					if (!empty($downsell_rules)) {
						array_push($step_ids, $step['id']);
					}
				}
			}
		}
		$response = array(
			'success' => false,
			'data' => []
		);
		return rest_ensure_response($response);
	}


	/**
	 * Get ab testing default setttings
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return Array
	 *
	 * @since 1.6.21
	 */
	public function get_ab_settings(WP_REST_Request $request)
	{

		$response = [];
		if (isset($request['step_id'])) {
			$step_id = $request['step_id'];
			$default_settings = $this->get_default_start_setting($step_id);
			$response['data'] = $default_settings;
			$response['success'] = true;
		} else {
			$response['data'] = '';
			$response['success'] = false;
		}

		return rest_ensure_response($response);
	}

	/**
	 * Change funnel name
	 *
	 * @param WP_REST_Request $payload Funnel name payload.
	 *
	 * @return array
	 * @since  2.7.5
	 */
	public function funnel_name_change($payload)
	{
		if (!isset($payload['funnel_id'], $payload['funnel_name'])) {
			return new WP_Error(
				'rest_invalid_request_params',
				__('Invalid request params.'),
				array('status' => 404)
			);
		}

		$funnel_id = sanitize_text_field($payload['funnel_id']);
		$updated_name = sanitize_text_field($payload['funnel_name']);
		Wpfnl::$instance->funnel_store->set_id($funnel_id);
		Wpfnl::$instance->funnel_store->update_funnel_name($updated_name);
		self::update_steps_url_on_funnel_name_update($funnel_id);
		flush_rewrite_rules();

		$response = array(
			'success' => true,
			'message' => 'Funnel name changed successfully',
			'funnelID' => $funnel_id,
			'name' => $updated_name
		);

		return rest_ensure_response($response);
	}

	/**
	 * Update the URL and funnel name for each step in a funnel.
	 *
	 * This function retrieves the step IDs associated with a specific funnel,
	 * and updates the URL and funnel name for each step based on the step ID.
	 *
	 * @param int $funnel_id The ID of the funnel.
	 * @return void
	 * @since 2.7.10
	 */
	public function update_steps_url_on_funnel_name_update($funnel_id)
	{
		$step_controller = new StepController();
		$step_ids = Wpfnl_functions::get_step_ids($funnel_id);
		foreach ($step_ids as $step_id) {
			$step_title = get_post_meta($step_id, '_wpf_step_title', true);
			$step_slug = get_post_meta($step_id, '_wpf_step_slug', true);
			$settings = array(
				'funnel_id' => $funnel_id,
				'step_id' => $step_id,
				'title' => !empty($step_title) ? $step_title : get_the_title($step_id),
				'slug' => !empty($step_slug) ? $step_slug : get_post_field('post_name', $step_id)
			);
			$step_controller->update_step_meta_on_funnel_name_change($funnel_id, $step_id, $settings);
		}
	}

	/**
	 * Get funnel settings by funnel id
	 *
	 * @param array $payload Funnel Settings payload data.
	 * @return WP_Rest_Response
	 *
	 * @since 1.0.0
	 */
	public function wpfnl_get_funnel_settings($payload)
	{
		// Early return in case of missing funnel id.
		if (!isset($payload['funnel_id'])) {
			return $this->prepare_wp_error_response(
				'rest_missing_funnelID',
				__('Funnel id is missing', 'wpfnl'),
				array(
					'status' => 400
				)
			);
		}

		// To get global settings.
		$global_gtm = Wpfnl_functions::get_gtm_settings();
		$global_pixel = Wpfnl_functions::get_facebook_pixel_settings();
		$settings = Wpfnl_functions::get_funnel_settings($payload['funnel_id']);

		// Set default skip offer settings.
		$skip_settings = array(
			'skip_offer' => 'no',
			'skip_if_quantity' => 'no',
		);

		if (get_post_meta($payload['funnel_id'], '_wpfunnels_skip_offer', true)) {
			$skip_settings = get_post_meta($payload['funnel_id'], '_wpfunnels_skip_offer', true);
		}

		// Preparing rest response.
		$response = array(
			'success' => true,
			'data' => $settings,
			'globalGtm' => isset ($global_gtm['gtm_enable']) ? $global_gtm['gtm_enable'] : 'no',
			'globalPixel' => isset ($global_pixel['enable_fb_pixel']) ? $global_pixel['enable_fb_pixel'] : 'no',
			'skipSettings' => $skip_settings,
			'skipRecurringOffer' => get_post_meta($payload['funnel_id'], '_wpfunnels_skip_recurring_offer', true),
			'skipRecurringOfferWithinDays' => sanitize_text_field(get_post_meta($payload['funnel_id'], '_wpfunnels_skip_recurring_offer_within_days', true)),
		);

		/**
		 * Fires to get an individual funnel settings.
		 *
		 * @param string $response The settings data.
		 * @param string $payload ['funnel_id'] The individual funnel id.
		 * @since 1.0.0
		 *
		 */
		$response = apply_filters('wpfunnels/funnel_individual_settings', $response, $payload['funnel_id']);
		$response = rest_ensure_response($response);

		return $response;//phpcs:ignore
	}

	/**
	 * Delete marked funnel
	 *
	 * @param $payload
	 *
	 * @return array|bool
	 * @since  1.0.0
	 */
	public function delete_marked_funnels($payload)
	{
		$funnel_controller = Module::instance();

		return $funnel_controller->delete_marked_funnels($payload);
	}


	/**
	 * Restore  marked funnel
	 *
	 * @param $payload
	 *
	 * @return array|bool
	 * @since  3.1.8
	 */
	public function restore_marked_funnels($payload)
	{
		$funnel_controller = Module::instance();

		return $funnel_controller->restore_marked_funnels($payload);
	}

	/**
	 * Restore  marked funnel
	 *
	 * @param $payload
	 *
	 * @return array|bool
	 * @since  3.1.8
	 */
	public function trash_marked_funnels($payload)
	{
		$funnel_controller = Module::instance();

		return $funnel_controller->trash_marked_funnels($payload);
	}

}
