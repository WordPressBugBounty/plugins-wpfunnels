<?php
/**
 * Gutenberg batch
 *
 * @package WPFunnels\Batch\Gutenberg
 */

namespace WPFunnels\Batch\Gutenberg;

use WPFunnels\Batch\Wpfnl_Background_Task;

/**
 * Gutenberg batch
 *
 * @since 1.0.0
 */
class Wpfnl_Gutenberg_Batch extends Wpfnl_Background_Task {


	/**
	 * Action

	 * @var string
	 */
	protected $action = 'wpfunnels_gutenberg_import_process';

	/**
	 * Task
	 *
	 * @param mixed $item Gutenburg batch.
	 *
	 * @inheritDoc
	 */
	protected function task( $item ) {
		if ( class_exists( 'Wpfnl_Gutenberg_Source' ) ) {
			$gutenberg_source = new Wpfnl_Gutenberg_Source();
			$gutenberg_source->import_single_template( $item );
		}

		return false;
	}
}
