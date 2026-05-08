<?php
/**
 * Module registry for Divi 5.
 *
 * Maintains the list of available modules and orchestrates their registration.
 * Modules are only loaded on wpfunnel_steps post type pages (or in REST/admin
 * context where post type is unavailable) to avoid unnecessary overhead.
 *
 * @package WPFunnels\Widgets\DiviModules\D5
 * @since   2.9.1
 */

namespace WPFunnels\Widgets\DiviModules\D5;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}


class ModuleRegistry {

	/**
	 * Map of module directory → module class.
	 * Key   = subdirectory name inside divi-5/modules/
	 * Value = fully-qualified class name of the BaseModule subclass
	 */
	private static array $modules = [
		'Checkout'       => 'WPFunnels\Widgets\DiviModules\D5\Checkout\Checkout',
		'OptIn'          => 'WPFunnels\Widgets\DiviModules\D5\OptIn\OptIn',
		'NextStepButton' => 'WPFunnels\Widgets\DiviModules\D5\NextStepButton\NextStepButton',
		'OrderDetails'   => 'WPFunnels\Widgets\DiviModules\D5\OrderDetails\OrderDetails',
		'OfferButton'    => 'WPFunnels\Widgets\DiviModules\D5\OfferButton\OfferButton',
	];

	/**
	 * Register all modules.
	 *
	 * @param mixed $dependency_tree  Passed by divi_module_library_modules_dependency_tree;
	 *                                null when called from our init:20 fallback.
	 */
	public static function register_modules( $dependency_tree ): void {
		// Post-type guard: skip on non-funnel pages (but not in REST/admin context)
		$post_type = self::get_current_post_type();
		if ( ! empty( $post_type )
			&& $post_type !== WPFNL_STEPS_POST_TYPE
			&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			&& ! is_admin()
		) {
			return;
		}

		require_once __DIR__ . '/BaseModule.php';

		$modules_dir = __DIR__;

		foreach ( self::$modules as $dir => $class ) {
			$module_file = $modules_dir . '/' . $dir . '/' . $dir . '.php';

			// Load the base module subclass definition
			$base_file = $modules_dir . '/' . $dir . '/' . $dir . 'Module.php';
			// Some modules use {Dir}.php directly as their subclass
			$entry = file_exists( $base_file ) ? $base_file : null;

			if ( ! class_exists( $class ) ) {
				// Traits are loaded inside BaseModule::load(), so just need the subclass
				if ( $entry && file_exists( $entry ) ) {
					require_once $entry;
				} elseif ( file_exists( $module_file ) ) {
					// Try loading directly
					require_once $module_file;
				}
			}

			if ( ! class_exists( $class ) ) {
				continue;
			}

			/** @var BaseModule $instance */
			$instance = new $class();
			$instance->load();
		}
	}

	/**
	 * Register REST API routes for all modules (called from rest_api_init).
	 */
	public static function register_rest_routes(): void {
		require_once __DIR__ . '/BaseModule.php';

		$modules_dir = __DIR__;

		foreach ( self::$modules as $dir => $class ) {
			// Load traits + class file so REST route registration methods are available
			$trait_dir  = $modules_dir . '/' . $dir . '/' . $dir . 'Trait/';
			$rest_trait = $trait_dir . 'RestApiTrait.php';
			if ( file_exists( $rest_trait ) ) {
				require_once $rest_trait;
			}

			$class_file = $modules_dir . '/' . $dir . '/' . $dir . '.php';
			if ( file_exists( $class_file ) ) {
				require_once $class_file;
			}

			if ( method_exists( $class, 'register_rest_routes_direct' ) ) {
				$class::register_rest_routes_direct();
			}
		}
	}

	/**
	 * Determine the current post type safely (returns empty string when unknown).
	 */
	private static function get_current_post_type(): string {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '';
		}
		if ( is_admin() ) {
			return '';
		}
		$type = get_post_type();
		return $type ?: '';
	}
}
