<?php
require ABSPATH . 'wp-content/plugins/woo-scrape/vendor/simple_html_dom.php';
require ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-fishdeal-crawler-service.php';

class Woo_scrape_category_crawling_job {
	private static Woo_scrape_fishdeal_crawler_service $crawler;

	function __construct() {
		self::$crawler = new Woo_scrape_fishdeal_crawler_service();
	}

	public function run() {
		global $wpdb;

		$this->fetch_categories_for_profitable_products();

			error_log( "The category " . $category->id . " has crawled " . count( $profitable_products ) . " items" );



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

		$now = date( 'Y-m-d H:i:s' );

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
	private function save_partial_products( array $partial_products ) {
		global $wpdb;

		$now = date( 'Y-m-d H:i:s' );

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
