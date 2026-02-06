<?php
/**
 * View LMS settings
 * 
 * @package WPFunnels
 */

// Get available LMS providers
$available_providers = array();
$selected_provider = '';

if ( class_exists( '\WPFunnels\lms\settings\LMS_Settings_Helper' ) ) {
	$available_providers = \WPFunnels\lms\settings\LMS_Settings_Helper::get_available_lms_providers();
	$selected_provider = \WPFunnels\lms\settings\LMS_Settings_Helper::get_selected_lms_provider();
}

// If no providers available, show message
if ( empty( $available_providers ) ) {
	?>
	<div class="wpfnl-field-wrapper">
		<div class="wpfnl-notice wpfnl-notice-warning" style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 4px;">
			<p style="margin: 0; color: #856404; line-height: 1.5;">
				<strong style="display: block; margin-bottom: 5px;"><?php esc_html_e( 'No LMS Plugin Detected', 'wpfnl' ); ?></strong>
				<?php esc_html_e( 'Please install and activate either LearnDash or CreatorLMS to use LMS funnels.', 'wpfnl' ); ?>
			</p>
		</div>
	</div>
	<?php
	return;
}
?>

<div class="wpfnl-field-wrapper">
	<label>
		<?php esc_html_e( 'LMS Provider', 'wpfnl' ); ?>
		<span class="wpfnl-tooltip">
			<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
			<p><?php esc_html_e( 'Choose your LMS platform. This will be used for LMS funnels and course enrollment.', 'wpfnl' ); ?></p>
		</span>
	</label>
	<div class="wpfnl-fields">
		<select name="lms_provider" id="lms-provider-select" class="wpfnl-select">
			<option value=""><?php esc_html_e( 'Select LMS Provider', 'wpfnl' ); ?></option>
			<?php foreach ( $available_providers as $provider_id => $provider_data ) : ?>
				<option value="<?php echo esc_attr( $provider_id ); ?>" <?php selected( $selected_provider, $provider_id ); ?>>
					<?php echo esc_html( $provider_data['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
</div>

<?php if ( count( $available_providers ) > 1 ) : ?>
	<div class="wpfnl-notice wpfnl-notice-info">
		<svg width="17" height="17" fill="none" viewBox="0 0 17 17" xmlns="http://www.w3.org/2000/svg"><path fill="#F68524" stroke="#F68524" stroke-width=".2" d="M8.1.1c-4.422 0-8 3.578-8 8 0 4.422 3.578 8 8 8 4.422 0 8-3.578 8-8 0-4.422-3.578-8-8-8zm0 14.75A6.746 6.746 0 011.35 8.1c0-3.73 3.02-6.75 6.75-6.75 3.731 0 6.75 3.02 6.75 6.75 0 3.731-3.02 6.75-6.75 6.75z"/><path fill="#F68524" stroke="#F68524" stroke-width=".2" d="M8.1 11.855a.844.844 0 100-1.688.844.844 0 000 1.688zm0-7.728a.625.625 0 00-.625.625v4.025a.625.625 0 101.25 0V4.752a.625.625 0 00-.625-.625z"/></svg>
		
		<div class="wpfnl-notice-content">
			<p>
				<strong><?php esc_html_e( 'Multiple LMS Plugins Detected', 'wpfnl' ); ?></strong>
				<?php 
				$provider_names = array_map( function( $provider ) {
					return $provider['name'];
				}, $available_providers );
				printf( 
					esc_html__( 'You have %s installed. Select the one you want to use with WPFunnels.', 'wpfnl' ),
					implode( ' and ', $provider_names )
				);
				?>
			</p>
		</div>
	</div>
<?php endif; ?>
