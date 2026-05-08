<?php
/**
 * Divi 5 background import batch processor.
 *
 * Mirrors Wpfnl_Divi_Batch but uses the Divi 5 source class which handles
 * JSON block content format instead of shortcode strings.
 *
 * @package WPFunnels\Batch
 * @since   2.9.1
 */

namespace WPFunnels\Batch;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use WPFunnels\Batch\DiviV5\Wpfnl_Divi5_Source;

class Wpfnl_Divi5_Batch extends Wpfnl_Background_Task {

	/** @var string Unique action key for the background queue. */
	protected $action = 'wpfunnels_divi5_import_process';

	/**
	 * Process a single import item.
	 *
	 * @param array{post_id: int, content: string|array} $item
	 * @return false  Always return false to remove item from queue.
	 */
	protected function task( $item ) {
		$source = new Wpfnl_Divi5_Source();
		$source->import_single_template(
			(int) ( $item['post_id'] ?? 0 ),
			$item['content'] ?? ''
		);
		return false;
	}

	/** @inheritDoc */
	protected function complete() {
		parent::complete();
		do_action( 'wpfunnels/wpfnl_import_complete' );
	}
}
