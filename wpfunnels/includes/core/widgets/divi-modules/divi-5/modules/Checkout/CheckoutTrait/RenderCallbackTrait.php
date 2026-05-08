<?php
namespace WPFunnels\Widgets\DiviModules\D5\Checkout\CheckoutTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use WPFunnels\Widgets\DiviModules\D5\Checkout\Checkout;
use WPFunnels\Widgets\DiviModules\D5\SharedTrait\ModuleRenderHelperTrait;
use WPFunnels\Wpfnl_functions;

trait RenderCallbackTrait {

	use ModuleRenderHelperTrait;

	public static function render_callback(
		array $attrs,
		string $_content,
		\WP_Block $block,
		mixed $elements,
		array $_default_printed_style_attrs = []
	): string {
		$attrs = static::merge_defaults( $attrs, 'wpfnl/checkout' );
		$props = static::build_props( $attrs );

		// Use scope class from REST request (editor) or generate one (frontend)
		$scope_class = $block->parsed_block['wpfnl_scope_class'] ?? null;
		if ( empty( $scope_class ) ) {
			$module_uid  = $block->parsed_block['id'] ?? '';
			$scope_class = 'wpfnl-co-' . substr( md5( (string) $module_uid ), 0, 8 );
		}
		$props['scope_class'] = $scope_class;

		// Build inline button style CSS for editor preview
		$button_inline_css = static::build_order_button_inline_style( $attrs, $scope_class );

		$html = static::render_checkout_html( $props );

		// Inject scoped CSS for order button design settings INSIDE the HTML
		if ( ! empty( $button_inline_css ) ) {
			// Insert the style tag right after the opening div
			$html = preg_replace(
				'/(<div[^>]*class="[^"]*wpfnl-checkout[^"]*"[^>]*>)/',
				'$1<style>' . $button_inline_css . '</style>',
				$html,
				1
			);
		}

		return static::wrap_with_module_render(
			$html,
			$attrs,
			$elements,
			$block,
			'wpfnl/checkout',
			[ Checkout::class, 'module_classnames' ],
			[ Checkout::class, 'module_styles' ],
			[ Checkout::class, 'module_script_data' ]
		);
	}

	private static function render_checkout_html( array $props ): string {
		$checkout_layout  = $props['layout'] ?? '';
		$floating_label   = $props['checkout_floating_label'] ?? '';
		$show_coupon      = $props['show_coupon_field'] ?? 'on';
		$enable_summary   = $props['enable_order_summary'] ?? 'on';
		$btn_text         = $props['place_order_button_text'] ?? '';
		$scope_class      = $props['scope_class'] ?? '';

		// Resolve step ID (mirrors Divi 4 get_checkout_form logic).
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$step_id = get_the_ID();
		if ( ! $step_id ) {
			$step_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
		}
		// phpcs:enable

		// Persist layout to post meta so the WooCommerce templates can read it.
		if ( $step_id > 0 ) {
			update_post_meta( $step_id, '_wpfnl_checkout_layout', $checkout_layout );
		}

		// Session-based layout flag (matches Divi 4 behaviour).
		if ( PHP_SESSION_DISABLED === session_status() ) {
			session_start();
		}
		$_SESSION['checkout_layout'] = $checkout_layout;

		// Conditional layout class modifications.
		if ( Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-express-checkout' === $checkout_layout ) {
			$checkout_layout .= ' wpfnl-multistep';
		}
		if ( Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-2-step' === $checkout_layout ) {
			$checkout_layout .= ' wpfnl-multistep';
		}
		if ( 'wpfnl-modern-one-column' === $checkout_layout || 'wpfnl-modern-checkout' === $checkout_layout ) {
			if ( false === strpos( $checkout_layout, 'wpfnl-modern-checkout' ) ) {
				$checkout_layout .= ' wpfnl-modern-checkout';
			}
		}
		if ( 'wpfnl-modern-multistep' === $checkout_layout ) {
			$checkout_layout .= ' wpfnl-modern-checkout';
		}

		// Coupon field: save meta so display_coupon_field() in the public class picks it up.
		if ( $step_id > 0 ) {
			update_post_meta( $step_id, '_wpfnl_checkout_coupon', 'on' === $show_coupon ? 'yes' : 'no' );
		}

		// Place order button text: filter WooCommerce's button HTML.
		$place_order_filter = null;
		if ( ! empty( $btn_text ) ) {
			$label              = $btn_text;
			$place_order_filter = static function( $button_html ) use ( $label ) {
				$safe = esc_html( $label );
				return '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" '
					. 'id="place_order" value="' . $safe . '" data-value="' . $safe . '">'
					. '<span class="wpfnl-place-order-text">' . $safe . '</span>'
					. '</button>';
			};
			add_filter( 'woocommerce_order_button_html', $place_order_filter );

			// Persist for WooCommerce AJAX order-review updates.
			if ( $step_id > 0 ) {
				update_post_meta( $step_id, '_wpfnl_place_order_settings', [ 'placeOrderBtnText' => $btn_text ] );
			}
		}

		// Order summary: add a CSS class when hidden so themes/CSS can target it.
		$summary_class = 'off' === $enable_summary ? 'wpfnl-hide-order-summary' : '';

		// In REST (builder preview), initialize_cart_data() never fires — populate manually.
		if ( static::is_rest_request() && $step_id > 0 ) {
			static::populate_cart_for_preview( $step_id );
		}

		do_action( 'wpfunnels/before_checkout_form', $step_id );

		// In REST/preview context, is_funnel_step_page() returns false so the public class
		// never registers woocommerce_locate_template — WooCommerce would then use its own
		// default form-checkout.php which ignores $_SESSION['checkout_layout'].
		// Register the filter manually for the duration of this render.
		$locate_filter = static function ( $template, $template_name ) {
			$plugin_path = WPFNL_DIR . '/woocommerce/templates/';
			if ( file_exists( $plugin_path . $template_name ) ) {
				return $plugin_path . $template_name;
			}
			return $template;
		};
		add_filter( 'woocommerce_locate_template', $locate_filter, 20, 3 );

		$classes = implode( ' ', array_filter( [ 'wpfnl-checkout', $checkout_layout, $floating_label, $summary_class, $scope_class ] ) );
		$html    = '<div class="' . esc_attr( $classes ) . '">'
			. do_shortcode( '[woocommerce_checkout]' )
			. '</div>';

		remove_filter( 'woocommerce_locate_template', $locate_filter, 20 );

		if ( null !== $place_order_filter ) {
			remove_filter( 'woocommerce_order_button_html', $place_order_filter );
		}

		return $html;
	}

