<?php
/**
 * Remote class
 *
 * @package
 */
namespace WPFunnels\TemplateLibrary;

use WPFunnels\API;
use WPFunnels\Rest\Controllers\TemplateLibraryController;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;
use function cli\err;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
class Wpfnl_Source_Remote extends Wpfnl_Source_Base
{

    /**
     * Get souce
     *
     * @return String
     */
    public function get_source()
    {
        return 'remote';
    }

    /**
     * Get funnels
     *
     * @return Obj
     */
    public function get_funnels($arg = [])
    {
        return API::get_funnels_data($arg);
    }


    /**
     * Get funnel
     */
    public function get_funnel($template_id)
    {
    }

    /**
     * Get data
     *
     * @param Array $args
     *
     * @return String
     */
    public function get_data(array $args)
    {
    }


	/**
	 * Import funnel
	 *
	 * @param array $args
     *
	 * @return array
	 */
    public function import_funnel($args = [])
    {
        $funnel 	= Wpfnl::$instance->funnel_store;
        $funnel_id 	= $funnel->create();
        $funnel->update_meta($funnel_id, '_is_imported', 'yes');

		if( $funnel_id ){
			$general_settings = get_option( '_wpfunnels_general_settings' );
			if( isset( $general_settings['funnel_type'] ) ){
				if( 'woocommerce' == $general_settings['funnel_type'] ){
					$general_settings['funnel_type'] = 'sales';
					update_option( '_wpfunnels_general_settings', $general_settings );
				}
				if( 'sales' == $general_settings['funnel_type'] ){
					if( Wpfnl_functions::is_lms_addon_active() && isset($args['type']) && 'lms' === $args['type'] ){
						update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'lms' );
					} elseif( Wpfnl_functions::is_wc_active() && isset($args['type']) && 'wc' === $args['type'] ){
						update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'wc' );
					} elseif( isset($args['type']) && 'lead' === $args['type'] ){
						update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'lead' );
					}
				}else{
					if( isset($args['type']) && 'lead' === $args['type'] ){
                        update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'lead' );
                    }
				}
			}
		}


		$remote_id 		= isset($args['remoteID']) ? $args['remoteID'] : 0;
		if ( !$remote_id ) {
			return array(
				'success' => false,
			);
		}
		$response		= TemplateLibraryController::get_funnel( $remote_id );
		$funnel_data 	= isset($response['_funnel_data']) ? $response['_funnel_data'] : $response['funnel_data'];

		update_post_meta( $funnel_id, '_funnel_data', $funnel_data );

        $funnel_title = isset($args['name']) ? $args['name'] : '';
        $params = array(
            'ID' => $funnel_id,
            'post_title' => $funnel_title,
            'post_name' => sanitize_title($funnel_title),
        );
        wp_update_post($params);
        return [
            'success' => true,
            'funnelID' => $funnel_id,
        ];
    }


	/**
	 * Import step
	 *
	 * @param array $args
     *
	 * @return array
	 */
    public function import_step( $args = [] )
    {

        if (empty($args['funnelID'])) {
            return [
                'success' => true,
                'message' => __('No funnel id found', 'wpfnl'),
            ];
        }

        do_action('wpfunnel_step_import_start');
        $response = TemplateLibraryController::get_step($args['step']['ID']);
        $isSingleStep = isset($args['isSingleStep']) && 'yes' === $args['isSingleStep'] ? true : false;
        $title = $response['title'];
        $post_content = $response['content'];
        $post_metas = $response['post_meta'];
       
        $builder = Wpfnl_functions::get_builder_type();

        $step = Wpfnl::$instance->step_store;
        $step_id = $step->create_step($args['funnelID'], $title, $args['step']['step_type'], $post_content);
        $step->import_metas($step_id, $post_metas, $isSingleStep );


        // re-signing the shortcode signature keys if builder type is oxygen
        if( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
        	$ct_shortcodes 	= get_post_meta( $step_id, 'ct_builder_shortcodes', true );
			$ct_shortcodes 	= parse_shortcodes($ct_shortcodes, false, false);
			$shortcodes = parse_components_tree($ct_shortcodes['content']);
			update_post_meta($step_id, 'ct_builder_shortcodes', $shortcodes);
		}

		if ( 'divi-builder' === Wpfnl_functions::get_builder_type() ) {
			if ( isset( $response['data']['divi_content'] ) && ! empty( $response['data']['divi_content'] ) ) {
				update_post_meta( $step_id, 'divi_content', $response['data']['divi_content'] );
				wp_update_post(
					array(
						'ID' 			=> $step_id,
						'post_content' 	=> $response['data']['divi_content']
					)
				);
			}
		}

        if ( 'gutenberg' === Wpfnl_functions::get_builder_type() ) {
			if ( isset( $response['data']['rawData'] ) && ! empty( $response['data']['rawData'] ) ) {
				wp_update_post(
					array(
						'ID' => $step_id,
						'post_content' => $response['data']['rawData']
                	)
				);
			}
        }

        $funnel = Wpfnl::$instance->funnel_store;
        $funnel->set_id($args['funnelID']);
        $funnel->set_steps_order();
        $funnel->save_steps_order( $step_id, $args['step']['step_type'], $title );

		if( isset($args['importType']) && $args['importType'] === 'templates' ) {
			$this->update_step_id_in_funnel_data_and_identifier( $args['step']['ID'], $step_id, $args );
		}
        do_action('wpfunnels/update_ab_testing_start_settings', $step_id);
        do_action('wpfunnels_step_import_complete');
        do_action('wpfunnels_after_step_import', $step_id, $builder);
		update_post_meta($step_id, '_wp_page_template', 'wpfunnels_default');
        $funnel_id = $args['funnelID'];
        $utm_settings = Wpfnl_functions::get_funnel_utm_settings( $funnel_id );
	    $view_link    = get_post_permalink( $step_id );

	    if ( $utm_settings[ 'utm_enable' ] == 'on' ) {
		    unset( $utm_settings[ 'utm_enable' ] );
		    $view_link = add_query_arg( $utm_settings, $view_link );
		    $view_link = strtolower( $view_link );
	    }

        $response = [
            'success' 		=> true,
            'stepID' 		=> $step_id,
			'stepEditLink'	=> get_edit_post_link($step_id),
			'stepViewLink'	=> $view_link,
            'abTestingSettingsData'=> $this->get_default_start_setting($step_id),
        ];

        if( isset( $args['lastClickedAddStep'] ) ){
            $reconfigureSettings = get_post_meta( $funnel->get_id(),'_wpfnl_reconfigurable_condition_data', true);
            if( is_array($reconfigureSettings) && !empty($reconfigureSettings) ){
                $key = array_search($args['lastClickedAddStep'], array_column($reconfigureSettings, 'nodeId'));
                if( false !== $key ){
                    $reconfigureSettings[$key]['step_id'] = $step_id;
                    // update_post_meta( $funnel->get_id(),'_wpfnl_reconfigurable_condition_data', $reconfigureSettings );
                    $response['reconfigureSettings'] = $reconfigureSettings;
                    // update_post_meta( $step_id, '_wpfnl_maybe_enable_condition', $reconfigureSettings[$key]['is_enable'] );
                }
            }
        }
        update_post_meta($step_id, '_step_type', $args['step']['step_type']);
        return $response;
    }

    /**
     * Import variation step
     *
     * @param Array $args
     *
     * @return Array
     */
    public function import_variation_step( $args = [] )
    {
        if (empty($args['funnelID'])) {
            return [
                'success' => true,
                'message' => __('No funnel id found', 'wpfnl'),
            ];
        }
        do_action('wpfunnel_step_import_start');
        $response = TemplateLibraryController::get_step($args['step']['id']);

        $title = !empty($args['step_name']) ? $args['step_name'] : $response['title'];
        $post_content = $response['content'];
        $post_metas = $response['post_meta'];

        $builder = Wpfnl_functions::get_builder_type();

        $step = Wpfnl::$instance->step_store;
        $step_id = $step->create_step($args['funnelID'], $title, $args['step']['step_type'], $post_content,false);
        $step->import_metas($step_id, $post_metas);

        if( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
        	$ct_shortcodes 	= get_post_meta( $step_id, 'ct_builder_shortcodes', true );
			$ct_shortcodes 	= parse_shortcodes($ct_shortcodes, false, false);
			$shortcodes = parse_components_tree($ct_shortcodes['content']);
			update_post_meta($step_id, 'ct_builder_shortcodes', $shortcodes);
		}

		if ( 'divi-builder' === Wpfnl_functions::get_builder_type() ) {
			if ( isset( $response['data']['divi_content'] ) && ! empty( $response['data']['divi_content'] ) ) {
				update_post_meta( $step_id, 'divi_content', $response['data']['divi_content'] );
				wp_update_post(
					array(
						'ID' 			=> $step_id,
						'post_content' 	=> $response['data']['divi_content']
					)
				);
			}
		}

        if ( 'gutenberg' === Wpfnl_functions::get_builder_type() ) {
			if ( isset( $response['data']['rawData'] ) && ! empty( $response['data']['rawData'] ) ) {
				wp_update_post(
					array(
						'ID' => $step_id,
						'post_content' => $response['data']['rawData']
                	)
				);
			}
        }

        $funnel = Wpfnl::$instance->funnel_store;
        $funnel->set_id($args['funnelID']);

        /**
         * Update ab testig settings
         *
         * @param Integer $args['step_id'] - this is parent step id
         * @param Integer $step_id - this is parent variant id
         */
        do_action('wpfunnels/update_ab_testing_settings', $args['step_id'], $step_id );
        do_action('wpfunnels_step_import_complete');
        do_action('wpfunnels_after_step_import', $step_id, $builder);
		update_post_meta($step_id, '_wp_page_template', 'wpfunnels_default');

        $response = [
            'success' 		=> true,
            'stepID' 		=> $step_id,
			'stepEditLink'	=> get_edit_post_link($step_id),
			'stepViewLink'	=> get_permalink($step_id),
        ];

        return apply_filters('wpfunnels/modify_import_variant_response', $response, $args['step_id'] );

    }


    /**
     * Get default start settings
     */
    public function get_default_start_setting( $step_id ){
        $step_edit_link =  get_edit_post_link($step_id);

	    $funnel_id    = Wpfnl_functions::get_funnel_id_from_step( $step_id );
	    $utm_settings = Wpfnl_functions::get_funnel_utm_settings( $funnel_id );
	    $view_link    = get_post_permalink( $step_id );

	    if ( $utm_settings[ 'utm_enable' ] == 'on' ) {
		    unset( $utm_settings[ 'utm_enable' ] );
		    $view_link = add_query_arg( $utm_settings, $view_link );
		    $view_link = strtolower( $view_link );
	    }

        if( 'elementor' ==  Wpfnl_functions::get_builder_type() ){
            $step_edit_link = str_replace('/&amp;/g','&',$step_edit_link);
            $step_edit_link = str_replace('edit','elementor',$step_edit_link);
        }

        $default_settings = [
            'is_ab_enabled'         => '',
            'start_settings'        => [
                'auto_winner' => [
                    'is_enabled' => '',
                    'conditions' => [
                        'index'  => 'trafiic',
                        'value'  =>   70
                    ]
                ],
                'winner'      => '',
                'is_started'  => '',
                'start_date'  => date( 'Y-m-d H:i:s' ),
                'variations'  => [
                    [
                        'id'      => $step_id,
                        'traffic' => 100,
                        'step_type'=>  get_post_meta($step_id,'_step_type',true),
                        'variation_type'     => 'original',
                        'step_edit_link'   		=> $step_edit_link,
                        'step_view_link'   		=> $view_link,
                        'step_title'       		=> get_the_title($step_id),
                        'conversion'       		=> 0,
                        'visit'       			=> 0,
                        'shouldShowAnalytics' 	=> false,
                    ],
                ],
            ]

        ];

        return $default_settings;
    }


    /**
     * Update funnel identifier
     *
     * @param $remote_step
     * @param $new_step
     * @param $args
     *
     * @return void
     */
    public function update_step_id_in_funnel_data_and_identifier( $remote_step, $new_step, $args )
    {
        $funnel_id 			= $args['funnelID'];
        $funnel_identifier 	= array();
		$funnel_data 		= array();
        $funnel_json 		= get_post_meta( $funnel_id, '_funnel_data', true ) ? get_post_meta( $funnel_id, '_funnel_data', true ) : get_post_meta( $funnel_id, 'funnel_data', true );

        if ($funnel_json) {
            if(is_array($funnel_json)) {
                $funnel_data = $funnel_json;
            } else {
                $funnel_data = json_decode($funnel_json,1);
            }
            $node_data = $funnel_data['drawflow']['Home']['data'];
            foreach ($node_data as $node_key => $node_value) {
				if ( isset($node_value['data']['step_id']) && $node_value['data']['step_id'] == $remote_step ) {
					$post_edit_link = base64_encode(get_edit_post_link($new_step));
					$post_view_link = base64_encode(get_post_permalink($new_step));
					$funnel_data['drawflow']['Home']['data'][$node_key]['data']['step_id'] = $new_step;
					$funnel_data['drawflow']['Home']['data'][$node_key]['data']['step_edit_link'] = $post_edit_link;
					$funnel_data['drawflow']['Home']['data'][$node_key]['data']['step_view_link'] = $post_view_link;
					$funnel_data['drawflow']['Home']['data'][$node_key]['html'] = $node_value['data']['step_type'] . $new_step;
					$funnel_identifier[$node_value['id']] = $new_step;
				} else {
					if ($node_value['data']['step_type'] != 'conditional') {
						$funnel_identifier[$node_value['id']] = $node_value['data']['step_id'];
					} else {
						$funnel_identifier[$node_value['id']] = $node_value['data']['node_identifier'];
					}
				}
            }
        }

        if ( $funnel_data ) {

            update_post_meta($funnel_id, '_funnel_data', $funnel_data);
        }

        if ($funnel_identifier) {
            $funnel_identifier_json = json_encode($funnel_identifier, JSON_UNESCAPED_SLASHES);
            update_post_meta($funnel_id, 'funnel_identifier', $funnel_identifier_json);
        }
    }



    private function save_step_meta_data( $funnel_id, $step_id ) {
    	if ( !$funnel_id || !$step_id ) {
			return;
		}

    	$step_title = get_the_title( $step_id );
    	$step_type	= get_post_meta( $step_id, '_step_type', true );

	}


	/**
	 * Get steps
	 *
	 * @param $funnel_flow_data
     *
	 * @return array
	 *
	 * @since 2.0.5
	 */
	private function get_steps( $funnel_flow_data ) {
		$drawflow		= $funnel_flow_data['drawflow'];
		$steps 			= array();
		if( isset( $drawflow['Home']['data'] ) ) {
			$drawflow_data = $drawflow['Home']['data'];
			foreach ( $drawflow_data as $key => $data ) {
				$step_data 	= $data['data'];
				$step_type 	= $step_data['step_type'];
				if('conditional' !== $step_type) {
					$step_id 	= $step_data['step_id'];
					$step_name	= sanitize_text_field(get_the_title($step_data['step_id']));
					$steps[]	= array(
						'id'		=> $step_id,
						'step_type'	=> $step_type,
						'name'		=> $step_name,
					);
				}

			}
		}
		return $steps;
	}
}
