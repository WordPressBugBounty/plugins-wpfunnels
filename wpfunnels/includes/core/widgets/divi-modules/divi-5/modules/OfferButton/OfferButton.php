<?php
namespace WPFunnels\Widgets\DiviModules\D5\OfferButton;

if ( ! defined( 'ABSPATH' ) ) die();

use WPFunnels\Widgets\DiviModules\D5\BaseModule;

require_once __DIR__ . '/OfferButtonTrait/RenderCallbackTrait.php';
require_once __DIR__ . '/OfferButtonTrait/RestApiTrait.php';
require_once __DIR__ . '/OfferButtonTrait/ModuleClassnamesTrait.php';
require_once __DIR__ . '/OfferButtonTrait/ModuleStylesTrait.php';
require_once __DIR__ . '/OfferButtonTrait/ModuleScriptDataTrait.php';

class OfferButton extends BaseModule {

	use OfferButtonTrait\RenderCallbackTrait;
	use OfferButtonTrait\RestApiTrait;
	use OfferButtonTrait\ModuleClassnamesTrait;
	use OfferButtonTrait\ModuleStylesTrait;
	use OfferButtonTrait\ModuleScriptDataTrait;

	protected function get_module_name(): string      { return 'OfferButton'; }
	protected function get_module_namespace(): string { return 'WPFunnels\Widgets\DiviModules\D5\OfferButton'; }
	protected function get_module_dir(): string       { return 'OfferButton'; }

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
