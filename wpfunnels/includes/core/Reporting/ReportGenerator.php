<?php

namespace WPFunnels\Report;

use WPFunnels\Wpfnl_functions;

class ReportGenerator {


	/**
	 * Format a monetary value using WooCommerce's decimal settings.
	 *
	 * Uses wc_format_decimal() + wc_get_price_decimals() when WooCommerce
	 * is active, otherwise falls back to rounding to 2 decimal places.
	 *
	 * @param mixed $value Raw numeric value.
	 * @return float Formatted decimal value.
	 *
	 * @since 3.6.0
	 */
	private static function format_price( $value ) {
		if ( function_exists( 'wc_format_decimal' ) ) {
			return (float) wc_format_decimal( $value, wc_get_price_decimals() );
		}
		return round( (float) $value, 2 );
	}


	/**
	 * Get overview data of all funnels
	 *
	 * @param $start_date
	 * @param $end_date
	 * @return array
	 *
	 * @since 3.2.0
	 */
	public static function get_overview( $start_date, $end_date ) {
		$total_orders           = self::get_total_orders( $start_date, $end_date );
		$total_customers        = self::get_total_customers( $start_date, $end_date );
		$total_sales            = self::get_total_sales( $start_date, $end_date );
		$total_ob_revenue       = self::get_total_ob_sales( $start_date, $end_date );
		$wc_total               = self::get_wc_total_sales( $start_date, $end_date );
		$wc_total_orders        = self::get_wc_total_orders( $start_date, $end_date );
		// Non-funnel (native WooCommerce) revenue and order count
		$store_revenue          = max( 0, $wc_total - (float) $total_sales );
		$store_order_count      = max( 0, $wc_total_orders - (int) $total_orders );
		$avg_order_value        = (int) $total_orders > 0 ? ( (float) $total_sales / (int) $total_orders ) : 0;
		// Store baseline AOV = native WC revenue ÷ native WC orders (funnel orders excluded)
		$store_aov              = $store_order_count > 0 ? ( $store_revenue / $store_order_count ) : 0;

		$funnel_checkout_visits         = CheckoutTracker::get_funnel_checkout_visits( $start_date, $end_date );
		$store_checkout_visits          = CheckoutTracker::get_store_checkout_visits( $start_date, $end_date );
		// Cap at 100 — visits may be undercount if tracking started after orders were placed.
		$checkout_conversion_rate       = $funnel_checkout_visits > 0
			? min( 100, round( ( (int) $total_orders / $funnel_checkout_visits ) * 100, 1 ) )
			: 0;
		$store_checkout_conversion_rate = $store_checkout_visits > 0
			? min( 100, round( ( $store_order_count / $store_checkout_visits ) * 100, 1 ) )
			: 0;

		$result = array(
			'total_orders'                   => (int) $total_orders,
			'total_customers'                => (int) $total_customers,
			'total_sales'                    => self::format_price( $total_sales ),
			'total_ob_revenue'               => self::format_price( $total_ob_revenue ),
			'total_revenue'                  => self::format_price( $total_sales ),
			'avg_order_value'                => self::format_price( $avg_order_value ),
			'store_aov'                      => self::format_price( $store_aov ),
			'store_total_orders'             => (int) $store_order_count,
			'store_revenue'                  => self::format_price( $store_revenue ),
			'conversion_rate'                => $checkout_conversion_rate, // backward compat alias
			'checkout_conversion_rate'       => $checkout_conversion_rate,
			'store_checkout_conversion_rate' => $store_checkout_conversion_rate,
			'ob_acceptance_rate'             => self::get_ob_acceptance_rate( $start_date, $end_date ),
			'upsell_acceptance_rate'         => self::get_upsell_acceptance_rate( $start_date, $end_date ),
			'downsell_recovery_rate'         => self::get_downsell_recovery_rate( $start_date, $end_date ),
			'checkout_completion_rate'       => $checkout_conversion_rate,
		);

		$response['status'] = true;
		$response['data']   = apply_filters( 'wpfunnels/funnels-overview-data', $result, $start_date, $end_date );
		return $response;
	}


