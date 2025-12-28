<?php
/**
 * Validator Utility Class
 *
 * Handles input validation and sanitization
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Validator Class
 *
 * Validates and sanitizes all user input
 */
class Bulk_Pricer_Validator
{
    /**
     * Validate and sanitize request data
     *
     * @since 2.0.0
     * @param array $post_data POST data
     * @return array|false Validated data or false if invalid
     */
    public function validate_request($post_data)
    {
        // Sanitize operation type
        $operation_type = isset($post_data['operation_type'])
            ? sanitize_text_field($post_data['operation_type'])
            : '';

        // Validate operation type
        if (!in_array($operation_type, array('increase_reg', 'decrease_reg', 'set_sale', 'remove_discount'))) {
            return false;
        }

        // Sanitize numeric inputs
        $change_percent = isset($post_data['change_percent']) && $post_data['change_percent'] !== ''
            ? floatval($post_data['change_percent'])
            : 0;

        $change_fixed = isset($post_data['change_fixed']) && $post_data['change_fixed'] !== ''
            ? floatval($post_data['change_fixed'])
            : 0;

        // Validate: only one change method allowed (except for remove_discount)
        if ($operation_type !== 'remove_discount' && $change_percent > 0 && $change_fixed > 0) {
            return false;
        }

        // Validate: at least one value must be provided (except for remove_discount)
        if ($operation_type !== 'remove_discount' && $change_percent <= 0 && $change_fixed <= 0) {
            return false;
        }

        // Sanitize category ID
        $category_id = isset($post_data['product_cat_id']) ? intval($post_data['product_cat_id']) : 0;

        // Sanitize checkboxes
        $only_on_sale = isset($post_data['only_on_sale']);
        $sync_sale = isset($post_data['sync_sale']);

        // Sanitize sale dates
        $sale_start = isset($post_data['sale_start']) ? sanitize_text_field($post_data['sale_start']) : '';
        $sale_expiry = isset($post_data['sale_expiry']) ? sanitize_text_field($post_data['sale_expiry']) : '';

        // Return validated and organized data
        return array(
            'operation' => array(
                'operation_type' => $operation_type,
                'change_percent' => $change_percent,
                'change_fixed' => $change_fixed,
                'sync_sale' => $sync_sale,
                'sale_start' => $sale_start,
                'sale_expiry' => $sale_expiry
            ),
            'filters' => array(
                'category_id' => $category_id,
                'only_on_sale' => $only_on_sale
            )
        );
    }

    /**
     * Validate nonce
     *
     * @since 2.0.0
     * @param string $nonce  Nonce value
     * @param string $action Nonce action
     * @return bool Validation result
     */
    public function validate_nonce($nonce, $action = 'sbp_bulk_nonce')
    {
        return wp_verify_nonce($nonce, $action);
    }
}
