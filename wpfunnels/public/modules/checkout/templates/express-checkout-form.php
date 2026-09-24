<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$hide_section_settings = \WPFunnels\Wpfnl_functions::get_checkout_section_heading_settings( 'order' , get_the_ID() );
$shipping_active_cls = '';
$billing_active_cls  = '';

if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
	if ( WC()->cart && WC()->cart->needs_shipping_address() ) {
		$shipping_active_cls = 'current';
	} else {
		$billing_active_cls  = 'current';
	}
}

$get_checkout_fields = get_post_meta(get_the_ID(),'wpfnl_checkout_additional_fields',true);
$is_order_comments_enable = isset($get_checkout_fields['order_comments']['enable']) ? $get_checkout_fields['order_comments']['enable'] : true;
?>


<?php

$loged_in_cls = '';
$create_acc_field = '';
if( ! is_user_logged_in() && 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ){
	$loged_in_cls = ' user-not-logedin ';
}

if( ! is_user_logged_in() && 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) && $checkout->is_registration_required() ){
	$create_acc_field = ' no-create-acc-field ';
}
?>


<div class="wpfnl-express-checkout-wrapper">
	<div class="wpfnl-express-checkout-left">
		<ul class="wpfnl-multistep-wizard">
			<?php  if( ! is_user_logged_in() && 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ){ ?>
				<li class="login current">
					<button type="button" data-target="login">
						<span class="step-icon">
							<?php include WPFNL_DIR.'public/assets/icons/icon-login.php'; ?>
						</span>
						<span class="step-title">
							<?php  esc_html_e( 'Login', 'wpfnl' ); ?>
						</span>
					</button>
				</li>
			<?php } ?>

			<?php if( is_user_logged_in() ){ ?>
				<?php if( WC()->cart && WC()->cart->needs_shipping_address() ) { ?>
					<li class="shipping <?php echo $shipping_active_cls; ?>">
						<button type="button" data-target="shipping">
							<span class="step-icon">
								<?php include WPFNL_DIR.'public/assets/icons/icon-shipping.php'; ?>
							</span>
							<span class="step-title">
								<?php esc_html_e( 'Shipping', 'wpfnl' ); ?>
							</span>
						</button>
					</li>
				<?php } ?>

				<li class="billing <?php echo $billing_active_cls; ?>">
					<button type="button" data-target="billing">
						<span class="step-icon">
							<?php include WPFNL_DIR.'public/assets/icons/icon-billing.php'; ?>
						</span>
						<span class="step-title">
							<?php esc_html_e( 'Billing', 'wpfnl' );  ?>
						</span>
					</button>
				</li>

				<li class="order-review">
					<button type="button" data-target="order-review">
						<span class="step-icon">
							<?php include WPFNL_DIR.'public/assets/icons/icon-order-review.php'; ?>
						</span>
						<span class="step-title">
							<?php esc_html_e( 'Payment', 'wpfnl' ); ?>
						</span>
					</button>
				</li>
			<?php }else{

				if( ! $checkout->is_registration_required() || 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) || $checkout->is_registration_enabled() ){
					?>
					<?php if( WC()->cart && WC()->cart->needs_shipping_address() ) { ?>
						<li class="shipping <?php echo $shipping_active_cls; ?>">
							<button type="button" data-target="shipping">
								<span class="step-icon">
									<?php include WPFNL_DIR.'public/assets/icons/icon-shipping.php'; ?>
								</span>
								<span class="step-title">
									<?php esc_html_e( 'Shipping', 'wpfnl' ); ?>
								</span>
							</button>
						</li>
					<?php } ?>

					<li class="billing <?php echo $billing_active_cls; ?>">
						<button type="button" data-target="billing">
							<span class="step-icon">
								<?php include WPFNL_DIR.'public/assets/icons/icon-billing.php'; ?>
							</span>
							<span class="step-title">
								<?php esc_html_e( 'Billing', 'wpfnl' );  ?>
							</span>
						</button>
					</li>

					<li class="order-review">
						<button type="button" data-target="order-review">
							<span class="step-icon">
								<?php include WPFNL_DIR.'public/assets/icons/icon-order-review.php'; ?>
							</span>
							<span class="step-title">
								<?php esc_html_e( 'Your order', 'wpfnl' ); ?>
							</span>
						</button>
					</li>
					<?php
				}
			}
			?>
		</ul>

		<?php do_action( 'woocommerce_before_checkout_form', $checkout ); ?>

		<?php
		if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
			echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'wpfnl' ) ) );

			echo "</div>";
		}else{
		?>

		<form name="checkout" method="post" class="checkout woocommerce-checkout <?php echo $loged_in_cls.' '.$create_acc_field; ?>" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="wpfnl_step_id" value="<?php echo esc_attr( get_the_ID() ); ?>" />
			<?php if ( $checkout->get_checkout_fields() ) : ?>

				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<div class="col2-set" id="customer_details">
					<?php
						/**
						 * Fires before customer details in checkout page
						 * @since 2.8.21
						 */
						do_action( 'wpfunnels/before_customer_details' );
					?>

					<?php
					/*
					 * Contact details above the address cards, the way WooCommerce's own checkout and
					 * the modern layouts present them. Inside #customer_details so it stacks in the
					 * customer column rather than spanning the full form width, and so the
					 * update_order_review fragment rebuilds it along with the address cards.
					 */
					include WPFNL_DIR . 'public/modules/checkout/templates/parts/customer-information.php';
					?>


					<?php if ( WC()->cart && WC()->cart->needs_shipping_address() ) : ?>
						<div class="col-1" id="wpfnl_checkout_shipping">
							<?php do_action( 'woocommerce_checkout_shipping' ); ?>

							<div class="express-shipping-table">
								<table>
									<thead>
										<?php
											if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) { ?>
												<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

												<?php wc_cart_totals_shipping_html(); ?>

												<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

											<?php
											}
										?>
									</thead>
								</table>
							</div>
							<!-- /.express-shipping-table -->
						</div>

						<div class="col-2" id="wpfnl_checkout_billing">
							<?php do_action( 'woocommerce_checkout_billing' ); ?>
						</div>
					<?php else : ?>
						<div class="col-1" id="wpfnl_checkout_billing">
							<?php do_action( 'woocommerce_checkout_billing' ); ?>
						</div>
					<?php endif; ?>

					<!-- </div> -->
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

			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php
				// The order summary (coupon field, order bumps, testimonials) already renders
				// once in the always-visible .wpfnl-express-checkout-right column below —
				// suppress it here so .wpfnl-after-order-total isn't duplicated on the page.
				$GLOBALS['wpfnl_suppress_after_order_total'] = true;
				do_action( 'woocommerce_checkout_order_review' );
				$GLOBALS['wpfnl_suppress_after_order_total'] = false;
				?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

			<!-- multistep navigation -->
			<div class="wpfnl-multistep-navigation">
				<button type="button" class="previous btn-default" data-target="" disabled>
					<?php echo __( 'Previous', 'wpfnl' ); ?>
				</button>
				<?php if( !is_user_logged_in() && 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ){ ?>
					<?php if ( WC()->cart && WC()->cart->needs_shipping_address() ) { ?>
						<button type="button" class="next btn-default" data-target="shipping"><?php echo __( 'Next', 'wpfnl' ); ?></button>
					<?php } else { ?>
						<button type="button" class="next btn-default" data-target="billing"><?php echo __( 'Next', 'wpfnl' ); ?></button>
					<?php } ?>
				<?php }else{ ?>
					<?php if ( WC()->cart && WC()->cart->needs_shipping_address() ) { ?>
						<button type="button" class="next btn-default" data-target="billing"><?php echo __( 'Next', 'wpfnl' ); ?></button>
					<?php } else { ?>
						<button type="button" class="next btn-default" data-target="order-review"><?php echo __( 'Next', 'wpfnl' ); ?></button>
					<?php } ?>
				<?php } ?>
			</div>
		</form>
	</div>

	<div class="wpfnl-express-checkout-right">
		<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

			<?php if( !$hide_section_settings || ( isset($hide_section_settings['custom_heading']) && $hide_section_settings['custom_heading'] != '' )  ) { ?>
				<h3 id="order_review_heading">
					<?php
						if( isset($hide_section_settings['custom_heading']) && $hide_section_settings['custom_heading'] != '' ){
							esc_html( $hide_section_settings['custom_heading'] );
						}else{
							esc_html_e( 'Your orders', 'wpfnl' );
						}
					?>
				</h3>
			<?php } ?>

			<?php do_action( 'wpfunnels/before_checkout_order_review' ); ?>

			<div class="responsive-table">
				<?php woocommerce_order_review();?>
			</div>

			<?php do_action( 'wpfunnels/after_checkout_order_review' ); ?>

		</form>
	</div>

	<?php } ?>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
