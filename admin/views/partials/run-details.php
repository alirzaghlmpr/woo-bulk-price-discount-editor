<?php
/**
 * History run details
 *
 * Expects: $bulk_pricer_run, $bulk_pricer_items, $bulk_pricer_changed, $bulk_pricer_export_url
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$bulk_pricer_product_formatter = new Bulk_Pricer_Product_Data_Formatter();
$bulk_pricer_symbol = get_woocommerce_currency_symbol();
?>
<div class="sbp-run-details">
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Product Name', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                <th><?php echo esc_html__('Regular Price', 'bulk-price-discount-editor-for-woocommerce'); ?> <small>(<?php echo esc_html__('Before → After', 'bulk-price-discount-editor-for-woocommerce'); ?>)</small></th>
                <th><?php echo esc_html__('Sale Price', 'bulk-price-discount-editor-for-woocommerce'); ?> <small>(<?php echo esc_html__('Before → After', 'bulk-price-discount-editor-for-woocommerce'); ?>)</small></th>
                <th><?php echo esc_html__('State', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bulk_pricer_items as $bulk_pricer_item) : ?>
                <?php $bulk_pricer_detail_product = wc_get_product((int) $bulk_pricer_item->product_id); ?>
                <tr>
                    <td>
                        <?php if ($bulk_pricer_detail_product) : ?>
                            <strong><?php echo esc_html($bulk_pricer_detail_product->get_name()); ?></strong>
                            <?php if ($bulk_pricer_detail_product->get_sku()) : ?>
                                <small class="sbp-muted">(<?php echo esc_html($bulk_pricer_detail_product->get_sku()); ?>)</small>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="sbp-muted">#<?php echo esc_html($bulk_pricer_item->product_id); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html($bulk_pricer_item->old_regular !== '' ? $bulk_pricer_product_formatter->format_price($bulk_pricer_item->old_regular) : '-'); ?>
                        →
                        <strong><?php echo esc_html($bulk_pricer_item->new_regular !== '' ? $bulk_pricer_product_formatter->format_price($bulk_pricer_item->new_regular) : '-'); ?></strong>
                        <?php echo esc_html($bulk_pricer_symbol); ?>
                    </td>
                    <td>
                        <?php echo esc_html($bulk_pricer_item->old_sale !== '' ? $bulk_pricer_product_formatter->format_price($bulk_pricer_item->old_sale) : '-'); ?>
                        →
                        <strong><?php echo esc_html($bulk_pricer_item->new_sale !== '' ? $bulk_pricer_product_formatter->format_price($bulk_pricer_item->new_sale) : '-'); ?></strong>
                        <?php echo esc_html($bulk_pricer_symbol); ?>
                    </td>
                    <td>
                        <?php echo (int) $bulk_pricer_item->status === Bulk_Pricer_Run_Model::ITEM_REVERTED
                            ? esc_html__('Reverted', 'bulk-price-discount-editor-for-woocommerce')
                            : esc_html__('Applied', 'bulk-price-discount-editor-for-woocommerce'); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p>
        <?php if ($bulk_pricer_changed > count($bulk_pricer_items)) : ?>
            <span class="description">
                <?php
                echo esc_html(sprintf(
                    /* translators: 1: rows shown, 2: total changed products */
                    __('Showing the first %1$d of %2$d changed products.', 'bulk-price-discount-editor-for-woocommerce'),
                    count($bulk_pricer_items),
                    $bulk_pricer_changed
                ));
                ?>
            </span>
        <?php endif; ?>
        <a class="button" href="<?php echo esc_url($bulk_pricer_export_url); ?>">
            ⬇️ <?php echo esc_html__('Download full list as CSV', 'bulk-price-discount-editor-for-woocommerce'); ?>
        </a>
    </p>
</div>
