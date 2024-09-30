<?php

namespace WPFunnels\Admin\Migrations;

use WPFunnels\Admin\BatchProcessing\BatchProcessingController;

/**
 * Class MigrationManager
 *
 * Manages the migration process by scheduling and enqueuing migration instances.
 *
 * @package WPFunnels\Admin\Migrations
 * @since 3.5.0
 */
class MigrationManager {

	/**
	 * Batch processing controller instance.
	 *
	 * @var BatchProcessingController
	 */
	public $batchProcessingController;


	/**
	 * Array of migration instances.
	 *
	 * @var array
	 */
	public static $migration_instances = array(
		'order_stat_migration' => 'WPFunnels\\Admin\\Migrations\\OrderToStatTableMigration'
	);


	/**
	 * MigrationManager constructor.
	 *
	 * @param BatchProcessingController $batchProcessingController The batch processing controller instance.
	 * @since 3.5.0
	 */
	public function __construct( BatchProcessingController $batchProcessingController ) {
		$this->batchProcessingController = $batchProcessingController;
		$this->schedule_action();
	}


	/**
	 * Schedule migration actions.
	 *
	 * Iterates over migration instances and enqueues them if they should be processed.
	 *
	 * @since 3.5.0
	 */
	public function schedule_action () {
		foreach ( self::$migration_instances as $key => $instance_class_name ) {
			$instance = new $instance_class_name();
			if ( $instance->should_enqueue_migration_instance( $instance_class_name ) ) {
				$this->batchProcessingController->enqueue_processor( $key, $instance_class_name );
			}
		}
	}
}
