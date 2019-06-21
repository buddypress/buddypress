/**
 * jQuery.ajax wrapper for BP REST API requests.
 *
 * @since  5.0.0
 * @output bp-core/js/bp-api-request.js
 */
/* global bpApiSettings */
window.bp = window.bp || {};

( function( wp, bp, $ ) {
    // Bail if not set
    if ( typeof bpApiSettings === 'undefined' ) {
        return;
    }

    bp.isRestEnabled = true;

    // Polyfill wp.apiRequest if WordPress < 4.9
    bp.apiRequest = wp.apiRequest || function( options ) {
        var url = bpApiSettings.root;

        if ( options.path ) {
            url = url + options.path.replace( /^\//, '' );
        }

        options.url = url;
        options.beforeSend = function( xhr ) {
            xhr.setRequestHeader( 'X-WP-Nonce', bpApiSettings.nonce );
        };

        return $.ajax( options );
    };

} )( window.wp || {}, window.bp, jQuery );
