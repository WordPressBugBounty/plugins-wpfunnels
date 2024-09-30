<?php
/**
 * User role management settings
 *
 * @package
 */
?>
<div class="basic-tools-field">
	<div class="wpfnl-user-role-container">
		<h4 class="settings-title"> <?php esc_html_e('User role manager', 'wpfnl'); ?> </h4>
		<div class="wpfnl-box">
			<?php if ($this->user_roles_settings) { ?>
				<div class="wpfnl-role-table-wrapper">
					<table class="wpfnl-role-table">
						<tr>
							<th><?php esc_html_e('Role', 'wpfnl'); ?></th>
							<th><?php esc_html_e('Access', 'wpfnl'); ?></th>
						</tr>

						<?php foreach ($this->user_roles_settings as $role => $setting) { ?>
							<tr>
								<td>
								<span class="role-title">
									<?php echo str_replace("_"," ",ucfirst($role)); ?>
								</span>
								</td>

								<td>
								<span class="wpfnl-checkbox">
									<input type="checkbox" name="user_role[]"  value="<?php echo $this->user_roles_settings[$role]; ?>" id="user-role-<?php echo $role; ?>" data-role="<?php echo $role; ?>" <?php checked( 'yes', $this->user_roles_settings[$role], 'true' ) ?>>
									<label for="user-role-<?php echo $role; ?>">Full Access</label>
								</span>
								</td>
							</tr>
						<?php } ?>
					</table>
				</div>
			<?php }?>
		</div>
	</div>

	<?php if( apply_filters( 'wpfunnels/is_wpfnl_pro_active', false ) ){?>
		<div class="wpfnl-analytics-settings-container">
			<h4 class="settings-title"> <?php esc_html_e('Analytics', 'wpfnl'); ?> </h4>
			<div class="wpfnl-box">
				<div class="wpfnl-field-wrapper analytics-stats">
					<label><?php esc_html_e('Disable Analytics Tracking For', 'wpfnl'); ?>
						<span class="wpfnl-tooltip">
							<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
							<p><?php esc_html_e('If you want WPFunnels not to track traffic, conversion, & revenue count for Analytics from certain user roles in your site, then you may do so using these options.', 'wpfnl'); ?></p>
						</span>
					</label>

					<div class="wpfnl-fields">
						<?php foreach( $this->user_roles as $role ) { ?>
							<span class="wpfnl-checkbox">
								<input type="checkbox" name="analytics-role[]"  id="<?php echo $role; ?>-analytics-role" data-role="<?php echo $role; ?>" <?php if(isset($this->general_settings['disable_analytics'][$role])){checked( $this->general_settings['disable_analytics'][$role], 'true' );} ?> />
								<label for="<?php echo $role; ?>-analytics-role"><?php echo str_replace("_"," ",ucfirst($role)); ?></label>
							</span>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>

	<?php } ?>

</div>
