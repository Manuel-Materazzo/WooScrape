<?php

require_once ABSPATH . 'wp-content/plugins/woo-scrape/jobs/class-woo-scrape-crawling-job.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/jobs/class-woo-scrape-translation-job.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/jobs/class-woo-scrape-woocommerce-update-job.php';


class Woo_scrape_orchestrator {
	private function __construct() {
	}

	public static function orchestrate_main_job(): void {
		$crawling_job = new Woo_scrape_crawling_job();
		$crawling_job->run();

		//TODO: translate job

		$woocommerce_job = new Woo_scrape_woocommerce_update_job();
		$woocommerce_job->run();
	}
}
