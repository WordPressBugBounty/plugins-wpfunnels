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
			'edit_url'   => add_query_arg(
				array(
					'page'    => WPFNL_EDIT_FUNNEL_SLUG,
					'id'      => $store_checkout->ID,
					'step_id' => 0,
				),
				admin_url( 'admin.php' )
			),
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
				'edit_url'       => add_query_arg(
					array(
						'page'    => WPFNL_EDIT_FUNNEL_SLUG,
						'id'      => $funnel->ID,
						'step_id' => 0,
					),
					admin_url( 'admin.php' )
				),
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
