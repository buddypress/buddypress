/**
 * WordPress dependencies.
 */
const {
	blockEditor: {
		RichText,
	},
	element: {
		createElement,
	},
} = wp;

const saveEmbedActivityBlock = ( { attributes } ) => {
	const { url, caption } = attributes;

	if ( ! url ) {
		return null;
	}

	return (
		<figure className="wp-block-embed is-type-bp-activity">
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
