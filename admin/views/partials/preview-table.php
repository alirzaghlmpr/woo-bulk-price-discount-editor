<?php
/**
 * Preview Table Partial
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="sbp-table-wrap">
    <table class="wp-list-table widefat fixed striped sbp-preview-table">
        <thead>
            <tr>
                <th style="width: 30px;"></th>
                <th style="width: 50px;"><?php echo esc_html__('Image', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                <th style="width: 17%;"><?php echo esc_html__('Product Name', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                <th style="width: 7%;"><?php echo esc_html__('Status', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                <th style="width: 9%;">
                    <?php echo esc_html__('Regular Price', 'bulk-price-discount-editor-for-woocommerce'); ?><br>
                    <small><?php echo esc_html__('(Before → After)', 'bulk-price-discount-editor-for-woocommerce'); ?></small>
                </th>
                <th style="width: 9%;">
                    <?php echo esc_html__('Sale Price', 'bulk-price-discount-editor-for-woocommerce'); ?><br>
                    <small><?php echo esc_html__('(Before → After)', 'bulk-price-discount-editor-for-woocommerce'); ?></small>
                </th>
                <th style="width: 8%;">
                    <?php echo esc_html__('Discount %', 'bulk-price-discount-editor-for-woocommerce'); ?><br>
                    <small><?php echo esc_html__('(Before → After)', 'bulk-price-discount-editor-for-woocommerce'); ?></small>
                </th>
                <th style="width: 8%;"><?php echo esc_html__('Start Date', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                <th style="width: 8%;"><?php echo esc_html__('End Date', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                <th style="width: 8%;"><?php echo esc_html__('Price Difference', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                <th style="width: 8%;"><?php echo esc_html__('Final Price', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($preview_data as $bulk_pricer_item): ?>
                <?php
                $bulk_pricer_status_badge = $bulk_pricer_item['is_on_sale']
                    ? '<span class="badge-sale">' . esc_html__('On Sale', 'bulk-price-discount-editor-for-woocommerce') . '</span>'
                    : '<span class="sbp-muted">' . esc_html__('Regular', 'bulk-price-discount-editor-for-woocommerce') . '</span>';

                $bulk_pricer_row_class = $bulk_pricer_item['price_changed'] ? 'price-changed' : '';

                // Regular price display
                $bulk_pricer_reg_display = esc_html($bulk_pricer_item['old_reg_formatted']);
                if ($bulk_pricer_item['old_reg'] != $bulk_pricer_item['new_reg']) {
                    $bulk_pricer_reg_display .= ' <span class="sbp-arrow-up">→</span> <strong class="sbp-arrow-up">' . esc_html($bulk_pricer_item['new_reg_formatted']) . '</strong>';
                }

                // Sale price display
                $bulk_pricer_sale_display = $bulk_pricer_item['old_sale'] > 0 ? esc_html($bulk_pricer_item['old_sale_formatted']) : '<span class="sbp-muted">-</span>';
                if ($bulk_pricer_item['old_sale'] != $bulk_pricer_item['new_sale']) {
                    if ($bulk_pricer_item['new_sale'] > 0) {
                        $bulk_pricer_sale_display .= ' <span class="sbp-arrow-sale">→</span> <strong class="sbp-arrow-sale">' . esc_html($bulk_pricer_item['new_sale_formatted']) . '</strong>';
                    } else {
                        $bulk_pricer_sale_display .= ' <span class="sbp-arrow-remove">→</span> <strong class="sbp-muted">' . esc_html__('Removed', 'bulk-price-discount-editor-for-woocommerce') . '</strong>';
                    }
                } elseif ($bulk_pricer_item['new_sale'] > 0 && $bulk_pricer_item['old_sale'] == $bulk_pricer_item['new_sale']) {
                    $bulk_pricer_sale_display = '<span>' . esc_html($bulk_pricer_item['new_sale_formatted']) . '</span>';
                }

                // Discount percentage display
                if ($bulk_pricer_item['old_discount_percent'] > 0) {
                    $bulk_pricer_discount_display = '<span class="discount-badge discount-before">' . esc_html($bulk_pricer_item['old_discount_percent']) . '%</span>';
                } else {
                    $bulk_pricer_discount_display = '<span class="sbp-muted">-</span>';
                }

                if ($bulk_pricer_item['old_discount_percent'] != $bulk_pricer_item['new_discount_percent']) {
                    if ($bulk_pricer_item['new_discount_percent'] > 0) {
                        $bulk_pricer_discount_display .= ' <span class="sbp-arrow-sale">→</span> <span class="discount-badge discount-after">' . esc_html($bulk_pricer_item['new_discount_percent']) . '%</span>';
                    } else {
                        $bulk_pricer_discount_display .= ' <span class="sbp-arrow-remove">→</span> <span class="sbp-muted">0%</span>';
                    }
                } elseif ($bulk_pricer_item['new_discount_percent'] > 0 && $bulk_pricer_item['old_discount_percent'] == $bulk_pricer_item['new_discount_percent']) {
                    $bulk_pricer_discount_display = '<span class="discount-badge discount-after">' . esc_html($bulk_pricer_item['new_discount_percent']) . '%</span>';
                }

                // Price difference display
                $bulk_pricer_diff_display = '<span class="sbp-muted">-</span>';
                if ($bulk_pricer_item['price_diff'] > 0) {
                    $bulk_pricer_sign = $bulk_pricer_item['price_diff_type'] == 'increase' ? '+' : '-';
                    $bulk_pricer_diff_class = $bulk_pricer_item['price_diff_type'] == 'increase' ? 'price-increase' : 'price-decrease';
                    $bulk_pricer_diff_display = '<span class="' . esc_attr($bulk_pricer_diff_class) . '">' . esc_html($bulk_pricer_sign . ' ' . $bulk_pricer_item['price_diff_formatted']) . '</span>';
                }

                // Sync badge
                $bulk_pricer_sync_badge = $bulk_pricer_item['sync_applied'] ? '<br><span class="badge-sync">SYNC</span>' : '';

                // Capped badge (requested discount met/exceeded the regular price)
                $bulk_pricer_cap_badge = !empty($bulk_pricer_item['sale_capped'])
                    ? '<br><span class="badge-cap" title="' . esc_attr__('Discount exceeded the price and was limited to 10% off', 'bulk-price-discount-editor-for-woocommerce') . '">' . esc_html__('Capped 10%', 'bulk-price-discount-editor-for-woocommerce') . '</span>'
                    : '';

                // Limited badge (the price floor/ceiling stopped the full change)
                $bulk_pricer_limit_badge = !empty($bulk_pricer_item['limited'])
                    ? '<br><span class="badge-cap" title="' . esc_attr__('The change was limited by your minimum/maximum price', 'bulk-price-discount-editor-for-woocommerce') . '">' . esc_html__('Limited', 'bulk-price-discount-editor-for-woocommerce') . '</span>'
                    : '';
                ?>
                <tr class="<?php echo esc_attr($bulk_pricer_row_class); ?>" data-product-id="<?php echo esc_attr($bulk_pricer_item['product_id']); ?>">
                    <td>
                        <button type="button" class="sbp-delete-row" title="<?php echo esc_attr__('Remove from list', 'bulk-price-discount-editor-for-woocommerce'); ?>">×</button>
                    </td>
                    <td>
                        <img class="sbp-thumb" src="<?php echo esc_url($bulk_pricer_item['image']); ?>" alt="<?php echo esc_attr($bulk_pricer_item['name']); ?>">
                    </td>
                    <td class="sbp-col-name">
                        <strong><?php echo esc_html($bulk_pricer_item['name']); ?></strong>
                        <?php if (!empty($bulk_pricer_item['is_variation'])) : ?>
                            <span class="badge-variation"><?php echo esc_html__('Variation', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($bulk_pricer_item['sku'])) : ?>
                            <br><small class="sbp-muted"><?php echo esc_html($bulk_pricer_item['sku']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo wp_kses_post($bulk_pricer_status_badge); ?></td>
                    <td><?php echo wp_kses_post($bulk_pricer_reg_display) . ' ' . esc_html($bulk_pricer_currency); ?></td>
                    <td>
                        <?php echo wp_kses_post($bulk_pricer_sale_display); ?>
                        <?php if ($bulk_pricer_item['new_sale'] > 0 || $bulk_pricer_item['old_sale'] > 0): ?>
                            <?php echo ' ' . esc_html($bulk_pricer_currency); ?>
                        <?php endif; ?>
                        <?php echo wp_kses_post($bulk_pricer_sync_badge); ?>
                        <?php echo wp_kses_post($bulk_pricer_cap_badge); ?>
                        <?php echo wp_kses_post($bulk_pricer_limit_badge); ?>
                    </td>
                    <td><?php echo wp_kses_post($bulk_pricer_discount_display); ?></td>
                    <td class="sbp-col-date"><?php echo esc_html($bulk_pricer_item['sale_start']); ?></td>
                    <td class="sbp-col-date"><?php echo esc_html($bulk_pricer_item['sale_end']); ?></td>
                    <td><?php echo wp_kses_post($bulk_pricer_diff_display) . ' ' . esc_html($bulk_pricer_currency); ?></td>
                    <td class="sbp-col-final">
                        <strong class="sbp-final-price"><?php echo esc_html($bulk_pricer_item['new_final']) . ' ' . esc_html($bulk_pricer_currency); ?></strong>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
