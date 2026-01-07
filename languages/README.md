# Translation Files for Bulk Price & Discount Editor

This directory contains translation files for the plugin in multiple languages.

## Files Structure

- `bulk-price-discount-editor-for-woocommerce.pot` - Translation template (POT file)
- `bulk-price-discount-editor-for-woocommerce-fa_IR.po` - Persian translation source
- `bulk-price-discount-editor-for-woocommerce-en_US.po` - English translation source

## How to Compile Translations

WordPress uses `.mo` files (compiled binary) for translations. You need to compile the `.po` files to `.mo` files.

### Method 1: Using Poedit (Recommended - Easy)

1. Download and install [Poedit](https://poedit.net/) (Free version works fine)
2. Open the `.po` file you want to compile (e.g., `bulk-price-discount-editor-for-woocommerce-fa_IR.po`)
3. Click **File → Save** or press `Ctrl+S`
4. Poedit will automatically generate the `.mo` file in the same directory

### Method 2: Using msgfmt (Command Line)

If you have `gettext` tools installed:

```bash
# For Persian
msgfmt -o bulk-price-discount-editor-for-woocommerce-fa_IR.mo bulk-price-discount-editor-for-woocommerce-fa_IR.po

# For English
msgfmt -o bulk-price-discount-editor-for-woocommerce-en_US.mo bulk-price-discount-editor-for-woocommerce-en_US.po
```

### Method 3: Using WP-CLI

If you have WP-CLI installed:

```bash
wp i18n make-mo languages/
```

This will compile all `.po` files in the languages directory.

## How WordPress Selects the Language

WordPress will automatically load the correct translation based on your site's language setting:

1. Go to **WordPress Admin → Settings → General**
2. Set **Site Language** to:
   - `فارسی` (Persian) - Will load `bulk-price-discount-editor-for-woocommerce-fa_IR.mo`
   - `English (United States)` - Will load `bulk-price-discount-editor-for-woocommerce-en_US.mo`

## Adding a New Language

1. Copy the `.pot` file
2. Rename it to `bulk-price-discount-editor-for-woocommerce-{locale}.po` (e.g., `bulk-price-discount-editor-for-woocommerce-fr_FR.po` for French)
3. Open it with Poedit
4. Translate all strings
5. Save (this will generate the `.mo` file automatically)
6. Upload both `.po` and `.mo` files to this directory

## Common Language Codes

- `fa_IR` - Persian (فارسی)
- `en_US` - English (United States)
- `ar` - Arabic (العربية)
- `fr_FR` - French (Français)
- `de_DE` - German (Deutsch)
- `es_ES` - Spanish (Español)
- `tr_TR` - Turkish (Türkçe)

## Testing Translations

1. Make sure both `.po` and `.mo` files are in the `/languages/` directory
2. Go to **WordPress Admin → Settings → General**
3. Change **Site Language** to the language you want to test
4. Clear your browser cache
5. Reload the plugin page

## Troubleshooting

### Translation not showing?

1. **Check the `.mo` file exists** - WordPress uses `.mo`, not `.po`
2. **Check the filename** - Must match pattern: `bulk-price-discount-editor-for-woocommerce-{locale}.mo`
3. **Clear cache** - Clear browser cache and any WordPress caching plugins
4. **Check WordPress language** - Make sure WordPress is set to the correct language
5. **File permissions** - Make sure files are readable by the web server

### How to verify .mo file is loaded?

Add this to your theme's `functions.php` temporarily:

```php
add_action('admin_footer', function() {
    global $l10n;
    if (isset($l10n['bulk-price-discount-editor-for-woocommerce'])) {
        echo '<!-- Translation loaded! -->';
    } else {
        echo '<!-- Translation NOT loaded -->';
    }
});
```

Then view page source in your browser and look for the comment.

## Need Help?

If you need help with translations, please open an issue on the plugin's support page.
