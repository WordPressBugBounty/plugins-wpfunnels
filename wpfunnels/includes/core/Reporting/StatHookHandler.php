<?php

namespace WPFunnels\Report;

use Automattic\WooCommerce\Utilities\OrderUtil;
use FKWCS\Gateway\Stripe\P24;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Rest\Controllers\DashboardController;

class StatHookHandler
{

	public function __construct()
	{
		add_action('wpfunnels/funnel_order_placed', array($this, 'update_stat_data_from_order'), 10, 3);
		add_action('woocommerce_order_status_changed', array($this, 'change_order_status'), 10, 4);
		add_action('template_redirect', array($this, 'track_funnel_checkout_visit'), 1);
		add_action('wp_footer', array($this, 'track_native_checkout_visit'));

		if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && OrderUtil::custom_orders_table_usage_is_enabled()) {
			add_action('woocommerce_delete_order', array($this, 'delete_stat_data'));
		} else {
			add_action('delete_post', array($this, 'delete_stat_data'));
		}

		add_action('woocommerce_order_fully_refunded', array($this, 'update_stat_table_for_full_refund'), 10, 1);
		add_action('woocommerce_order_partially_refunded', array($this, 'update_stat_table_for_partial_refund'), 10, 2);
	}


	/**
	 * Update reporting data from order
	 *
	 * @param $order_id
	 * @param $funnel_id
	 * @param $checkout_id
	 */
	public function update_stat_data_from_order($order_id, $funnel_id, $checkout_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'wpfnl_stats';
		$order = wc_get_order($order_id);

		if (!$order) {
			return;
		}

		$status = $order->get_status();
		$total = $order->get_total();
		$customer_id = $order->get_customer_id();
		$ob_revenue = $this->get_order_bump_revenue($order);
		$current_date_time = current_time('mysql');
		$current_date_time_gmt = current_time('mysql', 1);

		$wpdb->insert(
			$table,
			array(
				'order_id' => $order_id,
				'funnel_id' => $funnel_id,
				'customer_id' => $customer_id,
				'total_sales' => $total,
				'orderbump_sales' => $ob_revenue,
				'status' => $status,
				'date_created' => $current_date_time,
				'date_created_gmt' => $current_date_time_gmt
			)
		);
	}


	/**
	 * Get order bump revenue from order
	 *
	 * @param $order
	 * @return int
	 *
	 * @since 3.1.7
	 */
	public function get_order_bump_revenue($order)
	{

		$ob_products = $order->get_meta('_wpfunnels_order_bump_products');

		if (empty($ob_products)) {
			return 0;
		}

		$total = 0;

		foreach ($order->get_items() as $item) {
			$product_id = $item->get_product_id();

			// If the product is a variation, get its variation ID
			if ($item->get_variation_id()) {
				$product_id = $item->get_variation_id();
			}

			if (in_array($product_id, $ob_products)) {
				$total += $item->get_total();
			}
		}
		return round($total, 2);
	}


	/**
	 * Update the order status if status changed.
	 *
	 * @param $order_id
	 * @param $old_status
	 * @param $new_status
	 * @param $order
	 *
	 * @since 3.2.0
	 */
	public function change_order_status($order_id, $old_status, $new_status, $order)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'wpfnl_stats';

		$offer_settings = Wpfnl_functions::get_offer_settings();
		if ($offer_settings['offer_orders'] == 'main-order') {
			$order_id = $order->get_id();
		} else {
			$parent_id = $order->get_meta('_wpfunnels_offer_parent_id');
			$order_id = !empty($parent_id) ? $parent_id : $order->get_id();
		}

		if ('completed' === $new_status) {
			$paid_date_time = current_time('mysql');
			$wpdb->update(
				$table,
				array(
					'status' => $new_status,
					'paid_date' => $paid_date_time,
				),
				array(
					'order_id' => $order_id
				)
			);
			DashboardController::delete_dashboard_cache();
		} else {
			$wpdb->update(
				$table,
				array(
					'status' => $new_status,
				),
				array(
					'order_id' => $order_id
				)
			);
		}

	}


	/**
	 * Delete stat if order is deleted
	 *
	 * @param $order_id
	 *
	 * @since 3.2.0
	 */
	public function delete_stat_data($order_id)
	{
		if (empty($order_id) || absint(0 === $order_id)) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wpfnl_stats';

		if (0 < did_action('delete_post')) {
			$get_post_type = get_post_type($order_id);
			if ('shop_order' !== $get_post_type) {
				return;
			}
		}
		$wpdb->delete($table, ['order_id' => $order_id], ['%d']);
	}


	/**
	 *  Update the stat table if fully refund is initiated
	 *
	 * @param $order_id
	 * @since 3.2.0
	 */
	public function update_stat_table_for_full_refund($order_id)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'wpfnl_stats';
		$wpdb->update(
			$table,
			array(
				'total_sales' => 0,
				'orderbump_sales' => 0,
				'upsell_sales' => 0,
				'downsell_sales' => 0,
			),
			array(
				'order_id' => $order_id
			)
		);
	}



	/**
	 * Update the stat table if partial refund is initiated
	 *
	 * @param $order_id
	 * @param $refund_id
	 *
	 * @since 3.2.0
	 */
	public function update_stat_table_for_partial_refund($order_id, $refund_id)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'wpfnl_stats';
		$order = wc_get_order($order_id);
		$refund = wc_get_order($refund_id);
		$refund_amount = 0;

		if (!$order instanceof \WC_Order) {
			return;
		}

		if (!$refund instanceof \WC_Order_Refund) {
			return;
		}

		if (!Wpfnl_functions::check_if_funnel_order($order)) {
			return;
		}

		$types = array(
			'line_item',
			'tax',
			'shipping',
			'fee',
			'coupon',
		);
		if (0 < count($refund->get_items($types))) {
			foreach ($refund->get_items($types) as $refund_item) {
				$item_id = $refund_item->get_meta('_refunded_item_id', true);
				if (empty($item_id)) {
					continue;
				}
				$item = $order->get_item($item_id);
				if (!$item instanceof \WC_Order_Item) {
					continue;
				}

				$is_orderbump = $item->get_meta('_wpfunnels_order_bump');
				$is_upsell = $item->get_meta('_wpfunnels_upsell');
				$is_downsell = $item->get_meta('_wpfunnels_downsell');

				if (('' === $is_orderbump) && ('' === $is_upsell) && ('' === $is_downsell)) {
					$refund_amount += abs($refund_item->get_total());
				}
			}

			if ($refund_amount > 0) {
				$total = $wpdb->get_var("SELECT total_sales FROM " . $table . " WHERE order_id = " . $order_id);
				$refund_amount = ($total <= $refund_amount) ? 0 : $total - $refund_amount;
				$wpdb->update(
					$table,
					array(
						'total_sales' => $refund_amount
					),
					array(
						'order_id' => $order_id
					)
				);
			}
		}
	}


	/**
	 * Track funnel checkout page visits.
	 * Runs at template_redirect priority 1, before any WC redirect hooks.
	 */
	public function track_funnel_checkout_visit()
	{
		if ( is_admin() ) {
			return;
		}

		if ( Wpfnl_functions::check_if_this_is_step_type( 'checkout' ) ) {
			global $post;
			$funnel_id = Wpfnl_functions::get_funnel_id_from_step( $post->ID );
			CheckoutTracker::track_visit( 'funnel', $funnel_id );
			CheckoutTracker::track_visit( 'store', 0 );
		}
	}

	/**
	 * Track native WooCommerce checkout page visits.
	 * wp_footer fires only when a full page renders — WC's empty-cart redirect
	 * calls exit() before this point, so no cart check is needed here.
	 * Works for both classic shortcode and block-based checkout.
	 */
	public function track_native_checkout_visit()
	{
		if ( ! function_exists( 'is_checkout' ) ) {
			return;
		}
		if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_wc_endpoint_url( 'order-pay' ) ) {
			CheckoutTracker::track_visit( 'store', 0 );
		}
	}

}
