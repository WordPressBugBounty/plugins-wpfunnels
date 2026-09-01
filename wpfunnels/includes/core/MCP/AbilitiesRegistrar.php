<?php
/**
 * AbilitiesRegistrar — single source of truth for WPFunnels abilities.
 *
 * Every tool is declared once here (via the Tools\* definition arrays) and
 * registered with the WordPress Abilities API. The same registry backs both the
 * MCP server for external agents and the in-plugin copilot, so a new tool needs
 * no second wiring.
 *
 * Every execute callback is wrapped with:
 *   1. the destructive hard stop — a tool annotated `destructive` refuses to run
 *      without `confirm: true`, for every client, not just our own UI;
 *   2. an exception guard that converts a thrown error into a WP_Error.
 *
 * @package WPFunnels\MCP
 * @since 3.13.0
 */

namespace WPFunnels\MCP;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/Helpers/MCPHelper.php';
require_once __DIR__ . '/Support/ElementorLayoutBuilder.php';
require_once __DIR__ . '/Tools/ContextTools.php';
require_once __DIR__ . '/Tools/FunnelTools.php';
require_once __DIR__ . '/Tools/StepTools.php';
require_once __DIR__ . '/Tools/ProductTools.php';
require_once __DIR__ . '/Tools/OptinTools.php';
require_once __DIR__ . '/Tools/TemplateTools.php';
require_once __DIR__ . '/Tools/PageContentTools.php';
require_once __DIR__ . '/Tools/ReportTools.php';
require_once __DIR__ . '/Tools/DiagnosticTools.php';
require_once __DIR__ . '/Tools/CheckoutTools.php';
require_once __DIR__ . '/Tools/OfferTools.php';
require_once __DIR__ . '/Tools/SetupWizardTools.php';
require_once __DIR__ . '/Tools/LegacyAutomationTools.php';

use WPFunnels\MCP\Tools\CheckoutTools;
use WPFunnels\MCP\Tools\ContextTools;
use WPFunnels\MCP\Tools\DiagnosticTools;
use WPFunnels\MCP\Tools\FunnelTools;
use WPFunnels\MCP\Tools\LegacyAutomationTools;
use WPFunnels\MCP\Tools\OfferTools;
use WPFunnels\MCP\Tools\OptinTools;
use WPFunnels\MCP\Tools\PageContentTools;
use WPFunnels\MCP\Tools\ProductTools;
use WPFunnels\MCP\Tools\ReportTools;
use WPFunnels\MCP\Tools\SetupWizardTools;
use WPFunnels\MCP\Tools\StepTools;
use WPFunnels\MCP\Tools\TemplateTools;

/**
 * Class AbilitiesRegistrar
 */
class AbilitiesRegistrar {

	/**
	 * Ability category all WPFunnels tools belong to.
	 */
	public const CATEGORY = 'wpfunnels';

	/**
	 * Cached definitions.
	 *
	 * @var array|null
	 */
	private static $definitions = null;

	/**
	 * All Free ability definitions, keyed by ability name.
	 *
	 * @return array
	 */
	public static function getDefinitions() {
		if ( null !== self::$definitions ) {
			return self::$definitions;
		}

		$definitions = array_merge(
			ContextTools::definitions(),
			FunnelTools::definitions(),
			StepTools::definitions(),
			ProductTools::definitions(),
			OptinTools::definitions(),
			TemplateTools::definitions(),
			PageContentTools::definitions(),
			ReportTools::definitions(),
			DiagnosticTools::definitions(),
			CheckoutTools::definitions(),
			OfferTools::definitions(),
			SetupWizardTools::definitions(),
			LegacyAutomationTools::definitions()
		);

		self::$definitions = apply_filters( 'wpfunnels/mcp_register_tools', $definitions );

		return self::$definitions;
	}

	/**
	 * Register every ability with the Abilities API.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( self::getDefinitions() as $name => $definition ) {
			wp_register_ability( $name, self::prepare( $name, $definition ) );
		}
	}

	/**
	 * Turn a definition array into the shape WP_Ability expects.
	 *
	 * @param string $name       Ability name.
	 * @param array  $definition Definition array.
	 * @return array
	 */
	public static function prepare( $name, $definition ) {
		$annotations = isset( $definition['annotations'] ) ? (array) $definition['annotations'] : [];

		$prepared = [
			'label'               => isset( $definition['label'] ) ? $definition['label'] : $name,
			'description'         => isset( $definition['description'] ) ? $definition['description'] : '',
			'category'            => self::CATEGORY,
			'input_schema'        => self::normalizeSchema( isset( $definition['input_schema'] ) ? $definition['input_schema'] : ( isset( $definition['parameters'] ) ? $definition['parameters'] : [] ) ),
			'output_schema'       => [ 'type' => 'object' ],
			'execute_callback'    => self::wrapExecuteCallback( $name, $definition ),
			'permission_callback' => isset( $definition['permission_callback'] )
				? $definition['permission_callback']
				: Helpers\MCPHelper::currentUserCan(),
			'meta'                => [
				// WP_Ability expects annotations as key => true pairs.
				'annotations' => array_fill_keys( $annotations, true ),
			],
		];

		return $prepared;
	}

