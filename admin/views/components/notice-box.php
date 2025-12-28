<?php
/**
 * Notice Box Component
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div style="margin-top: 25px; padding: 20px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(238, 90, 111, 0.3);">
    <div style="display: flex; align-items: center; gap: 15px;">
        <div style="font-size: 40px;">⚠️</div>
        <div>
            <h3 style="margin: 0 0 8px 0; color: white;">
                <?php echo esc_html__('Warning: Irreversible Operation', 'bulk-price-discount-editor'); ?>
            </h3>
            <p style="margin: 0;">
                <?php
                echo sprintf(
                    esc_html__('By clicking the button below, changes will be %1$spermanently%2$s applied to %3$s%4$d products%5$s.', 'bulk-price-discount-editor'),
                    '<strong>',
                    '</strong>',
                    '<strong>',
                    $total_count,
                    '</strong>'
                );
                ?>
            </p>
        </div>
    </div>
</div>
