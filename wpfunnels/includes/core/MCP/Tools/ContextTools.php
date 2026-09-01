<?php
/**
 * ContextTools — discovery and setup abilities.
 *
 * `get-funnel-context` is the first call any agent should make: it reports what
 * this install actually supports so the model never proposes a step type,
 * builder or offer this site cannot build.
 *
 * @package WPFunnels\MCP
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Rest\Controllers\SettingsController;

/**
 * Class ContextTools
 */
class ContextTools {

	/**
	 * Transient holding the cached context payload.
	 */
	private const CACHE_KEY = '_wpfnl_mcp_context_cache';

	/**
	 * Cache lifetime in seconds. Short: funnels change while an agent works.
	 */
	private const CACHE_TTL = 60;

	/**
	 * WPFunnels-owned option keys the generic settings mechanism may read/write.
	 *
	 * This is a verbatim copy of the explicit allow-list inside
	 * `SettingsController::is_allowed_settings_group()`. That method is a
	 * security boundary: group_id is passed straight into
	 * get_option()/update_option(), so an unguarded value could target
	 * arbitrary WordPress options (e.g. `wp_user_roles`). The source method is
	 * `protected` with no legitimate inheritance path from this class, so the
	 * list is duplicated rather than called into — keep it byte-for-byte in
	 * sync with the controller if that list ever changes.
	 *
	 * @see \WPFunnels\Rest\Controllers\SettingsController::is_allowed_settings_group()
	 */
	private const ALLOWED_SETTINGS_GROUPS = [
		'_wpfunnels_general_settings',
		'_wpfunnels_permalink_settings',
		'_wpfunnels_optin_settings',
		'_wpfunnels_advanced_settings',
		'_wpfunnels_integrations',
		'_wpfunnels_notification_settings',
		'_wpfunnels_lms_settings',
		'_wpfunnels_recaptcha_setting',
		'_wpfunnels_google_map_api_key',
		'_wpfunnels_user_roles',
	];