	public static function get_stats( $start_date, $end_date, $interval ) {
		$intervals	= self::get_intervals( $start_date, $end_date, $interval );
		$response		= array();
		foreach ( $intervals as $interval ) {
			$start_date 			= isset($interval['start_date']) ? $interval['start_date'] : (new \DateTime('monday last week'))->format('Y-m-d H:i:s');
			$end_date 				= isset($interval['end_date']) ? $interval['end_date'] : (new \DateTime('sunday last week'))->format('Y-m-d H:i:s');
			$total_orders			= self::get_total_orders( $start_date, $end_date );
			$total_customers 		= self::get_total_customers( $start_date, $end_date );
			$total_sales 			= self::get_total_sales( $start_date, $end_date );
			$total_checkout_sales 	= self::get_total_checkout_sales( $start_date, $end_date );
			$total_ob_revenue 		= self::get_total_ob_sales( $start_date, $end_date );
			$total_leads 		    = self::get_total_leads( $start_date, $end_date );
			$wc_interval_total      = self::get_wc_total_sales( $start_date, $end_date );
			$store_sales            = max( 0, $wc_interval_total - (float) $total_sales );
			$response['sales']['interval'][]	= apply_filters( 'wpfunnels/stat-interval-data',  array(
				'total_orders'			=> (int) $total_orders,
				'total_customers'		=> (int) $total_customers,
				'total_sales'			=> self::format_price( $total_checkout_sales ),
				'total_ob_revenue'		=> self::format_price( $total_ob_revenue ),
				'total_funnel_sales'	=> self::format_price( $total_sales ),
				'store_sales'			=> self::format_price( $store_sales ),
			), $start_date, $end_date );

			$response['lead']['interval'][]	= apply_filters( 'wpfunnels/stat-interval-data-leads',  array(
				'total'			        => (int) $total_leads,
			), $start_date, $end_date );
		}
		$response['status'] = true;
		return apply_filters( 'wpfunnels/funnels-stats-data', $response );
	}


	/**
	 * Get the top performing funnels.
	 *
	 * This method retrieves the top three performing funnels based on total revenue,
	 * which is calculated as the sum of total sales, upsell sales, downsell sales, and orderbump sales.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return array An array of top performing funnels, where each funnel is represented as an associative array
	 *               containing 'id', 'link', 'title', 'views', 'conversion', 'revenue', and 'conversion_rate'.
	 * @throws Exception If there is an issue with the database query.
	 * @since 3.5.0
	 */
	public static function get_top_funnels( $start_date = null, $end_date = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wpfnl_stats';

		if ( ! $start_date ) {
			$start_date = '2000-01-01 00:00:00';
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d H:i:s' );
		}

		$sql = $wpdb->prepare(
			"SELECT funnel_id,
				SUM(CASE WHEN status = 'completed' THEN total_sales ELSE 0 END) AS total_revenue,
				COUNT(CASE WHEN status = 'completed' THEN 1 END) AS order_count
			 FROM {$table}
			 WHERE paid_date >= %s AND paid_date <= %s
			 GROUP BY funnel_id
			 ORDER BY total_revenue DESC
			 LIMIT 3",
			$start_date,
			$end_date
		);

		$top_funnels = $wpdb->get_results( $sql );

		// Store AOV baseline for lift calculation
		$wc_total  = self::get_wc_total_sales( $start_date, $end_date );
		$wc_orders = self::get_wc_total_orders( $start_date, $end_date );
		$store_aov = $wc_orders > 0 ? $wc_total / $wc_orders : 0;

		$funnel_data = array();
		foreach ( $top_funnels as $top_funnel ) {
			$funnel_id = $top_funnel->funnel_id;
			if ( 'publish' !== get_post_status( $funnel_id ) ) {
				continue;
			}
			$revenue  = (float) $top_funnel->total_revenue;
			$orders   = (int) $top_funnel->order_count;
			$aov      = $orders > 0 ? $revenue / $orders : 0;
			$aov_lift = $store_aov > 0 ? round( ( ( $aov - $store_aov ) / $store_aov ) * 100, 1 ) : 0;

			$funnel_data[] = array(
				'id'              => $funnel_id,
				'link'            => admin_url( "/admin.php?page=edit_funnel&id={$funnel_id}&step_id=0" ),
				'title'           => get_the_title( $funnel_id ),
				'views'           => 0,
				'orders'          => $orders,
				'conversion'      => 0,
				'revenue'         => self::format_price( $revenue ),
				'aov'             => self::format_price( $aov ),
				'aov_lift'        => $aov_lift,
				'conversion_rate' => 0,
			);
		}
		return apply_filters( 'wpfunnels/top-performing-funnels-data', $funnel_data, $start_date, $end_date );
	}


