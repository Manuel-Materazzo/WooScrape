<?php

require ABSPATH . 'wp-content/plugins/woo-scrape/utils/class-woo-scrape-fishdeal-dom-utils.php';

class Woo_scrape_fishdeal_crawler_service {

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

	/**
	 * Crawls a product and extracts a standardized WooScrapeProduct
	 *
	 * @param string $url url of the product to crawl
	 *
	 * @return WooScrapeProduct standardized product
	 */
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

			// must always be an array
			if ( is_object( $variations_array_json ) ) {
				$variations_array_json = array( $variations_array_json );
			}

			$variations_element = $html->find( '.SC_DealInfo-description attribute-select-block', 0 );
//			$variations_element-> data-deal-products-assignment, data-deal-options

			$description_element = $html->find( '.SC_DealDescription-blocks', 0 );

			$variations = array();
			$images     = array();

			foreach ( $variations_array_json as $variation_json ) {
				$variation = new WooScrapeProduct();
				$variation->setName( $variation_json->name );
//                $variation->setSuggestedPrice(?); // TODO: not found on html, it's a websocket byproduct
				$variation->setDiscountedPrice( new WooScrapeDecimal($variation_json->offers->price) );
				//TODO: quantity

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
	 * Crawls a list of images (if not already crawled), and returns their id
	 *
	 * @param array $urls array of url to crawl for images
	 *
	 * @return array array of image ids
	 */
	public function crawl_images( array $urls ): array {

		include_once ABSPATH . 'wp-admin/includes/image.php';
		$ids = array();

		foreach ( $urls as $url ) {
			//TODO: search for duplicate images and avoid crawling them again

			// generate file name
			$exploded  = explode( '/', getimagesize( $url )['mime'] );
			$imagetype = end( $exploded );
			$uniq_name = date( 'dmY' ) . '' . (int) microtime( true );
			$filename  = $uniq_name . '.' . $imagetype;

			// download and save file
			$uploaddir  = wp_upload_dir();
			$uploadfile = $uploaddir['path'] . '/' . $filename;
			$contents= file_get_contents($url);
			$savefile = fopen($uploadfile, 'w');
			fwrite($savefile, $contents);
			fclose($savefile);

			// prepare file
			$wp_filetype = wp_check_filetype( basename( $filename ), null );
			$attachment  = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => $filename,
				'post_content'   => '',
				'post_status'    => 'inherit'
			);

			// save on media library
			$attach_id    = wp_insert_attachment( $attachment, $uploadfile );
			$imagenew     = get_post( $attach_id );
			$fullsizepath = get_attached_file( $imagenew->ID );
			$attach_data  = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			// add the id to the list
			$ids[] = $attach_id;
		}

		return $ids;
	}

	/**
	 * Requests the crawling of an url to the scraping service
	 *
	 * @param string $url the url to crawl
	 *
	 * @return string a string containing the HTML body
	 */
	private function crawl( string $url ): string {
		$response = wp_remote_post( "http://host.docker.internal:3004/", array(
			'method'      => 'GET',
			'headers'     => array( 'Accept' => 'application/json' ),
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true
		) );

		return $response["body"];
	}
}
