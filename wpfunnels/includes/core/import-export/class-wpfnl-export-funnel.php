<?php

namespace WPFunnels\Export;


use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * This class is responsible export funnel 
 * 
 * Export json file 
 * @since 1.7.5
 */
class Wpfnl_Export {

    use SingletonTrait;


    /**
     * Initialize ajax callback
     * 
     * @since 1.7.5
     */

    public function init_ajax()
    {
        wp_ajax_helper()->handle('wpfnl-export-funnel')
            ->with_callback([ $this, 'export_funnel' ])
            ->with_validation($this->get_validation_data());


        wp_ajax_helper()->handle('wpfnl-export-all-funnels')
            ->with_callback([ $this, 'export_all_funnels' ])
            ->with_validation($this->get_validation_data());

    }



    /**
     * Export a single funnel
     * 
     * @param Array $payload
     * 
     * @return Array
     * @since 1.7.5
     */
    public function export_funnel( $payload ){
       
        $funnel_id = isset( $payload['funnel_id'] ) ? $payload['funnel_id'] : '';
        $response = [
            'success' => false,
        ];
        if( $funnel_id ){
            $data[] = $this->get_exported_funnel_data( $funnel_id );
            if( $data ){
                $response = [
                    'success'   => true,
                    'title'     => get_the_title( $funnel_id ),
                    'steps'     => $data ? $data : [],
                ];
            }
           
        }
        return $response;
    }


    /**
     * Export all funnels
     * @param Array $payload
     * 
     * @return Array
     * @since 1.7.5
     */
    public function export_all_funnels( $payload ){
        $status = isset($payload['status']) ? $payload['status'] : 'publish';
        $funnels = Wpfnl_functions::get_all_funnels( $status );
        $funnel_data = array();
        $response = [
            'success' => false,
            'data'    => $funnel_data,
        ];

        if( $funnels ){
            if ( $funnels && is_array($funnels) ) {
                foreach ( $funnels as $key => $funnel ) {
                    $funnel_data[] = $this->get_exported_funnel_data( $funnel->ID );
                }
            }
            $response['success'] =  true;
            $response['data']    =  $funnel_data;
        }
        
        return $response;
    }

    /**
     * Export selected funnels
     * 
     * @param array $payload Funnel ids and status.
     * 
     * @return array|\WP_Rest_Response|\WP_Error
     * @since 1.7.5
     */
    public function bulk_export_funnel ( $payload ){
        $status       = isset( $payload['status'] ) ? $payload['status'] : 'publish';
        $selected_ids = isset($payload['ids']) ? $payload['ids'] : [];

        $arg = [
				"post_type"   	=> WPFNL_FUNNELS_POST_TYPE,
                "fields"        => "ids",
				"orderby" 		=> "date",
				"order" 		=> 'ASC',
                "post__in"      => $selected_ids,
				"numberposts"   => -1
			];

        if( 'all' !== $status ){
            $arg['post_status'] = $status;
		}
		
        $funnels = get_posts( $arg );
        
        $funnel_data = array();

        $redirect_link = add_query_arg(
                            [
                                'page' => WPFNL_FUNNEL_PAGE_SLUG,
                            ],
                            admin_url('admin.php')
                        );

        $response = [
            'success'     => false,
            'data'        => $funnel_data,
            'redirectUrl' => $redirect_link
        ];
        if( $funnels ){
            if ( $funnels && is_array($funnels) ) {
                foreach ( $funnels as $key => $funnel_id ) {
                    $funnel_data[] = $this->get_exported_funnel_data( $funnel_id );
                }
            }
            $response['success']     = true;
            $response['data']        = $funnel_data;
            $response['redirectUrl'] = $redirect_link;
        }
        
        return rest_ensure_response ( $response );
    }

