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

	const DB_VERSION = '1.0.0';

	/**
	 * Runs on plugin activation: creates database tables and stores DB version.
	 *
	 * @since    1.0.0
	 */
	public static function activate(): void {
		self::create_database_tables();
		update_option( 'woo_scrape_db_version', self::DB_VERSION );
	}

	/**
	 * Checks if the database schema needs updating and runs migrations if needed.
	 *
	 * @since    1.0.0
	 */
	public static function check_db_version(): void {
		$installed_version = get_option( 'woo_scrape_db_version', '0' );
		if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
			self::create_database_tables();
			update_option( 'woo_scrape_db_version', self::DB_VERSION );
		}
	}

	private static function create_database_tables(): void
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

        $job_logs_table_name = $wpdb->prefix . 'woo_scrape_job_logs';
        $job_logs_table_sql = "CREATE TABLE $job_logs_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        type tinytext NOT NULL,
        name tinytext NOT NULL,
        completed_counter mediumint(9) UNSIGNED,
        failed_counter mediumint(9) UNSIGNED,
        job_start_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        job_end_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
        ) $charset_collate;";

		$pages_list_table_name = $wpdb->prefix . 'woo_scrape_pages';
		$pages_list_table_sql = "CREATE TABLE $pages_list_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        provider tinytext NOT NULL,
        url varchar(250) DEFAULT '' NOT NULL,
        corresponding_woocommerce_category_id mediumint(9) UNSIGNED NOT NULL,
        weight decimal(7,2) NOT NULL,
        length SMALLINT UNSIGNED NOT NULL,
        width SMALLINT UNSIGNED NOT NULL,
        height SMALLINT UNSIGNED NOT NULL,
        PRIMARY KEY (id)
        ) $charset_collate;";

		$products_table_name = $wpdb->prefix . 'woo_scrape_products';
		$products_table_sql = "CREATE TABLE $products_table_name (
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
		$variants_table_sql = "CREATE TABLE $variations_table_name (
        id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        translated_name text DEFAULT NULL,
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
