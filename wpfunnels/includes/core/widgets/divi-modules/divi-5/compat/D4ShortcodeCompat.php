<?php
/**
 * Divi 4 → Divi 5 frontend shortcode compatibility.
 *
 * When Divi 5 is active, WPFunnels only loads the new Divi 5 block modules
 * (DiviModulesV5). Funnel templates that were built/exported with Divi 4 store
 * their layout as legacy shortcodes (e.g. `[wpfnl_offer_button]`,
 * `[wpfnl_checkout]`). Divi 5 still runs `do_shortcode()` on that legacy
 * content on the frontend, but because the old `ET_Builder_Module` shortcodes
 * are no longer registered, WordPress prints the raw `[wpfnl_*]` tag as plain
 * text (see the imported upsell template showing `[wpfnl_offer_button ...]`).
 *
 * This shim registers lightweight frontend handlers for those orphaned tags so
 * imported Divi 4 templates render correctly under Divi 5 — without rewriting
 * the stored content. The handlers reuse the existing canonical WPFunnels
 * shortcodes (which already power the Gutenberg/Elementor renders) and the
 * shared LMS render helpers, so there is a single source of truth.
 *
 * Once a page is opened in the Divi 5 Visual Builder it is migrated to native
 * D5 blocks via the `divi.moduleLibrary.conversion.moduleConversionOutline`
 * map, after which these tags are no longer present — so this shim only ever
 * affects not-yet-migrated legacy content.
 *
 * @package WPFunnels\Widgets\DiviModules\D5\Compat
 * @since   3.12.7
 */

namespace WPFunnels\Widgets\DiviModules\D5\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

final class D4ShortcodeCompat {

	/**
	 * Map of legacy Divi 4 shortcode tags → handler method on this class.
	 *
	 * @var array<string,string>
	 */
	private const TAG_HANDLERS = array(
		'wpfnl_checkout'          => 'render_checkout',
		'wpfnl_optin'             => 'render_optin',
		'wpfnl_order_details'     => 'render_order_details',
		'wpfnl_next_step_button'  => 'render_next_step_button',
		'wpfnl_offer_button'      => 'render_offer_button',
		'wpfnl_lms_checkout'      => 'render_lms_checkout',
		'wpfnl_lms_order_details' => 'render_lms_order_details',
	);

	/**
	 * Register the frontend shortcode handlers.
	 *
	 * Only runs on the frontend (never in admin or during a Visual Builder
	 * AJAX render, where the live D5 modules handle preview output).
	 */
	public static function register(): void {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		foreach ( self::TAG_HANDLERS as $tag => $method ) {
			// Do not clobber a handler that is somehow already registered.
			if ( shortcode_exists( $tag ) ) {
				continue;
			}
			add_shortcode( $tag, array( __CLASS__, $method ) );
		}
	}

