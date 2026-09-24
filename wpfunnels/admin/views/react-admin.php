<?php
/**
 * Mount point for the React single-page admin.
 *
 * The whole screen is rendered by src/admin — everything below the root div is
 * owned by React Router, so this view stays a container and nothing else.
 *
 * @package
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="rwpfnl-app">

	<?php

	/**
	 * Fires before the React admin root is printed.
	 *
	 * @since 3.13.2
	 */
	do_action( 'wpfunnels/before-react-admin-content' );
	?>

	<div id="rwpfnl-admin-root"></div>

	<?php

	/**
	 * Fires after the React admin root is printed.
	 *
	 * @since 3.13.2
	 */
	do_action( 'wpfunnels/after-react-admin-content' );
	?>

</div>
