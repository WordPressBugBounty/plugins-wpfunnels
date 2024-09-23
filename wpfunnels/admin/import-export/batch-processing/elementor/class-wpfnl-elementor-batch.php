<?php
/**
 * Elementor batch
 *
 * @package WPFunnels\Batch
 */

namespace WPFunnels\Batch;

use WPFunnels\Batch\Elementor\Wpfnl_Elementor_Source;

/**
 * Elementor batch
 *
 * @since 1.0.0
 */
class Wpfnl_Elementor_Batch extends Wpfnl_Background_Task {

	/**
	 * Image Process
	 *
	 * @var string
	 */
	protected $action = 'wpfunnels_elementor_import_process';

	/**
	 * Task
	 *
	 * @param mixed $item Elementor item.
	 * @inheritDoc
	 */
	protected function task( $item ) {
		$elementor_source = new Wpfnl_Elementor_Source();
		$elementor_source->import_single_template( $item );
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
