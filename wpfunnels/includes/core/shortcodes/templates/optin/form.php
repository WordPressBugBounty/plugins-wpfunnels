<?php

/**
 * Opt-in form design
 *
 * @package Optin form
 */

echo esc_js($recaptch_script);
?>
<div class="wpfnl-optin-form wpfnl-shortcode-optin-form-wrapper">
	<form method="post">
		<?php wp_nonce_field('wpfnl-optin-form-submission', 'wpfnl_optin_form_submission'); ?>
		<input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>" />
		<input type="hidden" name="admin_email" value="<?php echo esc_html($this->attributes['admin_email']); ?>" />
		<input type="hidden" name="admin_email_subject" value="<?php echo esc_html($this->attributes['admin_email_subject']); ?>" />
		<input type="hidden" name="redirect_url" value="<?php echo esc_url($this->attributes['redirect_url']); ?>" />
		<input type="hidden" name="notification_text" value="<?php echo esc_html($this->attributes['notification_text']); ?>" />
		<input type="hidden" name="post_action" value="<?php echo esc_html($this->attributes['post_action']); ?>" />
		<input type="hidden" name="enable_mm_contact" value="<?php echo esc_html($this->attributes['enable_mm_contact']); ?>" />
		<input type="hidden" name="mm_contact_status" value="<?php echo esc_html($this->attributes['mm_contact_status']); ?>" />
		<input type="hidden" name="mm_lists" value="<?php echo esc_html($this->attributes['mm_lists']); ?>" />
		<input type="hidden" name="mm_tags" value="<?php echo esc_html($this->attributes['mm_tags']); ?>" />
		<?php
		echo esc_html($is_recaptch_input);
		echo esc_html($token_input);
		echo esc_html($token_secret_key);
		?>

		<div class="wpfnl-optin-form-wrapper">
			<?php
			$first_name_required      = isset($this->attributes['first_name_required']) && $this->attributes['first_name_required'] == 'true';
			$first_name_required_attr = $first_name_required ? 'required' : '';
			$show_required_mark       = isset($this->attributes['show_required_mark']) && $this->attributes['show_required_mark'] == 'true';
			$first_name_autocomplete  = !empty($this->attributes['first_name_autocomplete'])
				? 'autocomplete="' . esc_attr($this->attributes['first_name_autocomplete']) . '"'
				: '';
			?>
			<?php if ('true' == esc_html($this->attributes['first_name'])) { //phpcs:ignore 
			?>
				<div class="wpfnl-optin-form-group first-name">
					<label for="wpfnl-first-name">
						<?php echo isset($this->attributes['first_name_label']) ? esc_html($this->attributes['first_name_label']) : esc_html_e('First Name', 'wpfnl'); ?>
						<?php echo (!empty($this->attributes['first_name_label']) && $first_name_required && $show_required_mark) ? wp_kses_post('<span class="required-mark">*</span>') : ''; ?>
					</label>
					<span class="input-wrapper">
						<?php if ('true' == esc_html($this->attributes['show_input_fields_icon'])) : ?>
							<span class="field-icon">
								<img src="<?php echo esc_url($this->attributes['first_name_icon_url']); ?>" alt="icon">
							</span>
						<?php endif; ?>
						<?php $f_name_placeholder = isset($this->attributes['first_name_placeholder']) ? esc_html($this->attributes['first_name_placeholder']) : ''; ?>
						<input type="text" name="first_name" id="wpfnl-first-name" placeholder="<?php echo esc_html($f_name_placeholder); ?>" <?php echo $first_name_required_attr; ?> <?php echo $first_name_autocomplete; ?> />
					</span>

				</div>
			<?php } ?>

			<?php
			$last_name_required      = isset($this->attributes['last_name_required']) && $this->attributes['last_name_required'] == 'true';
			$last_name_required_attr = $last_name_required ? 'required' : '';
			$last_name_autocomplete  = !empty($this->attributes['last_name_autocomplete'])
				? 'autocomplete="' . esc_attr($this->attributes['last_name_autocomplete']) . '"'
				: '';
			?>
			<?php if ('true' == $this->attributes['last_name']) { //phpcs:ignore 
			?>
				<div class="wpfnl-optin-form-group last-name">
					<label for="wpfnl-last-name">
						<?php echo isset($this->attributes['last_name_label']) ? esc_html($this->attributes['last_name_label']) : esc_html_e('Last Name', 'wpfnl'); ?>
						<?php echo (!empty($this->attributes['last_name_label']) && $last_name_required && $show_required_mark) ? wp_kses_post('<span class="required-mark">*</span>') : ''; ?>
					</label>

					<span class="input-wrapper">
						<?php if ('true' == esc_html($this->attributes['show_input_fields_icon'])) : ?>
							<span class="field-icon">
								<img src="<?php echo esc_url($this->attributes['last_name_icon_url']); ?>" alt="icon">
							</span>
						<?php endif; ?>
						<?php $l_name_placeholder = isset($this->attributes['last_name_placeholder']) ? esc_html($this->attributes['last_name_placeholder']) : ''; ?>
						<input type="text" name="last_name" id="wpfnl-last-name" placeholder="<?php echo esc_html($l_name_placeholder); ?>" <?php echo $last_name_required_attr; ?> <?php echo $last_name_autocomplete; ?> />
					</span>
				</div>
			<?php } ?>

			<div class="wpfnl-optin-form-group email">
				<label for="wpfnl-email">
					<label for="wpfnl-email">
						<?php echo isset($this->attributes['email_label']) ? esc_html($this->attributes['email_label']) : esc_html_e('Email', 'wpfnl'); ?>
						<?php echo (!empty($this->attributes['email_label']) && $show_required_mark) ? wp_kses_post('<span class="required-mark">*</span>') : ''; ?>
					</label>
				</label>
				<span class="input-wrapper">
					<?php if ('true' == esc_html($this->attributes['show_input_fields_icon'])) : ?>
						<span class="field-icon">
							<img src="<?php echo esc_url($this->attributes['email_icon_url']); ?>" alt="icon">
						</span>
					<?php endif; ?>

					<?php $email_placeholder = isset($this->attributes['email_placeholder']) ? esc_html($this->attributes['email_placeholder']) : ''; ?>

					<input type="email" name="email" id="wpfnl-email" placeholder="<?php echo esc_html($email_placeholder); ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" required />
				</span>
			</div>

			<?php
			$phone_required      = isset($this->attributes['phone_required']) && $this->attributes['phone_required'] == 'true';
			$phone_required_attr = $phone_required ? 'required' : '';
			$phone_autocomplete  = !empty($this->attributes['phone_autocomplete'])
				? 'autocomplete="' . esc_attr($this->attributes['phone_autocomplete']) . '"'
				: '';
			?>
			<?php if ('true' == $this->attributes['phone']) { //phpcs:ignore 
			?>
				<div class="wpfnl-optin-form-group phone">
					<label for="wpfnl-phone">
						<?php echo isset($this->attributes['phone_label']) ? esc_html($this->attributes['phone_label']) : esc_html_e('Phone', 'wpfnl'); ?>
						<?php echo (!empty($this->attributes['phone_label']) && $phone_required && $show_required_mark) ? wp_kses_post('<span class="required-mark">*</span>') : ''; ?>
					</label>

					<span class="input-wrapper">
						<?php if ('true' == esc_html($this->attributes['show_input_fields_icon'])) : ?>
							<span class="field-icon">
								<img src="<?php echo esc_url($this->attributes['phone_icon_url']); ?>" alt="icon">
							</span>
						<?php endif; ?>

						<?php $phone_placeholder = isset($this->attributes['phone_placeholder']) ? esc_html($this->attributes['phone_placeholder']) : ''; ?>
						<input type="text" name="phone" id="wpfnl-phone" placeholder="<?php echo esc_html($phone_placeholder); ?>" <?php echo $phone_required_attr; ?> <?php echo $phone_autocomplete; ?> />
					</span>
				</div>
			<?php } ?>

			<?php
			$website_url_required      = isset($this->attributes['website_url_required']) && $this->attributes['website_url_required'] == 'true';
			$website_url_required_attr = $website_url_required ? 'required' : '';
			$website_url_autocomplete  = !empty($this->attributes['website_url_autocomplete'])
				? 'autocomplete="' . esc_attr($this->attributes['website_url_autocomplete']) . '"'
				: '';
			?>
			<?php if ('true' == $this->attributes['website_url']) { //phpcs:ignore 
			?>
				<div class="wpfnl-optin-form-group website-url">
					<label for="wpfnl-web-url">
						<?php echo isset($this->attributes['website_url_label']) ? esc_html($this->attributes['website_url_label']) : esc_html_e('Website Url', 'wpfnl'); ?>
						<?php echo (!empty($this->attributes['website_url_label']) && $website_url_required && $show_required_mark) ? wp_kses_post('<span class="required-mark">*</span>') : ''; ?>
					</label>

					<span class="input-wrapper">
						<?php if ('true' == esc_html($this->attributes['show_input_fields_icon'])) : ?>
							<span class="field-icon">
								<img src="<?php echo esc_url($this->attributes['website_url_icon_url']); ?>" alt="icon">
							</span>
						<?php endif; ?>

						<?php $weburl_placeholder = isset($this->attributes['website_url_placeholder']) ? esc_html($this->attributes['website_url_placeholder']) : ''; ?>
						<input type="text" name="web-url" id="wpfnl-web-url" pattern="https?://.+" size="30" placeholder="<?php echo esc_html($weburl_placeholder); ?>" <?php echo $website_url_required_attr; ?> <?php echo $website_url_autocomplete; ?> />
					</span>
				</div>
			<?php } ?>

			<?php
			$message_required      = isset($this->attributes['message_required']) && $this->attributes['message_required'] == 'true';
			$message_required_attr = $message_required ? 'required' : '';
			?>
			<?php if ('true' == $this->attributes['message']) { //phpcs:ignore 
			?>
				<div class="wpfnl-optin-form-group message">
					<label for="wpfnl-message">
						<?php echo isset($this->attributes['message_label']) ? esc_html($this->attributes['message_label']) : esc_html_e('Message', 'wpfnl'); ?>
						<?php echo (!empty($this->attributes['message_label']) && $message_required && $show_required_mark) ? wp_kses_post('<span class="required-mark">*</span>') : ''; ?>
					</label>

					<?php $message_placeholder = isset($this->attributes['message_placeholder']) ? esc_html($this->attributes['message_placeholder']) : ''; ?>
					<span class="input-wrapper">
						<textarea name="message" id="wpfnl-message" cols="30" rows="3" placeholder="<?php echo esc_html($message_placeholder); ?>" <?php echo $message_required_attr; ?>></textarea>
					</span>
				</div>
			<?php } ?>

			<?php
			$acceptance_required      = isset($this->attributes['acceptance_checkbox_required']) && $this->attributes['acceptance_checkbox_required'] === 'true';
			$show_required_mark       = isset($this->attributes['show_required_mark']) && $this->attributes['show_required_mark'] === 'true';
			$acceptance_required_attr = $acceptance_required ? 'required' : '';
			$acceptance_asterisk      = ($acceptance_required && $show_required_mark) ? '<span class="required-mark">*</span>' : '';
			$acceptance_text          = !empty($this->attributes['acceptance_checkbox_text']) ? $this->attributes['acceptance_checkbox_text'] : __('I have read and agree the Terms & Condition.', 'wpfnl');
			if ('true' == $this->attributes['acceptance_checkbox']) { //phpcs:ignore
			?>
				<div class="wpfnl-optin-form-group acceptance-checkbox">
					<input type="checkbox" name="acceptance_checkbox" id="wpfnl-acceptance_checkbox" <?php echo $acceptance_required_attr; ?> />
					<label for="wpfnl-acceptance_checkbox">
						<span class="check-box"></span>
						<?php echo esc_html($acceptance_text); ?>
						<?php echo wp_kses_post($acceptance_asterisk); ?>
					</label>
				</div>
			<?php
			}
			?>

			<?php
			if ('true' == $this->attributes['data_to_checkout']) { //phpcs:ignore
			?>
				<input type="hidden" name="data_to_checkout" value="<?php echo esc_html('yes'); ?>" />
			<?php
			}

			if ('true' == $this->attributes['register_as_subscriber']) { //phpcs:ignore
			?>
				<input type="hidden" name="optin_allow_registration" value="<?php echo esc_html('yes'); ?>" />
				<?php
				if ('true' == esc_html($this->attributes['subscription_permission'])) { //phpcs:ignore
				?>
					<div class="wpfnl-optin-form-group user-registration-checkbox">
						<input type="checkbox" name="user_registration_checkbox" id="wpfnl-registration_checkbox" required />
						<label for="wpfnl-registration_checkbox">
							<span class="check-box"></span>
							<?php
							echo isset($this->attribute['subscription_permission_text']) ? esc_html($this->attribute['subscription_permission_text']) : esc_html_e('I agree to be registered as a subscriber.', 'wpfnl');
							?>
							<span class="required-mark">*</span>
						</label>
					</div>
			<?php
				}
			}
			?>
			<?php
			$button_text           = !empty($this->attributes['button_text']) ? esc_html($this->attributes['button_text']) : 'Submit';
			$button_text_color     = !empty($this->attributes['button_text_color']) ? 'color: ' . esc_attr($this->attributes['button_text_color']) . ';' : '';
			$button_bg_color       = !empty($this->attributes['button_bg_color']) ? 'background-color: ' . esc_attr($this->attributes['button_bg_color']) . ';' : '';
			$button_font_size      = !empty($this->attributes['button_font_size']) ? 'font-size: ' . esc_attr($this->attributes['button_font_size']) . ';' : '';
			$button_font_weight    = !empty($this->attributes['button_font_weight']) ? 'font-weight: ' . esc_attr($this->attributes['button_font_weight']) . ';' : '';
			$button_padding        = !empty($this->attributes['button_padding']) ? 'padding: ' . esc_attr($this->attributes['button_padding']) . ';' : '';
			$button_border_radius  = !empty($this->attributes['button_border_radius']) ? 'border-radius: ' . esc_attr($this->attributes['button_border_radius']) . ';' : '';
			$button_width          = !empty($this->attributes['button_width']) ? 'width: ' . esc_attr($this->attributes['button_width']) . ';' : '';
			$button_style          = $button_text_color . $button_bg_color . $button_font_size . $button_font_weight . $button_padding . $button_border_radius . $button_width;
			$button_id             = !empty($this->attributes['button_id']) ? esc_attr($this->attributes['button_id']) : '';
			$button_icon           = !empty($this->attributes['button_icon']) ? wp_kses_post($this->attributes['button_icon']) : '';
			$icon_position         = !empty($this->attributes['button_icon_position']) ? $this->attributes['button_icon_position'] : 'before';
			$button_align          = !empty($this->attributes['button_align']) ? esc_attr($this->attributes['button_align']) : 'center';
			?>
			<div class="wpfnl-optin-form-group submit align-<?php echo $button_align; ?>">
				<button
					type="submit"
					class="btn-optin <?php echo esc_attr($this->attributes['btn_class']); ?>"
					style="<?php echo esc_attr($button_style); ?>"
					id="<?php echo $button_id; ?>">
					<span>
						<?php
						if (!empty($button_icon) && $icon_position === 'before') echo wp_kses_post($button_icon) . ' ';
						echo $button_text;
						if (!empty($button_icon) && $icon_position === 'after') echo ' ' . wp_kses_post($button_icon);
						?>
					</span>
					<?php if (empty($this->attributes['button_loader_type']) || $this->attributes['button_loader_type'] !== 'none'): ?>
						<span class="wpfnl-loader"></span>
					<?php endif; ?>
				</button>
			</div>
		</div>
	</form>
	<?php
	if ('on' == $is_recaptch && '' != $site_key && '' != $site_secret_key) { //phpcs:ignore
	?>
		<script>
			grecaptcha.ready(function() {
				grecaptcha.execute('<?php echo esc_html($site_key); ?>', {
					action: 'homepage'
				}).then(function(token) {
					document.getElementById("wpf-optin-g-token").value = token;
				});
			});
		</script>
	<?php
	}
	?>

	<div class="response"></div>
</div>