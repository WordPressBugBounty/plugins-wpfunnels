<?php
/**
 * SingleShotAiController — REST API controller for single-shot AI tasks (POST /wpfunnels/v1/ai/generate).
 *
 * @package WPFunnels\Rest\Controllers
 * @since 3.13.0
 */

namespace WPFunnels\Rest\Controllers;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\AI\AIInit;
use WPFunnels\AI\Settings\AISettings;

require_once dirname( __DIR__, 2 ) . '/AI/SingleShotAIService.php';

/**
 * Class SingleShotAiController
 */
class SingleShotAiController extends Wpfnl_REST_Controller {

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
	protected $rest_base = 'ai';

	/**
	 * Check permissions.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_access',
				__( 'You must be logged in to access AI generation.', 'wpfnl' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		if ( ! current_user_can( 'wpf_manage_funnels' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_access',
				__( 'Sorry, you do not have permission to access AI generation.', 'wpfnl' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/generate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'generate' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);
	}

	/**
	 * Single-shot generation endpoint handler.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate( $request ) {
		$task   = sanitize_text_field( (string) $request->get_param( 'task' ) );
		$prompt = sanitize_textarea_field( (string) $request->get_param( 'prompt' ) );
		$params = $request->get_param( 'params' ) ?: [];

		$result = \WPFunnels\AI\SingleShotAIService::executeTask( $task, $prompt, $params );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}
}
