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
import editDynamicGroupsBlock from './dynamic-groups/edit';
import transforms from './dynamic-groups/transforms';

registerBlockType( 'bp/dynamic-groups', {
	title: __( 'Dynamic Groups List', 'buddypress' ),
	description: __( 'A dynamic list of recently active, popular, newest, or alphabetical groups.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'buddicons-groups',
	},
	category: 'buddypress',
	attributes: {
		title: {
			type: 'string',
			default: __( 'Groups', 'buddypress' ),
		},
		maxGroups: {
			type: 'number',
			default: 5
		},
		groupDefault: {
			type: 'string',
			default: 'active',
		},
		linkTitle: {
			type: 'boolean',
			default: false,
		},
	},
	edit: editDynamicGroupsBlock,
	transforms: transforms,
} );
