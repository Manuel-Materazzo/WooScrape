<?php

require_once ABSPATH . 'wp-content/plugins/woo-scrape/utils/class-woo-scrape-fishdeal-dom-utils.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/abstracts/class-woo-scrape-abstract-crawler-service.php';

class Woo_scrape_fishdeal_crawler_service extends Woo_Scrape_Abstract_Crawler_Service {

	/**
	 * Crawls a category and all its sub pages and returns a list of profitable products
	 *
	 * @param string $url url of the category to crawl
	 *
	 * @return array array of profitable products
	 */
	public function crawl_category( string $url ): array {
		$html = null;

		// crawl first page
		try {
			$response = $this->crawl( $url );
			$html     = str_get_html( $response );
			$products = Woo_scrape_fishdeal_dom_utils::extract_products( $html );
		} catch ( Exception $ex ) {
			error_log( "Failed to crawl category " . $url );
			error_log( $ex );

			return array();
		}

		// extract page count
		$pages = Woo_scrape_fishdeal_dom_utils::extract_pages( $html );

		// crawl other pages
		for ( $i = 2; $i <= $pages; ++ $i ) {
			try {
				$page_response = $this->crawl( $url . "?order=Relevant%20-%20EnhancedGA4&scroll=deals&page=" . $i );
				$page_html     = str_get_html( $page_response );
				$page_products = Woo_scrape_fishdeal_dom_utils::extract_products( $page_html );
				$products      = array_merge( $products, $page_products );
			} catch ( Exception $ex ) {
				error_log( "Failed to crawl page " . $i . " of the category " . $url );
				error_log( $ex );
			}
		}

		error_log( "Crawled " . $pages . " pages for category " . $url );

		return $products;
	}

	/**
	 * Crawls a product and extracts a standardized WooScrapeProduct
	 *
	 * @param string $url url of the product to crawl
	 *
	 * @return WooScrapeProduct standardized product
	 */
	public function crawl_product( string $url, WooScrapeDecimal $suggested_price_multiplier ): WooScrapeProduct {
		$partial_product = new WooScrapeProduct();
		$partial_product->setUrl( $url );
		// crawl product page
		try {
			$response = $this->crawl( $url );
			$html     = str_get_html( $response );

			$variations_json_element = $html->find( 'body script[type=application/ld+json]', 0 );
			$variations_array_json   = json_decode( $variations_json_element->innertext() );

			// must always be an array
			if ( is_object( $variations_array_json ) ) {
				$variations_array_json = array( $variations_array_json );
			}

//			$variations_element = $html->find( '.SC_DealInfo-description attribute-select-block', 0 );
//			$variations_element-> data-deal-products-assignment, data-deal-options

			// format description
			$specification_element = $html->find( '.SC_DealDescription-block .SC_DealDescription-description', 0 );
			$specification         = $this->sanitize_text( $specification_element->text() );

			$description_element = $html->find( '.SC_DealDescription-block .SC_DealDescription-description', 1 );
			$description         = $this->sanitize_text( $description_element->text() );


			$variations = array();
			$images     = array();

			foreach ( $variations_array_json as $variation_json ) {
				$variation = new WooScrapeProduct();
				$variation->setName( $variation_json->name );
				// the suggested price is not on html, it's a websocket byproduct, so i'll approximate it
				$variation->setSuggestedPrice( $suggested_price_multiplier->clone()->multiply( $variation_json->offers->price ) );
				$variation->setDiscountedPrice( new WooScrapeDecimal( $variation_json->offers->price ) );
				//TODO: quantity

				// cast to array
				$variation_images = $variation_json->image;
				if ( ! is_array( $variation_images ) ) {
					$variation_images = array( $variation_images );
				}

				$images       = array_merge( $images, $variation_images );
				$variations[] = $variation;
			}

			// add variations to the main product
			$partial_product->setVariations( $variations );
			// set has_variations = false i f the product has only one variation.
			$partial_product->setHasVariations( count( $variations_array_json ) > 1 );
			// update main product
			$partial_product->setDescription( $description );
			$partial_product->setSpecification( $specification );
			$partial_product->setImageUrls( array_unique( $images ) );
		} catch ( Exception $ex ) {
			error_log( "Failed to crawl product " . $url );
			error_log( $ex );
		}

		return $partial_product;
	}

	private function sanitize_text( string $text ): string {
		$text = preg_replace( '/[ \t]+/', ' ', $text );
		$text = str_replace( 'Descrizione', '', $text );
		$text = str_replace( 'Caratteristiche', '', $text );
		$text = str_replace( 'Vedere di più', '', $text );
		$text = str_replace( 'Chiudi lista', '', $text );
		$text = str_replace( 'Mostra di più', '', $text );
		$text = str_replace( 'Riduci', '', $text );

		return $text;
	}

}
