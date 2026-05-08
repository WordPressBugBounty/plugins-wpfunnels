<?php
namespace WPFunnels\Widgets\DiviModules\D5\OfferButton\OfferButtonTrait;
if ( ! defined( 'ABSPATH' ) ) die();
trait ModuleClassnamesTrait {
	public static function module_classnames( array $params ): void {
		$ci = $params['classnamesInstance'] ?? null;
		if ( $ci ) $ci->add( 'wpfnl-offer-button-module' );
	}
}