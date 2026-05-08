<?php
namespace WPFunnels\Widgets\DiviModules\D5\NextStepButton\NextStepButtonTrait;
if ( ! defined( 'ABSPATH' ) ) die();
trait ModuleClassnamesTrait {
	public static function module_classnames( array $params ): void {
		$ci = $params['classnamesInstance'] ?? null;
		if ( $ci ) $ci->add( 'wpfnl-next-step-btn-module' );
	}
}