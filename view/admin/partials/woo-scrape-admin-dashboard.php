<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/Manuel-Materazzo
 * @since      1.0.0
 *
 * @package    Woo_Scrape
 * @subpackage Woo_Scrape/admin/partials
 */
?>
<div class="wrap">
    <h2>Woo Scrape - Dashboard</h2>

    <hr class="solid" id="toast-hanger">

    <h3>Stats</h3>
    <table class="table-style">
        <tr>
            <th>Products</th>
            <th>To Crawl</th>
            <th>Crawled Once</th>
            <th>Not Crawled Once</th>
            <th>Untranslated Product Names</th>
            <th>Translated Product Names</th>
            <th>Untranslated Variation Names</th>
            <th>Translated Variation Names</th>
            <th>Untranslated Specifications</th>
            <th>Translated Specifications</th>
            <th>Untranslated Descriptions</th>
            <th>Translated Descriptions</th>
        </tr>
		<?php
		global $wpdb;

		$products_table = $wpdb->prefix . 'woo_scrape_products';
		$variations_table = $wpdb->prefix . 'woo_scrape_variations';

		$results = $wpdb->get_results( "SELECT
    (SELECT COUNT(*) FROM $products_table) AS products,
    (SELECT COUNT(*) FROM $products_table WHERE DATE(`latest_crawl_timestamp`) = CURDATE() and has_variations is not false) AS to_crawl,
    (SELECT COUNT(*) FROM $products_table WHERE has_variations IS NOT NULL) AS crawled_once,
    (SELECT COUNT(*) FROM $products_table WHERE has_variations IS NULL) AS not_crawled_once,
    (SELECT COUNT(*) FROM $products_table WHERE translated_name IS NULL) AS untranslated_product_names,
    (SELECT COUNT(*) FROM $products_table WHERE translated_name IS NOT NULL) AS translated_product_names,
    (SELECT COUNT(*) FROM $variations_table WHERE translated_name IS NULL) AS untranslated_variation_names,
    (SELECT COUNT(*) FROM $variations_table WHERE translated_name IS NOT NULL) AS translated_variation_names,
    (SELECT COUNT(*) FROM $products_table WHERE translated_specifications IS NULL) AS untranslated_specifications,
    (SELECT COUNT(*) FROM $products_table WHERE translated_specifications IS NOT NULL) AS translated_specifications,
    (SELECT COUNT(*) FROM $products_table WHERE translated_description IS NULL) AS untranslated_descriptions,
    (SELECT COUNT(*) FROM $products_table WHERE translated_description IS NOT NULL) AS translated_descriptions", OBJECT );

		foreach ( $results as $row ) {
			echo '<tr>';
			echo '<td>' . esc_html( $row->products ) . '</td>';
			echo '<td>' . esc_html( $row->to_crawl ) . '</td>';
			echo '<td>' . esc_html( $row->crawled_once ) . '</td>';
			echo '<td>' . esc_html( $row->not_crawled_once ) . '</td>';
			echo '<td>' . esc_html( $row->untranslated_product_names ) . '</td>';
			echo '<td>' . esc_html( $row->translated_product_names ) . '</td>';
			echo '<td>' . esc_html( $row->untranslated_variation_names ) . '</td>';
			echo '<td>' . esc_html( $row->translated_variation_names ) . '</td>';
			echo '<td>' . esc_html( $row->untranslated_specifications ) . '</td>';
			echo '<td>' . esc_html( $row->translated_specifications ) . '</td>';
			echo '<td>' . esc_html( $row->untranslated_descriptions ) . '</td>';
			echo '<td>' . esc_html( $row->translated_descriptions ) . '</td>';
			echo '</tr>';
		}
		?>
    </table>
    <h3>Manual Actions</h3>
    <button class="accordion">Start Jobs</button>
    <div class="panel">
        <div class="m-1">
            <div class="col-2 d-inline-block">
                <button id="run-orchestrator-job-button" class="button button-primary">Run Orchestrated Job</button>
            </div>
            <div class="col-9 d-inline-block description">
                Manually trigger cron schedule to run all jobs in order
            </div>
        </div>
        <div class="m-1">
            <div class="col-2 d-inline-block">
                <button id="run-crawling-job-button" class="button">Run Crawling Job</button>
            </div>
            <div class="col-9 d-inline-block description">
                Manually crawl remote website and store products on DB
            </div>
        </div>
        <div class="m-1">
            <div class="col-2 d-inline-block">
                <button id="run-product-crawling-job-button" class="button">Run Product Crawling Job</button>
            </div>
            <div class="col-9 d-inline-block description">
                Manually crawl products stored on DB
            </div>
        </div>
        <div class="m-1">
            <div class="col-2 d-inline-block">
                <button id="run-translate-job-button" class="button">Run Translate Job</button>
            </div>
            <div class="col-9 d-inline-block description">
                Manually start the DB product translation process.
            </div>
        </div>
        <div class="m-1">
            <div class="col-2 d-inline-block">
                <button id="run-wordpress-job-button" class="button">Run Wordpress Update Job</button>
            </div>
            <div class="col-9 d-inline-block description">
                Manually update wordpress products from DB stored ones.
            </div>
        </div>
    </div>

    <button class="accordion">Crawl one product</button>
    <div class="panel p-1">
        <p class="description">
            sku of the product to crawl
        </p>
        <input type="text" id="manual-crawl-sku" name="manual-crawl-sku"/>
        <button id="run-single-product-job" class="button">Crawl and update</button>
    </div>

    <h3>Logs <button id="clear-job-logs-button" class="button" style="margin-left: 10px;">Clear Logs</button></h3>
    <table class="table-style">
        <tr>
            <th>Type</th>
            <th>Name</th>
            <th>Completed Counter</th>
            <th>Failed Counter</th>
            <th>Job Start Timestamp</th>
            <th>Job End Timestamp</th>
        </tr>
		<?php
		$per_page = 20; // Number of items to display per page
		$page     = isset( $_GET['paged'] ) ? abs( (int) $_GET['paged'] ) : 1;
		$offset   = ( $page * $per_page ) - $per_page;

		$job_logs_table = $wpdb->prefix . 'woo_scrape_job_logs';
		$total       = $wpdb->get_var( "SELECT COUNT('id') FROM $job_logs_table" );
		$num_pages   = ceil( $total / $per_page );

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $job_logs_table ORDER BY id DESC LIMIT %d, %d", $offset, $per_page ), OBJECT );

		if ( ! $results ) {
			echo '<tr><td colspan="6">No logs found</td></tr>';
		}

		foreach ( $results as $row ) {
			echo '<tr>';
			echo '<td>' . esc_html( $row->type ) . '</td>';
			echo '<td>' . esc_html( $row->name ) . '</td>';
			echo '<td>' . esc_html( $row->completed_counter ) . '</td>';
			echo '<td>' . esc_html( $row->failed_counter ) . '</td>';
			echo '<td>' . esc_html( $row->job_start_timestamp ) . '</td>';
			echo '<td>' . esc_html( $row->job_end_timestamp ) . '</td>';
			echo '</tr>';
		}

		$wpdb->flush();

		echo '</table>';

		if ( $num_pages > 1 ) {
			echo '<div class="pagination">';
			echo '<a class="page-numbers" href="?page=woo-scrape-dashboard&paged=1"><<</a>';
			$start = max( 1, $page - 3 );
			$end   = min( $num_pages, $page + 3 );
			for ( $i = $start; $i <= $end; $i ++ ) {
				if ( $i == $page ) {
					echo '<span class="page-numbers active">' . $i . '</span>';
				} else {
					echo '<a class="page-numbers" href="?page=woo-scrape-dashboard&paged=' . $i . '">' . $i . '</a>';
				}
			}
			echo '<span class="page-numbers">...</span>';
			echo '<a class="page-numbers" href="?page=woo-scrape-dashboard&paged=' . $num_pages . '">' . $num_pages . '</a>';
			echo '<a class="page-numbers" href="?page=woo-scrape-dashboard&paged=' . $num_pages . '">>></a>';
			echo '</div>';
		}

		?>
</div>

