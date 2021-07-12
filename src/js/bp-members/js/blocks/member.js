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
import editMemberBlock from './member/edit';

registerBlockType( 'bp/member', {
	title: __( 'Member', 'buddypress' ),
	description: __( 'BuddyPress Member.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'admin-users',
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
		displayMentionSlug: {
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
	edit: editMemberBlock,
} );
