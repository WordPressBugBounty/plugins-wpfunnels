<?php
/**
 * Shared helpers used by all WPFunnels Divi 5 module render callbacks.
 *
 * @package WPFunnels\Widgets\DiviModules\D5\SharedTrait
 * @since   2.9.1
 */

namespace WPFunnels\Widgets\DiviModules\D5\SharedTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleRenderHelperTrait {

	/**
	 * Resolve a valid wpfunnel_steps post ID from several candidate sources.
	 *
	 * Divi VB often sends the page ID (not the step CPT ID) inside REST
	 * requests, so we check multiple $_REQUEST keys and the HTTP referrer.
	 *
	 * @param int              $post_id  Post ID from the REST request body.
	 * @param \WP_REST_Request $request  The REST request (may add more candidates).
	 * @return int  Valid wpfunnel_steps post ID, or 0 if none found.
	 */
	protected static function resolve_step_id( int $post_id, \WP_REST_Request $request ): int {
		$candidates = [];

		// 1. Divi VB custom param for WPFunnels
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		foreach ( [ 'et_wpfnl_id', 'et_post_id' ] as $key ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				$candidates[] = absint( wp_unslash( $_REQUEST[ $key ] ) );
			}
		}
		// phpcs:enable

		// 2. Explicit post_id from request body
		$candidates[] = $post_id;

		// 3. HTTP referrer query params
		if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			if ( preg_match( '/[?&](?:et_wpfnl_id|et_post_id|post_id|p)=(\d+)/', $referer, $m ) ) {
				$candidates[] = absint( $m[1] );
			}
		}

		foreach ( $candidates as $id ) {
			if ( $id <= 0 ) {
				continue;
			}
			$post = get_post( $id );
			if ( $post && WPFNL_STEPS_POST_TYPE === $post->post_type ) {
				return $id;
			}
		}

		// 4. Fallback: any published wpfunnel_steps post
		$posts = get_posts(
			[
				'post_type'              => WPFNL_STEPS_POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		return ! empty( $posts ) ? $posts[0]->ID : 0;
	}

	/**
	 * Return true when the current request is a REST API call
	 * (Visual Builder preview context).
	 */
	protected static function is_rest_request(): bool {
		return ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| false !== strpos( $_SERVER['REQUEST_URI'] ?? '', '/wp-json/' );
	}

	/**
	 * Wrap rendered HTML in Divi 5's Module::render() to apply order-class,
	 * styles, and script data.  Falls back to raw HTML in REST context.
	 *
	 * @param string     $html               Server-rendered module HTML.
	 * @param array      $attrs              Module attributes.
	 * @param mixed      $elements           ModuleElements object (null in REST).
	 * @param \WP_Block  $block              The WP_Block being rendered.
	 * @param string     $block_name         Divi 5 block name, e.g. 'wpfnl/checkout'.
	 * @param callable   $classnames_fn      Callable for classnames.
	 * @param callable   $styles_component   Callable for styles.
	 * @param callable   $script_data_fn     Callable for script data.
	 * @return string
	 */
	protected static function wrap_with_module_render(
		string $html,
		array $attrs,
		$elements,
		\WP_Block $block,
		string $block_name,
		callable $classnames_fn,
		callable $styles_component,
		callable $script_data_fn
	): string {
		// In REST API context (VB preview), Module::render() is not needed.
		if ( self::is_rest_request() && null === $elements ) {
			return $html;
		}

		if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
			return $html;
		}

		$module_id = $block->parsed_block['id'] ?? '';

		return \ET\Builder\Packages\Module\Module::render(
			[
				'orderIndex'          => $block->parsed_block['orderIndex'] ?? 0,
				'storeInstance'       => $block->parsed_block['storeInstance'] ?? null,
				'attrs'               => $attrs,
				'elements'            => $elements,
				'id'                  => $module_id,
				'name'                => $block_name,
				'moduleCategory'      => 'module',
				'classnamesFunction'  => $classnames_fn,
				'stylesComponent'     => $styles_component,
				'scriptDataComponent' => $script_data_fn,
				'parentAttrs'         => [],
				'parentId'            => '',
				'parentName'          => '',
				'children'            => [ $html ],
			]
		);
	}

	/**
	 * Merge module.json defaults into $attrs.
	 *
	 * @param array  $attrs       Saved block attributes.
	 * @param string $block_name  Divi 5 block name, e.g. 'wpfnl/checkout'.
	 * @return array
	 */
	protected static function merge_defaults( array $attrs, string $block_name ): array {
		if ( ! class_exists( 'ET\Builder\Packages\ModuleLibrary\ModuleRegistration' ) ) {
			return $attrs;
		}
		try {
			$defaults = \ET\Builder\Packages\ModuleLibrary\ModuleRegistration::get_default_attrs( $block_name );
			if ( ! empty( $defaults ) ) {
				return array_replace_recursive( $defaults, $attrs );
			}
		} catch ( \Exception $e ) {
			// Continue without defaults.
		}
		return $attrs;
	}

	/**
	 * Helper: extract a toggle/select value from D5 attribute structure.
	 * D5 toggle: $attrs['key']['desktop']['value']
	 *
	 * @param array  $attrs
	 * @param string $key
	 * @return string|null
	 */
	protected static function get_attr_value( array $attrs, string $key ): ?string {
		return $attrs[ $key ]['desktop']['value'] ?? null;
	}

	/**
	 * Helper: extract a text/label value from D5 attribute structure.
	 * D5 text: $attrs['key']['innerContent']['desktop']['value']
	 *
	 * @param array  $attrs
	 * @param string $key
	 * @return string|null
	 */
	protected static function get_text_value( array $attrs, string $key ): ?string {
		return $attrs[ $key ]['innerContent']['desktop']['value'] ?? null;
	}
}
