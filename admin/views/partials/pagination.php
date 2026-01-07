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
    <span style="color: #666; margin-left: 10px;"><?php echo esc_html__('صفحه:', 'bluk-price-discount-editor'); ?></span>
    <?php for ($bulk_pricer_i = 1; $bulk_pricer_i <= $total_pages; $bulk_pricer_i++): ?>
        <a class="sbp-page-link <?php echo ($bulk_pricer_i == $page) ? 'current' : ''; ?>" data-page="<?php echo esc_attr($bulk_pricer_i); ?>">
            <?php echo esc_html($bulk_pricer_i); ?>
        </a>
    <?php endfor; ?>
</div>
