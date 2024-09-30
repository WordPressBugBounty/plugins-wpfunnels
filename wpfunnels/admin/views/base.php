<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wpfnl-admin-views-wrapper">

	<?php

	/**
	 * Fires before the admin content is rendered
	 *
	 * @since 3.1.7
	 */
	do_action('wpfunnels/before-admin-content');
	?>

	<div id="wpfnl-admin-app"></div>

	<?php

	/**
	 * Fires after the admin content is rendered
	 *
	 * @since 3.1.7
	 */
	do_action('wpfunnels/after-admin-content');
	?>

</div>
