<?php
/**
 * ToolGateway — bridges the agent loop to the WordPress Abilities registry.
 *
 * The AI copilot and the external MCP server share ONE tool surface: every
 * 'wpfunnels' category ability. WP_Ability::execute() enforces input validation
 * and the per-tool permission_callback as the logged-in user, so the loop never
 * bypasses the permission model.
 *
 * @package WPFunnels\AI
 * @since 3.13.0
 */

namespace WPFunnels\AI;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\AbilitiesRegistrar;
use WPFunnels\MCP\Helpers\MCPHelper;

/**
 * Class ToolGateway
 */
class ToolGateway {

	/**
	 * Cap on a single tool result fed back to the model — protects the
	 * context window from a massive response payload.
	 */
	private const RESULT_CHAR_LIMIT = 12000;

	/**
	 * Tools the loop never auto-executes; the user must approve in the UI.
	 */
	private const ALWAYS_CONFIRM = [
		'wpfunnels/update-funnel',
		'wpfunnels/reorder-steps',
		'wpfunnels/assign-products-to-step',
		'wpfunnels/update-global-settings',
		'wpfunnels/configure-store-checkout',
		'wpfunnels/set-store-checkout-conditions',
		'wpfunnels/declare-winner',
		'wpfunnels/set-automation-status',
		'wpfunnels/upsert-automation',
	];

	/**
	 * Tool → { label, pct } decoration for the copilot's progress bar.
	 *
	 * Purely cosmetic: the client falls back to a local guess when a tool
	 * isn't listed here, so this only needs the stages worth calling out by
	 * name (plan §6.3).
	 */
	private const PROGRESS_MAP = [
		'wpfunnels/get-funnel-context'       => [ 'label' => 'Reviewing your store…', 'pct' => 10 ],
		'wpfunnels/check-setup-status'       => [ 'label' => 'Checking setup…', 'pct' => 10 ],
		'wpfunnels/search-products'          => [ 'label' => 'Finding products…', 'pct' => 20 ],
		'wpfunnels/get-store-analysis'       => [ 'label' => 'Analyzing your store…', 'pct' => 20 ],
		'wpfunnels/list-funnel-templates'    => [ 'label' => 'Checking templates…', 'pct' => 25 ],
		'wpfunnels/create-funnel'            => [ 'label' => 'Creating funnel…', 'pct' => 30 ],
		'wpfunnels/import-funnel-template'   => [ 'label' => 'Importing template design…', 'pct' => 40 ],
		'wpfunnels/create-step'              => [ 'label' => 'Adding step…', 'pct' => 45 ],
		'wpfunnels/assign-products-to-step'  => [ 'label' => 'Assigning products…', 'pct' => 55 ],
		'wpfunnels/upsert-order-bump'        => [ 'label' => 'Adding order bump…', 'pct' => 65 ],
		'wpfunnels/upsert-upsell-offer'      => [ 'label' => 'Configuring upsell…', 'pct' => 70 ],
		'wpfunnels/upsert-downsell-offer'    => [ 'label' => 'Configuring downsell…', 'pct' => 72 ],
		'wpfunnels/compose-step-page'        => [ 'label' => 'Building the page…', 'pct' => 80 ],
		'wpfunnels/rewrite-step-copy'        => [ 'label' => 'Updating copy…', 'pct' => 82 ],
		'wpfunnels/get-funnel-analytics'     => [ 'label' => 'Pulling analytics…', 'pct' => 85 ],
	];

	/**
	 * Tools whose successful result renders an inline preview card in the
	 * thread instead of raw JSON (plan §6.3).
	 */
	private const PREVIEW_TOOLS = [
		'wpfunnels/create-funnel',
		'wpfunnels/import-funnel-template',
		'wpfunnels/update-funnel',
		'wpfunnels/create-step',
		'wpfunnels/reorder-steps',
		'wpfunnels/upsert-order-bump',
		'wpfunnels/compose-step-page',
	];

