<?php
/**
 * Database schema handler
 *
 * Creates the tables that store change history (runs) and the per-product
 * before/after values (items) used for undo, scheduling and CSV imports.
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_DB Class
 */
class Bulk_Pricer_DB
{
    /**
     * Schema version
     *
     * @var string
     */
    const VERSION = '1.0';

    /**
     * Option that stores the installed schema version
     *
     * @var string
     */
    const OPTION_KEY = 'bulk_pricer_db_version';

    /**
     * Runs table name
     *
     * @since 2.1.0
     * @return string
     */
    public static function runs_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'sbp_runs';
    }

    /**
     * Run items table name
     *
     * @since 2.1.0
     * @return string
     */
    public static function items_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'sbp_run_items';
    }

    /**
     * Create or update the tables
     *
     * @since 2.1.0
     */
    public static function install()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $runs    = self::runs_table();
        $items   = self::items_table();

        dbDelta("CREATE TABLE {$runs} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  run_type varchar(20) NOT NULL DEFAULT 'operation',
  status varchar(20) NOT NULL DEFAULT 'running',
  label varchar(255) NOT NULL DEFAULT '',
  params longtext NULL,
  total int(11) unsigned NOT NULL DEFAULT 0,
  changed int(11) unsigned NOT NULL DEFAULT 0,
  scheduled_at bigint(20) unsigned NULL DEFAULT NULL,
  revert_at bigint(20) unsigned NULL DEFAULT NULL,
  created_at bigint(20) unsigned NOT NULL DEFAULT 0,
  applied_at bigint(20) unsigned NULL DEFAULT NULL,
  reverted_at bigint(20) unsigned NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY created_at (created_at)
) {$charset};");

        dbDelta("CREATE TABLE {$items} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  run_id bigint(20) unsigned NOT NULL,
  product_id bigint(20) unsigned NOT NULL,
  parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
  status tinyint(3) unsigned NOT NULL DEFAULT 0,
  old_regular varchar(40) NULL DEFAULT NULL,
  old_sale varchar(40) NULL DEFAULT NULL,
  old_from bigint(20) NULL DEFAULT NULL,
  old_to bigint(20) NULL DEFAULT NULL,
  new_regular varchar(40) NULL DEFAULT NULL,
  new_sale varchar(40) NULL DEFAULT NULL,
  new_from bigint(20) NULL DEFAULT NULL,
  new_to bigint(20) NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY run_product (run_id,product_id),
  KEY run_status (run_id,status)
) {$charset};");

        update_option(self::OPTION_KEY, self::VERSION);
    }

    /**
     * Install/upgrade the schema when the stored version is outdated
     *
     * @since 2.1.0
     */
    public static function maybe_upgrade()
    {
        if (get_option(self::OPTION_KEY) !== self::VERSION) {
            self::install();
        }
    }

    /**
     * Drop the tables (used on uninstall)
     *
     * @since 2.1.0
     */
    public static function uninstall()
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query('DROP TABLE IF EXISTS ' . self::items_table());
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query('DROP TABLE IF EXISTS ' . self::runs_table());

        delete_option(self::OPTION_KEY);
    }
}
