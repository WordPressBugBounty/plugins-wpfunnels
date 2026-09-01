<?php
/**
 * McpSettingsController — REST API controller for the WPFunnels MCP settings tab.
 *
 * Routes:
 *   GET    /wpfunnels/v1/settings/mcp                              — MCP enable/endpoint/tool count
 *   POST   /wpfunnels/v1/settings/mcp                              — Toggle MCP on/off
 *   GET    /wpfunnels/v1/settings/mcp/app-passwords                — List WPFunnels-scoped app passwords
 *   POST   /wpfunnels/v1/settings/mcp/app-passwords                — Create a new app password
 *   DELETE /wpfunnels/v1/settings/mcp/app-passwords/{uuid}         — Revoke an app password
 *
 * @package WPFunnels\Rest\Controllers
 * @since 3.13.0
 */

namespace WPFunnels\Rest\Controllers;

defined( 'ABSPATH' ) || exit;

use WP_Application_Passwords;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\MCP\AbilitiesRegistrar;
use WPFunnels\MCP\MCPInit;

/**
 * Class McpSettingsController
 */
class McpSettingsController extends Wpfnl_REST_Controller {

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
	protected $rest_base = 'settings/mcp';

	/**
	 * Name prefix used for application passwords created by this manager, so
	 * listing/revoking never touches credentials created for unrelated plugins
	 * or in the user's normal profile screen.
	 *
	 * @var string
	 */
	const APP_PASSWORD_PREFIX = 'WPFunnels · ';

	/**
	 * Check permissions for MCP settings endpoints. Same gate as AiChatController
	 * (logged-in + wpf_manage_funnels or manage_options) — WPFunnels' controllers
	 * don't share permission logic via inheritance today, so this is duplicated
	 * intentionally rather than reaching into a sibling controller.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_access',
				__( 'You must be logged in to access this endpoint.', 'wpfnl' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		if ( ! current_user_can( 'wpf_manage_funnels' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_access',
				__( 'Sorry, you do not have permission to access this endpoint.', 'wpfnl' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Permission gate for the Application Password manager. Creating/revoking
	 * credentials for external MCP clients is more sensitive than everyday
	 * copilot use, so this additionally requires `manage_options` and a working
	 * Abilities API — app passwords are pointless without the MCP surface up.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_app_password_permission( $request ) {
		$base = $this->check_permission( $request );
		if ( is_wp_error( $base ) ) {
			return $base;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_access',
				__( 'Sorry, only site administrators can manage MCP application passwords.', 'wpfnl' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		if ( ! MCPInit::abilitiesApiAvailable() ) {
			return new WP_Error(
				'wpfunnels_mcp_unavailable',
				__( 'The MCP surface requires the WordPress Abilities API, which is not available on this site. Application passwords are not needed without it.', 'wpfnl' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! class_exists( '\WP_Application_Passwords' ) || ! wp_is_application_passwords_available() ) {
			return new WP_Error(
				'wpfunnels_app_passwords_unavailable',
				__( 'Application Passwords are not available on this site.', 'wpfnl' ),
				[ 'status' => 400 ]
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
		// MCP toggle/endpoint/tool count: GET|POST /settings/mcp
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_mcp_settings' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_mcp_settings' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Application password manager collection: GET (list), POST (create)
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/app-passwords',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'list_app_passwords' ],
					'permission_callback' => [ $this, 'check_app_password_permission' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_app_password' ],
					'permission_callback' => [ $this, 'check_app_password_permission' ],
				],
			]
		);

		// Revoke a single application password: DELETE /settings/mcp/app-passwords/{uuid}
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/app-passwords/(?P<uuid>[\w-]+)',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_app_password' ],
					'permission_callback' => [ $this, 'check_app_password_permission' ],
				],
			]
		);
	}

	/**
	 * Build the MCP settings payload shared by GET and POST.
	 *
	 * @return array
	 */
	protected function mcp_settings_payload() {
		$definitions = AbilitiesRegistrar::getDefinitions();

		return [
			'success'                 => true,
			'enabled'                 => 'no' !== get_option( '_wpfnl_mcp_enabled', 'yes' ),
			'endpoint_url'            => MCPInit::endpointUrl(),
			'tool_count'              => is_array( $definitions ) ? count( $definitions ) : 0,
			'abilities_api_available' => MCPInit::abilitiesApiAvailable(),
		];
	}