	/**
	 * Normalized tool definitions the current user may call.
	 *
	 * @return array [ {name, label, description, input_schema, destructive, annotations} ]
	 */
	public static function toolDefinitions() {
		$definitions = [];

		if ( function_exists( 'wp_get_abilities' ) ) {
			foreach ( wp_get_abilities() as $ability ) {
				$name = $ability->get_name();
				if ( ! str_starts_with( $name, 'wpfunnels/' ) ) {
					continue;
				}
				if ( true !== $ability->check_permissions( [] ) ) {
					continue;
				}

				$schema        = $ability->get_input_schema();
				$annotations   = $ability->get_meta_item( 'annotations', [] );
				$definitions[] = [
					'name'         => $name,
					'label'        => $ability->get_label(),
					'description'  => $ability->get_description(),
					'input_schema' => ! empty( $schema ) ? AbilitiesRegistrar::normalizeSchema( $schema ) : [ 'type' => 'object', 'properties' => new \stdClass() ],
					'destructive'  => is_array( $annotations ) && ! empty( $annotations['destructive'] ),
					'annotations'  => is_array( $annotations ) ? array_keys( array_filter( $annotations ) ) : [],
				];
			}
		} else {
			// Fallback to definitions array when core Abilities API functions are missing or stubbed.
			foreach ( AbilitiesRegistrar::getPublicDefinitions() as $name => $def ) {
				$definitions[] = [
					'name'         => $name,
					'label'        => isset( $def['label'] ) ? $def['label'] : $name,
					'description'  => isset( $def['description'] ) ? $def['description'] : '',
					'input_schema' => isset( $def['input_schema'] ) ? $def['input_schema'] : [ 'type' => 'object', 'properties' => new \stdClass() ],
					'destructive'  => ! empty( $def['destructive'] ),
					'annotations'  => isset( $def['annotations'] ) ? $def['annotations'] : [],
				];
			}
		}

		return $definitions;
	}

	/**
	 * Whether a tool requires explicit user confirmation before execution.
	 *
	 * @param string $tool_name Ability name.
	 * @return bool
	 */
	public static function requiresConfirmation( $tool_name ) {
		if ( in_array( $tool_name, self::ALWAYS_CONFIRM, true ) ) {
			return true;
		}

		if ( function_exists( 'wp_get_ability' ) ) {
			$ability = wp_get_ability( $tool_name );
			if ( $ability ) {
				$annotations = $ability->get_meta_item( 'annotations', [] );
				return is_array( $annotations ) && ! empty( $annotations['destructive'] );
			}
		}

		$public = AbilitiesRegistrar::getPublicDefinitions();
		if ( isset( $public[ $tool_name ] ) ) {
			return ! empty( $public[ $tool_name ]['destructive'] );
		}

		return true;
	}

	/**
	 * Execute a tool as the current user. Returns a JSON string content payload
	 * and an error flag.
	 *
	 * @param string $tool_name Ability name.
	 * @param array  $arguments Tool input.
	 * @return array{content: string, is_error: bool}
	 */
	public static function executeTool( $tool_name, $arguments = [] ) {
		$arguments = is_array( $arguments ) ? $arguments : [];

		if ( function_exists( 'wp_get_ability' ) ) {
			$ability = wp_get_ability( $tool_name );
			if ( ! $ability || ! str_starts_with( $tool_name, 'wpfunnels/' ) ) {
				return [
					'content'  => sprintf( 'Unknown tool "%s".', $tool_name ),
					'is_error' => true,
				];
			}
			$result = $ability->execute( $arguments );
		} else {
			$public = AbilitiesRegistrar::getPublicDefinitions();
			if ( ! isset( $public[ $tool_name ] ) ) {
				return [
					'content'  => sprintf( 'Unknown tool "%s".', $tool_name ),
					'is_error' => true,
				];
			}

			$all_defs = AbilitiesRegistrar::getDefinitions();
			if ( ! isset( $all_defs[ $tool_name ]['execute_callback'] ) ) {
				return [
					'content'  => sprintf( 'Execution callback missing for tool "%s".', $tool_name ),
					'is_error' => true,
				];
			}

			try {
				$result = call_user_func( $all_defs[ $tool_name ]['execute_callback'], $arguments );
			} catch ( \Throwable $e ) {
				$result = new \WP_Error( 'tool_exception', $e->getMessage() );
			}
		}

		if ( is_wp_error( $result ) ) {
			return [
				'content'  => wp_json_encode(
					[
						'error'   => $result->get_error_code(),
						'message' => $result->get_error_message(),
						'data'    => $result->get_error_data(),
					]
				),
				'is_error' => true,
			];
		}

		$encoded = wp_json_encode( $result );
		if ( false === $encoded ) {
			$encoded = '"<unencodable result>"';
		}

		if ( strlen( $encoded ) > self::RESULT_CHAR_LIMIT ) {
			$encoded = substr( $encoded, 0, self::RESULT_CHAR_LIMIT )
				. '"...[truncated — narrow the query with filters or smaller per_page]"';
		}

		do_action( 'wpfnl_ai_tool_executed', $tool_name, $arguments, $result );

		return [
			'content'  => $encoded,
			'is_error' => false,
		];
	}

