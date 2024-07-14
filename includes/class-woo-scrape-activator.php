<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/Manuel-Materazzo
 * @since      1.0.0
 *
 * @package    Woo_Scrape
 * @subpackage Woo_Scrape/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Woo_Scrape
 * @subpackage Woo_Scrape/includes
 * @author     Manuel <madonnagamer@gmail.com>
 */
class Woo_Scrape_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate(): void {
		self::create_database_tables();
	}

	private static function create_database_tables(): void
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

        $job_logs_table_name = $wpdb->prefix . 'woo_scrape_job_logs';
        $job_logs_table_sql = "CREATE TABLE IF NOT EXISTS $job_logs_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        categories_crawled mediumint(9) UNSIGNED,
        categories_crawl_fails mediumint(9) UNSIGNED,
        products_crawled mediumint(9) UNSIGNED,
        products_crawl_fails mediumint(9) UNSIGNED,
        image_crawls mediumint(9) UNSIGNED,
        woo_out_of_stock_products mediumint(9) UNSIGNED,
        woo_out_of_stock_products_fails mediumint(9) UNSIGNED,
        woo_updated_products mediumint(9) UNSIGNED,
        woo_updated_products_fails mediumint(9) UNSIGNED,
        woo_created_products mediumint(9) UNSIGNED,
        woo_created_products_fails mediumint(9) UNSIGNED,
        job_start_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        categories_crawl_end_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        products_crawl_end_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        woo_out_of_stock_end_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        woo_update_end_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        job_end_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
        ) $charset_collate;";

		$pages_list_table_name = $wpdb->prefix . 'woo_scrape_pages';
		$pages_list_table_sql = "CREATE TABLE IF NOT EXISTS $pages_list_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        provider tinytext NOT NULL,
        url varchar(250) DEFAULT '' NOT NULL,
        weight decimal(4,2) NOT NULL,
        length TINYINT UNSIGNED NOT NULL,
        width TINYINT UNSIGNED NOT NULL,
        height TINYINT UNSIGNED NOT NULL,
        PRIMARY KEY (id)
        ) $charset_collate;";

		$products_table_name = $wpdb->prefix . 'woo_scrape_products';
		$products_table_sql = "CREATE TABLE IF NOT EXISTS $products_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        translated_name text DEFAULT NULL,
        specifications text NOT NULL,
        translated_specifications text DEFAULT NULL,
        description text NOT NULL,
        translated_description text DEFAULT NULL,
        brand tinytext NOT NULL,
        has_variations boolean DEFAULT NULL,
        url varchar(250) DEFAULT '' NOT NULL,
        image_urls text DEFAULT '' NOT NULL,
        image_ids text DEFAULT NULL,
        category_id mediumint(9) UNSIGNED DEFAULT NULL,
        quantity mediumint(9) UNSIGNED DEFAULT NULL,
        suggested_price decimal(7,2) UNSIGNED NOT NULL,
        discounted_price decimal(7,2) UNSIGNED NOT NULL,
        first_crawl_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        latest_crawl_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        item_updated_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        CONSTRAINT fk_woo_scrape_pages_id FOREIGN KEY (category_id) REFERENCES $pages_list_table_name(id),
        PRIMARY KEY (id)
        ) $charset_collate;";

		$variations_table_name = $wpdb->prefix . 'woo_scrape_variations';
		$variants_table_sql = "CREATE TABLE IF NOT EXISTS $variations_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        product_id mediumint(9) UNSIGNED DEFAULT NULL,
        quantity mediumint(9) UNSIGNED DEFAULT NULL,
        suggested_price decimal(7,2) UNSIGNED NOT NULL,
        discounted_price decimal(7,2) UNSIGNED NOT NULL,
        first_crawl_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        latest_crawl_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        item_updated_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        CONSTRAINT fk_woo_scrape_variants_id FOREIGN KEY (product_id) REFERENCES $products_table_name(id),
        PRIMARY KEY (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($pages_list_table_sql);
		dbDelta($products_table_sql);
		dbDelta($variants_table_sql);
		dbDelta($job_logs_table_sql);
	}

}
