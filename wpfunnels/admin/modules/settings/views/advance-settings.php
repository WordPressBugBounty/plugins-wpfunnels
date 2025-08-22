<?php
/**
 * Advanced Settings with Tabs
 *
 * @package
 */
?>

<div class="settings-tab-container">
    <ul class="wpfnl-general-settings-tab-nav">
        <li class="inner-tab active" data-tab="tab-basic-tools"><?php esc_html_e('Basic Tools', 'wpfnl'); ?></li>
        <li class="inner-tab" data-tab="tab-rollback"><?php esc_html_e('Rollback Settings', 'wpfnl'); ?></li>
    </ul>
</div>

<!-- Basic Tools Tab -->
<div class="wpfnl-tab-content active" id="tab-basic-tools">
	<div class="wpfnl-box basic-tools-field">
		<div class="wpfnl-field-wrapper transient-cash-wrapper">
			<label>
				<?php esc_html_e( 'Remove WPF Transient Cache', 'wpfnl' ); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
					<p><?php esc_html_e('If you are facing issues such as not getting plugin updates or license not working, clear the transient cache and try again.', 'wpfnl'); ?></p>
				</span>
			</label>
			<div class="wpfnl-fields remove-transients">
				<div class="transient-btn-wrapper">
					<button class="clear-transients-btn clear-template" id="clear-transients">
						<?php require WPFNL_DIR . '/admin/partials/icons/delete-transient-icon.php'; ?>
					</button>
					<span class="wpfnl-alert"></span>
				</div>
			</div>
		</div>

		<div class="wpfnl-field-wrapper analytics-stats">
			<label><?php esc_html_e('Disable Theme Styles in Funnel Pages', 'wpfnl'); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
					<p><?php esc_html_e('When editing funnel pages, Enabling this option will mean the default theme styles will not be loaded when editing funnel pages and rather load the default style by WPFunnels.', 'wpfnl'); ?></p>
				</span>
			</label>
			<div class="wpfnl-fields disable-theme-style">
				<span class="wpfnl-switcher extra-sm no-title">
					<input type="checkbox" name="disable-theme-style" id="disable-theme-style" <?php if( $this->general_settings['disable_theme_style'] == 'on' ){ echo 'checked';} ?> />
					<label for="disable-theme-style"></label>
				</span>
			</div>
		</div>

		<div class="wpfnl-field-wrapper">
			<label class="has-tooltip">
				<?php esc_html_e('Clear Funnel Data on Plugin Uninstall', 'wpfnl'); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
					<p><?php esc_html_e('All the funnel data will be cleared when you uninstall the plugin', 'wpfnl'); ?></p>
				</span>
			</label>
			<div class="wpfnl-fields clear-funnel-data">
				<span class="wpfnl-switcher extra-sm no-title">
					<input type="checkbox" name="wpfnl-data-cleanup" id="wpfnl-data-cleanup" <?php echo $this->general_settings['uninstall_cleanup'] == 'on' ? 'checked' : ''; ?> />
					<label for="wpfnl-data-cleanup"></label>
				</span>
			</div>
		</div>
	</div>
</div>

<!-- Rollback Settings Tab -->
<div class="wpfnl-tab-content" id="tab-rollback">
	<div class="wpfnl-box rollback-field">
		<div class="wpfnl-field-wrapper">
			<label><?php esc_html_e('Current Version', 'wpfnl'); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
					<p><?php esc_html_e('This is the current version of your plugin', 'wpfnl'); ?></p>
				</span>
			</label>
			<!--  -->
			<div class="wpfnl-fields">
				v<?php echo WPFNL_VERSION; ?>
			</div>
		</div>

		<div class="wpfnl-field-wrapper">
			<label><?php esc_html_e('Rollback to older Version', 'wpfnl'); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/settings-tooltip-icon.php'; ?>
					<p><?php esc_html_e('You can roll back to our older versions', 'wpfnl'); ?></p>
				</span>
			</label>
			<div class="wpfnl-fields">
				<select class="rollback-options" name="wpfnl-rollback" id="wpfnl-rollback">
					<?php foreach ( $rollback_versions as $version ) {
						echo "<option value='{$version}'>$version</option>";
					} ?>
				</select>
				<?php
					echo sprintf(
						'<a href="%s" class="wpfnl-rollback-button">%s</a>',
						wp_nonce_url( admin_url( 'admin-post.php?action=wpfunnels_rollback&version=VERSION' ), 'wpfunnels_rollback' ),
						__( 'Reinstall', 'wpfnl' )
					);
				?>
			</div>
		</div>
		<div class="hints wpfnl-error">
			<?php require WPFNL_DIR . '/admin/partials/icons/rollback-error-icon.php'; ?>
			<?php
			_e(
				sprintf(
					'Warning: Please backup your database before rolling back to an older version of the plugin.'
				),
				'wpfnl'
			);
			?>
		</div>
	</div>
</div>
