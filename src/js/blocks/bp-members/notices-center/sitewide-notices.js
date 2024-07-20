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
		event.preventDefault();
	}

	/**
	 * Notices Center Class starter.
	 *
	 * @since 15.0.0
	 */
	start() {
		// Use event delegation to catch all clicks happening into the Center.
		this.container.addEventListener( 'click', this.catchEvents.bind( this ), false );

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

const settings = window.bpNoticesCenterSettings || {};
const bpManageNotices = new bpNoticesCenter( settings );

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', bpManageNotices.start() );
} else {
	bpManageNotices.start();
}
