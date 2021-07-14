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
		RangeControl,
		TextControl,
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

const editOnlineMembersBlock = ( { attributes, setAttributes } ) => {
	const { title, maxMembers } = attributes;

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
					<RangeControl
						label={ __( 'Max members to show', 'buddypress' ) }
						value={ maxMembers }
						onChange={ ( value ) =>
							setAttributes( { maxMembers: value } )
						}
						min={ 1 }
						max={ 15 }
						required
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/online-members" attributes={ attributes } />
			</Disabled>
		</Fragment>
	);
};

export default editOnlineMembersBlock;
