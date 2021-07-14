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
import editOnlineMembersBlock from './online-members/edit';
import transforms from './online-members/transforms';

registerBlockType( 'bp/online-members', {
	title: __( 'Online Members', 'buddypress' ),
	description: __( 'Profile photos of online users.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'groups',
	},
	category: 'buddypress',
	attributes: {
		title: {
			type: 'string',
			default: __( 'Who\'s Online', 'buddypress' ),
		},
		maxMembers: {
			type: 'number',
			default: 15
		},
	},
	edit: editOnlineMembersBlock,
	transforms: transforms,
} );
