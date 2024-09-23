<?php
/**
 * HPOS Compatibility Class
 *
 * This class provides compatibility support for HPOS integration within the WPFunnels plugin.
 * HPOS compatibility features can be added to this class to ensure seamless integration with HPOS systems.
 *
 * @package WPFunnels\Compatibility
 */
namespace WPFunnels\Compatibility;

/**
 * Class Hpos_Compatibility
 *
 * Provides compatibility support for HPOS integration.
 */
class Hpos_Compatibility {


	/**
	 * Get HPOS settings.
	 *
	 * This function retrieves the HPOS (Hosted Point of Sale) settings and returns them as an array.
	 * The returned array includes the following settings:
	 *   - hpos_enabled: Whether HPOS is enabled or not.
	 *   - hpos_sync_enabled: Whether data synchronization for custom order tables is enabled or not.
	 *   - hpos_cot_authoritative: Whether custom orders table is authoritative or not.
	 *   - hpos_transactions_enabled: Whether database transactions are enabled for data synchronization.
	 *   - hpos_transactions_level: The isolation level for database transactions during data synchronization.
	 *
	 * @return array HPOS settings.
	 * @since 2.8.0
	 */
	public static function get_settings() {
		return [
			'hpos_enabled'                      => get_option('woocommerce_feature_custom_order_tables_enabled'),
			'hpos_sync_enabled'                 => get_option('woocommerce_custom_orders_table_data_sync_enabled'),
			'hpos_cot_authoritative'            => get_option('woocommerce_custom_orders_table_enabled'),
			'hpos_transactions_enabled'         => get_option('woocommerce_use_db_transactions_for_custom_orders_table_data_sync'),
			'hpos_transactions_level'           => get_option('woocommerce_db_transactions_isolation_level_for_custom_orders_table_data_sync'),
		];
	}
}
