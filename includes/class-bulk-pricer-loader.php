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
     * History controller instance
     *
     * @var Bulk_Pricer_History_Controller
     */
    protected $history_controller;

    /**
     * CSV controller instance
     *
     * @var Bulk_Pricer_Csv_Controller
     */
    protected $csv_controller;

    /**
     * Scheduler instance
     *
     * @var Bulk_Pricer_Scheduler
     */
    protected $scheduler;

    /**
     * Constructor
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $this->load_dependencies();

        // Background jobs (Action Scheduler / WP-Cron) don't run inside wp-admin,
        // so the scheduler must be hooked on every request.
        $this->define_scheduler_hooks();

        if (is_admin()) {
            $this->define_admin_hooks();
            $this->define_ajax_hooks();
        }
    }

    /**
     * Capability required to use the plugin
     *
     * @since 2.1.0
     * @return string
     */
    public static function capability()
    {
        return apply_filters('bulk_pricer_capability', 'manage_options');
    }

    /**
     * Load all required dependencies
     *
     * @since 2.0.0
     */
    private function load_dependencies()
    {
        // Database schema
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/class-bulk-pricer-db.php';

        // Load modular calculator components
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/calculators/class-price-calculator.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/formatters/class-product-data-formatter.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/handlers/class-date-handler.php';

        // Load models
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/class-operations.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/class-product-model.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/class-run-model.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/models/class-pricing-calculator.php';

        // Load utilities
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/utilities/class-validator.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/utilities/class-formatter.php';

        // Load services
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/services/class-run-service.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/services/class-scheduler.php';
        require_once BULK_PRICER_PLUGIN_DIR . 'includes/services/class-csv-service.php';

        if (is_admin()) {
            // Load controllers
            require_once BULK_PRICER_PLUGIN_DIR . 'includes/controllers/class-base-controller.php';
            require_once BULK_PRICER_PLUGIN_DIR . 'includes/controllers/class-ajax-controller.php';
            require_once BULK_PRICER_PLUGIN_DIR . 'includes/controllers/class-history-controller.php';
            require_once BULK_PRICER_PLUGIN_DIR . 'includes/controllers/class-csv-controller.php';
            require_once BULK_PRICER_PLUGIN_DIR . 'includes/controllers/class-preview-controller.php';

            // Load admin classes
            require_once BULK_PRICER_PLUGIN_DIR . 'admin/class-admin-menu.php';
            require_once BULK_PRICER_PLUGIN_DIR . 'admin/class-admin-assets.php';
        }
    }

    /**
     * Register the background job callbacks
     *
     * @since 2.1.0
     */
    private function define_scheduler_hooks()
    {
        $this->scheduler = new Bulk_Pricer_Scheduler();
        $this->scheduler->register();
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

        // Create / upgrade the history tables (also covers updates that skip activation).
        add_action('admin_init', array('Bulk_Pricer_DB', 'maybe_upgrade'));

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
        $this->history_controller = new Bulk_Pricer_History_Controller();
        $this->csv_controller = new Bulk_Pricer_Csv_Controller();

        add_action('wp_ajax_sbp_preview_action', array($this->ajax_controller, 'handle_preview'));
        add_action('wp_ajax_sbp_summary_action', array($this->ajax_controller, 'handle_summary'));
        add_action('wp_ajax_sbp_apply_batch_action', array($this->ajax_controller, 'handle_apply_batch'));
        add_action('wp_ajax_sbp_schedule_action', array($this->ajax_controller, 'handle_schedule'));

        add_action('wp_ajax_sbp_revert_batch_action', array($this->history_controller, 'handle_revert_batch'));
        add_action('wp_ajax_sbp_cancel_run_action', array($this->history_controller, 'handle_cancel'));
        add_action('wp_ajax_sbp_delete_run_action', array($this->history_controller, 'handle_delete'));
        add_action('wp_ajax_sbp_run_details_action', array($this->history_controller, 'handle_details'));
        add_action('admin_post_sbp_export_run', array($this->history_controller, 'handle_export_run'));

        add_action('wp_ajax_sbp_import_upload_action', array($this->csv_controller, 'handle_import_upload'));
        add_action('admin_post_sbp_export_csv', array($this->csv_controller, 'handle_export'));
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
