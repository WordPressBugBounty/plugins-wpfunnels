<?php
/**
 * View log settings
 * 
 * @package
 */
?>
<div class="log-field">
	<h4 class="settings-title"> <?php echo __('Log Settings', 'wpfnl'); ?> </h4>

	<!-- Enable log -->
	<div class="wpfnl-box enable-log-wrapper">
		<div class="wpfnl-field-wrapper analytics-stats">
			<label class="log-status-label"><?php esc_html_e('Enable Logs', 'wpfnl'); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
					<p><?php esc_html_e('Enable logger status to save any log', 'wpfnl'); ?></p>
				</span>
			</label>
			<div class="wpfnl-fields enable-log-status">
				<span class="wpfnl-switcher extra-sm no-title">
					<input type="checkbox" name="enable-log-status" id="enable-log-status" <?php if( $this->general_settings['enable_log_status'] === 'on' ){ echo 'checked';} ?> />
					<label for="enable-log-status"></label>
				</span>
			</div>
		</div>
	</div>

	<div class="wpfnl-box log-options-wrapper" style="<?php echo ($this->general_settings['enable_log_status'] === 'on') ? '' : 'display:none'; ?>">
		<div class="wpfnl-field-wrapper">
			<div class="log-view-select">
				<select name="wpfnl-log" id="wpfnl-log">
					<?php
						if (empty($files)) {
							echo "<option value='' disabled selected>" . __('No log files available', 'wpfnl') . "</option>";
						} else {
							foreach ($files as $key => $file) {
								echo "<option value='{$file}'>$file</option>";
							}
						}
					?>
				</select>
			</div>

			<div class="log-view-btn">
				<?php
					if( count($files) ){
						echo sprintf(
							'<a data-placeholder-text="View" href="#" data-placeholder-url="%1s" class="wpfnl-log-view btn-default">%2s <span class="wpfnl-loader"></span></a>',
							wp_nonce_url( admin_url( 'admin-post.php?action=wpfunnels_rollback&version=VERSION' ), 'wpfunnels_rollback' ),
							__( 'View', 'wpfnl' )
						);

						echo sprintf(
							'<a data-placeholder-text="Delete" href="#" data-placeholder-url="%1s" class="wpfnl-log-delete btn-default">%2s <span class="wpfnl-loader"></span></a>',
							wp_nonce_url( admin_url( 'admin-post.php?action=wpfunnels_rollback&version=VERSION' ), 'wpfunnels_rollback' ),
							__( 'Delete', 'wpfnl' )
						);
					}
				?>
			</div>
		</div>

		<div id="log-viewer">
			<pre id="wpfnl-log-content"></pre>
		</div>
	</div>
</div>
