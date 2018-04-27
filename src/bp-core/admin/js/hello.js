/**
 * Loads for BuddyPress Hello in wp-admin for query string `hello=buddypress`.
 *
 * @since 3.0.0
 */
(function() {
	/**
	 * Open the BuddyPress Hello modal.
	 */
	var bp_hello_open_modal = function() {
		document.body.classList.add( 'bp-disable-scroll' );

		// Show.
		document.getElementById( 'bp-hello-backdrop' ).style.display  = '';
		document.getElementById( 'bp-hello-container' ).style.display = '';
	};

	/**
	 * Close the BuddyPress Hello modal.
	 */
	var bp_hello_close_modal = function() {
		var backdrop = document.getElementById( 'bp-hello-backdrop' ),
			modal = document.getElementById( 'bp-hello-container' );

		document.body.classList.remove( 'bp-disable-scroll' );

		// Hide.
		modal.parentNode.removeChild( modal );
		backdrop.parentNode.removeChild( backdrop );
	};

	// Close modal if "X" or background is touched.
	document.addEventListener( 'click', function( event ) {
		var backdrop = document.getElementById( 'bp-hello-backdrop' );

		if ( ! backdrop || ! document.getElementById( 'bp-hello-container' ) ) {
			return;
		}

		var backdrop_click = backdrop.contains( event.target ),
			modal_close_click = event.target.classList.contains( 'close-modal' );

		if ( ! modal_close_click && ! backdrop_click ) {
			return;
		}

		bp_hello_close_modal();
	}, false );

	// Close modal if escape key is presssed.
	document.addEventListener( 'keyup', function( event ) {
		if ( event.keyCode === 27 ) {
			if ( ! document.getElementById( 'bp-hello-backdrop' ) || ! document.getElementById( 'bp-hello-container' ) ) {
				return;
			}

			bp_hello_close_modal();
		}
	}, false );

	// Init modal after the screen's loaded.
	if ( document.attachEvent ? document.readyState === 'complete' : document.readyState !== 'loading' ) {
		bp_hello_open_modal();
	} else {
		document.addEventListener( 'DOMContentLoaded', bp_hello_open_modal );
	}
}());
