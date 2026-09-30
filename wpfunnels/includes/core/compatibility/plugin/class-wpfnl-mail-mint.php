<?php
/**
 * Mail Mint Compatibility
 *
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Traits\SingletonTrait;

/**
 * Mail Mint Compatibility
 *
 * Keeps a cart restored from a Mail Mint abandoned cart recovery link intact when the
 * link lands on a funnel checkout step.
 *
 * @package WPFunnels\Compatibility\Plugin
 */
class MailMint extends PluginCompatibility {
	use SingletonTrait;

	/**
	 * Initiate Filters/Hook
	 *
	 * @since 3.13.3
	 */
	public function init() {
		add_filter( 'wpfunnels/allow_initiate_funnel_checkout', array( $this, 'allow_initiate_funnel_checkout' ), 10, 2 );
	}

	/**
	 * Skip rebuilding the funnel cart when the visitor arrives from a recovery link.
	 *
	 * Mail Mint Pro restores the abandoned cart on `wp`, then redirects to the checkout page
	 * the cart was captured on with `mint-cart-restored=success`. On a funnel checkout step
	 * the checkout would then empty the cart and re-add the step's default products,
	 * throwing away the restored quantities, variations and order bumps.
	 *
	 * The query arg alone is not trusted, since anyone can add it to a URL. Mail Mint keeps
	 * the restored cart in the WooCommerce session until an order is placed, and that cart
	 * must belong to this step. Otherwise the funnel cart is built as usual.
	 *
	 * @param bool $allow     Whether to initiate the funnel checkout.
	 * @param int  $funnel_id Funnel ID.
	 *
	 * @return bool
	 * @since  3.13.3
	 */
	public function allow_initiate_funnel_checkout( $allow, $funnel_id ) {
		if ( ! $allow || ! isset( $_GET['mint-cart-restored'] ) || 'success' !== $_GET['mint-cart-restored'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $allow;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->session || ! WC()->cart || WC()->cart->is_empty() ) {
			return $allow;
		}

		$restored = WC()->session->get( 'mint_restored_cart_detail' );
		if ( empty( $restored['checkout_page_id'] ) || (int) $restored['checkout_page_id'] !== (int) get_the_ID() ) {
			return $allow;
		}

		return false;
	}

	/**
	 * Check if Mail Mint is activated
	 *
	 * @return bool
	 * @since  3.13.3
	 */
	public function maybe_activate() {
		return defined( 'MRM_VERSION' );
	}
}
