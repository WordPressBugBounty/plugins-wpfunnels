<?php
/**
 * Orderbump controller
 *
 * @package WPFunnels\Rest\Controllers
 * @since 2.7.9
 */

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use Wpfnl_Type_Factory;
use WPFunnels\Wpfnl_functions;


/**
 * This class has the functions which are responsible
 * to control the checkout step
 *
 * @package /includes/core/rest-api/Controllers
 * @since 2.7.9
 */
class CheckoutController extends Wpfnl_REST_Controller {
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
	protected $rest_base = 'checkout';

	/**
	 * Makes sure the current user has access to READ the settings APIs.
	 *
	 *
	 * @return WP_Error|boolean
	 * @since  2.7.9
	 */
	public function get_items_permissions_check( $request ) {
		if ( !Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'settings' ) ) {
			return new WP_Error( 'wpfunnels_rest_cannot_edit', __( 'Sorry, you cannot list resources.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

    /**
	 * Register rest routes
	 *
	 * @since 2.7.9
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/wpfnl-add-product',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array(
						$this,
						'add_product',
					),
					'args'				  => array(
						'id'         => array(
							'required'          => true,
							'type'              => array('integer', 'string'),
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg'
						),
						'step_id'		  => array(
							'default'           => 0,
							'type'              => array('integer', 'string'),
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg'
						),
						'isLms'			  => array(
							'type'				=> 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'default'			=> 'wc',
							'validate_callback' => 'rest_validate_request_arg'
						)
					),
					'permission_callback' => array(
						$this,
						'get_items_permissions_check',
					),
				),
			)
		);
	}

    /**
	 * Add product row
	 *
	 * @param WP_REST_Request $payload Payload request.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function add_product( $payload ) {
		if ( !$payload || !isset( $payload['id'] ) ) {
            return $this->prepare_wp_error_response(
                'product_add_error',
                __( 'Product Can\'t be added', 'wpfnl'),
                array(
                    'success'  => false,
                    'status'   => 400,
                    'products' => array()
                )
            );
		}
		$product_id     = $payload['id'];
		$step_id        = isset ( $payload['step_id'] ) ? $payload['step_id'] : 0;
		$saved_products = get_post_meta( $step_id, '_wpfnl_checkout_products', true );

        $_class = ( isset( $payload['isLms'] ) && 'true' === $payload['isLms'] ) ? 'lms' : 'wc';

		$class_object = Wpfnl_Type_Factory::build( $_class );
		if ( $class_object ) {
			$response = $class_object->save_items( $payload, $product_id, $saved_products );
			if ( $response ) {
				return $response;
			}
		}
		return $this->prepare_wp_error_response(
            'product_add_error',
            __( 'Product Can\'t be added', 'wpfnl'),
            array(
                'success'  => false,
                'status'   => 400,
                'products' => array()
            )
        );
	}
}