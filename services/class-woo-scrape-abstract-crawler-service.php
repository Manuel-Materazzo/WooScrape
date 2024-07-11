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
        $proxy_url = get_option('proxy_url', 'http://localhost:3000/');
        $ids = array();

        foreach ( $urls as $url ) {
            //TODO: search for duplicate images and avoid crawling them again

            // generate file name
            $exploded  = explode( '/', getimagesize( $url )['mime'] );
            $imagetype = end( $exploded );
            $uniq_name = date( 'dmY' ) . '' . (int) microtime( true );
            $filename  = $uniq_name . '.' . $imagetype;

            // download and save file
            $uploaddir  = wp_upload_dir();
            $uploadfile = $uploaddir['path'] . '/' . $filename;
            //TODO: real implementation
            $contents = file_get_contents( $proxy_url . $url );
            $savefile = fopen( $uploadfile, 'w' );
            fwrite( $savefile, $contents );
            fclose( $savefile );

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
        }

        return $ids;
    }

    /**
     * Requests the crawling of an url to the cf workers web scraping service
     *
     * @param string $url the url to crawl
     *
     * @return string a string containing the HTML body
     */
    protected function crawl(string $url ): string {
        $proxy_url = get_option('proxy_url', 'http://localhost:3000/');
        $response = wp_remote_post( $proxy_url . $url, array(
            'method'      => 'GET',
            'headers'     => array( 'Accept' => 'application/json' ),
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true
        ) );

        return json_decode($response["body"])->result;
    }
}
