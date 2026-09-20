<?php
/**
 * Scheduler Class
 *
 * Runs scheduled applies and auto-restores through WooCommerce's bundled
 * Action Scheduler. The callbacks are registered on every request (not just
 * in wp-admin) because Action Scheduler runs from WP-Cron / async requests.
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Scheduler Class
 */
class Bulk_Pricer_Scheduler
{
    const HOOK_APPLY  = 'bulk_pricer_scheduled_apply';
    const HOOK_REVERT = 'bulk_pricer_scheduled_revert';
    const GROUP       = 'bulk-price-discount-editor';

    /**
     * Seconds a single background action may work before handing over to the next
     */
    const TIME_BUDGET = 20;

    /**
     * Register the Action Scheduler callbacks
     *
     * @since 2.1.0
     */
    public function register()
    {
        add_action(self::HOOK_APPLY, array($this, 'handle_apply'));
        add_action(self::HOOK_REVERT, array($this, 'handle_revert'));
    }

    /**
     * Whether Action Scheduler can be used
     *
     * @since 2.1.0
     * @return bool
     */
    public static function is_available()
    {
        return function_exists('as_schedule_single_action')
            && function_exists('as_enqueue_async_action')
            && function_exists('as_unschedule_all_actions');
    }

    /**
     * Schedule the apply of a run
     *
     * @since 2.1.0
     * @param int $run_id    Run ID
     * @param int $timestamp When to apply
     * @return bool
     */
    public function schedule_apply($run_id, $timestamp)
    {
        return self::is_available()
            && as_schedule_single_action((int) $timestamp, self::HOOK_APPLY, array((int) $run_id), self::GROUP) > 0;
    }

    /**
     * Schedule the auto-restore of a run
     *
     * @since 2.1.0
     * @param int $run_id    Run ID
     * @param int $timestamp When to restore
     * @return bool
     */
    public function schedule_revert($run_id, $timestamp)
    {
        return self::is_available()
            && as_schedule_single_action((int) $timestamp, self::HOOK_REVERT, array((int) $run_id), self::GROUP) > 0;
    }

    /**
     * Remove every pending action of a run
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function unschedule($run_id)
    {
        if (!self::is_available()) {
            return;
        }

        as_unschedule_all_actions(self::HOOK_APPLY, array((int) $run_id), self::GROUP);
        as_unschedule_all_actions(self::HOOK_REVERT, array((int) $run_id), self::GROUP);
    }

    /**
     * Remove only the pending apply action of a run (used by "Run now")
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function unschedule_apply($run_id)
    {
        if (self::is_available()) {
            as_unschedule_all_actions(self::HOOK_APPLY, array((int) $run_id), self::GROUP);
        }
    }

    /**
     * Action Scheduler callback: apply a scheduled run
     *
     * Works through the run for up to TIME_BUDGET seconds and, if products are
     * left, queues itself again so long runs never hit PHP time limits.
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function handle_apply($run_id)
    {
        $run_id = (int) $run_id;
        $runs = new Bulk_Pricer_Run_Model();
        $run = $runs->get_run($run_id);

        if (!$run || !in_array($run->status, array(Bulk_Pricer_Run_Model::STATUS_SCHEDULED, Bulk_Pricer_Run_Model::STATUS_RUNNING), true)) {
            return;
        }

        if ($run->status === Bulk_Pricer_Run_Model::STATUS_SCHEDULED) {
            $runs->update_run($run_id, array('status' => Bulk_Pricer_Run_Model::STATUS_RUNNING));
        }

        $service = new Bulk_Pricer_Run_Service();
        $products = new Bulk_Pricer_Product_Model();
        $deadline = microtime(true) + self::TIME_BUDGET;

        do {
            $progress = $service->process_batch($run_id, $products->get_batch_size());
        } while ($progress['remaining'] && microtime(true) < $deadline);

        if ($progress['remaining']) {
            as_enqueue_async_action(self::HOOK_APPLY, array($run_id), self::GROUP);
            return;
        }

        $service->finalize($run_id);
    }

    /**
     * Action Scheduler callback: restore a run's original prices
     *
     * @since 2.1.0
     * @param int $run_id Run ID
     */
    public function handle_revert($run_id)
    {
        $run_id = (int) $run_id;
        $runs = new Bulk_Pricer_Run_Model();
        $run = $runs->get_run($run_id);

        if (!$run) {
            return;
        }

        // Not applied yet (e.g. the apply is still working through a big run): try again shortly.
        if (in_array($run->status, array(Bulk_Pricer_Run_Model::STATUS_SCHEDULED, Bulk_Pricer_Run_Model::STATUS_STAGED), true)) {
            as_schedule_single_action(time() + 5 * MINUTE_IN_SECONDS, self::HOOK_REVERT, array($run_id), self::GROUP);
            return;
        }

        if (!in_array($run->status, array(
            Bulk_Pricer_Run_Model::STATUS_APPLIED,
            Bulk_Pricer_Run_Model::STATUS_RUNNING,
            Bulk_Pricer_Run_Model::STATUS_REVERTING,
        ), true)) {
            return;
        }

        $service = new Bulk_Pricer_Run_Service();
        $products = new Bulk_Pricer_Product_Model();
        $deadline = microtime(true) + self::TIME_BUDGET;

        do {
            $progress = $service->revert_batch($run_id, $products->get_batch_size());
        } while ($progress['remaining'] && microtime(true) < $deadline);

        if ($progress['remaining']) {
            as_enqueue_async_action(self::HOOK_REVERT, array($run_id), self::GROUP);
        }
    }
}
