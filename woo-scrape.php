<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Manuel-Materazzo
 * @since             1.0.0
 * @package           Woo_Scrape
 *
 * @wordpress-plugin
 * Plugin Name:       WooScrape
 * Plugin URI:        https://github.com/Manuel-Materazzo
 * Description:       Scrape woocommerce products from various websites
 * Version:           1.0.0
 * Author:            Manuel
 * Author URI:        https://github.com/Manuel-Materazzo/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-scrape
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOO_SCRAPE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-scrape-activator.php
 */
function activate_woo_scrape() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-scrape-activator.php';
	Woo_Scrape_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-scrape-deactivator.php
 */
function deactivate_woo_scrape() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-scrape-deactivator.php';
	Woo_Scrape_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woo_scrape' );
register_deactivation_hook( __FILE__, 'deactivate_woo_scrape' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woo-scrape.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_scrape() {

	$plugin = new Woo_Scrape();
	$plugin->run();

}
run_woo_scrape();
