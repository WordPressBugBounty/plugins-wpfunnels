<?php
namespace WPFunnels\Widgets\DiviModules\D5\OfferButton\OfferButtonTrait;

if ( ! defined( 'ABSPATH' ) ) die();

use WPFunnels\Widgets\DiviModules\D5\OfferButton\OfferButton;
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
		$attrs = static::merge_defaults( $attrs, 'wpfnl/offer-button' );

		// Extract content values
		$offer_button_type = static::get_text_value( $attrs, 'offer_button_type' ) ?: 'upsell';
		$offer_action      = static::get_text_value( $attrs, 'offer_action' ) ?: 'accept';
		$accept_text       = static::get_text_value( $attrs, 'accept_button_text' ) ?: __( 'Yes, Add to My Order!', 'wpfnl' );
		$reject_text       = static::get_text_value( $attrs, 'reject_button_text' ) ?: __( 'No Thanks', 'wpfnl' );
		$show_price        = static::get_text_value( $attrs, 'show_product_price' ) ?: 'off';
		$alignment         = static::get_text_value( $attrs, 'button_alignment' ) ?: 'left';

		// Determine which text to show based on action
		$button_text = ( $offer_action === 'accept' ) ? $accept_text : $reject_text;

		// Build alignment classes and styles
		$alignment_class = 'et_pb_button_alignment_' . esc_attr( $alignment );
		$justify_content = $alignment === 'left' ? 'flex-start' : ( $alignment === 'right' ? 'flex-end' : 'center' );

		// Build button ID, instance class and icon detection (mirrors NextStepButton pattern).
		$button_id      = sprintf( 'wpfunnels_%s_%s', esc_attr( $offer_button_type ), esc_attr( $offer_action ) );
		$module_uid     = $block->parsed_block['id'] ?? '';
		$instance_class = 'wpfnl-ob-' . substr( md5( (string) $module_uid . $button_text ), 0, 8 );

		$button_decoration = $attrs['button']['decoration']['button'] ?? [];
		$is_icon_enabled   = 'on' === ( $button_decoration['desktop']['value']['icon']['enable'] ?? 'off' );

		$button_class  = 'et_pb_button wpfnl-offer-btn offer-button wpfunnels_offer_button ' . $instance_class;
		$button_class .= $is_icon_enabled ? ' et_pb_custom_button_icon' : ' et_pb_button_no_icon';

		// Build data-icon attributes for each breakpoint (Divi uses these to render icon glyphs).
		$icon_attrs_html = '';
		if ( $is_icon_enabled && class_exists( 'ET\Builder\Packages\IconLibrary\IconFont\Utils' ) ) {
			foreach ( $button_decoration as $breakpoint => $bp_value ) {
				$icon_settings = $bp_value['value']['icon']['settings'] ?? null;
				if ( null === $icon_settings ) {
					continue;
				}
				$processed_icon = \ET\Builder\Packages\IconLibrary\IconFont\Utils::process_font_icon( $icon_settings );
				if ( '' === $processed_icon ) {
					continue;
				}
				$attr_name = 'desktop' === $breakpoint
					? 'data-icon'
					: 'data-icon-' . strtolower( preg_replace( '/([A-Z])/', '-$1', $breakpoint ) );
				$icon_attrs_html .= sprintf( ' %s="%s"', esc_attr( $attr_name ), esc_attr( $processed_icon ) );
			}
		}

		// Inject Divi style components when available
		$style_components = '';
		if ( $elements && method_exists( $elements, 'style_components' ) ) {
			$style_components = $elements->style_components( [ 'attrName' => 'button' ] );
		}

		// Build inline button style
		$inline_style = static::build_button_inline_style( $attrs );

		// Build icon CSS block scoped to instance class
		$icon_css = $is_icon_enabled
			? static::build_icon_css_block( $attrs, '.' . $instance_class )
			: '';

		// Build product price HTML (only for accept action)
		$price_html = '';
		if ( $show_price === 'on' && $offer_action === 'accept' ) {
			$price_html = static::get_product_price_html();
		}

		// Outer wrapper: drives flex alignment for both price and button.
		$outer_style = sprintf(
			'display:flex; flex-flow:row wrap; align-items:center; gap:10px; justify-content:%s;',
			$justify_content
		);

		// Compose final HTML
		ob_start();
		?>
		<?php echo $icon_css; ?>
		<div class="wpfnl-offerbtn-wrapper" id="wpfnl-offerbtn-wrapper">
			<div class="wpfnl-offerbtn-and-price-wrapper <?php echo esc_attr( $alignment_class ); ?>"
				 style="<?php echo esc_attr( $outer_style ); ?>"
				 data-alignment="<?php echo esc_attr( $alignment ); ?>">
				<?php if ( $price_html ) : ?>
					<span class="wpfnl-offer-product-price" id="wpfnl-offer-product-price">
						<?php echo $price_html; ?>
					</span>
				<?php endif; ?>
				<div class="et_pb_button_module_wrapper">
					<a id="<?php echo esc_attr( $button_id ); ?>"
					   class="<?php echo esc_attr( $button_class ); ?>"
					   data-offertype="<?php echo esc_attr( $offer_button_type ); ?>"
					   href="#"
					   <?php echo $icon_attrs_html; ?>
					   style="<?php echo esc_attr( $inline_style ); ?>">
						<?php echo $style_components; ?>
						<?php echo esc_html( $button_text ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		// Fire after offer button hook — mirrors D4 condition exactly:
		// fires for all non-reject cases when WooCommerce is active.
		// This renders the PayPal payment form modal and pre-populates the cart.
		if ( $offer_action !== 'reject'
			&& class_exists( '\WPFunnels\Wpfnl_functions' )
			&& \WPFunnels\Wpfnl_functions::is_wc_active()
		) {
			do_action( 'wpfunnels/after_offer_button' );
		}

		return static::wrap_with_module_render(
			$html,
			$attrs,
			$elements,
			$block,
			'wpfnl/offer-button',
			[ OfferButton::class, 'module_classnames' ],
			[ OfferButton::class, 'module_styles' ],
			[ OfferButton::class, 'module_script_data' ]
		);
	}

	/**
	 * Build inline button style mirroring design values.
	 */
	private static function build_button_inline_style( array $attrs ): string {
		$deco        = $attrs['button']['decoration'] ?? [];
		$btn_group   = $deco['button']['desktop']['value'] ?? [];
		$bg_group    = $btn_group['backgroundGroup']['background'] ?? [];
		$border_grp  = $btn_group['borderGroup']['border'] ?? [];
		$font_group  = $btn_group['fontGroup']['font'] ?? [];
		$deco_font   = $deco['font']['font']['desktop']['value'] ?? [];
		$deco_space  = $deco['spacing']['desktop']['value'] ?? [];
		$deco_bg     = $deco['background']['desktop']['value'] ?? [];
		$deco_border = $deco['border']['desktop']['value']['styles']['all'] ?? [];
		$deco_radius = $deco['border']['desktop']['value']['radius'] ?? null;

		// Start with WPFunnels default button appearance (mirrors wpfnl-public.css
		// .wpfunnels_offer_button — that rule is scoped under .wpfunnel_steps-template*
		// and may not apply on Divi 5 pages).
		$styles = [
			'display:inline-flex',
			'align-items:center',
			'background-color:#6E42D3',
			'color:#ffffff',
			'border-radius:5px',
			'padding:10px 20px',
			'font-size:15px',
			'font-weight:700',
			'line-height:1.2',
			'border:none',
			'text-decoration:none',
		];

		// User-set values override the defaults.

		// Background color
		$bg_color = $bg_group['color'] ?? $deco_bg['color'] ?? null;
		if ( $bg_color ) {
			$styles[] = 'background-color:' . esc_attr( $bg_color );
		}

		// Text color
		$text_color = $font_group['color'] ?? $deco_font['color'] ?? null;
		if ( $text_color ) {
			$styles[] = 'color:' . esc_attr( $text_color );
		}

		// Font size
		$font_size = $font_group['size'] ?? $deco_font['size'] ?? null;
		if ( $font_size ) {
			$styles[] = 'font-size:' . static::with_unit( $font_size );
		}

		// Letter spacing
		$letter_spacing = $font_group['letterSpacing'] ?? $deco_font['letterSpacing'] ?? null;
		if ( $letter_spacing ) {
			$styles[] = 'letter-spacing:' . static::with_unit( $letter_spacing );
		}

		// Line height
		$line_height = $font_group['lineHeight'] ?? $deco_font['lineHeight'] ?? null;
		if ( $line_height ) {
			$styles[] = 'line-height:' . esc_attr( $line_height );
		}

		// Font family
		$font_family = $font_group['family'] ?? $deco_font['family'] ?? null;
		if ( $font_family ) {
			$styles[] = 'font-family:' . esc_attr( $font_family );
		}

		// Font weight
		$font_weight = $font_group['weight'] ?? $deco_font['weight'] ?? null;
		if ( $font_weight ) {
			$styles[] = 'font-weight:' . esc_attr( $font_weight );
		}

		// Font style
		$font_style = $font_group['style']['fontStyle'] ?? $deco_font['style']['fontStyle'] ?? null;
		if ( $font_style ) {
			$styles[] = 'font-style:' . esc_attr( $font_style );
		}

		// Border
		$border_width = $border_grp['allWidth'] ?? $deco_border['width'] ?? null;
		$border_color = $border_grp['allColor'] ?? $deco_border['color'] ?? null;
		$border_style = $border_grp['allStyle'] ?? $deco_border['style'] ?? 'solid';
		if ( $border_width ) {
			$styles[] = 'border-width:' . static::with_unit( $border_width );
			$styles[] = 'border-style:' . esc_attr( $border_style );
		}
		if ( $border_color ) {
			$styles[] = 'border-color:' . esc_attr( $border_color );
		}

		// Border radius
		$radius = $border_grp['radius'] ?? $deco_radius;
		if ( is_string( $radius ) && $radius !== '' ) {
			$styles[] = 'border-radius:' . static::with_unit( $radius );
		} elseif ( is_array( $radius ) ) {
			$t  = static::with_unit( $radius['topLeft'] ?? '0' );
			$tr = static::with_unit( $radius['topRight'] ?? '0' );
			$br = static::with_unit( $radius['bottomRight'] ?? '0' );
			$bl = static::with_unit( $radius['bottomLeft'] ?? '0' );
			if ( ! ( $t === '0' && $tr === '0' && $br === '0' && $bl === '0' ) ) {
				$styles[] = "border-radius:{$t} {$tr} {$br} {$bl}";
			}
		}

		// Margin
		$margin = static::sides_string( $deco_space['margin'] ?? [] );
		if ( $margin ) {
			$styles[] = 'margin:' . $margin;
		}

		// Padding
		$padding = static::sides_string( $deco_space['padding'] ?? [] );
		if ( $padding ) {
			$styles[] = 'padding:' . $padding;
		}

		return implode( '; ', $styles );
	}

	/**
	 * Build icon CSS block scoped to $base_selector (e.g. ".wpfnl-ob-XXXXXXXX").
	 * Mirrors NextStepButton's build_icon_css_block exactly.
	 */
	private static function build_icon_css_block( array $attrs, string $base_selector ): string {
		$btn_value = $attrs['button']['decoration']['button']['desktop']['value'] ?? [];
		$icon      = $btn_value['icon'] ?? [];
		$settings  = $icon['settings'] ?? [];
		if ( empty( $settings['unicode'] ) ) {
			return '';
		}

		$placement  = ( $icon['placement'] ?? 'right' ) === 'left' ? 'before' : 'after';
		$on_hover   = ( $icon['onHover']   ?? 'on' )  === 'on';
		$icon_color = $icon['color'] ?? '';

		$char = '';
		if ( class_exists( 'ET\Builder\Packages\IconLibrary\IconFont\Utils' ) ) {
			$processed = \ET\Builder\Packages\IconLibrary\IconFont\Utils::process_font_icon( $settings );
			if ( '' !== $processed ) {
				$code = function_exists( 'mb_ord' ) ? mb_ord( $processed, 'UTF-8' ) : null;
				if ( null === $code ) {
					$utf16 = mb_convert_encoding( $processed, 'UTF-16BE', 'UTF-8' );
					$code  = ( ord( $utf16[0] ) << 8 ) | ord( $utf16[1] );
				}
				$char = '\\' . dechex( $code );
			}
		}
		if ( '' === $char ) {
			return '';
		}

		$type        = $settings['type'] ?? 'divi';
		$font_family = 'fa' === $type ? 'FontAwesome' : 'ETmodules';
		$other       = 'before' === $placement ? 'after' : 'before';
		$pseudo      = $base_selector . ':' . $placement;

		$active_decls = [
			'display:inline-block !important',
			'opacity:1 !important',
			'position:static !important',
			'top:auto !important',
			'right:auto !important',
			'left:auto !important',
			'transform:none !important',
			"font-family:'{$font_family}' !important",
			"content:'{$char}' !important",
			'line-height:1em',
			'font-size:inherit',
		];
		if ( '' !== $icon_color ) {
			$active_decls[] = 'color:' . $icon_color . ' !important';
		}
		if ( 'before' === $placement ) {
			$active_decls[] = 'margin-left:-1.3em';
			$active_decls[] = 'margin-right:0.3em';
		} else {
			$active_decls[] = 'margin-left:0.3em';
		}

		$css  = $pseudo . '{' . implode( ';', $active_decls ) . ';}';
		$css .= $base_selector . ':' . $other . '{display:none !important;content:none !important;}';

		if ( $on_hover ) {
			$side = 'before' === $placement ? 'right' : 'left';
			$css .= $pseudo . '{margin-' . $side . ':-1em !important;opacity:0 !important;}';
			$css .= $base_selector . ':hover:' . $placement . '{margin-' . $side . ':0.3em !important;opacity:1 !important;}';
		}

		return '<style>' . $css . '</style>';
	}

	/**
	 * Get product price HTML.
	 */
	private static function get_product_price_html(): string {
		$step_id = get_the_ID();
		if ( ! $step_id ) {
			return '';
		}

		$step_type = get_post_meta( $step_id, '_step_type', true );
		$products  = get_post_meta( $step_id, '_wpfnl_' . $step_type . '_products', true );

		if ( empty( $products ) || ! is_array( $products ) ) {
			return '';
		}

		$product_id = $products[0]['id'] ?? 0;
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		return $product->get_price_html();
	}

	/**
	 * Helper: append 'px' to bare numbers.
	 */
	private static function with_unit( $v ): string {
		if ( $v === null || $v === '' ) {
			return '';
		}
		$s = trim( (string) $v );
		if ( $s === '0' ) {
			return '0';
		}
		if ( preg_match( '/^[\d.]+$/', $s ) ) {
			return $s . 'px';
		}
		return $s;
	}

	/**
	 * Helper: build 4-side shorthand string.
	 */
	private static function sides_string( $sides ): string {
		if ( ! is_array( $sides ) ) {
			return '';
		}
		$t = static::with_unit( $sides['top'] ?? '0' );
		$r = static::with_unit( $sides['right'] ?? '0' );
		$b = static::with_unit( $sides['bottom'] ?? '0' );
		$l = static::with_unit( $sides['left'] ?? '0' );
		if ( $t === '0' && $r === '0' && $b === '0' && $l === '0' ) {
			return '';
		}
		return "{$t} {$r} {$b} {$l}";
	}
}
