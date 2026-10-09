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

$bulk_pricer_tab_icons = array(
    'editor' => '✏️',
    'import' => '📤',
    'history' => '🕘',
);

// Read-only tab selection; no state is changed by this parameter.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$bulk_pricer_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'editor';
if (!isset($bulk_pricer_tabs[$bulk_pricer_tab])) {
    $bulk_pricer_tab = 'editor';
}
?>
<div class="wrap sbp-wrap">
    <header class="sbp-header">
        <div class="sbp-header__logo">
            <img src="<?php echo esc_url(BULK_PRICER_PLUGIN_URL . 'assets/images/icon-128.png'); ?>" width="64" height="64" alt="">
        </div>
        <div class="sbp-header__text">
            <h1>
                <?php echo esc_html__('Bulk Price & Discount Manager (Pro)', 'bulk-price-discount-editor-for-woocommerce'); ?>
                <span class="sbp-header__version">v<?php echo esc_html(BULK_PRICER_VERSION); ?></span>
            </h1>
            <p class="sbp-header__tagline">
                <?php echo esc_html__('Preview, apply, schedule and undo price changes across your whole catalog.', 'bulk-price-discount-editor-for-woocommerce'); ?>
            </p>
        </div>
    </header>

    <nav class="sbp-tabs" aria-label="<?php echo esc_attr__('Sections', 'bulk-price-discount-editor-for-woocommerce'); ?>">
        <?php foreach ($bulk_pricer_tabs as $bulk_pricer_slug => $bulk_pricer_label) : ?>
            <a href="<?php echo esc_url(add_query_arg(array('page' => 'theme-bulk-pricer', 'tab' => $bulk_pricer_slug), admin_url('admin.php'))); ?>"
               class="sbp-tab <?php echo $bulk_pricer_slug === $bulk_pricer_tab ? 'is-active' : ''; ?>"
               <?php echo $bulk_pricer_slug === $bulk_pricer_tab ? 'aria-current="page"' : ''; ?>>
                <span class="sbp-tab__icon" aria-hidden="true"><?php echo esc_html($bulk_pricer_tab_icons[$bulk_pricer_slug]); ?></span>
                <?php echo esc_html($bulk_pricer_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div id="sbp-batch-status" class="sbp-hidden"></div>

    <?php include BULK_PRICER_PLUGIN_DIR . 'admin/views/tabs/' . $bulk_pricer_tab . '.php'; ?>
</div>
