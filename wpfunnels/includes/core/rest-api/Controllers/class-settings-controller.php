<?php
/**
 * Settings controller
 *
 * @package WPFunnels\Rest\Controllers
 */
namespace WPFunnels\Rest\Controllers;

use GuzzleHttp\Psr7\Request;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnels\Wpfnl;
use Elementor\Core\Kits\Manager;
class SettingsController extends Wpfnl_REST_Controller {

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
    protected $rest_base = 'settings/(?P<group_id>[\w-]+)';


    protected $rest_wc_plugin_active_base = 'settings/activate_wc_plugins';
    protected $rest_mrm_plugin_active_base = 'settings/activate_mrm_plugins';

    protected $rest_funnel_settings = 'settings';

    public function update_items_permissions_check( $request ) {
        $permission = current_user_can('wpf_manage_funnels');
        if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'settings' ) ) {
            return new WP_Error( 'wpfunnels_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|boolean
     * @since  3.0.0
     */
    public function get_items_permissions_check( $request ) {
        if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'settings' ) ) {
            return new WP_Error( 'wpfunnels_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }

    public function register_routes()
    {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/(?P<settings_id>[\w-]+)', array(
                'args'   => array(
                    'settings_id' => array(
                        'description'       => __( 'Settings group ID.', 'wpfnl' ),
                        'type'              => 'string',
                    )
                ),
                // array(
                //     'methods'               => WP_REST_Server::READABLE,
                //     'callback'              => array( $this, 'get_item' ),
                //     'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                // ),
                array(
                    'methods'               => WP_REST_Server::EDITABLE,
                    'callback'              => array( $this, 'update_settings' ),
                    'permission_callback' => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_wc_plugin_active_base , array(
                array(
                    'methods'               => WP_REST_Server::EDITABLE,
                    'callback'              => array( $this, 'activate_wc_plugins' ),
                    'permission_callback'   => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_mrm_plugin_active_base , array(
                array(
                    'methods'               => WP_REST_Server::EDITABLE,
                    'callback'              => array( $this, 'activate_mrm_plugins' ),
                    'permission_callback'   => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );


        register_rest_route(
            $this->namespace, '/' . $this->rest_funnel_settings . '/is-lms/(?P<funnel_id>[\w-]+)' , array(
                array(
                    'methods'               => 'GET',
                    'callback'              => array( $this, 'is_lms' ),
                    'permission_callback'   => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );
        
        register_rest_route(
            $this->namespace, '/' . $this->rest_funnel_settings . '/activate-plugins' , array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'activate_plugins' ),
                    'permission_callback'   => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );
        
        register_rest_route(
            $this->namespace, '/' . $this->rest_funnel_settings . '/create-contact' , array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'create_contact' ),
                    'permission_callback'   => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );
        
        register_rest_route(
            $this->namespace, '/' . $this->rest_funnel_settings . '/save-setup-wizard-settings' , array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'save_setup_wizard_settings' ),
                    'permission_callback'   => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_funnel_settings . '/update-funnel-type' , array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'update_funnel_type' ),
                    'permission_callback'   => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_funnel_settings . '/update-builder-type' , array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'update_builder_type' ),
                    'permission_callback'   => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_funnel_settings . '/get-funnel-type-id/(?P<funnel_id>[\w-]+)' , array(
                array(
                    'methods'               => 'GET',
                    'callback'              => array( $this, 'get_funnel_type_id' ),
                    'permission_callback'   => array( $this, 'update_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                )
            )
        );

    }


    /**
     * Get all settings in a group.
     *
     * @param string $group_id Group ID.
     *
     * @return array|WP_Error
     */
    public function get_group_settings( $group_id ) {
        if ( empty( $group_id ) ) {
            return new WP_Error( 'rest_setting_setting_group_invalid', __( 'Invalid setting group.', 'wpfnl' ), array( 'status' => 404 ) );
        }
        $settings = Wpfnl_functions::get_admin_settings( $group_id );
        if ( empty( $settings ) ) {
            return new WP_Error( 'rest_setting_setting_group_invalid', __( 'Invalid setting group.', 'wpfnl' ), array( 'status' => 404 ) );
        }

        $filtered_settings = array();
        foreach ($settings as $key => $setting) {
            $filtered_settings[] = array(
                'id'    => $key,
                'value' => $setting
            );
        }
        return $filtered_settings;
    }


    /**
     * Get setting data.
     *
     * @param string $group_id Group ID.
     * @param string $setting_id Setting ID.
     *
     * @return stdClass|WP_Error
     * @since  3.0.0
     */
    public function get_setting( $group_id, $setting_id ) {
        if ( empty( $setting_id ) ) {
            return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'wpfnl' ), array( 'status' => 404 ) );
        }

        $settings = $this->get_group_settings( $group_id );

        if ( is_wp_error( $settings ) ) {
            return $settings;
        }

        $array_key = array_keys( wp_list_pluck( $settings, 'id' ), $setting_id );

        if ( empty( $array_key ) ) {
            return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'wpfnl' ), array( 'status' => 404 ) );
        }

        $setting = $settings[ $array_key[0] ];
        return $setting;
    }


    /**
     * Active required plugin based on slug
     *
     * @param $plugin_name
     *
     * @return WP_Error|WP_REST_Response
     * @since  1.0.0
     */
	public function activate_wc_plugins( \WP_REST_Request $request ) {
		if( !function_exists('activate_plugin') ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}



		$permission = $request['permission'];
		$islms = $request['islms'];
		$isWc = $request['isWc'];

		$plugin_slug_arr = array(
			'woocommerce/woocommerce.php' => false,
			'cart-lift/cart-lift.php' => false,
			'sfwd-lms/sfwd_lms.php' => false,
		);
		$should_activated = array(
			'woocommerce/woocommerce.php' => false,
			'cart-lift/cart-lift.php' => false,
			'sfwd-lms/sfwd_lms.php' => false,
		);
		if($permission == '' ){
			unset($should_activated[ 'cart-lift/cart-lift.php' ]);
			unset($plugin_slug_arr[ 'cart-lift/cart-lift.php' ]);
		}

        if($permission == '' ){
			unset($should_activated[ 'cart-lift/cart-lift.php' ]);
			unset($plugin_slug_arr[ 'cart-lift/cart-lift.php' ]);
		}

        if( $isWc == 'no' ){
            unset($should_activated[ 'woocommerce/woocommerce.php' ]);
			unset($plugin_slug_arr[ 'woocommerce/woocommerce.php' ]);
        }
        if( $islms == '' ){
            unset($should_activated[ 'sfwd-lms/sfwd_lms.php' ]);
            unset($plugin_slug_arr[ 'sfwd-lms/sfwd_lms.php' ]);
        }

		foreach ( $plugin_slug_arr as $slug => $is_silent ) {
			$data = activate_plugin( $slug, '', false, $is_silent );
			if ( !is_wp_error( $data ) ) {
				$should_activated[ $slug ] = true;

			}
		}
		delete_transient( '_wc_activation_redirect' );

		foreach ( $should_activated as $slug => $is_activated ) {
			if(!$is_activated) {
				$settings['success'] = false;
				return rest_ensure_response( $settings );
			}
		}

		$settings['success'] = true;
		return rest_ensure_response( $settings );
	}
    /**
     * Active required plugin based on slug
     *
     * @param $plugin_name
     *
     * @return WP_Error|WP_REST_Response
     * @since  1.0.0
     */
	public function activate_mrm_plugins( \WP_REST_Request $request ) {
		if( !function_exists('activate_plugin') ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
        $plugins = isset( $request['plugins'] ) ? $request['plugins'] : [];
        $plugin_slug_arr  = [];
        $should_activated = [];
        if( !empty($plugins) ){
            foreach( $plugins as $plugin ){
                if( 'mail-mint' == $plugin ) {
                    $plugin_slug_arr['mail-mint/mail-mint.php'] = false;
                    $should_activated['mail-mint/mail-mint.php'] = false;
                }
            }
        }
        foreach ( $plugin_slug_arr as $slug => $is_silent ) {
            $data = activate_plugin( $slug, '', false, $is_silent );

            if ( !is_wp_error( $data ) ) {
                $should_activated[ $slug ] = true;
            }
        }
        delete_transient( '_wc_activation_redirect' );
        foreach ( $should_activated as $slug => $is_activated ) {
            if(!$is_activated) {
                $settings['success'] = false;
                return rest_ensure_response( $settings );
            }
        }

		$settings['success'] = true;
		return rest_ensure_response( $settings );
	}


    /**
     * Disable activation redirect
     */
    public function disable_wc_activation_redirect() {
        delete_transient( '_wc_activation_redirect' );
    }



    /**
     * Activate required plugins
     *
     * @param $plugin_name
     */
    public function active_plugin($plugin_slug,$permission = 1) {
		$plugin_slug_arr = array();

    	switch ($plugin_slug) {
            case 'elementor':
				$plugin_slug_arr = array(
					'elementor/elementor.php' => true,
				);
				break;
			case 'gutenberg':
                if($permission == 1){
                    $plugin_slug_arr = array(
                        'qubely/qubely.php' => true,
                    );
                }
				break;
            case 'divi-builder':
                $is_divi_installed = Wpfnl_functions::wpfnl_check_is_plugin_installed( $plugin_slug.'/divi-builder.php' );
                $is_divi_theme_active = Wpfnl_functions::wpfnl_is_theme_active( 'Divi' );
                if( $is_divi_installed ){
                    $plugin_slug_arr = array(
                        'divi-builder/divi-builder.php' => true,
                    );
                }
                break;

		}
		if($plugin_slug_arr) {
			foreach ( $plugin_slug_arr as $slug => $is_silent ) {
				$activate[ $slug ] = activate_plugin( $slug, '', false, $is_silent );
			}
		}

        if(is_plugin_active( 'elementor/elementor.php' )){
            Manager::create_default_kit();
        }
    }


    /**
     * Update a single setting in a group.
     *
     * @param WP_REST_Request $request Request data.
     *
     * @return WP_Error|WP_REST_Response
     * @since  1.0.0
     */
    public function update_settings( $request ) {
        if( isset( $request['settings_id'], $request['group_id'] )){
            if( $request['settings_id'] === 'permalink') {
                $default = Wpfnl_functions::get_permalink_settings();
                $settings = Wpfnl_functions::get_admin_settings( '_wpfunnels_permalink_settings', $default);
            } elseif( $request['settings_id'] === 'optin') {
                $default = Wpfnl_functions::get_optin_settings();
                $settings = Wpfnl_functions::get_admin_settings( '_wpfunnels_optin_settings', $default);
            } elseif ($request['settings_id'] === 'funnel_type') {
                $default = Wpfnl_functions::get_general_settings();
                $settings = Wpfnl_functions::get_admin_settings( $request['group_id'], $default);
            }
            else {
                $default = Wpfnl_functions::get_general_settings();
                $settings = Wpfnl_functions::get_admin_settings( $request['group_id'], $default );
            }

            if ( !$settings ) {
                return new WP_Error( 'rest_setting_invalid', __( 'Invalid setting.', 'wpfnl' ), array( 'status' => 404 ) );
            }
            if ( empty( $settings ) ) {
                return new WP_Error( 'rest_setting_invalid', __( 'Invalid setting.', 'wpfnl' ), array( 'status' => 404 ) );
            }

            if($request['type'] !== 'ignore_activation' ) {
                $this->active_plugin($request['slug'],$request['permission']);
            }

            if($request['settings_id'] === 'permalink') {
                $settings['structure'] = $request['settings'];
                $settings['funnel_base'] = $request['funnelBase'];
                $settings['step_base'] = $request['stepBase'];
                Wpfnl_functions::update_admin_settings('_wpfunnels_permalink_saved', true);
                Wpfnl_functions::update_admin_settings($request['group_id'], $settings);

            }else {
                if( 'funnel_type' === $request['settings_id'] ) {
                    $general_settings = get_option( '_wpfunnels_general_settings' );
                    $settings[$request['settings_id']] = isset($general_settings['funnel_type']) ? $general_settings['funnel_type'] : 'sales';
                }else{
                    $settings[$request['settings_id']] = $request['value'];
                }

                Wpfnl_functions::update_admin_settings($request['group_id'], $settings);

            }
        }
        $settings['success'] = true;
        $settings = $this->prepare_item_for_response( $settings, $request );
        delete_option(WPFNL_TEMPLATES_OPTION_KEY);
        delete_transient('wpfunnels_remote_template_data_' . WPFNL_VERSION);
        return rest_ensure_response( $settings );
    }


    /**
     * Check lms or not
     */
    public function is_lms( $request ){

        $response = [
            'success' => false,
            'is_lms'  => false,
        ];

        if( $request['funnel_id'] ){
            $funnel_id = $request['funnel_id'];
            $type   = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
            if( Wpfnl_functions::is_lms_addon_active() && $type == 'lms' ){
                $response['success'] = true;
                $response['is_lms'] = true;
            }else{
                $response['success'] = true;
                $response['is_lms'] = false;
            }
        }
        return rest_ensure_response( $response );
    }


    /**
     * Update funnel type
     *
     * @param Array $request
     *
     * @return Array
     * @since  2.5.3
     */
    public function update_funnel_type( $request ){

        $response = [
            'success' => false,
        ];

        if( $request['type'] ){
            $type = $request['type'];
            $general_settings = get_option( '_wpfunnels_general_settings' );
            $general_settings['funnel_type'] = $type;
            update_option('_wpfunnels_general_settings', $general_settings );
            $response = [
                'success' => true,
            ];
        }
        return rest_ensure_response( $response );
    }


    /**
     * Update funnel builder
     *
     * @param Array $request
     *
     * @return Array
     * @since  2.5.3
     */
    public function update_builder_type( $request ){

        $response = [
            'success' => false,
        ];

        if( $request['builder'] ){
            $builder = $request['builder'];
            $general_settings = get_option( '_wpfunnels_general_settings' );
            $general_settings['builder'] = $builder;
            update_option('_wpfunnels_general_settings', $general_settings );
            $response = [
                'success' => true,
            ];
        }
        return rest_ensure_response( $response );
    }


    /**
     * Activates plugins.
     *
     * @param WP_REST_Request $request The REST request object.
     */
    public function activate_plugins( $request ){
        $response = [
            'success' => false,
        ];
        
        if( !isset( $request['plugins'] )){
            rest_ensure_response( $response );
        }

        $plugins = $request['plugins'];
        $activatePluginInstance = new \WPFunnels\Admin\SetupWizard\ActivatePlugin( $plugins );
        $response = $activatePluginInstance->activate_plugins();
        return rest_ensure_response( $response );
    }
    
    
    /**
     * Save setup wizard settings
     *
     * @param WP_REST_Request $request The REST request object.
     */
    public function save_setup_wizard_settings( $request ){
        $response = [
            'success' => false,
        ];
        
        if( !isset( $request['name'],$request['value'] )){
            rest_ensure_response( $response );
        }

        if( 'builder'  === $request['name'] || 'funnel_type' === $request['name'] ){
            $name = sanitize_text_field($request['name']);
            $value = sanitize_text_field($request['value']);
            $value = 'qubely' === $value ? 'gutenberg' : $value;
            $settings = Wpfnl_functions::get_general_settings();
            $settings[$name] = $value;
            update_option('_wpfunnels_general_settings', $settings );
            
            if( 'builder' === $name ){
                delete_option(WPFNL_TEMPLATES_OPTION_KEY.'_wc');
                delete_option(WPFNL_TEMPLATES_OPTION_KEY.'_lms');
                delete_transient('wpfunnels_remote_template_data_wc_' . WPFNL_VERSION);
                delete_transient('wpfunnels_remote_template_data_lms_' . WPFNL_VERSION);
                delete_transient('wpfunnels_remote_template_data_lead_' . WPFNL_VERSION);
            }

            $response = [
                'success' => true,
            ];
        }
        
        return rest_ensure_response( $response );
    }


    /**
     * Create contact to Mail Mint.
     *
     * @param WP_REST_Request $request The REST request object.
     */
    public function create_contact( $request ){
        $response = [
            'success' => false,
        ];
        
        if( !isset( $request['email'] )){
            rest_ensure_response( $response );
        }

        $email = sanitize_text_field($request['email']);
        $name =  isset($request['name']) ? sanitize_text_field($request['name']) : '';
        $createContactInstance = new \WPFunnels\Admin\SetupWizard\CreateContact($email, $name );
        $response = $createContactInstance->create_contact_via_webhook();
        $createContactInstance->send_contact_to_appsero();
        return rest_ensure_response( $response );
    }



    /**
     * Get funnel type id
     */
    public function get_funnel_type_id( $request ){

        $response = [
            'success' => false,
            'type_id'  => '',
        ];

        if( $request['funnel_id'] ){
            $funnel_id = $request['funnel_id'];
            $type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
            $term = get_term_by('slug', $type, 'template_type');
            if( $term ){
                $response['success'] = true;
                $response['type_id'] = $term->term_id;
            }
        }

        return rest_ensure_response( $response );
    }




    /**
     * Prepare a single setting object for response.
     *
     * @param object          $item Setting object.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response $response Response data.
     * @since  3.0.0
     */
    public function prepare_item_for_response( $item, $request ) {
        $data     = $this->add_additional_fields_to_object( $item, $request );
        $response = rest_ensure_response( $data );
        $response->add_links( $this->prepare_links( $request['settings_id'] ) );
        return $response;
    }



}
