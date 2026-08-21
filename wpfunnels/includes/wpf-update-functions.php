<?php
/**
 * WPFunnels Update
 *
 * Functions for updating data, used by the background updater.
 *
 */



defined( 'ABSPATH' ) || exit;

/**
 * Create stat table
 *
 */
function wpf_create_350_stat_table() {
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
}


/**
 * Create optin entries table
 *
 * @return void
 * @since 3.2.0
 */
function wpf_create_350_optin_entries_table() {
    global $wpdb;
    $wpdb->hide_errors();
    $charset_collate = $wpdb->get_charset_collate();
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

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}


/**
 * Update DB version to 3.2.0
 *
 */
function wpf_update_350_db_version() {
	update_option('wpfunnels_db_version', '3.2.0');
}


/**
 * Create checkout visits table for native checkout conversion rate tracking.
 *
 * @since 3.11.0
 */
function wpf_create_3110_checkout_visits_table() {
	global $wpdb;
	$wpdb->hide_errors();
	$charset_collate = $wpdb->get_charset_collate();
	$table_name      = $wpdb->prefix . 'wpfnl_checkout_visits';

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

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}


/**
 * Add an index on (funnel_id, status) to the stats table to avoid full
 * table scans in funnel-level analytics lookups (e.g. the funnel canvas).
 *
 * Guarded with SHOW INDEX so it stays idempotent on sites where the index
 * may already have been applied manually.
 *
 * Return value follows the batched-migration contract documented on
 * Wpfnl_Activator::run_update_callback_end(): true means "there is more work
 * left, queue me again", false means "finished". This callback completes in a
 * single pass, so it always returns false. Returning true here is what caused
 * the runaway ActionScheduler loop fixed in 3.12.13 — do not change it back.
 *
 * @return bool Always false; this migration never needs a second pass.
 *
 * @since 3.12.12
 */
function wpf_add_31212_stats_funnel_status_index() {
	global $wpdb;
	$table = $wpdb->prefix . 'wpfnl_stats';

	// SHOW INDEX errors outright on a missing table, so confirm it exists
	// first. Sites that predate the stats table get it from the 3.5.0
	// callback, which ActionScheduler runs ahead of this one.
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore
	if ( ! $table_exists ) {
		return false;
	}

	$exists = $wpdb->get_var( $wpdb->prepare(
		'SHOW INDEX FROM ' . $table . ' WHERE Key_name = %s',
		'idx_funnel_status'
	) ); // phpcs:ignore

	if ( ! $exists ) {
		$wpdb->query( "ALTER TABLE {$table} ADD INDEX idx_funnel_status (funnel_id, status)" ); // phpcs:ignore
	}

	return false;
}
