<?php
/**
 * ReportTools — MCP abilities for WPFunnels Free "Reports" domain (plan §5.1).
 *
 * Replaces the old `AnalyticsTools.php`, which mixed this Free-domain
 * `get-dashboard-overview` tool with two Pro-domain analytics tools
 * (`get-funnel-analytics`, `get-offer-performance` — now moved to
 * `wpfunnels-pro/includes/core/mcp/Tools/AnalyticsTools.php`).
 *
 * `get-dashboard-overview` is rewired to the real `ReportGenerator::get_overview()`
 * (the same class `DashboardController`'s `/report/overview` REST route calls)
 * instead of the previous hardcoded numbers, which also called a
 * `MCPHelper::countFunnels()` method that does not exist anywhere in the
 * codebase and would have fataled on first use.
 *
 * `list-funnel-orders` and `get-recent-logs` are the plan's other two Free
 * "Reports" tools, backed respectively by the real `{prefix}wpfnl_stats`
 * parent/child order table (see `includes/utils/class-wpfnl-activator.php`)
 * and the real `Wpfnl_Logger` log file registry.
 *
 * @package WPFunnels\MCP\Tools
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;

/**
 * Class ReportTools
 */
class ReportTools {

	/**
	 * Register tool definitions.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/get-dashboard-overview' => [
				'description' => 'Get overall store and funnel performance metrics (total revenue, orders, conversion rate, active funnels).',
				'category'    => 'reports',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'period' => [
							'type'        => 'string',
							'enum'        => [ '7days', '30days', 'this_month', 'all_time' ],
							'description' => 'Time period for metrics.',
						],
					],
				],
				'callback'    => [ __CLASS__, 'getDashboardOverview' ],
			],

			'wpfunnels/list-funnel-orders' => [
				'description' => 'List parent orders and their child offer orders (order bump / upsell / downsell) placed through funnels, with a per-order revenue breakdown.',
				'category'    => 'reports',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => array_merge(
						[
							'funnel_id' => [
								'type'        => 'integer',
								'description' => 'Filter by funnel ID (0 or omitted for all funnels).',
							],
						],
						MCPHelper::paginationSchema()
					),
				],
				'callback'    => [ __CLASS__, 'listFunnelOrders' ],
			],

			'wpfunnels/get-recent-logs' => [
				'description' => 'Read the most recent lines from the WPFunnels debug/error log files, for diagnosing a reported problem.',
				'category'    => 'reports',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'lines' => [
							'type'        => 'integer',
							'description' => 'Max lines to return per log file (max 200).',
							'minimum'     => 1,
							'maximum'     => 200,
							'default'     => 50,
						],
					],
				],
				'callback'    => [ __CLASS__, 'getRecentLogs' ],
			],

			'wpfunnels/get-report-stats' => [
				'description' => 'Get interval-bucketed dashboard chart data (orders, customers, checkout/order-bump/funnel/store sales, leads) between two dates — the same data backing the dashboard charts. Defaults to the last year, bucketed weekly, when no dates are given.',
				'category'    => 'reports',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'after'    => [
							'type'        => 'string',
							'description' => 'Start of the date range, e.g. "2026-01-01 00:00:00". Defaults to one year ago.',
						],
						'before'   => [
							'type'        => 'string',
							'description' => 'End of the date range, e.g. "2026-08-25 23:59:59". Defaults to now.',
						],
						'interval' => [
							'type'        => 'string',
							'enum'        => [ 'hour', 'day', 'week', 'month', 'quarter', 'year' ],
							'description' => 'Bucket size for the interval breakdown.',
							'default'     => 'week',
						],
					],
				],
				'callback'    => [ __CLASS__, 'getReportStats' ],
			],

			'wpfunnels/get-top-funnels' => [
				'description' => 'Get the top 3 performing funnels by completed-order revenue (orderbump + upsell + downsell + base sales) between two dates, each with order count, revenue, AOV, and AOV lift vs. the store average.',
				'category'    => 'reports',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'after'  => [
							'type'        => 'string',
							'description' => 'Start of the date range, e.g. "2026-01-01 00:00:00". Defaults to one year ago.',
						],
						'before' => [
							'type'        => 'string',
							'description' => 'End of the date range, e.g. "2026-08-25 23:59:59". Defaults to now.',
						],
					],
				],
				'callback'    => [ __CLASS__, 'getTopFunnels' ],
			],

			'wpfunnels/get-onboarding-status' => [
				'description' => 'Get the dashboard onboarding checklist state: whether it should still be shown, per-step completion (WooCommerce connected, funnel created, store checkout created, order bump added, upsell added, test order placed, funnel live), and overall progress.',
				'category'    => 'reports',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [],
				],
				'callback'    => [ __CLASS__, 'getOnboardingStatus' ],
			],

			'wpfunnels/dismiss-onboarding' => [
				'description' => 'Dismiss the dashboard onboarding checklist for 30 days. This only sets a UI flag (a transient) — it does not change or delete any funnel, order, or setting, and reverses itself automatically after 30 days.',
				'category'    => 'reports',
				'readonly'    => false,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [],
				],
				'callback'    => [ __CLASS__, 'dismissOnboarding' ],
			],

			'wpfunnels/sync-report-cache' => [
				'description' => 'Force the dashboard report cache to refresh by clearing its cached transients (overview/stats/top-funnels results cached for 24 hours). Only deletes derived cache entries, not real orders/funnels/settings — the next dashboard read simply recomputes and re-caches them, at the cost of a slightly slower next load.',
				'category'    => 'reports',
				'readonly'    => false,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [],
				],
				'callback'    => [ __CLASS__, 'syncReportCache' ],
			],
		];
	}

	/**
	 * Resolve a period slug into a [start_date, end_date] pair, matching the
	 * date-time format `ReportGenerator` expects.
	 *
	 * @param string $period Period slug.
	 * @return array{0:string,1:string}
	 */
	private static function periodRange( $period ) {
		$now = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		switch ( $period ) {
			case '7days':
				$start = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days', $now ) );
				break;
			case 'this_month':
				$start = gmdate( 'Y-m-01 00:00:00', $now );
				break;
			case 'all_time':
				$start = '2000-01-01 00:00:00';
				break;
			case '30days':
			default:
				$start = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days', $now ) );
				break;
		}

		return [ $start, gmdate( 'Y-m-d H:i:s', $now ) ];
	}