	/**
	 * Get total number of orders
	 *
	 * @param $start_date
	 * @param $end_date
	 * @return array|object|null
	 *
	 * @since 3.2.0
	 */
	public static function get_total_orders( $start_date, $end_date ) {
		global $wpdb;
		$table 			= $wpdb->prefix. 'wpfnl_stats';
		$sql			= "SELECT count(id) FROM $table";
		$sql			= self::include_where_clause($sql);
		$result 		= $wpdb->get_var($wpdb->prepare($sql, $start_date, $end_date ));
		return $result;
	}


	/**
	 * Get total number of customers
	 *
	 * @param $start_date
	 * @param $end_date
	 * @return string|null
	 *
	 * @since 3.2.0
	 */
	public static function get_total_customers( $start_date, $end_date ) {
		global $wpdb;
		$table 			= $wpdb->prefix. 'wpfnl_stats';
		$sql			= "SELECT count(DISTINCT customer_id) as count FROM $table" ;
		$sql			= self::include_where_clause($sql);
		$result 		= $wpdb->get_var( $wpdb->prepare($sql, $start_date, $end_date ) );
		return $result;
	}


	/**
	 * Get total sales
	 *
	 * @param $start_date
	 * @param $end_date
	 * @return mixed
	 *
	 * @since 3.2.0
	 */
	public static function get_total_sales( $start_date, $end_date ) {
		global $wpdb;
		$table 			= $wpdb->prefix. 'wpfnl_stats' ;
		$sql			= "SELECT SUM(total_sales) as total_sales FROM $table";
		$sql			= self::include_where_clause($sql);
		$result 		= $wpdb->get_var( $wpdb->prepare( $sql, $start_date, $end_date ) );
		return $result;
	}


	public static function get_total_checkout_sales( $start_date, $end_date ){
		global $wpdb;
		$table 			= $wpdb->prefix. 'wpfnl_stats' ;
		$sql			= "
							SELECT
								SUM(total_sales - ( orderbump_sales + upsell_sales + downsell_sales )) AS checkout_sales
							FROM
								{$table}
							";
		$sql			= self::include_where_clause($sql);
		$result 		= $wpdb->get_var( $wpdb->prepare( $sql, $start_date, $end_date ) );

		return $result;
	}


	/**
	 * Get total order bump sales
	 *
	 * @param $start_date
	 * @param $end_date
	 * @return mixed
	 *
	 * @since 3.2.0
	 */
	public static function get_total_ob_sales( $start_date, $end_date ) {
		global $wpdb;
		$table 			= $wpdb->prefix. 'wpfnl_stats' ;
		$sql			= "SELECT SUM(orderbump_sales) as orderbump_sales FROM $table";
		$sql			= self::include_where_clause($sql);
		$result 		= $wpdb->get_var( $wpdb->prepare( $sql, $start_date, $end_date ) );
		return $result;
	}


	/**
	 * Get total number of leads
	 *
	 * @param $start_date
	 * @param $end_date
	 * @return string|null
	 *
	 * @since 3.2.0
	 */
	public static function get_total_leads( $start_date, $end_date ) {
		global $wpdb;
		$table 			= $wpdb->prefix. 'wpfnl_optin_entries' ;
		$sql			= "SELECT COUNT(id) as total FROM $table";
		$sql			= self::include_where_clause_leads($sql);
		$result 		= $wpdb->get_var( $wpdb->prepare( $sql, $start_date, $end_date ) );
		return $result;
	}


