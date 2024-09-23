<?php
/**
 * View GTM settings
 * 
 * @package
 */
$is_pro_active = apply_filters( 'wpfunnels/is_pro_license_activated', false );
?>
<div class="wpfnl-box">
	<!-- /field-wrapper -->
		<div class="wpfnl-field-wrapper analytics-stats">
			<label>
				<?php esc_html_e('Track Events Using Google Tag Manager', 'wpfnl'); ?>
				<?php
					if (!$is_pro_active) {
						echo '<span class="pro-tag-icon">';
						require WPFNL_DIR . '/admin/partials/icons/pro-icon.php';
						echo '</span>';
					}
				?>
			</label>
			<?php if ($is_pro_active) { ?>
				<div class="wpfnl-fields">
					<span class="wpfnl-checkbox no-title">
						<input type="checkbox" name="wpfnl-gtm-enable"  id="gtm-enable" <?php if($this->gtm_settings['gtm_enable'] == 'on'){echo 'checked'; } ?>/>
						<label for="gtm-enable"></label>
					</span>
				</div>
			<?php } else { ?>
				<div class="wpfnl-fields">
					<span class="wpfnl-checkbox no-title">
						<input type="checkbox" name="wpfnl-gtm-enable"  id="gtm-enable-pro"/>
						<label for="gtm-enable-pro"></label>
					</span>
				</div>
			<?php } ?>
		</div>
		<div id="wpfnl-gtm">
			<div class="wpfnl-field-wrapper gtm-snippet-head" id="gtm-snippet-head">
				<label>
					<?php esc_html_e('GTM Container ID', 'wpfnl'); ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
						<p><?php esc_html_e('In your Google Tab Manager Workspace, near the top of the window, you will find your container ID, formatted as “GTM-XXXXXX“.', 'wpfnl'); ?></p>
					</span>
				</label>
				<div class="wpfnl-fields">
					<input type="text" name="wpfnl-gtm-container-id" id="wpfnl-gtm-container-id" value="<?php echo isset($this->gtm_settings['gtm_container_id']) ? sanitize_text_field($this->gtm_settings['gtm_container_id']) : '' ; ?>" />
				</div>
			</div>
			<div class="wpfnl-field-wrapper analytics-stats">
				<label>
					<?php esc_html_e('GTM Events', 'wpfnl'); ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
						<p><?php esc_html_e('Choose what events to track in your funnels via Google Tag Manager.', 'wpfnl'); ?></p>
					</span>
				</label>
				<div class="wpfnl-fields">
					<?php foreach( $this->gtm_events as $key => $events ) { ?>
						<span class="wpfnl-checkbox">
                        <input type="checkbox" name="wpfnl-gtm-events[]"  id="<?php echo $key; ?>-gtm-events" data-role="<?php echo $key; ?>"
							<?php if(isset($this->gtm_settings['gtm_events'][$key])){checked( $this->gtm_settings['gtm_events'][$key], 'true' );} ?>
						/>
                        <label for="<?php echo $key; ?>-gtm-events"><?php echo ucfirst($events); ?></label>
                    </span>
					<?php } ?>
				</div>
			</div>
		</div>



</div>
<!-- /settings-box -->