	/**
	 * Count funnels in a given post status.
	 *
	 * @param string $status Post status.
	 * @return int
	 */
	private static function countFunnels( $status = 'publish' ) {
		$post_type = defined( 'WPFNL_FUNNELS_POST_TYPE' ) ? WPFNL_FUNNELS_POST_TYPE : 'wpfunnels';
		$counts    = wp_count_posts( $post_type );

		return isset( $counts->{$status} ) ? (int) $counts->{$status} : 0;
	}

	/**
	 * Get dashboard overview, via the real ReportGenerator used by
	 * DashboardController's `/report/overview` REST route.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getDashboardOverview( $input = [] ) {
		$period = isset( $input['period'] ) ? sanitize_text_field( $input['period'] ) : '30days';
		if ( ! in_array( $period, [ '7days', '30days', 'this_month', 'all_time' ], true ) ) {
			$period = '30days';
		}

		if ( ! class_exists( '\WPFunnels\Report\ReportGenerator' ) ) {
			return MCPHelper::error( 'report_unavailable', 'The WPFunnels reporting module is not available on this install.' );
		}

		list( $start_date, $end_date ) = self::periodRange( $period );

		$overview = \WPFunnels\Report\ReportGenerator::get_overview( $start_date, $end_date );
		$data     = isset( $overview['data'] ) && is_array( $overview['data'] ) ? $overview['data'] : [];

		$data['period']         = $period;
		$data['active_funnels'] = self::countFunnels( 'publish' );

		return $data;
	}

	/**
	 * Get an instance of the real DashboardController, whose non-ReportGenerator
	 * logic (onboarding checklist, cache sync) lives only on the controller
	 * itself, with no separate service class to call statically.
	 *
	 * @return \WPFunnels\Rest\Controllers\DashboardController|null
	 */
	private static function dashboardController() {
		if ( ! class_exists( '\WPFunnels\Rest\Controllers\DashboardController' ) ) {
			return null;
		}

		return new \WPFunnels\Rest\Controllers\DashboardController();
	}

