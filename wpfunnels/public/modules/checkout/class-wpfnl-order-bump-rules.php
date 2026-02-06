<?php
/**
 * Order Bump Rules Evaluator (Compatibility Layer)
 *
 * This is a compatibility layer that redirects to the Pro version if available.
 * Conditional rules are a Pro feature.
 *
 * @package WPFunnels\Modules\Frontend\Checkout
 * @since 3.0.0
 */

namespace WPFunnels\Modules\Frontend\Checkout;

class Wpfnl_Order_Bump_Rules {

	/**
	 * Evaluate if order bump should be displayed based on rules
	 *
	 * @param array $order_bump_settings Order bump settings including ruleSettings
	 * @return bool True if should display, false otherwise
	 */
	public static function should_display_order_bump( $order_bump_settings ) {
		// Check if Pro version is available AND license is active
		if ( class_exists( '\WPFunnelsPro\Modules\Frontend\Checkout\Wpfnl_Pro_Order_Bump_Rules' ) ) {
			$license_status = get_option( 'wpfunnels_pro_license_status' );
			
			// Only use Pro conditional rules if license is active
			if ( 'active' === $license_status ) {
				return \WPFunnelsPro\Modules\Frontend\Checkout\Wpfnl_Pro_Order_Bump_Rules::should_display_order_bump( $order_bump_settings );
			}
		}
		
		// Pro not available or license inactive - always show order bump (conditional rules are a Pro feature)
		return true;
	}
}
