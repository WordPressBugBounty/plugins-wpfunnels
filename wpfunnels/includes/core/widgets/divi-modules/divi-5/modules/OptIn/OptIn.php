<?php
namespace WPFunnels\Widgets\DiviModules\D5\OptIn;

if ( ! defined( 'ABSPATH' ) ) die();

use WPFunnels\Widgets\DiviModules\D5\BaseModule;

require_once __DIR__ . '/OptInTrait/RenderCallbackTrait.php';
require_once __DIR__ . '/OptInTrait/RestApiTrait.php';
require_once __DIR__ . '/OptInTrait/ModuleClassnamesTrait.php';
require_once __DIR__ . '/OptInTrait/ModuleStylesTrait.php';
require_once __DIR__ . '/OptInTrait/ModuleScriptDataTrait.php';

class OptIn extends BaseModule {

	use OptInTrait\RenderCallbackTrait;
	use OptInTrait\RestApiTrait;
	use OptInTrait\ModuleClassnamesTrait;
	use OptInTrait\ModuleStylesTrait;
	use OptInTrait\ModuleScriptDataTrait;

	protected function get_module_name(): string      { return 'OptIn'; }
	protected function get_module_namespace(): string { return 'WPFunnels\Widgets\DiviModules\D5\OptIn'; }
	protected function get_module_dir(): string       { return 'OptIn'; }

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
