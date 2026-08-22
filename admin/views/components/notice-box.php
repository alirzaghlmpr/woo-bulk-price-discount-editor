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
<div class="sbp-warning-box">
    <div class="sbp-warning-box__inner">
        <div class="sbp-warning-box__icon">⚠️</div>
        <div>
            <h3>
                <?php echo esc_html__('Warning: Irreversible Operation', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </h3>
            <p>
                <?php
                echo sprintf(
                    /* translators: %1$s and %2$s: opening and closing strong tag, %3$s and %5$s: opening and closing strong tag, %4$d: number of products */
                    esc_html__('By clicking the button below, changes will be %1$spermanently%2$s applied to %3$s%4$d products%5$s.', 'bulk-price-discount-editor-for-woocommerce'),
                    '<strong>',
                    '</strong>',
                    '<strong>',
                    (int) $total_count,
                    '</strong>'
                );
                ?>
            </p>
        </div>
    </div>
</div>
