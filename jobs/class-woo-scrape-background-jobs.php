<?php

/**
 * Background job handlers for WP Cron execution.
 *
 * Each public static method serves as a cron callback, with transient-based
 * locking to prevent concurrent runs of the same job.
 *
 * @package    Woo_Scrape
 * @subpackage Woo_Scrape/jobs
 */
class Woo_Scrape_Background_Jobs {

	/**
	 * Attempt to acquire a transient lock.
	 *
	 * @param string $key Lock transient key.
	 * @param int    $ttl Lock lifetime in seconds (default 6 hours).
	 *
	 * @return bool True if lock was acquired, false if already held.
	 */
	private static function acquire_lock( string $key, int $ttl = 21600 ): bool {
		if ( get_transient( $key ) ) {
			return false;
		}
		set_transient( $key, 1, $ttl );
		return true;
	}

	/**
	 * Release a transient lock.
	 *
	 * @param string $key Lock transient key.
	 *
	 * @return void
	 */
	private static function release_lock( string $key ): void {
		delete_transient( $key );
	}

	/**
	 * Run the orchestrator job in the background.
	 *
	 * @return void
	 */
	public static function run_orchestrator(): void {
		$lock = 'woo_scrape_lock_orchestrator';
		if ( ! self::acquire_lock( $lock ) ) {
			error_log( 'Orchestrator job skipped: already running.' );
			return;
		}
		try {
			require_once plugin_dir_path( __FILE__ ) . 'class-woo-scrape-orchestrator.php';
			Woo_scrape_orchestrator::orchestrate_main_job();
		} finally {
			self::release_lock( $lock );
		}
	}

	/**
	 * Run the crawling job in the background.
	 *
	 * @return void
	 */
	public static function run_crawling(): void {
		$lock = 'woo_scrape_lock_crawling';
		if ( ! self::acquire_lock( $lock ) ) {
			error_log( 'Crawling job skipped: already running.' );
			return;
		}
		try {
			require_once plugin_dir_path( __FILE__ ) . 'class-woo-scrape-crawling-job.php';
			$job = new Woo_scrape_crawling_job();
			$job->run();
		} finally {
			self::release_lock( $lock );
		}
	}

	/**
	 * Run the product crawling job in the background.
	 *
	 * @return void
	 */
	public static function run_product_crawling(): void {
		$lock = 'woo_scrape_lock_product_crawling';
		if ( ! self::acquire_lock( $lock ) ) {
			error_log( 'Product crawling job skipped: already running.' );
			return;
		}
		try {
			require_once plugin_dir_path( __FILE__ ) . 'class-woo-scrape-crawling-job.php';
			$job = new Woo_scrape_crawling_job();
			$job->run_products();
		} finally {
			self::release_lock( $lock );
		}
	}

	/**
	 * Run the translation job in the background.
	 *
	 * @return void
	 */
	public static function run_translate(): void {
		$lock = 'woo_scrape_lock_translate';
		if ( ! self::acquire_lock( $lock ) ) {
			error_log( 'Translation job skipped: already running.' );
			return;
		}
		try {
			require_once plugin_dir_path( __FILE__ ) . 'class-woo-scrape-translation-job.php';
			$job = new Woo_Scrape_Translation_Job();
			$job->run( true );
		} finally {
			self::release_lock( $lock );
		}
	}

	/**
	 * Run the WordPress/WooCommerce update job in the background.
	 *
	 * @return void
	 */
	public static function run_wordpress_update(): void {
		$lock = 'woo_scrape_lock_wordpress';
		if ( ! self::acquire_lock( $lock ) ) {
			error_log( 'WordPress update job skipped: already running.' );
			return;
		}
		try {
			require_once plugin_dir_path( __FILE__ ) . 'class-woo-scrape-woocommerce-update-job.php';
			$job = new Woo_scrape_woocommerce_update_job();
			$job->run();
		} finally {
			self::release_lock( $lock );
		}
	}

	/**
	 * Run a single product crawl and update in the background.
	 *
	 * @param string $sku The product SKU to process.
	 *
	 * @return void
	 */
	public static function run_single_product( string $sku ): void {
		require_once plugin_dir_path( __FILE__ ) . 'class-woo-scrape-crawling-job.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-woo-scrape-woocommerce-update-job.php';

		$crawl = new Woo_scrape_crawling_job();
		$crawl->run_single( $sku );

		$update = new Woo_scrape_woocommerce_update_job();
		$update->run_single( $sku );
	}
}

add_action( 'woo_scrape_crawling_job_hook', array( 'Woo_Scrape_Background_Jobs', 'run_crawling' ) );
add_action( 'woo_scrape_product_crawling_job_hook', array( 'Woo_Scrape_Background_Jobs', 'run_product_crawling' ) );
add_action( 'woo_scrape_translate_job_hook', array( 'Woo_Scrape_Background_Jobs', 'run_translate' ) );
add_action( 'woo_scrape_wordpress_job_hook', array( 'Woo_Scrape_Background_Jobs', 'run_wordpress_update' ) );
add_action( 'woo_scrape_single_product_job_hook', array( 'Woo_Scrape_Background_Jobs', 'run_single_product' ) );
