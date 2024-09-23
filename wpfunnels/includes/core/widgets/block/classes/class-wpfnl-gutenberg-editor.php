<?php
/**
 * Gutenberg block
 * 
 * @package WPFunnels\Widgets\Gutenberg
 */
namespace WPFunnels\Widgets\Gutenberg;

use WPFunnels\Meta\Wpfnl_Default_Meta;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Gutenberg_Editor {

	use SingletonTrait;

	public function __construct() {
		$this->gb_compatibility();
	}


	private function gb_compatibility() {
		add_action( 'admin_init', array( $this, 'editor_compatibility' ) );
		add_action( 'wpfunnels/before_gb_checkout_form_ajax', array( $this, 'load_gb_cf_ajax_action' ), 10, 2 );
	}


	/**
	 * Editor compatibility action for gutenberg
	 *
	 * @since 2.0.3
	 */
	public function editor_compatibility() {

		if( Wpfnl_functions::is_step_edit_page() && Wpfnl_functions::is_wc_active() ) {
			if(is_admin()) {
				add_filter( 'wpfunnels/show_dummy_order_details', '__return_true' );
			}
			$this->before_checkout_actions();
			$frontend = Wpfnl::get_instance()->frontend;
			add_filter('woocommerce_locate_template', array( $frontend, 'wpfunnels_woocommerce_locate_template' ), 20, 3);

			$step_id = isset($_POST['post_id']) ? intval( $_POST['post_id'] ) : 0;

			$show_coupon = Wpfnl::get_instance()->meta->get_checkout_meta_value( $step_id, '_wpfnl_checkout_coupon' );
			if( 'no' === $show_coupon ) {
				remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form' );
			}
		}
	}

	/**
	 * Before checkout shortcode actions
	 *
	 * @since 2.0.3
	 */
	private function before_checkout_actions() {
		wc()->frontend_includes();
		/* For preview */
		add_filter('woocommerce_checkout_redirect_empty_cart', '__return_false');
	}


	/**
	 * This will be only triggered on checkout form block. and only in the
	 * editor page on ajax call
	 *
	 * @param $checkout_id
	 * @param $post_data
	 */
	public function load_gb_cf_ajax_action( $checkout_id, $post_data ) {
		$frontend = Wpfnl::get_instance()->frontend;
		add_filter('woocommerce_locate_template', array( $frontend, 'wpfunnels_woocommerce_locate_template' ), 20, 3);

		$default_settings	= Wpfnl_Default_Meta::get_default_order_bump_meta();
		$ob_data 			= isset( $post_data['order_bump_data'] ) ? $post_data['order_bump_data'] : $default_settings;
		$ob_settings 		= $this->get_order_bump_settings_for_preview( $checkout_id, $ob_data );

		update_post_meta($checkout_id, 'order-bump-settings', $ob_settings);
		do_action('wpfunnels/gb_render_order_bump_ajax', $checkout_id, $ob_settings );
	}


	/**
	 * Get order bump settings for preview
	 *
	 * @param $post_id
	 * @param $post_data
	 * 
	 * @return mixed
	 *
	 * @since 2.0.4
	 */
	private function get_order_bump_settings_for_preview( $post_id, $post_data ) {
		// $order_bump_settings 	= get_post_meta( $post_id, 'order-bump-settings', true );
		$order_bump_settings 	= Wpfnl_functions::get_ob_settings( $post_id );
		if( $order_bump_settings ) {
			return $this->replace_ob_settings_with_block_data( $order_bump_settings, $post_data );
		}
		return [];
	}


	/**
	 * Replace ob settings with widget data
	 *
	 * @param $order_bump_settings
	 * @param $post_data
	 * 
	 * @return mixed
	 *
	 * @since 2.0.4
	 */
	private function replace_ob_settings_with_block_data( $order_bump_settings, $post_data ) {
		if( empty($post_data) ) {
			return $order_bump_settings;
		}

		if( is_array( $post_data ) ){
			foreach( $post_data as $key => $data ){
				$order_bump_settings[$key]['checkBoxLabel'] 			= isset( $data['checkBoxLabel'] ) ? $data['checkBoxLabel'] : $data['checkBoxLabel'];
				$order_bump_settings[$key]['highLightText'] 			= isset( $data['highLightText'] ) ? $data['highLightText'] : $data['highLightText'];
				$order_bump_settings[$key]['productDescriptionText'] 	= isset( $data['productDescriptionText'] ) ? $data['productDescriptionText'] : $data['productDescriptionText'];
				$order_bump_settings[$key]['position'] 				    = isset( $data['position'] ) ? $data['position'] : $data['position'];
				$order_bump_settings[$key]['selectedStyle'] 			= isset( $data['selectedStyle'] ) ? $data['selectedStyle'] : $data['selectedStyle'];
				$order_bump_settings[$key]['productImage'] 			    = isset( $data['productImage'] ) ? $data['productImage'] : $data['productImage'];
				$order_bump_settings[$key]['isEnabled'] 				= isset( $data['isEnabled'] ) ? $data['isEnabled'] : $data['isEnabled'];

			}
			return $order_bump_settings;
		}


	}
}
