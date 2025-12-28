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

        // Validate input
        $validated_data = $this->validator->validate_request($_POST);
        if (!$validated_data) {
            wp_send_json_error('Invalid input data');
            return;
        }

        $page = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

        // Get excluded product IDs
        $excluded_ids = array();
        if (isset($_POST['excluded_ids']) && !empty($_POST['excluded_ids'])) {
            $excluded_ids = json_decode(stripslashes($_POST['excluded_ids']), true);
            if (!is_array($excluded_ids)) {
                $excluded_ids = array();
            }
        }

        // Get products
        $products_result = $this->product_model->get_products_paginated($page, $validated_data['filters']);
        $products = $this->product_model->get_all_product_variants($products_result);

        // Apply changes (skip excluded products)
        foreach ($products as $product) {
            // Skip if product is in excluded list
            if (in_array($product->get_id(), $excluded_ids)) {
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

        // Check if more pages remain
        $has_more = ($page < $products_result->max_num_pages);

        wp_send_json_success(array('remaining' => $has_more));
    }
}
