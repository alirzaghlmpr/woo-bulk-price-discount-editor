<?php
/**
 * Pricing Calculator Class (Refactored)
 *
 * Orchestrates pricing calculations using modular components
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Pricing_Calculator Class
 *
 * Main coordinator for pricing operations
 */
class Bulk_Pricer_Pricing_Calculator
{
    /**
     * Price calculator instance
     *
     * @var Bulk_Pricer_Price_Calculator
     */
    private $price_calc;

    /**
     * Date handler instance
     *
     * @var Bulk_Pricer_Date_Handler
     */
    private $date_handler;

    /**
     * Product data formatter instance
     *
     * @var Bulk_Pricer_Product_Data_Formatter
     */
    private $formatter;

    /**
     * Constructor
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $this->price_calc = new Bulk_Pricer_Price_Calculator();
        $this->date_handler = new Bulk_Pricer_Date_Handler();
        $this->formatter = new Bulk_Pricer_Product_Data_Formatter();
    }

    /**
     * Calculate the new prices for a product (raw numbers only)
     *
     * Single source of truth used by the preview, the summary, the CSV export
     * and the actual apply, so they can never disagree.
     *
     * @since 2.1.0
     * @param WC_Product $product          Product object
     * @param array      $operation_params Operation parameters
     * @return array|null Result, or null if the parameters are invalid
     */
    public function compute_for_product($product, $operation_params)
    {
        return $this->compute(
            (float) $product->get_regular_price('edit'),
            (float) $product->get_sale_price('edit'),
            (bool) $product->is_on_sale(),
            $operation_params
        );
    }

    /**
     * Calculate new prices from raw values
     *
     * @since 2.1.0
     * @param float $current_regular Current regular price
     * @param float $current_sale    Current sale price (0 = none)
     * @param bool  $is_on_sale      Whether the product is on sale right now
     * @param array $params          Operation parameters (validated)
     * @return array|null
     */
    public function compute($current_regular, $current_sale, $is_on_sale, $params)
    {
        $type    = $params['operation_type'];
        $percent = isset($params['change_percent']) ? (float) $params['change_percent'] : 0;
        $fixed   = isset($params['change_fixed']) ? (float) $params['change_fixed'] : 0;
        $exact   = isset($params['exact_price']) ? (float) $params['exact_price'] : 0;
        $sync    = !empty($params['sync_sale']);
        $floor   = isset($params['price_floor']) ? (float) $params['price_floor'] : 0;
        $ceiling = isset($params['price_ceiling']) ? (float) $params['price_ceiling'] : 0;

        // Validation: both percent and fixed cannot be used together
        $operation = Bulk_Pricer_Operations::get($type);
        if (!$operation || ($operation['input'] === 'change' && $percent > 0 && $fixed > 0)) {
            return null;
        }

        $new_regular = $current_regular;
        $new_sale = $current_sale;
        $sync_applied = false;
        $sale_capped = false;

        // Which prices this operation actually changes. Limits and rounding
        // only apply to those, never to prices the operation leaves alone.
        $touch_regular = false;
        $touch_sale = false;

        // A "real" sale price. Sync keys off this rather than is_on_sale(), so
        // sales scheduled for the future are kept in step as well.
        $has_sale = $current_sale > 0 && $current_regular > 0 && $current_sale < $current_regular;

        switch ($type) {
            case 'remove_discount':
                $new_sale = 0;
                break;

            case 'set_sale':
                if ($current_regular > 0) {
                    $new_sale = $this->price_calc->calculate_sale_price($current_regular, $percent, $fixed);
                    $touch_sale = true;

                    // Detect when the requested discount would meet/exceed the
                    // regular price and was therefore capped to 10% off.
                    $intended_change = $percent > 0 ? $current_regular * ($percent / 100) : $fixed;
                    if ($intended_change > 0 && $intended_change >= $current_regular) {
                        $sale_capped = true;
                    }
                }
                break;

            case 'set_sale_exact':
                if ($current_regular > 0 && $exact > 0) {
                    $new_sale = $exact;
                    $touch_sale = true;
                    if ($new_sale >= $current_regular) {
                        $new_sale = $this->price_calc->cap_sale_price($current_regular);
                        $sale_capped = true;
                    }
                }
                break;

            case 'set_regular_exact':
                if ($exact > 0) {
                    $new_regular = $exact;
                    $touch_regular = true;

                    // Keep the same discount percentage on the sale price.
                    if ($sync && $has_sale) {
                        $new_sale = $this->price_calc->round_price($new_regular * ($current_sale / $current_regular));
                        $touch_sale = true;
                        $sync_applied = true;
                    }
                }
                break;

            case 'increase_reg':
            case 'decrease_reg':
                if ($current_regular > 0) {
                    $new_regular = $this->price_calc->calculate_regular_price($current_regular, $type, $percent, $fixed);
                    $touch_regular = true;

                    // Sync sale price if enabled and the product has a sale price
                    if ($sync && $has_sale) {
                        $new_sale = $this->price_calc->calculate_synced_sale_price(
                            $current_sale,
                            $new_regular,
                            $type,
                            $percent,
                            $fixed
                        );
                        $touch_sale = true;
                        $sync_applied = true;
                    }
                }
                break;

            case 'increase_sale':
            case 'decrease_sale':
                if ($has_sale) {
                    $new_sale = $this->price_calc->calculate_adjusted_price(
                        $current_sale,
                        $type === 'increase_sale',
                        $percent,
                        $fixed
                    );
                    $touch_sale = true;
                }
                break;

            case 'sale_to_regular':
                if ($has_sale) {
                    $new_regular = $current_sale;
                    $new_sale = 0;
                    $touch_regular = true;
                }
                break;

            case 'round_prices':
                $touch_regular = $current_regular > 0;
                $touch_sale = $current_sale > 0;
                break;
        }

        // Floor / ceiling guards
        $limited = false;
        if ($touch_regular && $new_regular != $current_regular) {
            list($new_regular, $regular_limited) = $this->price_calc->apply_limits($current_regular, $new_regular, $floor, $ceiling);
            $limited = $limited || $regular_limited;
        }
        if ($touch_sale && $new_sale > 0 && $new_sale != $current_sale) {
            // A brand-new sale is "lowering" the price from the regular price.
            $reference = $current_sale > 0 ? $current_sale : $current_regular;
            list($new_sale, $sale_limited) = $this->price_calc->apply_limits($reference, $new_sale, $floor, $ceiling);
            $limited = $limited || $sale_limited;
        }

        // Rounding (only on the prices this operation changed)
        $round_mode = isset($params['round_mode']) ? $params['round_mode'] : 'none';
        if ($round_mode !== 'none') {
            $round_step = isset($params['round_step']) ? $params['round_step'] : 0;
            $round_ending = isset($params['round_ending']) ? $params['round_ending'] : '';

            if ($touch_regular && $new_regular > 0) {
                $new_regular = $this->price_calc->apply_rounding($new_regular, $round_mode, $round_step, $round_ending);
            }
            if ($touch_sale && $new_sale > 0) {
                $new_sale = $this->price_calc->apply_rounding($new_sale, $round_mode, $round_step, $round_ending);
            }
        }

        // Consistency: a sale price must always stay below the regular price.
        if ($touch_regular && $new_regular <= 0) {
            $new_sale = 0;
        } elseif (($touch_regular || $touch_sale) && $new_sale > 0 && $new_regular > 0 && $new_sale >= $new_regular) {
            $new_sale = $this->price_calc->cap_sale_price($new_regular);
            $sale_capped = true;
        }

        // Discount percentages
        $old_discount_percent = $this->price_calc->calculate_discount_percent($current_regular, $current_sale);
        $new_discount_percent = $this->price_calc->calculate_discount_percent($new_regular, $new_sale);

        // Final price a customer pays, before and after
        $old_final = ($is_on_sale && $current_sale > 0 && $current_sale < $current_regular) ? $current_sale : $current_regular;
        $new_final = $this->formatter->calculate_final_price($type, $new_regular, $new_sale);

        // Price difference for display
        if (in_array($type, Bulk_Pricer_Operations::regular_diff_types(), true)) {
            $diff_data = $this->price_calc->calculate_price_difference($current_regular, $new_regular);
        } else {
            $diff_data = $this->price_calc->calculate_price_difference($old_final, $new_final);
        }

        $changed = abs($current_regular - $new_regular) > 0.000001 || abs($current_sale - $new_sale) > 0.000001;

        return array(
            'old_regular' => $current_regular,
            'new_regular' => $new_regular,
            'old_sale' => $current_sale,
            'new_sale' => $new_sale,
            'old_final' => $old_final,
            'new_final' => $new_final,
            'old_discount_percent' => $old_discount_percent,
            'new_discount_percent' => $new_discount_percent,
            'price_diff' => $diff_data['amount'],
            'price_diff_type' => $diff_data['type'],
            'sync_applied' => $sync_applied,
            'sale_capped' => $sale_capped,
            'limited' => $limited,
            'changed' => $changed,
            'is_on_sale' => $is_on_sale,
        );
    }

