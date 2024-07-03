<?php


require ABSPATH . 'wp-content/plugins/woo-scrape/dtos/class-woo-scrape-product.php';

class Woo_scrape_fishdeal_dom_utils {
    
    /**
     * Parses an HTML and extracts the number of pages to crawl
     * @param simple_html_dom $html the scraped page
     * @return int number of pages to crawl
     */
    public static function extract_pages(simple_html_dom $html): int
    {
        $page_elements = $html->find('.SC_DealsBlock-pagination .Link');
        if (!$page_elements) {
            error_log("pagination element not found");
            return 0;
        }
        return (int)end($page_elements)->plaintext;
    }

    /**
     * Parses an HTML and extracts profitable products
     * @param simple_html_dom $html the scraped page
     * @return array an array of profitable products
     */
    public static function extract_products(simple_html_dom $html, int $category_id): array
    {
        $product_elements = $html->find('.SC_DealTile a');
        $products = array();

        foreach ($product_elements as $product_element) {

            $product = self::parse_category_product($product_element);

            if ($product->isProfitable()) {
                $product->setCategoryId($category_id);
                $products[] = $product;
            }
        }

        return $products;
    }


    /**
     * Parses a product object from an html product element from the category page
     *
     * @param simple_html_dom_node $product_element the html product element to be parsed
     *
     * @return WooScrapeProduct the parsed product
     */
    private static function parse_category_product(simple_html_dom_node $product_element ): WooScrapeProduct
    {
        // Extract infos
        $name_element = $product_element->find('.SC_DealTile-title', 0);
        $brand_element = $product_element->find('.SC_Manufacturer img', 0);
        $image_element = $product_element->find('img.SC_DealTile-image[srcset]', 0);

        $suggested_price_element = $product_element->find('.SC_DealTile-value', 0);
        $discounted_price_element = $product_element->find('.SC_DealTile-price', 0);

        // if the product is not discounted, get the single price
        if (is_null($suggested_price_element) && is_null($discounted_price_element)) {
            $price = $product_element->find('.SC_DealTile-valuePrice span', 0);
            $suggested_price_element = $price;
            $discounted_price_element = $price;
        }

        // compose object
        $product = new WooScrapeProduct();
        $product->setName(trim($name_element->innertext()));
        $product->setUrl($product_element->href);
        $product->setBrand($brand_element->alt);
        $product->setImageUrls(array($image_element->src));
        $product->setSuggestedPrice(new WooScrapeDecimal($suggested_price_element->innertext()));
        $product->setDiscountedPrice(new WooScrapeDecimal($discounted_price_element->innertext()));

        return $product;
    }
    
}
