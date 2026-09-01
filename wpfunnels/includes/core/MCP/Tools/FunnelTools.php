<?php
/**
 * FunnelTools — funnel-level abilities.
 *
 * @package WPFunnels\MCP
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\Export\Wpfnl_Export;
use WPFunnels\MCP\Helpers\MCPHelper;
use WPFunnels\Modules\Admin\Funnel\Module as FunnelAdminModule;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

/**
 * Class FunnelTools
 */
class FunnelTools {

	/**
	 * Funnel types this plugin understands.
	 */
	private const TYPES = [ 'wc', 'lms', 'lead' ];

	/**
	 * Ability definitions for this domain.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/list-funnels'  => [
				'label'               => __( 'List Funnels', 'wpfnl' ),
				'description'         => 'List funnels with optional search, type and status filters. Returns id, name, status, type, step count and step types per funnel. Use this to resolve a funnel name to an ID before any write.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => array_merge(
						[
							'search' => [
								'type'        => 'string',
								'description' => 'Match against the funnel name.',
							],
							'type'   => [
								'type'        => 'string',
								'description' => 'Filter by funnel type.',
								'enum'        => [ 'wc', 'lms', 'lead', 'store_checkout' ],
							],
							'status' => [
								'type'        => 'string',
								'description' => 'Filter by post status.',
								'enum'        => [ 'publish', 'draft', 'trash', 'any' ],
								'default'     => 'any',
							],
						],
						MCPHelper::paginationSchema()
					),
				],
				'execute_callback'    => [ __CLASS__, 'listFunnels' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/get-funnel'    => [
				'label'               => __( 'Get Funnel', 'wpfnl' ),
				'description'         => 'Full detail for one funnel: metadata, ordered steps with their types and URLs, and the canvas URL. Does not return page-builder content — use get-step-outline for that.',
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
				'execute_callback'    => [ __CLASS__, 'getFunnel' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/create-funnel' => [
				'label'               => __( 'Create Funnel', 'wpfnl' ),
				'description'         => 'Create an EMPTY funnel with blank steps and no design — a fallback, not the default. Before calling this, call wpfunnels/list-funnel-templates: a matching template imports the funnel AND a real page-builder design for every step in one call (wpfunnels/import-funnel-template), which this tool cannot do. Only use create-funnel when no template fits or the user explicitly asked for a from-scratch/blank build. Steps are added separately with create-step. Type defaults to wc when WooCommerce is active, otherwise lead.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'name'   => [
							'type'        => 'string',
							'description' => 'Funnel name shown in the dashboard.',
						],
						'type'   => [
							'type'        => 'string',
							'description' => 'Funnel type. wc for WooCommerce sales, lead for opt-in, lms for course funnels.',
							'enum'        => self::TYPES,
						],
						'status' => [
							'type'        => 'string',
							'description' => 'Initial post status.',
							'enum'        => [ 'publish', 'draft' ],
							'default'     => 'draft',
						],
					],
					'required'   => [ 'name' ],
				],
				'execute_callback'    => [ __CLASS__, 'createFunnel' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [],
			],
			'wpfunnels/update-funnel' => [
				'label'               => __( 'Update Funnel', 'wpfnl' ),
				'description'         => 'Rename a funnel, change its type, publish/unpublish it, or update its tracking and offer settings (UTM defaults, Facebook Pixel, GTM, skip-offer). Publishing makes the funnel live for visitors, and changing type can strip settings that do not apply to the new type — both need confirmation.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id'            => [
							'type'        => 'integer',
							'description' => 'Funnel post ID.',
						],
						'name'                 => [
							'type'        => 'string',
							'description' => 'New funnel name.',
						],
						'type'                 => [
							'type'        => 'string',
							'description' => 'New funnel type.',
							'enum'        => self::TYPES,
						],
						'status'               => [
							'type'        => 'string',
							'description' => 'New post status. publish makes the funnel live.',
							'enum'        => [ 'publish', 'draft' ],
						],
						'utm'                  => [
							'type'        => 'object',
							'description' => 'UTM parameter defaults appended to this funnel\'s links. Any field left out keeps its current saved value.',
							'properties'  => [
								'enabled'  => [
									'type'        => 'boolean',
									'description' => 'Whether UTM parameters are appended to funnel links.',
								],
								'source'   => [
									'type'        => 'string',
									'description' => 'Default utm_source value.',
								],
								'medium'   => [
									'type'        => 'string',
									'description' => 'Default utm_medium value.',
								],
								'campaign' => [
									'type'        => 'string',
									'description' => 'Default utm_campaign value.',
								],
								'content'  => [
									'type'        => 'string',
									'description' => 'Default utm_content value.',
								],
							],
						],
						'fb_pixel_disabled'    => [
							'type'        => 'boolean',
							'description' => 'Disable Facebook Pixel tracking for this funnel.',
						],
						'gtm_disabled'         => [
							'type'        => 'boolean',
							'description' => 'Disable Google Tag Manager tracking for this funnel.',
						],
						'skip_offer'           => [
							'type'        => 'object',
							'description' => 'Conditions under which the order-bump/upsell offer is skipped for this funnel. Any field left out keeps its current saved value.',
							'properties'  => [
								'enabled'          => [
									'type'        => 'boolean',
									'description' => 'Skip the offer entirely for this funnel.',
								],
								'skip_if_quantity' => [
									'type'        => 'boolean',
									'description' => 'Skip the offer when the triggering product quantity condition is met.',
								],
							],
						],
						'skip_recurring_offer' => [
							'type'        => 'boolean',
							'description' => 'Skip showing the offer again for recurring/subscription orders. Note: this can only be turned on through this tool, not off — the underlying setting has no explicit "off" write path.',
						],
						'skip_recurring_offer_within_days' => [
							'type'        => 'integer',
							'description' => 'Number of days within which a recurring order skips the offer. A value of 0 is ignored (no-op) by the underlying setting.',
						],
					],
					'required'   => [ 'funnel_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'updateFunnel' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/trash-funnel'  => [
				'label'               => __( 'Trash Funnel', 'wpfnl' ),
				'description'         => 'Move a funnel to the trash. It stops accepting visitors immediately but can be restored with restore-funnel until it is permanently deleted.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [ 'type' => 'integer', 'description' => 'Funnel post ID.' ],
					],
					'required'   => [ 'funnel_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'trashFunnel' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/restore-funnel' => [
				'label'               => __( 'Restore Funnel', 'wpfnl' ),
				'description'         => 'Restore a trashed funnel back to its previous status.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [ 'type' => 'integer', 'description' => 'Funnel post ID.' ],
					],
					'required'   => [ 'funnel_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'restoreFunnel' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [],
			],
			'wpfunnels/delete-funnel' => [
				'label'               => __( 'Permanently Delete Funnel', 'wpfnl' ),
				'description'         => 'Permanently delete a funnel and all of its steps. This cannot be undone — prefer trash-funnel unless the site owner explicitly asked for permanent deletion.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [ 'type' => 'integer', 'description' => 'Funnel post ID.' ],
					],
					'required'   => [ 'funnel_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'deleteFunnel' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/bulk-funnel-action' => [
				'label'               => __( 'Bulk Funnel Action', 'wpfnl' ),
				'description'         => 'Trash, restore or permanently delete several funnels in one call. Each funnel gets the same per-tool confirmation guarantee as the single-funnel version.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_ids' => [
							'type'        => 'array',
							'description' => 'Funnel post IDs.',
							'items'       => [ 'type' => 'integer' ],
						],
						'action'     => [
							'type'        => 'string',
							'enum'        => [ 'trash', 'restore', 'delete' ],
						],
						'dry_run'    => [
							'type'        => 'boolean',
							'description' => 'When true, validates the funnel IDs and reports what would happen without changing anything.',
							'default'     => false,
						],
					],
					'required'   => [ 'funnel_ids', 'action' ],
				],
				'execute_callback'    => [ __CLASS__, 'bulkFunnelAction' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/export-funnel' => [
				'label'               => __( 'Export Funnel', 'wpfnl' ),
				'description'         => 'Export one or more funnels to the same portable JSON format the Import/Export screen produces, for backup or moving to another site.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_ids' => [
							'type'        => 'array',
							'description' => 'Funnel post IDs to export.',
							'items'       => [ 'type' => 'integer' ],
						],
					],
					'required'   => [ 'funnel_ids' ],
				],
				'execute_callback'    => [ __CLASS__, 'exportFunnel' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/get-funnel-preview-links' => [
				'label'               => __( 'Get Funnel Preview Links', 'wpfnl' ),
				'description'         => 'Front-end preview URLs for every step of a funnel, plus the canvas (builder) URL — use this to hand the site owner a link after building or changing a funnel.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [ 'type' => 'integer', 'description' => 'Funnel post ID.' ],
					],
					'required'   => [ 'funnel_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'getFunnelPreviewLinks' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/duplicate-funnel' => [
				'label'               => __( 'Duplicate Funnel', 'wpfnl' ),
				'description'         => 'Duplicate a funnel: clones the funnel post, all of its steps, page-builder content and settings into a new funnel named "<original> - Copy". Use this instead of manually recreating a funnel a user wants to reuse as a starting point.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel post ID to duplicate.',
						],
					],
					'required'   => [ 'funnel_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'duplicateFunnel' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [],
			],
			'wpfunnels/import-funnel' => [
				'label'               => __( 'Import Funnel', 'wpfnl' ),
				'description'         => 'Import funnel(s) from exported JSON (the "data" array from wpfunnels/export-funnel, or the parsed contents of a file exported by the Import/Export screen). Always creates NEW funnels — never overwrites an existing one. Subject to the free-plan funnel-count limit.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnels' => [
							'type'        => 'array',
							'description' => 'Exported funnel data objects, exactly as produced by wpfunnels/export-funnel\'s "data" field.',
							'items'       => [ 'type' => 'object' ],
						],
					],
					'required'   => [ 'funnels' ],
				],
				'execute_callback'    => [ __CLASS__, 'importFunnel' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
		];
	}

	/**
	 * List funnels.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function listFunnels( $input = [] ) {
		$per_page = MCPHelper::perPage( isset( $input['per_page'] ) ? $input['per_page'] : 0 );
		$page     = max( 1, isset( $input['page'] ) ? (int) $input['page'] : 1 );
		$status   = isset( $input['status'] ) && 'any' !== $input['status'] ? $input['status'] : [ 'publish', 'draft' ];

		$args = [
			'post_type'      => WPFNL_FUNNELS_POST_TYPE,
			'post_status'    => $status,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		if ( ! empty( $input['type'] ) ) {
			$args['meta_query'] = [
				[
					'key'   => '_wpfnl_funnel_type',
					'value' => sanitize_text_field( $input['type'] ),
				],
			];
		}

		$query = new \WP_Query( $args );
		$items = array_map(
			static function ( $post ) {
				return MCPHelper::formatFunnel( $post );
			},
			$query->posts
		);

		return MCPHelper::paginate( $items, $query->found_posts, $page, $per_page );
	}

	/**
	 * Get one funnel with its steps.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getFunnel( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		return MCPHelper::formatFunnel( $funnel, true );
	}

	/**
	 * Create a funnel.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function createFunnel( $input = [] ) {
		$name = isset( $input['name'] ) ? sanitize_text_field( $input['name'] ) : '';
		if ( '' === $name ) {
			return MCPHelper::error( 'missing_name', 'A funnel name is required.' );
		}

		$store     = Wpfnl::get_instance()->funnel_store;
		$funnel_id = $store->create( $name );

		if ( is_wp_error( $funnel_id ) || ! $funnel_id ) {
			return MCPHelper::error( 'create_failed', 'WordPress could not create the funnel post.' );
		}

		// Resolve the type: explicit input wins, otherwise follow what this site
		// can actually run. create() already guesses from global settings, so only
		// overwrite when we have something better.
		$type = isset( $input['type'] ) ? sanitize_text_field( $input['type'] ) : '';
		if ( '' === $type ) {
			$type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
		}
		if ( '' === $type || ! in_array( $type, self::TYPES, true ) ) {
			$type = Wpfnl_functions::is_wc_active() ? 'wc' : 'lead';
		}
		update_post_meta( $funnel_id, '_wpfnl_funnel_type', $type );

		$status = isset( $input['status'] ) && 'publish' === $input['status'] ? 'publish' : 'draft';
		wp_update_post(
			[
				'ID'          => $funnel_id,
				'post_status' => $status,
			]
		);

		// Initialise the steps order so later create-step calls append cleanly.
		$store->set_id( $funnel_id );
		$store->set_steps_order();

		ContextTools::invalidateCache();

		return [
			'success'    => true,
			'funnel_id'  => (int) $funnel_id,
			'name'       => $name,
			'type'       => $type,
			'status'     => $status,
			'canvas_url' => MCPHelper::canvasUrl( $funnel_id ),
			'next_step'  => 'Add steps with wpfunnels/create-step, starting with a landing step.',
		];
	}

	/**
	 * Duplicate a funnel and all of its steps/settings.
	 *
	 * Reuses the same clone routine the dashboard's "Duplicate" action calls
	 * (Wpfnl_Funnel_Store_Data::clone_funnel(), via admin/modules/funnel/
	 * class-wpfnl-funnel.php:clone_funnel()), driven the same way createFunnel()
	 * above drives the funnel store directly.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function duplicateFunnel( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$store = Wpfnl::get_instance()->funnel_store;
		$store->read( (int) $funnel->ID );
		$new_funnel_id = $store->clone_funnel();

		if ( ! $new_funnel_id || is_wp_error( $new_funnel_id ) ) {
			return MCPHelper::error(
				'duplicate_failed',
				is_wp_error( $new_funnel_id ) ? $new_funnel_id->get_error_message() : 'WordPress could not duplicate this funnel.'
			);
		}

		ContextTools::invalidateCache();

		return [
			'success'    => true,
			'funnel_id'  => (int) $new_funnel_id,
			'name'       => get_the_title( $new_funnel_id ),
			'type'       => get_post_meta( $new_funnel_id, '_wpfnl_funnel_type', true ),
			'status'     => get_post_status( $new_funnel_id ),
			'canvas_url' => MCPHelper::canvasUrl( $new_funnel_id ),
			'next_step'  => 'The duplicate is a separate funnel — review its steps with wpfunnels/get-funnel before publishing it.',
		];
	}

	/**
	 * Update a funnel.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function updateFunnel( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$funnel_id = (int) $funnel->ID;
		$changed   = [];
		$post_args = [ 'ID' => $funnel_id ];

		if ( isset( $input['name'] ) && '' !== trim( (string) $input['name'] ) ) {
			$post_args['post_title'] = sanitize_text_field( $input['name'] );
			$changed[]               = 'name';
		}

		if ( isset( $input['status'] ) && in_array( $input['status'], [ 'publish', 'draft' ], true ) ) {
			$post_args['post_status'] = $input['status'];
			$changed[]                = 'status';
		}

		if ( count( $post_args ) > 1 ) {
			$updated = wp_update_post( $post_args, true );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
			if ( isset( $post_args['post_status'] ) && 'publish' === $post_args['post_status'] ) {
				$user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
				do_action( 'wpfunnels_ai_funnel_published', $funnel_id, $user_id );
			}
		}

		if ( isset( $input['type'] ) && in_array( $input['type'], self::TYPES, true ) ) {
			$current = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
			if ( 'store_checkout' === $current ) {
				return MCPHelper::error(
					'store_checkout_type_locked',
					'This funnel is a store checkout. Converting it to another type is not supported through this tool.'
				);
			}
			update_post_meta( $funnel_id, '_wpfnl_funnel_type', sanitize_text_field( $input['type'] ) );
			$changed[] = 'type';
		}

		// Tracking/offer settings all funnel through the same real routine the
		// funnel settings screen calls (Wpfnl_Funnel::wpfnl_update_funnel_settings()),
		// so the exact postmeta shapes it expects/writes stay in one place.
		$settings_payload = [ 'funnel_id' => $funnel_id ];

		if ( isset( $input['utm'] ) && is_array( $input['utm'] ) ) {
			// wpfnl_update_funnel_settings() overwrites _wpfunnels_utm_params
			// wholesale, so any field the caller left out must be backfilled
			// from the currently saved value rather than dropped.
			$existing_utm = get_post_meta( $funnel_id, '_wpfunnels_utm_params', true );
			$existing_utm = is_array( $existing_utm ) ? $existing_utm : [];

			$settings_payload['utm_settings'] = [
				'utm_enable'   => array_key_exists( 'enabled', $input['utm'] )
					? (bool) $input['utm']['enabled']
					: ( isset( $existing_utm['utm_enable'] ) && 'on' === $existing_utm['utm_enable'] ),
				'utm_source'   => isset( $input['utm']['source'] ) ? sanitize_text_field( $input['utm']['source'] ) : ( isset( $existing_utm['utm_source'] ) ? $existing_utm['utm_source'] : '' ),
				'utm_medium'   => isset( $input['utm']['medium'] ) ? sanitize_text_field( $input['utm']['medium'] ) : ( isset( $existing_utm['utm_medium'] ) ? $existing_utm['utm_medium'] : '' ),
				'utm_campaign' => isset( $input['utm']['campaign'] ) ? sanitize_text_field( $input['utm']['campaign'] ) : ( isset( $existing_utm['utm_campaign'] ) ? $existing_utm['utm_campaign'] : '' ),
				'utm_content'  => isset( $input['utm']['content'] ) ? sanitize_text_field( $input['utm']['content'] ) : ( isset( $existing_utm['utm_content'] ) ? $existing_utm['utm_content'] : '' ),
			];
			$changed[] = 'utm';
		}

		if ( isset( $input['fb_pixel_disabled'] ) ) {
			$settings_payload['is_fb_pixel'] = (bool) $input['fb_pixel_disabled'];
			$changed[]                       = 'fb_pixel_disabled';
		}

		if ( isset( $input['gtm_disabled'] ) ) {
			$settings_payload['is_gtm'] = (bool) $input['gtm_disabled'];
			$changed[]                  = 'gtm_disabled';
		}

		if ( isset( $input['skip_offer'] ) && is_array( $input['skip_offer'] )
			&& ( array_key_exists( 'enabled', $input['skip_offer'] ) || array_key_exists( 'skip_if_quantity', $input['skip_offer'] ) )
		) {
			// wpfnl_update_funnel_settings() only writes _wpfunnels_skip_offer
			// when BOTH keys are present, so backfill whichever one was omitted.
			$existing_skip = get_post_meta( $funnel_id, '_wpfunnels_skip_offer', true );
			$existing_skip = is_array( $existing_skip ) ? $existing_skip : [];

			$settings_payload['skip_offer'] = array_key_exists( 'enabled', $input['skip_offer'] )
				? (bool) $input['skip_offer']['enabled']
				: ( isset( $existing_skip['skip_offer'] ) && 'yes' === $existing_skip['skip_offer'] );

			$settings_payload['skip_if_quantity'] = array_key_exists( 'skip_if_quantity', $input['skip_offer'] )
				? (bool) $input['skip_offer']['skip_if_quantity']
				: ( isset( $existing_skip['skip_if_quantity'] ) && 'yes' === $existing_skip['skip_if_quantity'] );

			$changed[] = 'skip_offer';
		}

		if ( isset( $input['skip_recurring_offer_within_days'] ) ) {
			$settings_payload['skip_recurring_offer_within_days'] = (int) $input['skip_recurring_offer_within_days'];
			// The underlying routine only writes this meta when non-empty, so a
			// value of 0 is silently ignored — that limitation is inherited here.
			if ( ! empty( $settings_payload['skip_recurring_offer_within_days'] ) ) {
				$changed[] = 'skip_recurring_offer_within_days';
			}
		}

		if ( isset( $input['skip_recurring_offer'] ) ) {
			$settings_payload['skip_recurring_offer'] = (bool) $input['skip_recurring_offer'];
			// The underlying routine only writes this meta when truthy, so it
			// has no explicit "off" write path — that limitation is inherited here.
			if ( $settings_payload['skip_recurring_offer'] ) {
				$changed[] = 'skip_recurring_offer';
			}
		}

		if ( count( $settings_payload ) > 1 ) {
			if ( ! class_exists( '\WPFunnels\Modules\Admin\Funnel\Module' ) ) {
				return MCPHelper::error( 'settings_module_unavailable', 'The funnel settings routine is not available.' );
			}

			$settings_result = FunnelAdminModule::instance()->wpfnl_update_funnel_settings( $settings_payload );
			if ( empty( $settings_result['success'] ) ) {
				return MCPHelper::error( 'settings_update_failed', 'WordPress could not save the funnel settings.' );
			}
		}

		if ( empty( $changed ) ) {
			return MCPHelper::error( 'nothing_to_update', 'Provide at least one of name, type, status, utm, fb_pixel_disabled, gtm_disabled, skip_offer, skip_recurring_offer or skip_recurring_offer_within_days.' );
		}

		ContextTools::invalidateCache();

		return [
			'success' => true,
			'funnel'  => MCPHelper::formatFunnel( get_post( $funnel_id ) ),
			'changed' => $changed,
		];
	}

	/**
	 * Trash a funnel.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function trashFunnel( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$result = wp_trash_post( $funnel->ID );
		if ( ! $result ) {
			return MCPHelper::error( 'trash_failed', 'WordPress could not trash this funnel.' );
		}

		ContextTools::invalidateCache();

		return [
			'success'   => true,
			'funnel_id' => (int) $funnel->ID,
			'status'    => 'trash',
		];
	}

	/**
	 * Restore a trashed funnel.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function restoreFunnel( $input = [] ) {
		$funnel_id = isset( $input['funnel_id'] ) ? (int) $input['funnel_id'] : 0;
		$post      = $funnel_id ? get_post( $funnel_id ) : null;

		if ( ! $post || WPFNL_FUNNELS_POST_TYPE !== $post->post_type ) {
			return MCPHelper::error( 'funnel_not_found', sprintf( 'No funnel found with ID %d.', $funnel_id ) );
		}

		$result = wp_untrash_post( $funnel_id );
		if ( ! $result ) {
			return MCPHelper::error( 'restore_failed', 'WordPress could not restore this funnel.' );
		}

		ContextTools::invalidateCache();

		return [
			'success'   => true,
			'funnel'    => MCPHelper::formatFunnel( get_post( $funnel_id ) ),
		];
	}

	/**
	 * Permanently delete a funnel.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function deleteFunnel( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$funnel_id = (int) $funnel->ID;
		$result    = wp_delete_post( $funnel_id, true );

		if ( ! $result ) {
			return MCPHelper::error( 'delete_failed', 'WordPress could not delete this funnel.' );
		}

		ContextTools::invalidateCache();

		return [
			'success'   => true,
			'funnel_id' => $funnel_id,
		];
	}

	/**
	 * Trash, restore or permanently delete several funnels.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function bulkFunnelAction( $input = [] ) {
		$funnel_ids = isset( $input['funnel_ids'] ) && is_array( $input['funnel_ids'] ) ? array_map( 'intval', $input['funnel_ids'] ) : [];
		$action     = isset( $input['action'] ) ? (string) $input['action'] : '';
		$dry_run    = ! empty( $input['dry_run'] );

		if ( empty( $funnel_ids ) || ! in_array( $action, [ 'trash', 'restore', 'delete' ], true ) ) {
			return MCPHelper::error( 'invalid_input', 'Provide funnel_ids and one of trash, restore, delete.' );
		}

		$results = [];
		foreach ( $funnel_ids as $funnel_id ) {
			$post = get_post( $funnel_id );
			if ( ! $post || WPFNL_FUNNELS_POST_TYPE !== $post->post_type ) {
				$results[] = [ 'funnel_id' => $funnel_id, 'success' => false, 'message' => 'Not a funnel.' ];
				continue;
			}

			if ( $dry_run ) {
				$results[] = [ 'funnel_id' => $funnel_id, 'success' => true, 'would' => $action, 'name' => $post->post_title ];
				continue;
			}

			switch ( $action ) {
				case 'trash':
					$ok = (bool) wp_trash_post( $funnel_id );
					break;
				case 'restore':
					$ok = (bool) wp_untrash_post( $funnel_id );
					break;
				default:
					$ok = (bool) wp_delete_post( $funnel_id, true );
					break;
			}

			$results[] = [ 'funnel_id' => $funnel_id, 'success' => $ok ];
		}

		if ( ! $dry_run ) {
			ContextTools::invalidateCache();
		}

		return [
			'success'  => true,
			'dry_run'  => $dry_run,
			'action'   => $action,
			'results'  => $results,
		];
	}

	/**
	 * Export one or more funnels, reusing the same exporter the Import/Export
	 * admin screen calls.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function exportFunnel( $input = [] ) {
		$funnel_ids = isset( $input['funnel_ids'] ) && is_array( $input['funnel_ids'] ) ? array_map( 'intval', $input['funnel_ids'] ) : [];
		if ( empty( $funnel_ids ) ) {
			return MCPHelper::error( 'missing_funnel_ids', 'Provide at least one funnel_id.' );
		}

		if ( ! class_exists( '\WPFunnels\Export\Wpfnl_Export' ) ) {
			return MCPHelper::error( 'exporter_unavailable', 'The funnel exporter is not available.' );
		}

		return Wpfnl_Export::getInstance()->bulk_export_funnel(
			[
				'ids'    => $funnel_ids,
				'status' => 'all',
			]
		);
	}

	/**
	 * Import funnels from previously-exported JSON, reusing the same importer
	 * the Import/Export admin screen calls (WPFunnels\Import\Wpfnl_Import).
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function importFunnel( $input = [] ) {
		$funnels = isset( $input['funnels'] ) && is_array( $input['funnels'] ) ? $input['funnels'] : [];
		if ( empty( $funnels ) ) {
			return MCPHelper::error( 'missing_funnels', 'Provide the funnels array from an export-funnel response.' );
		}

		if ( ! class_exists( '\WPFunnels\Import\Wpfnl_Import' ) || empty( Wpfnl::$instance->import_funnel ) ) {
			return MCPHelper::error( 'importer_unavailable', 'The funnel importer is not available.' );
		}

		$before_ids = get_posts(
			[
				'post_type'      => WPFNL_FUNNELS_POST_TYPE,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_status'    => 'any',
			]
		);

		$upload_dir = wp_upload_dir();
		$path       = trailingslashit( $upload_dir['basedir'] ) . 'wpfunnels/import/';
		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( $path );
		}

		$file = $path . wp_unique_filename( $path, 'mcp-import-' . uniqid() . '.json' );
		if ( false === file_put_contents( $file, wp_json_encode( $funnels ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return MCPHelper::error( 'write_failed', 'Could not write the import file.' );
		}

		// Runs the same synchronous logic the admin screen schedules via
		// Action Scheduler; import_funnels() reads $file itself and deletes it.
		Wpfnl::$instance->import_funnel->import_funnels( $file );

		$after_ids = get_posts(
			[
				'post_type'      => WPFNL_FUNNELS_POST_TYPE,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_status'    => 'any',
			]
		);

		$created_ids = array_values( array_diff( $after_ids, $before_ids ) );

		return [
			'imported'           => count( $created_ids ),
			'created_funnel_ids' => $created_ids,
			'message'            => get_option( 'wpfnl_import_notice', '' ),
		];
	}

	/**
	 * Front-end and canvas links for a funnel and each of its steps.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getFunnelPreviewLinks( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$funnel_id = (int) $funnel->ID;
		$steps     = Wpfnl_functions::get_steps( $funnel_id );
		$steps     = is_array( $steps ) ? $steps : [];

		return [
			'funnel_id'  => $funnel_id,
			'canvas_url' => MCPHelper::canvasUrl( $funnel_id ),
			'steps'      => array_map(
				static function ( $step ) {
					return MCPHelper::formatStepSummary( $step );
				},
				$steps
			),
		];
	}
}
