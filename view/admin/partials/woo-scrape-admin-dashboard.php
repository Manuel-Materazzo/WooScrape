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
<button id="run-job-button">Run Job</button>


<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#run-job-button').click(function(){
            $.post(ajaxurl, { action: 'run_my_job' }, function(response) {
                console.log('Job run successfully');
            });
        });
    });
</script>