	/**
	 * Unwrap a WP_REST_Response into its plain data array, matching how
	 * getDashboardOverview() already treats ReportGenerator's array returns.
	 *
	 * @param mixed $response Controller return value.
	 * @return mixed
	 */
	private static function unwrapResponse( $response ) {
		return $response instanceof \WP_REST_Response ? $response->get_data() : $response;
	}

	/**
	 * Resolve the after/before date-range params shared by get_stats() and
	 * get_top_funnels(), matching DashboardController::default_after()/
	 * default_before() (1 year ago .. now, in the site's timezone) when omitted.
	 *
	 * @param array                                        $input      Tool input.
	 * @param \WPFunnels\Rest\Controllers\DashboardController $controller Controller instance.
	 * @return array{0:string,1:string}
	 */
	private static function statsDateRange( $input, $controller ) {
		$after  = isset( $input['after'] ) && '' !== $input['after']
			? sanitize_text_field( $input['after'] )
			: $controller->default_after()->format( 'Y-m-d H:i:s' );

		$before = isset( $input['before'] ) && '' !== $input['before']
			? sanitize_text_field( $input['before'] )
			: $controller->default_before()->format( 'Y-m-d H:i:s' );

		return [ $after, $before ];
	}

	/**
	 * Get interval-bucketed stats, via the real ReportGenerator::get_stats()
	 * used by DashboardController's `/report/stats` REST route (called
	 * directly, bypassing that route's 24-hour transient cache, for freshness —
	 * same rationale as getDashboardOverview()).
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getReportStats( $input = [] ) {
		if ( ! class_exists( '\WPFunnels\Report\ReportGenerator' ) ) {
			return MCPHelper::error( 'report_unavailable', 'The WPFunnels reporting module is not available on this install.' );
		}

		$controller = self::dashboardController();
		if ( ! $controller ) {
			return MCPHelper::error( 'report_unavailable', 'The WPFunnels dashboard controller is not available on this install.' );
		}

		list( $after, $before ) = self::statsDateRange( $input, $controller );

		$interval = isset( $input['interval'] ) ? sanitize_text_field( $input['interval'] ) : 'week';
		if ( ! in_array( $interval, [ 'hour', 'day', 'week', 'month', 'quarter', 'year' ], true ) ) {
			$interval = 'week';
		}

		return \WPFunnels\Report\ReportGenerator::get_stats( $after, $before, $interval );
	}

	/**
	 * Get the top 3 performing funnels, via the real
	 * ReportGenerator::get_top_funnels() used by DashboardController's
	 * `/report/top-funnels` REST route.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getTopFunnels( $input = [] ) {
		if ( ! class_exists( '\WPFunnels\Report\ReportGenerator' ) ) {
			return MCPHelper::error( 'report_unavailable', 'The WPFunnels reporting module is not available on this install.' );
		}

		$controller = self::dashboardController();
		if ( ! $controller ) {
			return MCPHelper::error( 'report_unavailable', 'The WPFunnels dashboard controller is not available on this install.' );
		}

		list( $after, $before ) = self::statsDateRange( $input, $controller );

		return [
			'status' => true,
			'data'   => \WPFunnels\Report\ReportGenerator::get_top_funnels( $after, $before ),
		];
	}

	/**
	 * Get onboarding checklist status, via the real
	 * DashboardController::get_onboarding_status().
	 *
	 * @param array $input Tool input (unused — no params).
	 * @return array|\WP_Error
	 */
	public static function getOnboardingStatus( $input = [] ) {
		$controller = self::dashboardController();
		if ( ! $controller ) {
			return MCPHelper::error( 'report_unavailable', 'The WPFunnels dashboard controller is not available on this install.' );
		}

		return self::unwrapResponse( $controller->get_onboarding_status() );
	}

	/**
	 * Dismiss the onboarding checklist for 30 days, via the real
	 * DashboardController::dismiss_onboarding().
	 *
	 * @param array $input Tool input (unused — no params).
	 * @return array|\WP_Error
	 */
	public static function dismissOnboarding( $input = [] ) {
		$controller = self::dashboardController();
		if ( ! $controller ) {
			return MCPHelper::error( 'report_unavailable', 'The WPFunnels dashboard controller is not available on this install.' );
		}

		return self::unwrapResponse( $controller->dismiss_onboarding( new \WP_REST_Request() ) );
	}

