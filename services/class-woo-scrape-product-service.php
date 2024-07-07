<?php

class Woo_scrape_product_service {

	private static string $date_format = 'Y-m-d H:i:s';
	private static string $products_table_name = 'woo_scrape_products';
	private static string $pages_list_table_name = 'woo_scrape_pages';

	/**
	 * Gets a paged list of products joined with package information from DB
	 *
	 * @param int $page
	 *
	 * @return array|object|stdClass[]|null
	 */
	public function get_products_with_package_paged( int $page ): array {
		global $wpdb;
		$products_table_name   = $wpdb->prefix . self::$products_table_name;
		$pages_list_table_name = $wpdb->prefix . self::$pages_list_table_name;

		$start = $page * 30;

		return $wpdb->get_results(
			"SELECT $products_table_name.id, $products_table_name.name, suggested_price, description, weight,
       						length, width, height, has_variations, image_ids
				FROM $products_table_name INNER JOIN $pages_list_table_name
            	ON $products_table_name.category_id = $pages_list_table_name.id
                WHERE DATE(`item_updated_timestamp`) = CURDATE()
                LIMIT $start,30"
		);
	}

	/**
	 * Gets a paged list of products (id) not updated today
	 *
	 * @param int $page
	 *
	 * @return array|object|stdClass[]|null
	 */
	public function get_outdated_products_paged( int $page ): array {
		global $wpdb;
		$products_table_name = $wpdb->prefix . self::$products_table_name;

		$start = $page * 30;

		return $wpdb->get_results(
			"SELECT id FROM $products_table_name
                WHERE DATE(`latest_crawl_timestamp`) != CURDATE()
                LIMIT $start,30"
		);
	}

	/**
	 * Gets a paged list of products (id, url, image_urls, image_ids) updated today that have variations
	 *
	 * @param int $page
	 *
	 * @return array|object|stdClass[]|null
	 */
	public function get_updated_products_with_variations_paged( int $page ): array {
		global $wpdb;
		$products_table_name = $wpdb->prefix . self::$products_table_name;

		$start = $page * 30;

		return $wpdb->get_results(
			"SELECT id, url, image_urls, image_ids FROM $products_table_name
                WHERE DATE(`latest_crawl_timestamp`) = CURDATE()
                and has_variations is not false
                LIMIT $start,30"
		);
	}

	/**
	 * Updates a product on DB, using its URL as index, and returns the ones not found on DB.
	 * Products without variations (with has_variation=false, uncrawled products have has_variation=null) are directly
	 * set as updated.
	 *
	 * @param array $partial_products array of products to update
	 *
	 * @return array array of products not updated
	 */
	public function update_all_by_url( array $partial_products ): array {
		$now = date( self::$date_format );

		foreach ( $partial_products as $key => $partial_product ) {
			// update products already on the table
			$updated = $this->update_by_url( $partial_product, false, $now );

			// if the the product got updated, remove it from the array
			if ( $updated ) {
				unset( $partial_products[ $key ] );
			}
		}

		// returns product that didn't update
		return $partial_products;
	}

	/**
	 * Creates a list of products on DB
	 *
	 * @param int $category_id id of the product's category
	 * @param array $partial_products array of products to insert
	 */
	public function create_all( int $category_id, array $products ): void {
		$now = date( self::$date_format );

		//TODO: transaction?
		foreach ( $products as $product ) {
			$this->create( $category_id, $product, $now );
		}
	}

	/**
	 * Creates a product on DB
	 *
	 * @param int $category_id
	 * @param WooScrapeProduct $product
	 * @param string|null $date
	 *
	 * @return void
	 */
	public function create( int $category_id, WooScrapeProduct $product, string $date = null ): void {
		global $wpdb;

		// initialize date, if not given
		if ( is_null( $date ) ) {
			$date = date( self::$date_format );
		}

		$parameters = array(
			'first_crawl_timestamp'  => $date,
			'latest_crawl_timestamp' => $date,
			'category_id'            => $category_id
		);

		// add query parameters only if needed
		$parameters = $this->add_standard_parameters( $parameters, $product );

		$wpdb->insert( $wpdb->prefix . 'woo_scrape_products', $parameters );
	}

