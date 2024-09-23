<?php
/**
 * Settings module
 *
 * @package
 */

namespace WPFunnels\Modules\Admin\Settings;

use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Rollback;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

class Module extends Wpfnl_Admin_Module
{
    use SingletonTrait;

    protected $validations;

    protected $prefix = '_wpfunnels_';

    protected $general_settings;

    protected $permalink_settings;

    protected $optin_settings;

    protected $offer_settings;

    protected $user_roles;

    protected $gtm_events;

    protected $gtm_settings;

    protected $facebook_pixel_events;

    protected $facebook_pixel_settings;

    protected $recaptcha_settings;

    protected $utm_params;

    protected $utm_settings;

    protected $is_allow_sales;

	/**
	 * User Roles Settings.
	 *
	 * @var array $user_roles_settings An associative array containing user roles settings.
	 */
    protected $user_roles_settings;


	/**
	 * Google Maps API Key.
	 *
	 * This property stores the API key used for accessing Google Maps services.
	 *
	 * @var string $google_map_api_key The Google Maps API key.
	 */
    protected $google_map_api_key;

    /**
	 * Page hooks
	 *
	 * @var array
	 */
	private $page_hooks = [
		'toplevel_page_wp_funnels',
		'wp-funnels_page_wp_funnel_settings',
		'wp-funnels_page_edit_funnel',
		'wp-funnels_page_create_funnel',
		'wp-funnels_page_wpfnl_settings',
		'wp-funnels_page_wpf-license',
		'wp-funnels_page_email-builder',
	];