    /**
     * Calculate new prices based on operation (preview row)
     *
     * @since 2.0.0
     * @param WC_Product $product          Product object
     * @param array      $operation_params Operation parameters
     * @return array|false Preview data or false if invalid
     */
    public function calculate_new_prices($product, $operation_params)
    {
        $result = $this->compute_for_product($product, $operation_params);
        if (!$result) {
            return false;
        }

        // Get dates for preview
        $dates = $this->date_handler->get_preview_dates($product, $result['new_sale'], $operation_params);

        $price_data = array(
            'is_on_sale' => $result['is_on_sale'],
            'current_sale' => $result['old_sale'],
            'old_regular' => $result['old_regular'],
            'new_regular' => $result['new_regular'],
            'old_sale' => $result['old_sale'],
            'new_sale' => $result['new_sale'],
            'final_price' => $result['new_final'],
            'old_discount_percent' => $result['old_discount_percent'],
            'new_discount_percent' => $result['new_discount_percent'],
            'price_diff' => $result['price_diff'],
            'price_diff_type' => $result['price_diff_type'],
            'sync_applied' => $result['sync_applied'],
            'sale_capped' => $result['sale_capped'],
            'limited' => $result['limited'],
        );

        // Return formatted preview data
        return $this->formatter->build_preview_data($product, $price_data, $dates);
    }

    /**
     * Calculate the lightweight numbers used by the summary bar and CSV export
     *
     * @since 2.1.0
     * @param WC_Product $product          Product object
     * @param array      $operation_params Operation parameters
     * @return array|false
     */
    public function calculate_summary($product, $operation_params)
    {
        $result = $this->compute_for_product($product, $operation_params);

        return $result ? $result : false;
    }
}
