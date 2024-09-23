<?php
/**
 * Divi batch
 *
 * @package WPFunnels\Batch
 */

namespace WPFunnels\Batch;

use WPFunnels\Batch\Divi\Wpfnl_Divi_Source;

/**
 * Divi batch
 *
 * @package
 */
class Wpfnl_Divi_Batch extends Wpfnl_Background_Task {

	/**
	 * Image Process
	 *
	 * @var string
	 */
	protected $action = 'wpfunnels_divi_import_process';

	/**
	 * Task
	 *
	 * @param mixed $item Divi module item.
	 * @inheritDoc
	 */
	protected function task( $item ) {
		$elementor_source = new Wpfnl_Divi_Source();
		$elementor_source->import_single_template( $item );
		return false;
	}
}
