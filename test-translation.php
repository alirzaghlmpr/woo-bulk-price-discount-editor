<?php
/**
 * Translation Diagnostic Test
 *
 * Add this to your browser: http://localhost/otp/wp-admin/admin.php?page=test-translation
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (!is_admin()) {
    wp_redirect(admin_url('admin.php?page=test-translation'));
    exit;
}

// Check if we're in admin and authorized
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Translation Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; padding: 20px; background: #f0f0f1; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { color: #1d2327; border-bottom: 3px solid #2271b1; padding-bottom: 10px; }
        h2 { color: #2271b1; margin-top: 30px; }
        .test-item { background: #f6f7f7; padding: 15px; margin: 10px 0; border-left: 4px solid #2271b1; border-radius: 4px; }
        .success { border-left-color: #00a32a; }
        .error { border-left-color: #d63638; }
        .warning { border-left-color: #dba617; }
        code { background: #f0f0f1; padding: 3px 6px; border-radius: 3px; font-family: Consolas, Monaco, monospace; }
        pre { background: #1d2327; color: #f0f0f1; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .icon { margin-right: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f6f7f7; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Translation Diagnostic Test</h1>

    <h2>1. WordPress Configuration</h2>
    <div class="test-item">
        <strong>WordPress Locale:</strong> <code><?php echo get_locale(); ?></code><br>
        <strong>Site Language:</strong> <code><?php echo get_option('WPLANG', 'en_US'); ?></code><br>
        <strong>WordPress Version:</strong> <code><?php echo get_bloginfo('version'); ?></code>
    </div>

    <h2>2. Plugin Information</h2>
    <div class="test-item">
        <strong>Plugin Directory:</strong> <code><?php echo BULK_PRICER_PLUGIN_DIR; ?></code><br>
        <strong>Languages Directory:</strong> <code><?php echo BULK_PRICER_PLUGIN_DIR . 'languages/'; ?></code><br>
        <strong>Plugin Basename:</strong> <code><?php echo BULK_PRICER_PLUGIN_BASENAME; ?></code>
    </div>

    <h2>3. Translation Files Check</h2>
    <?php
    $locale = get_locale();
    $languages_dir = BULK_PRICER_PLUGIN_DIR . 'languages/';
    $mo_file = $languages_dir . "bulk-price-discount-editor-for-woocommerce-{$locale}.mo";
    $po_file = $languages_dir . "bulk-price-discount-editor-for-woocommerce-{$locale}.po";
    ?>
    <div class="test-item <?php echo file_exists($mo_file) ? 'success' : 'error'; ?>">
        <strong>Expected MO File:</strong> <code><?php echo basename($mo_file); ?></code><br>
        <strong>File Exists:</strong> <?php echo file_exists($mo_file) ? '<span style="color: green;">✓ YES</span>' : '<span style="color: red;">✗ NO</span>'; ?><br>
        <?php if (file_exists($mo_file)): ?>
            <strong>File Size:</strong> <?php echo number_format(filesize($mo_file)); ?> bytes<br>
            <strong>Last Modified:</strong> <?php echo date('Y-m-d H:i:s', filemtime($mo_file)); ?>
        <?php endif; ?>
    </div>

    <h2>4. Available Translation Files</h2>
    <div class="test-item">
        <?php
        $files = glob($languages_dir . '*.{mo,po}', GLOB_BRACE);
        if ($files) {
            echo '<table>';
            echo '<tr><th>File</th><th>Size</th><th>Modified</th></tr>';
            foreach ($files as $file) {
                echo '<tr>';
                echo '<td><code>' . basename($file) . '</code></td>';
                echo '<td>' . number_format(filesize($file)) . ' bytes</td>';
                echo '<td>' . date('Y-m-d H:i:s', filemtime($file)) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p style="color: red;">No translation files found!</p>';
        }
        ?>
    </div>

    <h2>5. Text Domain Loading Test</h2>
    <?php
    global $l10n;
    $domain = 'bulk-price-discount-editor-for-woocommerce';
    $is_loaded = isset($l10n[$domain]);
    ?>
    <div class="test-item <?php echo $is_loaded ? 'success' : 'error'; ?>">
        <strong>Text Domain:</strong> <code><?php echo $domain; ?></code><br>
        <strong>Status:</strong> <?php echo $is_loaded ? '<span style="color: green;">✓ LOADED</span>' : '<span style="color: red;">✗ NOT LOADED</span>'; ?>
        <?php if ($is_loaded): ?>
            <br><strong>Entries:</strong> <?php echo count($l10n[$domain]->entries); ?> translations loaded
        <?php endif; ?>
    </div>

    <h2>6. Translation Test</h2>
    <div class="test-item">
        <table>
            <tr>
                <th>Test String</th>
                <th>Translated</th>
                <th>Status</th>
            </tr>
            <?php
            $test_strings = array(
                'None' => __('None', 'bulk-price-discount-editor-for-woocommerce'),
                'Product Name' => __('Product Name', 'bulk-price-discount-editor-for-woocommerce'),
                'Image' => __('Image', 'bulk-price-discount-editor-for-woocommerce'),
                'Status' => __('Status', 'bulk-price-discount-editor-for-woocommerce'),
                'Regular Price' => __('Regular Price', 'bulk-price-discount-editor-for-woocommerce'),
                'Sale Price' => __('Sale Price', 'bulk-price-discount-editor-for-woocommerce')
            );

            foreach ($test_strings as $original => $translated) {
                $is_different = ($translated !== $original);
                echo '<tr>';
                echo '<td><code>' . esc_html($original) . '</code></td>';
                echo '<td>' . esc_html($translated) . '</td>';
                echo '<td>' . ($is_different ? '<span style="color: green;">✓ Translated</span>' : '<span style="color: orange;">⚠ Not translated</span>') . '</td>';
                echo '</tr>';
            }
            ?>
        </table>
    </div>

    <h2>7. Loaded Text Domains</h2>
    <div class="test-item">
        <?php
        if (!empty($l10n)) {
            echo '<strong>Currently Loaded Domains:</strong><br>';
            echo '<ul>';
            foreach (array_keys($l10n) as $loaded_domain) {
                echo '<li><code>' . $loaded_domain . '</code>';
                if ($loaded_domain === $domain) {
                    echo ' <span style="color: green;">← Our plugin!</span>';
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No text domains loaded.</p>';
        }
        ?>
    </div>

    <h2>8. Quick Fix Actions</h2>
    <div class="test-item warning">
        <p><strong>Try these steps:</strong></p>
        <ol>
            <li>Make sure WordPress language is set correctly in <strong>Settings → General</strong></li>
            <li>The locale should be <code>fa_IR</code> for Persian or <code>en_US</code> for English</li>
            <li>Deactivate and reactivate the plugin</li>
            <li>Clear your browser cache (Ctrl+Shift+Delete)</li>
            <li>If using a caching plugin, clear all caches</li>
        </ol>
    </div>

    <h2>9. Manual Load Test</h2>
    <div class="test-item warning">
        WordPress.org plugins load translations automatically. No manual load call is required.
    </div>

    <div style="margin-top: 40px; padding: 20px; background: #f0f6fc; border-radius: 4px; border-left: 4px solid #2271b1;">
        <strong>📝 Need Help?</strong><br>
        Copy this entire page and share it to get support. It contains all the diagnostic information needed.
    </div>
</div>
</body>
</html>
