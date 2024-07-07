<?php

require ABSPATH . 'wp-content/plugins/woo-scrape/vendor/simple_html_dom.php';
require ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-fishdeal-crawler-service.php';

class Woo_scrape_category_crawling_job {
	private static Woo_scrape_fishdeal_crawler_service $crawler;
	private static Woo_scrape_product_service $product_service;
	private static Woo_scrape_variation_service $variation_service;
	private static Woo_Scrape_WooCommerce_Service $woocommerce_service;
	private static string $date_format = 'Y-m-d H:i:s';

	public function __construct() {
		self::$crawler             = new Woo_scrape_fishdeal_crawler_service();
		self::$product_service     = new Woo_scrape_product_service();
		self::$variation_service   = new Woo_scrape_variation_service();
		self::$woocommerce_service = new Woo_Scrape_WooCommerce_Service();
	}

	public function run(): void {
		// crawl categories to get partial products
		$this->fetch_categories_for_profitable_products();

		// crawl each partial product to complete informations
		$this->fetch_profitable_products();

		// get from database outdated products, and set them out of stock
		$this->update_out_of_stock();

		// extracts from DB today's crawled products and variants, and persists them on woocomerce
		$this->update_woocommerce_database();

	}

	private function update_woocommerce_database(): void {
		$page = 0;

		// gets products crawled today
		while ( true ) {
			// get product page
			$crawled_products = self::$product_service->get_products_with_package_paged( $page );

			error_log( "Fetched " . count( $crawled_products ) . " products to store on woocommerce." );

			// if there are no products left, stop the cycle
			if ( empty( $crawled_products ) ) {
				error_log( "There are no more products to update on woocommerce" );
				break;
			}

			$new_products = array();

			// update each product on woocommerce
			foreach ( $crawled_products as $crawled_product ) {
				$product_id = wc_get_product_id_by_sku( 'kum-fd-' . $crawled_product->id );
				// if there is no such product on woocommerce, queue it for creation and go on
				if ( ! $product_id ) {
					$new_products[] = $crawled_product;
					continue;
				}

				// update the product on woocommerce
				$woocommerce_product = self::$woocommerce_service->update_product( $product_id, $crawled_product );

				// if the product has no variations, update them
				if ( $crawled_product->has_variations ) {
					$this->update_woocommerce_vatiarions( $crawled_product->id, $woocommerce_product, $crawled_product );
				}


			}

			error_log( "there are " . count( $new_products ) . " new products to create on woocommerce." );

			// save new products on woocommerce
			foreach ( $new_products as $new_product ) {

				if ( ! $new_product->has_variations ) {
					$product = new WC_Product_Simple();
				} else {
					$product = $this->save_woocommerce_variations( $new_product->id );
				}

				$product->set_sku( 'kum-fd-' . $new_product->id );

				$product->set_name( $new_product->name );
				$product->set_regular_price( $new_product->suggested_price );
				$product->set_description( $new_product->description );
				$product->set_stock_status( 'instock' );
				$product->set_weight( $new_product->weight );
				$product->set_length( $new_product->length );
				$product->set_width( $new_product->width );
				$product->set_height( $new_product->height );
//				TODO: $product->set_image_id( 90 );
//				TODO: $product->set_category_ids( array( 19 ) );
				$product->save();
			}

			$page += 1;
		}
	}

	private function update_out_of_stock(): void {
		$page = 0;

		// gets products not crawled today
		while ( true ) {
			// get product page
			$outdated_products = self::$product_service->get_outdated_products_paged( $page );

			error_log( "Fetched " . count( $outdated_products ) . " outdated products to set out of stock." );

			// if there are no outdated products left, stop the cycle
			if ( empty( $outdated_products ) ) {
				error_log( "There are no more outdated products" );
				break;
			}

			// set each product as out of stock
			foreach ( $outdated_products as $outdated_product ) {

				$product_id                 = wc_get_product_id_by_sku( 'kum-fd-' . $outdated_product->id );
				$outdated_product->quantity = 0;
				$product                    = self::$woocommerce_service->update_product( $product_id, $outdated_product );

				// if the product has variations, set them out of stock
				if ( $outdated_product->has_variations() ) {
					$variation_ids = $product->get_children();
					foreach ( $variation_ids as $variation_id ) {
						// $outdated_product has only the quantity, and it's 0
						self::$woocommerce_service->update_product( $variation_id, $outdated_product );
					}
				}
			}
			$page += 1;
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
			$partial_profitable_products = self::$crawler->crawl_category( $category->url );

			error_log( "The category " . $category->id . " has crawled " . count( $partial_profitable_products ) . " items" );

			// update existing products, and return "new" products
			$partial_profitable_products = self::$product_service->update_all_by_url( $partial_profitable_products );

			error_log( "There are " . count( $partial_profitable_products ) . " new items to save" );

			// save the new products
			self::$product_service->create_all( $category->id, $partial_profitable_products );
		}
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
			foreach ( $updated_products as $partial_product ) {
				$complete_product = self::$crawler->crawl_product( $partial_product->url );
				$complete_product->setId( $partial_product->id );

				error_log( "Crawled " . $partial_product->url );

				// if the product has no crawled images, or the images changed
				if ( ! $partial_product->image_ids ||
				     json_encode( $complete_product->getImageUrls() ) !== $partial_product->image_urls ) {
					error_log( "Found new images for " . $partial_product->url . " : " . json_encode( $complete_product->getImageUrls() ) );
					// crawl images and save them
					$image_ids = self::$crawler->crawl_images( $complete_product->getImageUrls() );
					// add ids to the product
					$complete_product->setImageIds( $image_ids );
					error_log( "Crawled " . count( $image_ids ) . " images for " . $partial_product->url );
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
				}

			}
			$page += 1;
		}
	}

	private function update_woocommerce_vatiarions( int $product_id, WC_Product $woocommerce_product, $crawled_product ): void {

		// get crawled variation for this product from DB
		$crawled_variations = self::$variation_service->get_updated_variations_by_product_id( $product_id );
		// extract product variations
		$variation_ids = $woocommerce_product->get_children();

		error_log( "Updating " . count( $crawled_variations ) . " variations..." );

		// update each variation
		foreach ( $variation_ids as $variation_id ) {
			// get the variation reference
			$variation = wc_get_product( $variation_id );

			// find the correct DB variation
			$current_crawled_variation = null;
			foreach ( $crawled_variations as $variation_key => $crawled_variation ) {
				if ( $crawled_variation->name == $variation->get_name() ) {
					$current_crawled_variation = $crawled_variation;
					// remove existing variation from list
					unset( $crawled_variations[ $variation_key ] );
				}
			}

			// update variation if crawled
			if ( $current_crawled_variation ) {
				self::$woocommerce_service->update_product( $variation_id, $current_crawled_variation );
			}

		}

		if ( count( $crawled_variations ) > 0 ) {
			error_log( "There are " . count( $crawled_variations ) . " new variations to create for $product_id." );
		}

		//TODO: insert new variations, they are all contained into $crawled_variations
	}

	private function save_woocommerce_variations( int $product_id ): WC_Product_Variable {
		$variations = self::$variation_service->get_updated_variations_by_product_id( $product_id );

		return self::$woocommerce_service->create_variable_product( $variations );
	}

}
