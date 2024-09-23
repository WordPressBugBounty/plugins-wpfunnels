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
        $funnel = Wpfnl::$instance->funnel_store;
        $funnel_id = $funnel->create($payload['funnelName']);
        $funnel_type = '';
        if ( $funnel_id ) {
            $general_settings = get_option( '_wpfunnels_general_settings' );
            if( isset($general_settings['funnel_type']) ){
                if( 'woocommerce' == $general_settings['funnel_type'] ){
                    $general_settings['funnel_type'] = 'sales';
                    update_option( '_wpfunnels_general_settings', $general_settings );
                    
                }
                
                if( 'sales' == $general_settings['funnel_type'] ){
                    if( Wpfnl_functions::is_lms_addon_active() && isset($payload['type']) && 'lms' === $payload['type'] ){
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
        
        if( !empty($payload['selectedFunnelLayout']['steps']) ){
            $templateLayout = new \WPFunnels\Admin\Module\Layout\TemplateLayout($funnel, $funnel_id, $funnel_type, $payload['selectedFunnelLayout']['steps']);
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

        return [
            'success' => true,
            'funnelID' => $funnel_id,
            'redirectUrl' => $link,
        ];
    }

    public function get_name()
    {
        return 'create-funnel';
    }
}