	/**
	 * Ability definitions for this domain.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/get-funnel-context' => [
				'label'               => __( 'Get Funnel Context', 'wpfnl' ),
				'description'         => 'Snapshot of this WPFunnels install: plugin versions, licence tier, active page builder, WooCommerce and Mail Mint availability, currency, funnel counts by type, supported step types, and the guidelines for building funnels here. Call this FIRST in any session — it tells you what this site can actually do.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'execute_callback'    => [ __CLASS__, 'getFunnelContext' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/get-site-design-tokens' => [
				'label'               => __( 'Get Site Design Tokens', 'wpfnl' ),
				'description'         => 'The site\'s Elementor design tokens (currently just the kit\'s primary brand color, if Elementor is active). compose-step-layout already applies this automatically to the widgets it builds — call this only to answer a direct question about the site\'s brand color, not before composing a page.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'execute_callback'    => [ __CLASS__, 'getSiteDesignTokens' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/check-setup-status' => [
				'label'               => __( 'Check Setup Status', 'wpfnl' ),
				'description'         => 'Audit whether this install is ready to run funnels: setup wizard completion, page builder availability, WooCommerce state, permalink structure, global settings and licence. Returns a list of issues with the admin URL to fix each one.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'execute_callback'    => [ __CLASS__, 'checkSetupStatus' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/update-global-settings' => [
				'label'               => __( 'Update Global Settings', 'wpfnl' ),
				'description'         => 'Update WPFunnels\' site-wide settings (default page builder, order bump enable, A/B testing enable, default funnel type, funnel builder mode, and similar toggles from Settings → General). Only recognized keys are written; unknown keys are ignored. This is a merge, not a full replace — existing settings not mentioned are left alone.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'settings' => [
							'type'        => 'object',
							'description' => 'Key/value pairs matching the keys returned by get-funnel-context\'s settings section (e.g. builder, order_bump, ab_testing, funnel_type, funnel_builder_mode).',
						],
					],
					'required'   => [ 'settings' ],
				],
				'execute_callback'    => [ __CLASS__, 'updateGlobalSettings' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/update-permalink-settings' => [
				'label'               => __( 'Update Permalink Settings', 'wpfnl' ),
				'description'         => 'Update WPFunnels\' funnel/step URL structure: the permalink style and the URL base segments used for funnel and step pages. Changes site URL structure — every existing funnel/step link changes if the base segments change.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'structure'   => [
							'type'        => 'string',
							'description' => 'Permalink style for funnel/step URLs.',
						],
						'funnel_base' => [
							'type'        => 'string',
							'description' => 'URL base segment for funnels.',
						],
						'step_base'   => [
							'type'        => 'string',
							'description' => 'URL base segment for steps.',
						],
					],
				],
				'execute_callback'    => [ __CLASS__, 'updatePermalinkSettings' ],
				'permission_callback' => MCPHelper::currentUserCan( 'manage_options' ),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/get-settings-group' => [
				'label'               => __( 'Get Settings Group', 'wpfnl' ),
				'description'         => 'Read one WPFunnels site-wide settings group by its option-group ID. Covers tabs with no dedicated ability: _wpfunnels_optin_settings (global opt-in email defaults — sender_name, sender_email, email_subject), _wpfunnels_lms_settings (lms_provider and other LMS defaults), _wpfunnels_notification_settings (revenue report emails — enable_revenue_report, revenue_report_frequency, revenue_report_recipient, revenue_report_subject, send_time), _wpfunnels_recaptcha_setting (enable_recaptcha, recaptcha_site_key, recaptcha_site_secret), _wpfunnels_advanced_settings (show_supported_payment_gateway), _wpfunnels_google_map_api_key (a plain string, returned as {value: "..."}), _wpfunnels_user_roles (map of role slug to "yes"/"no" funnel-management access), and _wpfunnels_integrations (reserved for a future integrations tab; not currently populated by core). _wpfunnels_general_settings and _wpfunnels_permalink_settings also work here but get-funnel-context / update-global-settings / update-permalink-settings are the better-typed way to read/write those. Only WPFunnels-owned _wpfunnels_* option keys are accepted; anything else is rejected.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'group_id' => [
							'type'        => 'string',
							'description' => 'Settings group / option key to read, e.g. _wpfunnels_optin_settings, _wpfunnels_notification_settings, _wpfunnels_recaptcha_setting, _wpfunnels_lms_settings, _wpfunnels_advanced_settings, _wpfunnels_google_map_api_key, _wpfunnels_user_roles, _wpfunnels_integrations, _wpfunnels_general_settings, _wpfunnels_permalink_settings.',
						],
					],
					'required'   => [ 'group_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'getSettingsGroup' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/update-settings-group' => [
				'label'               => __( 'Update Settings Group', 'wpfnl' ),
				'description'         => 'Update one or more keys in a WPFunnels site-wide settings group (see get-settings-group for the field names each group holds). This is a merge into the existing group, applied one key at a time — a later key failing does not roll back keys already applied. Only WPFunnels-owned _wpfunnels_* option keys are accepted, the same allow-list get-settings-group uses; anything else is rejected. Changes here are site-wide and can affect every funnel (e.g. disabling reCAPTCHA, changing revenue report recipients, changing the LMS provider) — use narrower abilities like update-global-settings or update-permalink-settings when they already cover the field you need.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'group_id' => [
							'type'        => 'string',
							'description' => 'Settings group / option key to update. See get-settings-group for the allowed groups and their fields.',
						],
						'settings' => [
							'type'        => 'object',
							'description' => 'Key/value pairs to merge into this group, e.g. {"enable_recaptcha": "yes", "recaptcha_site_key": "..."}. For the scalar _wpfunnels_google_map_api_key group, pass {"value": "..."}.',
						],
					],
					'required'   => [ 'group_id', 'settings' ],
				],
				'execute_callback'    => [ __CLASS__, 'updateSettingsGroup' ],
				'permission_callback' => MCPHelper::currentUserCan( 'manage_options' ),
				'annotations'         => [ 'destructive' ],
			],
		];
	}

	/**
	 * Invalidate the cached context. Wired to funnel/step mutations.
	 *
	 * @return void
	 */
	public static function invalidateCache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Build (or serve cached) install context.
	 *
	 * @return array
	 */
	public static function getFunnelContext() {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$general  = Wpfnl_functions::get_general_settings();
		$wc       = Wpfnl_functions::is_wc_active();
		$pro      = Wpfnl_functions::is_wpfnl_pro_activated();
		$counts   = self::funnelCounts();
		$builder  = Wpfnl_functions::get_builder_type();

		$context = [
			'site'         => [
				'name'      => get_bloginfo( 'name' ),
				'url'       => home_url(),
				'admin_url' => admin_url(),
				'locale'    => get_locale(),
				'timezone'  => wp_timezone_string(),
				'today'     => current_time( 'Y-m-d' ),
			],
			'plugin'       => [
				'version'     => defined( 'WPFNL_VERSION' ) ? WPFNL_VERSION : '',
				'pro_active'  => $pro,
				'pro_version' => defined( 'WPFNL_PRO_VERSION' ) ? WPFNL_PRO_VERSION : '',
			],
			'capabilities' => [
				'page_builder'      => $builder ? $builder : 'gutenberg',
				'elementor_active'  => Wpfnl_functions::is_elementor_active(),
				'woocommerce'       => $wc,
				'currency'          => $wc && function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
				'currency_symbol'   => $wc && function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '',
				'mail_mint'         => class_exists( '\Mint\MRM\Internal\AI\AIInit' ) || defined( 'MAIL_MINT_VERSION' ),
				'lms'              => Wpfnl_functions::is_lms_addon_active(),
				'supported_step_types' => MCPHelper::supportedStepTypes(),
				'offer_steps'       => $wc && $pro,
				'order_bump'        => $wc,
				'ab_testing'        => $pro,
				'analytics'         => $pro,
			],
			'funnels'      => $counts,
			'settings'     => [
				'default_funnel_type' => isset( $general['funnel_type'] ) ? $general['funnel_type'] : '',
				'permalink_base'      => isset( $general['permalink'] ) ? $general['permalink'] : '',
				'funnel_builder_mode' => isset( $general['funnel_builder_mode'] ) ? $general['funnel_builder_mode'] : 'horizontal',
			],
			'guidelines'   => self::guidelines( $wc, $pro ),
		];

		set_transient( self::CACHE_KEY, $context, self::CACHE_TTL );

		return $context;
	}

