<?php
/**
 * Admin Menu Class
 *
 * Handles WordPress admin menu registration
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Admin_Menu Class
 *
 * Registers and renders admin menu pages
 */
class Bulk_Pricer_Admin_Menu
{
    /**
     * Register admin menu
     *
     * @since 2.0.0
     */
    public function register_menu()
    {
        add_menu_page(
            __('Bulk Price Editor', 'bulk-price-discount-editor'),
            __('Bulk Price Editor', 'bulk-price-discount-editor'),
            'manage_options',
            'theme-bulk-pricer',
            array($this, 'render_admin_page'),
            'dashicons-money-alt',
            56
        );

        // Add test submenu (can be removed later)
        add_submenu_page(
            'theme-bulk-pricer',
            __('Translation Test', 'bulk-price-discount-editor'),
            __('Translation Test', 'bulk-price-discount-editor'),
            'manage_options',
            'bulk-pricer-test',
            array($this, 'render_test_page')
        );
    }

    /**
     * Render admin page
     *
     * @since 2.0.0
     */
    public function render_admin_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bulk-price-discount-editor'));
        }

        // Load the main admin page view
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/admin-page.php';
    }

    /**
     * Render translation test page
     *
     * @since 2.0.0
     */
    public function render_test_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bulk-price-discount-editor'));
        }

        global $l10n;
        $locale = get_locale();
        $domain = 'bulk-price-discount-editor';
        ?>
        <div class="wrap">
            <h1><?php _e('Translation Test', 'bulk-price-discount-editor'); ?></h1>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>WordPress Language Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>WordPress Locale:</th>
                        <td><code><?php echo esc_html($locale); ?></code></td>
                    </tr>
                    <tr>
                        <th>Site Language (WPLANG):</th>
                        <td><code><?php echo esc_html(get_option('WPLANG', 'en_US')); ?></code></td>
                    </tr>
                    <tr>
                        <th>Expected MO File:</th>
                        <td><code>bulk-price-discount-editor-<?php echo esc_html($locale); ?>.mo</code></td>
                    </tr>
                </table>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Translation Files Status</h2>
                <?php
                $languages_dir = BULK_PRICER_PLUGIN_DIR . 'languages/';
                $mo_file = $languages_dir . "bulk-price-discount-editor-{$locale}.mo";
                ?>
                <table class="form-table">
                    <tr>
                        <th>Languages Directory:</th>
                        <td><code><?php echo esc_html($languages_dir); ?></code></td>
                    </tr>
                    <tr>
                        <th>MO File Exists:</th>
                        <td>
                            <?php if (file_exists($mo_file)): ?>
                                <span style="color: green;">✓ YES</span>
                                (<?php echo number_format(filesize($mo_file)); ?> bytes)
                            <?php else: ?>
                                <span style="color: red;">✗ NO</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Text Domain Loaded:</th>
                        <td>
                            <?php if (isset($l10n[$domain])): ?>
                                <span style="color: green;">✓ YES</span>
                                (<?php echo count($l10n[$domain]->entries); ?> translations)
                            <?php else: ?>
                                <span style="color: red;">✗ NO</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <h3>Available Translation Files:</h3>
                <ul>
                    <?php
                    $files = glob($languages_dir . '*.mo');
                    if ($files) {
                        foreach ($files as $file) {
                            echo '<li><code>' . esc_html(basename($file)) . '</code></li>';
                        }
                    } else {
                        echo '<li style="color: red;">No .mo files found!</li>';
                    }
                    ?>
                </ul>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Translation Test Results</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>English String</th>
                            <th>Translated String</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $test_strings = array(
                            'None',
                            'Product Name',
                            'Image',
                            'Status',
                            'Regular Price',
                            'Sale Price',
                            'Bulk Price Editor'
                        );

                        foreach ($test_strings as $string) {
                            $translated = __($string, 'bulk-price-discount-editor');
                            $is_translated = ($translated !== $string);
                            ?>
                            <tr>
                                <td><code><?php echo esc_html($string); ?></code></td>
                                <td><strong><?php echo esc_html($translated); ?></strong></td>
                                <td>
                                    <?php if ($is_translated): ?>
                                        <span style="color: green;">✓ Translated</span>
                                    <?php else: ?>
                                        <span style="color: orange;">⚠ Same as English</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
                <h2>Quick Fix Steps</h2>
                <ol>
                    <li>Go to <strong>Settings → General</strong></li>
                    <li>Set <strong>Site Language</strong> to:
                        <ul>
                            <li><code>فارسی</code> for Persian</li>
                            <li><code>English (United States)</code> for English</li>
                        </ul>
                    </li>
                    <li>Save changes</li>
                    <li><strong>Deactivate</strong> this plugin</li>
                    <li><strong>Reactivate</strong> this plugin</li>
                    <li>Clear browser cache (Ctrl+Shift+Delete)</li>
                    <li>Return to this page to verify</li>
                </ol>
            </div>

            <?php if (!isset($l10n[$domain]) || !file_exists($mo_file)): ?>
            <div class="notice notice-error" style="margin-top: 20px;">
                <p><strong>Problem Detected!</strong></p>
                <?php if (!file_exists($mo_file)): ?>
                    <p>The translation file <code><?php echo basename($mo_file); ?></code> doesn't exist.</p>
                    <p>Available locales: <code>fa_IR</code> (Persian), <code>en_US</code> (English)</p>
                <?php endif; ?>
                <?php if (!isset($l10n[$domain])): ?>
                    <p>The text domain is not loaded. Try deactivating and reactivating the plugin.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
