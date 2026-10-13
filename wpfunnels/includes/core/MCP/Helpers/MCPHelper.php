<?php
/**
 * MCPHelper — shared plumbing for WPFunnels abilities.
 *
 * Pagination, error construction, permission callbacks and the output
 * formatters every tool implementation reuses.
 *
 * @package WPFunnels\MCP
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Helpers;

defined( 'ABSPATH' ) || exit;

use WPFunnels\Wpfnl_functions;

/**
 * Class MCPHelper
 */
class MCPHelper {

	/**
	 * Default page size for list tools.
	 */
	public const DEFAULT_PER_PAGE = 20;

	/**
	 * Hard ceiling on page size — protects the model's context window.
	 */
	public const MAX_PER_PAGE = 100;

	/**
	 * Build a structured error.
	 *
	 * @param string $code    Machine-readable code.
	 * @param string $message Human-readable message.
	 * @param array  $data    Extra context.
	 * @return \WP_Error
	 */
	public static function error( $code, $message, $data = [] ) {
		return new \WP_Error( 'wpfunnels_' . $code, $message, $data );
	}

	/**
	 * Permission callback for a capability.
	 *
	 * Every WPFunnels capability today funnels through wpf_manage_funnels;
	 * administrators are allowed via manage_options so a site with a customized
	 * role map never locks the owner out of their own tools.
	 *
	 * @param string $capability Capability to require.
	 * @return callable
	 */
	public static function currentUserCan( $capability = 'wpf_manage_funnels' ) {
		return static function () use ( $capability ) {
			return current_user_can( $capability ) || current_user_can( 'manage_options' );
		};
	}

	/**
	 * Clamp a per_page value into the allowed range.
	 *
	 * @param mixed $per_page Requested page size.
	 * @return int
	 */
	public static function perPage( $per_page ) {
		$per_page = (int) $per_page;
		if ( $per_page < 1 ) {
			$per_page = self::DEFAULT_PER_PAGE;
		}
		return min( $per_page, self::MAX_PER_PAGE );
	}

	/**
	 * Standard pagination envelope.
	 *
	 * @param array $items    Page of items.
	 * @param int   $total    Total matching items.
	 * @param int   $page     Current page (1-based).
	 * @param int   $per_page Page size.
	 * @return array
	 */
	public static function paginate( $items, $total, $page, $per_page ) {
		$per_page = self::perPage( $per_page );
		$page     = max( 1, (int) $page );

		return [
			'items'      => $items,
			'pagination' => [
				'total'       => (int) $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 0,
				'has_more'    => ( $page * $per_page ) < (int) $total,
			],
		];
	}

	/**
	 * Shared pagination schema fragment.
	 *
	 * @return array
	 */
	public static function paginationSchema() {
		return [
			'page'     => [
				'type'        => 'integer',
				'description' => 'Page number, 1-based.',
				'minimum'     => 1,
				'default'     => 1,
			],
			'per_page' => [
				'type'        => 'integer',
				'description' => 'Items per page (max ' . self::MAX_PER_PAGE . ').',
				'minimum'     => 1,
				'maximum'     => self::MAX_PER_PAGE,
				'default'     => self::DEFAULT_PER_PAGE,
			],
		];
	}

	/**
	 * Step types this install supports, as slug => label.
	 *
	 * Offer steps depend on WooCommerce; lead funnels use opt-in steps. Reported
	 * from the runtime so the model never emits a type this site cannot build.
	 *
	 * @return array
	 */
	public static function supportedStepTypes() {
		$types = [
			'landing'  => 'Landing page — first page visitors see',
			'checkout' => 'Checkout page — collects payment',
			'thankyou' => 'Thank-you page — confirmation and next steps',
			'custom'   => 'Custom page — any additional content page',
		];

		if ( Wpfnl_functions::is_wc_active() ) {
			$types['upsell']   = 'Upsell page — one-click offer after checkout';
			$types['downsell'] = 'Downsell page — alternative when the upsell is declined';
		}

		$types['optin'] = 'Opt-in page — captures leads without payment';

		/**
		 * Filter the step types exposed to AI tools.
		 *
		 * @since 3.13.0
		 * @param array $types Slug => description.
		 */
		return apply_filters( 'wpfunnels/mcp_step_types', $types );
	}

	/**
	 * Site's Elementor design tokens, for widgets that don't inherit
	 * Elementor's Global Colors automatically.
	 *
	 * Only the kit's `primary` system color is reported — deliberately not
	 * the `text` color or typography: the kit's body-text color is tuned for
	 * light backgrounds and would likely be illegible as button text, and
	 * most kits carry no custom font override worth copying.
	 *
	 * @return array
	 */
	public static function getSiteDesignTokens() {
		if ( ! Wpfnl_functions::is_elementor_active() || ! class_exists( '\Elementor\Plugin' ) ) {
			return [
				'elementor_active' => false,
				'primary_color'    => null,
			];
		}

		$kit_id   = \Elementor\Plugin::$instance->kits_manager->get_active_id();
		$settings = $kit_id ? get_post_meta( $kit_id, '_elementor_page_settings', true ) : [];
		$colors   = isset( $settings['system_colors'] ) && is_array( $settings['system_colors'] ) ? $settings['system_colors'] : [];

		$primary = null;
		foreach ( $colors as $color ) {
			if ( isset( $color['_id'], $color['color'] ) && 'primary' === $color['_id'] ) {
				$primary = $color['color'];
				break;
			}
		}

		return [
			'elementor_active' => true,
			'primary_color'    => $primary,
		];
	}