	/**
	 * Checkout module → canonical `[wpf_checkout]` shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_checkout( $atts ): string {
		return do_shortcode( '[wpf_checkout]' );
	}

	/**
	 * Opt-in module → canonical `[wpf_optin_form]` shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_optin( $atts ): string {
		return do_shortcode( '[wpf_optin_form]' );
	}

	/**
	 * Order details module → canonical `[wpfunnels_order_details]` shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_order_details( $atts ): string {
		return do_shortcode( '[wpfunnels_order_details]' );
	}

	/**
	 * Next step button module → canonical `[wpf_next_step_button]` shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_next_step_button( $atts ): string {
		$atts      = is_array( $atts ) ? $atts : array();
		$btn_text  = isset( $atts['button_text'] ) ? $atts['button_text'] : '';
		$align     = isset( $atts['button_alignment'] ) ? $atts['button_alignment'] : '';

		$mapped = array();
		if ( '' !== $btn_text ) {
			$mapped[] = 'btn_text="' . esc_attr( $btn_text ) . '"';
		}
		if ( '' !== $align ) {
			$mapped[] = 'align="' . esc_attr( $align ) . '"';
		}

		return do_shortcode( '[wpf_next_step_button ' . implode( ' ', $mapped ) . ']' );
	}

	/**
	 * Offer button module render (upsell/downsell).
	 *
	 * The Divi 4 module's frontend output is self-contained plain HTML, so it
	 * is reproduced here rather than instantiating the legacy ET_Builder_Module.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_offer_button( $atts ): string {
		$atts = shortcode_atts(
			array(
				'offer_action'       => 'accept',
				'button_text'        => __( 'Yes, Add to My Order!', 'wpfnl' ),
				'show_product_price' => 'off',
				'button_alignment'   => 'left',
				'offer_type'         => 'upsell',
			),
			is_array( $atts ) ? $atts : array()
		);

		$offer_action      = $atts['offer_action'];
		$button_text       = $atts['button_text'];
		$show_price        = $atts['show_product_price'];
		$button_align      = $atts['button_alignment'];
		$offer_button_type = $atts['offer_type'];

		$step_id          = get_the_ID();
		$get_product_type = '';
		try {
			$response         = \WPFunnels\Wpfnl_functions::get_product_data_for_widget( $step_id );
			$get_product_type = isset( $response['get_product_type'] ) && $response['get_product_type'] ? $response['get_product_type'] : '';
		} catch ( \Throwable $e ) {
			$get_product_type = '';
		}

		$is_variable = ( 'variable' === $get_product_type || 'variable-subscription' === $get_product_type );

		ob_start();
		?>
		<div class="wp-block-wpfnl-offer-btn-<?php echo esc_attr( $button_align ); ?>">
			<div class="wpfnl-offerbtn-wrapper" id="wpfnl-offerbtn-wrapper">
				<?php
				if ( $is_variable && 'accept' === $offer_action ) {
					echo '<div class="has-variation-product">';
					echo '<div class="wpfnl-product-variation">';
					if ( 'on' === $show_price ) {
						echo '<span class="offer-btn-loader"></span>';
					}
					echo do_shortcode( '[wpf_variable_offer post_id="' . esc_attr( $step_id ) . '"]' );
					echo '</div>';
				}
				?>

				<div class="wpfnl-offerbtn-and-price-wrapper">
					<?php if ( 'on' === $show_price && 'accept' === $offer_action ) : ?>
						<span class="wpfnl-offer-product-price" id="wpfnl-offer-product-price">
							<?php
							if ( ! $is_variable && 'accept' === $offer_action ) {
								$step_type = get_post_meta( $step_id, '_step_type', true );
								$products  = get_post_meta( $step_id, '_wpfnl_' . $step_type . '_products', true );

								if ( ! empty( $products ) && is_array( $products ) ) {
									$product_id = isset( $products[0]['id'] ) ? $products[0]['id'] : 0;
									if ( $product_id ) {
										$product = wc_get_product( $product_id );
										if ( $product ) {
											echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
									}
								}
							}
							?>
						</span>
					<?php endif; ?>

					<a href="#"
						class="wpfunnels-divi-module wpfunnels_offer_button"
						id="wpfunnels_<?php echo esc_attr( $offer_button_type ); ?>_<?php echo esc_attr( $offer_action ); ?>"
						data-offertype="<?php echo esc_attr( $offer_button_type ); ?>">
						<?php echo esc_html( $button_text ); ?>
					</a>
				</div>

				<?php
				if ( $is_variable && 'accept' === $offer_action ) {
					echo '</div>'; // end .has-variation-product
				}
				?>
			</div>
		</div>
		<?php

		if ( 'accept' === $offer_action && \WPFunnels\Wpfnl_functions::is_wc_active() ) {
			do_action( 'wpfunnels/after_offer_button' );
		}

		return ob_get_clean();
	}

	/**
	 * LMS checkout module → shared static render helper from the D4 module.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_lms_checkout( $atts ): string {
		$class = '\WPFunnels\Widgets\DiviModules\Modules\WPFNL_Lms_Checkout';
		if ( ! self::load_legacy_module_class( $class, 'LmsCheckout/class-lms-checkout.php' )
			|| ! method_exists( $class, 'get_lms_checkout_form' ) ) {
			return '';
		}

		$props = shortcode_atts(
			array(
				'lms_order_header_title'         => __( 'Order Details', 'wpfnl' ),
				'lms_order_course_details_title' => __( 'Course Details', 'wpfnl' ),
				'lms_order_course_description'   => '',
				'lms_order_plan_details_title'   => __( 'Plan Details', 'wpfnl' ),
			),
			is_array( $atts ) ? $atts : array()
		);

		return (string) call_user_func( array( $class, 'get_lms_checkout_form' ), $props );
	}

	/**
	 * LMS order details module → shared static render helper from the D4 module.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_lms_order_details( $atts ): string {
		$class = '\WPFunnels\Widgets\DiviModules\Modules\WPFNL_Lms_Order_details';
		if ( ! self::load_legacy_module_class( $class, 'LmsOrderDetails/class-lms-order-details.php' )
			|| ! method_exists( $class, 'get_lms_order_details' ) ) {
			return '';
		}

		$props = shortcode_atts(
			array(
				'lms_order_header_title'         => __( 'Order Details', 'wpfnl' ),
				'lms_order_course_details_title' => __( 'Course Details', 'wpfnl' ),
				'lms_order_course_description'   => '',
				'lms_order_plan_details_title'   => __( 'Plan Details', 'wpfnl' ),
			),
			is_array( $atts ) ? $atts : array()
		);

		return (string) call_user_func( array( $class, 'get_lms_order_details' ), $props );
	}

	/**
	 * Lazily load a legacy Divi 4 module class file.
	 *
	 * The LMS module classes extend `ET_Builder_Module`, so the file can only be
	 * required when Divi's legacy builder framework is available (which it is
	 * while Divi 5 renders not-yet-migrated D4 content on the frontend).
	 *
	 * @param string $class    Fully-qualified class name.
	 * @param string $rel_path Path to the class file relative to includes/modules/.
	 * @return bool Whether the class is available after loading.
	 */
	private static function load_legacy_module_class( string $class, string $rel_path ): bool {
		if ( class_exists( $class ) ) {
			return true;
		}
		if ( ! class_exists( '\ET_Builder_Module' ) ) {
			return false;
		}

		$file = dirname( __DIR__, 2 ) . '/includes/modules/' . $rel_path;
		if ( file_exists( $file ) ) {
			require_once $file;
		}

		return class_exists( $class );
	}
}
