<?php

require ABSPATH . 'wp-content/plugins/woo-scrape/vendor/simple_html_dom.php';
require ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-fishdeal-crawler-service.php';

class Woo_scrape_category_crawling_job {
	private static Woo_scrape_fishdeal_crawler_service $crawler;
	private static string $date_format = 'Y-m-d H:i:s';

	function __construct() {
		self::$crawler = new Woo_scrape_fishdeal_crawler_service();
	}

	public function run() {
		// crawl categories to get partial products
		$this->fetch_categories_for_profitable_products();

		// crawl each partial product to complete informations
		$this->fetch_profitable_products();


			// save the new products
			$this->save_products( $profitable_products );
		}



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
			// crawl all products
			$partial_profitable_products = self::$crawler->crawl_category( $category->url, $category->id );

			error_log( "The category " . $category->id . " has crawled " . count( $partial_profitable_products ) . " items" );

			// update existing products, and return "new" products
			$partial_profitable_products = $this->update_partial_products( $partial_profitable_products );

			error_log( "There are " . count( $partial_profitable_products ) . " new items to save" );

			// save the new products
			$this->save_partial_products( $partial_profitable_products );
		}
	}

	/**
	 * Gets today's profitable products, and updates detailed metadata of each product
	 * @return void
	 */
	private function fetch_profitable_products(): void {
		global $wpdb;

		$page                = 0;
		$products_table_name = $wpdb->prefix . 'woo_scrape_products';
		$now                 = date( self::$date_format );

		// gets profitable products crawled today. Does not crawl products that have has_variants = false
		// (already crawled once, and found no variants. using the categpry price is fine)
		while ( true ) {
			// get product page
			$start            = $page * 30;
			$updated_products = $wpdb->get_results(
				"SELECT id, url, image_urls, image_ids FROM $products_table_name
                WHERE DATE(`latest_crawl_timestamp`) = CURDATE()
                and latest_crawl_timestamp has_variations is not false
                LIMIT $start,30"
			);

			error_log( "Fetched " . count( $updated_products ) . " products to crawl and update." );

			// if there are no product left to crawl, stop the cycle
			if ( empty( $updated_products ) ) {
				error_log( "There are no more updated products" );
				break;
			}

			// crawl each product to get the complete informations
			foreach ( $updated_products as $partial_product ) {
				$complete_product = self::$crawler->crawl_product( $partial_product->url );

				error_log( "Crawled " . $partial_product->url );

				// if the product has no crawled images, or the images changed
				if ( ! $partial_product->image_ids ||
				     json_encode( $complete_product->getImageUrls() ) !== $partial_product->image_urls ) {
					error_log( "Found new images for " . $partial_product->url . " : " . json_encode( $complete_product->getImageUrls() ) );
					// crawl images and save them
					$image_ids = self::$crawler->crawl_images( $complete_product->getImageUrls() );
					// add ids to the product
					$complete_product->setImageIds( $image_ids );
					error_log( "Crawled " . count($image_ids) . " images for " . $partial_product->url );
				}

				// update the product on DB
				$this->update_complete_product( $now, $partial_product->id, $complete_product );

				if ( $complete_product->hasvariations() ) {
					error_log( "The item " . $partial_product->url . "has " . count($complete_product->getVariations()) . " variations!");
					// update variations on DB
					$new_variations = $this->update_variations( $now, $partial_product->id, $complete_product->getVariations() );
					error_log( "The item " . $partial_product->url . "has " . count($new_variations) . " new variations!");
					// create new variations on db
					$this->save_variations( $now, $partial_product->id, $new_variations );
				}

			}
			$page += 1;
		}
	}

	/**
	 * Updates on DB partial products to enrich and complete their metadata.
	 *
	 * @param string $now the product update timestamp
	 * @param int $product_id the DB product id
	 * @param WooScrapeProduct $complete_product the complete product object
	 *
	 * @return void
	 */
	private function update_complete_product( string $now, int $product_id, WooScrapeProduct $complete_product ): void {
		global $wpdb;

		// update products with the additional details
		$rows_updated = $wpdb->update(
			$wpdb->prefix . 'woo_scrape_products',
			array(
				'item_updated_timestamp' => $now,
				'image_urls'             => json_encode( $complete_product->getImageUrls() ),
				'image_ids'              => json_encode( $complete_product->getImageIds() ),
				'description'            => $complete_product->getDescription()
			),
			array( 'id' => $product_id )
		);

	}

	/**
	 * Updates on DB the variations of the $variations array, and returns the ones not found on DB.
	 *
	 * @param string $now the variation update timestamp
	 * @param int $product_id the id of the variation's base product
	 * @param array $variations array of product variations
	 *
	 * @return array array of variations not updated
	 */
	private function update_variations( string $now, int $product_id, array $variations ): array {
		global $wpdb;

		foreach ( $variations as $key => $variation ) {
			// update variation already on table
			$rows_updated = $wpdb->update(
				$wpdb->prefix . 'woo_scrape_products',
				array(

//					'quantity'                   => $variation->getName(),
					'latest_crawl_timestamp' => $now,
					'item_updated_timestamp' => $now,
//					'suggested_price'        => strval( $variation->getSuggestedPrice() ),
					'discounted_price'       => strval( $variation->getDiscountedPrice() ),
				),
				array(
					'product_id' => $product_id,
					'name'       => $variation->getName(),
				)
			);

			// if the the product got updated, remove it from the array
			if ( $rows_updated >= 1 ) {
				unset( $variations[ $key ] );
			}
			// there shouldn't be more than one row updated
			if ( $rows_updated > 1 ) {
				error_log(
					"There is more than one variation on database for the product" . $product_id . "  with name "
					. $variation->getName()
				);
			}
		}

		// returns variations that didn't update
		return $variations;
	}

	/**
	 * Creates on DB the variations of the $variations array.
	 *
	 * @param string $now the variation update timestamp
	 * @param int $product_id the id of the variation's base product
	 * @param array $variations array of product variations
	 *
	 */
	private function save_variations( string $now, int $product_id, array $variations ): void {
		global $wpdb;

		foreach ( $variations as $variation ) {
			// create the variation on db
			$wpdb->insert(
				$wpdb->prefix . 'woo_scrape_products',
				array(
					'name'                   => $variation->getName(),
					'product_id'             => $product_id,
					'first_crawl_timestamp'  => $now,
					'latest_crawl_timestamp' => $now,
					'item_updated_timestamp' => $now,
//					'suggested_price'        => strval( $profitable_product->getSuggestedPrice() ),
					'discounted_price'       => strval( $variation->getDiscountedPrice() ),
				)
			);
		}
	}

	/**
	 * Updates on DB the products of the $profitable_products array, and returns the ones not found on DB.
	 * Products without variations (with has_variation=false, uncrawled products have has_variation=null) are directly
	 * set as updated.
	 *
	 * @param array $partial_products array of products to update
	 *
	 * @return array array of products not updated
	 */
	private function update_partial_products( array $partial_products ): array {
		global $wpdb;

		$now = date( self::$date_format );

		foreach ( $partial_products as $key => $partial_product ) {
			// update products already on the table
			$rows_updated = $wpdb->update(
				$wpdb->prefix . 'woo_scrape_products',
				array(
					'name'                   => $partial_product->getName(),
					'latest_crawl_timestamp' => $now,
					'brand'                  => $partial_product->getBrand(),
					'suggested_price'        => strval( $partial_product->getSuggestedPrice() ),
					'discounted_price'       => strval( $partial_product->getDiscountedPrice() ),
				),
				array( 'url' => $partial_product->getUrl() )
			);

			// if the product has no variations (and was already crawled once) this is all the data we need
			// set the item as updated to avoid additional crawlings
			$single_items_updated = $wpdb->update(
				$wpdb->prefix . 'woo_scrape_products',
				array(
					'item_updated_timestamp' => $now,
				),
				array(
					'url'            => $partial_product->getUrl(),
					'has_variations' => false,
				)
			);

			// if the the product got updated, remove it from the array
			if ( $rows_updated >= 1 ) {
				unset( $partial_products[ $key ] );
			}
			// there shouldn't be more than one row updated
			if ( $rows_updated > 1 ) {
				error_log(
					"There is more than one woo_scrape_product on database with url "
					. $partial_product->url
				);
			}
		}

		// returns product that didn't update
		return $partial_products;
	}

	/**
	 * Creates on DB the products of the $profitable_products array.
	 *
	 * @param array $partial_products array of products to insert
	 */
	private function save_partial_products( array $partial_products ): void {
		global $wpdb;

		$now = date( self::$date_format );

		foreach ( $partial_products as $partial_product ) {
			$wpdb->insert(
				$wpdb->prefix . 'woo_scrape_products',
				array(
					'name'                   => $partial_product->getName(),
					'url'                    => $partial_product->getUrl(),
					'image_urls'             => json_encode( $partial_product->getImageUrls() ),
					'first_crawl_timestamp'  => $now,
					'latest_crawl_timestamp' => $now,
					'brand'                  => $partial_product->getBrand(),
					'suggested_price'        => strval( $partial_product->getSuggestedPrice() ),
					'discounted_price'       => strval( $partial_product->getDiscountedPrice() ),
				)
			);
		}
	}

}
