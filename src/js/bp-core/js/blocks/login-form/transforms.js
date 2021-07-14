/**
 * WordPress dependencies.
 */
 const {
	blocks: {
		createBlock,
	},
} = wp;

/**
 * Transforms Legacy Login Form Widget to Login Form Block.
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

				return idBase === 'bp_core_login_widget';
			},
			transform: ( { instance } ) => {
				return createBlock( 'bp/login-form', {
					title: instance.raw.title,
				} );
			},
		},
	],
};

export default transforms;
