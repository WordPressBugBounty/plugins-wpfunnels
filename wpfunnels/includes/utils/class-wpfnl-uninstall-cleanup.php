<?php

/**
 * UninstallCleanup: cleans all data during uninstallation
 * 
 * @package WPFunnels
 * @since 3.0.13
 */
class UninstallCleanup {

	public static function init() {
		$general_settings = get_option( '_wpfunnels_general_settings', []);

		if(isset($general_settings['uninstall_cleanup']) && $general_settings['uninstall_cleanup'] == 'on') {
			self::deleteAllFunnels();
			self::deleteAllSteps();
			self::deleteAllDBTables();
			self::deleteAllOptionsValue();
		}
	}
	
	/**
	 * deleteAllFunnels: deletes all funnel data during uninstallation. 
	 *
	 * @since 3.0.13
	 * @return void
	 */
	public static function deleteAllFunnels() {
		
			$funnel_ids = get_posts(
				array(
					'fields'      => 'ids',
					'numberposts' => - 1,
					'post_type'   => 'wpfunnels',
				)
			);

			if(!empty($funnel_ids)) {
				foreach ( $funnel_ids as $funnel_id ) {
					wp_delete_post( $funnel_id );
				}
			}
	}
	
	/**
	 * deleteAllSteps: deletes all funnel steps
	 *
	 * @since 3.0.13
	 * @return void
	 */
	public static function deleteAllSteps() {
		$funnel_step_ids = get_posts(
			array(
				'fields'      => 'ids',
				'numberposts' => - 1,
				'post_type'   => 'wpfunnel_steps',
			)
		);

		if(!empty($funnel_step_ids)) {
			foreach ( $funnel_step_ids as $funnel_step_id ) {
				wp_delete_post( $funnel_step_id );
			}
		}
	}

	/**
	 * deleteAllDBTables: deletes all plugin custom tables
	 *
	 * @since 3.0.13
	 * @return void
	 */
	public static function deleteAllDBTables() {
		
		global $wpdb;

		$sql = 'DROP TABLE IF EXISTS %1s';

		$wpfnl_tables = array(
			$wpdb->prefix . 'wpfnl_analytics_meta',
			$wpdb->prefix . 'wpfnl_analytics',
		);
		$wpfnl_tables = implode( ', ', $wpfnl_tables );

		$wpdb->query( $wpdb->prepare( $sql, $wpfnl_tables ) ); 
	}
	
	/**
	 * deleteAllOptionsValue: deletes plugins generic options data
	 *
	 * @since 3.0.13
	 * @return void
	 */
	public static function deleteAllOptionsValue() {
		
		global $wpdb;

		$option_ids_sql      = "SELECT `option_id` FROM {$wpdb->options} WHERE `option_name` LIKE '%wpfunnels%' OR `option_name` LIKE '%wpfnl%'";
		$funnels_option_ids = $wpdb->get_results( $option_ids_sql, ARRAY_A );

		$funnels_option_ids = array_column( $funnels_option_ids, 'option_id' );
		$funnels_option_ids = implode( ', ', $funnels_option_ids );
		$option_sql          = "DELETE FROM {$wpdb->options} WHERE `option_id` IN ({$funnels_option_ids})";

		$wpdb->query( $option_sql ); 
	}
}

UninstallCleanup::init();
