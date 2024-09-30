<?php

namespace WPFunnels\Admin\BatchProcessing;


/**
 * This class handles batch processing of data in a deferred way, abstracting the scheduling and error handling.
 *
 * Usage:
 * 1. Create a class that implements BatchProcessorInterface.
 * 2. Invoke the 'enqueue_processor' method with the processor class name when there's data to process.
 *
 * Processing will be done in batches via scheduled actions. Processors will be dequeued once they finish processing.
 * Failed batches will be retried.
 *
 * Credit: Inspired by WooCommerce's BatchProcessingController.
 *
 * @package WPFunnels\Admin\BatchProcessing
 * @since 3.5.0
 */
class BatchProcessingController {

	/**
	 * Action name for scheduling pending batch processes.
	 */
	const WPFUNNELS_SCHEDULE_PENDING_ACTION_NAME = 'wpfunnels_schedule_pending_batch_processes';

	/**
	 * Action name for processing a single batch.
	 */
	const WPFUNNELS_SINGLE_BATCH_ACTION_NAME = 'wpfunnels_batch_process';


	/**
	 * BatchProcessingController constructor.
	 *
	 * Initializes the batch processing controller and sets up the necessary actions.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		add_action(
			self::WPFUNNELS_SCHEDULE_PENDING_ACTION_NAME,
			function () {
				$this->handle_pending_actions();
			}
		);

		add_action(
			self::WPFUNNELS_SINGLE_BATCH_ACTION_NAME,
			function ( $processor_class_name ) {
				$this->process_next_batch_for_single_processor( $processor_class_name );
			}
		);
	}


	/**
	 * Enqueue a processor for batch processing.
	 *
	 * @param string $key The key for the processor.
	 * @param string $instance_class_name The class name of the processor instance.
	 *
	 * @since 3.5.0
	 */
	public function enqueue_processor( $key, $instance_class_name ) {
		$enqueued_processes = get_option( 'wpfunnels_pending_batch_processes', array() );
		if ( ! in_array( $key, array_keys( $enqueued_processes ), true ) ) {
			$pending_updates[$key] = $instance_class_name;
			$this->set_enqueued_processors( $pending_updates );
		}
		$this->schedule_action();
	}



	/**
	 * Dequeue a processor from batch processing.
	 *
	 * @param string $processor_class_name The class name of the processor instance.
	 *
	 * @since 3.5.0
	 */
	private function dequeue_processor( $processor_class_name ) {
		$pending_processes = $this->get_enqueued_processors();
		if ( in_array( $processor_class_name, $pending_processes, true ) ) {
			$pending_processes = array_diff( $pending_processes, array( $processor_class_name ) );
			$this->set_enqueued_processors( $pending_processes );
		}
	}


	/**
	 * Set the enqueued processors.
	 *
	 * @param array $pending_updates The array of pending updates.
	 *
	 * @since 3.5.0
	 */
	public function set_enqueued_processors( $pending_updates ) {
		update_option( 'wpfunnels_pending_batch_processes', $pending_updates, false );
	}


	/**
	 * Get the enqueued processors.
	 *
	 * @return array The array of enqueued processors.
	 *
	 * @since 3.5.0
	 */
	public function get_enqueued_processors() {
		return get_option('wpfunnels_pending_batch_processes', array());
	}


	/**
	 * Schedule the action for processing pending batch processes.
	 *
	 * @since 3.5.0
	 */
	public function schedule_action() {
		$time = time();
		if ( ! as_has_scheduled_action( self::WPFUNNELS_SCHEDULE_PENDING_ACTION_NAME ) ) {
			as_schedule_single_action(
				$time,
				self::WPFUNNELS_SCHEDULE_PENDING_ACTION_NAME,
				array(),
				'wpfunnels_batch_processes',
				true
			);
		}
	}


	/**
	 * Check if a processor is scheduled.
	 *
	 * @param string $processor_class_name The class name of the processor instance.
	 * @return bool True if the processor is scheduled, false otherwise.
	 *
	 * @since 3.5.0
	 */
	public function is_scheduled( $processor_class_name ) {
		return as_has_scheduled_action( self::WPFUNNELS_SINGLE_BATCH_ACTION_NAME, array( $processor_class_name ) );
	}



	/**
	 * Check if a processor is scheduled.
	 *
	 * @param string $processor_class_name The class name of the processor instance.
	 * @return bool True if the processor is scheduled, false otherwise.
	 *
	 * @since 3.5.0
	 */
	private function schedule_batch_processing( $processor_class_name )  {
		as_schedule_single_action( time(), self::WPFUNNELS_SINGLE_BATCH_ACTION_NAME, array( $processor_class_name ) );
	}


	/**
	 * Check if a processor is enqueued.
	 *
	 * @param string $processor_class_name The class name of the processor instance.
	 * @return bool True if the processor is enqueued, false otherwise.
	 *
	 * @since 3.5.0
	 */
	private function is_enqueued( $processor_class_name ) {
		return in_array( $processor_class_name, $this->get_enqueued_processors(), true );
	}


	/**
	 * Get the instance of a processor.
	 *
	 * @param string $processor_class_name The class name of the processor instance.
	 * @return mixed The processor instance or false if the class does not exist.
	 *
	 * @since 3.5.0
	 */
	private function get_processor_instance( $processor_class_name ) {
		if ( class_exists( $processor_class_name ) ) {
			$processor = new $processor_class_name();
			return $processor;
		}
		return false;
	}


	/**
	 * Handle pending actions by scheduling batch processing for each enqueued processor.
	 *
	 * @since 3.5.0
	 */
	private function handle_pending_actions() {
		$pending_processes = $this->get_enqueued_processors();
		if ( empty( $pending_processes ) ) {
			return;
		}
		foreach ( $pending_processes as $processor_class_name ) {
			if ( ! $this->is_scheduled( $processor_class_name ) ) {
				$this->schedule_batch_processing( $processor_class_name );
			}
		}
		$this->schedule_action();
	}


	/**
	 * Process the next batch for a single processor.
	 *
	 * @param string $processor_class_name The class name of the processor instance.
	 *
	 * @since 3.5.0
	 */
	private function process_next_batch_for_single_processor( $processor_class_name ) {
		if ( ! $this->is_enqueued( $processor_class_name ) ) {
			return;
		}
		$batch_processor = $this->get_processor_instance( $processor_class_name );
		$batch_processor->process_batch();
		$still_pending 	 = $batch_processor->should_enqueue_migration_instance();
		if ( $still_pending ) {
			$this->schedule_batch_processing( $processor_class_name );
		} else {
			$this->dequeue_processor( $processor_class_name );
		}
	}
}