	/**
	 * The site's Elementor design tokens.
	 *
	 * @return array
	 */
	public static function getSiteDesignTokens() {
		return MCPHelper::getSiteDesignTokens();
	}

	/**
	 * Setup audit with actionable links.
	 *
	 * @return array
	 */
	public static function checkSetupStatus() {
		$issues   = [];
		$general  = Wpfnl_functions::get_general_settings();
		$wc       = Wpfnl_functions::is_wc_active();

		if ( ! $wc && ! Wpfnl_functions::is_lms_addon_active() ) {
			$issues[] = [
				'severity' => 'warning',
				'issue'    => 'WooCommerce is not active, so checkout, order bump and offer steps are unavailable.',
				'fix'      => 'Install and activate WooCommerce, or build lead-generation funnels instead.',
				'url'      => admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ),
			];
		}

		if ( ! get_option( 'permalink_structure' ) ) {
			$issues[] = [
				'severity' => 'error',
				'issue'    => 'Plain permalinks are enabled; funnel step URLs will not resolve.',
				'fix'      => 'Switch to any pretty permalink structure.',
				'url'      => admin_url( 'options-permalink.php' ),
			];
		}

		if ( empty( $general['builder'] ) ) {
			$issues[] = [
				'severity' => 'warning',
				'issue'    => 'No page builder is selected, so new steps fall back to the block editor.',
				'fix'      => 'Pick a page builder in WPFunnels settings.',
				'url'      => admin_url( 'admin.php?page=' . ( defined( 'WPFNL_GLOBAL_SETTINGS_SLUG' ) ? WPFNL_GLOBAL_SETTINGS_SLUG : 'wpfnl_settings' ) ),
			];
		}

		if ( ! Wpfnl_functions::is_wpfnl_pro_activated() ) {
			$issues[] = [
				'severity' => 'info',
				'issue'    => 'WPFunnels Pro is not active: A/B testing, analytics, one-click upsells and CRM integrations are unavailable.',
				'fix'      => 'Activate WPFunnels Pro to unlock those tools.',
				'url'      => admin_url( 'admin.php?page=wpf-license' ),
			];
		}

		$counts = self::funnelCounts();
		if ( 0 === $counts['total'] ) {
			$issues[] = [
				'severity' => 'info',
				'issue'    => 'No funnels exist yet.',
				'fix'      => 'Create one with wpfunnels/create-funnel.',
				'url'      => admin_url( 'admin.php?page=' . ( defined( 'WPFNL_FUNNEL_PAGE_SLUG' ) ? WPFNL_FUNNEL_PAGE_SLUG : 'wp_funnels' ) ),
			];
		}

