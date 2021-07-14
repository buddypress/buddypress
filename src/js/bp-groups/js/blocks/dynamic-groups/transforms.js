/**
 * WordPress dependencies.
 */
const {
	blocks: {
		createBlock,
	},
} = wp;

/**
 * Transforms Legacy Widget to Dynamic Groups Block.
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

				return idBase === 'bp_groups_widget';
			},
			transform: ( { instance } ) => {
				return createBlock( 'bp/dynamic-groups', {
					title: instance.raw.title,
					maxGroups: instance.raw.max_groups,
					groupDefault: instance.raw.group_default,
					linkTitle: instance.raw.link_title,
				} );
			},
		},
	],
};

export default transforms;
