<?php
/**
 * Abstract base class for all WPFunnels Divi 5 modules.
 *
 * Every Divi 5 module must implement DependencyInterface so Divi's dependency
 * manager can call load() when it builds the module library tree.
 *
 * @package WPFunnels\Widgets\DiviModules\D5
 * @since   2.9.1
 */

namespace WPFunnels\Widgets\DiviModules\D5;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

abstract class BaseModule implements DependencyInterface {

	/** Tracks registered modules to prevent duplicate registration. */
	private static array $registered_modules = [];

	/** Module name, e.g. 'Checkout'. Used as class name and directory name. */
	abstract protected function get_module_name(): string;

	/** Fully-qualified namespace of the concrete module class. */
	abstract protected function get_module_namespace(): string;

	/** Directory name inside divi-5/modules/, e.g. 'Checkout'. */
	abstract protected function get_module_dir(): string;

	/**
	 * Trait files to load from {ModuleDir}/{ModuleName}Trait/.
	 * Override to add or remove traits.
	 */
	protected function get_trait_files(): array {
		return [
			'RenderCallbackTrait.php',
			'ModuleClassnamesTrait.php',
			'ModuleStylesTrait.php',
			'ModuleScriptDataTrait.php',
		];
	}

	/** Required by DependencyInterface — modules have no external deps. */
	public function getDependencies(): array {
		return [];
	}

	/**
	 * Load trait files for this module.
	 */
	protected function load_traits(): void {
		$module_dir = __DIR__ . '/' . $this->get_module_dir();
		$trait_dir  = $module_dir . '/' . $this->get_module_name() . 'Trait/';

		foreach ( $this->get_trait_files() as $file ) {
			$path = $trait_dir . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}
	}

	/**
	 * Called by Divi 5's dependency manager (or our fallback).
	 * Validates prerequisites, loads traits and the main class file,
	 * then registers the module with Divi 5.
	 */
	public function load(): void {
		$module_dir      = __DIR__ . '/' . $this->get_module_dir();
		$json_folder     = $module_dir . '/module-json';
		$json_file       = $json_folder . '/module.json';

		if ( ! is_dir( $json_folder ) || ! file_exists( $json_file ) ) {
			return;
		}

		$id = $this->get_module_namespace() . '\\' . $this->get_module_name();
		if ( isset( self::$registered_modules[ $id ] ) ) {
			return;
		}

		if ( ! class_exists( 'ET\Builder\Packages\ModuleLibrary\ModuleRegistration' ) ) {
			return;
		}

		$class = $this->get_module_namespace() . '\\' . $this->get_module_name();

		// Load traits first, then the main class file (traits are inlined into the class)
		$this->load_traits();

		$class_file = $module_dir . '/' . $this->get_module_name() . '.php';
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}

		if ( ! method_exists( $class, 'render_callback' ) ) {
			return;
		}

		try {
			ModuleRegistration::register_module(
				$json_folder,
				[ 'render_callback' => [ $class, 'render_callback' ] ]
			);
			self::$registered_modules[ $id ] = true;
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'WPFunnels Divi5: Module registration failed for ' . $id . ': ' . $e->getMessage() );
			}
		}
	}
}
