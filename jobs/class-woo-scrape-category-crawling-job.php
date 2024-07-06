<?php

require ABSPATH . 'wp-content/plugins/woo-scrape/vendor/simple_html_dom.php';
require ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-fishdeal-crawler-service.php';

class Woo_scrape_category_crawling_job {
	private static Woo_scrape_fishdeal_crawler_service $crawler;
	private static string $date_format = 'Y-m-d H:i:s';

	public function __construct() {
		self::$crawler = new Woo_scrape_fishdeal_crawler_service();
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
		global $wpdb;

		$page = 0;

		$products_table_name   = $wpdb->prefix . 'woo_scrape_products';
		$pages_list_table_name = $wpdb->prefix . 'woo_scrape_pages';

		// gets products crawled today
		while ( true ) {
			// get product page
			$start            = $page * 30;
			$crawled_products = $wpdb->get_results(
				"SELECT $products_table_name.id, $products_table_name.name, suggested_price, description, weight, length, width, height, has_variations FROM $products_table_name
    			INNER JOIN $pages_list_table_name
            	ON $products_table_name.category_id = $pages_list_table_name.id
                WHERE DATE(`item_updated_timestamp`) = CURDATE()
                LIMIT $start,30"
			);

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
				$woocommerce_product = $this->update_woocommerce_product( $product_id, $crawled_product );

				// if the product has no variations, update them
				if ( $crawled_product->has_variations ) {
					$this->update_woocommerce_vatiarions( $crawled_product->id );
				}


			}

			error_log( "there are " . count( $new_products ) . " new products to create on woocommerce." );

			// save new products on woocommerce
			foreach ( $new_products as $new_product ) {
				if ( ! $new_product->has_variations ) {
					$product = new WC_Product_Simple();
				} else {
					$product    = $this->save_woocommerce_variations($new_product->id);
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
		global $wpdb;

		$page                = 0;
		$products_table_name = $wpdb->prefix . 'woo_scrape_products';

		// gets products not crawled today
		while ( true ) {
			// get product page
			$start             = $page * 30;
			$outdated_products = $wpdb->get_results(
				"SELECT id, has_variations FROM $products_table_name
                WHERE DATE(`latest_crawl_timestamp`) != CURDATE()
                LIMIT $start,30"
			);

			error_log( "Fetched " . count( $outdated_products ) . " outdated products to set out of stock." );

			// if there are no outdated products left, stop the cycle
			if ( empty( $outdated_products ) ) {
				error_log( "There are no more outdated products" );
				break;
			}

			// set each product as out of stock
			foreach ( $outdated_products as $outdated_product ) {
				$product_id = wc_get_product_id_by_sku( 'kum-fd-' . $outdated_product->id );
				$product    = wc_get_product( $product_id );
				$product->set_stock_status( 'outofstock' );
				$product->save();
				if ( $outdated_product->has_variations() ) {
					$variation_ids = $product->get_children();
					foreach ( $variation_ids as $variation_id ) {
						$variation = wc_get_product( $variation_id );
						$variation->set_stock_status( 'outofstock' );
						$variation->save();
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
			$partial_profitable_products = $this->update_partial_products( $partial_profitable_products );

			error_log( "There are " . count( $partial_profitable_products ) . " new items to save" );

			// save the new products
			$this->save_partial_products( $category->id, $partial_profitable_products );
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
                and has_variations is not false
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
					error_log( "Crawled " . count( $image_ids ) . " images for " . $partial_product->url );
				}

				// update the product on DB
				$this->update_complete_product( $now, $partial_product->id, $complete_product );

				if ( $complete_product->hasvariations() ) {
					error_log( "The item " . $partial_product->url . "has " . count( $complete_product->getVariations() ) . " variations!" );
					// update variations on DB
					$new_variations = $this->update_variations( $now, $partial_product->id, $complete_product->getVariations() );
					error_log( "The item " . $partial_product->url . "has " . count( $new_variations ) . " new variations!" );
					// create new variations on db
					$this->save_variations( $now, $partial_product->id, $new_variations );
				}

			}
			$page += 1;
		}
	}

	private function update_woocommerce_product( int $product_id, $crawled_product ): WC_Product {
		$product = wc_get_product( $product_id );
		$product->set_stock_status( 'instock' );
		$product->set_weight( $crawled_product->weight );
		$product->set_length( $crawled_product->length );
		$product->set_width( $crawled_product->width );
		$product->set_height( $crawled_product->height );
		//TODO: update other things

		// if the product has no variations, set the price
		if ( ! $crawled_product->has_variations ) {
			$product->set_price( $crawled_product->suggested_price );
		}

		$product->save();

		return $product;
	}

	private function update_woocommerce_vatiarions( int $product_id, WC_Product $woocommerce_product, $crawled_product ): void {
		global $wpdb;

		$variations_table_name = $wpdb->prefix . 'woo_scrape_variations';

		// get crawled variation for this product from DB
		$crawled_variations = $wpdb->get_results(
			"SELECT id, name, suggested_price FROM $variations_table_name
                					WHERE DATE(`item_updated_timestamp`) = CURDATE() AND product_id = $product_id"
		);
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
				$variation->set_stock_status( 'instock' );
				$variation->set_price( $current_crawled_variation->suggested_price );
				$variation->set_weight( $crawled_product->weight );
				$variation->set_length( $crawled_product->length );
				$variation->set_width( $crawled_product->width );
				$variation->set_height( $crawled_product->height );

				$variation->save();
			}

		}

		//TODO: insert new variations, they are all contained into $crawled_variations
	}

	private function save_woocommerce_variations(int $product_id): WC_Product_Variable {
		global $wpdb;

		$variations_table_name = $wpdb->prefix . 'woo_scrape_variations';

		$product    = new WC_Product_Variable();
		$variations = $wpdb->get_results(
			"SELECT id, name, suggested_price FROM $variations_table_name
                				WHERE product_id = " . $product_id
		);

		$variation_options = array();

		foreach ( $variations as $variation ) {
			$variation_options[] = $variation->name;
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Variant' );
		$attribute->set_options( $variation_options );
		$attribute->set_position( 0 );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product->set_attributes( array( $attribute ) );
		$product->save();

		foreach ( $variations as $variation ) {
			$variation_name      = $variation->name;
			$variation_price     = $variation->suggested_price;
			$variation_options[] = $variation_name;
			$variation           = new WC_Product_Variation();
			$variation->set_parent_id( $product->get_id() );
			$variation->set_attributes( array( 'variant' => $variation_name ) );
			$variation->set_regular_price( $variation_price );
			$variation->save();
		}

		return $product;
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

		$columns = array(
			'item_updated_timestamp' => $now,
			'has_variations'         => $complete_product->hasVariations(),
			'image_urls'             => json_encode( $complete_product->getImageUrls() ),
			'description'            => $complete_product->getDescription()
		);

		// edit image ids only if there is something in the product
		if ( count( $complete_product->getImageIds() ) > 0 ) {
			$columns['image_ids'] = json_encode( $complete_product->getImageIds() );
		}

		// update products with the additional details
		$rows_updated = $wpdb->update(
			$wpdb->prefix . 'woo_scrape_products',
			$columns,
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
				$wpdb->prefix . 'woo_scrape_variations',
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
				$wpdb->prefix . 'woo_scrape_variations',
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
	 * @param int $category_id id of the product's category
	 * @param array $partial_products array of products to insert
	 */
	private function save_partial_products( int $category_id, array $partial_products ): void {
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
					'category_id'            => $category_id,
					'suggested_price'        => strval( $partial_product->getSuggestedPrice() ),
					'discounted_price'       => strval( $partial_product->getDiscountedPrice() ),
				)
			);
		}
	}

}
