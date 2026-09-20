<?php
/**
 * CSV Service Class
 *
 * Exports the preview as CSV and parses CSV files for per-SKU price imports.
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Csv_Service Class
 */
class Bulk_Pricer_Csv_Service
{
    /**
     * Max data rows accepted in one import
     */
    const MAX_ROWS = 20000;

    /**
     * Rows shown in the import preview
     */
    const PREVIEW_ROWS = 200;

    /**
     * Errors listed in the import preview
     */
    const MAX_ERRORS_SHOWN = 100;

    /**
     * @var Bulk_Pricer_Pricing_Calculator
     */
    private $calculator;

    /**
     * @var Bulk_Pricer_Product_Model
     */
    private $products;

    /**
     * @var Bulk_Pricer_Date_Handler
     */
    private $dates;

    /**
     * Constructor
     *
     * @since 2.1.0
     */
    public function __construct()
    {
        $this->calculator = new Bulk_Pricer_Pricing_Calculator();
        $this->products = new Bulk_Pricer_Product_Model();
        $this->dates = new Bulk_Pricer_Date_Handler();
    }

    /**
     * Guard a text cell against spreadsheet formula injection
     *
     * @since 2.1.0
     * @param string $value Cell text
     * @return string
     */
    public function safe_cell($value)
    {
        $value = (string) $value;

        if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Write the preview of an operation as CSV
     *
     * Streams in chunks so the whole catalog never sits in memory.
     *
     * @since 2.1.0
     * @param resource $out       Output stream
     * @param int[]    $ids       Product IDs to include
     * @param array    $operation Validated operation data
     */
    public function write_preview_csv($out, $ids, $operation)
    {
        $decimals = function_exists('wc_get_price_decimals') ? max((int) wc_get_price_decimals(), 2) : 2;

        $this->put($out, array(
            __('ID', 'bulk-price-discount-editor-for-woocommerce'),
            __('Parent ID', 'bulk-price-discount-editor-for-woocommerce'),
            __('SKU', 'bulk-price-discount-editor-for-woocommerce'),
            __('Name', 'bulk-price-discount-editor-for-woocommerce'),
            __('Old regular price', 'bulk-price-discount-editor-for-woocommerce'),
            __('New regular price', 'bulk-price-discount-editor-for-woocommerce'),
            __('Old sale price', 'bulk-price-discount-editor-for-woocommerce'),
            __('New sale price', 'bulk-price-discount-editor-for-woocommerce'),
            __('Old final price', 'bulk-price-discount-editor-for-woocommerce'),
            __('New final price', 'bulk-price-discount-editor-for-woocommerce'),
            __('Old discount %', 'bulk-price-discount-editor-for-woocommerce'),
            __('New discount %', 'bulk-price-discount-editor-for-woocommerce'),
            __('Will change', 'bulk-price-discount-editor-for-woocommerce'),
        ));

        foreach (array_chunk($ids, 200) as $chunk) {
            foreach ($chunk as $id) {
                $product = wc_get_product($id);
                if (!$product) {
                    continue;
                }

                $r = $this->calculator->calculate_summary($product, $operation);
                if (!$r) {
                    continue;
                }

                $this->put($out, array(
                    $product->get_id(),
                    $product->get_parent_id(),
                    $this->safe_cell($product->get_sku()),
                    $this->safe_cell($product->get_name()),
                    $this->num($r['old_regular'], $decimals),
                    $this->num($r['new_regular'], $decimals),
                    $r['old_sale'] > 0 ? $this->num($r['old_sale'], $decimals) : '',
                    $r['new_sale'] > 0 ? $this->num($r['new_sale'], $decimals) : '',
                    $this->num($r['old_final'], $decimals),
                    $this->num($r['new_final'], $decimals),
                    $r['old_discount_percent'],
                    $r['new_discount_percent'],
                    $r['changed'] ? __('Yes', 'bulk-price-discount-editor-for-woocommerce') : __('No', 'bulk-price-discount-editor-for-woocommerce'),
                ));
            }
        }
    }

    /**
     * Write a history run's recorded changes as CSV
     *
     * @since 2.1.0
     * @param resource $out    Output stream
     * @param int      $run_id Run ID
     */
    public function write_run_csv($out, $run_id)
    {
        $runs = new Bulk_Pricer_Run_Model();

        $this->put($out, array(
            __('ID', 'bulk-price-discount-editor-for-woocommerce'),
            __('Parent ID', 'bulk-price-discount-editor-for-woocommerce'),
            __('SKU', 'bulk-price-discount-editor-for-woocommerce'),
            __('Name', 'bulk-price-discount-editor-for-woocommerce'),
            __('Old regular price', 'bulk-price-discount-editor-for-woocommerce'),
            __('New regular price', 'bulk-price-discount-editor-for-woocommerce'),
            __('Old sale price', 'bulk-price-discount-editor-for-woocommerce'),
            __('New sale price', 'bulk-price-discount-editor-for-woocommerce'),
            __('State', 'bulk-price-discount-editor-for-woocommerce'),
        ));

        foreach (array(Bulk_Pricer_Run_Model::ITEM_APPLIED, Bulk_Pricer_Run_Model::ITEM_REVERTED) as $status) {
            $offset = 0;
            do {
                $items = $runs->get_items($run_id, $status, 500, $offset);
                foreach ($items as $item) {
                    $product = wc_get_product((int) $item->product_id);

                    $this->put($out, array(
                        (int) $item->product_id,
                        (int) $item->parent_id,
                        $product ? $this->safe_cell($product->get_sku()) : '',
                        $product ? $this->safe_cell($product->get_name()) : '',
                        $item->old_regular,
                        $item->new_regular,
                        $item->old_sale,
                        $item->new_sale,
                        $status === Bulk_Pricer_Run_Model::ITEM_APPLIED
                            ? __('Applied', 'bulk-price-discount-editor-for-woocommerce')
                            : __('Reverted', 'bulk-price-discount-editor-for-woocommerce'),
                    ));
                }
                $offset += 500;
            } while (count($items) === 500);
        }
    }

    /**
     * Parse an uploaded price CSV
     *
     * Columns (header row required, case-insensitive): "sku" or "id" to find
     * the product, then any of "regular_price", "sale_price", "sale_start",
     * "sale_end". Empty cells leave that value alone; a sale_price of 0
     * removes the sale.
     *
     * @since 2.1.0
     * @param string $path Uploaded file path
     * @return array|WP_Error rows, preview, errors, counts
     */
    public function parse($path)
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $handle = fopen($path, 'r');
        if (!$handle) {
            return new WP_Error('sbp_csv', __('The uploaded file could not be read.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $first = fgets($handle);
        if ($first === false || trim($first) === '') {
            fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            return new WP_Error('sbp_csv', __('The CSV file is empty.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = $this->detect_delimiter($first);
        $map = $this->map_headers(str_getcsv($first, $delimiter, '"', '\\'));

        if (!isset($map['sku']) && !isset($map['id'])) {
            fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            return new WP_Error('sbp_csv', __('The CSV needs a "sku" or "id" column to find products.', 'bulk-price-discount-editor-for-woocommerce'));
        }
        if (!isset($map['regular']) && !isset($map['sale']) && !isset($map['from']) && !isset($map['to'])) {
            fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            return new WP_Error('sbp_csv', __('The CSV needs at least one of: regular_price, sale_price, sale_start, sale_end.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $items = array();
        $preview = array();
        $errors = array();
        $error_count = 0;
        $unchanged = 0;
        $duplicates = 0;
        $line = 1;
        $data_rows = 0;

        while (($cols = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $line++;

            // Skip blank lines
            if (count($cols) === 1 && ($cols[0] === null || trim((string) $cols[0]) === '')) {
                continue;
            }

            if (++$data_rows > self::MAX_ROWS) {
                /* translators: %d: maximum number of rows */
                $errors[] = array($line, sprintf(__('Stopped reading: a single import is limited to %d rows.', 'bulk-price-discount-editor-for-woocommerce'), self::MAX_ROWS));
                $error_count++;
                break;
            }

            $row = $this->parse_row($cols, $map);

            if (isset($row['error'])) {
                $error_count++;
                if (count($errors) < self::MAX_ERRORS_SHOWN) {
                    $errors[] = array($line, $row['error']);
                }
                continue;
            }

            if (!$row['changed']) {
                $unchanged++;
                continue;
            }

            $pid = $row['item']['product_id'];
            if (isset($items[$pid])) {
                $duplicates++;
                unset($preview[$pid]);
            }
            $items[$pid] = $row['item'];

            if (count($preview) < self::PREVIEW_ROWS || isset($preview[$pid])) {
                $preview[$pid] = $row['display'];
            }
        }

        fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

        return array(
            'items' => array_values($items),
            'preview' => array_values($preview),
            'errors' => $errors,
            'counts' => array(
                'ready' => count($items),
                'unchanged' => $unchanged,
                'errors' => $error_count,
                'duplicates' => $duplicates,
            ),
        );
    }

    /**
     * Parse and validate one CSV row
     *
     * @param array $cols Row cells
     * @param array $map  Canonical column => index
     * @return array error message, or item + display + changed
     */
    private function parse_row($cols, $map)
    {
        $id_cell = $this->cell($cols, $map, 'id');
        $sku = $this->cell($cols, $map, 'sku');

        $product_id = 0;
        if ($id_cell !== '' && ctype_digit($id_cell)) {
            $product_id = (int) $id_cell;
        } elseif ($sku !== '') {
            $product_id = (int) wc_get_product_id_by_sku($sku);
        }

        if (!$product_id) {
            return array('error' => $sku !== ''
                /* translators: %s: SKU */
                ? sprintf(__('No product found with SKU "%s".', 'bulk-price-discount-editor-for-woocommerce'), $sku)
                : __('Missing product SKU or ID.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            /* translators: %d: product ID */
            return array('error' => sprintf(__('Product #%d does not exist.', 'bulk-price-discount-editor-for-woocommerce'), $product_id));
        }
        if (!$this->products->is_priceable($product)) {
            /* translators: %d: product ID */
            return array('error' => sprintf(__('Product #%d is a variable/grouped parent with no price of its own. Use the SKU or ID of a variation.', 'bulk-price-discount-editor-for-woocommerce'), $product_id));
        }

        $regular_raw = $this->cell($cols, $map, 'regular');
        $sale_raw = $this->cell($cols, $map, 'sale');
        $from_raw = $this->cell($cols, $map, 'from');
        $to_raw = $this->cell($cols, $map, 'to');

        $new_regular = null;
        if ($regular_raw !== '') {
            $new_regular = $this->parse_number($regular_raw);
            if ($new_regular === null || $new_regular <= 0) {
                /* translators: %s: cell value */
                return array('error' => sprintf(__('Invalid regular price "%s".', 'bulk-price-discount-editor-for-woocommerce'), $regular_raw));
            }
        }

        $new_sale = null;
        if ($sale_raw !== '') {
            $new_sale = $this->parse_number($sale_raw);
            if ($new_sale === null) {
                /* translators: %s: cell value */
                return array('error' => sprintf(__('Invalid sale price "%s".', 'bulk-price-discount-editor-for-woocommerce'), $sale_raw));
            }
        }

        $new_from = null;
        if ($from_raw !== '') {
            $new_from = $this->dates->to_timestamp(str_replace('/', '-', $from_raw));
            if ($new_from === null) {
                /* translators: %s: cell value */
                return array('error' => sprintf(__('Invalid sale start date "%s" (use YYYY-MM-DD).', 'bulk-price-discount-editor-for-woocommerce'), $from_raw));
            }
        }

        $new_to = null;
        if ($to_raw !== '') {
            $new_to = $this->dates->to_timestamp(str_replace('/', '-', $to_raw), true);
            if ($new_to === null) {
                /* translators: %s: cell value */
                return array('error' => sprintf(__('Invalid sale end date "%s" (use YYYY-MM-DD).', 'bulk-price-discount-editor-for-woocommerce'), $to_raw));
            }
        }

        if ($new_regular === null && $new_sale === null && $new_from === null && $new_to === null) {
            return array('error' => __('Nothing to change in this row.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $old = $this->products->get_price_snapshot($product);
        $old_regular = (float) $old['regular'];
        $old_sale = (float) $old['sale'];

        $final_regular = $new_regular !== null ? $new_regular : $old_regular;
        $final_sale = $new_sale !== null ? ($new_sale > 0 ? $new_sale : 0) : $old_sale;

        if ($final_regular <= 0) {
            return array('error' => __('The product has no regular price; provide one.', 'bulk-price-discount-editor-for-woocommerce'));
        }
        if ($final_sale > 0 && $final_sale >= $final_regular) {
            return array('error' => __('The sale price must be lower than the regular price.', 'bulk-price-discount-editor-for-woocommerce'));
        }
        if ($new_from !== null && $new_to !== null && $new_from > $new_to) {
            return array('error' => __('The sale end date must be after the start date.', 'bulk-price-discount-editor-for-woocommerce'));
        }

        $changed = abs($old_regular - $final_regular) > 0.000001
            || abs($old_sale - $final_sale) > 0.000001
            || ($final_sale > 0 && $new_from !== null && (int) $old['from'] !== (int) $new_from)
            || ($final_sale > 0 && $new_to !== null && (int) $old['to'] !== (int) $new_to);

        return array(
            'changed' => $changed,
            'item' => array(
                'product_id' => $product_id,
                'regular' => $new_regular !== null ? (string) $new_regular : null,
                'sale' => $new_sale !== null ? (string) ($new_sale > 0 ? $new_sale : 0) : null,
                'from' => $new_from,
                'to' => $new_to,
            ),
            'display' => array(
                'product_id' => $product_id,
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'old_regular' => $old_regular,
                'new_regular' => $final_regular,
                'old_sale' => $old_sale,
                'new_sale' => $final_sale,
                'start' => ($final_sale > 0 && $new_from !== null) ? $this->dates->format_timestamp($new_from) : '',
                'end' => ($final_sale > 0 && $new_to !== null) ? $this->dates->format_timestamp($new_to) : '',
            ),
        );
    }

    /**
     * Get a trimmed cell by canonical column name
     *
     * @param array  $cols Row cells
     * @param array  $map  Canonical column => index
     * @param string $key  Canonical column
     * @return string
     */
    private function cell($cols, $map, $key)
    {
        if (!isset($map[$key]) || !isset($cols[$map[$key]])) {
            return '';
        }

        return trim((string) $cols[$map[$key]]);
    }

    /**
     * Map header names to canonical columns
     *
     * @param array $headers Header cells
     * @return array Canonical column => index
     */
    private function map_headers($headers)
    {
        $aliases = array(
            'sku' => 'sku',
            'id' => 'id',
            'product_id' => 'id',
            'regular_price' => 'regular',
            'regular' => 'regular',
            'sale_price' => 'sale',
            'sale' => 'sale',
            'sale_start' => 'from',
            'sale_from' => 'from',
            'date_on_sale_from' => 'from',
            'sale_end' => 'to',
            'sale_expiry' => 'to',
            'sale_to' => 'to',
            'date_on_sale_to' => 'to',
        );

        $map = array();
        foreach ($headers as $index => $header) {
            $key = strtolower(trim(str_replace(array(' ', '-'), '_', (string) $header)));
            if (isset($aliases[$key]) && !isset($map[$aliases[$key]])) {
                $map[$aliases[$key]] = $index;
            }
        }

        return $map;
    }

    /**
     * Pick the delimiter that fits the header line best
     *
     * @param string $line Header line
     * @return string
     */
    private function detect_delimiter($line)
    {
        $best = ',';
        $max = 0;

        foreach (array(',', ';', "\t") as $delimiter) {
            $count = substr_count($line, $delimiter);
            if ($count > $max) {
                $max = $count;
                $best = $delimiter;
            }
        }

        return $best;
    }

    /**
     * Parse a price cell
     *
     * Understands Persian/Arabic digits and both "1,234.50" and "1.234,50"
     * style separators.
     *
     * @param string $raw Cell text
     * @return float|null
     */
    private function parse_number($raw)
    {
        $s = strtr(trim($raw), array(
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٫' => '.', '٬' => ',',
        ));

        $s = preg_replace('/[^0-9.,\-]/u', '', $s);
        if ($s === '' || $s === null) {
            return null;
        }

        if (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $s)) {
            $s = str_replace(',', '', $s);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace(array('.', ','), array('', '.'), $s);
        } else {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) && (float) $s >= 0 ? (float) $s : null;
    }

    /**
     * Format a number for CSV output (dot decimal, no thousands separator)
     *
     * @param float $value    Number
     * @param int   $decimals Minimum decimals
     * @return string
     */
    private function num($value, $decimals)
    {
        $formatted = number_format((float) $value, 4, '.', '');
        $trimmed = rtrim(rtrim($formatted, '0'), '.');
        $dot = strpos($trimmed, '.');
        $have = $dot === false ? 0 : strlen($trimmed) - $dot - 1;

        return $have >= $decimals ? $trimmed : number_format((float) $value, $decimals, '.', '');
    }

    /**
     * Write one CSV row
     *
     * @param resource $out Output stream
     * @param array    $row Cells
     */
    private function put($out, $row)
    {
        fputcsv($out, $row, ',', '"', '\\'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
    }
}
