<?php
/**
 * Funnel module
 *
 * @package
 */
namespace WPFunnels\Modules\Admin\Funnel;

use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro;
use WC_Countries;
class Module extends Wpfnl_Admin_Module
{

    use SingletonTrait;

    private $id;

    protected $funnel;

    protected $step_module = null;

    protected $step_type;


    public function init($id)
    {
        $this->id = $id;
        $this->funnel = Wpfnl::$instance->funnel_store;
        $this->funnel->set_id($id);
    }



    public function init_ajax()
    {
		wp_ajax_helper()->handle('save-steps-order')
			->with_callback([ $this, 'save_steps_order' ])
			->with_validation($this->get_validation_data());

        wp_ajax_helper()->handle('clone-funnel')
            ->with_callback([ $this, 'clone_funnel' ])
            ->with_validation($this->get_validation_data());

        wp_ajax_helper()->handle('trash-funnel')
            ->with_callback([ $this, 'trash_funnel' ])
            ->with_validation($this->get_validation_data());

        wp_ajax_helper()->handle('restore-funnel')
            ->with_callback([ $this, 'restore_funnel' ])
            ->with_validation($this->get_validation_data());

        wp_ajax_helper()->handle('delete-funnel')
            ->with_callback([ $this, 'delete_funnel' ])
            ->with_validation($this->get_validation_data());

		wp_ajax_helper()->handle('update-funnel-status')
			->with_callback([ $this, 'update_funnel_status' ])
			->with_validation($this->get_validation_data());

        wp_ajax_helper()->handle('funnel-drag-order')
            ->with_callback([ $this, 'funnel_drag_order' ])
            ->with_validation($this->get_validation_data());

        wp_ajax_helper()->handle('wpfnl-update-funnel-settings')
            ->with_callback([ $this, 'wpfnl_update_funnel_settings' ])
            ->with_validation($this->get_validation_data());

    }


    /**
     * Return funnel object
     *
     * @return Wpfnl_Funnel_Store_Data
     * @since  1.0.0
     */
    public function get_funnel()
    {
        return $this->funnel;
    }


    /**
     * Show funnel window if the following conditions met
     *      a. if funnel exits
     *          show steps if -
     *              a. step_id exits in url
     *              b. step exits
     *              c. this funnel contains the step
     *  otherwise show 404 page
     *
     * @throws \Exception
     * @since  1.0.0
     */
    public function get_view()
    {
        if (Wpfnl_functions::check_if_module_exists($this->funnel->get_id())) {
            $step_id = filter_input(INPUT_GET, 'step_id', FILTER_SANITIZE_SPECIAL_CHARS);
            $this->funnel->read($this->id);
            $funnel = $this->get_funnel();

            if (
                $step_id
                && Wpfnl_functions::check_if_module_exists($step_id)
                && $this->funnel->check_if_step_in_funnel($step_id)
            ) {
                $this->step_type = 'landing';
            } else {
                $step_id = $funnel->get_first_step_id();
                $this->step_type = $funnel->get_first_step_type();
            }
            $is_pro_activated = Wpfnl_functions::is_wpfnl_pro_activated();
            $is_pro_module = Wpfnl_functions::is_pro_module($this->step_type);

            $is_module_registered = Wpfnl_functions::is_module_registered($this->step_type, 'steps', true, $is_pro_module);

            if ($this->step_type) {
                if ($is_pro_activated && $is_module_registered && $is_pro_module) {
                    $this->step_module = Wpfnl_Pro::$instance->module_manager->get_admin_modules($this->step_type);
                    $this->step_module->init($step_id);
                } elseif ($is_module_registered) {
                    $this->step_module = Wpfnl::$instance->module_manager->get_admin_modules($this->step_type);
                    $this->step_module->init($step_id);
                }
            }
            require_once WPFNL_DIR . '/admin/modules/funnel/views/view.php';
        } else {
            require_once WPFNL_DIR . '/admin/partials/404.php';
        }
    }


	/**
	 * Save steps order
	 *
	 * @param $payload
     *
	 * @return array
	 *
	 * @since 2.0.5
	 */
    public function save_steps_order( $payload ) {
		$funnel_id 		= isset( $payload['funnelID'] ) ? $payload['funnelID'] : 0;
		$input_node  	= $payload['inputNode'];
		$output_node  	= $payload['outputNode'];
		if( $funnel_id ) {
			$funnel_data = get_post_meta( $funnel_id, '_funnel_data', true );
		}
		return array(
			'success' => true
		);
	}

