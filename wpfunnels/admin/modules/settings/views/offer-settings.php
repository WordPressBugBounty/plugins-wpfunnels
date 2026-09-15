<?php
/**
 * View offer settings
 *
 * @package
 */

$offer_settings = \WPFunnels\Wpfnl_functions::get_offer_settings();

/**
 * Skip Cart ships with the Global Funnel plan, the rest of this screen with any
 * Pro plan. Every field renders either way so the options stay discoverable.
 *
 * A gated field is disabled and trades its tooltip for a description printed
 * under the label, and each gated box closes with a single upgrade note. One
 * note per tab rather than one per field: they all unlock together, so
 * repeating the link beside every row only added noise.
 */
$is_skip_cart_enabled = \WPFunnels\Wpfnl_functions::is_global_funnel_activated();
?>

<div class="settings-tab-container">
    <ul class="wpfnl-general-settings-tab-nav">
        <li class="inner-tab active" data-tab="tab-skip-cart"><?php esc_html_e('Skip Cart Settings', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-order-management"><?php esc_html_e('Order Management', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-payment-management"><?php esc_html_e('Payment Management', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-thank-you-page"><?php esc_html_e('Global Thank You', 'wpfnl'); ?></li>
    </ul>
</div>

<!-- Skip Cart Settings Tab -->
<div class="wpfnl-tab-content active" id="tab-skip-cart">
	<div class="wpfnl-box skip-cart-setting">
		<div class="wpfnl-field-wrapper skip-cart-input-wrapper">
			<label class="skip-cart-label">
				<?php esc_html_e('Enable Skip Cart', 'wpfnl'); ?>
				<?php if(!$is_skip_cart_enabled){ ?>
					<span class="wpfnl-field-description"><?php esc_html_e('Send buyers straight from the product to the checkout page by skipping the cart.', 'wpfnl'); ?></span>
				<?php } ?>
			</label>
			<div class="wpfnl-fields enable-skip-cart">
				<span class="wpfnl-switcher extra-sm no-title">
					<?php if($is_skip_cart_enabled){ ?>
						<input type="checkbox" name="enable-skip-cart"  id="enable-skip-cart" <?php if($this->general_settings['enable_skip_cart'] == 'on'){echo 'checked'; } ?>/>

					<?php }else{ ?>
						<input type="checkbox" name="" id="enable-skip-cart" disabled />
					<?php } ?>
					<label for="enable-skip-cart"></label>
				</span>
			</div>
		</div>
		<div class="wpfnl-skip-cart">
			<div class="wpfnl-field-wrapper">
				<label class="has-tooltip">
					<?php esc_html_e('Skip Cart For The Entire Store', 'wpfnl'); ?>
					<span class="wpfnl-tooltip">
							<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
							<p><?php esc_html_e('Select this option to enable Skip Cart for all products in your entire WooCommerce store and your funnels.', 'wpfnl'); ?></p>
						</span>
				</label>
				<div class="wpfnl-fields">
					<div class="wpfnl-radiobtn no-title">
						<?php if($is_skip_cart_enabled){ ?>
							<input type="radio" name="skip-cart" id="enable-skip-cart-for-whole" value="whole" <?php checked( $this->general_settings['skip_cart_for'], 'whole' ) ?>/>

						<?php }else{ ?>
							<input type="radio" name="" id="enable-skip-cart-for-whole" value="" disabled />
						<?php } ?>
						<label for="enable-skip-cart-for-whole"></label>
					</div>
				</div>
			</div>
			<!-- /field-wrapper -->

			<div class="wpfnl-field-wrapper">
				<label class="has-tooltip">
					<?php esc_html_e('Skip Cart for Global Funnels Only', 'wpfnl'); ?>
					<span class="wpfnl-tooltip">
							<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
							<p><?php esc_html_e("Select this option to enable Skip Cart only for products that meet your global funnels' conditions.", "wpfnl"); ?></p>
						</span>
				</label>
				<div class="wpfnl-fields">
					<div class="wpfnl-radiobtn no-title">
						<?php if($is_skip_cart_enabled){ ?>
							<input type="radio" name="skip-cart" id="enable-skip-cart-for-gbf" value="gbf" <?php checked(  $this->general_settings['skip_cart_for'], 'gbf' ) ?>/>

						<?php }else{ ?>
							<input type="radio" name="" id="enable-skip-cart-for-gbf" value="" disabled />
						<?php } ?>
						<label for="enable-skip-cart-for-gbf"></label>
					</div>
				</div>
			</div>
		</div>
		<!-- /field-wrapper -->

		<?php if(!$is_skip_cart_enabled){
			$pro_note_text = __('Skip Cart comes with the WPFunnels Global Funnel plan.', 'wpfnl');
			$pro_note_utm  = 'settings-offer-skip-cart';
			require WPFNL_DIR . '/admin/partials/pro-note.php';
		} ?>
	</div>
</div>


<!-- Order Management Tab -->
<div class="wpfnl-tab-content" id="tab-order-management">
	<div class="wpfnl-box order-management">
		<div class="wpfnl-field-wrapper">
			<label<?php echo $is_pro_activated ? ' class="has-tooltip"' : ''; ?>>
				<?php esc_html_e('Create a new child order for every accepted Upsell/Downsell offer', 'wpfnl'); ?>
				<?php if($is_pro_activated){ ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
						<p><?php esc_html_e('Enabling this will create separate orders for every post-purchase offers you make.', 'wpfnl'); ?></p>
					</span>
				<?php }else{ ?>
					<span class="wpfnl-field-description"><?php esc_html_e('Enabling this will create separate orders for every post-purchase offers you make.', 'wpfnl'); ?></span>
				<?php } ?>
			</label>
			<div class="wpfnl-fields">
				<div class="wpfnl-radiobtn no-title">
					<?php if($is_pro_activated){ ?>
						<input type="radio" name="offer-orders" id="wpfunnels-offer-child-order" value="child-order" <?php checked($offer_settings['offer_orders'], 'child-order') ?>/>

					<?php }else{ ?>
						<input type="radio" name="offer-orders" id="wpfunnels-offer-child-order" value="" disabled />
					<?php } ?>

					<label for="wpfunnels-offer-child-order"></label>
				</div>
			</div>
		</div>
		<!-- /field-wrapper -->

		<div class="wpfnl-field-wrapper">
			<label<?php echo $is_pro_activated ? ' class="has-tooltip"' : ''; ?>>
				<?php esc_html_e('Add all accepted offers (Upsell/Downsell) to the main order', 'wpfnl'); ?>
				<?php if($is_pro_activated){ ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
						<p><?php esc_html_e('All purchases including main product, order bump, upsell(s), and downsell(s), will be included as part of a single order in WooCommerce.', 'wpfnl'); ?></p>
					</span>
				<?php }else{ ?>
					<span class="wpfnl-field-description"><?php esc_html_e('All purchases including main product, order bump, upsell(s), and downsell(s), will be included as part of a single order in WooCommerce.', 'wpfnl'); ?></span>
				<?php } ?>
			</label>
			<div class="wpfnl-fields">
				<div class="wpfnl-radiobtn no-title">
					<?php if($is_pro_activated){ ?>
						<input type="radio" name="offer-orders" id="wpfunnels-offer-main-order" value="main-order" <?php checked($offer_settings['offer_orders'], 'main-order') ?>/>

					<?php }else{ ?>
						<input type="radio" name="" id="wpfunnels-offer-main-order" value="" disabled />
					<?php } ?>
					
					<label for="wpfunnels-offer-main-order"></label>
				</div>
			</div>
		</div>
		<!-- /field-wrapper -->

		<?php if(!$is_pro_activated){
			$pro_note_text = __('Order Management comes with WPFunnels Pro.', 'wpfnl');
			$pro_note_utm  = 'settings-offer-order-management';
			require WPFNL_DIR . '/admin/partials/pro-note.php';
		} ?>
	</div>
</div>

<!-- Payment Management Tab -->
<div class="wpfnl-tab-content" id="tab-payment-management">
	<div class="wpfnl-box payment-management">
		<div class="wpfnl-field-wrapper">
			<label<?php echo $is_pro_activated ? ' class="has-tooltip"' : ''; ?>>
				<?php esc_html_e('Only show supported payment gateways during funnel checkout', 'wpfnl'); ?>
				<?php if($is_pro_activated){ ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
						<p><?php esc_html_e('You may have several payment gateways in your site. But since we have limited supported payment gateways for post purchase offers, you can use this option to only view supported payment gateways during the funnel checkout page. This means, you do not have to disable other payment gateways for the funnel.', 'wpfnl'); ?></p>
					</span>
				<?php }else{ ?>
					<span class="wpfnl-field-description"><?php esc_html_e('Show only the payment gateways that support post-purchase offers on your funnel checkout.', 'wpfnl'); ?></span>
				<?php } ?>
			</label>
			<div class="wpfnl-fields">
				<span class="wpfnl-switcher extra-sm no-title">
					<?php if($is_pro_activated){ ?>
						<input type="checkbox" name="wpfnl-show-supported-payment-gateway"
						id="wpfnl-show-supported-payment-gateway" <?php if ($this->offer_settings['show_supported_payment_gateway'] == 'on') { echo 'checked'; } ?>/>

					<?php }else{ ?>
						<input type="checkbox" name=""
						id="wpfnl-show-supported-payment-gateway" disabled />
					<?php } ?>

					<label for="wpfnl-show-supported-payment-gateway"></label>
				</span>
			</div>
		</div>
		<!-- /field-wrapper -->

		<div class="wpfnl-field-wrapper">
			<label<?php echo $is_pro_activated ? ' class="has-tooltip"' : ''; ?>>
				<?php esc_html_e('Skip upsell/downsell for unsupported payment gateways', 'wpfnl'); ?>
				<?php if($is_pro_activated){ ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
						<p><?php esc_html_e('Use this option so that if a buyer chooses to use a payment option that is not supported by WPFunnels, they will not get the post-purchase offers. This means, all payment options will be shown at the checkout page.', 'wpfnl'); ?></p>
					</span>
				<?php }else{ ?>
					<span class="wpfnl-field-description"><?php esc_html_e('Keep every payment option at checkout and skip the offers when the buyer picks an unsupported one.', 'wpfnl'); ?></span>
				<?php } ?>
			</label>
			<div class="wpfnl-fields">
					<span class="wpfnl-switcher extra-sm no-title">
						<?php if($is_pro_activated){ ?>
							<input type="checkbox" name="wpfnl-skip-offer-step" id="wpfnl-skip-offer-step" <?php if ($this->offer_settings['skip_offer_step'] == 'on') { echo 'checked'; } ?>/>

						<?php }else{ ?>
							<input type="checkbox" name="" id="wpfnl-skip-offer-step" disabled />
						<?php } ?>

						<label for="wpfnl-skip-offer-step"></label>
					</span>
			</div>
		</div>


		<div class="wpfnl-field-wrapper">
			<label<?php echo $is_pro_activated ? ' class="has-tooltip"' : ''; ?>>
				<?php esc_html_e('Skip upsell/downsell for free products', 'wpfnl'); ?>
				<?php if($is_pro_activated){ ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
						<p><?php esc_html_e("Enable this option in case you want to use a $0 product as the main funnel product. In this case, Upsell and downsell steps will be skipped since they won't work without a successful payment during checkout.", 'wpfnl'); ?></p>
					</span>
				<?php }else{ ?>
					<span class="wpfnl-field-description"><?php esc_html_e('Skip the upsell and downsell steps when the main funnel product is free.', 'wpfnl'); ?></span>
				<?php } ?>
			</label>
			<div class="wpfnl-fields">
					<span class="wpfnl-switcher extra-sm no-title">
						<?php if($is_pro_activated){ ?>
							<input type="checkbox" name="wpfnl-skip-offer-step-for-free" id="wpfnl-skip-offer-step-for-free" <?php if ($this->offer_settings['skip_offer_step_for_free'] == 'on') { echo 'checked'; } ?>/>

						<?php }else{ ?>
							<input type="checkbox" name="" id="wpfnl-skip-offer-step-for-free" disabled />

						<?php } ?>
						<label for="wpfnl-skip-offer-step-for-free"></label>
					</span>
			</div>
		</div>
		<!-- /field-wrapper -->

		<?php if(!$is_pro_activated){
			$pro_note_text = __('Payment Management comes with WPFunnels Pro.', 'wpfnl');
			$pro_note_utm  = 'settings-offer-payment-management';
			require WPFNL_DIR . '/admin/partials/pro-note.php';
		} ?>
	</div>
</div>
<!-- /settings-box -->

<!-- Thank You Page Tab -->
<div class="wpfnl-tab-content" id="tab-thank-you-page">
	<div class="wpfnl-box payment-management">
		<div class="wpfnl-field-wrapper">
			<label<?php echo $is_pro_activated ? ' class="has-tooltip"' : ''; ?>>
				<?php esc_html_e('Enable global thank you', 'wpfnl'); ?>
				<?php if($is_pro_activated){ ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
						<p><?php esc_html_e('Enable this option to redirect users to the WooCommerce order confirmation page for all funnels. If disabled, each funnel will use its own thank you page.', 'wpfnl'); ?></p>
					</span>
				<?php }else{ ?>
					<span class="wpfnl-field-description"><?php esc_html_e('Send buyers of every funnel to the WooCommerce order confirmation page instead of a per-funnel thank you page.', 'wpfnl'); ?></span>
				<?php } ?>
			</label>
			<div class="wpfnl-fields">
				<span class="wpfnl-switcher extra-sm no-title">
					
					<?php if($is_pro_activated){ ?>
						<input type="checkbox" name="wpfnl-enable-global-thankyou" id="wpfnl-enable-global-thankyou" <?php if ($this->offer_settings['enable_global_thankyou'] == 'on') { echo 'checked'; } ?>/>

					<?php }else{ ?>
						<input type="checkbox" name="" id="wpfnl-enable-global-thankyou" disabled />
					<?php } ?>
					<label for="wpfnl-enable-global-thankyou"></label>
				</span>
			</div>
		</div>
		<!-- /field-wrapper -->

		<?php if(!$is_pro_activated){
			$pro_note_text = __('Global Thank You comes with WPFunnels Pro.', 'wpfnl');
			$pro_note_utm  = 'settings-offer-global-thank-you';
			require WPFNL_DIR . '/admin/partials/pro-note.php';
		} ?>
	</div>
</div>
