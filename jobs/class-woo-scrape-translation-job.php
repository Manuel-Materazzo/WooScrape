<?php
require_once ABSPATH . 'wp-content/plugins/woo-scrape/dtos/class-woo-scrape-product.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-product-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-variation-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-deepl-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-gtranslate-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-job-log-service.php';

class Woo_Scrape_Translation_Job {
	private static Woo_scrape_product_service $product_service;
	private static Woo_Scrape_Variation_Service $variation_service;
	private static Woo_scrape_gtranslate_service $gtranslate_service;
	private static Woo_scrape_deepl_service $deepl_service;
	private static Woo_Scrape_Job_Log_Service $log_service;

	public function __construct() {
		self::$product_service    = new Woo_scrape_product_service();
		self::$variation_service  = new Woo_Scrape_Variation_Service();
		self::$gtranslate_service = new Woo_scrape_gtranslate_service();
		self::$deepl_service      = new Woo_scrape_deepl_service();
		self::$log_service        = new Woo_Scrape_Job_Log_Service();
	}

	public function run( bool $manual = false ): void {
		$automatic_description_translation   = get_option( 'woo_scrape_automatic_description_translation', true );
		$automatic_specification_translation = get_option( 'woo_scrape_automatic_specification_translation', true );
		$automatic_title_translation         = get_option( 'woo_scrape_automatic_title_translation', true );

		if ( $manual || $automatic_title_translation ) {
			$title_google_translation = get_option( 'woo_scrape_title_google_translation', true );

			self::$log_service->job_start( JobType::Names_translation );
			$this->translate( $title_google_translation, 'name', JobType::Names_translation );
			// translate variations too
			$this->translate( $title_google_translation, 'name', JobType::Names_translation, true );
			self::$log_service->job_end( JobType::Names_translation );
		}

		if ( $manual || $automatic_specification_translation ) {
			$specification_google_translation = get_option( 'woo_scrape_specification_google_translation', true );

			self::$log_service->job_start( JobType::Specifications_translation );
			$this->translate( $specification_google_translation, 'specifications', JobType::Specifications_translation );
			self::$log_service->job_end( JobType::Specifications_translation );
		}

		if ( $manual || $automatic_description_translation ) {
			$description_google_translation = get_option( 'woo_scrape_descriptions_google_translation', true );

			self::$log_service->job_start( JobType::Descriptions_translation );
			$this->translate( $description_google_translation, 'description', JobType::Descriptions_translation );
			self::$log_service->job_end( JobType::Descriptions_translation );
		}

	}

	/**
	 * Retrieves on DB products with the provided field untranslated and translates them with a translator of choice
	 *
	 * @param bool $use_gtranslate
	 * @param string $field
	 *
	 * @return void
	 */
	private function translate( bool $use_gtranslate, string $field, JobType $job_type, bool $variation = false ): void {
		$language_code = get_option( 'woo_scrape_translation_language', 'en' );
		$ignore_brands = get_option( 'woo_scrape_translation_ignore_brands', true );
		$sleep_ms      = (int) get_option( 'woo_scrape_translation_delay_ms', 50 );

		// get the correct translator
		if ( $use_gtranslate ) {
			$translator = self::$gtranslate_service;
		} else {
			$translator = self::$deepl_service;
		}

		// get all untranslated products with a paged query
		while ( true ) {
			// select the correct service, variation or product
			if ( $variation ) {
				$untranslated_products = self::$variation_service->get_variations_with_untranslated_field_paged( $field );
			} else {
				$untranslated_products = self::$product_service->get_products_with_untranslated_field_paged( $field );
			}

			error_log( "Fetched " . count( $untranslated_products ) . " products with untranslated {$field}." );

			// if there are no product left to translate, stop the cycle
			if ( empty( $untranslated_products ) ) {
				error_log( "There are no more products with untranslated {$field}" );
				break;
			}

			// translate each product
			foreach ( $untranslated_products as $untranslated_product ) {
				try {
					// if it's supposed to ignore brands, extract it
					if ( $ignore_brands ) {
						$brand = $untranslated_product->brand;
					}

					// remove newlines
					$text_to_translate = $untranslated_product->$field;
					$text_to_translate = preg_replace( '/\s+/', ' ', $text_to_translate );

					// translate
					$translated_field = $translator->translate( $text_to_translate, $language_code, $brand );

					// add back newlines
					$translated_field = str_replace( '- ', "\r\n- ", $translated_field );
					$translated_field = str_replace( ' .', '.', $translated_field );

					// update product on database
					$product = new WooScrapeProduct();
					$product->setId( $untranslated_product->id );
					$product->setTranslatedField( $field, $translated_field );

					self::$product_service->update_by_id( $product, true );
					self::$log_service->increase_completed_counter( $job_type );

				} catch ( Exception $e ) {
					error_log( $e );
					self::$log_service->increase_failed_counter( $job_type );
				}

				// delay to avoid too many requests
				usleep( $sleep_ms * 1000 );
			}
		}

	}
}
