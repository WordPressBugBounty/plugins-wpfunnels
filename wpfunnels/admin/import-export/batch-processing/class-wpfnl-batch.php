<?php
/**
 * Batch processing
 *
 * @package WPFunnels\Batch\Elementor
 */

namespace WPFunnels\Batch\Elementor;

use WPFunnels\Batch\Divi\Wpfnl_Divi_Source;
use WPFunnels\Batch\Gutenberg\Wpfnl_Gutenberg_Batch;
use WPFunnels\Batch\Gutenberg\Wpfnl_Gutenberg_Source;
use WPFunnels\Batch\Wpfnl_Divi_Batch;
use WPFunnels\Batch\Wpfnl_Elementor_Batch;
use WPFunnels\Batch\Wpfnl_Bricks_Batch;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
/**
 * Batch processing
 *
 * @since 1.0.1
 */
class Wpfnl_Batch {

	use SingletonTrait;

	/**
	 * Elementor batch instance
	 *
	 * @var Wpfnl_Elementor_Batch
	 */
	protected $elementor_batch;

	/**
	 * Elementor source object
	 *
	 * @var Wpfnl_Elementor_Source
	 */
	protected $elementor_source;

	/**
	 * Gutenberg batch instance
	 *
	 * @var Wpfnl_Gutenberg_Batch
	 */
	protected $gutenberg_batch;

	/**
	 * Gutenberg source object
	 *
	 * @var Wpfnl_Gutenberg_Source
	 */
	protected $gutenberg_source;

	/**
	 * Divi batch instance
	 *
	 * @var Wpfnl_Divi_Batch
	 */
	protected $divi_batch;

	/**
	 * Bricks batch instance
	 *
	 * @var Wpfnl_Bricks_Batch
	 */
	protected $bricks_batch;

	/**
	 * Divi source object
	 *
	 * @var Wpfnl_Divi_Source
	 */
	protected $divi_source;

	/**
	 * Bricks source object
	 *
	 * @var Wpfnl_Bricks_Source
	 */
	protected $bricks_source;


	/**
	 * Oxygen source object
	 *
	 * @var Oxygen source
	 */
	protected $oxygen_source;

	/**
	 * Class constructor.
	 *
	 * Initializes the appropriate batch object based on the page builder type.
	 * Sets up the 'wpfunnels_after_step_import' action hook for processing.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$page_builder = Wpfnl_functions::get_builder_type();

		if ( 'elementor' === $page_builder ) {
			$this->elementor_batch = new Wpfnl_Elementor_Batch();
		}

		if ( 'gutenberg' === $page_builder ) {
			$this->gutenberg_batch = new Wpfnl_Gutenberg_Batch();
		}

		if ( 'divi-builder' === $page_builder ) {
			$this->divi_batch = new Wpfnl_Divi_Batch();
		}

		if ( 'bricks' === $page_builder ) {
			$this->bricks_batch = new Wpfnl_Bricks_Batch();
		}

		add_action( 'wpfunnels_after_step_import', array( $this, 'start_processing' ), 10, 2 );
	}

	/**
	 * Start the batch import process
	 *
	 * @param int    $step_id funnel step id.
	 * @param string $builder active page builder.
	 *
	 * @since 1.0.0
	 */
	public function start_processing( $step_id = 0, $builder = 'elementor' ) {
		if ( $step_id && !Wpfnl_functions::maybe_duplicate_step( $step_id ) ) {
			if ( 'elementor' === $builder && class_exists( '\Elementor\Plugin' ) ) {
				$this->elementor_batch->push_to_queue( $step_id );
				$this->elementor_batch->save()->dispatch();
			}

			if ( 'gutenberg' === $builder ) {
				$this->gutenberg_batch->push_to_queue( $step_id );
				$this->gutenberg_batch->save()->dispatch();
			}

			if ( 'divi-builder' === $builder ) {
				$this->divi_batch->push_to_queue( $step_id );
				$this->divi_batch->save()->dispatch();
			}

			if ( 'bricks' === $builder ) {
				$this->bricks_batch->push_to_queue( $step_id );
				$this->bricks_batch->save()->dispatch();
			}
		}
	}
}
