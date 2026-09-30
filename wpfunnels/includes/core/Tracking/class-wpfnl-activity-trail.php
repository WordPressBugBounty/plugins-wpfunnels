<?php
/**
 * Product activity trail.
 *
 * Records every WPFunnels feature interaction on the site:
 *   - every admin screen opened: routes of the React admin (sent from the app
 *     as route patterns) and the server-rendered pages (edit funnel canvas,
 *     email builder, integrations, setup wizard), and
 *   - every WPFunnels REST action (all create/update/delete/import/test calls in
 *     Free and Pro, plus any request that fails), named automatically from the
 *     route pattern, e.g. `POST /funnels/:funnel_id/duplicate` becomes
 *     `funnel_duplicated`, and a failure becomes `funnel_duplicated_failed`.
 *
 * Delivery to PostHog, one event per entry with its original timestamp and the
 * same distinct id the Linno Telemetry SDK uses:
 *   - opted-in sites: sent daily, so retained and churned users can be compared.
 *   - every site: whatever is unsent is sent when the plugin is deactivated,
 *     followed by a `deactivation_context` summary.
 *
 * Payload is anonymous: route patterns (never ids), feature names, HTTP status
 * and error codes. No request bodies, messages, emails, orders or customer data.
 *
 * @package WPFunnels\Tracking
 * @since   3.14.0
 */

namespace WPFunnels\Tracking;

use LinnoSDK\Telemetry\Helpers\Utils;
use PostHog\PostHog;

/**
 * Class ActivityTrail
 *
 * @since 3.14.0
 */
class ActivityTrail {

	/**
	 * Option that stores the trail (not autoloaded).
	 */
	const OPTION = 'wpfnl_activity_trail';

	/**
	 * Maximum unsent entries kept (a busy day on an opted-in site).
	 */
	const MAX_UNSENT = 200;

	/**
	 * Already-sent entries kept for the deactivation summary.
	 */
	const KEEP_SENT = 30;

	/**
	 * Entries older than this are dropped (30 days).
	 */
	const MAX_AGE = 2592000;

	/**
	 * Repeats of the same activity within this window collapse into one entry
	 * (canvas autosave, reopening a screen).
	 */
	const COLLAPSE_WINDOW = 600;

	/**
	 * WP-Cron hook for the daily flush on opted-in sites.
	 */
	const FLUSH_HOOK = 'wpfnl_activity_trail_flush';

	/**
	 * PostHog ingestion host (same project as the Linno SDK client).
	 */
	const POSTHOG_HOST = 'https://eu.i.posthog.com';

	/**
	 * PostHog event name prefix for trail events.
	 */
	const EVENT_PREFIX = 'wpfnl_activity/';

	/**
	 * Slug the Linno SDK client is built with; names its deactivation transient.
	 */
	const SDK_SLUG = 'wpfunnels';

	/**
	 * Admin REST namespaces of WPFunnels Free and Pro (Pro registers its routes
	 * under `wpfunnels/v1` too). The MCP server namespace (`wpfunnels`) is left
	 * out: MCP traffic is recorded through the MCP observability handler.
	 */
	const NAMESPACES = array( 'wpfunnels/v1', 'wpfnl/v1' );

	/**
	 * Path rewrites per namespace, applied before naming. `wpfnl/v1` only hosts
	 * the automation canvas controller — the feature `wpfunnels/v1/automation-canvas`
	 * also serves — while `wpfunnels/v1/automation` is the Pro CRM automations.
	 */
	const NAMESPACE_PATHS = array(
		'wpfnl/v1' => array( '/automation' => '/automation-canvas' ),
	);

	/**
	 * Routes (without namespace) that are never recorded.
	 */
	const IGNORED_ROUTES = array( '/activity-trail/screen' );

	/**
	 * First route segment → feature area.
	 */
	const AREA_ALIASES = array(
		'funnels'           => 'funnel',
		'funnel-control'    => 'funnel',
		'steps'             => 'step',
		'templates'         => 'template',
		'remote_funnel'     => 'template',
		'order-bump'        => 'order_bump',
		'products'          => 'product',
		'getProducts'       => 'product',
		'updateSelectedProduct' => 'product',
		'delete-product'    => 'product',
		'calculateDiscountPrice' => 'discount',
		'calculateMainProductsDiscount' => 'discount',
		'getFluentForms'    => 'optin',
		'getOtherSteps'     => 'step',
		'import-export'     => 'import_export',
		'report'            => 'dashboard',
		'setup-wizard'      => 'setup_wizard',
		'store-checkout'    => 'store_checkout',
		'automation'        => 'crm_automation',
		'automation-canvas' => 'automation',
		'mint'              => 'mail_mint_automation',
		'abtesting'         => 'ab_test',
		'global-funnel'     => 'global_funnel',
		'activate-plugin'   => 'plugin',
	);

	/**
	 * Route words that name the verb of the action, and the past tense the
	 * event uses. `get` carries no meaning in an event name and is dropped.
	 */
	const VERBS = array(
		'create'     => 'created',
		'save'       => 'saved',
		'update'     => 'updated',
		'delete'     => 'deleted',
		'add'        => 'added',
		'duplicate'  => 'duplicated',
		'import'     => 'imported',
		'export'     => 'exported',
		'restore'    => 'restored',
		'reset'      => 'reset',
		'remove'     => 'removed',
		'paste'      => 'pasted',
		'change'     => 'changed',
		'declare'    => 'declared',
		'stop'       => 'stopped',
		'archive'    => 'archived',
		'clear'      => 'cleared',
		'sync'       => 'synced',
		'activate'   => 'activated',
		'dismiss'    => 'dismissed',
		'trash'      => 'trashed',
		'convert'    => 'converted',
		'generate'   => 'generated',
		'regenerate' => 'regenerated',
		'confirm'    => 'confirmed',
		'finish'     => 'finished',
		'get'        => '',
	);

	/**
	 * Words that add nothing to an event name (plugin prefixes).
	 */
	const NOISE_WORDS = array( 'wpfnl', 'wpfunnel', 'wpfunnels' );