	/**
	 * Force the dashboard report cache to refresh, via the real
	 * DashboardController::sync_cache(). This only deletes the
	 * `wpfnl_dash_*` transients (see DashboardController::delete_dashboard_cache()) —
	 * cheap, idempotent, and self-healing, since the next overview/stats/
	 * top-funnels read simply recomputes and re-caches them. It touches no
	 * funnel, order, or settings data, so it is not marked destructive.
	 *
	 * @param array $input Tool input (unused — no params).
	 * @return array|\WP_Error
	 */
	public static function syncReportCache( $input = [] ) {
		$controller = self::dashboardController();
		if ( ! $controller ) {
			return MCPHelper::error( 'report_unavailable', 'The WPFunnels dashboard controller is not available on this install.' );
		}

		return self::unwrapResponse( $controller->sync_cache( new \WP_REST_Request() ) );
	}

	/**
	 * List parent/child funnel orders from the real `{prefix}wpfnl_stats`
	 * table (see includes/utils/class-wpfnl-activator.php for the schema).
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function listFunnelOrders( $input = [] ) {
		global $wpdb;

		$funnel_id = isset( $input['funnel_id'] ) ? (int) $input['funnel_id'] : 0;
		$page      = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;
		$per_page  = MCPHelper::perPage( isset( $input['per_page'] ) ? $input['per_page'] : MCPHelper::DEFAULT_PER_PAGE );

		$table = $wpdb->prefix . 'wpfnl_stats';
		$where = $funnel_id ? $wpdb->prepare( 'WHERE funnel_id = %d', $funnel_id ) : '';

		$total = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table} {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$offset = ( $page - 1 ) * $per_page;
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, order_id, funnel_id, parent_id, total_sales, orderbump_sales, upsell_sales, downsell_sales, status, date_created FROM {$table} {$where} ORDER BY date_created DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$items = array_map(
			static function ( $row ) {
				return [
					'order_id'        => (int) $row['order_id'],
					'funnel_id'       => (int) $row['funnel_id'],
					'is_child_order'  => ( (int) $row['parent_id'] > 0 ),
					'parent_order_id' => (int) $row['parent_id'],
					'total_sales'     => (float) $row['total_sales'],
					'orderbump_sales' => (float) $row['orderbump_sales'],
					'upsell_sales'    => (float) $row['upsell_sales'],
					'downsell_sales'  => (float) $row['downsell_sales'],
					'status'          => $row['status'],
					'date_created'    => $row['date_created'],
				];
			},
			is_array( $rows ) ? $rows : []
		);

		return MCPHelper::paginate( $items, $total, $page, $per_page );
	}

	/**
	 * Read recent lines from the most recently modified WPFunnels log files.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getRecentLogs( $input = [] ) {
		if ( ! class_exists( '\Wpfnl_Logger' ) ) {
			return MCPHelper::error( 'logger_unavailable', 'The WPFunnels logger is not available on this install.' );
		}

		// Instantiate so the log directory constants are defined even if
		// nothing has logged yet this request.
		\Wpfnl_Logger::getInstance();

		$limit = isset( $input['lines'] ) ? max( 1, min( 200, (int) $input['lines'] ) ) : 50;

		$files = \Wpfnl_Logger::get_log_files();
		if ( empty( $files ) || ! defined( 'WPFNL_LOG_FILE_DIR' ) ) {
			return [ 'files' => [] ];
		}

		$mtimes = [];
		foreach ( $files as $file ) {
			$path = trailingslashit( WPFNL_LOG_FILE_DIR ) . $file;
			if ( is_readable( $path ) ) {
				$mtimes[ $path ] = filemtime( $path );
			}
		}
		arsort( $mtimes );
		$paths = array_slice( array_keys( $mtimes ), 0, 3 );

		$result = [];
		foreach ( $paths as $path ) {
			$content  = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$lines    = $content ? array_slice( preg_split( '/\r\n|\r|\n/', trim( $content ) ), -$limit ) : [];
			$result[] = [
				'file'  => basename( $path ),
				'lines' => $lines,
			];
		}

		return [ 'files' => $result ];
	}
}
