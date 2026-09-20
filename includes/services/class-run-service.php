<?php
/**
 * Run Service Class
 *
 * Creates runs, applies them in batches and reverts them. Used by the AJAX
 * controllers (interactive apply / undo) and by the scheduler (background).
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Run_Service Class
 */
class Bulk_Pricer_Run_Service
{
    /**
     * @var Bulk_Pricer_Run_Model
     */
    private $runs;

    /**
     * @var Bulk_Pricer_Product_Model
     */
    private $products;

    /**
     * @var Bulk_Pricer_Pricing_Calculator
     */
    private $calculator;

    /**
     * @var Bulk_Pricer_Date_Handler
     */
    private $dates;

    /**
     * Constructor
     *
     * @since 2.1.0
     */
    public function __construct()
    {
        $this->runs = new Bulk_Pricer_Run_Model();
        $this->products = new Bulk_Pricer_Product_Model();
        $this->calculator = new Bulk_Pricer_Pricing_Calculator();
        $this->dates = new Bulk_Pricer_Date_Handler();
    }

    /**
     * Create a run from a validated operation and snapshot its products
     *
     * @since 2.1.0
     * @param array  $validated Validated request data
     * @param int[]  $ids       Product/variation IDs to process
     * @param string $status    Initial run status
     * @return int Run ID (0 on failure)
     */
    public function create_operation_run($validated, $ids, $status)
    {
        $formatter = new Bulk_Pricer_Formatter();
        $schedule = $validated['schedule'];

        $run_id = $this->runs->create_run(array(
            'run_type' => 'operation',
            'status' => $status,
            'label' => mb_substr($formatter->describe_operation($validated), 0, 250),
            'params' => wp_json_encode(array(
                'operation' => $validated['operation'],
                'filters' => $validated['filters'],
            )),
            'total' => count($ids),
            'scheduled_at' => $schedule['mode'] === 'schedule' ? $schedule['apply_at'] : null,
            'revert_at' => $schedule['revert_at'] ? $schedule['revert_at'] : null,
        ));

        if ($run_id) {
            $this->runs->add_items($run_id, $ids);
        }

        return $run_id;
    }

    /**
     * Create a staged CSV import run
     *
     * @since 2.1.0
     * @param string $filename Uploaded file name
     * @param array  $items    Rows from the CSV service
     * @return int Run ID (0 on failure)
     */
    public function create_csv_run($filename, $items)
    {
        $this->runs->delete_staged_for_user(get_current_user_id());

        $run_id = $this->runs->create_run(array(
            'run_type' => 'csv',
            'status' => Bulk_Pricer_Run_Model::STATUS_STAGED,
            /* translators: %s: CSV file name */
            'label' => mb_substr(sprintf(__('CSV import: %s', 'bulk-price-discount-editor-for-woocommerce'), $filename), 0, 250),
            'params' => wp_json_encode(array('filename' => $filename)),
            'total' => count($items),
        ));

        if ($run_id) {
            $this->runs->add_csv_items($run_id, $items);
        }

        return $run_id;
    }

