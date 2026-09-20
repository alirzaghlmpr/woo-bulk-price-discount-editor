<?php
/**
 * AJAX Controller Class
 *
 * Handles preview, summary, apply and schedule AJAX requests
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Ajax_Controller Class
 *
 * Processes AJAX requests for preview, summary, batch apply and scheduling
 */
class Bulk_Pricer_Ajax_Controller extends Bulk_Pricer_Base_Controller
{
    /**
     * Product model instance
     *
     * @var Bulk_Pricer_Product_Model
     */
    private $product_model;

    /**
     * Pricing calculator instance
     *
     * @var Bulk_Pricer_Pricing_Calculator
     */
    private $pricing_calculator;

    /**
     * Preview controller instance
     *
     * @var Bulk_Pricer_Preview_Controller
     */
    private $preview_controller;

    /**
     * Run model instance
     *
     * @var Bulk_Pricer_Run_Model
     */
    private $runs;

    /**
     * Run service instance
     *
     * @var Bulk_Pricer_Run_Service
     */
    private $run_service;

    /**
     * Scheduler instance
     *
     * @var Bulk_Pricer_Scheduler
     */
    private $scheduler;

    /**
     * Constructor
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $this->product_model = new Bulk_Pricer_Product_Model();
        $this->pricing_calculator = new Bulk_Pricer_Pricing_Calculator();
        $this->preview_controller = new Bulk_Pricer_Preview_Controller();
        $this->runs = new Bulk_Pricer_Run_Model();
        $this->run_service = new Bulk_Pricer_Run_Service();
        $this->scheduler = new Bulk_Pricer_Scheduler();
    }

    /**
     * Handle preview AJAX request
     *
     * Pages through the flat list of products/variations, so every page holds
     * exactly one page worth of rows and the total counts variations too.
     *
     * @since 2.0.0
     */
    public function handle_preview()
    {
        $this->authorize();

        $validated_data = $this->get_validated_request();

        $page = max(1, $this->get_post_int('paged'));

        $ids = $this->product_model->get_all_matching_ids($validated_data['filters']);
        $total = count($ids);
        $total_pages = max(1, (int) ceil($total / $this->product_model->get_per_page()));
        $page = min($page, $total_pages);

        // Calculate new prices for preview
        $preview_data = array();
        foreach ($this->product_model->get_ids_page($ids, $page) as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }

            $price_data = $this->pricing_calculator->calculate_new_prices($product, $validated_data['operation']);
            if ($price_data) {
                $preview_data[] = $price_data;
            }
        }

        // Generate HTML
        $html = $this->preview_controller->generate_preview_html(
            $preview_data,
            $page,
            $total_pages,
            $total,
            $validated_data
        );

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Handle a summary chunk request
     *
     * Returns one compact row per product ([id, changed, old final, new final])
     * for the products in the requested slice. The browser adds them up, so
     * removing rows from the preview updates the totals instantly.
     *
     * @since 2.1.0
     */
    public function handle_summary()
    {
        $this->authorize();

        $validated_data = $this->get_validated_request();

        $offset = $this->get_post_int('offset');
        $chunk = max(10, (int) apply_filters('bulk_pricer_summary_chunk', 100));

        $ids = $this->product_model->get_all_matching_ids($validated_data['filters']);
        $slice = array_slice($ids, $offset, $chunk);

        $rows = array();
        foreach ($slice as $product_id) {
            $product = wc_get_product($product_id);
            $summary = $product ? $this->pricing_calculator->calculate_summary($product, $validated_data['operation']) : false;

            $rows[] = $summary
                ? array((int) $product_id, $summary['changed'] ? 1 : 0, round($summary['old_final'], 6), round($summary['new_final'], 6))
                : array((int) $product_id, 0, 0, 0);
        }

        $next = $offset + count($slice);

        wp_send_json_success(array(
            'total' => count($ids),
            'next_offset' => $next,
            'done' => $next >= count($ids),
            'rows' => $rows,
        ));
    }

