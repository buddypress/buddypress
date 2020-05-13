/* global bpHelloStrings */
/**
 * Loads for BuddyPress Hello in wp-admin for query string `hello=buddypress`.
 *
 * @since 3.0.0
 */
(function( $, bp ) {
	// Bail if not set
	if ( typeof bpHelloStrings === 'undefined' ) {
		return;
	}

	/**
	 * Open the BuddyPress Hello modal.
	 */
	var bpHelloOpenModal = function() {
		if ( 'function' !== typeof window.tb_show ) {
			return false;
		}

		window.tb_show( 'BuddyPress', '#TB_inline?inlineId=bp-hello-container' );

		$( '#TB_window' ).attr( {
							'role': 'dialog',
							'aria-label': bpHelloStrings.modalLabel
						} )
						.addClass( 'plugin-details-modal' )
						.removeClass( 'thickbox-loading' );

		$( '#TB_ajaxContent' ).prop( 'style', 'height: 100%; width: auto; padding: 0; border: none;' );

		var tabbables = $( ':tabbable', '#TB_ajaxContent' ), lastTabbable = tabbables.last();

		// Move the focus to the Modal's close button once the last Hello link was tabbed out.
		$( '#TB_window' ).on( 'keydown', function( event ) {
			if ( 9 === event.keyCode && ! event.shiftKey && $( lastTabbable ).prop( 'classList' ).value === $( event.target ).prop( 'classList' ).value ) {
				event.preventDefault();

				$( '#TB_closeWindowButton' ).focus();
			}

			if ( 9 === event.keyCode && event.shiftKey && 'TB_closeWindowButton' === $( event.target ).prop( 'id' ) ) {
				event.preventDefault();

				$( lastTabbable ).focus();
			}
		} );
	};

	/**
	 * Prints an error message.
	 *
	 * @param {string} message The error message to display.
	 */
	var printErrorMessage = function( message ) {
		if ( ! message ) {
			message = bpHelloStrings.pageNotFound;
		}

		$( '#dynamic-content' ).html(
			$('<div></div>' ).prop( 'id', 'message' )
					.addClass( 'notice notice-error error' )
					.html(
						$( '<p></p>' ).html( message )
					)
		);
	};

	// Listen to Tab Menu clicks to display the different screens.
	$( '#plugin-information-tabs').on( 'click', 'a', function( event ) {
		event.preventDefault();

		var anchor = $( event.currentTarget ), target = $( '#dynamic-content' );

		if ( anchor.hasClass( 'dynamic' ) ) {
			$( '#top-features' ).hide();
			target.html( '' );
			target.addClass( 'show' );

			$( '#TB_window' ).addClass( 'thickbox-loading' );

			bp.apiRequest( {
				url: anchor.data( 'endpoint' ),
				type: 'GET',
				beforeSend: function( xhr, settings ) {
					settings.url = settings.url.replace( '&_wpnonce=none', '' );
				},
				data: {
					context: 'view',
					slug: anchor.data( 'slug' ),
					_wpnonce: 'none'
				}
			} ).done( function( data ) {
				var page = _.first( data );

				if ( page && page.content ) {
					target.html( page.content.rendered );
				} else {
					printErrorMessage();
				}

			} ).fail( function( error ) {
				if ( ! error || ! error.message ) {
					return false;
				}

				printErrorMessage( error.message );

			} ).always( function() {
				$( '#TB_window' ).removeClass( 'thickbox-loading' );
			} );

		} else {
			$( '#top-features' ).show();
			target.html( '' );
			target.removeClass( 'show' );
		}
	} );

	// Init modal after the screen's loaded.
	$( document ).ready( function() {
		bpHelloOpenModal();
	} );

}( jQuery, window.bp || {} ) );
