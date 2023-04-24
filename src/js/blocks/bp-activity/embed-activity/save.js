/**
 * WordPress dependencies.
 */
import {
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';

const saveEmbedActivityBlock = ( { attributes } ) => {
	const blockProps = useBlockProps.save( {
		className: 'wp-block-embed is-type-bp-activity',
	} );
	const { url, caption } = attributes;

	if ( ! url ) {
		return null;
	}

	return (
		<figure { ...blockProps }>
			<div className="wp-block-embed__wrapper">
			{
				`\n${ url }\n` /* URL needs to be on its own line. */
			}
			</div>
			{ ! RichText.isEmpty( caption ) && (
				<RichText.Content
					tagName="figcaption"
					value={ caption }
				/>
			) }
		</figure>
	);
};

export default saveEmbedActivityBlock;
