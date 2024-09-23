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
?>
<div id="wpfnl-checkout-form" class="wpfnl-checkout  <?php echo esc_attr( $checkout_layout ); ?>  <?php echo esc_attr( $floating_label ); ?> ">
	<?php
	$checkout_html = do_shortcode( '[woocommerce_checkout]' );

	if (empty( $checkout_html ) || trim( $checkout_html ) == '<div class="woocommerce"></div>') {
		echo esc_html__( 'Your cart is currently empty.', 'wpfnl' );
	} else {
		echo $checkout_html;
	}
	?>
</div>
