<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( Woo_scrape_gtranslate_service::class )]
class GtranslateServiceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../services/abstracts/class-woo-scrape-abstract-translator-service.php';
		require_once __DIR__ . '/../services/class-woo-scrape-gtranslate-service.php';
	}

	public function test_class_extends_abstract_translator(): void {
		$service = new Woo_scrape_gtranslate_service();
		$this->assertInstanceOf( Woo_Scrape_Abstract_Translator_Service::class, $service );
	}

	public function test_translate_method_exists(): void {
		$this->assertTrue( method_exists( Woo_scrape_gtranslate_service::class, 'translate' ) );
	}

	public function test_translate_method_signature(): void {
		$method = new ReflectionMethod( Woo_scrape_gtranslate_service::class, 'translate' );
		$params = $method->getParameters();

		$this->assertCount( 3, $params );
		$this->assertSame( 'text', $params[0]->getName() );
		$this->assertSame( 'lang_code', $params[1]->getName() );
		$this->assertSame( 'ignored_text', $params[2]->getName() );
		$this->assertTrue( $params[2]->isDefaultValueAvailable() );
		$this->assertSame( '', $params[2]->getDefaultValue() );
	}
}
