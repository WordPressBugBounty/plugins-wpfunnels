<?php
/**
 * Store Checkout Controller
 *
 * @package WPFunnels\Rest\Controllers
 * @since 3.5.0
 */

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;
use WPFunnels\WooCommerce\Wpfnl_Store_Checkout_Conditions;

/**
 * Class StoreCheckoutController
 * Handles Store Checkout specific operations
 *
 * @package WPFunnels\Rest\Controllers
 * @since 3.5.0
 */
class StoreCheckoutController extends Wpfnl_REST_Controller {

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
	protected $rest_base = 'store-checkout';


	/**
	 * Check user permission
	 *
	 * @param $request
	 *
	 * @return bool|WP_Error
	 */
	public function update_items_permissions_check( $request ) {
		if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'funnels' ) ) {
			return new WP_Error( 'wpfunnels_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}


	/**
	 * Check user read permission
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'funnels' ) ) {
			return new WP_Error( 'wpfunnels_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}


	/**
	 * Register routes
	 */
	public function register_routes() {
		// Check if Store Checkout exists
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/exists',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'check_exists' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		// Get Store Checkout details (first / any)
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_store_checkout' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		// List all Store Checkout funnels
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/list',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_store_checkouts' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		// One page of the React Store Checkout screen (`#/store-checkout`).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/listing',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_listing' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'page'          => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page'      => array(
							'type'              => 'integer',
							'default'           => 25,
							'sanitize_callback' => 'absint',
						),
						'search'        => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						/*
						 * Trash is a value of this filter rather than a tab of
						 * its own, the way the React funnel list reads it — the
						 * legacy screen had a separate `sc_status=trash` view.
						 */
						'funnel_status' => array(
							'type'    => 'string',
							'default' => 'all',
							'enum'    => array( 'all', 'publish', 'draft', 'trash' ),
						),
						'orderby'       => array(
							'type'    => 'string',
							'default' => 'date',
							'enum'    => array( 'date', 'title', 'modified' ),
						),
						'order'         => array(
							'type'    => 'string',
							'default' => 'desc',
							'enum'    => array( 'asc', 'desc' ),
						),
					),
				),
			)
		);

		// Validate Store Checkout creation (always allowed in multi-checkout mode)
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/validate-creation',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'validate_creation' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		// Save conditions for a store checkout step
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/conditions/save',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_conditions' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => array(
						'step_id'   => array(
							'description'       => __( 'Checkout step ID.', 'wpfnl' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => function( $value ) {
								return is_numeric( $value );
							},
						),
						'condition' => array(
							'description' => __( 'Condition configuration object.', 'wpfnl' ),
							'type'        => 'object',
							'required'    => true,
						),
					),
				),
			)
		);

		// Get conditions for a store checkout step
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/conditions/get',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_conditions' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'step_id' => array(
							'description'       => __( 'Checkout step ID.', 'wpfnl' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => function( $value ) {
								return is_numeric( $value );
							},
						),
					),
				),
			)
		);

		// Search products for condition UI
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/search/products',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_products' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'search' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// Search product terms (categories or tags) for condition UI
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/search/terms',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_terms' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'taxonomy' => array(
							'type'              => 'string',
							'required'          => true,
							'enum'              => array( 'product_cat', 'product_tag' ),
							'sanitize_callback' => 'sanitize_key',
						),
						'search'   => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}


	/**
	 * Check if Store Checkout exists
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function check_exists( $request ) {
		$store_checkout = $this->get_store_checkout_funnel();
		
		$response = array(
			'exists' => !empty( $store_checkout ),
			'funnel_id' => $store_checkout ? $store_checkout->ID : null,
		);

		return rest_ensure_response( $response );
	}


	/**
	 * Get Store Checkout funnel details (first one found — legacy endpoint)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_store_checkout( $request ) {
		$store_checkout = $this->get_store_checkout_funnel();
		
		if ( !$store_checkout ) {
			return new WP_Error(
				'no_store_checkout',
				__( 'No Store Checkout found.', 'wpfnl' ),
				array( 'status' => 404 )
			);
		}

		$response = array(
			'success'    => true,
			'funnel_id'  => $store_checkout->ID,
			'funnel_name' => get_the_title( $store_checkout->ID ),
			'edit_url'   => \WPFunnels\Wpfnl_functions::get_funnel_edit_url( $store_checkout->ID ),
		);

		return rest_ensure_response( $response );
	}


	/**
	 * List all Store Checkout funnels with their condition info.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 * @since 3.6.0
	 */
	public function list_store_checkouts( $request ) {
		$this->ensure_conditions_class();

		$funnels = Wpfnl_Store_Checkout_Conditions::get_all_store_checkout_funnels();
		$items   = array();

		foreach ( $funnels as $funnel ) {
			$step_id   = Wpfnl_Store_Checkout_Conditions::get_checkout_step_id_for_funnel( $funnel->ID );
			$condition = $step_id
				? Wpfnl_Store_Checkout_Conditions::get_condition( $step_id )
				: array( 'condition_type' => 'all' );

			$items[] = array(
				'funnel_id'      => $funnel->ID,
				'funnel_name'    => get_the_title( $funnel->ID ),
				'status'         => $funnel->post_status,
				'created_at'     => $funnel->post_date,
				'step_id'        => $step_id,
				'condition'      => $condition,
				'edit_url'       => \WPFunnels\Wpfnl_functions::get_funnel_edit_url( $funnel->ID ),
			);
		}

		return rest_ensure_response( array(
			'success' => true,
			'items'   => $items,
		) );
	}


	/**
	 * Validate if Store Checkout can be created.
	 *
	 * Since 3.6.0 multiple store checkouts are allowed — creation is always
	 * permitted.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function validate_creation( $request ) {
		return rest_ensure_response( array(
			'can_create' => true,
			'message'    => __( 'Store Checkout can be created.', 'wpfnl' ),
		) );
	}


	/**
	 * Save conditions for a store-checkout step.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 * @since 3.6.0
	 */
	public function save_conditions( $request ) {
		$this->ensure_conditions_class();

		$step_id   = (int) $request->get_param( 'step_id' );
		$condition = $request->get_param( 'condition' );

		if ( ! $step_id || WPFNL_STEPS_POST_TYPE !== get_post_type( $step_id ) ) {
			return new WP_Error(
				'invalid_step',
				__( 'Invalid step ID provided.', 'wpfnl' ),
				array( 'status' => 400 )
			);
		}

		if ( ! is_array( $condition ) ) {
			return new WP_Error(
				'invalid_condition',
				__( 'Condition must be an object.', 'wpfnl' ),
				array( 'status' => 400 )
			);
		}

		$saved = Wpfnl_Store_Checkout_Conditions::save_condition( $step_id, $condition );

		if ( ! $saved ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to save condition.', 'wpfnl' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( array(
			'success'   => true,
			'condition' => Wpfnl_Store_Checkout_Conditions::get_condition( $step_id ),
			'message'   => __( 'Condition saved successfully.', 'wpfnl' ),
		) );
	}


	/**
	 * Get conditions for a store-checkout step.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 * @since 3.6.0
	 */
	public function get_conditions( $request ) {
		$this->ensure_conditions_class();

		$step_id = (int) $request->get_param( 'step_id' );

		if ( ! $step_id || WPFNL_STEPS_POST_TYPE !== get_post_type( $step_id ) ) {
			// If the step_id is actually a funnel ID, try to resolve the checkout step from it.
			$resolved_step_id = null;
			if ( $step_id && WPFNL_FUNNELS_POST_TYPE === get_post_type( $step_id ) ) {
				$resolved_step_id = Wpfnl_Store_Checkout_Conditions::get_checkout_step_id_for_funnel( $step_id );
			}

			if ( ! $resolved_step_id ) {
				return new WP_Error(
					'invalid_step',
					__( 'Invalid step ID provided.', 'wpfnl' ),
					array( 'status' => 400 )
				);
			}

			$step_id = $resolved_step_id;
		}

		$condition = Wpfnl_Store_Checkout_Conditions::get_condition( $step_id );

		return rest_ensure_response( array(
			'success'   => true,
			'step_id'   => $step_id,
			'condition' => $condition,
		) );
	}


	/**
	 * Get Store Checkout funnel if exists (returns first one — legacy helper)
	 *
	 * @return WP_Post|false
	 */
	private function get_store_checkout_funnel() {
		$args = array(
			'post_type'      => WPFNL_FUNNELS_POST_TYPE,
			'posts_per_page' => 1,
			'post_status'    => array( 'publish', 'draft' ),
			'meta_query'     => array(
				array(
					'key'   => '_wpfnl_funnel_type',
					'value' => 'store_checkout',
				),
			),
		);
		
		$funnels = get_posts( $args );
		return !empty( $funnels ) ? $funnels[0] : false;
	}


	/**
	 * Search WooCommerce products for the conditions UI.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 * @since 3.6.0
	 */
	public function search_products( $request ) {
		$search  = $request->get_param( 'search' );
		$results = array();

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			'fields'         => 'ids',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$ids = get_posts( $args );

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			$results[] = array(
				'id'   => $id,
				'name' => $product->get_name(),
			);
		}

		return rest_ensure_response( array(
			'success' => true,
			'items'   => $results,
		) );
	}


	/**
	 * Search WooCommerce product taxonomy terms for the conditions UI.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 * @since 3.6.0
	 */
	public function search_terms( $request ) {
		$taxonomy = $request->get_param( 'taxonomy' );
		$search   = $request->get_param( 'search' );

		if ( ! in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
			return new WP_Error(
				'invalid_taxonomy',
				__( 'Invalid taxonomy. Must be product_cat or product_tag.', 'wpfnl' ),
				array( 'status' => 400 )
			);
		}

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => 50,
		);

		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		$terms   = get_terms( $args );
		$results = array();

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$results[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
				);
			}
		}

		return rest_ensure_response( array(
			'success' => true,
			'items'   => $results,
		) );
	}


	/**
	 * Free installs may keep this many store checkouts before the screen stops
	 * offering to build another. Same number the legacy view hard-coded.
	 */
	const FREE_STORE_CHECKOUT_LIMIT = 3;


	/**
	 * One page of the React Store Checkout screen.
	 *
	 * Everything the screen needs travels with the listing — the analytics
	 * strip, the status counts and the free-plan allowance — because all three
	 * change with the rows and a second round trip would let them disagree with
	 * what is on screen.
	 *
	 * Priority is worked out over the whole set rather than the page: it is a
	 * funnel's position in the evaluation order of every store checkout on the
	 * site, so paging must not renumber it.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 3.13.6
	 */
	public function get_listing( $request ) {
		$this->ensure_conditions_class();

		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = (int) $request->get_param( 'per_page' );
		$per_page = $per_page > 0 ? min( $per_page, 100 ) : 25;
		$search   = (string) $request->get_param( 'search' );
		$status   = (string) $request->get_param( 'funnel_status' );
		$orderby  = (string) $request->get_param( 'orderby' );
		$order    = 'asc' === $request->get_param( 'order' ) ? 'ASC' : 'DESC';

		$post_status = in_array( $status, array( 'publish', 'draft', 'trash' ), true )
			? array( $status )
			: array( 'publish', 'draft', 'trash' );

		$matching = $this->query_store_checkouts( $post_status, $search, $orderby, $order );
		$total    = count( $matching );
		$posts    = array_slice( $matching, ( $page - 1 ) * $per_page, $per_page );

		/*
		 * The evaluation order is a property of the live set, not of the filter
		 * being looked at: a trashed checkout is evaluated by nobody, and a
		 * search must not move the numbers of the rows it happens to match.
		 */
		$priority = $this->get_priority_map();

		$items = array();

		foreach ( $posts as $funnel ) {
			$items[] = $this->prepare_row( $funnel, $priority );
		}

		return rest_ensure_response(
			array(
				'success'     => true,
				'items'       => $items,
				'total'       => $total,
				'total_pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 0,
				'page'        => $page,
				'per_page'    => $per_page,
				'counts'      => $this->get_listing_counts(),
				'analytics'   => $this->get_listing_analytics(),
				'meta'        => $this->get_listing_meta(),
			)
		);
	}


	/**
	 * Every store checkout matching a status and a search, newest first.
	 *
	 * Resolved in full rather than paged by WP_Query because the screen also
	 * needs the whole set to number the evaluation order, and asking twice for
	 * the same posts is the more expensive of the two.
	 *
	 * @param array  $post_status Post statuses to match.
	 * @param string $search      Search term, or an empty string.
	 * @param string $orderby     Order column.
	 * @param string $order       `ASC` or `DESC`.
	 *
	 * @return \WP_Post[]
	 * @since 3.13.6
	 */
	private function query_store_checkouts( $post_status, $search = '', $orderby = 'date', $order = 'DESC' ) {
		$args = array(
			'post_type'      => WPFNL_FUNNELS_POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => $post_status,
			'orderby'        => $orderby,
			'order'          => $order,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_wpfnl_funnel_type',
					'value' => 'store_checkout',
				),
			),
		);

		if ( $search ) {
			$args['s'] = $search;
		}

		return get_posts( $args );
	}


	/**
	 * Where each live store checkout sits in the evaluation order.
	 *
	 * The same ordering the matcher itself applies: conditional checkouts are
	 * tried newest first, and the catch-all is the fallback when none of them
	 * match. Only the newest catch-all can ever be reached — the ones behind it
	 * are overridden, and the screen says so rather than showing a number that
	 * means nothing.
	 *
	 * @return array `[ 'position' => int[], 'overridden' => int[], 'catchAll' => int[] ]`.
	 * @since 3.13.6
	 */
	private function get_priority_map() {
		$funnels     = Wpfnl_Store_Checkout_Conditions::get_all_store_checkout_funnels();
		$conditional = array();
		$catch_all   = array();

		foreach ( $funnels as $funnel ) {
			$step_id   = Wpfnl_Store_Checkout_Conditions::get_checkout_step_id_for_funnel( $funnel->ID );
			$condition = $step_id
				? Wpfnl_Store_Checkout_Conditions::get_condition( $step_id )
				: array( 'condition_type' => 'all' );

			if ( Wpfnl_Store_Checkout_Conditions::is_catch_all( $condition ) ) {
				$catch_all[] = (int) $funnel->ID;
			} else {
				$conditional[] = (int) $funnel->ID;
			}
		}

		$position   = array();
		$overridden = array();
		$next       = 1;

		foreach ( $conditional as $funnel_id ) {
			$position[ $funnel_id ] = $next++;
		}

		foreach ( $catch_all as $index => $funnel_id ) {
			$position[ $funnel_id ] = $next++;

			if ( $index > 0 ) {
				$overridden[] = $funnel_id;
			}
		}

		return array(
			'position'   => $position,
			'overridden' => $overridden,
			'catchAll'   => $catch_all,
		);
	}


	/**
	 * Shape one row of the listing.
	 *
	 * @param \WP_Post $funnel   Store checkout funnel.
	 * @param array    $priority Output of get_priority_map().
	 *
	 * @return array
	 * @since 3.13.6
	 */
	private function prepare_row( $funnel, $priority ) {
		$funnel_id = (int) $funnel->ID;
		$step_id   = Wpfnl_Store_Checkout_Conditions::get_checkout_step_id_for_funnel( $funnel_id );
		$condition = $step_id
			? Wpfnl_Store_Checkout_Conditions::get_condition( $step_id )
			: array( 'condition_type' => 'all' );

		$is_trash = 'trash' === $funnel->post_status;
		$badges   = $this->get_condition_badges( $condition );

		/*
		 * A catch-all has no badge of its own, so the fallback one is added
		 * here and the row is marked as having no real condition — which is
		 * also what decides the wording of its priority.
		 */
		$is_catch_all = empty( $badges );

		if ( $is_catch_all ) {
			$badges[] = array(
				'label'   => __( 'No Condition', 'wpfnl' ),
				'tooltip' => __( 'This store checkout will apply as the default WooCommerce checkout without any condition.', 'wpfnl' ),
				'isDate'  => false,
			);
		}

		$summary = '';

		foreach ( $badges as $index => $badge ) {
			if ( $index > 0 ) {
				$summary .= empty( $badge['isDate'] ) ? ' OR ' : ' AND ';
			}

			$summary .= $badge['label'];
		}

		$view_url = '';

		if ( ! $is_trash && $step_id ) {
			$permalink = get_the_permalink( $step_id );
			$view_url  = $permalink ? esc_url_raw( $permalink ) : '';
		}

		return array(
			'id'               => $funnel_id,
			'name'             => $funnel->post_title ? $funnel->post_title : __( '(no title)', 'wpfnl' ),
			'status'           => $funnel->post_status,
			'statusLabel'      => $this->get_status_label( $funnel->post_status ),
			'builder'          => Wpfnl_functions::get_page_builder_by_step_id( $funnel_id ),
			'stepId'           => $step_id ? (int) $step_id : 0,
			'createdAt'        => $funnel->post_date_gmt,
			// Rendered server-side so the column reads in the site's own
			// locale and date format rather than the browser's.
			'createdLabel'     => get_the_date( 'Y-m-d g:i A', $funnel_id ),
			'conditionSummary' => $summary,
			'conditionTooltip' => implode( ' ', wp_list_pluck( $badges, 'tooltip' ) ),
			'hasCondition'     => ! $is_catch_all,
			'editUrl'          => $is_trash ? '' : $this->get_funnel_edit_url( $funnel_id ),
			'viewUrl'          => $view_url,
		) + $this->get_row_priority( $funnel_id, $priority, $is_catch_all, $is_trash );
	}


	/**
	 * The priority badge of one row: its wording, its tone and why.
	 *
	 * @param int   $funnel_id    Funnel id.
	 * @param array $priority     Output of get_priority_map().
	 * @param bool  $is_catch_all Whether the checkout carries no condition.
	 * @param bool  $is_trash     Whether the checkout is in the trash.
	 *
	 * @return array
	 * @since 3.13.6
	 */
	private function get_row_priority( $funnel_id, $priority, $is_catch_all, $is_trash ) {
		// Nothing in the trash is ever evaluated, so it has no place in the order.
		if ( $is_trash || ! isset( $priority['position'][ $funnel_id ] ) ) {
			return array(
				'priorityLabel'   => '—',
				'priorityTone'    => 'none',
				'priorityTooltip' => __( 'Trashed store checkouts are never evaluated.', 'wpfnl' ),
			);
		}

		if ( in_array( $funnel_id, $priority['overridden'], true ) ) {
			return array(
				'priorityLabel'   => __( 'Overridden', 'wpfnl' ),
				'priorityTone'    => 'overridden',
				'priorityTooltip' => __( 'This fallback checkout is overridden by a newer fallback. Only the most recently created fallback is active when no conditional checkout matches.', 'wpfnl' ),
			);
		}

		if ( $is_catch_all ) {
			return array(
				'priorityLabel'   => __( 'Default', 'wpfnl' ),
				'priorityTone'    => 'fallback',
				'priorityTooltip' => __( 'This checkout has no condition and acts as the default fallback. It will only be used when no other conditional store checkout matches the customer\'s cart.', 'wpfnl' ),
			);
		}

		$position = (int) $priority['position'][ $funnel_id ];

		if ( 1 === $position ) {
			return array(
				'priorityLabel'   => __( 'Highest', 'wpfnl' ),
				'priorityTone'    => 'active',
				'priorityTooltip' => __( 'This is the most recently created conditional checkout. It will be evaluated first and takes priority over all other store checkouts.', 'wpfnl' ),
			);
		}

		return array(
			/* translators: %d: priority position number. */
			'priorityLabel'   => sprintf( __( '#%d', 'wpfnl' ), $position ),
			'priorityTone'    => 'active',
			/* translators: %d: priority position number. */
			'priorityTooltip' => sprintf( __( 'This checkout has evaluation priority #%d. Store checkouts with conditions are evaluated from newest to oldest. The first match wins.', 'wpfnl' ), $position ),
		);
	}


	/**
	 * One badge per rule a condition carries, plus the date range when it has
	 * one. An empty list means the checkout is a catch-all.
	 *
	 * @param array $condition Stored condition.
	 *
	 * @return array List of `[ 'label' => string, 'tooltip' => string, 'isDate' => bool ]`.
	 * @since 3.13.6
	 */
	private function get_condition_badges( $condition ) {
		$labels = array(
			'all'        => __( 'No Condition', 'wpfnl' ),
			'products'   => __( 'Specific Products', 'wpfnl' ),
			'categories' => __( 'Product Categories', 'wpfnl' ),
			'tags'       => __( 'Product Tags', 'wpfnl' ),
			'date_range' => __( 'Date Range', 'wpfnl' ),
			'rules'      => __( 'Custom Rules', 'wpfnl' ),
		);

		$type   = isset( $condition['condition_type'] ) ? $condition['condition_type'] : 'all';
		$badges = array();
		$rules  = array();

		if ( 'rules' === $type && ! empty( $condition['rules'] ) && is_array( $condition['rules'] ) ) {
			$rules = $condition['rules'];

			foreach ( $rules as $rule ) {
				$rule_type = isset( $rule['type'] ) ? $rule['type'] : 'all';

				$badges[] = array(
					'label'   => isset( $labels[ $rule_type ] ) ? $labels[ $rule_type ] : $labels['all'],
					'tooltip' => $this->get_rule_tooltip( $rule, count( $rules ) ),
					'isDate'  => false,
				);
			}
		}

		if ( empty( $condition['use_date_range'] ) ) {
			return $badges;
		}

		$badges[] = array(
			'label'   => $labels['date_range'],
			'tooltip' => $this->get_date_tooltip( $condition, ! empty( $rules ) ),
			'isDate'  => true,
		);

		return $badges;
	}


	/**
	 * What one rule means, with its product, category or tag ids resolved to
	 * the names the reader gave them.
	 *
	 * @param array $rule        One rule of a condition.
	 * @param int   $total_rules How many rules the condition carries.
	 *
	 * @return string
	 * @since 3.13.6
	 */
	private function get_rule_tooltip( $rule, $total_rules = 1 ) {
		$type    = isset( $rule['type'] ) ? $rule['type'] : 'all';
		$values  = isset( $rule['values'] ) ? (array) $rule['values'] : array();
		$or_note = $total_rules > 1 ? ' ' . __( '(OR with other rules)', 'wpfnl' ) : '';

		if ( ! $values ) {
			return __( 'This checkout will appear when the condition is met.', 'wpfnl' ) . $or_note;
		}

		$names = array();

		foreach ( $values as $value_id ) {
			$value_id = (int) $value_id;

			if ( 'products' === $type ) {
				$names[] = get_the_title( $value_id );
				continue;
			}

			$taxonomy = 'categories' === $type ? 'product_cat' : ( 'tags' === $type ? 'product_tag' : '' );

			if ( ! $taxonomy ) {
				continue;
			}

			$term    = get_term( $value_id, $taxonomy );
			$names[] = ( $term && ! is_wp_error( $term ) ) ? $term->name : '#' . $value_id;
		}

		$names = implode( ', ', $names );

		switch ( $type ) {
			case 'products':
				/* translators: %s: comma separated product names. */
				return sprintf( __( 'This checkout will appear when the cart contains: %s.', 'wpfnl' ), $names ) . $or_note;

			case 'categories':
				/* translators: %s: comma separated category names. */
				return sprintf( __( 'This checkout will appear when the cart has products from: %s.', 'wpfnl' ), $names ) . $or_note;

			case 'tags':
				/* translators: %s: comma separated tag names. */
				return sprintf( __( 'This checkout will appear when the cart has products tagged: %s.', 'wpfnl' ), $names ) . $or_note;
		}

		return __( 'This checkout will appear when the condition is met.', 'wpfnl' ) . $or_note;
	}


	/**
	 * What the date range of a condition means.
	 *
	 * A date range narrows whatever the rules already matched rather than
	 * offering another way to match, so its wording says "additionally" when
	 * there are rules alongside it.
	 *
	 * @param array $condition Stored condition.
	 * @param bool  $has_rules Whether the condition also carries rules.
	 *
	 * @return string
	 * @since 3.13.6
	 */
	private function get_date_tooltip( $condition, $has_rules ) {
		$from = isset( $condition['date_from'] ) ? $condition['date_from'] : '';
		$to   = isset( $condition['date_to'] ) ? $condition['date_to'] : '';

		if ( $from && $to ) {
			return $has_rules
				/* translators: 1: start date, 2: end date. */
				? sprintf( __( 'Additionally, this checkout will only be active between %1$s and %2$s.', 'wpfnl' ), $from, $to )
				/* translators: 1: start date, 2: end date. */
				: sprintf( __( 'This checkout will only be active between %1$s and %2$s.', 'wpfnl' ), $from, $to );
		}

		if ( $from ) {
			return $has_rules
				/* translators: %s: start date. */
				? sprintf( __( 'Additionally, this checkout will only be active from %s onwards.', 'wpfnl' ), $from )
				/* translators: %s: start date. */
				: sprintf( __( 'This checkout will only be active from %s onwards.', 'wpfnl' ), $from );
		}

		if ( $to ) {
			return $has_rules
				/* translators: %s: end date. */
				? sprintf( __( 'Additionally, this checkout will only be active until %s.', 'wpfnl' ), $to )
				/* translators: %s: end date. */
				: sprintf( __( 'This checkout will only be active until %s.', 'wpfnl' ), $to );
		}

		return __( 'This checkout has a date range condition.', 'wpfnl' );
	}


	/**
	 * Orders, revenue, average order value and bump revenue across every live
	 * store checkout.
	 *
	 * @return array
	 * @since 3.13.6
	 */
	private function get_listing_analytics() {
		global $wpdb;

		$analytics = array(
			'totalOrders'      => 0,
			'totalRevenue'     => 0,
			'aov'              => 0,
			'orderBumpRevenue' => 0,
		);

		$ids = wp_list_pluck( Wpfnl_Store_Checkout_Conditions::get_all_store_checkout_funnels(), 'ID' );
		$ids = array_filter( array_map( 'absint', (array) $ids ) );

		if ( ! $ids ) {
			return $analytics;
		}

		$stats_table = $wpdb->prefix . 'wpfnl_stats';

		// A site that has never tracked an order has no stats table, and that
		// is not an error worth failing the whole screen over.
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $stats_table ) ) ) { // phpcs:ignore
			return $analytics;
		}

		// Safe to interpolate: every id has been through absint().
		$placeholders = implode( ',', $ids );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT id) AS total_orders,
					COALESCE(SUM(total_sales), 0) AS total_revenue,
					COALESCE(SUM(orderbump_sales), 0) AS orderbump_revenue
				FROM {$stats_table}
				WHERE funnel_id IN ({$placeholders}) AND status IN (%s, %s)",
				'completed',
				'processing'
			)
		); // phpcs:ignore

		if ( ! $row ) {
			return $analytics;
		}

		$analytics['totalOrders']      = (int) $row->total_orders;
		$analytics['totalRevenue']     = round( (float) $row->total_revenue, 2 );
		$analytics['orderBumpRevenue'] = round( (float) $row->orderbump_revenue, 2 );
		$analytics['aov']              = $analytics['totalOrders'] > 0
			? round( $analytics['totalRevenue'] / $analytics['totalOrders'], 2 )
			: 0;

		return $analytics;
	}


	/**
	 * How many store checkouts each value of the status filter would find.
	 *
	 * @return array
	 * @since 3.13.6
	 */
	private function get_listing_counts() {
		$publish = count( $this->query_store_checkouts( array( 'publish' ) ) );
		$draft   = count( $this->query_store_checkouts( array( 'draft' ) ) );
		$trash   = count( $this->query_store_checkouts( array( 'trash' ) ) );

		return array(
			'all'     => $publish + $draft + $trash,
			'publish' => $publish,
			'draft'   => $draft,
			'trash'   => $trash,
		);
	}


	/**
	 * State the screen's chrome depends on: the plan, the store checkout
	 * allowance and where the creation journey starts.
	 *
	 * @return array
	 * @since 3.13.6
	 */
	private function get_listing_meta() {
		$is_pro_active = (bool) apply_filters( 'wpfunnels/is_pro_license_activated', false );
		$count         = (int) Wpfnl_functions::count_store_checkout_funnels();

		return array(
			'isProActive'         => $is_pro_active,
			'isWooActive'         => Wpfnl_functions::is_wc_active(),
			'storeCheckoutCount'  => $count,
			'maxStoreCheckouts'   => $is_pro_active ? -1 : self::FREE_STORE_CHECKOUT_LIMIT,
			'limitReached'        => ! $is_pro_active && $count >= self::FREE_STORE_CHECKOUT_LIMIT,
			'upgradeUrl'          => 'https://getwpfunnels.com/pricing/',
		);
	}


	/**
	 * Word for a post status, in this screen's terms.
	 *
	 * A store checkout is either taking over the WooCommerce checkout or it is
	 * not, so published and draft read as Enabled and Disabled here — which is
	 * what the legacy screen called them.
	 *
	 * @param string $status Post status.
	 *
	 * @return string
	 * @since 3.13.6
	 */
	private function get_status_label( $status ) {
		if ( 'publish' === $status ) {
			return __( 'Enabled', 'wpfnl' );
		}

		if ( 'trash' === $status ) {
			return __( 'Trashed', 'wpfnl' );
		}

		return __( 'Disabled', 'wpfnl' );
	}


	/**
	 * Canvas URL of a store checkout funnel.
	 *
	 * @param int $funnel_id Funnel id.
	 *
	 * @return string
	 * @since 3.13.6
	 */
	private function get_funnel_edit_url( $funnel_id ) {
		return esc_url_raw( \WPFunnels\Wpfnl_functions::get_funnel_edit_url( $funnel_id ) );
	}


	/**
	 * Ensure the conditions class is loaded.
	 *
	 * @since 3.6.0
	 */
	private function ensure_conditions_class() {
		if ( ! class_exists( 'WPFunnels\\WooCommerce\\Wpfnl_Store_Checkout_Conditions' ) ) {
			require_once WPFNL_DIR . 'includes/core/woocommerce/class-wpfnl-store-checkout-conditions.php';
		}
	}
}
