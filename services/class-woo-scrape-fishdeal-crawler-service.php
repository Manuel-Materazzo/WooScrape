<?php

require ABSPATH . 'wp-content/plugins/woo-scrape/utils/class-woo-scrape-fishdeal-dom-utils.php';

class Woo_scrape_fishdeal_crawler_service {

	/**
	 * Crawls a category and all its sub pages and returns a list of profitable products
	 *
	 * @param string $url url of the category to crawl
	 * @param int $category_id id of the category to crawl
	 *
	 * @return array array of profitable products
	 */
	public function crawl_category( string $url, int $category_id ): array {
		$html = null;

		// crawl first page
		try {
			$response = $this->crawl( $url );
			$html     = str_get_html( $response );
			$products = Woo_scrape_fishdeal_dom_utils::extract_products( $html, $category_id );
		} catch ( Exception $ex ) {
			error_log( "Failed to crawl category " . $url );
			error_log( $ex );

			return array();
		}

		// extract page count
		$pages = Woo_scrape_fishdeal_dom_utils::extract_pages( $html );

		// crawl other pages
		for ( $i = 1; $i < $pages; ++ $i ) {
			try {
				$page_response = $this->crawl( $url );
				$page_html     = str_get_html( $page_response );
				$page_products = Woo_scrape_fishdeal_dom_utils::extract_products( $page_html, $category_id );
				$products      = array_merge( $products, $page_products );
			} catch ( Exception $ex ) {
				error_log( "Failed to crawl page " . $i . " of the category " . $url );
				error_log( $ex );
			}
		}

		error_log( "Crawled " . $pages . " pages for category " . $url );

		return $products;
	}

	public function crawl_product( string $url ): WooScrapeProduct {
		$html            = null;
		$partial_product = new WooScrapeProduct();
		$partial_product->setUrl( $url );
		// crawl product page
		try {
			$response = $this->crawl( $url );
			$html     = str_get_html( $response );

			$variations_json_element = $html->find( 'body script[type=application/ld+json]', 0 );
			$variations_array_json   = json_decode( $variations_json_element->innertext() );

			$variations_element  = $html->find( '.SC_DealInfo-description attribute-select-block', 0 );
			$variations_element-> // data-deal-products-assignment, data-deal-options

			$description_element = $html->find( '.SC_DealDescription-blocks', 0 );

			$variations = array();
			$images     = array();

			foreach ( $variations_array_json as $variation_json ) {
				$variation = new WooScrapeProduct();
				$variation->setName( $variation_json->name );
//                $variation->setSuggestedPrice(?); // TODO: not found on html, it's a websocket byproduct
				$variation->setDiscountedPrice( $variation_json->offers->price );

				$images       = array_merge( $images, $variation_json->image );
				$variations[] = $variation;
			}

			// add variations to the main product
			$partial_product->setVariations( $variations );
			// set has_variations = false i f the product has only one variation.
			$partial_product->setHasVariations( count( $variations_array_json ) > 1 );
			// update main product
			$partial_product->setDescription( $description_element->innertext() );
			$partial_product->setImageUrls( array_unique( $images ) );
		} catch ( Exception $ex ) {
			error_log( "Failed to crawl product " . $url );
			error_log( $ex );
		}

		return $partial_product;
	}

	/**
	 * Requests the crawling of an url to the scraping service
	 *
	 * @param string $url the url to crawl
	 *
	 * @return string a string containing the HTML body
	 */
	private function crawl( string $url ): string {
		//TODO: implement
		return "";
	}
}
