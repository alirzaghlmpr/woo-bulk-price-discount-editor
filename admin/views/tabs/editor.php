<?php
/**
 * Bulk Editor tab
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="sbp-card">
    <form id="sbp-form">
        <?php include BULK_PRICER_PLUGIN_DIR . 'admin/views/partials/form-fields.php'; ?>

        <p class="sbp-actions">
            <button type="button" id="sbp-preview-btn" class="button button-primary button-large">
                🔍 <?php echo esc_html__('Preview & Review Changes', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </button>
        </p>
    </form>
</div>

<div id="sbp-results"></div>
