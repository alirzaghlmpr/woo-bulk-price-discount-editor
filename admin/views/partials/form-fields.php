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

$bulk_pricer_operations = Bulk_Pricer_Operations::all();
$bulk_pricer_groups = Bulk_Pricer_Operations::groups();
$bulk_pricer_timezone = wp_timezone_string();
?>
<table class="form-table">
    <tr>
        <th><?php echo esc_html__('Operation Type', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <select name="operation_type" class="widefat sbp-input--medium">
                <?php foreach ($bulk_pricer_groups as $bulk_pricer_group_key => $bulk_pricer_group_label) : ?>
                    <optgroup label="<?php echo esc_attr($bulk_pricer_group_label); ?>">
                        <?php foreach ($bulk_pricer_operations as $bulk_pricer_type => $bulk_pricer_op) : ?>
                            <?php if ($bulk_pricer_op['group'] !== $bulk_pricer_group_key) { continue; } ?>
                            <option value="<?php echo esc_attr($bulk_pricer_type); ?>"
                                    data-input="<?php echo esc_attr($bulk_pricer_op['input']); ?>"
                                    data-sync="<?php echo $bulk_pricer_op['sync'] ? '1' : '0'; ?>"
                                    data-dates="<?php echo $bulk_pricer_op['dates'] ? '1' : '0'; ?>">
                                <?php echo esc_html($bulk_pricer_op['icon'] . ' ' . $bulk_pricer_op['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <p class="description sbp-op-description" data-for="round_prices">
                <?php echo esc_html__('Rounds the current prices using the rounding options below without changing them otherwise.', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </p>
            <p class="description sbp-op-description" data-for="sale_to_regular">
                <?php echo esc_html__('The sale price becomes the regular price and the sale is removed. Only products with a sale price are affected.', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </p>
            <p class="description sbp-op-description" data-for="increase_sale decrease_sale">
                <?php echo esc_html__('Changes only the sale price, by a percentage or fixed amount of the current sale price. Only products with a sale price are affected.', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </p>
        </td>
    </tr>
    <tr class="sbp-row-change">
        <th><?php echo esc_html__('Change Amount', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <div class="sbp-change-row">
                <div class="sbp-change-col">
                    <label class="sbp-field-label"><b>📊 <?php echo esc_html__('Percentage (%)', 'bulk-price-discount-editor-for-woocommerce'); ?></b></label>
                    <input type="number" name="change_percent" class="widefat sbp-field" placeholder="<?php echo esc_attr__('e.g. 10', 'bulk-price-discount-editor-for-woocommerce'); ?>" step="any" min="0">
                </div>
                <div class="sbp-change-col">
                    <label class="sbp-field-label"><b>💰 <?php echo esc_html__('Fixed Amount', 'bulk-price-discount-editor-for-woocommerce'); ?> (<?php echo esc_html($bulk_pricer_currency); ?>)</b></label>
                    <input type="number" name="change_fixed" class="widefat sbp-field" placeholder="<?php echo esc_attr__('e.g. 5000', 'bulk-price-discount-editor-for-woocommerce'); ?>" step="any" min="0">
                </div>
            </div>
            <p class="description sbp-hint-warning">
                ⚠️ <b><?php echo esc_html__('Only one', 'bulk-price-discount-editor-for-woocommerce'); ?></b> <?php echo esc_html__('of the two fields above should be filled. If both are filled, no changes will be applied.', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </p>
        </td>
    </tr>
    <tr class="sbp-row-exact">
        <th><?php echo esc_html__('New Price', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <input type="number" name="exact_price" class="widefat sbp-field" placeholder="<?php echo esc_attr__('e.g. 12000', 'bulk-price-discount-editor-for-woocommerce'); ?>" step="any" min="0">
            <span class="sbp-inline-symbol"><?php echo esc_html($bulk_pricer_currency); ?></span>
            <p class="description"><?php echo esc_html__('Every selected product gets exactly this price.', 'bulk-price-discount-editor-for-woocommerce'); ?></p>
        </td>
    </tr>
    <tr>
        <th><?php echo esc_html__('Advanced Settings', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <label class="sbp-option-label sbp-option-label--filter">
                <input type="checkbox" name="only_on_sale" value="1">
                <b>🎯 <?php echo esc_html__('Product Filter:', 'bulk-price-discount-editor-for-woocommerce'); ?></b> <?php echo esc_html__('Process only products currently on sale', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </label>
            <label class="sbp-option-label sbp-option-label--sync sbp-row-sync">
                <input type="checkbox" name="sync_sale" value="1" checked>
                <b>🔄 <?php echo esc_html__('Sync Sale Price:', 'bulk-price-discount-editor-for-woocommerce'); ?></b> <?php echo esc_html__('When changing regular price, also change sale price proportionally', 'bulk-price-discount-editor-for-woocommerce'); ?>
                <br><small class="sbp-option-hint">
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
                'class' => 'widefat sbp-input--medium',
                'hierarchical' => 1,
            ));
            ?>
        </td>
    </tr>
    <tr class="sbp-row-rounding">
        <th><?php echo esc_html__('Price Rounding', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <select name="round_mode" class="sbp-input--medium">
                <option value="none"><?php echo esc_html__('No rounding (store decimals)', 'bulk-price-discount-editor-for-woocommerce'); ?></option>
                <option value="nearest"><?php echo esc_html__('Round to the nearest multiple of…', 'bulk-price-discount-editor-for-woocommerce'); ?></option>
                <option value="up"><?php echo esc_html__('Round up to a multiple of…', 'bulk-price-discount-editor-for-woocommerce'); ?></option>
                <option value="down"><?php echo esc_html__('Round down to a multiple of…', 'bulk-price-discount-editor-for-woocommerce'); ?></option>
                <option value="ending"><?php echo esc_html__('Make prices end in…', 'bulk-price-discount-editor-for-woocommerce'); ?></option>
            </select>
            <span class="sbp-round-step">
                <input type="number" name="round_step" class="sbp-field" step="any" min="0" placeholder="<?php echo esc_attr__('e.g. 1000', 'bulk-price-discount-editor-for-woocommerce'); ?>">
            </span>
            <span class="sbp-round-ending">
                <input type="text" name="round_ending" class="sbp-field" maxlength="12" placeholder="<?php echo esc_attr__('e.g. 99, .99 or 900', 'bulk-price-discount-editor-for-woocommerce'); ?>">
            </span>
            <p class="description sbp-round-help sbp-round-help--step">
                <?php echo esc_html__('Example: a step of 1000 turns 12,340 into 12,000 (nearest), 13,000 (up) or 12,000 (down).', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </p>
            <p class="description sbp-round-help sbp-round-help--ending">
                <?php echo esc_html__('Example: ".99" turns 19.60 into 19.99; "900" turns 12,340 into 11,900; "99" turns 1,234 into 1,199. Uses the closest price with that ending.', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </p>
        </td>
    </tr>
    <tr class="sbp-row-limits">
        <th><?php echo esc_html__('Price Limits', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <div class="sbp-change-row">
                <div class="sbp-change-col">
                    <label class="sbp-field-label"><b>⛔ <?php echo esc_html__('Never lower a price below', 'bulk-price-discount-editor-for-woocommerce'); ?> (<?php echo esc_html($bulk_pricer_currency); ?>)</b></label>
                    <input type="number" name="price_floor" class="widefat sbp-field" step="any" min="0" placeholder="<?php echo esc_attr__('No minimum', 'bulk-price-discount-editor-for-woocommerce'); ?>">
                </div>
                <div class="sbp-change-col">
                    <label class="sbp-field-label"><b>⛔ <?php echo esc_html__('Never raise a price above', 'bulk-price-discount-editor-for-woocommerce'); ?> (<?php echo esc_html($bulk_pricer_currency); ?>)</b></label>
                    <input type="number" name="price_ceiling" class="widefat sbp-field" step="any" min="0" placeholder="<?php echo esc_attr__('No maximum', 'bulk-price-discount-editor-for-woocommerce'); ?>">
                </div>
            </div>
            <p class="description">
                <?php echo esc_html__('Protects against going too low or too high. A product already outside the limit is left as it is, never pushed further.', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </p>
        </td>
    </tr>
    <tr class="sbp-row-dates">
        <th><?php echo esc_html__('Sale Start Date', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <input type="date" name="sale_start" class="widefat sbp-input--narrow">
            <p class="description"><?php echo esc_html__('For \'Apply Sale Price\' operation, you can set a start date', 'bulk-price-discount-editor-for-woocommerce'); ?></p>
        </td>
    </tr>
    <tr class="sbp-row-dates">
        <th><?php echo esc_html__('Sale End Date', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <input type="date" name="sale_expiry" class="widefat sbp-input--narrow">
            <p class="description"><?php echo esc_html__('For \'Apply Sale Price\' operation, you can set an expiry date', 'bulk-price-discount-editor-for-woocommerce'); ?></p>
        </td>
    </tr>
    <tr class="sbp-row-schedule">
        <th><?php echo esc_html__('When to Apply', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <label class="sbp-radio-label">
                <input type="radio" name="apply_mode" value="now" checked>
                <?php echo esc_html__('Apply immediately after confirming', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </label>
            <label class="sbp-radio-label">
                <input type="radio" name="apply_mode" value="schedule">
                <?php echo esc_html__('Schedule for later', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </label>
            <div class="sbp-schedule-at">
                <input type="datetime-local" name="apply_at" class="sbp-input--narrow">
            </div>
        </td>
    </tr>
    <tr class="sbp-row-schedule">
        <th><?php echo esc_html__('Auto-Restore', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
        <td>
            <input type="datetime-local" name="revert_at" class="sbp-input--narrow">
            <p class="description">
                <?php echo esc_html__('Optional. Automatically put the original prices back at this time — handy for time-limited campaigns.', 'bulk-price-discount-editor-for-woocommerce'); ?>
                <br>
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: site timezone, e.g. Asia/Tehran */
                    __('Times use the site timezone (%s). Scheduled changes run through WooCommerce\'s Action Scheduler, which needs site visits or a server cron to fire.', 'bulk-price-discount-editor-for-woocommerce'),
                    $bulk_pricer_timezone
                ));
                ?>
            </p>
        </td>
    </tr>
</table>
