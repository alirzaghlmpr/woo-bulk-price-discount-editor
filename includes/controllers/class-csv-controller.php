<?php
/**
 * CSV Controller Class
 *
 * Handles the preview CSV export and the price CSV import upload.
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Csv_Controller Class
 */
class Bulk_Pricer_Csv_Controller extends Bulk_Pricer_Base_Controller
{
    /**
     * Largest accepted upload (bytes)
     */
    const MAX_FILE_SIZE = 5242880;

    /**
     * @var Bulk_Pricer_Csv_Service
     */
    private $csv;

    /**
     * @var Bulk_Pricer_Product_Model
     */
    private $products;

    /**
     * @var Bulk_Pricer_Run_Service
     */
    private $run_service;

    /**
     * Constructor
     *
     * @since 2.1.0
     */
    public function __construct()
    {
        $this->csv = new Bulk_Pricer_Csv_Service();
        $this->products = new Bulk_Pricer_Product_Model();
        $this->run_service = new Bulk_Pricer_Run_Service();
    }

    /**
     * Stream the preview (every product in scope, not just one page) as CSV
     *
     * @since 2.1.0
     */
    public function handle_export()
    {
        $this->authorize_admin_post();

        $validated_data = $this->get_validated_request(false);

        $ids = $this->products->get_all_matching_ids($validated_data['filters']);
        $excluded = $this->get_excluded_ids();
        if ($excluded) {
            $ids = array_values(array_diff($ids, $excluded));
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bulk-price-preview-' . gmdate('Ymd-His') . '.csv"');

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads non-Latin names correctly.

        $this->csv->write_preview_csv($out, $ids, $validated_data['operation']);

        fclose($out); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        exit;
    }

    /**
     * Receive a price CSV, stage it as a run and answer with a preview
     *
     * @since 2.1.0
     */
    public function handle_import_upload()
    {
        $this->authorize();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in authorize().
        $file = isset($_FILES['sbp_csv']) ? $_FILES['sbp_csv'] : null;

        if (!$file || !isset($file['tmp_name']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('Choose a CSV file to upload.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $filename = sanitize_file_name(wp_unslash($file['name']));
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, array('csv', 'txt'), true)) {
            wp_send_json_error(__('Only .csv files are supported.', 'bulk-price-discount-editor-for-woocommerce'));
        }
        if ((int) $file['size'] > self::MAX_FILE_SIZE) {
            wp_send_json_error(__('The file is too large (5 MB maximum).', 'bulk-price-discount-editor-for-woocommerce'));
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            wp_send_json_error(__('The upload could not be verified.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $parsed = $this->csv->parse($file['tmp_name']);
        if (is_wp_error($parsed)) {
            wp_send_json_error($parsed->get_error_message());
        }

        $run_id = 0;
        if (!empty($parsed['items'])) {
            $run_id = $this->run_service->create_csv_run($filename, $parsed['items']);
            if (!$run_id) {
                wp_send_json_error(__('Could not stage the import.', 'bulk-price-discount-editor-for-woocommerce'));
            }
        }

        $bulk_pricer_run_id = $run_id;
        $bulk_pricer_filename = $filename;
        $bulk_pricer_counts = $parsed['counts'];
        $bulk_pricer_rows = $parsed['preview'];
        $bulk_pricer_errors = $parsed['errors'];
        $bulk_pricer_currency = get_woocommerce_currency_symbol();
        $bulk_pricer_formatter = new Bulk_Pricer_Product_Data_Formatter();

        ob_start();
        include BULK_PRICER_PLUGIN_DIR . 'admin/views/partials/import-preview.php';
        wp_send_json_success(array('html' => ob_get_clean(), 'run_id' => $run_id));
    }
}
