<?php
/**
 * PHPUnit bootstrap for WooScrape.
 *
 * Provides WordPress/WooCommerce stubs so unit tests can run without a full
 * WordPress installation.
 */

// Composer autoloader (for PHPUnit itself)
require_once __DIR__ . '/../vendor/autoload.php';

// ── WordPress constants ────────────────────────────────────────────────────────
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

// ── WordPress function stubs ───────────────────────────────────────────────────
// These minimal stubs allow the production code to be included and tested
// without a running WordPress installation.

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) {
		return rtrim( $value, '/\\' ) . '/';
	}
}

/**
 * In-memory option store used by get_option / update_option stubs.
 */
global $woo_scrape_test_options;
$woo_scrape_test_options = array();

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $woo_scrape_test_options;
		return array_key_exists( $option, $woo_scrape_test_options )
			? $woo_scrape_test_options[ $option ]
			: $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		global $woo_scrape_test_options;
		$woo_scrape_test_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'register_setting' ) ) {
	function register_setting( $option_group, $option_name, $args = array() ) {
		// no-op in tests
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		return time();
	}
}

if ( ! function_exists( 'error_log' ) ) {
	// built-in error_log exists, no need to override
}

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $sql ) {
		return array();
	}
}

if ( ! function_exists( 'version_compare' ) ) {
	// built-in, no stub needed
}

// ── Minimal $wpdb stub ─────────────────────────────────────────────────────────
class WooScrapeTestWpdb {
	public string $prefix = 'wp_';
	public function get_charset_collate(): string { return ''; }
	public function prepare( $query, ...$args ) { return $query; }
	public function get_results( $query ) { return array(); }
	public function insert( $table, $data, $format = null ) { return 1; }
	public function update( $table, $data, $where, $format = null, $where_format = null ) { return 1; }
	public function query( $query ) { return true; }
	public function flush() {}
}

global $wpdb;
if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
	$wpdb = new WooScrapeTestWpdb();
}

// ── Load production code ───────────────────────────────────────────────────────
require_once __DIR__ . '/../dtos/enums/class-woo-scrape-job-type-enum.php';
require_once __DIR__ . '/../dtos/class-woo-scrape-decimal.php';
require_once __DIR__ . '/../dtos/class-woo-scrape-product.php';
require_once __DIR__ . '/../includes/class-woo-scrape-loader.php';
require_once __DIR__ . '/../includes/class-woo-scrape-activator.php';
require_once __DIR__ . '/../utils/class-woo-scrape-setting-utils.php';
