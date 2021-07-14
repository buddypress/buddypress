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
import editDynamicMembersBlock from './dynamic-members/edit';
import transforms from './dynamic-members/transforms';

registerBlockType( 'bp/dynamic-members', {
	title: __( 'Dynamic Members List', 'buddypress' ),
	description: __( 'A dynamic list of recently active, popular, and newest members.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'groups',
	},
	category: 'buddypress',
	attributes: {
		title: {
			type: 'string',
			default: __( 'Members', 'buddypress' ),
		},
		maxMembers: {
			type: 'number',
			default: 5
		},
		memberDefault: {
			type: 'string',
			default: 'active',
		},
		linkTitle: {
			type: 'boolean',
			default: false,
		},
	},
	edit: editDynamicMembersBlock,
	transforms: transforms,
} );
