<?php
/**
 * AJAX Controller Class
 *
 * Handles all AJAX requests
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Ajax_Controller Class
 *
 * Processes AJAX requests for preview and batch apply
 */
class Bulk_Pricer_Ajax_Controller
{
    /**
     * Product model instance
     *
     * @var Bulk_Pricer_Product_Model
     */
    private $product_model;

    /**
     * Pricing calculator instance
     *
     * @var Bulk_Pricer_Pricing_Calculator
     */
    private $pricing_calculator;

    /**
     * Validator instance
     *
     * @var Bulk_Pricer_Validator
     */
    private $validator;

    /**
     * Preview controller instance
     *
     * @var Bulk_Pricer_Preview_Controller
     */
    private $preview_controller;

    /**
     * Constructor
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $this->product_model = new Bulk_Pricer_Product_Model();
        $this->pricing_calculator = new Bulk_Pricer_Pricing_Calculator();
        $this->validator = new Bulk_Pricer_Validator();
        $this->preview_controller = new Bulk_Pricer_Preview_Controller();
    }

    /**
     * Handle preview AJAX request
     *
     * @since 2.0.0
     */
    public function handle_preview()
    {
        // Security check
        check_ajax_referer('sbp_bulk_nonce', 'security');

        // Capability check (defense in depth - the nonce is not an authorization check)
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        // Validate and sanitize input
        $validated_data = $this->validator->validate_request($_POST);
        if (!$validated_data) {
            wp_send_json_error('Invalid input data');
            return;
        }

        $page = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

        // Get products
        $products_result = $this->product_model->get_products_paginated($page, $validated_data['filters']);
        $products = $this->product_model->get_all_product_variants($products_result);

        // Calculate new prices for preview
        $preview_data = array();
        foreach ($products as $product) {
            $price_data = $this->pricing_calculator->calculate_new_prices($product, $validated_data['operation']);
            if ($price_data) {
                $preview_data[] = $price_data;
            }
        }

        // Generate HTML
        $html = $this->preview_controller->generate_preview_html(
            $preview_data,
            $page,
            $products_result->max_num_pages,
            $products_result->total,
            $validated_data
        );

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Handle batch apply AJAX request
     *
     * @since 2.0.0
     */
    public function handle_apply_batch()
    {
        // Security check
        check_ajax_referer('sbp_bulk_nonce', 'security');

        // Capability check (defense in depth - the nonce is not an authorization check)
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        // Validate input
        $validated_data = $this->validator->validate_request($_POST);
        if (!$validated_data) {
            wp_send_json_error('Invalid input data');
            return;
        }

        $page = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;
        $per_page = $this->product_model->get_per_page();
        $transient_key = 'sbp_apply_ids_' . get_current_user_id();

        // Take a stable snapshot of the target product IDs on the first batch and
        // reuse it for the remaining batches. This prevents the pagination from
        // shifting as products are mutated mid-run (e.g. with the "only on sale"
        // filter), which would otherwise skip or double-process products.
        if ($page === 1) {
            $all_ids = $this->product_model->get_all_matching_ids($validated_data['filters']);
            set_transient($transient_key, $all_ids, HOUR_IN_SECONDS);
        } else {
            $all_ids = get_transient($transient_key);
            if (!is_array($all_ids)) {
                $all_ids = $this->product_model->get_all_matching_ids($validated_data['filters']);
                set_transient($transient_key, $all_ids, HOUR_IN_SECONDS);
            }
        }

        // Get excluded product IDs
        $excluded_ids = array();
        if (isset($_POST['excluded_ids']) && !empty($_POST['excluded_ids'])) {
            $decoded = json_decode(sanitize_text_field(wp_unslash($_POST['excluded_ids'])), true);
            if (is_array($decoded)) {
                $excluded_ids = array_map('intval', $decoded);
            }
        }

        // Process the current slice of the snapshot
        $offset = ($page - 1) * $per_page;
        $slice = array_slice($all_ids, $offset, $per_page);

        foreach ($slice as $product_id) {
            // Skip if product is in excluded list
            if (in_array((int) $product_id, $excluded_ids, true)) {
                continue;
            }

            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }

            $price_data = $this->pricing_calculator->calculate_new_prices($product, $validated_data['operation']);
            if ($price_data) {
                $this->product_model->update_product_prices(
                    $product,
                    $price_data['new_reg'],
                    $price_data['new_sale'],
                    $validated_data['operation']['sale_start'],
                    $validated_data['operation']['sale_expiry']
                );
            }
        }

        // Check if more batches remain; clean up the snapshot when finished
        $has_more = (($offset + $per_page) < count($all_ids));
        if (!$has_more) {
            delete_transient($transient_key);
        }

        wp_send_json_success(array('remaining' => $has_more));
    }
}
