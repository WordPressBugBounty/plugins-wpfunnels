<?php
$checkout_layout = '';
if( class_exists( '\WPFunnels\Wpfnl_functions' ) && \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && isset( $_SESSION[ 'checkout_layout' ] ) && 'wpfnl-express-checkout' === $_SESSION[ 'checkout_layout' ] ) {
	$checkout_layout = $_SESSION[ 'checkout_layout' ];
	unset( $_SESSION[ 'checkout_layout' ] );
	// load when funnel checkout step is express checkout
	require_once WPFNL_DIR . '/public/modules/checkout/templates/express-checkout-form.php';



} else if( class_exists( '\WPFunnels\Wpfnl_functions' ) && \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && isset( $_SESSION[ 'checkout_layout' ] ) && 'wpfnl-2-step' === $_SESSION[ 'checkout_layout' ] ) {
	$checkout_layout = $_SESSION[ 'checkout_layout' ];
	unset( $_SESSION[ 'checkout_layout' ] );

	require_once WPFNL_DIR . '/public/modules/checkout/templates/two-step-checkout.php';



} else {
	require_once WPFNL_DIR . '/public/modules/checkout/templates/default-checkout-form.php';
}
// session_destroy();
?>
