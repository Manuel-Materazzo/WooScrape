<?php

class Woo_Scrape_Job_Log_Service
{
    private static string $date_format = 'Y-m-d H:i:s';
    private static $job_logs_table_name = 'woo_scrape_job_logs';


    public function job_start(): void
    {
        global $wpdb;
        $now = date(self::$date_format);

        $wpdb->insert(
            $wpdb->prefix . self::$job_logs_table_name,
            array(
                'job_start_timestamp' => $now,
                'categories_crawled' => 0,
                'categories_crawl_fails' => 0,
                'products_crawled' => 0,
                'products_crawl_fails' => 0,
                'image_crawls' => 0,
                'woo_out_of_stock_products' => 0,
                'woo_out_of_stock_products_fails' => 0,
                'woo_updated_products' => 0,
                'woo_updated_products_fails' => 0,
                'woo_created_products' => 0,
                'woo_created_products_fails' => 0,
            )
        );
    }

    public function increase_crawled_categories(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET categories_crawled = categories_crawled + {$quantity} order by id desc limit 1;");
    }

    public function increase_failed_categories(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET categories_crawl_fails = categories_crawl_fails + {$quantity} order by id desc limit 1;");
    }


    public function categories_crawl_end(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;
        $now = date(self::$date_format);

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET categories_crawl_end_timestamp = %d order by id desc limit 1;",
                $now
            )
        );
    }

    public function increase_crawled_products(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET products_crawled = products_crawled + {$quantity} order by id desc limit 1;");
    }

    public function increase_crawled_images(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET image_crawls = image_crawls + {$quantity} order by id desc limit 1;");
    }

    public function increase_failed_products(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET products_crawl_fails = products_crawl_fails + {$quantity} order by id desc limit 1;");
    }

    public function product_crawl_end(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;
        $now = date(self::$date_format);

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET products_crawl_end_timestamp = %d order by id desc limit 1;",
                $now
            )
        );
    }

    public function increase_out_of_stock_products(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET woo_out_of_stock_products = woo_out_of_stock_products + {$quantity} order by id desc limit 1;");
    }

    public function increase_failed_out_of_stock_products(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET woo_out_of_stock_products_fails = woo_out_of_stock_products_fails + {$quantity} order by id desc limit 1;");
    }

    public function woocommerce_out_of_stock_end(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;
        $now = date(self::$date_format);

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET woo_out_of_stock_end_timestamp = %d order by id desc limit 1;",
                $now
            )
        );
    }

    public function increase_woocommerce_updated_products(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET woo_updated_products = woo_updated_products + {$quantity} order by id desc limit 1;");
    }

    public function increase_failed_woocommerce_updated_products(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET woo_updated_products_fails = woo_updated_products_fails + {$quantity} order by id desc limit 1;");
    }

    public function increase_woocommerce_created_products(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET woo_created_products = woo_created_products + {$quantity} order by id desc limit 1;");
    }

    public function increase_failed_woocommerce_created_products(int $quantity = 1): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;

        $wpdb->query("UPDATE {$table} SET SET woo_created_products_fails = woo_created_products_fails + {$quantity} order by id desc limit 1;");
    }

    public function job_end(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::$job_logs_table_name;
        $now = date(self::$date_format);

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET job_end_timestamp = %d order by id desc limit 1;",
                $now
            )
        );
    }
}