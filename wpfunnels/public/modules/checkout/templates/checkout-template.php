<?php
// Exit if accessed directly.
use WPFunnels\Wpfnl;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checkout_layout = Wpfnl::get_instance()->meta->get_checkout_meta_value( $checkout_id, 'wpfnl_checkout_layout', 'wpfnl-col-2' );

$floating_label = Wpfnl::get_instance()->meta->get_checkout_meta_value( $checkout_id, 'wpfnl_floating_label', 'wpfnl-col-2' );

if( PHP_SESSION_DISABLED == session_status() ) {
	session_start();
}
$_SESSION[ 'checkout_layout' ] = $checkout_layout;

if( \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-express-checkout' === $checkout_layout ) {
	$checkout_layout .= ' wpfnl-multistep';
}

if( \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-2-step' === $checkout_layout ) {
	$checkout_layout .= ' wpfnl-multistep';
}

// Add shared modern checkout class for both modern layouts
if( 'wpfnl-modern-one-column' === $checkout_layout || 'wpfnl-modern-checkout' === $checkout_layout ) {
	if( false === strpos( $checkout_layout, 'wpfnl-modern-checkout' ) ) {
		$checkout_layout .= ' wpfnl-modern-checkout';
	}
}

if( 'wpfnl-modern-multistep' === $checkout_layout ) {
	$checkout_layout .= ' wpfnl-modern-checkout';
}

?>

<div id="wpfnl-checkout-form" class="wpfnl-checkout  <?php echo esc_attr( $checkout_layout ); ?>  <?php echo esc_attr( $floating_label ); ?> ">
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
