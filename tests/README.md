# WooScrape Test Suite

## Prerequisites

- **PHP 8.4+** with the `bcmath` extension enabled
- **Composer 2.x**

## Setup

```bash
composer install
```

## Running Tests

```bash
php vendor/bin/phpunit
```

With detailed output:

```bash
php vendor/bin/phpunit --testdox
```

## Architecture

The test suite runs **without WordPress or WooCommerce** installed. A bootstrap file (`tests/bootstrap.php`) provides minimal stubs for WordPress functions (`get_option`, `plugin_dir_path`, `current_time`, `absint`, `dbDelta`, etc.) and a lightweight `$wpdb` mock, allowing all production classes to be loaded and tested in isolation.

```
tests/
├── bootstrap.php                    # WP function stubs, $wpdb mock, class loading
├── wp-admin/includes/upgrade.php    # Empty stub for dbDelta require
├── WooScrapeDecimalTest.php         # DTOs – Decimal value object
├── WooScrapeProductTest.php         # DTOs – Product DTO & business logic
├── JobTypeEnumTest.php              # DTOs – JobType enum
├── LoaderTest.php                   # Includes – Hook loader
├── ActivatorTest.php                # Includes – Plugin activation / DB versioning
├── SettingUtilsTest.php             # Utils – Settings helpers & schedule time
├── ProductServiceTest.php           # Services – Product parameter building
├── VariationServiceTest.php         # Services – Variation parameter building
├── DeeplServiceTest.php             # Services – DeepL text processing
├── GtranslateServiceTest.php        # Services – Google Translate structure
├── FishdealCrawlerServiceTest.php   # Services – Fishdeal text sanitization
├── AbstractCrawlerServiceTest.php   # Services – UUIDv4 generation
├── BackgroundJobsTest.php           # Jobs – Transient-based lock mechanism
└── README.md
```

## Test Classes

### WooScrapeDecimalTest (49 tests)

Covers the `WooScrapeDecimal` value object which wraps `bcmath` for precise two-decimal arithmetic.

| Area | What's tested |
|---|---|
| Constructor | String, int, float, `WooScrapeDecimal` input; whitespace trimming; negative values; zero; large numbers; truncation to 2 decimals |
| Validation | `InvalidArgumentException` on non-numeric strings and empty strings |
| Comparisons | `equals()`, `greater_than()`, `lower_than()`, `greater_than_or_equal()`, `lower_than_or_equal()` — each with true/false/boundary cases |
| Arithmetic | `add()`, `subtract()`, `multiply()`, `divide()` — basic operations, negative results, zero multiplication, truncation behavior |
| Fluent API | Chaining multiple operations in sequence; return-self verification |
| Clone | Returns independent copy; mutations don't affect original |
| `__toString()` | String coercion returns the stored value |

### WooScrapeProductTest (29 tests)

Covers the `WooScrapeProduct` DTO and its `isProfitable()` business logic.

| Area | What's tested |
|---|---|
| `isProfitable()` | Discounted price below suggested (profitable); equal prices (not profitable); shipping addendum pushing cost above suggested; free shipping threshold (products ≥ threshold skip shipping); default option values |
| `setTranslatedField()` | Routing `name` → `translated_name`, `specifications` → `translated_specification`, `description` → `translated_description`; unknown field names are silently ignored |
| Getters/Setters | All 18 property pairs: id, name, description, specification, brand, url, image URLs/IDs, category ID, quantity, has_variations, suggested/discounted price, timestamps, variations, translated fields |
| Default values | Verifies all properties have correct initial values (empty strings, null, empty arrays) |

### JobTypeEnumTest (6 tests)

Covers the `JobType` backed enum.

| Area | What's tested |
|---|---|
| Completeness | All 9 cases exist with correct string values |
| `from()` | Valid string resolves to correct case; invalid string throws `ValueError` |
| `tryFrom()` | Returns `null` for invalid strings |
| Count | Exactly 9 cases defined |

### LoaderTest (7 tests)

Covers the `Woo_Scrape_Loader` hook registration system.

| Area | What's tested |
|---|---|
| Actions | Single action registration and execution via `run()` |
| Filters | Single filter registration and execution via `run()` |
| Multiple hooks | Three actions registered and all fire in order |
| Mixed | Actions and filters coexist independently |
| Defaults | Priority defaults to 10, accepted_args defaults to 1 |
| Custom values | Custom priority and accepted_args are passed through |
| Empty | `run()` with no hooks registered doesn't error |