		return [
			'ready'  => empty(
				array_filter(
					$issues,
					static function ( $issue ) {
						return 'error' === $issue['severity'];
					}
				)
			),
			'issues' => $issues,
		];
	}

	/**
	 * Merge changes into WPFunnels' general settings option.
	 *
	 * Only keys already present in `get_general_settings()`'s defaults are
	 * writable — this is the same option the Settings → General screen reads
	 * and writes, so an unrecognized key here would just be dead weight in
	 * the option, never surfaced anywhere.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function updateGlobalSettings( $input = [] ) {
		$changes = isset( $input['settings'] ) && is_array( $input['settings'] ) ? $input['settings'] : [];
		if ( empty( $changes ) ) {
			return MCPHelper::error( 'missing_settings', 'Provide at least one setting to update.' );
		}

		$current = Wpfnl_functions::get_general_settings();
		$allowed = array_intersect_key( $changes, $current );

		if ( empty( $allowed ) ) {
			return MCPHelper::error(
				'no_recognized_keys',
				'None of the given keys are recognized general settings.',
				[ 'recognized_keys' => array_keys( $current ) ]
			);
		}

		$updated = array_merge( $current, $allowed );
		Wpfnl_functions::update_admin_settings( '_wpfunnels_general_settings', $updated );

		self::invalidateCache();

		return [
			'success' => true,
			'changed' => array_keys( $allowed ),
			'settings' => $updated,
		];
	}

	/**
	 * Merge changes into WPFunnels' funnel/step permalink settings.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function updatePermalinkSettings( $input = [] ) {
		$changes = array_intersect_key( $input, array_flip( [ 'structure', 'funnel_base', 'step_base' ] ) );
		if ( empty( $changes ) ) {
			return MCPHelper::error( 'missing_settings', 'Provide at least one of structure, funnel_base, step_base.' );
		}

		$current = Wpfnl_functions::get_permalink_settings();
		$updated = array_merge( $current, array_map( 'sanitize_text_field', $changes ) );

		Wpfnl_functions::update_admin_settings( '_wpfunnels_permalink_settings', $updated );
		Wpfnl_functions::update_admin_settings( '_wpfunnels_permalink_saved', true );
		flush_rewrite_rules();

		self::invalidateCache();

		return [
			'success'  => true,
			'changed'  => array_keys( $changes ),
			'settings' => $updated,
		];
	}

	/**
	 * Read one allow-listed WPFunnels settings group.
	 *
	 * Reuses `SettingsController::get_group_settings()` for the actual read
	 * (including its own internal allow-list check) so this stays byte-for-byte
	 * consistent with what the Settings screens themselves read.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getSettingsGroup( $input = [] ) {
		$group_id = isset( $input['group_id'] ) ? sanitize_text_field( $input['group_id'] ) : '';

		if ( ! self::isAllowedSettingsGroup( $group_id ) ) {
			return MCPHelper::error(
				'invalid_settings_group',
				'group_id is not a recognized WPFunnels settings group.',
				[ 'allowed_groups' => self::ALLOWED_SETTINGS_GROUPS ]
			);
		}

		$controller = new SettingsController();
		$result     = $controller->get_group_settings( $group_id );

		if ( is_wp_error( $result ) ) {
			return MCPHelper::error( 'settings_group_error', $result->get_error_message() );
		}

		if ( empty( $result ) ) {
			// get_group_settings() only flattens array-shaped options; a scalar
			// option (currently only _wpfunnels_google_map_api_key) foreach()es
			// to nothing there, so fall back to the raw stored value.
			$raw = Wpfnl_functions::get_admin_settings( $group_id );
			if ( ! empty( $raw ) && ! is_array( $raw ) ) {
				return [
					'group_id' => $group_id,
					'settings' => [ 'value' => $raw ],
				];
			}

			return [
				'group_id' => $group_id,
				'settings' => [],
			];
		}

		$settings = [];
		foreach ( $result as $entry ) {
			$settings[ $entry['id'] ] = $entry['value'];
		}

		return [
			'group_id' => $group_id,
			'settings' => $settings,
		];
	}

	/**
	 * Merge changes into one allow-listed WPFunnels settings group.
	 *
	 * Reuses `SettingsController::update_settings()` for the actual write, one
	 * key at a time (that method is built around updating a single named
	 * setting per call). `type` is forced to `ignore_activation` so this never
	 * triggers the controller's plugin-activation side effect, which is meant
	 * for the setup wizard's own flow, not a generic settings write.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function updateSettingsGroup( $input = [] ) {
		$group_id = isset( $input['group_id'] ) ? sanitize_text_field( $input['group_id'] ) : '';

		if ( ! self::isAllowedSettingsGroup( $group_id ) ) {
			return MCPHelper::error(
				'invalid_settings_group',
				'group_id is not a recognized WPFunnels settings group.',
				[ 'allowed_groups' => self::ALLOWED_SETTINGS_GROUPS ]
			);
		}

		$settings = isset( $input['settings'] ) ? $input['settings'] : null;
		if ( ! is_array( $settings ) || empty( $settings ) ) {
			return MCPHelper::error( 'missing_settings', 'Provide a settings object with at least one key to update.' );
		}

		// _wpfunnels_google_map_api_key stores a plain string, not a keyed
		// array. update_settings() assumes every group is an associative array
		// and would corrupt a string option via `$settings['x'] = ...` (illegal
		// string offset), so this one group is written directly instead of
		// being looped through that method.
		if ( '_wpfunnels_google_map_api_key' === $group_id ) {
			$value = isset( $settings['value'] ) ? $settings['value'] : reset( $settings );
			Wpfnl_functions::update_admin_settings( $group_id, sanitize_text_field( $value ) );

			self::invalidateCache();

			return [
				'success'  => true,
				'group_id' => $group_id,
				'changed'  => [ 'value' ],
				'settings' => [ 'value' => Wpfnl_functions::get_admin_settings( $group_id, '' ) ],
			];
		}

		$controller = new SettingsController();
		$changed    = [];

		foreach ( $settings as $key => $value ) {
			$key = sanitize_text_field( $key );

			$result = $controller->update_settings(
				[
					'group_id'    => $group_id,
					'settings_id' => $key,
					'value'       => $value,
					'type'        => 'ignore_activation',
					'slug'        => '',
					'permission'  => '',
				]
			);

			if ( is_wp_error( $result ) ) {
				return MCPHelper::error(
					'update_failed',
					$result->get_error_message(),
					[
						'failed_key'             => $key,
						'changed_before_failure' => $changed,
					]
				);
			}

			$changed[] = $key;
		}

		self::invalidateCache();

		$fresh = self::getSettingsGroup( [ 'group_id' => $group_id ] );

		return [
			'success'  => true,
			'group_id' => $group_id,
			'changed'  => $changed,
			'settings' => is_wp_error( $fresh ) ? null : $fresh['settings'],
		];
	}

	/**
	 * Re-check of SettingsController::is_allowed_settings_group(): the same
	 * explicit allow-list, plus the same `_wpfunnels_` prefix fallback so
	 * add-on-registered groups keep working. See ALLOWED_SETTINGS_GROUPS for
	 * why this is a duplicate rather than a call into the real method.
	 *
	 * @param string $group_id Candidate option key.
	 * @return bool
	 */
	private static function isAllowedSettingsGroup( $group_id ) {
		if ( ! is_string( $group_id ) || '' === $group_id ) {
			return false;
		}

		if ( in_array( $group_id, self::ALLOWED_SETTINGS_GROUPS, true ) ) {
			return true;
		}

		return 0 === strpos( $group_id, '_wpfunnels_' );
	}

	/**
	 * Funnel counts by type and status.
	 *
	 * @return array
	 */
	private static function funnelCounts() {
		$counts = [
			'total'          => 0,
			'published'      => 0,
			'draft'          => 0,
			'by_type'        => [],
			'store_checkout' => 0,
		];

		$funnels = get_posts(
			[
				'post_type'   => WPFNL_FUNNELS_POST_TYPE,
				'post_status' => [ 'publish', 'draft' ],
				'numberposts' => -1,
				'fields'      => 'ids',
			]
		);

		foreach ( $funnels as $funnel_id ) {
			$counts['total']++;

			if ( 'publish' === get_post_status( $funnel_id ) ) {
				$counts['published']++;
			} else {
				$counts['draft']++;
			}

			$type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true ) ?: 'wc';
			if ( 'store_checkout' === $type ) {
				$counts['store_checkout']++;
			}

			$counts['by_type'][ $type ] = isset( $counts['by_type'][ $type ] ) ? $counts['by_type'][ $type ] + 1 : 1;
		}

		return $counts;
	}

	/**
	 * Build guidelines, trimmed to what this install supports.
	 *
	 * @param bool $wc  WooCommerce active.
	 * @param bool $pro Pro active.
	 * @return array
	 */
	private static function guidelines( $wc, $pro ) {
		$lines = [
			'Resolve names to IDs before writing: use list-funnels, list-steps and search-products. Never invent an ID.',
			'Build order: create-funnel, then create-step per step, then assign-products-to-step on checkout/upsell/downsell steps.',
			'Funnel types are wc (WooCommerce sales), lms, lead (opt-in) and store_checkout. A funnel keeps one type.',
			'A funnel has at most one landing, one checkout and one thank-you step. Canonical order is landing, checkout, upsell, downsell, thank-you.',
			'Products can only be attached to checkout, upsell and downsell steps.',
		];

		if ( ! $wc ) {
			$lines[] = 'WooCommerce is inactive here: build lead funnels with landing, opt-in and thank-you steps only.';
		}
		if ( ! $pro ) {
			$lines[] = 'Pro is inactive here: one-click upsell/downsell, A/B testing and analytics tools are unavailable. Say so plainly rather than pretending.';
		}

		return $lines;
	}
}
