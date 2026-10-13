<?php
/**
 * Funnel listing controller.
 *
 * Serves the React funnel list (`?page=wpfunnels#/funnels`), which replaced the
 * server-rendered `?page=wp_funnels` screen and its jQuery/admin-ajax actions.
 * Everything that screen did through `wp_ajax_helper` — duplicate, trash,
 * restore, delete, publish/draft — is a REST route here, so the React app never
 * touches admin-ajax.
 *
 * @package WPFunnels\Rest\Controllers
 */

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Server;
use WPFunnels\Export\Wpfnl_Export;
use WPFunnels\Modules\Admin\Funnel\Module as FunnelModule;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

class FunnelListController extends Wpfnl_REST_Controller
{

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wpfunnels/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'funnels';

	/**
	 * Funnels a free install may keep before the list stops offering to make
	 * more. Same number the legacy view hard-coded.
	 */
	const FREE_FUNNEL_LIMIT = 3;

	/**
	 * Makes sure the current user may read the funnel list.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 3.13.4
	 */
	public function get_items_permissions_check($request)
	{
		if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('funnels')) {
			return new WP_Error(
				'wpfunnels_rest_cannot_view',
				__('Sorry, you cannot list resources.', 'wpfnl'),
				array('status' => rest_authorization_required_code())
			);
		}

