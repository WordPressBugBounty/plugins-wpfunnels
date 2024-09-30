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

	private static $db_updates = array(
		'3.5.0' => array(
			'wpf_create_350_stat_table',
			'wpf_create_350_optin_entries_table'
		),
	);


	/**
	 * Init hook
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'update' ), 20 );
		add_action('wpfunnels_run_update_callback', array( __CLASS__, 'run_update_callback' ) );
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
	 * @param $callback
	 * @param $result
	 */
	protected static function run_update_callback_end( $callback, $result ) {
		if ( $result ) {
			as_schedule_single_action(
				time(),
				'wpfunnels_run_update_callback',
				array(
					'update_callback' => $callback,
				),
				'wpfunnels-db-updates'
			);
		} else {
			update_option('wpfunnels_'.$callback.'_update', 'completed');
		}
	}



	/**
	 * Initiate the activation process
	 *
	 * @since 1.0.0
	 */
	public static function activate()
	{

		self::set_wpfunnels_activation_transients();

		// added from version 3.1.7
		self::create_tables();

		self::update_wpfunnles_version();
		self::update_wpfunnels_db_version();
		self::update_installed_time();

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
