<?php

/**
 * WPFunnels public module
 *
 * @package WPFunnels\Frontend
 */

namespace WPFunnels\Frontend;

use PLL_Model;
use PLL_Translated_Post;
use WPFunnels\Ajax_Handler\Ajax_Handler;
use WPFunnels\Compatibility\Wpfnl_Theme_Compatibility;
use WPFunnels\Conditions\Wpfnl_Condition_Checker;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;

use Wpfnl_Logger;

/**
 * The public-facing functionality of the plugin.
 *
 * @link  https://rextheme.com
 * @since 1.0.0
 *
 * @package    Wpfnl
 * @subpackage Wpfnl/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wpfnl
 * @subpackage Wpfnl/public
 * @author     RexTheme <support@rextheme.com>
 */
class Wpfnl_Public
{

	use SingletonTrait;

	public $offer_metas;
	public $step_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		/**
		 * This will trigger when funnel first start
		 */
		add_action('wp', array($this, 'init_funnel'), 1);
		add_action('wp', array($this, 'init_wp_actions'), 55);

		add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

		add_action('init', array($this, 'init_function'));

		add_filter('wpfnl_offer_meta', array($this, 'wpfnl_offer_meta'), 10, 1);

		/**
		 * Modify the checkout order received url to next step
		 */
		add_filter('woocommerce_get_checkout_order_received_url', [$this, 'redirect_to_funnel_thankyou_page'], 10, 2);

		/*
		 * This hook will be triggered once the elementor data is saved.
		 * Will be placed this hook on another class in future.
		 */
		add_action('elementor/editor/after_save', array($this, 'elementor_data_after_save_action'), 10, 2);
		add_filter('woocommerce_hidden_order_itemmeta', array($this, 'wpfnl_woocommerce_hidden_order_itemmeta'), 10, 1);

		add_filter('woocommerce_order_item_display_meta_key', array($this, 'wpfnl_beautify_item_meta_on_order'), 10, 3);

		add_filter('woocommerce_order_item_display_meta_value', array($this, 'wpfnl_update_order_item_display_meta_value'), 9999, 3);


		/**
		 * Trigger ajax if any user abandoned a funnel
		 */
		add_action('wp_ajax_nopriv_maybe_abandoned_funnel', array($this, 'maybe_abandoned_funnel'), 10);
		add_action('wp_ajax_maybe_abandoned_funnel', array($this, 'maybe_abandoned_funnel'), 10);

		add_action('wpfunnels/funnel_journey_end', array($this, 'end_journey'), 10, 2);

		add_filter('woocommerce_add_cart_item_data', [$this, 'namespace_force_individual_cart_items'], 10, 2);
		add_filter('wpfunnels/funnel_order_placed', [$this, 'add_order_details_to_logger'], 10, 3);
		add_action('wpfunnels/after_optin_submit', array($this, 'add_optin_data_to_logger'), 10, 4);
		add_action('wpfunnels/after_optin_submit', array($this, 'get_optin_data'), 10, 4);
		add_action('wpfunnels/after_optin_submit', [$this, 'register_optin_user'], 10, 5);
		add_action('wpfunnels/optin_submission_response', [$this, 'regenerate_redirect_url'], 10, 5);

		add_filter('posts_where', array($this, 'posts_where'), 99);

		/**
		 * Add edit funnel menu at admin bar
		 */
		add_action('admin_bar_menu', array($this, 'add_edit_funnel_menu'), 999);

		/**
		 * Add custom script
		 */
		add_action('wp_head', array($this, 'add_custom_script'));

