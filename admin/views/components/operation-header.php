<?php
/**
 * Operation Header Component
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get formatter instance
$bulk_pricer_formatter = new Bulk_Pricer_Formatter();

// Get operation details
$bulk_pricer_operation_label = $bulk_pricer_formatter->get_operation_label($bulk_pricer_operation_type);
$bulk_pricer_operation_icon = $bulk_pricer_formatter->get_operation_icon($bulk_pricer_operation_type);

// Get change text
$bulk_pricer_change_text = '';
if ($bulk_pricer_change_percent > 0) {
    $bulk_pricer_change_text = $bulk_pricer_change_percent . '%';
} elseif ($bulk_pricer_change_fixed > 0) {
    $bulk_pricer_change_text = number_format($bulk_pricer_change_fixed) . ' ' . $bulk_pricer_currency;
}
?>
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; margin-bottom: 25px; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <h2 style="margin: 0 0 15px 0; color: white;">
        <?php echo wp_kses_post($bulk_pricer_operation_icon); ?> <?php echo esc_html__('Preview Changes', 'bulk-price-discount-editor-for-woocommerce'); ?>
    </h2>
    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        <div>
            <strong>📋 <?php echo esc_html__('Operation:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
            <?php echo esc_html($bulk_pricer_operation_label); ?>
        </div>
        <?php if ($bulk_pricer_change_text): ?>
            <div>
                <strong>📊 <?php echo esc_html__('Change Amount:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
                <?php echo esc_html($bulk_pricer_change_text); ?>
            </div>
        <?php endif; ?>
        <div>
            <strong>🔢 <?php echo esc_html__('Total Products:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
            <?php echo esc_html($total_count); ?>
        </div>
        <div>
            <strong>🔄 <?php echo esc_html__('Sync:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
            <?php echo $bulk_pricer_sync ? '✅ ' . esc_html__('Enabled', 'bulk-price-discount-editor-for-woocommerce') : '❌ ' . esc_html__('Disabled', 'bulk-price-discount-editor-for-woocommerce'); ?>
        </div>
    </div>
</div>
