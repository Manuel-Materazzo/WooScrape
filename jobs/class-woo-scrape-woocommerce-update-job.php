<?php


require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-product-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-variation-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-woocommerce-service.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/class-woo-scrape-job-log-service.php';

class Woo_scrape_woocommerce_update_job {
	private static Woo_scrape_product_service $product_service;
	private static Woo_scrape_variation_service $variation_service;
	private static Woo_Scrape_WooCommerce_Service $woocommerce_service;
	private static Woo_Scrape_Job_Log_Service $log_service;

	public function __construct() {
		self::$product_service     = new Woo_scrape_product_service();
		self::$variation_service   = new Woo_scrape_variation_service();
		self::$woocommerce_service = new Woo_Scrape_WooCommerce_Service();
		self::$log_service         = new Woo_Scrape_Job_Log_Service();
	}

	public function run(): void {
		$stock_management = get_option( 'woo_scrape_woocommerce_stock_management', true );
		$auto_import      = get_option( 'woo_scrape_woocommerce_auto_import', true );

		// get from database outdated products, and set them out of stock
		if ( $stock_management ) {
			self::$log_service->job_start(JobType::Woocommerce_out_of_stock);
			$this->update_out_of_stock();
			self::$log_service->job_end(JobType::Woocommerce_out_of_stock);
		}

		// extracts from DB today's crawled products and variants, and persists them on woocomerce
		if ( $auto_import ) {
			self::$log_service->job_start(JobType::Woocommerce_update);
			self::$log_service->job_start(JobType::Woocommerce_create);
			$this->update_woocommerce_database();
			self::$log_service->job_end(JobType::Woocommerce_update);
			self::$log_service->job_end(JobType::Woocommerce_create);
		}
	}

	private function update_woocommerce_database(): void {
		$page       = 0;
		$sku_prefix = get_option( 'woo_scrape_sku_prefix', 'sku-1-' );
		$sleep_ms = (int) get_option('woo_scrape_import_delay_ms', 10);

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
				try {
					$product_id = wc_get_product_id_by_sku( $sku_prefix . $crawled_product->id );
					// if there is no such product on woocommerce, queue it for creation and go on
					if ( ! $product_id ) {
						$new_products[] = $crawled_product;
						continue;
					}

					// update the product on woocommerce
					$woocommerce_product = self::$woocommerce_service->update_product_by_id( $product_id, $crawled_product );

					// if the product has variations, update them
					if ( $crawled_product->has_variations ) {
						$this->update_woocommerce_vatiarions( $crawled_product->id, $woocommerce_product );
					}
					self::$log_service->increase_completed_counter(JobType::Woocommerce_update);
				} catch ( Exception $e ) {
					error_log( $e );
					self::$log_service->increase_failed_counter(JobType::Woocommerce_update);
				}

				// delay to avoid harrassing the DB
				usleep($sleep_ms * 1000);
			}

			error_log( "there are " . count( $new_products ) . " new products to create on woocommerce." );

			// save new products on woocommerce
			foreach ( $new_products as $new_product ) {
				try {
					if ( ! $new_product->has_variations ) {
						$product = new WC_Product_Simple();
					} else {
						$product = $this->save_woocommerce_variations( $new_product->id );
					}

					$product->set_sku( $sku_prefix . $new_product->id );
					$product->set_category_ids( array( $new_product->corresponding_woocommerce_category_id ) );

					self::$woocommerce_service->update_product( $product, $new_product );
					self::$log_service->increase_completed_counter(JobType::Woocommerce_create);
				} catch ( Exception $e ) {
					error_log( $e );
					self::$log_service->increase_failed_counter(JobType::Woocommerce_create);
				}

				// delay to avoid harrassing the DB
				usleep($sleep_ms * 1000);
			}

			$page += 1;
		}
	}

	private function update_out_of_stock(): void {
		$page       = 0;
		$sku_prefix = get_option( 'woo_scrape_sku_prefix', 'sku-1-' );

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

				try {

					$product_id                 = wc_get_product_id_by_sku( $sku_prefix . $outdated_product->id );
					$outdated_product->quantity = 0;
					$product                    = self::$woocommerce_service->update_product_by_id( $product_id, $outdated_product );

					// if the product has variations, set them out of stock
					if ( $outdated_product->has_variations() ) {
						$variation_ids = $product->get_children();
						foreach ( $variation_ids as $variation_id ) {
							// $outdated_product has only the quantity, and it's 0
							self::$woocommerce_service->update_product_by_id( $variation_id, $outdated_product );
						}
					}
					self::$log_service->increase_completed_counter(JobType::Woocommerce_out_of_stock);
				} catch ( Exception $e ) {
					error_log( $e );
					self::$log_service->increase_failed_counter(JobType::Woocommerce_out_of_stock);
				}
			}
			$page += 1;
		}
	}

	private function update_woocommerce_vatiarions( int $product_id, WC_Product $woocommerce_product ): void {

		// get crawled variation for this product from DB
		$crawled_variations = self::$variation_service->get_updated_variations_by_product_id( $product_id );
		// extract product variations
		$variation_ids = $woocommerce_product->get_children();

		error_log( "Updating " . count( $crawled_variations ) . " variations..." );

		// update each variation
		foreach ( $variation_ids as $variation_id ) {
			// get the variation reference
			$variation = wc_get_product( $variation_id );
			// extract the variation name from woocommerce title
			$variation_name = explode( ' - ', $variation->get_name() )[1];

			// find the correct DB variation
			$current_crawled_variation = null;
			foreach ( $crawled_variations as $variation_key => $crawled_variation ) {
				$crawled_variation_name = $crawled_variation->translated_name ?? $crawled_variation->name;
				if ( $crawled_variation_name == $variation_name ) {
					$current_crawled_variation = $crawled_variation;
					// remove existing variation from list
					unset( $crawled_variations[ $variation_key ] );
				}
			}

			// update variation if crawled
			if ( $current_crawled_variation ) {
				self::$woocommerce_service->update_product_by_id( $variation_id, $current_crawled_variation );
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