	/**
	 * Current MCP state: enabled, endpoint URL, tool count, Abilities API support.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_mcp_settings( $request ) {
		return rest_ensure_response( $this->mcp_settings_payload() );
	}

	/**
	 * Persist the MCP enable toggle.
	 *
	 * This writes the same `_wpfnl_mcp_enabled` option that
	 * AiChatController::save_ai_settings() already writes as a convenience fold —
	 * that path is left as-is for backward compatibility with any existing
	 * frontend calls; this is the dedicated settings/mcp counterpart per the plan.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_mcp_settings( $request ) {
		$params = (array) $request->get_json_params();
		if ( empty( $params ) ) {
			$params = (array) $request->get_params();
		}

		if ( array_key_exists( 'enabled', $params ) ) {
			update_option( '_wpfnl_mcp_enabled', rest_sanitize_boolean( $params['enabled'] ) ? 'yes' : 'no' );
		}

		return rest_ensure_response( $this->mcp_settings_payload() );
	}

	/**
	 * List WPFunnels-scoped application passwords for the current user.
	 *
	 * Only entries whose stored name starts with self::APP_PASSWORD_PREFIX are
	 * returned, so credentials created for unrelated plugins never surface here.
	 * The plaintext password is never stored by WordPress core past creation, so
	 * it is never part of this response.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function list_app_passwords( $request ) {
		$user_id = get_current_user_id();
		$all     = WP_Application_Passwords::get_user_application_passwords( $user_id );

		$items = [];
		foreach ( (array) $all as $item ) {
			if ( empty( $item['name'] ) || 0 !== strpos( $item['name'], self::APP_PASSWORD_PREFIX ) ) {
				continue;
			}

			$items[] = [
				'uuid'      => $item['uuid'],
				'name'      => substr( $item['name'], strlen( self::APP_PASSWORD_PREFIX ) ),
				'created'   => isset( $item['created'] ) ? (int) $item['created'] : 0,
				'last_used' => ! empty( $item['last_used'] ) ? (int) $item['last_used'] : null,
				'last_ip'   => ! empty( $item['last_ip'] ) ? $item['last_ip'] : '',
			];
		}

		return rest_ensure_response(
			[
				'success'       => true,
				'app_passwords' => $items,
			]
		);
	}

	/**
	 * Create a new WPFunnels-scoped application password.
	 *
	 * The plaintext password is returned exactly once, in this response — WordPress
	 * core only stores its hash, so it can never be re-displayed after this call.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_app_password( $request ) {
		$user_id = get_current_user_id();
		$label   = sanitize_text_field( (string) $request->get_param( 'name' ) );
		if ( '' === $label ) {
			$label = __( 'MCP Client', 'wpfnl' );
		}

		$created = WP_Application_Passwords::create_new_application_password(
			$user_id,
			[ 'name' => self::APP_PASSWORD_PREFIX . $label ]
		);

		if ( is_wp_error( $created ) ) {
			return $created;
		}

		list( $password, $item ) = $created;

		return rest_ensure_response(
			[
				'success'      => true,
				'password'     => $password,
				'app_password' => [
					'uuid'    => $item['uuid'],
					'name'    => $label,
					'created' => isset( $item['created'] ) ? (int) $item['created'] : time(),
				],
			]
		);
	}

	/**
	 * Revoke a WPFunnels-scoped application password.
	 *
	 * Scoped to the current user and to entries carrying the WPFunnels name
	 * prefix, so this manager cannot revoke a password created by another plugin
	 * or by the user's normal profile screen.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_app_password( $request ) {
		$user_id = get_current_user_id();
		$uuid    = sanitize_text_field( (string) $request->get_param( 'uuid' ) );

		$existing = WP_Application_Passwords::get_user_application_password( $user_id, $uuid );
		if ( ! $existing || empty( $existing['name'] ) || 0 !== strpos( $existing['name'], self::APP_PASSWORD_PREFIX ) ) {
			return new WP_Error(
				'wpfunnels_app_password_not_found',
				__( 'Application password not found.', 'wpfnl' ),
				[ 'status' => 404 ]
			);
		}

		$deleted = WP_Application_Passwords::delete_application_password( $user_id, $uuid );
		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}

		return rest_ensure_response(
			[
				'success' => (bool) $deleted,
				'uuid'    => $uuid,
			]
		);
	}
}
