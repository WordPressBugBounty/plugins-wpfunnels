<?php

namespace WPFunnels\Admin\Migrations;

use Automattic\WooCommerce\Utilities\OrderUtil;


/**
 * Class OrderToStatTableMigration
 *
 * Handles the migration of orders to the wpfnl_stat table.
 *
 * @package WPFunnels\Admin\Migrations
 * @since 3.5.0
 */
class OrderToStatTableMigration extends AbstractMigrations {

	/**
	 * Migration key
	 *
	 * @var string
	 */
	public $key = 'order_table_migration';


	/**
	 * Check if the migration should be enqueued
	 *
	 * Determines if the migration process should be added to the queue.
	 *
	 * @return bool
	 *
	 * @since 3.5.0
	 */
	public function should_enqueue_migration_instance() {
		$is_migration_completed = get_option( "wpfunnels_".$this->key."_status", '' );
		$is_stat_table_created = get_option( 'wpfunnels_wpf_create_350_stat_table_update' );

		if ( 'completed' !== $is_stat_table_created ) {
			return false;
		}

		if ( 'completed' === $is_migration_completed ) {
			return false;
		}
		return true;
	}


	/**
	 * Get the next batch to process
	 *
	 * Retrieves the next batch of orders to be processed in the migration.
	 *
	 * @return mixed
	 *
	 * @since 3.5.0
	 */
	public function get_next_batch_to_process() {
		if ( !$this->is_on_queue() ) {
			$batch = get_option( "wpfunnels_".$this->key."_batch", '1' );
			return $batch;
		}
		return false;
	}


	/**
	 * Get SQL query
	 *
	 * Generates the SQL query to fetch orders based on the WooCommerce version.
	 *
	 * @return string
	 *
	 * @since 3.5.0
	 */
	private function get_sql() {
		global $wpdb;
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return "
				SELECT o.id as id
				FROM {$wpdb->prefix}wc_orders o
				INNER JOIN {$wpdb->prefix}wc_orders_meta om ON o.id = om.order_id
				WHERE om.meta_key = '_wpfunnels_order'
				  AND om.meta_value = 'yes'
				ORDER BY o.id ASC
				LIMIT %d OFFSET %d;
			";
		}

		return "SELECT p.ID as id
				FROM wp_posts p
				INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
				WHERE p.post_type = 'shop_order'
				  AND pm.meta_key = '_wpfunnels_order'
				  AND pm.meta_value = 'yes'
				ORDER BY p.ID ASC
				LIMIT %d OFFSET %d;";
	}



	/**
	 * Check if order exists in order table
	 *
	 * Verifies if a given order already exists in the stats table.
	 *
	 * @param array $order The order data.
	 * @return bool
	 *
	 * @since 3.5.0
	 */
	public function check_if_order_exists_on_order_table( $order ) {
		global $wpdb;
		$table 		= $wpdb->prefix.'wpfnl_stats';
		$order_id 	= $order['order_id'];
		$sql 		= "SELECT count(id) from $table WHERE order_id=%d;";
		$result 	= $wpdb->get_var($wpdb->prepare($sql, $order_id));
		return $result > 0;
	}



	/**
	 * Get orders
	 *
	 * Retrieves a batch of orders to be processed.
	 *
	 * @param int $limit The number of orders to retrieve.
	 * @param int $offset The offset for the SQL query.
	 * @return array
	 *
	 * @since 3.5.0
	 */
	public function get_orders( $limit, $offset ) {
		global $wpdb;
		$sql = $this->get_sql();
		$order_ids = $wpdb->get_results($wpdb->prepare( $sql, $limit, $offset ));
		$orders = array();
		foreach ( $order_ids as $_order ) {

			if ( !isset( $_order->id ) ) {
				continue;
			}

			$order = wc_get_order( $_order->id );

			if ( false === is_a( $order, 'WC_Order' ) ) {
				continue;
			}

			$total_sales 		= $order->get_total();
			$orderbump_sales 	= 0;
			$upsell_sales 		= 0;
			$downsell_sales 	= 0;
			$ob_products		= $order->get_meta('_wpfunnels_order_bump_products');

			foreach ( $order->get_items() as $item ) {

				$is_upsell 		= $item->get_meta('_wpfunnels_upsell');
				$is_downsell 	= $item->get_meta('_wpfunnels_downsell');

				$product_id = $item->get_product_id();

				// If the product is a variation, get its variation ID
				if ( $item->get_variation_id() ) {
					$product_id = $item->get_variation_id();
				}

				if ( $ob_products ) {
					if ( in_array( $product_id, $ob_products ) ) {
						$orderbump_sales += $item->get_total();
					}
				}

				if ( 'yes' === $is_upsell ) {
					$upsell_sales += $item->get_total();
				}

				if ( 'yes' === $is_downsell ) {
					$downsell_sales += $item->get_total();
				}
			}

			$current_date_time 		= current_time( 'mysql' );
			$current_date_time_gmt 	= current_time( 'mysql', 1 );
			$paid_data				= $order->get_date_paid();

			$orders[] = array(
				'order_id'			=> $order->get_id(),
				'funnel_id'			=> $order->get_meta('_wpfunnels_funnel_id'),
				'parent_id'			=> $order->get_parent_id(),
				'customer_id'		=> $order->get_customer_id(),
				'total_sales'		=> $total_sales,
				'orderbump_sales'	=> $orderbump_sales,
				'upsell_sales'		=> $upsell_sales,
				'downsell_sales'	=> $downsell_sales,
				'status'			=> $order->get_status(),
				'paid_date'			=> $paid_data ? $paid_data->date('Y-m-d H:i:s') : $current_date_time,
				'date_created'		=> $current_date_time,
				'date_created_gmt'	=> $current_date_time_gmt,
			);
		}

		return $orders;
	}


	/**
	 * Process batch
	 *
	 * Processes a batch of orders and inserts them into the stats table.
	 *
	 * @since 3.5.0
	 */
	public function process_batch() {
		global $wpdb;
		$table = $wpdb->prefix.'wpfnl_stats';
		$batch 	= get_option( "wpfunnels_".$this->key."_batch" , '1' );
		$offset = ( $batch - 1 ) * 50;
		$this->set_migration_status('running');
		$orders = $this->get_orders( 50, $offset );

		if ( empty($orders) ) {
			$this->set_migration_status('completed');
			return;
		}
		foreach ( $orders as $order ) {
			$is_exists = $this->check_if_order_exists_on_order_table($order);
			if ($is_exists) {
				continue;
			}

			$wpdb->insert(
				$table,
				$order
			);
		}
		$this->set_batch($batch+1);
	}
}
