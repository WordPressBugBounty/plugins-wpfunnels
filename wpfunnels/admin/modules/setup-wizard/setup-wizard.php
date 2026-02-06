<?php
/**
 * Setup wizard
 * 
 * @package
 */

namespace WPFunnels\Admin;

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class SetupWizard
{
    use SingletonTrait;


    public $step_name;

    public $steps;

    public function __construct()
    {
        $this->setup_wizard();
    }

    /**
     * Register AJAX handlers
     * This should be called early, not just when rendering the wizard
     *
     * @since 3.3.1
     */
    public static function register_ajax_handlers() {
        add_action('wp_ajax_wpfnl_activate_plugin', array(__CLASS__, 'ajax_activate_plugin_static'));
        add_action('wp_ajax_wpfnl_update_step_meta', array(__CLASS__, 'ajax_update_step_meta'));
        add_action('wp_ajax_wpfnl_activate_mail_mint', array(__CLASS__, 'ajax_activate_mail_mint'));
    }

    /**
     * AJAX handler for updating step meta
     *
     * @since 3.3.1
     */
    public static function ajax_update_step_meta() {
        // Check user capabilities
        if (!current_user_can('wpf_manage_funnels')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            wp_die();
        }

        $step_id = isset($_POST['step_id']) ? absint($_POST['step_id']) : 0;
        $meta_key = isset($_POST['meta_key']) ? sanitize_text_field($_POST['meta_key']) : '';
        $meta_value = isset($_POST['meta_value']) ? $_POST['meta_value'] : '';

        if (!$step_id || !$meta_key) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            wp_die();
        }

        // Decode JSON if it's a JSON string
        if (is_string($meta_value) && (substr($meta_value, 0, 1) === '{' || substr($meta_value, 0, 1) === '[')) {
            $decoded = json_decode(stripslashes($meta_value), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $meta_value = $decoded;
            }
        }

        update_post_meta($step_id, $meta_key, $meta_value);

        wp_send_json_success(array(
            'message' => 'Meta updated successfully',
            'step_id' => $step_id,
            'meta_key' => $meta_key
        ));
        wp_die();
    }

    /**
     * Static AJAX handler for plugin activation
     *
     * @since 3.3.1
     */
    public static function ajax_activate_plugin_static() {
        // Check user capabilities first
        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            wp_die();
        }

        // Get the plugin parameter
        $plugin = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';

        if (empty($plugin)) {
            wp_send_json_error(array('message' => 'Plugin parameter is required'));
            wp_die();
        }

        // Check if plugin file exists
        $plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
        if (!file_exists($plugin_file)) {
            wp_send_json_error(array('message' => 'Plugin file not found: ' . $plugin));
            wp_die();
        }

        // Check if already active
        if (is_plugin_active($plugin)) {
            wp_send_json_success(array('message' => 'Plugin is already active'));
            wp_die();
        }

        // Activate the plugin
        $result = activate_plugin($plugin, '', false, true);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            wp_die();
        }

        // Special handling for WooCommerce
        if (strpos($plugin, 'woocommerce') !== false) {
            delete_transient('_wc_activation_redirect');
        }

        // Special handling for Elementor
        if (strpos($plugin, 'elementor') !== false) {
            update_option('elementor_onboarded', true);
        }

        wp_send_json_success(array('message' => 'Plugin activated successfully', 'plugin' => $plugin));
        wp_die();
    }

    /**
     * AJAX handler for Mail Mint activation with explicit database initialization
     * This ensures Mail Mint databases are created even on PHP 7.4
     *
     * @since 3.3.1
     */
    public static function ajax_activate_mail_mint() {
        // Check user capabilities first
        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            wp_die();
        }

        // Activate the Mail Mint plugin
        $mail_mint_plugin = 'mail-mint/mail-mint.php';
        
        if (!is_plugin_active($mail_mint_plugin)) {
            $result = activate_plugin($mail_mint_plugin, '', false, true);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
                wp_die();
            }
        }

        // Explicitly trigger Mail Mint activation logic to ensure database creation
        $mail_mint_file = WP_PLUGIN_DIR . '/' . $mail_mint_plugin;
        if (file_exists($mail_mint_file)) {
            require_once WP_PLUGIN_DIR . '/mail-mint/includes/MrmActivator.php';
            
            // Call the activation method directly to ensure databases are created
            \MrmActivator::activate();
            
            // Remove the Mail Mint setup wizard transient to prevent it from showing
            delete_transient('mailmint_show_setup_wizard');
        }

        wp_send_json_success(array('message' => 'Mail Mint activated and initialized successfully'));
        wp_die();
    }

    /**
     * Initialize setup wizards
     *
     * @since 1.0.0
     */
    private function setup_wizard()
    {

        $steps = array(
            'type' => array(
                'name'      => 'Funnel Type',
                'slug'      => 'funnel-type',
                'icon'      => WPFNL_URL . 'admin/assets/images/funnel-type.svg',
                'iconActive'=> WPFNL_URL . 'admin/assets/images/funnel-type-active.svg',
                'iconCompleted'=> WPFNL_URL . 'admin/assets/images/funnel-type-completed.svg',
                'completed' => false,
                'isActive'  => false,
            ),
            'builder' => array(
                'name'      => 'Builder Type',
                'slug'      => 'builder-type',
                'icon'      => WPFNL_URL . 'admin/assets/images/builder.svg',
                'iconActive'=> WPFNL_URL . 'admin/assets/images/builder-active.svg',
                'iconCompleted'=> WPFNL_URL . 'admin/assets/images/builder-completed.svg',
                'completed' => false,
                'isActive'  => false,
            ),
            'automation' => array(
                'name'      => 'Email Automation',
                'slug'      => 'email-automation',
                'icon'      => WPFNL_URL . 'admin/assets/images/email-automation.svg',
                'iconActive'=> WPFNL_URL . 'admin/assets/images/email-automation-active.svg',
                'iconCompleted'=> WPFNL_URL . 'admin/assets/images/email-automation-completed.svg',
                'completed' => false,
                'isActive'  => false,
            ),
            'permalink' => array(
                'name'      => 'Permalink',
                'slug'      => 'permalink',
                'icon'      => WPFNL_URL . 'admin/assets/images/permalink.svg',
                'iconActive'=> WPFNL_URL . 'admin/assets/images/permalink-active.svg',
                'iconCompleted'=> WPFNL_URL . 'admin/assets/images/permalink-completed.svg',
                'completed' => false,
                'isActive'  => false,
            ),
            'thankyou' => array(
                'name'      => 'Thank You',
                'slug'      => 'thankyou',
                'icon'      => WPFNL_URL . 'admin/assets/images/thankyou.svg',
                'iconActive'=> WPFNL_URL . 'admin/assets/images/thankyou-active.svg',
                'iconCompleted'=> WPFNL_URL . 'admin/assets/images/thankyou-completed.svg',
                'completed' => false,
                'isActive'  => false,
            ),
        );
        $this->step_name = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : current( array_keys( $steps ) );
        foreach ( $steps as $key => $step ) {
            if( $key === $this->step_name ) {
                $step['isActive'] = true;
                $this->steps[$key] = $step;
            } else {
                $step['completed'] = array_search($this->step_name,array_keys($steps)) > array_search($key,array_keys($steps)) ;
                $this->steps[$key] = $step;
            }
        }
        $installed_plugins = get_plugins();

        $product_url = esc_url_raw(
            add_query_arg(
                array(
                    'post_type'      => 'product',
                    'wpfunnels' => 'yes',
                ),
                admin_url('post-new.php')
            )
        );

        // Admin Name & Email Finding Starts
        $admin_email = get_option('admin_email');
        $admin_user = get_user_by('email', $admin_email);
        $admin_name = $admin_user ? $admin_user->display_name : '';
        // Admin Name & Email Finding Ends

        wp_enqueue_style('setup-wizard', WPFNL_URL . 'admin/assets/css/wpfnl-admin.css', false, '1.1', 'all');
        wp_enqueue_script('setup-wizard-runtime', WPFNL_URL . 'admin/assets/dist/runtime/index.min.js', array(), time(), true);
        wp_enqueue_script('setup-wizard', WPFNL_URL . 'admin/assets/dist/js/setup-wizard.min.js', array('jquery', 'wp-util', 'updates', 'setup-wizard-runtime'), time(), true);
        wp_localize_script('setup-wizard', 'setup_wizard_obj',
            array(
                'rest_api_url'          => esc_url_raw(get_rest_url()),
                'ajax_url'              => esc_url_raw(admin_url('admin-ajax.php')),
                'admin_url'             => esc_url_raw(admin_url()),
                'dashboard_url'         => esc_url_raw(admin_url('admin.php?page=' . WPFNL_MAIN_PAGE_SLUG)),
                'settings_url'          => class_exists( 'WooCommerce' ) ? esc_url_raw(admin_url('admin.php?page=wpfnl_settings')) : esc_url_raw(admin_url()),
                'wizard_url'            => esc_url_raw(admin_url('admin.php?page=wpfunnels-setup')),
                'home_url'              => esc_url_raw(home_url()),
                'nonce'                 => wp_create_nonce('wp_rest'),
                'product_url' 				=> $product_url,
                'admin_nonce'           => wp_create_nonce('wpfnl-admin'),
                'currency_symbol'       => class_exists( 'WooCommerce' ) ? get_woocommerce_currency_symbol() : '$',
                'currency_position'     => class_exists( 'WooCommerce' ) ? get_option('woocommerce_currency_pos', 'left') : 'left',
                'current_step'          => $this->step_name,
                'steps'                 => $this->steps,
                'next_step_link'        => $this->get_next_step_link(),
                'prev_step_link'        => $this->get_prev_step_link(),
                'is_woo_installed'      => isset( $installed_plugins['woocommerce/woocommerce.php'] ) ? 'yes' : 'no',
                'is_mrm_installed'      => isset( $installed_plugins['mail-mint/mail-mint.php'] ) ? 'yes' : 'no',
                'is_elementor_installed'=> isset( $installed_plugins['elementor/elementor.php'] ) ? 'yes' : 'no',
                'is_ff_installed'       => isset( $installed_plugins['fluentform/fluentform.php'] ) ? 'yes' : 'no',
                'is_cl_installed'       => isset( $installed_plugins['cart-lift/cart-lift.php'] ) ? 'yes' : 'no',
                'is_lms_installed'      => is_plugin_active( 'wpfunnels-pro-lms/wpfunnels-pro-lms.php' ) ? 'yes' : 'no',
				'is_qb_installed'       => isset( $installed_plugins['qubely/qubely.php'] ) ? 'yes' : 'no',
				'is_woo_active'         => is_plugin_active( 'woocommerce/woocommerce.php' ) ? 'yes' : 'no',
                'is_elementor_active'   => is_plugin_active( 'elementor/elementor.php' ) ? 'yes' : 'no',
                'is_mrm_active'         => is_plugin_active( 'mail-mint/mail-mint.php' ) ? 'yes' : 'no',
                'is_ff_active'          => is_plugin_active( 'fluentform/fluentform.php' ) ? 'yes' : 'no',
                'is_cl_active'          => is_plugin_active( 'cart-lift/cart-lift.php' ) ? 'yes' : 'no',
                'is_qb_active'          => is_plugin_active( 'qubely/qubely.php' ) ? 'yes' : 'no',
                'is_pro_active'         => Wpfnl_functions::is_wpfnl_pro_activated() ? 'yes' : 'no',
                'funnel_type'           => $this->get_funnel_type(),
                'getPlugins'            => $this->get_essential_plugins(),
                'defaultSettings'       => Wpfnl_functions::get_general_settings(),
                'logo_url'              => WPFNL_URL .'admin/assets/images/setup-funnel-logo.png',
                'welcome_image'         => WPFNL_URL .'admin/assets/images/welcome-image.png',
                'gb_builder_img'        => WPFNL_URL .'admin/assets/images/gutenberg.png',
                'elementor_img'         => WPFNL_URL .'admin/assets/images/elementor.png',
                'oxygen_img'            => WPFNL_URL .'admin/assets/images/oxygen.png',
                'divi_img'              => WPFNL_URL .'admin/assets/images/divi.png',
                'bricks_img'            => WPFNL_URL .'admin/assets/images/bricks.png',
                'others_builder_img'    => WPFNL_URL .'admin/assets/images/others.png',
                'wc_logo'               => WPFNL_URL .'admin/assets/images/wc-logo.png',
                'mail_mint_logo'        => WPFNL_URL .'admin/assets/images/mail-mint.png',
                'wizard_video_poster'   => WPFNL_URL .'admin/assets/images/setup-wizard-done-video-poster.png',
                'no_plugin_image'       => WPFNL_URL .'admin/assets/images/no-plugin-install.png',
                'qubely_img'            => WPFNL_URL .'admin/assets/images/qubely.svg',
                'quote_img'             => WPFNL_URL .'admin/assets/images/quote-icon.svg',
                'done_icon'             => WPFNL_URL .'admin/assets/images/done-icon.svg',
                'admin_email'           => $admin_email,
                'admin_name'            => $admin_name,
            )
        );
        $this->output_html();
    }


    /**
     * Get essential plugins for WPFunnels
     * 
     * @return array
     * @since 3.3.1
     */
    public function get_essential_plugins(){
        $plugins = [
            ['name' => 'wc', 'slug' => 'woocommerce', 'type' => 'plugin', 'constant' => 'WC_PLUGIN_FILE', 'path' => 'woocommerce/woocommerce.php'],
            ['name' => 'mailmint', 'slug' => 'mail-mint', 'type' => 'plugin', 'constant' => 'MRM_VERSION', 'path' => 'mail-mint/mail-mint.php'],
            ['name' => 'elementor', 'slug' => 'elementor', 'type' => 'plugin', 'constant' => 'ELEMENTOR_VERSION', 'path' => 'elementor/elementor.php'],
            ['name' => 'qubely', 'slug' => 'qubely', 'type' => 'plugin', 'constant' => 'QUBELY_VERSION', 'path' => 'qubely/qubely.php'],
            ['name' => 'divi', 'slug' => 'divi-builder', 'type' => 'plugin', 'constant' => 'ET_BUILDER_PLUGIN_VERSION', 'path' => 'divi-builder/divi-builder.php']
        ];
    
        foreach($plugins as &$plugin) {
            $plugin['status'] = $this->get_plugin_status($plugin['constant'], $plugin['path']);
        }
    
        $oxygen_status = 'uninstalled';
        if (Wpfnl_functions::is_plugin_activated('oxygen/functions.php')) {
            $oxygen_status = 'activated';
        } else if (Wpfnl_functions::is_plugin_installed('oxygen/functions.php')) {
            $oxygen_status = 'installed';
        }
        $plugins[] = ['name' => 'oxygen', 'slug' => 'oxygen', 'type' => 'plugin', 'path' => 'oxygen/functions.php', 'status' => $oxygen_status];
    
        $maybe_bricks = Wpfnl_functions::maybe_bricks_theme();
        $bricks_status = $maybe_bricks ? 'activated' : 'uninstalled';
        if (Wpfnl_functions::maybe_theme_installed('bricks') && !$maybe_bricks ) {
            $bricks_status = 'installed';
        }
        $plugins[] = ['name' => 'bricks', 'slug' => 'bricks', 'type' => 'theme', 'path' => 'bricks', 'status' => $bricks_status];

        return $plugins;
    }

    /**
     * Get plugin status
     * 
     * @param string $constant
     * @param string $path
     * @return string
     * @since 3.3.1
     */
    public function get_plugin_status($constant, $path) {
        $installed_plugins = get_plugins();
        if(defined($constant)){
            return 'activated';
        } else if(isset($installed_plugins[$path])) {
            return 'installed';
        } else {
            return 'uninstalled';
        }
    }


    /**
     * Get funnel type
     * 
     * @since 2.5.3
     */
    private function get_funnel_type(){
        $general_settings = get_option( '_wpfunnels_general_settings' );
        if( isset($general_settings['funnel_type']) ){
            if( 'woocommerce' == $general_settings['funnel_type'] ){
                return 'sales';
            }
            return $general_settings['funnel_type'];
        }
        return 'sales';
    }




    /**
     * Get next step link
     *
     * @return string|void
     * @since  1.0.0
     */
    private function get_next_step_link() {
        $keys       = array_keys( $this->steps );
        $step_index = array_search( $this->step_name, $keys, true );
        $step_index = ( count( $keys ) == $step_index + 1 ) ? $step_index : $step_index + 1;
        $step       = $keys[ $step_index ];
        return admin_url( 'admin.php?page=wpfunnels-setup&step=' . $step );
    }


    /**
     * Get prev step link
     *
     * @return string|void
     * @since  1.0.0
     */
    private function get_prev_step_link() {
        $keys       = array_keys( $this->steps );
        $step = '';
        $step_index = array_search( $this->step_name, $keys, true );
        $step_index = ( count( $keys ) == $step_index - 1 ) ? $step_index : $step_index - 1;
        if (isset($keys[ $step_index ])) {
            $step       = $keys[ $step_index ];
        }

        return admin_url( 'admin.php?page=wpfunnels-setup&step=' . $step );
    }


    /**
     * Output the rendered contents
     *
     * @since 1.0.0
     */
    private function output_html()
    {
        require_once plugin_dir_path(__FILE__) . 'views/views.php';
        exit();
    }
}
