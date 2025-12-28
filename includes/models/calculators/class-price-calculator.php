<?php
/**
 * Price Calculator Class
 *
 * Handles mathematical price calculations
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Price_Calculator Class
 *
 * Performs price calculations with precision
 */
class Bulk_Pricer_Price_Calculator
{
    /**
     * Calculate sale price from regular price
     *
     * @since 2.0.0
     * @param float $regular_price Regular price
     * @param float $percent       Percentage discount
     * @param float $fixed         Fixed amount discount
     * @return float Calculated sale price
     */
    public function calculate_sale_price($regular_price, $percent, $fixed)
    {
        $change = 0;

        if ($percent > 0) {
            $change = $regular_price * ($percent / 100);
        } elseif ($fixed > 0) {
            $change = $fixed;
        }

        if ($change > 0) {
            $new_sale = $this->round_price($regular_price - $change);

            // Ensure sale price is less than regular price
            if ($new_sale >= $regular_price) {
                $new_sale = $this->round_price($regular_price * 0.9);
            }
            if ($new_sale < 0) {
                $new_sale = 0;
            }

            return $new_sale;
        }

        return 0;
    }

    /**
     * Calculate new regular price (increase or decrease)
     *
     * @since 2.0.0
     * @param float  $current_price Current regular price
     * @param string $type          Operation type (increase_reg or decrease_reg)
     * @param float  $percent       Percentage change
     * @param float  $fixed         Fixed amount change
     * @return float New regular price
     */
    public function calculate_regular_price($current_price, $type, $percent, $fixed)
    {
        $change = 0;

        if ($percent > 0) {
            $change = $current_price * ($percent / 100);
        } elseif ($fixed > 0) {
            $change = $fixed;
        }

        if ($change > 0) {
            if ($type === 'increase_reg') {
                return $this->round_price($current_price + $change);
            } else {
                $new_price = $this->round_price($current_price - $change);
                return $new_price < 0 ? 0 : $new_price;
            }
        }

        return $current_price;
    }

    /**
     * Calculate synced sale price
     *
     * @since 2.0.0
     * @param float  $current_sale  Current sale price
     * @param float  $new_regular   New regular price
     * @param string $type          Operation type
     * @param float  $percent       Percentage change
     * @param float  $fixed         Fixed amount change
     * @return float New sale price
     */
    public function calculate_synced_sale_price($current_sale, $new_regular, $type, $percent, $fixed)
    {
        $change = 0;

        if ($percent > 0) {
            $change = $current_sale * ($percent / 100);
        } elseif ($fixed > 0) {
            $change = $fixed;
        }

        if ($change > 0) {
            $new_sale = 0;
            if ($type === 'increase_reg') {
                $new_sale = $this->round_price($current_sale + $change);
            } else {
                $new_sale = $this->round_price($current_sale - $change);
                if ($new_sale < 0) {
                    $new_sale = 0;
                }
            }

            // Ensure sale price is less than new regular price
            if ($new_sale >= $new_regular) {
                $new_sale = $this->round_price($new_regular * 0.9);
            }

            return $new_sale;
        }

        return $current_sale;
    }

    /**
     * Calculate discount percentage
     *
     * @since 2.0.0
     * @param float $regular_price Regular price
     * @param float $sale_price    Sale price
     * @return float Discount percentage
     */
    public function calculate_discount_percent($regular_price, $sale_price)
    {
        if ($regular_price <= 0 || $sale_price <= 0 || $sale_price >= $regular_price) {
            return 0;
        }
        return round((($regular_price - $sale_price) / $regular_price) * 100, 1);
    }

    /**
     * Calculate price difference
     *
     * @since 2.0.0
     * @param float $old_price Old price
     * @param float $new_price New price
     * @return array Difference data with amount and type
     */
    public function calculate_price_difference($old_price, $new_price)
    {
        $diff = $new_price - $old_price;
        $type = '';

        if ($diff > 0) {
            $type = 'increase';
        } elseif ($diff < 0) {
            $type = 'decrease';
        }

        return array(
            'amount' => abs($diff),
            'type' => $type
        );
    }

    /**
     * Round price with precision
     *
     * @since 2.0.0
     * @param float $price Price to round
     * @return int Rounded price
     */
    private function round_price($price)
    {
        return (int) round($price, 0, PHP_ROUND_HALF_DOWN);
    }
}
