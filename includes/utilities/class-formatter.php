<?php
/**
 * Formatter Utility Class
 *
 * Handles formatting for prices, dates, and labels
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Formatter Class
 *
 * Provides formatting helper methods
 */
class Bulk_Pricer_Formatter
{
    /**
     * Currency symbol
     *
     * @var string
     */
    private $currency_symbol;

    /**
     * Constructor
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $this->currency_symbol = function_exists('get_woocommerce_currency_symbol')
            ? get_woocommerce_currency_symbol()
            : '$';
    }

    /**
     * Format price
     *
     * @since 2.0.0
     * @param float $price Price value
     * @return string Formatted price
     */
    public function format_price($price)
    {
        return number_format($price);
    }

    /**
     * Format price with currency symbol
     *
     * @since 2.0.0
     * @param float $price Price value
     * @return string Formatted price with currency
     */
    public function format_price_with_currency($price)
    {
        return $this->format_price($price) . ' ' . $this->currency_symbol;
    }

    /**
     * Format discount percentage
     *
     * @since 2.0.0
     * @param float $percent Percentage value
     * @return string Formatted percentage
     */
    public function format_discount_percent($percent)
    {
        return round($percent, 1) . '%';
    }

    /**
     * Format date
     *
     * @since 2.0.0
     * @param mixed $date_object Date object or null
     * @return string Formatted date
     */
    public function format_date($date_object)
    {
        if (!$date_object) {
            return __('None', 'bulk-price-discount-editor');
        }
        return $date_object->date_i18n('Y/m/d');
    }

    /**
     * Get operation label
     *
     * @since 2.0.0
     * @param string $operation_type Operation type
     * @return string Operation label
     */
    public function get_operation_label($operation_type)
    {
        $labels = array(
            'increase_reg' => __('Increase Regular Price', 'bulk-price-discount-editor'),
            'decrease_reg' => __('Decrease Regular Price', 'bulk-price-discount-editor'),
            'set_sale' => __('Apply/Update Sale Price', 'bulk-price-discount-editor'),
            'remove_discount' => __('Remove All Discounts', 'bulk-price-discount-editor')
        );
        return isset($labels[$operation_type]) ? $labels[$operation_type] : '';
    }

    /**
     * Get operation icon
     *
     * @since 2.0.0
     * @param string $operation_type Operation type
     * @return string Operation icon
     */
    public function get_operation_icon($operation_type)
    {
        $icons = array(
            'increase_reg' => '⬆️',
            'decrease_reg' => '⬇️',
            'set_sale' => '🏷️',
            'remove_discount' => '❌'
        );
        return isset($icons[$operation_type]) ? $icons[$operation_type] : '';
    }

    /**
     * Get currency symbol
     *
     * @since 2.0.0
     * @return string Currency symbol
     */
    public function get_currency_symbol()
    {
        return $this->currency_symbol;
    }
}
