<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add "AI Assistant" to the shared top nav every other WPFunnels admin page
// uses (admin/partials/dashboard-nav.php), so this page reads as part of the
// plugin rather than a standalone island.
add_filter(
	'wpfnl_dashboard_nav_lists',
	function ( $items ) {
		$items['ai_assistant'] = [
			'title' => __( 'AI Assistant', 'wpfnl' ),
			'icon'  => 'ai-assistant-icon',
			'page'  => defined( 'WPFNL_AI_FUNNEL_PAGE_SLUG' ) ? WPFNL_AI_FUNNEL_PAGE_SLUG : 'wpfnl-ai-funnel',
		];
		return $items;
	}
);

?>

<div class="wpfnl-dashboard wpfnl-ai-funnel-admin-page">
	<nav class="wpfnl-dashboard__nav">
		<?php require_once WPFNL_DIR . '/admin/partials/dashboard-nav.php'; ?>
	</nav>

	<div class="dashboard-nav__content">
		<div id="wpfnl-ai-copilot"></div>
	</div>
</div>
