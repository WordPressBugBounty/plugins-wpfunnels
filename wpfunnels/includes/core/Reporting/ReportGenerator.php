<?php

namespace WPFunnels\Report;

use WPFunnels\Wpfnl_functions;

class ReportGenerator {


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
		$total_orders 			= self::get_total_orders( $start_date, $end_date );
		$total_customers 		= self::get_total_customers( $start_date, $end_date );
		$total_sales 			= self::get_total_sales( $start_date, $end_date );
		$total_ob_revenue 		= self::get_total_ob_sales( $start_date, $end_date );

		$result = array(
			'total_orders'			=> (int) $total_orders,
			'total_customers'		=> (int) $total_customers,
			'total_sales'			=> floatval(number_format(  floatval($total_sales) , 2, '.', '' )),
			'total_ob_revenue'		=> floatval(number_format( floatval($total_ob_revenue), 2, '.', '' )),
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
			$response['sales']['interval'][]	= apply_filters( 'wpfunnels/stat-interval-data',  array(
				'total_orders'			=> (int) $total_orders,
				'total_customers'		=> (int) $total_customers,
				'total_sales'			=> floatval( $total_checkout_sales ),
				'total_ob_revenue'		=> floatval($total_ob_revenue),
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
	public static function get_top_funnels() {
		global $wpdb;
		$table	= $wpdb->prefix. 'wpfnl_stats';
		$sql = "SELECT funnel_id, SUM(
					CASE
						WHEN status = 'completed' THEN ( total_sales )
						ELSE 0
					END
				) AS total_revenue
					FROM $table
					GROUP BY funnel_id
					ORDER BY total_revenue DESC
				LIMIT 3";
		$top_funnels = $wpdb->get_results($sql);
		$funnel_data = array();
		foreach ( $top_funnels as $top_funnel ) {
			$funnel_id 		= $top_funnel->funnel_id;
			if ( 'publish' !== get_post_status($funnel_id) ) {
				continue;
			}
			$revenue 		= $top_funnel->total_revenue;
			$funnel_data[] 	= array(
				'id'				=> $funnel_id,
				'link'				=> admin_url("/admin.php?page=edit_funnel&id={$funnel_id}&step_id=0"),
				'title'				=> get_the_title( $funnel_id ),
				'views'				=> 0,
				'conversion'		=> 0,
				'revenue' 			=> number_format(floatval($revenue), 2, '.', ''),
				'conversion_rate'	=> 0,
			);
		}
		return apply_filters( 'wpfunnels/top-performing-funnels-data', $funnel_data );
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

}
