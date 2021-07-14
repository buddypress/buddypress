/**
 * WordPress dependencies.
 */
const {
	blocks: {
		createBlock,
	},
} = wp;

/**
 * Transforms Legacy Widget to Recent Posts Block.
 *
 * @type {Object}
 */
const transforms = {
	from: [
		{
			type: 'block',
			blocks: [ 'core/legacy-widget' ],
			isMatch: ( { idBase, instance } ) => {
				if ( ! instance?.raw ) {
					return false;
				}

				return idBase === 'bp_blogs_recent_posts_widget';
			},
			transform: ( { instance } ) => {
				return createBlock( 'bp/recent-posts', {
					title: instance.raw.title,
					maxPosts: instance.raw.max_posts,
					linkTitle: instance.raw.link_title,
				} );
			},
		},
	],
};

export default transforms;
