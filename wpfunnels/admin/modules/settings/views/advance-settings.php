<?php
/**
 * Advanced settings panel.
 *
 * Each option is a self-describing row — label and helper copy on the left, the
 * control on the right — instead of the old label/tooltip pair. Rollback used to
 * be a second tab in here; it is its own settings panel now, see
 * views/rollback-settings.php.
 *
 * @package
 */

// Consent lives in the telemetry SDK's option, not in WPFunnels' general
// settings, so read it through the singleton. A site that has never answered
// the setup-wizard question returns null and renders as off, which matches the
// SDK's own "no consent yet, send nothing" behaviour.
$wpfnl_telemetry         = \WPFunnels\Tracking\Telemetry::instance();
$wpfnl_tracking_consent  = $wpfnl_telemetry ? 'yes' === $wpfnl_telemetry->get_consent_state() : false;
?>

<div class="wpfnl-settings-list">

	<div class="wpfnl-settings-list__row">
		<div class="wpfnl-settings-list__info">
			<h5 class="wpfnl-settings-list__title"><?php esc_html_e( 'Remove WPF Transient Cache', 'wpfnl' ); ?></h5>
			<p class="wpfnl-settings-list__desc"><?php esc_html_e( 'If you are facing issues such as not getting plugin updates or your license not working, clear the transient cache and try again.', 'wpfnl' ); ?></p>
		</div>

		<div class="wpfnl-settings-list__action">
			<div class="transient-btn-wrapper">
				<button class="clear-transients-btn clear-template" id="clear-transients" title="<?php esc_attr_e( 'Clear transient cache', 'wpfnl' ); ?>">
					<?php require WPFNL_DIR . '/admin/partials/icons/delete-transient-icon.php'; ?>
				</button>
				<span class="wpfnl-alert"></span>
			</div>
		</div>
	</div>

	<div class="wpfnl-settings-list__row">
		<div class="wpfnl-settings-list__info">
			<h5 class="wpfnl-settings-list__title"><?php esc_html_e( 'Disable Theme Styles in Funnel Pages', 'wpfnl' ); ?></h5>
			<p class="wpfnl-settings-list__desc"><?php esc_html_e( 'When enabled, your theme styles will not be loaded while editing funnel pages. WPFunnels will load its own default styles instead.', 'wpfnl' ); ?></p>
		</div>

		<div class="wpfnl-settings-list__action">
			<span class="wpfnl-switcher extra-sm no-title">
				<input type="checkbox" name="disable-theme-style" id="disable-theme-style" <?php checked( 'on', isset( $this->general_settings['disable_theme_style'] ) ? $this->general_settings['disable_theme_style'] : 'off' ); ?> />
				<label for="disable-theme-style"></label>
			</span>
		</div>
	</div>

	<div class="wpfnl-settings-list__row">
		<div class="wpfnl-settings-list__info">
			<h5 class="wpfnl-settings-list__title"><?php esc_html_e( 'Clear Funnel Data on Plugin Uninstall', 'wpfnl' ); ?></h5>
			<p class="wpfnl-settings-list__desc"><?php esc_html_e( 'All of your funnel data will be permanently deleted when you uninstall WPFunnels. If you enable this and delete the plugin, you cannot restore your saved data.', 'wpfnl' ); ?></p>
		</div>

		<div class="wpfnl-settings-list__action">
			<span class="wpfnl-switcher extra-sm no-title">
				<input type="checkbox" name="wpfnl-data-cleanup" id="wpfnl-data-cleanup" <?php checked( 'on', isset( $this->general_settings['uninstall_cleanup'] ) ? $this->general_settings['uninstall_cleanup'] : 'off' ); ?> />
				<label for="wpfnl-data-cleanup"></label>
			</span>
		</div>
	</div>

	<div class="wpfnl-settings-list__row">
		<div class="wpfnl-settings-list__info">
			<h5 class="wpfnl-settings-list__title"><?php esc_html_e( 'Help Us Improve Your Experience', 'wpfnl' ); ?></h5>
			<p class="wpfnl-settings-list__desc">
				<?php esc_html_e( 'Collect non-sensitive information from your website, such as the PHP version and features used, to help us fix bugs faster, make smarter decisions, and build features that actually matter to you.', 'wpfnl' ); ?>
				<a href="https://getwpfunnels.com/privacy-policy/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn More', 'wpfnl' ); ?></a>.
			</p>
		</div>

		<div class="wpfnl-settings-list__action">
			<span class="wpfnl-switcher extra-sm no-title">
				<input
					type="checkbox"
					name="wpfnl-allow-tracking"
					id="wpfnl-allow-tracking"
					data-initial="<?php echo $wpfnl_tracking_consent ? 'yes' : 'no'; ?>"
					<?php checked( true, $wpfnl_tracking_consent ); ?>
				/>
				<label for="wpfnl-allow-tracking"></label>
			</span>
		</div>
	</div>

</div>
