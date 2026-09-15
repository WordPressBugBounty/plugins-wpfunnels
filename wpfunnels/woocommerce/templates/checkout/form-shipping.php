<?php
/**
 * Checkout shipping information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global  WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
$hide_section_settings = \WPFunnels\Wpfnl_functions::get_checkout_section_heading_settings( 'shipping' , get_the_ID() );
?>
<div class="wpfnl-shipping-fields woocommerce-shipping-fields">
	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>

		<?php if( !$hide_section_settings || ( isset($hide_section_settings['custom_heading']) && $hide_section_settings['custom_heading'] != '' )  ) { ?>
			<h3>
				<?php
					if( isset($hide_section_settings['custom_heading']) && $hide_section_settings['custom_heading'] != '' ){
						echo esc_html( $hide_section_settings['custom_heading'] );
					}else{
						esc_html_e( 'Shipping details', 'woocommerce' );
					}
				?>
			</h3>
		<?php } ?>

		<div class="shipping_address">

			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

			<div class="woocommerce-shipping-fields__field-wrapper">
				<?php
				$fields = $checkout->get_checkout_fields( 'shipping' );

				foreach ( $fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>

			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>

		</div>

	<?php endif; ?>
</div>
<?php
/*
 * The additional information (order notes) block is intentionally NOT rendered
 * here. This template runs during `woocommerce_checkout_shipping`, which the
 * checkout layouts fire before `woocommerce_checkout_billing` — so keeping the
 * block here pushed the order notes above the "Use same address for billing"
 * toggle, and skipped the block entirely on carts that need no shipping address.
 * Module::maybe_render_additional_fields() renders it on
 * `woocommerce_checkout_after_customer_details` instead, which is after both
 * address forms and fires for every cart.
 */
