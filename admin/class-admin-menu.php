<?php
/**
 * Admin Menu Class
 *
 * Handles WordPress admin menu registration
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Admin_Menu Class
 *
 * Registers and renders admin menu pages
 */
class Bulk_Pricer_Admin_Menu
{
    /**
     * Register admin menu
     *
     * @since 2.0.0
     */
    public function register_menu()
    {
        add_menu_page(
            __('Bulk Price Editor', 'bulk-price-discount-editor-for-woocommerce'),
            __('Bulk Price Editor', 'bulk-price-discount-editor-for-woocommerce'),
            Bulk_Pricer_Loader::capability(),
            'theme-bulk-pricer',
            array($this, 'render_admin_page'),
            BULK_PRICER_PLUGIN_URL . 'assets/images/menu-icon.png',
            56
        );
    }

    /**
     * Render admin page
     *
     * @since 2.0.0
     */
    public function render_admin_page()
    {
        // Check user capabilities
        if (!current_user_can(Bulk_Pricer_Loader::capability())) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        // Load the main admin page view
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/admin-page.php';
    }
}
