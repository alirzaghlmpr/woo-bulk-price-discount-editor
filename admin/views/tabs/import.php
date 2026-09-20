<?php
/**
 * Import CSV tab
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="sbp-card">
    <h2><?php echo esc_html__('Update prices from a CSV file', 'bulk-price-discount-editor-for-woocommerce'); ?></h2>
    <p><?php echo esc_html__('Upload a CSV to set exact prices per product. You will see a preview and confirm before anything is changed, and the import is recorded in History so it can be undone.', 'bulk-price-discount-editor-for-woocommerce'); ?></p>

    <table class="widefat striped sbp-csv-help">
        <thead>
            <tr>
                <th><?php echo esc_html__('Column', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                <th><?php echo esc_html__('Meaning', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>sku</code> / <code>id</code></td>
                <td><?php echo esc_html__('Required. Identifies the product. For variable products use the SKU or ID of the variation.', 'bulk-price-discount-editor-for-woocommerce'); ?></td>
            </tr>
            <tr>
                <td><code>regular_price</code></td>
                <td><?php echo esc_html__('New regular price. Leave empty to keep the current one.', 'bulk-price-discount-editor-for-woocommerce'); ?></td>
            </tr>
            <tr>
                <td><code>sale_price</code></td>
                <td><?php echo esc_html__('New sale price. Use 0 to remove the sale. Leave empty to keep the current one.', 'bulk-price-discount-editor-for-woocommerce'); ?></td>
            </tr>
            <tr>
                <td><code>sale_start</code> / <code>sale_end</code></td>
                <td><?php echo esc_html__('Optional sale dates as YYYY-MM-DD (site timezone).', 'bulk-price-discount-editor-for-woocommerce'); ?></td>
            </tr>
        </tbody>
    </table>
    <p class="description"><?php echo esc_html__('Comma, semicolon or tab separated files are accepted. Example: sku,regular_price,sale_price', 'bulk-price-discount-editor-for-woocommerce'); ?></p>

    <form id="sbp-import-form" enctype="multipart/form-data">
        <p>
            <input type="file" name="sbp_csv" accept=".csv,.txt,text/csv" required>
            <button type="submit" class="button button-primary" id="sbp-import-upload-btn">
                📤 <?php echo esc_html__('Upload & Preview', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </button>
        </p>
    </form>
</div>

<div id="sbp-import-results"></div>
