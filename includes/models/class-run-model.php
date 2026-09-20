<?php
/**
 * Run Model Class
 *
 * Persistence for change runs (history) and their per-product items.
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Run_Model Class
 */
class Bulk_Pricer_Run_Model
{
    // Run statuses
    const STATUS_STAGED    = 'staged';     // CSV import waiting for confirmation
    const STATUS_SCHEDULED = 'scheduled';  // Waiting for its scheduled time
    const STATUS_RUNNING   = 'running';    // Being applied (or interrupted part-way)
    const STATUS_APPLIED   = 'applied';
    const STATUS_REVERTING = 'reverting';
    const STATUS_REVERTED  = 'reverted';
    const STATUS_CANCELLED = 'cancelled';

    // Item statuses
    const ITEM_PENDING  = 0;
    const ITEM_APPLIED  = 1;
    const ITEM_SKIPPED  = 2;
    const ITEM_REVERTED = 3;

    /**
     * Create a run
     *
     * @since 2.1.0
     * @param array $data Column values
     * @return int New run ID (0 on failure)
     */
    public function create_run($data)
    {
        global $wpdb;

        $data = wp_parse_args($data, array(
            'user_id' => get_current_user_id(),
            'run_type' => 'operation',
            'status' => self::STATUS_RUNNING,
            'label' => '',
            'params' => '',
            'total' => 0,
            'changed' => 0,
            'scheduled_at' => null,
            'revert_at' => null,
            'created_at' => time(),
        ));

        $this->purge_old_runs();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $inserted = $wpdb->insert(Bulk_Pricer_DB::runs_table(), $data);

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * Get a run by ID
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     * @return object|null
     */
    public function get_run($run_id)
    {
        global $wpdb;

        $table = Bulk_Pricer_DB::runs_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $run_id));
    }

    /**
     * Get the most recent runs
     *
     * @since 2.1.0
     * @param int $limit Max rows
     * @return object[]
     */
    public function get_runs($limit = 50)
    {
        global $wpdb;

        $table = Bulk_Pricer_DB::runs_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit));
    }

    /**
     * Update run columns
     *
     * @since 2.1.0
     * @param int   $run_id Run ID
     * @param array $fields Column => value
     */
    public function update_run($run_id, $fields)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->update(Bulk_Pricer_DB::runs_table(), $fields, array('id' => $run_id));
    }

    /**
     * Add to a run's "changed" counter
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     * @param int $count  Amount to add
     */
    public function increment_changed($run_id, $count)
    {
        global $wpdb;

        if ($count < 1) {
            return;
        }

        $table = Bulk_Pricer_DB::runs_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET changed = changed + %d WHERE id = %d", $count, $run_id));
    }

    /**
     * Delete a run and its items
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function delete_run($run_id)
    {
        global $wpdb;

        $this->delete_items($run_id);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->delete(Bulk_Pricer_DB::runs_table(), array('id' => $run_id));
    }

    /**
     * Delete a run's items
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function delete_items($run_id)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->delete(Bulk_Pricer_DB::items_table(), array('run_id' => $run_id));
    }

    /**
     * Delete the current user's unconfirmed CSV imports
     *
     * @since 2.1.0
     * @param int $user_id User ID
     */
    public function delete_staged_for_user($user_id)
    {
        global $wpdb;

        $table = Bulk_Pricer_DB::runs_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE status = %s AND user_id = %d", self::STATUS_STAGED, $user_id));

        foreach ($ids as $id) {
            $this->delete_run((int) $id);
        }
    }

    /**
     * Remove finished runs older than the retention window (and stale imports)
     *
     * @since 2.1.0
     */
    public function purge_old_runs()
    {
        global $wpdb;

        $days = (int) apply_filters('bulk_pricer_history_retention_days', 90);
        if ($days < 1) {
            return;
        }

        $table  = Bulk_Pricer_DB::runs_table();
        $cutoff = time() - ($days * DAY_IN_SECONDS);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $old = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$table}
             WHERE (status IN (%s, %s, %s) AND created_at < %d)
                OR (status = %s AND created_at < %d)",
            self::STATUS_APPLIED,
            self::STATUS_REVERTED,
            self::STATUS_CANCELLED,
            $cutoff,
            self::STATUS_STAGED,
            time() - DAY_IN_SECONDS
        ));

        foreach ($old as $id) {
            $this->delete_run((int) $id);
        }
    }

    /**
     * Snapshot product IDs into a run as pending items
     *
     * @since 2.1.0
     * @param int   $run_id      Run ID
     * @param int[] $product_ids Product/variation IDs
     */
    public function add_items($run_id, $product_ids)
    {
        global $wpdb;

        $table = Bulk_Pricer_DB::items_table();

        foreach (array_chunk($product_ids, 500) as $chunk) {
            $placeholders = array();
            $values       = array();
            foreach ($chunk as $product_id) {
                $placeholders[] = '(%d,%d)';
                $values[]       = $run_id;
                $values[]       = (int) $product_id;
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$table} (run_id, product_id) VALUES " . implode(',', $placeholders), $values));
        }
    }

    /**
     * Stage CSV rows as pending items that already carry their target values
     *
     * Each row: product_id, regular (string|null), sale (string|null),
     * from (int|null), to (int|null). Null means "leave unchanged".
     *
     * @since 2.1.0
     * @param int   $run_id Run ID
     * @param array $rows   Rows
     */
    public function add_csv_items($run_id, $rows)
    {
        global $wpdb;

        $table = Bulk_Pricer_DB::items_table();

        foreach (array_chunk($rows, 200) as $chunk) {
            $tuples = array();
            foreach ($chunk as $row) {
                $tuples[] = $wpdb->prepare('(%d,%d,', $run_id, $row['product_id'])
                    . $this->nullable($row['regular'], '%s') . ','
                    . $this->nullable($row['sale'], '%s') . ','
                    . $this->nullable($row['from'], '%d') . ','
                    . $this->nullable($row['to'], '%d') . ')';
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query("INSERT IGNORE INTO {$table} (run_id, product_id, new_regular, new_sale, new_from, new_to) VALUES " . implode(',', $tuples));
        }
    }

    /**
     * Prepare a nullable SQL literal
     *
     * @param mixed  $value  Value or null
     * @param string $format printf format for wpdb::prepare
     * @return string SQL fragment
     */
    private function nullable($value, $format)
    {
        global $wpdb;

        if ($value === null) {
            return 'NULL';
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        return $wpdb->prepare($format, $value);
    }

    /**
     * Get items with a given status
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     * @param int $status Item status
     * @param int $limit  Max rows
     * @param int $offset Offset
     * @return object[]
     */
    public function get_items($run_id, $status, $limit, $offset = 0)
    {
        global $wpdb;

        $table = Bulk_Pricer_DB::items_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE run_id = %d AND status = %d ORDER BY id ASC LIMIT %d OFFSET %d",
            $run_id,
            $status,
            $limit,
            $offset
        ));
    }

    /**
     * Count items (optionally by status)
     *
     * @since 2.1.0
     * @param int      $run_id Run ID
     * @param int|null $status Item status, or null for all
     * @return int
     */
    public function count_items($run_id, $status = null)
    {
        global $wpdb;

        $table = Bulk_Pricer_DB::items_table();

        if ($status === null) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE run_id = %d", $run_id));
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE run_id = %d AND status = %d", $run_id, $status));
    }

    /**
     * Update an item
     *
     * @since 2.1.0
     * @param int   $item_id Item ID
     * @param array $fields  Column => value
     */
    public function update_item($item_id, $fields)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->update(Bulk_Pricer_DB::items_table(), $fields, array('id' => $item_id));
    }

    /**
     * Mark every still-pending item of a run as skipped
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function skip_pending($run_id)
    {
        global $wpdb;

        $table = Bulk_Pricer_DB::items_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET status = %d WHERE run_id = %d AND status = %d",
            self::ITEM_SKIPPED,
            $run_id,
            self::ITEM_PENDING
        ));
    }
}
