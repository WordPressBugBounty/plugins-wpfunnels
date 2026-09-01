<?php
/**
 * MCPInit — bootstraps the WPFunnels MCP surface.
 *
 * Responsibilities:
 *   - Register the "wpfunnels" ability category.
 *   - Register all Free abilities via AbilitiesRegistrar, then fire
 *     `wpfunnels/mcp_loaded` so Pro can add its own.
 *   - Create the MCP server at POST /wp-json/wpfunnels/mcp.
 *   - Invalidate the cached install context when funnels or steps change.
 *
 * The adapter is shared with any other plugin that ships it (Mail Mint does), so
 * boot defensively: if the classes are missing or too old, skip the server
 * rather than fatal, and leave the abilities registered for the copilot.
 *
 * @package WPFunnels\MCP
 * @since 3.13.0
 */

namespace WPFunnels\MCP;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Tools\ContextTools;
use WPFunnels\MCP\Observability\FunnelMcpObservabilityHandler;

/**
 * Class MCPInit
 */
class MCPInit {

	/**
	 * Server identifier.
	 */
	private const SERVER_ID = 'wpfunnels';

	/**
	 * Wire up all hooks.
	 *
	 * Ability registration always runs (whenever the Abilities API is present):
	 * the in-plugin AI copilot calls tools through the SAME registry
	 * (ToolGateway -> wp_get_abilities()) as the external MCP server, so it
	 * needs abilities registered even on a site that has turned the external
	 * MCP endpoint off. `$expose_server` gates ONLY the external-agent surface
	 * (the MCP REST server) — the `_wpfnl_mcp_enabled` option is about
	 * whether third-party agents can reach this site, not whether the
	 * copilot has tools to call.
	 *
	 * @param bool $expose_server Whether to also boot the external MCP server.
	 * @return void
	 */
	public function init( $expose_server = true ) {
		add_action( 'wp_abilities_api_categories_init', [ $this, 'registerCategory' ] );
		add_action( 'wp_abilities_api_init', [ $this, 'registerAbilities' ] );

		if ( $expose_server ) {
			add_action( 'mcp_adapter_init', [ $this, 'registerServer' ] );

			// The adapter package is PSR-4 only: vendoring it does not boot it, and
			// nothing fires `mcp_adapter_init` until someone asks for the singleton.
			// It self-hooks on init/rest_api_init and guards against double
			// initialization, so calling this is safe even when another plugin
			// (Mail Mint ships the same package) has already done it.
			$this->bootAdapter();
		}

		// Context cache invalidation — funnel/step mutations change what the
		// model is told this site contains. Needed regardless of server exposure.
		$invalidate = [ ContextTools::class, 'invalidateCache' ];
		foreach ( [ 'wpfunnels_after_funnel_creation', 'wpfunnels_after_step_creation', 'wpfunnels/ai/funnel_created' ] as $hook ) {
			add_action( $hook, $invalidate );
		}
	}

	/**
	 * Ask the adapter for its singleton so it schedules its own bootstrap.
	 *
	 * @return void
	 */
	private function bootAdapter() {
		if ( ! self::adapterAvailable() ) {
			return;
		}

		try {
			\WP\MCP\Core\McpAdapter::instance();
		} catch ( \Throwable $e ) {
			do_action( 'wpfunnels/mcp_server_error', $e );
		}
	}

	/**
	 * Whether the Abilities API is available on this WordPress version.
	 *
	 * @return bool
	 */
	public static function abilitiesApiAvailable() {
		return function_exists( 'wp_register_ability' ) && function_exists( 'wp_register_ability_category' );
	}

	/**
	 * Whether a usable MCP adapter is loaded.
	 *
	 * Another plugin may have loaded an older copy of the package first, so check
	 * for the specific entry point we call rather than assuming the version.
	 *
	 * @return bool
	 */
	public static function adapterAvailable() {
		return class_exists( '\WP\MCP\Core\McpAdapter' )
			&& class_exists( '\WP\MCP\Transport\HttpTransport' )
			&& method_exists( '\WP\MCP\Core\McpAdapter', 'create_server' );
	}

