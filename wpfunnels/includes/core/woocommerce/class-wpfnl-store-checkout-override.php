<?php
/**
 * Store Checkout Override
 *
 * Replaces the default WooCommerce checkout page with the WPFunnels
 * Store Checkout step, mirroring the CartFlows global-checkout approach.
 * Also ensures the funnel thank-you step is used after order completion
 * (the existing `woocommerce_get_checkout_order_received_url` filter in
 * Wpfnl_Public already handles that via `redirect_to_funnel_thankyou_page`).
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
 * Hooks:
 * - `woocommerce_get_checkout_url`    — returns the Store Checkout step permalink.
 * - `wp` (priority 0)                — overrides $GLOBALS['post'] so the checkout
 *                                       page renders the WPFunnels step template.
 * - `template_redirect` (priority 1) — hard redirects from the WC checkout page to
 *                                       the Store Checkout step URL.
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
		add_action( 'before_delete_post', array( $this, 'maybe_clear_checkout_step_id' ) );
	}

	// =========================================================================
	// Public helpers
	// =========================================================================

	/**
	 * Return the Store Checkout step ID from the WP option, or null if not set /
	 * the step is not published.
	 *
	 * @return int|null
	 * @since 3.5.0
	 */
	public static function get_checkout_step_id() {
		$step_id = (int) get_option( '_wpfunnels_store_checkout_step_id', 0 );
		if ( ! $step_id ) {
			return null;
		}
		$post = get_post( $step_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return null;
		}
		return $step_id;
	}

	/**
	 * Persist the checkout step ID for a Store Checkout funnel.
	 * Call this whenever a Store Checkout funnel is created or updated.
	 *
	 * @param int $funnel_id The WPFunnels funnel post ID.
	 * @return int|null       The saved step ID, or null if no checkout step found.
	 * @since 3.5.0
	 */
	public static function save_checkout_step_id( $funnel_id ) {
		$funnel_id = absint( $funnel_id );
		if ( ! $funnel_id ) {
			return null;
		}
        $steps = Wpfnl_functions::get_steps( $funnel_id );
        foreach ( $steps as $step ) {
            $step_id = is_array( $step ) && isset( $step['id'] ) ? $step['id'] : '';
            if( ! $step_id ) {
                continue;
            }
            $step_type = get_post_meta( $step_id, '_step_type', true );
            if ( 'checkout' === $step_type ) {
                update_option( '_wpfunnels_store_checkout_step_id', $step_id );
                return $step_id;
            }
        }

		return null;
	}

	/**
	 * Clear the saved checkout step ID (e.g. when the Store Checkout funnel is deleted).
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
	 * On the `wp` hook — override $GLOBALS['post'] to the Store Checkout step so
	 * the WooCommerce checkout page renders the WPFunnels step template instead of
	 * the native WC checkout page.  Mirrors CartFlows' `override_global_checkout()`.
	 *
	 * @since 3.5.0
	 */
	public function override_wc_checkout() {
		if ( ! $this->should_override() ) {
			return;
		}

		$step_id       = self::get_checkout_step_id();
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

	/**
	 * When a funnel or step is permanently deleted, clear the stored checkout
	 * step ID if it matches the deleted post so stale data is never used.
	 *
	 * @param int $post_id The post being deleted.
	 * @return void
	 * @since 3.5.0
	 */
	public function maybe_clear_checkout_step_id( $post_id ) {
		$stored = (int) get_option( '_wpfunnels_store_checkout_step_id', 0 );
		if ( ! $stored ) {
			return;
		}

		// Clear if the deleted post is the saved step or its parent funnel.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$stored_post = get_post( $stored );

		if (
			(int) $post_id === $stored ||
			( WPFNL_FUNNELS_POST_TYPE === $post->post_type && $stored_post && (int) $post->ID === (int) $stored_post->post_parent )
		) {
			self::clear_checkout_step_id();
		}
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Decide whether the Store Checkout override should run on the current request.
	 *
	 * Returns false when:
	 * - Already on a WPFunnels step page (avoids double-override).
	 * - Not on the WooCommerce checkout page, on the order-received page, or on the
	 *   checkout-pay page.
	 * - The request carries a gateway return `key` or `order` parameter (payment
	 *   gateway callbacks / order-received pages must not be redirected).
	 * - No valid published Store Checkout step is configured.
	 *
	 * @return bool
	 * @since 3.5.0
	 */
	private function should_override() {
		// Skip if already on a WPFunnels funnel step
		if ( Wpfnl_functions::is_funnel_step_page() ) {
			return false;
		}

		// Only intercept on the standard WC checkout page
		if (
			is_product() ||
			! is_checkout() ||
			is_order_received_page() ||
			is_checkout_pay_page()
		) {
			return false;
		}

		// Payment-gateway return / order-received: must not redirect
		if (
			isset( $_GET['key'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			isset( $_GET['order'] )  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			return false;
		}

		// Must have a valid, published checkout step
		if ( ! self::get_checkout_step_id() ) {
			return false;
		}

		return true;
	}
}
