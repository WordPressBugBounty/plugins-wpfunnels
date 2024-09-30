<?php

namespace WPFunnels\Admin\Migrations;

/**
 * Class AbstractMigrations
 *
 * Abstract class that provides common functionality for migration classes.
 *
 * @package WPFunnels\Admin\Migrations
 * @since 3.5.0
 */
abstract class AbstractMigrations {

	/**
	 * Migration key.
	 *
	 * @var string
	 * @since 3.5.0
	 */
	public $key;


	/**
	 * Check if the migration should be enqueued.
	 *
	 * Determines if the migration process should be added to the queue.
	 *
	 * @return bool
	 *
	 * @since 3.5.0
	 */
	public function should_enqueue_migration_instance() {
		$is_migration_completed = $this->is_migration_completed();
		if ( 'completed' === $is_migration_completed ) {
			return false;
		}
		return true;
	}


	/**
	 * Check if the migration is completed.
	 *
	 * @return string The status of the migration.
	 *
	 * @since 3.5.0
	 */
	public function is_migration_completed() {
		return get_option( "wpfunnels_".$this->key."_status", '' );
	}


	/**
	 * Set the migration status.
	 *
	 * @param string $status The status to set.
	 *
	 * @since 3.5.0
	 */
	public function set_migration_status( $status ) {
		update_option( "wpfunnels_".$this->key."_status", $status );
	}


	/**
	 * Check if the migration is on the queue.
	 *
	 * @return bool True if the migration is running, false otherwise.
	 *
	 * @since 3.5.0
	 */
	public function is_on_queue() {
		return get_option( "wpfunnels_".$this->key."_status", '' ) === 'running';
	}


	/**
	 * Set the batch number.
	 *
	 * @param int $batch The batch number to set.
	 *
	 * @since 3.5.0
	 */
	public function set_batch( $batch ) {
		update_option( "wpfunnels_".$this->key."_batch", $batch );
	}


	/**
	 * Get the next batch to process.
	 *
	 * @return mixed The next batch to process.
	 *
	 * @since 3.5.0
	 */
	abstract function get_next_batch_to_process();
}
