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
import editDynamicFriendsBlock from './friends/edit';
import transforms from './friends/transforms';

registerBlockType( 'bp/friends', {
	title: __( 'Friends List', 'buddypress' ),
	description: __( 'A dynamic list of recently active, popular, and newest friends of the post author (when used into a page or post) or of the displayed member (when used in a widgetized area). If author/member data is not available the block is not displayed.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'buddicons-friends',
	},
	category: 'buddypress',
	attributes: {
		maxFriends: {
			type: 'number',
			default: 5
		},
		friendDefault: {
			type: 'string',
			default: 'active',
		},
		linkTitle: {
			type: 'boolean',
			default: false,
		},
		postId: {
			type: 'number',
			default: 0,
		},
	},
	edit: editDynamicFriendsBlock,
	transforms: transforms,
} );
