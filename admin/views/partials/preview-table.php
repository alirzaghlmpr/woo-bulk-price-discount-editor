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
<div style="overflow-x: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px;">
    <table class="wp-list-table widefat fixed striped" style="border: none;">
        <thead>
            <tr>
                <th style="width: 30px; text-align: center;"></th>
                <th style="width: 50px; text-align: center;"><?php echo esc_html__('Image', 'bluk-price-discount-editor'); ?></th>
                <th style="width: 17%;"><?php echo esc_html__('Product Name', 'bluk-price-discount-editor'); ?></th>
                <th style="width: 7%; text-align: center;"><?php echo esc_html__('Status', 'bluk-price-discount-editor'); ?></th>
                <th style="width: 9%; text-align: center;">
                    <?php echo esc_html__('Regular Price', 'bluk-price-discount-editor'); ?><br>
                    <small><?php echo esc_html__('(Before → After)', 'bluk-price-discount-editor'); ?></small>
                </th>
                <th style="width: 9%; text-align: center;">
                    <?php echo esc_html__('Sale Price', 'bluk-price-discount-editor'); ?><br>
                    <small><?php echo esc_html__('(Before → After)', 'bluk-price-discount-editor'); ?></small>
                </th>
                <th style="width: 8%; text-align: center;">
                    <?php echo esc_html__('Discount %', 'bluk-price-discount-editor'); ?><br>
                    <small><?php echo esc_html__('(Before → After)', 'bluk-price-discount-editor'); ?></small>
                </th>
                <th style="width: 8%; text-align: center;"><?php echo esc_html__('Start Date', 'bluk-price-discount-editor'); ?></th>
                <th style="width: 8%; text-align: center;"><?php echo esc_html__('End Date', 'bluk-price-discount-editor'); ?></th>
                <th style="width: 8%; text-align: center;"><?php echo esc_html__('Price Difference', 'bluk-price-discount-editor'); ?></th>
                <th style="width: 8%; text-align: center;"><?php echo esc_html__('Final Price', 'bluk-price-discount-editor'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($preview_data as $bulk_pricer_item): ?>
                <?php
                $bulk_pricer_status_badge = $bulk_pricer_item['is_on_sale']
                    ? '<span class="badge-sale">' . esc_html__('On Sale', 'bluk-price-discount-editor') . '</span>'
                    : '<span style="color: #666;">' . esc_html__('Regular', 'bluk-price-discount-editor') . '</span>';

                $bulk_pricer_row_class = $bulk_pricer_item['price_changed'] ? 'price-changed' : '';

                // Regular price display
                $bulk_pricer_reg_display = esc_html($bulk_pricer_item['old_reg_formatted']);
                if ($bulk_pricer_item['old_reg'] != $bulk_pricer_item['new_reg']) {
                    $bulk_pricer_reg_display .= ' <span style="color: #2271b1;">→</span> <strong style="color: #2271b1;">' . esc_html($bulk_pricer_item['new_reg_formatted']) . '</strong>';
                }

                // Sale price display
                $bulk_pricer_sale_display = $bulk_pricer_item['old_sale'] > 0 ? esc_html(number_format($bulk_pricer_item['old_sale'])) : '<span style="color: #999;">-</span>';
                if ($bulk_pricer_item['old_sale'] != $bulk_pricer_item['new_sale']) {
                    if ($bulk_pricer_item['new_sale'] > 0) {
                        $bulk_pricer_sale_display .= ' <span style="color: #27ae60;">→</span> <strong style="color: #27ae60;">' . esc_html($bulk_pricer_item['new_sale_formatted']) . '</strong>';
                    } else {
                        $bulk_pricer_sale_display .= ' <span style="color: #e74c3c;">→</span> <strong style="color: #999;">' . esc_html__('Removed', 'bluk-price-discount-editor') . '</strong>';
                    }
                } elseif ($bulk_pricer_item['new_sale'] > 0 && $bulk_pricer_item['old_sale'] == $bulk_pricer_item['new_sale']) {
                    $bulk_pricer_sale_display = '<span style="color: #666;">' . esc_html($bulk_pricer_item['new_sale_formatted']) . '</span>';
                }

                // Discount percentage display
                $bulk_pricer_discount_display = '';
                if ($bulk_pricer_item['old_discount_percent'] > 0) {
                    $bulk_pricer_discount_display = '<span class="discount-badge discount-before">' . esc_html($bulk_pricer_item['old_discount_percent']) . '%</span>';
                } else {
                    $bulk_pricer_discount_display = '<span style="color: #999;">-</span>';
                }

                if ($bulk_pricer_item['old_discount_percent'] != $bulk_pricer_item['new_discount_percent']) {
                    if ($bulk_pricer_item['new_discount_percent'] > 0) {
                        $bulk_pricer_discount_display .= ' <span style="color: #27ae60;">→</span> <span class="discount-badge discount-after">' . esc_html($bulk_pricer_item['new_discount_percent']) . '%</span>';
                    } else {
                        $bulk_pricer_discount_display .= ' <span style="color: #e74c3c;">→</span> <span style="color: #999;">0%</span>';
                    }
                } elseif ($bulk_pricer_item['new_discount_percent'] > 0 && $bulk_pricer_item['old_discount_percent'] == $bulk_pricer_item['new_discount_percent']) {
                    $bulk_pricer_discount_display = '<span class="discount-badge" style="background: #e3f2fd; color: #1976d2; border-color: #1976d2;">' . esc_html($bulk_pricer_item['new_discount_percent']) . '%</span>';
                }

                // Price difference display
                $bulk_pricer_diff_display = '<span style="color: #999;">-</span>';
                if ($bulk_pricer_item['price_diff'] > 0) {
                    $bulk_pricer_sign = $bulk_pricer_item['price_diff_type'] == 'increase' ? '+' : '-';
                    $bulk_pricer_color = $bulk_pricer_item['price_diff_type'] == 'increase' ? '#d32f2f' : '#2e7d32';
                    $bulk_pricer_diff_display = '<span style="color: ' . esc_attr($bulk_pricer_color) . '; font-weight: bold;">' . esc_html($bulk_pricer_sign . ' ' . $bulk_pricer_item['price_diff_formatted']) . '</span>';
                }

                // Sync badge
                $bulk_pricer_sync_badge = $bulk_pricer_item['sync_applied'] ? '<br><span class="badge-sync">SYNC</span>' : '';
                ?>
                <tr class="<?php echo esc_attr($bulk_pricer_row_class); ?>" data-product-id="<?php echo esc_attr($bulk_pricer_item['product_id']); ?>">
                    <td style="text-align: center; padding: 5px;">
                        <button type="button" class="sbp-delete-row" style="background: #dc3545; color: white; border: none; border-radius: 3px; width: 24px; height: 24px; cursor: pointer; font-size: 16px; line-height: 1; padding: 0;" title="<?php echo esc_attr__('Remove from list', 'bluk-price-discount-editor'); ?>">×</button>
                    </td>
                    <td style="text-align: center; padding: 5px;">
                        <img src="<?php echo esc_url($bulk_pricer_item['image']); ?>" alt="<?php echo esc_attr($bulk_pricer_item['name']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                    </td>
                    <td><strong><?php echo esc_html($bulk_pricer_item['name']); ?></strong></td>
                    <td style="text-align: center;"><?php echo wp_kses_post($bulk_pricer_status_badge); ?></td>
                    <td style="text-align: center;"><?php echo wp_kses_post($bulk_pricer_reg_display) . ' ' . esc_html($bulk_pricer_currency); ?></td>
                    <td style="text-align: center;">
                        <?php echo wp_kses_post($bulk_pricer_sale_display); ?>
                        <?php if ($bulk_pricer_item['new_sale'] > 0 || $bulk_pricer_item['old_sale'] > 0): ?>
                            <?php echo ' ' . esc_html($bulk_pricer_currency); ?>
                        <?php endif; ?>
                        <?php echo wp_kses_post($bulk_pricer_sync_badge); ?>
                    </td>
                    <td style="text-align: center;"><?php echo wp_kses_post($bulk_pricer_discount_display); ?></td>
                    <td style="text-align: center; font-size: 12px;"><?php echo esc_html($bulk_pricer_item['sale_start']); ?></td>
                    <td style="text-align: center; font-size: 12px;"><?php echo esc_html($bulk_pricer_item['sale_end']); ?></td>
                    <td style="text-align: center;"><?php echo wp_kses_post($bulk_pricer_diff_display) . ' ' . esc_html($bulk_pricer_currency); ?></td>
                    <td style="text-align: center; font-size: 15px;">
                        <strong style="color: #27ae60;"><?php echo esc_html($bulk_pricer_item['new_final']) . ' ' . esc_html($bulk_pricer_currency); ?></strong>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
