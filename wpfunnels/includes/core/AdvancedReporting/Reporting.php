<?php
/**
 * Class Reporting
 *
 * This class handles the reporting functionalities for WP Funnels.
 */

namespace WPFunnels\Report;

use WPFunnels\Wpfnl_functions;

class Reporting {

	/**
	 * The single instance of the class.
	 *
	 * @var Reporting|null
	 * @since 2.4.1
	 */
	private static $instance = null;


	/**
	 * Retrieves the single instance of the Reporting class.
	 *
	 * This method ensures that only one instance of the class is created (singleton pattern).
	 * If the instance doesn't exist, it initializes the class and calls the `init` method.
	 *
	 * @return Reporting The single instance of the Reporting class.
	 * @since 2.4.1
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Reporting();
			self::$instance->init();
		}
		return self::$instance;
	}


	/**
	 * Initializes the Reporting class by registering actions and filters.
	 *
	 * @return void
	 * @since 2.4.1
	 */
	public function init() {
		add_action( 'wpfunnels/offer_accepted', array( $this, 'update_offer_data' ), 10, 2 );
        add_filter( 'wpfunnels/funnels-overview-data', array($this, 'get_upsell_data'), 10, 3 );
        add_filter( 'wpfunnels/stat-interval-data', array($this, 'get_upsell_stat_data'), 10, 3 );
        add_filter( 'wpfunnels/top-performing-funnels-data', array($this, 'get_conversion_data'), 10 );
	}


    /**
     * Update reporting data from order
     *
     * @param $order
     * @param $offer_product
     *
     * @since 2.4.1
     */
	public function update_offer_data( \WC_Order $order, $offer_product ) {
	    if ( !$order ) {
	        return;
        }

	    global $wpdb;
	    $table          = $wpdb->prefix . 'wpfnl_stats';
        $step_id        = $offer_product['step_id'];
        $step_type      = get_post_meta($step_id, '_step_type', true);
        $column_key     = 'upsell' === $step_type ? 'upsell_sales' : 'downsell_sales';
		$offer_settings = Wpfnl_functions::get_offer_settings();

		if ( $offer_settings['offer_orders'] == 'main-order' ) {
			$order_id       = $order->get_id();
            $status = $order->get_status();
		} else {
			$order_id 		= $order->get_meta('_wpfunnels_offer_parent_id');
            $status = $order->get_status();
            
		}
        $paid_date_time	= current_time( 'mysql' );

		$offer_total	= $this->get_offer_sales( $order, $offer_product );
		$total 			= $wpdb->get_var( $wpdb->prepare("SELECT total_sales FROM {$table} WHERE order_id = %d", $order_id ));
        
        $wpdb->update(
            $table,
            array(
                $column_key     => $offer_total,
                'total_sales'   => round(floatval( $total ) + $offer_total, 2 ),
                'status'        => $order->get_status(),
                'paid_date' => $paid_date_time,
            ),
            array(
                'order_id'  => $order_id,
            )
        );
	}


    /**
     * Get offer total sales data
     *
     * @param $order
     * @param $offer_product
     * @return int|string|null
     *
     * @since 2.4.1
     */
	public function get_offer_sales ( $order, $offer_product ) {
	    global $wpdb;
        $table              = $wpdb->prefix . 'wpfnl_stats';
        $step_id            = $offer_product['step_id'];
        $step_type          = get_post_meta($step_id, '_step_type', true);
        $offer_product_data = 'upsell' === $step_type ? get_post_meta($step_id, '_wpfnl_upsell_products', true) : get_post_meta($step_id, '_wpfnl_downsell_products', true);
        $column_key         = 'upsell' === $step_type ? 'upsell_sales' : 'downsell_sales';
        $offer_products     = array();
        $total              = 0;

        foreach ( $offer_product_data as $data ) {
            $offer_products[] = $data['id'];
        }

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();

            // If the product is a variation, get its variation ID
            if ( $item->get_variation_id() ) {
                $product_id = $item->get_variation_id();
            }

            if ( in_array( $product_id, $offer_products ) ) {
                $total += $item->get_total();
            }
        }

