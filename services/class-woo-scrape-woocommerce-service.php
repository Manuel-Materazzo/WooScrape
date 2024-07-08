<?php

class Woo_Scrape_WooCommerce_Service {

	/**
	 * Creates a variable product for the provided variations, saves,  and returns it
	 *
	 * @param array $variations
	 *
	 * @return WC_Product_Variable
	 */
	public function create_variable_product( array $variations ): WC_Product_Variable {
		$product = new WC_Product_Variable();

		$variation_options = array();

		foreach ( $variations as $variation ) {
			$variation_options[] = $variation->name;
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Variant' );
		$attribute->set_options( $variation_options );
		$attribute->set_position( 0 );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product->set_attributes( array( $attribute ) );
		$product->save();

		foreach ( $variations as $variation ) {
			$variation_name  = $variation->name;
			$variation_price = $variation->suggested_price;
			$variation       = new WC_Product_Variation();
			$variation->set_parent_id( $product->get_id() );
			$variation->set_attributes( array( 'variant' => $variation_name ) );
			$variation->set_regular_price( $variation_price );
			$variation->save();
		}

		return $product;
	}

	/**
	 * Updates a product by id, saves, and returns it.
	 * Does not set the price on variable products.
	 *
	 * @param int $product_id
	 * @param $crawled_product
	 *
	 * @return WC_Product
	 */
	public function update_product_by_id( int $product_id, $crawled_product ): WC_Product {
		$product = wc_get_product( $product_id );

		return $this->update_product( $product, $crawled_product );
	}

	/**
	 * Updates a product, saves, and returns it.
	 * Does not set the price on variable products.
	 *
	 * @param WC_Product $product
	 * @param $crawled_product
	 *
	 * @return WC_Product
	 */
	public function update_product( WC_Product $product, $crawled_product ): WC_Product {

		if ( $crawled_product->name ) {
			$product->set_name( $crawled_product->name );
		}

		if ( $crawled_product->description ) {
			$product->set_description( $crawled_product->description );
		}

		// if the quantity is not specified, set the item in stock
		if ( is_null( $crawled_product->quantity ) ) {
			$product->set_stock_status( 'instock' );
		} else {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( $crawled_product->quantity );
			$product->set_low_stock_amount( 0 );
		}

		if ( $crawled_product->weight ) {
			$product->set_weight( $crawled_product->weight );
		}

		if ( $crawled_product->length ) {
			$product->set_weight( $crawled_product->length );
		}

		if ( $crawled_product->width ) {
			$product->set_weight( $crawled_product->width );
		}

		if ( $crawled_product->height ) {
			$product->set_weight( $crawled_product->height );
		}

		if ( $crawled_product->image_ids ) {
			$product_images = json_decode( $crawled_product->image_ids );
			$product->set_image_id( $product_images[0] );
			array_shift( $product_images );
			$product->set_gallery_image_ids( $product_images );
		}

//				TODO: $product->set_category_ids( array( 19 ) );

		//TODO: update other things

		// if the product has no variations, set the price
		if ( ! $crawled_product->has_variations ) {

            // get options from settings
            $price_multiplier = get_option('price_multiplier');
            $provider_shipping_addendum = get_option('provider_shipping_addendum');

            $profitable_price = new WooScrapeDecimal( $crawled_product->discounted_price );
			$profitable_price->add( $provider_shipping_addendum )->multiply( $price_multiplier );

			$suggested_price = new WooScrapeDecimal( $crawled_product->suggested_price ?? 0 );

			// if the product has a suggested price and it's greater than the profitable price
			if ( $suggested_price->greater_than( $profitable_price ) ) {
				// display a discount
				$product->set_regular_price( $suggested_price );
				$product->set_sale_price( $profitable_price );
			} else {
				// otherwise, just the price
				$product->set_regular_price( $profitable_price );
			}

		}

		$product->save();

		return $product;
	}
}
