/**
 * WordPress dependencies.
 */
const {
	url: {
		addQueryArgs,
	},
} = wp;

/**
 * External dependencies.
 */
const {
	template,
} = lodash;

// Use the bp global.
window.bp = window.bp || {};

/**
 * Generic class to be used by Dynamic Widget Blocks.
 *
 * @since 9.0.0
 */
bp.dynamicWidgetBlock = class bpDynamicWidgetBlock {
	constructor( settings, blocks ) {
		const { path, root, nonce } = settings;
		this.path = path;
		this.root = root;
		this.nonce = nonce,
		this.blocks = blocks;

		this.blocks.forEach( ( block, i ) => {
			const { type } = block.query_args || 'active';
			const { body } = block.preloaded || [];

			this.blocks[ i ].items = {
				'active': [],
				'newest': [],
				'popular': [],
				'alphabetical': [],
			}

			if ( ! this.blocks[ i ].items[ type ].length && body && body.length ) {
				this.blocks[ i ].items[ type ] = body;
			}
		} );
	}

	useTemplate( tmpl ) {
		const options = {
			evaluate:    /<#([\s\S]+?)#>/g,
			interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
			escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
			variable:    'data'
		};

		return template( document.querySelector( '#tmpl-' + tmpl ).innerHTML, options );
	}

	loop() {
		// This method needs to be overriden.
	}

	getItems( type = 'active', blockIndex = 0 ) {
		this.blocks[ blockIndex ].query_args.type = type;

		if ( this.blocks[ blockIndex ].items[ type ].length ) {
			this.loop( this.blocks[ blockIndex ].items[ type ], this.blocks[ blockIndex ].selector, type );
		} else {
			fetch( addQueryArgs( this.root + this.path, this.blocks[ blockIndex ].query_args ), {
				method: 'GET',
				headers: {
					'X-WP-Nonce' : this.nonce,
				}
			} ).then(
				( response ) => response.json()
			).then(
				( data ) => {
					this.blocks[ blockIndex ].items[ type ] = data;
					this.loop( this.blocks[ blockIndex ].items[ type ], this.blocks[ blockIndex ].selector, type );
				}
			);
		}
	}
};
