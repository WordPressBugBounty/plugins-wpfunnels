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

		// Get Store Checkout details
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

		// Validate Store Checkout creation
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
	 * Get Store Checkout funnel details
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
			'success' => true,
			'funnel_id' => $store_checkout->ID,
			'funnel_name' => get_the_title( $store_checkout->ID ),
			'edit_url' => add_query_arg(
				array(
					'page' => WPFNL_EDIT_FUNNEL_SLUG,
					'id' => $store_checkout->ID,
					'step_id' => 0,
				),
				admin_url( 'admin.php' )
			),
		);

		return rest_ensure_response( $response );
	}


	/**
	 * Validate if Store Checkout can be created
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function validate_creation( $request ) {
		$store_checkout = $this->get_store_checkout_funnel();
		
		$response = array(
			'can_create' => empty( $store_checkout ),
			'message' => empty( $store_checkout ) 
				? __( 'Store Checkout can be created.', 'wpfnl' )
				: __( 'A Store Checkout already exists. Please delete the existing one before creating a new one.', 'wpfnl' ),
		);

		return rest_ensure_response( $response );
	}


	/**
	 * Get Store Checkout funnel if exists
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
}
