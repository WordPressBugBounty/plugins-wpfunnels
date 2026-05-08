<?php
/**
 * Divi 5 Checkout module.
 *
 * @package WPFunnels\Widgets\DiviModules\D5\Checkout
 * @since   2.9.1
 */

namespace WPFunnels\Widgets\DiviModules\D5\Checkout;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use WPFunnels\Widgets\DiviModules\D5\BaseModule;

// Traits are loaded by BaseModule::load_traits() before this file is included,
// but we also require them here for standalone REST route loading.
require_once __DIR__ . '/CheckoutTrait/RenderCallbackTrait.php';
require_once __DIR__ . '/CheckoutTrait/RestApiTrait.php';
require_once __DIR__ . '/CheckoutTrait/ModuleClassnamesTrait.php';
require_once __DIR__ . '/CheckoutTrait/ModuleStylesTrait.php';
require_once __DIR__ . '/CheckoutTrait/ModuleScriptDataTrait.php';

class Checkout extends BaseModule {

	use CheckoutTrait\RenderCallbackTrait;
	use CheckoutTrait\RestApiTrait;
	use CheckoutTrait\ModuleClassnamesTrait;
	use CheckoutTrait\ModuleStylesTrait;
	use CheckoutTrait\ModuleScriptDataTrait;

	protected function get_module_name(): string      { return 'Checkout'; }
	protected function get_module_namespace(): string { return 'WPFunnels\Widgets\DiviModules\D5\Checkout'; }
	protected function get_module_dir(): string       { return 'Checkout'; }

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
