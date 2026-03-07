<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( Woo_Scrape_Activator::class )]
class ActivatorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		global $woo_scrape_test_options;
		$woo_scrape_test_options = array();
	}

	public function test_db_version_constant_exists(): void {
		$this->assertSame( '1.0.0', Woo_Scrape_Activator::DB_VERSION );
	}

	public function test_check_db_version_sets_version_when_missing(): void {
		global $woo_scrape_test_options;

		Woo_Scrape_Activator::check_db_version();

		$this->assertSame( '1.0.0', $woo_scrape_test_options['woo_scrape_db_version'] );
	}

	public function test_check_db_version_skips_when_current(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['woo_scrape_db_version'] = '1.0.0';

		Woo_Scrape_Activator::check_db_version();

		$this->assertSame( '1.0.0', $woo_scrape_test_options['woo_scrape_db_version'] );
	}

	public function test_check_db_version_upgrades_when_old(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['woo_scrape_db_version'] = '0.5.0';

		Woo_Scrape_Activator::check_db_version();

		$this->assertSame( '1.0.0', $woo_scrape_test_options['woo_scrape_db_version'] );
	}

	public function test_activate_sets_db_version(): void {
		global $woo_scrape_test_options;

		Woo_Scrape_Activator::activate();

		$this->assertSame( '1.0.0', $woo_scrape_test_options['woo_scrape_db_version'] );
	}
}
