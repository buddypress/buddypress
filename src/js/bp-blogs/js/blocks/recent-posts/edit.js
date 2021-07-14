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

const editRecentPostsBlock = ( { attributes, setAttributes } ) => {
	const { title, maxPosts, linkTitle } = attributes;

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
						label={ __( 'Max posts to show', 'buddypress' ) }
						value={ maxPosts }
						onChange={ ( value ) =>
							setAttributes( { maxPosts: value } )
						}
						min={ 1 }
						max={ 10 }
						required
					/>
					<ToggleControl
						label={ __( 'Link block title to Blogs directory', 'buddypress' ) }
						checked={ !! linkTitle }
						onChange={ () => {
							setAttributes( { linkTitle: ! linkTitle } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/recent-posts" attributes={ attributes } />
			</Disabled>
		</Fragment>
	);
};

export default editRecentPostsBlock;
