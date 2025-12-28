<?php
/**
 * Core Loader Class
 *
 * Loads all dependencies and initializes the plugin
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Loader Class
 *
 * Central orchestrator that loads all components and registers hooks
 */
class Bulk_Pricer_Loader
{
    /**
     * Admin menu instance
     *
     * @var Bulk_Pricer_Admin_Menu
     */
    protected $admin_menu;

    /**
     * Admin assets instance
     *
     * @var Bulk_Pricer_Admin_Assets
     */
    protected $admin_assets;

    /**
     * AJAX controller instance
     *
     * @var Bulk_Pricer_Ajax_Controller
     */
    protected $ajax_controller;

    /**
     * Constructor
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_ajax_hooks();
    }

    /**
     * Load all required dependencies
     *
     * @since 2.0.0
     */
    private function load_dependencies()
    {
        // Load modular calculator components
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/calculators/class-price-calculator.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/formatters/class-product-data-formatter.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/handlers/class-date-handler.php';

        // Load models
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/class-product-model.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/class-pricing-calculator.php';

        // Load controllers
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/controllers/class-ajax-controller.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/controllers/class-preview-controller.php';

        // Load utilities
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/utilities/class-validator.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/utilities/class-formatter.php';

        // Load admin classes
        require_once BULK_PRICER_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'admin/class-admin-assets.php';
    }

    /**
     * Register admin hooks
     *
     * @since 2.0.0
     */
    private function define_admin_hooks()
    {
        $this->admin_menu = new Bulk_Pricer_Admin_Menu();
        $this->admin_assets = new Bulk_Pricer_Admin_Assets();

        add_action('admin_menu', array($this->admin_menu, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this->admin_assets, 'enqueue_assets'));
    }

    /**
     * Register AJAX hooks
     *
     * @since 2.0.0
     */
    private function define_ajax_hooks()
    {
        $this->ajax_controller = new Bulk_Pricer_Ajax_Controller();

        add_action('wp_ajax_sbp_preview_action', array($this->ajax_controller, 'handle_preview'));
        add_action('wp_ajax_sbp_apply_batch_action', array($this->ajax_controller, 'handle_apply_batch'));
    }

    /**
     * Run the plugin
     *
     * @since 2.0.0
     */
    public function run()
    {
        // Plugin is now running with all hooks registered
    }
}
