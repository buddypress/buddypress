/**
 * WordPress dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import './latest-activities.scss';
import editDynamicActivitiesBlock from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'buddicons-activity',
	},
	edit: editDynamicActivitiesBlock,
} );