	/**
	 * Load funnel checkout products into the WooCommerce cart for the builder preview.
	 * Mirrors what initialize_cart_data() does on the frontend (wp action).
	 */
	private static function populate_cart_for_preview( int $step_id ): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$products = get_post_meta( $step_id, '_wpfnl_checkout_products', true );
		if ( empty( $products ) || ! is_array( $products ) ) {
			return;
		}

		WC()->cart->empty_cart();

		foreach ( $products as $product ) {
			$product_id   = isset( $product['id'] ) ? absint( $product['id'] ) : 0;
			$variation_id = isset( $product['variation_id'] ) ? absint( $product['variation_id'] ) : 0;
			$quantity     = isset( $product['quantity'] ) ? max( 1, absint( $product['quantity'] ) ) : 1;

			if ( $product_id <= 0 ) {
				continue;
			}

			try {
				WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
			} catch ( \Exception $e ) {
				// Skip products that can't be added (out of stock, etc.).
			}
		}
	}

	private static function build_props( array $attrs ): array {
		return [
			'layout'                  => static::get_text_value( $attrs, 'checkout_layout' ) ?? '',
			'checkout_floating_label' => static::get_text_value( $attrs, 'checkout_floating_label' ) ?? '',
			'show_coupon_field'       => static::get_text_value( $attrs, 'show_coupon_field' ) ?? 'on',
			'enable_order_summary'    => static::get_text_value( $attrs, 'enable_order_summary' ) ?? 'on',
			'place_order_button_text' => static::get_text_value( $attrs, 'place_order_button_text' ) ?? '',
		];
	}

	/**
	 * Append `px` if value is bare number, else return as-is.
	 *
	 * @param mixed $value Raw value from attrs.
	 * @return string
	 */
	protected static function with_unit( $value ): string {
		if ( null === $value || '' === $value ) {
			return '';
		}
		$s = trim( (string) $value );
		if ( '0' === $s ) {
			return '0';
		}
		if ( preg_match( '/^[\d.]+$/', $s ) ) {
			return $s . 'px';
		}
		return $s;
	}

	/**
	 * Convert a 4-side spacing array to a CSS shorthand string. Empty if all zero.
	 *
	 * @param mixed $sides Array with top/right/bottom/left keys.
	 * @return string
	 */
	protected static function sides_string( $sides ): string {
		if ( ! is_array( $sides ) ) {
			return '';
		}
		$t = static::with_unit( $sides['top']    ?? '' );
		$r = static::with_unit( $sides['right']  ?? '' );
		$b = static::with_unit( $sides['bottom'] ?? '' );
		$l = static::with_unit( $sides['left']   ?? '' );
		$t = '' === $t ? '0' : $t;
		$r = '' === $r ? '0' : $r;
		$b = '' === $b ? '0' : $b;
		$l = '' === $l ? '0' : $l;
		if ( '0' === $t && '0' === $r && '0' === $b && '0' === $l ) {
			return '';
		}
		return $t . ' ' . $r . ' ' . $b . ' ' . $l;
	}

	/**
	 * Build scoped CSS for the Place Order button from order_button decoration attrs.
	 * Mirrors the React buildOrderButtonCss() function.
	 *
	 * @param array  $attrs       Module attributes.
	 * @param string $scope_class Scoped wrapper class (e.g. "wpfnl-co-XXXXXXXX").
	 * @return string CSS rules scoped to the wrapper class.
	 */
	protected static function build_order_button_inline_style( array $attrs, string $scope_class ): string {
		$deco         = $attrs['order_button']['decoration'] ?? [];
		$btn_group    = $deco['button']['desktop']['value'] ?? [];
		$bg_group     = $btn_group['backgroundGroup']['background'] ?? [];
		$border_group = $btn_group['borderGroup']['border'] ?? [];
		$font_group   = $btn_group['fontGroup']['font'] ?? [];
		$deco_bg      = $deco['background']['desktop']['value'] ?? [];
		$deco_font    = $deco['font']['font']['desktop']['value'] ?? [];
		$deco_spacing = $deco['spacing']['desktop']['value'] ?? [];
		$deco_border  = $deco['border']['desktop']['value']['styles']['all'] ?? [];
		$deco_radius  = $deco['border']['desktop']['value']['radius'] ?? null;

		$decls = [];

		$bg = $bg_group['color'] ?? ( $deco_bg['color'] ?? '' );
		if ( '' !== $bg ) {
			$decls[] = 'background-color:' . $bg . ' !important';
		}

		$tc = $font_group['color'] ?? ( $deco_font['color'] ?? '' );
		if ( '' !== $tc ) {
			$decls[] = 'color:' . $tc . ' !important';
		}

		$ts = $font_group['size'] ?? ( $deco_font['size'] ?? '' );
		if ( '' !== $ts ) {
			$decls[] = 'font-size:' . static::with_unit( $ts ) . ' !important';
		}

		$ls = $font_group['letterSpacing'] ?? ( $deco_font['letterSpacing'] ?? '' );
		if ( '' !== $ls ) {
			$decls[] = 'letter-spacing:' . static::with_unit( $ls ) . ' !important';
		}

		$bw = $border_group['allWidth'] ?? ( $deco_border['width'] ?? '' );
		$bc = $border_group['allColor'] ?? ( $deco_border['color'] ?? '' );
		$bs = $border_group['allStyle'] ?? ( $deco_border['style'] ?? 'solid' );
		if ( '' !== $bw ) {
			$decls[] = 'border-width:' . static::with_unit( $bw ) . ' !important';
			$decls[] = 'border-style:' . $bs . ' !important';
		}
		if ( '' !== $bc ) {
			$decls[] = 'border-color:' . $bc . ' !important';
		}

		$radius = $border_group['radius'] ?? $deco_radius;
		if ( is_string( $radius ) && '' !== $radius ) {
			$decls[] = 'border-radius:' . static::with_unit( $radius ) . ' !important';
		} elseif ( is_array( $radius ) ) {
			$tl = static::with_unit( $radius['topLeft']     ?? '' );
			$tr = static::with_unit( $radius['topRight']    ?? '' );
			$br = static::with_unit( $radius['bottomRight'] ?? '' );
			$bl = static::with_unit( $radius['bottomLeft']  ?? '' );
			$tl = '' === $tl ? '0' : $tl;
			$tr = '' === $tr ? '0' : $tr;
			$br = '' === $br ? '0' : $br;
			$bl = '' === $bl ? '0' : $bl;
			if ( ! ( '0' === $tl && '0' === $tr && '0' === $br && '0' === $bl ) ) {
				$decls[] = 'border-radius:' . $tl . ' ' . $tr . ' ' . $br . ' ' . $bl . ' !important';
			}
		}

		$margin  = static::sides_string( $deco_spacing['margin']  ?? null );
		$padding = static::sides_string( $deco_spacing['padding'] ?? null );
		if ( '' !== $margin ) {
			$decls[] = 'margin:' . $margin . ' !important';
		}
		if ( '' !== $padding ) {
			$decls[] = 'padding:' . $padding . ' !important';
		}

		if ( empty( $decls ) ) {
			return '';
		}

		// Use double class selector for higher specificity
		return '.' . $scope_class . '.' . $scope_class . ' #payment #place_order{' . implode( ';', $decls ) . '}';
	}
}