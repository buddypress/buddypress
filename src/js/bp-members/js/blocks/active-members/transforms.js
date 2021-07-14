/**
 * WordPress dependencies.
 */
const {
	blocks: {
		createBlock,
	},
} = wp;

/**
 * Transforms Legacy Widget to Active Members Block.
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

				return idBase === 'bp_core_recently_active_widget';
			},
			transform: ( { instance } ) => {
				return createBlock( 'bp/active-members', {
					title: instance.raw.title,
					maxMembers: instance.raw.max_members,
				} );
			},
		},
	],
};

export default transforms;
