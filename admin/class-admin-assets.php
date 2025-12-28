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

        // Localize script with AJAX data
        wp_localize_script('bulk-pricer-admin-js', 'sbp_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sbp_bulk_nonce'),
            'i18n' => array(
                'loading_preview' => __('Loading preview page', 'bulk-price-discount-editor'),
                'processing' => __('Processing...', 'bulk-price-discount-editor'),
                'processing_batch' => __('⏳ Applying batch', 'bulk-price-discount-editor'),
                'confirm_apply' => __('Are you sure you want to apply changes to all products?', 'bulk-price-discount-editor'),
                'success' => __('✅ Changes successfully applied to all products. Please refresh the page.', 'bulk-price-discount-editor'),
                'confirm_final' => __('✅ Confirm and Apply', 'bulk-price-discount-editor'),
                'error_connection' => __('Error connecting to server', 'bulk-price-discount-editor'),
                'error_applying' => __('Error applying changes', 'bulk-price-discount-editor')
            )
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
