<?php
/**
 * Modern One Column Checkout Form
 *
 * A modern, single-column checkout layout with collapsible sections.
 * All sections are stacked vertically in a clean, minimal design.
 * Sections: Contact → Billing → Shipping → Order Summary → Payment.
 *
 * @package WPFunnels\Checkout\Templates
 * @since 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$get_checkout_fields     = get_post_meta( get_the_ID(), 'wpfnl_checkout_additional_fields', true );
$is_order_comments_enable = isset( $get_checkout_fields['order_comments']['enable'] ) ? $get_checkout_fields['order_comments']['enable'] : true;

$loged_in_cls     = '';
$create_acc_field = '';
if ( ! is_user_logged_in() && 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
	$loged_in_cls = ' user-not-logedin ';
}

if ( ! is_user_logged_in() && 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) && $checkout->is_registration_required() ) {
	$create_acc_field = ' no-create-acc-field ';
}

$default_email      = '';
$lost_password_url  = esc_url( wp_lostpassword_url() );
$current_user       = wp_get_current_user();
$current_user_name  = $current_user ? $current_user->display_name : '';
$current_user_email = $current_user ? $current_user->user_email : '';
$is_allow_login     = 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' );
$required_mark      = '*';

$cookie_name = 'wpfunnels_send_data_checkout';
$cookie      = isset( $_COOKIE[ $cookie_name ] ) ? json_decode( wp_unslash( $_COOKIE[ $cookie_name ] ), true ) : array();

if ( isset( $_GET['billing_email'] ) && ! empty( $_GET['billing_email'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$default_email = sanitize_email( wp_unslash( $_GET['billing_email'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

if ( empty( $default_email ) && isset( $cookie['after_optin_submit_send_for_checkout']['email'] ) ) {
	$default_email = sanitize_email( $cookie['after_optin_submit_send_for_checkout']['email'] );
}

if ( empty( $default_email ) ) {
	$default_email = $checkout->get_value( 'billing_email' );
}

if ( empty( $default_email ) ) {
	$default_email = $current_user_email;
}
?>

<?php
// Keep WooCommerce pre-checkout hooks, but suppress returning-customer login prompt and default coupon form.
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
do_action( 'woocommerce_before_checkout_form', $checkout );
add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
?>

<?php
// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'wpfnl' ) ) );
	return;
}
?>


<div class="wpfnl-modern-one-column-wrapper wpfnl-modern-checkout-wrapper">

	<form name="checkout" method="post" class="checkout woocommerce-checkout <?php echo esc_attr( $loged_in_cls . ' ' . $create_acc_field ); ?>" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<?php if ( $checkout->get_checkout_fields() ) : ?>

			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<!-- Contact Information Section -->
			<div class="wpfnl-modern-section wpfnl-modern-section--contact" data-section="contact">
				<div class="wpfnl-modern-section__content">
					<div class="wpfnl-customer-info" id="customer_info">
						<div class="wpfnl-customer-info__notice"></div>
						<div class="woocommerce-billing-fields-custom">
							<h3 id="customer_information_heading">
								<?php echo esc_html( apply_filters( 'wpfunnels_woo_customer_info_text', __( 'Customer information', 'wpfnl' ) ) ); ?>
								<?php if ( ! is_user_logged_in() && $is_allow_login ) { ?>
									<div class="woocommerce-billing-fields__customer-login-label"><?php echo wp_kses_post( sprintf( __( 'Already have an account? %1$sLog in%2$s', 'wpfnl' ), '<a href="#!" class="wpfnl-customer-login-url">', '</a>' ) ); ?></div>
								<?php } ?>
							</h3>
							<div class="woocommerce-billing-fields__customer-info-wrapper">
								<?php
								$checkout_fields = $checkout->get_checkout_fields( 'billing' );
								if ( ! is_user_logged_in() && isset( $checkout_fields['billing_email'] ) ) {
									$billing_email_field = $checkout_fields['billing_email'];
									$billing_email_field['required'] = true;
									$billing_email_field['label'] = __( 'Email Address', 'wpfnl' );
									$billing_email_field['placeholder'] = sprintf( __( 'Email Address %s', 'wpfnl' ), $required_mark );
									$billing_email_field['autocomplete'] = 'email username';
									if ( empty( $billing_email_field['class'] ) || ! is_array( $billing_email_field['class'] ) ) {
										$billing_email_field['class'] = array( 'form-row-fill' );
									}
									woocommerce_form_field( 'billing_email', $billing_email_field, $default_email );
								}
								?>

								<?php if ( ! is_user_logged_in() && $is_allow_login ) { ?>
									<div class="wpfnl-customer-login-section">
										<?php
										woocommerce_form_field(
											'billing_password',
											array(
												'type'        => 'password',
												'class'       => array( 'form-row-fill', 'wpfnl-password-field' ),
												'required'    => true,
												'label'       => __( 'Password', 'wpfnl' ),
												'placeholder' => sprintf( __( 'Password %s', 'wpfnl' ), $required_mark ),
											)
										);
										?>
										<div class="wpfnl-customer-login-actions">
											<input type="button" name="wpfnl-customer-login-btn" class="button wpfnl-customer-login-section__login-button" id="wpfnl-customer-login-section__login-button" value="<?php echo esc_attr__( 'Login', 'wpfnl' ); ?>">
											<a href="<?php echo esc_url( $lost_password_url ); ?>" class="wpfnl-customer-login-lost-password-url"><?php esc_html_e( 'Lost your password?', 'wpfnl' ); ?></a>
										</div>
										<?php if ( 'yes' === get_option( 'woocommerce_enable_guest_checkout', false ) ) { ?>
											<p class="wpfnl-login-section-message"><?php esc_html_e( 'Login is optional, you can continue with your order below.', 'wpfnl' ); ?></p>
										<?php } ?>
									</div>
								<?php } ?>

								<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) { ?>
									<div class="wpfnl-create-account-section" hidden>
										<?php if ( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) ) { ?>
											<p class="form-row form-row-wide create-account">
												<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
													<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'wpfnl' ); ?></span>
												</label>
											</p>
										<?php } ?>
										<div class="create-account">
											<?php
											if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) {
												woocommerce_form_field(
													'account_username',
													array(
														'type'        => 'text',
														'class'       => array( 'form-row-fill' ),
														'id'          => 'account_username',
														'required'    => true,
														'label'       => __( 'Account username', 'wpfnl' ),
														'placeholder' => sprintf( __( 'Account username %s', 'wpfnl' ), $required_mark ),
													)
												);
											}
											if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
												woocommerce_form_field(
													'account_password',
													array(
														'type'        => 'password',
														'id'          => 'account_password',
														'class'       => array( 'form-row-fill' ),
														'required'    => true,
														'label'       => __( 'Create account password', 'wpfnl' ),
														'placeholder' => sprintf( __( 'Create account password %s', 'wpfnl' ), $required_mark ),
													)
												);
											}
											?>
										</div>
									</div>
								<?php } ?>

								<?php if ( is_user_logged_in() ) { ?>
									<div class="wpfnl-logged-in-customer-info">
										<?php echo esc_html( apply_filters( 'wpfunnels_logged_in_customer_info_text', sprintf( __( 'Welcome Back %1$s (%2$s)', 'wpfnl' ), $current_user_name, $current_user_email ) ) ); ?>
										<div><input type="hidden" class="wpfnl-email-address" id="billing_email" name="billing_email" value="<?php echo esc_attr( $current_user_email ); ?>"/></div>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>

					<?php do_action( 'wpfunnels/modern_checkout_contact_section' ); ?>
				</div>
			</div>

			<div class="col2-set" id="customer_details">
				<?php
				/**
				 * Fires before customer details in checkout page
				 * @since 2.8.21
				 */
				do_action( 'wpfunnels/before_customer_details' );
				?>

				<!-- Billing Section -->
				<div class="wpfnl-modern-section wpfnl-modern-section--billing" data-section="billing">
					<div class="wpfnl-modern-section__content">
						<div class="col-1" id="wpfnl_checkout_billing">
							<?php do_action( 'woocommerce_checkout_billing' ); ?>
						</div>
					</div>
				</div>

				<!-- Shipping Section -->
				<div class="wpfnl-modern-section wpfnl-modern-section--shipping" data-section="shipping">
					<div class="wpfnl-modern-section__content">
						<div class="col-2" id="wpfnl_checkout_shipping">
							<?php do_action( 'woocommerce_checkout_shipping' ); ?>
						</div>
					</div>
				</div>

				<?php
				/**
				 * Fires after customer details in checkout page
				 * @since 2.8.21
				 */
				do_action( 'wpfunnels/after_customer_details' );
				?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<?php endif; ?>

		<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

		<?php do_action( 'wpfunnels/woocommerce_checkout_before_order_heading' ); ?>

		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

		<!-- Order Summary Section -->
		<div class="wpfnl-modern-section wpfnl-modern-section--order-summary" data-section="order-summary">
			<?php do_action( 'wpfunnels/woocommerce_checkout_before_order_heading' ); ?>

			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

			<h3 id="order_review_heading"><?php echo wp_kses_post( apply_filters( 'wpfunnels_woo_order_text', esc_html__( 'Your order', 'woocommerce' ) ) ); ?></h3>

			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 ); ?>
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				<?php add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
		</div>

		<!-- Payment Section -->
		<div class="wpfnl-modern-section wpfnl-modern-section--payment" data-section="payment">
			<h3 class="wpfnl-modern-section__title"><?php esc_html_e( 'Payment', 'wpfnl' ); ?></h3>
			<div class="wpfnl-modern-section__content">
				<?php wc_get_template( 'checkout/payment.php', array( 'checkout' => $checkout ) ); ?>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

	</form>

</div>
<!-- /.wpfnl-modern-one-column-wrapper -->

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
