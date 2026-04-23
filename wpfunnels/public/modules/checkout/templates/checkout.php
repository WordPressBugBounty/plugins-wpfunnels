<?php
/**
 * Checkout template
 * 
 * @package
 */
?>
<div id="wpfunnels-checkout-form" class="wpfunnels-checkout-form">
	<?php
	if ( WC()->cart && WC()->cart->is_empty() ) {
		// WC shortcode redirects to cart when empty — bypass it and render the
		// checkout form template directly so the design is visible without products.
		// Set a session flag so the woocommerce_checkout_update_order_review_expired
		// filter can suppress the "session expired" AJAX response for this session.
		wc_clear_notices();
		WC()->session->set( 'wpfnl_checkout_preview_mode', true );
		ob_start();
		wc_get_template( 'checkout/form-checkout.php', array( 'checkout' => WC()->checkout() ) );
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		$checkout_html = do_shortcode( '[woocommerce_checkout]' );
		if ( empty( $checkout_html ) || trim( $checkout_html ) === '<div class="woocommerce"></div>' ) {
			echo esc_html__( 'Your cart is currently empty.', 'wpfnl' );
		} else {
			echo $checkout_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	?>
</div>
