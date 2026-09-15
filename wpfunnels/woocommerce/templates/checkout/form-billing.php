<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
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
$hide_section_settings = \WPFunnels\Wpfnl_functions::get_checkout_section_heading_settings( 'billing' , get_the_ID() );

?>
<?php if ( WC()->cart && WC()->cart->needs_shipping_address() ) : ?>
	<p class="form-row form-row-wide wpfnl-billing-same-as-shipping">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
			<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="billing-same-as-shipping-checkbox" type="checkbox" name="billing_same_as_shipping" value="1" checked="checked" /> <span><?php esc_html_e( 'Use same address for billing', 'wpfnl' ); ?></span>
		</label>
	</p>
<?php endif; ?>

<?php $billing_container_style = ( WC()->cart && WC()->cart->needs_shipping_address() ) ? 'style="display: none;"' : ''; ?>
<div class="wpfnl-billing-fields woocommerce-billing-fields" <?php echo $billing_container_style; ?>>
	<?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>
		<?php if( !$hide_section_settings || ( isset($hide_section_settings['custom_heading']) && $hide_section_settings['custom_heading'] != '' )  ) { ?>
			<h3>
				<?php
				   if( isset($hide_section_settings['custom_heading']) && $hide_section_settings['custom_heading'] != '' ){
						esc_html_e( $hide_section_settings['custom_heading'], 'woocommerce' ); 
				   }else{
						esc_html_e( 'Billing &amp; Shipping', 'woocommerce' ); 
				   }
				?>
			</h3>
		<?php } ?>
	<?php else : ?>
		
		<?php if( !$hide_section_settings || ( isset($hide_section_settings['custom_heading']) && $hide_section_settings['custom_heading'] != '' )  ) { ?>
			<h3>
				<?php
				   if( isset($hide_section_settings['custom_heading']) && $hide_section_settings['custom_heading'] != '' ){
						esc_html_e( $hide_section_settings['custom_heading'], 'woocommerce' ); 
				   }else{
						esc_html_e( 'Billing details', 'woocommerce' ); 
				   }
				?>
			</h3>
		<?php } ?>

	<?php endif; ?>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );

		/*
		 * The modern layouts render billing_email up in their contact section — a
		 * visible field for guests, a hidden input for logged-in customers — matching
		 * how WooCommerce's own checkout keeps the email out of the billing address.
		 * Without this the field is emitted twice, producing two inputs sharing
		 * id="billing_email", which breaks label association and browser autofill.
		 *
		 * WC_Checkout::get_checkout_fields() caches its result on first call, and the
		 * contact section calls it before this template runs, so a late
		 * woocommerce_checkout_fields filter cannot remove the field — it has to come
		 * out of the local copy here.
		 */
		$checkout_id = get_the_ID();
		if ( ! $checkout_id && wp_doing_ajax() ) {
			$checkout_id = \WPFunnels\Wpfnl_functions::get_checkout_id_from_post( $_POST );
			if ( ! $checkout_id && ! empty( $_POST['wpfnl_step_id'] ) ) {
				$checkout_id = absint( $_POST['wpfnl_step_id'] );
			}
		}
		$checkout_layout = $checkout_id ? \WPFunnels\Wpfnl_functions::get_checkout_layout( $checkout_id ) : '';
		if ( empty( $checkout_layout ) && isset( $_SESSION['checkout_layout'] ) ) {
			$checkout_layout = $_SESSION['checkout_layout'];
		}
		$is_modern = ( false !== strpos( $checkout_layout, 'modern' ) ) || in_array( $checkout_layout, array( 'wpfnl-modern-checkout', 'wpfnl-modern-one-column', 'wpfnl-modern-multistep' ), true );

		if ( did_action( 'wpfunnels/modern_checkout_email_rendered' ) || $is_modern ) {
			unset( $fields['billing_email'] );
		}

		foreach ( $fields as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="woocommerce-account-fields">
		<?php if ( ! $checkout->is_registration_required() ) : ?>

			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

			<div class="create-account">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
				<div class="clear"></div>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
<?php endif; ?>