	/**
	 * Progress decoration for a tool, if it marks a notable build stage.
	 *
	 * @param string $tool_name Ability name.
	 * @return array{label: string, pct: int}|null
	 */
	public static function progressFor( $tool_name ) {
		return isset( self::PROGRESS_MAP[ $tool_name ] ) ? self::PROGRESS_MAP[ $tool_name ] : null;
	}

	/**
	 * Build an inline preview descriptor for a successful tool call.
	 *
	 * Only funnel-shaped previews are supported today (the canvas/flow
	 * preview) — step/variant preview cards can extend this once their
	 * renderers exist. Returns null for anything that shouldn't render a
	 * card, so the thread falls back to no preview rather than raw JSON.
	 *
	 * @param string $tool_name Ability name.
	 * @param array  $result    Result from executeTool().
	 * @return array|null
	 */
	public static function previewFor( $tool_name, $result ) {
		if ( empty( $result ) || ! empty( $result['is_error'] ) || ! in_array( $tool_name, self::PREVIEW_TOOLS, true ) ) {
			return null;
		}

		$decoded = json_decode( (string) ( isset( $result['content'] ) ? $result['content'] : '' ), true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		$funnel_id = 0;
		if ( ! empty( $decoded['funnel_id'] ) ) {
			$funnel_id = (int) $decoded['funnel_id'];
		} elseif ( 'wpfunnels/create-funnel' === $tool_name && ! empty( $decoded['id'] ) ) {
			$funnel_id = (int) $decoded['id'];
		}

		if ( ! $funnel_id ) {
			return null;
		}

		$post = get_post( $funnel_id );
		if ( ! $post || ! defined( 'WPFNL_FUNNELS_POST_TYPE' ) || WPFNL_FUNNELS_POST_TYPE !== $post->post_type ) {
			return null;
		}

		return MCPHelper::formatFunnel( $post, true );
	}

	/**
	 * Short human-readable summary line for confirmation logging.
	 *
	 * @param string $tool_name Fully-qualified ability name.
	 * @param array  $arguments Tool arguments.
	 * @return string
	 */
	public static function describeCall( $tool_name, $arguments = [] ) {
		$details = self::describeCallDetails( $tool_name, $arguments );
		$line    = $details['title'];
		if ( '' !== $details['subject'] ) {
			$line .= ': ' . $details['subject'];
		}
		return '' !== $details['note'] ? $line . ' — ' . $details['note'] : $line;
	}

	/**
	 * Structured, plain-English description of a queued tool call.
	 *
	 * @param string $tool_name Fully-qualified ability name.
	 * @param array  $arguments Tool arguments.
	 * @return array{tool: string, action: string, kind: string, title: string, subject: string, note: string, risk: string, fields: array, raw: string}
	 */
	public static function describeCallDetails( $tool_name, $arguments = [] ) {
		$arguments = is_array( $arguments ) ? $arguments : [];
		$short     = str_replace( 'wpfunnels/', '', $tool_name );
		$raw       = (string) wp_json_encode( $arguments );
		if ( strlen( $raw ) > 400 ) {
			$raw = substr( $raw, 0, 400 ) . '…';
		}

		$defaults = [
			'tool'    => $short,
			'action'  => isset( $arguments['action'] ) ? (string) $arguments['action'] : '',
			'kind'    => 'generic',
			'title'   => self::humanizeToolName( $short ),
			'subject' => self::guessSubject( $arguments ),
			'note'    => '',
			'risk'    => 'high',
			'fields'  => self::humanizeArguments( $arguments ),
		];

		$known = self::describeKnownCall( $tool_name, $arguments );

		return array_merge( $defaults, $known, [ 'raw' => $raw ] );
	}

	/**
	 * Per-tool overrides for describeCallDetails().
	 *
	 * @param string $tool_name Fully-qualified ability name.
	 * @param array  $arguments Tool arguments.
	 * @return array
	 */
	private static function describeKnownCall( $tool_name, $arguments ) {
		switch ( $tool_name ) {
			case 'wpfunnels/create-funnel':
				$name = isset( $arguments['name'] ) ? (string) $arguments['name'] : '';
				$type = isset( $arguments['type'] ) ? (string) $arguments['type'] : 'wc';
				return [
					'kind'    => 'funnel',
					'action'  => 'create',
					'title'   => __( 'Create a new funnel', 'wpfnl' ),
					'subject' => $name,
					'note'    => __( 'Initializes the funnel. Steps and products will be added next.', 'wpfnl' ),
					'risk'    => 'low',
					'fields'  => self::fields(
						[
							__( 'Funnel Name', 'wpfnl' ) => $name,
							__( 'Funnel Type', 'wpfnl' ) => $type,
						]
					),
				];

			case 'wpfunnels/update-funnel':
				$id     = isset( $arguments['funnel_id'] ) ? (int) $arguments['funnel_id'] : 0;
				$name   = isset( $arguments['name'] ) ? (string) $arguments['name'] : '';
				$status = isset( $arguments['status'] ) ? (string) $arguments['status'] : '';
				$type   = isset( $arguments['type'] ) ? (string) $arguments['type'] : '';

				$subject = '' !== $name ? $name : self::refLabel( $id );
				$is_pub  = 'publish' === $status;

				return [
					'kind'    => 'funnel',
					'action'  => $status ? $status : 'update',
					'title'   => $is_pub ? __( 'Publish funnel live', 'wpfnl' ) : __( 'Update funnel settings', 'wpfnl' ),
					'subject' => $subject,
					'note'    => $is_pub ? __( 'This makes the funnel live and accessible to site visitors.', 'wpfnl' ) : '',
					'risk'    => $is_pub ? 'high' : 'medium',
					'fields'  => self::fields(
						[
							__( 'Funnel', 'wpfnl' ) => $subject,
							__( 'Status', 'wpfnl' ) => $status,
							__( 'Type', 'wpfnl' )   => $type,
						]
					),
				];

			case 'wpfunnels/create-step':
				$funnel_id = isset( $arguments['funnel_id'] ) ? (int) $arguments['funnel_id'] : 0;
				$step_type = isset( $arguments['step_type'] ) ? (string) $arguments['step_type'] : '';
				$name      = isset( $arguments['name'] ) ? (string) $arguments['name'] : ucfirst( $step_type );

				return [
					'kind'    => 'step',
					'action'  => 'create',
					'title'   => sprintf( __( 'Add %s step', 'wpfnl' ), $step_type ),
					'subject' => $name,
					'note'    => __( 'Adds a new step to the funnel flow.', 'wpfnl' ),
					'risk'    => 'low',
					'fields'  => self::fields(
						[
							__( 'Step Name', 'wpfnl' ) => $name,
							__( 'Step Type', 'wpfnl' ) => $step_type,
							__( 'Funnel ID', 'wpfnl' ) => (string) $funnel_id,
						]
					),
				];

			case 'wpfunnels/update-step':
				$id     = isset( $arguments['step_id'] ) ? (int) $arguments['step_id'] : 0;
				$name   = isset( $arguments['name'] ) ? (string) $arguments['name'] : '';
				$status = isset( $arguments['status'] ) ? (string) $arguments['status'] : '';

				return [
					'kind'    => 'step',
					'action'  => 'update',
					'title'   => __( 'Update step settings', 'wpfnl' ),
					'subject' => '' !== $name ? $name : self::refLabel( $id ),
					'note'    => __( 'Renaming a step changes its URL slug.', 'wpfnl' ),
					'risk'    => 'medium',
					'fields'  => self::fields(
						[
							__( 'Step', 'wpfnl' )   => self::refLabel( $id ),
							__( 'Name', 'wpfnl' )   => $name,
							__( 'Status', 'wpfnl' ) => $status,
						]
					),
				];

			case 'wpfunnels/reorder-steps':
				$funnel_id = isset( $arguments['funnel_id'] ) ? (int) $arguments['funnel_id'] : 0;
				$step_ids  = isset( $arguments['step_ids'] ) && is_array( $arguments['step_ids'] ) ? $arguments['step_ids'] : [];

				return [
					'kind'    => 'step',
					'action'  => 'reorder',
					'title'   => __( 'Reorder funnel steps', 'wpfnl' ),
					'subject' => sprintf( __( 'Funnel #%d', 'wpfnl' ), $funnel_id ),
					'note'    => __( 'Replaces the funnel step sequence. Omitted steps are removed.', 'wpfnl' ),
					'risk'    => 'high',
					'fields'  => self::fields(
						[
							__( 'Funnel ID', 'wpfnl' )  => (string) $funnel_id,
							__( 'New Order', 'wpfnl' )  => implode( ' → ', $step_ids ),
						]
					),
				];

			case 'wpfunnels/assign-products-to-step':
				$step_id  = isset( $arguments['step_id'] ) ? (int) $arguments['step_id'] : 0;
				$products = isset( $arguments['products'] ) && is_array( $arguments['products'] ) ? $arguments['products'] : [];
				$discount = isset( $arguments['discount_percentage'] ) ? (float) $arguments['discount_percentage'] : 0;

				$p_ids = array_filter( array_column( $products, 'product_id' ) );

				return [
					'kind'    => 'product',
					'action'  => 'assign',
					'title'   => __( 'Assign products to step', 'wpfnl' ),
					'subject' => sprintf( __( 'Step #%d', 'wpfnl' ), $step_id ),
					'note'    => __( 'Replaces existing products attached to this step.', 'wpfnl' ),
					'risk'    => 'high',
					'fields'  => self::fields(
						[
							__( 'Step ID', 'wpfnl' )     => (string) $step_id,
							__( 'Product IDs', 'wpfnl' ) => implode( ', ', $p_ids ),
							__( 'Discount', 'wpfnl' )    => $discount > 0 ? $discount . '%' : __( 'None', 'wpfnl' ),
						]
					),
				];
		}

		return [];
	}

	/**
	 * Build label/value field rows, dropping empties.
	 *
	 * @param array $pairs label => value.
	 * @return array
	 */
	private static function fields( array $pairs ) {
		$fields = [];
		foreach ( $pairs as $label => $value ) {
			if ( null === $value || '' === $value ) {
				continue;
			}
			$fields[] = [
				'label' => (string) $label,
				'value' => (string) $value,
			];
		}
		return $fields;
	}

	/**
	 * Generic argument humanizer.
	 *
	 * @param array $arguments Tool arguments.
	 * @return array
	 */
	private static function humanizeArguments( array $arguments ) {
		$pairs = [];
		foreach ( $arguments as $key => $value ) {
			if ( 'confirm' === $key ) {
				continue;
			}
			if ( is_bool( $value ) ) {
				$value = $value ? __( 'Yes', 'wpfnl' ) : __( 'No', 'wpfnl' );
			} elseif ( is_array( $value ) || is_object( $value ) ) {
				$value = (string) wp_json_encode( $value );
			}
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$value = (string) $value;
			if ( strlen( $value ) > 120 ) {
				$value = substr( $value, 0, 120 ) . '…';
			}
			$pairs[ self::humanizeKey( (string) $key ) ] = $value;
		}
		return self::fields( $pairs );
	}

	/**
	 * Humanize an argument key (e.g. `funnel_id` -> "Funnel ID").
	 *
	 * @param string $key Key slug.
	 * @return string
	 */
	private static function humanizeKey( $key ) {
		$key = trim( str_replace( '_', ' ', $key ) );
		$key = (string) preg_replace( '/\bid\b/i', 'ID', $key );
		return ucfirst( $key );
	}

	/**
	 * Humanize a tool name (e.g. `create-funnel` -> "Create funnel").
	 *
	 * @param string $short Short tool name.
	 * @return string
	 */
	private static function humanizeToolName( $short ) {
		return ucfirst( trim( str_replace( '-', ' ', $short ) ) );
	}

	/**
	 * Guess the subject line from common name-like arguments.
	 *
	 * @param array $arguments Tool input.
	 * @return string
	 */
	private static function guessSubject( array $arguments ) {
		foreach ( [ 'title', 'name', 'funnel_name', 'step_name' ] as $key ) {
			if ( ! empty( $arguments[ $key ] ) && is_scalar( $arguments[ $key ] ) ) {
				return (string) $arguments[ $key ];
			}
		}
		return '';
	}

	/**
	 * Reference label fallback.
	 *
	 * @param int $id Record ID.
	 * @return string
	 */
	private static function refLabel( $id ) {
		return $id > 0 ? sprintf( '#%d', $id ) : __( 'the selected item', 'wpfnl' );
	}
}
