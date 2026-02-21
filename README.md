# WooScrape

A WordPress plugin that automatically scrapes products from external e-commerce websites, translates product content into your target language, and syncs everything to your local WooCommerce store — including prices, images, variations, and stock levels.

Currently supports [Fishdeal/PescaPromo](https://pescapromo.it) as a scraping provider, with an extensible architecture for adding more.

## Features

- **Automated product crawling** — Scrape entire category pages, extract products, deduplicate, and store them locally
- **Product detail enrichment** — Crawl individual product pages for descriptions, specifications, images, and variation data
- **Translation** — Translate product titles, descriptions, and specifications via [DeepL](https://www.deepl.com/) or Google Translate (via Google Apps Script)
- **WooCommerce sync** — Create and update WooCommerce products (simple and variable), including:
  - Prices with configurable markup multiplier
  - Product images (downloaded and saved to the WordPress media library)
  - Product variations with per-variant pricing and stock
  - Automatic out-of-stock management for products no longer found on the source site
- **Profitability filtering** — Only imports products where there's a margin between the discounted (buy) price and the suggested (sell) price, accounting for shipping costs
- **Scheduling** — Run the full pipeline daily via WordPress cron at a configurable time
- **Proxy support** — Route crawling, image downloads, and translation API calls through a configurable proxy (e.g., [Fluid-Proxy](https://github.com/Manuel-Materazzo/Fluid-Proxy))
- **Currency conversion** — Apply a conversion multiplier for cross-currency imports
- **Admin dashboard** — View crawling/translation stats, job logs, and trigger any job manually from the WordPress admin
- **Single product re-crawl** — Re-crawl and update a single product by SKU directly from the dashboard

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 8.1+ (uses enums, typed properties, union types)
- PHP `bcmath` extension (for precise decimal arithmetic)

## Installation

1. Clone or download this repository into your WordPress plugins directory:
   ```
   wp-content/plugins/woo-scrape/
   ```
2. Activate the plugin from the **Plugins** page in WordPress admin.
3. Navigate to **Woo Scrape → Settings** to configure the plugin.

## Configuration

Settings are organized into five tabs under **Woo Scrape → Settings**:

### Schedule

| Setting | Description | Default |
|---|---|---|
| Schedule enabled | Automatically run the full orchestrated job daily | Off |
| Schedule time | Hour and minute for daily execution (in your WordPress timezone) | 01:00 |

### Provider

| Setting | Description | Default |
|---|---|---|
| Free shipping threshold | Cart value above which shipping is free on the source site | 100 |
| Shipping addendum | Shipping cost added to products below the threshold (before markup) | 7 |
| Currency conversion multiplier | Multiply all prices by this value (e.g., for EUR → USD conversion) | 1 |

### Scraping

| Setting | Description | Default |
|---|---|---|
| Proxy URL | Proxy for crawling requests — the target URL is appended | `http://localhost:3000/` |
| Images proxy URL | Proxy for image download requests | `http://localhost:3000/` |
| Crawl delay (ms) | Wait time between consecutive crawl requests | 100 |
| Crawl images if changed | Re-download images if the source URLs changed | Off |

### Translation

| Setting | Description | Default |
|---|---|---|
| Target language code | Language to translate into (e.g., `en`, `it`, `de`) | `en` |
| Google script URL | URL of a Google Apps Script for Google Translate calls | — |
| DeepL API key | Your DeepL API key (free or paid) | — |
| DeepL free endpoint | Use the free DeepL API endpoint | On |
| Proxy URL | Proxy for translation API requests | `http://localhost:3000/` |
| Translation delay (ms) | Wait time between translation requests | 50 |
| Automatic title/description/specification translation | Auto-translate each field during scheduled runs | On |
| Use Google Translate for titles/specifications/descriptions | Use Google Translate instead of DeepL per field (saves DeepL credits) | Titles & specs: On, Descriptions: Off |
| Ignore brands during translation | Prevent brand names from being translated | On |

### Product Import

| Setting | Description | Default |
|---|---|---|
| SKU prefix | Prefix for imported product SKUs (e.g., `sku-1-`) | `sku-1-` |
| Price multiplier | Markup multiplier applied to all product prices | 1.2 |
| Import delay (ms) | Wait time between WooCommerce database writes | 10 |
| Stock management | Automatically set outdated products as out-of-stock | On |
| Auto import/update | Automatically create/update WooCommerce products after crawling | On |

## How It Works

The plugin follows a three-stage pipeline, orchestrated by `Woo_scrape_orchestrator`:

### 1. Crawling (`Woo_scrape_crawling_job`)
- Reads category URLs from the `woo_scrape_pages` database table
- Crawls each category, paginating through all pages
- Extracts partial product data (name, brand, prices, URL) from the category listing
- Filters for profitable products only (discounted price + shipping < suggested price)
- Deduplicates and saves/updates products in the `woo_scrape_products` table
- Crawls each product page for full details: description, specifications, images, and variations
- Downloads product images through the proxy and saves them to the WordPress media library
- Saves variations to the `woo_scrape_variations` table

### 2. Translation (`Woo_Scrape_Translation_Job`)
- Queries products/variations with untranslated fields
- Translates each field (name, specifications, description) using the configured translator
- Brand names can be excluded from translation to preserve them
- DeepL uses XML tag handling for ignored segments; Google Translate uses placeholder substitution
- Results are stored back in `translated_*` columns

### 3. WooCommerce Sync (`Woo_scrape_woocommerce_update_job`)
- **Out-of-stock**: Products not crawled today are marked out-of-stock (including their variations)
- **Update**: Existing WooCommerce products (matched by SKU) are updated with latest prices and stock
- **Create**: New products are created as `WC_Product_Simple` or `WC_Product_Variable` with full variation setup
- Prices are calculated as: `(discounted_price + shipping_addendum) × price_multiplier × currency_multiplier`
- If the suggested price is higher than the calculated profitable price, a sale price is displayed

## Database Schema

The plugin creates four custom tables on activation (prefixed with your WordPress table prefix):

| Table | Purpose |
|---|---|
| `woo_scrape_pages` | Source category URLs and their mapping to WooCommerce categories, with per-category package dimensions |
| `woo_scrape_products` | Scraped product data including original and translated text, prices, image references, crawl timestamps |
| `woo_scrape_variations` | Product variation data (name, prices, quantities) linked to parent products |
| `woo_scrape_job_logs` | Execution logs for each job stage with completed/failed counters and timestamps |

## Extending

### Adding a New Crawler

1. Create a new class in `services/` extending `Woo_Scrape_Abstract_Crawler_Service`
2. Implement `crawl_category(string $url): array` — returns an array of `WooScrapeProduct`
3. Implement `crawl_product(string $url, WooScrapeDecimal $suggested_price_multiplier): WooScrapeProduct`
4. The base class provides `crawl(string $url): string` (HTTP with retry), `crawl_images(array $urls): array`, and image upload to the media library

### Adding a New Translator

1. Create a new class in `services/` extending `Woo_Scrape_Abstract_Translator_Service`
2. Implement `translate(string $text, string $lang_code, string $ignored_text = ''): string`

## Admin Dashboard

Access via **Woo Scrape** in the WordPress admin menu:

- **Dashboard** — Shows real-time stats (total products, crawled counts, translation progress) and paginated job logs
- **Manual Actions** — Trigger individual jobs:
  - **Run Orchestrated Job** — Full pipeline (crawl → translate → sync)
  - **Run Crawling Job** — Crawl categories + products
  - **Run Product Crawling Job** — Crawl product pages only (skip categories)
  - **Run Translate Job** — Translate all untranslated fields
  - **Run WordPress Update Job** — Sync to WooCommerce
- **Single Product Crawl** — Enter a product SKU to re-crawl and update just that product
- **Settings** — All plugin configuration (see [Configuration](#configuration))

A **"Custom Product Links"** meta box is also added to the WooCommerce product edit screen, providing a direct link back to the original source product page.

## License

GPL-2.0+ — see [LICENSE.txt](LICENSE.txt) for details.
