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
        $bulk_pricer_currency = get_woocommerce_currency_symbol();
        $bulk_pricer_operation = $operation_data['operation'];
        $bulk_pricer_schedule = $operation_data['schedule'];
        $bulk_pricer_operation_type = $bulk_pricer_operation['operation_type'];
        $bulk_pricer_sync = $bulk_pricer_operation['sync_sale'];

        // Operation header
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/components/operation-header.php';

        // Summary bar (numbers are filled in by the browser)
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/components/summary-bar.php';

        // Preview table
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/partials/preview-table.php';

        // Pagination
        if ($total_pages > 1) {
            include BULK_PRICER_PLUGIN_DIR . 'admin/views/partials/pagination.php';
        }

        // Info notice
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/components/notice-box.php';

        // Confirm button
        echo wp_kses_post($this->render_confirm_button($bulk_pricer_schedule['mode'] === 'schedule'));

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
        <div class="notice notice-warning sbp-empty-notice">
            <h3>⚠️ <?php echo esc_html__('No products found or invalid input', 'bulk-price-discount-editor-for-woocommerce'); ?></h3>
            <p><b><?php echo esc_html__('Please check the following:', 'bulk-price-discount-editor-for-woocommerce'); ?></b></p>
            <ul>
                <li><?php echo esc_html__('Products exist with your selected filters', 'bulk-price-discount-editor-for-woocommerce'); ?></li>
                <li><?php echo esc_html__('Operations that use an existing sale price (increase/decrease sale, make permanent, remove discounts) only list products that already have a sale price', 'bulk-price-discount-editor-for-woocommerce'); ?></li>
                <li><?php echo esc_html__('Input value is greater than zero', 'bulk-price-discount-editor-for-woocommerce'); ?></li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render confirm button
     *
     * @since 2.0.0
     * @param bool $scheduled Whether the changes are scheduled for later
     * @return string HTML output
     */
    private function render_confirm_button($scheduled = false)
    {
        ob_start();
        ?>
        <p class="sbp-confirm-wrap">
            <button id="sbp-confirm-btn" class="button button-primary button-hero" data-scheduled="<?php echo $scheduled ? '1' : '0'; ?>">
                <?php if ($scheduled) : ?>
                    🗓️ <?php echo esc_html__('Schedule Changes', 'bulk-price-discount-editor-for-woocommerce'); ?>
                <?php else : ?>
                    ✅ <?php echo esc_html__('Confirm and Apply', 'bulk-price-discount-editor-for-woocommerce'); ?>
                <?php endif; ?>
            </button>
        </p>
        <?php
        return ob_get_clean();
    }
}
