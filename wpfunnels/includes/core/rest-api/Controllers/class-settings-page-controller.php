<?php
/**
 * REST controller for the settings screen (`?page=wpfunnels#/settings`).
 *
 * The screen is React now, and everything it reads or writes goes through here.
 * Nothing on the server moved: saving hands the same payload the legacy
 * `update-general-settings` admin-ajax action received to the very same handler
 * (Settings Module::update_general_settings), so every hook that listens on
 * `wpfunnels/before_settings_saved` and `wpfunnels/after_settings_saved` — the
 * Pro offer, GTM, Facebook Pixel and UTM writers among them — still fires
 * exactly as before. The admin-ajax actions stay registered too, so anything
 * else that posts to them keeps working.
 *
 * @package WPFunnels\Rest\Controllers
 */

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsPageController
 *
 * @package WPFunnels\Rest\Controllers
 */
class SettingsPageController extends Wpfnl_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wpfunnels/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/page';


	/**
	 * Whether the current user may read the settings screen.
	 *
	 * @return bool|WP_Error
	 * @since  3.13.8
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'settings' ) ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_view',
				__( 'Sorry, you cannot list resources.', 'wpfnl' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}


	/**
	 * Whether the current user may save the settings screen.
	 *
	 * @return bool|WP_Error
	 * @since  3.13.8
	 */
	public function update_items_permissions_check( $request ) {
		if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'settings' ) ) {
			return new WP_Error(
				'wpfunnels_rest_cannot_edit',
				__( 'Sorry, you cannot edit this resource.', 'wpfnl' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}


	/**
	 * Register the routes the settings screen talks to.
	 *
	 * @return void
	 * @since  3.13.8
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/clear-templates',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clear_templates' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/clear-transients',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clear_transients' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/logs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_log' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'file' => array(
							'description' => __( 'Log file name.', 'wpfnl' ),
							'type'        => 'string',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_log' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => array(
						'file' => array(
							'description' => __( 'Log file name.', 'wpfnl' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/test-notification',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_test_notification' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
				),
			)
		);
	}


	/**
	 * The settings module, which still owns every write this screen makes.
	 *
	 * @return \WPFunnels\Modules\Admin\Settings\Module|null
	 * @since  3.13.8
	 */
	private function get_settings_module() {
		if ( ! class_exists( '\WPFunnels\Modules\Admin\Settings\Module' ) ) {
			return null;
		}

		return \WPFunnels\Modules\Admin\Settings\Module::instance();
	}


	/**
	 * Everything the settings screen renders from.
	 *
	 * One request rather than one per panel: the screen saves every panel at
	 * once, so it has to hold every panel's values anyway — a field the reader
	 * never opened still has to be sent back untouched.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 * @since  3.13.8
	 */
	public function get_settings( $request ) {
		$module = $this->get_settings_module();

		$general   = Wpfnl_functions::get_general_settings();
		$permalink = Wpfnl_functions::get_permalink_settings();
		$optin     = Wpfnl_functions::get_optin_settings();
		$offer     = Wpfnl_functions::get_offer_settings();
		$recaptcha = Wpfnl_functions::get_recaptcha_settings();
		$gtm       = Wpfnl_functions::get_gtm_settings();
		$pixel     = Wpfnl_functions::get_facebook_pixel_settings();

		$is_pro_activated      = Wpfnl_functions::is_wpfnl_pro_activated();
		$is_pro_license_active = (bool) apply_filters( 'wpfunnels/is_pro_license_activated', false );

		return rest_ensure_response(
			array(
				'success'      => true,
				'general'      => $general,
				'permalink'    => $permalink,
				'optin'        => $optin,
				'offer'        => $offer,
				'recaptcha'    => $recaptcha,
				'gtm'          => $gtm,
				'pixel'        => $pixel,
				'notification' => Wpfnl_functions::get_notification_settings(),
				'userRoles'    => Wpfnl_functions::get_user_role_settings(),
				'googleMapKey' => Wpfnl_functions::get_google_map_api_key(),
				'lms'          => $this->get_lms_data(),
				'options'      => array(
					'builders'         => Wpfnl_functions::get_supported_builders(),
					'canvasMode'       => Wpfnl_functions::get_default_funnel_builder_mode(),
					'gtmEvents'        => Wpfnl_functions::get_gtm_events(),
					'pixelEvents'      => Wpfnl_functions::get_facebook_events(),
					'analyticsRoles'   => array_values( Wpfnl_functions::get_user_roles() ),
					'rollbackVersions' => $module ? array_values( (array) $module->get_roll_back_versions() ) : array(),
					/*
					 * Reinstalling is an admin-post action, not a setting, so the
					 * screen follows this URL with the chosen version swapped in
					 * for the VERSION placeholder — the nonce has to be minted
					 * here, which is exactly what the legacy view did.
					 */
					'rollbackUrl'      => wp_nonce_url( admin_url( 'admin-post.php?action=wpfunnels_rollback&version=VERSION' ), 'wpfunnels_rollback' ),
					'logFiles'         => $this->get_log_files(),
					'homeUrl'          => home_url(),
					'adminEmail'       => get_option( 'admin_email' ),
					'version'          => WPFNL_VERSION,
					'imagePath'        => WPFNL_URL . 'admin/assets/images',
					'defaultStepBase'  => WPFNL_STEPS_POST_TYPE,
					'defaultFunnelBase' => WPFNL_FUNNELS_POST_TYPE,
					'proUrl'           => add_query_arg( 'wpfnl-dashboard', '1', GETWPFUNNEL_PRICING_URL ),
					'pricingUrl'       => GETWPFUNNEL_PRICING_URL,
				),
				'capabilities' => array(
					'isWcActive'          => Wpfnl_functions::is_wc_active(),
					'isLmsAddonActive'    => Wpfnl_functions::is_lms_addon_active(),
					'isProActivated'      => (bool) $is_pro_activated,
					'isProLicenseActive'  => $is_pro_license_active,
					'isGlobalFunnel'      => (bool) Wpfnl_functions::is_global_funnel_activated(),
					'canManageOptions'    => current_user_can( 'manage_options' ),
					// The analytics panel is behind both the Pro plugin running
					// and the same filter the legacy view checked.
					'isAnalyticsVisible'  => (bool) apply_filters( 'wpfunnels/is_wpfnl_pro_active', false ),
					'trackingConsent'     => $this->get_tracking_consent(),
				),
			)
		);
	}


	/**
	 * Save the whole screen.
	 *
	 * The payload is handed to the settings module unchanged — same keys, same
	 * handler, same hooks — so this route is a transport change and nothing
	 * more.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since  3.13.8
	 */
	public function save_settings( $request ) {
		$module = $this->get_settings_module();

		if ( ! $module ) {
			return new WP_Error(
				'wpfunnels_settings_unavailable',
				__( 'Settings module is not available.', 'wpfnl' ),
				array( 'status' => 500 )
			);
		}

		$payload = $request->get_json_params();

		if ( empty( $payload ) || ! is_array( $payload ) ) {
			$payload = $request->get_params();
		}

		// `builder` is read unguarded by the handler, and an empty one would
		// wipe the site's page builder — fall back to what is stored.
		if ( empty( $payload['builder'] ) ) {
			$general             = Wpfnl_functions::get_general_settings();
			$payload['builder']  = isset( $general['builder'] ) ? $general['builder'] : 'gutenberg';
		}

		$module->update_general_settings( $payload );

		/*
		 * Permalink changes only take effect once the rewrite rules are flushed,
		 * which admin_init does on the next page load (see
		 * Module::after_permalink_settings_saved). The React screen never
		 * reloads, so flush here as well — the option the handler set is what
		 * both paths key on, and deleting it keeps the admin_init pass a no-op.
		 */
		if ( get_option( '_wpfunnels_permalink_saved' ) ) {
			flush_rewrite_rules();
			delete_option( '_wpfunnels_permalink_saved' );
		}

		$response       = $this->get_settings( $request );
		$data           = $response->get_data();
		$data['saved']  = true;

		return rest_ensure_response( $data );
	}


	/**
	 * Drop the cached template listings so the next one is fetched fresh.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since  3.13.8
	 */
	public function clear_templates( $request ) {
		$module = $this->get_settings_module();

		if ( ! $module ) {
			return new WP_Error(
				'wpfunnels_settings_unavailable',
				__( 'Settings module is not available.', 'wpfnl' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( $module->clear_templates_data( array() ) );
	}


	/**
	 * Clear every transient WPFunnels owns.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since  3.13.8
	 */
	public function clear_transients( $request ) {
		$module = $this->get_settings_module();

		if ( ! $module ) {
			return new WP_Error(
				'wpfunnels_settings_unavailable',
				__( 'Settings module is not available.', 'wpfnl' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( $module->clear_transient_cache_data( array() ) );
	}


	/**
	 * Read one log file.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 * @since  3.13.8
	 */
	public function get_log( $request ) {
		$file = $request->get_param( 'file' );

		if ( empty( $file ) ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'files'   => $this->get_log_files(),
					'content' => '',
				)
			);
		}

		$result          = \WPFunnels\Modules\Admin\Settings\Module::wpfnl_show_log( array( 'logKey' => $file ) );
		$result['files'] = $this->get_log_files();

		return rest_ensure_response( $result );
	}


	/**
	 * Delete one log file.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 * @since  3.13.8
	 */
	public function delete_log( $request ) {
		$file = $request->get_param( 'file' );

		$result          = \WPFunnels\Modules\Admin\Settings\Module::wpfnl_delete_log( array( 'logKey' => $file ) );
		$result['files'] = $this->get_log_files();

		return rest_ensure_response( $result );
	}


	/**
	 * Send the revenue report to one address, as a test.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since  3.13.8
	 */
	public function send_test_notification( $request ) {
		$module = $this->get_settings_module();

		if ( ! $module ) {
			return new WP_Error(
				'wpfunnels_settings_unavailable',
				__( 'Settings module is not available.', 'wpfnl' ),
				array( 'status' => 500 )
			);
		}

		$payload = $request->get_json_params();

		if ( empty( $payload ) || ! is_array( $payload ) ) {
			$payload = $request->get_params();
		}

		$email = isset( $payload['email'] ) ? sanitize_email( $payload['email'] ) : '';

		return rest_ensure_response( $module->send_test_notification( array( 'email' => $email ) ) );
	}


	/**
	 * Log files, as the screen's select renders them.
	 *
	 * The value stays the real file name; the label drops the site-specific
	 * suffix, exactly as the screen this replaced did.
	 *
	 * @return array List of `{ value, label }`.
	 * @since  3.13.8
	 */
	private function get_log_files() {
		if ( ! class_exists( '\Wpfnl_Logger' ) ) {
			return array();
		}

		$files  = \Wpfnl_Logger::get_log_files();
		$suffix = \Wpfnl_Logger::get_log_file_suffix();
		$result = array();

		foreach ( (array) $files as $file ) {
			$result[] = array(
				'value' => $file,
				'label' => str_replace( '-' . $suffix, '', $file ),
			);
		}

		return $result;
	}


	/**
	 * Available LMS providers and the one in use.
	 *
	 * @return array
	 * @since  3.13.8
	 */
	private function get_lms_data() {
		$providers = array();
		$selected  = '';

		if ( class_exists( '\WPFunnels\lms\settings\LMS_Settings_Helper' ) ) {
			$providers = \WPFunnels\lms\settings\LMS_Settings_Helper::get_available_lms_providers();
			$selected  = \WPFunnels\lms\settings\LMS_Settings_Helper::get_selected_lms_provider();
		}

		$list = array();

		foreach ( (array) $providers as $id => $provider ) {
			$list[] = array(
				'value' => $id,
				'label' => isset( $provider['name'] ) ? $provider['name'] : $id,
			);
		}

		return array(
			'providers' => $list,
			'selected'  => $selected,
		);
	}


	/**
	 * Usage-tracking consent as the Advanced panel renders it.
	 *
	 * A site that has never answered the setup-wizard question reads as 'no',
	 * which is also what the legacy view rendered — the difference between
	 * "undecided" and "opted out" is kept by the screen only sending the toggle
	 * when it actually changed.
	 *
	 * @return string 'yes' or 'no'.
	 * @since  3.13.8
	 */
	private function get_tracking_consent() {
		if ( ! class_exists( '\WPFunnels\Tracking\Telemetry' ) ) {
			return 'no';
		}

		$telemetry = \WPFunnels\Tracking\Telemetry::instance();

		return ( $telemetry && 'yes' === $telemetry->get_consent_state() ) ? 'yes' : 'no';
	}
}
