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
$formatter = new Bulk_Pricer_Formatter();

// Get operation details
$operation_label = $formatter->get_operation_label($operation_type);
$operation_icon = $formatter->get_operation_icon($operation_type);

// Get change text
$change_text = '';
if ($change_percent > 0) {
    $change_text = $change_percent . '%';
} elseif ($change_fixed > 0) {
    $change_text = number_format($change_fixed) . ' ' . $currency;
}
?>
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; margin-bottom: 25px; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <h2 style="margin: 0 0 15px 0; color: white;">
        <?php echo $operation_icon; ?> <?php echo esc_html__('Preview Changes', 'bulk-price-discount-editor'); ?>
    </h2>
    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        <div>
            <strong>📋 <?php echo esc_html__('Operation:', 'bulk-price-discount-editor'); ?></strong>
            <?php echo esc_html($operation_label); ?>
        </div>
        <?php if ($change_text): ?>
            <div>
                <strong>📊 <?php echo esc_html__('Change Amount:', 'bulk-price-discount-editor'); ?></strong>
                <?php echo esc_html($change_text); ?>
            </div>
        <?php endif; ?>
        <div>
            <strong>🔢 <?php echo esc_html__('Total Products:', 'bulk-price-discount-editor'); ?></strong>
            <?php echo esc_html($total_count); ?>
        </div>
        <div>
            <strong>🔄 <?php echo esc_html__('Sync:', 'bulk-price-discount-editor'); ?></strong>
            <?php echo $sync ? '✅ ' . esc_html__('Enabled', 'bulk-price-discount-editor') : '❌ ' . esc_html__('Disabled', 'bulk-price-discount-editor'); ?>
        </div>
    </div>
</div>
