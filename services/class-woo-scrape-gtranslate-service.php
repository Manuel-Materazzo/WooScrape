<?php
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/abstracts/class-woo-scrape-abstract-translator-service.php';

class Woo_scrape_gtranslate_service extends Woo_Scrape_Abstract_Translator_Service{

	/**
	 * Translates the given text to the specified language code, ignoring the provided ignored_text in the process
	 * @param string $text
	 * @param string $lang_code
	 * @param string $ignored_text
	 *
	 * @return string
	 */
	public function translate( string $text, string $lang_code, string $ignored_text = '' ): string {
		$proxy_url  = get_option( 'translation_proxy_url', 'http://localhost:3000/' );
		$script_url = get_option( 'google_script_url', '' );

		// replace translation ignore with placeholder
		if ( $ignored_text ) {
			$text = str_replace( $ignored_text, '[]', $text );
		}

		$url = $script_url . "&to_lang={$lang_code}&text={$text}";

		$response = wp_remote_post( $proxy_url . $url, array(
			'method'      => 'GET',
			'headers'     => array(
				'Accept'        => 'application/json'
			),
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true
		) );

		if(is_wp_error($response)){
			error_log($response->get_error_message());
		}

		$translated_text = $response["body"];

		// put back the ignored translation
		if ( $ignored_text ) {
			$translated_text = str_replace( '[]', $ignored_text, $translated_text );
		}

		return $translated_text;
	}

}
