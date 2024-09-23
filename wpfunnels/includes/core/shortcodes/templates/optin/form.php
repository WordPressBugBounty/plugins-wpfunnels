<?php
/**
 * Opt-in form design
 *
 * @package Optin form
 */

echo esc_js( $recaptch_script );
?>
<div class="wpfnl-optin-form wpfnl-shortcode-optin-form-wrapper" >
	<form method="post">
		<?php wp_nonce_field( 'wpfnl-optin-form-submission', 'wpfnl_optin_form_submission' ); ?>
		<input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>" />
		<input type="hidden" name="admin_email" value="<?php echo esc_html( $this->attributes['admin_email'] ); ?>" />
		<input type="hidden" name="admin_email_subject" value="<?php echo esc_html( $this->attributes['admin_email_subject'] ); ?>" />
		<input type="hidden" name="redirect_url" value="<?php echo esc_url( $this->attributes['redirect_url'] ); ?>" />
		<input type="hidden" name="notification_text" value="<?php echo esc_html( $this->attributes['notification_text'] ); ?>" />
		<input type="hidden" name="post_action" value="<?php echo esc_html( $this->attributes['post_action'] ); ?>" />
		<input type="hidden" name="enable_mm_contact" value="<?php echo esc_html( $this->attributes['enable_mm_contact'] ); ?>" />
		<input type="hidden" name="mm_contact_status" value="<?php echo esc_html( $this->attributes['mm_contact_status'] ); ?>" />
		<input type="hidden" name="mm_lists" value="<?php echo esc_html( $this->attributes['mm_lists'] ); ?>" />
		<input type="hidden" name="mm_tags" value="<?php echo esc_html( $this->attributes['mm_tags'] ); ?>" />
		<?php
		echo esc_html( $is_recaptch_input );
		echo esc_html( $token_input );
		echo esc_html( $token_secret_key );
		?>

		<div class="wpfnl-optin-form-wrapper" >
			<?php if ( 'true' == esc_html( $this->attributes['first_name'] ) ) { //phpcs:ignore ?>
				<div class="wpfnl-optin-form-group first-name">
					<label for="wpfnl-first-name">
						<?php echo isset( $this->attributes['first_name_label'] ) ? esc_html( $this->attributes['first_name_label'] ) : esc_html_e( 'First Name', 'wpfnl' ); ?>
					</label>
					<span class="input-wrapper">
						<span class="field-icon">
							<img src="<?php echo esc_html( WPFNL_DIR_URL . '/public/assets/images/user-icon.svg' ); ?>" alt="icon">
						</span>
						<?php $f_name_placeholder = isset( $this->attributes['first_name_placeholder'] ) ? esc_html( $this->attributes['first_name_placeholder'] ) : ''; ?>
						<input type="text" name="first_name" id="wpfnl-first-name" placeholder="<?php echo esc_html( $f_name_placeholder ); ?>"/>
					</span>

				</div>
			<?php } ?>

			<?php if ( 'true' == $this->attributes['last_name'] ) { //phpcs:ignore ?> 
				<div class="wpfnl-optin-form-group last-name">
					<label for="wpfnl-last-name">
						<?php echo isset( $this->attributes['last_name_label'] ) ? esc_html( $this->attributes['last_name_label'] ) : esc_html_e( 'Last Name', 'wpfnl' ); ?>
					</label>

					<span class="input-wrapper">
						<span class="field-icon">
							<img src="<?php echo esc_html( WPFNL_DIR_URL . '/public/assets/images/user-icon.svg' ); ?>" alt="icon">
						</span>
						<?php $l_name_placeholder = isset( $this->attributes['last_name_placeholder'] ) ? esc_html( $this->attributes['last_name_placeholder'] ) : ''; ?>
						<input type="text" name="last_name" id="wpfnl-last-name" placeholder="<?php echo esc_html( $l_name_placeholder ); ?>" />
					</span>
				</div>
			<?php } ?>

			<div class="wpfnl-optin-form-group email">
				<label for="wpfnl-email">
					<label for="wpfnl-email">
						<?php echo isset( $this->attributes['email_label'] ) ? esc_html( $this->attributes['email_label'] ) : esc_html_e( 'Email', 'wpfnl' ); ?>
					</label>
				</label>
				<span class="input-wrapper">
					<span class="field-icon">
						<img src="<?php echo esc_html( WPFNL_DIR_URL . '/public/assets/images/email-open-icon.svg' ); ?>" alt="icon">
					</span>

					<?php $email_placeholder = isset( $this->attributes['email_placeholder'] ) ? esc_html( $this->attributes['email_placeholder'] ) : ''; ?>

					<input type="email" name="email" id="wpfnl-email" placeholder="<?php echo esc_html( $email_placeholder ); ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"/>
				</span>
			</div>

			<?php if ( 'true' == $this->attributes['phone'] ) { //phpcs:ignore ?>
				<div class="wpfnl-optin-form-group phone">
					<label for="wpfnl-phone">
						<?php echo isset( $this->attributes['phone_label'] ) ? esc_html( $this->attributes['phone_label'] ) : esc_html_e( 'Phone', 'wpfnl' ); ?>
					</label>

					<span class="input-wrapper">
						<span class="field-icon">
							<img src="<?php echo esc_html( WPFNL_DIR_URL . '/public/assets/images/phone.svg' ); ?>" alt="icon">
						</span>

						<?php $phone_placeholder = isset( $this->attributes['phone_placeholder'] ) ? esc_html( $this->attributes['phone_placeholder'] ) : ''; ?>
						<input type="text" name="phone" id="wpfnl-phone" placeholder="<?php echo esc_html( $phone_placeholder ); ?>"/>
					</span>
				</div>
			<?php } ?>

			<?php if ( 'true' == $this->attributes['website_url'] ) { //phpcs:ignore ?>
				<div class="wpfnl-optin-form-group website-url">
					<label for="wpfnl-web-url">
						<?php echo isset( $this->attributes['website_url_label'] ) ? esc_html( $this->attributes['website_url_label'] ) : esc_html_e( 'Website Url', 'wpfnl' ); ?>
					</label>

					<span class="input-wrapper">
						<span class="field-icon">
							<img src="<?php echo esc_html( WPFNL_DIR_URL . '/public/assets/images/web-url.svg' ); ?>" alt="icon">
						</span>

						<?php $weburl_placeholder = isset( $this->attributes['website_url_placeholder'] ) ? esc_html( $this->attributes['website_url_placeholder'] ) : ''; ?>
						<input type="text" name="web-url" id="wpfnl-web-url" pattern="https?://.+" size="30" placeholder="<?php echo esc_html( $weburl_placeholder ); ?>"/>
					</span>
				</div>
			<?php } ?>

			<?php if ( 'true' == $this->attributes['message'] ) { //phpcs:ignore ?>
				<div class="wpfnl-optin-form-group message">
					<label for="wpfnl-message">
						<?php echo isset( $this->attributes['message_label'] ) ? esc_html( $this->attributes['message_label'] ) : esc_html_e( 'Message', 'wpfnl' ); ?>
					</label>

					<?php $message_placeholder = isset( $this->attributes['message_placeholder'] ) ? esc_html( $this->attributes['message_placeholder'] ) : ''; ?>
					<span class="input-wrapper">
						<textarea name="message" id="wpfnl-message" cols="30" rows="3" placeholder="<?php echo esc_html( $message_placeholder ); ?>" ></textarea>
					</span>
				</div>
			<?php } ?>

			<?php
			if ( 'true' == $this->attributes['acceptance_checkbox'] ) { //phpcs:ignore
				?>
				<div class="wpfnl-optin-form-group acceptance-checkbox">
					<input type="checkbox" name="acceptance_checkbox" id="wpfnl-acceptance_checkbox"/>
					<label for="wpfnl-acceptance_checkbox">
						<span class="check-box"></span>
						<?php
							echo esc_html_e( 'I have read and agree the Terms & Condition.', 'wpfnl' );
						?>
					</label>
				</div>
				<?php
			}
			?>

			<?php
			if ( 'true' == $this->attributes['data_to_checkout'] ) { //phpcs:ignore
				?>
				<input type="hidden" name="data_to_checkout" value="<?php echo esc_html( 'yes' ); ?>"/>
				<?php
			}

			if ( 'true' == $this->attributes['register_as_subscriber'] ) { //phpcs:ignore
				?>
				<input type="hidden" name="optin_allow_registration" value="<?php echo esc_html( 'yes' ); ?>"/>
				<?php
				if ( 'true' == esc_html( $this->attributes['subscription_permission'] ) ) { //phpcs:ignore
					?>
					<div class="wpfnl-optin-form-group user-registration-checkbox">
						<input type="checkbox" name="user_registration_checkbox" id="wpfnl-registration_checkbox" required/>
						<label for="wpfnl-registration_checkbox">
							<span class="check-box"></span>
							<?php
							echo isset( $this->attribute['subscription_permission_text'] ) ? esc_html( $this->attribute['subscription_permission_text'] ) : esc_html_e( 'I agree to be registered as a subscriber.', 'wpfnl' );
							?>
							<span class="required-mark">*</span>
						</label>
					</div>
					<?php
				}
			}
			?>
			<div class="wpfnl-optin-form-group submit align-center">
				<button type="submit" class="btn-optin <?php echo esc_html( $this->attributes['btn_class'] ); ?>">
					<span>
						Submit
					</span>
					<span class="wpfnl-loader"></span>
				</button>
			</div>
		</div>
	</form>
	<?php
	if ( 'on' == $is_recaptch && '' != $site_key && '' != $site_secret_key ) { //phpcs:ignore
		?>
		<script>
			grecaptcha.ready(function() {
				grecaptcha.execute('<?php echo esc_html( $site_key ); ?>', {action: 'homepage'}).then(function(token) {
					document.getElementById("wpf-optin-g-token").value = token;
				});
			});
		</script>
		<?php
	}
	?>

	<div class="response"></div>
</div>
