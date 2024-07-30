<?php
add_action( 'add_meta_boxes', 'add_custom_meta_box' );
add_action( 'save_post', 'save_custom_meta_box', 10, 2 );

function add_custom_meta_box() {
	add_meta_box(
		'custom_product_links', // ID, should be a string
		'Custom Product Links', // Meta Box Title
		'custom_meta_box_markup', // Your call back function, this is where your form field will go
		'product', // The post type you want this to show up on, can be post, page, or custom post type
		'side', // The placement of your meta box, can be normal or side
		'high' // The priority in which this will be displayed
	);
}

function custom_meta_box_markup( $post ) {
	global $wpdb;
	$products_table_name = $wpdb->prefix . 'woo_scrape_products';
	$sku_prefix          = get_option( 'woo_scrape_sku_prefix' );

	// get current page product sku
	$product     = wc_get_product( $post->ID );
	$product_sku = $product->get_sku();

	// extract product id from sku (just remove the prefix)
	$product_id = str_replace( $sku_prefix, '', $product_sku );

	// get the url from DB (if the id extraction is successful)
	if ( is_numeric($product_id) ) {
		$product_on_db = $wpdb->get_results(
			"SELECT url FROM $products_table_name WHERE id = $product_id ORDER BY id LIMIT 1"
		);
		$product_url   = $product_on_db[0]->url;
	}

	?>
    <div>
        <p>
            <label>Original website url</label><br>
            <a href="<?php echo $product_url ?? '#'; ?>"
               target="_blank"><?php _e( 'Visit website', 'woocommerce' ); ?></a>
        </p>
    </div>
	<?php
}

function save_custom_meta_box( $post_id, $post ) {
	// This function is required for WordPress to save the data from your custom meta box,
	// even if you don't have any fields that need to be saved.
}
