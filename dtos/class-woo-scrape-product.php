<?php

require 'class-woo-scrape-decimal.php';

class WooScrapeProduct
{
	//TODO: private
    public int $id;
    public string $name;
    public string $description;
    public bool $has_variations;
    public string $brand;
    public string $url;
    public array $image_urls;
    public array $image_ids;
    public int $category_id;
    public WooScrapeDecimal $suggested_price;
    public WooScrapeDecimal $discounted_price;
    public DateTime $first_crawl_timestamp;
    public DateTime $latest_crawl_timestamp;
    public DateTime $item_updated_timestamp;
    public array $variations;

    /**
     * Checks if there is at least a 10% profit margin by selling at suggested price. Keeps in account shipment costs.
     * @return bool true if the product is profitable.
     */
    public function isProfitable(): bool
    {
        $supplier_price = $this->discounted_price;

        // add shipping if the product is lower than 100€
        if ($supplier_price->lower_than(100)) {
            $supplier_price->add(7);
        }

        // calculate profit
        $profit = $this->suggested_price->clone()->subtract($supplier_price);

        // the item is profitable only if the profit is at least 10% of the selling price
	    // (profit * 100 / suggested_price) > 10
        return $profit->multiply(100)->divide($this->suggested_price)->greater_than(10);
    }

    public function hasVariations(): bool
    {
        return $this->has_variations;
    }

    public function setHasVariations(bool $has_variations): void
    {
        $this->has_variations = $has_variations;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getImageUrls(): array
    {
        return $this->image_urls;
    }

    public function setImageUrls(array $image_urls): void
    {
        $this->image_urls = $image_urls;
    }

    public function getImageIds(): array
    {
        return $this->image_ids;
    }

    public function setImageIds(array $image_ids): void
    {
        $this->image_ids = $image_ids;
    }

    public function getCategoryId(): int
    {
        return $this->category_id;
    }

    public function setCategoryId(int $category_id): void
    {
        $this->category_id = $category_id;
    }

    public function getSuggestedPrice(): WooScrapeDecimal
    {
        return $this->suggested_price;
    }

    public function setSuggestedPrice(WooScrapeDecimal $suggested_price): void
    {
        $this->suggested_price = $suggested_price;
    }

    public function getDiscountedPrice(): WooScrapeDecimal
    {
        return $this->discounted_price;
    }

    public function setDiscountedPrice(WooScrapeDecimal $discounted_price): void
    {
        $this->discounted_price = $discounted_price;
    }

    public function getFirstCrawlTimestamp(): DateTime
    {
        return $this->first_crawl_timestamp;
    }

    public function setFirstCrawlTimestamp(DateTime $first_crawl_timestamp): void
    {
        $this->first_crawl_timestamp = $first_crawl_timestamp;
    }

    public function getLatestCrawlTimestamp(): DateTime
    {
        return $this->latest_crawl_timestamp;
    }

    public function setLatestCrawlTimestamp(DateTime $latest_crawl_timestamp): void
    {
        $this->latest_crawl_timestamp = $latest_crawl_timestamp;
    }

    public function getItemUpdatedTimestamp(): DateTime
    {
        return $this->item_updated_timestamp;
    }

    public function setItemUpdatedTimestamp(DateTime $item_updated_timestamp): void
    {
        $this->item_updated_timestamp = $item_updated_timestamp;
    }

    public function getVariations(): array
    {
        return $this->variations;
    }

    public function setVariations(array $variations): void
    {
        $this->variations = $variations;
    }

}
