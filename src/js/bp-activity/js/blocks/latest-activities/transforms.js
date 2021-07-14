/**
 * WordPress dependencies.
 */
const {
	blocks: {
		createBlock,
	},
} = wp;

/**
 * Transforms Nouveau Activity Widget to Activity Block.
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

				return idBase === 'bp_latest_activities';
			},
			transform: ( { instance } ) => {
				const regex = /i:\d*;s:\d*:"(.*?)";/gmi;
				let types = [];
				let matches;

				while ( ( matches = regex.exec( instance.raw.type ) ) !== null ) {
					if ( matches.index === regex.lastIndex ) {
						regex.lastIndex++;
					}

					matches.forEach( ( match, groupIndex ) => {
						if ( 1 === groupIndex ) {
							types.push( match );
						}
					} );
				}

				return createBlock( 'bp/latest-activities', {
					title: instance.raw.title,
					maxActivities: parseInt( instance.raw.max, 10 ),
					type: types,
				} );
			},
		},
	],
};

export default transforms;
