/**
 * WordPress dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import editEmbedActivityBlock from './edit';
import saveEmbedActivityBlock from './save';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'buddicons-activity',
	},
	edit: editEmbedActivityBlock,
	save: saveEmbedActivityBlock,
} );
