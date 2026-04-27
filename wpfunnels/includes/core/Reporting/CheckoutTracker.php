<?php

namespace WPFunnels\Report;

class CheckoutTracker {

	const TABLE_SUFFIX = 'wpfnl_checkout_visits';

	/**
	 * Create the checkout visits table using dbDelta.
	 */
	public static function create_table() {
		global $wpdb;
		$wpdb->hide_errors();
		$charset_collate = $wpdb->get_charset_collate();
		$table           = $wpdb->prefix . self::TABLE_SUFFIX;

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type varchar(10) NOT NULL DEFAULT 'store',
			funnel_id bigint(20) unsigned NOT NULL DEFAULT 0,
			session_hash varchar(32) NOT NULL,
			visit_date date NOT NULL,
			date_created datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_visit (session_hash, type, funnel_id, visit_date)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Record a checkout page visit with per-browser-session deduplication.
	 *
	 * Uniqueness key: WooCommerce session ID (unique per browser — incognito gets
	 * a separate session from the regular browser). Falls back to md5(IP+UA) when
	 * the WC session is unavailable (e.g. called outside a WC context).
	 *
	 * @param string $type      'funnel' or 'store'
	 * @param int    $funnel_id Funnel post ID (0 for store visits)
	 */
	public static function track_visit( $type, $funnel_id = 0 ) {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		if ( self::is_bot( $user_agent ) ) {
			return;
		}

		// WC session ID is unique per browser session — incognito vs normal = different IDs.
		// Fall back to IP+UA when WC session isn't available.
		if ( function_exists( 'WC' ) && WC()->session && WC()->session->get_customer_id() ) {
			$session_hash = md5( WC()->session->get_customer_id() );
		} else {
			$session_hash = md5( self::get_client_ip() . $user_agent );
		}

		$visit_date   = current_time( 'Y-m-d' );
		$date_created = current_time( 'mysql' );

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_SUFFIX;

		$wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$table} (type, funnel_id, session_hash, visit_date, date_created)
			 VALUES (%s, %d, %s, %s, %s)",
			$type,
			(int) $funnel_id,
			$session_hash,
			$visit_date,
			$date_created
		) );
	}

	/**
	 * Count unique funnel checkout visitors for a date range.
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return int
	 */
	public static function get_funnel_checkout_visits( $start_date, $end_date ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_SUFFIX;

		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM {$table}
			 WHERE type = 'funnel'
			 AND visit_date >= %s AND visit_date <= %s",
			date( 'Y-m-d', strtotime( $start_date ) ),
			date( 'Y-m-d', strtotime( $end_date ) )
		) );
	}

	/**
	 * Count unique native WC checkout visitors for a date range.
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return int
	 */
	public static function get_store_checkout_visits( $start_date, $end_date ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_SUFFIX;

		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM {$table}
			 WHERE type = 'store'
			 AND visit_date >= %s AND visit_date <= %s",
			date( 'Y-m-d', strtotime( $start_date ) ),
			date( 'Y-m-d', strtotime( $end_date ) )
		) );
	}

	/**
	 * Returns true if the user agent looks like a known bot/crawler.
	 *
	 * @param string $user_agent
	 * @return bool
	 */
	private static function is_bot( $user_agent ) {
		if ( empty( $user_agent ) ) {
			return true;
		}
		$bot_tokens = array( 'bot', 'crawl', 'slurp', 'spider', 'mediapartners', 'facebookexternalhit', 'wget', 'curl', 'python-requests', 'go-http-client' );
		$ua_lower   = strtolower( $user_agent );
		foreach ( $bot_tokens as $token ) {
			if ( strpos( $ua_lower, $token ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Resolve client IP, honouring common proxy headers (Cloudflare first).
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// X-Forwarded-For may be a comma-separated chain; take the first
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}
		return '';
	}
}
