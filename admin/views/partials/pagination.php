<?php
/**
 * Pagination Partial
 *
 * Shows a window of pages around the current one (plus first/last) so large
 * catalogs don't render hundreds of page links.
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$bulk_pricer_pages = array_unique(array_filter(array_merge(
    array(1, $total_pages),
    range(max(1, $page - 2), min($total_pages, $page + 2))
)));
sort($bulk_pricer_pages);
$bulk_pricer_previous = 0;
?>
<div class="sbp-pagination">
    <span class="sbp-pagination__label"><?php echo esc_html__('Page:', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
    <?php foreach ($bulk_pricer_pages as $bulk_pricer_i) : ?>
        <?php if ($bulk_pricer_previous && $bulk_pricer_i - $bulk_pricer_previous > 1) : ?>
            <span class="sbp-pagination__gap">…</span>
        <?php endif; ?>
        <a class="sbp-page-link <?php echo ($bulk_pricer_i == $page) ? 'current' : ''; ?>" data-page="<?php echo esc_attr($bulk_pricer_i); ?>">
            <?php echo esc_html($bulk_pricer_i); ?>
        </a>
        <?php $bulk_pricer_previous = $bulk_pricer_i; ?>
    <?php endforeach; ?>
</div>
