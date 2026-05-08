<?php
namespace WPFunnels\Widgets\DiviModules\D5\NextStepButton\NextStepButtonTrait;

if ( ! defined( 'ABSPATH' ) ) die();

use WPFunnels\Widgets\DiviModules\D5\NextStepButton\NextStepButton;
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
		$attrs = static::merge_defaults( $attrs, 'wpfnl/next-step-button' );

		// ---- Content props ----
		$button_text     = static::get_text_value( $attrs, 'button_text' ) ?? __( 'Next Step', 'wpfnl' );
		
		// The alignment is saved in innerContent structure (like text fields)
		// because the visual builder is using the old module.json structure
		$alignment = static::get_text_value( $attrs, 'button_alignment' ) ?? 'center';
		
		$button_type     = static::get_text_value( $attrs, 'button_type_selector' ) ?? 'checkout';
		$enable_subtitle = static::get_text_value( $attrs, 'enable_subtitle' ) ?? 'off';
		$subtitle_text   = static::get_text_value( $attrs, 'subtitle_text' ) ?? __( 'Click to continue', 'wpfnl' );
		$url             = '';

		if ( 'another-funnel' === $button_type ) {
			$url = static::get_text_value( $attrs, 'another_funnel_field' ) ?? '';
		} elseif ( 'url-path' === $button_type ) {
			$url = static::get_text_value( $attrs, 'url_path_field' ) ?? '';
		}

		// ---- Build subtitle HTML ----
		// Native Divi 5 `subtitle` element decoration (font + spacing) handles
		// color, family, size, weight, style, line-height, letter-spacing,
		// text-transform and margin via ModuleStylesTrait.
		$subtitle_html = '';
		if ( 'on' === $enable_subtitle && '' !== $subtitle_text ) {
			$subtitle_html = sprintf(
				'<small class="wpfnl-button-subtitle" style="display:block;">%s</small>',
				esc_html( $subtitle_text )
			);
		}

		// ---- Build button inner content ----
		// Inject Divi 5 button style components (handles icon, hover styles, etc.)
		// when running in the full Module::render() context (not REST preview).
		$style_components = '';
		if ( $elements && method_exists( $elements, 'style_components' ) ) {
			$style_components = $elements->style_components( [ 'attrName' => 'button' ] );
		}

		if ( '' !== $subtitle_html ) {
			$inner_content = sprintf(
				'<span class="wpfnl-divi-btn-text-wrap" style="display:flex;flex-direction:column;align-items:center;"><span>%s</span>%s</span>',
				esc_html( $button_text ),
				$subtitle_html
			);
		} else {
			$inner_content = esc_html( $button_text );
		}
		
		$alignment_class = 'et_pb_button_alignment_' . sanitize_html_class( $alignment );

		$alignment_style = '';
		switch ( $alignment ) {
			case 'left':
				$alignment_style = 'display: flex; justify-content: flex-start; width: 100%;';
				break;
			case 'right':
				$alignment_style = 'display: flex; justify-content: flex-end; width: 100%;';
				break;
			case 'center':
			default:
				$alignment_style = 'display: flex; justify-content: center; width: 100%;';
				break;
		}

		// Build icon data-* attributes mirroring native Divi 5 ButtonModule.
		// CSS for icon `:before/:after` pulls character from data-icon attribute.
		$icon_attrs_html  = '';
		$button_decoration = $attrs['button']['decoration']['button'] ?? [];
		$is_icon_enabled   = 'on' === ( $button_decoration['desktop']['value']['icon']['enable'] ?? 'off' );
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

		// Inline button style — mirrors edit.tsx so builder and frontend match.
		$button_inline_style = static::build_button_inline_style( $attrs );
		$module_uid          = $block->parsed_block['id'] ?? '';
		$instance_class      = 'wpfnl-nsb-' . substr( md5( (string) $module_uid . $button_text ), 0, 8 );
		$button_class        = 'et_pb_button wpfnl-next-step-btn next-step-button ' . $instance_class;
		$button_class       .= $is_icon_enabled ? ' et_pb_custom_button_icon' : ' et_pb_button_no_icon';

		$icon_css_block = $is_icon_enabled
			? static::build_icon_css_block( $attrs, '.' . $instance_class )
			: '';

		$html = sprintf(
			'%s<div class="et_pb_button_module_wrapper %s" style="%s" data-alignment="%s">
				<a id="wpfunnels_next_step_controller"
				   class="%s"
				   data-button-type="%s"
				   data-url="%s"
				   href="#"%s style="%s">%s%s</a>
			</div>',
			$icon_css_block,
			esc_attr( $alignment_class ),
			esc_attr( $alignment_style ),
			esc_attr( $alignment ),
			esc_attr( $button_class ),
			esc_attr( $button_type ),
			esc_url( $url ),
			$icon_attrs_html,
			esc_attr( $button_inline_style ),
			$style_components,
			$inner_content
		);

		return static::wrap_with_module_render(
			$html,
			$attrs,
			$elements,
			$block,
			'wpfnl/next-step-button',
			[ NextStepButton::class, 'module_classnames' ],
			[ NextStepButton::class, 'module_styles' ],
			[ NextStepButton::class, 'module_script_data' ]
		);
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
	 * Build inline CSS string from button decoration attrs. Mirrors React side
	 * so builder preview and frontend produce identical button styling.
	 *
	 * @param array $attrs Module attributes.
	 * @return string CSS declarations joined with `;`.
	 */
	protected static function build_button_inline_style( array $attrs ): string {
		$deco         = $attrs['button']['decoration'] ?? [];
		$btn_group    = $deco['button']['desktop']['value'] ?? [];
		$bg_group     = $btn_group['backgroundGroup']['background'] ?? [];
		$border_group = $btn_group['borderGroup']['border']         ?? [];
		$font_group   = $btn_group['fontGroup']['font']             ?? [];
		$deco_font    = $deco['font']['font']['desktop']['value']   ?? [];
		$deco_spacing = $deco['spacing']['desktop']['value']        ?? [];
		$deco_bg_root = $deco['background']['desktop']['value']     ?? [];
		$deco_border  = $deco['border']['desktop']['value']['styles']['all'] ?? [];
		$deco_radius  = $deco['border']['desktop']['value']['radius']        ?? null;

		$decls   = [];
		$decls[] = 'display:inline-flex';
		$decls[] = 'align-items:center';

		$bg = $bg_group['color'] ?? ( $deco_bg_root['color'] ?? '' );
		if ( '' !== $bg ) {
			$decls[] = 'background-color:' . $bg;
		}

		$tc = $font_group['color'] ?? ( $deco_font['color'] ?? '' );
		if ( '' !== $tc ) {
			$decls[] = 'color:' . $tc;
		}

		$ts = $font_group['size'] ?? ( $deco_font['size'] ?? '' );
		if ( '' !== $ts ) {
			$decls[] = 'font-size:' . static::with_unit( $ts );
		}

		$ls = $font_group['letterSpacing'] ?? ( $deco_font['letterSpacing'] ?? '' );
		if ( '' !== $ls ) {
			$decls[] = 'letter-spacing:' . static::with_unit( $ls );
		}

		$lh = $font_group['lineHeight'] ?? ( $deco_font['lineHeight'] ?? '' );
		if ( '' !== $lh ) {
			$decls[] = 'line-height:' . $lh;
		}

		$ff = $font_group['family'] ?? ( $deco_font['family'] ?? '' );
		if ( '' !== $ff ) {
			$decls[] = 'font-family:' . $ff;
		}

		$fw = $font_group['weight'] ?? ( $deco_font['weight'] ?? '' );
		if ( '' !== $fw ) {
			$decls[] = 'font-weight:' . $fw;
		}

		$fst = $font_group['style']['fontStyle'] ?? ( $deco_font['style']['fontStyle'] ?? '' );
		if ( '' !== $fst ) {
			$decls[] = 'font-style:' . $fst;
		}

		$bw = $border_group['allWidth'] ?? ( $deco_border['width'] ?? '' );
		$bc = $border_group['allColor'] ?? ( $deco_border['color'] ?? '' );
		$bs = $border_group['allStyle'] ?? ( $deco_border['style'] ?? '' );
		if ( '' !== $bw ) {
			$decls[] = 'border-width:' . static::with_unit( $bw );
			$decls[] = 'border-style:' . ( '' !== $bs ? $bs : 'solid' );
		}
		if ( '' !== $bc ) {
			$decls[] = 'border-color:' . $bc;
		}

		$radius = $border_group['radius'] ?? $deco_radius;
		if ( is_string( $radius ) && '' !== $radius ) {
			$decls[] = 'border-radius:' . static::with_unit( $radius );
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
				$decls[] = 'border-radius:' . $tl . ' ' . $tr . ' ' . $br . ' ' . $bl;
			}
		}

		$margin = static::sides_string( $deco_spacing['margin']  ?? null );
		$padding = static::sides_string( $deco_spacing['padding'] ?? null );
		if ( '' !== $margin ) {
			$decls[] = 'margin:' . $margin;
		}
		if ( '' !== $padding ) {
			$decls[] = 'padding:' . $padding;
		}

		return implode( ';', $decls );
	}

	/**
	 * Build a `<style>` block emulating the native Divi 5 button icon CSS rules,
	 * scoped to a per-instance class. Renders icon character via :before/:after
	 * pseudo-element so it appears in builder and frontend identically.
	 *
	 * @param array  $attrs        Module attributes.
	 * @param string $base_selector Selector targeting the button anchor (e.g. `.wpfnl-nsb-XYZ`).
	 * @return string `<style>...</style>` HTML, or '' when nothing to emit.
	 */
	protected static function build_icon_css_block( array $attrs, string $base_selector ): string {
		$btn_value = $attrs['button']['decoration']['button']['desktop']['value'] ?? [];
		$icon      = $btn_value['icon'] ?? [];
		$settings  = $icon['settings'] ?? [];
		if ( empty( $settings['unicode'] ) ) {
			return '';
		}

		$placement  = ( $icon['placement'] ?? 'right' ) === 'left' ? 'before' : 'after';
		$on_hover   = ( $icon['onHover']  ?? 'on' )  === 'on';
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

		// Override Divi's default rules:
		//   .et_pb_button:after  → opacity:0; position:absolute; transform:translateY(-50%)
		//   .et_pb_button:before → display:none
		// Force the active pseudo visible and the unused pseudo hidden.
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
}
