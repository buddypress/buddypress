/**
 * WordPress dependencies.
 */
import domReady from '@wordpress/dom-ready';

/**
 * Notices center class.
 *
 * @since 15.0.0
 */
class bpNoticesCenter {
	constructor( settings ) {
		const { path, dismissPath, root, nonce } = settings;
		this.path = path;
		this.dismissPath = dismissPath;
		this.root = root;
		this.nonce = nonce;
		this.container = document.querySelector( '#bp-notices-container' );
		this.bubble = document.querySelector( '#wp-admin-bar-bp-notifications' );
	}

	/**
	 * Catches all notices container clicks.
	 *
	 * @since 15.0.0
	 *
	 * @param {PointerEvent} event The click event.
	 */
	catchEvents( event ) {
		// Use the BP REST API to dismiss the notice.
		if ( event.target.dataset.bpDismissId ) {
			//event.preventDefault();

			/*
			 * @todo: Dismissing notices should be done using the BP REST API.
			 * The corresponding controller needs to be updated.
			 */
		}
	}

	/**
	 * Notices Center Class starter.
	 *
	 * @since 15.0.0
	 */
	start() {
		// Use event delegation to catch all clicks happening into the Center.
		this.container.addEventListener( 'click', this.catchEvents.bind( this ), false );

		// Take care of browsers not supporting the Popover API.
		if ( undefined === this.container.popover ) {
			this.container.remove();
			console.warn( 'Your browser does not support the Popover API, please update it to its latest version to enjoy BuddyPress Notices.' );

			document.querySelector( '#bp-notices-toggler' ).addEventListener( 'click', ( e ) => {
				e.preventDefault();

				let url = '';
				if ( 'BUTTON' !== e.target.nodeName ) {
					url = e.target.closest( '#bp-notices-toggler' ).dataset.bpFallbackUrl;
				} else {
					url = e.target.dataset.bpFallbackUrl;
				}

				location.href = url;
			} );

		} else {
			this.container.classList.remove( 'no-popover-support' );

			// Adapt toggler according to popover state.
			this.container.addEventListener( 'toggle', ( e ) => {
				if ( 'open' === e.newState ) {
					if ( ! this.bubble.classList.contains( 'is-open' ) ) {
						this.bubble.classList.add( 'is-open' );
					}
				} else {
					this.bubble.classList.remove( 'is-open' );
				}
			} );
		}
	}
}

domReady( function() {
	const settings = window.bpNoticesCenterSettings || {};
	const bpManageNotices = new bpNoticesCenter( settings );

	bpManageNotices.start();
} );