    /**
     * Funnel drag order
     *
     * @param Array $payload
     *
     * @return Array
     * @since  2.0.4
     */
    public function funnel_drag_order($payload)
    {
        $funnel_id = $payload['funnel_id'];
        $orders = $payload['order'];
        $existing_order = get_post_meta($funnel_id, '_steps_order', true);
        $step_names = apply_filters('wpfunnels_steps', [
            'landing'       => __('Landing', 'wpfnl'),
            'thankyou'      => __('Thank You', 'wpfnl'),
            'checkout'      => __('Checkout', 'wpfnl'),
            'upsell'        => __('Upsell', 'wpfnl'),
            'downsell'      => __('Downsell', 'wpfnl'),
        ]);
        $modified_order = [];
        foreach ($orders as $order) {
            $order = str_replace('setp-list-', '', $order);
            $step_type = get_post_meta($order, '_step_type', true);
            $step_array = [
                'id' => $order,
                'step_type' => $step_type,
                'name' => $step_names[$step_type],
            ];
            $modified_order[] = $step_array;
        }
        $modified_order = array_values(array_filter($modified_order));
        update_post_meta($funnel_id, '_steps_order', $modified_order);
        return [
            'success' => true,
        ];
    }


    /**
     * Trash funnel and all the
     * data
     *
     * @param $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function trash_funnel($payload)
    {
        $funnel_id = sanitize_text_field($payload['funnel_id']);

        $funnel = Wpfnl::$instance->funnel_store;
        $funnel->read($funnel_id);

        if ($funnel->get_step_ids()) {
            foreach ($funnel->get_step_ids() as $step_id) {
                $post_data = array(
                    'ID' => $step_id,
                    'post_status' => 'trash',
                );
                wp_update_post($post_data);
            }
        }
        do_action('wpfunnels/before_trash_funnel', $funnel_id );

        $post_data = array(
            'ID' => $funnel_id,
            'post_status' => 'trash',
        );
        wp_update_post($post_data);

        $redirect_link = add_query_arg(
            [
                'page' => WPFNL_FUNNEL_PAGE_SLUG,
            ],
            admin_url('admin.php')
        );
        return [
            'success' => true,
            'redirectUrl' => $redirect_link,
        ];
    }


    /**
     * Restore funnel and all the
     * data
     *
     * @param $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function restore_funnel($payload)
    {
        if( !$this->maybe_restore_allowed() ){
            $redirect_link = add_query_arg(
                [
                    'page' => WPFNL_TRASH_FUNNEL_SLUG,
                ],
                admin_url('admin.php')
            );

            return [
                'success' => true,
                'redirectUrl' => $redirect_link,
            ];
        }

        $funnel_id = sanitize_text_field($payload['funnel_id']);
        $funnel_id = sanitize_text_field($payload['funnel_id']);

		do_action('wpfunnels/before_restore_funnel', $funnel_id );

        $post_data = array(
            'ID' => $funnel_id,
            'post_status' => 'publish',
        );

        wp_update_post($post_data);


        $funnel = Wpfnl::$instance->funnel_store;
        $funnel->read($funnel_id);

        if ($funnel->get_step_ids()) {
            foreach ($funnel->get_step_ids() as $step_id) {
                $post_data = array(
                    'ID' => $step_id,
                    'post_status' => 'publish',
                );
                wp_update_post($post_data);
            }
        }

        $redirect_link = add_query_arg(
            [
                'page' => WPFNL_TRASH_FUNNEL_SLUG,
            ],
            admin_url('admin.php')
        );

        return [
            'success' => true,
            'redirectUrl' => $redirect_link,
        ];
    }

    /**
     * Check if the user can restore the funnel
     * If the user has pro license then he can restore any time
     * If the user has free license then he can restore only 3 funnels
     * If the user has more than 3 funnels then he can't restore
     * @return bool
     * @since  3.4.14
     */
    private function maybe_restore_allowed(){
        if( !Wpfnl_functions::is_pro_license_activated() ){
            $args = [
                'post_type'         => WPFNL_FUNNELS_POST_TYPE,
                'posts_per_page'    => -1,
                'suppress_filters'  => true,
                'fields'            => 'ids'
            ];
            $args['post_status'] = array('publish', 'draft');
            $all_funnels = get_posts($args);
            $total_funnels = count( $all_funnels );
            if( (int)$total_funnels < 3 ){
                return true;
            }
            return false;
        }
        return true;
    }


