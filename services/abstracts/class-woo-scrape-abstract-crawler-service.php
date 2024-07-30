<?php

abstract class Woo_Scrape_Abstract_Crawler_Service
{
    /**
     * Crawls a list of images (if not already crawled), and returns their id
     *
     * @param array $urls array of url to crawl for images
     *
     * @return array array of image ids
     */
    public function crawl_images( array $urls ): array {
        include_once ABSPATH . 'wp-admin/includes/image.php';
        $proxy_url = get_option('woo_scrape_image_proxy_url', 'http://localhost:3000/');
	    $sleep_ms = (int) get_option('woo_scrape_crawl_delay_ms', 100);
        $ids = array();

        foreach ( $urls as $url ) {
            //TODO: search for duplicate images and avoid crawling them again

            // generate file name
            $exploded  = explode( '/', getimagesize( $url )['mime'] );
            $imagetype = end( $exploded );
            $filename  = uniqid($this->guidv4()) . '.' . $imagetype;

			// free up memory
	        unset($exploded);

            // download and save file
            $uploaddir  = wp_upload_dir();
            $uploadfile = $uploaddir['path'] . '/' . $filename;
            $contents = file_get_contents( $proxy_url . $url );
            $savefile = fopen( $uploadfile, 'w' );
            fwrite( $savefile, $contents );
            fclose( $savefile );

			// free up memory
			unset($contents);
	        unset($savefile);

            // prepare file
            $wp_filetype = wp_check_filetype( basename( $filename ), null );
            $attachment  = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title'     => $filename,
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            // save on media library
            $attach_id    = wp_insert_attachment( $attachment, $uploadfile );
            $imagenew     = get_post( $attach_id );
            $fullsizepath = get_attached_file( $imagenew->ID );
            $attach_data  = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
            wp_update_attachment_metadata( $attach_id, $attach_data );
            // add the id to the list
            $ids[] = $attach_id;

	        // free up memory
	        unset($imagenew);
	        unset($attach_data);

			// delay to avoid too many requests
	        usleep($sleep_ms * 1000);
        }

        return $ids;
    }

	/**
	 * Requests the crawling of an url to the cf workers web scraping service
	 *
	 * @param string $url the url to crawl
	 *
	 * @return string a string containing the HTML body
	 * @throws Exception
	 */
    protected function crawl(string $url ): string {
        $proxy_url = get_option('woo_scrape_crawl_proxy_url', 'http://localhost:3000/');
        $sleep_ms = (int) get_option('woo_scrape_crawl_delay_ms', 100);
	    $max_retries = 5; // Maximum number of retries
	    $retry_count = 0; // Counter for retries

	    do {
		    error_log("crawling " .$url . " as " . $proxy_url . urlencode(urlencode($url)));
		    $response = wp_remote_post( $proxy_url . $url, array(
			    'method'      => 'GET',
			    'headers'     => array( 'Accept' => 'application/json' ),
			    'timeout'     => 60, // Increase timeout to 60 seconds
			    'redirection' => 5,
			    'httpversion' => '1.0',
			    'blocking'    => true
		    ) );

		    if ( is_wp_error( $response ) ) {
			    error_log( $response->get_error_message() );
			    error_log( "Retrying in 60 seconds..." );
			    sleep(60); // Wait for 60 seconds before retrying
			    $retry_count++;
			    continue;
		    }

		    if ( $response["response"]["code"] != 200 ) {
			    error_log( "The request returned a {$response["response"]["code"]} error code, not retrying." );
			    break;
		    }

		    // delay to avoid too many requests
		    usleep($sleep_ms * 1000);

		    $body = json_decode($response["body"])->result;

		    // free up memory
		    unset($response);

		    return $body;
	    } while ($retry_count < $max_retries);

	    throw new Exception("The request failed after {$max_retries} attempts");

    }

	/**
	 * Generates a random UUIDv4
	 * @param $data
	 *
	 * @return string|void
	 * @throws \Random\RandomException
	 */
	private function guidv4($data = null) {
		// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
		$data = $data ?? random_bytes(16);
		assert(strlen($data) == 16);

		// Set version to 0100
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		// Set bits 6-7 to 10
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

		// Output the 36 character UUID.
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}
