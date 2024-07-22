<?php

require_once ABSPATH . 'wp-content/plugins/woo-scrape/jobs/class-woo-scrape-crawling-job.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/jobs/class-woo-scrape-translation-job.php';
require_once ABSPATH . 'wp-content/plugins/woo-scrape/jobs/class-woo-scrape-woocommerce-update-job.php';


class Woo_scrape_orchestrator {
	private function __construct() {
		// this class must not be instantiated
	}

	public static function orchestrate_main_job(): void {
		$crawling_job = new Woo_scrape_crawling_job();
		$crawling_job->run();

		$translation_job = new Woo_scrape_translation_job();
		$translation_job->run();

		$woocommerce_job = new Woo_scrape_woocommerce_update_job();
		$woocommerce_job->run();
	}

	/**
	 * Schedules the orchestration job to run daily at the specified time
	 *
	 * @param int $start_time the first occurrence time of the daily schedule
	 *
	 * @return void
	 */
	public static function schedule_daily( int $start_time ): void {
		// unschedule if already scheduled
		if ( wp_next_scheduled( 'woo_scrape_orchestration_job_hook' ) ) {
			self::unschedule();
		}
		error_log( "Orchestration job scheduled daily, starting at " . $start_time );
		wp_schedule_event( $start_time, 'daily', 'woo_scrape_orchestration_job_hook' );

	}

	/**
	 * Unschedules the orchestration job, it will no longer run daily
	 * @return void
	 */
	public static function unschedule(): void {
		error_log( "Orchestration job unscheduled" );
		wp_clear_scheduled_hook( 'woo_scrape_orchestration_job_hook' );
	}

}
add_action( 'woo_scrape_orchestration_job_hook', array( 'Woo_scrape_orchestrator', 'orchestrate_main_job' ) );

