<?php
/**
 * SetupWizardTools — setup wizard plugin activation and add-on discovery.
 *
 * Wraps the setup wizard's AJAX-only plugin activation handlers
 * (admin/modules/setup-wizard/setup-wizard.php) as callable abilities, plus a
 * read-only listing of the recommended add-ons shown on the Add-ons admin
 * page (admin/modules/addons/view.php).
 *
 * `ajax_update_step_meta()` (also defined in setup-wizard.php) is deliberately
 * NOT wrapped here: it has no allow-list of meta keys today (any key the
 * caller names is written via `update_post_meta()`), and a repo-wide search
 * for its only registered AJAX action, `wp_ajax_wpfnl_update_step_meta`,
 * turned up no caller anywhere in this plugin's or WPFunnels Pro's JS/Vue —
 * it appears to be dead code. Wrapping it would mean guessing at an
 * allow-list rather than confirming real meta keys, which the security
 * review for this tool set explicitly rules out.
 *
 * @package WPFunnels\MCP
 * @since 3.14.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;

/**
 * Class SetupWizardTools
 */
class SetupWizardTools {

	/**
	 * Ability definitions for this domain.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/activate-plugin'        => [
				'label'               => __( 'Activate Plugin', 'wpfnl' ),
				'description'         => 'Activate a single already-installed plugin by its file path (e.g. "woocommerce/woocommerce.php"), the same action the setup wizard\'s per-plugin "Activate" buttons perform. The plugin must already be installed — this never downloads or installs anything.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'plugin' => [
							'type'        => 'string',
							'description' => 'Plugin file path relative to the plugins directory, e.g. "woocommerce/woocommerce.php" or "elementor/elementor.php".',
						],
					],
					'required'   => [ 'plugin' ],
				],
				'execute_callback'    => [ __CLASS__, 'activatePlugin' ],
				'permission_callback' => MCPHelper::currentUserCan( 'activate_plugins' ),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/activate-mail-mint'     => [
				'label'               => __( 'Activate Mail Mint', 'wpfnl' ),
				'description'         => 'Activate the Mail Mint plugin and run its first-run database setup, the same action the setup wizard\'s Mail Mint step performs. Mail Mint must already be installed.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'execute_callback'    => [ __CLASS__, 'activateMailMint' ],
				'permission_callback' => MCPHelper::currentUserCan( 'activate_plugins' ),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/list-recommended-addons' => [
				'label'               => __( 'List Recommended Add-ons', 'wpfnl' ),
				'description'         => 'The add-ons WPFunnels recommends on its Add-ons admin page, each with its current install/active status on this site.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'execute_callback'    => [ __CLASS__, 'listRecommendedAddons' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
		];
	}

	/**
	 * Activate a single plugin by file path.
	 *
	 * Reimplements the body of
	 * \WPFunnels\Admin\SetupWizard::ajax_activate_plugin_static() as a
	 * plain-argument callable — that method reads `$_POST['plugin']` directly
	 * and calls `wp_send_json_*()`/`wp_die()`, so it cannot be called as-is
	 * outside of an AJAX request.
	 *
	 * (Also considered reusing \WPFunnels\Admin\SetupWizard\ActivatePlugin,
	 * the bulk-activation helper behind the REST `activate_plugins` endpoint —
	 * its `activate_plugins()` expects an array of
	 * `{name, status, path, type, slug}` descriptors taken straight from
	 * `SetupWizard::get_essential_plugins()`, has no `file_exists()`/
	 * `is_plugin_active()` guards, and can't be driven cleanly from a single
	 * plugin-file-path argument, so this method mirrors the simpler AJAX
	 * handler instead of forcing that shape.)
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function activatePlugin( $input = [] ) {
		$plugin = isset( $input['plugin'] ) ? sanitize_text_field( $input['plugin'] ) : '';

		if ( '' === $plugin ) {
			return MCPHelper::error( 'missing_plugin', 'Provide the plugin file path, e.g. "woocommerce/woocommerce.php".' );
		}

		if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
		if ( ! file_exists( $plugin_file ) ) {
			return MCPHelper::error(
				'plugin_not_found',
				sprintf( 'Plugin file not found: %s. It must be installed before it can be activated.', $plugin )
			);
		}

		if ( is_plugin_active( $plugin ) ) {
			return [
				'success' => true,
				'plugin'  => $plugin,
				'message' => 'Plugin is already active.',
			];
		}

		$result = activate_plugin( $plugin, '', false, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Special handling for WooCommerce, mirroring the AJAX handler.
		if ( false !== strpos( $plugin, 'woocommerce' ) ) {
			delete_transient( '_wc_activation_redirect' );
		}

		// Special handling for Elementor, mirroring the AJAX handler.
		if ( false !== strpos( $plugin, 'elementor' ) ) {
			update_option( 'elementor_onboarded', true );
		}

		return [
			'success' => true,
			'plugin'  => $plugin,
			'message' => 'Plugin activated successfully.',
		];
	}

	/**
	 * Activate Mail Mint and run its first-run database setup.
	 *
	 * Reimplements the body of
	 * \WPFunnels\Admin\SetupWizard::ajax_activate_mail_mint() as a
	 * plain-argument callable, for the same reason as activatePlugin() above.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function activateMailMint( $input = [] ) {
		if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$mail_mint_plugin = 'mail-mint/mail-mint.php';
		$mail_mint_file   = WP_PLUGIN_DIR . '/' . $mail_mint_plugin;

		if ( ! is_plugin_active( $mail_mint_plugin ) ) {
			if ( ! file_exists( $mail_mint_file ) ) {
				return MCPHelper::error( 'plugin_not_found', 'Mail Mint is not installed. It must be installed before it can be activated.' );
			}

			$result = activate_plugin( $mail_mint_plugin, '', false, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Explicitly trigger Mail Mint's activation logic so its databases
		// exist even when core `activate_plugin()` alone didn't run it (same
		// PHP 7.4 safety net as the original AJAX handler).
		if ( file_exists( $mail_mint_file ) ) {
			$activator_file = WP_PLUGIN_DIR . '/mail-mint/includes/MrmActivator.php';
			if ( file_exists( $activator_file ) ) {
				require_once $activator_file;
				if ( class_exists( '\MrmActivator' ) ) {
					\MrmActivator::activate();
				}
			}

			delete_transient( 'mailmint_show_setup_wizard' );
		}

		return [
			'success' => true,
			'message' => 'Mail Mint activated and initialized successfully.',
		];
	}

	/**
	 * The recommended add-ons shown on the Add-ons admin page, with
	 * install/active status computed the same way that page computes it.
	 *
	 * Mirrors the `$addons` array built inline in
	 * admin/modules/addons/view.php (including the `wpfnl_recommended_addons`
	 * filter it runs through), minus the inline SVG icon markup, which has no
	 * use for an AI agent.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function listRecommendedAddons( $input = [] ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$addons = [
			[
				'title'       => __( 'Integrations', 'wpfnl' ),
				'description' => __( 'Connect WPFunnels with marketing tools, automate customer journey, sync contacts, and trigger campaigns — all without leaving your funnel.', 'wpfnl' ),
				'guide_link'  => 'https://getwpfunnels.com/docs/funnel-integrations/',
				'cta_link'    => 'https://getwpfunnels.com/pricing/',
				'plugin_file' => 'wpfunnels-pro-integrations/wpfunnels-pro-integrations.php',
			],
			[
				'title'       => __( 'Storewide Checkout (Global Funnel)', 'wpfnl' ),
				'description' => __( 'Apply a single funnel flow across your entire store and replace the default WooCommerce checkout - no need to create funnels individually.', 'wpfnl' ),
				'guide_link'  => 'https://getwpfunnels.com/docs/global-funnels-for-woocommerce/',
				'cta_link'    => 'https://getwpfunnels.com/pricing/',
				'plugin_file' => 'wpfunnels-pro/wpfnl-pro.php',
			],
			[
				'title'       => __( 'LMS', 'wpfnl' ),
				'description' => __( 'Seamlessly sell and deliver online courses, manage students, and automate access after purchase — all inside your funnel ecosystem.', 'wpfnl' ),
				'guide_link'  => 'https://getwpfunnels.com/docs/sales-funnels-for-courses/',
				'cta_link'    => 'https://getwpfunnels.com/pricing/',
				'plugin_file' => 'wpfunnels-pro-lms/wpfunnels-pro-lms.php',
			],
			[
				'title'       => __( 'Checkoutify', 'wpfnl' ),
				'description' => __( 'Use the most unique checkout field editor for WooCommerce to design and optimize your checkout form with full control.', 'wpfnl' ),
				'guide_link'  => 'https://getwpfunnels.com/docs/checkoutify-documentations-guides/',
				'cta_link'    => 'https://getwpfunnels.com/pricing/',
				'plugin_file' => 'checkoutify/checkoutify.php',
			],
		];

		/**
		 * Filters the recommended add-ons list.
		 *
		 * Same filter admin/modules/addons/view.php runs its own copy of this
		 * array through, so anything a third party (or WPFunnels Pro) adds to
		 * the admin page shows up here too.
		 *
		 * @since 3.14.0
		 * @param array $addons Recommended add-ons.
		 */
		$addons = apply_filters( 'wpfnl_recommended_addons', $addons );

		$items = [];
		foreach ( (array) $addons as $addon ) {
			$plugin_file  = isset( $addon['plugin_file'] ) ? $addon['plugin_file'] : '';
			$is_installed = false;
			$is_active    = false;

			if ( '' !== $plugin_file ) {
				$is_installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
				$is_active    = is_plugin_active( $plugin_file );
			}

			$items[] = [
				'title'        => isset( $addon['title'] ) ? $addon['title'] : '',
				'description'  => isset( $addon['description'] ) ? $addon['description'] : '',
				'plugin_file'  => $plugin_file,
				'guide_link'   => isset( $addon['guide_link'] ) ? $addon['guide_link'] : '',
				'cta_link'     => isset( $addon['cta_link'] ) ? $addon['cta_link'] : '',
				'is_installed' => $is_installed,
				'is_active'    => $is_active,
			];
		}

		return [
			'addons'       => $items,
			'addon_count'  => count( $items ),
		];
	}
}