        $_total = $wpdb->get_var( $wpdb->prepare("SELECT %s FROM {$table} WHERE order_id = %s", $column_key, $order->get_id() ) );
        $total += floatval($_total);
        return round( $total, 2 );
    }


    /**
     * Attach offer overview data with report data
     *
     * @param $result
     * @param $start_date
     * @param $end_date
     * @return mixed
     * @since 2.4.1
     */
    public function get_upsell_data( $result, $start_date, $end_date ) {
        $total_upsell_revenue 	        = $this->get_total_offer_sales( $start_date, $end_date );
        $result['total_upsell_revenue'] = floatval(number_format(floatval($total_upsell_revenue), 2, '.', '' ));
        return $result;
    }


    /**
     * Get offer sale data
     *
     * @param $start_date
     * @param $end_date
     * @return mixed
     * @since 2.4.1
     */
    public function get_total_offer_sales( $start_date, $end_date ) {
        global $wpdb;
        $table 			= $wpdb->prefix. 'wpfnl_stats' ;
        $sql			= "SELECT SUM(upsell_sales + downsell_sales) as offer_sales FROM $table";
        $sql			= $this->include_where_clause($sql);
        
        $result 		= $wpdb->get_var( $wpdb->prepare( $sql, $start_date, $end_date ) );
        return $result;
    }


    /**
     * Get upsell stat datt
     *
     * @param $result
     * @param $start_date
     * @param $end_date
     * @return mixed
     *
     * @since 2.4.1
     */
    public function get_upsell_stat_data( $result, $start_date, $end_date ) {
        $total_upsell_revenue           = $this->get_total_offer_sales($start_date, $end_date);
        $result['total_upsell_revenue'] = floatval(number_format(floatval($total_upsell_revenue), 2, '.', '' ));
        return $result;
    }


    /**
     * Get conversion data
     *
     * @param $funnel_data
     * @param $funnel_id
     * @return mixed
     *
     * @since 2.4.1
     */
    public function get_conversion_data( $funnel_data ) {
        foreach ( $funnel_data as $key => $data ) {
            $funnel_id                  = $data['id'];
            $views                      = $this->get_views($funnel_id);
            $conversion                 = $this->get_conversion($funnel_id);
            $conversion_rate            = $this->get_conversion_rate( $views, $conversion );
            $funnel_data[$key]['views'] = $views;
            $funnel_data[$key]['conversion'] = $conversion;
            $funnel_data[$key]['conversion_rate'] = $conversion_rate;
        }
        return $funnel_data;
    }


    /**
     * Retrieves the views for a specific funnel.
     *
     * @param int $funnel_id The ID of the funnel.
     * @return array An array containing the views for the specified funnel.
     * 
     * @since 2.4.1
     */
    public function get_views( $funnel_id ) {
        global $wpdb;
        $table = $wpdb->prefix.'wpfnl_analytics';
        $sql   = "SELECT count(id) as total from $table WHERE funnel_id=%d AND visitor_type='new'";
        $count = $wpdb->get_var($wpdb->prepare($sql, $funnel_id));
        return $count;
    }


    /**
     * Get total number of conversion
     *
     * @param $funnel_id
     * @return mixed
     *
     * @since 2.4.1
     */
    public function get_conversion($funnel_id) {
        global $wpdb;
        $table = $wpdb->prefix.'wpfnl_stats';
        $sql   = "SELECT COUNT(DISTINCT o1.order_id) AS total_conversion
                    FROM $table o1
                    LEFT JOIN $table o2 ON o1.parent_id = o2.order_id AND o1.funnel_id = o2.funnel_id
                    WHERE o2.order_id IS NULL
                    AND o1.status='completed'
                    AND o1.funnel_id='%d'";
        $count = $wpdb->get_var($wpdb->prepare($sql, $funnel_id));

        if ( !$count ) {
            $optin_entries_table    = $wpdb->prefix.'wpfnl_optin_entries';
            $optin_conversion_sql   = "SELECT COUNT(DISTINCT email) AS total_distinct_emails FROM $optin_entries_table WHERE funnel_id = %d;";
            $count                  = $wpdb->get_var($wpdb->prepare($optin_conversion_sql, $funnel_id));
        }
        return $count;
    }


    /**
     * Get conversion rate
     *
     * @param $views
     * @param $conversion
     * @return float|int
     *
     * @since 2.4.1
     */
    public function get_conversion_rate( $views, $conversion ) {
        if ( $views > 0 ) {
            return round( $conversion / ( $views / 100 ), 2 );
        } else {
            return 0;
        }
    }


    /**
     * Include where clause
     *
     * @param $sql
     * @return string
     *
     * @since 2.4.1
     */
    public function include_where_clause( $sql ) {
        return $sql." WHERE paid_date >= %s AND paid_date <= %s AND status = 'completed' ";
    }
}