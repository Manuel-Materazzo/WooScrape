<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

// Stub WordPress hook registration functions if not already defined
if ( ! function_exists( 'add_action' ) ) {
	/** @var array Global collector for registered actions */
	global $wp_test_actions;
	$wp_test_actions = array();

	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		global $wp_test_actions;
		$wp_test_actions[] = array(
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/** @var array Global collector for registered filters */
	global $wp_test_filters;
	$wp_test_filters = array();

	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		global $wp_test_filters;
		$wp_test_filters[] = array(
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}

#[CoversClass( Woo_Scrape_Loader::class )]
class LoaderTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		global $wp_test_actions, $wp_test_filters;
		$wp_test_actions = array();
		$wp_test_filters = array();
	}

	public function test_add_action_and_run(): void {
		global $wp_test_actions;

		$loader    = new Woo_Scrape_Loader();
		$component = new stdClass();

		$loader->add_action( 'init', $component, 'my_callback' );
		$loader->run();

		$this->assertCount( 1, $wp_test_actions );
		$this->assertSame( 'init', $wp_test_actions[0]['hook'] );
		$this->assertSame( array( $component, 'my_callback' ), $wp_test_actions[0]['callback'] );
		$this->assertSame( 10, $wp_test_actions[0]['priority'] );
		$this->assertSame( 1, $wp_test_actions[0]['accepted_args'] );
	}

	public function test_add_filter_and_run(): void {
		global $wp_test_filters;

		$loader    = new Woo_Scrape_Loader();
		$component = new stdClass();

		$loader->add_filter( 'the_content', $component, 'filter_content', 20, 2 );
		$loader->run();

		$this->assertCount( 1, $wp_test_filters );
		$this->assertSame( 'the_content', $wp_test_filters[0]['hook'] );
		$this->assertSame( 20, $wp_test_filters[0]['priority'] );
		$this->assertSame( 2, $wp_test_filters[0]['accepted_args'] );
	}

	public function test_multiple_actions(): void {
		global $wp_test_actions;

		$loader = new Woo_Scrape_Loader();
		$comp   = new stdClass();

		$loader->add_action( 'hook_a', $comp, 'callback_a' );
		$loader->add_action( 'hook_b', $comp, 'callback_b' );
		$loader->add_action( 'hook_c', $comp, 'callback_c' );
		$loader->run();

		$this->assertCount( 3, $wp_test_actions );
		$this->assertSame( 'hook_a', $wp_test_actions[0]['hook'] );
		$this->assertSame( 'hook_b', $wp_test_actions[1]['hook'] );
		$this->assertSame( 'hook_c', $wp_test_actions[2]['hook'] );
	}

	public function test_mixed_actions_and_filters(): void {
		global $wp_test_actions, $wp_test_filters;

		$loader = new Woo_Scrape_Loader();
		$comp   = new stdClass();

		$loader->add_action( 'init', $comp, 'init_cb' );
		$loader->add_filter( 'the_title', $comp, 'title_cb' );
		$loader->add_action( 'wp_loaded', $comp, 'loaded_cb' );
		$loader->run();

		$this->assertCount( 2, $wp_test_actions );
		$this->assertCount( 1, $wp_test_filters );
	}

	public function test_default_priority_and_args(): void {
		global $wp_test_actions;

		$loader = new Woo_Scrape_Loader();
		$comp   = new stdClass();

		$loader->add_action( 'test_hook', $comp, 'test_cb' );
		$loader->run();

		$this->assertSame( 10, $wp_test_actions[0]['priority'] );
		$this->assertSame( 1, $wp_test_actions[0]['accepted_args'] );
	}

	public function test_custom_priority_and_args(): void {
		global $wp_test_actions;

		$loader = new Woo_Scrape_Loader();
		$comp   = new stdClass();

		$loader->add_action( 'test_hook', $comp, 'test_cb', 99, 3 );
		$loader->run();

		$this->assertSame( 99, $wp_test_actions[0]['priority'] );
		$this->assertSame( 3, $wp_test_actions[0]['accepted_args'] );
	}

	public function test_no_hooks_registered(): void {
		global $wp_test_actions, $wp_test_filters;

		$loader = new Woo_Scrape_Loader();
		$loader->run();

		$this->assertCount( 0, $wp_test_actions );
		$this->assertCount( 0, $wp_test_filters );
	}
}
