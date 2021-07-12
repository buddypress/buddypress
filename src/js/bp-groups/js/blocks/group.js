/**
 * WordPress dependencies.
 */
const {
	blocks: {
		registerBlockType,
	},
	i18n: {
		__,
	},
} = wp;

/**
 * Internal dependencies.
 */
import editGroupBlock from './group/edit';

registerBlockType( 'bp/group', {
	title: __( 'Group', 'buddypress' ),
	description: __( 'BuddyPress Group.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'buddicons-groups',
	},
	category: 'buddypress',
	attributes: {
		itemID: {
			type: 'integer',
			default: 0,
		},
		avatarSize: {
			type: 'string',
			default: 'full',
		},
		displayDescription: {
			type: 'boolean',
			default: true,
		},
		displayActionButton: {
			type: 'boolean',
			default: true,
		},
		displayCoverImage: {
			type: 'boolean',
			default: true,
		},
	},
	edit: editGroupBlock,
} );
