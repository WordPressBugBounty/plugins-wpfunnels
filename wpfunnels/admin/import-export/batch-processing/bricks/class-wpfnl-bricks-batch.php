<?php
/**
 * Bricks batch
 *
 * @package WPFunnels\Batch
 * @since 3.3.2
 */

namespace WPFunnels\Batch;

use WPFunnels\Batch\Bricks\Wpfnl_Bricks_Source;

/**
 * Bricks batch
 *
 * @package
 * @since 3.3.2
 */
class Wpfnl_Bricks_Batch extends Wpfnl_Background_Task {

	/**
	 * Image Process
	 *
	 * @var string
	 * @since 3.3.2
	 */
	protected $action = 'wpfunnels_bricks_import_process';

	/**
	 * Task
	 *
	 * @param mixed $item Bricks module item.
	 * @inheritDoc
	 * @since 3.3.2
	 */
	protected function task( $item ) {
		$elementor_source = new Wpfnl_Bricks_Source();
		$elementor_source->import_single_template( $item );
		return false;
	}
}
