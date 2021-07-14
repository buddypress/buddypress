/**
 * WordPress dependencies.
 */
const {
	blocks: {
		createBlock,
	},
} = wp;

/**
 * Transforms Legacy Widget to Online Members Block.
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

				return idBase === 'bp_core_whos_online_widget';
			},
			transform: ( { instance } ) => {
				return createBlock( 'bp/online-members', {
					title: instance.raw.title,
					maxMembers: instance.raw.max_members,
				} );
			},
		},
	],
};

export default transforms;
