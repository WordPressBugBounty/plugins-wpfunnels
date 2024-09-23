<?php
/**
 * Checkout module
 *
 * @package
 */

namespace WPFunnels\Admin\Modules\Steps\Checkout;

use WPFunnels\Admin\Modules\Steps\Module as Steps;
use WPFunnels\Metas\Wpfnl_Step_Meta_keys;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;
use \WC_Subscriptions_Product;
use Wpfnl_Type_Factory;
class Module extends Steps
{
	protected $validations;

	protected $_internal_keys = [];

	protected $type = 'checkout';

	protected $prefix = '_wpfnl_checkout_';


	public function __construct()
	{
		add_action('wp_ajax_order_bump_search_products', [$this, 'fetch_products']);
		add_action('wp_ajax_wpfnl_replace_product_search', [$this, 'fetch_replace_products']);
		add_action('wp_ajax_order_bump_search_coupons', [$this, 'fetch_coupons']);
		add_action('wpfunnels/after_save_order_bump_data', [$this, 'update_elementor_data'], 10, 2);
	}


	public function get_validation_data()
	{
		return $this->validations;
	}


	public function init($id)
	{
		parent::init($id);
		$this->set_internal_meta_value();
	}


	/**
	 * Load assets
	 *
	 * @param $hook
	 *
	 * @since 1.0.0
	 */
	public function load_scripts($hook)
	{
		if (isset($_GET['step_id'])) {
			$step_id = filter_input(INPUT_GET, 'step_id', FILTER_VALIDATE_INT);
			if (Wpfnl_functions::check_if_this_is_step_type_by_id($step_id, 'checkout')) {
				wp_enqueue_script($this->type . '-js', WPFNL_URL . 'admin/assets/dist/js/order-bump.min.js', ['jquery', 'wp-util'], '1.0.0', true);
				wp_localize_script(
					$this->type . '-js',
					'CheckoutStep',
					[
						'ajaxurl' => esc_url_raw(admin_url('admin-ajax.php')),
						'rest_api_url' => esc_url_raw(get_rest_url()),
						'wc_currency' => get_woocommerce_currency_symbol(),
						'nonce' => wp_create_nonce('wp_rest'),
						'security' => wp_create_nonce('wpfnl-admin'),
						'image_path' => WPFNL_URL . 'admin/assets/images',
						'tooltipIcon' => WPFNL_URL . 'admin/partials/icons/question-tooltip-icon.php',
						'imageUploadIcon' => WPFNL_URL . 'admin/partials/icons/image-upload-icon.php',
						'step_id' => $step_id,
						'back' => add_query_arg(
							[
								'page' => WPFNL_EDIT_FUNNEL_SLUG,
								'id' => filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT),
								'step_id' => $step_id,
							],
							admin_url('admin.php')
						)
					]
				);
			}
		}
	}


	/**
	 * Init ajax hooks for
	 * saving metas
	 *
	 * @since 1.0.0
	 */
	public function init_ajax()
	{
		$this->validations = [
			'logged_in' => true,
			'user_can' => 'wpf_manage_funnels',
		];
		wp_ajax_helper()->handle('wpfnl-update-checkout-product-tab')
			->with_callback([$this, 'save_products'])
			->with_validation($this->validations);

	}

	/**
	 * Get view of the checkout settings page
	 *
	 * @since 1.0.0
	 */
	public function get_view()
	{
		$is_pro_activated = Wpfnl_functions::is_wpfnl_pro_activated();
		$show_settings = filter_input(INPUT_GET, 'show_settings', FILTER_SANITIZE_SPECIAL_CHARS);
		$this->_internal_keys = Wpfnl_Step_Meta_keys::get_meta_keys($this->type);
		$this->set_internal_meta_value();

		if ($show_settings == 1) {
			require_once WPFNL_DIR . '/admin/modules/steps/checkout/views/settings.php';
		} else {
			require_once WPFNL_DIR . '/admin/modules/steps/checkout/views/view.php';
		}
	}


	/**
	 * Save products
	 *
	 * @param Array $payload
	 *
	 * @return Array
	 * @since  2.0.4
	 */
	public function save_products($payload)
	{
		$step_id = $payload['step_id'];
		$products = array();

		if (isset($payload['products'])) {
			$products = $payload['products'];
		}

		$funnel_id = get_post_meta($step_id,'_funnel_id',true);
		$is_gbf = get_post_meta($funnel_id,'is_global_funnel',true);

		$coupon = $payload['coupon'];
		$isMultipleProduct = $payload['isMultipleProduct'];
		$isQuantity = $payload['isQuantity'];

		if ($coupon == 'true') {
			$coupon = 'yes';
		} else {
			$coupon = 'no';
		}

		if ($isMultipleProduct == 'true') {
			$isMultipleProduct = 'yes';
		} else {
			$isMultipleProduct = 'no';
		}
		if ($isQuantity == 'true') {
			$isQuantity = 'yes';
		} else {
			$isQuantity = 'no';
		}

		if( isset($payload['enableAutoCoupon'], $payload['selectedCoupon'] ) ){
			$autocoupon = [
				'enableAutoCoupon' 	 => $payload['enableAutoCoupon'],
				'selectedCoupon' 	 => $payload['selectedCoupon'],
			];
			update_post_meta($step_id, '_wpfnl_checkout_auto_coupon', $autocoupon);
		}

		if( isset($payload['discountOptions']) ){
			$discount = [
				'discountOptions' 	 => isset($payload['discountOptions']) ? $payload['discountOptions'] : 'original',
				'discountapplyto' 	 => isset($payload['discountapplyto']) && $payload['discountOptions'] != 'original' ? $payload['discountapplyto'] : '',
				'mutedDiscountValue' => isset($payload['mutedDiscountValue']) && $payload['discountOptions'] != 'original' ? $payload['mutedDiscountValue'] : 0,
				'discountedPrice'    => isset($payload['discountedPrice']) && $payload['discountOptions'] != 'original' ? $payload['discountedPrice'] : 0,
			];
			update_post_meta($step_id, '_wpfnl_checkout_discount_main_product', $discount);

			if( isset($payload['timeBoundDiscount'])){
				update_post_meta($step_id, '_wpfnl_time_bound_discount_settings', $payload['timeBoundDiscount']);
			}
		}

		if( 'yes' == $is_gbf ){
			return [
				'success' => true,
				'message' => 'Saved Successfully',
			];
		}

		if (!$products) {
			return [
				'success' => false,
				'message' => 'No Product Found',
			];
		}

		foreach ($products as $pr_key => $pr_value) {
			foreach ($pr_value as $key => $value) {
				if ($key == 'price' || $key == 'image' || $key == 'title') {
					unset($products[$pr_key][$key]);
				}
			}
		}



		update_post_meta($step_id, '_wpfnl_checkout_products', $products);
		update_post_meta($step_id, '_wpfnl_checkout_coupon', $coupon);
		update_post_meta($step_id, '_wpfnl_multiple_product', $isMultipleProduct);
		update_post_meta($step_id, '_wpfnl_quantity_support', $isQuantity);

		if( isset( $payload['quantityLimit'] ) ){
			update_post_meta($step_id, '_wpfnl_quantity_limit', $payload['quantityLimit']);
		}

		if( isset( $payload['wpmlWidgetPosition'] ) ){
			update_post_meta($step_id, '_wpfnl_wpml_Widget_Position_on_checkout', $payload['wpmlWidgetPosition']);
		}

		if( isset( $payload['disableGateways'] ) ){
			update_post_meta($step_id, '_wpfnl_disabled_payemnts', $payload['disableGateways']);
		}else{
			update_post_meta($step_id, '_wpfnl_disabled_payemnts', [] );
		}

		return [
			'success' => true,
			'message' => 'Saved Successfully',
		];
	}

	/**
	 * Save checkout product tab
	 * data
	 *
	 * @param $payload
	 *
	 * @return array
	 */
	public  function checkout_update_product_tab_options($payload)
	{

		$step_id = sanitize_text_field($payload['step_id']);
		unset($payload['step_id']);
		$step = Wpfnl::get_instance()->step_store;
		$step->set_id($step_id);
		$this->_internal_keys = Wpfnl_Step_Meta_keys::get_meta_keys($this->type);
		foreach ($payload as $key => $value) {
			if (array_key_exists($this->prefix . $key, $this->_internal_keys)) {
				switch ($key) {
					case 'products':
						$products = [];
						if (!empty($value)) {
							$saved_products = get_post_meta($step_id, '_wpfnl_checkout_products', true);
							if ($saved_products) {
								$saved_products[] = $value;
								$products = $saved_products;
							} else {
								$products[] = $value;
							}
						}
						$step->update_meta($step_id, $this->prefix . $key, $products);
						break;
					case 'discount':
						$discount[] = $value;
						$step->update_meta( $step_id, $this->prefix . $key, $discount );
						break;
					case 'coupon':
						$coupon = $value;
						$step->update_meta($step_id, $this->prefix . $key, $coupon);
						break;
					default:
						$step->update_meta($step_id, $this->prefix . $key, $value);
						break;
				}
			}
		}
		return [
			'success' => true,
		];
	}


	/**
	 * Fetch product from WC data store
	 *
	 * @throws \Exception
	 * @since  1.0.0
	 */
	public function fetch_products()
	{

		check_ajax_referer('wpfnl-admin', 'security');
		if (isset($_GET['term'])) {
			$term = (string)esc_attr(wp_unslash($_GET['term']));
		}
		if (empty($term)) {
			wp_die();
		}

		if( isset($_GET['isLearndash']) && $_GET['isLearndash'] == 'true' ){
			$_class = 'lms';
        }else{
			$_class = 'wc';
		}
		$class_object = Wpfnl_Type_Factory::build($_class);
        if( $class_object ){
            $products = $class_object->retrieve_ob_item( $term );
        }
		wp_send_json($products);
	}


	/**
	 * Fetch product from WC data store
	 *
	 * @throws \Exception
	 * @since  1.0.0
	 */
	public function fetch_replace_products()
	{

		check_ajax_referer('wpfnl-admin', 'security');
		if (isset($_GET['term'])) {
			$term = (string)esc_attr(wp_unslash($_GET['term']));
		}
		if (empty($term)) {
			wp_die();
		}

		$class_object = Wpfnl_Type_Factory::build('wc');
        if( $class_object ){
            $products = $class_object->retrieve_replace_ob_item( $term );
        }
		wp_send_json($products);
	}


	/**
	 * Fetch product from WC data store
	 *
	 * @throws \Exception
	 * @since  1.0.0
	 */
	public function fetch_coupons()
	{
		check_ajax_referer('wpfnl-admin', 'security');
		$term = (string)esc_attr(urldecode(wp_unslash($_GET['term'])));
		$term = (string)wp_unslash($term);

		if (empty($term)) {
			wp_die();
		}

		$args = [
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'asc',
			'post_type' => 'shop_coupon',
			'post_status' => 'publish',
			's' => $term,
		];
		$coupons = get_posts($args);

		$discount_types = wc_get_coupon_types();
		$fetched_coupons = [];

		if ($coupons) {
			foreach ($coupons as $coupon) {
				$discount_type = get_post_meta($coupon->ID, 'discount_type', true);
				if (!empty($discount_types[$discount_type])) {
					$fetched_coupons[$coupon->post_title] = $coupon->post_title;
				}
			}
		}
		wp_send_json($fetched_coupons);
	}


	/**
	 * This will regenerate the elementor data.
	 * This approach is not for programmers. Logic behind this code is We know position of WPFunnels widget - $path_array e.g. $path_array = array(0). This means
	 * position of WPFunnels widget will be $elementor_data[$key]['elements'][$path_array[0]]. if $path_array = array(0, 0), the position will be
	 * $elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]. So we update the WPFunnels widget with new data ($settings) one by one manullay.
	 * This is a very bad approach. We will update the code soon.
  *
	 * @param $key
	 * @param $elementor_data
	 * @param $settings
	 * @param $path_array
	 *
	 * @return mixed
	 * @since  2.0.0
	 */
	private function regenerate_elementor_data($key, $elementor_data, $all_settings, $path_array)
	{

		foreach( $all_settings as $settings ){
			$path_array_count = count($path_array);
			if ($path_array_count == 1) {
				$elementor_data[$key]['elements'][$path_array[0]]['settings']['order_bump_position'] = $settings['position'];
				$elementor_data[$key]['elements'][$path_array[0]]['settings']['order_bump_layout'] = $settings['selectedStyle'];
				$elementor_data[$key]['elements'][$path_array[0]]['settings']['order_bump_image']['url'] = $settings['productImage']['url'];
				$elementor_data[$key]['elements'][$path_array[0]]['settings']['order_bump_image']['id'] = $settings['productImage']['id'];
				$elementor_data[$key]['elements'][$path_array[0]]['settings']['order_bump_checkbox_label'] = $settings['checkBoxLabel'];
				$elementor_data[$key]['elements'][$path_array[0]]['settings']['settings']['order_bump_product_detail_header'] = $settings['highLightText'];
				$elementor_data[$key]['elements'][$path_array[0]]['settings']['order_bump_product_detail'] = $settings['productDescriptionText'];
				$elementor_data[$key]['elements'][$path_array[0]]['settings']['order_bump'] = $settings['isEnabled'];
			} elseif ($path_array_count == 2) {
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['settings']['order_bump_position'] = $settings['position'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['settings']['order_bump_layout'] = $settings['selectedStyle'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['settings']['order_bump_image']['url'] = $settings['productImage']['url'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['settings']['order_bump_image']['id'] = $settings['productImage']['id'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['settings']['order_bump_checkbox_label'] = $settings['checkBoxLabel'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['settings']['order_bump_product_detail_header'] = $settings['highLightText'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['settings']['order_bump_product_detail'] = $settings['productDescriptionText'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['settings']['order_bump'] = $settings['isEnabled'];
			} elseif ($path_array_count == 3) {
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['settings']['order_bump_position'] = $settings['position'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['settings']['order_bump_layout'] = $settings['selectedStyle'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['settings']['order_bump_image']['url'] = $settings['productImage']['url'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['settings']['order_bump_image']['id'] = $settings['productImage']['id'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['settings']['order_bump_checkbox_label'] = $settings['checkBoxLabel'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['settings']['order_bump_product_detail_header'] = $settings['highLightText'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['settings']['order_bump_product_detail'] = $settings['productDescriptionText'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['settings']['order_bump'] = $settings['isEnabled'];
			} elseif ($path_array_count == 4) {
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['settings']['order_bump_position'] = $settings['position'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['settings']['order_bump_layout'] = $settings['selectedStyle'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['settings']['order_bump_image']['url'] = $settings['productImage']['url'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['settings']['order_bump_image']['id'] = $settings['productImage']['id'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['settings']['order_bump_checkbox_label'] = $settings['checkBoxLabel'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['settings']['order_bump_product_detail_header'] = $settings['highLightText'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['settings']['order_bump_product_detail'] = $settings['productDescriptionText'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['settings']['order_bump'] = $settings['isEnabled'];
			} elseif ($path_array_count == 5) {
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['elements'][$path_array[4]]['settings']['order_bump_position'] = $settings['position'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['elements'][$path_array[4]]['settings']['order_bump_layout'] = $settings['selectedStyle'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['elements'][$path_array[4]]['settings']['order_bump_image']['url'] = $settings['productImage']['url'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['elements'][$path_array[4]]['settings']['order_bump_image']['id'] = $settings['productImage']['id'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['elements'][$path_array[4]]['settings']['order_bump_checkbox_label'] = $settings['checkBoxLabel'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['elements'][$path_array[4]]['settings']['order_bump_product_detail_header'] = $settings['highLightText'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['elements'][$path_array[4]]['settings']['order_bump_product_detail'] = $settings['productDescriptionText'];
				$elementor_data[$key]['elements'][$path_array[0]]['elements'][$path_array[1]]['elements'][$path_array[2]]['elements'][$path_array[3]]['elements'][$path_array[4]]['settings']['order_bump'] = $settings['isEnabled'];
			}
		}
		return $elementor_data;
	}


	/**
	 * This hook will trigger once the order bump data is saved from admin
	 *
	 * @param $post_id
	 * @param $settings
	 *
	 * @since 2.0.0
	 */
	public function update_elementor_data($post_id, $all_settings)
	{
		if (Wpfnl_functions::get_builder_type() === 'elementor') {
			$elementor_data = get_post_meta($post_id, '_elementor_data', true);
			if ($elementor_data) {
				if (is_array($elementor_data)) {
					$elementor_data = $elementor_data;
				} else {
					$elementor_data = add_magic_quotes( json_decode( $elementor_data, true ) );
				}


				$el_data = array();
				$checkout_widget = null;
				foreach ($elementor_data as $key => $inner_element) {
					$checkout_widget = Wpfnl_functions::recursive_multidimensional_ob_array_search_by_value('wpfnl-checkout', $inner_element['elements']);
					if ($checkout_widget) {
						$path_array = $checkout_widget['path'];

						if (!$path_array) continue;

						if ($path_array) {
							$path = '';
							$widget_settings = $checkout_widget['settings'];
							$widget_settings['order_bump_checkbox_label'] = 'hukka hua';
							$regenerated_elementor_data = $this->regenerate_elementor_data($key, $elementor_data, $all_settings, $path_array);
							update_post_meta($post_id, '_elementor_data', $regenerated_elementor_data);
						}
						break;
					}
				}
			}
		}
	}


}
