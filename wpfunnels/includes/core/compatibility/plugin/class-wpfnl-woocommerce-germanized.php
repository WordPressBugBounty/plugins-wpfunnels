<?php
/**
 * WooCommerce Germanized Compatibility
 *
 * When Germanized is active it removes standard WooCommerce order notification
 * hooks (customer_processing_order, new_order) before the order status changes,
 * then sends its own confirmation via the woocommerce_payment_successful_result
 * filter.  That filter never fires for WPFunnels upsell/downsell AJAX requests,
 * so the confirmation email is silently dropped.
 *
 * This class hooks into wpfunnels/offer_accepted – which fires after a child
 * order has been created and payment_complete() called – and explicitly
 * triggers Germanized's own confirm_order() so the customer and admin
 * receive their order confirmation.
 *
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Traits\SingletonTrait;

class WooCommerceGermanized extends PluginCompatibility {
	use SingletonTrait;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wpfunnels/offer_accepted', array( $this, 'send_offer_order_confirmation' ), 10, 2 );
	}

	/**
	 * Send Germanized order confirmation email for upsell/downsell orders.
	 *
	 * Germanized prevents the standard WooCommerce notification hooks from
	 * firing and relies on woocommerce_payment_successful_result to send its
	 * own confirmation.  That filter is never applied during WPFunnels'
	 * AJAX-based offer acceptance, so we trigger it manually here.
	 *
	 * Only acts on child/separate orders (identified by _wpfunnels_offer_parent_id
	 * meta) because the main-order path does not call payment_complete() again
	 * and therefore is unaffected by Germanized's hook removal.
	 *
	 * @param \WC_Order $order        The order object (child order when using
	 *                                separate-order mode, main order otherwise).
	 * @param array     $offer_product Offer product data.
	 * @return void
	 */
	public function send_offer_order_confirmation( $order, $offer_product ) {
		if ( ! $this->maybe_activate() ) {
			return;
		}

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Only trigger for child (separate) orders – these are the ones whose
		// payment_complete() call gets intercepted by Germanized.
		if ( ! $order->get_meta( '_wpfunnels_offer_parent_id' ) ) {
			return;
		}

		if ( ! function_exists( 'WC_germanized' ) ) {
			return;
		}

		$gzd = WC_germanized();
		if ( ! isset( $gzd->emails ) || ! is_object( $gzd->emails ) ) {
			return;
		}

		if ( ! function_exists( 'wc_gzd_send_instant_order_confirmation' ) ) {
			return;
		}

		if ( wc_gzd_send_instant_order_confirmation( $order ) ) {
			$gzd->emails->confirm_order( $order );
		}
	}

	/**
	 * Check whether WooCommerce Germanized is active.
	 *
	 * @return bool
	 */
	public function maybe_activate() {
		return defined( 'WC_GERMANIZED_VERSION' );
	}
}
