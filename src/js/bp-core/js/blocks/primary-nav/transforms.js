/**
 * WordPress dependencies.
 */
 const {
	blocks: {
		createBlock,
	},
} = wp;

/**
 * Transforms Legacy Widget to Primary Nav Block.
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

				return idBase === 'bp_nouveau_sidebar_object_nav_widget';
			},
			transform: ( { instance } ) => {
				return createBlock( 'bp/primary-nav', {
					displayTitle: instance.raw.bp_nouveau_widget_title,
				} );
			},
		},
	],
};

export default transforms;
