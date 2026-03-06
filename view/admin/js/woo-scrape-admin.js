jQuery(document).ready(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    function showToast(message, type) {
        var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        $('#toast-hanger').append(
            '<div class="notice ' + noticeClass + ' is-dismissible"> ' +
            '<p>' + message + '</p> ' +
            '</div>'
        );
    }

    function runJob(action, data, successMessage) {
        $.post(ajaxurl, $.extend({action: action, nonce: woo_scrape_ajax.nonce}, data))
            .done(function () {
                showToast(successMessage, 'success');
            })
            .fail(function (jqXHR) {
                showToast('Job failed: ' + (jqXHR.statusText || 'Unknown error'), 'error');
            });
    }

    $('#run-orchestrator-job-button').click(function () {
        runJob('run_orchestrator_job', {}, 'Orchestrated job completed successfully.');
    });
    $('#run-crawling-job-button').click(function () {
        runJob('run_crawling_job', {}, 'Crawling job completed successfully.');
    });
    $('#run-product-crawling-job-button').click(function () {
        runJob('run_product_crawling_job', {}, 'Product Crawling job completed successfully.');
    });
    $('#run-translate-job-button').click(function () {
        runJob('run_translate_job', {}, 'Translation job completed successfully.');
    });
    $('#run-wordpress-job-button').click(function () {
        runJob('run_wordpress_job', {}, 'Wordpress update job completed successfully.');
    });
    $('#run-single-product-job').click(function () {
        var sku = $("#manual-crawl-sku").val();
        runJob('run_single_product_job', {sku: sku}, 'Single product crawl job completed successfully.');
    });
    $('#clear-job-logs-button').click(function () {
        if (confirm('Are you sure you want to clear all job logs?')) {
            runJob('clear_job_logs', {}, 'Job logs cleared successfully.');
        }
    });

    // accordion
    const acc = document.getElementsByClassName("accordion");
    let i;

    for (i = 0; i < acc.length; i++) {
        acc[i].addEventListener("click", function() {
            /* Toggle between adding and removing the "active" class,
            to highlight the button that controls the panel */
            this.classList.toggle("active");

            /* Toggle between hiding and showing the active panel */
            const panel = this.nextElementSibling;
            if (panel.style.display === "block") {
                panel.style.display = "none";
            } else {
                panel.style.display = "block";
            }
        });
    }
});
