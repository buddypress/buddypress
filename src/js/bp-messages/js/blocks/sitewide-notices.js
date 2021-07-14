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
import editSitewideNoticesBlock from './sitewide-notices/edit';
import transforms from './sitewide-notices/transforms';

registerBlockType( 'bp/sitewide-notices', {
	title: __( 'Sitewide Notices', 'buddypress' ),
	description: __( 'Display Sitewide Notices posted by the site administrator', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'megaphone',
	},
	category: 'buddypress',
	attributes: {
		title: {
			type: 'string',
			default: '',
		},
	},
	edit: editSitewideNoticesBlock,
	transforms: transforms,
} );
