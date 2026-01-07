<?php
/**
 * Form Fields Partial
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<table class="form-table">
    <tr>
        <th style="width: 200px;"><?php echo esc_html__('Operation Type', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <select name="operation_type" class="widefat" style="max-width: 400px;">
                <option value="increase_reg">⬆️ <?php echo esc_html__('Increase Regular Price', 'bulk-price-discount-editor-for-woocommerce'); ?></option>
                <option value="decrease_reg">⬇️ <?php echo esc_html__('Decrease Regular Price', 'bulk-price-discount-editor-for-woocommerce'); ?></option>
                <option value="set_sale">🏷️ <?php echo esc_html__('Apply/Update Sale Price', 'bulk-price-discount-editor-for-woocommerce'); ?></option>
                <option value="remove_discount">❌ <?php echo esc_html__('Remove All Discounts', 'bulk-price-discount-editor-for-woocommerce'); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th><?php echo esc_html__('Change Amount', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px;"><b>📊 <?php echo esc_html__('Percentage (%)', 'bulk-price-discount-editor-for-woocommerce'); ?></b></label>
                    <input type="number" name="change_percent" class="widefat" placeholder="<?php echo esc_attr__('e.g. 10', 'bulk-price-discount-editor-for-woocommerce'); ?>" step="0.01" min="0" style="max-width: 200px;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px;"><b>💰 <?php echo esc_html__('Fixed Amount', 'bulk-price-discount-editor-for-woocommerce'); ?> (<?php echo esc_html($bulk_pricer_currency); ?>)</b></label>
                    <input type="number" name="change_fixed" class="widefat" placeholder="<?php echo esc_attr__('e.g. 5000', 'bulk-price-discount-editor-for-woocommerce'); ?>" min="0" style="max-width: 200px;">
                </div>
            </div>
            <p class="description" style="color: #d63638; margin-top: 10px;">
                ⚠️ <b><?php echo esc_html__('Only one', 'bulk-price-discount-editor-for-woocommerce'); ?></b> <?php echo esc_html__('of the two fields above should be filled. If both are filled, no changes will be applied.', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </p>
        </td>
    </tr>
    <tr>
        <th><?php echo esc_html__('Advanced Settings', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <label style="display:block; margin-bottom:10px; padding: 10px; background: #f0f6fc; border-radius: 4px;">
                <input type="checkbox" name="only_on_sale" value="1">
                <b>🎯 <?php echo esc_html__('Product Filter:', 'bulk-price-discount-editor-for-woocommerce'); ?></b> <?php echo esc_html__('Process only products currently on sale', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </label>
            <label style="display:block; padding: 10px; background: #fff3cd; border-radius: 4px;">
                <input type="checkbox" name="sync_sale" value="1" checked>
                <b>🔄 <?php echo esc_html__('Sync Sale Price:', 'bulk-price-discount-editor-for-woocommerce'); ?></b> <?php echo esc_html__('When changing regular price, also change sale price proportionally', 'bulk-price-discount-editor-for-woocommerce'); ?>
                <br><small style="color: #856404; margin-top: 5px; display: block;">
                    💡 <?php echo esc_html__('With this option enabled, the discount percentage stays constant and the sale price changes accordingly.', 'bulk-price-discount-editor-for-woocommerce'); ?>
                    <br><?php echo esc_html__('Example: If discount was 20%, it will remain 20% after the regular price change.', 'bulk-price-discount-editor-for-woocommerce'); ?>
                </small>
            </label>
        </td>
    </tr>
    <tr>
        <th><?php echo esc_html__('Category', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <?php
            wp_dropdown_categories(array(
                'taxonomy' => 'product_cat',
                'name' => 'product_cat_id',
                'show_option_all' => '🗂️ ' . __('All Categories', 'bulk-price-discount-editor-for-woocommerce'),
                'class' => 'widefat',
                'hierarchical' => 1,
                'style' => 'max-width: 400px;'
            ));
            ?>
        </td>
    </tr>
    <tr>
        <th><?php echo esc_html__('Sale Start Date', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <input type="date" name="sale_start" class="widefat" style="max-width: 250px;">
            <p class="description"><?php echo esc_html__('For \'Apply Sale Price\' operation, you can set a start date', 'bulk-price-discount-editor-for-woocommerce'); ?></p>
        </td>
    </tr>
    <tr>
        <th><?php echo esc_html__('Sale End Date', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <input type="date" name="sale_expiry" class="widefat" style="max-width: 250px;">
            <p class="description"><?php echo esc_html__('For \'Apply Sale Price\' operation, you can set an expiry date', 'bulk-price-discount-editor-for-woocommerce'); ?></p>
        </td>
    </tr>
</table>
