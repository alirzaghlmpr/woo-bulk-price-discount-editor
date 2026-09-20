<?php
/**
 * Admin Assets Class
 *
 * Handles asset enqueuing for admin pages
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Admin_Assets Class
 *
 * Enqueues CSS and JavaScript files
 */
class Bulk_Pricer_Admin_Assets
{
    /**
     * Enqueue admin assets
     *
     * @since 2.0.0
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook)
    {
        // Only load on our plugin page
        if (strpos($hook, 'theme-bulk-pricer') === false) {
            return;
        }

        // Enqueue jQuery
        wp_enqueue_script('jquery');

        // Enqueue custom JavaScript
        wp_enqueue_script(
            'bulk-pricer-admin-js',
            BULK_PRICER_PLUGIN_URL . 'assets/js/bulk-pricer-admin.js',
            array('jquery'),
            BULK_PRICER_VERSION,
            true
        );

        $formatter = new Bulk_Pricer_Formatter();

        // Localize script with AJAX data
        wp_localize_script('bulk-pricer-admin-js', 'sbp_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'adminPostUrl' => admin_url('admin-post.php'),
            'historyUrl' => add_query_arg(array('page' => 'theme-bulk-pricer', 'tab' => 'history'), admin_url('admin.php')),
            'nonce' => wp_create_nonce('sbp_bulk_nonce'),
            'format' => array(
                'decimals' => function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 0,
                'decimalSep' => function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : '.',
                'thousandSep' => function_exists('wc_get_price_thousand_separator') ? wc_get_price_thousand_separator() : ',',
                'symbol' => $formatter->get_plain_currency_symbol(),
            ),
            'i18n' => array(
                'loading_preview' => __('Loading preview page', 'bulk-price-discount-editor-for-woocommerce'),
                'processing' => __('Processing...', 'bulk-price-discount-editor-for-woocommerce'),
                'processing_batch' => __('⏳ Applying changes', 'bulk-price-discount-editor-for-woocommerce'),
                'reverting_batch' => __('⏳ Reverting changes', 'bulk-price-discount-editor-for-woocommerce'),
                'confirm_apply' => __('Are you sure you want to apply changes to all products?', 'bulk-price-discount-editor-for-woocommerce'),
                'confirm_schedule' => __('Schedule these changes?', 'bulk-price-discount-editor-for-woocommerce'),
                'confirm_import' => __('Apply the prices from this file?', 'bulk-price-discount-editor-for-woocommerce'),
                'confirm_revert' => __('Revert this run? Each product goes back to the prices it had before this run, including changes made to it since.', 'bulk-price-discount-editor-for-woocommerce'),
                'confirm_delete' => __('Delete this entry? It can no longer be reverted.', 'bulk-price-discount-editor-for-woocommerce'),
                'confirm_cancel' => __('Cancel this run? Nothing will be changed.', 'bulk-price-discount-editor-for-woocommerce'),
                'confirm_run_now' => __('Apply this run now instead of waiting for its schedule?', 'bulk-price-discount-editor-for-woocommerce'),
                'success' => __('✅ Changes successfully applied. You can undo them from the History tab.', 'bulk-price-discount-editor-for-woocommerce'),
                'import_success' => __('✅ Prices imported. You can undo them from the History tab.', 'bulk-price-discount-editor-for-woocommerce'),
                'revert_success' => __('✅ The run was reverted.', 'bulk-price-discount-editor-for-woocommerce'),
                'view_history' => __('Open History', 'bulk-price-discount-editor-for-woocommerce'),
                'confirm_final' => __('✅ Confirm and Apply', 'bulk-price-discount-editor-for-woocommerce'),
                'schedule_final' => __('🗓️ Schedule Changes', 'bulk-price-discount-editor-for-woocommerce'),
                'error_connection' => __('Error connecting to server', 'bulk-price-discount-editor-for-woocommerce'),
                'error_applying' => __('Error applying changes', 'bulk-price-discount-editor-for-woocommerce'),
                'error_resume_hint' => __('The run was interrupted. You can resume or revert it from the History tab.', 'bulk-price-discount-editor-for-woocommerce'),
                /* translators: 1: number of products, 2: date and time the changes are applied */
                'scheduled_ok' => __('🗓️ Scheduled: %1$d products will be updated on %2$s.', 'bulk-price-discount-editor-for-woocommerce'),
                /* translators: %s: date and time the original prices are restored */
                'scheduled_revert' => __('Original prices will be restored on %s.', 'bulk-price-discount-editor-for-woocommerce'),
                'remove_row' => __('Remove from list', 'bulk-price-discount-editor-for-woocommerce'),
                'restore_row' => __('Include again', 'bulk-price-discount-editor-for-woocommerce'),
                /* translators: 1: products calculated so far, 2: total products */
                'summary_loading' => __('Calculating for all products… %1$d / %2$d', 'bulk-price-discount-editor-for-woocommerce'),
                'summary_error' => __('Could not calculate the summary.', 'bulk-price-discount-editor-for-woocommerce'),
                /* translators: %d: number of products whose price goes up */
                'summary_increase' => __('%d up', 'bulk-price-discount-editor-for-woocommerce'),
                /* translators: %d: number of products whose price goes down */
                'summary_decrease' => __('%d down', 'bulk-price-discount-editor-for-woocommerce'),
                /* translators: %d: number of products with another kind of change */
                'summary_other' => __('%d other', 'bulk-price-discount-editor-for-woocommerce'),
                /* translators: %s: average price change, e.g. +4.5% */
                'summary_average' => __('avg %s', 'bulk-price-discount-editor-for-woocommerce'),
                /* translators: 1: products processed so far, 2: total products */
                'progress_label' => __('%1$d / %2$d', 'bulk-price-discount-editor-for-woocommerce'),
                'details_loading' => __('Loading…', 'bulk-price-discount-editor-for-woocommerce'),
            ),
        ));

        // Enqueue custom CSS
        wp_enqueue_style(
            'bulk-pricer-admin-css',
            BULK_PRICER_PLUGIN_URL . 'assets/css/bulk-pricer-admin.css',
            array(),
            BULK_PRICER_VERSION
        );
    }
}
