<?php
/**
 * View log settings
 * 
 * @package
 */
?>
<div class="log-field">
	<h4 class="settings-title"> <?php echo __('Log Settings', 'wpfnl'); ?> </h4>
	<div class="wpfnl-box">
		<div class="wpfnl-field-wrapper">
			<div class="log-view-select">
				<select name="wpfnl-log" id="wpfnl-log">
					<?php
						foreach ( $files as $key=>$file ) {
							echo "<option value='{$file}'>$file</option>";
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
		
		<!-- /field-wrapper -->
	</div>
</div>
