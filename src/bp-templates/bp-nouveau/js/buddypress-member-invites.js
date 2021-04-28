/**
 * Handles the dynamic parts of the Member's Pending Invitations screen.
 *
 * @since  8.0.0
 * @version 8.0.0
 */
( function() {
	/**
	 * Organizes the dynamic parts of the Member's Pending Invitations screen.
	 *
	 * @namespace bp.Nouveau.Invitations
	 * @memberof  bp.Nouveau
	 *
	 * @since  8.0.0
	 * @type {Object}
	 */
	var Invitations = {
		/**
		 * Selects/Unselects all invitations.
		 *
		 * @since 8.0.0
		 *
		 * @param {Object} event The click event.
		 */
		toggleSelection: function( event ) {
			document.querySelectorAll( '.invitation-check' ).forEach( function( cb ) {
				cb.checked = event.target.checked;
			} );
		},
		/**
		 * Selects/Unselects all invitations.
		 *
		 * @since 8.0.0
		 *
		 * @param {Object} event The click event.
		 */
		toggleSubmit: function( event ) {
			if ( ! event.target.value ) {
				document.querySelector( '#invitation-bulk-manage' ).setAttribute( 'disabled', 'disabled' );
			} else {
				document.querySelector( '#invitation-bulk-manage' ).removeAttribute( 'disabled' );
			}
		},
		/**
		 * Adds listeners.
		 *
		 * @since 8.0.0
		 */
		start: function() {
			// Disable the submit button.
			document.querySelector( '#invitation-bulk-manage' ).setAttribute( 'disabled', 'disabled' );

			// Select/UnSelect all invitations.
			document.querySelector( '#select-all-invitations' ).addEventListener( 'click', this.toggleSelection );

			// Enable/Disable the submit button.
			document.querySelector( '#invitation-select' ).addEventListener( 'change', this.toggleSubmit );
		}
	};

	window.bp = window.bp || {};
	if ( window.bp.Nouveau ) {
		window.bp.Nouveau.Invitations = Invitations;

		Invitations.start();
	}
} )();
