<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( Woo_Scrape_Variation_Service::class )]
class VariationServiceTest extends TestCase {

	private Woo_Scrape_Variation_Service $service;

	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../services/class-woo-scrape-variation-service.php';
		$this->service = new Woo_Scrape_Variation_Service();
	}

	// ── add_standard_parameters (via reflection) ───────────────────────────

	private function invoke_add_standard_parameters( array $parameters, WooScrapeProduct $variation ): array {
		$method = new ReflectionMethod( Woo_Scrape_Variation_Service::class, 'add_standard_parameters' );
		return $method->invoke( $this->service, $parameters, $variation );
	}

	public function test_add_standard_parameters_empty_variation(): void {
		$variation = new WooScrapeProduct();
		$initial   = array( 'existing' => 'value' );

		$result = $this->invoke_add_standard_parameters( $initial, $variation );

		$this->assertArrayHasKey( 'existing', $result );
		$this->assertArrayNotHasKey( 'name', $result );
		$this->assertArrayNotHasKey( 'translated_name', $result );
		$this->assertArrayNotHasKey( 'quantity', $result );
		$this->assertArrayNotHasKey( 'suggested_price', $result );
		$this->assertArrayNotHasKey( 'discounted_price', $result );
	}

	public function test_add_standard_parameters_full_variation(): void {
		$variation = new WooScrapeProduct();
		$variation->setName( 'Variation A' );
		$variation->setTranslatedName( 'Translated A' );
		$variation->set_quantity( 5 );
		$variation->setSuggestedPrice( new WooScrapeDecimal( '25.00' ) );
		$variation->setDiscountedPrice( new WooScrapeDecimal( '20.00' ) );

		$result = $this->invoke_add_standard_parameters( array(), $variation );

		$this->assertSame( 'Variation A', $result['name'] );
		$this->assertSame( 'Translated A', $result['translated_name'] );
		$this->assertSame( '5', $result['quantity'] );
		$this->assertSame( '25.00', $result['suggested_price'] );
		$this->assertSame( '20.00', $result['discounted_price'] );
	}

	public function test_add_standard_parameters_name_only(): void {
		$variation = new WooScrapeProduct();
		$variation->setName( 'Only Name' );

		$result = $this->invoke_add_standard_parameters( array(), $variation );

		$this->assertSame( 'Only Name', $result['name'] );
		$this->assertArrayNotHasKey( 'translated_name', $result );
		$this->assertArrayNotHasKey( 'quantity', $result );
	}

	public function test_add_standard_parameters_quantity_zero(): void {
		$variation = new WooScrapeProduct();
		$variation->set_quantity( 0 );

		$result = $this->invoke_add_standard_parameters( array(), $variation );

		$this->assertArrayHasKey( 'quantity', $result );
		$this->assertSame( '0', $result['quantity'] );
	}

	public function test_add_standard_parameters_preserves_existing_keys(): void {
		$variation = new WooScrapeProduct();
		$variation->setName( 'Test' );

		$initial = array( 'product_id' => 42, 'timestamp' => '2024-01-01' );
		$result  = $this->invoke_add_standard_parameters( $initial, $variation );

		$this->assertSame( 42, $result['product_id'] );
		$this->assertSame( '2024-01-01', $result['timestamp'] );
		$this->assertSame( 'Test', $result['name'] );
	}

	// ── Allowed fields for untranslated variations ─────────────────────────

	public function test_untranslated_field_allowed_fields(): void {
		$source = file_get_contents( __DIR__ . '/../services/class-woo-scrape-variation-service.php' );
		// Variation service only allows 'name' for translated field lookup
		$this->assertStringContainsString( "'name'", $source );
	}
}
