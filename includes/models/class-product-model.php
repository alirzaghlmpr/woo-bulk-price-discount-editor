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
     * Products per preview page
     *
     * @var int
     */
    private $per_page = 20;

    /**
     * Product types that hold variations (and have no price of their own)
     *
     * @since 2.1.0
     * @return string[]
     */
    public function get_variable_types()
    {
        return (array) apply_filters('bulk_pricer_variable_types', array('variable', 'variable-subscription'));
    }

    /**
     * Get all matching product IDs (variable products expanded into variations)
     *
     * Variable and grouped parents have no price of their own, so the list
     * contains simple/external products plus the individual variations. The
     * order is stable (parent ID, then variation order), so it can be paginated
     * and processed in chunks.
     *
     * Runs a handful of queries instead of loading every parent product, so
     * it stays fast on large catalogs.
     *
     * @since 2.0.1
     * @param array $filters Filter options (category_id, only_on_sale, has_sale_price)
     * @return int[] Array of product/variation IDs
     */
    public function get_all_matching_ids($filters = array())
    {
        global $wpdb;

        $args = array(
            'limit'   => -1,
            'status'  => 'publish',
            'return'  => 'ids',
            'orderby' => 'ID',
            'order'   => 'ASC',
        );

        // Apply on sale filter
        $only_on_sale = !empty($filters['only_on_sale']);
        if ($only_on_sale) {
            $args['on_sale'] = true;
        }

        // Apply category filter
        if (isset($filters['category_id']) && intval($filters['category_id']) > 0) {
            $category_term = get_term(intval($filters['category_id']), 'product_cat');
            if ($category_term && !is_wp_error($category_term)) {
                $args['category'] = array($category_term->slug);
            }
        }

        $parent_ids = array_map('intval', wc_get_products($args));
        if (empty($parent_ids)) {
            return array();
        }

        $variable_ids = array_flip(array_map('intval', wc_get_products(array_merge($args, array('type' => $this->get_variable_types())))));
        $grouped_ids  = array_flip(array_map('intval', wc_get_products(array_merge($args, array('type' => 'grouped')))));

        $variations = $variable_ids ? $this->get_variation_ids_by_parent(array_keys($variable_ids)) : array();

        // "Only on sale" must apply per variation: a variable parent is "on sale"
        // as soon as one variation is, but only those variations should be edited.
        $on_sale_lookup = $only_on_sale ? array_flip(array_map('intval', wc_get_product_ids_on_sale())) : null;

        // Operations that need an existing sale price skip everything else up front.
        $sale_lookup = !empty($filters['has_sale_price']) ? array_flip($this->get_ids_with_sale_price()) : null;

        $ids = array();
        foreach ($parent_ids as $parent_id) {
            if (isset($grouped_ids[$parent_id])) {
                continue;
            }

            if (isset($variable_ids[$parent_id])) {
                if (empty($variations[$parent_id])) {
                    continue;
                }
                foreach ($variations[$parent_id] as $variation_id) {
                    if ($on_sale_lookup !== null && !isset($on_sale_lookup[$variation_id])) {
                        continue;
                    }
                    if ($sale_lookup !== null && !isset($sale_lookup[$variation_id])) {
                        continue;
                    }
                    $ids[] = $variation_id;
                }
                continue;
            }

            if ($sale_lookup !== null && !isset($sale_lookup[$parent_id])) {
                continue;
            }
            $ids[] = $parent_id;
        }

        return $ids;
    }

    /**
     * Get the variation IDs of several variable products
     *
     * Includes disabled (private) variations, like WC_Product_Variable::get_children().
     *
     * @since 2.1.0
     * @param int[] $parent_ids Variable product IDs
     * @return array Map of parent ID => variation IDs
     */
    private function get_variation_ids_by_parent($parent_ids)
    {
        global $wpdb;

        $map = array();

        foreach (array_chunk($parent_ids, 1000) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '%d'));

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_parent FROM {$wpdb->posts}
                 WHERE post_type = 'product_variation'
                   AND post_status IN ('publish', 'private')
                   AND post_parent IN ({$placeholders})
                 ORDER BY post_parent ASC, menu_order ASC, ID ASC",
                $chunk
            ));

            foreach ($rows as $row) {
                $map[(int) $row->post_parent][] = (int) $row->ID;
            }
        }

        return $map;
    }

    /**
     * IDs of products/variations that have a sale price set
     *
     * @since 2.1.0
     * @return int[]
     */
    private function get_ids_with_sale_price()
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ids = $wpdb->get_col("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sale_price' AND meta_value <> ''");

        return array_map('intval', $ids);
    }

    /**
     * Slice one preview page out of an ID list
     *
     * @since 2.1.0
     * @param int[] $ids  All matching IDs
     * @param int   $page Page number (1-based)
     * @return int[]
     */
    public function get_ids_page($ids, $page)
    {
        return array_slice($ids, (max(1, (int) $page) - 1) * $this->per_page, $this->per_page);
    }

    /**
     * Whether a product's own price can be edited
     *
     * @since 2.1.0
     * @param WC_Product $product Product object
     * @return bool
     */
    public function is_priceable($product)
    {
        return !$product->is_type(array_merge($this->get_variable_types(), array('grouped')));
    }

    /**
     * Read the current price fields of a product
     *
     * @since 2.1.0
     * @param WC_Product $product Product object
     * @return array regular, sale (strings), from, to (timestamps or null)
     */
    public function get_price_snapshot($product)
    {
        $from = $product->get_date_on_sale_from('edit');
        $to   = $product->get_date_on_sale_to('edit');

        return array(
            'regular' => (string) $product->get_regular_price('edit'),
            'sale' => (string) $product->get_sale_price('edit'),
            'from' => $from ? $from->getTimestamp() : null,
            'to' => $to ? $to->getTimestamp() : null,
        );
    }

    /**
     * Update product prices
     *
     * $sale_from / $sale_to: a timestamp sets the date, null leaves the existing
     * date untouched. When the sale is removed both dates are cleared.
     *
     * @since 2.0.0
     * @param WC_Product $product     Product object
     * @param float      $new_regular New regular price
     * @param float      $new_sale    New sale price (0 = remove)
     * @param int|null   $sale_from   Sale start timestamp
     * @param int|null   $sale_to     Sale end timestamp
     * @return bool Success status
     */
    public function update_product_prices($product, $new_regular, $new_sale, $sale_from = null, $sale_to = null)
    {
        // Update regular price
        $product->set_regular_price($this->to_storage($new_regular));

        // Update sale price
        if ($new_sale > 0) {
            $product->set_sale_price($this->to_storage($new_sale));
            if ($sale_from !== null) {
                $product->set_date_on_sale_from((int) $sale_from);
            }
            if ($sale_to !== null) {
                $product->set_date_on_sale_to((int) $sale_to);
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
     * Put a product's price fields back to earlier values (undo)
     *
     * @since 2.1.0
     * @param WC_Product $product Product object
     * @param string     $regular Regular price ('' = none)
     * @param string     $sale    Sale price ('' = none)
     * @param int|null   $from    Sale start timestamp
     * @param int|null   $to      Sale end timestamp
     */
    public function restore_product_prices($product, $regular, $sale, $from, $to)
    {
        $product->set_regular_price((string) $regular);
        $product->set_sale_price((string) $sale);
        $product->set_date_on_sale_from($from ? (int) $from : '');
        $product->set_date_on_sale_to($to ? (int) $to : '');
        $product->save();
    }

    /**
     * Refresh variable products after their variations were saved
     *
     * Saving a variation does not update its parent. Without this the
     * parent's stored price (used for the "From X" price range, sorting and
     * price filters) and the cached variation prices stay stale until the
     * parent is next edited by hand.
     *
     * @since 2.1.0
     * @param int[] $parent_ids Variable product IDs
     */
    public function sync_variable_parents($parent_ids)
    {
        foreach (array_unique(array_filter(array_map('intval', $parent_ids))) as $parent_id) {
            if (class_exists('WC_Product_Variable')) {
                WC_Product_Variable::sync($parent_id);
            }
            wc_delete_product_transients($parent_id);
        }
    }

    /**
     * Format a price for storage (no float noise, no trailing zeros)
     *
     * @param float $price Price
     * @return string
     */
    private function to_storage($price)
    {
        $formatted = rtrim(rtrim(number_format((float) $price, 6, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
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

    /**
     * Number of products processed per apply request
     *
     * @since 2.1.0
     * @return int
     */
    public function get_batch_size()
    {
        return max(1, (int) apply_filters('bulk_pricer_batch_size', 20));
    }
}