    /**
     * Delete funnel and all the
     * data
     *
     * @param $payload
     *
     * @return array
     * @since  1.0.0
     */
    public function delete_funnel($payload)
    {
        $funnel_id = sanitize_text_field($payload['funnel_id']);
        $funnel = Wpfnl::$instance->funnel_store;
        $funnel->read($funnel_id);

        if ($funnel->get_step_ids()) {
            foreach ($funnel->get_step_ids() as $step_id) {
                $step = Wpfnl::$instance->step_store;
                $step->delete($step_id);
            }
        }
        do_action('wpfunnels/before_delete_funnel', $funnel_id );
        $response = $funnel->delete($funnel_id);
        if ($response) {
            $redirect_link = add_query_arg(
                [
                    'page' => WPFNL_TRASH_FUNNEL_SLUG,
                ],
                admin_url('admin.php')
            );
            return [
                'success' => true,
                'redirectUrl' => $redirect_link,
            ];
        }
    }

    /**
     * Update funnel status
     *
     * @param Array $payload
     *
     * @return Array
     */
    public function update_funnel_status( $payload ) {

		if ( ! isset( $payload['funnel_id'] ) ) {
			return array(
				'message' => __( 'No funnel id found', 'wpfnl' )
			);
		}

		$funnel_id 	= sanitize_text_field($payload['funnel_id']);
		$status		= sanitize_text_field($payload['status']);
		$steps 		= get_post_meta( $funnel_id, '_steps_order', true );
		if( $steps ) {
			foreach ($steps as $step) {
				$step_data = array(
					'ID'			=> $step['id'],
					'post_status' 	=> $status
				);
				wp_update_post($step_data);
			}
		}

		$funnel_data = array(
			'ID'			=> $funnel_id,
			'post_status' 	=> $status
		);
		wp_update_post($funnel_data);

		return array(
			'success'	=> true,
			'funnel_id'	=> $funnel_id,
			'message'	=> __('Funnel status has been updated.', 'wpfnl'),
			'redirect_url'	=> admin_url('admin.php?page=wp_funnels')
		);
	}


