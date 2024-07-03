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
	public static function activate() {
		self::create_database_tables();
	}

	private static function create_database_tables()
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$pages_list_table_name = $wpdb->prefix . 'woo_scrape_pages';
		$pages_list_table_sql = "CREATE TABLE $pages_list_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        url varchar(250) DEFAULT '' NOT NULL,
        weight decimal(4,2) NOT NULL,
        length TINYINT UNSIGNED NOT NULL,
        width TINYINT UNSIGNED NOT NULL,
        height TINYINT UNSIGNED NOT NULL,
        PRIMARY KEY (id)
        ) $charset_collate;";

		$products_table_name = $wpdb->prefix . 'woo_scrape_products';
		$products_table_sql = "CREATE TABLE $products_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        description text NOT NULL,
        brand tinytext NOT NULL,
        has_variations boolean DEFAULT NULL,
        url varchar(250) DEFAULT '' NOT NULL,
        image_urls text DEFAULT '' NOT NULL,
        image_ids text DEFAULT NULL,
        category_id mediumint(9) UNSIGNED DEFAULT NULL,
        suggested_price decimal(7,2) UNSIGNED NOT NULL,
        discounted_price decimal(7,2) UNSIGNED NOT NULL,
        first_crawl_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        latest_crawl_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        item_updated_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        CONSTRAINT fk_woocommerce_scraper_pages_id FOREIGN KEY (category_id) REFERENCES $pages_list_table_name(id),
        PRIMARY KEY (id)
        ) $charset_collate;";

		$variants_table_name = $wpdb->prefix . 'woo_scrape_variants';
		$variants_table_sql = "CREATE TABLE $variants_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        image_urls text DEFAULT '' NOT NULL,
        image_ids text DEFAULT NULL,
        product_id mediumint(9) UNSIGNED DEFAULT NULL,
        suggested_price decimal(7,2) UNSIGNED NOT NULL,
        discounted_price decimal(7,2) UNSIGNED NOT NULL,
        first_crawl_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        latest_crawl_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        item_updated_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        CONSTRAINT fk_woocommerce_scraper_variants_id FOREIGN KEY (product_id) REFERENCES $products_table_name(id),
        PRIMARY KEY (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($pages_list_table_sql);
		dbDelta($products_table_sql);
		dbDelta($variants_table_sql);
	}

}
