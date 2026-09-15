<?php

/**
 * Fired during plugin activation
 *
 * @link  https://rextheme.com
 * @since 1.0.0
 *
 * @package    Wpfnl
 * @subpackage Wpfnl/includes
 */

use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wpfnl
 * @subpackage Wpfnl/includes
 * @author     RexTheme <support@rextheme.com>
 */
class Wpfnl_Activator
{

	/**
	 * Flags that the one-time canvas layout pin has already run.
	 *
	 * @since 3.12.13
	 */
	const BUILDER_MODE_PINNED_OPTION = 'wpfunnels_builder_mode_pinned';

	/**
	 * Flags that a fresh install still owes the revenue report its schedule.
	 *
	 * Set while seeding on activation and cleared once Action Scheduler is
	 * loaded and the recurring action is queued.
	 *
	 * @since 3.14.0
	 */
	const REVENUE_REPORT_PENDING_OPTION = 'wpfunnels_revenue_report_pending_schedule';

	/**
	 * Seconds to wait before re-queuing a DB update callback that reported
	 * more work to do. Throttles a misbehaving callback instead of letting it
	 * saturate the ActionScheduler queue runner.
	 *
	 * @since 3.12.13
	 */
	const REQUEUE_DELAY = MINUTE_IN_SECONDS;

	/**
	 * One-time cleanup of the ActionScheduler rows left behind by the runaway
	 * update-callback loop that shipped in 3.12.12.
	 *
	 * @since 3.12.13
	 */
	const RUNAWAY_PURGE_HOOK   = 'wpfunnels_purge_runaway_update_actions';
	const RUNAWAY_PURGE_OPTION = 'wpfunnels_runaway_update_actions_purged';
	const RUNAWAY_PURGE_BATCH  = 5000;

	private static $db_updates = array(
		'3.5.0' => array(
			'wpf_create_350_stat_table',
			'wpf_create_350_optin_entries_table'
		),
		'3.11.0' => array(
			'wpf_create_3110_checkout_visits_table',
		),
		'3.12.12' => array(
			'wpf_add_31212_stats_funnel_status_index',
		),
	);


