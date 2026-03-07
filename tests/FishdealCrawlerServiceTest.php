<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( Woo_scrape_fishdeal_crawler_service::class )]
class FishdealCrawlerServiceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		// The crawler has a dependency on simple_html_dom and WP functions;
		// we only test internal pure methods here.
		require_once __DIR__ . '/../services/abstracts/class-woo-scrape-abstract-crawler-service.php';
		require_once __DIR__ . '/../services/class-woo-scrape-fishdeal-crawler-service.php';
	}

	public function test_class_extends_abstract_crawler(): void {
		$service = new Woo_scrape_fishdeal_crawler_service();
		$this->assertInstanceOf( Woo_Scrape_Abstract_Crawler_Service::class, $service );
	}

	// ── sanitize_text (via reflection) ─────────────────────────────────────

	public function test_sanitize_text_removes_italian_keywords(): void {
		$service = new Woo_scrape_fishdeal_crawler_service();
		$method  = new ReflectionMethod( Woo_scrape_fishdeal_crawler_service::class, 'sanitize_text' );
		$result  = $method->invoke( $service, 'Descrizione Some product text Caratteristiche' );

		$this->assertStringNotContainsString( 'Descrizione', $result );
		$this->assertStringNotContainsString( 'Caratteristiche', $result );
		$this->assertStringContainsString( 'Some product text', $result );
	}

	public function test_sanitize_text_removes_vedere_di_piu(): void {
		$service = new Woo_scrape_fishdeal_crawler_service();
		$method  = new ReflectionMethod( Woo_scrape_fishdeal_crawler_service::class, 'sanitize_text' );
		$result  = $method->invoke( $service, 'Text Vedere di più more text' );
		$this->assertStringNotContainsString( 'Vedere di più', $result );
	}

	public function test_sanitize_text_removes_chiudi_lista(): void {
		$service = new Woo_scrape_fishdeal_crawler_service();
		$method  = new ReflectionMethod( Woo_scrape_fishdeal_crawler_service::class, 'sanitize_text' );
		$result  = $method->invoke( $service, 'Text Chiudi lista more text' );
		$this->assertStringNotContainsString( 'Chiudi lista', $result );
	}

	public function test_sanitize_text_removes_mostra_di_piu(): void {
		$service = new Woo_scrape_fishdeal_crawler_service();
		$method  = new ReflectionMethod( Woo_scrape_fishdeal_crawler_service::class, 'sanitize_text' );
		$result  = $method->invoke( $service, 'Text Mostra di più end' );
		$this->assertStringNotContainsString( 'Mostra di più', $result );
	}

	public function test_sanitize_text_removes_riduci(): void {
		$service = new Woo_scrape_fishdeal_crawler_service();
		$method  = new ReflectionMethod( Woo_scrape_fishdeal_crawler_service::class, 'sanitize_text' );
		$result  = $method->invoke( $service, 'Text Riduci end' );
		$this->assertStringNotContainsString( 'Riduci', $result );
	}

	public function test_sanitize_text_collapses_whitespace(): void {
		$service = new Woo_scrape_fishdeal_crawler_service();
		$method  = new ReflectionMethod( Woo_scrape_fishdeal_crawler_service::class, 'sanitize_text' );
		$result  = $method->invoke( $service, "Word1   \t   Word2     Word3" );
		$this->assertSame( 'Word1 Word2 Word3', $result );
	}

	public function test_sanitize_text_empty_string(): void {
		$service = new Woo_scrape_fishdeal_crawler_service();
		$method  = new ReflectionMethod( Woo_scrape_fishdeal_crawler_service::class, 'sanitize_text' );
		$this->assertSame( '', $method->invoke( $service, '' ) );
	}

	public function test_sanitize_text_all_keywords(): void {
		$service = new Woo_scrape_fishdeal_crawler_service();
		$method  = new ReflectionMethod( Woo_scrape_fishdeal_crawler_service::class, 'sanitize_text' );

		$input  = 'Descrizione Caratteristiche Vedere di più Chiudi lista Mostra di più Riduci';
		$result = $method->invoke( $service, $input );

		// All keywords removed — verify none remain in output
		$this->assertStringNotContainsString( 'Descrizione', $result );
		$this->assertStringNotContainsString( 'Caratteristiche', $result );
		$this->assertStringNotContainsString( 'Vedere di più', $result );
		$this->assertStringNotContainsString( 'Chiudi lista', $result );
		$this->assertStringNotContainsString( 'Mostra di più', $result );
		$this->assertStringNotContainsString( 'Riduci', $result );
	}
}
