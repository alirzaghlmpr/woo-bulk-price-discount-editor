<?php
/**
 * Fired during plugin activation
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Activator Class
 *
 * Handles plugin activation tasks
 */
class Bulk_Pricer_Activator
{
    /**
     * Plugin activation handler
     *
     * @since 2.0.0
     */
    public static function activate()
    {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.8', '<')) {
            wp_die(__('This plugin requires WordPress 5.8 or higher.', 'bulk-price-discount-editor'));
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            wp_die(__('This plugin requires PHP 7.4 or higher.', 'bulk-price-discount-editor'));
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_die(__('This plugin requires WooCommerce to be installed and active.', 'bulk-price-discount-editor'));
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
