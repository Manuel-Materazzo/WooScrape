<?php
require_once ABSPATH . 'wp-content/plugins/woo-scrape/services/abstracts/class-woo-scrape-abstract-translator-service.php';

class Woo_scrape_deepl_service extends Woo_Scrape_Abstract_Translator_Service {

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
		$proxy_url     = get_option( 'translation_proxy_url', 'http://localhost:3000/' );
		$api_key       = get_option( 'deepl_api_key', '' );
		$free_endpoint = get_option( 'deepl_api_free', true );

		// add tags for translation ignores
		if ( $ignored_text ) {
			$text = str_replace( $ignored_text, '<ignored>' . $ignored_text . '</ignored>', $text );
		}

		if ( $free_endpoint ) {
			$url = 'https://api-free.deepl.com/v2/translate';
		} else {
			$url = 'https://api.deepl.com/v2/translate';
		}

		if ( $proxy_url ) {
			$response = $this->proxied_call( $url, $proxy_url, $api_key, $lang_code, $text );
		} else {
			$response = $this->direct_call( $url, $api_key, $lang_code, $text );
		}

		if ( is_wp_error( $response ) ) {
			error_log( $response->get_error_message() );
		}

		if ( $response["response"]["code"] != 200 ) {
			error_log( "The request returned a {$response["response"]["code"]} error code" );
			throw new Exception( "The request returned a {$response["response"]["code"]} error code" );
		}

		$translated_text = json_decode( $response["body"] )->translations[0]->text;

		// remove translation ignores tags
		if ( $ignored_text ) {
			$translated_text = str_replace( '<ignored>', '', $translated_text );
			$translated_text = str_replace( '</ignored>', '', $translated_text );
		}

		return $translated_text;
	}

	private function direct_call( string $url, string $api_key, string $lang_code, string $text ): array|WP_Error {
		return wp_remote_post( $url, array(
			'method'      => 'POST',
			'headers'     => array(
				'Authorization' => 'DeepL-Auth-Key ' . $api_key,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/x-www-form-urlencoded;charset=UTF-8'
			),
			'body'        => 'tag_handling=xml&ignore_tags=ignored&target_lang=' . $lang_code . '&text=' . $text,
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true
		) );
	}

	private function proxied_call( string $url, string $proxy_url, string $api_key, string $lang_code, string $text ): array|WP_Error {

		$headers = json_encode( array(
			'Authorization' => 'DeepL-Auth-Key ' . $api_key,
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/x-www-form-urlencoded;charset=UTF-8'
		) );

		$body = 'tag_handling=xml&ignore_tags=ignored&target_lang=' . $lang_code . '&text=' . $text;

		return wp_remote_post( $proxy_url, array(
			'method'      => 'GET',
			'headers'     => array(
				'Host'           => $url,
				'requestHeaders' => $headers,
				'requestBody'    => $body,
				'requestMethod'  => 'POST'
			),
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true
		) );
	}

}
