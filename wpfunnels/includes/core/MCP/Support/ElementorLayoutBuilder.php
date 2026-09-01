<?php
/**
 * ElementorLayoutBuilder — assembles Elementor `_elementor_data` node trees.
 *
 * Pure array assembly, no WordPress I/O. Every public factory returns a node
 * shaped the way Elementor's editor expects it, so the caller only has to
 * write the result to `_elementor_data` (see PageContentTools::writeElementorTree()).
 *
 * @package WPFunnels\MCP\Support
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Class ElementorLayoutBuilder
 */
class ElementorLayoutBuilder {

	/**
	 * Ids already handed out in the current build, to guarantee uniqueness.
	 *
	 * @var array
	 */
	private static $used_ids = [];

	/**
	 * Reset id tracking. Call once at the start of building a tree.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$used_ids = [];
	}

	/**
	 * Unique 7-char id, Elementor's own node id format.
	 *
	 * @return string
	 */
	public static function newId() {
		do {
			$id = substr( bin2hex( random_bytes( 4 ) ), 0, 7 );
		} while ( isset( self::$used_ids[ $id ] ) );

		self::$used_ids[ $id ] = true;

		return $id;
	}

	/**
	 * A section node.
	 *
	 * @param array $columns  Column nodes.
	 * @param array $settings Section settings.
	 * @return array
	 */
	public static function section( $columns, $settings = [] ) {
		return [
			'id'       => self::newId(),
			'elType'   => 'section',
			'settings' => $settings,
			'elements' => $columns,
			'isInner'  => false,
		];
	}

	/**
	 * A column node, full-width by default.
	 *
	 * @param array $widgets  Widget nodes.
	 * @param array $settings Column settings.
	 * @return array
	 */
	public static function column( $widgets, $settings = [] ) {
		return [
			'id'       => self::newId(),
			'elType'   => 'column',
			'settings' => array_merge( [ '_column_size' => 100 ], $settings ),
			'elements' => $widgets,
		];
	}

	/**
	 * A widget node.
	 *
	 * @param string $widget_type Elementor widget slug (its `get_name()`).
	 * @param array  $settings    Widget settings.
	 * @return array
	 */
	public static function widget( $widget_type, $settings = [] ) {
		return [
			'id'         => self::newId(),
			'elType'     => 'widget',
			'widgetType' => $widget_type,
			'settings'   => $settings,
			'elements'   => [],
		];
	}

	/**
	 * Convenience: wrap a single widget in its own full-width section/column.
	 *
	 * @param array $widget Widget node.
	 * @return array Section node.
	 */
	public static function sectionFor( $widget ) {
		return self::section( [ self::column( [ $widget ] ) ] );
	}

	/**
	 * Core `heading` widget.
	 *
	 * @param string $text Heading text.
	 * @param string $size Header tag (h1-h6).
	 * @return array
	 */
	public static function headingWidget( $text, $size = 'h2' ) {
		return self::widget(
			'heading',
			[
				'title'       => $text,
				'header_size' => $size,
			]
		);
	}

	/**
	 * Core `text-editor` widget. Accepts HTML.
	 *
	 * @param string $html Editor content.
	 * @return array
	 */
	public static function textWidget( $html ) {
		return self::widget( 'text-editor', [ 'editor' => $html ] );
	}

	/**
	 * `<ul>` rendering of a benefits list, as a text-editor widget.
	 *
	 * @param array $benefits Benefit strings.
	 * @return array|null Null when there is nothing to render.
	 */
	public static function benefitsWidget( $benefits ) {
		if ( empty( $benefits ) ) {
			return null;
		}

		$items = implode(
			'',
			array_map(
				static function ( $benefit ) {
					return '<li>' . esc_html( $benefit ) . '</li>';
				},
				$benefits
			)
		);

		return self::textWidget( '<ul>' . $items . '</ul>' );
	}