	/**
	 * Explicit names for actions the naming rules can't infer (actions exposed
	 * as GET, typos in routes, or clearer names for key signals).
	 * Keys are "<GET|WRITE|DELETE> <normalized route>".
	 */
	const OVERRIDES = array(
		'GET /funnel-control/exportFunnel'                   => 'funnel_exported',
		'GET /funnels/:funnel_id/export'                     => 'funnel_exported',
		'GET /webhook/delete-webhook/:funnel_id'             => 'webhook_deleted',
		'GET /webhook/update-webhook-status/:funnel_id'      => 'webhook_status_updated',
		'GET /updateSelectedProduct'                         => 'product_selected',
		'WRITE /funnel-control/saveFunnel'                   => 'funnel_canvas_saved',
		'WRITE /funnel-control/steps'                        => 'funnel_steps_saved',
		'WRITE /funnel-control/wpfnl-get-funnel-settings'    => 'funnel_settings_saved',
		'WRITE /funnel-control/:funnel_id/funnel-name-change' => 'funnel_renamed',
		'WRITE /funnels/:funnel_id/status'                   => 'funnel_status_updated',
		'WRITE /funnels/bulk'                                => 'funnel_bulk_action',
		'WRITE /funnels/:funnel_id/steps/:step_id/metadata'  => 'step_metadata_updated',
		'WRITE /order-bump'                                  => 'order_bump_saved',
		'WRITE /offer/saveUpsellData'                        => 'upsell_saved',
		'WRITE /offer/saveDownsellData'                      => 'downsell_saved',
		'WRITE /steps/wpfunnel-import-step'                  => 'step_template_imported',
		'WRITE /steps/update-conditional-status'             => 'step_condition_status_updated',
		'WRITE /templates/import-funnel'                     => 'template_import_started',
		'WRITE /import-export/import'                        => 'funnel_import_started',
		'WRITE /import-export/import-reset'                   => 'funnel_import_reset',
		'WRITE /funnel-control/:funnel_id/convert-to-store-checkout' => 'funnel_converted_to_store_checkout',
		'WRITE /import-export/bulk-export-funnel'            => 'funnel_bulk_exported',
		'WRITE /gutenberg'                                   => 'gutenberg_block_rendered',
		'WRITE /settings/:group_id/:settings_id'             => 'settings_saved',
		'WRITE /settings/activate_wc_plugins'                => 'woocommerce_activated',
		'WRITE /settings/activate_mrm_plugins'               => 'mail_mint_activated',
		'WRITE /settings/activate-plugins'                   => 'plugins_activated',
		'WRITE /settings/create-contact'                     => 'setup_wizard_contact_created',
		'WRITE /settings/save-setup-wizard-settings'         => 'setup_wizard_settings_saved',
		'WRITE /settings/update-builder-type'                => 'page_builder_changed',
		'WRITE /settings/canvas-view-mode'                   => 'canvas_view_mode_changed',
		'WRITE /settings/test-notification'                  => 'test_notification_sent',
		'WRITE /settings/mcp/app-passwords'                  => 'mcp_app_password_created',
		'DELETE /settings/mcp/app-passwords/:uuid'           => 'mcp_app_password_deleted',
		'DELETE /settings/logs'                              => 'logs_deleted',
		'WRITE /setup-wizard/track-step'                     => 'setup_wizard_step_viewed',
		'WRITE /setup-wizard/complete-step'                  => 'setup_wizard_step_completed',
		'WRITE /report/sync-cache'                           => 'dashboard_synced',
		'WRITE /report/dismiss-onboarding'                   => 'onboarding_checklist_dismissed',
		'WRITE /activate-plugin'                             => 'plugin_activated',
		'WRITE /ai/conversations'                            => 'ai_conversation_started',
		'WRITE /ai/conversations/:id'                        => 'ai_conversation_updated',
		'DELETE /ai/conversations/:id'                       => 'ai_conversation_deleted',
		'WRITE /ai/conversations/:id/messages'               => 'ai_message_sent',
		'WRITE /ai/conversations/:id/step'                   => 'ai_agent_step',
		'WRITE /ai/conversations/:id/confirm'                => 'ai_tool_confirmed',
		'WRITE /ai/generate'                                 => 'ai_content_generated',
		'WRITE /webhook/update-webhook/:funnel_id'           => 'webhook_saved',
		'WRITE /automation/save_crm'                         => 'crm_connection_saved',
		'WRITE /automation/wpfnl-test-zapier/:funnel_id'     => 'zapier_tested',
		'WRITE /automation/save_data/:funnel_id'             => 'crm_automation_saved',
		'WRITE /automation/save_name/:funnel_id'             => 'crm_automation_renamed',
		'WRITE /mint/automation/save_data'                   => 'mail_mint_automation_saved',
		'WRITE /mint/automation/save_mail_data'              => 'mail_mint_automation_email_saved',
		'WRITE /mint/automation/delete_data'                 => 'mail_mint_automation_deleted',
		'WRITE /abtesting/update-settings'                   => 'ab_test_traffic_updated',
		'WRITE /abtesting/save-general-settings'             => 'ab_test_settings_saved',
		'WRITE /abtesting/stop-ab-testing/:step_id'          => 'ab_test_stopped',
		'WRITE /abtesting/delete-archive-variant'            => 'ab_test_archived_variant_deleted',
		'WRITE /global-funnel/saveObRules/:step_id'          => 'global_funnel_order_bump_rules_saved',
	);

	/**
	 * Server-rendered WPFunnels admin pages (`?page=`) → screen name. The React
	 * admin (`wpfunnels`, `wpfunnels-admin`) reports its own routes.
	 */
	const SERVER_SCREENS = array(
		'edit_funnel'            => '/edit-funnel',
		'email-builder'          => '/email-builder',
		'wpfunnels_integrations' => '/integrations',
		'wpfunnels-setup'        => '/setup-wizard',
		'store_checkout'         => '/store-checkout',
	);

	/**
	 * AI assistant settings option (see AISettings::OPTION_KEY).
	 */
	const AI_SETTINGS_OPTION = '_wpfnl_ai_settings';

	/**
	 * MCP server on/off option (default 'yes').
	 */
	const MCP_ENABLED_OPTION = '_wpfnl_mcp_enabled';

	/**
	 * Last MCP client name seen on `initialize` (tools/call requests don't carry it).
	 */
	const MCP_CLIENT_OPTION = 'wpfnl_mcp_last_client';

	/**
	 * MCP methods recorded; list/ping calls are client housekeeping and skipped.
	 */
	const MCP_METHODS = array(
		'initialize'     => 'mcp_client_connected',
		'tools/call'     => 'mcp_tool_called',
		'prompts/get'    => 'mcp_prompt_used',
		'resources/read' => 'mcp_resource_read',
	);

