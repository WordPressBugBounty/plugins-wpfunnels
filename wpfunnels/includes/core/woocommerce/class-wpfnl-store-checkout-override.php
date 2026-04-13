<?php
/**
 * Store Checkout Override
 *
 * Replaces the default WooCommerce checkout page with the WPFunnels
 * Store Checkout step, mirroring the CartFlows global-checkout approach.
 *
 * Multiple store-checkout funnels are supported.  The override evaluates
 * each funnel's configured conditions (products, categories, tags, date range,
 * or no condition) and overrides with the first funnel whose conditions match
 * the current cart.  When several funnels match, the one created earliest wins.
 *
 * @package WPFunnels\WooCommerce
 * @since   3.5.0
 */

namespace WPFunnels\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

/**
 * Class Wpfnl_Store_Checkout_Override
 *
 * @since 3.5.0
 */
class Wpfnl_Store_Checkout_Override {

	use SingletonTrait;

	/**
	 * Constructor — registers all public-facing hooks.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'override_wc_checkout' ), 0 );
	}

	// =========================================================================
	// Public helpers
	// =========================================================================

	/**
	 * Return the best-matching Store Checkout step ID for the current request,
	 * or null if no store-checkout funnel matches.
	 *
	 * Uses the condition evaluator to find the earliest-created funnel whose
	 * checkout conditions match the current WooCommerce cart.
	 *
	 * @return int|null
	 * @since 3.5.0 (updated 3.6.0 to support multiple funnels with conditions)
	 */
	public static function get_checkout_step_id() {
		// Load conditions class if not yet available.
		if ( ! class_exists( 'WPFunnels\\WooCommerce\\Wpfnl_Store_Checkout_Conditions' ) ) {
			require_once __DIR__ . '/class-wpfnl-store-checkout-conditions.php';
		}
		return Wpfnl_Store_Checkout_Conditions::get_matching_checkout_step_id();
	}

	/**
	 * Persist the checkout step ID for a Store Checkout funnel.
	 *
	 * Kept for backwards compatibility — no-op since 3.6.0 as the step is
	 * resolved dynamically at runtime via condition evaluation.
	 *
	 * @param int $funnel_id The WPFunnels funnel post ID.
	 * @return int|null       The checkout step ID, or null if not found.
	 * @since 3.5.0
	 */
	public static function save_checkout_step_id( $funnel_id ) {
		$funnel_id = absint( $funnel_id );
		if ( ! $funnel_id ) {
			return null;
		}
		if ( ! class_exists( 'WPFunnels\\WooCommerce\\Wpfnl_Store_Checkout_Conditions' ) ) {
			require_once __DIR__ . '/class-wpfnl-store-checkout-conditions.php';
		}
		return Wpfnl_Store_Checkout_Conditions::get_checkout_step_id_for_funnel( $funnel_id );
	}

	/**
	 * Clear the saved checkout step ID.
	 *
	 * Kept for backwards compatibility — the single-option approach is no longer
	 * used since 3.6.0 (multi-checkout).
	 *
	 * @since 3.5.0
	 */
	public static function clear_checkout_step_id() {
		delete_option( '_wpfunnels_store_checkout_step_id' );
	}

	// =========================================================================
	// Hook callbacks
	// =========================================================================

	/**
	 * On the `wp` hook — override $GLOBALS['post'] to the best-matching Store
	 * Checkout step so the WooCommerce checkout page renders the WPFunnels step
	 * template instead of the native WC checkout page.
	 *
	 * @since 3.5.0
	 */
	public function override_wc_checkout() {
		if ( ! $this->should_override() ) {
			return;
		}

		$step_id       = self::get_checkout_step_id();
		if ( ! $step_id ) {
			return;
		}

		$checkout_post = get_post( $step_id );
		if ( ! $checkout_post ) {
			return;
		}

		if ( isset( $GLOBALS['posts'][0] ) ) {
			$GLOBALS['posts'][0] = $checkout_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		if ( isset( $GLOBALS['wp_the_query']->post ) ) {
			$GLOBALS['wp_the_query']->post = $checkout_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		$GLOBALS['post'] = $checkout_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Decide whether the Store Checkout override should run on the current request.
	 *
	 * @return bool
	 * @since 3.5.0
	 */
	private function should_override() {
		// Skip if already on a WPFunnels funnel step.
		if ( Wpfnl_functions::is_funnel_step_page() ) {
			return false;
		}

		// Only intercept on the standard WC checkout page.
		if (
			is_product() ||
			! is_checkout() ||
			is_order_received_page() ||
			is_checkout_pay_page()
		) {
			return false;
		}

		// Payment-gateway return / order-received: must not redirect.
		if (
			isset( $_GET['key'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			isset( $_GET['order'] )  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			return false;
		}

		return true;
	}
}
