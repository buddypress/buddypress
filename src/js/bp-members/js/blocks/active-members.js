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
import editActiveMembersBlock from './active-members/edit';
import transforms from './active-members/transforms';

registerBlockType( 'bp/active-members', {
	title: __( 'Recently Active Members', 'buddypress' ),
	description: __( 'Profile photos of recently active members.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'groups',
	},
	category: 'buddypress',
	attributes: {
		title: {
			type: 'string',
			default: __( 'Recently Active Members', 'buddypress' ),
		},
		maxMembers: {
			type: 'number',
			default: 15
		},
	},
	edit: editActiveMembersBlock,
	transforms: transforms,
} );
