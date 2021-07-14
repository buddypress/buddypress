/**
 * WordPress dependencies.
 */
const {
	blockEditor: {
		InspectorControls,
	},
	components: {
		Disabled,
		Notice,
		PanelBody,
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
	blockData: {
		getCurrentWidgetsSidebar,
	}
} = bp;

const editPrimaryNavBlock = ( { attributes, setAttributes, clientId } ) => {
	const { displayTitle } = attributes;
	const currentSidebar = getCurrentWidgetsSidebar( clientId );
	const disabledSidebars = ['sidebar-buddypress-members', 'sidebar-buddypress-groups'];

	if ( currentSidebar && currentSidebar.id && -1 !== disabledSidebars.indexOf( currentSidebar.id ) ) {
		return (
			<Notice status="error" isDismissible={ false }>
				<p>
					{ __( 'The BuddyPress Primary Navigation block shouldn\'t be used into this widget area. Please remove it.', 'buddypress' ) }
				</p>
			</Notice>
		);
	}

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Include navigation title', 'buddypress' ) }
						checked={ !! displayTitle }
						onChange={ () => {
							setAttributes( { displayTitle: ! displayTitle } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/primary-nav" attributes={ attributes } />
			</Disabled>
		</Fragment>
	);
};

export default editPrimaryNavBlock;
