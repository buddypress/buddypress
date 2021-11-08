/**
 * WordPress dependencies.
 */
 const {
	blockEditor: {
		InspectorControls,
	},
	components: {
		Disabled,
		PanelBody,
		TextControl,
	},
	element: {
		Fragment,
		createElement,
	},
	i18n: {
		__,
	},
	serverSideRender: ServerSideRender,
} = wp;

const editSitewideNoticesBlock = ( { attributes, setAttributes } ) => {
	const { title } = attributes;

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true }>
					<TextControl
						label={ __( 'Title', 'buddypress' ) }
						value={ title }
						onChange={ ( text ) => {
							setAttributes( { title: text } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/sitewide-notices" attributes={ attributes } />
			</Disabled>
		</Fragment>
	);
};

export default editSitewideNoticesBlock;
