/**
 * jQuery.ajax wrapper for BP REST API requests.
 *
 * @since  5.0.0
 * @deprecated 10.0.0
 * @output bp-core/js/bp-api-request.js
 */
/* global bpApiSettings */
window.bp = window.bp || {};

( function( wp, bp ) {
    // Bail if not set.
    if ( typeof bpApiSettings === 'undefined' ) {
        return;
    }

    bp.isRestEnabled = true;

    // Polyfill wp.apiRequest.
    bp.apiRequest = function( options ) {
		window.console.log( bpApiSettings.deprecatedWarning );

        var bpRequest;

        if ( ! options.dataType ) {
            options.dataType = 'json';
        }

        bpRequest = wp.apiRequest( options );

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

} )( window.wp || {}, window.bp );
