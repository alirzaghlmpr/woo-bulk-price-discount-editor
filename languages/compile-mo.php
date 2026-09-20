<?php
/**
 * Simple PHP script to compile .po files to .mo files
 *
 * This doesn't require any external tools, just PHP!
 * Supports the header entry (Plural-Forms etc.), plural forms and msgctxt.
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

    $entries = bulk_pricer_parse_po_file($po_file);

    return bulk_pricer_write_mo_file($mo_file, $entries);
}

/**
 * Parse a .po file into MO-style entries
 *
 * Keys follow the .mo convention: "context\x04msgid" for contexts and
 * "msgid\0msgid_plural" for plurals. Values hold the translation(s), the
 * plural forms joined by "\0". Untranslated entries are left out.
 *
 * @param string $po_file Path to .po file
 * @return array Key => translation
 */
function bulk_pricer_parse_po_file($po_file) {
    $lines = file($po_file, FILE_IGNORE_NEW_LINES);
    $entries = array();
    $entry = array();
    $field = null;

    // A trailing blank line makes sure the last entry is flushed.
    $lines[] = '';

    foreach ($lines as $line) {
        $line = trim($line);

        // Blank line: the current entry is complete.
        if ($line === '') {
            bulk_pricer_store_po_entry($entry, $entries);
            $entry = array();
            $field = null;
            continue;
        }

        // Comments (including obsolete "#~" entries) are ignored.
        if ($line[0] === '#') {
            continue;
        }

        if ($line[0] === '"') {
            if ($field !== null) {
                $entry[$field] .= bulk_pricer_parse_po_string($line);
            }
            continue;
        }

        if (strpos($line, 'msgctxt ') === 0) {
            $field = 'msgctxt';
            $entry[$field] = bulk_pricer_parse_po_string(substr($line, 8));
        } elseif (strpos($line, 'msgid_plural ') === 0) {
            $field = 'msgid_plural';
            $entry[$field] = bulk_pricer_parse_po_string(substr($line, 13));
        } elseif (strpos($line, 'msgid ') === 0) {
            // A new msgid straight after a finished entry (no blank line between).
            if (isset($entry['msgid']) && (isset($entry['msgstr']) || isset($entry['msgstr[0]']))) {
                bulk_pricer_store_po_entry($entry, $entries);
                $entry = array();
            }
            $field = 'msgid';
            $entry[$field] = bulk_pricer_parse_po_string(substr($line, 6));
        } elseif (preg_match('/^msgstr\[(\d+)\]\s+(.*)$/', $line, $match)) {
            $field = 'msgstr[' . $match[1] . ']';
            $entry[$field] = bulk_pricer_parse_po_string($match[2]);
        } elseif (strpos($line, 'msgstr ') === 0) {
            $field = 'msgstr';
            $entry[$field] = bulk_pricer_parse_po_string(substr($line, 7));
        }
    }

    // Sort like msgfmt does (byte order); the header entry has the empty key and comes first.
    ksort($entries, SORT_STRING);

    return $entries;
}

/**
 * Add one parsed entry to the list
 *
 * @param array $entry   Parsed fields
 * @param array $entries Entries so far (by reference)
 */
function bulk_pricer_store_po_entry($entry, &$entries) {
    if (!isset($entry['msgid'])) {
        return;
    }

    $key = $entry['msgid'];
    if (isset($entry['msgctxt'])) {
        $key = $entry['msgctxt'] . "\x04" . $key;
    }

    if (isset($entry['msgid_plural'])) {
        $forms = array();
        foreach ($entry as $name => $value) {
            if (preg_match('/^msgstr\[(\d+)\]$/', $name, $match)) {
                $forms[(int) $match[1]] = $value;
            }
        }
        ksort($forms);

        // Skip plural entries that aren't translated.
        if ($forms === array() || implode('', $forms) === '') {
            return;
        }

        $entries[$key . "\0" . $entry['msgid_plural']] = implode("\0", $forms);
        return;
    }

    // Untranslated strings fall back to the original text, so leave them out.
    if (!isset($entry['msgstr']) || $entry['msgstr'] === '') {
        return;
    }

    $entries[$key] = $entry['msgstr'];
}

/**
 * Parse a PO string value
 */
function bulk_pricer_parse_po_string($str) {
    $str = trim($str);
    if ($str !== '' && $str[0] === '"' && $str[strlen($str) - 1] === '"') {
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
        $key = (string) $key;
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

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- standalone CLI build script, WordPress isn't loaded.
    return file_put_contents($filename, $mo) !== false;
}

// Main execution (command line only). Plain-text CLI output: WordPress isn't loaded, so there is nothing to escape for.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
if (php_sapi_name() === 'cli') {
    echo "========================================\n";
    echo "MO File Compiler\n";
    echo "========================================\n\n";

    $bulk_pricer_dir = __DIR__;
    $bulk_pricer_files = glob($bulk_pricer_dir . '/*.po');

    if (empty($bulk_pricer_files)) {
        echo "No .po files found in " . $bulk_pricer_dir . "\n";
        exit(1);
    }

    $bulk_pricer_success = 0;
    $bulk_pricer_failed = 0;

    foreach ($bulk_pricer_files as $bulk_pricer_po_file) {
        $bulk_pricer_mo_file = preg_replace('/\.po$/', '.mo', $bulk_pricer_po_file);
        $bulk_pricer_filename = basename($bulk_pricer_po_file);

        echo "Compiling " . $bulk_pricer_filename . "... ";

        if (bulk_pricer_compile_po_to_mo($bulk_pricer_po_file, $bulk_pricer_mo_file)) {
            echo "[OK]\n";
            $bulk_pricer_success++;
        } else {
            echo "[FAILED]\n";
            $bulk_pricer_failed++;
        }
    }

    echo "\n========================================\n";
    echo "Results: " . $bulk_pricer_success . " succeeded, " . $bulk_pricer_failed . " failed\n";
    echo "========================================\n";
}
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
