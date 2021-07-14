/**
 * WordPress dependencies.
 */
const {
	blocks: {
		createBlock,
	},
} = wp;

/**
 * Transforms Legacy Widget to Dynamic Members Block.
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

				return idBase === 'bp_core_members_widget';
			},
			transform: ( { instance } ) => {
				return createBlock( 'bp/dynamic-members', {
					title: instance.raw.title,
					maxMembers: instance.raw.max_members,
					memberDefault: instance.raw.member_default,
					linkTitle: instance.raw.link_title,
				} );
			},
		},
	],
};

export default transforms;
