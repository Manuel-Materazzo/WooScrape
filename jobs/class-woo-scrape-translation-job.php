<?php
require_once ABSPATH . 'wp-content/plugins/woo-scrape/dtos/class-woo-scrape-product.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-product-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-deepl-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-gtranslate-service.php';

class Woo_Scrape_Translation_Job {
	private static Woo_scrape_product_service $product_service;
	private static Woo_scrape_gtranslate_service $gtranslate_service;
	private static Woo_scrape_deepl_service $deepl_service;

	public function __construct() {
		self::$product_service    = new Woo_scrape_product_service();
		self::$gtranslate_service = new Woo_scrape_gtranslate_service();
		self::$deepl_service      = new Woo_scrape_deepl_service();
	}

	public function run( bool $manual = false ): void {
		$automatic_description_translation = get_option( 'automatic_description_translation', true );
		$automatic_title_translation       = get_option( 'automatic_title_translation', true );

		if ( $manual || $automatic_title_translation ) {
			$title_google_translation = get_option( 'title_google_translation', true );

			$this->translate( $title_google_translation, 'name' );
		}

		if ( $manual || $automatic_description_translation ) {
			$description_google_translation   = get_option( 'descriptions_google_translation', true );
			$specification_google_translation = get_option( 'specification_google_translation', true );

			$this->translate( $description_google_translation, 'description' );
			$this->translate( $specification_google_translation, 'specifications' );
		}

	}

	/**
	 * Retrieves on DB products with the provided field untranslated and translates them with a translator of choice
	 * @param bool $use_gtranslate
	 * @param string $field
	 *
	 * @return void
	 */
	private function translate( bool $use_gtranslate, string $field ): void {
		$language_code = get_option( 'translation_language', 'en' );
		$ignore_brands = get_option( 'translation_ignore_brands', true );
		$page          = 0;

		// get the correct translator
		if ( $use_gtranslate ) {
			$translator = self::$gtranslate_service;
		} else {
			$translator = self::$deepl_service;
		}

		// get all untranslated products with a paged query
		while ( true ) {
			$untranslated_products = self::$product_service->get_products_with_untranslated_field_paged( $field, $page );

			error_log( "Fetched " . count( $untranslated_products ) . " products with untranslated {$field}." );

			// if there are no product left to translate, stop the cycle
			if ( empty( $untranslated_products ) ) {
				error_log( "There are no more products with untranslated {$field}" );
				break;
			}

			// translate each product
			foreach ( $untranslated_products as $untranslated_product ) {

				// if it's supposed to ignore brands, extract it
				if ( $ignore_brands ) {
					$brand = $untranslated_product->brand;
				}

				// translate
				$translated_field = $translator->translate( $untranslated_product->$field, $language_code, $brand );

				// update product on database
				$product = new WooScrapeProduct();
				$product->setId( $untranslated_product->id );
				$product->setTranslatedField( $field, $translated_field );
				self::$product_service->update_by_id( $product, true );
			}
			$page += 1;
		}

	}
}
