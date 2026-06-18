<?php
/**
 * WooCommerce Germanized Compatibility
 *
 * Handles two integration points between WPFunnels and Germanized:
 *
 * 1. Checkout submit button – Germanized hides the default WC submit button via
 *    woocommerce_order_button_html filter and renders its own block at the end
 *    of woocommerce_checkout_order_review.  On WPFunnels funnel checkout pages
 *    this means the button lands outside WPFunnels' payment section.  We tell
 *    Germanized to disable its checkout adjustments by defining the
 *    WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS constant before Germanized's own
 *    mechanism reads it (woocommerce_before_checkout_form priority -999).
 *    Germanized then:
 *      - stops hiding / relocating the WC order button,
 *      - moves legal checkboxes to woocommerce_review_order_before_payment
 *        so they still render inside WPFunnels' payment section, and
 *      - outputs a wc_gzd_checkout_disabled hidden field so AJAX order-review
 *        requests also carry the disabled flag.
 *
 * 2. Offer order confirmation – Germanized removes the standard WC order-
 *    notification hooks and sends its own confirmation via the
 *    woocommerce_payment_successful_result filter, which never fires for
 *    WPFunnels' AJAX offer acceptance.  We hook into wpfunnels/offer_accepted
 *    and call Germanized's confirm_order() directly for child/separate orders.
 *
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class WooCommerceGermanized extends PluginCompatibility {
	use SingletonTrait;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		// Disable Germanized's checkout adjustments on WPFunnels funnel checkout
		// pages. Priority -1000 fires before Germanized's priority -999 handler
		// (wc_gzd_maybe_disable_checkout_adjustments), so the constant is already
		// set when Germanized reads it and removes its button/submit hooks.
		add_action( 'woocommerce_before_checkout_form', array( $this, 'maybe_disable_checkout_adjustments' ), -1000 );

		// Re-add the standard order-review/payment hooks that Germanized's ET Builder
		// (Divi) compatibility strips when checkout adjustments are disabled. Priority 20
		// runs after Germanized's own priority-10 handler so it can undo the removal.
		add_action( 'woocommerce_gzd_disabled_checkout_adjustments', array( $this, 'restore_order_review_hooks' ), 20 );

		// Trigger Germanized's order confirmation for upsell/downsell child orders.
		add_action( 'wpfunnels/offer_accepted', array( $this, 'send_offer_order_confirmation' ), 10, 2 );
	}

	/**
	 * Restore the default WooCommerce order-review hooks on WPFunnels checkout steps.
	 *
	 * When the Divi theme is active, Germanized's ET Builder compatibility
	 * (WC_GZD_Compatibility_ET_Builder) listens on woocommerce_gzd_disabled_checkout_adjustments
	 * and removes woocommerce_order_review (priority 10) and woocommerce_checkout_payment
	 * (priority 20) from woocommerce_checkout_order_review.  It assumes Divi's own native
	 * checkout modules (et_pb_wc_checkout_order_details / _payment_info) will re-add them.
	 *
	 * WPFunnels' Divi checkout module instead renders the full [woocommerce_checkout]
	 * shortcode, which relies on those standard hooks, so they are never re-added and the
	 * order review section renders empty.  We re-attach them here for WPFunnels checkout
	 * steps only, leaving genuine Divi WooCommerce checkouts untouched.
	 *
	 * @return void
	 */
	public function restore_order_review_hooks() {
		if ( ! Wpfnl_functions::check_if_this_is_step_type( 'checkout' ) ) {
			return;
		}

		if ( ! has_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review' ) ) {
			add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
		}

		if ( ! has_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment' ) ) {
			add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		}
	}

	/**
	 * Define WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS on WPFunnels funnel checkout pages.
	 *
	 * Germanized's wc_gzd_maybe_disable_checkout_adjustments() reads this
	 * constant at woocommerce_before_checkout_form priority -999.  When true it:
	 *   - removes the hooks that temporarily hide woocommerce_order_button_html,
	 *   - removes woocommerce_gzd_template_order_submit from
	 *     woocommerce_checkout_order_review (prevents a duplicate button),
	 *   - moves legal checkboxes to woocommerce_review_order_before_payment so
	 *     they still display inside WPFunnels' payment.php template, and
	 *   - outputs a hidden wc_gzd_checkout_disabled field so AJAX order-review
	 *     fragments also receive the disabled flag.
	 *
	 * @return void
	 */
	public function maybe_disable_checkout_adjustments() {
		if ( ! Wpfnl_functions::check_if_this_is_step_type( 'checkout' ) ) {
			return;
		}

		if ( ! defined( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS' ) ) {
			define( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS', true );
		}
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
	 * @param \WC_Order $order          Child order created for the upsell/downsell.
	 * @param array     $_offer_product Offer product data (unused; required by hook signature).
	 * @return void
	 */
	public function send_offer_order_confirmation( $order, $_offer_product ) {
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

		$gzd = \WC_germanized();
		if ( ! isset( $gzd->emails ) || ! is_object( $gzd->emails ) ) {
			return;
		}

		if ( ! function_exists( 'wc_gzd_send_instant_order_confirmation' ) ) {
			return;
		}

		if ( \wc_gzd_send_instant_order_confirmation( $order ) ) {
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
