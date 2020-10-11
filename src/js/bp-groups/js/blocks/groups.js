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
import editGroupsBlock from './groups/edit';

registerBlockType( 'bp/groups', {
	title: __( 'Groups', 'buddypress' ),
	description: __( 'BuddyPress Groups.', 'buddypress' ),
	icon: 'buddicons-groups',
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
		displayGroupName: {
			type: 'boolean',
			default: true,
		},
		extraInfo: {
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
	edit: editGroupsBlock,
} );
