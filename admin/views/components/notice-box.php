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
                <?php echo esc_html__('Review before you apply', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </h3>
            <p>
                <?php
                echo sprintf(
                    /* translators: %1$s and %2$s: opening and closing strong tag, %3$d: number of products */
                    esc_html__('Changes will be applied to %1$s%3$d products%2$s. Every change is recorded in the History tab, where the run can be reverted.', 'bulk-price-discount-editor-for-woocommerce'),
                    '<strong>',
                    '</strong>',
                    (int) $total_count
                );
                ?>
            </p>
        </div>
    </div>
</div>