    protected $settings_meta_keys = [
        '_wpfunnels_funnel_type' => 'sales',
        '_wpfunnels_builder' => 'elementor',
        '_wpfunnels_paypal_reference' => '',
        '_wpfunnels_order_bump' => '',
        '_wpfunnels_ab_testing' => '',
        '_wpfunnels_allow_funnels' => [
			'administrator' => true,
        ],
        '_wpfunnels_permalink_settings' => '',
        '_wpfunnels_optin_settings' => '',
        '_wpfunnels_permalink_step_base' => 'wpfunnels',
        '_wpfunnels_permalink_flow_base' => 'step',
        '_wpfunnels_set_permalink' => 'step',
    ];

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_init', [$this, 'after_permalink_settings_saved']);
		add_action( 'admin_post_wpfunnels_rollback', [ $this, 'post_wpfunnels_rollback' ] );
        $this->init_ajax();
    }

    public function is_wc_installed()
    {
        $path    = 'woocommerce/woocommerce.php';
        $plugins = get_plugins();

        return isset($plugins[ $path ]);
    }

    public function is_ff_installed()
    {
        $path    = 'fluentform/fluentform.php';
        $plugins = get_plugins();

        return isset($plugins[ $path ]);
    }

    public function is_elementor_installed()
    {
        $path    = 'elementor/elementor.php';
        $plugins = get_plugins();

        return isset($plugins[ $path ]);
    }


    public function enqueue_scripts()
    {
        wp_enqueue_script('settings', plugin_dir_url(__FILE__) . 'js/settings.js', ['jquery'], WPFNL_VERSION, true);
    }


    public function get_view()
    {
        $this->init_settings();
        $is_pro_activated   = Wpfnl_functions::is_wpfnl_pro_activated();
        $global_funnel_type = Wpfnl_functions::get_global_funnel_type();
        require_once WPFNL_DIR . '/admin/modules/settings/views/view.php';
    }

    /**
     * Init ajax hooks for
     * saving metas
     *
     * @since 1.0.0
     */
    public function init_ajax()
    {
        $this->validations = [
            'logged_in' => true,
            'user_can' => 'wpf_manage_funnels',
        ];
        wp_ajax_helper()->handle('update-general-settings')
            ->with_callback([ $this, 'update_general_settings' ])
            ->with_validation($this->validations);

        wp_ajax_helper()->handle('clear-templates')
            ->with_callback([ $this, 'clear_templates_data' ])
            ->with_validation($this->validations);

		wp_ajax_helper()->handle('clear-transient')
			->with_callback([ $this, 'clear_transient_cache_data' ])
			->with_validation($this->validations);

        wp_ajax_helper()->handle('wpfnl-show-log')
            ->with_callback([ $this, 'wpfnl_show_log' ])
            ->with_validation($this->validations);

        wp_ajax_helper()->handle('wpfnl-delete-log')
            ->with_callback([ $this, 'wpfnl_delete_log' ])
            ->with_validation($this->validations);
    }


    /**
     * Update handler for settings
     * page
     *
     * @param $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function update_general_settings($payload)
    {
        do_action('wpfunnels/before_settings_saved', $payload);

        $general_settings  = [
            'funnel_type'               => sanitize_text_field($payload['funnel_type']),
            'builder'                   => sanitize_text_field($payload['builder']),
            'uninstall_cleanup'         => sanitize_text_field($payload['uninstall_cleanup']),
            'disable_analytics'         => isset($payload['analytics_roles']) ? $payload['analytics_roles'] : '',
            'allow_funnels'             => isset($payload['permission_role']) ? $payload['permission_role'] : [],
            'paypal_reference'          => $payload['paypal_reference'],
            'order_bump'                => $payload['order_bump'],
            'ab_testing'                => $payload['ab_testing'],
            'disable_theme_style'       => $payload['disable_theme_style'],
            'enable_log_status'         => $payload['enable_log_status'],
            'enable_skip_cart'          => isset($payload['enable_skip_cart']) ? $payload['enable_skip_cart'] : 'no',
            'skip_cart_for'             => isset($payload['skip_cart_for']) ? $payload['skip_cart_for'] : 'whole',
        ];

        $permalink_settings = [
            'structure'             => sanitize_text_field($payload['permalink_settings']),
            'step_base'             => sanitize_text_field($payload['permalink_step_base']),
            'funnel_base'           => sanitize_text_field($payload['permalink_funnel_base']),
        ];
        
        if( isset($payload['sender_name']) && isset($payload['sender_email']) ){
            $optin_settings = [
                'sender_name'           => sanitize_text_field($payload['sender_name']),
                'sender_email'          => sanitize_text_field($payload['sender_email']),
                'email_subject'         => isset($payload['email_subject']) ? sanitize_text_field($payload['email_subject']) : '',
            ];
        }else{
            $optin_settings = Wpfnl_functions::get_optin_settings();
        }
       

        foreach ($payload as $key => $value) {
            switch ($key) {
                case 'funnel_type':
                case 'builder':
                    $cache_key = 'wpfunnels_remote_template_data_' . WPFNL_VERSION;
                    delete_transient($cache_key);
                    delete_option(WPFNL_TEMPLATES_OPTION_KEY);
                    break;
                case 'permalink_settings':
                    Wpfnl_functions::update_admin_settings($this->prefix.'permalink_saved', true);
                    break;
                case 'advanced_settings':
                    Wpfnl_functions::update_admin_settings($this->prefix.'advanced_settings', $value);
                    break;
                default:
                    break;
            }
        }

        Wpfnl_functions::update_admin_settings($this->prefix.'general_settings', $general_settings);
        Wpfnl_functions::update_admin_settings($this->prefix.'permalink_settings', $permalink_settings);
        Wpfnl_functions::update_admin_settings($this->prefix.'optin_settings', $optin_settings);
		$this->save_recaptcha_settings($payload);
		$this->save_user_role_management_data($payload);
		$this->save_google_map_key($payload);

        delete_option(WPFNL_TEMPLATES_OPTION_KEY.'_74');
        delete_option(WPFNL_TEMPLATES_OPTION_KEY.'_73');
        delete_option(WPFNL_TEMPLATES_OPTION_KEY.'_52');
        delete_option(WPFNL_TEMPLATES_OPTION_KEY.'_53');

        delete_transient('wpfunnels_remote_template_data_74_' . WPFNL_VERSION);
        delete_transient('wpfunnels_remote_template_data_73_' . WPFNL_VERSION);
        delete_transient('wpfunnels_remote_template_data_52_' . WPFNL_VERSION);
        delete_transient('wpfunnels_remote_template_data_53_' . WPFNL_VERSION);

        do_action('wpfunnels/after_settings_saved', $payload );
        return [
            'success' => true
        ];
    }

	/**
	 * Save Facebook pixel settings
	 *
	 * @param $settings
	 *
	 * @since 1.0.0
	 */
	public function save_recaptcha_settings( $settings ) {
		$enable_recapcha        = isset($settings['enable_recaptcha']) ? $settings['enable_recaptcha'] : 'no';
		$recapcha_site_key 		= isset($settings['recaptcha_site_key']) ? $settings['recaptcha_site_key'] : '';
		$recapcha_site_secret 	= isset($settings['recaptcha_site_secret']) ? $settings['recaptcha_site_secret'] : '';
		$recaptcha_settings 	= array(
			'enable_recaptcha'  => $enable_recapcha,
			'recaptcha_site_key'  => $recapcha_site_key,
			'recaptcha_site_secret' => $recapcha_site_secret
		);
		Wpfnl_functions::update_admin_settings('_wpfunnels_recaptcha_setting', $recaptcha_settings );
	}


	/**
	 * Save user role management data
	 *
	 * @param $payload
	 *
	 * @since 3.1.4
	 */
	public function save_user_role_management_data($payload) {
		if ( !isset($payload['user_role_management']) ) {
			return;
		}

		$roles 				= wp_unslash( $payload['user_role_management']['roles'] );
		$sanitized_settings = array();
		foreach ( $roles as $key => $value ) {
			$sanitized_settings[ $key ] = ( isset( $roles[ $key ] ) ) ? sanitize_text_field( $value ) : '';
		}
		Wpfnl_functions::update_admin_settings('_wpfunnels_user_roles', $sanitized_settings );

		$this->update_user_role_management( $sanitized_settings );
	}


	/**
	 * Update user role management data
	 *
	 * @param $sanitized_settings
	 *
	 * @since 3.1.4
	 */
	private function update_user_role_management( $sanitized_settings ) {

		foreach ( $sanitized_settings as $user_role => $value ) {
			$user_role_obj = get_role( $user_role );
			if ( $user_role_obj ) {
				if ( 'yes' === $value ) {
					$user_role_obj->add_cap('wpf_manage_funnels' );
				} elseif ( 'no' === $value ) {
					$user_role_obj->remove_cap('wpf_manage_funnels' );
				}
			}
		}
	}



	/**
	 * Save google map api key settings key
	 *
	 *  @param $payload
	 *
	 * @since 3.1.3
	 */
	public function save_google_map_key( $payload ) {
		if ( isset($payload['google_map_api_key']) ) {
			Wpfnl_functions::update_admin_settings('_wpfunnels_google_map_api_key', $payload['google_map_api_key'] );
		}
	}


    /**
     * Initialize all the settings value
     *
     * @since 1.0.0
     */
    public function init_settings()
    {
        $this->general_settings     = Wpfnl_functions::get_general_settings();
        $this->is_allow_sales       = Wpfnl_functions::maybe_allow_sales_funnel();
        $this->permalink_settings   = Wpfnl_functions::get_permalink_settings();
        $this->optin_settings       = Wpfnl_functions::get_optin_settings();
        $this->offer_settings       = Wpfnl_functions::get_offer_settings();
        $this->user_roles           = Wpfnl_functions::get_user_roles();
        $this->gtm_events           = Wpfnl_functions::get_gtm_events();
        $this->gtm_settings         = Wpfnl_functions::get_gtm_settings();
        $this->facebook_pixel_events    = Wpfnl_functions::get_facebook_events();
        $this->facebook_pixel_settings  = Wpfnl_functions::get_facebook_pixel_settings();
        $this->utm_params           = Wpfnl_functions::get_utm_params();
        $this->utm_settings         = Wpfnl_functions::get_utm_settings();
        $this->recaptcha_settings   = Wpfnl_functions::get_recaptcha_settings();
        $this->user_roles_settings 	= Wpfnl_functions::get_user_role_settings();
        $this->google_map_api_key	= Wpfnl_functions::get_google_map_api_key();
    }




    /**
     * Clear saved templates data
     *
     * @param $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function clear_templates_data($payload) {

        delete_option(WPFNL_TEMPLATES_OPTION_KEY.'_wc');
        delete_option(WPFNL_TEMPLATES_OPTION_KEY.'_lms');


        delete_transient('wpfunnels_remote_template_data_wc_' . WPFNL_VERSION);
        delete_transient('wpfunnels_remote_template_data_lms_' . WPFNL_VERSION);
        delete_transient('wpfunnels_remote_template_data_lead_' . WPFNL_VERSION);
        return [
            'success' => true
        ];
    }


	/**
     * Clear transient
     *
	 * @param $payload
     *
	 * @return array
	 */
    public function clear_transient_cache_data($payload) {

		delete_transient('wpfunnels_remote_template_data_' . WPFNL_VERSION);
        delete_transient('wpfunnels_remote_template_data_wc_' . WPFNL_VERSION);
        delete_transient('wpfunnels_remote_template_data_lms_' . WPFNL_VERSION);
        delete_transient('wpfunnels_remote_template_data_lead_' . WPFNL_VERSION);
		delete_transient('wpfunnels_rollback_versions_' . WPFNL_VERSION);
		do_action('wpfunnels/after_clear_transient');
		return array(
			'success' => true
		);
	}


    /**
     * After settings saved hooks
     *
     * @since 1.0.0
     */
    public function after_permalink_settings_saved()
    {

        if( Wpfnl_functions::maybe_funnel_page() ){
            $is_permalink_saved = get_option('_wpfunnels_permalink_saved');
            if ($is_permalink_saved) {
                flush_rewrite_rules();
                delete_option('_wpfunnels_permalink_saved');
            }
        }
    }


    /**
     * Get settings by meta key
     *
     * @param $key
     *
     * @return mixed|string
     * @since  1.0.0
     */
    public function get_settings_by_key($key)
    {
        return isset($this->settings_meta_keys[$key]) ? $this->settings_meta_keys[$key]: '';
    }

    public function get_name()
    {
        return __('settings','wpfnl');
    }


	/**
	 * Get rollback version of WPF
	 *
	 * @return array|mixed
	 * @since  2.3.0
	 */
    public function get_roll_back_versions() {
		$rollback_versions = get_transient( 'wpfunnels_rollback_versions_' . WPFNL_VERSION );
		if ( false === $rollback_versions ) {
			$max_versions = 10;
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			$plugin_information = plugins_api(
				'plugin_information', [
					'slug' => 'wpfunnels',
				]
			);
			if ( empty( $plugin_information->versions ) || ! is_array( $plugin_information->versions ) ) {
				return [];
			}

			natsort( $plugin_information->versions );
			$plugin_information->versions = array_reverse($plugin_information->versions);
			$rollback_versions = [];

			$current_index = 0;
			foreach ( $plugin_information->versions as $version => $download_link ) {
				if ( $max_versions <= $current_index ) {
					break;
				}

				$lowercase_version = strtolower( $version );
				$is_valid_rollback_version = ! preg_match( '/(trunk|beta|rc|dev)/i', $lowercase_version );

				/**
				 * Is rollback version is valid.
				 *
				 * Filters the check whether the rollback version is valid.
				 *
				 * @param bool $is_valid_rollback_version Whether the rollback version is valid.
				 */
				$is_valid_rollback_version = apply_filters(
					'wpfunnels/is_valid_rollback_version',
					$is_valid_rollback_version,
					$lowercase_version
				);

				if ( ! $is_valid_rollback_version ) {
					continue;
				}

				if ( version_compare( $version, WPFNL_VERSION, '>=' ) ) {
					continue;
				}

				$current_index++;
				$rollback_versions[] = $version;
			}

			set_transient( 'wpfunnels_rollback_versions_' . WPFNL_VERSION, $rollback_versions, DAY_IN_SECONDS );
		}

		return $rollback_versions;
	}


	public function post_wpfunnels_rollback() {
		check_admin_referer( 'wpfunnels_rollback' );

		$rollback_versions = $this->get_roll_back_versions();
		if ( empty( $_GET['version'] ) || ! in_array( $_GET['version'], $rollback_versions ) ) {
			wp_die( esc_html__( 'Error occurred, The version selected is invalid. Try selecting different version.', 'wpfnl' ) );
		}

		$plugin_slug = basename( 'wpfunnels', '.php' );

		$rollback = new Rollback(
			[
				'version' => $_GET['version'],
				'plugin_name' => WPFNL_BASE,
				'plugin_slug' => $plugin_slug,
				'package_url' => sprintf( 'https://downloads.wordpress.org/plugin/%1s.%2s.zip', $plugin_slug, $_GET['version'] ),
			]
		);

		$rollback->run();

		wp_die(
			'', esc_html__( 'Rollback to Previous Version', 'wpfnl' ), [
				'response' => 200,
			]
		);
	}


    /**
     * WPFunnels log
     *
     * @param $payload
     *
     * @return array
     */
    public static function wpfnl_show_log( $payload ) {
        if( isset($payload['logKey']) ){
            $key = $payload['logKey'];
            $upload_dir = wp_upload_dir( null, false );
            $log_url = $upload_dir['basedir'].'/wpfunnels/wpfunnels-logs/';
            $file_url = $log_url  . $key;

            ob_start();
            include_once $file_url;
            $out = ob_get_clean();
            ob_end_clean();

            return array(
                'success' => true,
                'content' => $out,
                'file_url' => $log_url. $key
            );
        }

        return array(
            'success' => false,
            'content' => '',
            'file_url' => ''
        );


    }


    /**
     * WPFunnels log
     *
     * @param $payload
     *
     * @return array
     */
    public static function wpfnl_delete_log( $payload ) {
        if( isset($payload['logKey']) ){
            $key = $payload['logKey'];
            $upload_dir = wp_upload_dir( null, false );
            $file_name = $upload_dir['basedir'].'/wpfunnels/wpfunnels-logs/'.$key;

            $response = \Wpfnl_Logger::delete_log_file( $file_name );
            if( $response ){
                return array(
                    'success' => true,
                );
            }

        }

        return array(
            'success' => false,
            'content' => '',
            'file_url' => ''
        );
    }
}
