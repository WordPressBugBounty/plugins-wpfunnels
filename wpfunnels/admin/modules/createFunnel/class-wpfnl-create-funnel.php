<?php
/**
 * Create funnel
 * 
 * @package
 */
namespace WPFunnels\Modules\Admin\CreateFunnel;

use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

class Module extends Wpfnl_Admin_Module
{
    use SingletonTrait;

    private $builder;

    public function get_view()
    {
        $this->builder = Wpfnl_functions::get_builder_type();
        if (Wpfnl_functions::is_builder_active($this->builder)) {
            require_once WPFNL_DIR . '/admin/modules/createFunnel/views/view.php';
        } else {
            require_once WPFNL_DIR . '/admin/modules/createFunnel/views/builder-not-activated.php';
        }
    }

    public function init_ajax()
    {
        wp_ajax_helper()->handle('create-funnel')
            ->with_callback([ $this, 'create_funnel' ])
            ->with_validation($this->get_validation_data());
    }


    /**
     * Create funnel by ajax request
     *
     * @return array
     * @since  1.0.0
     */
    public function create_funnel( $payload )
    {
        // Check if this is a Store Checkout import
        $is_store_checkout = isset($payload['is_store_checkout']) && filter_var($payload['is_store_checkout'], FILTER_VALIDATE_BOOLEAN);
        
        // Validate Store Checkout creation
        if ($is_store_checkout) {
            $existing_store_checkout = $this->get_store_checkout_funnel();
            if ($existing_store_checkout) {
                return [
                    'success' => false,
                    'message' => __('A Store Checkout already exists. Please delete the existing one before creating a new one.', 'wpfnl'),
                ];
            }
        }

        $funnel = Wpfnl::$instance->funnel_store;
        $funnel_id = $funnel->create($payload['funnelName']);
        $funnel_type = '';
        if ( $funnel_id ) {
            // Mark funnel as Store Checkout if applicable
            if ($is_store_checkout) {
                update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'store_checkout' );
                $funnel_type = 'store_checkout';
            } else {
                $general_settings = get_option( '_wpfunnels_general_settings' );
                if( isset($general_settings['funnel_type']) ){
                    if( 'woocommerce' == $general_settings['funnel_type'] ){
                        $general_settings['funnel_type'] = 'sales';
                        update_option( '_wpfunnels_general_settings', $general_settings );
                        
                    }
                    
                    if( 'sales' == $general_settings['funnel_type'] ){
                        // Check if LMS add-on is active AND at least one LMS plugin (LearnDash or CreatorLMS) is active
                        if( Wpfnl_functions::is_lms_addon_active() && Wpfnl_functions::is_any_lms_plugin_active() && isset($payload['type']) && 'lms' === $payload['type'] ){
                            update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'lms' );
                            $funnel_type = 'lms';
                        }elseif( Wpfnl_functions::is_wc_active() && isset($payload['type']) && 'wc' === $payload['type'] ){
                            update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'wc' );
                            $funnel_type = 'wc';
                        }elseif( isset($payload['type']) && 'lead' === $payload['type'] ){
                            update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'lead' );
                            $funnel_type = 'lead';
                        }

                    }else{
                       
                        if( isset($payload['type']) && 'lead' === $payload['type'] ){
                            update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'lead' );
                            $funnel_type = 'lead';
                        }
                    }
                }
            }
        }
        
        if ($is_store_checkout) {
            // For Store Checkout funnels, always create a fixed checkout → thank you layout
            $steps = $this->get_store_checkout_default_steps();
            $templateLayout = new \WPFunnels\Admin\Module\Layout\TemplateLayout($funnel, $funnel_id, $funnel_type, $steps);
            $templateLayout->create_step();
            $templateLayout->save_funnel_data();

            // Persist the checkout step ID so the public-facing override can use it
            require_once WPFNL_DIR . 'includes/core/woocommerce/class-wpfnl-store-checkout-override.php';
            \WPFunnels\WooCommerce\Wpfnl_Store_Checkout_Override::save_checkout_step_id( $funnel_id );
        } elseif( !empty($payload['selectedFunnelLayout']['steps']) ){
            $steps = $payload['selectedFunnelLayout']['steps'];
            $templateLayout = new \WPFunnels\Admin\Module\Layout\TemplateLayout($funnel, $funnel_id, $funnel_type, $steps);
            $templateLayout->create_step();
            $templateLayout->save_funnel_data();
        }
        
        $link = add_query_arg(
            [
                'page' => 'edit_funnel',
                'id' => $funnel_id,
            ],
            admin_url('admin.php')
        );

        do_action('wpfunnels_after_funnel_created', $funnel_id, $funnel_type);

        return [
            'success' => true,
            'funnelID' => $funnel_id,
            'redirectUrl' => $link,
        ];
    }


    /**
     * Get the default steps for a Store Checkout funnel (checkout + thank you only).
     *
     * @return array
     * @since 3.5.0
     */
    private function get_store_checkout_default_steps()
    {
        return [
            [
                'name'  => __( 'Checkout', 'wpfnl' ),
                'value' => 'checkout',
                'pos_x' => 156,
                'pos_y' => 258,
            ],
            [
                'name'  => __( 'Thank You', 'wpfnl' ),
                'value' => 'thankyou',
                'pos_x' => 399,
                'pos_y' => 258,
            ],
        ];
    }


    /**
     * Get Store Checkout funnel if exists
     *
     * @return WP_Post|false
     * @since 3.5.0
     */
    private function get_store_checkout_funnel()
    {
        $args = array(
            'post_type'      => WPFNL_FUNNELS_POST_TYPE,
            'posts_per_page' => 1,
            'post_status'    => array( 'publish', 'draft' ),
            'meta_query'     => array(
                array(
                    'key'   => '_wpfnl_funnel_type',
                    'value' => 'store_checkout',
                ),
            ),
        );
        
        $funnels = get_posts( $args );
        return !empty( $funnels ) ? $funnels[0] : false;
    }

    public function get_name()
    {
        return 'create-funnel';
    }
}
