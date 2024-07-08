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
    <h3>Scraping</h3>

    <h3>Product import</h3>
    <form method="post" action="options.php">
        <?php settings_fields('woo-scrape-import-settings-group'); ?>
        <?php do_settings_sections('woo-scrape-import-settings-group'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Price multiplier</th>
                <td>
                    <input type="number" name="price_multiplier" step="0.1"
                           value="<?php echo esc_attr(get_option('price_multiplier')); ?>"/>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>

