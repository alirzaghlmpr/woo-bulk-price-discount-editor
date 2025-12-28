<?php
/**
 * Fired during plugin deactivation
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Deactivator Class
 *
 * Handles plugin deactivation tasks
 */
class Bulk_Pricer_Deactivator
{
    /**
     * Plugin deactivation handler
     *
     * @since 2.0.0
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clean up transients if needed
        // delete_transient('bulk_pricer_cache');
    }
}
