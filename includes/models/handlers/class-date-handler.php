<?php
/**
 * Date Handler Class
 *
 * Handles date-related operations for sales
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Date_Handler Class
 *
 * Manages sale date operations
 */
class Bulk_Pricer_Date_Handler
{
    /**
     * Get product sale start date
     *
     * @since 2.0.0
     * @param WC_Product $product    Product object
     * @param bool       $is_on_sale Whether product is on sale
     * @return string Formatted start date
     */
    public function get_sale_start_date($product, $is_on_sale)
    {
        if (!$is_on_sale) {
            return __('None', 'bulk-price-discount-editor');
        }

        $start_date = $product->get_date_on_sale_from();
        if ($start_date) {
            return $start_date->date_i18n('Y/m/d');
        }

        return __('None', 'bulk-price-discount-editor');
    }

    /**
     * Get product sale end date
     *
     * @since 2.0.0
     * @param WC_Product $product    Product object
     * @param bool       $is_on_sale Whether product is on sale
     * @return string Formatted end date
     */
    public function get_sale_end_date($product, $is_on_sale)
    {
        if (!$is_on_sale) {
            return __('None', 'bulk-price-discount-editor');
        }

        $end_date = $product->get_date_on_sale_to();
        if ($end_date) {
            return $end_date->date_i18n('Y/m/d');
        }

        return __('None', 'bulk-price-discount-editor');
    }

    /**
     * Get preview start date (from form or existing product)
     *
     * @since 2.0.0
     * @param WC_Product $product          Product object
     * @param bool       $is_on_sale       Whether product is on sale
     * @param float      $new_sale         New sale price
     * @param array      $operation_params Operation parameters
     * @return string Formatted start date
     */
    public function get_preview_start_date($product, $is_on_sale, $new_sale, $operation_params)
    {
        // If setting a sale and user provided a start date, show that
        if ($new_sale > 0 && !empty($operation_params['sale_start'])) {
            return date_i18n('Y/m/d', strtotime($operation_params['sale_start']));
        }

        // Otherwise show existing date
        return $this->get_sale_start_date($product, $is_on_sale || $new_sale > 0);
    }

    /**
     * Get preview end date (from form or existing product)
     *
     * @since 2.0.0
     * @param WC_Product $product          Product object
     * @param bool       $is_on_sale       Whether product is on sale
     * @param float      $new_sale         New sale price
     * @param array      $operation_params Operation parameters
     * @return string Formatted end date
     */
    public function get_preview_end_date($product, $is_on_sale, $new_sale, $operation_params)
    {
        // If setting a sale and user provided an end date, show that
        if ($new_sale > 0 && !empty($operation_params['sale_expiry'])) {
            return date_i18n('Y/m/d', strtotime($operation_params['sale_expiry']));
        }

        // Otherwise show existing date
        return $this->get_sale_end_date($product, $is_on_sale || $new_sale > 0);
    }

    /**
     * Get date information for preview
     *
     * @since 2.0.0
     * @param WC_Product $product          Product object
     * @param bool       $is_on_sale       Whether product is on sale
     * @param float      $new_sale         New sale price
     * @param array      $operation_params Operation parameters
     * @return array Date information
     */
    public function get_preview_dates($product, $is_on_sale, $new_sale, $operation_params)
    {
        return array(
            'start' => $this->get_preview_start_date($product, $is_on_sale, $new_sale, $operation_params),
            'end' => $this->get_preview_end_date($product, $is_on_sale, $new_sale, $operation_params)
        );
    }
}
