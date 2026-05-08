<?php
namespace WPFunnels\Widgets\DiviModules\D5\NextStepButton\NextStepButtonTrait;
if ( ! defined( 'ABSPATH' ) ) die();
use WPFunnels\Widgets\DiviModules\D5\SharedTrait\RestApiBaseTrait;
trait RestApiTrait {
	use RestApiBaseTrait;
	protected static function get_rest_namespace(): string { return 'wpfnl/v1'; }
	protected static function get_rest_route(): string     { return '/next-step-button/render'; }
	protected static function get_block_name(): string     { return 'wpfnl/next-step-button'; }
}
