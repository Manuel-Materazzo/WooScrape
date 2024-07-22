<?php

class Woo_scrape_setting_utils {

	public static function register_boolean_true( string $group, string $name ): void {
		register_setting(
			$group,
			$name,
			array(
				'type'    => 'boolean',
				'default' => true,
			)
		);
	}

	public static function register_boolean_false( string $group, string $name ): void {
		register_setting(
			$group,
			$name,
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);
	}

	public static function register_string( string $group, string $name, string $default ): void {
		register_setting(
			$group,
			$name,
			array(
				'type'    => 'text',
				'default' => $default,
			)
		);
	}

	public static function get_schedule_time( $hour, $minute ): float|int {
		$gmt_offset          = intval( get_option( 'gmt_offset' ) );
		$schedule_time_local = strtotime( 'today' ) + HOUR_IN_SECONDS * absint( $hour ) + MINUTE_IN_SECONDS * absint( $minute );
		if ( $gmt_offset < 0 ) {
			$schedule_time_local -= DAY_IN_SECONDS;
		}
		$schedule_time = $schedule_time_local - HOUR_IN_SECONDS * $gmt_offset;
		if ( $schedule_time < time() ) {
			$schedule_time += DAY_IN_SECONDS;
		}

		return $schedule_time;
	}
}
