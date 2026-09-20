<?php
/**
 * Main Admin Page Template
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$bulk_pricer_currency = get_woocommerce_currency_symbol();

$bulk_pricer_tabs = array(
    'editor' => __('Bulk Editor', 'bulk-price-discount-editor-for-woocommerce'),
    'import' => __('Import CSV', 'bulk-price-discount-editor-for-woocommerce'),
    'history' => __('History', 'bulk-price-discount-editor-for-woocommerce'),
);

// Read-only tab selection; no state is changed by this parameter.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$bulk_pricer_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'editor';
if (!isset($bulk_pricer_tabs[$bulk_pricer_tab])) {
    $bulk_pricer_tab = 'editor';
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('Bulk Price & Discount Manager (Pro)', 'bulk-price-discount-editor-for-woocommerce'); ?></h1>

    <h2 class="nav-tab-wrapper sbp-tabs">
        <?php foreach ($bulk_pricer_tabs as $bulk_pricer_slug => $bulk_pricer_label) : ?>
            <a href="<?php echo esc_url(add_query_arg(array('page' => 'theme-bulk-pricer', 'tab' => $bulk_pricer_slug), admin_url('admin.php'))); ?>"
               class="nav-tab <?php echo $bulk_pricer_slug === $bulk_pricer_tab ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($bulk_pricer_label); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div id="sbp-batch-status" class="sbp-hidden"></div>

    <?php include BULK_PRICER_PLUGIN_DIR . 'admin/views/tabs/' . $bulk_pricer_tab . '.php'; ?>
</div>
