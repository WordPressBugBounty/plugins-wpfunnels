<?php
namespace WPFunnels\Widgets\DiviModules\D5\NextStepButton;

if ( ! defined( 'ABSPATH' ) ) die();

use WPFunnels\Widgets\DiviModules\D5\BaseModule;

require_once __DIR__ . '/NextStepButtonTrait/RenderCallbackTrait.php';
require_once __DIR__ . '/NextStepButtonTrait/RestApiTrait.php';
require_once __DIR__ . '/NextStepButtonTrait/ModuleClassnamesTrait.php';
require_once __DIR__ . '/NextStepButtonTrait/ModuleStylesTrait.php';
require_once __DIR__ . '/NextStepButtonTrait/ModuleScriptDataTrait.php';

class NextStepButton extends BaseModule {

	use NextStepButtonTrait\RenderCallbackTrait;
	use NextStepButtonTrait\RestApiTrait;
	use NextStepButtonTrait\ModuleClassnamesTrait;
	use NextStepButtonTrait\ModuleStylesTrait;
	use NextStepButtonTrait\ModuleScriptDataTrait;

	protected function get_module_name(): string      { return 'NextStepButton'; }
	protected function get_module_namespace(): string { return 'WPFunnels\Widgets\DiviModules\D5\NextStepButton'; }
	protected function get_module_dir(): string       { return 'NextStepButton'; }

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
