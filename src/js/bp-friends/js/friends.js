/**
 * WordPress dependencies.
 */
const {
	i18n: {
		__,
		sprintf,
	},
} = wp;

/**
 * BuddyPress dependencies.
 */
const {
	dynamicWidgetBlock,
} = bp;

/**
 * Front-end Friends block class.
 */
class bpFriendsWidgetBlock extends dynamicWidgetBlock {
	loop( friends = [], container = '', type = 'active' ) {
		const tmpl = super.useTemplate( 'bp-friends-item' );
		const selector = document.querySelector( '#' + container );
		let output = '';

		if ( friends && friends.length ) {
			friends.forEach( ( friend ) => {
				if ( 'active' === type && friend.last_activity ) {
					/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
					friend.extra = sprintf( __( 'Active %s', 'buddypress' ), friend.last_activity.timediff );
				} else if ( 'popular' === type && friend.total_friend_count ) {
					const friendsCount = parseInt( friend.total_friend_count, 10 );

					if ( 0 === friendsCount ) {
						friend.extra = __( 'No friends', 'buddypress' );
					} else if ( 1 === friendsCount ) {
						friend.extra = __( '1 friend', 'buddypress' );
					} else {
						/* translators: %s: total friend count (more than 1). */
						friend.extra = sprintf( __( '%s friends', 'buddypress' ), friend.total_friend_count );
					}
				} else if ( 'newest' === type && friend.registered_since ) {
					/* translators: %s is time elapsed since the registration date happened */
					friend.extra = sprintf( __( 'Registered %s', 'buddypress' ), friend.registered_since );
				}

				/* translators: %s: member name */
				friend.avatar_alt = sprintf( __( 'Profile picture of %s', 'buddypress' ), friend.name );

				output += tmpl( friend );
			} );
		} else {
			output = '<div class="widget-error">' + __( 'Sorry, no members were found.', 'buddypress' ) + '</div>';
		}

		selector.innerHTML = output;
	}

	start() {
		this.blocks.forEach( ( block, i ) => {
			const { selector } = block;
			const { type } = block.query_args;
			const list = document.querySelector( '#' + selector ).closest( '.bp-dynamic-block-container' );

			// Get default Block's type friends.
			super.getItems( type, i );

			// Listen to Block's Nav item clics
			list.querySelectorAll( '.item-options a' ).forEach( ( navItem ) => {
				navItem.addEventListener( 'click', ( event ) => {
					event.preventDefault();

					// Changes the displayed filter.
					event.target.closest( '.item-options' ).querySelector( '.selected' ).classList.remove( 'selected' );
					event.target.classList.add( 'selected' );

					const newType = event.target.getAttribute( 'data-bp-sort' );

					if ( newType !== this.blocks[ i ].query_args.type ) {
						super.getItems( newType, i );
					}
				} );
			} );
		} );
	}
}

const settings = window.bpFriendsSettings || {};
const blocks = window.bpFriendsBlocks || {};
const bpFriends = new bpFriendsWidgetBlock( settings, blocks );

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', bpFriends.start() );
} else {
	bpFriends.start();
}
