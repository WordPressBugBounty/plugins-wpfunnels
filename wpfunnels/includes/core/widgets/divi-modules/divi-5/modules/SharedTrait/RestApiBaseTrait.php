<?php
/**
 * Base REST API trait for Divi 5 module preview endpoints.
 *
 * Concrete modules that use this trait must implement:
 *   - static function get_rest_namespace(): string   e.g. 'wpfnl/v1'
 *   - static function get_rest_route(): string       e.g. '/checkout/render'
 *   - static function render_callback(array $attrs, string $content, \WP_Block $block, $elements, array $default_printed_style_attrs): string
 *
 * @package WPFunnels\Widgets\DiviModules\D5\SharedTrait
 * @since   2.9.1
 */

namespace WPFunnels\Widgets\DiviModules\D5\SharedTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait RestApiBaseTrait {

	use ModuleRenderHelperTrait;

	/** Track route registration to prevent duplicates. */
	private static bool $rest_routes_registered = false;

	/**
	 * Called from rest_api_init via ModuleRegistry::register_rest_routes().
	 */
	public static function register_rest_routes_direct(): void {
		if ( static::$rest_routes_registered ) {
			return;
		}
		static::$rest_routes_registered = true;

		register_rest_route(
			static::get_rest_namespace(),
			static::get_rest_route(),
			[
				'methods'             => 'POST',
				'callback'            => [ static::class, 'rest_render_callback' ],
				'permission_callback' => static fn() => current_user_can( 'edit_posts' ),
				'args'                => [
					'attrs'   => [
						'required'          => false,
						'type'              => 'object',
						'default'           => [],
						'sanitize_callback' => static fn( $v ) => is_array( $v ) ? $v : [],
					],
					'id'      => [
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'post_id' => [
						'required'          => false,
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * REST callback — builds a WP_Block and delegates to render_callback().
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_render_callback( \WP_REST_Request $request ) {
		$body    = $request->get_json_params() ?: [];
		$attrs   = is_array( $body['attrs'] ?? null ) ? $body['attrs'] : [];
		$id      = sanitize_text_field( $body['id'] ?? '' );
		$post_id = absint( $body['post_id'] ?? 0 );
		$scope_class = sanitize_html_class( $body['scope_class'] ?? '' );

		// Ensure WooCommerce cart is available
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			if ( ! WC()->cart ) {
				if ( function_exists( 'wc_load_cart' ) ) {
					wc_load_cart();
				}
			}
			if ( ! WC()->session ) {
				WC()->initialize_session();
			}
		}

		$step_id = static::resolve_step_id( $post_id, $request );

		// Set WPFunnels step context and global post so get_the_ID() works
		// inside render_callback (mirrors the frontend WP query context).
		if ( $step_id > 0 ) {
			$step_post = get_post( $step_id );
			if ( $step_post ) {
				$GLOBALS['post'] = $step_post;
				setup_postdata( $step_post );
			}
			do_action( 'wpfnl_divi5_before_rest_render', $step_id );
		}

		// Build WP_Block
		$block_name   = static::get_block_name();
		$parsed_block = [
			'blockName'    => $block_name,
			'attrs'        => $attrs,
			'innerHTML'    => '',
			'innerContent' => [],
			'id'           => sanitize_text_field( $id ) ?: ( $block_name . '-preview' ),
			'orderIndex'   => 0,
			'wpfnl_scope_class' => $scope_class, // Pass scope class here
		];

		$block = new \WP_Block( $parsed_block );

		// Set block_type via reflection (not directly settable)
		try {
			$ref      = new \ReflectionClass( $block );
			$prop     = $ref->getProperty( 'block_type' );
			$prop->setAccessible( true );
			$prop->setValue( $block, (object) [
				'name'     => $block_name,
				'category' => 'module',
			] );
		} catch ( \ReflectionException $e ) {
			// Continue without block_type — render_callback handles it.
		}

		try {
			$html = static::render_callback( $attrs, '', $block, null, [] );

			$response = new \WP_REST_Response( [ 'success' => true, 'html' => $html ], 200 );
			$response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate' );
			$response->header( 'Pragma', 'no-cache' );
			$response->header( 'Expires', '0' );

			return $response;
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'render_failed',
				'WPFunnels Divi5: ' . $e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}
}
