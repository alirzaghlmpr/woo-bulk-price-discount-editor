<?php
/**
 * History Controller Class
 *
 * Handles undo, cancel, delete and detail requests for past runs.
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_History_Controller Class
 */
class Bulk_Pricer_History_Controller extends Bulk_Pricer_Base_Controller
{
    /**
     * Rows shown in the details panel
     */
    const DETAIL_ROWS = 100;

    /**
     * @var Bulk_Pricer_Run_Model
     */
    private $runs;

    /**
     * @var Bulk_Pricer_Run_Service
     */
    private $run_service;

    /**
     * @var Bulk_Pricer_Product_Model
     */
    private $products;

    /**
     * Constructor
     *
     * @since 2.1.0
     */
    public function __construct()
    {
        $this->runs = new Bulk_Pricer_Run_Model();
        $this->run_service = new Bulk_Pricer_Run_Service();
        $this->products = new Bulk_Pricer_Product_Model();
    }

    /**
     * Revert the next batch of a run (the browser calls this until done)
     *
     * @since 2.1.0
     */
    public function handle_revert_batch()
    {
        $this->authorize();

        $run = $this->runs->get_run($this->get_post_int('run_id'));
        $revertable = array(
            Bulk_Pricer_Run_Model::STATUS_APPLIED,
            Bulk_Pricer_Run_Model::STATUS_RUNNING,
            Bulk_Pricer_Run_Model::STATUS_REVERTING,
        );

        if (!$run || !in_array($run->status, $revertable, true)) {
            wp_send_json_error(__('This run cannot be reverted.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $progress = $this->run_service->revert_batch((int) $run->id, $this->products->get_batch_size());
        $progress['run_id'] = (int) $run->id;

        wp_send_json_success($progress);
    }

    /**
     * Cancel a scheduled run or discard a staged import
     *
     * @since 2.1.0
     */
    public function handle_cancel()
    {
        $this->authorize();

        $run = $this->runs->get_run($this->get_post_int('run_id'));
        $cancellable = array(Bulk_Pricer_Run_Model::STATUS_SCHEDULED, Bulk_Pricer_Run_Model::STATUS_STAGED);

        if (!$run || !in_array($run->status, $cancellable, true)) {
            wp_send_json_error(__('This run cannot be cancelled.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $this->run_service->cancel_run((int) $run->id);

        wp_send_json_success();
    }

    /**
     * Delete a run from the history
     *
     * @since 2.1.0
     */
    public function handle_delete()
    {
        $this->authorize();

        $run = $this->runs->get_run($this->get_post_int('run_id'));
        if (!$run) {
            wp_send_json_error(__('Run not found.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        // Deleting a run that is still running would leave products half-updated.
        if ($run->status === Bulk_Pricer_Run_Model::STATUS_REVERTING) {
            wp_send_json_error(__('Finish reverting this run before deleting it.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $this->run_service->delete_run((int) $run->id);

        wp_send_json_success();
    }

    /**
     * Render the per-product details of a run
     *
     * @since 2.1.0
     */
    public function handle_details()
    {
        $this->authorize();

        $run = $this->runs->get_run($this->get_post_int('run_id'));
        if (!$run) {
            wp_send_json_error(__('Run not found.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        // Applied items first, then already reverted ones.
        $items = $this->runs->get_items((int) $run->id, Bulk_Pricer_Run_Model::ITEM_APPLIED, self::DETAIL_ROWS);
        if (count($items) < self::DETAIL_ROWS) {
            $items = array_merge(
                $items,
                $this->runs->get_items((int) $run->id, Bulk_Pricer_Run_Model::ITEM_REVERTED, self::DETAIL_ROWS - count($items))
            );
        }

        $bulk_pricer_run = $run;
        $bulk_pricer_items = $items;
        $bulk_pricer_changed = (int) $run->changed;
        $bulk_pricer_export_url = wp_nonce_url(
            add_query_arg(array('action' => 'sbp_export_run', 'run_id' => (int) $run->id), admin_url('admin-post.php')),
            'sbp_bulk_nonce',
            'security'
        );

        ob_start();
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/partials/run-details.php';
        wp_send_json_success(array('html' => ob_get_clean()));
    }

    /**
     * Download a run's recorded changes as CSV
     *
     * @since 2.1.0
     */
    public function handle_export_run()
    {
        $this->authorize_admin_post();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified above.
        $run_id = isset($_GET['run_id']) ? absint(wp_unslash($_GET['run_id'])) : 0;
        $run = $this->runs->get_run($run_id);
        if (!$run) {
            wp_die(esc_html__('Run not found.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bulk-price-run-' . (int) $run->id . '.csv"');

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads non-Latin names correctly.

        $csv = new Bulk_Pricer_Csv_Service();
        $csv->write_run_csv($out, (int) $run->id);

        fclose($out); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        exit;
    }
}