    /**
     * Clone funnel and all the steps
     * data
     *
     * @param $payload
     * @since 1.0.0
     * @return array
     */
    public function clone_funnel($payload)
    {
        $funnel_id = sanitize_text_field($payload['funnel_id']);
        $funnel = Wpfnl::$instance->funnel_store;
        $funnel->read($funnel_id);
        $response = $funnel->clone_funnel();

        if ($response && ! is_wp_error($response)) {
            $link = add_query_arg(
                [
                    'page' => 'wp_funnels',
                    'id' => $response,
                ],
                admin_url('admin.php')
            );

            return [
                'success' => true,
                'redirectUrl' => $link,
            ];
        } else {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
    }

    /**
     * Get module name
     *
     * @return String
     */
    public function get_name()
    {
        return __('funnel','wpfnl');
    }

    /**
     * Delete marked funnel
     *
     * @param $payload
     *
     * @return array|bool
     * @since  1.0.0
     */
    public function delete_marked_funnels( $payload ) {
        $response = [];
        if (isset($payload['ids'])) {
          $data_array = $payload['ids'];
          foreach ($data_array as $data_key => $data_value) {
            $funnel_id = sanitize_text_field($data_value);
            $funnel = Wpfnl::$instance->funnel_store;
            $funnel->read($funnel_id);

            if ($funnel->get_step_ids()) {
                foreach ($funnel->get_step_ids() as $step_id) {
                    $step = Wpfnl::$instance->step_store;
                    Wpfnl_functions::maybe_delete_screenshot_by_step_id( $step_id );
                    $step->delete($step_id);
                }
            }
            /**
             * Fires before deleting a funnel to delete Mail Mint automation
             *
             * @param int funnel_id Funnel id
             * @since 2.7.18
             */
            do_action('wpfunnels/before_delete_funnel', $funnel_id );
            $response = $funnel->delete($funnel_id);

          }
        }
        if ($response) {
            $redirect_link = add_query_arg(
                [
                    'page' => WPFNL_TRASH_FUNNEL_SLUG,
                ],
                admin_url('admin.php')
            );
            return [
                'success' => true,
                'redirectUrl' => $redirect_link,
            ];
        }
        return false;
    }


    /**
     * Restore marked funnel
     *
     * @param $payload
     *
     * @return array|bool
     * @since  1.0.0
     */
    public function restore_marked_funnels( $payload ) {
        $response = [];
        if (isset($payload['ids'])) {
          $data_array = $payload['ids'];
          foreach ($data_array as $data_key => $data_value) {

            if( !$this->maybe_restore_allowed() ){
                $redirect_link = add_query_arg(
                    [
                        'page' => WPFNL_TRASH_FUNNEL_SLUG,
                    ],
                    admin_url('admin.php')
                );

                return [
                    'success' => true,
                    'redirectUrl' => $redirect_link,
                ];
            }

            $funnel_id = sanitize_text_field($data_value);
            $funnel = Wpfnl::$instance->funnel_store;
            $funnel->read($funnel_id);

            if ($funnel->get_step_ids()) {
                foreach ($funnel->get_step_ids() as $step_id) {
                    $post_data = array(
                        'ID' => $step_id,
                        'post_status' => 'publish',
                    );
                    wp_update_post($post_data);
                }
            }
            /**
             * Fires before deleting a funnel to delete Mail Mint automation
             *
             * @param int funnel_id Funnel id
             * @since 2.7.18
             */
            do_action('wpfunnels/before_restore_funnel', $funnel_id );
            $post_data = array(
                'ID' => $funnel_id,
                'post_status' => 'publish',
            );
            $response = wp_update_post($post_data);

          }
        }
        if ($response) {
            $redirect_link = add_query_arg(
                [
                    'page' => WPFNL_TRASH_FUNNEL_SLUG,
                ],
                admin_url('admin.php')
            );
            return [
                'success' => true,
                'redirectUrl' => $redirect_link,
            ];
        }
        return false;
    }

    /**
     * Trash marked funnel
     *
     * @param $payload
     *
     * @return array|bool
     * @since  1.0.0
     */
    public function trash_marked_funnels( $payload ) {
        $response = [];
        if (isset($payload['ids'])) {
          $data_array = $payload['ids'];
          foreach ($data_array as $data_key => $data_value) {
            $funnel_id = sanitize_text_field($data_value);
            $funnel = Wpfnl::$instance->funnel_store;
            $funnel->read($funnel_id);

            if ($funnel->get_step_ids()) {
                foreach ($funnel->get_step_ids() as $step_id) {
                    $post_data = array(
                        'ID' => $step_id,
                        'post_status' => 'trash',
                    );
                    wp_update_post($post_data);
                }
            }
            /**
             * Fires before deleting a funnel to delete Mail Mint automation
             *
             * @param int funnel_id Funnel id
             * @since 2.7.18
             */
            do_action('wpfunnels/before_trash_funnel', $funnel_id );
            $post_data = array(
                'ID' => $funnel_id,
                'post_status' => 'trash',
            );
            $response = wp_update_post($post_data);

          }
        }
        if ($response) {
            $redirect_link = add_query_arg(
                [
                    'page' => WPFNL_FUNNEL_PAGE_SLUG,
                ],
                admin_url('admin.php')
            );
            return [
                'success' => true,
                'redirectUrl' => $redirect_link,
            ];
        }
        return false;
    }


    /**
     * Update funnel settings by funnel id to postmeta
     */
    public function wpfnl_update_funnel_settings( $payload ){

        $response = [
            'success' => false,
        ];

        if( isset($payload['funnel_id']) ){
            if( isset($payload['utm_settings']) ){
                $payload['utm_settings']['utm_enable'] =  $payload['utm_settings']['utm_enable'] == 'true' ? 'on' : 'off';
                update_post_meta( $payload['funnel_id'] , '_wpfunnels_utm_params', $payload['utm_settings'] );
            }

            if( isset($payload['is_fb_pixel']) ){

                $payload['is_fb_pixel'] = $payload['is_fb_pixel'] == 'true' ? 'yes' : 'no';
                update_post_meta( $payload['funnel_id'] , '_wpfunnels_disabled_fb_pixel', $payload['is_fb_pixel'] );
            }

            if( isset($payload['is_gtm']) ){
                $payload['is_gtm'] = $payload['is_gtm'] == 'true' ? 'yes' : 'no';
                update_post_meta( $payload['funnel_id'] , '_wpfunnels_disabled_gtm', $payload['is_gtm'] );
            }

            if( isset($payload['skip_offer']) && isset($payload['skip_if_quantity']) ){
                $skip_offer = [
                    'skip_offer'        => $payload['skip_offer'] == 'true' ? 'yes' : 'no',
                    'skip_if_quantity'  => $payload['skip_if_quantity'] == 'true' ? 'yes' : 'no',
                ];
                update_post_meta( $payload['funnel_id'] , '_wpfunnels_skip_offer', $skip_offer );
            }

            if( !empty($payload['skip_recurring_offer_within_days']) ){
                update_post_meta( $payload['funnel_id'] , '_wpfunnels_skip_recurring_offer_within_days', sanitize_text_field($payload['skip_recurring_offer_within_days']) );
            }

            if( !empty($payload['skip_recurring_offer']) ){
                update_post_meta( $payload['funnel_id'] , '_wpfunnels_skip_recurring_offer', $payload['skip_recurring_offer'] );
            }


            $response = [
                'success' => true,
                'data'    => 'Save successfull',
            ];

        }

        return $response;
    }


}
