/**
 * WordPress dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import './recent-posts.scss';
import editRecentPostsBlock from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'wordpress',
	},
	edit: editRecentPostsBlock,
} );
