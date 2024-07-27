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

    <h3>Manual Actions</h3>
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



    <h3>Logs</h3>
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
		global $wpdb;

		$per_page = 20; // Number of items to display per page
		$page     = isset( $_GET['paged'] ) ? abs( (int) $_GET['paged'] ) : 1;
		$offset   = ( $page * $per_page ) - $per_page;

		$total_query = "SELECT COUNT('id') FROM wp_woo_scrape_job_logs";
		$total       = $wpdb->get_var( $total_query );
		$num_pages   = ceil( $total / $per_page );

		$results = $wpdb->get_results( "SELECT * FROM wp_woo_scrape_job_logs ORDER BY id DESC LIMIT $offset, $per_page", OBJECT );

		if ( ! $results ) {
			echo '<tr>No logs found</tr>';
		}

		foreach ( $results as $row ) {
			echo '<tr>';
			echo '<td>' . $row->type . '</td>';
			echo '<td>' . $row->name . '</td>';
			echo '<td>' . $row->completed_counter . '</td>';
			echo '<td>' . $row->failed_counter . '</td>';
			echo '<td>' . $row->job_start_timestamp . '</td>';
			echo '<td>' . $row->job_end_timestamp . '</td>';
			echo '</tr>';
		}

		$wpdb->flush();

		echo '</table>';

		if ( $num_pages > 1 ) {
			echo '<div class="pagination">';
			for ( $i = 1; $i <= $num_pages; $i ++ ) {
				if ( $i == $page ) {
					echo '<span class="page-numbers active">' . $i . '</span>';
				} else {
					echo '<a class="page-numbers" href="?page=woo-scrape-dashboard&paged=' . $i . '">' . $i . '</a>';
				}
			}
			echo '</div>';
		}
		?>
</div>

