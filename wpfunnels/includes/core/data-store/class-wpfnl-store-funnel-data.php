<?php
/**
 * Store funnel data
 *
 * @package
 */
namespace WPFunnels\Data_Store;

use Elementor\Plugin;
use WP_Error;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Funnel_Store_Data extends Wpfnl_Abstract_Store_data implements Wpfnl_Data_Store
{
	protected $id = 0;

	protected $funnel_name;

	protected $published_date;

	protected $data;

	protected $steps_order = [];

	protected $nr_steps = 0;

	protected $status;

	protected $step_ids = [];

	protected $first_step_id = 0;

	protected $first_step_type;

	protected $current_step_id;

	/**
	 * Create funnel. This will create funnel without steps
	 *
	 * @param string $funnel_name
	 *
	 * @return int|\WP_Error
	 *
	 * @since 1.0.0
	 */
	public function create( $funnel_name = '' )
	{
		$funnel_id = wp_insert_post(
			apply_filters(
				'wpfunnels/wpfunnels_new_funnel_params',
				[
					'post_type'     => WPFNL_FUNNELS_POST_TYPE,
					'post_status'   => 'publish',
					'post_title'    => $funnel_name ? wp_strip_all_tags( $funnel_name ) : 'New Funnel',
				]
			),
			true
		);
		if ($funnel_id && ! is_wp_error($funnel_id)) {

			$general_settings = get_option( '_wpfunnels_general_settings' );
			if( isset($general_settings['funnel_type']) ){
				if( 'woocommerce' == $general_settings['funnel_type'] ){
					$general_settings['funnel_type'] = 'sales';
					update_option( '_wpfunnels_general_settings', $general_settings );
				}
				if( 'sales' == $general_settings['funnel_type'] ){
					if( Wpfnl_functions::is_wc_active() ){
						update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'wc' );
					}else{
						update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'lms' );
					}

				}else{
					update_post_meta( $funnel_id, '_wpfnl_funnel_type', 'lead' );
				}
			}
			$this->set_id($funnel_id);
		}

		do_action('wpfunnels_after_funnel_creation');
		return $funnel_id;
	}


	/**
	 * Clone funnel with all the
	 * steps and meta data
	 * derived from https://rudrastyh.com/wordpress/duplicate-post.html
     *
	 * @since 1.0.0
	 */
	public function clone_funnel()
	{
		global $wpdb;
		$funnel_id = wp_insert_post(
			apply_filters(
				'wpfunnels/wpfunnels_new_funnel_params',
				[
					'post_title'    => wp_strip_all_tags($this->get_funnel_name()).' - Copy',
					'post_type'     => WPFNL_FUNNELS_POST_TYPE,
					'post_status'   => 'publish',
				]
			),
			true
		);
		$parent_id 						= $this->get_id();
		$funnel_data 					= $this->get_meta($this->get_id(), '_funnel_data');
		$funnel_identifier 				= $this->get_meta($this->get_id(), 'funnel_identifier');
		$funnel_identifier_to_string	= preg_replace('/\: *([0-9]+\.?[0-9e+\-]*)/', ':"\\1"', $funnel_identifier);
		$funnel_identifier_json_to_data = json_decode($funnel_identifier_to_string, true);
		$exclude_meta 					= array( '_is_imported', '_steps_order', '_steps', '_funnel_data', 'wpfnls_is_newui_migrated','wpfnl_mint_automation_id' );
		$compoare_step_ids = array();

		foreach ($funnel_identifier_json_to_data as $funnel_identifier_key => $funnel_identifier_value) {
			$identifier_data = get_post_meta( $parent_id, $funnel_identifier_value, true );
			if ($identifier_data) {
				$exclude_meta[] = $funnel_identifier_value;
				$this->update_meta($funnel_id, $funnel_identifier_value, $identifier_data);
			}
		}

		$this->duplicate_all_meta( $this->get_id(), $funnel_id, $exclude_meta, true );
		$this->update_meta($funnel_id, '_funnel_data', $funnel_data);
		$this->update_meta($funnel_id, 'funnel_data', $funnel_data);
		$this->update_meta($funnel_id, 'funnel_identifier', $funnel_identifier);


		if ($funnel_id && ! is_wp_error($funnel_id)) {
			$step_order = $this->get_order();
			$this->set_step_ids();
			$_new_step_ids = [];
			foreach ($step_order as $order) {
				if (Wpfnl_functions::check_if_module_exists($order['id'])) {
					if ($this->check_if_step_in_funnel($order['id'])) {
						$sql_query_sel = [];
						$_step_id = $order['id'];
						$title = get_the_title($_step_id);
						$page_template = get_post_meta($_step_id, '_wp_page_template', true);
						$post_content = get_post_field('post_content', $_step_id);

						$step = new Wpfnl_Steps_Store_Data();
						$step_id = $step->create_step($funnel_id, $title, $order['step_type'], $post_content, true);

						$compoare_step_ids[$_step_id] = $step_id;
						$builder = Wpfnl_functions::get_builder_type();

						$_new_step_ids[] = [
							'id'        => $step_id,
							'step_type' => $order['step_type'],
							'name'      => $order['name'],
						];
						$step->update_meta($step_id, '_funnel_id', $funnel_id);
						$this->duplicate_all_meta( $_step_id, $step_id, array('_funnel_id','_is_duplicate','wpfnl_mint_automation_id') );
						
						/**
						 * Save the new step information on funnel data and funnel identifier.
						 * This is required to show steps in funnel canvas
						 */

						$this->update_step_id_in_funnel_data_and_identifier($_step_id, $step_id, $funnel_id);

						delete_post_meta($step_id, '_wp_page_template');
						$step->update_meta($step_id, '_wp_page_template', $page_template);
						$step->update_meta($step_id, '_is_duplicate', 'yes');

						$step_edit_link =  get_edit_post_link($step_id);
						if( 'elementor' ===  Wpfnl_functions::get_builder_type() ){
							$step_edit_link = str_replace(['&amp;', 'edit'], ['&', 'elementor'], $step_edit_link);
							if ( is_plugin_active( 'elementor/elementor.php' ) && class_exists( '\Elementor\Plugin' ) ) {
								Plugin::$instance->files_manager->clear_cache(); // Clearing cache of Elementor CSS.
							}
						}

						/**
						 * Fires after duplicating an A/B testing step in WP Funnels.
						 *
						 * @param int    $step_id The ID of the duplicated step.
						 * @param string $builder The name of the builder used for the duplication.
						 */
						do_action('wpfunnels_after_ab_testing_duplicate', $step_id, $builder);


						/**
						 * Fires after a step is duplicated in a funnel.
						 *
						 * @param int $funnel_id The ID of the funnel.
						 * @param int $step_id   The ID of the duplicated step.
						 *
						 * @since 3.1.0
						 */
						do_action('wpfunnels/after_step_duplicate', $funnel_id, $step_id );

					}
				}
			}
			$_new_step_ids = array_values(array_filter($_new_step_ids));
			$this->update_meta( $funnel_id, '_steps', $_new_step_ids );
			$this->update_meta( $funnel_id, '_steps_order', $_new_step_ids );

		}
		//duplicate funnel automation event settings
		$this->duplicate_automation_event_settings( $parent_id , $funnel_id , $compoare_step_ids );
		do_action( 'wpfunnels/after_save_funnel_data', $funnel_id );
		Wpfnl_functions::generate_first_step( $funnel_id, [] );
		return $funnel_id;
	}


	/**
	 * Duplicate all meta key and values
     *
	 * @param $parent_id
	 * @param $post_id
	 * @param array $exclude_meta
	 * @param bool $raw
	 */
	public function duplicate_all_meta( $parent_id, $post_id, $exclude_meta = array(), $raw = false ) {
		global $wpdb;
		$exclude_sql = '';
		if( !empty($exclude_meta) ) {
			$metas 			= implode("', '",$exclude_meta );
			$exclude_sql 	= "AND meta_key NOT IN ('".$metas."')";
		}
		$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE (post_id=$parent_id {$exclude_sql})");
		if (count($post_meta_infos)!=0) {
			$insert_sql_query   = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES";
			$sql_query_arr      = [];
			foreach ($post_meta_infos as $meta_info) {
				$meta_key = $meta_info->meta_key;

				if( '_wp_old_slug' === $meta_key) continue;
				if( 'funnel_automation_data' === $meta_key ) continue;

				if ( '_wpfnl_ab_testing_start_settings' === $meta_key ){
					$meta_value = get_post_meta( $parent_id, $meta_key,true );

					$variations = isset( $meta_value['variations'] ) ? $meta_value['variations'] : array();

					$step = new Wpfnl_Steps_Store_Data();
					for ($i = 0; $i < count($variations); $i++){
						
						$id           = isset( $variations[$i]['stepId'] ) ? $variations[$i]['stepId'] : 0;
						$title        = isset( $variations[$i]['stepName'] ) ? $variations[$i]['stepName'] : '' ;
						$step_type    = isset( $variations[$i]['stepType'] ) ? $variations[$i]['stepType'] : '' ;
						$post_content = get_post_field('post_content', $id);

						if (isset($variations[$i]['variationType']) && 'original' === $variations[$i]['variationType']){
							$step_id = $post_id;
						}else {
							
							$step_id = $step->create_step($post_id, $title, $step_type, $post_content, true);
							// If step can't be created, continue to try creating next variation
							if ( !$step_id || $step_id instanceof WP_Error ){
								continue;
							}

							$this->duplicate_elementor_step_meta( $id, $step_id);
							$this->duplicate_product_data_for_each_step($step_type, $id, $step_id);
							$this->duplicate_all_meta( $id, $step_id, array('_wpfnl_ab_testing_start_settings') );
							update_post_meta($step_id, '_parent_step_id', $post_id);

							// Get the funnel id from variation parent step id
							$funnel_id = Wpfnl_functions::get_funnel_id_from_step( $post_id );
							update_post_meta($step_id, '_funnel_id', $funnel_id);
						}
						$this->update_step_id_in_funnel_data_and_identifier($id, $step_id, $post_id);

						$meta_value['variations'][$i]['stepId']         = $step_id;
						$meta_value['variations'][$i]['stepEditLink'] 	=  get_edit_post_link($step_id);
						$meta_value['variations'][$i]['stepViewLink'] 	=  get_post_permalink($step_id);
					
					}
					
					update_post_meta($post_id, $meta_key, $meta_value);

					continue;
				}

				if ( $raw ) {
					$meta_value = get_post_meta( $parent_id, $meta_key,true );

					update_post_meta($post_id, $meta_key, $meta_value);
				}

				$sql_query_arr[] = $wpdb->prepare( '( %d, %s, %s )', $post_id, $meta_key, $meta_info->meta_value );

			}

			if (!$raw) {
				$insert_sql_query .= implode( ',', $sql_query_arr );
				$wpdb->query( $insert_sql_query );
			}
		}
	}



	/**
	 * Duplicate automation event settings
	 *
	 * @param String $parent_id
	 * @param String $post_id
	 * @param Array $compoare_step_ids
	 */
	private function duplicate_automation_event_settings( $parent_id , $post_id, $compoare_step_ids ){

		$prev_settings = get_post_meta( $parent_id , 'funnel_automation_data', true );

		if( $prev_settings ){
			foreach( $prev_settings as $key => $settings ){
				foreach( $settings['triggers'] as $index => $trigger ){
					if( $prev_settings[$key]['triggers'][$index][0]['type'] == 'offer' ){

						$prev_settings[$key]['triggers'][$index][0]['event']  = str_replace($prev_settings[$key]['triggers'][$index][0]['stepID'],$compoare_step_ids[$prev_settings[$key]['triggers'][$index][0]['stepID']],$prev_settings[$key]['triggers'][$index][0]['event']);
						$prev_settings[$key]['triggers'][$index][0]['stepID'] = $compoare_step_ids[$prev_settings[$key]['triggers'][$index][0]['stepID']];
					}
				}
			}
			update_post_meta( $post_id, 'funnel_automation_data', $prev_settings );
		}
	}

	/**
	 * Duplicate Meta of Each Step related to Elementor style
	 *
	 * @param int $id parent id
	 * @param int $new_id duplicated id
	 *
	 * @since 2.7.7
	 */
	public function duplicate_elementor_step_meta($id, $new_id){
		$meta_keys = array(
			'_elementor_edit_mode',
			'_elementor_template_type',
			'_elementor_version',
			'_elementor_data',
			'_elementor_page_assets',
			'_elementor_css'
		);

		foreach ($meta_keys as $meta_key) {
			$meta_value = get_post_meta($id, $meta_key, true);
			update_post_meta($new_id, $meta_key, $meta_value);
		}
	}

	/**
	 * Duplicate Meta of Each Step related to Woo-products
	 *
	 * @param string $type step type
	 * @param int $id parent id
	 * @param int $new_id duplicated id
	 *
	 * @since 2.7.7
	 */
	public function duplicate_product_data_for_each_step($type, $id, $new_id){
		$meta_fields = [
			'checkout' => [
				'_wpfnl_checkout_products',
				'_wpfnl_checkout_discount_main_product',
				'order-bump-settings',
				'_wpfnl_checkout_coupon',
				'_wpfnl_multiple_product',
				'_wpfnl_quantity_support',
				'wpfnl_checkout_billing_fields',
				'wpfnl_checkout_shipping_fields',
				'wpfnl_checkout_additional_fields',
				'_wpfunnels_edit_field_additional_settings',
			],
			'upsell' => [
				'_wpfnl_upsell_products',
				'_wpfnl_upsell_discount',
				'_wpfnl_upsell_replacement_settings',
				'_wpfnl_upsell_replacement',
			],
			'downsell' => [
				'_wpfnl_downsell_products',
				'_wpfnl_downsell_discount',
				'_wpfnl_downsell_replacement_settings',
				'_wpfnl_downsell_replacement',
			],
			'thankyou' => [
				'_wpfnl_thankyou_order_overview',
				'_wpfnl_thankyou_order_details',
				'_wpfnl_thankyou_billing_details',
				'_wpfnl_thankyou_shipping_details',
				'_wpfnl_thankyou_is_custom_redirect',
				'_wpfnl_thankyou_is_direct_redirect',
				'_wpfnl_thankyou_set_time',
				'_wpfnl_thankyou_custom_redirect_url'
			]
		];

		if ( isset($meta_fields[$type] )) {
			foreach ( $meta_fields[$type] as $meta_key ) {
				$meta_value = get_post_meta($id, $meta_key, true);
				update_post_meta($new_id, $meta_key, $meta_value);
			}
		}
	}


	/**
	 * Update funnel data and funnel
	 * window for canvas
	 *
	 * @param $prev_step_id
	 * @param $new_step
	 * @param $funnel_id
	 *
	 * @since 2.0.0
	 */
	public function update_step_id_in_funnel_data_and_identifier($prev_step_id, $new_step, $funnel_id)
	{
		$funnel_identifier = array();
		$funnel_json = get_post_meta($funnel_id, '_funnel_data', true);
		$funnel_data = array();
		if ($funnel_json) {
			$funnel_data = $funnel_json;
			$node_data = $funnel_data['drawflow']['Home']['data'];
			foreach ($node_data as $node_key => $node_value) {
				if(isset($node_value['data']['step_id'])) {
					if ($node_value['data']['step_id'] == $prev_step_id) {
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
						}
					}
				}
				if( $node_value['data']['step_type'] == 'conditional' ) {
					$funnel_identifier[$node_value['id']] = $node_value['data']['node_identifier'];
				}
			}
		}
		update_post_meta($funnel_id, '_funnel_data', $funnel_data);

		if ($funnel_identifier) {
			$funnel_identifier_json = json_encode($funnel_identifier, JSON_UNESCAPED_SLASHES);
			update_post_meta($funnel_id, 'funnel_identifier', $funnel_identifier_json);
		}
	}


	/**
	 * Delete funnel with all its
	 * data
	 *
	 * @param $id
	 *
	 * @return bool|void
	 */
	public function delete($id)
	{
		wp_delete_post($id);
		$this->set_step_ids();
		foreach ($this->step_ids as $step_id) {
			wp_delete_post($step_id);
		}
		return true;
	}


	public function read($id)
	{
		$funnel = get_post($id);
		if ($funnel) {
			$this->set_data($funnel);
		} else {
			$this->error('funnel_not_found', __('Invalid Funnel', 'wpfnl'));
		}
	}


	/**
	 * Init all required data for a complete
	 * funnel object
	 *
	 * @param \WP_Post $funnel
	 *
	 * @since 1.0.0
	 */
	public function set_data(\WP_Post $funnel)
	{
		$step_id = filter_input(INPUT_GET, 'step_id', FILTER_SANITIZE_SPECIAL_CHARS);
		$this->current_step_id = $step_id;
		$this->set_id($funnel->ID);
		$this->funnel_name = $funnel->post_title ? $funnel->post_title : 'No title';
		$this->status = Wpfnl_functions::get_formatted_status($funnel->post_status);
		if (Wpfnl_functions::validate_date( $funnel->post_date )) {
			$this->published_date = Wpfnl_functions::get_formatted_date($funnel->post_date);
		}
		$this->set_step_ids();
		$this->set_steps_order();
		$this->set_fisrt_step_info();
	}


	/**
	 * All the steps ids within
	 * the funnel
	 *
	 * @since 1.0.0
	 */
	public function set_step_ids()
	{

		$this->step_ids = get_posts(
			[
				'numberposts'   => -1,
				'post_type'     => WPFNL_STEPS_POST_TYPE,
				'post_status'   => array('publish', 'draft','trash'),
				'fields'        => 'ids',
				'meta_query'    => [
					[
						'key'   => '_funnel_id',
						'value' => $this->id,
					]
				]
			]
		);
	}



	/**
	 * Set step order for funnel
	 *
	 * @since 1.0.0
	 */
	public function set_steps_order()
	{
		$steps_order = get_post_meta($this->id, '_steps_order', true);
		$_steps_order = Wpfnl_functions::unserialize_array_data($steps_order);
		// check if step exits and status is published
		if( is_array($_steps_order) ){
			$this->steps_order = array_filter($_steps_order, function ($item) {
				if (isset($item['id']) && Wpfnl_functions::check_if_module_exists($item['id'])) {
					return true;
				}
				return false;
			});
		}
		
		if ($steps_order) {
			$this->nr_steps = count($this->steps_order);
		} else {
			$this->steps_order = [];
		}
	}


	public function get_steps_order() {
		return $this->steps_order;
	}

	/**
	 * Reinit the step order of the funnel
	 * if any step is deleted
	 *
	 * @since 1.0.0
	 */
	public function reinitialize_steps_order()
	{
		if (count($this->get_order())) {
			$_steps_order = Wpfnl_functions::unserialize_array_data($this->get_order());
			$steps_order = array();
			// check if step exits and status is published
			if($_steps_order) {
				foreach ($_steps_order as $key => $step) {
					if (Wpfnl_functions::check_if_module_exists($step['id'])) {
						$steps_order[$key] = $step;
					}
				}
			}

			$this->steps_order = $steps_order;
			$this->update_meta($this->get_id(), '_steps_order', $steps_order);
		}
	}


	/**
	 * Save store order of the funnel
	 *
	 * @param $step_id
	 * @param $step_type
	 * @param string $title
	 *
	 * @since 1.0.0
	 */
	public function save_steps_order($step_id, $step_type, $title='')
	{
		$this->steps_order[] = [
			'id' => $step_id,
			'step_type' => $step_type,
			'name' => get_the_title($step_id),
		];
		$this->update_meta($this->get_id(), '_steps_order', $this->steps_order);
	}


	/**
	 * Set the first step of the
	 * funnel if any exists
	 *
	 * @since 1.0.0
	 */
	public function set_fisrt_step_info()
	{
		if (count($this->steps_order)) {
			$first_step = reset($this->steps_order);
			$this->first_step_type = $first_step['step_type'];
			$this->first_step_id = $first_step['id'];
		}
	}


	public function get_first_step_type()
	{
		return $this->first_step_type;
	}


	public function get_first_step_id()
	{
		return $this->first_step_id;
	}


	/**
	 * Update funnel name
	 *
	 * @param $name
	 *
	 * @since 1.0.0
	 */
	public function update_funnel_name($name)
	{
		$funnel = [
			'ID'           => $this->id,
			'post_title'   => $name,
		];
		wp_update_post($funnel);
	}



	public function get_id()
	{
		return $this->id;
	}


	public function get_funnel_name()
	{
		return $this->funnel_name;
	}


	public function get_published_date()
	{
		return $this->published_date;
	}


	public function get_data()
	{
		return $this->data;
	}


	public function get_order()
	{
		return $this->steps_order;
	}


	public function get_total_steps()
	{
		return $this->nr_steps;
	}


	public function get_status()
	{
		return $this->status;
	}

	public function get_step_ids()
	{
		return $this->step_ids;
	}

	public function get_current_step_id()
	{
		return $this->current_step_id;
	}


	public function check_if_step_in_funnel($step_id)
	{
		return in_array($step_id, $this->step_ids);
	}
}