    /**
     * Apply the next batch of a run
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     * @param int $limit  Items per batch
     * @return array remaining, total, done, changed
     */
    public function process_batch($run_id, $limit)
    {
        $run = $this->runs->get_run($run_id);
        if (!$run) {
            return array('remaining' => false, 'total' => 0, 'done' => 0, 'changed' => 0);
        }

        $params = json_decode((string) $run->params, true);
        $operation = (is_array($params) && isset($params['operation'])) ? $params['operation'] : array();
        $is_csv = ($run->run_type === 'csv');

        $changed = 0;
        $parents = array();

        foreach ($this->runs->get_items($run_id, Bulk_Pricer_Run_Model::ITEM_PENDING, $limit) as $item) {
            $fields = array('status' => Bulk_Pricer_Run_Model::ITEM_SKIPPED);

            try {
                $product = wc_get_product((int) $item->product_id);

                if ($product && $this->products->is_priceable($product)) {
                    $target = $is_csv
                        ? $this->target_from_csv($product, $item)
                        : $this->target_from_operation($product, $operation);

                    $old = $this->products->get_price_snapshot($product);

                    if ($target && $this->differs($old, $target)) {
                        $this->products->update_product_prices(
                            $product,
                            $target['regular'],
                            $target['sale'],
                            $target['from'],
                            $target['to']
                        );

                        // Record what was actually saved.
                        $new = $this->products->get_price_snapshot($product);
                        $fields = array(
                            'status' => Bulk_Pricer_Run_Model::ITEM_APPLIED,
                            'parent_id' => (int) $product->get_parent_id(),
                            'old_regular' => $old['regular'],
                            'old_sale' => $old['sale'],
                            'old_from' => $old['from'],
                            'old_to' => $old['to'],
                            'new_regular' => $new['regular'],
                            'new_sale' => $new['sale'],
                            'new_from' => $new['from'],
                            'new_to' => $new['to'],
                        );

                        if ($product->get_parent_id()) {
                            $parents[] = $product->get_parent_id();
                        }
                        $changed++;
                    }
                }
            } catch (Exception $e) {
                // Leave the item skipped; one bad product must not stop the run.
                $fields = array('status' => Bulk_Pricer_Run_Model::ITEM_SKIPPED);
            }

            $this->runs->update_item((int) $item->id, $fields);
        }

        // Variation saves don't update their parent, so refresh the variable products.
        $this->products->sync_variable_parents($parents);
        $this->runs->increment_changed($run_id, $changed);

        return $this->progress($run_id);
    }

    /**
     * Mark a run as fully applied
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function finalize($run_id)
    {
        $this->runs->update_run($run_id, array(
            'status' => Bulk_Pricer_Run_Model::STATUS_APPLIED,
            'applied_at' => time(),
        ));
    }

    /**
     * Revert the next batch of a run's applied changes
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     * @param int $limit  Items per batch
     * @return array remaining, total, done, changed
     */
    public function revert_batch($run_id, $limit)
    {
        $run = $this->runs->get_run($run_id);
        if (!$run) {
            return array('remaining' => false, 'total' => 0, 'done' => 0, 'changed' => 0);
        }

        if ($run->status !== Bulk_Pricer_Run_Model::STATUS_REVERTING) {
            $this->runs->update_run($run_id, array('status' => Bulk_Pricer_Run_Model::STATUS_REVERTING));
        }

        $parents = array();

        foreach ($this->runs->get_items($run_id, Bulk_Pricer_Run_Model::ITEM_APPLIED, $limit) as $item) {
            try {
                $product = wc_get_product((int) $item->product_id);
                if ($product) {
                    $this->products->restore_product_prices(
                        $product,
                        $item->old_regular,
                        $item->old_sale,
                        $item->old_from,
                        $item->old_to
                    );

                    if ($product->get_parent_id()) {
                        $parents[] = $product->get_parent_id();
                    }
                }
            } catch (Exception $e) {
                // A product that can't be restored is marked reverted so the run can finish.
                unset($e);
            }

            $this->runs->update_item((int) $item->id, array('status' => Bulk_Pricer_Run_Model::ITEM_REVERTED));
        }

        $this->products->sync_variable_parents($parents);

        $left = $this->runs->count_items($run_id, Bulk_Pricer_Run_Model::ITEM_APPLIED);
        if ($left === 0) {
            // Anything that never got applied must not be applied later.
            $this->runs->skip_pending($run_id);
            $this->runs->update_run($run_id, array(
                'status' => Bulk_Pricer_Run_Model::STATUS_REVERTED,
                'reverted_at' => time(),
            ));
            $this->scheduler()->unschedule($run_id);
        }

        return array(
            'remaining' => $left > 0,
            'total' => (int) $run->changed,
            'done' => max(0, (int) $run->changed - $left),
            'changed' => (int) $run->changed,
        );
    }