	/**
	 * Init hook
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_pin_legacy_builder_mode' ), 1 );
		add_action( 'init', array( __CLASS__, 'update' ), 20 );
		add_action('wpfunnels_run_update_callback', array( __CLASS__, 'run_update_callback' ) );
		add_action( 'init', array( __CLASS__, 'maybe_schedule_runaway_purge' ), 21 );
		add_action( 'init', array( __CLASS__, 'maybe_schedule_seeded_revenue_report' ), 21 );
		add_action( self::RUNAWAY_PURGE_HOOK, array( __CLASS__, 'purge_runaway_update_actions' ) );
	}

	/**
	 * Push all db updates to ActionScheduler
	 *
	 * @return void
	 * @since 3.5.0
	 */
	public static function update() {
		$current_db_version = get_option( 'wpfunnels_db_version' );
		$loop               = 0;

		$db_update_callbacks= self::get_db_update_callbacks();

		foreach ( $db_update_callbacks as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					if (!as_next_scheduled_action('wpfunnels_run_update_callback', array(
						'update_callback' => $update_callback,
					),
						'wpfunnels-db-updates'
					)) {
						as_schedule_single_action(
							time() + $loop,
							'wpfunnels_run_update_callback',
							array(
								'update_callback' => $update_callback,
							),
							'wpfunnels-db-updates'
						);
						++$loop;
					}
				}
			}
		}

		// After the callbacks finish, update the db version to the current WPFunnel version.
		$current_wpfnl_version = wpfnl()->get_version();
		if ( version_compare( $current_db_version, $current_wpfnl_version, '<' ) ) {
			update_option( 'wpfunnels_db_version', $current_wpfnl_version );
		}
	}


	/**
	 * Run an update callback when triggered by ActionScheduler.
	 *
	 * @param $update_callback
	 * @since 3.2.0
	 */
	public static function run_update_callback( $update_callback ) {
		include_once WPFNL_DIR . '/includes/wpf-update-functions.php';
		if ( is_callable( $update_callback ) ) {
			self::run_update_callback_start( $update_callback );
			$result = (bool) call_user_func( $update_callback );
			self::run_update_callback_end( $update_callback, $result );
		}
	}


	/**
	 * Triggered when a callback will run.
	 *
	 * @since 3.2.0
	 * @param string $callback Callback name.
	 */
	protected static function run_update_callback_start( $callback ) {
		if ( ! defined( 'WPF_UPDATING' ) ) {
			define( 'WPF_UPDATING', true );
		}
	}


	/**
	 * Triggered when a callback has ran.
	 *
	 * A truthy $result means the callback processed one batch and has more work
	 * left, so it gets queued again; a falsy $result means it finished. Callbacks
	 * that do all their work in one pass MUST return false — returning true from a
	 * single-pass callback is what produced the runaway queue fixed in 3.12.13.
	 *
	 * Two guards keep a callback that gets this wrong from flooding the queue:
	 * the re-queue is skipped when an identical action is already pending, and it
	 * is spaced out by REQUEUE_DELAY rather than being due the instant it lands.
	 * A genuine batched migration loses nothing from the delay; a buggy one is
	 * capped at one row per interval instead of saturating the queue runner.
	 *
	 * @param string $callback Callback name.
	 * @param bool   $result   Whether the callback has more work queued up.
	 */
	protected static function run_update_callback_end( $callback, $result ) {
		if ( ! $result ) {
			update_option( 'wpfunnels_' . $callback . '_update', 'completed' );
			return;
		}

		$args = array( 'update_callback' => $callback );

		if ( as_next_scheduled_action( 'wpfunnels_run_update_callback', $args, 'wpfunnels-db-updates' ) ) {
			return;
		}

		as_schedule_single_action(
			time() + self::REQUEUE_DELAY,
			'wpfunnels_run_update_callback',
			$args,
			'wpfunnels-db-updates'
		);
	}



	/**
	 * Queue the one-time purge of runaway update-callback rows.
	 *
	 * 3.12.12 shipped a callback that reported "more work to do" on every run,
	 * so ActionScheduler re-queued it without pause and affected sites ended up
	 * with millions of rows in actionscheduler_actions. The return-value fix
	 * stops the loop, but it cannot remove what already accumulated, and the
	 * volume is far past what the Scheduled Actions screen can delete by hand.
	 *
	 * @since 3.12.13
	 */
	public static function maybe_schedule_runaway_purge() {
		if ( 'completed' === get_option( self::RUNAWAY_PURGE_OPTION ) ) {
			return;
		}

		// Front-end requests are the bulk of a site's traffic and there is no
		// reason to spend a queue lookup on each one; admin and cron hits are
		// frequent enough to get the chain started and to restart it if it
		// ever breaks part-way through.
		if ( ! is_admin() && ! wp_doing_cron() ) {
			return;
		}

		if ( ! function_exists( 'as_schedule_single_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}

		// Covers in-progress as well as pending, so a batch that is mid-flight
		// does not get a duplicate queued alongside it.
		if ( as_next_scheduled_action( self::RUNAWAY_PURGE_HOOK, array(), 'wpfunnels-db-updates' ) ) {
			return;
		}

		self::queue_purge_batch( time() );
	}


	/**
	 * Delete one batch of finished update-callback rows, chaining the next
	 * batch until nothing is left.
	 *
	 * Only rows in a terminal state are touched. Pending and in-progress rows
	 * are left alone so a site part-way through a legitimate migration does not
	 * lose queued work.
	 *
	 * @since 3.12.13
	 */
	public static function purge_runaway_update_actions() {
		global $wpdb;

		$actions_table = $wpdb->prefix . 'actionscheduler_actions';

		if ( ! self::table_exists( $actions_table ) ) {
			self::finish_runaway_purge();
			return;
		}

		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT action_id FROM {$actions_table} WHERE hook = %s AND status IN ( %s, %s, %s ) LIMIT %d",
			'wpfunnels_run_update_callback',
			'complete',
			'canceled',
			'failed',
			self::RUNAWAY_PURGE_BATCH
		) ); // phpcs:ignore

		if ( empty( $ids ) ) {
			self::finish_runaway_purge();
			return;
		}

		$ids          = array_map( 'absint', $ids );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$logs_table   = $wpdb->prefix . 'actionscheduler_logs';

		// Logs first: dropping the actions before their logs would strand the
		// log rows with no id left to match them on.
		if ( self::table_exists( $logs_table ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$logs_table} WHERE action_id IN ({$placeholders})", $ids ) ); // phpcs:ignore
		}

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$actions_table} WHERE action_id IN ({$placeholders})", $ids ) ); // phpcs:ignore

		// Chain the next batch by hand rather than using a recurring action.
		// ActionScheduler re-creates a recurring action after the callback
		// returns, from the action object it read before execution, so a
		// recurring purge could not switch itself off from in here — it would
		// keep queueing a fresh row every interval long after the work ran out.
		self::queue_purge_batch( time() + MINUTE_IN_SECONDS );
	}


	/**
	 * Queue a single purge batch, unless one is already waiting.
	 *
	 * The pending check is deliberately narrower than as_next_scheduled_action(),
	 * which reports true for the in-progress action as well. Called from inside
	 * a running batch, that would always short-circuit and the chain would stop
	 * after its first pass.
	 *
	 * @param int $timestamp When the batch should run.
	 *
	 * @since 3.12.13
	 */
	protected static function queue_purge_batch( $timestamp ) {
		if ( ! function_exists( 'as_schedule_single_action' ) || ! function_exists( 'as_get_scheduled_actions' ) ) {
			return;
		}

		$pending = as_get_scheduled_actions(
			array(
				'hook'     => self::RUNAWAY_PURGE_HOOK,
				'status'   => ActionScheduler_Store::STATUS_PENDING,
				'group'    => 'wpfunnels-db-updates',
				'per_page' => 1,
			),
			'ids'
		);

		if ( ! empty( $pending ) ) {
			return;
		}

		as_schedule_single_action( $timestamp, self::RUNAWAY_PURGE_HOOK, array(), 'wpfunnels-db-updates' );
	}


	/**
	 * Mark the purge done and make sure no further batch is queued.
	 *
	 * @since 3.12.13
	 */
	protected static function finish_runaway_purge() {
		update_option( self::RUNAWAY_PURGE_OPTION, 'completed' );

		// Nothing should be queued at this point, since a batch only chains a
		// successor when it actually deleted something. Swept anyway so a row
		// left over from an interrupted run cannot keep the chain alive.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::RUNAWAY_PURGE_HOOK, array(), 'wpfunnels-db-updates' );
		}
	}


	/**
	 * Whether a database table exists.
	 *
	 * @param string $table Fully prefixed table name.
	 * @return bool
	 *
	 * @since 3.12.13
	 */
	protected static function table_exists( $table ) {
		global $wpdb;

		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ); // phpcs:ignore
	}


	/**
	 * Initiate the activation process
	 *
	 * @since 1.0.0
	 */
	public static function activate(){
		$is_new_install = self::is_new_install(); // capture before update_wpfunnles_version() sets the flag

		set_transient( 'wpfunnels_just_activated', true, 60 );
		self::set_wpfunnels_activation_transients();

		// added from version 3.1.7
		self::create_tables();

		self::update_wpfunnles_version();
		self::update_wpfunnels_db_version();
		self::update_installed_time();

		if ( $is_new_install ) {
			self::seed_default_builder_mode();
			self::seed_default_notification_settings();
		}

		// add funnel type meta
		Wpfnl_functions::add_type_meta();
	}




	/**
	 * Update WP Funnels version to current.
	 *
	 * @since 1.0.0
	 */
	private static function update_wpfunnles_version()
	{
		update_site_option('wpfunnels_version', Wpfnl::get_instance()->get_version());
	}


	/**
	 * See if we need to redirect the admin to setup wizard or not.
	 *
	 * @since 1.0.0
	 */
	private static function set_wpfunnels_activation_transients()
	{
		if (self::is_new_install()) {
			set_transient('_wpfunnels_activation_redirect', 1, 30);
		}
	}

	/**
	 * Brand new install of wpfunnels
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function is_new_install()
	{
		return is_null(get_site_option('wpfunnels_version', null));
	}

	/**
	 * Seed funnel_builder_mode for brand-new installs.
	 *
	 * Writes the current default explicitly so the value in the database always
	 * reflects a decision, and marks the legacy pin as handled — a fresh install
	 * has no old default to preserve.
	 *
	 * @since 3.11.0
	 */
	private static function seed_default_builder_mode()
	{
		$general_settings = get_option( '_wpfunnels_general_settings', array() );
		if ( ! isset( $general_settings['funnel_builder_mode'] ) ) {
			$general_settings['funnel_builder_mode'] = Wpfnl_functions::DEFAULT_FUNNEL_BUILDER_MODE;
			update_option( '_wpfunnels_general_settings', $general_settings );
		}

		update_option( self::BUILDER_MODE_PINNED_OPTION, 'yes' );
	}

	/**
	 * Seed the notification settings for brand-new installs.
	 *
	 * The store revenue report is on out of the box from 3.14.0, but only for
	 * sites created from that version onward: the choice is written into the
	 * database here rather than flipped in the fallback that
	 * Wpfnl_functions::get_notification_settings() applies, so an existing site
	 * — which has either its own saved settings or no row at all — is left
	 * exactly as it was and never starts mailing after an update.
	 *
	 * A site that somehow already holds notification settings is skipped, so
	 * this can never overwrite a stored preference.
	 *
	 * @since 3.14.0
	 */
	private static function seed_default_notification_settings()
	{
		$stored = get_option( '_wpfunnels_notification_settings', array() );
		if ( ! empty( $stored ) ) {
			return;
		}

		update_option( '_wpfunnels_notification_settings', Wpfnl_functions::get_new_install_notification_settings() );

		// Action Scheduler is not dependable during activation, so the actual
		// queueing waits for a normal request on `init`.
		update_option( self::REVENUE_REPORT_PENDING_OPTION, 'yes' );
	}


	/**
	 * Queue the seeded revenue report once Action Scheduler is available.
	 *
	 * Only runs for an install that was just seeded — the flag is set nowhere
	 * else — so no existing site's schedule is touched. If the admin saved the
	 * settings before this ran, that save has already queued the action and this
	 * only clears the flag.
	 *
	 * @since 3.14.0
	 */
	public static function maybe_schedule_seeded_revenue_report()
	{
		if ( 'yes' !== get_option( self::REVENUE_REPORT_PENDING_OPTION ) ) {
			return;
		}

		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}

		$settings = Wpfnl_functions::get_notification_settings();

		if ( 'yes' === $settings['enable_revenue_report'] && ! Wpfnl_functions::is_revenue_report_scheduled() ) {
			Wpfnl_functions::schedule_revenue_report_email( $settings );
		}

		delete_option( self::REVENUE_REPORT_PENDING_OPTION );
	}


	/**
	 * Keep pre-existing sites on the horizontal canvas.
	 *
	 * The default canvas layout became vertical in 3.12.13. A site that has been
	 * running without a stored funnel_builder_mode has been on the old horizontal
	 * default all along, so flipping the fallback would silently rearrange every
	 * canvas its users know. This writes horizontal in explicitly, once, leaving
	 * the new default to apply only to installs created after the change. Users
	 * opt in to vertical from Settings or the canvas view switcher.
	 *
	 * Runs synchronously on `init` rather than through the Action Scheduler db
	 * updates, because a queued job could land after the user has already opened
	 * the builder and seen the wrong layout.
	 *
	 * @since 3.12.13
	 */
	public static function maybe_pin_legacy_builder_mode()
	{
		if ( 'yes' === get_option( self::BUILDER_MODE_PINNED_OPTION ) ) {
			return;
		}

		$general_settings = get_option( '_wpfunnels_general_settings', array() );
		$general_settings = is_array( $general_settings ) ? $general_settings : array();

		if ( ! isset( $general_settings['funnel_builder_mode'] ) ) {
			$general_settings['funnel_builder_mode'] = 'horizontal';
			update_option( '_wpfunnels_general_settings', $general_settings );
		}

		update_option( self::BUILDER_MODE_PINNED_OPTION, 'yes' );
	}

	/**
	 * Update db version to current
	 *
	 * @param null $version
	 *
	 * @since 1.0.0
	 */
	private static function update_wpfunnels_db_version($version = null)
	{
		if ( self::needs_db_update() ) {
			self::update();
		} else {
			update_site_option('wpfunnels_db_version', is_null($version) ? Wpfnl::get_instance()->get_version() : $version);
		}
	}


	/**
	 * Retrieve the time when funnel is installed
	 *
	 * @return int|mixed|void
	 * @since  2.0.0
	 */
	public static function get_installed_time() {
		$installed_time = get_option( 'wpfunnels_installed_time' );
		if ( ! $installed_time ) {
			$installed_time = time();
			update_site_option( 'wpfunnels_installed_time', $installed_time );
		}
		return $installed_time;
	}


	public static function update_installed_time() {
		self::get_installed_time();
	}


	/**
	 * Create necessary databases on plugin installation process
	 *
	 * @since 3.1.7
	 */
	public static function create_tables() {

		if ( !self::should_create_table() ) {
			return;
		}

		global $wpdb;
		$wpdb->hide_errors();
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'wpfnl_stats';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			funnel_id bigint(20) unsigned NOT NULL,
			parent_id bigint(20) unsigned NOT NULL,
			customer_id bigint(20) unsigned NOT NULL,
			total_sales double DEFAULT 0 NOT NULL,
			orderbump_sales double DEFAULT 0 NOT NULL,
			upsell_sales double DEFAULT 0 NOT NULL,
			downsell_sales double DEFAULT 0 NOT NULL,
			gateway double DEFAULT 0 NOT NULL,
			status varchar(20) NOT NULL,
			paid_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			date_created_gmt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
    	) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );


		/**
		 * Create checkout visits table for conversion rate tracking
		 */
		$table_name = $wpdb->prefix . 'wpfnl_checkout_visits';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type varchar(10) NOT NULL DEFAULT 'store',
			funnel_id bigint(20) unsigned NOT NULL DEFAULT 0,
			session_hash varchar(32) NOT NULL,
			visit_date date NOT NULL,
			date_created datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_visit (session_hash, type, funnel_id, visit_date)
		) $charset_collate;";
		dbDelta( $sql );

		/**
		 * Create table for optin entries
		 */
		$table_name = $wpdb->prefix . 'wpfnl_optin_entries';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			funnel_id bigint(20) unsigned NOT NULL,
			step_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
    		email varchar(100) NOT NULL,
        	hash varchar(100) NOT NULL,
            data LONGTEXT NULL DEFAULT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
    	) $charset_collate;";
		dbDelta( $sql );

		/**
		 * Create the AI copilot conversation and message tables.
		 *
		 * @since 3.13.0
		 */
		\WPFunnels\AI\ConversationStore::createTables();
	}


	/**
	 * Check if we need to create new tables
	 *
	 * @return bool
	 * @since 3.1.7
	 */
	public static function should_create_table() {
		$db_version = get_option( 'wpfunnels_db_version', '3.2.0' );

		if ( version_compare('3.2.1', $db_version, '>') ) {
			return true;
		}
		return false;
	}


	/**
	 * Get list of DB update callbacks.
	 *
	 * @return array
	 * @since  3.5.0
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}


	/**
	 * Is a DB update needed?
	 *
	 * @return bool
	 * @since 3.5.0
	 */
	public static function needs_db_update() {
		$current_db_version = get_option( 'wpfunnels_db_version', null );
		$updates            = self::get_db_update_callbacks();
		$update_versions    = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, end( $update_versions ), '<' );
	}
}
