<?php

abstract class Woo_Scrape_Abstract_Translator_Service {
	abstract public function translate( string $text, string $lang_code, string $ignored_text = '' ): string;
}
