<?php
/**
 * StepTools — funnel step abilities.
 *
 * @package WPFunnels\MCP
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;
use WPFunnels\Metas\Wpfnl_Step_Meta_keys;
use WPFunnels\Rest\Controllers\StepController;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

/**
 * Class StepTools
 */
class StepTools {

	/**
	 * Canonical step order used to validate and sort a funnel flow.
	 */
	private const CANONICAL_ORDER = [ 'landing', 'optin', 'checkout', 'upsell', 'downsell', 'thankyou' ];

	/**
	 * Step types a funnel may only contain once.
	 */
	private const SINGLETON_TYPES = [ 'landing', 'checkout', 'thankyou' ];

	/**
	 * Ability definitions for this domain.
	 *
	 * @return array
	 */
	public static function definitions() {
		$step_types = array_keys( MCPHelper::supportedStepTypes() );

		return [
			'wpfunnels/list-steps'     => [
				'label'               => __( 'List Funnel Steps', 'wpfnl' ),
				'description'         => 'Ordered steps of a funnel with id, name, type, view and edit URLs, plus any attached products. Use this to resolve a step name to an ID before writing.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel post ID.',
						],
					],
					'required'   => [ 'funnel_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'listSteps' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/create-step'    => [
				'label'               => __( 'Create Funnel Step', 'wpfnl' ),
				'description'         => 'Add a step to a funnel and append it to the flow. The page starts blank — content is written separately. A funnel may hold only one landing, one checkout and one thank-you step, and offer steps (upsell/downsell) require WooCommerce.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel post ID.',
						],
						'step_type' => [
							'type'        => 'string',
							'description' => 'Step type. Call get-funnel-context for what this install supports.',
							'enum'        => $step_types,
						],
						'name'      => [
							'type'        => 'string',
							'description' => 'Step name. Defaults to a title derived from the type.',
						],
					],
					'required'   => [ 'funnel_id', 'step_type' ],
				],
				'execute_callback'    => [ __CLASS__, 'createStep' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [],
			],
			'wpfunnels/update-step'    => [
				'label'               => __( 'Update Funnel Step', 'wpfnl' ),
				'description'         => 'Rename a step or change its published status. The step name also drives its URL slug, so renaming a live step changes the link visitors use.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'Step post ID.',
						],
						'name'    => [
							'type'        => 'string',
							'description' => 'New step name.',
						],
						'status'  => [
							'type'        => 'string',
							'description' => 'New post status.',
							'enum'        => [ 'publish', 'draft' ],
						],
					],
					'required'   => [ 'step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'updateStep' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/reorder-steps'  => [
				'label'               => __( 'Reorder Funnel Steps', 'wpfnl' ),
				'description'         => 'Set the complete step order of a funnel. This REPLACES the whole order, so pass every step ID — any omitted step is dropped from the flow. Read list-steps first, then send the full array.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel post ID.',
						],
						'step_ids'  => [
							'type'        => 'array',
							'description' => 'Every step ID of the funnel, in the order visitors should move through them.',
							'items'       => [ 'type' => 'integer' ],
						],
					],
					'required'   => [ 'funnel_id', 'step_ids' ],
				],
				'execute_callback'    => [ __CLASS__, 'reorderSteps' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/get-step'             => [
				'label'               => __( 'Get Step', 'wpfnl' ),
				'description'         => 'Detail for one step: name, type, status, funnel, URLs and attached products (if any). Does not return page-builder content — use get-step-outline for that.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'Step post ID.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'getStep' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/delete-step'          => [
				'label'               => __( 'Delete Step', 'wpfnl' ),
				'description'         => 'Permanently remove a step from its funnel, including its page content. This cannot be undone.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'Step post ID.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'deleteStep' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/copy-step'            => [
				'label'               => __( 'Copy Step', 'wpfnl' ),
				'description'         => 'Duplicate a step, including its page content, and append the copy to a funnel (the same funnel by default). Does not overwrite anything — the copy is a new step.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id'          => [
							'type'        => 'integer',
							'description' => 'Step to duplicate.',
						],
						'target_funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel to append the copy to. Defaults to the source step\'s own funnel.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'copyStep' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [],
			],
			'wpfunnels/get-step-settings'    => [
				'label'               => __( 'Get Step Settings', 'wpfnl' ),
				'description'         => 'Read the WPFunnels-specific settings for a step (the fields shown in its settings tab — these are separate from page-builder content).',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'Step post ID.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'getStepSettings' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/update-step-settings' => [
				'label'               => __( 'Update Step Settings', 'wpfnl' ),
				'description'         => 'Write one or more WPFunnels-specific settings for a step (from get-step-settings). Only recognized keys for the step\'s type are written; unknown keys are ignored.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id'  => [
							'type'        => 'integer',
							'description' => 'Step post ID.',
						],
						'settings' => [
							'type'        => 'object',
							'description' => 'Key/value pairs matching the keys returned by get-step-settings.',
						],
					],
					'required'   => [ 'step_id', 'settings' ],
				],
				'execute_callback'    => [ __CLASS__, 'updateStepSettings' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/get-step-capabilities' => [
				'label'               => __( 'Get Step Capabilities', 'wpfnl' ),
				'description'         => 'What step types, and which of them can hold products, this install currently supports — check this before proposing a step type.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'execute_callback'    => [ __CLASS__, 'getStepCapabilities' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/get-step-conditions'   => [
				'label'               => __( 'Get Step Conditions', 'wpfnl' ),
				'description'         => 'Read a step\'s conditional-branching setup: whether it is enabled, the condition rules that decide the true/false outcome, and which next step each outcome routes to. This is the canvas\'s "conditional rules" feature — separate from ordinary linear step order.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'Step post ID.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'getStepConditions' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/upsert-step-conditions' => [
				'label'               => __( 'Upsert Step Conditions', 'wpfnl' ),
				'description'         => 'Write a step\'s conditional-branching rules and enable/disable branching in one call. Read wpfunnels/get-step-conditions first to see the current shape (and, if you only mean to flip enabled on/off, to avoid overwriting existing rules/routing with empty ones). Enabling a step with two outgoing canvas connections is what makes conditional branching apply.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id'         => [
							'type'        => 'integer',
							'description' => 'Step post ID.',
						],
						'conditions'      => [
							'type'        => 'array',
							'description' => 'OR-groups of AND condition rows. Each item is an array of condition objects shaped like {field, condition, value, selectedCondition}, e.g. [[{"field":"optin_123","condition":"is","value":"yes","selectedCondition":""}]]. Matches the shape returned by get-step-conditions.',
							'items'       => [ 'type' => 'array' ],
						],
						'after_condition' => [
							'type'        => 'object',
							'description' => 'Next-step routing keyed by the condition outcome, e.g. {"true": 456, "false": 789}. Omit to keep the step\'s existing routing unchanged.',
						],
						'enabled'         => [
							'type'        => 'boolean',
							'description' => 'Whether conditional branching is active for this step. Defaults to true when conditions is non-empty, false when conditions is empty.',
						],
					],
					'required'   => [ 'step_id', 'conditions' ],
				],
				'execute_callback'    => [ __CLASS__, 'upsertStepConditions' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
		];
	}

	/**
	 * List the steps of a funnel.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function listSteps( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$funnel_id = (int) $funnel->ID;
		$steps     = Wpfnl_functions::get_steps( $funnel_id );
		$steps     = is_array( $steps ) ? $steps : [];

		$items = [];
		foreach ( $steps as $index => $step ) {
			$summary          = MCPHelper::formatStepSummary( $step );
			$summary['order'] = $index + 1;

			if ( MCPHelper::stepTypeHoldsProducts( $summary['step_type'] ) ) {
				$summary['products'] = ProductTools::attachedProducts( $summary['id'], $summary['step_type'] );
			}

			$items[] = $summary;
		}

		return [
			'funnel_id'   => $funnel_id,
			'funnel_name' => $funnel->post_title,
			'steps'       => $items,
			'step_count'  => count( $items ),
		];
	}

	/**
	 * Create a step and append it to the funnel flow.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function createStep( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$funnel_id  = (int) $funnel->ID;
		$step_type  = isset( $input['step_type'] ) ? sanitize_text_field( $input['step_type'] ) : '';
		$supported  = MCPHelper::supportedStepTypes();

		if ( ! isset( $supported[ $step_type ] ) ) {
			return MCPHelper::error(
				'unsupported_step_type',
				sprintf(
					'Step type "%s" is not available on this site. Supported types: %s.',
					$step_type,
					implode( ', ', array_keys( $supported ) )
				),
				[ 'supported_step_types' => array_keys( $supported ) ]
			);
		}

		$existing = Wpfnl_functions::get_steps( $funnel_id );
		$existing = is_array( $existing ) ? $existing : [];

		if ( in_array( $step_type, self::SINGLETON_TYPES, true ) ) {
			foreach ( $existing as $step ) {
				if ( isset( $step['step_type'] ) && $step_type === $step['step_type'] ) {
					return MCPHelper::error(
						'duplicate_step_type',
						sprintf(
							'This funnel already has a %s step (ID %d). A funnel may only contain one.',
							$step_type,
							isset( $step['id'] ) ? (int) $step['id'] : 0
						),
						[ 'existing_step_id' => isset( $step['id'] ) ? (int) $step['id'] : 0 ]
					);
				}
			}
		}

		$name = isset( $input['name'] ) && '' !== trim( (string) $input['name'] )
			? sanitize_text_field( $input['name'] )
			: ucfirst( $step_type );

		$step_store = Wpfnl::get_instance()->step_store;
		$step_id    = $step_store->create_step( $funnel_id, $name, $step_type );

		if ( is_wp_error( $step_id ) ) {
			return $step_id;
		}
		if ( ! $step_id ) {
			return MCPHelper::error( 'create_step_failed', 'WordPress could not create the step post.' );
		}

		$funnel_store = Wpfnl::get_instance()->funnel_store;
		$funnel_store->set_id( $funnel_id );
		// Hydrate the store's in-memory step list from the DB BEFORE appending.
		// Funnel_Store::save_steps_order() only ever appends to
		// $this->steps_order and then persists that array as the funnel's
		// COMPLETE _steps_order — set_id() alone never loads the existing
		// steps, so without this call every create-step request (each tool
		// call here is its own separate PHP request/AgentLoop::step()) starts
		// from an empty list and overwrites _steps_order with just the one
		// new step, silently discarding every step a prior call had added.
		// That's why a 4-step AI build left the funnel canvas with none of
		// them wired in.
		$funnel_store->set_steps_order();
		$funnel_store->save_steps_order( $step_id, $step_type, $name );

		// _steps_order is correct now, but the VISUAL canvas doesn't read it —
		// it renders _funnel_data's drawflow node graph, which nothing here
		// (or anywhere server-side) ever builds for an AI-created funnel. See
		// self::syncFunnelCanvasData().
		self::syncFunnelCanvasData( $funnel_id );

		ContextTools::invalidateCache();

		return [
			'success'     => true,
			'step_id'     => (int) $step_id,
			'funnel_id'   => $funnel_id,
			'step_type'   => $step_type,
			'name'        => $name,
			'view_url'    => get_permalink( $step_id ),
			'edit_url'    => get_edit_post_link( $step_id, 'raw' ),
			'next_step'   => MCPHelper::stepTypeHoldsProducts( $step_type )
				? 'Attach products with wpfunnels/assign-products-to-step.'
				: 'The page is blank; add content next.',
			'flow_notice' => self::flowNotice( $funnel_id ),
		];
	}

	/**
	 * Update a step's name or status.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function updateStep( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id   = (int) $step->ID;
		$funnel_id = (int) get_post_meta( $step_id, '_funnel_id', true );
		$changed   = [];
		$post_args = [ 'ID' => $step_id ];

		if ( isset( $input['name'] ) && '' !== trim( (string) $input['name'] ) ) {
			$post_args['post_title'] = sanitize_text_field( $input['name'] );
			$changed[]               = 'name';
		}

		if ( isset( $input['status'] ) && in_array( $input['status'], [ 'publish', 'draft' ], true ) ) {
			$post_args['post_status'] = $input['status'];
			$changed[]                = 'status';
		}

		if ( empty( $changed ) ) {
			return MCPHelper::error( 'nothing_to_update', 'Provide at least one of name or status.' );
		}

		$updated = wp_update_post( $post_args, true );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		// The funnel keeps its own copy of step names in _steps_order.
		if ( $funnel_id && in_array( 'name', $changed, true ) ) {
			self::syncStepNameInOrder( $funnel_id, $step_id, $post_args['post_title'] );
		}

		ContextTools::invalidateCache();

		return [
			'success'   => true,
			'step_id'   => $step_id,
			'funnel_id' => $funnel_id,
			'name'      => get_the_title( $step_id ),
			'status'    => get_post_status( $step_id ),
			'view_url'  => get_permalink( $step_id ),
			'changed'   => $changed,
		];
	}

	/**
	 * Replace the funnel's step order.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function reorderSteps( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$funnel_id = (int) $funnel->ID;
		$step_ids  = isset( $input['step_ids'] ) && is_array( $input['step_ids'] ) ? array_map( 'intval', $input['step_ids'] ) : [];

		if ( empty( $step_ids ) ) {
			return MCPHelper::error( 'missing_step_ids', 'Pass every step ID of the funnel in the desired order.' );
		}

		$current = Wpfnl_functions::get_steps( $funnel_id );
		$current = is_array( $current ) ? $current : [];

		$by_id = [];
		foreach ( $current as $step ) {
			if ( isset( $step['id'] ) ) {
				$by_id[ (int) $step['id'] ] = $step;
			}
		}

		// Every id must belong to this funnel — a stray id means the model mixed
		// up funnels, and silently dropping it would corrupt the flow.
		$unknown = array_values( array_diff( $step_ids, array_keys( $by_id ) ) );
		if ( ! empty( $unknown ) ) {
			return MCPHelper::error(
				'step_not_in_funnel',
				sprintf( 'These step IDs do not belong to funnel %d: %s.', $funnel_id, implode( ', ', $unknown ) ),
				[ 'unknown_step_ids' => $unknown ]
			);
		}

		$duplicates = array_keys( array_filter( array_count_values( $step_ids ), static function ( $count ) {
			return $count > 1;
		} ) );
		if ( ! empty( $duplicates ) ) {
			return MCPHelper::error(
				'duplicate_step_ids',
				sprintf( 'Step IDs repeated in the order: %s.', implode( ', ', $duplicates ) )
			);
		}

		$dropped = array_values( array_diff( array_keys( $by_id ), $step_ids ) );

		$reordered = [];
		foreach ( $step_ids as $step_id ) {
			$reordered[] = $by_id[ $step_id ];
		}

		update_post_meta( $funnel_id, '_steps_order', $reordered );

		$funnel_store = Wpfnl::get_instance()->funnel_store;
		$funnel_store->set_id( $funnel_id );
		$funnel_store->set_steps_order();

		// Keep the visual canvas's node graph in the same order.
		self::syncFunnelCanvasData( $funnel_id );

		ContextTools::invalidateCache();

		return [
			'success'      => true,
			'funnel_id'    => $funnel_id,
			'order'        => array_map(
				static function ( $step ) {
					return MCPHelper::formatStepSummary( $step );
				},
				$reordered
			),
			'dropped_step_ids' => $dropped,
			'flow_notice'  => self::flowNotice( $funnel_id ),
		];
	}

	/**
	 * Get one step.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getStep( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id   = (int) $step->ID;
		$step_type = (string) get_post_meta( $step_id, '_step_type', true );
		$funnel_id = (int) get_post_meta( $step_id, '_funnel_id', true );

		$data = [
			'id'         => $step_id,
			'name'       => $step->post_title,
			'step_type'  => $step_type,
			'status'     => $step->post_status,
			'funnel_id'  => $funnel_id,
			'view_url'   => get_permalink( $step_id ),
			'edit_url'   => get_edit_post_link( $step_id, 'raw' ),
			'created_at' => $step->post_date,
			'modified_at' => $step->post_modified,
		];

		if ( MCPHelper::stepTypeHoldsProducts( $step_type ) ) {
			$data['products'] = ProductTools::attachedProducts( $step_id, $step_type );
		}

		return $data;
	}

	/**
	 * Permanently delete a step, reusing the same controller logic the
	 * builder canvas' delete action calls.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function deleteStep( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id   = (int) $step->ID;
		$funnel_id = (int) get_post_meta( $step_id, '_funnel_id', true );

		if ( ! class_exists( '\WPFunnels\Rest\Controllers\StepController' ) ) {
			return MCPHelper::error( 'controller_unavailable', 'The step controller is not available.' );
		}

		$controller = new StepController();
		$response   = $controller->delete_step( [ 'step_id' => $step_id ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// The controller only wp_delete_post()s the step — it never prunes the
		// funnel's own _steps_order copy or the drawflow canvas graph built from
		// it, so a deleted step keeps showing up as a nameless, linkless ghost
		// node on the canvas until both are refreshed here too.
		if ( $funnel_id ) {
			$remaining = array_values(
				array_filter(
					Wpfnl_functions::get_steps( $funnel_id ),
					static function ( $step_row ) use ( $step_id ) {
						return isset( $step_row['id'] ) && (int) $step_row['id'] !== $step_id;
					}
				)
			);
			update_post_meta( $funnel_id, '_steps_order', $remaining );

			$funnel_store = Wpfnl::get_instance()->funnel_store;
			$funnel_store->set_id( $funnel_id );
			$funnel_store->set_steps_order();

			self::syncFunnelCanvasData( $funnel_id );
		}

		ContextTools::invalidateCache();

		return [
			'success'   => true,
			'step_id'   => $step_id,
			'funnel_id' => $funnel_id,
		];
	}

	/**
	 * Duplicate a step, reusing the same clone logic the builder canvas'
	 * copy/paste action calls.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function copyStep( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id          = (int) $step->ID;
		$source_funnel_id = (int) get_post_meta( $step_id, '_funnel_id', true );
		$target_funnel_id = isset( $input['target_funnel_id'] ) ? (int) $input['target_funnel_id'] : $source_funnel_id;

		$target = MCPHelper::requireFunnel( $target_funnel_id );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		if ( ! class_exists( '\WPFunnels\Rest\Controllers\StepController' ) ) {
			return MCPHelper::error( 'controller_unavailable', 'The step controller is not available.' );
		}

		$controller = new StepController();
		$response   = $controller->paste_step(
			[
				'stepId'   => $step_id,
				'funnelId' => $target_funnel_id,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		ContextTools::invalidateCache();

		$data = is_array( $response ) ? $response : ( method_exists( $response, 'get_data' ) ? $response->get_data() : [] );

		return [
			'success'   => true,
			'funnel_id' => $target_funnel_id,
			'result'    => $data,
		];
	}

	/**
	 * Read a step's WPFunnels-specific settings (allow-listed by step type).
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getStepSettings( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id      = (int) $step->ID;
		$step_type    = (string) get_post_meta( $step_id, '_step_type', true );
		$default_meta = Wpfnl_functions::get_step_default_meta( $step_type );

		$settings = [];
		foreach ( (array) $default_meta as $key => $meta ) {
			$settings[ $key ] = get_post_meta( $step_id, $key, true );
		}

		return [
			'step_id'   => $step_id,
			'step_type' => $step_type,
			'settings'  => $settings,
		];
	}

	/**
	 * Write a step's WPFunnels-specific settings.
	 *
	 * Reuses `Wpfnl_Step_Meta_keys::save_meta()` — the same allow-list logic
	 * the step settings panel writes through, so a stray key here is silently
	 * ignored rather than creating an unrecognized meta entry.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function updateStepSettings( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$settings = isset( $input['settings'] ) && is_array( $input['settings'] ) ? $input['settings'] : [];
		if ( empty( $settings ) ) {
			return MCPHelper::error( 'missing_settings', 'Provide at least one setting to write.' );
		}

		$step_id      = (int) $step->ID;
		$step_type    = (string) get_post_meta( $step_id, '_step_type', true );
		$default_meta = Wpfnl_functions::get_step_default_meta( $step_type );

		if ( empty( $default_meta ) ) {
			return MCPHelper::error( 'no_settings_for_type', sprintf( 'Step type "%s" has no recognized settings.', $step_type ) );
		}

		Wpfnl_Step_Meta_keys::save_meta( $step_id, $settings, $default_meta );

		ContextTools::invalidateCache();

		return self::getStepSettings( [ 'step_id' => $step_id ] );
	}

	/**
	 * What step types (and product-holding types) this install supports.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function getStepCapabilities( $input = [] ) {
		$types            = MCPHelper::supportedStepTypes();
		$product_holding   = array_values( array_filter( array_keys( $types ), [ MCPHelper::class, 'stepTypeHoldsProducts' ] ) );

		return [
			'step_types'              => $types,
			'product_holding_types'   => $product_holding,
			'active_builder'          => method_exists( '\WPFunnels\Wpfnl_functions', 'get_builder_type' )
				? Wpfnl_functions::get_builder_type()
				: '',
		];
	}

	/**
	 * Read a step's conditional-branching setup, reusing the same controller
	 * logic the builder canvas' condition drawer calls.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getStepConditions( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id = (int) $step->ID;

		if ( ! class_exists( '\WPFunnels\Rest\Controllers\StepController' ) ) {
			return MCPHelper::error( 'controller_unavailable', 'The step controller is not available.' );
		}

		$controller = new StepController();
		$response   = $controller->get_conditions( [ 'stepId' => $step_id ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = is_array( $response ) ? $response : ( method_exists( $response, 'get_data' ) ? $response->get_data() : [] );

		return [
			'step_id'         => $step_id,
			'enabled'         => isset( $data['status'] ) && 'yes' === $data['status'],
			'conditions'      => isset( $data['conditions'] ) ? $data['conditions'] : [],
			'after_condition' => isset( $data['afterCondition'] ) ? $data['afterCondition'] : [],
		];
	}

	/**
	 * Write a step's conditional-branching rules and sync its enabled flag,
	 * reusing the same controller logic the builder canvas' condition drawer
	 * calls.
	 *
	 * The drawer itself makes two separate REST calls in sequence — flip
	 * `_wpfnl_maybe_enable_condition` via update_conditional_status(), then
	 * (only when switching branching ON) write the rules via
	 * save_condition() — because it has two separate widgets (an on/off
	 * switch and a save button) that can be operated independently. A single
	 * MCP call has no such separation: the model always hands us the full
	 * desired state in one shot, so to keep the enabled flag and the stored
	 * conditions from ever drifting out of sync we always call both, in the
	 * same order the drawer does.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function upsertStepConditions( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		if ( ! isset( $input['conditions'] ) || ! is_array( $input['conditions'] ) ) {
			return MCPHelper::error( 'missing_conditions', 'Provide the conditions array — see wpfunnels/get-step-conditions for the expected shape.' );
		}

		$step_id    = (int) $step->ID;
		$conditions = $input['conditions'];
		$enabled    = isset( $input['enabled'] ) ? (bool) $input['enabled'] : ! empty( $conditions );

		if ( ! class_exists( '\WPFunnels\Rest\Controllers\StepController' ) ) {
			return MCPHelper::error( 'controller_unavailable', 'The step controller is not available.' );
		}

		$controller = new StepController();

		// Preserve the existing next-step routing when the caller doesn't pass
		// after_condition — save_condition() always overwrites it with whatever
		// is given (defaulting to an empty array), so silently omitting it here
		// would wipe out routing the user already configured on the canvas.
		if ( isset( $input['after_condition'] ) && is_array( $input['after_condition'] ) ) {
			$after_condition = $input['after_condition'];
		} else {
			$existing        = $controller->get_conditions( [ 'stepId' => $step_id ] );
			$existing_data   = is_wp_error( $existing )
				? []
				: ( is_array( $existing ) ? $existing : ( method_exists( $existing, 'get_data' ) ? $existing->get_data() : [] ) );
			$after_condition = isset( $existing_data['afterCondition'] ) && is_array( $existing_data['afterCondition'] )
				? $existing_data['afterCondition']
				: [];
		}

		$status_response = $controller->update_conditional_status(
			[
				'stepId' => $step_id,
				'status' => $enabled ? 'yes' : 'no',
			]
		);
		if ( is_wp_error( $status_response ) ) {
			return $status_response;
		}

		$save_response = $controller->save_condition(
			[
				'stepId'         => $step_id,
				'conditions'     => $conditions,
				'afterCondition' => $after_condition,
			]
		);
		if ( is_wp_error( $save_response ) ) {
			return $save_response;
		}

		ContextTools::invalidateCache();

		return [
			'success'         => true,
			'step_id'         => $step_id,
			'enabled'         => $enabled,
			'conditions'      => $conditions,
			'after_condition' => $after_condition,
		];
	}

	/**
	 * Warn when the stored flow deviates from the canonical order.
	 *
	 * Advisory only: unusual orders are legal, but the model should know it
	 * built something visitors may not expect.
	 *
	 * @param int $funnel_id Funnel id.
	 * @return string
	 */
	private static function flowNotice( $funnel_id ) {
		$steps = Wpfnl_functions::get_steps( $funnel_id );
		$steps = is_array( $steps ) ? $steps : [];

		$positions = [];
		foreach ( $steps as $step ) {
			$type  = isset( $step['step_type'] ) ? $step['step_type'] : '';
			$index = array_search( $type, self::CANONICAL_ORDER, true );
			if ( false !== $index ) {
				$positions[] = $index;
			}
		}

		$sorted = $positions;
		sort( $sorted );

		if ( $positions !== $sorted ) {
			return 'The current order differs from the usual flow (landing, opt-in, checkout, upsell, downsell, thank-you). Reorder with wpfunnels/reorder-steps if that was not deliberate.';
		}

		return '';
	}

	/**
	 * Keep the funnel's `_steps_order` copy of a step name in sync.
	 *
	 * @param int    $funnel_id Funnel id.
	 * @param int    $step_id   Step id.
	 * @param string $name      New name.
	 * @return void
	 */
	private static function syncStepNameInOrder( $funnel_id, $step_id, $name ) {
		$steps = Wpfnl_functions::get_steps( $funnel_id );
		if ( ! is_array( $steps ) ) {
			return;
		}

		$dirty = false;
		foreach ( $steps as $index => $step ) {
			if ( isset( $step['id'] ) && (int) $step['id'] === (int) $step_id ) {
				$steps[ $index ]['name'] = $name;
				$dirty                   = true;
			}
		}

		if ( $dirty ) {
			update_post_meta( $funnel_id, '_steps_order', $steps );
		}
	}

	/**
	 * Rebuild the funnel's `_funnel_data` drawflow node graph from its current
	 * `_steps_order` list.
	 *
	 * The visual canvas (admin/src/components/funnel-window, "newUI") renders
	 * nodes exclusively from `_funnel_data['drawflow']['Home']['data']` — see
	 * Funnel_Controller::prepare_funnel_data_response(), which falls back to
	 * an empty "scratch-funnel" canvas whenever that's missing, REGARDLESS of
	 * how many entries `_steps_order`/`_steps` hold. Nothing server-side ever
	 * builds it for a normal "Add Step" click either: the manual flow only
	 * creates the step post over REST (StepController::create_step()) and
	 * leaves the browser's own drawflow.js state to insert the node and push
	 * the complete graph back via the save-funnel endpoint. An AI-driven
	 * build has no browser in the loop, so without this the canvas stays
	 * blank forever no matter how many steps actually exist.
	 *
	 * Rebuilds the WHOLE graph from scratch on every step/reorder call rather
	 * than patching the previous one — AI-built funnels are linear (per
	 * SystemPrompt's playbooks: Landing -> Checkout -> Upsell -> Downsell ->
	 * Thank You, no branching), so there's nothing bespoke to preserve, and a
	 * full rebuild is simpler and far less error-prone than diffing node ids
	 * and connections against whatever was there before.
	 *
	 * @param int $funnel_id Funnel id.
	 * @return void
	 */
	private static function syncFunnelCanvasData( $funnel_id ) {
		$steps = Wpfnl_functions::get_steps( $funnel_id );
		$steps = is_array( $steps ) ? $steps : [];

		$pos_x      = 383;
		$pos_y      = 143;
		$pos_x_step = 243;
		$node_id    = 1;
		$prev_id    = null;
		$nodes      = [];

		foreach ( $steps as $step ) {
			$step_id   = isset( $step['id'] ) ? (int) $step['id'] : 0;
			$step_type = isset( $step['step_type'] ) ? (string) $step['step_type'] : '';
			if ( ! $step_id || ! $step_type || 'addstep' === $step_type ) {
				continue;
			}

			$node = [
				'id'       => $node_id,
				'name'     => $step_type,
				'data'     => [
					'step_edit_link' => base64_encode( (string) get_edit_post_link( $step_id, 'raw' ) ),
					'step_type'      => $step_type,
					'step_id'        => $step_id,
					'step_view_link' => base64_encode( (string) get_post_permalink( $step_id ) ),
				],
				'class'    => $step_type,
				'html'     => $step_type . $step_id,
				'typenode' => 'vue',
				'inputs'   => [],
				'outputs'  => [],
				'pos_x'    => $pos_x,
				'pos_y'    => $pos_y,
			];

			if ( null !== $prev_id ) {
				$node['inputs']['input_1']              = [
					'connections' => [ [ 'node' => $prev_id, 'input' => 'output_1' ] ],
				];
				$nodes[ $prev_id ]['outputs']['output_1'] = [
					'connections' => [ [ 'node' => $node_id, 'output' => 'input_1' ] ],
				];
			}

			$nodes[ $node_id ] = $node;
			$prev_id           = $node_id;
			$node_id++;
			$pos_x += $pos_x_step;
		}

		// Trailing "+ Add step" ghost node — same as a brand-new/empty
		// canvas — so there's still somewhere to click to keep building.
		$ghost = [
			'id'       => $node_id,
			'name'     => 'addstep',
			'data'     => [
				'step_type'       => 'addstep',
				'node_identifier' => wp_rand( 100, 500 ),
			],
			'class'    => 'addstep',
			'html'     => 'addstep' . $node_id,
			'typenode' => 'vue',
			'inputs'   => [],
			'outputs'  => [],
			'pos_x'    => $pos_x,
			'pos_y'    => $pos_y,
		];
		if ( null !== $prev_id ) {
			$ghost['inputs']['input_1']               = [
				'connections' => [ [ 'node' => $prev_id, 'input' => 'output_1' ] ],
			];
			$nodes[ $prev_id ]['outputs']['output_1'] = [
				'connections' => [ [ 'node' => $node_id, 'output' => 'input_1' ] ],
			];
		}
		$nodes[ $node_id ] = $ghost;

		update_post_meta(
			$funnel_id,
			'_funnel_data',
			[ 'drawflow' => [ 'Home' => [ 'data' => $nodes ] ] ]
		);

		// _steps mirrors _steps_order — Funnel_Controller::prepare_funnel_data_response()
		// reads _steps (not _steps_order) for the response's `steps_order` field.
		update_post_meta( $funnel_id, '_steps', $steps );
	}
}
