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

				return idBase === 'bp_messages_sitewide_notices_widget';
			},
			transform: ( { instance } ) => {
				return createBlock( 'bp/sitewide-notices', {
					title: instance.raw.title
				} );
			},
		},
	],
};

export default transforms;
