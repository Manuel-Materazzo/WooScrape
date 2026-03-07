<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( Woo_Scrape_Abstract_Crawler_Service::class )]
class AbstractCrawlerServiceTest extends TestCase {

	private object $crawler;

	protected function setUp(): void {
		parent::setUp();

		require_once __DIR__ . '/../services/abstracts/class-woo-scrape-abstract-crawler-service.php';

		// Create a concrete subclass for testing the abstract
		$this->crawler = new class extends Woo_Scrape_Abstract_Crawler_Service {
			// expose protected crawl for testing
			public function public_crawl( string $url ): string {
				return $this->crawl( $url );
			}
		};
	}

	// ── guidv4 (via reflection) ────────────────────────────────────────────

	public function test_guidv4_format(): void {
		$method = new ReflectionMethod( Woo_Scrape_Abstract_Crawler_Service::class, 'guidv4' );
		$uuid   = $method->invoke( $this->crawler );

		$this->assertMatchesRegularExpression(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
			$uuid
		);
	}

	public function test_guidv4_uniqueness(): void {
		$method = new ReflectionMethod( Woo_Scrape_Abstract_Crawler_Service::class, 'guidv4' );

		$uuids = array();
		for ( $i = 0; $i < 100; $i++ ) {
			$uuids[] = $method->invoke( $this->crawler );
		}

		$this->assertCount( 100, array_unique( $uuids ) );
	}

	public function test_guidv4_with_custom_data(): void {
		$method = new ReflectionMethod( Woo_Scrape_Abstract_Crawler_Service::class, 'guidv4' );
		$data   = str_repeat( "\x01", 16 );

		$uuid1 = $method->invoke( $this->crawler, $data );
		$uuid2 = $method->invoke( $this->crawler, $data );

		$this->assertSame( $uuid1, $uuid2 );
	}

	public function test_guidv4_version_nibble(): void {
		$method = new ReflectionMethod( Woo_Scrape_Abstract_Crawler_Service::class, 'guidv4' );

		for ( $i = 0; $i < 20; $i++ ) {
			$uuid = $method->invoke( $this->crawler );
			$this->assertSame( '4', $uuid[14] );
			$this->assertContains( $uuid[19], array( '8', '9', 'a', 'b' ) );
		}
	}

	public function test_guidv4_returns_36_char_string(): void {
		$method = new ReflectionMethod( Woo_Scrape_Abstract_Crawler_Service::class, 'guidv4' );
		$uuid   = $method->invoke( $this->crawler );
		$this->assertSame( 36, strlen( $uuid ) );
	}
}
