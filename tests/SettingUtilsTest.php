<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( Woo_scrape_setting_utils::class )]
class SettingUtilsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		global $woo_scrape_test_options;
		$woo_scrape_test_options = array();
	}

	// ── register helpers ───────────────────────────────────────────────────
	// These are thin wrappers around register_setting; we just verify
	// they don't throw and are callable.

	public function test_register_boolean_true(): void {
		// Should not throw
		Woo_scrape_setting_utils::register_boolean_true( 'test_group', 'test_option' );
		$this->assertTrue( true );
	}

	public function test_register_boolean_false(): void {
		Woo_scrape_setting_utils::register_boolean_false( 'test_group', 'test_option' );
		$this->assertTrue( true );
	}

	public function test_register_string(): void {
		Woo_scrape_setting_utils::register_string( 'test_group', 'test_option', 'default_value' );
		$this->assertTrue( true );
	}

	// ── get_schedule_time() ────────────────────────────────────────────────

	public function test_schedule_time_returns_future_timestamp(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['gmt_offset'] = 0;

		$result = Woo_scrape_setting_utils::get_schedule_time( 23, 59 );

		// The result should be a positive integer (Unix timestamp)
		$this->assertIsNumeric( $result );
		// Should be >= current time (scheduled in the future or at current second)
		$this->assertGreaterThanOrEqual( time() - 1, $result );
	}

	public function test_schedule_time_with_positive_gmt_offset(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['gmt_offset'] = 2;

		$result = Woo_scrape_setting_utils::get_schedule_time( 12, 0 );

		$this->assertIsNumeric( $result );
		$this->assertGreaterThan( 0, $result );
	}

	public function test_schedule_time_with_negative_gmt_offset(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['gmt_offset'] = -5;

		$result = Woo_scrape_setting_utils::get_schedule_time( 6, 30 );

		$this->assertIsNumeric( $result );
		$this->assertGreaterThan( 0, $result );
	}

	public function test_schedule_time_midnight(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['gmt_offset'] = 0;

		$result = Woo_scrape_setting_utils::get_schedule_time( 0, 0 );

		$this->assertIsNumeric( $result );
		// Should be within the next 24 hours + 1 day buffer
		$this->assertLessThanOrEqual( time() + DAY_IN_SECONDS + 1, $result );
	}

	public function test_schedule_time_wraps_to_next_day_if_past(): void {
		global $woo_scrape_test_options;
		$woo_scrape_test_options['gmt_offset'] = 0;

		// Use hour 0, minute 0 — likely in the past for UTC
		$result = Woo_scrape_setting_utils::get_schedule_time( 0, 0 );

		// Result must be >= now (the code adds DAY_IN_SECONDS if in the past)
		$this->assertGreaterThanOrEqual( time() - 1, $result );
	}
}
