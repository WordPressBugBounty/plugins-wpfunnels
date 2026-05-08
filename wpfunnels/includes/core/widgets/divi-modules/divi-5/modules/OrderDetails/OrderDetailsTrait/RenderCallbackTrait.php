<?php
namespace WPFunnels\Widgets\DiviModules\D5\OrderDetails\OrderDetailsTrait;

if ( ! defined( 'ABSPATH' ) ) die();

use WPFunnels\Widgets\DiviModules\D5\OrderDetails\OrderDetails;
use WPFunnels\Widgets\DiviModules\D5\SharedTrait\ModuleRenderHelperTrait;

trait RenderCallbackTrait {

	use ModuleRenderHelperTrait;

	public static function render_callback(
		array $attrs,
		string $_content,
		\WP_Block $block,
		mixed $elements,
		array $_default_printed_style_attrs = []
	): string {
		$attrs = static::merge_defaults( $attrs, 'wpfnl/order-details' );
		$props = static::build_props( $attrs );

		$order_overview   = sanitize_html_class( $props['enable_order_overview']   ?? 'on' );
		$order_details    = sanitize_html_class( $props['enable_order_details']    ?? 'on' );
		$billing_details  = sanitize_html_class( $props['enable_billing_details']  ?? 'on' );
		$shipping_details = sanitize_html_class( $props['enable_shipping_details'] ?? 'on' );

		// Signal the shortcode to render demo/preview output.
		add_filter( 'wpfunnels/show_dummy_order_details', '__return_true' );

		// WooCommerce only renders billing/shipping addresses when the current
		// user matches the order owner ($show_customer_details check in
		// order-details.php:42). For demo orders in the builder the admin user
		// won't match, so force the template to load regardless.
		$force_customer_details = static function ( $order ) {
			if ( $order instanceof \WC_Order && $order->get_user_id() !== get_current_user_id() ) {
				wc_get_template( 'order/order-details-customer.php', [ 'order' => $order ] );
			}
		};
		add_action( 'woocommerce_after_order_details', $force_customer_details, 5 );

		$shortcode_html = do_shortcode( '[wpfunnels_order_details]' );

		remove_filter( 'wpfunnels/show_dummy_order_details', '__return_true' );
		remove_action( 'woocommerce_after_order_details', $force_customer_details, 5 );

		$html = '<div class="wpfnl-elementor-order-details-form'
			. ' wpfnl-elementor-display-order-overview-' . $order_overview
			. ' wpfnl-elementor-display-order-details-' . $order_details
			. ' wpfnl-elementor-display-billing-address-' . $billing_details
			. ' wpfnl-elementor-display-shipping-address-' . $shipping_details
			. '">'
			. $shortcode_html
			. '</div>';

		return static::wrap_with_module_render(
			$html,
			$attrs,
			$elements,
			$block,
			'wpfnl/order-details',
			[ OrderDetails::class, 'module_classnames' ],
			[ OrderDetails::class, 'module_styles' ],
			[ OrderDetails::class, 'module_script_data' ]
		);
	}

	private static function build_props( array $attrs ): array {
		return [
			// Try innerContent path (Divi 5 content-field save format) then fall
			// back to direct desktop path (responsive-decorator format).
			'enable_order_overview'   => static::get_text_value( $attrs, 'enable_order_overview' )
				?? static::get_attr_value( $attrs, 'enable_order_overview' )
				?? 'on',
			'enable_order_details'    => static::get_text_value( $attrs, 'enable_order_details' )
				?? static::get_attr_value( $attrs, 'enable_order_details' )
				?? 'on',
			'enable_billing_details'  => static::get_text_value( $attrs, 'enable_billing_details' )
				?? static::get_attr_value( $attrs, 'enable_billing_details' )
				?? 'on',
			'enable_shipping_details' => static::get_text_value( $attrs, 'enable_shipping_details' )
				?? static::get_attr_value( $attrs, 'enable_shipping_details' )
				?? 'on',
		];
	}
}
