<?php

require_once 'class-woo-scrape-decimal.php';

class WooScrapeProduct {
	//TODO: private
	public int $id;
	public string $name = '';
	public string $specification = '';
	public string $description = '';
	public string $translated_name = '';
	public string $translated_specification = '';
	public string $translated_description = '';
	public ?bool $has_variations = null;
	public string $brand = '';
	public string $url = '';
	public array $image_urls = array();
	public array $image_ids = array();
	public int $category_id;
	public ?int $quantity = null;
	public ?WooScrapeDecimal $suggested_price = null;
	public ?WooScrapeDecimal $discounted_price = null;
	public ?DateTime $first_crawl_timestamp = null;
	public ?DateTime $latest_crawl_timestamp = null;
	public ?DateTime $item_updated_timestamp = null;
	public array $variations;

	/**
	 * Checks if there is at least a 10% profit margin by selling at suggested price. Keeps in account shipment costs.
	 * @return bool true if the product is profitable.
	 */
	public function isProfitable(): bool {
		$supplier_price = $this->discounted_price->clone();

		// get options from settings
		$provider_free_shipping_threshold = get_option( 'woo_scrape_provider_free_shipping_threshold', 100 );
		$provider_shipping_addendum       = get_option( 'woo_scrape_provider_shipping_addendum', 7 );

		// add shipping if the product is lower than 100€
		if ( $supplier_price->lower_than( $provider_free_shipping_threshold ) ) {
			$supplier_price->add( $provider_shipping_addendum );
		}

		// calculate profit
//        $profit = $this->suggested_price->clone()->subtract($supplier_price);

		// the item is profitable only if the profit is at least 10% of the selling price
		// (profit * 100 / suggested_price) > 10
//        return $profit->multiply(100)->divide($this->suggested_price)->greater_than(10);
		return $supplier_price->lower_than( $this->suggested_price );
	}

	/**
	 * Sets the value of the translated_$field property
	 *
	 * @param string $field
	 * @param string $translated_value
	 *
	 * @return void
	 */
	public function setTranslatedField( string $field, string $translated_value ): void {
		switch ( $field ) {
			case 'name':
				$this->translated_name = $translated_value;
				break;
			case 'specifications':
				$this->translated_specification = $translated_value;
				break;
			case 'description':
				$this->translated_description = $translated_value;
				break;
			default:
				break;
		}
	}

	public function getTranslatedName(): string {
		return $this->translated_name;
	}

	public function setTranslatedName( string $translated_name ): void {
		$this->translated_name = $translated_name;
	}

	public function getTranslatedSpecification(): string {
		return $this->translated_specification;
	}

	public function setTranslatedSpecification( string $translated_specification ): void {
		$this->translated_specification = $translated_specification;
	}

	public function getTranslatedDescription(): string {
		return $this->translated_description;
	}

	public function setTranslatedDescription( string $translated_description ): void {
		$this->translated_description = $translated_description;
	}

	public function getSpecification(): string {
		return $this->specification;
	}

	public function setSpecification( string $specification ): void {
		$this->specification = $specification;
	}

	public function getQuantity(): int|null {
		return $this->quantity;
	}

	public function set_quantity( int $quantity ): void {
		$this->quantity = $quantity;
	}

	public function hasVariations(): bool|null {
		return $this->has_variations;
	}

	public function setHasVariations( bool $has_variations ): void {
		$this->has_variations = $has_variations;
	}

	public function getId(): int {
		return $this->id;
	}

	public function setId( int $id ): void {
		$this->id = $id;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName( string $name ): void {
		$this->name = $name;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function setDescription( string $description ): void {
		$this->description = $description;
	}

	public function getBrand(): string {
		return $this->brand;
	}

	public function setBrand( string $brand ): void {
		$this->brand = $brand;
	}

	public function getUrl(): string {
		return $this->url;
	}

	public function setUrl( string $url ): void {
		$this->url = $url;
	}

	public function getImageUrls(): array {
		return $this->image_urls;
	}

	public function setImageUrls( array $image_urls ): void {
		$this->image_urls = $image_urls;
	}

	public function getImageIds(): array {
		return $this->image_ids;
	}

	public function setImageIds( array $image_ids ): void {
		$this->image_ids = $image_ids;
	}

	public function getCategoryId(): int {
		return $this->category_id;
	}

	public function setCategoryId( int $category_id ): void {
		$this->category_id = $category_id;
	}

	public function getSuggestedPrice(): WooScrapeDecimal|null {
		return $this->suggested_price;
	}

	public function setSuggestedPrice( WooScrapeDecimal $suggested_price ): void {
		$this->suggested_price = $suggested_price;
	}

	public function getDiscountedPrice(): WooScrapeDecimal|null {
		return $this->discounted_price;
	}

	public function setDiscountedPrice( WooScrapeDecimal $discounted_price ): void {
		$this->discounted_price = $discounted_price;
	}

	public function getFirstCrawlTimestamp(): DateTime|null {
		return $this->first_crawl_timestamp;
	}

	public function setFirstCrawlTimestamp( DateTime $first_crawl_timestamp ): void {
		$this->first_crawl_timestamp = $first_crawl_timestamp;
	}

	public function getLatestCrawlTimestamp(): DateTime|null {
		return $this->latest_crawl_timestamp;
	}

	public function setLatestCrawlTimestamp( DateTime $latest_crawl_timestamp ): void {
		$this->latest_crawl_timestamp = $latest_crawl_timestamp;
	}

	public function getItemUpdatedTimestamp(): DateTime|null {
		return $this->item_updated_timestamp;
	}

	public function setItemUpdatedTimestamp( DateTime $item_updated_timestamp ): void {
		$this->item_updated_timestamp = $item_updated_timestamp;
	}

	public function getVariations(): array {
		return $this->variations;
	}

	public function setVariations( array $variations ): void {
		$this->variations = $variations;
	}

}
