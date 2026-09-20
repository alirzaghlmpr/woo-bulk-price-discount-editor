<?php
/**
 * Date Handler Class
 *
 * Handles sale date parsing and display. All dates are interpreted in the
 * site's timezone (like the WooCommerce product editor does), not UTC.
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
     * Convert a Y-m-d date to a timestamp in the site timezone
     *
     * The sale end date is moved to 23:59:59 so the last day is included,
     * matching how WooCommerce saves "sale end" from the product editor.
     *
     * @since 2.1.0
     * @param string $date        Date as Y-m-d
     * @param bool   $end_of_day  True for the end-of-day timestamp
     * @return int|null Timestamp, or null if empty/invalid
     */
    public function to_timestamp($date, $end_of_day = false)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        $dt = date_create_immutable_from_format('!Y-m-d', $date, wp_timezone());
        if (!$dt) {
            return null;
        }

        if ($end_of_day) {
            $dt = $dt->setTime(23, 59, 59);
        }

        return $dt->getTimestamp();
    }

    /**
     * Format a timestamp for display (site timezone)
     *
     * @since 2.1.0
     * @param int|null $timestamp Timestamp
     * @return string Formatted date or "None"
     */
    public function format_timestamp($timestamp)
    {
        if (!$timestamp) {
            return __('None', 'bulk-price-discount-editor-for-woocommerce');
        }

        return wp_date('Y/m/d', (int) $timestamp);
    }

    /**
     * Get a product's existing sale start date, formatted
     *
     * @since 2.0.0
     * @param WC_Product $product Product object
     * @return string Formatted start date
     */
    public function get_sale_start_date($product)
    {
        $start_date = $product->get_date_on_sale_from('edit');

        return $start_date ? $this->format_timestamp($start_date->getTimestamp()) : $this->format_timestamp(null);
    }

    /**
     * Get a product's existing sale end date, formatted
     *
     * @since 2.0.0
     * @param WC_Product $product Product object
     * @return string Formatted end date
     */
    public function get_sale_end_date($product)
    {
        $end_date = $product->get_date_on_sale_to('edit');

        return $end_date ? $this->format_timestamp($end_date->getTimestamp()) : $this->format_timestamp(null);
    }

    /**
     * Get the start/end dates to show in the preview
     *
     * Dates typed in the form win over the product's current ones. When the
     * product ends up without a sale price there are no dates to show.
     *
     * @since 2.0.0
     * @param WC_Product $product          Product object
     * @param float      $new_sale         New sale price
     * @param array      $operation_params Operation parameters
     * @return array Date information
     */
    public function get_preview_dates($product, $new_sale, $operation_params)
    {
        $none = $this->format_timestamp(null);

        if ($new_sale <= 0) {
            return array('start' => $none, 'end' => $none);
        }

        $start = $this->get_sale_start_date($product);
        $end   = $this->get_sale_end_date($product);

        if (!empty($operation_params['sale_start'])) {
            $start = $this->format_timestamp($this->to_timestamp($operation_params['sale_start']));
        }
        if (!empty($operation_params['sale_expiry'])) {
            $end = $this->format_timestamp($this->to_timestamp($operation_params['sale_expiry'], true));
        }

        return array('start' => $start, 'end' => $end);
    }
}
