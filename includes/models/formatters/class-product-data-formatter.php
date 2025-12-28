<?php
/**
 * Product Data Formatter Class
 *
 * Formats product data for display
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Product_Data_Formatter Class
 *
 * Handles formatting of product information
 */
class Bulk_Pricer_Product_Data_Formatter
{
    /**
     * Get product image URL
     *
     * @since 2.0.0
     * @param WC_Product $product Product object
     * @return string Image URL or placeholder
     */
    public function get_product_image($product)
    {
        $image_id = $product->get_image_id();
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
            if ($image_url) {
                return $image_url;
            }
        }
        return wc_placeholder_img_src('thumbnail');
    }

    /**
     * Format price for display
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
     * Format sale price for display
     *
     * @since 2.0.0
     * @param float $price     Sale price
     * @param bool  $is_on_sale Whether product is on sale
     * @return string Formatted sale price or dash
     */
    public function format_sale_price($price, $is_on_sale)
    {
        if ($is_on_sale && $price > 0) {
            return number_format($price);
        }
        return '-';
    }

    /**
     * Calculate final display price
     *
     * @since 2.0.0
     * @param string $operation_type Operation type
     * @param float  $new_regular    New regular price
     * @param float  $new_sale       New sale price
     * @return float Final price
     */
    public function calculate_final_price($operation_type, $new_regular, $new_sale)
    {
        if ($operation_type === 'remove_discount') {
            return $new_regular;
        } elseif ($operation_type === 'set_sale') {
            return $new_sale > 0 ? $new_sale : $new_regular;
        } elseif ($operation_type === 'increase_reg' || $operation_type === 'decrease_reg') {
            if ($new_sale > 0 && $new_sale < $new_regular) {
                return $new_sale;
            }
            return $new_regular;
        }

        return $new_regular;
    }

    /**
     * Build preview data array
     *
     * @since 2.0.0
     * @param WC_Product $product     Product object
     * @param array      $price_data  Calculated price data
     * @param array      $dates       Date information
     * @return array Preview data
     */
    public function build_preview_data($product, $price_data, $dates)
    {
        return array(
            'product_id' => $product->get_id(),
            'name' => $product->get_name(),
            'image' => $this->get_product_image($product),
            'is_on_sale' => $price_data['is_on_sale'],
            'current_sale' => $this->format_sale_price($price_data['current_sale'], $price_data['is_on_sale']),
            'sale_start' => $dates['start'],
            'sale_end' => $dates['end'],
            'expiry' => $dates['end'],
            'old_reg' => $price_data['old_regular'],
            'old_reg_formatted' => $this->format_price($price_data['old_regular']),
            'new_reg' => $price_data['new_regular'],
            'new_reg_formatted' => $this->format_price($price_data['new_regular']),
            'old_sale' => $price_data['old_sale'],
            'new_sale' => $price_data['new_sale'],
            'new_sale_formatted' => $price_data['new_sale'] > 0 ? $this->format_price($price_data['new_sale']) : '-',
            'new_final' => $this->format_price($price_data['final_price']),
            'price_changed' => ($price_data['old_regular'] != $price_data['new_regular'] || $price_data['old_sale'] != $price_data['new_sale']),
            'old_discount_percent' => $price_data['old_discount_percent'],
            'new_discount_percent' => $price_data['new_discount_percent'],
            'price_diff' => $price_data['price_diff'],
            'price_diff_formatted' => $this->format_price($price_data['price_diff']),
            'price_diff_type' => $price_data['price_diff_type'],
            'sync_applied' => $price_data['sync_applied']
        );
    }
}
