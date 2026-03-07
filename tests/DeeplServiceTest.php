<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( Woo_scrape_deepl_service::class )]
class DeeplServiceTest extends TestCase {

	private Woo_scrape_deepl_service $service;

	protected function setUp(): void {
		parent::setUp();
		global $woo_scrape_test_options;
		$woo_scrape_test_options = array();

		// Stub wp_remote_post so we can test without network
		if ( ! function_exists( 'wp_remote_post' ) ) {
			// will be defined below via the global override
		}

		require_once __DIR__ . '/../services/abstracts/class-woo-scrape-abstract-translator-service.php';
		require_once __DIR__ . '/../services/class-woo-scrape-deepl-service.php';

		$this->service = new Woo_scrape_deepl_service();
	}

	// ── filter_artifacts (via reflection) ──────────────────────────────────

	public function test_filter_artifacts_removes_encoding_garbage(): void {
		$method = new ReflectionMethod( Woo_scrape_deepl_service::class, 'filter_artifacts' );
		$result = $method->invoke( $this->service, 'Hello Ã¢ world â€ test â end' );
		$this->assertSame( "Hello   world ' test   end", $result );
	}

	public function test_filter_artifacts_no_artifacts(): void {
		$method = new ReflectionMethod( Woo_scrape_deepl_service::class, 'filter_artifacts' );
		$input  = 'Clean text without artifacts';
		$this->assertSame( $input, $method->invoke( $this->service, $input ) );
	}

	// ── replace_special_characters (via reflection) ────────────────────────

	public function test_replace_special_characters(): void {
		$method = new ReflectionMethod( Woo_scrape_deepl_service::class, 'replace_special_characters' );
		$this->assertSame( 'Size: 10x20 ~5', $method->invoke( $this->service, 'Size: 10×20 ±5' ) );
	}

	public function test_replace_special_characters_no_special(): void {
		$method = new ReflectionMethod( Woo_scrape_deepl_service::class, 'replace_special_characters' );
		$input  = 'Normal text 123';
		$this->assertSame( $input, $method->invoke( $this->service, $input ) );
	}

	public function test_replace_special_characters_multiple_occurrences(): void {
		$method = new ReflectionMethod( Woo_scrape_deepl_service::class, 'replace_special_characters' );
		$this->assertSame( '5x5x5 ~1~2', $method->invoke( $this->service, '5×5×5 ±1±2' ) );
	}
}