	/**
	 * Updates a product on DB, using its URL as index
	 *
	 * @param WooScrapeProduct $product
	 * @param bool $set_updated_time
	 * @param string|null $date
	 *
	 * @return bool
	 */
	public function update_by_url( WooScrapeProduct $product, bool $set_updated_time = false, string $date = null ): bool {
		return $this->update( $product, array( 'url' => $product->getUrl() ), $set_updated_time, $date );
	}

	/**
	 * Updates a product on DB, using its URL as index
	 *
	 * @param WooScrapeProduct $product
	 * @param bool $set_updated_time
	 * @param string|null $date
	 *
	 * @return bool
	 */
	public function update_by_id( WooScrapeProduct $product, bool $set_updated_time = false, string $date = null ): bool {
		return $this->update( $product, array( 'id' => $product->getId() ), $set_updated_time, $date );
	}

	/**
	 * Updates a product on DB, using a dynamic where clause
	 *
	 * @param WooScrapeProduct $product
	 * @param array $where_clause
	 * @param bool $set_updated_time
	 * @param string $date
	 *
	 * @return bool
	 */
	private function update( WooScrapeProduct $product, array $where_clause, bool $set_updated_time, string $date ): bool {
		global $wpdb;

		// initialize date, if not given
		if ( is_null( $date ) ) {
			$date = date( self::$date_format );
		}

		$parameters = array(
			'latest_crawl_timestamp' => $date
		);

		// add query parameters only if needed
		$parameters = $this->add_standard_parameters( $parameters, $product );

		// add updated param if needed
		if ( $set_updated_time ) {
			$parameters['item_updated_timestamp'] = $date;
		}

		// update products already on the table
		$rows_updated = $wpdb->update(
			$wpdb->prefix . 'woo_scrape_products', $parameters,
			$where_clause
		);

		// if the product has no variations (and was already crawled once) we don't need additional data.
		// ignore the $set_updated flag and set the item as updated anyway to avoid additional crawlings
		if ( ! $set_updated_time ) {
			$single_items_updated = $wpdb->update(
				$wpdb->prefix . self::$products_table_name,
				array(
					'item_updated_timestamp' => $date,
				),
				array_merge( $where_clause, array( 'has_variations' => false ) ),
			);
		}


		// there shouldn't be more than one row updated
		if ( $rows_updated > 1 ) {
			error_log(
				"More than one woo_scrape_product matched the clause " . json_encode( $where_clause )
			);
		}

		return $rows_updated >= 1;
	}

	/**
	 * Given an array of pre-existing parameters and a product, adds only the valorized parameter
	 *
	 * @param array $parameters array of pre-existing parameters to be kept
	 * @param WooScrapeProduct $product product to scan
	 *
	 * @return array array of pre-existing parameters
	 */
	private function add_standard_parameters( array $parameters, WooScrapeProduct $product ): array {
		if ( ! is_null( $product->getName() ) ) {
			$parameters['name'] = $product->getName();
		}
		if ( ! is_null( $product->getDescription() ) ) {
			$parameters['description'] = $product->getDescription();
		}
		if ( ! is_null( $product->getBrand() ) ) {
			$parameters['brand'] = $product->getBrand();
		}
		if ( ! is_null( $product->hasVariations() ) ) {
			$parameters['has_variations'] = $product->hasVariations();
		}
		if ( ! is_null( $product->getUrl() ) ) {
			$parameters['url'] = $product->getUrl();
		}
		if ( ! empty( $product->getImageUrls() ) ) {
			$parameters['image_urls'] = $product->getImageUrls();
		}
		if ( ! empty( $product->getImageIds() ) ) {
			$parameters['image_ids'] = $product->getImageIds();
		}
		if ( ! is_null( $product->getQuantity() ) ) {
			$parameters['quantity'] = strval( $product->getQuantity() );
		}
		if ( ! is_null( $product->getSuggestedPrice() ) ) {
			$parameters['suggested_price'] = strval( $product->getSuggestedPrice() );
		}
		if ( ! is_null( $product->getDiscountedPrice() ) ) {
			$parameters['discounted_price'] = strval( $product->getDiscountedPrice() );
		}

		return $parameters;
	}
}

