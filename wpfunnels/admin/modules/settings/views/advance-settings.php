<?php
/**
 * Advance settings view
 *
 * @package
 */
?>
<div class="basic-tools-field">
	<h4 class="settings-title"> <?php esc_html_e('Basic Tools', 'wpfnl'); ?> </h4>
	<div class="wpfnl-box">
		<div class="wpfnl-field-wrapper">
			<label>
				<?php esc_html_e( 'Remove WPF Transient Cache', 'wpfnl' ); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
					<p><?php esc_html_e('If you are facing issues such as not getting plugin updates or license not working, clear the transient cache and try again.', 'wpfnl'); ?></p>
				</span>
			</label>

			<div class="wpfnl-fields">
				<button class="btn-default clear-template" id="clear-transients">
					<span class="sync-icon"><?php require WPFNL_DIR . '/admin/partials/icons/sync-icon.php'; ?></span>
					<span class="check-icon"><?php require WPFNL_DIR . '/admin/partials/icons/check-icon.php'; ?></span>
					<?php esc_html_e('Delete transients', 'wpfnl'); ?>
				</button>
				<span class="wpfnl-alert"></span>
			</div>

		</div>

		<div class="wpfnl-field-wrapper analytics-stats">
			<label><?php esc_html_e('Disable Theme Styles in Funnel Pages', 'wpfnl'); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
					<p><?php esc_html_e('When editing funnel pages, Enabling this option will mean the default theme styles will not be loaded when editing funnel pages and rather load the default style by WPFunnels.', 'wpfnl'); ?></p>
				</span>
			</label>

			<div class="wpfnl-fields">
				<span class="wpfnl-checkbox no-title">
					<input type="checkbox" name="disable-theme-style"  id="disable-theme-style" <?php if( $this->general_settings['disable_theme_style'] == 'on' ){ echo 'checked';} ?> />
					<label for="disable-theme-style"></label>
				</span>
			</div>
		</div>

		<div class="wpfnl-field-wrapper analytics-stats">
			<label><?php esc_html_e('Enable Log Status', 'wpfnl'); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
					<p><?php esc_html_e('Enable logger status to save any log', 'wpfnl'); ?></p>
				</span>
			</label>

			<div class="wpfnl-fields">
				<span class="wpfnl-checkbox no-title">
					<input type="checkbox" name="enable-log-status"  id="enable-log-status" <?php if( $this->general_settings['enable_log_status'] == 'on' ){ echo 'checked';} ?> />
					<label for="enable-log-status"></label>
				</span>
			</div>
		</div>

		<div class="wpfnl-field-wrapper">
			<label class="has-tooltip">
			<?php
				esc_html_e('Clear Funnel Data on Plugin Uninstall', 'wpfnl');
				?>
				<span class="wpfnl-tooltip">
					<?php
					require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php';
					?>
					<p><?php
					esc_html_e('All the funnel data will be cleared when you uninstall the plugin', 'wpfnl');
					?></p>
				</span>
			</label>
			<div class="wpfnl-fields">
				<span class="wpfnl-checkbox no-title">
					<input type="checkbox" name="wpfnl-data-cleanup"  id="wpfnl-data-cleanup" <?php
					echo $this->general_settings['uninstall_cleanup'] == 'on' ? 'checked' : '';
					?> />
					<label for="wpfnl-data-cleanup"></label>
				</span>
			</div>
		</div>
	</div>
</div>


<div class="rollback-field">
	<h4 class="settings-title"> <?php esc_html_e('Rollback Settings', 'wpfnl'); ?> </h4>
	<div class="wpfnl-box">
		<div class="wpfnl-field-wrapper">
			<label><?php esc_html_e('Current Version', 'wpfnl'); ?></label>
			<div class="wpfnl-fields">
				<b>v<?php echo WPFNL_VERSION; ?></b>
			</div>
		</div>
		<!-- /field-wrapper -->

		<div class="wpfnl-field-wrapper wpfnl-align-top">
			<label><?php esc_html_e('Rollback to older Version', 'wpfnl'); ?></label>
			<div class="wpfnl-fields">
				<select name="wpfnl-rollback" id="wpfnl-rollback">
					<?php
						foreach ( $rollback_versions as $version ) {
							echo "<option value='{$version}'>$version</option>";
						}
					?>
				</select>
				<?php
					echo sprintf(
						'<a data-placeholder-text="%s v{VERSION}" href="#" data-placeholder-url="%s" class="wpfnl-button-spinner wpfnl-rollback-button btn-default">%s</a>',
                        __( 'Reinstall', 'wpfnl' ),
                        wp_nonce_url( admin_url( 'admin-post.php?action=wpfunnels_rollback&version=VERSION' ), 'wpfunnels_rollback' ),
						__( 'Reinstall', 'wpfnl' )
					);
				?>
				<span class="hints wpfnl-error">
                    <?php
                    _e(
                            sprintf(
                                    '%sWarning:%s Please backup your database before rolling back to an older version of the plugin.',
                        '<b>', '</b>'
                    ), 'wpfnl' );
                    ?>
				</span>
			</div>
		</div>
		<!-- /field-wrapper -->
	</div>
</div>
