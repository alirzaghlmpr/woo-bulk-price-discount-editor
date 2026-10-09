<div align="center">

<img src="assets/images/icon-256.png" alt="Bulk Price & Discount Editor logo" width="150" height="150">

# Bulk Price & Discount Editor for WooCommerce

**Preview, apply, schedule and undo price changes across your whole catalog — in a few clicks.**

[![Version](https://img.shields.io/badge/version-2.1.0-2563eb?style=for-the-badge)](#-changelog)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-6.0%E2%80%939.5-7f54b3?style=for-the-badge&logo=woocommerce&logoColor=white)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-16a34a?style=for-the-badge)](#-license)
[![HPOS](https://img.shields.io/badge/HPOS-compatible-16a34a?style=for-the-badge)](https://woocommerce.com/document/high-performance-order-storage/)

[Features](#-features) · [Screenshots](#-screenshots) · [Installation](#-installation) · [Usage](#-usage) · [CSV import](#-csv-import) · [Developers](#-for-developers) · [FAQ](#-faq)

</div>

---

## ✨ Why this plugin?

Changing prices one product at a time doesn't scale — and changing them blindly is risky. This plugin lets you **see exactly what will change before anything is touched**, applies it in safe batches that never time out, and records every run so you can **undo it later**.

| | |
|---|---|
| 🔍 **Preview first** | Before → after prices, discount %, and a whole-catalog impact summary |
| 🛡️ **Always reversible** | Every run is logged per product and can be reverted from the History tab |
| 🗓️ **Time-based campaigns** | Schedule a change for later and auto-restore the original prices afterwards |
| ⚡ **Built for big stores** | Batch processing with a live progress bar — no PHP timeouts |

---

## 🎯 Features

### 💰 Price operations

| Operation | What it does |
|---|---|
| ⬆️ Increase regular price | By a percentage or a fixed amount |
| ⬇️ Decrease regular price | By a percentage or a fixed amount |
| 🏷️ Apply / update sale price | Optional sale start and end dates |
| ❌ Remove all discounts | Clear sale prices in bulk |
| 🎯 Exact price | Set regular or sale price to one exact value |
| 🔼🔽 Sale price only | Raise or lower just the sale price |
| 📌 Make sale permanent | Sale price becomes the regular price |
| 🔢 Round prices only | Apply rounding without changing prices otherwise |

### 🛡️ Safety & automation

- **↩️ Undo & history** – old/new price per product, one-click revert, resume interrupted runs, CSV download of any run (kept 90 days)
- **🗓️ Scheduling & auto-restore** – run at a future date/time and put the original prices back automatically (uses WooCommerce's bundled Action Scheduler)
- **🔢 Price rounding** – nearest / up / down to a multiple (e.g. `1000`) or charm endings (`.99`, `99`, `900`)
- **⛔ Price limits** – never go below a floor or above a ceiling
- **🔄 Sale sync** – keep discount percentages constant when regular prices change
- **📊 Live summary** – products that change, increases vs. decreases and net price impact, recalculated as you exclude rows
- **🧩 Variable products** – variations are edited individually and the parent's price range is refreshed
- **🎛️ Smart targeting** – filter by category or "only products on sale", and exclude individual rows from the preview

### 📤 Import / export

- **CSV import** by SKU or product ID — with preview, confirmation and undo
- **CSV export** of the complete preview, not just the visible page

### 🎨 Experience

- Modern, responsive admin UI with a clean tab layout
- AJAX-powered — no page reloads
- Full **RTL** support and **English + Persian** translations included
- HPOS compatible · secure by design (nonces, capability checks, sanitized input)

---

## 📸 Screenshots

<table>
  <tr>
    <td align="center" width="50%">
      <img src="screenshots/main-interface.png" alt="Main interface"><br>
      <sub><b>Editor</b> — pick an operation, set the amount and options</sub>
    </td>
    <td align="center" width="50%">
      <img src="screenshots/preview.png" alt="Live preview"><br>
      <sub><b>Preview</b> — see every change before you commit</sub>
    </td>
  </tr>
</table>

---

## 🚀 Installation

**Requirements:** WordPress 5.8+ · WooCommerce 6.0+ (tested up to 9.5) · PHP 7.4+

1. Download or clone this repository
   ```bash
   git clone https://github.com/alirzaghlmpr/woo-bulk-price-discount-editor-for-woocommerce.git
   ```
2. Copy the folder to `/wp-content/plugins/` (or upload the ZIP via **Plugins → Add New → Upload Plugin**)
3. Activate **Bulk Price & Discount Editor for WooCommerce**
4. Open **Bulk Price Editor** in the admin menu

---

## 📖 Usage

### 1️⃣ Choose an operation
Pick what you want to do from the grouped dropdown — increase, decrease, set a sale, set exact prices, round, and more.

### 2️⃣ Set the amount
Fill **one** of *Percentage (%)* or *Fixed Amount*. If both are filled, nothing is changed.

### 3️⃣ Fine-tune
| Option | Effect |
|---|---|
| 🎯 **Product filter** | Process only products currently on sale |
| 🔄 **Sync sale price** | Keep the discount % constant when the regular price changes |
| 🗂️ **Category** | Limit the change to one category |
| 🔢 **Rounding** | Round to a multiple or force a price ending |
| ⛔ **Price limits** | Floor and ceiling the change can never cross |
| 🗓️ **Schedule / auto-restore** | Apply later, and optionally restore the original prices at a set time |

> 💡 **Sync example:** a product has a 20 % discount and you raise the regular price by 10 %. With sync **on** the sale price rises too and the discount stays 20 %; with sync **off** the sale price stays put and the discount shrinks.

### 4️⃣ Preview
Click **Preview & Review Changes** to see before → after prices, discount %, dates and the overall impact. Remove any row with **×** to exclude that product.

### 5️⃣ Apply (or schedule)
Click **Confirm and Apply**. Progress is shown live; if a run is ever interrupted, resume or revert it from **History**.

### ↩️ Undo
Open the **History** tab, find the run and press **Revert**. Each product returns to the prices it had before that run.

---

## 📤 CSV import

Upload a CSV from the **Import CSV** tab. Comma, semicolon and tab separated files are accepted.

| Column | Meaning |
|---|---|
| `sku` / `id` | **Required.** Identifies the product (for variations, use the variation's SKU/ID) |
| `regular_price` | New regular price — leave empty to keep the current one |
| `sale_price` | New sale price — `0` removes the sale, empty keeps it |
| `sale_start` / `sale_end` | Optional dates as `YYYY-MM-DD` (site timezone) |

```csv
sku,regular_price,sale_price
TSHIRT-BLUE,29.90,24.90
MUG-WHITE,12,0
```

You always get a preview and a confirmation step, and the import is recorded in History so it can be undone.

---

## 🧑‍💻 For developers

### Filters

| Filter | Default | Purpose |
|---|---|---|
| `bulk_pricer_capability` | `manage_options` | Capability required to use the plugin |
| `bulk_pricer_batch_size` | `20` | Products processed per apply/revert request |
| `bulk_pricer_summary_chunk` | `100` | Products per request when computing the summary |
| `bulk_pricer_history_retention_days` | `90` | How long finished runs are kept |
| `bulk_pricer_variable_types` | `variable`, `variable-subscription` | Product types treated as variable |

```php
// Let shop managers use the plugin
add_filter('bulk_pricer_capability', fn() => 'manage_woocommerce');

// Keep history for a year
add_filter('bulk_pricer_history_retention_days', fn() => 365);
```

### Project structure

```
├── admin/                  # Menu, assets and views (tabs, partials, components)
├── assets/
│   ├── css/                # Admin styles (design tokens, RTL-safe)
│   ├── images/             # Plugin icon & menu icon
│   └── js/                 # AJAX & UI interactions
├── includes/
│   ├── controllers/        # AJAX, preview, CSV and history handlers
│   ├── models/             # Operations, products, runs, calculators, formatters
│   ├── services/           # Run processing, CSV, scheduler (Action Scheduler)
│   └── utilities/          # Formatter, validator
├── languages/              # .pot / .po / .mo (en_US, fa_IR)
└── index.php               # Plugin bootstrap
```

### Notes on precision
Prices are calculated with whole-number-safe arithmetic to avoid floating-point drift on large amounts (e.g. Iranian Rial).

---

## 🌍 Translations

Included: 🇬🇧 English and 🇮🇷 Persian (فارسی), with full RTL support.

To add a language, copy `languages/bulk-price-discount-editor-for-woocommerce.pot` to `…-{locale}.po`, translate it, then run `cd languages && php compile-mo.php` (or `languages/compile-translations.bat` on Windows) to build the `.mo` file.

---

## ❓ FAQ

<details>
<summary><b>"WooCommerce is required" notice</b></summary>
Install and activate WooCommerce first, then activate this plugin.
</details>

<details>
<summary><b>Nothing changes when I apply</b></summary>
Make sure only <i>one</i> of Percentage / Fixed Amount is filled, that it is greater than 0, and that your category/filter actually matches products.
</details>

<details>
<summary><b>My scheduled change didn't run on time</b></summary>
Scheduling uses WooCommerce's Action Scheduler, which needs site visits or a real server cron to fire. Overdue runs are flagged in History, where you can press <b>Run now</b>.
</details>

<details>
<summary><b>Does it work with variable products?</b></summary>
Yes. Variations are edited individually and the parent product's price range is refreshed afterwards.
</details>

<details>
<summary><b>Can I undo a change?</b></summary>
Yes — every run is recorded. Revert it from the History tab within the retention period (90 days by default).
</details>

---

## 📜 Changelog

### 2.1.0
**✨ New**
- Undo & change history (per-product old/new prices, resume interrupted runs, CSV download of a run)
- Scheduled apply and auto-restore via Action Scheduler
- Price rounding (nearest/up/down multiples, charm endings) and price floor/ceiling
- New operations: exact regular/sale price, sale-only increase/decrease, make sale permanent, round prices only
- Whole-catalog preview summary, CSV export of the preview, CSV import by SKU/ID
- Progress bar while applying
- 🎨 Redesigned admin UI and new plugin icon

**🐛 Fixed**
- Variable products: parent price and cached variation prices refresh after variations change
- "Only on sale" now edits only the variations that are on sale
- Sale dates now apply only to operations that create a sale, use the site timezone, and the end date covers the whole day
- Sale sync now also covers sales scheduled for the future
- Preview pages hold exactly 20 rows and the total counts variations
- Much faster product collection on large catalogs
- Decimal amounts accepted in the fixed amount field

### 2.0.0
Complete rewrite: live preview, product images, sale dates, row exclusion, sale sync, batch processing, English & Persian translations, HPOS compatibility.

### 1.0.0
Initial release.

---

## 🤝 Contributing

Bug reports, ideas and pull requests are welcome.

1. Fork the repo and create a branch (`git checkout -b feature/my-feature`)
2. Follow the WordPress coding standards and test thoroughly
3. Open a pull request describing the change

When reporting a bug, please include your WordPress, WooCommerce and PHP versions, steps to reproduce, and screenshots if relevant.

---

## 📄 License

Released under the **GNU General Public License v2.0 or later** — see the [license text](https://www.gnu.org/licenses/gpl-2.0.html).

## 👨‍💻 Author

**Alireza Gholampour**
[GitHub](https://github.com/alirzaghlmpr) · [LinkedIn](https://www.linkedin.com/in/alireza-gholampour-6a0541211) · [webioo.ir](https://webioo.ir) · alirzaghlmpr@gmail.com

---

<div align="center">

**Made with ❤️ for the WordPress & WooCommerce community**

⭐ If this plugin saves you time, consider starring the repo!

</div>