    /**
     * Cancel a run that hasn't been applied (scheduled / staged)
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function cancel_run($run_id)
    {
        $this->scheduler()->unschedule($run_id);
        $this->runs->delete_items($run_id);
        $this->runs->update_run($run_id, array('status' => Bulk_Pricer_Run_Model::STATUS_CANCELLED));
    }

    /**
     * Delete a run from the history
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function delete_run($run_id)
    {
        $this->scheduler()->unschedule($run_id);
        $this->runs->delete_run($run_id);
    }

    /**
     * Progress numbers for a run
     *
     * @param int $run_id Run ID
     * @return array
     */
    private function progress($run_id)
    {
        $run = $this->runs->get_run($run_id);
        $pending = $this->runs->count_items($run_id, Bulk_Pricer_Run_Model::ITEM_PENDING);

        return array(
            'remaining' => $pending > 0,
            'total' => $run ? (int) $run->total : 0,
            'done' => $run ? max(0, (int) $run->total - $pending) : 0,
            'changed' => $run ? (int) $run->changed : 0,
        );
    }

    /**
     * Work out a product's target prices for an operation run
     *
     * @param WC_Product $product   Product
     * @param array      $operation Stored operation parameters
     * @return array|null regular, sale, from, to
     */
    private function target_from_operation($product, $operation)
    {
        if (empty($operation['operation_type'])) {
            return null;
        }

        $result = $this->calculator->compute_for_product($product, $operation);
        if (!$result) {
            return null;
        }

        $from = null;
        $to = null;
        if ($result['new_sale'] > 0) {
            $from = !empty($operation['sale_start']) ? $this->dates->to_timestamp($operation['sale_start']) : null;
            $to = !empty($operation['sale_expiry']) ? $this->dates->to_timestamp($operation['sale_expiry'], true) : null;
        }

        return array(
            'regular' => $result['new_regular'],
            'sale' => $result['new_sale'],
            'from' => $from,
            'to' => $to,
        );
    }

    /**
     * Work out a product's target prices for a CSV row
     *
     * Null columns in the row mean "leave as is".
     *
     * @param WC_Product $product Product
     * @param object     $item    Run item row
     * @return array|null regular, sale, from, to
     */
    private function target_from_csv($product, $item)
    {
        $regular = $item->new_regular !== null ? (float) $item->new_regular : (float) $product->get_regular_price('edit');
        $sale = $item->new_sale !== null ? (float) $item->new_sale : (float) $product->get_sale_price('edit');

        if ($regular <= 0 || ($sale > 0 && $sale >= $regular)) {
            return null;
        }

        return array(
            'regular' => $regular,
            'sale' => $sale > 0 ? $sale : 0,
            'from' => ($sale > 0 && $item->new_from !== null) ? (int) $item->new_from : null,
            'to' => ($sale > 0 && $item->new_to !== null) ? (int) $item->new_to : null,
        );
    }

    /**
     * Whether saving the target would change anything
     *
     * @param array $old    Current price snapshot
     * @param array $target Target prices
     * @return bool
     */
    private function differs($old, $target)
    {
        if (abs((float) $old['regular'] - $target['regular']) > 0.000001) {
            return true;
        }
        if (abs((float) $old['sale'] - $target['sale']) > 0.000001) {
            return true;
        }
        if ($target['sale'] > 0) {
            if ($target['from'] !== null && (int) $old['from'] !== (int) $target['from']) {
                return true;
            }
            if ($target['to'] !== null && (int) $old['to'] !== (int) $target['to']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lazily built scheduler (avoids a construction loop with the scheduler)
     *
     * @return Bulk_Pricer_Scheduler
     */
    private function scheduler()
    {
        return new Bulk_Pricer_Scheduler();
    }
}
