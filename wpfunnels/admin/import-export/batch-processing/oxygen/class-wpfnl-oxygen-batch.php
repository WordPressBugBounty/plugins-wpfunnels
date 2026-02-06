<?php
/**
 * Oxygen batch
 *
 * @package WPFunnels\Batch\Oxygen
 */

namespace WPFunnels\Batch\Oxygen;

use WPFunnels\Batch\Wpfnl_Background_Task;

/**
 * Oxygen batch
 *
 * @since 1.0.0
 */
class Wpfnl_Oxygen_Batch extends Wpfnl_Background_Task {


	/**
	 * Action
	 *
	 * @var string
	 */
	protected $action = 'wpfunnels_gutenberg_import_process';

	/**
	 * Task
	 *
	 * @param mixed $item Oxygen Item.
	 * @inheritDoc
	 */
	protected function task( $item ) {
		if ( class_exists( 'Wpfnl_Gutenberg_Source' ) ) {
			$gutenberg_source = new Wpfnl_Oxygen_Source();
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
