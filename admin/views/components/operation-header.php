<?php
/**
 * Operation Header Component
 *
 * Expects: $bulk_pricer_operation, $bulk_pricer_operation_type, $bulk_pricer_sync,
 * $bulk_pricer_schedule, $total_count
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
$bulk_pricer_change_text = $bulk_pricer_formatter->describe_amount($bulk_pricer_operation);
$bulk_pricer_rounding_text = $bulk_pricer_formatter->describe_rounding($bulk_pricer_operation);
$bulk_pricer_operation_meta = Bulk_Pricer_Operations::get($bulk_pricer_operation_type);
$bulk_pricer_date_format = get_option('date_format') . ' ' . get_option('time_format');
?>
<div class="sbp-op-header">
    <h2>
        <?php echo wp_kses_post($bulk_pricer_operation_icon); ?> <?php echo esc_html__('Preview Changes', 'bulk-price-discount-editor-for-woocommerce'); ?>
    </h2>
    <div class="sbp-op-header__meta">
        <div>
            <strong>📋 <?php echo esc_html__('Operation:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
            <?php echo esc_html($bulk_pricer_operation_label); ?>
        </div>
        <?php if ($bulk_pricer_change_text) : ?>
            <div>
                <strong>📊 <?php echo esc_html__('Change Amount:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
                <?php echo esc_html($bulk_pricer_change_text); ?>
            </div>
        <?php endif; ?>
        <?php if ($bulk_pricer_rounding_text) : ?>
            <div>
                <strong>🔢 <?php echo esc_html__('Rounding:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
                <?php echo esc_html($bulk_pricer_rounding_text); ?>
            </div>
        <?php endif; ?>
        <div>
            <strong>🔢 <?php echo esc_html__('Total Products:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
            <?php echo esc_html($total_count); ?>
        </div>
        <?php if ($bulk_pricer_operation_meta && $bulk_pricer_operation_meta['sync']) : ?>
            <div>
                <strong>🔄 <?php echo esc_html__('Sync:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
                <?php echo $bulk_pricer_sync ? '✅ ' . esc_html__('Enabled', 'bulk-price-discount-editor-for-woocommerce') : '❌ ' . esc_html__('Disabled', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </div>
        <?php endif; ?>
        <?php if ($bulk_pricer_schedule['mode'] === 'schedule') : ?>
            <div>
                <strong>🗓️ <?php echo esc_html__('Applies on:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
                <?php echo esc_html(wp_date($bulk_pricer_date_format, $bulk_pricer_schedule['apply_at'])); ?>
            </div>
        <?php endif; ?>
        <?php if ($bulk_pricer_schedule['revert_at']) : ?>
            <div>
                <strong>↩️ <?php echo esc_html__('Restores on:', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
                <?php echo esc_html(wp_date($bulk_pricer_date_format, $bulk_pricer_schedule['revert_at'])); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
