<?php
/**
 * This class is responsible for rendering the testimonial section on frontend checkout pages.
 *
 * @package WPFunnels
 */
namespace WPFunnels\Classes\TestimonialActions;

use WPFunnels\Wpfnl_functions;

class Wpfnl_Testimonial_Action {

	/**
	 * Testimonial config for the current checkout step.
	 *
	 * @var array
	 */
	protected $config = array();

	/**
	 * Current checkout step ID.
	 *
	 * @var int
	 */
	protected $checkout_id = 0;

	public function __construct() {
		add_action( 'wpfunnels/before_checkout_form', array( $this, 'load_actions' ), 20, 1 );
		add_action( 'wpfunnels/elementor_render_testimonial', array( $this, 'load_elementor_actions' ), 9999, 1 );
	}


	/**
	 * Load action hooks for testimonial render (all builder paths).
	 *
	 * @param int $checkout_id
	 *
	 * @return void
	 */
	public function load_actions( $checkout_id ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( 'elementor/elementor.php' ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				return;
			}
		}

		$this->checkout_id = (int) $checkout_id;
		$this->config      = $this->get_config( $this->checkout_id );

		$this->trigger_testimonial_actions();
	}


	/**
	 * Load action hooks for Elementor editor/preview.
	 *
	 * @param int $checkout_id
	 *
	 * @return void
	 */
	public function load_elementor_actions( $checkout_id ) {
		$this->checkout_id = (int) $checkout_id;
		$this->config      = $this->get_config( $this->checkout_id );
		$this->trigger_testimonial_actions();
	}


	/**
	 * Get and validate testimonial config from post meta.
	 *
	 * @param int $checkout_id
	 *
	 * @return array
	 */
	private function get_config( $checkout_id ) {
		$raw = get_post_meta( $checkout_id, 'wpf_checkout_testimonial', true );

		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$defaults = array(
			'enabled'     => false,
			'layout'      => 'layout-1',
			'position'    => 'after_bump',
			'testimonial' => array(
				'text'   => '',
				'author' => '',
				'rating' => 5,
			),
			'guarantee'   => array(
				'headline' => '',
				'text'     => '',
				'days'     => 30,
				'image'    => array( 'id' => 0, 'url' => '' ),
			),
			'benefits'    => array(
				'title' => 'Here\'s what you get',
				'items' => array(),
			),
		);

		return wp_parse_args( $raw, $defaults );
	}


	/**
	 * Register WooCommerce/WPFunnels action hooks based on the configured position.
	 *
	 * @return void
	 */
	private function trigger_testimonial_actions() {
		if ( empty( $this->config['enabled'] ) ) {
			return;
		}

		$position = isset( $this->config['position'] ) ? $this->config['position'] : 'after_bump';

		if ( 'after_bump' === $position ) {
			add_action( 'wpfunnels/after_order_total', array( $this, 'render' ), 20 );
		} elseif ( 'before_bump' === $position ) {
			add_action( 'woocommerce_review_order_before_cart_contents', array( $this, 'render' ), 20 );
		} elseif ( 'above_form' === $position ) {
			add_action( 'woocommerce_before_checkout_form', array( $this, 'render' ), 20 );
		} elseif ( 'below_form' === $position ) {
			add_action( 'woocommerce_review_order_after_payment', array( $this, 'render' ), 20 );
		}
	}


	/**
	 * Render the testimonial section HTML.
	 *
	 * @return void
	 */
	public function render() {
		if ( empty( $this->config['enabled'] ) ) {
			return;
		}

		$layout   = sanitize_key( isset( $this->config['layout'] ) ? $this->config['layout'] : 'layout-1' );
		$template = WPFNL_DIR . 'public/modules/checkout/testimonial/' . $layout . '.php';

		if ( file_exists( $template ) ) {
			load_template( $template, false, $this->config );
		}
	}
}