	/**
	 * Add the `confirm` flag to any destructive tool's schema so a client can
	 * satisfy the hard stop without guessing the parameter name.
	 *
	 * @param array $schema      Input schema.
	 * @param bool  $destructive Whether the tool is destructive.
	 * @return array
	 */
	private static function withConfirmFlag( $schema, $destructive ) {
		if ( ! $destructive ) {
			return $schema;
		}

		if ( ! isset( $schema['properties'] ) || ! is_array( $schema['properties'] ) ) {
			$schema['properties'] = [];
		}

		$schema['properties']['confirm'] = [
			'type'        => 'boolean',
			'description' => 'Must be true to execute. Calling without it returns a preview of what would change.',
			'default'     => false,
		];

		return $schema;
	}

	/**
	 * Wrap an execute callback with the confirmation hard stop and an exception
	 * guard.
	 *
	 * @param string $name       Ability name.
	 * @param array  $definition Definition array.
	 * @return callable
	 */
	private static function wrapExecuteCallback( $name, $definition ) {
		$callback    = isset( $definition['execute_callback'] ) ? $definition['execute_callback'] : ( isset( $definition['callback'] ) ? $definition['callback'] : null );
		$annotations = isset( $definition['annotations'] ) ? (array) $definition['annotations'] : [];
		$destructive = in_array( 'destructive', $annotations, true );

		return static function ( $input = [] ) use ( $name, $callback, $destructive ) {
			$input = is_array( $input ) ? $input : [];

			if ( $destructive && empty( $input['confirm'] ) ) {
				return [
					'confirmation_required' => true,
					'tool'                  => $name,
					'message'               => sprintf(
						/* translators: %s: tool name. */
						__( 'This action changes or deletes data and needs confirmation. Re-call %s with "confirm": true to execute it.', 'wpfnl' ),
						$name
					),
					'pending_action'        => $input,
				];
			}

			// Strip the flag so tool bodies never see it: a confirmed call must
			// behave identically to one that never needed confirmation.
			unset( $input['confirm'] );

			try {
				return call_user_func( $callback, $input );
			} catch ( \Throwable $e ) {
				/**
				 * Fires when an ability throws.
				 *
				 * @since 3.13.0
				 * @param \Throwable $e     The exception.
				 * @param string     $name  Ability name.
				 * @param array      $input Tool input.
				 */
				do_action( 'wpfunnels/mcp_tool_exception', $e, $name, $input );

				return Helpers\MCPHelper::error(
					'tool_exception',
					sprintf(
						/* translators: 1: tool name, 2: error message. */
						__( '%1$s failed: %2$s', 'wpfnl' ),
						$name,
						$e->getMessage()
					)
				);
			}
		};
	}

	/**
	 * Normalize a JSON schema for transport.
	 *
	 * Recursively converts empty `properties` arrays to objects so they
	 * serialize as `{}` rather than `[]` — OpenAI's strict function schema
	 * rejects the array form.
	 *
	 * @param array $schema Input schema.
	 * @return array
	 */
	public static function normalizeSchema( $schema ) {
		if ( ! is_array( $schema ) ) {
			return [ 'type' => 'object' ];
		}

		if ( isset( $schema['properties'] ) ) {
			if ( is_array( $schema['properties'] ) && empty( $schema['properties'] ) ) {
				$schema['properties'] = new \stdClass();
			} elseif ( is_array( $schema['properties'] ) ) {
				foreach ( $schema['properties'] as $key => $property ) {
					$schema['properties'][ $key ] = self::normalizeSchema( $property );
				}
			}
		}

		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			$schema['items'] = self::normalizeSchema( $schema['items'] );
		}

		return $schema;
	}

	/**
	 * Definitions with the `confirm` flag folded into destructive schemas.
	 *
	 * Used by both the registrar and the copilot's tool gateway so both surfaces
	 * advertise the same parameters.
	 *
	 * @return array
	 */
	public static function getPublicDefinitions() {
		$public = [];

		foreach ( self::getDefinitions() as $name => $definition ) {
			$annotations = isset( $definition['annotations'] ) ? (array) $definition['annotations'] : [];
			$destructive = in_array( 'destructive', $annotations, true );

			$public[ $name ] = [
				'name'         => $name,
				'label'        => isset( $definition['label'] ) ? $definition['label'] : $name,
				'description'  => isset( $definition['description'] ) ? $definition['description'] : '',
				'input_schema' => self::normalizeSchema(
					self::withConfirmFlag( isset( $definition['input_schema'] ) ? $definition['input_schema'] : [], $destructive )
				),
				'destructive'  => $destructive,
				'annotations'  => $annotations,
			];
		}

		return $public;
	}
}
