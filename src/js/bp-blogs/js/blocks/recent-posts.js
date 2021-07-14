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
import editRecentPostsBlock from './recent-posts/edit';
import transforms from './recent-posts/transforms';

registerBlockType( 'bp/recent-posts', {
	title: __( 'Recent Networkwide Posts', 'buddypress' ),
	description: __( 'A list of recently published posts from across your network.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'wordpress',
	},
	category: 'buddypress',
	attributes: {
		title: {
			type: 'string',
			default: __( 'Recent Networkwide Posts', 'buddypress' ),
		},
		maxPosts: {
			type: 'number',
			default: 10
		},
		linkTitle: {
			type: 'boolean',
			default: false,
		},
	},
	edit: editRecentPostsBlock,
	transforms: transforms,
} );