### ActivatorTest (5 tests)

Covers the `Woo_Scrape_Activator` plugin activation and DB versioning.

| Area | What's tested |
|---|---|
| Constant | `DB_VERSION` is `'1.0.0'` |
| `check_db_version()` | Creates tables and stores version when none exists; skips when version matches; upgrades when version is older |
| `activate()` | Runs table creation and stores DB version |

### SettingUtilsTest (8 tests)

Covers the `Woo_scrape_setting_utils` helper class.

| Area | What's tested |
|---|---|
| Registration | `register_boolean_true()`, `register_boolean_false()`, `register_string()` execute without errors |
| `get_schedule_time()` | Returns a future timestamp; handles positive GMT offset (+2); handles negative GMT offset (−5); midnight edge case; wraps to next day if calculated time is in the past |

### ProductServiceTest (10 tests)

Covers the `Woo_scrape_product_service` internal parameter building logic.

| Area | What's tested |
|---|---|
| Empty product | Only pre-existing keys remain; no product fields added |
| Full product | All 14 product fields are correctly mapped to DB column names |
| Partial product | Only set fields appear in output |
| Key preservation | Pre-existing keys (timestamp, category_id) survive merging |
| `has_variations` | `false` is included; `null` is excluded |
| Quantity zero | `0` is included as string `'0'` (not excluded as falsy) |
| JSON encoding | `image_urls` and `image_ids` arrays are JSON-encoded |
| Field allowlist | Source code contains the expected allowed fields for untranslated queries |

### VariationServiceTest (6 tests)

Covers the `Woo_Scrape_Variation_Service` parameter building logic.

| Area | What's tested |
|---|---|
| Empty variation | No variation fields added to output |
| Full variation | All 5 variation fields (name, translated_name, quantity, suggested_price, discounted_price) mapped correctly |
| Partial | Only populated fields appear |
| Quantity zero | Included as `'0'` |
| Key preservation | Pre-existing keys survive |
| Allowed fields | Source confirms only `'name'` is allowed for untranslated variation lookup |

### DeeplServiceTest (5 tests)

Covers private text-processing methods in `Woo_scrape_deepl_service`.

| Area | What's tested |
|---|---|
| `filter_artifacts()` | Removes `Ã¢` → space, `â€` → apostrophe, `â` → space; clean text passes through unchanged |
| `replace_special_characters()` | `×` → `x`, `±` → `~`; no-op on normal text; multiple occurrences in one string |

### GtranslateServiceTest (3 tests)

Covers the `Woo_scrape_gtranslate_service` structure.

| Area | What's tested |
|---|---|
| Inheritance | Extends `Woo_Scrape_Abstract_Translator_Service` |
| Method signature | `translate()` accepts `$text`, `$lang_code`, `$ignored_text` (default `''`) |

### FishdealCrawlerServiceTest (9 tests)

Covers text sanitization in `Woo_scrape_fishdeal_crawler_service`.

| Area | What's tested |
|---|---|
| Inheritance | Extends `Woo_Scrape_Abstract_Crawler_Service` |
| Keyword removal | `Descrizione`, `Caratteristiche`, `Vedere di più`, `Chiudi lista`, `Mostra di più`, `Riduci` — each verified individually and all combined |
| Whitespace | Multiple spaces and tabs collapsed to single spaces |
| Edge cases | Empty string input |

### AbstractCrawlerServiceTest (5 tests)

Covers the UUIDv4 generator in `Woo_Scrape_Abstract_Crawler_Service`.

| Area | What's tested |
|---|---|
| Format | Matches `8-4-4-4-12` hex pattern |
| Version nibble | Character at position 14 is always `'4'` |
| Variant bits | Character at position 19 is `8`, `9`, `a`, or `b` |
| Length | Always 36 characters |
| Uniqueness | 100 generated UUIDs are all distinct |
| Determinism | Same 16-byte input produces same UUID |

### BackgroundJobsTest (7 tests)

Covers the transient-based lock mechanism in `Woo_Scrape_Background_Jobs`.

| Area | What's tested |
|---|---|
| `acquire_lock()` | Succeeds when lock is free; fails when already held |
| `release_lock()` | Allows re-acquisition after release |
| Independence | Two different lock keys don't interfere; releasing one doesn't affect the other |
| Public API | All 6 `run_*` methods exist, are `public`, and are `static` |
