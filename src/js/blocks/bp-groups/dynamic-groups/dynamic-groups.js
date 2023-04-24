/**
 * WordPress dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import './dynamic-groups.scss';
import editDynamicGroupsBlock from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'buddicons-groups',
	},
	edit: editDynamicGroupsBlock,
} );
