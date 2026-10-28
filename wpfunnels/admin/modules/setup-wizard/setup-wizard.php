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

    /**
     * Nonce action the wizard's AJAX handlers verify.
     *
     * @since 3.13.6
     */
    const NONCE_ACTION = 'wpfnl_setup_wizard';

    /**
     * Register AJAX handlers
     * This should be called early, not just when rendering the wizard
     *
     * @since 3.3.1
     */
    public static function register_ajax_handlers() {
        // `wpfnl_update_step_meta` was registered here too: it wrote any meta
        // key and value to any post with no nonce and no per-post check, and
        // nothing called it.
        add_action('wp_ajax_wpfnl_activate_plugin', array(__CLASS__, 'ajax_activate_plugin_static'));
        add_action('wp_ajax_wpfnl_activate_mail_mint', array(__CLASS__, 'ajax_activate_mail_mint'));
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

        if (!check_ajax_referer(self::NONCE_ACTION, 'security', false)) {
            wp_send_json_error(array('message' => 'Nonce verification failed'), 403);
            wp_die();
        }

        // Get the plugin parameter
        $plugin = isset($_POST['plugin']) ? sanitize_text_field(wp_unslash($_POST['plugin'])) : '';

        if (empty($plugin)) {
            wp_send_json_error(array('message' => 'Plugin parameter is required'));
            wp_die();
        }

        // Only the plugins the wizard offers, never any installed plugin.
        if (!in_array($plugin, self::get_activatable_plugins(), true)) {
            wp_send_json_error(array('message' => 'This plugin cannot be activated from the setup wizard'), 403);
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

        if (!check_ajax_referer(self::NONCE_ACTION, 'security', false)) {
            wp_send_json_error(array('message' => 'Nonce verification failed'), 403);
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
     * What the setup wizard needs from the server, for the React admin to
     * localize as `setupWizard`.
     *
     * The wizard is a route of the React admin (`?page=wpfunnels#/setup`,
     * src/admin/pages/SetupWizard/) rather than a page of its own, so this is
     * printed with the rest of the app's data instead of being enqueued on a
     * separate screen. Only what the wizard reads: plugin and builder states,
     * the saved settings, the admin's name and email for the opt-in contact,
     * and its image URLs.
     *
     * @return array
     * @since  3.13.8
     */
    public static function get_app_data()
    {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $installed_plugins = get_plugins();

        $admin_email = get_option('admin_email');
        $admin_user  = get_user_by('email', $admin_email);

        return array(
            'is_woo_installed'       => isset( $installed_plugins['woocommerce/woocommerce.php'] ) ? 'yes' : 'no',
            'is_mrm_installed'       => isset( $installed_plugins['mail-mint/mail-mint.php'] ) ? 'yes' : 'no',
            'is_elementor_installed' => isset( $installed_plugins['elementor/elementor.php'] ) ? 'yes' : 'no',
            'is_woo_active'          => is_plugin_active( 'woocommerce/woocommerce.php' ) ? 'yes' : 'no',
            'is_elementor_active'    => is_plugin_active( 'elementor/elementor.php' ) ? 'yes' : 'no',
            'is_mrm_active'          => is_plugin_active( 'mail-mint/mail-mint.php' ) ? 'yes' : 'no',
            'getPlugins'             => self::get_essential_plugins(),
            'defaultSettings'        => Wpfnl_functions::get_general_settings(),
            'ajax_nonce'             => wp_create_nonce( self::NONCE_ACTION ),
            'admin_email'            => $admin_email,
            'admin_name'             => $admin_user ? $admin_user->display_name : '',
            'logo_url'               => WPFNL_URL . 'admin/assets/images/setup-funnel-logo.png',
            'welcome_image'          => WPFNL_URL . 'admin/assets/images/welcome-image.png',
            'gb_builder_img'         => WPFNL_URL . 'admin/assets/images/gutenberg.png',
            'elementor_img'          => WPFNL_URL . 'admin/assets/images/elementor.png',
            'oxygen_img'             => WPFNL_URL . 'admin/assets/images/oxygen.png',
            'divi_img'               => WPFNL_URL . 'admin/assets/images/divi.png',
            'bricks_img'             => WPFNL_URL . 'admin/assets/images/bricks.png',
            'others_builder_img'     => WPFNL_URL . 'admin/assets/images/others.png',
            'wc_logo'                => WPFNL_URL . 'admin/assets/images/wc-logo.png',
            'mail_mint_logo'         => WPFNL_URL . 'admin/assets/images/mail-mint.png',
        );
    }


    /**
     * Plugin files `wpfnl_activate_plugin` may activate: the essential plugins
     * the wizard offers. Mail Mint goes through `wpfnl_activate_mail_mint`.
     *
     * @return array
     * @since 3.13.6
     */
    public static function get_activatable_plugins() {
        return array(
            'woocommerce/woocommerce.php',
            'elementor/elementor.php',
            'qubely/qubely.php',
            'divi-builder/divi-builder.php',
            'oxygen/functions.php',
        );
    }

    /**
     * Get essential plugins for WPFunnels
     * 
     * @return array
     * @since 3.3.1
     */
    public static function get_essential_plugins(){
        $plugins = [
            ['name' => 'wc', 'slug' => 'woocommerce', 'type' => 'plugin', 'constant' => 'WC_PLUGIN_FILE', 'path' => 'woocommerce/woocommerce.php'],
            ['name' => 'mailmint', 'slug' => 'mail-mint', 'type' => 'plugin', 'constant' => 'MRM_VERSION', 'path' => 'mail-mint/mail-mint.php'],
            ['name' => 'elementor', 'slug' => 'elementor', 'type' => 'plugin', 'constant' => 'ELEMENTOR_VERSION', 'path' => 'elementor/elementor.php'],
            ['name' => 'qubely', 'slug' => 'qubely', 'type' => 'plugin', 'constant' => 'QUBELY_VERSION', 'path' => 'qubely/qubely.php'],
            ['name' => 'divi', 'slug' => 'divi-builder', 'type' => 'plugin', 'constant' => 'ET_BUILDER_PLUGIN_VERSION', 'path' => 'divi-builder/divi-builder.php']
        ];
    
        foreach($plugins as &$plugin) {
            $plugin['status'] = self::get_plugin_status($plugin['constant'], $plugin['path']);
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

        $is_divi_theme_active = Wpfnl_functions::wpfnl_is_theme_active('Divi');
        $divi_status = $is_divi_theme_active ? 'activated' : 'uninstalled';
        if (Wpfnl_functions::maybe_theme_installed('Divi') && !$is_divi_theme_active ) {
            $divi_status = 'installed';
        }
        $plugins[] = ['name' => 'divi', 'slug' => 'divi', 'type' => 'theme', 'path' => 'Divi', 'status' => $divi_status];

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
    public static function get_plugin_status($constant, $path) {
        $installed_plugins = get_plugins();
        if(defined($constant)){
            return 'activated';
        } else if(isset($installed_plugins[$path])) {
            return 'installed';
        } else {
            return 'uninstalled';
        }
    }
}
