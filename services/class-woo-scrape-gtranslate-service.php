<?php
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/abstracts/class-woo-scrape-abstract-translator-service.php';

class Woo_scrape_gtranslate_service extends Woo_Scrape_Abstract_Translator_Service {

	/**
	 * Translates the given text to the specified language code, ignoring the provided ignored_text in the process
	 *
	 * @param string $text
	 * @param string $lang_code
	 * @param string $ignored_text
	 *
	 * @return string
	 * @throws Exception
	 */
	public function translate( string $text, string $lang_code, string $ignored_text = '' ): string {
		$proxy_url  = get_option( 'woo_scrape_translation_proxy_url', 'http://localhost:3000/' );
		$script_url = get_option( 'woo_scrape_google_script_url', '' );

		// replace translation ignore with placeholder
		if ( $ignored_text ) {
			$text = str_replace( $ignored_text, '[]', $text );
		}

		// urlencode to avoid weird characters
		$text = urlencode( $text );

		$url = $script_url . "?to_lang={$lang_code}&string={$text}";

		if ( $proxy_url ) {
			$response = $this->proxied_call( $url, $proxy_url );
		} else {
			$response = $this->direct_call( $url );
		}

		if ( is_wp_error( $response ) ) {
			error_log( $response->get_error_message() );
		}

		if ( $response["response"]["code"] != 200 ) {
			error_log( "The request returned a {$response["response"]["code"]} error code" );
			throw new Exception( "The request returned a {$response["response"]["code"]} error code" );
		}

		$translated_text = $response["body"];

		// put back the ignored translation
		if ( $ignored_text ) {
			$translated_text = str_replace( '[]', $ignored_text, $translated_text );
		}

		return $translated_text;
	}

	private function direct_call( string $url ): array|WP_Error {
		return wp_remote_post( $url, array(
			'method'      => 'GET',
			'headers'     => array(
				'Accept' => 'application/json'
			),
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true
		) );
	}

	private function proxied_call( string $url, string $proxy_url ): array|WP_Error {

		return wp_remote_post( $proxy_url, array(
			'method'      => 'GET',
			'headers'     => array(
				'Host'           => $url,
				'requestMethod'  => 'GET'
			),
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true
		) );
	}

}
