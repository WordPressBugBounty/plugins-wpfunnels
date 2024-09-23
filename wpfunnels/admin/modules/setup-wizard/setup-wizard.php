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
                'dashboard_url'         => esc_url_raw(admin_url('admin.php?page=' . WPFNL_MAIN_PAGE_SLUG)),
                'settings_url'          => class_exists( 'WooCommerce' ) ? esc_url_raw(admin_url('admin.php?page=wpfnl_settings')) : esc_url_raw(admin_url()),
                'wizard_url'            => esc_url_raw(admin_url('admin.php?page=wpfunnels-setup')),
                'home_url'              => esc_url_raw(home_url()),
                'nonce'                 => wp_create_nonce('wp_rest'),
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
