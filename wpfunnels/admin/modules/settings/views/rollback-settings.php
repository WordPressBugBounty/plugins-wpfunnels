<?php
/**
 * Rollback settings panel.
 *
 * Split out of the Advanced panel's second tab so it is reachable directly from
 * the settings nav. Reinstalling is a one-click admin-post action, so this panel
 * opts out of the global "Save changes" button (see data-save="false" on its nav
 * item in views/view.php).
 *
 * @var array $rollback_versions Older versions available on WordPress.org, newest first.
 *
 * @package
 */

$rollback_versions = isset( $rollback_versions ) ? $rollback_versions : $this->get_roll_back_versions();
?>

<div class="wpfnl-settings-list">

	<div class="wpfnl-settings-list__row">
		<div class="wpfnl-settings-list__info">
			<h5 class="wpfnl-settings-list__title"><?php esc_html_e( 'Current Version', 'wpfnl' ); ?></h5>
			<p class="wpfnl-settings-list__desc"><?php esc_html_e( 'The version of WPFunnels currently running on your site.', 'wpfnl' ); ?></p>
		</div>

		<div class="wpfnl-settings-list__action">
			<span class="wpfnl-settings-list__value">v<?php echo esc_html( WPFNL_VERSION ); ?></span>
		</div>
	</div>

	<div class="wpfnl-settings-list__row">
		<div class="wpfnl-settings-list__info">
			<h5 class="wpfnl-settings-list__title"><?php esc_html_e( 'Rollback to an Older Version', 'wpfnl' ); ?></h5>
			<p class="wpfnl-settings-list__desc"><?php esc_html_e( 'Reinstall a previous release of WPFunnels if the current one is not working as expected. Please back up your database before rolling back.', 'wpfnl' ); ?></p>
		</div>

		<div class="wpfnl-settings-list__action">
			<?php if ( ! empty( $rollback_versions ) ) { ?>
				<select class="rollback-options" name="wpfnl-rollback" id="wpfnl-rollback">
					<?php foreach ( $rollback_versions as $version ) { ?>
						<option value="<?php echo esc_attr( $version ); ?>"><?php echo esc_html( $version ); ?></option>
					<?php } ?>
				</select>
				<?php
				printf(
					'<a href="%1$s" class="wpfnl-rollback-button">%2$s</a>',
					esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpfunnels_rollback&version=VERSION' ), 'wpfunnels_rollback' ) ),
					esc_html__( 'Reinstall', 'wpfnl' )
				);
				?>
			<?php } else { ?>
				<span class="wpfnl-settings-list__value"><?php esc_html_e( 'No older versions available', 'wpfnl' ); ?></span>
			<?php } ?>
		</div>
	</div>

</div>

<div class="hints wpfnl-error wpfnl-settings-list__hint">
	<?php require WPFNL_DIR . '/admin/partials/icons/rollback-error-icon.php'; ?>
	<?php esc_html_e( 'Warning: Please backup your database before rolling back to an older version of the plugin.', 'wpfnl' ); ?>
</div>
