<?php

namespace WPFunnels\Admin\Migrations;

/**
 * Class StatStatusSyncMigration
 *
 * Syncs the status of existing wpfnl_stats rows that are stuck on "pending"
 * with the actual WooCommerce order status.
 *
 * Uses an ID-based cursor to move through all pending rows systematically,
 * ensuring no rows are skipped even if some orders are genuinely still pending.
 *
 * @package WPFunnels\Admin\Migrations
 * @since 3.5.1
 */
class StatStatusSyncMigration extends AbstractMigrations
{

    /**
     * Migration key
     *
     * @var string
     * @since 3.5.1
     */
    public $key = 'stat_status_sync';


    /**
     * Batch size – number of rows to process per batch.
     *
     * @var int
     */
    private $batch_size = 50;


    /**
     * Option key for storing the last processed row ID (cursor).
     *
     * @var string
     */
    private $cursor_option = 'wpfunnels_stat_status_sync_last_id';


    /**
     * Get the next batch to process.
     *
     * @return int|false
     * @since 3.5.1
     */
    public function get_next_batch_to_process()
    {
        if (!$this->is_on_queue()) {
            return get_option("wpfunnels_{$this->key}_batch", '1');
        }
        return false;
    }


    /**
     * Run the migration directly.
     *
     * Processes one batch per admin page load. Uses an ID-based cursor
     * to track progress, so it moves through ALL pending rows even if
     * some WooCommerce orders are genuinely still pending.
     *
     * @since 3.5.1
     */
    public function run()
    {
        // Already completed — do nothing.
        if ('completed' === $this->is_migration_completed()) {
            return;
        }

        $this->set_migration_status('running');
        $this->process_batch();
    }


    /**
     * Process a single batch.
     *
     * Queries rows in wpfnl_stats with status = 'pending' AND id > cursor,
     * looks up the real WooCommerce order status, and updates the stats table.
     *
     * @since 3.5.1
     */
    public function process_batch()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wpfnl_stats';
        $last_id = (int) get_option($this->cursor_option, 0);

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, order_id FROM {$table} WHERE status = 'pending' AND id > %d ORDER BY id ASC LIMIT %d",
                $last_id,
                $this->batch_size
            )
        );

        // No more rows beyond the cursor → migration is done.
        if (empty($rows)) {
            $this->set_migration_status('completed');
            delete_option($this->cursor_option);
            return;
        }

        $max_id = $last_id;

        foreach ($rows as $row) {
            // Always advance the cursor, even if we skip this row.
            if ((int) $row->id > $max_id) {
                $max_id = (int) $row->id;
            }

            $order = wc_get_order($row->order_id);

            if (!$order) {
                // Order no longer exists — mark as 'cancelled' to avoid re-processing.
                $wpdb->update(
                    $table,
                    array('status' => 'cancelled'),
                    array('id' => $row->id)
                );
                continue;
            }

            $new_status = $order->get_status();

            // If still truly pending in WooCommerce, skip but cursor still advances.
            if ('pending' === $new_status) {
                continue;
            }

            $update_data = array('status' => $new_status);

            // Record the paid date for completed/processing orders.
            if ('completed' === $new_status || 'processing' === $new_status) {
                $paid_date = $order->get_date_paid();
                if ($paid_date) {
                    $update_data['paid_date'] = $paid_date->date('Y-m-d H:i:s');
                }
            }

            $wpdb->update(
                $table,
                $update_data,
                array('id' => $row->id)
            );
        }

        // Save the cursor so the next batch starts after this point.
        update_option($this->cursor_option, $max_id, false);
    }
}
