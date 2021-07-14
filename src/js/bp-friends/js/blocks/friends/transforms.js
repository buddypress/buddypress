/**
 * WordPress dependencies.
 */
const {
	blocks: {
		createBlock,
	},
} = wp;

/**
 * Transforms Legacy Widget to Friends Block.
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

				return idBase === 'bp_core_friends_widget';
			},
			transform: ( { instance } ) => {
				return createBlock( 'bp/friends', {
					maxFriends: instance.raw.max_friends,
					friendDefault: instance.raw.friend_default,
					linkTitle: instance.raw.link_title,
				} );
			},
		},
	],
};

export default transforms;
