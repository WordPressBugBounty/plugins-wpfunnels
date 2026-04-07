<?php


namespace WPFunnels\Rest\Controllers;

use WPFunnels\Report\ReportGenerator;
use WPFunnels\Wpfnl_functions;

/**
 *
 *
 * Class DashboardController
 * @package WPFunnels\Rest\Controllers
 * @since 3.2.0
 */
class DashboardController extends Wpfnl_REST_Controller
{

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 * @since 3.2.0
	 */
	protected $namespace = 'wpfunnels/v1';


	/**
	 * Route base.
	 *
	 * @var string
	 * @since 3.2.0
	 */
	protected $rest_base = 'report';


	/**
	 * Makes sure the current user has access to READ the settings APIs.
	 *
	 *
	 * @param $request
	 * @return \WP_Error|boolean
	 * @since  3.2.0
	 */
	public function get_items_permissions_check($request)
	{
		if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('settings')) {
			return new \WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot list resources.', 'wpfnl'), array('status' => rest_authorization_required_code()));
		}
		return true;
	}


	/**
	 * Register rest routes
	 *
	 * @since 3.2.0
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/overview',
			array(
				array(
				'methods' => \WP_REST_Server::READABLE,
				'args' => $this->get_stats_args(),
				'callback' => array($this, 'get_overview'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
			),
		)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/stats',
			array(
				array(
				'methods' => \WP_REST_Server::READABLE,
				'args' => $this->get_stats_args(),
				'callback' => array($this, 'get_stats'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
			),
		)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/top-funnels',
			array(
				array(
				'methods' => \WP_REST_Server::READABLE,
				'args' => $this->get_stats_args(),
				'callback' => array($this, 'get_top_funnels'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
			),
		)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/onboarding-status',
			array(
				array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array($this, 'get_onboarding_status'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
			),
		)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/has-funnels',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array($this, 'check_has_funnels'),
					'permission_callback' => array( $this,  'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/dismiss-onboarding',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'dismiss_onboarding'),
					'permission_callback' => array( $this,  'get_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Get stats arguments
	 *
	 * @return mixed|void
	 * @since 3.2.0
	 */
	public function get_stats_args()
	{
		return array(
			'after' => array(
				'type' => 'string',
				'format' => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'before' => array(
				'type' => 'string',
				'format' => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'interval' => array(
				'type' => 'string',
				'default' => 'week',
				'enum' => array(
					'hour',
					'day',
					'week',
					'month',
					'quarter',
					'year',
				),
			),
		);
	}


	/**
	 * Get overview data of funnels
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response
	 * @throws \Exception
	 *
	 * @since 3.2.0
	 */
	public function get_overview(\WP_REST_Request $request)
	{
		$params = $request->get_params();
		$start_date = isset($params['after']) ? $params['after'] : $this->default_after()->format('Y-m-d H:i:s');
		$end_date = isset($params['before']) ? $params['before'] : $this->default_before()->format('Y-m-d H:i:s');
		$response = ReportGenerator::get_overview($start_date, $end_date);
		return rest_ensure_response($response);
	}


	/**
	 * Get stats of the funnels with intervals period
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response
	 * @throws \Exception
	 *
	 * @since 3.2.0
	 */
	public function get_stats(\WP_REST_Request $request)
	{
		$params = $request->get_params();
		$start_date = isset($params['after']) ? $params['after'] : $this->default_after()->format('Y-m-d H:i:s');
		$end_date = isset($params['before']) ? $params['before'] : $this->default_before()->format('Y-m-d H:i:s');
		$interval = isset($params['interval']) ? $params['interval'] : 'day';
		$response = ReportGenerator::get_stats($start_date, $end_date, $interval);
		return rest_ensure_response($response);
	}


	/**
	 * Get 3 top performing funnels
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response
	 *
	 * @since 3.2.0
	 */
	public function get_top_funnels(\WP_REST_Request $request)
	{
		$response['status'] = true;
		$response['data'] = ReportGenerator::get_top_funnels();
		return rest_ensure_response($response);
	}


	/**
	 * Get onboarding status.
	 *
	 * Determines whether the onboarding section should be shown on the dashboard.
	 * The onboarding is hidden when ALL three conditions are met:
	 *   1. At least one funnel is published (live)
	 *   2. At least one funnel contains an order bump (upsell is optional/bonus)
	 *   3. At least one real order has been placed through any funnel
	 *
	 * Also returns individual step completion states for the checklist.
	 *
	 * @return \WP_REST_Response
	 * @since 3.9.5
	 */
	public function get_onboarding_status()
	{
		// ── Check if onboarding has been dismissed ──
		$is_dismissed = get_transient( 'wpfnl_onboarding_dismissed' );
		if ( $is_dismissed ) {
			return rest_ensure_response( array(
				'success' => true,
				'show_onboarding' => false,
				'steps' => array(),
				'completed_count' => 0,
				'total_steps' => 0,
				'progress_percent' => 0,
			) );
		}

		$is_wc_active = class_exists('WooCommerce');
		$has_live_funnel = false;
		$has_order_bump = false;
		$has_upsell = false;
		$has_real_order = false;
		$has_funnel_created = false;

		// ── Step 1: Check for published funnels ──
		$funnels = get_posts(array(
			'post_type' => WPFNL_FUNNELS_POST_TYPE,
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields' => 'ids',
		));

		if (!empty($funnels)) {
			$has_funnel_created = true;
			$has_live_funnel = true;
		}

		// Also check for draft funnels (counts as "created" but not "live")
		if (!$has_funnel_created) {
			$draft_funnels = get_posts(array(
				'post_type' => WPFNL_FUNNELS_POST_TYPE,
				'post_status' => array('publish', 'draft'),
				'numberposts' => 1,
				'fields' => 'ids',
			));
			$has_funnel_created = !empty($draft_funnels);
		}

		// ── Step 2: Check for order bump & upsell across all published funnels ──
		if (!empty($funnels)) {
			foreach ($funnels as $funnel_id) {
				$steps = Wpfnl_functions::get_steps($funnel_id);

				if (!is_array($steps) || empty($steps)) {
					continue;
				}

				foreach ($steps as $step) {
					$step_id = isset($step['id']) ? $step['id'] : 0;
					$step_type = isset($step['step_type']) ? $step['step_type'] : '';

					// Check for order bump on checkout steps
					if ('checkout' === $step_type && !$has_order_bump) {
						$ob_settings = get_post_meta($step_id, 'order-bump-settings', true);
						if (!empty($ob_settings) && is_array($ob_settings)) {
							$is_multidimensional = Wpfnl_functions::check_array_is_multidimentional($ob_settings);
							if ($is_multidimensional) {
								foreach ($ob_settings as $ob) {
									if (!empty($ob['product']) && !empty($ob['isEnabled'])) {
										$has_order_bump = true;
										break;
									}
								}
							}
							else {
								// Single OB (legacy format)
								if (!empty($ob_settings['product'])) {
									$has_order_bump = true;
								}
							}
						}
					}

					// Check for upsell step
					if ('upsell' === $step_type) {
						$has_upsell = true;
					}
				}

				// If we already found both, no need to check more funnels
				if ($has_order_bump && $has_upsell) {
					break;
				}
			}
		}

		// ── Step 3: Check for real orders through funnels ──
		if ($is_wc_active && !empty($funnels)) {
			global $wpdb;
			$stats_table = $wpdb->prefix . 'wpfnl_stats';

			// Check if stats table exists
			$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $stats_table));
			if ($table_exists) {
				$order_count = $wpdb->get_var(
					"SELECT COUNT(id) FROM {$stats_table} WHERE status IN ('completed', 'processing') LIMIT 1"
				);
				$has_real_order = (int)$order_count > 0;
			}
		}

		// ── Determine if onboarding should be shown ──
		// Hide onboarding only when ALL conditions are met:
		//   - At least 1 live funnel
		//   - Has an order bump (upsell is optional)
		//   - Has at least 1 real order
		$should_hide_onboarding = $has_live_funnel && $has_order_bump && $has_real_order;

		// ── Build checklist step completion data ──
		$completed_count = 0;
		$steps_status = array(
			'woocommerce_connected' => $is_wc_active,
			'funnel_created' => $has_funnel_created,
			'order_bump_added' => $has_order_bump,
			'upsell_added' => $has_upsell,
			'test_order_placed' => $has_real_order,
			'funnel_live' => $has_live_funnel,
		);

		foreach ($steps_status as $status) {
			if ($status) {
				$completed_count++;
			}
		}

		$total_steps = count($steps_status);
		$progress_percent = $total_steps > 0 ? round(($completed_count / $total_steps) * 100) : 0;

		return rest_ensure_response(array(
			'success' => true,
			'show_onboarding' => !$should_hide_onboarding,
			'steps' => $steps_status,
			'completed_count' => $completed_count,
			'total_steps' => $total_steps,
			'progress_percent' => $progress_percent,
		));
	}

	/**
	 * Check if there are any funnels
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 *
	 * @since 3.9.5
	 */
	public function check_has_funnels( \WP_REST_Request $request ) {
		global $wpdb;

		$funnel_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
				'wpfunnels'
			)
		);

		return rest_ensure_response( array(
			'success' => true,
			'has_funnels' => (int) $funnel_count > 0,
			'funnel_count' => (int) $funnel_count
		) );
	}

	/**
	 * Dismiss the onboarding checklist
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 *
	 * @since 3.9.5
	 */
	public function dismiss_onboarding( \WP_REST_Request $request ) {
		// Store dismissal in a transient for 30 days
		set_transient( 'wpfnl_onboarding_dismissed', true, 30 * DAY_IN_SECONDS );

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Onboarding dismissed successfully'
		) );
	}
}