		// remove woodmart hook for funnel checkout
		add_action('wp', [$this, 'remove_woodmart_hook'], 150);

	}

	/**
	 * Removes Woodmart theme related hooks from the checkout page.
	 *
	 * This function is called when the checkout page is loaded in the admin or frontend.
	 * It checks if the Woodmart theme is active and if the current page is a checkout page.
	 * If both conditions are met, it removes the 'woodmart_enqueue_base_styles' action
	 * with a priority of 10000 and the 'woodmart_sticky_toolbar_template' action with a priority of 10000.
	 *
	 * @since 3.4.9
	 */
	public function remove_woodmart_hook()
	{
		global $post;

		// Check if we are in the admin or if the post object is not set
		if (is_admin() || !$post) {
			return;
		}

		$checkout_id = '';

		// Check if we are doing an AJAX request
		if (wp_doing_ajax()) {
			// Get the checkout ID from the POST data
			$checkout_id = Wpfnl_functions::get_checkout_id_from_post($_POST);
		}

		// If the checkout ID is not set, use the current post ID
		$checkout_id = !$checkout_id ? $post->ID : $checkout_id;
		$funnel_id    = Wpfnl_functions::get_funnel_id_from_step( $checkout_id );
		// Check if the Woodmart theme is active and if the current page is a checkout page
		if (Wpfnl_functions::maybe_woodmart_theme() && $checkout_id && $funnel_id) {
			// Remove the 'woodmart_enqueue_base_styles' action with a priority of 10000
			remove_action('wp_enqueue_scripts', 'woodmart_enqueue_base_styles', 10000);

			// Remove the 'woodmart_sticky_toolbar_template' action with a priority of 10000
			remove_action('wp_footer', 'woodmart_sticky_toolbar_template', 10000);
		}
	}



	/**
	 * Modify the SQL JOIN clause to exclude language-based filtering when querying posts.
	 *
	 * This function is a filter callback used in WordPress to customize the SQL query's JOIN clause
	 * when retrieving posts. It is specifically designed to work with the Polylang plugin to exclude
	 * language-based filtering from the JOIN clause.
	 *
	 * @param string $join The original SQL JOIN clause.
	 *
	 * @return string The modified SQL JOIN clause with language-based filtering excluded.
	 *
	 * @see https://polylang.pro/
	 * @since 2.8.6
	 */
	public function posts_join($join)
	{
		if (class_exists('PLL_Model') && class_exists('PLL_Translated_Post')) {
			$pll_options     = get_option('polylang', []);
			$model       = new PLL_Model($pll_options);
			$post        = new PLL_Translated_Post($model);
			$custom_join = $post->join_clause();
			return str_replace($custom_join, '', $join);
		}
		return $join;
	}

	/**
	 * Modify the SQL WHERE clause for post queries, optionally removing language filtering.
	 *
	 * This function is a filter callback used in WordPress to customize the SQL query's WHERE clause
	 * when retrieving posts. If the query appears to target 'wpfunnel_steps', it adjusts the WHERE
	 * clause, possibly removing language-based filtering. It also adds a filter to customize the SQL
	 * JOIN clause for further modifications.
	 *
	 * @param string $where The original SQL WHERE clause.
	 *
	 * @return string The modified SQL WHERE clause, possibly without language-based filtering.
	 *
	 * @see https://wordpress.org/
	 * @since 2.8.6
	 */
	public function posts_where($where)
	{
		if (preg_match('/wpfunnel_steps/', $where)) {
			add_filter('posts_join', array($this, 'posts_join'), 99);
			$lang = $this->get_pll_term_id();
			return str_replace("AND pll_tr.term_taxonomy_id = {$lang}", '', $where);
		}
		return $where;
	}

	/**
	 * Get the term ID associated with a language slug from Polylang plugin options.
	 *
	 * This function retrieves the term ID for a specific language slug by querying the WordPress
	 * database terms table. It uses the provided language slug and the Polylang plugin options.
	 *
	 * @return int|null The term ID of the language, or null if not found.
	 *
	 * @global object $wpdb WordPress database object.
	 *
	 * @see https://polylang.pro/
	 * @since 2.8.6
	 */
	private function get_pll_term_id()
	{
		global $wpdb;
		$pll_options = get_option('polylang', []);
		return $wpdb->get_var($wpdb->prepare("SELECT `term_id` FROM %i WHERE `slug`=%s", [$wpdb->terms, $pll_options['default_lang'] ?? '']));
	}

	/**
	 * Add individual items in cart forcefully. It's implemented for indentifying orderbump items
	 *
	 * @param Array
	 * @param String
	 *
	 * @return Array
	 */
	public function namespace_force_individual_cart_items($cart_item_data, $product_id)
	{

		$post_action  = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
		if (isset($post_action['action']) && ('wpfnl_order_bump_ajax' ===  $post_action['action'] || 'wpfnl_order_bump_ajax' === $post_action['action'])) {
			$unique_cart_item_key = md5(microtime() . rand());
			$cart_item_data['unique_key'] = $unique_cart_item_key;
		}
		return $cart_item_data;
	}



	/**
	 * Initialize function
	 */
	public function init_function()
	{
		$offer_meta = ['_wpfunnels_order_bump'];
		return apply_filters('wpfnl_offer_meta', $offer_meta);
	}

	/**
	 * Init actions for a funnel
	 *
	 * @since 2.0.2
	 */
	public function init_funnel()
	{
		if (Wpfnl_functions::is_funnel_step_page()) {
			global $post;
			do_action('wpfunnels/wp', $post->ID);
			$this->start_funnel_journey();
		}
	}


	/**
	 * Start session data when funnel starts
	 *
	 * @since 2.0.3
	 */
	private function start_funnel_journey()
	{
		Wpfnl_functions::start_journey();
		if (Wpfnl_functions::check_if_this_is_step_type('checkout')) {
			Wpfnl_functions::do_not_cache();
		}
	}


	public function init_wp_actions()
	{
		if (Wpfnl_functions::is_funnel_step_page('')) {
			/**
			 * Enqueue public styles
			 */
			add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
			add_action('wp_enqueue_scripts', array($this, 'remove_theme_styles'));
			add_filter('woocommerce_enqueue_styles', array($this, 'enqueue_wc_styles'), 9999);

			add_filter('woocommerce_locate_template', array($this, 'wpfunnels_woocommerce_locate_template'), 20, 3);
		}
	}


	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfnl_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfnl_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if (Wpfnl_functions::is_funnel_step_page()) {
			add_filter('astra_theme_woocommerce_dynamic_css', '__return_empty_string');

			wp_enqueue_style('wpfnl-public', plugin_dir_url(__FILE__) . 'assets/css/wpfnl-public.css', [], WPFNL_VERSION, 'all');
			//$this->load_googlefonts();
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfnl_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfnl_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if (Wpfnl_functions::is_funnel_step_page()) {

			$compatibility = Wpfnl_Theme_Compatibility::getInstance();

			wp_enqueue_script('wpfnl-public', plugin_dir_url(__FILE__) . 'assets/js/wpfnl-public.js', ['jquery'], WPFNL_VERSION, false);
			wp_localize_script(
				'wpfnl-public',
				'wpfnl_obj',
				[
					'ajaxurl' 				=> admin_url('admin-ajax.php'),
					'is_builder_preview'	=> $compatibility->is_builder_preview(),
					'funnel_id'				=> get_post_meta(get_the_ID(), '_funnel_id', true),
					'step_id' 				=> get_the_ID(),
					'ajax_nonce' 			=> wp_create_nonce('wpfnl'),
					'abandoned_ajax_nonce' 	=> wp_create_nonce('abandoned_ajax_nonce'),
					'optin_form_nonce' 		=> wp_create_nonce('optin_form_nonce'),
					'is_user_logged_in' 	=> is_user_logged_in(),
					'user_id' 				=> get_current_user_id(),
					'is_login_reminder' 	=> get_option('woocommerce_enable_checkout_login_reminder'),
				]
			);

			$post_id = get_the_ID();
			$custom_css = get_post_meta($post_id, 'rex_gutenberg_css', true);

			if ($custom_css) {
				wp_register_style('rex-gutenberg-css', false);
				wp_enqueue_style('rex-gutenberg-css');
				wp_add_inline_style('rex-gutenberg-css', $custom_css);
			}
		}
	}


	/**
	 * This functions are from qubely.
	 */
	private function load_googlefonts()
	{
		global $blocks;
		$contains_wpfnl_blocks = false;
		$block_fonts = [];
		$load_google_fonts = 'yes';

		if ($load_google_fonts == 'yes') {
			$blocks = $this->parse_all_blocks();
			$contains_wpfnl_blocks = $this->has_wpfnl_blocks($blocks);

			if ($contains_wpfnl_blocks) {
				$block_fonts = $this->gather_block_fonts($blocks, $block_fonts);
				$global_settings = get_option($this->option_keyword);
				$global_settings = $global_settings == false ? json_decode('{}') : json_decode($global_settings);
				$global_settings = json_decode(json_encode($global_settings), true);
				$gfonts = '';
				$all_global_fonts = array();
				if (isset($global_settings['presets']) && isset($global_settings['presets'][$global_settings['activePreset']]) && isset($global_settings['presets'][$global_settings['activePreset']]['typography'])) {
					$all_global_fonts = $this->colsFromArray(array_column($global_settings['presets'][$global_settings['activePreset']]['typography'], 'value'), ['family', 'weight']);
				}
				if (count($all_global_fonts) > 0) {
					$global_fonts = array_column($all_global_fonts, 'family');

					$all_fonts = array_unique(array_merge($global_fonts, $block_fonts));

					if (!empty($all_fonts)) {
						$system = array(
							'Arial',
							'Tahoma',
							'Verdana',
							'Helvetica',
							'Times New Roman',
							'Trebuchet MS',
							'Georgia',
						);

						$gfonts_attr = ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic';

						foreach ($all_fonts as $font) {
							if (!in_array($font, $system, true) && !empty($font)) {
								$gfonts .= str_replace(' ', '+', trim($font)) . $gfonts_attr . '|';
							}
						}
					}

					if (!empty($gfonts)) {
						$query_args = array(
							'family' => $gfonts,
						);

						wp_register_style(
							'qubely-google-fonts',
							add_query_arg($query_args, '//fonts.googleapis.com/css'),
							array(),
							QUBELY_VERSION
						);
						wp_enqueue_style('qubely-google-fonts');
					}
				}
			}
		}
	}


	/**
	 * Parse all blocks
	 *
	 * @return array[]
	 */
	public function parse_all_blocks()
	{
		$blocks;
		if (is_single() || is_page() || is_404()) {
			global $post;
			if (is_object($post) && property_exists($post, 'post_content')) {
				$blocks = parse_blocks($post->post_content);
			}
		} elseif (is_archive() || is_home() || is_search()) {
			global $wp_query;
			foreach ($wp_query as $post) {
				if (is_object($post) && property_exists($post, 'post_content')) {
					$blocks = parse_blocks($post->post_content);
				}
			}
		}
		return $blocks;
	}


	/**
	 * Check whether the contents has wpfnl blocks or not
	 *
	 * @param $blocks
	 *
	 * @return bool
	 */
	public function has_wpfnl_blocks($blocks)
	{
		$is_wpfnl_block = false;
		foreach ($blocks as $key => $block) {
			if (strpos($block['blockName'], 'wpfnl') !== false) {
				$is_wpfnl_block = true;
			}
			if (isset($block['innerBlocks']) && gettype($block['innerBlocks']) == 'array' && count($block['innerBlocks']) > 0) {
				$is_wpfnl_block = $this->has_qubely_blocks($block['innerBlocks']);
			}
		}
		return $is_wpfnl_block;
	}


	/**
	 * Gather block google fonts
	 *
	 * @param $blocks
	 * @param $block_fonts
	 *
	 * @return array
	 */
	public function gather_block_fonts($blocks, $block_fonts)
	{
		$google_fonts = $block_fonts;
		foreach ($blocks as $key => $block) {
			if (strpos($block['blockName'], 'wpfnl') !== false) {
				foreach ($block['attrs'] as $key =>  $att) {
					if (gettype($att) == 'array' && isset($att['openTypography']) && isset($att['family'])) {
						if (isset($block['attrs'][$key]['activeSource'])) {
							if ($block['attrs'][$key]['activeSource'] == 'custom') {
								array_push($google_fonts, $block['attrs'][$key]['family']);
							}
						} else {
							array_push($google_fonts, $block['attrs'][$key]['family']);
						}
					}
				}
			}
			if (isset($block['innerBlocks']) && gettype($block['innerBlocks']) == 'array' && count($block['innerBlocks']) > 0) {
				$child_fonts = $this->gather_block_fonts($block['innerBlocks'], $google_fonts);
				if (count($child_fonts) > 0) {
					$google_fonts =	array_merge($google_fonts, $child_fonts);
				}
			}
		}
		return array_unique($google_fonts);
	}


	/**
	 * Columns from array
	 *
	 * @param array $array
	 * @param $keys
	 *
	 * @return array
	 */
	public function colsFromArray(array $array, $keys)
	{
		if (!is_array($keys)) $keys = [$keys];
		return array_map(function ($el) use ($keys) {
			$o = [];
			foreach ($keys as $key) {
				//  if(isset($el[$key]))$o[$key] = $el[$key]; //you can do it this way if you don't want to set a default for missing keys.
				$o[$key] = isset($el[$key]) ? $el[$key] : false;
			}
			return $o;
		}, $array);
	}


	/**
	 * Remove theme style
	 *
	 * @return bool
	 */
	public function remove_theme_styles()
	{
		if (Wpfnl_functions::is_funnel_step_page()) {

			if (Wpfnl_Theme_Compatibility::getInstance()->is_compatible_theme_enabled()) {
				return;
			}

			$general_settings = Wpfnl_functions::get_general_settings();

			$wp_styles  = wp_styles();
			$themes_uri = get_theme_root_uri();

			if ($general_settings['disable_theme_style'] == 'on') {
				$dequeue_theme_style = apply_filters('wpfunnels/dequeue_theme_style', true);
			} else {
				$dequeue_theme_style = apply_filters('wpfunnels/dequeue_theme_style', false);
			}

			if ($dequeue_theme_style) {
				foreach ($wp_styles->registered as $wp_style) {
					if (strpos($wp_style->src, $themes_uri) !== false) {
						wp_deregister_style($wp_style->handle);
						wp_dequeue_style($wp_style->handle);
						do_action('wpfunnels/enqueue_custom_scripts');
					}
				}
			}
		}
	}


	/**
	 * Enqueue woocommerce styles
	 *
	 * @return array
	 */
	public function enqueue_wc_styles()
	{
		$wc_styles = array(
			'woocommerce-layout'      => array(
				'src'     => plugins_url('assets/css/woocommerce-layout.css', WC_PLUGIN_FILE),
				'deps'    => '',
				'version' => WC_VERSION,
				'media'   => 'all',
				'has_rtl' => true,
			),
			'woocommerce-smallscreen' => array(
				'src'     => plugins_url('assets/css/woocommerce-smallscreen.css', WC_PLUGIN_FILE),
				'deps'    => 'woocommerce-layout',
				'version' => WC_VERSION,
				'media'   => 'only screen and (max-width: ' . apply_filters('woocommerce_style_smallscreen_breakpoint', '768px') . ')',
				'has_rtl' => true,
			),
			'woocommerce-general'     => array(
				'src'     => plugins_url('assets/css/woocommerce.css', WC_PLUGIN_FILE),
				'deps'    => '',
				'version' => WC_VERSION,
				'media'   => 'all',
				'has_rtl' => true,
			),
		);
		//unset( $wc_styles['woocommerce-layout'] );
		//unset( $wc_styles['woocommerce-general'] );

		return $wc_styles;
	}


	/**
	 * Save custom meta fields for order bump while saving
	 * checkout widget
	 *
	 * @param $post_id
	 * @param $editor_data
	 *
	 * @since 2.0.0
	 */
	public function elementor_data_after_save_action($post_id, $editor_data)
	{
		if (Wpfnl_functions::check_if_this_is_step_type_by_id($post_id, 'checkout')) {

			foreach ($editor_data as $key => $inner_element) {

				$checkout_widget = Wpfnl_functions::recursive_multidimensional_ob_array_search_by_value('wpfnl-checkout', $inner_element['elements']);
				$lms_checkout_widget = Wpfnl_functions::recursive_multidimensional_ob_array_search_by_value('wpfnl-lms-checkout', $inner_element['elements']);

				if ($checkout_widget || $lms_checkout_widget) {
					$widget_settings = isset($checkout_widget['settings']) ? $checkout_widget['settings'] : $lms_checkout_widget['settings'];
					$layout = isset($widget_settings['checkout_layout']) ? $widget_settings['checkout_layout'] : 'wpfnl-col-2';
					update_post_meta($post_id, '_wpfnl_checkout_layout', $layout);
					$order_bump_status = get_post_meta($post_id, 'order-bump', true);
					$order_bump_settings = get_post_meta($post_id, 'order-bump-settings', true);
					$order_bump_enabled = 'no';
					$default_order_bump_data = [];

					if ($order_bump_status) {
						$order_bump_enabled = $order_bump_status;
					}

					if ($order_bump_settings) {
						$order_bump_data = $order_bump_settings;
					} else {
						$order_bump_data = $default_order_bump_data;
					}

					if (count($order_bump_data) > 0) {
						foreach ($order_bump_data as $key => $ob) {
							$order_bump_data[$key]['isEnabled'] = $this->set_order_bump_data('isEnabled', 'order_bump_' . $key, $widget_settings, $order_bump_data[$key], 'yes');
							$order_bump_data[$key]['selectedStyle'] = $this->set_order_bump_data('selectedStyle', 'order_bump_layout_' . $key, $widget_settings, $order_bump_data[$key], 'style1');
							$order_bump_data[$key]['position'] = $this->set_order_bump_data('position', 'order_bump_position_' . $key, $widget_settings, $order_bump_data[$key], 'after-order');
							$order_bump_data[$key]['checkBoxLabel'] = $this->set_order_bump_data('checkBoxLabel', 'order_bump_checkbox_label_' . $key, $widget_settings, $order_bump_data[$key], 'Grab this offer with one click!');
							$order_bump_data[$key]['highLightText'] = $this->set_order_bump_data('highLightText', 'order_bump_product_detail_header_' . $key, $widget_settings, $order_bump_data[$key], 'Special one time offer');
							$order_bump_data[$key]['productDescriptionText'] = $this->set_order_bump_data('productDescriptionText', 'order_bump_product_detail_' . $key, $widget_settings, $order_bump_data[$key], 'Get this scratch proof 6D Tempered Glass Screen Protector for your iPhone. Keep your phone safe and sound just like a new one.');
							$ob_image = $this->set_order_bump_data('productImage', 'order_bump_image_' . $key, $widget_settings, $order_bump_data[$key]);
							$order_bump_data[$key]['productImage'] = array(
								'id' => isset($ob_image['id']) ? $ob_image['id'] : 0,
								'url' => isset($ob_image['url']) ? $ob_image['url'] : '',
							);
						}
					}
					update_post_meta($post_id, 'order-bump-settings', $order_bump_data);
					// update_post_meta($post_id, 'order-bump', $order_bump_data['isEnabled']);
				}
			}
		}
	}


	/**
	 * Redirect to next step
	 *
	 * @param $order_received_url
	 * @param $order
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function redirect_to_funnel_thankyou_page($order_received_url, $order)
	{

		if ($order && Wpfnl_functions::check_if_funnel_order($order)) {

			$order_key       = $order->get_order_key();
			$order_id        = $order->get_id();
			$link            = '#';
			$funnel_id       = Wpfnl_functions::get_funnel_id_from_order($order);

			if (!$funnel_id) {
				return $link;
			}

			$current_page_id = apply_filters('wpfunnels/current_step_id', $order->get_meta('_wpfunnels_checkout_id'), $order);
			$parent_step_id = get_post_meta($current_page_id, '_parent_step_id', true);
			$current_page_id = $parent_step_id ? $parent_step_id : $current_page_id;
			$next_node 				= Wpfnl_functions::get_next_conditional_step($funnel_id, $current_page_id, $order);
			$next_node       		= apply_filters('wpfunnels/next_step_data', $next_node);
			$next_node       		= apply_filters('wpfunnels/modify_next_step_based_on_order', $next_node, $order);
			if (isset($next_node['step_type']) && 'thankyou' === $next_node['step_type']) {
				$custom_url = Wpfnl_functions::custom_url_for_thankyou_page($next_node['step_id']);
				if ($custom_url) {
					return $custom_url;
				}
			}

			$query_args = array(
				'wpfnl-order' => $order_id,
				'wpfnl-key' => $order_key,
			);
			$query_args = apply_filters('wpfunnels/order_meta', $query_args, $order);
			$next_step_url = $this->get_thankyou_step_url($funnel_id, $next_node['step_id'], $order);
			if ($next_step_url) {
				return add_query_arg($query_args, $next_step_url);
			}
		}
		return $order_received_url;
	}


	/**
	 * Get thankyou page url
	 *
	 * @param $funnel_id
	 * @param $step_id
	 * @param $order_received_url
	 *
	 * @return string
	 */
	private function get_thankyou_step_url($funnel_id, $step_id, $order)
	{
		/**
		 * If pro plugin isn't installed, there is no need to redirect user to the
		 * offer page. So for the plugin we redirect the user to the funnel thankyou page
		 * forcefully. We have also placed a hook for next step url, which will be modified
		 * on the pro plugin
		 */

		$next_step_url 		= get_permalink($step_id);
		$thankyou_step_url  = '';
		if (Wpfnl_functions::check_if_this_is_step_type_by_id($step_id, 'thankyou')) {
			return apply_filters('wpfunnels/funnel_thankyou_page_url', $next_step_url, $order);
		} else {
			$thankyou_page_id = Wpfnl_functions::get_thankyou_page_id($funnel_id);
			if ($thankyou_page_id) {
				$thankyou_step_url = get_permalink($thankyou_page_id);
			}
		}
		return apply_filters('wpfunnels/next_step_url', $thankyou_step_url, $next_step_url, $order);
	}


	/**
	 * Set order bump data
	 *
	 * @param $settings_key
	 * @param $key
	 * @param $widget_settings
	 * @param string $default_value
	 *
	 * @return mixed|string
	 *
	 * @since 2.0.0
	 */
	private function set_order_bump_data($settings_key, $key, $widget_settings, $order_bump_data, $default_value = '')
	{
		$value = $default_value;
		if (isset($widget_settings[$key])) {
			$value = $widget_settings[$key];
		} else {
			$value = $order_bump_data[$settings_key];
		}
		return $value;
	}


	/**
	 * Locate wc templates from plugin folders
	 *
	 * @param $template
	 * @param $template_name
	 * @param $template_path
	 *
	 * @return string
	 *
	 * @since 2.0.3
	 */
	public function wpfunnels_woocommerce_locate_template($template, $template_name, $template_path)
	{
		/***
		 * Fires when change the wc template
		 *
		 * @since 2.8.21
		 */
		if (apply_filters('wpfunnels/maybe_locate_template', true)) {
			$_template   = $template;
			$plugin_path = WPFNL_DIR . 'woocommerce/templates/';

			if (file_exists($plugin_path . $template_name)) {
				$template = $plugin_path . $template_name;
			}

			if (!$template) {
				$template = $_template;
			}
		}
		return $template;
	}


	/**
	 * Wpfnl offer meta
	 *
	 * Return offer meta
	 */
	public function wpfnl_offer_meta($offer_meta)
	{
		$this->offer_metas = $offer_meta;
		return $offer_meta;
	}

	/**
	 * Hidden order item meta
	 *
	 * @param $meta
	 *
	 * @return $meta
	 */
	public function wpfnl_woocommerce_hidden_order_itemmeta($meta)
	{
		$meta = ['_wpfunnels_step_id', '_wpfnl_upsell', '_wpfnl_downsell', '_wpfnl_step_id', '_wpfunnels_offer_txn_id', '_reduced_stock', '_wpfunnels_offer_refunded'];
		return $meta;
	}

	/**
	 * Beautify item meta on order
	 *
	 * @param $display_key, $meta, $item
	 *
	 * @return $display_key
	 */
	public function wpfnl_beautify_item_meta_on_order($display_key, $meta, $item)
	{
		$offer_meta = '_wpfunnels_order_bump';
		if (is_admin() && $item->get_type() === 'line_item' && ($meta->key === $offer_meta)) {
			$display_key = __("Offer Type", "wpfnl");
		}
		return $display_key;
	}

	/**
	 * Display customize meta value
	 *
	 * @param $display_key, $meta, $item
	 *
	 * @return $meta
	 */
	public function wpfnl_update_order_item_display_meta_value($display_key, $meta, $item)
	{
		if (isset($item['order_id']) &&  $item['order_id']) {
			$order = wc_get_order($item['order_id']);
			if ($order && Wpfnl_functions::check_if_funnel_order($order)) {
				if (is_admin() && $item->get_type() === 'line_item' && ($meta->key === '_wpfunnels_order_bump')) {
					$meta = __("Order Bump", "wpfnl");
					return $meta;
				} elseif (is_admin() && $item->get_type() === 'line_item' && ($meta->key === '_wpfunnels_upsell')) {
					$meta = __("Upsell", "wpfnl");
					return $meta;
				} elseif (is_admin() && $item->get_type() === 'line_item' && ($meta->key === '_wpfunnels_downsell')) {
					$meta = __("Downsell", "wpfnl");
					return $meta;
				} elseif (is_admin() && $item->get_type() === 'shipping' && $meta->key === 'Items') {
					$meta = $item->get_meta('Items');
					return $meta;
				} else {
					$display_value = $meta->value;
					return $display_value;
				}
			} else {
				$display_value = $meta->value;
				return $display_value;
			}
		} else {
			$display_value = $meta->value;
			return $display_value;
		}
	}


	/**
	 * May be user abandoned funnel
	 */
	public function maybe_abandoned_funnel()
	{
		check_ajax_referer('abandoned_ajax_nonce', 'security');
		$step_id 					= $_POST['step_id'];
		$funnel_id 					= $_POST['funnel_id'];
		$cookie_name        		= 'wpfunnels_automation_data';
		$cookie             		= isset($_COOKIE[$cookie_name]) ? json_decode(wp_unslash($_COOKIE[$cookie_name]), true) : array();
		$cookie['funnel_status']   	= 'abandoned';
		if (PHP_SESSION_DISABLED == session_status()) {
			session_start();
			if (isset($_SESSION) || $_SESSION) {
				if (isset($_SESSION['wpfnl_orders_' . get_current_user_id() . '_' . $funnel_id])) {
					unset($_SESSION['wpfnl_orders_' . get_current_user_id() . '_' . $funnel_id]);
				}
			}
		}
		$step_type = get_post_meta($step_id, '_step_type', true);
		if ('upsell' == $step_type || 'downsell' == $step_type || 'thankyou' == $step_type) {
			delete_option('wpfunnels_dynamic_offer_data');
		}
		delete_option('optin_data');
		do_action('wpfunnels/maybe_user_abandoned_funnel', $step_id, $funnel_id, $cookie);
		die();
	}


	/**
	 * Redirect Thankyou page
	 *
	 * @param mixed $step_id
	 * @param mixed $funnel_id
	 *
	 * @return [type]
	 */
	public function end_journey($step_id, $funnel_id)
	{

		if (!Wpfnl_Theme_Compatibility::getInstance()->is_elementor_preview()) {
			Wpfnl_functions::custom_url_for_thankyou_page($step_id);
		}
	}
	/**
	 * Redirects from LearnDash offer step templates if accessed
	 * without completing previous steps or user is already enrolled
	 * in the course.
	 */
	public function redirect_learndash_template()
	{
		global $post;
		$step_id = isset($post->ID) ? $post->ID : false;

		if ($step_id) {
			$funnel_id    = Wpfnl_functions::get_funnel_id_from_step($step_id);
			$lms_funnel   = get_post_meta($funnel_id, 'is_lms', true);

			if ($lms_funnel === 'yes') {
				$step_type    = get_post_meta($step_id, '_step_type', true);
				$course       = Wpfnl_lms_learndash_functions::get_course_details($step_id);
				$redirect_url = '';
				if ($step_type === 'upsell' || $step_type === 'downsell') {
					if (!isset($_GET['wpfnl_ld_payment'])) {
						$redirect_url = Wpfnl_functions::get_funnel_link($funnel_id);
						Wpfnl_lms_learndash_functions::learndash_template_safe_redirect($redirect_url);
					}
					if ($course && !empty($course)) {
						$course_id       = isset($course['id']) ? $course['id'] : '';
						$course_type     = isset($course['type']) ? $course['type'] : '';
						$next_step       = Wpfnl_functions::get_next_step($funnel_id, $step_id);
						$current_user_id = function_exists('get_current_user_id') ? get_current_user_id() : '';

						if (isset($next_step['step_id'])) {
							if ($next_step['step_type'] == 'conditional') {
								$next_step = Wpfnl_functions::get_next_step($funnel_id, $next_step['step_id']);
							}
							$redirect_url = get_permalink($next_step['step_id']);
						}

						if ($course_type === 'open') {
							Wpfnl_lms_learndash_functions::learndash_template_safe_redirect($redirect_url, 'free');
						}
						if ($course_id !== '' && $current_user_id && $current_user_id !== '') {
							$course_access = function_exists('sfwd_lms_has_access') ? sfwd_lms_has_access($course_id, $current_user_id) : false;

							if ($course_access) {
								Wpfnl_lms_learndash_functions::learndash_template_safe_redirect($redirect_url, 'free');
							}
						}
					}
				}
			}
		}
	}


	private function create_lms_order($order_details = [])
	{

		global $wpdb;
		$result = $wpdb->get_results("SELECT * FROM wp_wpfnl_lms_order WHERE funnel_id = " . $order_details['funnel_id'] . " AND step_id = " . $order_details['funnel_id'] . " AND user_id = " . $order_details['user_id'] . " ");

		if (!empty($result)) {
			$wpdb->query($wpdb->prepare("UPDATE wp_wpfnl_lms_order SET meta='order' WHERE funnel_id = " . $order_details['funnel_id'] . " AND step_id = " . $order_details['funnel_id'] . " AND user_id = " . $order_details['user_id'] . ""));
		}


		$wpdb->insert('wp_wpfnl_lms_order', array(
			'funnel_id' => $order_details['funnel_id'],
			'step_id' 	=> $order_details['step_id'],
			'user_id' 	=> $order_details['user_id'],
			'meta' 		=> 'new_order',
			'order_data' 		=> serialize($order_details['course_details']),
			'date_created' 		=> date("Y-m-d h:i:s")
		));
	}

	/**
	 * After main order placed
	 *
	 * @param String $order_id
	 * @param Number $funnel_id
	 *
	 * @since 2.5.9
	 */
	public function add_order_details_to_logger($order_id, $funnel_id, $step_id)
	{
		if (Wpfnl_functions::maybe_logger_enabled() && Wpfnl_functions::is_wc_active()) {
			if ($order_id && $funnel_id) {
				$order = wc_get_order($order_id);
				$order_items = $order->get_items();
				$product_array = [];
				foreach ($order_items as $item_id => $item) {
					$product_array[] = [
						'name'  => $item['name'],
						'price' => number_format($item['total'] + $item['total_tax'], 2),
					];
				}

				ob_start();
				print_r($product_array);
				$textual_representation = ob_get_contents();
				ob_end_clean();

				Wpfnl_Logger::modify_log_file('event', $textual_representation, 'Main Order Deatils');
			}
		}
	}

	/**
	 * Add optin data to logger
	 *
	 * @param Integrer $step_id
	 * @param String $post_action
	 * @param String $action_type
	 * @param Object $record
	 *
	 * @return void
	 * @since  2.5.9
	 */
	public function add_optin_data_to_logger($step_id, $post_action, $action_type, $record)
	{
		if (Wpfnl_functions::maybe_logger_enabled()) {
			$optin_data = (isset($record->form_data['email']) && $record->form_data['email']) ? $record->form_data : [];
			ob_start();
			print_r($optin_data);
			$textual_representation = ob_get_contents();
			ob_end_clean();

			Wpfnl_Logger::modify_log_file('event', $textual_representation, 'Optin Submission Data');
		}
	}


	/**
	 * Get optin data
	 */
	public function get_optin_data($step_id, $post_action, $action_type, $record)
	{

		$data = get_option('optin_data');
		$data['optin_data'][] = [
			'step_id' => $step_id,
			'step_type' => get_post_meta($step_id, '_step_type', true)
		];
		update_option('optin_data', $data);
	}

	/**
	 * Registers an opt-in user and performs actions based on the submitted data.
	 *
	 * This function is used to register an opt-in user and perform additional actions
	 * based on the provided data, such as creating a user account.
	 *
	 * @param int    $step_id     The ID of the opt-in step.
	 * @param string $post_action The type of action to be performed after registration.
	 * @param string $action_type The type of action being taken, such as 'redirect_to_url' or 'next_step'.
	 * @param object $record      An object containing the opt-in form data.
	 * @param array  $post_data   An array of submitted post data.
	 *
	 * @return void
	 * @since 2.8.2
	 */
	public function register_optin_user($step_id, $post_action, $action_type, $record, $post_data)
	{
		// Extract user info from the record.
		$user_info = $record->form_data ?? [];

		// Extract user's email for potential login and registration.
		$login = $user_info['email'] ? strstr($user_info['email'], '@', true) : '';

		// Check if opt-in registration is allowed and perform related actions.
		if (!empty($post_data['optin_allow_registration']) && $post_data['optin_allow_registration'] == 'yes') {
			// Create user account based on the extracted login and post data.
			Ajax_Handler::create_user_optin_allow_registration($login, $post_data);
		}
	}

	/**
	 * Generates the redirect URL based on the provided data and conditions.
	 *
	 * This function generates a redirect URL based on the next step's data and additional
	 * query parameters. If the next step's type is conditional, it matches the step
	 * based on conditions and updates the redirect URL accordingly.
	 *
	 * @param array $response     The response data containing the current redirect information.
	 * @param int   $funnel_id    The ID of the funnel.
	 * @param int   $step_id      The ID of the current step.
	 * @param array $next_step    The data of the next step.
	 * @param array $query_params Additional query parameters to be added to the redirect URL.
	 *
	 * @return array The updated response data with the generated redirect URL.
	 * @since 2.8.2
	 */
	public function regenerate_redirect_url($response, $funnel_id, $step_id, $next_step, $query_params)
	{
		// Check if the next step type is conditional.
		if (!empty($next_step['step_type']) && 'conditional' === $next_step['step_type']) {
			// Match the conditional step and update the redirect URL.
			$next_step                  = Ajax_Handler::match_conditional_step($next_step, $funnel_id, $step_id);
			$response['redirect_url'] = add_query_arg($query_params, get_the_permalink($next_step['step_id']));
		}

		return $response;
	}


	/**
	 * Add Edit Funnel menu on admin bar for admin users
	 *
	 * @param \WP_Admin_Bar $admin_bar
	 * @since 3.1.0
	 */
	public function add_edit_funnel_menu(\WP_Admin_Bar $admin_bar)
	{
		if (Wpfnl_functions::is_funnel_step_page()) {
			$funnel_id = Wpfnl_functions::get_funnel_id();

			if (!$funnel_id) {
				return;
			}

			$admin_bar->add_node(
				array(
					'id'    => 'edit-funnel',
					'title' => '<span class="ab-icon dashicons dashicons-edit"></span>' . esc_html__('Edit Funnel', 'wpfnl'),
					'href'  => admin_url("admin.php?page=edit_funnel&id={$funnel_id}&step_id=0"),
					'meta'  => array(
						'class' => 'wpf-admin-bar-edit-funnel-menu-item',
					),
				)
			);
		}
	}


	/**
	 * Add custom script to header of funnel step
	 *
	 * @return void
	 * @since 3.1.0
	 */
	public function add_custom_script()
	{
		global $post;

		if (!is_object($post) && !isset($post->ID)) {
			return;
		}

		$script = get_post_meta($post->ID, '_wpfnl_custom_script', true);
		if ('' !== $script) {
			if (false === strpos($script, htmlentities('<script'))) {
				$script = '<script>' . $script . '</script>';
			}
			echo '<!-- Custom WPFunnels Script -->';
			echo html_entity_decode($script); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<!-- End Custom WPFunnels Script -->';
		}
	}
}
