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
class DashboardController extends Wpfnl_REST_Controller {

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
	public function get_items_permissions_check( $request ) {
		if ( !Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'settings' ) ) {
			return new \WP_Error( 'wpfunnels_rest_cannot_edit', __( 'Sorry, you cannot list resources.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}


	/**
	 * Register rest routes
	 *
	 * @since 3.2.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/overview',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'args'                => $this->get_stats_args(),
					'callback'            => array( $this, 'get_overview' ),
					'permission_callback' => array( $this,  'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/stats',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'args'                => $this->get_stats_args(),
					'callback'            => array( $this, 'get_stats' ),
					'permission_callback' => array( $this,  'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/top-funnels',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'args'                => $this->get_stats_args(),
					'callback'            => array( $this, 'get_top_funnels' ),
					'permission_callback' => array( $this,  'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/show-banner',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array($this, 'should_show_banner'),
					'permission_callback' => function () {
						return current_user_can('manage_options');
					},
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/hide-banner-temporarily',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'hide_community_banner_temporarily'),
					'permission_callback' => function () {
						return current_user_can('manage_options');
					},
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/hide-banner-permanently',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'hide_community_banner_permanently'),
					'permission_callback' => function () {
						return current_user_can('manage_options');
					},
				),
			)
		);
	}

	/**
	 * Check if community banner should be shown.
	 * 
	 * Description:
	 * - If the user has permanently hidden the banner, it will not be shown.
	 * - If the banner is temporarily hidden, it will not be shown.
	 * - If neither of the above conditions are met, the banner will be shown.
	 *
	 * @return bool
	 * @since 3.5.21
	 */
	public function should_show_banner(){
		// Check if user has permanently hidden the banner.
		$permanently_hidden = get_transient('wpfnl_community_banner_permanently_hidden');
		if ($permanently_hidden) {
			return rest_ensure_response(array(
				'success' => true,
				'show_banner' => false
			));
		}

		// Check if banner is temporarily hidden
		$temporarily_hidden = get_transient('wpfnl_community_banner_temporarily_hidden');
		if ($temporarily_hidden) {
			return rest_ensure_response(array(
				'success' => true,
				'show_banner' => false
			));
		}

		return rest_ensure_response(array(
			'success' => true,
			'show_banner' => true
		));
	}

	/**
	 * Hide community banner temporarily.
	 * 
	 * Description:
	 * - Set a transient for 7 days to hide the banner.
	 *
	 * @return \WP_REST_Response
	 * @since 3.5.21
	 */
	public function hide_community_banner_temporarily(){
		// Set transient for 7 days.
		set_transient('wpfnl_community_banner_temporarily_hidden', true, 7 * DAY_IN_SECONDS);
		return rest_ensure_response([
			'success' => true
		]);
	}

	/**
	 * Hide community banner permanently.
	 *
	 * Description:
	 * - Set a permanent transient to hide the banner.
	 *
	 * @return \WP_REST_Response
	 * @since 3.5.21
	 */
	public function hide_community_banner_permanently(){
		// Set permanent transient (0 = no expiration).
		set_transient('wpfnl_community_banner_permanently_hidden', true, 0);
		return rest_ensure_response([
			'success' => true
		]);
	}

	/**
	 * Get stats arguments
	 *
	 * @return mixed|void
	 * @since 3.2.0
	 */
	public function get_stats_args() {
		return array(
			'after' => array(
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'before' => array(
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'interval' => array(
				'type'              => 'string',
				'default'			=> 'week',
				'enum'              => array(
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
	public function get_overview( \WP_REST_Request $request ) {
		$params		= $request->get_params();
		$start_date	= isset( $params['after'] ) ? $params['after'] : $this->default_after()->format( 'Y-m-d H:i:s' );
		$end_date	= isset( $params['before'] ) ? $params['before'] : $this->default_before()->format( 'Y-m-d H:i:s' );
		$response	= ReportGenerator::get_overview($start_date, $end_date);
		return rest_ensure_response( $response );
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
	public function get_stats( \WP_REST_Request $request ) {
		$params			= $request->get_params();
		$start_date		= isset( $params['after'] ) ? $params['after'] : $this->default_after()->format( 'Y-m-d H:i:s' );
		$end_date		= isset( $params['before'] ) ? $params['before'] : $this->default_before()->format( 'Y-m-d H:i:s' );
		$interval		= isset( $params['interval'] ) ? $params['interval'] : 'day';
		$response 		= ReportGenerator::get_stats($start_date, $end_date,$interval);
		return rest_ensure_response( $response );
	}


	/**
	 * Get 3 top performing funnels
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response
	 *
	 * @since 3.2.0
	 */
	public function get_top_funnels( \WP_REST_Request $request ) {
		$response['status'] = true;
		$response['data'] 	= ReportGenerator::get_top_funnels();
		return rest_ensure_response( $response );
	}
}
