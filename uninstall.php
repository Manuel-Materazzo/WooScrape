<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://github.com/Manuel-Materazzo
 * @since      1.0.0
 *
 * @package    Woo_Scrape
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if the user wants to keep data
if ( get_option( 'woo_scrape_keep_data_on_uninstall' ) ) {
	// Clear scheduled hooks only
	wp_clear_scheduled_hook( 'woo_scrape_orchestration_job_hook' );
	return;
}

global $wpdb;

// Drop custom tables
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woo_scrape_variations" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woo_scrape_products" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woo_scrape_pages" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woo_scrape_job_logs" );

// Delete all plugin options
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'woo_scrape_%'" );

// Clear scheduled hooks
wp_clear_scheduled_hook( 'woo_scrape_orchestration_job_hook' );
