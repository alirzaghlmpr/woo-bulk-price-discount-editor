<?php
/**
 * Plugin Name: WooCommerce Bulk Price & Discount Editor Pro
 * Plugin URI: https://yoursite.com
 * Description: Professional bulk price and discount management tool for WooCommerce - ابزار مدیریت قیمت و تخفیف حرفه‌ای
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Text Domain: bulk-price-discount-editor
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('BULK_PRICER_VERSION', '2.0.0');
define('BULK_PRICER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BULK_PRICER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BULK_PRICER_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Activation/Deactivation hooks
require_once BULK_PRICER_PLUGIN_DIR . 'includes/class-bulk-pricer-activator.php';
require_once BULK_PRICER_PLUGIN_DIR . 'includes/class-bulk-pricer-deactivator.php';
register_activation_hook(__FILE__, array('Bulk_Pricer_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Bulk_Pricer_Deactivator', 'deactivate'));

// Core loader
require_once BULK_PRICER_PLUGIN_DIR . 'includes/class-bulk-pricer-loader.php';

/**
 * Initialize the plugin
 */
function run_bulk_pricer()
{
    $plugin = new Bulk_Pricer_Loader();
    $plugin->run();
}

/**
 * Load plugin text domain for translations
 */
function bulk_pricer_load_textdomain()
{
    load_plugin_textdomain(
        'bulk-price-discount-editor',
        false,
        dirname(BULK_PRICER_PLUGIN_BASENAME) . '/languages/'
    );
}

/**
 * Check if WooCommerce is active and initialize plugin
 */
function bulk_pricer_init()
{
    // Load translations
    bulk_pricer_load_textdomain();

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'bulk_pricer_wc_missing_notice');
        return;
    }

    // Run only in admin
    if (is_admin()) {
        run_bulk_pricer();
    }
}

/**
 * Display admin notice if WooCommerce is not active
 */
function bulk_pricer_wc_missing_notice()
{
    ?>
    <div class="notice notice-error">
        <p><strong>WooCommerce Bulk Price & Discount Editor Pro</strong> requires WooCommerce to be installed and activated.</p>
    </div>
    <?php
}

// Declare WooCommerce HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Hook into plugins_loaded to ensure WooCommerce is loaded first
add_action('plugins_loaded', 'bulk_pricer_init');
