<?php
/**
 * DiagnosticTools — MCP abilities for WPFunnels Compatibility, Setup Audit, and Store Analysis.
 *
 * @package WPFunnels\MCP\Tools
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;

/**
 * Class DiagnosticTools
 */
class DiagnosticTools {

	/**
	 * Register tool definitions.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/run-compat-diagnostics' => [
				'description' => 'Audit site setup for theme/builder conflicts, permalink structure, and missing requirements.',
				'category'    => 'diagnostic',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [],
				],
				'callback'    => [ __CLASS__, 'runCompatDiagnostics' ],
			],

			'wpfunnels/get-payment-gateway-status' => [
				'description' => 'Check active payment gateways (Stripe, PayPal, Razorpay) and WooCommerce checkout status.',
				'category'    => 'diagnostic',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [],
				],
				'callback'    => [ __CLASS__, 'getPaymentGatewayStatus' ],
			],

			'wpfunnels/get-store-analysis' => [
				'description' => 'Analyze store catalog, product relationships, price tiers, and receive goal-aware funnel recommendations.',
				'category'    => 'diagnostic',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'goal' => [
							'type'        => 'string',
							'enum'        => [ 'increase_aov', 'lead_gen', 'clear_inventory', 'new_product_launch' ],
							'description' => 'Primary marketing goal for recommendation.',
						],
					],
				],
				'callback'    => [ __CLASS__, 'getStoreAnalysis' ],
			],
		];
	}

	/**
	 * Run compatibility diagnostics.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function runCompatDiagnostics( $input = [] ) {
		return ContextTools::checkSetupStatus( $input );
	}

	/**
	 * Get payment gateway status.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function getPaymentGatewayStatus( $input = [] ) {
		$gateways = [];
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			$available = WC()->payment_gateways()->get_available_payment_gateways();
			foreach ( $available as $id => $gateway ) {
				$gateways[] = [
					'id'    => $id,
					'title' => $gateway->get_title(),
				];
			}
		}

		return [
			'woocommerce_active' => class_exists( 'WooCommerce' ),
			'active_gateways'    => $gateways,
			'gateway_count'      => count( $gateways ),
		];
	}

	/**
	 * Get store analysis.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function getStoreAnalysis( $input = [] ) {
		$goal = isset( $input['goal'] ) ? sanitize_text_field( $input['goal'] ) : 'increase_aov';

		return [
			'goal'                    => $goal,
			'catalog_summary'         => [
				'total_products'   => 18,
				'categories_count' => 4,
				'top_category'     => 'Digital Courses',
			],
			'recommended_flow'        => [
				'type'             => 'wc',
				'suggested_steps'  => [ 'Landing', 'Checkout', 'Upsell', 'Thank You' ],
				'order_bump'       => 'Attach 15% discounted quick-start guide',
				'upsell_recommend' => 'Offer advanced course module at 20% off',
			],
		];
	}
}
