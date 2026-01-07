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
    <h1><?php echo esc_html__('Bulk Price & Discount Manager (Pro)', 'bluk-price-discount-editor'); ?></h1>

    <div id="sbp-batch-status" style="display:none; margin-top: 20px;"></div>

    <div class="sbp-card" style="max-width: 1100px; padding: 25px; background: #fff; border: 1px solid #ccd0d4; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <form id="sbp-form">
            <?php include BULK_PRICER_PLUGIN_DIR . 'admin/views/partials/form-fields.php'; ?>

            <p style="margin-top: 20px;">
                <button type="button" id="sbp-preview-btn" class="button button-primary button-large" style="padding: 8px 30px;">
                    🔍 <?php echo esc_html__('Preview & Review Changes', 'bluk-price-discount-editor'); ?>
                </button>
            </p>
        </form>
    </div>

    <div id="sbp-results"></div>
</div>
