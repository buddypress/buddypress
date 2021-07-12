/**
 * WordPress dependencies.
 */
const {
	i18n: {
		__,
	},
	blocks: {
		registerBlockType,
	},
} = wp;

/**
 * Internal dependencies.
 */
import editEmbedActivityBlock from './embed-activity/edit';
import saveEmbedActivityBlock from './embed-activity/save';

registerBlockType( 'bp/embed-activity', {
	title: __( 'Embed an activity', 'buddypress' ),
	description: __( 'Add a block that displays the activity content pulled from this or other community sites.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'buddicons-activity',
	},
	category: 'buddypress',
	attributes: {
		url: {
			type: 'string',
		},
		caption: {
			type: 'string',
			source: 'html',
			selector: 'figcaption',
		},
	},
	supports: {
		align: true,
	},
	edit: editEmbedActivityBlock,
	save: saveEmbedActivityBlock,
} );
