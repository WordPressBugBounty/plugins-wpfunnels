<?php
namespace WPFunnels\Widgets\DiviModules\D5\OrderDetails\OrderDetailsTrait;
if ( ! defined( 'ABSPATH' ) ) die();
trait ModuleClassnamesTrait {
	public static function module_classnames( array $params ): void {
		$ci = $params['classnamesInstance'] ?? null;
		if ( $ci ) $ci->add( 'wpfnl-order-details-module' );
	}
}