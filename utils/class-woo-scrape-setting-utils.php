<?php

class Woo_scrape_setting_utils {
	private function __construct() {}

	public static function register_boolean_true(string $group, string $name) {
		register_setting(
			$group,
			$name,
			array(
				'type' => 'boolean',
				'default' => true,
			)
		);
	}
}
