<?php
/**
 * Validator Class
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
     * Last validation error message
     *
     * @var string
     */
    private $error = '';

    /**
     * Get the last validation error
     *
     * @since 2.1.0
     * @return string
     */
    public function get_error()
    {
        return $this->error;
    }

    /**
     * Record an error and return false
     *
     * @param string $message Error message
     * @return false
     */
    private function fail($message)
    {
        $this->error = $message;
        return false;
    }

    /**
     * Validate and sanitize request data
     *
     * @since 2.0.0
     * @param array $post_data POST data
     * @return array|false Validated data or false if invalid (see get_error())
     */
    public function validate_request($post_data)
    {
        $this->error = '';
        // Sanitize operation type
        $operation_type = isset($post_data['operation_type'])
            ? sanitize_text_field(wp_unslash($post_data['operation_type']))
            : '';

        // Validate operation type
        $operation = Bulk_Pricer_Operations::get($operation_type);
        if (!$operation) {
            return $this->fail(__('Invalid operation type.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        // Sanitize numeric inputs
        $change_percent = $this->number($post_data, 'change_percent');
        $change_fixed = $this->number($post_data, 'change_fixed');
        $exact_price = $this->number($post_data, 'exact_price');

        switch ($operation['input']) {
            case 'change':
                if ($change_percent > 0 && $change_fixed > 0) {
                    return $this->fail(__('Fill only one of "Percentage" or "Fixed Amount", not both.', 'bulk-price-discount-editor-for-woocommerce'));
                }
                if ($change_percent <= 0 && $change_fixed <= 0) {
                    return $this->fail(__('Enter a percentage or a fixed amount greater than zero.', 'bulk-price-discount-editor-for-woocommerce'));
                }
                $exact_price = 0;
                break;

            case 'exact':
                if ($exact_price <= 0) {
                    return $this->fail(__('Enter a price greater than zero.', 'bulk-price-discount-editor-for-woocommerce'));
                }
                $change_percent = 0;
                $change_fixed = 0;
                break;

            default:
                $change_percent = 0;
                $change_fixed = 0;
                $exact_price = 0;
        }

        // Rounding
        $round_mode = isset($post_data['round_mode']) ? sanitize_text_field(wp_unslash($post_data['round_mode'])) : 'none';
        if (!in_array($round_mode, array('none', 'nearest', 'up', 'down', 'ending'), true) || $operation_type === 'remove_discount') {
            $round_mode = 'none';
        }
        $round_step = $this->number($post_data, 'round_step');
        $round_ending = isset($post_data['round_ending']) ? sanitize_text_field(wp_unslash($post_data['round_ending'])) : '';

        if ($operation_type === 'round_prices' && $round_mode === 'none') {
            return $this->fail(__('Choose a rounding method for the "Round Prices Only" operation.', 'bulk-price-discount-editor-for-woocommerce'));
        }
        if (in_array($round_mode, array('nearest', 'up', 'down'), true)) {
            if ($round_step <= 0) {
                return $this->fail(__('Enter a rounding step greater than zero.', 'bulk-price-discount-editor-for-woocommerce'));
            }
            $round_ending = '';
        } elseif ($round_mode === 'ending') {
            if (!Bulk_Pricer_Price_Calculator::parse_ending($round_ending)) {
                return $this->fail(__('Enter a valid price ending, for example 99, .99 or 900.', 'bulk-price-discount-editor-for-woocommerce'));
            }
            $round_step = 0;
        } else {
            $round_step = 0;
            $round_ending = '';
        }

        // Price limits
        $price_floor = $this->number($post_data, 'price_floor');
        $price_ceiling = $this->number($post_data, 'price_ceiling');
        if ($price_floor > 0 && $price_ceiling > 0 && $price_floor > $price_ceiling) {
            return $this->fail(__('The minimum price cannot be higher than the maximum price.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        // Sale dates (only operations that create a sale use them)
        $sale_start = '';
        $sale_expiry = '';
        if ($operation['dates']) {
            $sale_start = $this->date($post_data, 'sale_start');
            $sale_expiry = $this->date($post_data, 'sale_expiry');
            if ($sale_start === false || $sale_expiry === false) {
                return $this->fail(__('Enter valid sale dates.', 'bulk-price-discount-editor-for-woocommerce'));
            }
            if ($sale_start !== '' && $sale_expiry !== '' && $sale_start > $sale_expiry) {
                return $this->fail(__('The sale end date must be after the start date.', 'bulk-price-discount-editor-for-woocommerce'));
            }
        }

        // Schedule
        $schedule = $this->validate_schedule($post_data);
        if (!$schedule) {
            return false;
        }

        // Sanitize category ID
        $category_id = isset($post_data['product_cat_id']) ? intval($post_data['product_cat_id']) : 0;

        // Return validated and organized data
        return array(
            'operation' => array(
                'operation_type' => $operation_type,
                'change_percent' => $change_percent,
                'change_fixed' => $change_fixed,
                'exact_price' => $exact_price,
                'sync_sale' => $operation['sync'] && isset($post_data['sync_sale']),
                'sale_start' => $sale_start,
                'sale_expiry' => $sale_expiry,
                'round_mode' => $round_mode,
                'round_step' => $round_step,
                'round_ending' => $round_ending,
                'price_floor' => $price_floor,
                'price_ceiling' => $price_ceiling,
            ),
            'filters' => array(
                'category_id' => $category_id,
                'only_on_sale' => isset($post_data['only_on_sale']),
                'has_sale_price' => !empty($operation['needs_sale']),
            ),
            'schedule' => $schedule,
        );
    }

    /**
     * Validate the "when to apply / auto-restore" fields
     *
     * @since 2.1.0
     * @param array $post_data POST data
     * @return array|false array(mode, apply_at, revert_at) or false if invalid
     */
    public function validate_schedule($post_data)
    {
        $mode = (isset($post_data['apply_mode']) && wp_unslash($post_data['apply_mode']) === 'schedule') ? 'schedule' : 'now';
        $apply_at = $this->local_datetime($post_data, 'apply_at');
        $revert_at = $this->local_datetime($post_data, 'revert_at');

        $scheduler_ready = Bulk_Pricer_Scheduler::is_available();

        if ($mode === 'schedule') {
            if (!$scheduler_ready) {
                return $this->fail(__('Scheduling needs WooCommerce\'s Action Scheduler, which is not available.', 'bulk-price-discount-editor-for-woocommerce'));
            }
            if (!$apply_at) {
                return $this->fail(__('Choose the date and time to apply the changes.', 'bulk-price-discount-editor-for-woocommerce'));
            }
            if ($apply_at <= time() + 60) {
                return $this->fail(__('The scheduled time must be in the future.', 'bulk-price-discount-editor-for-woocommerce'));
            }
        } else {
            $apply_at = 0;
        }

        if ($revert_at) {
            if (!$scheduler_ready) {
                return $this->fail(__('Auto-restore needs WooCommerce\'s Action Scheduler, which is not available.', 'bulk-price-discount-editor-for-woocommerce'));
            }
            $earliest = $mode === 'schedule' ? $apply_at : time();
            if ($revert_at <= $earliest + 60) {
                return $this->fail(__('The restore time must be after the time the changes are applied.', 'bulk-price-discount-editor-for-woocommerce'));
            }
        }

        return array(
            'mode' => $mode,
            'apply_at' => (int) $apply_at,
            'revert_at' => (int) $revert_at,
        );
    }

    /**
     * Read a non-negative number from the request
     *
     * @param array  $post_data POST data
     * @param string $key       Field name
     * @return float
     */
    private function number($post_data, $key)
    {
        if (!isset($post_data[$key]) || $post_data[$key] === '') {
            return 0;
        }

        $value = floatval(wp_unslash($post_data[$key]));

        return $value > 0 ? $value : 0;
    }

    /**
     * Read a Y-m-d date from the request
     *
     * @param array  $post_data POST data
     * @param string $key       Field name
     * @return string|false '' when empty, the date when valid, false when invalid
     */
    private function date($post_data, $key)
    {
        if (!isset($post_data[$key])) {
            return '';
        }

        $value = sanitize_text_field(wp_unslash($post_data[$key]));
        if ($value === '') {
            return '';
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return false;
        }

        return $value;
    }

    /**
     * Read a datetime-local value (site timezone) as a timestamp
     *
     * @param array  $post_data POST data
     * @param string $key       Field name
     * @return int Timestamp, 0 when empty/invalid
     */
    private function local_datetime($post_data, $key)
    {
        if (!isset($post_data[$key])) {
            return 0;
        }

        $value = sanitize_text_field(wp_unslash($post_data[$key]));
        if ($value === '') {
            return 0;
        }

        foreach (array('Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i') as $format) {
            $dt = date_create_immutable_from_format($format, $value, wp_timezone());
            if ($dt) {
                return $dt->getTimestamp();
            }
        }

        return 0;
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
