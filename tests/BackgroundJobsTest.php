<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

// Stub transient functions for lock testing
global $woo_scrape_test_transients;
$woo_scrape_test_transients = array();

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		global $woo_scrape_test_transients;
		return $woo_scrape_test_transients[ $transient ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		global $woo_scrape_test_transients;
		$woo_scrape_test_transients[ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		global $woo_scrape_test_transients;
		unset( $woo_scrape_test_transients[ $transient ] );
		return true;
	}
}

#[CoversClass( Woo_Scrape_Background_Jobs::class )]
class BackgroundJobsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		global $woo_scrape_test_transients;
		$woo_scrape_test_transients = array();

		require_once __DIR__ . '/../jobs/class-woo-scrape-background-jobs.php';
	}

	// ── Lock mechanism tests (via reflection) ──────────────────────────────

	private function invoke_acquire_lock( string $key, int $ttl = 21600 ): bool {
		$method = new ReflectionMethod( Woo_Scrape_Background_Jobs::class, 'acquire_lock' );
		return $method->invoke( null, $key, $ttl );
	}

	private function invoke_release_lock( string $key ): void {
		$method = new ReflectionMethod( Woo_Scrape_Background_Jobs::class, 'release_lock' );
		$method->invoke( null, $key );
	}

	public function test_acquire_lock_succeeds_when_not_held(): void {
		$this->assertTrue( $this->invoke_acquire_lock( 'test_lock' ) );
	}

	public function test_acquire_lock_fails_when_already_held(): void {
		$this->invoke_acquire_lock( 'test_lock' );
		$this->assertFalse( $this->invoke_acquire_lock( 'test_lock' ) );
	}

	public function test_release_lock_allows_reacquire(): void {
		$this->invoke_acquire_lock( 'test_lock' );
		$this->invoke_release_lock( 'test_lock' );
		$this->assertTrue( $this->invoke_acquire_lock( 'test_lock' ) );
	}

	public function test_independent_locks(): void {
		$this->assertTrue( $this->invoke_acquire_lock( 'lock_a' ) );
		$this->assertTrue( $this->invoke_acquire_lock( 'lock_b' ) );
		$this->assertFalse( $this->invoke_acquire_lock( 'lock_a' ) );
		$this->assertFalse( $this->invoke_acquire_lock( 'lock_b' ) );
	}

	public function test_release_one_lock_does_not_affect_other(): void {
		$this->invoke_acquire_lock( 'lock_a' );
		$this->invoke_acquire_lock( 'lock_b' );
		$this->invoke_release_lock( 'lock_a' );

		$this->assertTrue( $this->invoke_acquire_lock( 'lock_a' ) );
		$this->assertFalse( $this->invoke_acquire_lock( 'lock_b' ) );
	}

	// ── Structure tests ────────────────────────────────────────────────────

	public function test_run_methods_exist(): void {
		$this->assertTrue( method_exists( Woo_Scrape_Background_Jobs::class, 'run_orchestrator' ) );
		$this->assertTrue( method_exists( Woo_Scrape_Background_Jobs::class, 'run_crawling' ) );
		$this->assertTrue( method_exists( Woo_Scrape_Background_Jobs::class, 'run_product_crawling' ) );
		$this->assertTrue( method_exists( Woo_Scrape_Background_Jobs::class, 'run_translate' ) );
		$this->assertTrue( method_exists( Woo_Scrape_Background_Jobs::class, 'run_wordpress_update' ) );
		$this->assertTrue( method_exists( Woo_Scrape_Background_Jobs::class, 'run_single_product' ) );
	}

	public function test_run_methods_are_static(): void {
		$methods = array(
			'run_orchestrator',
			'run_crawling',
			'run_product_crawling',
			'run_translate',
			'run_wordpress_update',
			'run_single_product',
		);
		foreach ( $methods as $name ) {
			$method = new ReflectionMethod( Woo_Scrape_Background_Jobs::class, $name );
			$this->assertTrue( $method->isStatic(), "$name should be static" );
			$this->assertTrue( $method->isPublic(), "$name should be public" );
		}
	}
}
