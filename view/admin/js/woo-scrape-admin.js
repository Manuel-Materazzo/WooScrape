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

    $('#run-orchestrator-job-button').click(function () {
        $.post(ajaxurl, {action: 'run_orchestrator_job'}, function (response) {
            $('#toast-hanger').append(
                '<div class="notice notice-success is-dismissible"> ' +
                '<p>Orchestrated job started successfully.</p> ' +
                '</div>'
            )
        });
    });
    $('#run-crawling-job-button').click(function () {
        $.post(ajaxurl, {action: 'run_crawling_job'}, function (response) {
            $('#toast-hanger').append(
                '<div class="notice notice-success is-dismissible"> ' +
                '<p>Crawling job started successfully.</p> ' +
                '</div>'
            )
        });
    });
    $('#run-product-crawling-job-button').click(function () {
        $.post(ajaxurl, {action: 'run_product_crawling_job'}, function (response) {
            $('#toast-hanger').append(
                '<div class="notice notice-success is-dismissible"> ' +
                '<p>Product Crawling job started successfully.</p> ' +
                '</div>'
            )
        });
    });
    $('#run-translate-job-button').click(function () {
        $.post(ajaxurl, {action: 'run_translate_job'}, function (response) {
            $('#toast-hanger').append(
                '<div class="notice notice-success is-dismissible"> ' +
                '<p>Translation job started successfully.</p> ' +
                '</div>'
            )
        });
    });
    $('#run-wordpress-job-button').click(function () {
        $.post(ajaxurl, {action: 'run_wordpress_job'}, function (response) {
            $('#toast-hanger').append(
                '<div class="notice notice-success is-dismissible"> ' +
                '<p>Wordpress update job started successfully.</p> ' +
                '</div>'
            )
        });
    });
});
