<?php

require_once ABSPATH . 'wp-content/plugins/woo-scrape/dtos/enums/class-woo-scrape-job-type-enum.php';

class Woo_Scrape_Job_Log_Service {
	private static string $date_format = 'Y-m-d H:i:s';
	private static string $job_logs_table_name = 'woo_scrape_job_logs';


	public function job_start( JobType $job_type, string $job_name = '' ): void {
		global $wpdb;
		$now = date( self::$date_format );

		$wpdb->insert(
			$wpdb->prefix . self::$job_logs_table_name,
			array(
				'job_start_timestamp' => $now,
				'type'                => $job_type->value,
				'name'                => $job_name,
				'completed_counter'   => 0,
				'failed_counter'      => 0,
			)
		);
		$wpdb->flush();
	}

	public function increase_completed_counter( JobType $job_type, int $quantity = 1 ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::$job_logs_table_name;

		$wpdb->query(
			"UPDATE {$table} SET completed_counter = completed_counter + {$quantity}
             		WHERE type = '{$job_type->value}' ORDER BY id DESC LIMIT 1;"
		);
		$wpdb->flush();
	}

	public function increase_failed_counter( JobType $job_type, int $quantity = 1 ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::$job_logs_table_name;

		$wpdb->query(
			"UPDATE {$table} SET failed_counter = failed_counter + {$quantity}
             		WHERE type = '{$job_type->value}' ORDER BY id DESC LIMIT 1;"
		);
		$wpdb->flush();
	}

	public function job_end( JobType $job_type ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::$job_logs_table_name;

		$now = date( self::$date_format );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET job_end_timestamp = %s WHERE type = '{$job_type->value}' ORDER BY id DESC LIMIT 1;",
				$now
			)
		);
		$wpdb->flush();
	}

}
