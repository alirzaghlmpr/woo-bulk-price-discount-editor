<?php
/**
 * Operations registry
 *
 * Single source of truth for the available bulk operations: labels, icons and
 * which inputs / options each one uses. The form, validator and calculator all
 * read from here so they can't drift apart.
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Operations Class
 */
class Bulk_Pricer_Operations
{
    /**
     * Get all operations
     *
     * Keys per operation:
     *  - label      Display label
     *  - icon       Emoji icon
     *  - group      Option group key (see groups())
     *  - input      'change' (percent OR fixed), 'exact' (one price), 'none'
     *  - sync       Supports the "sync sale price" option
     *  - dates      Supports sale start/end dates
     *  - needs_sale Only meaningful for products that already have a sale price
     *
     * @since 2.1.0
     * @return array
     */
    public static function all()
    {
        // Built once per request: the calculator asks for this for every product.
        static $operations = null;

        if ($operations !== null) {
            return $operations;
        }

        $operations = array(
            'increase_reg' => array(
                'label' => __('Increase Regular Price', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '⬆️',
                'group' => 'regular',
                'input' => 'change',
                'sync' => true,
                'dates' => false,
                'needs_sale' => false,
            ),
            'decrease_reg' => array(
                'label' => __('Decrease Regular Price', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '⬇️',
                'group' => 'regular',
                'input' => 'change',
                'sync' => true,
                'dates' => false,
                'needs_sale' => false,
            ),
            'set_regular_exact' => array(
                'label' => __('Set Exact Regular Price', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '🎯',
                'group' => 'regular',
                'input' => 'exact',
                'sync' => true,
                'dates' => false,
                'needs_sale' => false,
            ),
            'set_sale' => array(
                'label' => __('Apply/Update Sale Price', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '🏷️',
                'group' => 'sale',
                'input' => 'change',
                'sync' => false,
                'dates' => true,
                'needs_sale' => false,
            ),
            'set_sale_exact' => array(
                'label' => __('Set Exact Sale Price', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '🎯',
                'group' => 'sale',
                'input' => 'exact',
                'sync' => false,
                'dates' => true,
                'needs_sale' => false,
            ),
            'increase_sale' => array(
                'label' => __('Increase Sale Price Only', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '🔼',
                'group' => 'sale',
                'input' => 'change',
                'sync' => false,
                'dates' => false,
                'needs_sale' => true,
            ),
            'decrease_sale' => array(
                'label' => __('Decrease Sale Price Only', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '🔽',
                'group' => 'sale',
                'input' => 'change',
                'sync' => false,
                'dates' => false,
                'needs_sale' => true,
            ),
            'sale_to_regular' => array(
                'label' => __('Make Sale Permanent (Sale → Regular)', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '📌',
                'group' => 'sale',
                'input' => 'none',
                'sync' => false,
                'dates' => false,
                'needs_sale' => true,
            ),
            'remove_discount' => array(
                'label' => __('Remove All Discounts', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '❌',
                'group' => 'sale',
                'input' => 'none',
                'sync' => false,
                'dates' => false,
                'needs_sale' => true,
            ),
            'round_prices' => array(
                'label' => __('Round Prices Only', 'bulk-price-discount-editor-for-woocommerce'),
                'icon' => '🔢',
                'group' => 'other',
                'input' => 'none',
                'sync' => false,
                'dates' => false,
                'needs_sale' => false,
            ),
        );

        return $operations;
    }

    /**
     * Get one operation definition
     *
     * @since 2.1.0
     * @param string $type Operation type
     * @return array|null
     */
    public static function get($type)
    {
        $all = self::all();
        return isset($all[$type]) ? $all[$type] : null;
    }

    /**
     * Option group labels for the operation dropdown
     *
     * @since 2.1.0
     * @return array
     */
    public static function groups()
    {
        return array(
            'regular' => __('Regular price', 'bulk-price-discount-editor-for-woocommerce'),
            'sale' => __('Sale price', 'bulk-price-discount-editor-for-woocommerce'),
            'other' => __('Other', 'bulk-price-discount-editor-for-woocommerce'),
        );
    }

    /**
     * Operations whose price difference is measured on the regular price
     * (all others are measured on the final price the customer pays).
     *
     * @since 2.1.0
     * @return string[]
     */
    public static function regular_diff_types()
    {
        return array('increase_reg', 'decrease_reg', 'set_regular_exact', 'sale_to_regular');
    }
}
