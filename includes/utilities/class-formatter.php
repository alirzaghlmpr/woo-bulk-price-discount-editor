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
        $decimals = function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 0;
        return number_format((float) $price, $decimals);
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
            return __('None', 'bulk-price-discount-editor-for-woocommerce');
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
        $operation = Bulk_Pricer_Operations::get($operation_type);
        return $operation ? $operation['label'] : '';
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
        $operation = Bulk_Pricer_Operations::get($operation_type);
        return $operation ? $operation['icon'] : '';
    }

    /**
     * Currency symbol as plain text (no HTML entities)
     *
     * @since 2.1.0
     * @return string
     */
    public function get_plain_currency_symbol()
    {
        return html_entity_decode(wp_strip_all_tags($this->currency_symbol), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Describe the amount of an operation ("10%", "5,000 ﷼", "Exact 12,000 ﷼")
     *
     * @since 2.1.0
     * @param array $operation Validated operation data
     * @return string Plain text ('' when the operation has no amount)
     */
    public function describe_amount($operation)
    {
        $decimals = function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 0;
        $symbol = $this->get_plain_currency_symbol();

        if (!empty($operation['change_percent'])) {
            return (float) $operation['change_percent'] . '%';
        }
        if (!empty($operation['change_fixed'])) {
            return number_format((float) $operation['change_fixed'], $decimals) . ' ' . $symbol;
        }
        if (!empty($operation['exact_price'])) {
            return number_format((float) $operation['exact_price'], $decimals) . ' ' . $symbol;
        }

        return '';
    }

    /**
     * Describe the rounding option ("" when rounding is off)
     *
     * @since 2.1.0
     * @param array $operation Validated operation data
     * @return string Plain text
     */
    public function describe_rounding($operation)
    {
        $mode = isset($operation['round_mode']) ? $operation['round_mode'] : 'none';

        switch ($mode) {
            case 'nearest':
                /* translators: %s: rounding step, e.g. 100 */
                return sprintf(__('nearest %s', 'bulk-price-discount-editor-for-woocommerce'), (float) $operation['round_step']);
            case 'up':
                /* translators: %s: rounding step, e.g. 100 */
                return sprintf(__('up to a multiple of %s', 'bulk-price-discount-editor-for-woocommerce'), (float) $operation['round_step']);
            case 'down':
                /* translators: %s: rounding step, e.g. 100 */
                return sprintf(__('down to a multiple of %s', 'bulk-price-discount-editor-for-woocommerce'), (float) $operation['round_step']);
            case 'ending':
                /* translators: %s: price ending, e.g. .99 */
                return sprintf(__('ending in %s', 'bulk-price-discount-editor-for-woocommerce'), $operation['round_ending']);
        }

        return '';
    }

    /**
     * One-line description of a whole run, stored with the history entry
     *
     * @since 2.1.0
     * @param array $validated Validated request data (operation + filters)
     * @return string Plain text
     */
    public function describe_operation($validated)
    {
        $operation = $validated['operation'];
        $filters = isset($validated['filters']) ? $validated['filters'] : array();

        $parts = array($this->get_operation_label($operation['operation_type']));

        $amount = $this->describe_amount($operation);
        if ($amount !== '') {
            $parts[] = $amount;
        }

        $rounding = $this->describe_rounding($operation);
        if ($rounding !== '') {
            /* translators: %s: rounding description, e.g. "nearest 100" */
            $parts[] = sprintf(__('Rounding: %s', 'bulk-price-discount-editor-for-woocommerce'), $rounding);
        }

        if (!empty($filters['category_id'])) {
            $term = get_term((int) $filters['category_id'], 'product_cat');
            if ($term && !is_wp_error($term)) {
                /* translators: %s: category name */
                $parts[] = sprintf(__('Category: %s', 'bulk-price-discount-editor-for-woocommerce'), $term->name);
            }
        }

        if (!empty($filters['only_on_sale'])) {
            $parts[] = __('Only on-sale products', 'bulk-price-discount-editor-for-woocommerce');
        }

        return implode(' · ', $parts);
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
