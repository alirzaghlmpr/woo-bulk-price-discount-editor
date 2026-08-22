<?php
/**
 * Main Admin Page Template
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$bulk_pricer_currency = get_woocommerce_currency_symbol();
?>
<div class="wrap">
    <h1><?php echo esc_html__('Bulk Price & Discount Manager (Pro)', 'bulk-price-discount-editor-for-woocommerce'); ?></h1>

    <div id="sbp-batch-status" class="sbp-hidden"></div>

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
</div>
