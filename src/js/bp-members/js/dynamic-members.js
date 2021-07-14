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
 * Front-end Dynamic Members Widget Block class.
 *
 * @since 9.0.0
 */
class bpMembersWidgetBlock extends dynamicWidgetBlock {
	loop( members = [], container = '', type = 'active' ) {
		const tmpl = super.useTemplate( 'bp-dynamic-members-item' );
		const selector = document.querySelector( '#' + container );
		let output = '';

		if ( members && members.length ) {
			members.forEach( ( member ) => {
				if ( 'active' === type && member.last_activity ) {
					/* translators: %s: a human time diff. */
					member.extra = sprintf( __( 'Active %s', 'buddypress' ), member.last_activity.timediff );
				} else if ( 'popular' === type && member.total_friend_count ) {
					const friendsCount = parseInt( member.total_friend_count, 10 );

					if ( 0 === friendsCount ) {
						member.extra = __( 'No friends', 'buddypress' );
					} else if ( 1 === friendsCount ) {
						member.extra = __( '1 friend', 'buddypress' );
					} else {
						/* translators: %s: total friend count (more than 1). */
						member.extra = sprintf( __( '%s friends', 'buddypress' ), member.total_friend_count );
					}
				} else if ( 'newest' === type && member.registered_since ) {
					/* translators: %s is time elapsed since the registration date happened */
					member.extra = sprintf( __( 'Registered %s', 'buddypress' ), member.registered_since );
				}

				/* translators: %s: member name */
				member.avatar_alt = sprintf( __( 'Profile picture of %s', 'buddypress' ), member.name );

				output += tmpl( member );
			} );
		} else {
			output = '<div class="widget-error">' + __( 'No members found.', 'buddypress' ) + '</div>';
		}

		selector.innerHTML = output;
	}

	start() {
		this.blocks.forEach( ( block, i ) => {
			const { selector } = block;
			const { type } = block.query_args;
			const list = document.querySelector( '#' + selector ).closest( '.bp-dynamic-block-container' );

			// Get default Block's type members.
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

const settings = window.bpDynamicMembersSettings || {};
const blocks = window.bpDynamicMembersBlocks || {};
const bpDynamicMembers = new bpMembersWidgetBlock( settings, blocks );

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', bpDynamicMembers.start() );
} else {
	bpDynamicMembers.start();
}
