<?php
/**
 * Pagination Partial
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="sbp-pagination">
    <span style="color: #666; margin-left: 10px;"><?php echo esc_html__('صفحه:', 'bulk-price-discount-editor'); ?></span>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a class="sbp-page-link <?php echo ($i == $page) ? 'current' : ''; ?>" data-page="<?php echo esc_attr($i); ?>">
            <?php echo esc_html($i); ?>
        </a>
    <?php endfor; ?>
</div>
