<?php
/**
 * CSV import preview
 *
 * Expects: $bulk_pricer_run_id, $bulk_pricer_filename, $bulk_pricer_counts,
 * $bulk_pricer_rows, $bulk_pricer_errors, $bulk_pricer_currency,
 * $bulk_pricer_formatter
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="sbp-card">
    <h2>📄 <?php echo esc_html($bulk_pricer_filename); ?></h2>

    <div class="sbp-summary__items sbp-import-counts">
        <div class="sbp-summary__item">
            <span class="sbp-summary__label"><?php echo esc_html__('Ready to update', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
            <strong><?php echo esc_html($bulk_pricer_counts['ready']); ?></strong>
        </div>
        <div class="sbp-summary__item">
            <span class="sbp-summary__label"><?php echo esc_html__('Already up to date', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
            <strong><?php echo esc_html($bulk_pricer_counts['unchanged']); ?></strong>
        </div>
        <div class="sbp-summary__item">
            <span class="sbp-summary__label"><?php echo esc_html__('Rows with problems', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
            <strong class="<?php echo $bulk_pricer_counts['errors'] ? 'price-increase' : ''; ?>"><?php echo esc_html($bulk_pricer_counts['errors']); ?></strong>
        </div>
        <?php if ($bulk_pricer_counts['duplicates']) : ?>
            <div class="sbp-summary__item">
                <span class="sbp-summary__label"><?php echo esc_html__('Duplicate rows (last one used)', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
                <strong><?php echo esc_html($bulk_pricer_counts['duplicates']); ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($bulk_pricer_errors)) : ?>
        <details class="sbp-import-errors" <?php echo empty($bulk_pricer_rows) ? 'open' : ''; ?>>
            <summary><?php echo esc_html__('Rows with problems (these will be skipped)', 'bulk-price-discount-editor-for-woocommerce'); ?></summary>
            <ul>
                <?php foreach ($bulk_pricer_errors as $bulk_pricer_error) : ?>
                    <li>
                        <?php
                        echo esc_html(sprintf(
                            /* translators: 1: CSV line number, 2: problem description */
                            __('Line %1$d: %2$s', 'bulk-price-discount-editor-for-woocommerce'),
                            (int) $bulk_pricer_error[0],
                            $bulk_pricer_error[1]
                        ));
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
    <?php endif; ?>

    <?php if (!empty($bulk_pricer_rows)) : ?>
        <div class="sbp-table-wrap">
            <table class="wp-list-table widefat fixed striped sbp-preview-table">
                <thead>
                    <tr>
                        <th class="sbp-col-name"><?php echo esc_html__('Product Name', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                        <th><?php echo esc_html__('Regular Price', 'bulk-price-discount-editor-for-woocommerce'); ?><br><small><?php echo esc_html__('(Before → After)', 'bulk-price-discount-editor-for-woocommerce'); ?></small></th>
                        <th><?php echo esc_html__('Sale Price', 'bulk-price-discount-editor-for-woocommerce'); ?><br><small><?php echo esc_html__('(Before → After)', 'bulk-price-discount-editor-for-woocommerce'); ?></small></th>
                        <th><?php echo esc_html__('Start Date', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                        <th><?php echo esc_html__('End Date', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bulk_pricer_rows as $bulk_pricer_row) : ?>
                        <tr>
                            <td class="sbp-col-name">
                                <strong><?php echo esc_html($bulk_pricer_row['name']); ?></strong>
                                <?php if ($bulk_pricer_row['sku'] !== '') : ?>
                                    <br><small class="sbp-muted"><?php echo esc_html($bulk_pricer_row['sku']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html($bulk_pricer_formatter->format_price($bulk_pricer_row['old_regular'])); ?>
                                <?php if (abs($bulk_pricer_row['old_regular'] - $bulk_pricer_row['new_regular']) > 0.000001) : ?>
                                    <span class="sbp-arrow-up">→</span>
                                    <strong class="sbp-arrow-up"><?php echo esc_html($bulk_pricer_formatter->format_price($bulk_pricer_row['new_regular'])); ?></strong>
                                <?php endif; ?>
                                <?php echo esc_html($bulk_pricer_currency); ?>
                            </td>
                            <td>
                                <?php if ($bulk_pricer_row['old_sale'] > 0) : ?>
                                    <?php echo esc_html($bulk_pricer_formatter->format_price($bulk_pricer_row['old_sale'])); ?>
                                <?php else : ?>
                                    <span class="sbp-muted">-</span>
                                <?php endif; ?>
                                <?php if (abs($bulk_pricer_row['old_sale'] - $bulk_pricer_row['new_sale']) > 0.000001) : ?>
                                    <?php if ($bulk_pricer_row['new_sale'] > 0) : ?>
                                        <span class="sbp-arrow-sale">→</span>
                                        <strong class="sbp-arrow-sale"><?php echo esc_html($bulk_pricer_formatter->format_price($bulk_pricer_row['new_sale'])); ?></strong>
                                    <?php else : ?>
                                        <span class="sbp-arrow-remove">→</span>
                                        <strong class="sbp-muted"><?php echo esc_html__('Removed', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="sbp-col-date"><?php echo $bulk_pricer_row['start'] !== '' ? esc_html($bulk_pricer_row['start']) : '<span class="sbp-muted">-</span>'; ?></td>
                            <td class="sbp-col-date"><?php echo $bulk_pricer_row['end'] !== '' ? esc_html($bulk_pricer_row['end']) : '<span class="sbp-muted">-</span>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($bulk_pricer_counts['ready'] > count($bulk_pricer_rows)) : ?>
            <p class="description">
                <?php
                echo esc_html(sprintf(
                    /* translators: 1: rows shown, 2: total rows */
                    __('Showing the first %1$d of %2$d rows.', 'bulk-price-discount-editor-for-woocommerce'),
                    count($bulk_pricer_rows),
                    (int) $bulk_pricer_counts['ready']
                ));
                ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <p class="sbp-import-actions">
        <?php if ($bulk_pricer_run_id) : ?>
            <button type="button" id="sbp-import-confirm" class="button button-primary button-hero" data-run-id="<?php echo esc_attr($bulk_pricer_run_id); ?>">
                ✅ <?php echo esc_html(sprintf(
                    /* translators: %d: number of products */
                    _n('Apply to %d product', 'Apply to %d products', (int) $bulk_pricer_counts['ready'], 'bulk-price-discount-editor-for-woocommerce'),
                    (int) $bulk_pricer_counts['ready']
                )); ?>
            </button>
            <button type="button" id="sbp-import-cancel" class="button" data-run-id="<?php echo esc_attr($bulk_pricer_run_id); ?>">
                <?php echo esc_html__('Discard', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </button>
        <?php else : ?>
            <span class="description"><?php echo esc_html__('Nothing to import from this file.', 'bulk-price-discount-editor-for-woocommerce'); ?></span>
        <?php endif; ?>
    </p>
</div>
