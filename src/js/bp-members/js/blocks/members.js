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
import editMembersBlock from './members/edit';

registerBlockType( 'bp/members', {
	title: __( 'Members', 'buddypress' ),
	description: __( 'BuddyPress Members.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'groups',
	},
	category: 'buddypress',
	attributes: {
		itemIDs: {
			type: 'array',
			items: {
				type: 'integer',
			},
			default: [],
		},
		avatarSize: {
			type: 'string',
			default: 'full',
		},
		displayMentionSlug: {
			type: 'boolean',
			default: true,
		},
		displayUserName: {
			type: 'boolean',
			default: true,
		},
		extraData: {
			type: 'string',
			default: 'none',
		},
		layoutPreference: {
			type: 'string',
			default: 'list',
		},
		columns: {
			type: 'number',
			default: 2
		},
	},
	edit: editMembersBlock,
} );
