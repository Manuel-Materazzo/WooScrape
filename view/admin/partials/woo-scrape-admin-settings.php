<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/Manuel-Materazzo
 * @since      1.0.0
 *
 * @package    Woo_Scrape
 * @subpackage Woo_Scrape/admin/partials
 */
?>

<div class="wrap">
    <h2>Woo Scrape - Settings</h2>
    <nav class="nav-tab-wrapper">
        <a class="nav-tab nav-tab-active woo-scrape-tab-link" onclick="changeTab(event, 'schedule')">Schedulation</a>
        <a class="nav-tab woo-scrape-tab-link" onclick="changeTab(event, 'provider')">Provider</a>
        <a class="nav-tab  woo-scrape-tab-link" onclick="changeTab(event, 'scraping')">Scraping</a>
        <a class="nav-tab woo-scrape-tab-link" onclick="changeTab(event, 'translation')">Translation</a>
        <a class="nav-tab woo-scrape-tab-link" onclick="changeTab(event, 'product-import')">Product Import</a>


    </nav>
    <div id="schedule" class="woo-scrape-tab">
        <h3>Schedule</h3>
        <form method="post" action="options.php">
		    <?php settings_fields( 'woo-scrape-schedule-group' ); ?>
		    <?php do_settings_sections( 'woo-scrape-schedule-group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        Schedule enabled
                    </th>
                    <td>
                        <input type="checkbox" id="schedule_crawl"
                               name="schedule_crawl"
                               value="1" <?php checked( 1, get_option( 'schedule_crawl' ), true ); ?> />
                        <label for="schedule_crawl">Start automatically the Orchestrator Job</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Schedule time
                    </th>
                    <td>
                        <div class="d-inline-block">
                            <input type="number" name="schedule_crawl_hour" step="1" min="0" max="23"
                                   value="<?php echo esc_attr( get_option( 'schedule_crawl_hour' ) ); ?>"/>
                            <p class="description">
                                Hour
                            </p>
                        </div>
                        <div class="d-inline-block">
                            <input type="number" name="schedule_crawl_minute" step="1" min="0" max="59"
                                   value="<?php echo esc_attr( get_option( 'schedule_crawl_minute' ) ); ?>"/>
                            <p class="description">
                                Minute
                            </p>
                        </div>

                    </td>
                </tr>
            </table>
		    <?php submit_button(); ?>
        </form>
    </div>
    <div id="scraping" class="woo-scrape-tab" style="display:none">
        <h3>Scraping</h3>
        <form method="post" action="options.php">
			<?php settings_fields( 'woo-scrape-import-scraping-group' ); ?>
			<?php do_settings_sections( 'woo-scrape-import-scraping-group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        Proxy url
                    </th>
                    <td>
                        <input type="text" name="crawl_proxy_url"
                               value="<?php echo esc_attr( get_option( 'crawl_proxy_url' ) ); ?>"/>
                        <p class="description">
                            Every crawl request will pass from this proxy. The request URL will be appended at the end.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Images Proxy url
                    </th>
                    <td>
                        <input type="text" name="image_proxy_url"
                               value="<?php echo esc_attr( get_option( 'image_proxy_url' ) ); ?>"/>
                        <p class="description">
                            Every image crawl request will pass from this proxy. The request URL will be appended at the end.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Crawl delay (ms)
                    </th>
                    <td>
                        <input type="number" name="crawl_delay_ms" step="10" min="1"
                               value="<?php echo esc_attr( get_option( 'crawl_delay_ms' ) ); ?>"/>
                        <p class="description">
                            Time to wait at the end of a crawl before starting another one.
                        </p>
                    </td>
                </tr>
            </table>
			<?php submit_button(); ?>
        </form>
    </div>

    <div id="provider" class="woo-scrape-tab" style="display:none">
        <h3>Provider</h3>
        <form method="post" action="options.php">
			<?php settings_fields( 'woo-scrape-provider-settings-group' ); ?>
			<?php do_settings_sections( 'woo-scrape-provider-settings-group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        Free shipping threshold
                    </th>
                    <td>
                        <input type="number" name="provider_free_shipping_threshold" step="0.1" min="0"
                               value="<?php echo esc_attr( get_option( 'provider_free_shipping_threshold' ) ); ?>"/>
                        <p class="description">
                            When the cart value on provider's website is greater than this amount, the shipping is free.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Shipping addendum
                    </th>
                    <td>
                        <input type="number" name="provider_shipping_addendum" step="0.1" min="0"
                               value="<?php echo esc_attr( get_option( 'provider_shipping_addendum' ) ); ?>"/>
                        <p class="description">
                            Shipping cost on provider's carts that do not surpass the "Free shipping threshold".
                            This will be added on every product's price before the "Price multiplier" is applied
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Currency conversion multiplier
                    </th>
                    <td>
                        <input type="number" name="currency_conversion_multiplier" step="0.1" min="0"
                               value="<?php echo esc_attr( get_option( 'currency_conversion_multiplier' ) ); ?>"/>x
                        <p class="description">
                            Every product's price will be multiplied for this value before saving it on woocommerce to
                            correctly match its eur value.
                        </p>
                    </td>
                </tr>
            </table>
			<?php submit_button(); ?>
        </form>
    </div>

    <div id="product-import" class="woo-scrape-tab" style="display:none">
        <h3>Product import</h3>
        <form method="post" action="options.php">
			<?php settings_fields( 'woo-scrape-import-settings-group' ); ?>
			<?php do_settings_sections( 'woo-scrape-import-settings-group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        Sku prefix
                    </th>
                    <td>
                        <input type="text" name="sku_prefix"
                               value="<?php echo esc_attr( get_option( 'sku_prefix' ) ); ?>"/>
                        <p class="description">
                            Every product imported by this plugin will have this prefix.
                            Must be different from your standard woocommerce prefix.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Price multiplier
                    </th>
                    <td>
                        <input type="number" name="price_multiplier" step="0.1" min="0"
                               value="<?php echo esc_attr( get_option( 'price_multiplier' ) ); ?>"/>x
                        <p class="description">
                            Every product's price will be multiplied for this value before saving it on woocommerce.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Import delay (ms)
                    </th>
                    <td>
                        <input type="number" name="crawl_delay_ms" step="10"
                               value="<?php echo esc_attr( get_option( 'import_delay_ms' ) ); ?>"/>
                        <p class="description">
                            Time to wait at the end of a woocommerce import before starting another one.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Woocommerce stock management
                    </th>
                    <td>
                        <input type="checkbox" id="woocommerce_auto_import" name="woocommerce_auto_import"
                               value="1" <?php checked( 1, get_option( 'woocommerce_stock_management' ), true ); ?> />
                        <label for="woocommerce_auto_import">Enable automatic Woocommerce in stock and out of
                            stock</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Woocommerce auto import/update
                    </th>
                    <td>
                        <input type="checkbox" id="woocommerce_auto_import" name="woocommerce_auto_import"
                               value="1" <?php checked( 1, get_option( 'woocommerce_auto_import' ), true ); ?> />
                        <label for="woocommerce_auto_import">Enable automatic Woocommerce database update after product
                            crawling</label>
                    </td>
                </tr>
            </table>
			<?php submit_button(); ?>
    </div>
    <div id="translation" class="woo-scrape-tab" style="display:none">
        <h3>Translation</h3>
        <form method="post" action="options.php">
			<?php settings_fields( 'woo-scrape-translation-settings-group' ); ?>
			<?php do_settings_sections( 'woo-scrape-translation-settings-group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        Target language code
                    </th>
                    <td>
                        <input type="text" name="translation_language"
                               value="<?php echo esc_attr( get_option( 'translation_language' ) ); ?>"/>
                        <p class="description">
                            Every product's title and description will be translated to this language code.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Google script url
                    </th>
                    <td>
                        <input type="text" name="google_script_url"
                               value="<?php echo esc_attr( get_option( 'google_script_url' ) ); ?>"/>
                        <p class="description">
                            The google script url to use for translations see
                            <a href="https://stackoverflow.com/questions/8147284/how-to-use-google-translate-api-in-my-java-application/48159904#48159904"
                            >this</a>.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Deepl api key
                    </th>
                    <td>
                        <input type="text" name="deepl_api_key"
                               value="<?php echo esc_attr( get_option( 'deepl_api_key' ) ); ?>"/>
                        <p class="description">
                            Api key to use Deepl as translator, get it (free or paid) from <a
                                    href="https://www.deepl.com/it/pro-api/ru/en/pl/pro-api">here</a>.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Deepl free endpoint
                    </th>
                    <td>
                        <input type="checkbox" id="deepl_api_free" name="deepl_api_free"
                               value="1" <?php checked( 1, get_option( 'deepl_api_free' ), true ); ?> />
                        <label for="deepl_api_free">Use Deepl free api endpoint, enable only if you are not paying for a
                            Deepl api key.</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Proxy url
                    </th>
                    <td>
                        <input type="text" name="translation_proxy_url"
                               value="<?php echo esc_attr( get_option( 'translation_proxy_url' ) ); ?>"/>
                        <p class="description">
                            Every translation request will pass from this proxy. This is uses standard http/https proxy
                             protocol, for everything but deepl, where it's dependant from
                            <a href="https://github.com/Manuel-Materazzo/Fluid-Proxy">Fluid-proxy</a>'s
                            request manipulation features.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Translation delay (ms)
                    </th>
                    <td>
                        <input type="number" name="crawl_delay_ms" step="10" min="1"
                               value="<?php echo esc_attr( get_option( 'translation_delay_ms' ) ); ?>"/>
                        <p class="description">
                            Time to wait at the end of a translation before starting another one.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Automatic Title translation
                    </th>
                    <td>
                        <input type="checkbox" id="automatic_title_translation" name="automatic_title_translation"
                               value="1" <?php checked( 1, get_option( 'automatic_title_translation' ), true ); ?> />
                        <label for="automatic_title_translation">Enable automatic translation of the title for new
                            products</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Automatic Description translation
                    </th>
                    <td>
                        <input type="checkbox" id="automatic_description_translation"
                               name="automatic_description_translation"
                               value="1" <?php checked( 1, get_option( 'automatic_description_translation' ), true ); ?> />
                        <label for="automatic_description_translation">Enable automatic translation of the description
                            for new products</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Automatic Specification translation
                    </th>
                    <td>
                        <input type="checkbox" id="automatic_specification_translation"
                               name="automatic_specification_translation"
                               value="1" <?php checked( 1, get_option( 'automatic_specification_translation' ), true ); ?> />
                        <label for="automatic_specification_translation">Enable automatic translation of the
                            specifications for new products</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Use Google translate for titles
                    </th>
                    <td>
                        <input type="checkbox" id="title_google_translation" name="title_google_translation"
                               value="1" <?php checked( 1, get_option( 'title_google_translation' ), true ); ?> />
                        <label for="title_google_translation">Save Deepl credits by using google translate for
                            titles</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Use Google translate for specifications
                    </th>
                    <td>
                        <input type="checkbox" id="specification_google_translation"
                               name="specification_google_translation"
                               value="1" <?php checked( 1, get_option( 'specification_google_translation' ), true ); ?> />
                        <label for="specification_google_translation">Save Deepl credits by using google translate for
                            specifications</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Use Google translate for descriptions
                    </th>
                    <td>
                        <input type="checkbox" id="descriptions_google_translation"
                               name="descriptions_google_translation"
                               value="1" <?php checked( 1, get_option( 'descriptions_google_translation' ), true ); ?> />
                        <label for="descriptions_google_translation">Save Deepl credits by using google translate for
                            descriptions</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Ignore brands during translation
                    </th>
                    <td>
                        <input type="checkbox" id="translation_ignore_brands" name="translation_ignore_brands"
                               value="1" <?php checked( 1, get_option( 'translation_ignore_brands' ), true ); ?> />
                        <label for="translation_ignore_brands">Try to ignore brand names while translating</label>
                    </td>
                </tr>
            </table>
			<?php submit_button(); ?>
        </form>
    </div>
</div>

<script>
    function changeTab(evt, id) {
        let i;
        const x = document.getElementsByClassName("woo-scrape-tab");
        for (i = 0; i < x.length; i++) {
            x[i].style.display = "none";
        }
        document.getElementById(id).style.display = "block";

        const tablinks = document.getElementsByClassName("woo-scrape-tab-link");
        for (i = 0; i < x.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
        }
        evt.currentTarget.className += " nav-tab-active";
    }
</script>

