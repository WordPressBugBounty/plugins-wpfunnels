<?php
/**
 * Divi 5 module registration orchestrator.
 *
 * Loaded once by the DiviModulesV5 Manager.  Registers three hooks:
 *
 * 1. divi_module_library_modules_dependency_tree (init:0)
 *    — Primary registration path used by Divi 5's module library.
 *
 * 2. rest_api_init
 *    — Registers REST routes so the Visual Builder can fetch live previews
 *      even when the dependency-tree hook already fired.
 *
 * 3. init:20  (fallback)
 *    — Catches cases where this file is required after init:0 has already
 *      fired (e.g. on certain admin-ajax requests).
 *
 * Also registers the D4 → D5 shortcode conversion map so Divi can migrate
 * existing pages built with the old ET_Builder_Module modules.
 *
 * @package WPFunnels\Widgets\DiviModules\D5
 * @since   2.9.1
 */

namespace WPFunnels\Widgets\DiviModules\D5;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! defined( 'WPFNL_DIVI5_MODULES_LOADED' ) ) {

	require_once __DIR__ . '/ModuleRegistry.php';

	// ── Primary: Divi 5 dependency tree (fires at init priority 0) ──────────
	add_action(
		'divi_module_library_modules_dependency_tree',
		static function ( $dependency_tree ) {
			if ( ! is_object( $dependency_tree ) ) {
				return;
			}
			ModuleRegistry::register_modules( $dependency_tree );
		},
		10,
		1
	);

	// ── REST API: register routes independently of the dependency tree ───────
	// The dependency tree hook only fires during Divi page rendering.
	// REST requests to /wp-json/wpfnl/v1/* need routes registered here.
	add_action(
		'rest_api_init',
		static function () {
			if ( ! interface_exists( 'ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' ) ) {
				return;
			}
			ModuleRegistry::register_rest_routes();
		}
	);

	// ── Fallback: if this file was loaded after init:0 already fired ─────────
	add_action(
		'init',
		static function () {
			static $done = false;
			if ( $done ) {
				return;
			}
			// Use the same detection logic as is_divi5_active()
			$is_divi5 = false;
			if ( function_exists( 'et_builder_d5_enabled' ) ) {
				$is_divi5 = et_builder_d5_enabled();
			} elseif ( interface_exists( 'ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' ) ) {
				$is_divi5 = true;
			}
			
			if ( ! $is_divi5 ) {
				return;
			}
			ModuleRegistry::register_modules( null );
			$done = true;
		},
		20
	);

	// ── D4 → D5 shortcode conversion map ────────────────────────────────────
	add_filter(
		'divi.moduleLibrary.conversion.moduleConversionOutline',
		static function ( $outline ) {
			$outline['wpfnl_checkout']          = 'wpfnl/checkout';
			$outline['wpfnl_optin']             = 'wpfnl/opt-in';
			$outline['wpfnl_next_step_button']  = 'wpfnl/next-step-button';
			$outline['wpfnl_order_details']     = 'wpfnl/order-details';
			$outline['wpfnl_offer_button']      = 'wpfnl/offer-button';
			$outline['wpfnl_lms_checkout']      = 'wpfnl/lms-checkout';
			$outline['wpfnl_lms_order_details'] = 'wpfnl/lms-order-details';
			return $outline;
		}
	);

	// ── Prevent D4 shortcode content from being corrupted when Divi 5 ────────
	// is active and the post is saved via the Gutenberg editor.
	add_filter(
		'wp_insert_post_data',
		static function ( $data, $postarr ) {
			if ( empty( $data['post_content'] ) ) {
				return $data;
			}

			$new_content = $data['post_content'];
			$has_d5_wrap = false !== strpos( $new_content, '<!-- wp:divi/placeholder' );
			$has_d4_sc   = false !== strpos( $new_content, '[et_pb_' );

			if ( $has_d5_wrap && $has_d4_sc ) {
				// Restore original DB content to preserve the D4 shortcode layout
				$original = get_post_field( 'post_content', $postarr['ID'] ?? 0 );
				if ( ! empty( $original ) ) {
					$data['post_content'] = $original;
				}
			}

			return $data;
		},
		10,
		2
	);

	define( 'WPFNL_DIVI5_MODULES_LOADED', true );
}
