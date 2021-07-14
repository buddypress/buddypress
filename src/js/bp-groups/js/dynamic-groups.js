/**
 * WordPress dependencies
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
 * Front-end Dynamic Groups Widget Block class.
 *
 * @since 9.0.0
 */
class bpGroupsWidgetBlock extends dynamicWidgetBlock {
	loop( groups = [], container = '', type = 'active' ) {
		const tmpl = super.useTemplate( 'bp-dynamic-groups-item' );
		const selector = document.querySelector( '#' + container );
		let output = '';

		if ( groups && groups.length ) {
			groups.forEach( ( group ) => {
				if ( 'newest' === type && group.created_since ) {
					/* translators: %s is time elapsed since the group was created */
					group.extra = sprintf( __( 'Created %s', 'buddypress' ), group.created_since );
				} else if ( 'popular' === type && group.total_member_count ) {
					const membersCount = parseInt( group.total_member_count, 10 );

					if ( 0 === membersCount ) {
						group.extra = __( 'No members', 'buddypress' );
					} else if ( 1 === membersCount ) {
						group.extra = __( '1 member', 'buddypress' );
					} else {
						/* translators: %s is the number of Group members (more than 1). */
						group.extra = sprintf( __( '%s members', 'buddypress' ), group.total_member_count );
					}
				} else {
					/* translators: %s: a human time diff. */
					group.extra = sprintf( __( 'Active %s', 'buddypress' ), group.last_activity_diff );
				}

				/* Translators: %s is the group's name. */
				group.avatar_alt = sprintf( __( 'Group Profile photo of %s', 'buddypress' ), group.name );

				output += tmpl( group );
			} );
		} else {
			output = '<div class="widget-error">' + __( 'There are no groups to display.', 'buddypress' ) + '</div>';
		}

		selector.innerHTML = output;
	}

	start() {
		this.blocks.forEach( ( block, i ) => {
			const { selector } = block;
			const { type } = block.query_args;
			const list = document.querySelector( '#' + selector ).closest( '.bp-dynamic-block-container' );

			// Get default Block's type groups.
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

const settings = window.bpDynamicGroupsSettings || {};
const blocks = window.bpDynamicGroupsBlocks || [];
const bpDynamicGroups = new bpGroupsWidgetBlock( settings, blocks );

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', bpDynamicGroups.start() );
} else {
	bpDynamicGroups.start();
}
