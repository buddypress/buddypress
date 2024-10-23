/**
 * WordPress dependencies.
 */
import domReady from '@wordpress/dom-ready';

/**
 * BuddyPress dependencies.
 */
import noticesRequest from '@buddypress/notices-controller';

/**
 * Front-end Notice function to dismiss an item.
 *
 * @since 15.0.0
 *
 * @param {PointerEvent} event The click event.
 */
const dismissNotice = ( event ) => {
	event.preventDefault();

	const noticeId = 'A' !== event.target.nodeName ? event.target.closest( '[data-bp-sitewide-notice-id]' ).dataset.bpSitewideNoticeId : event.target.dataset.bpSitewideNoticeId;

	// Send a notice request to dismiss the notice.
	noticesRequest( { action: 'dismiss/' + noticeId, method: 'POST' } ).then( result => {
		if ( true === result.dismissed ) {
			event.target.closest( '.bp-sitewide-notice-block' ).remove();
		}
	} ).catch( error => {
		console.error( error );
	} );
}

domReady( function() {
	document.querySelectorAll( '.bp-sitewide-notice-block a.dismiss-notice' ).forEach( ( dismissButton ) => {
		dismissButton.addEventListener( 'click', dismissNotice );
	} );
} );
