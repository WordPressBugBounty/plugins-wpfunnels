<?php
/**
 * Customer information (contact) section.
 *
 * WooCommerce keeps billing_email inside the billing address fieldset. Every WPFunnels
 * layout collects it up front as contact details instead: the modern layouts have always
 * rendered it in their own "Customer information" card, and the classic layouts render
 * this partial to match. form-billing.php drops billing_email from the billing fieldset,
 * so only one input with id="billing_email" ever reaches the page.
 *
 * This also takes the email out of reach of the "Use same address for billing" toggle, which
 * renders .wpfnl-billing-fields with display:none on any cart needing a shipping address.
 * The address block can be collapsed; the required email never is.
 *
 * Include this from a layout template as the first thing inside #customer_details, so it
 * stacks above the address cards in the customer column and is rebuilt with them by the
 * update_order_review fragment.
 *
 * @var WC_Checkout $checkout
 *
 * @since 3.14.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $checkout ) || ! $checkout instanceof WC_Checkout ) {
	return;
}

$wpfnl_contact_fields = $checkout->get_checkout_fields( 'billing' );

if ( ! isset( $wpfnl_contact_fields['billing_email'] ) ) {
	return;
}

$wpfnl_current_user  = wp_get_current_user();
$wpfnl_user_email    = $wpfnl_current_user ? $wpfnl_current_user->user_email : '';
$wpfnl_default_email = '';

// An opt-in step can hand the address over in the URL, and the optin cookie carries it
// across steps, so an address already captured earlier in the funnel is not asked for twice.
if ( isset( $_GET['billing_email'] ) && ! empty( $_GET['billing_email'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$wpfnl_default_email = sanitize_email( wp_unslash( $_GET['billing_email'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

if ( empty( $wpfnl_default_email ) ) {
	$wpfnl_cookie_name = 'wpfunnels_send_data_checkout';
	$wpfnl_cookie      = isset( $_COOKIE[ $wpfnl_cookie_name ] ) ? json_decode( wp_unslash( $_COOKIE[ $wpfnl_cookie_name ] ), true ) : array();

	if ( isset( $wpfnl_cookie['after_optin_submit_send_for_checkout']['email'] ) ) {
		$wpfnl_default_email = sanitize_email( $wpfnl_cookie['after_optin_submit_send_for_checkout']['email'] );
	}
}

if ( empty( $wpfnl_default_email ) ) {
	$wpfnl_default_email = $checkout->get_value( 'billing_email' );
}

if ( empty( $wpfnl_default_email ) ) {
	$wpfnl_default_email = $wpfnl_user_email;
}
?>

<div class="wpfnl-customer-info-section" id="wpfnl_customer_information">
	<div class="woocommerce-billing-fields-custom">
		<h3 id="customer_information_heading">
			<?php echo esc_html( apply_filters( 'wpfunnels_woo_customer_info_text', __( 'Customer information', 'wpfnl' ) ) ); ?>
		</h3>

		<div class="woocommerce-billing-fields__customer-info-wrapper">
			<?php
			/*
			 * Rendered as an editable field for guests and logged-in customers alike, the way
			 * the classic layouts have always shown it — only its position changes here. The
			 * modern layouts swap in a "Welcome Back" line and a hidden input once someone is
			 * logged in; matching that would take the order email away from customers who
			 * could edit it before.
			 *
			 * The field definition is passed through as-is: its label, placeholder and
			 * required flag are whatever WooCommerce or the Pro checkout field editor set,
			 * and only a missing width class is filled in.
			 */
			$wpfnl_email_field = $wpfnl_contact_fields['billing_email'];

			if ( empty( $wpfnl_email_field['class'] ) || ! is_array( $wpfnl_email_field['class'] ) ) {
				$wpfnl_email_field['class'] = array( 'form-row-wide' );
			}

			woocommerce_form_field( 'billing_email', $wpfnl_email_field, $wpfnl_default_email );

			/**
			 * Fires once the contact section has rendered billing_email, so integrations can
			 * append their own contact fields next to it. The modern layouts fire the same
			 * action from their customer information card.
			 *
			 * @since 3.13.2
			 */
			do_action( 'wpfunnels/modern_checkout_email_rendered' );
			?>
		</div>
	</div>
</div>
