<?php
/**
 * Product Model Class
 *
 * Handles WooCommerce product data access
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Product_Model Class
 *
 * Manages product data retrieval and updates
 */
class Bulk_Pricer_Product_Model
{
    /**
     * Products per page
     *
     * @var int
     */
    private $per_page = 20;

    /**
     * Get products with pagination
     *
     * @since 2.0.0
     * @param int   $page    Page number
     * @param array $filters Filter options
     * @return object WooCommerce products result
     */
    public function get_products_paginated($page, $filters = array())
    {
        $args = array(
            'limit'    => $this->per_page,
            'page'     => $page,
            'status'   => 'publish',
            'paginate' => true
        );

        // Apply on sale filter
        if (isset($filters['only_on_sale']) && $filters['only_on_sale']) {
            $args['on_sale'] = true;
        }

        // Apply category filter
        if (isset($filters['category_id']) && intval($filters['category_id']) > 0) {
            $category_term = get_term(intval($filters['category_id']), 'product_cat');
            if ($category_term && !is_wp_error($category_term)) {
                $args['category'] = array($category_term->slug);
            }
        }

        return wc_get_products($args);
    }

    /**
     * Get all product variants (including variable products)
     *
     * @since 2.0.0
     * @param object $products_result WooCommerce products result
     * @return array Array of product objects
     */
    public function get_all_product_variants($products_result)
    {
        $items = array();

        foreach ($products_result->products as $product) {
            if ($product->is_type('variable')) {
                // Handle variable products - get all variations
                foreach ($product->get_children() as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $items[] = $variation;
                    }
                }
            } else {
                // Simple product
                $items[] = $product;
            }
        }

        return $items;
    }

    /**
     * Update product prices
     *
     * @since 2.0.0
     * @param WC_Product $product     Product object
     * @param float      $new_regular New regular price
     * @param float      $new_sale    New sale price
     * @param string     $start_date  Optional start date
     * @param string     $expiry_date Optional expiry date
     * @return bool Success status
     */
    public function update_product_prices($product, $new_regular, $new_sale, $start_date = null, $expiry_date = null)
    {
        // Update regular price
        $product->set_regular_price($new_regular);

        // Update sale price
        if ($new_sale > 0) {
            $product->set_sale_price($new_sale);
            if ($start_date) {
                $product->set_date_on_sale_from(strtotime($start_date));
            }
            if ($expiry_date) {
                $product->set_date_on_sale_to(strtotime($expiry_date));
            }
        } else {
            // Remove sale price
            $product->set_sale_price('');
            $product->set_date_on_sale_from('');
            $product->set_date_on_sale_to('');
        }

        // Save changes
        $product->save();

        return true;
    }

    /**
     * Get per page value
     *
     * @since 2.0.0
     * @return int Products per page
     */
    public function get_per_page()
    {
        return $this->per_page;
    }
}
