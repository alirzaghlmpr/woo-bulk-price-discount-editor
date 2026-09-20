<?php
/**
 * History tab
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$bulk_pricer_run_model = new Bulk_Pricer_Run_Model();
$bulk_pricer_runs = $bulk_pricer_run_model->get_runs(50);
$bulk_pricer_date_format = get_option('date_format') . ' ' . get_option('time_format');

$bulk_pricer_status_labels = array(
    Bulk_Pricer_Run_Model::STATUS_STAGED => __('Awaiting confirmation', 'bulk-price-discount-editor-for-woocommerce'),
    Bulk_Pricer_Run_Model::STATUS_SCHEDULED => __('Scheduled', 'bulk-price-discount-editor-for-woocommerce'),
    Bulk_Pricer_Run_Model::STATUS_RUNNING => __('Interrupted / in progress', 'bulk-price-discount-editor-for-woocommerce'),
    Bulk_Pricer_Run_Model::STATUS_APPLIED => __('Applied', 'bulk-price-discount-editor-for-woocommerce'),
    Bulk_Pricer_Run_Model::STATUS_REVERTING => __('Reverting (interrupted)', 'bulk-price-discount-editor-for-woocommerce'),
    Bulk_Pricer_Run_Model::STATUS_REVERTED => __('Reverted', 'bulk-price-discount-editor-for-woocommerce'),
    Bulk_Pricer_Run_Model::STATUS_CANCELLED => __('Cancelled', 'bulk-price-discount-editor-for-woocommerce'),
);

$bulk_pricer_now = time();
?>
<div class="sbp-card">
    <h2><?php echo esc_html__('Change history', 'bulk-price-discount-editor-for-woocommerce'); ?></h2>
    <p class="description">
        <?php echo esc_html__('Every applied change is recorded with the old and new prices, so you can revert it. Reverting puts each product back to the values it had before that run, including changes made to it since. Finished runs are kept for 90 days.', 'bulk-price-discount-editor-for-woocommerce'); ?>
    </p>

    <?php if (empty($bulk_pricer_runs)) : ?>
        <p><?php echo esc_html__('No changes have been made yet.', 'bulk-price-discount-editor-for-woocommerce'); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat striped sbp-history-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th><?php echo esc_html__('Date', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                    <th><?php echo esc_html__('User', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                    <th><?php echo esc_html__('Operation', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                    <th><?php echo esc_html__('Changed', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                    <th><?php echo esc_html__('Status', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                    <th><?php echo esc_html__('Actions', 'bulk-price-discount-editor-for-woocommerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bulk_pricer_runs as $bulk_pricer_run) : ?>
                    <?php
                    $bulk_pricer_user = get_userdata((int) $bulk_pricer_run->user_id);
                    $bulk_pricer_status = $bulk_pricer_run->status;
                    $bulk_pricer_scheduled_at = (int) $bulk_pricer_run->scheduled_at;
                    $bulk_pricer_revert_at = (int) $bulk_pricer_run->revert_at;
                    $bulk_pricer_overdue_apply = $bulk_pricer_status === Bulk_Pricer_Run_Model::STATUS_SCHEDULED
                        && $bulk_pricer_scheduled_at && $bulk_pricer_scheduled_at < $bulk_pricer_now - 10 * MINUTE_IN_SECONDS;
                    $bulk_pricer_overdue_revert = $bulk_pricer_status === Bulk_Pricer_Run_Model::STATUS_APPLIED
                        && $bulk_pricer_revert_at && $bulk_pricer_revert_at < $bulk_pricer_now - 10 * MINUTE_IN_SECONDS;
                    $bulk_pricer_can_revert = in_array($bulk_pricer_status, array(
                        Bulk_Pricer_Run_Model::STATUS_APPLIED,
                        Bulk_Pricer_Run_Model::STATUS_RUNNING,
                        Bulk_Pricer_Run_Model::STATUS_REVERTING,
                    ), true);
                    ?>
                    <tr data-run-id="<?php echo esc_attr($bulk_pricer_run->id); ?>">
                        <td><?php echo esc_html($bulk_pricer_run->id); ?></td>
                        <td>
                            <?php echo esc_html(wp_date($bulk_pricer_date_format, (int) $bulk_pricer_run->created_at)); ?>
                        </td>
                        <td><?php echo $bulk_pricer_user ? esc_html($bulk_pricer_user->display_name) : '—'; ?></td>
                        <td>
                            <?php echo esc_html($bulk_pricer_run->label); ?>
                            <?php if ($bulk_pricer_scheduled_at && $bulk_pricer_status === Bulk_Pricer_Run_Model::STATUS_SCHEDULED) : ?>
                                <br><small class="sbp-history-meta">
                                    🗓️ <?php echo esc_html(sprintf(
                                        /* translators: %s: date and time */
                                        __('Applies on %s', 'bulk-price-discount-editor-for-woocommerce'),
                                        wp_date($bulk_pricer_date_format, $bulk_pricer_scheduled_at)
                                    )); ?>
                                    <?php if ($bulk_pricer_overdue_apply) : ?>
                                        <strong class="sbp-history-overdue">— <?php echo esc_html__('overdue', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                            <?php if ($bulk_pricer_revert_at && in_array($bulk_pricer_status, array(Bulk_Pricer_Run_Model::STATUS_SCHEDULED, Bulk_Pricer_Run_Model::STATUS_RUNNING, Bulk_Pricer_Run_Model::STATUS_APPLIED), true)) : ?>
                                <br><small class="sbp-history-meta">
                                    ↩️ <?php echo esc_html(sprintf(
                                        /* translators: %s: date and time */
                                        __('Prices restore on %s', 'bulk-price-discount-editor-for-woocommerce'),
                                        wp_date($bulk_pricer_date_format, $bulk_pricer_revert_at)
                                    )); ?>
                                    <?php if ($bulk_pricer_overdue_revert) : ?>
                                        <strong class="sbp-history-overdue">— <?php echo esc_html__('overdue', 'bulk-price-discount-editor-for-woocommerce'); ?></strong>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            echo esc_html(sprintf(
                                /* translators: 1: changed products, 2: total products */
                                __('%1$d of %2$d', 'bulk-price-discount-editor-for-woocommerce'),
                                (int) $bulk_pricer_run->changed,
                                (int) $bulk_pricer_run->total
                            ));
                            ?>
                        </td>
                        <td>
                            <span class="sbp-status sbp-status--<?php echo esc_attr($bulk_pricer_status); ?>">
                                <?php echo esc_html(isset($bulk_pricer_status_labels[$bulk_pricer_status]) ? $bulk_pricer_status_labels[$bulk_pricer_status] : $bulk_pricer_status); ?>
                            </span>
                        </td>
                        <td class="sbp-history-actions">
                            <?php if ($bulk_pricer_can_revert) : ?>
                                <button type="button" class="button sbp-history-action" data-action="revert" data-run-id="<?php echo esc_attr($bulk_pricer_run->id); ?>">
                                    ↩️ <?php echo esc_html__('Revert', 'bulk-price-discount-editor-for-woocommerce'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ($bulk_pricer_status === Bulk_Pricer_Run_Model::STATUS_RUNNING) : ?>
                                <button type="button" class="button sbp-history-action" data-action="resume" data-run-id="<?php echo esc_attr($bulk_pricer_run->id); ?>">
                                    ▶️ <?php echo esc_html__('Resume', 'bulk-price-discount-editor-for-woocommerce'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ($bulk_pricer_status === Bulk_Pricer_Run_Model::STATUS_SCHEDULED) : ?>
                                <button type="button" class="button sbp-history-action" data-action="resume" data-run-id="<?php echo esc_attr($bulk_pricer_run->id); ?>">
                                    ▶️ <?php echo esc_html__('Run now', 'bulk-price-discount-editor-for-woocommerce'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if (in_array($bulk_pricer_status, array(Bulk_Pricer_Run_Model::STATUS_SCHEDULED, Bulk_Pricer_Run_Model::STATUS_STAGED), true)) : ?>
                                <button type="button" class="button sbp-history-action" data-action="cancel" data-run-id="<?php echo esc_attr($bulk_pricer_run->id); ?>">
                                    ✖️ <?php echo esc_html__('Cancel', 'bulk-price-discount-editor-for-woocommerce'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ((int) $bulk_pricer_run->changed > 0) : ?>
                                <button type="button" class="button sbp-history-action" data-action="details" data-run-id="<?php echo esc_attr($bulk_pricer_run->id); ?>">
                                    📋 <?php echo esc_html__('Details', 'bulk-price-discount-editor-for-woocommerce'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ($bulk_pricer_status !== Bulk_Pricer_Run_Model::STATUS_REVERTING) : ?>
                                <button type="button" class="button-link button-link-delete sbp-history-action" data-action="delete" data-run-id="<?php echo esc_attr($bulk_pricer_run->id); ?>">
                                    <?php echo esc_html__('Delete', 'bulk-price-discount-editor-for-woocommerce'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