	/**
	 * Include where clause
	 *
	 * @param $sql
	 * @return string
	 *
	 * @since 3.2.0
	 */
	public static function include_where_clause( $sql ) {
		return $sql." WHERE paid_date >= %s AND paid_date <= %s AND status = 'completed' ";
	}


	/**
	 * Include where clause for leads fetching query
	 *
	 * @param $sql
	 * @return string
	 *
	 * @since 3.2.0
	 */
	public static function include_where_clause_leads( $sql ) {
		return $sql." WHERE date_created >= %s AND date_created <= %s ";
	}


	/**
	 * Get interval data
	 *
	 * This method generates an array of date intervals between the given start and end dates,
	 * based on the specified interval type (day, month, quarter, hour).
	 *
	 * @param string $start_date The start date in 'Y-m-d H:i:s' format.
	 * @param string $end_date The end date in 'Y-m-d H:i:s' format.
	 * @param string $interval_type The type of interval ('day', 'month', 'quarter', 'hour').
	 * @return array An array of intervals, where each interval is an associative array
	 *               with 'start_date' and 'end_date' keys in 'Y-m-d H:i:s' format.
	 * @throws \Exception If an invalid interval type is provided.
	 *
	 * @since 3.2.0
	 */
	public static function get_intervals( $start_date, $end_date, $interval_type ) {
		$intervals = array();

		// Convert start and end dates to DateTime objects
		$start 	= new \DateTime($start_date);
		$end 	= new \DateTime($end_date);

		// Create interval based on interval type
		switch ($interval_type) {
			case 'day':
				$interval = new \DateInterval('P1D');
				break;
			case 'month':
				$interval = new \DateInterval('P1M');
				$end->modify('last day of this month')->setTime(23, 59, 59);
				break;
			case 'quarter':
				$interval = new \DateInterval('P3M');
				$end->modify('last day of this quarter')->setTime(23, 59, 59);
				break;
			case 'hour':
				$interval = new \DateInterval('PT1H');
				break;
			default:
				throw new \Exception("Invalid interval type: $interval_type");
		}

		// Iterate over the date range
		$period = new \DatePeriod($start, $interval, $end);
		foreach ($period as $dt) {
			$interval_end = clone $dt;
			if ($interval_type === 'day') {
				$interval_end->setTime(23, 59, 59);
			} elseif ($interval_type === 'month') {
				$interval_end->modify('last day of this month')->setTime(23, 59, 59);
			} elseif ($interval_type === 'hour') {
				$interval_end->setTime($interval_end->format('H'), 59, 59);
			}
			$intervals[] = array(
				'start_date' => $dt->format('Y-m-d H:i:s'),
				'end_date' => $interval_end->format('Y-m-d H:i:s')
			);
		}

		// Handle the last interval for month type
		if ($interval_type === 'month' && $end > $interval_end) {
			$last_month_end = new \DateTime($end->format('Y-m-t').' 23:59:59');
			$intervals[] = array(
				'start_date' => $interval_end->format('Y-m-d H:i:s'),
				'end_date' => $last_month_end->format('Y-m-d H:i:s')
			);
		}

		return $intervals;
	}


	/**
	 * Get WooCommerce total sales for all orders (not just funnel orders).
	 * Supports both HPOS and legacy post-based order storage.
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return float
	 *
	 * @since 3.9.6
	 */
	public static function get_wc_total_sales( $start_date, $end_date ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 0;
		}
		global $wpdb;

		$hpos_enabled = get_option( 'woocommerce_custom_orders_table_enabled' ) === 'yes';

