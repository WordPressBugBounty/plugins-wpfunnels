<?php
namespace WPFunnels\Widgets\DiviModules\D5\OrderDetails;

if ( ! defined( 'ABSPATH' ) ) die();

use WPFunnels\Widgets\DiviModules\D5\BaseModule;

require_once __DIR__ . '/OrderDetailsTrait/RenderCallbackTrait.php';
require_once __DIR__ . '/OrderDetailsTrait/RestApiTrait.php';
require_once __DIR__ . '/OrderDetailsTrait/ModuleClassnamesTrait.php';
require_once __DIR__ . '/OrderDetailsTrait/ModuleStylesTrait.php';
require_once __DIR__ . '/OrderDetailsTrait/ModuleScriptDataTrait.php';

class OrderDetails extends BaseModule {

	use OrderDetailsTrait\RenderCallbackTrait;
	use OrderDetailsTrait\RestApiTrait;
	use OrderDetailsTrait\ModuleClassnamesTrait;
	use OrderDetailsTrait\ModuleStylesTrait;
	use OrderDetailsTrait\ModuleScriptDataTrait;

	protected function get_module_name(): string      { return 'OrderDetails'; }
	protected function get_module_namespace(): string { return 'WPFunnels\Widgets\DiviModules\D5\OrderDetails'; }
	protected function get_module_dir(): string       { return 'OrderDetails'; }

	protected function get_trait_files(): array {
		return [
			'RenderCallbackTrait.php',
			'RestApiTrait.php',
			'ModuleClassnamesTrait.php',
			'ModuleStylesTrait.php',
			'ModuleScriptDataTrait.php',
		];
	}
}
