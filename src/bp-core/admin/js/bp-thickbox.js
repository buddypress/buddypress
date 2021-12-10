/**
 * Improves the Thickbox library for BuddyPress needs.
 *
 * @since 10.0.0
 */
( function( $ ) {
	window.bpAdjustThickbox = function( label, padding ) {
		$( '#TB_window' ).attr( {
							'role': 'dialog',
							'aria-label': label
						} )
						.addClass( 'plugin-details-modal' )
						.removeClass( 'thickbox-loading' );


		if ( ! padding ) {
			padding = 0;
		}

		$( '#TB_ajaxContent' ).prop( 'style', 'height: 100%; width: auto; padding: ' + padding + '; border: none;' );

		try {
			var tabbables = $( ':tabbable', '#TB_ajaxContent' ), lastTabbable = tabbables.last();

			// Move the focus to the Modal's close button once the last Hello link was tabbed out.
			$( '#TB_window' ).on( 'keydown', function( event ) {
				var keyCode;

				if ( event.key !== undefined ) {
					keyCode = event.key;
				} else {
					// event.keyCode is deprecated.
					keyCode = event.keyCode;
				}

				if ( 9 === keyCode && ! event.shiftKey && $( lastTabbable ).prop( 'classList' ).value === $( event.target ).prop( 'classList' ).value ) {
					event.preventDefault();

					$( '#TB_closeWindowButton' ).trigger( 'focus' );
				}

				if ( 9 === keyCode && event.shiftKey && 'TB_closeWindowButton' === $( event.target ).prop( 'id' ) ) {
					event.preventDefault();

					$( lastTabbable ).trigger( 'focus' );
				}
			} );
		} catch ( error ) {
			return;
		}
	};
} ( jQuery ) );
