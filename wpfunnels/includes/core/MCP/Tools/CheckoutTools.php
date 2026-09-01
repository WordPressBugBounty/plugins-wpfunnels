<?php
/**
 * CheckoutTools — MCP abilities for WPFunnels Free Checkout & Store Checkout.
 *
 * The custom checkout-fields tools (`list-checkout-fields`,
 * `upsert-checkout-field`) previously lived here but are a Pro-only capability
 * — they have moved to `wpfunnels-pro/includes/core/mcp/Tools/CheckoutFieldTools.php`.
 * This file now only contains the Free checkout/store-checkout domain.
 *
 * @package WPFunnels\MCP\Tools
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;

/**
 * Class CheckoutTools
 */
class CheckoutTools {

	/**
	 * Register tool definitions.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/get-checkout-settings' => [
				'description' => 'Get configured products, discount, layout style, and field options for a checkout step.',
				'category'    => 'checkout',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'ID of the checkout step.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'callback'    => [ __CLASS__, 'getCheckoutSettings' ],
			],

			'wpfunnels/update-checkout-settings' => [
				'description' => 'Update checkout step layout style, coupon input visibility, and express checkout options.',
				'category'    => 'checkout',
				'readonly'    => false,
				'destructive' => true,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'step_id'            => [
							'type'        => 'integer',
							'description' => 'ID of the checkout step.',
						],
						'layout'             => [
							'type'        => 'string',
							'enum'        => [ '1col', '2col', '2step', 'multistep' ],
							'description' => 'Checkout page layout style.',
						],
						'show_coupon'        => [
							'type'        => 'boolean',
							'description' => 'Show or hide coupon code field.',
						],
						'enable_express_checkout' => [
							'type'        => 'boolean',
							'description' => 'Enable Apple Pay / Google Pay / Stripe Express checkout buttons.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'callback'    => [ __CLASS__, 'updateCheckoutSettings' ],
			],

			'wpfunnels/get-store-checkout' => [
				'description' => 'Get the current global WooCommerce store checkout funnel configuration.',
				'category'    => 'checkout',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [],
				],
				'callback'    => [ __CLASS__, 'getStoreCheckout' ],
			],

			'wpfunnels/configure-store-checkout' => [
				'description' => 'Set or replace the global WooCommerce store checkout funnel.',
				'category'    => 'checkout',
				'readonly'    => false,
				'destructive' => true,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'ID of the funnel to use as store checkout.',
						],
						'enable'    => [
							'type'        => 'boolean',
							'description' => 'Enable or disable store checkout override.',
						],
					],
					'required'   => [ 'funnel_id' ],
				],
				'callback'    => [ __CLASS__, 'configureStoreCheckout' ],
			],
		];
	}

	/**
	 * Get checkout settings.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getCheckoutSettings( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		return [
			'step_id'                 => (int) $step->ID,
			'layout'                  => get_post_meta( $step->ID, '_wpfnl_checkout_layout', true ) ?: '2col',
			'show_coupon'             => 'yes' === ( get_post_meta( $step->ID, '_wpfnl_show_coupon', true ) ?: 'yes' ),
			'enable_express_checkout' => 'yes' === ( get_post_meta( $step->ID, '_wpfnl_enable_express_checkout', true ) ?: 'no' ),
		];
	}

	/**
	 * Update checkout settings.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function updateCheckoutSettings( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		if ( isset( $input['layout'] ) ) {
			update_post_meta( $step->ID, '_wpfnl_checkout_layout', sanitize_text_field( $input['layout'] ) );
		}
		if ( isset( $input['show_coupon'] ) ) {
			update_post_meta( $step->ID, '_wpfnl_show_coupon', $input['show_coupon'] ? 'yes' : 'no' );
		}
		if ( isset( $input['enable_express_checkout'] ) ) {
			update_post_meta( $step->ID, '_wpfnl_enable_express_checkout', $input['enable_express_checkout'] ? 'yes' : 'no' );
		}

		return self::getCheckoutSettings( [ 'step_id' => $step->ID ] );
	}

	/**
	 * Get store checkout.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function getStoreCheckout( $input = [] ) {
		$funnel_id = (int) get_option( '_wpfnl_store_checkout_funnel_id', 0 );
		$enabled   = 'yes' === get_option( '_wpfnl_store_checkout_enabled', 'no' );

		return [
			'enabled'    => $enabled,
			'funnel_id'  => $funnel_id,
			'canvas_url' => $funnel_id ? MCPHelper::canvasUrl( $funnel_id ) : null,
		];
	}

	/**
	 * Configure store checkout.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function configureStoreCheckout( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$enable = ! isset( $input['enable'] ) || ! ! $input['enable'];

		update_option( '_wpfnl_store_checkout_funnel_id', (int) $funnel->ID );
		update_option( '_wpfnl_store_checkout_enabled', $enable ? 'yes' : 'no' );
		update_post_meta( $funnel->ID, '_wpfnl_funnel_type', 'store_checkout' );

		return [
			'configured' => true,
			'funnel_id'  => (int) $funnel->ID,
			'enabled'    => $enable,
		];
	}
}