		if ( $hpos_enabled ) {
			$table       = $wpdb->prefix . 'wc_orders';
			$start_utc   = get_gmt_from_date( $start_date );
			$end_utc     = get_gmt_from_date( $end_date );
			$total = $wpdb->get_var( $wpdb->prepare(
				"SELECT COALESCE(SUM(total_amount), 0) FROM {$table}
				 WHERE type = 'shop_order'
				 AND status IN ('wc-completed', 'wc-processing')
				 AND date_created_gmt >= %s AND date_created_gmt <= %s",
				$start_utc,
				$end_utc
			) );
		} else {
			$total = $wpdb->get_var( $wpdb->prepare(
				"SELECT COALESCE(SUM(pm.meta_value), 0)
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
				 WHERE p.post_type = 'shop_order'
				 AND p.post_status IN ('wc-completed', 'wc-processing')
				 AND p.post_date >= %s AND p.post_date <= %s",
				$start_date,
				$end_date
			) );
		}

		return self::format_price( (float) $total );
	}


	/**
	 * Get the count of all WooCommerce orders (not just funnel orders) for a date range.
	 * Supports both HPOS and legacy post-based order storage.
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return int
	 *
	 * @since 3.9.6
	 */
	public static function get_wc_total_orders( $start_date, $end_date ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 0;
		}
		global $wpdb;

		$hpos_enabled = get_option( 'woocommerce_custom_orders_table_enabled' ) === 'yes';

		if ( $hpos_enabled ) {
			$table = $wpdb->prefix . 'wc_orders';
			return (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(id) FROM {$table}
				 WHERE type = 'shop_order'
				 AND status IN ('wc-completed', 'wc-processing')
				 AND date_created_gmt >= %s AND date_created_gmt <= %s",
				$start_date,
				$end_date
			) );
		}

		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(ID) FROM {$wpdb->posts}
			 WHERE post_type = 'shop_order'
			 AND post_status IN ('wc-completed', 'wc-processing')
			 AND post_date >= %s AND post_date <= %s",
			$start_date,
			$end_date
		) );
	}


	/**
	 * Get order bump acceptance rate.
	 * = orders with orderbump_sales > 0  /  total completed orders × 100
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return float
	 *
	 * @since 3.9.6
	 */
	public static function get_ob_acceptance_rate( $start_date, $end_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wpfnl_stats';

		$total = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM {$table}
			 WHERE paid_date >= %s AND paid_date <= %s AND status = 'completed'",
			$start_date, $end_date
		) );

		if ( ! $total ) {
			return 0;
		}

		$with_ob = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM {$table}
			 WHERE paid_date >= %s AND paid_date <= %s AND status = 'completed' AND orderbump_sales > 0",
			$start_date, $end_date
		) );

		return round( ( $with_ob / $total ) * 100, 1 );
	}


	/**
	 * Get upsell acceptance rate.
	 * = orders with upsell_sales > 0  /  total completed orders × 100
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return float
	 *
	 * @since 3.9.6
	 */
	public static function get_upsell_acceptance_rate( $start_date, $end_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wpfnl_stats';

		$total = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM {$table}
			 WHERE paid_date >= %s AND paid_date <= %s AND status = 'completed'",
			$start_date, $end_date
		) );

		if ( ! $total ) {
			return 0;
		}

		$with_upsell = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM {$table}
			 WHERE paid_date >= %s AND paid_date <= %s AND status = 'completed' AND upsell_sales > 0",
			$start_date, $end_date
		) );

		return round( ( $with_upsell / $total ) * 100, 1 );
	}


	/**
	 * Get downsell recovery rate.
	 * = orders with downsell_sales > 0  /  total completed orders × 100
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return float
	 *
	 * @since 3.9.6
	 */
	public static function get_downsell_recovery_rate( $start_date, $end_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wpfnl_stats';

		$total = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM {$table}
			 WHERE paid_date >= %s AND paid_date <= %s AND status = 'completed'",
			$start_date, $end_date
		) );

		if ( ! $total ) {
			return 0;
		}

		$with_downsell = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM {$table}
			 WHERE paid_date >= %s AND paid_date <= %s AND status = 'completed' AND downsell_sales > 0",
			$start_date, $end_date
		) );

		return round( ( $with_downsell / $total ) * 100, 1 );
	}

}
