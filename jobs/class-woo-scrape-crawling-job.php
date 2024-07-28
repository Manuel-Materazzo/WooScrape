<?php

require_once ABSPATH . 'wp-content/plugins/woo-scrape/vendor/simple_html_dom.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-fishdeal-crawler-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-product-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-variation-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-job-log-service.php';

class Woo_scrape_crawling_job {
	private static Woo_scrape_fishdeal_crawler_service $crawler;
	private static Woo_scrape_product_service $product_service;
	private static Woo_scrape_variation_service $variation_service;
	private static Woo_Scrape_Job_Log_Service $log_service;
	private static string $date_format = 'Y-m-d H:i:s';

	public function __construct() {
		self::$crawler           = new Woo_scrape_fishdeal_crawler_service();
		self::$product_service   = new Woo_scrape_product_service();
		self::$variation_service = new Woo_scrape_variation_service();
		self::$log_service       = new Woo_Scrape_Job_Log_Service();
	}

	public function run(): void {
		// crawl categories to get partial products
		self::$log_service->job_start( JobType::Categories_crawl );
		$this->fetch_categories_for_profitable_products();
		self::$log_service->job_end( JobType::Categories_crawl );

		$this->run_products();

	}

	public function run_products(): void {
		// crawl each partial product to complete informations
		self::$log_service->job_start( JobType::Products_crawl );
		$this->fetch_unfetched_products();
		$this->fetch_profitable_products();
		self::$log_service->job_end( JobType::Products_crawl );
	}

	/**
	 * Gets categories from DB and fetches them all to extract, update and save today's profitable products
	 * @return void
	 */
	private function fetch_categories_for_profitable_products(): void {
		global $wpdb;

		// gets categories from DB
		$categories_table_name = $wpdb->prefix . 'woo_scrape_pages';
		$categories            = $wpdb->get_results( "SELECT id, url FROM $categories_table_name" );

		// on each category
		foreach ( $categories as $category ) {
			try {


				// crawl all products
				$partial_profitable_products = self::$crawler->crawl_category( $category->url );

				// deduplicate products
				$partial_profitable_products = array_reduce($partial_profitable_products, function ($carry, $product) {
					$carry[$product->getUrl()] = $product;
					return $carry;
				}, []);

				// reindex deduplicated array
				$partial_profitable_products = array_values($partial_profitable_products);

				error_log( "The category " . $category->id . " has crawled " . count( $partial_profitable_products ) . " items" );

				// update existing products, and return "new" products
				$partial_profitable_products = self::$product_service->update_all_by_url( $partial_profitable_products );

				error_log( "There are " . count( $partial_profitable_products ) . " new items to save" );
				self::$log_service->increase_completed_counter( JobType::Categories_crawl );
			} catch ( Exception $e ) {
				error_log( $e );
				self::$log_service->increase_failed_counter( JobType::Categories_crawl );
			}

			// save the new products
			self::$product_service->create_all( $category->id, $partial_profitable_products );
			// free up memory
			unset($partial_profitable_products);
		}

		$wpdb->flush();
	}

	/**
	 * Gets today's profitable products, and updates detailed metadata of each product
	 * @return void
	 */
	private function fetch_profitable_products(): void {
		$page = 0;
		$now  = date( self::$date_format );

		// gets profitable products crawled today. Does not crawl products that have has_variants = false
		// (already crawled once, and found no variants. using the categpry price is fine)
		while ( true ) {
			// get product page
			$updated_products = self::$product_service->get_updated_products_with_variations_paged( $page );

			error_log( "Fetched " . count( $updated_products ) . " products to crawl and update." );

			// if there are no product left to crawl, stop the cycle
			if ( empty( $updated_products ) ) {
				error_log( "There are no more updated products" );
				break;
			}

			// crawl each product to get the complete informations
			$this->crawl_products_to_get_informations( $updated_products, $now );

			// free up memory
			unset($updated_products);

			$page += 1;
		}
	}

	private function fetch_unfetched_products(): void {
		$now  = date( self::$date_format );

		// gets profitable products crawled today. Does not crawl products that have has_variants = false
		// (already crawled once, and found no variants. using the categpry price is fine)
		while ( true ) {
			// get product page
			$updated_products = self::$product_service->get_unfetched_products_with_variations_paged();

			error_log( "Fetched " . count( $updated_products ) . " products to crawl and update." );

			// if there are no product left to crawl, stop the cycle
			if ( empty( $updated_products ) ) {
				error_log( "There are no more updated products" );
				break;
			}

			// crawl each product to get the complete informations
			$this->crawl_products_to_get_informations( $updated_products, $now );

			// free up memory
			unset($updated_products);
		}
	}

	/**
	 * @param array|object $updated_products
	 * @param string $now
	 *
	 * @return void
	 */
	public function crawl_products_to_get_informations( array|object $updated_products, string $now ): void {
		foreach ( $updated_products as $partial_product ) {
			try {
				// calculate price multiplier to get suggested price from discounted
				$suggested_price_multiplier = new WooScrapeDecimal( $partial_product->suggested_price );
				$suggested_price_multiplier = $suggested_price_multiplier->divide( $partial_product->discounted_price );

				$complete_product = self::$crawler->crawl_product( $partial_product->url, $suggested_price_multiplier );
				$complete_product->setId( $partial_product->id );

				error_log( "Crawled " . $partial_product->url );

				// if the product has no crawled images, or the images changed
				if ( ! $partial_product->image_ids ||
				     json_encode( $complete_product->getImageUrls() ) !== $partial_product->image_urls ) {
					$this->crawl_images( $partial_product, $complete_product );
				}

				// update the product on DB
				self::$product_service->update_by_id( $complete_product, true, $now );

				if ( $complete_product->hasvariations() ) {
					error_log( "The item " . $partial_product->url . "has " . count( $complete_product->getVariations() ) . " variations!" );
					// update variations on DB
					$new_variations = self::$variation_service->update_all_by_product_id_and_name( $partial_product->id, $complete_product->getVariations(), $now );
					error_log( "The item " . $partial_product->url . "has " . count( $new_variations ) . " new variations!" );
					// create new variations on db
					self::$variation_service->create_all( $partial_product->id, $new_variations, $now );
					// free up memory
					unset($new_variations);
				}

				// free up memory
				unset($complete_product);

				self::$log_service->increase_completed_counter( JobType::Products_crawl );
			} catch ( Exception $e ) {
				error_log( $e );
				self::$log_service->increase_failed_counter( JobType::Products_crawl );
			}
		}
	}

	/**
	 * @param mixed $partial_product
	 * @param WooScrapeProduct $complete_product
	 *
	 * @return void
	 */
	public function crawl_images( mixed $partial_product, WooScrapeProduct $complete_product ): void {
		error_log( "Found new images for " . $partial_product->url . " : " . json_encode( $complete_product->getImageUrls() ) );
		// crawl images and save them
		$image_ids = self::$crawler->crawl_images( $complete_product->getImageUrls() );
		// add ids to the product
		$complete_product->setImageIds( $image_ids );
		error_log( "Crawled " . count( $image_ids ) . " images for " . $partial_product->url );
		self::$log_service->job_start( JobType::Images_crawl, $partial_product->url );
		self::$log_service->increase_completed_counter( JobType::Images_crawl, count( $image_ids ) );
		self::$log_service->job_end( JobType::Images_crawl );
	}


}
