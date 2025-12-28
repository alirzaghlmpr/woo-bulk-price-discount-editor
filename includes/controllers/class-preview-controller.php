<?php
/**
 * Preview Controller Class
 *
 * Generates preview HTML for price changes
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Preview_Controller Class
 *
 * Orchestrates view templates for preview generation
 */
class Bulk_Pricer_Preview_Controller
{
    /**
     * Formatter instance
     *
     * @var Bulk_Pricer_Formatter
     */
    private $formatter;

    /**
     * Constructor
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $this->formatter = new Bulk_Pricer_Formatter();
    }

    /**
     * Generate preview HTML
     *
     * @since 2.0.0
     * @param array $preview_data  Preview data
     * @param int   $page          Current page
     * @param int   $total_pages   Total pages
     * @param int   $total_count   Total product count
     * @param array $operation_data Operation data
     * @return string HTML output
     */
    public function generate_preview_html($preview_data, $page, $total_pages, $total_count, $operation_data)
    {
        if (empty($preview_data)) {
            return $this->render_empty_message();
        }

        ob_start();

        // Prepare data for views
        $currency = get_woocommerce_currency_symbol();
        $operation_type = $operation_data['operation']['operation_type'];
        $change_percent = $operation_data['operation']['change_percent'];
        $change_fixed = $operation_data['operation']['change_fixed'];
        $sync = $operation_data['operation']['sync_sale'];

        // Operation header
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/components/operation-header.php';

        // Preview table
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/partials/preview-table.php';

        // Pagination
        if ($total_pages > 1) {
            include BULK_PRICER_PLUGIN_DIR . 'admin/views/partials/pagination.php';
        }

        // Warning notice
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/components/notice-box.php';

        // Confirm button
        echo $this->render_confirm_button();

        return ob_get_clean();
    }

    /**
     * Render empty message
     *
     * @since 2.0.0
     * @return string HTML output
     */
    private function render_empty_message()
    {
        ob_start();
        ?>
        <div class="notice notice-warning" style="padding: 20px; border-right: 4px solid #ffb900;">
            <h3 style="margin-top: 0;">⚠️ <?php echo esc_html__('No products found or invalid input', 'bulk-price-discount-editor'); ?></h3>
            <p style="margin-bottom: 10px;"><b><?php echo esc_html__('Please check the following:', 'bulk-price-discount-editor'); ?></b></p>
            <ul style="list-style: disc; margin-right: 20px;">
                <li><?php echo esc_html__('Only one of "Percentage" or "Fixed Amount" fields should be filled', 'bulk-price-discount-editor'); ?></li>
                <li><?php echo esc_html__('Products exist with your selected filters', 'bulk-price-discount-editor'); ?></li>
                <li><?php echo esc_html__('Input value is greater than zero', 'bulk-price-discount-editor'); ?></li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render confirm button
     *
     * @since 2.0.0
     * @return string HTML output
     */
    private function render_confirm_button()
    {
        ob_start();
        ?>
        <p style="margin-top: 25px; text-align: center;">
            <button id="sbp-confirm-btn" class="button button-primary button-hero" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); border: none; padding: 15px 50px; font-size: 16px; box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3); transition: all 0.3s;">
                ✅ <?php echo esc_html__('Confirm and Apply', 'bulk-price-discount-editor'); ?>
            </button>
        </p>
        <?php
        return ob_get_clean();
    }
}
