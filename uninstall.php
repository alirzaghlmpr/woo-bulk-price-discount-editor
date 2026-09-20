<?php
/**
 * Fired when the plugin is deleted
 *
 * Removes the change-history tables and any pending scheduled jobs.
 *
 * @package Bulk_Price_Discount_Editor
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-bulk-pricer-db.php';

// Pending scheduled applies / auto-restores (Action Scheduler is only there if WooCommerce is).
if (function_exists('as_unschedule_all_actions')) {
    as_unschedule_all_actions('bulk_pricer_scheduled_apply');
    as_unschedule_all_actions('bulk_pricer_scheduled_revert');
}

Bulk_Pricer_DB::uninstall();