    /**
     * Handle batch apply AJAX request
     *
     * The first call (no run_id) snapshots the target products into a run and
     * processes the first batch; later calls just continue that run. The
     * snapshot keeps pagination stable even though prices change mid-run.
     *
     * @since 2.0.0
     */
    public function handle_apply_batch()
    {
        $this->authorize();

        $run_id = $this->get_post_int('run_id');

        if (!$run_id) {
            $validated_data = $this->get_validated_request();

            $ids = $this->product_model->get_all_matching_ids($validated_data['filters']);
            $excluded = $this->get_excluded_ids();
            if ($excluded) {
                $ids = array_values(array_diff($ids, $excluded));
            }

            if (empty($ids)) {
                wp_send_json_error(__('No products to update.', 'bulk-price-discount-editor-for-woocommerce'));
            }

            // "Apply now" ignores any schedule fields except the auto-restore time.
            $validated_data['schedule']['mode'] = 'now';

            $run_id = $this->run_service->create_operation_run($validated_data, $ids, Bulk_Pricer_Run_Model::STATUS_RUNNING);
            if (!$run_id) {
                wp_send_json_error(__('Could not start the run.', 'bulk-price-discount-editor-for-woocommerce'));
            }

            if ($validated_data['schedule']['revert_at']) {
                $this->scheduler->schedule_revert($run_id, $validated_data['schedule']['revert_at']);
            }
        } else {
            $run = $this->runs->get_run($run_id);
            $resumable = array(
                Bulk_Pricer_Run_Model::STATUS_STAGED,
                Bulk_Pricer_Run_Model::STATUS_SCHEDULED,
                Bulk_Pricer_Run_Model::STATUS_RUNNING,
            );

            if (!$run || !in_array($run->status, $resumable, true)) {
                wp_send_json_error(__('This run can no longer be applied.', 'bulk-price-discount-editor-for-woocommerce'));
            }

            // "Run now" on a scheduled run, or the first confirm of a CSV import.
            if ($run->status !== Bulk_Pricer_Run_Model::STATUS_RUNNING) {
                $this->scheduler->unschedule_apply($run_id);
                $this->runs->update_run($run_id, array('status' => Bulk_Pricer_Run_Model::STATUS_RUNNING));
            }
        }

        $progress = $this->run_service->process_batch($run_id, $this->product_model->get_batch_size());

        if (!$progress['remaining']) {
            $this->run_service->finalize($run_id);
        }

        $progress['run_id'] = $run_id;
        wp_send_json_success($progress);
    }

    /**
     * Handle "schedule for later" AJAX request
     *
     * @since 2.1.0
     */
    public function handle_schedule()
    {
        $this->authorize();

        $validated_data = $this->get_validated_request();
        $schedule = $validated_data['schedule'];

        if ($schedule['mode'] !== 'schedule') {
            wp_send_json_error(__('Choose a date and time to schedule the changes.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $ids = $this->product_model->get_all_matching_ids($validated_data['filters']);
        $excluded = $this->get_excluded_ids();
        if ($excluded) {
            $ids = array_values(array_diff($ids, $excluded));
        }

        if (empty($ids)) {
            wp_send_json_error(__('No products to update.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $run_id = $this->run_service->create_operation_run($validated_data, $ids, Bulk_Pricer_Run_Model::STATUS_SCHEDULED);
        if (!$run_id) {
            wp_send_json_error(__('Could not schedule the run.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        if (!$this->scheduler->schedule_apply($run_id, $schedule['apply_at'])) {
            $this->run_service->delete_run($run_id);
            wp_send_json_error(__('The schedule could not be created.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        if ($schedule['revert_at']) {
            $this->scheduler->schedule_revert($run_id, $schedule['revert_at']);
        }

        $format = get_option('date_format') . ' ' . get_option('time_format');

        wp_send_json_success(array(
            'run_id' => $run_id,
            'count' => count($ids),
            'apply_at' => wp_date($format, $schedule['apply_at']),
            'revert_at' => $schedule['revert_at'] ? wp_date($format, $schedule['revert_at']) : '',
        ));
    }
}
