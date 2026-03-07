<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( WooScrapeProduct::class )]
class WooScrapeProductTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		global $woo_scrape_test_options;
		$woo_scrape_test_options = array();
	}

	// ── isProfitable() ─────────────────────────────────────────────────────

	public function test_is_profitable_when_discounted_lower_than_suggested(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['woo_scrape_provider_free_shipping_threshold'] = 100;
		$woo_scrape_test_options['woo_scrape_provider_shipping_addendum']       = 7;

		$product = new WooScrapeProduct();
		$product->setSuggestedPrice( new WooScrapeDecimal( '50.00' ) );
		$product->setDiscountedPrice( new WooScrapeDecimal( '30.00' ) );

		// 30 + 7 = 37 < 50, so profitable
		$this->assertTrue( $product->isProfitable() );
	}

	public function test_is_not_profitable_when_discounted_equals_suggested(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['woo_scrape_provider_free_shipping_threshold'] = 100;
		$woo_scrape_test_options['woo_scrape_provider_shipping_addendum']       = 0;

		$product = new WooScrapeProduct();
		$product->setSuggestedPrice( new WooScrapeDecimal( '50.00' ) );
		$product->setDiscountedPrice( new WooScrapeDecimal( '50.00' ) );

		$this->assertFalse( $product->isProfitable() );
	}

	public function test_is_not_profitable_when_shipping_makes_it_unprofitable(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['woo_scrape_provider_free_shipping_threshold'] = 100;
		$woo_scrape_test_options['woo_scrape_provider_shipping_addendum']       = 7;

		$product = new WooScrapeProduct();
		$product->setSuggestedPrice( new WooScrapeDecimal( '35.00' ) );
		$product->setDiscountedPrice( new WooScrapeDecimal( '30.00' ) );

		// 30 + 7 = 37 > 35, not profitable
		$this->assertFalse( $product->isProfitable() );
	}

	public function test_is_profitable_free_shipping_over_threshold(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['woo_scrape_provider_free_shipping_threshold'] = 100;
		$woo_scrape_test_options['woo_scrape_provider_shipping_addendum']       = 7;

		$product = new WooScrapeProduct();
		$product->setSuggestedPrice( new WooScrapeDecimal( '150.00' ) );
		$product->setDiscountedPrice( new WooScrapeDecimal( '120.00' ) );

		// 120 >= 100, no shipping added. 120 < 150, profitable
		$this->assertTrue( $product->isProfitable() );
	}

	public function test_is_not_profitable_free_shipping_but_price_too_close(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['woo_scrape_provider_free_shipping_threshold'] = 100;
		$woo_scrape_test_options['woo_scrape_provider_shipping_addendum']       = 7;

		$product = new WooScrapeProduct();
		$product->setSuggestedPrice( new WooScrapeDecimal( '105.00' ) );
		$product->setDiscountedPrice( new WooScrapeDecimal( '105.00' ) );

		// No shipping (>=100), but 105 is not < 105
		$this->assertFalse( $product->isProfitable() );
	}

	public function test_is_profitable_uses_default_options(): void {
		// No options set → defaults: threshold=100, addendum=7
		$product = new WooScrapeProduct();
		$product->setSuggestedPrice( new WooScrapeDecimal( '80.00' ) );
		$product->setDiscountedPrice( new WooScrapeDecimal( '50.00' ) );

		// 50 + 7 = 57 < 80 → profitable
		$this->assertTrue( $product->isProfitable() );
	}

	// ── setTranslatedField() ───────────────────────────────────────────────

	public function test_set_translated_field_name(): void {
		$product = new WooScrapeProduct();
		$product->setTranslatedField( 'name', 'Translated Name' );
		$this->assertSame( 'Translated Name', $product->getTranslatedName() );
	}

	public function test_set_translated_field_specifications(): void {
		$product = new WooScrapeProduct();
		$product->setTranslatedField( 'specifications', 'Translated Spec' );
		$this->assertSame( 'Translated Spec', $product->getTranslatedSpecification() );
	}

	public function test_set_translated_field_description(): void {
		$product = new WooScrapeProduct();
		$product->setTranslatedField( 'description', 'Translated Desc' );
		$this->assertSame( 'Translated Desc', $product->getTranslatedDescription() );
	}

	public function test_set_translated_field_unknown_does_nothing(): void {
		$product = new WooScrapeProduct();
		$product->setTranslatedField( 'nonexistent', 'value' );
		$this->assertSame( '', $product->getTranslatedName() );
		$this->assertSame( '', $product->getTranslatedSpecification() );
		$this->assertSame( '', $product->getTranslatedDescription() );
	}

	// ── Getters and setters ────────────────────────────────────────────────

	public function test_id_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setId( 42 );
		$this->assertSame( 42, $product->getId() );
	}

	public function test_name_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setName( 'Test Product' );
		$this->assertSame( 'Test Product', $product->getName() );
	}

	public function test_description_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setDescription( 'A description' );
		$this->assertSame( 'A description', $product->getDescription() );
	}

	public function test_specification_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setSpecification( 'Some specs' );
		$this->assertSame( 'Some specs', $product->getSpecification() );
	}

	public function test_brand_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setBrand( 'BrandX' );
		$this->assertSame( 'BrandX', $product->getBrand() );
	}

	public function test_url_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setUrl( 'https://example.com/product' );
		$this->assertSame( 'https://example.com/product', $product->getUrl() );
	}

	public function test_image_urls_getter_setter(): void {
		$product = new WooScrapeProduct();
		$urls    = array( 'https://img.com/1.jpg', 'https://img.com/2.jpg' );
		$product->setImageUrls( $urls );
		$this->assertSame( $urls, $product->getImageUrls() );
	}

	public function test_image_ids_getter_setter(): void {
		$product = new WooScrapeProduct();
		$ids     = array( 10, 20, 30 );
		$product->setImageIds( $ids );
		$this->assertSame( $ids, $product->getImageIds() );
	}

	public function test_category_id_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setCategoryId( 5 );
		$this->assertSame( 5, $product->getCategoryId() );
	}

	public function test_quantity_getter_setter(): void {
		$product = new WooScrapeProduct();
		$this->assertNull( $product->getQuantity() );
		$product->set_quantity( 100 );
		$this->assertSame( 100, $product->getQuantity() );
	}

	public function test_has_variations_getter_setter(): void {
		$product = new WooScrapeProduct();
		$this->assertNull( $product->hasVariations() );
		$product->setHasVariations( true );
		$this->assertTrue( $product->hasVariations() );
		$product->setHasVariations( false );
		$this->assertFalse( $product->hasVariations() );
	}

	public function test_suggested_price_getter_setter(): void {
		$product = new WooScrapeProduct();
		$this->assertNull( $product->getSuggestedPrice() );
		$price = new WooScrapeDecimal( '49.99' );
		$product->setSuggestedPrice( $price );
		$this->assertSame( $price, $product->getSuggestedPrice() );
	}

	public function test_discounted_price_getter_setter(): void {
		$product = new WooScrapeProduct();
		$this->assertNull( $product->getDiscountedPrice() );
		$price = new WooScrapeDecimal( '29.99' );
		$product->setDiscountedPrice( $price );
		$this->assertSame( $price, $product->getDiscountedPrice() );
	}

	public function test_timestamps_getter_setters(): void {
		$product = new WooScrapeProduct();
		$now     = new DateTime();

		$product->setFirstCrawlTimestamp( $now );
		$this->assertSame( $now, $product->getFirstCrawlTimestamp() );

		$product->setLatestCrawlTimestamp( $now );
		$this->assertSame( $now, $product->getLatestCrawlTimestamp() );

		$product->setItemUpdatedTimestamp( $now );
		$this->assertSame( $now, $product->getItemUpdatedTimestamp() );
	}

	public function test_variations_getter_setter(): void {
		$product    = new WooScrapeProduct();
		$variations = array( new WooScrapeProduct(), new WooScrapeProduct() );
		$product->setVariations( $variations );
		$this->assertCount( 2, $product->getVariations() );
	}

	public function test_translated_name_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setTranslatedName( 'Translated' );
		$this->assertSame( 'Translated', $product->getTranslatedName() );
	}

	public function test_translated_specification_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setTranslatedSpecification( 'Spec' );
		$this->assertSame( 'Spec', $product->getTranslatedSpecification() );
	}

	public function test_translated_description_getter_setter(): void {
		$product = new WooScrapeProduct();
		$product->setTranslatedDescription( 'Desc' );
		$this->assertSame( 'Desc', $product->getTranslatedDescription() );
	}

	// ── Default values ─────────────────────────────────────────────────────

	public function test_default_values(): void {
		$product = new WooScrapeProduct();
		$this->assertSame( '', $product->getName() );
		$this->assertSame( '', $product->getDescription() );
		$this->assertSame( '', $product->getSpecification() );
		$this->assertSame( '', $product->getTranslatedName() );
		$this->assertSame( '', $product->getTranslatedSpecification() );
		$this->assertSame( '', $product->getTranslatedDescription() );
		$this->assertSame( '', $product->getBrand() );
		$this->assertSame( '', $product->getUrl() );
		$this->assertSame( array(), $product->getImageUrls() );
		$this->assertSame( array(), $product->getImageIds() );
		$this->assertNull( $product->getQuantity() );
		$this->assertNull( $product->hasVariations() );
		$this->assertNull( $product->getSuggestedPrice() );
		$this->assertNull( $product->getDiscountedPrice() );
		$this->assertNull( $product->getFirstCrawlTimestamp() );
		$this->assertNull( $product->getLatestCrawlTimestamp() );
		$this->assertNull( $product->getItemUpdatedTimestamp() );
	}
}
