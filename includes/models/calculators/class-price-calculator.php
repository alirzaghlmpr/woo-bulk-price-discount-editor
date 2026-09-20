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
                $new_sale = $this->cap_sale_price($regular_price);
            }
            if ($new_sale < 0) {
                $new_sale = 0;
            }

            return $new_sale;
        }

        return 0;
    }

    /**
     * Sale price used when a requested sale would meet/exceed the regular price
     *
     * @since 2.1.0
     * @param float $regular_price Regular price
     * @return float 10% off the regular price
     */
    public function cap_sale_price($regular_price)
    {
        return $this->round_price($regular_price * 0.9);
    }

    /**
     * Increase/decrease any price by a percentage or fixed amount
     *
     * @since 2.1.0
     * @param float $current_price Current price
     * @param bool  $increase      True to increase, false to decrease
     * @param float $percent       Percentage change
     * @param float $fixed         Fixed amount change
     * @return float New price (never below zero)
     */
    public function calculate_adjusted_price($current_price, $increase, $percent, $fixed)
    {
        $change = 0;

        if ($percent > 0) {
            $change = $current_price * ($percent / 100);
        } elseif ($fixed > 0) {
            $change = $fixed;
        }

        if ($change > 0) {
            if ($increase) {
                return $this->round_price($current_price + $change);
            }

            $new_price = $this->round_price($current_price - $change);
            return $new_price < 0 ? 0 : $new_price;
        }

        return $current_price;
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
        return $this->calculate_adjusted_price($current_price, $type === 'increase_reg', $percent, $fixed);
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
                $new_sale = $this->cap_sale_price($new_regular);
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
     * Keep a changed price inside the floor/ceiling guards
     *
     * The floor only stops a price from being *lowered* below it and the
     * ceiling only stops a price from being *raised* above it. A product that
     * is already outside the range is never pushed further out, and is never
     * pulled back in either (the guard doesn't create changes of its own).
     *
     * @since 2.1.0
     * @param float $old     Price before the operation
     * @param float $new     Price after the operation
     * @param float $floor   Minimum price (0 = off)
     * @param float $ceiling Maximum price (0 = off)
     * @return array array(float $price, bool $limited)
     */
    public function apply_limits($old, $new, $floor, $ceiling)
    {
        $limited = false;

        if ($floor > 0 && $new < $old && $new < $floor) {
            $new     = min($old, $floor);
            $limited = true;
        }

        if ($ceiling > 0 && $new > $old && $new > $ceiling) {
            $new     = max($old, $ceiling);
            $limited = true;
        }

        return array($new, $limited);
    }

    /**
     * Round a price with one of the rounding modes
     *
     * Modes: 'nearest' / 'up' / 'down' (to a multiple of $step) and 'ending'
     * (charm pricing: nearest price that ends in $ending).
     *
     * @since 2.1.0
     * @param float  $price  Price
     * @param string $mode   Rounding mode
     * @param float  $step   Step for nearest/up/down
     * @param string $ending Ending for the 'ending' mode (e.g. "99", ".99", "900")
     * @return float Rounded price (unchanged if rounding isn't possible)
     */
    public function apply_rounding($price, $mode, $step, $ending)
    {
        $price = (float) $price;

        if ($price <= 0 || $mode === 'none') {
            return $price;
        }

        switch ($mode) {
            case 'nearest':
            case 'up':
            case 'down':
                $step = (float) $step;
                if ($step <= 0) {
                    return $price;
                }

                // Round the quotient first so 100.0000000001 doesn't tip 'up' to the next step.
                $units = round($price / $step, 8);
                if ($mode === 'nearest') {
                    $units = floor($units + 0.5);
                } elseif ($mode === 'up') {
                    $units = ceil($units);
                } else {
                    $units = floor($units);
                }

                $result = round($units * $step, 6);
                break;

            case 'ending':
                $result = $this->apply_ending($price, (string) $ending);
                break;

            default:
                return $price;
        }

        // Never round a price down to nothing.
        return $result > 0 ? $result : $price;
    }

    /**
     * Parse a price ending such as "99", ".99", "9.99" or "900"
     *
     * The number of integer digits sets the cycle: "99" repeats every 100
     * (…199, 299), "900" every 1000, ".99" / "0.99" every 1 (…19.99, 20.99),
     * "9.99" every 10 (…19.99, 29.99).
     *
     * @since 2.1.0
     * @param string $ending Ending
     * @return array|null array(float $modulus, float $value) or null if invalid
     */
    public static function parse_ending($ending)
    {
        $ending = trim((string) $ending);

        if (!preg_match('/^(\d*)(?:\.(\d+))?$/', $ending, $m)) {
            return null;
        }

        $int  = $m[1];
        $frac = isset($m[2]) ? $m[2] : '';

        if ($int === '' && $frac === '') {
            return null;
        }

        $modulus = ($int === '' || $int === '0') ? 1 : pow(10, strlen($int));
        $value   = (float) (($int === '' ? '0' : $int) . ($frac !== '' ? '.' . $frac : ''));

        if ($value >= $modulus) {
            return null;
        }

        return array((float) $modulus, $value);
    }

    /**
     * Snap a price to the nearest price with the given ending
     *
     * @param float  $price  Price
     * @param string $ending Ending
     * @return float
     */
    private function apply_ending($price, $ending)
    {
        $parsed = self::parse_ending($ending);
        if (!$parsed) {
            return $price;
        }

        list($modulus, $value) = $parsed;

        // Largest candidate at or below the price, and the one after it.
        $base = floor(round(($price - $value) / $modulus, 8)) * $modulus + $value;
        $next = $base + $modulus;

        $best = null;
        foreach (array($base, $next) as $candidate) {
            if ($candidate <= 0) {
                continue;
            }

            if ($best === null) {
                $best = $candidate;
                continue;
            }

            $dist_best = abs($best - $price);
            $dist_cand = abs($candidate - $price);

            // Closest wins; on a tie the higher price wins.
            if ($dist_cand < $dist_best - 0.000000001 || abs($dist_cand - $dist_best) <= 0.000000001) {
                $best = $candidate;
            }
        }

        return $best === null ? $price : round($best, 6);
    }

    /**
     * Round price with precision
     *
     * Respects the store's configured number of decimals so the plugin works
     * for both whole-number currencies (e.g. Toman/Rial) and decimal
     * currencies (e.g. USD/EUR).
     *
     * @since 2.0.0
     * @param float $price Price to round
     * @return float Rounded price
     */
    public function round_price($price)
    {
        $decimals = function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 0;
        return round((float) $price, $decimals, PHP_ROUND_HALF_DOWN);
    }
}
