<?php
namespace WPFunnels\Widgets\DiviModules\D5\Checkout\CheckoutTrait;

if ( ! defined( 'ABSPATH' ) ) die();

trait ModuleClassnamesTrait {
	public static function module_classnames( array $params ): void {
		$ci    = $params['classnamesInstance'] ?? null;
		$attrs = $params['attrs'] ?? [];
		if ( ! $ci ) return;
		$layout = $attrs['checkout_layout']['innerContent']['desktop']['value'] ?? '';
		if ( ! empty( $layout ) ) {
			$ci->add( 'wpfnl-checkout-' . sanitize_html_class( $layout ) );
		}
		$ci->add( 'wpfnl-checkout-module' );
	}
}