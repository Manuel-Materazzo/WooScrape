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
        <a class="nav-tab nav-tab-active woo-scrape-tab-link" onclick="changeTab(event, 'scraping')">Scraping</a>
        <a class="nav-tab woo-scrape-tab-link" onclick="changeTab(event, 'provider')">Provider</a>
        <a class="nav-tab woo-scrape-tab-link" onclick="changeTab(event, 'product-import')">Product Import</a>

    </nav>
    <div id="scraping" class="woo-scrape-tab">
        <h3>Scraping</h3>
    </div>

    <div id="provider" class="woo-scrape-tab" style="display:none">
        <h3>Provider</h3>
        <form method="post" action="options.php">
            <?php settings_fields('woo-scrape-provider-settings-group'); ?>
            <?php do_settings_sections('woo-scrape-provider-settings-group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        Free shipping threshold
                    </th>
                    <td>
                        <input type="number" name="provider_free_shipping_threshold" step="0.1"
                               value="<?php echo esc_attr(get_option('provider_free_shipping_threshold')); ?>"/>
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
                        <input type="number" name="provider_shipping_addendum" step="0.1"
                               value="<?php echo esc_attr(get_option('provider_shipping_addendum')); ?>"/>
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
                        <input type="number" name="currency_conversion_multiplier" step="0.1"
                               value="<?php echo esc_attr(get_option('currency_conversion_multiplier')); ?>"/>x
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
            <?php settings_fields('woo-scrape-import-settings-group'); ?>
            <?php do_settings_sections('woo-scrape-import-settings-group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        Sku Prefix
                    </th>
                    <td>
                        <input type="text" name="sku_prefix"
                               value="<?php echo esc_attr(get_option('sku_prefix')); ?>"/>
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
                        <input type="number" name="price_multiplier" step="0.1"
                               value="<?php echo esc_attr(get_option('price_multiplier')); ?>"/>x
                        <p class="description">
                            Every product's price will be multiplied for this value before saving it on woocommerce.
                        </p>
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