	/**
	 * Tool-name keywords → feature area, most specific first.
	 */
	const TOOL_AREAS = array(
		'checkout-field'  => 'checkout_field',
		'store-checkout'  => 'store_checkout',
		'order-bump'      => 'order_bump',
		'upsell'          => 'offer',
		'downsell'        => 'offer',
		'offer'           => 'offer',
		'variant'         => 'ab_test',
		'winner'          => 'ab_test',
		'ab-test'         => 'ab_test',
		'automation'      => 'automation',
		'webhook'         => 'webhook',
		'template'        => 'template',
		'optin'           => 'optin',
		'license'         => 'license',
		'context'         => 'context',
		'setup'           => 'context',
		'onboarding'      => 'context',
		'store-analysis'  => 'context',
		'step'            => 'step',
		'funnel'          => 'funnel',
		'product'         => 'product',
		'coupon'          => 'product',
		'analytics'       => 'analytics',
		'report'          => 'analytics',
		'dashboard'       => 'analytics',
		'lead'            => 'analytics',
		'setting'         => 'settings',
		'permalink'       => 'settings',
		'tracking'        => 'settings',
		'gutenberg'       => 'page_builder',
		'design'          => 'page_builder',
		'page-builder'    => 'page_builder',
		'mail-mint'       => 'integration',
		'addon'           => 'integration',
		'plugin'          => 'integration',
		'compat'          => 'diagnostics',
		'log'             => 'diagnostics',
	);

	/**
	 * Singleton instance.
	 *
	 * @var ActivityTrail|null
	 */
	private static $instance = null;

	/**
	 * Linno Telemetry client (for the consent state).
	 *
	 * @var object|null
	 */
	private $client;

	/**
	 * PostHog project key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Matched route pattern per in-flight REST request, keyed by object id.
	 *
	 * @var array
	 */
	private $matched_routes = array();

	/**
	 * @param object|null $client  Linno Telemetry client.
	 * @param string      $api_key PostHog project key.
	 */
	public function __construct( $client = null, $api_key = '' ) {
		$this->client  = $client;
		$this->api_key = (string) $api_key;
	}

