=== Bulk Price & Discount Editor for WooCommerce ===
Contributors: alirzaghlmpr
Tags: woocommerce, bulk edit, pricing, discount, sale price, products
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional bulk price and discount management tool for WooCommerce.

== Description ==

Bulk Price & Discount Editor for WooCommerce lets you update regular prices and sale prices across large catalogs in minutes. Use percentage or fixed-amount changes, preview all changes before applying, and keep discount percentages in sync when regular prices move.

Features:

* Bulk increase/decrease regular prices (percent or fixed).
* Apply/update sale prices with optional start/end dates.
* Live preview with before/after comparison.
* Filter by category or sale status.
* Optional sale-price sync to maintain discount percentages.
* Set exact prices, change only the sale price, or make a sale permanent.
* Round prices (multiples or endings like .99) and set minimum/maximum price limits.
* Undo any run from the History tab.
* Schedule changes for later and automatically restore the original prices.
* Preview summary for the whole catalog, CSV export of the preview and CSV import by SKU.
* Variable products supported: variations are edited and the parent price range is refreshed.
* HPOS compatible.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to "Bulk Price Editor" in the admin menu.

== Frequently Asked Questions ==

= Does this require WooCommerce? =
Yes. WooCommerce must be installed and activated.

= Can I preview changes before applying? =
Yes, the plugin provides a live preview with before/after values.

= Can I undo a change? =
Yes. Every run is stored in the History tab with the old and new price of each product and can be reverted. Finished runs are kept for 90 days.

= Do scheduled changes need anything special? =
They use WooCommerce's bundled Action Scheduler, which runs from WP-Cron. On low-traffic sites use a real server cron so it fires on time. Overdue runs can be started manually from the History tab.

= Does it work with variable products? =
Yes. Each variation is changed individually and the parent product's price range is refreshed automatically.

== Changelog ==

= 2.1.0 =
* New: undo and change history, scheduled apply with auto-restore, price rounding and limits, new operations, preview summary, CSV export and CSV import.
* Fix: variable product parent prices are refreshed after variations change.
* Fix: "only on sale" no longer edits non-sale variations; sale dates use the site timezone and only apply to operations that create a sale; sale sync covers scheduled sales; faster product collection; accurate preview pagination.

= 2.0.0 =
* Major rewrite with live preview, sale date support, and improved performance.

== Upgrade Notice ==

= 2.1.0 =
Adds undo/history, scheduling, rounding, CSV import/export and more, plus several fixes. Creates two database tables on first admin visit.

= 2.0.0 =
Major release with new UI, live preview, and batch processing improvements.
