<?php
/**
 * Summary Bar Component
 *
 * Placeholder markup; the numbers are calculated for the whole product set in
 * chunks and filled in by the browser (see bulk-pricer-admin.js).
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="sbp-summary" class="sbp-summary">
    <div class="sbp-summary__items">
        <div class="sbp-summary__item">
            <span class="sbp-summary__label"><?php echo esc_html__('In scope', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
            <strong data-sbp-sum="scope">…</strong>
        </div>
        <div class="sbp-summary__item">
            <span class="sbp-summary__label"><?php echo esc_html__('Will change', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
            <strong data-sbp-sum="changed">…</strong>
            <small class="sbp-muted" data-sbp-sum="direction"></small>
        </div>
        <div class="sbp-summary__item">
            <span class="sbp-summary__label"><?php echo esc_html__('Unchanged', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
            <strong data-sbp-sum="unchanged">…</strong>
        </div>
        <div class="sbp-summary__item">
            <span class="sbp-summary__label"><?php echo esc_html__('Excluded', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
            <strong data-sbp-sum="excluded">0</strong>
        </div>
        <div class="sbp-summary__item">
            <span class="sbp-summary__label"><?php echo esc_html__('Net price impact', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
            <strong data-sbp-sum="impact">…</strong>
            <small class="sbp-muted" data-sbp-sum="average"></small>
        </div>
    </div>
    <div class="sbp-summary__footer">
        <span class="sbp-summary__progress" data-sbp-sum="progress"></span>
        <button type="button" id="sbp-export-btn" class="button">
            ⬇️ <?php echo esc_html__('Export all as CSV', 'bulk-price-discount-editor-for-woocommerce'); ?>
        </button>
    </div>
</div>
