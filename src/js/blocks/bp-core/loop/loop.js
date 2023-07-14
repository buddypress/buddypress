/**
 * WordPress dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import './loop.scss';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'image-rotate',
	},
	edit: () => {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				<Placeholder
					className="block-editor-bp-placeholder bp-loop"
					label= { __( 'BuddyPress items loop', 'buddypress') }
				/>
			</div>
		);
	},
	save: () => {
		return null;
	},
} );
