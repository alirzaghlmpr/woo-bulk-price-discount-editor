<?php
/**
 * Simple PHP script to compile .po files to .mo files
 *
 * This doesn't require any external tools, just PHP!
 *
 * Usage: php compile-mo.php
 */

// Prevent direct file access (allow CLI execution).
if (!defined('ABSPATH')) {
    if (php_sapi_name() !== 'cli') {
        exit;
    }
}

/**
 * Compile a .po file to .mo format
 *
 * @param string $po_file Path to .po file
 * @param string $mo_file Path to output .mo file
 * @return bool Success status
 */
function bulk_pricer_compile_po_to_mo($po_file, $mo_file) {
    if (!file_exists($po_file)) {
        return false;
    }

    $lines = file($po_file);
    $entries = array();
    $current_msgid = '';
    $current_msgstr = '';
    $in_msgid = false;
    $in_msgstr = false;

    foreach ($lines as $line) {
        $line = trim($line);

        if (empty($line) || $line[0] === '#') {
            if ($current_msgid !== '' && $current_msgstr !== '') {
                $entries[$current_msgid] = $current_msgstr;
            }
            $current_msgid = '';
            $current_msgstr = '';
            $in_msgid = false;
            $in_msgstr = false;
            continue;
        }

        if (strpos($line, 'msgid ') === 0) {
            if ($current_msgid !== '' && $current_msgstr !== '') {
                $entries[$current_msgid] = $current_msgstr;
            }
            $current_msgid = bulk_pricer_parse_po_string(substr($line, 6));
            $current_msgstr = '';
            $in_msgid = true;
            $in_msgstr = false;
        } elseif (strpos($line, 'msgstr ') === 0) {
            $current_msgstr = bulk_pricer_parse_po_string(substr($line, 7));
            $in_msgid = false;
            $in_msgstr = true;
        } elseif ($line[0] === '"') {
            if ($in_msgid) {
                $current_msgid .= bulk_pricer_parse_po_string($line);
            } elseif ($in_msgstr) {
                $current_msgstr .= bulk_pricer_parse_po_string($line);
            }
        }
    }

    // Add last entry
    if ($current_msgid !== '' && $current_msgstr !== '') {
        $entries[$current_msgid] = $current_msgstr;
    }

    // Remove empty entry
    unset($entries['']);

    // Write MO file
    return bulk_pricer_write_mo_file($mo_file, $entries);
}

/**
 * Parse a PO string value
 */
function bulk_pricer_parse_po_string($str) {
    $str = trim($str);
    if ($str[0] === '"' && $str[strlen($str) - 1] === '"') {
        $str = substr($str, 1, -1);
    }
    return stripcslashes($str);
}

/**
 * Write MO file
 */
function bulk_pricer_write_mo_file($filename, $entries) {
    $keys = array_keys($entries);
    $values = array_values($entries);

    // MO file header
    $magic = 0x950412de;
    $revision = 0;
    $count = count($entries);

    // Calculate offsets
    $ids_offset = 28;
    $strs_offset = $ids_offset + ($count * 8);
    $keydata_offset = $strs_offset + ($count * 8);

    // Build key data
    $keydata = '';
    $valuedata = '';
    $keyoffsets = array();
    $valueoffsets = array();

    foreach ($keys as $key) {
        $keyoffsets[] = array(strlen($key), $keydata_offset + strlen($keydata));
        $keydata .= $key . "\0";
    }

    $valuedata_offset = $keydata_offset + strlen($keydata);

    foreach ($values as $value) {
        $valueoffsets[] = array(strlen($value), $valuedata_offset + strlen($valuedata));
        $valuedata .= $value . "\0";
    }

    // Write file
    $mo = pack('V', $magic);
    $mo .= pack('V', $revision);
    $mo .= pack('V', $count);
    $mo .= pack('V', $ids_offset);
    $mo .= pack('V', $strs_offset);
    $mo .= pack('V', 0); // hash table size
    $mo .= pack('V', 0); // hash table offset

    foreach ($keyoffsets as $offset) {
        $mo .= pack('V', $offset[0]);
        $mo .= pack('V', $offset[1]);
    }

    foreach ($valueoffsets as $offset) {
        $mo .= pack('V', $offset[0]);
        $mo .= pack('V', $offset[1]);
    }

    $mo .= $keydata;
    $mo .= $valuedata;

    return file_put_contents($filename, $mo) !== false;
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "========================================\n";
    echo "MO File Compiler\n";
    echo "========================================\n\n";

    $bulk_pricer_dir = __DIR__;
    $bulk_pricer_files = glob($bulk_pricer_dir . '/*.po');

    if (empty($bulk_pricer_files)) {
        echo "No .po files found in " . esc_html($bulk_pricer_dir) . "\n";
        exit(1);
    }

    $bulk_pricer_success = 0;
    $bulk_pricer_failed = 0;

    foreach ($bulk_pricer_files as $bulk_pricer_po_file) {
        $bulk_pricer_mo_file = preg_replace('/\.po$/', '.mo', $bulk_pricer_po_file);
        $bulk_pricer_filename = basename($bulk_pricer_po_file);

        echo "Compiling " . esc_html($bulk_pricer_filename) . "... ";

        if (bulk_pricer_compile_po_to_mo($bulk_pricer_po_file, $bulk_pricer_mo_file)) {
            echo "[OK]\n";
            $bulk_pricer_success++;
        } else {
            echo "[FAILED]\n";
            $bulk_pricer_failed++;
        }
    }

    echo "\n========================================\n";
    echo "Results: " . esc_html($bulk_pricer_success) . " succeeded, " . esc_html($bulk_pricer_failed) . " failed\n";
    echo "========================================\n";
}
