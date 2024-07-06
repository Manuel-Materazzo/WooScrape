<?php

class Woo_Scrape_Variation_Service {

	private static string $date_format = 'Y-m-d H:i:s';
	private static string $variations_table_name = 'woo_scrape_variations';

	/**
	 * Creates a list of variations on DB
	 *
	 * @param int $parent_product_id
	 * @param array $variations
	 * @param string|null $date
	 *
	 * @return void
	 */
	public function create_all( int $parent_product_id, array $variations, string $date = null ): void {

		// initialize date, if not given
		if ( is_null( $date ) ) {
			$date = date( self::$date_format );
		}

		//TODO: transaction?
		foreach ( $variations as $variation ) {
			$this->create( $parent_product_id, $variation, $date );
		}
	}

	/**
	 * Creates a variation on DB
	 *
	 * @param int $parent_product_id
	 * @param WooScrapeProduct $variation
	 * @param string|null $date
	 *
	 * @return void
	 */
	public function create( int $parent_product_id, WooScrapeProduct $variation, string $date = null ): void {
		global $wpdb;

		// initialize date, if not given
		if ( is_null( $date ) ) {
			$date = date( self::$date_format );
		}

		$parameters = array(
			'first_crawl_timestamp'  => $date,
			'latest_crawl_timestamp' => $date,
			'item_updated_timestamp' => $date,
			'product_id'             => $parent_product_id
		);

		// add query parameters only if needed
		$parameters = $this->add_standard_parameters( $parameters, $variation );

		$wpdb->insert( $wpdb->prefix . self::$variations_table_name, $parameters );
	}

	/**
	 * Updates a variation on DB, using using parent id and name as index, and returns the ones not found on DB.
	 * @param int $parent_product_id
	 * @param array $variations
	 * @param int|null $date
	 *
	 * @return array
	 */
	public function update_all_by_parent_id_and_name( int $parent_product_id, array $variations, int $date = null ): array {

		// initialize date, if not given
		if ( is_null( $date ) ) {
			$date = date( self::$date_format );
		}

		foreach ( $variations as $key => $variation ) {
			// update variation already on table
			$updated = $this->update_by_parent_id_and_name($parent_product_id, $variation, $date);

			// if the the product got updated, remove it from the array
			if ( $updated >= 1 ) {
				unset( $variations[ $key ] );
			}
		}

		// returns variations that didn't update
		return $variations;
	}

	/**
	 * Updates a variation on DB, using parent id and name as index
	 *
	 * @param int $parent_product_id
	 * @param WooScrapeProduct $variation
	 * @param int|null $date
	 *
	 * @return bool
	 */
	public function update_by_parent_id_and_name( int $parent_product_id, WooScrapeProduct $variation, int $date = null ): bool {
		global $wpdb;

		// initialize date, if not given
		if ( is_null( $date ) ) {
			$date = date( self::$date_format );
		}

		$parameters = array(
			'latest_crawl_timestamp' => $date,
			'item_updated_timestamp' => $date
		);

		// add query parameters only if needed
		$parameters = $this->add_standard_parameters( $parameters, $variation );

		// update products already on the table
		$rows_updated = $wpdb->update(
			$wpdb->prefix . self::$variations_table_name,
			$parameters,
			array(
				'product_id' => $parent_product_id,
				'name'       => $variation->getName(),
			)
		);

		// there shouldn't be more than one row updated
		if ( $rows_updated > 1 ) {
			error_log(
				"There is more than one variation on database for the product" . $parent_product_id .
				"  with name " . $variation->getName()
			);
		}

		return $rows_updated >= 1;
	}

	/**
	 * Given an array of pre-existing parameters and a product, adds only the valorized parameter
	 *
	 * @param array $parameters array of pre-existing parameters to be kept
	 * @param WooScrapeProduct $variation product to scan
	 *
	 * @return array array of pre-existing parameters
	 */
	private function add_standard_parameters( array $parameters, WooScrapeProduct $variation ): array {
		if ( ! is_null( $variation->getName() ) ) {
			$parameters['name'] = $variation->getName();
		}
		if ( ! is_null( $variation->getQuantity() ) ) {
			$parameters['quantity'] = strval( $variation->getQuantity() );
		}
		if ( ! is_null( $variation->getSuggestedPrice() ) ) {
			$parameters['suggested_price'] = strval( $variation->getSuggestedPrice() );
		}
		if ( ! is_null( $variation->getDiscountedPrice() ) ) {
			$parameters['discounted_price'] = strval( $variation->getDiscountedPrice() );
		}

		return $parameters;
	}
}
