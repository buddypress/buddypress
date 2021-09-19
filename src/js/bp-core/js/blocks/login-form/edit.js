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
		ToggleControl,
	},
	element: {
		Fragment,
		createElement,
	},
	i18n: {
		__,
	},
} = wp;

/**
 * BuddyPress dependencies.
 */
const {
	blockComponents: {
		ServerSideRender,
	},
} = bp;

const editLoginForm = ( { attributes, setAttributes } ) => {
	const { title,forgotPwdLink } = attributes;

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
					<ToggleControl
						label={ __( 'Include the link to reset the user password', 'buddypress' ) }
						checked={ !! forgotPwdLink }
						onChange={ () => {
							setAttributes( { forgotPwdLink: ! forgotPwdLink } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/login-form" attributes={ attributes } />
			</Disabled>
		</Fragment>
	);
};

export default editLoginForm;
