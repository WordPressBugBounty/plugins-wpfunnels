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

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 *
	 * @since 1.0.0
	 */
	protected function complete() {
		parent::complete();
		/***
		 * Fires when import is completed
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpfunnels/wpfnl_import_complete' );
	}
}
