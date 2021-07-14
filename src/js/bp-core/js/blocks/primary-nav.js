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
import editPrimaryNavBlock from './primary-nav/edit';
import transforms from './primary-nav/transforms';

registerBlockType( 'bp/primary-nav', {
	title: __( 'Primary navigation', 'buddypress' ),
	description: __( 'Displays BuddyPress primary nav in the sidebar of your site. Make sure to use it as the first widget of the sidebar and only once.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'buddicons-community',
	},
	category: 'buddypress',
	attributes: {
		displayTitle: {
			type: 'boolean',
			default: true,
		},
	},
	edit: editPrimaryNavBlock,
	transforms: transforms,
} );