	/**
	 * Register the ability category.
	 *
	 * @return void
	 */
	public function registerCategory() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			AbilitiesRegistrar::CATEGORY,
			[
				'label'       => __( 'WPFunnels', 'wpfnl' ),
				'description' => __( 'Funnel, step, offer and analytics abilities for WPFunnels.', 'wpfnl' ),
			]
		);
	}

	/**
	 * Register Free abilities, then let Pro register its own.
	 *
	 * @return void
	 */
	public function registerAbilities() {
		AbilitiesRegistrar::register();

		/**
		 * Fires once the Free abilities are registered.
		 *
		 * Pro (and third parties) register their own tools here and append the
		 * names via the `wpfunnels/mcp_ability_names` filter.
		 *
		 * @since 3.13.0
		 */
		do_action( 'wpfunnels/mcp_loaded' );
	}

	/**
	 * Create the MCP server.
	 *
	 * @param object $adapter McpAdapter instance passed by the adapter bootstrap.
	 * @return void
	 */
	public function registerServer( $adapter ) {
		if ( ! is_object( $adapter ) || ! method_exists( $adapter, 'create_server' ) ) {
			return;
		}

		/**
		 * Filter which abilities the MCP server exposes.
		 *
		 * @since 3.13.0
		 * @param array $ability_names Ability names.
		 */
		$ability_names = apply_filters(
			'wpfunnels/mcp_ability_names',
			array_keys( AbilitiesRegistrar::getDefinitions() )
		);

		/** Namespace of the MCP endpoint. */
		$namespace = apply_filters( 'wpfunnels/mcp_server_namespace', 'wpfunnels' );
		/** Route of the MCP endpoint. */
		$route = apply_filters( 'wpfunnels/mcp_server_route', 'mcp' );

		/**
		 * Filter the observability handler.
		 *
		 * Deliberately not the Null handler: this surface can delete funnels and
		 * flip live traffic, so calls are logged by default.
		 *
		 * FunnelMcpObservabilityHandler is the adapter's error-log sink minus the
		 * events it emits while building the server — those fire on every REST
		 * request and would otherwise flood the site's PHP error log.
		 *
		 * @since 3.13.0
		 * @param string $handler Fully-qualified handler class.
		 */
		$observability = apply_filters(
			'wpfunnels/mcp_observability_handler',
			FunnelMcpObservabilityHandler::class
		);

		try {
			$adapter->create_server(
				self::SERVER_ID,
				$namespace,
				$route,
				__( 'WPFunnels MCP Server', 'wpfnl' ),
				self::serverDescription(),
				defined( 'WPFNL_VERSION' ) ? WPFNL_VERSION : '1.0.0',
				[ '\WP\MCP\Transport\HttpTransport' ],
				'\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler',
				$observability,
				array_values( array_unique( array_filter( (array) $ability_names ) ) ),
				[], // Resources.
				[], // Prompts.
				[ $this, 'checkTransportPermission' ]
			);
		} catch ( \Throwable $e ) {
			// A shared adapter of an unexpected shape must not take the site down.
			do_action( 'wpfunnels/mcp_server_error', $e );
		}
	}

	/**
	 * Server description — also returned verbatim as the MCP `initialize`
	 * response's `instructions` field (see InitializeHandler), so this is
	 * the one piece of guidance every MCP client sees regardless of which
	 * agent or system prompt is driving it. Keep the funnel-build policy
	 * here in sync with SystemPrompt::basePrompt()'s hard rule — that one
	 * only reaches the in-plugin copilot, this one reaches everyone else.
	 *
	 * @return string
	 */
	private static function serverDescription() {
		return __(
			'AI agent tools for WPFunnels funnels, steps, offers and products. ' .
			'When asked to build or create a funnel: do not call wpfunnels/create-funnel first. ' .
			'Call wpfunnels/list-funnels to check for a similar existing funnel to mirror, then ' .
			'wpfunnels/list-funnel-templates to look for a real matching template — each template ' .
			'includes an actual page-builder design for every step, not a blank shell. If one fits, ' .
			'call wpfunnels/import-funnel-template instead of create-funnel/create-step; it creates the ' .
			'funnel and clones the real design in one call. Only hand-build with create-funnel + ' .
			'create-step when no template matches or the user explicitly asks for a from-scratch build. ' .
			'Either way, finish the job: assign-products-to-step, upsert-order-bump, and set-offer-routing ' .
			'for any accept/reject branching the user described, before proposing to publish.',
			'wpfnl'
		);
	}

	/**
	 * Transport-level capability floor.
	 *
	 * The adapter's own default is only `read`, which any subscriber has. This
	 * floor is defence in depth — the real gate is each ability's own
	 * permission_callback.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return bool
	 */
	public function checkTransportPermission( $request ) {
		/** Capability required to reach the MCP endpoint at all. */
		$capability = apply_filters( 'wpfunnels/mcp_transport_capability', 'wpf_manage_funnels', $request );
		$allowed    = current_user_can( $capability ) || current_user_can( 'manage_options' );

		/**
		 * Filter the final transport-level allow decision.
		 *
		 * @since 3.13.0
		 * @param bool             $allowed Whether the request may reach the server.
		 * @param \WP_REST_Request $request The incoming request.
		 */
		return (bool) apply_filters( 'wpfunnels/mcp_transport_allowed', $allowed, $request );
	}

	/**
	 * Public endpoint URL, for the settings screen.
	 *
	 * @return string
	 */
	public static function endpointUrl() {
		$namespace = apply_filters( 'wpfunnels/mcp_server_namespace', 'wpfunnels' );
		$route     = apply_filters( 'wpfunnels/mcp_server_route', 'mcp' );

		return rest_url( trailingslashit( $namespace ) . $route );
	}
}
