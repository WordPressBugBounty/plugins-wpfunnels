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
	 * Notification Settings.
	 *
	 * @var array $notification_settings An associative array containing notification settings.
	 */
    protected $notification_settings;


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
		'wpfunnels_page_email-builder',
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
		add_action( 'wpfunnels_send_revenue_report_email', [ $this, 'send_revenue_report_email' ] );
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

        wp_ajax_helper()->handle('wpfnl-send-test-notification')
            ->with_callback([ $this, 'send_test_notification' ])
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
		$this->save_notification_settings($payload);

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
	 * Save notification settings
	 *
	 * @param $payload
	 *
	 * @since 3.2.0
	 */
	public function save_notification_settings( $payload ) {
		$old_settings = Wpfnl_functions::get_notification_settings();
		$old_enabled = isset($old_settings['enable_revenue_report']) ? $old_settings['enable_revenue_report'] : 'no';
		
		$notification_settings = array(
			'enable_revenue_report' => isset($payload['enable_revenue_report']) ? sanitize_text_field($payload['enable_revenue_report']) : 'no',
			'revenue_report_frequency' => isset($payload['revenue_report_frequency']) ? sanitize_text_field($payload['revenue_report_frequency']) : 'weekly',
			'revenue_report_recipient' => isset($payload['revenue_report_recipient']) ? sanitize_text_field($payload['revenue_report_recipient']) : get_option('admin_email'),
			'revenue_report_subject' => isset($payload['revenue_report_subject']) ? sanitize_text_field($payload['revenue_report_subject']) : 'Store Revenue Report - {period}',
			'send_time' => isset($payload['send_time']) ? sanitize_text_field($payload['send_time']) : '10:00',
		);
		
		Wpfnl_functions::update_admin_settings('_wpfunnels_notification_settings', $notification_settings );
		
		$new_enabled = $notification_settings['enable_revenue_report'];
		
		// Handle action scheduler based on enable/disable
		if ( $new_enabled === 'yes' && $old_enabled !== 'yes' ) {
			// Enabled: Schedule the action
			$this->schedule_revenue_report_email( $notification_settings );
		} elseif ( $new_enabled !== 'yes' && $old_enabled === 'yes' ) {
			// Disabled: Remove the scheduled action
			$this->unschedule_revenue_report_email();
		} elseif ( $new_enabled === 'yes' ) {
			// Already enabled but settings changed: Reschedule
			$this->unschedule_revenue_report_email();
			$this->schedule_revenue_report_email( $notification_settings );
		}
	}


	/**
	 * Schedule revenue report email using Action Scheduler
	 *
	 * @param array $settings
	 *
	 * @since 3.2.0
	 */
	private function schedule_revenue_report_email( $settings ) {
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}
		
		// Calculate next run time based on send_time
		$send_time = isset( $settings['send_time'] ) ? $settings['send_time'] : '10:00';
		$frequency = isset( $settings['revenue_report_frequency'] ) ? $settings['revenue_report_frequency'] : 'weekly';
		
		// Parse time (format: HH:MM)
		list( $hour, $minute ) = explode( ':', $send_time );
		
		// Get current time
		$now = current_time( 'timestamp' );
		$today = strtotime( date( 'Y-m-d', $now ) );
		
		// Calculate first run time (today at specified time)
		$first_run = $today + ( $hour * HOUR_IN_SECONDS ) + ( $minute * MINUTE_IN_SECONDS );
		
		// If the time has already passed today, schedule for tomorrow
		if ( $first_run <= $now ) {
			$first_run += DAY_IN_SECONDS;
		}
		
		// Calculate interval based on frequency
		$interval = ( $frequency === 'monthly' ) ? MONTH_IN_SECONDS : WEEK_IN_SECONDS;
		
		// Schedule recurring action
		as_schedule_recurring_action( 
			$first_run, 
			$interval, 
			'wpfunnels_send_revenue_report_email', 
			array(), 
			'wpfunnels' 
		);
	}


	/**
	 * Unschedule revenue report email
	 *
	 * @since 3.2.0
	 */
	private function unschedule_revenue_report_email() {
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			return;
		}
		
		as_unschedule_all_actions( 'wpfunnels_send_revenue_report_email', array(), 'wpfunnels' );
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
        $this->notification_settings = Wpfnl_functions::get_notification_settings();
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
	 * Send test notification email
	 *
	 * @param $payload
	 *
	 * @return array
	 * @since 3.2.0
	 */
	public function send_test_notification( $payload ) {
		if ( ! isset( $payload['email'] ) || ! is_email( $payload['email'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please provide a valid email address.', 'wpfnl' )
			);
		}

		$settings = Wpfnl_functions::get_notification_settings();
		$subject_template = isset( $settings['revenue_report_subject'] ) ? $settings['revenue_report_subject'] : 'Store Revenue Report - {period}';
		
		// Calculate date range for test email (last week Monday to Sunday)
		$start_date = date( 'Y-m-d', strtotime( 'last monday -1 week' ) );
		$end_date = date( 'Y-m-d', strtotime( 'last sunday' ) );
		$date_range = date( 'F j, Y', strtotime( $start_date ) ) . ' - ' . date( 'F j, Y', strtotime( $end_date ) );
		
		$subject = '[Test] ' . str_replace( '{period}', $date_range, $subject_template );
		
		$to = sanitize_email( $payload['email'] );
		$message = $this->get_revenue_report_email_content( 'weekly' );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $to, $subject, $message, $headers );

		if ( $sent ) {
			return array(
				'success' => true,
				'message' => __( 'Test email sent successfully! Please check your inbox.', 'wpfnl' )
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to send test email. Please check your email configuration.', 'wpfnl' )
			);
		}
	}


	/**
	 * Send revenue report email (triggered by Action Scheduler)
	 *
	 * @since 3.2.0
	 */
	public function send_revenue_report_email() {
		$settings = Wpfnl_functions::get_notification_settings();
		
		// Check if still enabled
		if ( ! isset( $settings['enable_revenue_report'] ) || $settings['enable_revenue_report'] !== 'yes' ) {
			return;
		}
		
		$recipients = isset( $settings['revenue_report_recipient'] ) ? $settings['revenue_report_recipient'] : get_option('admin_email');
		$subject = isset( $settings['revenue_report_subject'] ) ? $settings['revenue_report_subject'] : 'Store Revenue Report - {period}';
		$frequency = isset( $settings['revenue_report_frequency'] ) ? $settings['revenue_report_frequency'] : 'weekly';
		
		// Calculate date range based on frequency
		if ( $frequency === 'monthly' ) {
			$start_date = date( 'Y-m-d', strtotime( 'first day of last month' ) );
			$end_date = date( 'Y-m-d', strtotime( 'last day of last month' ) );
		} else {
			// Weekly: Last week Monday to Sunday
			$start_date = date( 'Y-m-d', strtotime( 'last monday -1 week' ) );
			$end_date = date( 'Y-m-d', strtotime( 'last sunday' ) );
		}
		
		$date_range = date( 'F j, Y', strtotime( $start_date ) ) . ' - ' . date( 'F j, Y', strtotime( $end_date ) );
		
		// Replace {period} placeholder with date range
		$subject = str_replace( '{period}', $date_range, $subject );
		
		// Generate report content
		$message = $this->get_revenue_report_email_content( $frequency );
		
		// Prepare headers
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		
		// Send email to recipients (can be multiple, comma-separated)
		$recipient_array = array_map( 'trim', explode( ',', $recipients ) );
		
		foreach ( $recipient_array as $recipient ) {
			if ( is_email( $recipient ) ) {
				wp_mail( sanitize_email( $recipient ), $subject, $message, $headers );
			}
		}
	}


	/**
	 * Get revenue report email content with actual data
	 *
	 * @param string $frequency
	 * @return string
	 * @since 3.2.0
	 */
	private function get_revenue_report_email_content( $frequency = 'weekly' ) {
		$site_name = get_bloginfo( 'name' );
		$site_url = home_url();
		
		// Calculate date range
		if ( $frequency === 'monthly' ) {
			$start_date = date( 'Y-m-d', strtotime( 'first day of last month' ) );
			$end_date = date( 'Y-m-d', strtotime( 'last day of last month' ) );
			$period_text = __( 'Monthly', 'wpfnl' );
		} else {
			// Weekly: Last week Monday to Sunday
			$start_date = date( 'Y-m-d', strtotime( 'last monday -1 week' ) );
			$end_date = date( 'Y-m-d', strtotime( 'last sunday' ) );
			$period_text = __( 'Weekly', 'wpfnl' );
		}
		
		$date_range = date( 'F j, Y', strtotime( $start_date ) ) . ' - ' . date( 'F j, Y', strtotime( $end_date ) );
		
		// Calculate previous period for comparison
		if ( $frequency === 'monthly' ) {
			$prev_start_date = date( 'Y-m-d', strtotime( 'first day of -2 months' ) );
			$prev_end_date = date( 'Y-m-d', strtotime( 'last day of -2 months' ) );
		} else {
			$prev_start_date = date( 'Y-m-d', strtotime( 'last monday -2 weeks' ) );
			$prev_end_date = date( 'Y-m-d', strtotime( 'last sunday -1 week' ) );
		}
		
		// Get revenue data for current and previous periods
		$revenue_data = $this->get_revenue_data( $start_date, $end_date );
		$previous_data = $this->get_revenue_data( $prev_start_date, $prev_end_date );
		
		// Calculate percentage changes
		$revenue_data['total_orders_change'] = $this->calculate_percentage_change( $previous_data['total_orders'], $revenue_data['total_orders'] );
		$revenue_data['total_customers_change'] = $this->calculate_percentage_change( $previous_data['total_customers'], $revenue_data['total_customers'] );
		$revenue_data['total_revenue_change'] = $this->calculate_percentage_change( $previous_data['total_revenue_raw'], $revenue_data['total_revenue_raw'] );
		$revenue_data['average_order_value_change'] = $this->calculate_percentage_change( $previous_data['average_order_value_raw'], $revenue_data['average_order_value_raw'] );
		
		// Build email with FunnelKit-style design
		$message = '<!DOCTYPE html>';
		$message .= '<html lang="en">';
		$message .= '<head>';
		$message .= '<meta charset="UTF-8">';
		$message .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
		$message .= '<title>Performance Report</title>';
		$message .= '</head>';
		$message .= '<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">';
		
		// Main container
		$message .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f5f5f5; padding: 40px 20px;">';
		$message .= '<tr><td align="center">';
		$message .= '<table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
		
		// Top orange banner with text
		$message .= '<tr><td style="background-color: #6e42d3; padding: 12px 40px; text-align: center;">';
		$message .= '<div style="font-size: 16px; font-weight: 600; color: #ffffff;">Your ' . esc_html( strtolower( $frequency ) ) . ' summary from WPFunnels.</div>';
		$message .= '</td></tr>';
		
		// WPFunnels logo section
		$message .= '<tr><td style="background-color: #ffffff; padding: 20px 40px 20px; text-align: center;">';
		$message .= '<svg width="38" height="28" viewBox="0 0 38 28" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; margin-right: 10px; vertical-align: middle;">';
		$message .= '<path d="M7.01532 18H31.9847L34 11H5L7.01532 18Z" fill="#EE8134"/>';
		$message .= '<path d="M11.9621 27.2975C12.0923 27.7154 12.4792 28 12.9169 28H26.0831C26.5208 28 26.9077 27.7154 27.0379 27.2975L29 21H10L11.9621 27.2975Z" fill="#6E42D3"/>';
		$message .= '<path d="M37.8161 0.65986C37.61 0.247888 37.2609 0 36.8867 0H1.11326C0.739128 0 0.390003 0.247888 0.183972 0.65986C-0.0220592 1.07193 -0.0573873 1.59277 0.0898627 2.04655L1.69781 7H36.3022L37.9102 2.04655C38.0574 1.59287 38.022 1.07193 37.8161 0.65986Z" fill="#6E42D3"/>';
		$message .= '</svg>';
		$message .= '<span style="font-size: 32px; font-weight: bold; color: #231748; vertical-align: middle;">WPFunnels</span>';
		$message .= '</td></tr>';
		
		// Date range, Performance Report title, and View Button - all in same background
		$message .= '<tr><td style="padding: 20px 40px 30px; text-align: center; background-color: #f8f9fa;">';
		// Date range
		$message .= '<div style="font-size: 14px; color: #6c757d; font-weight: 500; margin-bottom: 20px;">' . esc_html( $date_range ) . '</div>';
		// Performance Report title
		$message .= '<h1 style="margin: 0 0 10px; font-size: 32px; font-weight: bold; color: #212529;">Store Revenue Summary</h1>';
		$message .= '<p style="margin: 0 0 20px; font-size: 14px; color: #6c757d;">Track your store performance and revenue metrics for this period</p>';
		// View Dashboard Button
		$message .= '<a href="' . esc_url( admin_url( 'admin.php?page=wpfunnels' ) ) . '" style="display: inline-block; background-color: #6e42d3; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-size: 14px; font-weight: 600;">View Dashboard</a>';
		$message .= '</td></tr>';
		
		// Key Performance Metrics section
		$message .= '<tr><td style="padding: 20px 40px;">';
		$message .= '<h2 style="margin: 0 0 10px; font-size: 24px; font-weight: bold; color: #212529; text-align: center;">Revenue Overview</h2>';
		$message .= '<p style="margin: 0 0 30px; font-size: 14px; color: #6c757d; text-align: center;">Your ' . esc_html( strtolower( $frequency ) ) . ' performance snapshot</p>';
		
		// Metrics grid
		$message .= '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
		
		// Row 1: Total orders and Total contacts
		$message .= '<tr>';
		$message .= '<td width="50%" style="padding: 20px; vertical-align: top;">';
		$message .= '<div style="text-align: left;">';
		$message .= '<div style="font-size: 14px; color: #6c757d; margin-bottom: 10px; font-weight: 500;">Total orders</div>';
		$message .= '<div style="font-size: 36px; font-weight: bold; color: #212529; margin-bottom: 5px;">' . esc_html( $revenue_data['total_orders'] ) . '</div>';
		$message .= $revenue_data['total_orders_change'];
		$message .= '</div>';
		$message .= '</td>';
		$message .= '<td width="50%" style="padding: 20px; vertical-align: top;">';
		$message .= '<div style="text-align: left;">';
		$message .= '<div style="font-size: 14px; color: #6c757d; margin-bottom: 10px; font-weight: 500;">Total customers</div>';
		$message .= '<div style="font-size: 36px; font-weight: bold; color: #212529; margin-bottom: 5px;">' . esc_html( $revenue_data['total_customers'] ) . '</div>';
		$message .= $revenue_data['total_customers_change'];
		$message .= '</div>';
		$message .= '</td>';
		$message .= '</tr>';
		
		// Row 2: Revenue and Bump revenue
		$message .= '<tr>';
		$message .= '<td width="50%" style="padding: 20px; vertical-align: top;">';
		$message .= '<div style="text-align: left;">';
		$message .= '<div style="font-size: 14px; color: #6c757d; margin-bottom: 10px; font-weight: 500;">Revenue</div>';
		$message .= '<div style="font-size: 36px; font-weight: bold; color: #212529; margin-bottom: 5px;">' . wp_strip_all_tags( $revenue_data['total_revenue'] ) . '</div>';
		$message .= $revenue_data['total_revenue_change'];
		$message .= '</div>';
		$message .= '</td>';
		$message .= '<td width="50%" style="padding: 20px; vertical-align: top;">';
		$message .= '<div style="text-align: left;">';
		$message .= '<div style="font-size: 14px; color: #6c757d; margin-bottom: 10px; font-weight: 500;">Bump revenue</div>';
		if ( Wpfnl_functions::is_wpfnl_pro_activated() ) {
			$message .= '<div style="font-size: 36px; font-weight: bold; color: #212529; margin-bottom: 5px;">0 <span style="font-size: 18px; font-weight: normal; color: #6c757d;">USD</span></div>';
			$message .= '<div style="font-size: 12px; color: #6c757d;">0% - Previous period</div>';
		} else {
			$message .= '<div style="font-size: 36px; margin-bottom: 5px;"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 11V7C17 4.79086 15.2091 3 13 3H11C8.79086 3 7 4.79086 7 7V11M12 14V16M8 11H16C17.1046 11 18 11.8954 18 13V19C18 20.1046 17.1046 21 16 21H8C6.89543 21 6 20.1046 6 19V13C6 11.8954 6.89543 11 8 11Z" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>';
			$message .= '<div style="font-size: 12px; color: #6c757d;">WPFunnels Pro</div>';
		}
		$message .= '</div>';
		$message .= '</td>';
		$message .= '</tr>';
		
		// Row 3: Upsell revenue and Average order value
		$message .= '<tr>';
		$message .= '<td width="50%" style="padding: 20px; vertical-align: top;">';
		$message .= '<div style="text-align: left;">';
		$message .= '<div style="font-size: 14px; color: #6c757d; margin-bottom: 10px; font-weight: 500;">Upsell revenue</div>';
		if ( Wpfnl_functions::is_wpfnl_pro_activated() ) {
			$message .= '<div style="font-size: 36px; font-weight: bold; color: #212529; margin-bottom: 5px;">0 <span style="font-size: 18px; font-weight: normal; color: #6c757d;">USD</span></div>';
			$message .= '<div style="font-size: 12px; color: #6c757d;">0% - Previous period</div>';
		} else {
			$message .= '<div style="font-size: 36px; margin-bottom: 5px;"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 11V7C17 4.79086 15.2091 3 13 3H11C8.79086 3 7 4.79086 7 7V11M12 14V16M8 11H16C17.1046 11 18 11.8954 18 13V19C18 20.1046 17.1046 21 16 21H8C6.89543 21 6 20.1046 6 19V13C6 11.8954 6.89543 11 8 11Z" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>';
			$message .= '<div style="font-size: 12px; color: #6c757d;">WPFunnels Pro</div>';
		}
		$message .= '</div>';
		$message .= '</td>';
		$message .= '<td width="50%" style="padding: 20px; vertical-align: top;">';
		$message .= '<div style="text-align: left;">';
		$message .= '<div style="font-size: 14px; color: #6c757d; margin-bottom: 10px; font-weight: 500;">Average order value</div>';
		$message .= '<div style="font-size: 36px; font-weight: bold; color: #212529; margin-bottom: 5px;">' . wp_strip_all_tags( $revenue_data['average_order_value'] ) . '</div>';
		$message .= $revenue_data['average_order_value_change'];
		$message .= '</div>';
		$message .= '</td>';
		$message .= '</tr>';
		
		$message .= '</table>';
		$message .= '</td></tr>';
		
		// Additional revenue info
		if ( isset( $revenue_data['total_revenue_raw'] ) && $revenue_data['total_revenue_raw'] > 0 ) {
			$message .= '<tr><td style="padding: 20px 40px;">';
			$message .= '<div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px;">';
			$message .= '<p style="margin: 0; font-size: 14px; color: #856404;">Since installing WPFunnels you have captured additional revenue of <strong>' . wp_kses_post( wc_price( $revenue_data['total_revenue_raw'] ) ) . '</strong></p>';
			$message .= '</div>';
			$message .= '</td></tr>';
		}
		
		// Footer
		$message .= '<tr><td style="padding: 30px 40px; text-align: center; background-color: #ffffff; border-top: 1px solid #e9ecef;">';
		$message .= '<p style="margin: 0 0 10px; font-size: 12px; color: #6c757d;">This performance report email was sent from the <a href="' . esc_url( $site_url ) . '" style="color: #007bff; text-decoration: none;">' . esc_html( $site_name ) . '</a> by admin ' . esc_html( date( 'F j, Y' ) ) . '</p>';
		$message .= '<p style="margin: 0; font-size: 12px; color: #6c757d;"><a href="' . esc_url( admin_url( 'admin.php?page=wpfnl_settings#notifications-settings' ) ) . '" style="color: #007bff; text-decoration: none;">Click here</a> to change the frequency and recipients.</p>';
		$message .= '</td></tr>';
		
		$message .= '</table>';
		$message .= '</td></tr>';
		$message .= '</table>';
		
		$message .= '</body>';
		$message .= '</html>';

		return $message;
	}


	/**
	 * Get revenue data from WPFunnels stats
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return array
	 * @since 3.2.0
	 */
	private function get_revenue_data( $start_date, $end_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wpfnl_stats';
		
		// Get total orders count
		$total_orders = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(id) FROM $table WHERE date_created >= %s AND date_created <= %s AND status = 'completed'",
			$start_date,
			$end_date . ' 23:59:59'
		) );
		
		// Get total revenue (total_sales includes everything: checkout + order bump + upsell + downsell)
		$total_revenue_raw = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(total_sales) FROM $table WHERE date_created >= %s AND date_created <= %s AND status = 'completed'",
			$start_date,
			$end_date . ' 23:59:59'
		) );
		
		// Get total customers (unique customer_id)
		$total_customers = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT customer_id) FROM $table WHERE date_created >= %s AND date_created <= %s AND status = 'completed'",
			$start_date,
			$end_date . ' 23:59:59'
		) );
		
		// Calculate average order value
		$total_orders_int = intval( $total_orders );
		$total_revenue_float = floatval( $total_revenue_raw );
		$average = $total_orders_int > 0 ? $total_revenue_float / $total_orders_int : 0;
		
		// Format data
		$data = array(
			'total_revenue' => function_exists( 'wc_price' ) ? wc_price( $total_revenue_float ) : '$' . number_format( $total_revenue_float, 2 ),
			'total_revenue_raw' => $total_revenue_float,
			'total_orders' => $total_orders_int,
			'total_customers' => intval( $total_customers ),
			'average_order_value' => function_exists( 'wc_price' ) ? wc_price( $average ) : '$' . number_format( $average, 2 ),
			'average_order_value_raw' => $average,
		);
		
		return $data;
	}


	/**
	 * Calculate percentage change between two values
	 *
	 * @param float $old_value
	 * @param float $new_value
	 * @return string HTML formatted percentage change
	 * @since 3.2.0
	 */
	private function calculate_percentage_change( $old_value, $new_value ) {
		$old_value = floatval( $old_value );
		$new_value = floatval( $new_value );
		
		if ( $old_value == 0 ) {
			if ( $new_value > 0 ) {
				return '<div style="font-size: 12px; color: #28a745;">+100% - Previous period</div>';
			} else {
				return '<div style="font-size: 12px; color: #6c757d;">0% - Previous period</div>';
			}
		}
		
		$change = ( ( $new_value - $old_value ) / $old_value ) * 100;
		$change_formatted = number_format( abs( $change ), 1 );
		
		if ( $change > 0 ) {
			return '<div style="font-size: 12px; color: #28a745;">+' . $change_formatted . '% - Previous period</div>';
		} elseif ( $change < 0 ) {
			return '<div style="font-size: 12px; color: #dc3545;">-' . $change_formatted . '% - Previous period</div>';
		} else {
			return '<div style="font-size: 12px; color: #6c757d;">0% - Previous period</div>';
		}
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
            $key        = sanitize_file_name( basename( $payload['logKey'] ) );
            $upload_dir = wp_upload_dir( null, false );
            $log_dir    = $upload_dir['basedir'].'/wpfunnels/wpfunnels-logs/';
            $file_name  = $log_dir . $key;

            // Verify the resolved path is within the log directory
            $real_file    = realpath($file_name);
            $real_log_dir = realpath($log_dir);
            
            if ( $real_file && $real_log_dir && strpos( $real_file, $real_log_dir ) === 0 ) {
                $response = \Wpfnl_Logger::delete_log_file( $file_name );
                if( $response ){
                    return array('success' => true);
                }
            }
        }

        return array(
            'success' => false,
            'content' => '',
            'file_url' => ''
        );
    }
}
