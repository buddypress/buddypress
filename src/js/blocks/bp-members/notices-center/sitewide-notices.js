/**
 * WordPress dependencies.
 */
import domReady from '@wordpress/dom-ready';

/**
 * BuddyPress dependencies.
 */
import noticesRequest from '@buddypress/notices-controller';

/**
 * Notices center class.
 *
 * @since 15.0.0
 */
class bpNoticesCenter {
	constructor() {
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
			const noticeContainers = document.querySelectorAll( '.notice-item' );

			// When it's not the last displayed notice, do not refresh the page.
			if ( 1 < noticeContainers.length ) {
				event.preventDefault();

				// Prepare pagination update.
				let noticesPagination = [];
				noticeContainers.forEach( ( container ) => {
					const noticePagination = {
						id: parseInt( container.getAttribute( 'id' ).replace( 'notice-', '' ), 10 ),
						prevLink: container.querySelector( '.bp-notice-prev-page a' ),
						nextLink: container.querySelector( '.bp-notice-next-page a' ),
						priorityPagination: container.querySelector( '.priority-pagination' ),
						totalCount: container.querySelector( '.total-notices-count' ),
					}

					noticesPagination.push( noticePagination );
				} );

				const noticeId = parseInt( event.target.dataset.bpDismissId, 10 );
				const noticeStatus = document.querySelector( 'article#notice-' + noticeId + ' .bp-notice-request-status' );

				// Clean potential notice errors.
				noticeStatus.classList.remove( 'error' );
				noticeStatus.querySelector( 'p' ).innerHTML = '';

				// Send a notice request to dismiss the notice.
				noticesRequest( { action: 'dismiss/' + noticeId, method: 'POST' } ).then( result => {
					if ( true === result.dismissed ) {
						event.target.closest( 'article#notice-' + noticeId ).remove();

						// Update WP Admin Bar Notices count.
						const wpAdminCount = this.bubble.querySelector( '.count' );
						wpAdminCount.innerHTML = parseInt( wpAdminCount.innerHTML, 10 ) - 1;

						// Update pagination.
						const noticesPaginationIndex = noticesPagination.findIndex( ( { id } ) => id === noticeId );
						if ( -1 !== noticesPaginationIndex ) {
							noticesPagination.splice( noticesPaginationIndex, 1 );
							noticesPagination.forEach( ( pagination, paginationIndex ) => {
								if ( 0 === paginationIndex && null !== pagination.prevLink ) {
									pagination.prevLink.remove();
									noticesPagination[ paginationIndex ]['prevLink'] = null;
								} else if ( pagination.prevLink ) {
									pagination.prevLink.setAttribute( 'href', '#notice-' + noticesPagination[ paginationIndex - 1 ]['id'] );
									noticesPagination[ paginationIndex ]['prevLink'] = pagination.prevLink;
								}

								if ( noticesPagination.length - 1 === paginationIndex && null !== pagination.nextLink ) {
									pagination.nextLink.remove();
									noticesPagination[ paginationIndex ]['nextLink'] = null;
								} else if ( pagination.nextLink ) {
									pagination.nextLink.setAttribute( 'href', '#notice-' + noticesPagination[ paginationIndex + 1 ]['id'] );
									noticesPagination[ paginationIndex ]['nextLink'] = pagination.nextLink;
								}

								pagination.priorityPagination.innerHTML = paginationIndex + 1 + '/' + noticesPagination.length;
								noticesPagination[ paginationIndex ]['priorityPagination'] = pagination.priorityPagination;

								if ( pagination.totalCount ) {
									pagination.totalCount.innerHTML = parseInt( pagination.totalCount.innerHTML, 10 ) - 1;
									noticesPagination[ paginationIndex ]['totalCount'] = pagination.totalCount;
								}
							} );
						}
					}
				} ).catch( error => {
					noticeStatus.querySelector( 'p' ).innerHTML = error;
					noticeStatus.classList.add( 'error' );
				} );
			}
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
	const bpManageNotices = new bpNoticesCenter();

	bpManageNotices.start();
} );
