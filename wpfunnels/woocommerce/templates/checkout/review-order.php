<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

$is_express_checkout = true;

defined( 'ABSPATH' ) || exit;
do_action( 'wpfunnel_review_order_before_cart_contents' );
?>
<table class="shop_table woocommerce-checkout-review-order-table">
	<thead>
		<tr>
			<th class="product-name">
				<?php esc_html_e( 'Product', 'woocommerce' ); ?>
				<span class="subtotal-text-for-mobile"><?php esc_html_e('with subtotal', 'woocommerce') ?></span>
			</th>
			<th class="product-total"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			
			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<td class="product-name">
						<?php echo apply_filters( 'woocommerce_cart_item_name',$_product->get_type() == 'variation' ? \WPFunnels\Wpfnl_functions::get_formated_product_name($_product) : $_product->get_name(), $cart_item, $cart_item_key ). '&nbsp;'; ?>
						<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
					<td class="product-total">
						<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</tbody>
	<tfoot>
		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php

		if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) { ?>

			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

			<?php

			$checkout_layout = '';
			if( isset( $_SESSION[ 'checkout_layout' ] ) ) {
				$checkout_layout = $_SESSION[ 'checkout_layout' ];
				unset( $_SESSION[ 'checkout_layout' ] );
				if( \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-express-checkout' === $checkout_layout ) {
					$shipping_method = WC()->session->get( 'chosen_shipping_methods' );
					$shipping_method = isset( $shipping_method[0] ) ? $shipping_method[0] : '';
					$shipping_label = $shipping_method && isset( $package[ 'rates' ][ $shipping_method ] ) ? $package[ 'rates' ][ $shipping_method ] : '';
					$shipping_label = $shipping_label ? $shipping_label->get_label() : '';
					$currency   = get_woocommerce_currency_symbol();
					$shipping_cost = '';

					foreach ( WC()->shipping->get_packages() as $key => $package ) {
						$cost = $shipping_method && $package[ 'rates' ][ $shipping_method ] ? $package[ 'rates' ][ $shipping_method ]->get_cost() : '';
						$cost = $cost ? $currency . $cost : '';
						$shipping_cost = $shipping_label . $cost;
					}
					?>
					<tr>
						<th><?php esc_html_e( 'Shipping', 'wpfnl' ); ?></th>
						<td class="wpfnl-express-shipping-method"><?php echo $shipping_cost ?></td>
					</tr>
					<?php
				}
				else {
					wc_cart_totals_shipping_html();
				}
			}
			else {
				$checkout_id = get_the_ID();
				if( !$checkout_id || 'wpfunnel_steps' !== get_post_type( $checkout_id ) ) {
					$data        = \WPFunnels\Wpfnl_functions::get_sanitized_get_post();
					$checkout_id = isset( $data[ 'post' ][ 'step_id' ] ) ? $data[ 'post' ][ 'step_id' ] : 0;
				}
				if( \WPFunnels\Wpfnl_functions::maybe_express_checkout( $checkout_id ) && \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() ) {
					$shipping_method = WC()->session->get( 'chosen_shipping_methods' );
					$shipping_method = isset( $shipping_method[0] ) ? $shipping_method[0] : '';
					$shipping_label = $shipping_method && isset( $package[ 'rates' ][ $shipping_method ] ) ? $package[ 'rates' ][ $shipping_method ] : '';
					$shipping_label = $shipping_label ? $shipping_label->get_label() : '';
					$currency   = get_woocommerce_currency_symbol();
					$shipping_cost = '';

					foreach ( WC()->shipping->get_packages() as $key => $package ) {
						$cost = $shipping_method && $package[ 'rates' ][ $shipping_method ] ? $package[ 'rates' ][ $shipping_method ]->get_cost() : '';
						$cost = $cost ? $currency . $cost : '';
						$shipping_cost = $shipping_label . $cost;
					}
					?>
					<tr>
						<th><?php esc_html_e( 'Shipping', 'wpfnl' ); ?></th>
						<td class="wpfnl-express-shipping-method"><?php echo $shipping_cost ?></td>
					</tr>
					<?php
				}
				else {
					wc_cart_totals_shipping_html();
				}
			}
			?>

			<?php //wc_cart_totals_shipping_html(); ?>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

			<?php
		}
		?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	</tfoot>
</table>
<?php 
	/**
	 * Fires after order total in checkout page
	 * @since 2.8.21
	 */
	do_action( 'wpfunnels/after_order_total' ); 
?>