	/**
	 * Detect which page builder authored a page's content.
	 *
	 * Elementor and Bricks stamp their own meta keys on any page they've
	 * edited, checked first since either can coexist with block markup left
	 * over in `post_content`. Falls back to `has_blocks()` for Gutenberg,
	 * then to non-empty `post_content` for a builder this site doesn't
	 * specifically detect ('other'), or 'unknown' for an empty/missing page.
	 *
	 * @param int $page_id Post id.
	 * @return string One of gutenberg|elementor|bricks|other|unknown.
	 */
	public static function detectPageBuilder( $page_id ) {
		$page_id = (int) $page_id;
		if ( ! $page_id ) {
			return 'unknown';
		}

		if ( defined( 'ELEMENTOR_VERSION' ) && 'builder' === get_post_meta( $page_id, '_elementor_edit_mode', true ) ) {
			return 'elementor';
		}

		// `_bricks_page_content_2` is Bricks' own storage key regardless of
		// whether it's active as a theme or plugin.
		if ( '' !== get_post_meta( $page_id, '_bricks_page_content_2', true ) ) {
			return 'bricks';
		}

		$post = get_post( $page_id );
		if ( ! $post ) {
			return 'unknown';
		}

		if ( function_exists( 'has_blocks' ) && has_blocks( $post ) ) {
			return 'gutenberg';
		}

		return '' !== trim( (string) $post->post_content ) ? 'other' : 'unknown';
	}

	/**
	 * Whether a step type can hold products.
	 *
	 * @param string $step_type Step type slug.
	 * @return bool
	 */
	public static function stepTypeHoldsProducts( $step_type ) {
		return in_array( $step_type, [ 'checkout', 'upsell', 'downsell' ], true );
	}

	/**
	 * Product meta key for a step type.
	 *
	 * @param string $step_type Step type slug.
	 * @return string
	 */
	public static function productMetaKey( $step_type ) {
		switch ( $step_type ) {
			case 'upsell':
				return '_wpfnl_upsell_products';
			case 'downsell':
				return '_wpfnl_downsell_products';
			default:
				return '_wpfnl_checkout_products';
		}
	}

	/**
	 * Load a funnel post, or an error when the id is not a funnel.
	 *
	 * @param int $funnel_id Funnel post id.
	 * @return \WP_Post|\WP_Error
	 */
	public static function requireFunnel( $funnel_id ) {
		$funnel_id = (int) $funnel_id;
		$post      = $funnel_id ? get_post( $funnel_id ) : null;

		if ( ! $post || WPFNL_FUNNELS_POST_TYPE !== $post->post_type ) {
			return self::error(
				'funnel_not_found',
				sprintf( 'No funnel found with ID %d. Use wpfunnels/list-funnels to find valid IDs.', $funnel_id ),
				[ 'funnel_id' => $funnel_id ]
			);
		}

		return $post;
	}

	/**
	 * Load a step post, or an error when the id is not a step.
	 *
	 * @param int $step_id Step post id.
	 * @return \WP_Post|\WP_Error
	 */
	public static function requireStep( $step_id ) {
		$step_id = (int) $step_id;
		$post    = $step_id ? get_post( $step_id ) : null;

		if ( ! $post || WPFNL_STEPS_POST_TYPE !== $post->post_type ) {
			return self::error(
				'step_not_found',
				sprintf( 'No funnel step found with ID %d. Use wpfunnels/list-steps to find valid IDs.', $step_id ),
				[ 'step_id' => $step_id ]
			);
		}

		return $post;
	}

	/**
	 * Format a funnel for tool output. Never includes builder payloads.
	 *
	 * @param \WP_Post $funnel  Funnel post.
	 * @param bool     $verbose Include step summaries and settings.
	 * @return array
	 */
	public static function formatFunnel( $funnel, $verbose = false ) {
		$funnel_id = (int) $funnel->ID;
		$steps     = Wpfnl_functions::get_steps( $funnel_id );
		$steps     = is_array( $steps ) ? $steps : [];

		$data = [
			'id'          => $funnel_id,
			'name'        => $funnel->post_title,
			'status'      => $funnel->post_status,
			'type'        => get_post_meta( $funnel_id, '_wpfnl_funnel_type', true ) ?: 'wc',
			'step_count'  => count( $steps ),
			'step_types'  => array_values(
				array_filter(
					array_map(
						static function ( $step ) {
							return isset( $step['step_type'] ) ? $step['step_type'] : null;
						},
						$steps
					)
				)
			),
			'created_at'  => $funnel->post_date,
			'modified_at' => $funnel->post_modified,
			'canvas_url'  => self::canvasUrl( $funnel_id ),
		];

		if ( $verbose ) {
			$data['steps']        = array_map(
				static function ( $step ) {
					return MCPHelper::formatStepSummary( $step );
				},
				$steps
			);
			$data['ai_generated'] = (bool) get_post_meta( $funnel_id, '_wpfnl_ai_generated', true );
		}

		return $data;
	}

	/**
	 * Format one entry of the `_steps_order` array.
	 *
	 * @param array $step Step order entry.
	 * @return array
	 */
	public static function formatStepSummary( $step ) {
		$step_id = isset( $step['id'] ) ? (int) $step['id'] : 0;

		return [
			'id'        => $step_id,
			'name'      => isset( $step['name'] ) ? $step['name'] : get_the_title( $step_id ),
			'step_type' => isset( $step['step_type'] ) ? $step['step_type'] : '',
			'view_url'  => $step_id ? get_permalink( $step_id ) : '',
			'edit_url'  => $step_id ? get_edit_post_link( $step_id, 'raw' ) : '',
		];
	}

	/**
	 * Funnel canvas (builder) URL.
	 *
	 * @param int $funnel_id Funnel id.
	 * @return string
	 */
	public static function canvasUrl( $funnel_id ) {
		return \WPFunnels\Wpfnl_functions::get_funnel_edit_url( (int) $funnel_id );
	}
}