	/**
	 * `wpfnl-optin-form` widget.
	 *
	 * @param array $args Optional overrides: cta_text, collect_name, primary_color.
	 * @return array
	 */
	public static function optinWidget( $args = [] ) {
		$settings = [
			'btn_text'            => isset( $args['cta_text'] ) ? $args['cta_text'] : 'Get Started',
			'first_name'          => empty( $args['collect_name'] ) ? '' : 'yes',
			'first_name_label'    => 'First Name',
			'email_label'         => 'Email',
			'acceptance_checkbox' => '',
		];

		if ( ! empty( $args['primary_color'] ) ) {
			$settings['btn_bg_color'] = $args['primary_color'];
		}

		return self::widget( 'wpfnl-optin-form', $settings );
	}

	/**
	 * `wpfnl-checkout` widget.
	 *
	 * @param array $args Optional overrides: cta_text, subheadline, layout, primary_color.
	 * @return array
	 */
	public static function checkoutWidget( $args = [] ) {
		$settings = [
			'checkout_layout'      => isset( $args['layout'] ) ? $args['layout'] : 'wpfnl-col-2',
			'place_order_btn_text' => isset( $args['cta_text'] ) ? $args['cta_text'] : 'Place Order',
			'place_order_sub_text' => isset( $args['subheadline'] ) ? $args['subheadline'] : '',
		];

		if ( ! empty( $args['primary_color'] ) ) {
			$settings['order_button_bg_color'] = $args['primary_color'];
		}

		return self::widget( 'wpfnl-checkout', $settings );
	}

	/**
	 * `wpfnl-offer` widget (one instance — call twice for accept + reject).
	 *
	 * @param array $args offer_type (accept|reject), text, subtitle, primary_color.
	 * @return array
	 */
	public static function offerWidget( $args ) {
		$settings = [
			'offer_button_type' => isset( $args['offer_button_type'] ) ? $args['offer_button_type'] : 'upsell',
			'offer_type'        => isset( $args['offer_type'] ) ? $args['offer_type'] : 'accept',
			'text'              => isset( $args['text'] ) ? $args['text'] : 'Accept Offer',
		];

		if ( ! empty( $args['subtitle'] ) ) {
			$settings['enable_subtitle'] = 'yes';
			$settings['subtitle_text']   = $args['subtitle'];
		}

		if ( ! empty( $args['primary_color'] ) ) {
			$settings['background_color'] = $args['primary_color'];
		}

		return self::widget( 'wpfnl-offer', $settings );
	}

	/**
	 * `wpfnl-next-step` widget.
	 *
	 * @param array $args cta_text, button_type_selector, primary_color.
	 * @return array
	 */
	public static function nextStepWidget( $args = [] ) {
		$settings = [
			'button_type_selector' => isset( $args['button_type_selector'] ) ? $args['button_type_selector'] : 'checkout',
			'text'                 => isset( $args['cta_text'] ) ? $args['cta_text'] : 'Go Next',
		];

		if ( ! empty( $args['primary_color'] ) ) {
			$settings['background_color'] = $args['primary_color'];
		}

		return self::widget( 'wpfnl-next-step', $settings );
	}

	/**
	 * `wpfnl-order-bump` widget. Cosmetic defaults only — the settings
	 * `wpfunnels/upsert-order-bump` writes to `order-bump-settings` postmeta
	 * are the source of truth and resync this widget's fields separately.
	 *
	 * @param array $args layout, checkbox_label, header, detail, product_id.
	 * @return array
	 */
	public static function orderBumpWidget( $args = [] ) {
		return self::widget(
			'wpfnl-order-bump',
			[
				'order_bump_layout'               => isset( $args['layout'] ) ? $args['layout'] : 'horizontal',
				'order_bump_product_selector'     => isset( $args['product_id'] ) ? $args['product_id'] : '',
				'order_bump_checkbox_label'        => isset( $args['checkbox_label'] ) ? $args['checkbox_label'] : 'Try this product',
				'order_bump_product_detail_header' => isset( $args['header'] ) ? $args['header'] : 'Amazing Product',
				'order_bump_product_detail'        => isset( $args['detail'] ) ? $args['detail'] : 'Product description',
			]
		);
	}
}
