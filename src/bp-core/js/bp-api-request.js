/**
 * jQuery.ajax wrapper for BP REST API requests.
 *
 * @since  5.0.0
 * @output bp-core/js/bp-api-request.js
 */
/* global bpApiSettings */
window.bp = window.bp || {};

( function( wp, bp, $ ) {
    // Bail if not set.
    if ( typeof bpApiSettings === 'undefined' ) {
        return;
    }

    bp.isRestEnabled = true;

    // Polyfill wp.apiRequest if WordPress < 4.9.
    bp.apiRequest = function( options ) {
        var bpRequest;

        if ( ! options.dataType ) {
            options.dataType = 'json';
        }

        // WordPress is >= 4.9.0.
        if ( wp.apiRequest ) {
            bpRequest = wp.apiRequest( options );

        // WordPress is < 4.9.0.
        } else {
            var url = bpApiSettings.root;

            if ( options.path ) {
                url = url + options.path.replace( /^\//, '' );
            }

            if ( ! options.url ) {
                options.url = url;
            }

            // Add The nonce only when needed.
            if ( -1 !== options.url.indexOf( url ) ) {
                options.beforeSend = function( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', bpApiSettings.nonce );
                };
            }

            bpRequest = $.ajax( options );
        }

        return bpRequest.then( null, function( result ) {
            var errorObject = {
                code: 'unexpected_error',
                message: bpApiSettings.unexpectedError,
                data: {
                    status: 404
                }
            };

            if ( result && result.responseJSON ) {
                errorObject = result.responseJSON;
            }

            return errorObject;
        } );
    };

} )( window.wp || {}, window.bp, jQuery );