    /**
     * Get exported funnel data by funnel id
     * 
     * @param Int $funnel_id
     * @return Array
     * @since 1.7.5
     */
    public function get_exported_funnel_data( $funnel_id ){
      
        if( $funnel_id ){
            $funnel_id = trim($funnel_id);
            $funnel_id = (int)$funnel_id;
            $is_export = apply_filters( 'wpfunnels/allow_to_export', true );
            $steps     = get_post_meta( $funnel_id, '_steps', true );
            $all_funnel_meta     = get_post_meta( $funnel_id );
            $exclude_funnel_meta = ['funnel_automation_data', 'global_funnel_start_condition' ];
            $all_funnel_meta = $this->exclude_meta( $all_funnel_meta, $exclude_funnel_meta );

            $funnel_data['funnel_id']   = $funnel_id;
            $funnel_data['funnel_name'] = get_the_title( $funnel_id );
            $funnel_data['funnel_meta'] = $all_funnel_meta;
            $funnel_data['steps_data']  = [];
           
            if ( $steps && is_array($steps) ) {
                
                foreach ( $steps as $key => $step ) {
                    if( isset( $step['id'], $step['step_type']) ){
                        $all_meta     = get_post_meta( $step['id'] );
                        $exclude_step_meta = [
                            '_wpfnl_checkout_products', 
                            '_wpfnl_checkout_discount_main_product', 
                            'order-bump-settings', 
                            '_wpfnl_upsell_products', 
                            '_wpfnl_upsell_discount', 
                            '_wpfnl_downsell_products', 
                            '_wpfnl_downsell_discount',
                            'global_funnel_upsell_rules',
                            'global_funnel_downsell_rules',
                        ];
                        
                        $all_meta = $this->exclude_meta( $all_meta, $exclude_step_meta );
                        $post_step = get_post($step['id']);
                        $step_slug = isset( $post_step->post_name) ? $post_step->post_name : '';
                        $step_data = array(
                            'id'            => $step['id'],
                            'title'         => get_the_title( $step['id'] ),
                            'slug'          => $step_slug,
                            'type'          => $step['step_type'],
                            'post_content'  => '',
                            'page_template' => get_post_meta( $step['id'], '_wp_page_template', true),
                            'meta'          => $all_meta
                        );
                        if ( $is_export ) {
                            $step_obj = get_post( $step['id'] );
                            $step_data['post_content'] = isset($step_obj->post_content) ? $step_obj->post_content : '';
                        }
                        $ab_settings = get_post_meta( $step['id'], '_wpfnl_ab_testing_start_settings', true );
                        if( $ab_settings ){
                            if( isset( $ab_settings['variations'] ) && is_array( $ab_settings['variations'] )  ){
                                foreach( $ab_settings['variations'] as $index => $variations ){
                                    if( isset( $variations['stepId'] ) ){
                                        $ab_step_meta = $this->export_ab_step_metas( $variations['stepId'] );
                                        $ab_step_obj = get_post( $variations['stepId'] );
                                        $step_data['ab_step_data'][] = [
                                            'step_id' => $variations['stepId'],
                                            'title'         => get_the_title( $variations['stepId'] ),
                                            'type'          => get_post_meta( $variations['stepId'], '_step_type', true),
                                            'post_content'  => isset($ab_step_obj->post_content) ? $ab_step_obj->post_content : '',
                                            'page_template' => get_post_meta( $variations['stepId'], '_wp_page_template', true),
                                            'meta'          => $ab_step_meta
                                        ];
                                    }
                                }
                            }
                        }
                        $funnel_data['steps_data'][] = $step_data;
                    }
                }
                
                return $funnel_data;
            }
        }
        return false;
    }


    /**
     * Export A/B testing steps
     * 
     * @param Int $step_id
     * 
     * @return Array
     * @since  1.7.5
     */
    public function export_ab_step_metas( $step_id ){
        $step_data = [];
        if( $step_id ){
            $is_export = apply_filters( 'wpfunnels/allow_to_export_ab_step', true );
            if ( $is_export ) {
                $all_meta     = get_post_meta( $step_id );
                $step_data = array(
                    'id'            => $step_id,
                    'title'         => get_the_title( $step_id ),
                    'type'          => get_post_meta( $step_id, '_step_type', true),
                    'post_content'  => '',
                    'page_template' => get_post_meta( $step_id, '_wp_page_template', true),
                    'meta'          => $all_meta
                );
                $step_obj = get_post( $step_id );
                $step_data['post_content'] = isset($step_obj->post_content) ? $step_obj->post_content : '';
            }
        }
        return $step_data;
    } 


    /**
     * Exclude meta
     * @param array $all_meta
     * @param array $meta_key Which meta should be excluded
     *  
     */
    private function exclude_meta( $all_meta, $meta_keys ){
        if( is_array( $meta_keys ) ){
            foreach( $meta_keys as $meta_key ){
                if( isset( $all_meta[$meta_key] ) ){
                    unset( $all_meta[$meta_key] );
                }
            }
        }
        return $all_meta;
    }


    public function get_validation_data()
    {
        return [
            'logged_in' => true,
            'user_can' => 'wpf_manage_funnels',
        ];
    }
}