	/**
	 * Register hooks once.
	 *
	 * @param object|null $client  Linno Telemetry client.
	 * @param string      $api_key PostHog project key.
	 * @return void
	 */
	public static function init( $client = null, $api_key = '' ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $client, $api_key );
			self::$instance->register_hooks();
		}
	}

	/**
	 * The running instance, or null before init().
	 *
	 * @return ActivityTrail|null
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Wire recorders, the screen-view endpoint, the daily flush and the
	 * deactivation sender.
	 *
	 * @return void
	 */
	private function register_hooks() {
		// Every WPFunnels REST action (Free + Pro).
		add_filter( 'rest_dispatch_request', array( $this, 'remember_route' ), 10, 4 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'record_rest_action' ), 10, 3 );

		// Server-rendered admin pages; the React admin posts its own routes.
		add_action( 'current_screen', array( $this, 'record_server_screen' ) );

		// Work that finishes outside the admin request.
		add_action(
			'wpfunnels/wpfnl_import_complete',
			function () {
				$this->record( 'import_completed' );
			}
		);
		add_action(
			'wpfunnels_after_funnel_imported',
			function ( $funnel_id = 0, $type = '', $builder = '' ) {
				$this->record(
					'template_funnel_imported',
					array(
						'template_type' => sanitize_key( (string) $type ),
						'builder'       => sanitize_key( (string) $builder ),
					)
				);
			},
			10,
			3
		);

		// AI assistant: provider connection lifecycle and copilot tool calls.
		add_action( 'add_option_' . self::AI_SETTINGS_OPTION, array( $this, 'on_ai_settings_added' ), 10, 2 );
		add_action( 'update_option_' . self::AI_SETTINGS_OPTION, array( $this, 'on_ai_settings_updated' ), 10, 2 );
		add_action(
			'wpfnl_ai_tool_executed',
			function ( $tool_name ) {
				$this->record_ai_tool( (string) $tool_name, true );
			}
		);
		add_action(
			'wpfnl_ai_tool_failed',
			function ( $tool_name, $error_code = '' ) {
				$this->record_ai_tool( (string) $tool_name, false, (string) $error_code );
			},
			10,
			2
		);

		// MCP server on/off. MCP requests are recorded by FunnelMcpObservabilityHandler.
		add_action( 'add_option_' . self::MCP_ENABLED_OPTION, array( $this, 'on_mcp_enabled_added' ), 10, 2 );
		add_action( 'update_option_' . self::MCP_ENABLED_OPTION, array( $this, 'on_mcp_enabled_updated' ), 10, 2 );

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		add_action( self::FLUSH_HOOK, array( $this, 'flush' ) );
		add_action( 'init', array( $this, 'maybe_schedule_flush' ) );

		add_action( 'wpfunnels_plugin_deactivated', array( $this, 'send_on_deactivation' ) );
	}

	/**
	 * Register the screen-view endpoint used by the React admin.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'wpfunnels/v1',
			'/activity-trail/screen',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_screen' ),
				'permission_callback' => array( $this, 'can_record' ),
				'args'                => array(
					'screen' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Any logged-in user who can use WPFunnels may be recorded.
	 *
	 * @return bool
	 */
	public function can_record() {
		return is_user_logged_in() && ( current_user_can( 'manage_options' ) || current_user_can( 'wpf_manage_funnels' ) );
	}

	/**
	 * REST callback: record a screen (route pattern such as `/automation/:automationId`).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function record_screen( $request ) {
		$this->record_screen_name( (string) $request->get_param( 'screen' ) );

		return new \WP_REST_Response( null, 204 );
	}

	/**
	 * `current_screen`: record a server-rendered WPFunnels admin page.
	 *
	 * @return void
	 */
	public function record_server_screen() {
		try {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen name.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			if ( ! isset( self::SERVER_SCREENS[ $page ] ) || wp_doing_ajax() || ! $this->can_record() ) {
				return;
			}

			$screen = self::SERVER_SCREENS[ $page ];
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen name.
			$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : '';
			if ( '/setup-wizard' === $screen && '' !== $step ) {
				$screen .= '/' . $step;
			}

			$this->record_screen_name( $screen );
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Record a screen view from a route pattern.
	 *
	 * @param string $screen Route pattern.
	 * @return void
	 */
	private function record_screen_name( $screen ) {
		// Route patterns only: letters, digits, slashes, colons, dashes, underscores.
		$screen = substr( preg_replace( '/[^a-zA-Z0-9\/:_-]/', '', $screen ), 0, 100 );

		if ( '' !== $screen ) {
			$this->record( 'screen_viewed', array( 'screen' => $screen ) );
		}
	}

	/**
	 * Filter `rest_dispatch_request`: remember which route pattern matched.
	 *
	 * @param mixed            $result  Dispatch result (returned unchanged).
	 * @param \WP_REST_Request $request Request.
	 * @param string           $route   Matched route pattern.
	 * @param array            $handler Route handler.
	 * @return mixed
	 */
	public function remember_route( $result, $request, $route, $handler ) {
		if ( $request instanceof \WP_REST_Request && is_string( $route ) ) {
			$this->matched_routes[ spl_object_id( $request ) ] = $route;
		}

		return $result;
	}

	/**
	 * Filter `rest_request_after_callbacks`: record the WPFunnels action and its outcome.
	 *
	 * Writes (POST/PUT/PATCH/DELETE) are always recorded; reads (GET) only when
	 * they fail or are listed in OVERRIDES as actions.
	 *
	 * @param mixed            $response Callback result (returned unchanged).
	 * @param array            $handler  Route handler.
	 * @param \WP_REST_Request $request  Request.
	 * @return mixed
	 */
	public function record_rest_action( $response, $handler, $request ) {
		if ( ! $request instanceof \WP_REST_Request ) {
			return $response;
		}

		$key     = spl_object_id( $request );
		$pattern = isset( $this->matched_routes[ $key ] ) ? $this->matched_routes[ $key ] : null;
		unset( $this->matched_routes[ $key ] );

		try {
			// Rejected by argument validation or the permission check: WordPress
			// skipped rest_dispatch_request, so look the pattern up instead.
			if ( null === $pattern && is_wp_error( $response ) ) {
				$pattern = $this->resolve_route( $request );
			}
			if ( null === $pattern ) {
				return $response;
			}

			$path = $this->strip_namespace( $pattern );
			if ( null === $path || in_array( $path, self::IGNORED_ROUTES, true ) || ! $this->can_record() ) {
				return $response;
			}

			$method = strtoupper( $request->get_method() );
			$class  = 'GET' === $method || 'DELETE' === $method ? $method : 'WRITE';
			$route  = $this->normalize_route( $path );

			list( $ok, $status, $error_code ) = $this->outcome( $response, $class );

			$name = isset( self::OVERRIDES[ $class . ' ' . $route ] ) ? self::OVERRIDES[ $class . ' ' . $route ] : null;
			if ( null === $name ) {
				if ( 'GET' === $class && $ok ) {
					return $response; // Plain reads are covered by screen views.
				}
				$name = $this->derive_name( $route, $class );
			}

			$properties = array(
				'area'  => $this->area_of( $route ),
				'route' => $class . ' ' . $route,
			);

			if ( 'ai' === $properties['area'] || 'settings_ai' === substr( $name, 0, 11 ) ) {
				$properties['ai_provider'] = $this->active_ai_provider();
			}

			if ( ! $ok ) {
				$name                     .= '_failed';
				$properties['http_status'] = $status;
				if ( '' !== $error_code ) {
					$properties['error_code'] = $error_code;
				}
			}

			$this->record( $name, $properties );
		} catch ( \Throwable $e ) {
			// Telemetry must never break a request.
			return $response;
		}

		return $response;
	}

	/**
	 * Find the registered route pattern a WPFunnels request path matches, the
	 * way WP_REST_Server does. Only used for requests rejected before dispatch.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return string|null
	 */
	private function resolve_route( $request ) {
		$path = (string) $request->get_route();
		if ( null === $this->strip_namespace( $path ) || ! function_exists( 'rest_get_server' ) ) {
			return null;
		}

		foreach ( array_keys( rest_get_server()->get_routes() ) as $route ) {
			if ( null !== $this->strip_namespace( $route ) && preg_match( '@^' . $route . '$@i', $path ) ) {
				return $route;
			}
		}

		return null;
	}

	/**
	 * Record one MCP request. Called by FunnelMcpObservabilityHandler for every
	 * `mcp.request` metric the MCP adapter emits.
	 *
	 * Events:
	 *   mcp_client_connected  (initialize)     client_name, transport
	 *   mcp_tool_called       (tools/call)     tool, tool_area, client_name
	 *   mcp_prompt_used       (prompts/get)    prompt, client_name
	 *   mcp_resource_read     (resources/read) client_name
	 * Each gets `_failed` plus error_code / error_category on failure.
	 *
	 * @param array $tags Adapter tags: method, status, params, tool_name, error_code, ...
	 * @return void
	 */
	public function record_mcp_request( array $tags ) {
		try {
			$method = isset( $tags['method'] ) ? (string) $tags['method'] : '';
			if ( ! isset( self::MCP_METHODS[ $method ] ) || ! $this->can_record() ) {
				return;
			}

			$name   = self::MCP_METHODS[ $method ];
			$params = isset( $tags['params'] ) && is_array( $tags['params'] ) ? $tags['params'] : array();
			$client = '';

			if ( 'initialize' === $method ) {
				$client = $this->clean_label( isset( $params['client_name'] ) ? $params['client_name'] : '' );
				if ( '' !== $client ) {
					update_option( self::MCP_CLIENT_OPTION, $client, false );
				}
			}

			$properties = array(
				'client_name' => '' !== $client ? $client : (string) get_option( self::MCP_CLIENT_OPTION, 'unknown' ),
				'transport'   => $this->clean_label( isset( $tags['transport'] ) ? $tags['transport'] : '' ),
			);

			if ( 'tools/call' === $method ) {
				$tool                    = (string) ( isset( $tags['tool_name'] ) ? $tags['tool_name'] : ( isset( $params['name'] ) ? $params['name'] : '' ) );
				$properties['tool']      = $this->short_tool_name( $tool );
				$properties['tool_area'] = $this->tool_area( $tool );
				if ( isset( $params['arguments_count'] ) ) {
					$properties['arguments_count'] = (int) $params['arguments_count'];
				}
			} elseif ( 'prompts/get' === $method ) {
				$properties['prompt'] = $this->short_tool_name( (string) ( isset( $params['name'] ) ? $params['name'] : '' ) );
			}

			if ( isset( $tags['status'] ) && 'error' === $tags['status'] ) {
				$name .= '_failed';
				if ( isset( $tags['error_code'] ) && is_scalar( $tags['error_code'] ) ) {
					$properties['error_code'] = (string) $tags['error_code'];
				}
				if ( isset( $tags['error_category'] ) ) {
					$properties['error_category'] = $this->clean_label( $tags['error_category'] );
				}
			}

			$this->record( $name, $properties );
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Record one tool call made by the in-app AI copilot (ToolGateway).
	 *
	 * @param string $tool_name  Ability name, e.g. `wpfunnels/create-funnel`.
	 * @param bool   $ok         Whether the tool succeeded.
	 * @param string $error_code WP_Error code on failure.
	 * @return void
	 */
	public function record_ai_tool( $tool_name, $ok, $error_code = '' ) {
		try {
			if ( ! $this->can_record() ) {
				return;
			}

			$properties = array(
				'tool'        => $this->short_tool_name( $tool_name ),
				'tool_area'   => $this->tool_area( $tool_name ),
				'ai_provider' => $this->active_ai_provider(),
			);

			if ( ! $ok && '' !== $error_code ) {
				$properties['error_code'] = sanitize_key( $error_code );
			}

			$this->record( $ok ? 'ai_tool_called' : 'ai_tool_called_failed', $properties );
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * `add_option__wpfnl_ai_settings`: first AI settings save.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  New value.
	 * @return void
	 */
	public function on_ai_settings_added( $option, $value ) {
		$this->on_ai_settings_updated( array(), $value );
	}

	/**
	 * `update_option__wpfnl_ai_settings`: turn the settings diff into events.
	 *
	 * Events: ai_provider_connected / ai_provider_disconnected (provider, model),
	 * ai_provider_switched (provider), ai_model_changed (provider, model),
	 * ai_assistant_enabled / ai_assistant_disabled, and
	 * ai_mail_mint_connection_enabled / _disabled. API keys are never read.
	 *
	 * @param mixed $old_value Previous settings.
	 * @param mixed $new_value New settings.
	 * @return void
	 */
	public function on_ai_settings_updated( $old_value, $new_value ) {
		try {
			$old = is_array( $old_value ) ? $old_value : array();
			$new = is_array( $new_value ) ? $new_value : array();

			$old_providers = $this->connected_ai_providers( $old );
			$new_providers = $this->connected_ai_providers( $new );

			foreach ( array_diff_key( $new_providers, $old_providers ) as $provider => $model ) {
				$this->record( 'ai_provider_connected', array( 'provider' => $provider, 'model' => $model ) );
			}
			foreach ( array_diff_key( $old_providers, $new_providers ) as $provider => $model ) {
				$this->record( 'ai_provider_disconnected', array( 'provider' => $provider, 'model' => $model ) );
			}
			foreach ( array_intersect_key( $new_providers, $old_providers ) as $provider => $model ) {
				if ( $model !== $old_providers[ $provider ] ) {
					$this->record( 'ai_model_changed', array( 'provider' => $provider, 'model' => $model ) );
				}
			}

			$old_active = isset( $old['active_provider'] ) ? sanitize_key( (string) $old['active_provider'] ) : '';
			$new_active = isset( $new['active_provider'] ) ? sanitize_key( (string) $new['active_provider'] ) : '';
			if ( '' !== $new_active && $new_active !== $old_active && isset( $old_providers[ $new_active ] ) ) {
				$this->record( 'ai_provider_switched', array( 'provider' => $new_active ) );
			}

			$old_enabled = ! empty( $old['enabled'] );
			$new_enabled = ! empty( $new['enabled'] );
			if ( $old_enabled !== $new_enabled ) {
				$this->record( $new_enabled ? 'ai_assistant_enabled' : 'ai_assistant_disabled', array( 'provider' => $new_active ) );
			}

			$old_mint = ! empty( $old['use_mail_mint_connection'] );
			$new_mint = ! empty( $new['use_mail_mint_connection'] );
			if ( $old_mint !== $new_mint ) {
				$this->record( $new_mint ? 'ai_mail_mint_connection_enabled' : 'ai_mail_mint_connection_disabled' );
			}
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * `add_option__wpfnl_mcp_enabled`.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  New value.
	 * @return void
	 */
	public function on_mcp_enabled_added( $option, $value ) {
		$this->on_mcp_enabled_updated( 'yes', $value );
	}

	/**
	 * `update_option__wpfnl_mcp_enabled`: MCP server switched on or off.
	 *
	 * @param mixed $old_value Previous value ('yes' / 'no').
	 * @param mixed $new_value New value.
	 * @return void
	 */
	public function on_mcp_enabled_updated( $old_value, $new_value ) {
		$was = 'no' !== $old_value;
		$now = 'no' !== $new_value;

		if ( $was !== $now ) {
			$this->record( $now ? 'mcp_server_enabled' : 'mcp_server_disabled' );
		}
	}

	/**
	 * Connected AI providers and their models, from an AI settings array.
	 *
	 * @param array $settings AI settings.
	 * @return array provider => model
	 */
	private function connected_ai_providers( array $settings ) {
		$connected = array();
		$providers = isset( $settings['providers'] ) && is_array( $settings['providers'] ) ? $settings['providers'] : array();

		foreach ( $providers as $provider => $config ) {
			if ( is_array( $config ) && ( ! empty( $config['key'] ) || ! empty( $config['connected'] ) ) ) {
				$connected[ sanitize_key( (string) $provider ) ] = $this->clean_label( isset( $config['model'] ) ? $config['model'] : '' );
			}
		}

		return $connected;
	}

	/**
	 * Active AI provider slug, or '' when none.
	 *
	 * @return string
	 */
	private function active_ai_provider() {
		$settings = get_option( self::AI_SETTINGS_OPTION, array() );

		return is_array( $settings ) && isset( $settings['active_provider'] ) ? sanitize_key( (string) $settings['active_provider'] ) : '';
	}

	/**
	 * Same short name for copilot and MCP tools:
	 * `wpfunnels/create-funnel` (ability) and `wpfunnels-create-funnel`
	 * (MCP flattens `/` to `-`) both become `create_funnel`.
	 *
	 * @param string $tool_name Ability, MCP tool or prompt name.
	 * @return string
	 */
	private function short_tool_name( $tool_name ) {
		$short = strtolower( substr( (string) strrchr( '/' . $tool_name, '/' ), 1 ) );
		$short = preg_replace( '/^(wpfunnels-pro-|wpfunnels-)/', '', $short );

		return sanitize_key( str_replace( '-', '_', $short ) );
	}

	/**
	 * Feature area of a tool, from keywords in its name.
	 *
	 * @param string $tool_name Ability name.
	 * @return string
	 */
	private function tool_area( $tool_name ) {
		$short = str_replace( '_', '-', $this->short_tool_name( $tool_name ) );

		foreach ( self::TOOL_AREAS as $keyword => $area ) {
			if ( false !== strpos( $short, $keyword ) ) {
				return $area;
			}
		}

		return 'other';
	}

	/**
	 * Short, safe label (client names, model ids, categories).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function clean_label( $value ) {
		return is_scalar( $value ) ? substr( preg_replace( '/[^a-zA-Z0-9 ._:\/-]/', '', (string) $value ), 0, 60 ) : '';
	}

	/**
	 * Return the route without its namespace, or null if it isn't a WPFunnels admin route.
	 *
	 * @param string $pattern Full route pattern, e.g. `/wpfunnels/v1/funnels`.
	 * @return string|null
	 */
	private function strip_namespace( $pattern ) {
		foreach ( self::NAMESPACES as $namespace ) {
			$prefix = '/' . $namespace;
			if ( 0 !== strpos( $pattern, $prefix . '/' ) ) {
				continue;
			}

			$path = substr( $pattern, strlen( $prefix ) );
			if ( isset( self::NAMESPACE_PATHS[ $namespace ] ) ) {
				foreach ( self::NAMESPACE_PATHS[ $namespace ] as $from => $to ) {
					if ( $path === $from || 0 === strpos( $path, $from . '/' ) ) {
						$path = $to . substr( $path, strlen( $from ) );
						break;
					}
				}
			}

			return $path;
		}

		return null;
	}

	/**
	 * Turn a route regex into a readable pattern:
	 * `/funnels/(?P<funnel_id>\d+)/duplicate` → `/funnels/:funnel_id/duplicate`,
	 * and optional groups `(?:...)?` are dropped.
	 *
	 * @param string $path Route regex without namespace.
	 * @return string
	 */
	public function normalize_route( $path ) {
		do {
			$previous = $path;
			$path     = preg_replace( '/\(\?:[^()]*\)\?/', '', $path );
			$path     = preg_replace( '/\(\?:(?:[^()]|\((?:[^()])*\))*\)\?/', '', $path );
		} while ( $path !== $previous );

		$route = preg_replace( '/\(\?P<([a-zA-Z0-9_]+)>(?:[^()]|\([^()]*\))*\)/', ':$1', $path );

		return rtrim( $route, '/' );
	}

	/**
	 * Name an action from its route.
	 *
	 * Examples:
	 *   WRITE  /funnels                           → funnel_created
	 *   DELETE /funnels/:funnel_id                → funnel_deleted
	 *   WRITE  /funnels/:funnel_id/duplicate      → funnel_duplicated
	 *   DELETE /steps/:step_id/delete-step        → step_deleted
	 *   WRITE  /abtesting/create-varient          → ab_test_variant_created
	 *   WRITE  /automation-canvas/:id             → automation_updated
	 *   WRITE  /settings/ai                       → settings_ai_saved
	 *
	 * A verb word in the route (create, save, delete, duplicate, ...) moves to
	 * the end in the past tense; words repeating the area are dropped.
	 *
	 * @param string $route Normalized route.
	 * @param string $class GET, WRITE or DELETE.
	 * @return string
	 */
	public function derive_name( $route, $class ) {
		$segments = array_values( array_filter( explode( '/', $route ), 'strlen' ) );
		$literals = array_values(
			array_filter(
				$segments,
				function ( $segment ) {
					return ':' !== $segment[0];
				}
			)
		);

		if ( empty( $literals ) ) {
			return 'unknown';
		}

		$area          = $this->area_of( $route );
		$area_words    = explode( '_', $area );
		$ends_in_param = ':' === substr( end( $segments ), 0, 1 );
		$words         = array();

		foreach ( array_slice( $literals, 1 ) as $literal ) {
			foreach ( explode( '_', $this->to_snake( $literal ) ) as $word ) {
				$word = 'varient' === $word ? 'variant' : $word; // Route typo.
				if ( '' === $word || in_array( $word, self::NOISE_WORDS, true )
					|| in_array( $word, $area_words, true ) || in_array( rtrim( $word, 's' ), $area_words, true ) ) {
					continue;
				}
				$words[] = $word;
			}
		}

		$verb = null;
		foreach ( $words as $index => $word ) {
			if ( isset( self::VERBS[ $word ] ) ) {
				$verb = self::VERBS[ $word ];
				unset( $words[ $index ] );
				break;
			}
		}

		if ( 'DELETE' === $class && ( null === $verb || '' === $verb ) ) {
			$verb = 'deleted';
		} elseif ( null === $verb && 'WRITE' === $class && empty( $words ) ) {
			$verb = $ends_in_param ? 'updated' : 'created';
		} elseif ( null === $verb && 'WRITE' === $class && 'settings' === $area && 1 === count( $words ) ) {
			$verb = 'saved';
		} elseif ( null === $verb ) {
			$verb = '';
		}

		$name = implode( '_', array_filter( array_merge( array( $area ), $words, array( $verb ) ), 'strlen' ) );

		return trim( preg_replace( '/_+/', '_', preg_replace( '/[^a-z0-9_]/', '_', strtolower( $name ) ) ), '_' );
	}

	/**
	 * Feature area of a normalized route (its first literal segment).
	 *
	 * @param string $route Normalized route.
	 * @return string
	 */
	private function area_of( $route ) {
		$segments = array_values( array_filter( explode( '/', $route ), 'strlen' ) );
		$first    = isset( $segments[0] ) ? $segments[0] : '';

		return isset( self::AREA_ALIASES[ $first ] ) ? self::AREA_ALIASES[ $first ] : $this->to_snake( $first );
	}

	/**
	 * `saveFunnel` / `status-update` → `save_funnel` / `status_update`.
	 *
	 * @param string $segment Route segment.
	 * @return string
	 */
	private function to_snake( $segment ) {
		return strtolower( str_replace( '-', '_', preg_replace( '/(?<!^)[A-Z]/', '_$0', $segment ) ) );
	}

	/**
	 * Work out whether a REST callback succeeded.
	 *
	 * WPFunnels signals failure with an HTTP status >= 400, a WP_Error, or a
	 * 200 body with `success: false` or `status: 'error'`. The callback result is
	 * often a plain array (rest_ensure_response() runs after this filter). For
	 * reads, a `success: false` body usually means "nothing found", so only
	 * HTTP errors count there.
	 *
	 * @param mixed  $response Callback result.
	 * @param string $class    GET, WRITE or DELETE.
	 * @return array [ bool $ok, int $status, string $error_code ]
	 */
	public function outcome( $response, $class = 'WRITE' ) {
		if ( is_wp_error( $response ) ) {
			$data   = $response->get_error_data();
			$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 500;

			return array( false, $status, sanitize_key( (string) $response->get_error_code() ) );
		}

		$status = 200;
		$data   = $response;
		if ( $response instanceof \WP_HTTP_Response ) {
			$status = (int) $response->get_status();
			$data   = $response->get_data();
		}

		$failed = false;
		if ( 'GET' !== $class && is_array( $data ) ) {
			$failed = ( array_key_exists( 'success', $data ) && false === $data['success'] )
				|| ( isset( $data['status'] ) && in_array( $data['status'], array( 'error', 'fail', 'failed' ), true ) );
		}

		$code = is_array( $data ) && isset( $data['code'] ) && is_string( $data['code'] ) ? sanitize_key( $data['code'] ) : '';
		$ok   = $status < 400 && ! $failed;

		return array( $ok, $status, $ok ? '' : $code );
	}

	/**
	 * Append an entry to the trail.
	 *
	 * Repeating the last unsent activity with the same properties within
	 * COLLAPSE_WINDOW updates that entry and bumps its count instead.
	 *
	 * @param string $activity   Activity name.
	 * @param array  $properties Extra scalar properties.
	 * @return void
	 */
	public function record( $activity, array $properties = array() ) {
		try {
			$trail = $this->get_trail();
			$now   = time();
			$index = count( $trail ) - 1;

			if ( $index >= 0 ) {
				$last = $trail[ $index ];
				if ( empty( $last['s'] ) && $last['a'] === $activity
					&& ( isset( $last['p'] ) ? $last['p'] : array() ) === $properties
					&& $now - (int) $last['t'] <= self::COLLAPSE_WINDOW ) {
					$trail[ $index ]['t'] = $now;
					$trail[ $index ]['n'] = (int) ( isset( $last['n'] ) ? $last['n'] : 1 ) + 1;
					$this->save_trail( $trail );
					return;
				}
			}

			$trail[] = array(
				'a' => $activity,
				'p' => $properties,
				't' => $now,
			);

			$this->save_trail( $trail );
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Load the trail, dropping expired or malformed entries.
	 *
	 * @return array
	 */
	public function get_trail() {
		$trail = get_option( self::OPTION, array() );

		if ( ! is_array( $trail ) ) {
			return array();
		}

		$cutoff = time() - self::MAX_AGE;

		return array_values(
			array_filter(
				$trail,
				function ( $entry ) use ( $cutoff ) {
					return is_array( $entry ) && isset( $entry['a'], $entry['t'] ) && $entry['t'] >= $cutoff;
				}
			)
		);
	}

	/**
	 * Persist the trail, keeping the newest MAX_UNSENT unsent and KEEP_SENT sent entries.
	 *
	 * @param array $trail Trail entries.
	 * @return void
	 */
	private function save_trail( array $trail ) {
		$kept   = array();
		$sent   = 0;
		$unsent = 0;

		for ( $i = count( $trail ) - 1; $i >= 0; $i-- ) {
			if ( ! empty( $trail[ $i ]['s'] ) ) {
				if ( $sent++ < self::KEEP_SENT ) {
					$kept[] = $trail[ $i ];
				}
			} elseif ( $unsent++ < self::MAX_UNSENT ) {
				$kept[] = $trail[ $i ];
			}
		}

		$kept = array_reverse( $kept );

		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, $kept, '', false );
			return;
		}

		update_option( self::OPTION, $kept, false );
	}

	/**
	 * Whether the site owner opted in to usage tracking.
	 *
	 * @return bool
	 */
	private function is_opted_in() {
		return $this->client && method_exists( $this->client, 'get_optin_state' )
			&& 'yes' === $this->client->get_optin_state();
	}

	/**
	 * Keep the daily flush scheduled only while the site is opted in.
	 *
	 * @return void
	 */
	public function maybe_schedule_flush() {
		try {
			$next = wp_next_scheduled( self::FLUSH_HOOK );

			if ( $this->is_opted_in() ) {
				if ( ! $next ) {
					wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::FLUSH_HOOK );
				}
			} elseif ( $next ) {
				wp_unschedule_event( $next, self::FLUSH_HOOK );
			}
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Daily cron (opted-in sites only): send unsent entries and mark them sent.
	 *
	 * @return void
	 */
	public function flush() {
		if ( ! $this->is_opted_in() ) {
			return;
		}

		$unsent = $this->unsent( $this->get_trail() );
		if ( empty( $unsent ) ) {
			return;
		}

		$last_sent_at = max( array_column( $unsent, 't' ) );

		if ( ! $this->send( $unsent ) ) {
			return;
		}

		// Re-read so entries recorded while sending are kept unsent.
		$trail = $this->get_trail();
		foreach ( $trail as $index => $entry ) {
			if ( empty( $entry['s'] ) && (int) $entry['t'] <= $last_sent_at ) {
				$trail[ $index ]['s'] = true;
			}
		}
		$this->save_trail( $trail );
	}

	/**
	 * On deactivation (every site): send unsent entries plus a summary event.
	 *
	 * @return void
	 */
	public function send_on_deactivation() {
		try {
			wp_clear_scheduled_hook( self::FLUSH_HOOK );

			// Always send the context, even with an empty trail: deactivation_source
			// explains the SDK's `reason: none` deactivations on sites with no activity.
			$trail = $this->get_trail();

			if ( $this->send( $this->unsent( $trail ), $this->build_context( $trail ) ) ) {
				delete_option( self::OPTION );
			}
		} catch ( \Throwable $e ) {
			// Never block deactivation on telemetry.
			return;
		}
	}

	/**
	 * Entries not yet sent.
	 *
	 * @param array $trail Trail entries.
	 * @return array
	 */
	private function unsent( array $trail ) {
		return array_values(
			array_filter(
				$trail,
				function ( $entry ) {
					return empty( $entry['s'] );
				}
			)
		);
	}

	/**
	 * Summary of the whole trail for the `deactivation_context` event.
	 *
	 * @param array $trail All entries (sent and unsent).
	 * @return array
	 */
	public function build_context( array $trail ) {
		$now          = time();
		$last         = empty( $trail ) ? null : end( $trail );
		$last_feature = '';
		$last_screen  = '';
		$last_failure = '';
		$failures     = 0;

		foreach ( $trail as $entry ) {
			if ( 'screen_viewed' === $entry['a'] ) {
				$last_screen = isset( $entry['p']['screen'] ) ? $entry['p']['screen'] : '';
				continue;
			}
			$last_feature = $entry['a'];
			if ( '_failed' === substr( $entry['a'], -7 ) ) {
				$last_failure = $entry['a'];
				++$failures;
			}
		}

		$installed = (int) get_option( 'wpfunnels_installed_time', 0 );

		return array(
			'deactivation_source'         => $this->deactivation_source(),
			'modal_reason_submitted'      => $this->modal_submitted(),
			'last_activity'               => $last ? $this->label( $last ) : '',
			'last_feature'                => $last_feature,
			'last_screen'                 => $last_screen,
			'last_failure'                => $last_failure,
			'failures_in_trail'           => $failures,
			'minutes_since_last_activity' => $last ? (int) floor( ( $now - (int) $last['t'] ) / MINUTE_IN_SECONDS ) : null,
			'days_since_install'          => $installed ? (int) floor( ( $now - $installed ) / DAY_IN_SECONDS ) : null,
			'trail_length'                => count( $trail ),
			'recent_activities'           => array_map( array( $this, 'label' ), array_slice( $trail, -15 ) ),
			'opted_in'                    => $this->is_opted_in(),
		);
	}

	/**
	 * Whether the deactivation feedback modal sent a reason in the last minute.
	 *
	 * WPFunnels renders its own modal but submits through the SDK's AJAX
	 * handler, which sets `<slug>_deactivation_event_sent`. The SDK deletes it
	 * in Client::deactivate(), so deactivate_wpfnl() is registered before the
	 * SDK client is built (see wpfnl.php) to read it first.
	 *
	 * @return bool
	 */
	private function modal_submitted() {
		return 'yes' === get_transient( self::SDK_SLUG . '_deactivation_event_sent' );
	}

	/**
	 * How the plugin is being deactivated.
	 *
	 * The deactivation modal only opens for the single "Deactivate" link on
	 * plugins.php; every other route makes the SDK send `reason: none`. This
	 * tells those routes apart.
	 *
	 * @return string wp_cli | rest_api | xmlrpc | cron | recovery_mode | network_admin |
	 *                bulk_action | modal_submitted | link_modal_skipped | ajax | programmatic
	 */
	public function deactivation_source() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only classification; core verified the request.
		$action  = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		$action2 = isset( $_REQUEST['action2'] ) ? sanitize_key( wp_unslash( $_REQUEST['action2'] ) ) : '';
		$network = ! empty( $_REQUEST['networkwide'] );
		// phpcs:enable

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'wp_cli';
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return 'rest_api';
		}
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return 'xmlrpc';
		}
		if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			return 'cron';
		}
		if ( function_exists( 'wp_is_recovery_mode' ) && wp_is_recovery_mode() ) {
			return 'recovery_mode';
		}
		if ( $network || ( function_exists( 'is_network_admin' ) && is_network_admin() ) ) {
			return 'network_admin';
		}
		if ( 'deactivate-selected' === $action || 'deactivate-selected' === $action2 ) {
			return 'bulk_action';
		}
		if ( 'deactivate' === $action ) {
			return $this->modal_submitted() ? 'modal_submitted' : 'link_modal_skipped';
		}
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return 'ajax';
		}

		return 'programmatic';
	}

	/**
	 * Short label for an entry, e.g. `screen:/funnels` or `funnel_created`.
	 *
	 * @param array $entry Trail entry.
	 * @return string
	 */
	private function label( $entry ) {
		return 'screen_viewed' === $entry['a'] ? 'screen:' . ( isset( $entry['p']['screen'] ) ? $entry['p']['screen'] : '' ) : $entry['a'];
	}

	/**
	 * Send entries (and optionally a deactivation summary) to PostHog.
	 *
	 * Screens go out as PostHog `$screen` events (`$screen_name` = `wpfunnels<route>`)
	 * so they appear as screens in Paths; everything else as `wpfnl_activity/<name>`.
	 *
	 * @param array      $entries Entries to send, oldest first.
	 * @param array|null $context Deactivation summary, or null.
	 * @return bool True when PostHog accepted the batch.
	 */
	protected function send( array $entries, $context = null ) {
		if ( ( empty( $entries ) && null === $context ) || '' === $this->api_key
			|| ! class_exists( PostHog::class ) || ! class_exists( Utils::class ) ) {
			return false;
		}

		$now         = time();
		$distinct_id = Utils::getUniqueSiteId();
		$base        = array(
			'plugin_name'    => 'WPFunnels',
			'plugin_version' => defined( 'WPFNL_VERSION' ) ? WPFNL_VERSION : '',
			'is_pro_active'  => defined( 'WPFNL_PRO_VERSION' ),
			'source'         => null === $context ? 'activity_trail_daily' : 'activity_trail_deactivation',
		);

		try {
			PostHog::init( $this->api_key, array( 'host' => self::POSTHOG_HOST ) );

			foreach ( $entries as $entry ) {
				$properties = array_merge(
					$base,
					isset( $entry['p'] ) && is_array( $entry['p'] ) ? $entry['p'] : array(),
					array( 'repeat_count' => (int) ( isset( $entry['n'] ) ? $entry['n'] : 1 ) )
				);

				if ( null !== $context ) {
					$properties['seconds_before_deactivation'] = max( 0, $now - (int) $entry['t'] );
				}

				if ( 'screen_viewed' === $entry['a'] ) {
					$event                      = '$screen';
					$properties['$screen_name'] = 'wpfunnels' . ( isset( $entry['p']['screen'] ) ? $entry['p']['screen'] : '' );
				} else {
					$event = self::EVENT_PREFIX . $entry['a'];
				}

				PostHog::capture(
					array(
						'distinctId' => $distinct_id,
						'event'      => $event,
						'properties' => $properties,
						'timestamp'  => (int) $entry['t'],
					)
				);
			}

			if ( null !== $context ) {
				PostHog::capture(
					array(
						'distinctId' => $distinct_id,
						'event'      => self::EVENT_PREFIX . 'deactivation_context',
						'properties' => array_merge( $base, $context ),
						'timestamp'  => $now,
					)
				);
			}

			// flush() returns the last HttpResponse (always truthy), so check the status code.
			$response = PostHog::flush();

			return is_object( $response ) && method_exists( $response, 'getResponseCode' )
				? 200 === (int) $response->getResponseCode()
				: (bool) $response;
		} catch ( \Throwable $e ) {
			// Never block the request or deactivation on telemetry.
			return false;
		}
	}
}
