<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/Manuel-Materazzo
 * @since      1.0.0
 *
 * @package    Woo_Scrape
 * @subpackage Woo_Scrape/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Scrape
 * @subpackage Woo_Scrape/admin
 * @author     Manuel <madonnagamer@gmail.com>
 */
class Woo_Scrape_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     *
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    function run_my_job(): void
    {
        include plugin_dir_path(__FILE__) . '../../jobs/class-woo-scrape-category-crawling-job.php';
        error_log("job started");
        $job = new Woo_scrape_category_crawling_job();
        $job->run();
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    public function display_plugin_dashboard(): void
    {
        include 'partials/woo-scrape-admin-dashboard.php';
    }

    public function display_plugin_settings(): void
    {
        include 'partials/woo-scrape-admin-settings.php';
    }

    public function add_menus(): void
    {
        add_menu_page(
            'Woo Scrape - Dashboard',// page title
            'Woo Scrape',// menu title
            'manage_options',// capability of user that can see the menu
            'woo-scrape-dashboard',// menu slug
            [$this, 'display_plugin_dashboard']
        );

        add_submenu_page(
            'woo-scrape-dashboard', // parent slug
            'Woo Scrape - Settings',// page title
            'Settings',// menu title
            'manage_options',// capability of user that can see the menu
            'woo-scrape-settings',// menu slug
            [$this, 'display_plugin_settings']
        );

        $this->register_settings();
    }

    public function register_settings(): void
    {
        // provider
        register_setting(
            'woo-scrape-provider-settings-group',
            'provider_free_shipping_threshold',
            array(
                'type' => 'number',
                'default' => 100,
            )
        );
        register_setting(
            'woo-scrape-provider-settings-group',
            'provider_shipping_addendum',
            array(
                'type' => 'number',
                'default' => 7,
            )
        );
        register_setting(
            'woo-scrape-provider-settings-group',
            'currency_conversion_multiplier',
            array(
                'type' => 'number',
                'default' => 1,
            )
        );

        // product import
        register_setting(
            'woo-scrape-import-settings-group',
            'price_multiplier',
            array(
                'type' => 'number',
//                'sanitize_callback' => 'sanitize_text_field',
                'default' => 1.2,
            )
        );
        register_setting(
            'woo-scrape-import-settings-group',
            'sku_prefix',
            array(
                'type' => 'text',
                'default' => 'sku-1-',
            )
        );
    }


    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Woo_Scrape_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Woo_Scrape_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-scrape-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Woo_Scrape_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Woo_Scrape_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-scrape-admin.js', array('jquery'), $this->version, false);

    }

}