		return true;
	}


	/**
	 * Makes sure the current user may act on a funnel.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 3.13.4
	 */
	public function update_items_permissions_check($request)
	{
		if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('funnels', 'edit')) {
			return new WP_Error(
				'wpfunnels_rest_cannot_edit',
				__('Sorry, you cannot edit this resource.', 'wpfnl'),
				array('status' => rest_authorization_required_code())
			);
		}

		return true;
	}


	/**
	 * Register rest routes.
	 *
	 * @return void
	 * @since 3.13.4
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_funnels'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => array(
						'page'     => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page' => array(
							'type'              => 'integer',
							'default'           => 25,
							'sanitize_callback' => 'absint',
						),
						'search'   => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						/*
						 * The one status the listing filters on. Trash is a
						 * value here rather than a separate listing: it is a
						 * post status like the others, and `all` means all of
						 * them, trash included — a count labelled "All" that
						 * quietly left a status out is a count nobody can
						 * reconcile.
						 *
						 * Applied in the query rather than in the browser so
						 * `total` counts what the filter actually matched;
						 * filtering a single page of results client-side left
						 * the pager describing a set nobody could see.
						 */
						'funnel_status' => array(
							'type'    => 'string',
							'default' => 'all',
							'enum'    => array('all', 'publish', 'draft', 'trash'),
						),
						/*
						 * Retired in 3.13.4, when the Live and Trash tabs became
						 * one filter. Still honoured so a bookmarked request (or
						 * an older bundle served from a cache) keeps working.
						 */
						'status'   => array(
							'type'    => 'string',
							'default' => '',
							'enum'    => array('', 'live', 'trash'),
						),
						/*
						 * `conversions` and `sales` are not post columns — they
						 * come from the wpfnl_stats aggregate — so those two are
						 * ordered outside WP_Query. See sort_by_analytics().
						 */
						'orderby'  => array(
							'type'    => 'string',
							'default' => 'id',
							'enum'    => array('id', 'modified', 'title', 'date', 'conversions', 'sales'),
						),
						'order'    => array(
							'type'    => 'string',
							'default' => 'desc',
							'enum'    => array('asc', 'desc'),
						),
					),
				),
				/*
				 * Creating a funnel from nothing — "Start from scratch" on the
				 * template library screen. The layout is the skeleton of steps
				 * to lay out on the canvas; without one the funnel is created
				 * empty.
				 */
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'create_funnel'),
					'permission_callback' => array($this, 'update_items_permissions_check'),
					'args'                => array(
						'funnelName' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'type'       => array(
							'type'              => 'string',
							'default'           => 'wc',
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<funnel_id>\d+)/duplicate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'duplicate_funnel'),
					'permission_callback' => array($this, 'update_items_permissions_check'),
					'args'                => $this->get_funnel_id_arg(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<funnel_id>\d+)/status',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'update_status'),
					'permission_callback' => array($this, 'update_items_permissions_check'),
					'args'                => array_merge(
						$this->get_funnel_id_arg(),
						array(
							'status' => array(
								'required' => true,
								'type'     => 'string',
								'enum'     => array('publish', 'draft'),
							),
						)
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<funnel_id>\d+)/trash',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'trash_funnel'),
					'permission_callback' => array($this, 'update_items_permissions_check'),
					'args'                => $this->get_funnel_id_arg(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<funnel_id>\d+)/restore',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'restore_funnel'),
					'permission_callback' => array($this, 'update_items_permissions_check'),
					'args'                => $this->get_funnel_id_arg(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<funnel_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_funnel'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => $this->get_funnel_id_arg(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array($this, 'delete_funnel'),
					'permission_callback' => array($this, 'update_items_permissions_check'),
					'args'                => $this->get_funnel_id_arg(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<funnel_id>\d+)/export',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array($this, 'export_funnel'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => $this->get_funnel_id_arg(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'bulk_action'),
					'permission_callback' => array($this, 'update_items_permissions_check'),
					'args'                => array(
						'action' => array(
							'required' => true,
							'type'     => 'string',
							'enum'     => array('trash', 'restore', 'delete', 'draft', 'publish'),
						),
						'ids'    => array(
							'required' => true,
							'type'     => 'array',
							'items'    => array('type' => 'integer'),
						),
					),
				),
			)
		);
	}


	/**
	 * Shared `funnel_id` route argument.
	 *
	 * @return array
	 * @since 3.13.4
	 */
	private function get_funnel_id_arg()
	{
		return array(
			'funnel_id' => array(
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);
	}


	/**
	 * One page of funnels, plus everything the screen's chrome needs.
	 *
	 * The counts and the free-plan limit travel with the listing on purpose:
	 * the tab headers and the "New Funnel" button both change with them, and a
	 * second round trip would let them disagree with the rows on screen.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 * @since 3.13.4
	 */
	/**
	 * Create an empty funnel, optionally laid out from a layout skeleton.
	 *
	 * The REST twin of the `create-funnel` admin-ajax handler, which the legacy
	 * screens still post to. Both run the same module method, so the funnel is
	 * built one way whichever door it came through.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response The module's own response, carrying `redirectUrl`.
	 * @since  3.13.5
	 */
	public function create_funnel($request)
	{
		$module = Wpfnl::$instance->module_manager->get_admin_modules('create-funnel');

		if (!$module) {
			return new WP_Error(
				'wpfunnels_rest_cannot_create',
				__('Funnels cannot be created right now.', 'wpfnl'),
				array('status' => 500)
			);
		}

		$payload = array(
			'funnelName'           => $request->get_param('funnelName'),
			'type'                 => $request->get_param('type'),
			// The layout arrives as the nested skeleton the module reads to lay
			// the canvas out, so it is passed through rather than sanitised
			// field by field; an absent or empty one creates a bare funnel.
			'selectedFunnelLayout' => $request->get_param('selectedFunnelLayout'),
			/*
			 * A store checkout is created through this same route, and only
			 * this flag tells the module so: it stamps the funnel type and lays
			 * out the checkout/thank-you pair instead of a blank canvas. The
			 * legacy screens were recognised by their `?page=store_checkout`
			 * query string, which the React admin no longer has.
			 */
			'is_store_checkout'    => filter_var($request->get_param('is_store_checkout'), FILTER_VALIDATE_BOOLEAN),
		);

		return rest_ensure_response($module->create_funnel($payload));
	}


	public function get_funnels($request)
	{
		$page     = max(1, (int) $request->get_param('page'));
		$per_page = (int) $request->get_param('per_page');
		$per_page = $per_page > 0 ? min($per_page, 100) : 25;
		$search   = (string) $request->get_param('search');

		$funnel_status = (string) $request->get_param('funnel_status');

		// The retired `status=trash` still means the same thing.
		if ('trash' === $request->get_param('status')) {
			$funnel_status = 'trash';
		}

		$post_status = in_array($funnel_status, array('publish', 'draft', 'trash'), true)
			? array($funnel_status)
			: array('publish', 'draft', 'trash');

		$orderby = (string) $request->get_param('orderby');
		$order   = 'asc' === $request->get_param('order') ? 'ASC' : 'DESC';

		$args = array(
			'post_type'   => WPFNL_FUNNELS_POST_TYPE,
			'post_status' => $post_status,
		);

		if ($search) {
			$args['s'] = $search;
		}

		$exclude_store_checkout = $this->get_store_checkout_exclusion();

		if ($exclude_store_checkout) {
			$args['meta_query'] = $exclude_store_checkout; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		if (in_array($orderby, array('conversions', 'sales'), true)) {
			list($posts, $total) = $this->query_by_analytics($args, $orderby, $order, $page, $per_page);
		} else {
			$query = new WP_Query(
				array_merge(
					$args,
					array(
						'posts_per_page' => $per_page,
						'paged'          => $page,
						// WP_Query matches its order keys case-sensitively, and
						// the post id column is the one spelled in capitals.
						'orderby'        => 'id' === $orderby ? 'ID' : $orderby,
						'order'          => $order,
					)
				)
			);

			$posts = $query->posts;
			$total = (int) $query->found_posts;
		}

		$funnels = array();

		foreach ($posts as $funnel) {
			$funnels[] = $this->prepare_funnel($funnel);
		}

		$total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 0;

		return rest_ensure_response(
			array(
				'success'     => true,
				'items'       => $funnels,
				'total'       => $total,
				'total_pages' => $total_pages,
				'page'        => $page,
				'per_page'    => $per_page,
				'counts'      => $this->get_counts(),
				'meta'        => $this->get_screen_meta(),
			)
		);
	}


	/**
	 * One page of funnels ordered by a number the posts table does not hold.
	 *
	 * Conversions and sales are aggregates over `wpfnl_stats`, so they cannot be
	 * an ORDER BY on a post query. The matching funnels are resolved first (ids
	 * only), their totals fetched in a single grouped query, and the ordering
	 * and paging done over that list — which also means funnels with no orders
	 * at all still sort, at zero, instead of dropping out of a join.
	 *
	 * @param array  $args     Base query arguments: post type, status, search
	 *                         and the Store Checkout exclusion.
	 * @param string $orderby  `conversions` or `sales`.
	 * @param string $order    `ASC` or `DESC`.
	 * @param int    $page     Page being asked for.
	 * @param int    $per_page Page size.
	 *
	 * @return array `[ WP_Post[], int $total ]`.
	 * @since 3.13.4
	 */
	private function query_by_analytics($args, $orderby, $order, $page, $per_page)
	{
		$ids = get_posts(
			array_merge(
				$args,
				array(
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			)
		);

		if (!$ids) {
			return array(array(), 0);
		}

		$totals = $this->get_analytics_map($ids);
		$key    = 'sales' === $orderby ? 'total_revenue' : 'total_orders';

		usort(
			$ids,
			function ($left, $right) use ($totals, $key, $order) {
				$a = isset($totals[$left][$key]) ? $totals[$left][$key] : 0;
				$b = isset($totals[$right][$key]) ? $totals[$right][$key] : 0;

				if ($a === $b) {
					// Newest first among equals, so the zeroes are not shuffled
					// into a different order on every request.
					return $right - $left;
				}

				return 'ASC' === $order ? ($a < $b ? -1 : 1) : ($a > $b ? -1 : 1);
			}
		);

		$total     = count($ids);
		$page_ids  = array_slice($ids, ($page - 1) * $per_page, $per_page);

		if (!$page_ids) {
			return array(array(), $total);
		}

		$posts = get_posts(
			array(
				'post_type'        => WPFNL_FUNNELS_POST_TYPE,
				'post_status'      => isset($args['post_status']) ? $args['post_status'] : 'any',
				'post__in'         => $page_ids,
				'orderby'          => 'post__in',
				'posts_per_page'   => count($page_ids),
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);

		return array($posts, $total);
	}


	/**
	 * Conversions and sales for a set of funnels, in one query.
	 *
	 * The per-row reader below answers for a single funnel and caches per
	 * funnel; ordering needs every matching funnel at once, and doing that one
	 * funnel at a time is a query per funnel.
	 *
	 * @param array $funnel_ids Funnel ids.
	 *
	 * @return array Map of funnel id to `[ 'total_orders' => int, 'total_revenue' => float ]`.
	 * @since 3.13.4
	 */
	private function get_analytics_map($funnel_ids)
	{
		global $wpdb;

		$map = array();

		$stats_table  = $wpdb->prefix . 'wpfnl_stats';
		$table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $stats_table)); // phpcs:ignore

		if (!$table_exists) {
			return $map;
		}

		$ids = array_map('absint', (array) $funnel_ids);

		if (!$ids) {
			return $map;
		}

		// Safe to interpolate: every id has been through absint().
		$placeholders = implode(',', $ids);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT funnel_id,
					COUNT(DISTINCT id) AS total_orders,
					COALESCE(SUM(total_sales), 0) AS total_revenue
				FROM {$stats_table}
				WHERE funnel_id IN ({$placeholders}) AND status IN (%s, %s)
				GROUP BY funnel_id",
				'completed',
				'processing'
			)
		); // phpcs:ignore

		foreach ((array) $rows as $row) {
			$map[(int) $row->funnel_id] = array(
				'total_orders'  => (int) $row->total_orders,
				'total_revenue' => round((float) $row->total_revenue, 2),
			);
		}

		return $map;
	}


	/**
	 * Meta query that keeps Store Checkout funnels out of this listing.
	 *
	 * Mirrors the legacy view: the remote-funnel build lists everything, every
	 * other install hides the Store Checkout funnel, which has its own screen.
	 *
	 * @return array Meta query, or an empty array when nothing is excluded.
	 * @since 3.13.4
	 */
	private function get_store_checkout_exclusion()
	{
		if (defined('REMOTE_FUNNEL_VERSION')) {
			return array();
		}

		return array(
			'relation' => 'OR',
			array(
				'key'     => '_wpfnl_funnel_type',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_wpfnl_funnel_type',
				'value'   => 'store_checkout',
				'compare' => '!=',
			),
		);
	}


	/**
	 * How many funnels each value of the status filter would find.
	 *
	 * @return array Counts keyed by filter value.
	 * @since 3.13.4
	 */
	private function get_counts()
	{
		$publish = $this->count_funnels(array('publish'));
		$draft   = $this->count_funnels(array('draft'));
		$trash   = $this->count_funnels(array('trash'));

		return array(
			// What each value of the status filter would find, so the filter can
			// show its own numbers without a request per option. `all` is the
			// sum of the three below it, which is the only way the four numbers
			// add up on screen.
			'all'     => $publish + $draft + $trash,
			'publish' => $publish,
			'draft'   => $draft,
			'trash'   => $trash,
			// Retired alias for the Live tab, which counted everything but the
			// trash. Kept for older bundles.
			'live'    => $publish + $draft,
		);
	}


	/**
	 * Count funnels in the given post statuses, Store Checkout excluded.
	 *
	 * @param array $post_status Post statuses to count.
	 *
	 * @return int
	 * @since 3.13.4
	 */
	private function count_funnels($post_status)
	{
		$args = array(
			'post_type'        => WPFNL_FUNNELS_POST_TYPE,
			'post_status'      => $post_status,
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		);

		$exclusion = $this->get_store_checkout_exclusion();

		if ($exclusion) {
			$args['meta_query'] = $exclusion; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		return count(get_posts($args));
	}


	/**
	 * State the screen's chrome depends on: the plan, the funnel allowance, and
	 * the URLs of the journeys that still live outside the React admin.
	 *
	 * @return array
	 * @since 3.13.4
	 */
	private function get_screen_meta()
	{
		$is_pro_active = (bool) apply_filters('wpfunnels/is_pro_license_activated', false);
		$funnel_count  = (int) Wpfnl_functions::count_non_store_checkout_funnels();
		$limit_reached = !$is_pro_active && $funnel_count >= self::FREE_FUNNEL_LIMIT;

		return array(
			'isProActive'       => $is_pro_active,
			'funnelCount'       => $funnel_count,
			'maxFunnels'        => $is_pro_active ? -1 : self::FREE_FUNNEL_LIMIT,
			'limitReached'      => $limit_reached,
			'isWooActive'       => Wpfnl_functions::is_wc_active(),
			'isLmsActive'       => Wpfnl_functions::is_lms_addon_active(),
			// Where "New Funnel" goes: the template library opens its own modal
			// when it is loaded with this page in the query string.
			// The template library is a route of the React admin now; the legacy
			// page slug only redirects here, so link straight at the route.
			'createFunnelUrl'   => esc_url_raw(add_query_arg(array('page' => WPFNL_MAIN_PAGE_SLUG), admin_url('admin.php'))) . '#/funnel-templates',
			'aiFunnelUrl'       => esc_url_raw(add_query_arg(array('page' => WPFNL_AI_FUNNEL_PAGE_SLUG), admin_url('admin.php'))),
			'upgradeUrl'        => 'https://getwpfunnels.com/pricing/',
			'canCreateWithAi'   => $is_pro_active,
			'canBulkExport'     => $is_pro_active && defined('WPFNL_PRO_VERSION') && version_compare(WPFNL_PRO_VERSION, '1.9.3', '>='),
		);
	}


	/**
	 * Shape one funnel row.
	 *
	 * @param \WP_Post $funnel Funnel post.
	 *
	 * @return array
	 * @since 3.13.4
	 */
	private function prepare_funnel($funnel)
	{
		$funnel_id = (int) $funnel->ID;
		$type      = $this->get_funnel_type($funnel_id);
		$steps     = $this->get_steps_order($funnel_id);
		$analytics = $this->get_funnel_analytics($funnel_id);
		$is_trash  = 'trash' === $funnel->post_status;

		/*
		 * A funnel whose stack is switched off must not be opened: the legacy
		 * row greyed itself out and said why, and the reason is resolved here
		 * because only PHP knows whether WooCommerce or the LMS addon is
		 * running.
		 */
		$disabled_reason = '';

		if ('wc' === $type['key'] && !Wpfnl_functions::is_wc_active()) {
			$disabled_reason = __('To run/edit this funnel, please Activate WooCommerce.', 'wpfnl');
		} elseif ('lms' === $type['key'] && !Wpfnl_functions::is_lms_addon_active()) {
			$disabled_reason = __('To run/edit this funnel, please Activate LearnDash & WPFunnels Pro - LMS Funnel', 'wpfnl');
		}

		return array(
			'id'             => $funnel_id,
			'name'           => $funnel->post_title ? $funnel->post_title : __('(no title)', 'wpfnl'),
			'status'         => $funnel->post_status,
			// `get_formatted_status()` knows publish and draft; the trash tab is
			// this screen's own state and needs its own word.
			'statusLabel'    => $is_trash
				? __('Trashed', 'wpfnl')
				: Wpfnl_functions::get_formatted_status($funnel->post_status),
			'steps'          => count($steps),
			'stepsLabel'     => count($steps) . ' ' . Wpfnl_functions::get_formatted_data_with_phrase(count($steps), 'step', 'steps'),
			'type'           => $type['key'],
			'typeLabel'      => $type['label'],
			'builder'        => Wpfnl_functions::get_page_builder_by_step_id($funnel_id),
			'createdAt'      => $funnel->post_date_gmt,
			'modifiedAt'     => $funnel->post_modified_gmt,
			// Rendered server-side so the row reads "5 hours ago" in the site's
			// own locale rather than the browser's.
			'modifiedHuman'  => $this->get_relative_time($funnel->post_modified_gmt),
			// The relative wording loses precision as it ages ("4 days ago"),
			// so the exact moment rides along for the hover bubble.
			'modifiedExact'  => $this->get_absolute_time($funnel->post_modified_gmt),
			'conversions'    => $analytics['total_orders'],
			'sales'          => $analytics['total_revenue'],
			'editUrl'        => $is_trash ? '' : $this->get_edit_url($funnel_id),
			'viewUrl'        => $is_trash ? '' : $this->get_view_url($funnel_id),
			'viewDisabled'   => (bool) apply_filters('wpfunnels/disable_funnel_view_button', false, $funnel_id),
			'disabled'       => (bool) $disabled_reason,
			'disabledReason' => $disabled_reason,
		);
	}


	/**
	 * One funnel, as the React canvas header reads it.
	 *
	 * The list row's fields, so the header and the list agree on the view URL
	 * and the disabled state, plus what only the canvas needs: the raw title
	 * the rename field edits (`name` is the list's "(no title)" label), whether
	 * the funnel is a store checkout, the viewer's saved canvas layout, and the
	 * step types the step importer may offer.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 * @since 3.13.8
	 */
	public function get_funnel($request)
	{
		$funnel = get_post(absint($request->get_param('funnel_id')));

		if (!$funnel || WPFNL_FUNNELS_POST_TYPE !== $funnel->post_type) {
			return new WP_Error(
				'wpfunnels_rest_funnel_not_found',
				__('Funnel not found.', 'wpfnl'),
				array('status' => 404)
			);
		}

		$data = $this->prepare_funnel($funnel);

		// A global funnel has no single public entry point, so the classic
		// header hid its view button too.
		if (Wpfnl_functions::is_global_funnel($funnel->ID)) {
			$data['viewDisabled'] = true;
		}

		$data['title']           = $funnel->post_title;
		$data['isStoreCheckout'] = 'store_checkout' === get_post_meta($funnel->ID, '_wpfnl_funnel_type', true);
		// A Global Funnel (Pro's add-on, switched on in the funnel settings):
		// the canvas disables its landing card, limits the step importer and
		// reads the product badges from get_gbf_data. As the classic maybe_gbf().
		$data['isGbf']           = Wpfnl_functions::is_global_funnel_activated()
			&& 'yes' === get_post_meta($funnel->ID, 'is_global_funnel', true);
		$data['canvasViewMode']  = Wpfnl_functions::get_canvas_view_mode();
		// Step types the step importer lists; Pro adds downsell and custom.
		$data['supportedSteps']  = array_values(Wpfnl_functions::get_supported_step_type());

		// For the condition drawer's billing/shipping rules; a few hundred
		// entries, so only when asked for.
		if ($request->get_param('with_countries')) {
			$data['countries'] = Wpfnl_functions::get_countries();
		}

		// What the checkout settings drawer read from the classic canvas's
		// localized WPFunnelVars and wpfnl_addons_vars; only when asked for.
		if ($request->get_param('with_checkout')) {
			$data['checkoutContext'] = $this->get_checkout_context($funnel->ID);
		}

		// What the funnel settings drawer read from WPFunnelVars; only when asked for.
		if ($request->get_param('with_settings')) {
			$data['settingsContext'] = $this->get_settings_context($funnel->ID);
		}

		return rest_ensure_response($data);
	}


	/**
	 * The classic canvas's funnel-settings localized values, for one funnel.
	 *
	 * Mirrors Wpfnl_Admin's `WPFunnelVars` (isGlobalFunnelActivated, isGbf,
	 * isSkipOffer, isLmsSettings, isLmsDisbaled), which decide which rows the
	 * funnel settings drawer shows and whether the LMS switch can be used.
	 *
	 * @param int $funnel_id Funnel id.
	 *
	 * @return array
	 * @since 3.13.8
	 */
	private function get_settings_context($funnel_id)
	{
		$is_gbf_active = Wpfnl_functions::is_global_funnel_activated();

		// Wpfnl_Admin::is_skip_offer(): built into Pro with Global Funnels, or
		// the legacy standalone addon from 1.0.9.
		$is_skip_offer = $is_gbf_active
			|| (defined('WPFNL_PRO_GB_VERSION') && version_compare(WPFNL_PRO_GB_VERSION, '1.0.9', '>='));

		return array(
			'isGlobalFunnelActivated' => (bool) $is_gbf_active,
			'isGbf'                   => $is_gbf_active && 'yes' === get_post_meta($funnel_id, 'is_global_funnel', true),
			'isSkipOffer'             => (bool) $is_skip_offer,
			'isLmsSettings'           => (bool) Wpfnl_functions::is_enable_lms_settings(),
			// Wpfnl_Admin::maybe_lms_settings_disbaled(), which despite its name
			// is true when the switch may be used.
			'canSwitchLms'            => (bool) ( Wpfnl_functions::is_wc_active() && Wpfnl_functions::is_lms_addon_active() ),
		);
	}


	/**
	 * The classic canvas's checkout-related localized values, for one funnel.
	 *
	 * Mirrors Wpfnl_Admin's `WPFunnelVars` (isGlobalFunnelActivated, isGbf,
	 * lmsVersion, availableGateways) and `wpfnl_addons_vars`, so the ported
	 * checkout tabs see exactly what the Vue ones saw.
	 *
	 * @param int $funnel_id Funnel id.
	 *
	 * @return array
	 * @since 3.13.8
	 */
	private function get_checkout_context($funnel_id)
	{
		$is_gbf_active = Wpfnl_functions::is_global_funnel_activated();

		return array(
			'isGlobalFunnelActivated' => (bool) $is_gbf_active,
			'isGbf'                   => $is_gbf_active && 'yes' === get_post_meta($funnel_id, 'is_global_funnel', true),
			'lmsVersion'              => (defined('WPFNL_PRO_LMS_VERSION') || defined('WPFUNNELS_PRO_LMS_VERSION')) && defined('LEARNDASH_VERSION')
				? version_compare(LEARNDASH_VERSION, '4.2.1.1', '>=')
				: false,
			'availableGateways'       => Wpfnl_functions::get_enabled_payment_gateways(),
			'addonsVars'              => array(
				'addons'          => Wpfnl_functions::get_supported_addons(),
				'gbf_conditions'  => Wpfnl_functions::get_supported_gbf_offer_condition(),
				'categories'      => Wpfnl_functions::get_categories_gbf_offer_condition(),
				'role'            => Wpfnl_functions::get_all_user_roles(),
				'tags'            => Wpfnl_functions::get_all_tags(),
				'shippingClasses' => Wpfnl_functions::get_shipping_classes(),
			),
		);
	}


	/**
	 * Steps order meta of a funnel, always as an array.
	 *
	 * @param int $funnel_id Funnel id.
	 *
	 * @return array
	 * @since 3.13.4
	 */
	private function get_steps_order($funnel_id)
	{
		$steps = get_post_meta($funnel_id, '_steps_order', true);
		$steps = Wpfnl_functions::unserialize_array_data($steps);

		return is_array($steps) ? $steps : array();
	}


	/**
	 * Funnel type and its label, with the same fallbacks the legacy row used.
	 *
	 * @param int $funnel_id Funnel id.
	 *
	 * @return array `[ 'key' => string, 'label' => string ]`.
	 * @since 3.13.4
	 */
	private function get_funnel_type($funnel_id)
	{
		$type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);

		if ('lead' === $type) {
			return array('key' => 'lead', 'label' => __('Lead', 'wpfnl'));
		}

		if ('lms' === $type) {
			return array('key' => 'lms', 'label' => __('LMS', 'wpfnl'));
		}

		if (defined('WC_PLUGIN_FILE')) {
			return array('key' => 'wc', 'label' => __('Woo', 'wpfnl'));
		}

		return array('key' => 'lead', 'label' => __('Lead', 'wpfnl'));
	}


	/**
	 * Canvas URL of a funnel.
	 *
	 * @param int $funnel_id Funnel id.
	 *
	 * @return string
	 * @since 3.13.4
	 */
	private function get_edit_url($funnel_id)
	{
		return esc_url_raw(\WPFunnels\Wpfnl_functions::get_funnel_edit_url($funnel_id));
	}


	/**
	 * Public URL of a funnel's first step, UTM parameters included.
	 *
	 * @param int $funnel_id Funnel id.
	 *
	 * @return string
	 * @since 3.13.4
	 */
	private function get_view_url($funnel_id)
	{
		Wpfnl_functions::generate_first_step($funnel_id);
		$first_step_id = Wpfnl_functions::get_first_step($funnel_id);

		if (!$first_step_id) {
			return '';
		}

		$view_link = apply_filters(
			'wpfunnels/modify_funnel_view_link',
			get_the_permalink($first_step_id),
			$first_step_id,
			$funnel_id
		);

		$utm_settings = Wpfnl_functions::get_funnel_utm_settings($funnel_id);

		if (!empty($utm_settings['utm_enable']) && 'on' === $utm_settings['utm_enable']) {
			unset($utm_settings['utm_enable']);
			$view_link = strtolower(add_query_arg($utm_settings, $view_link));
		}

		return esc_url_raw($view_link);
	}


	/**
	 * Conversions and sales of a funnel.
	 *
	 * Reads the same `wpfnl_stats` aggregate the canvas header shows, and stops
	 * at zero when the table is not there — a site that has never tracked an
	 * order has no stats table, and that is not an error worth failing the
	 * whole listing over.
	 *
	 * @param int $funnel_id Funnel id.
	 *
	 * @return array `[ 'total_orders' => int, 'total_revenue' => float ]`.
	 * @since 3.13.4
	 */
	private function get_funnel_analytics($funnel_id)
	{
		global $wpdb;

		$analytics = array('total_orders' => 0, 'total_revenue' => 0);

		$cache_key = 'wpfnl_funnel_list_analytics_' . $funnel_id;
		$cached    = wp_cache_get($cache_key, 'wpfnl');

		if (false !== $cached) {
			return $cached;
		}

		$stats_table  = $wpdb->prefix . 'wpfnl_stats';
		$table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $stats_table)); // phpcs:ignore

		if (!$table_exists) {
			return $analytics;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT id) AS total_orders, COALESCE(SUM(total_sales), 0) AS total_revenue
				FROM {$stats_table}
				WHERE funnel_id = %d AND status IN (%s, %s)",
				$funnel_id,
				'completed',
				'processing'
			)
		); // phpcs:ignore

		if ($row) {
			$analytics['total_orders']  = (int) $row->total_orders;
			$analytics['total_revenue'] = round((float) $row->total_revenue, 2);
		}

		wp_cache_set($cache_key, $analytics, 'wpfnl', 5 * MINUTE_IN_SECONDS);

		return $analytics;
	}


	/**
	 * "5 hours ago" for a GMT date string.
	 *
	 * @param string $gmt_date Post date in GMT.
	 *
	 * @return string
	 * @since 3.13.4
	 */
	private function get_relative_time($gmt_date)
	{
		$timestamp = strtotime($gmt_date . ' GMT');

		if (!$timestamp) {
			return '';
		}

		/* translators: %s: human readable time difference. */
		return sprintf(__('%s ago', 'wpfnl'), human_time_diff($timestamp, time()));
	}


	/**
	 * Exact modification time of a funnel, in the site's own timezone.
	 *
	 * Shown on hover beside the relative wording, which is readable but vague
	 * once a funnel is more than a day old.
	 *
	 * @param string $gmt_date Date in GMT.
	 *
	 * @return string
	 * @since 3.13.4
	 */
	private function get_absolute_time($gmt_date)
	{
		if (empty($gmt_date) || '0000-00-00 00:00:00' === $gmt_date) {
			return '';
		}

		return get_date_from_gmt($gmt_date, 'Y-m-d H:i:s');
	}


	/**
	 * Whether the named trashed funnels may come back.
	 *
	 * Restoring is how a free install would otherwise walk past its three-funnel
	 * allowance, so the module refuses quietly below that line. It checks this
	 * privately and answers "success" either way, which would have this endpoint
	 * reporting a restore that never happened — hence the same check here, where
	 * it can be answered with an error the screen can show.
	 *
	 * A store checkout has its own allowance of three, counted separately: these
	 * routes serve the Store Checkout screen too (see StoreCheckoutController),
	 * and measuring a store checkout against the funnel count refuses restores
	 * that are within its own allowance — and lets through ones that are not.
	 *
	 * @param array $funnel_ids Funnels about to be restored. An empty list is
	 *                          read as ordinary funnels, which is what the
	 *                          allowance meant before store checkouts had one.
	 *
	 * @return bool
	 * @since 3.13.4
	 */
	private function can_restore($funnel_ids = array())
	{
		if (Wpfnl_functions::is_pro_license_activated()) {
			return true;
		}

		$ids = array_filter(array_map('absint', (array) $funnel_ids));

		$has_funnel         = !$ids;
		$has_store_checkout = false;

		foreach ($ids as $id) {
			if ('store_checkout' === get_post_meta($id, '_wpfnl_funnel_type', true)) {
				$has_store_checkout = true;
			} else {
				$has_funnel = true;
			}
		}

		if ($has_funnel && (int) Wpfnl_functions::count_non_store_checkout_funnels(array('publish', 'draft')) >= self::FREE_FUNNEL_LIMIT) {
			return false;
		}

		if ($has_store_checkout && (int) Wpfnl_functions::count_store_checkout_funnels(array('publish', 'draft')) >= self::FREE_FUNNEL_LIMIT) {
			return false;
		}

		return true;
	}


	/**
	 * The error a restore is refused with.
	 *
	 * @param array $funnel_ids Funnels the restore was refused for, so the
	 *                          message names the allowance that was reached.
	 *
	 * @return WP_Error
	 * @since 3.13.4
	 */
	private function get_restore_limit_error($funnel_ids = array())
	{
		$ids              = array_filter(array_map('absint', (array) $funnel_ids));
		$is_store_checkout = (bool) $ids;

		foreach ($ids as $id) {
			if ('store_checkout' !== get_post_meta($id, '_wpfnl_funnel_type', true)) {
				$is_store_checkout = false;
				break;
			}
		}

		return new WP_Error(
			'wpfunnels_funnel_limit_reached',
			$is_store_checkout
				? sprintf(
					/* translators: %d: number of store checkouts allowed on the free plan. */
					__('The free plan allows %d live store checkouts. Trash a store checkout or upgrade to Pro before restoring this one.', 'wpfnl'),
					self::FREE_FUNNEL_LIMIT
				)
				: sprintf(
					/* translators: %d: number of funnels allowed on the free plan. */
					__('The free plan allows %d live funnels. Trash a funnel or upgrade to Pro before restoring this one.', 'wpfnl'),
					self::FREE_FUNNEL_LIMIT
				),
			array('status' => 403)
		);
	}


	/**
	 * Duplicate a funnel.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 * @since 3.13.4
	 */
	public function duplicate_funnel($request)
	{
		$funnel_id = absint($request->get_param('funnel_id'));
		$response  = FunnelModule::instance()->clone_funnel(array('funnel_id' => $funnel_id));

		if (empty($response['success'])) {
			return new WP_Error(
				'wpfunnels_duplicate_failed',
				!empty($response['message']) ? $response['message'] : __('Could not duplicate this funnel.', 'wpfnl'),
				array('status' => 500)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __('Funnel has been duplicated.', 'wpfnl'),
			)
		);
	}


	/**
	 * Publish or unpublish a funnel and its steps.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 * @since 3.13.4
	 */
	public function update_status($request)
	{
		$response = FunnelModule::instance()->update_funnel_status(
			array(
				'funnel_id' => absint($request->get_param('funnel_id')),
				'status'    => sanitize_text_field($request->get_param('status')),
			)
		);

		return rest_ensure_response($response);
	}


	/**
	 * Move a funnel to trash.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 * @since 3.13.4
	 */
	public function trash_funnel($request)
	{
		FunnelModule::instance()->trash_funnel(array('funnel_id' => absint($request->get_param('funnel_id'))));

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __('Funnel has been moved to trash.', 'wpfnl'),
			)
		);
	}


	/**
	 * Restore a trashed funnel.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 * @since 3.13.4
	 */
	public function restore_funnel($request)
	{
		$funnel_id = absint($request->get_param('funnel_id'));

		if (!$this->can_restore(array($funnel_id))) {
			return $this->get_restore_limit_error(array($funnel_id));
		}

		FunnelModule::instance()->restore_funnel(array('funnel_id' => $funnel_id));

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __('Funnel has been restored.', 'wpfnl'),
			)
		);
	}


	/**
	 * Delete a funnel and its steps for good.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 * @since 3.13.4
	 */
	public function delete_funnel($request)
	{
		FunnelModule::instance()->delete_marked_funnels(
			array('ids' => array(absint($request->get_param('funnel_id'))))
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __('Funnel has been deleted.', 'wpfnl'),
			)
		);
	}


	/**
	 * Export one funnel as a JSON payload.
	 *
	 * The REST twin of the `wpfnl-export-funnel` admin-ajax action the kebab
	 * menu used to post to, and the same exporter behind it — unlike the bulk
	 * route, this one exports a funnel whatever its post status, so a draft can
	 * be exported too.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 * @since 3.13.4
	 */
	public function export_funnel($request)
	{
		$funnel_id = absint($request->get_param('funnel_id'));

		/*
		 * The exporter answers "success" for any id it is handed, so the funnel
		 * has to be confirmed here — otherwise a stale row exports a file
		 * describing nothing.
		 */
		if (WPFNL_FUNNELS_POST_TYPE !== get_post_type($funnel_id)) {
			return new WP_Error(
				'wpfunnels_funnel_not_found',
				__('That funnel no longer exists.', 'wpfnl'),
				array('status' => 404)
			);
		}

		$response = Wpfnl_Export::getInstance()->export_funnel(array('funnel_id' => $funnel_id));

		if (empty($response['success'])) {
			return new WP_Error(
				'wpfunnels_export_failed',
				__('There is nothing to export for this funnel.', 'wpfnl'),
				array('status' => 404)
			);
		}

		return rest_ensure_response($response);
	}


	/**
	 * Apply one action to several funnels.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 * @since 3.13.4
	 */
	public function bulk_action($request)
	{
		$ids    = array_filter(array_map('absint', (array) $request->get_param('ids')));
		$action = sanitize_text_field($request->get_param('action'));

		if (!$ids) {
			return new WP_Error(
				'wpfunnels_no_funnel_selected',
				__('No funnel was selected.', 'wpfnl'),
				array('status' => 400)
			);
		}

		$module = FunnelModule::instance();

		switch ($action) {
			case 'trash':
				$module->trash_marked_funnels(array('ids' => $ids));
				$message = __('Selected funnels have been moved to trash.', 'wpfnl');
				break;

			case 'restore':
				if (!$this->can_restore($ids)) {
					return $this->get_restore_limit_error($ids);
				}

				$module->restore_marked_funnels(array('ids' => $ids));
				$message = __('Selected funnels have been restored.', 'wpfnl');
				break;

			case 'draft':
			case 'publish':
				/*
				 * One funnel at a time on purpose: the status change carries the
				 * funnel's steps with it, which the module already knows how to
				 * do — a bulk version would be the same loop with its own copy
				 * of that rule.
				 */
				foreach ($ids as $id) {
					$module->update_funnel_status(array('funnel_id' => $id, 'status' => $action));
				}

				$message = 'draft' === $action
					? __('Selected funnels have been moved to draft.', 'wpfnl')
					: __('Selected funnels have been published.', 'wpfnl');
				break;

			default:
				$module->delete_marked_funnels(array('ids' => $ids));
				$message = __('Selected funnels have been deleted.', 'wpfnl');
				break;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => $message,
			)
		);
	}


	/**
	 * Prepare a single item for response.
	 *
	 * @param mixed           $item    Item to prepare.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 * @since 3.13.4
	 */
	public function prepare_item_for_response($item, $request)
	{
		return rest_ensure_response($this->add_additional_fields_to_object($item, $request));
	}
}
