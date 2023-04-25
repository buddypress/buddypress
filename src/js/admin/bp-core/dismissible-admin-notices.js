// Use the bp global.
window.bp = window.bp || {};

/**
 * Use an XHR request to dismiss admin notices.
 *
 * @since 10.0.0
 */
bp.DismissibleAdminNotices = class {
	constructor( settings ) {
		this.settings = settings || {};
	}

	start() {
		const { url, nonce } = this.settings;

		if ( ! url || ! nonce ) {
			return;
		}

		document.querySelectorAll( '.bp-is-dismissible' ).forEach( ( notice ) => {
			notice.addEventListener( 'click', ( event ) => {
				event.preventDefault();

				const noticeLink = event.target;
				if ( noticeLink.classList.contains( 'loading' ) ) {
					return;
				}

				// Prevent multiple clicks.
				noticeLink.classList.add( 'loading' );

				// Set the notice ID & notice container.
				const { notice_id } = noticeLink.dataset;
				const noticeContainer = noticeLink.closest( '.bp-notice-container' );

				// Set notice headers.
				const noticeHeaders = new Headers( {
					'X-BP-Nonce' : nonce,
				} );

				// Set notice data.
				const noticeData = new FormData();
				noticeData.append( 'action', 'bp_dismiss_notice' );
				noticeData.append( 'notice_id', notice_id );

				fetch( url, {
					method: 'POST',
					headers: noticeHeaders,
					body: noticeData,
				} ).then( ( response ) => {
					return response.json();
				} ).then( ( data ) => {
					const { success } = data;

					if ( success ) {
						noticeContainer.remove();
					} else {
						noticeLink.classList.remove( 'loading' );
					}
				} );
			} );
		} );
	}
}

const settings = window.bpDismissibleAdminNoticesSettings || {};
const bpDismissibleAdminNotices = new bp.DismissibleAdminNotices( settings );

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', bpDismissibleAdminNotices.start() );
} else {
	bpDismissibleAdminNotices.start();
}
