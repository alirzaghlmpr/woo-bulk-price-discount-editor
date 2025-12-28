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
     * Calculate new prices based on operation
     *
     * @since 2.0.0
     * @param WC_Product $product          Product object
     * @param array      $operation_params Operation parameters
     * @return array|false Price data or false if invalid
     */
    public function calculate_new_prices($product, $operation_params)
    {
        $operation_type = $operation_params['operation_type'];
        $change_percent = $operation_params['change_percent'];
        $change_fixed = $operation_params['change_fixed'];
        $sync_sale = $operation_params['sync_sale'];

        // Get current prices
        $current_regular = (float) $product->get_regular_price();
        $current_sale = (float) $product->get_sale_price();
        $is_on_sale = $product->is_on_sale();

        // Initialize new prices
        $new_regular = $current_regular;
        $new_sale = $current_sale;
        $sync_applied = false;

        // Validation: both percent and fixed cannot be used together
        if ($operation_type !== 'remove_discount' && $change_percent > 0 && $change_fixed > 0) {
            return false;
        }

        // Calculate based on operation type
        switch ($operation_type) {
            case 'remove_discount':
                $new_sale = 0;
                break;

            case 'set_sale':
                if ($current_regular > 0) {
                    $new_sale = $this->price_calc->calculate_sale_price(
                        $current_regular,
                        $change_percent,
                        $change_fixed
                    );
                }
                break;

            case 'increase_reg':
            case 'decrease_reg':
                if ($current_regular > 0) {
                    $result = $this->handle_regular_price_change(
                        $current_regular,
                        $current_sale,
                        $operation_type,
                        $change_percent,
                        $change_fixed,
                        $sync_sale,
                        $is_on_sale
                    );
                    $new_regular = $result['new_regular'];
                    $new_sale = $result['new_sale'];
                    $sync_applied = $result['sync_applied'];
                }
                break;
        }

        // Calculate discount percentages
        $old_discount_percent = $this->price_calc->calculate_discount_percent($current_regular, $current_sale);
        $new_discount_percent = $this->price_calc->calculate_discount_percent($new_regular, $new_sale);

        // Calculate final display price
        $final_display_price = $this->formatter->calculate_final_price($operation_type, $new_regular, $new_sale);

        // Calculate price difference
        $price_diff_data = $this->calculate_price_difference_for_display(
            $operation_type,
            $current_regular,
            $current_sale,
            $new_regular,
            $new_sale,
            $is_on_sale,
            $final_display_price
        );

        // Get dates for preview
        $dates = $this->date_handler->get_preview_dates($product, $is_on_sale, $new_sale, $operation_params);

        // Build price data
        $price_data = array(
            'is_on_sale' => $is_on_sale,
            'current_sale' => $current_sale,
            'old_regular' => $current_regular,
            'new_regular' => $new_regular,
            'old_sale' => $current_sale,
            'new_sale' => $new_sale,
            'final_price' => $final_display_price,
            'old_discount_percent' => $old_discount_percent,
            'new_discount_percent' => $new_discount_percent,
            'price_diff' => $price_diff_data['price_diff'],
            'price_diff_type' => $price_diff_data['price_diff_type'],
            'sync_applied' => $sync_applied
        );

        // Return formatted preview data
        return $this->formatter->build_preview_data($product, $price_data, $dates);
    }

    /**
     * Handle regular price change with optional sale price sync
     *
     * @since 2.0.0
     * @param float  $regular      Current regular price
     * @param float  $sale         Current sale price
     * @param string $type         Operation type
     * @param float  $percent      Percentage change
     * @param float  $fixed        Fixed amount change
     * @param bool   $sync         Whether to sync sale price
     * @param bool   $is_on_sale   Whether product is on sale
     * @return array New prices and sync status
     */
    private function handle_regular_price_change($regular, $sale, $type, $percent, $fixed, $sync, $is_on_sale)
    {
        // Calculate new regular price
        $new_regular = $this->price_calc->calculate_regular_price($regular, $type, $percent, $fixed);
        $new_sale = $sale;
        $sync_applied = false;

        // Sync sale price if enabled and product is on sale
        if ($sync && $is_on_sale && $sale > 0) {
            $new_sale = $this->price_calc->calculate_synced_sale_price(
                $sale,
                $new_regular,
                $type,
                $percent,
                $fixed
            );
            $sync_applied = true;
        }

        return array(
            'new_regular' => $new_regular,
            'new_sale' => $new_sale,
            'sync_applied' => $sync_applied
        );
    }

    /**
     * Calculate price difference for display
     *
     * @since 2.0.0
     * @param string $operation_type     Operation type
     * @param float  $old_regular        Old regular price
     * @param float  $old_sale           Old sale price
     * @param float  $new_regular        New regular price
     * @param float  $new_sale           New sale price
     * @param bool   $is_on_sale         Whether product is on sale
     * @param float  $final_display_price Final display price
     * @return array Price difference data
     */
    private function calculate_price_difference_for_display($operation_type, $old_regular, $old_sale, $new_regular, $new_sale, $is_on_sale, $final_display_price)
    {
        $price_diff = 0;

        if ($operation_type === 'set_sale' || $operation_type === 'remove_discount') {
            $old_final = $is_on_sale && $old_sale > 0 ? $old_sale : $old_regular;
            $price_diff = $final_display_price - $old_final;
        } else {
            $price_diff = $new_regular - $old_regular;
        }

        $diff_data = $this->price_calc->calculate_price_difference($old_regular, $new_regular);

        if ($operation_type === 'set_sale' || $operation_type === 'remove_discount') {
            $old_final = $is_on_sale && $old_sale > 0 ? $old_sale : $old_regular;
            $diff_data = $this->price_calc->calculate_price_difference($old_final, $final_display_price);
        }

        return array(
            'price_diff' => $diff_data['amount'],
            'price_diff_type' => $diff_data['type']
        );
    }
}
