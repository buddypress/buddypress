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
		SelectControl,
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

/**
 * BuddyPress dependencies.
 */
const {
	blockData: {
		currentPostId,
		activityTypes,
	}
} = bp;

const editDynamicActivitiesBlock = ( { attributes, setAttributes } ) => {
	const { postId, maxActivities, type, title } = attributes;
	const post = currentPostId();
	const types = activityTypes();

	if ( ! postId && post ) {
		setAttributes( { postId: post } );
		if ( ! attributes.postId ) {
			attributes.postId = post;
		}
	}

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true } className="bp-latest-activities">
					<TextControl
						label={ __( 'Title', 'buddypress' ) }
						value={ title }
						onChange={ ( text ) => {
							setAttributes( { title: text } );
						} }
					/>
					<RangeControl
						label={ __( 'Maximum amount to display', 'buddypress' ) }
						value={ maxActivities }
						onChange={ ( value ) =>
							setAttributes( { maxActivities: value } )
						}
						min={ 1 }
						max={ 10 }
						required
					/>
					<SelectControl
						multiple
						label={ __( 'Type', 'buddypress' ) }
						value={ type }
						options={ types }
						onChange={ ( option ) => {
							setAttributes( { type: option } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/latest-activities" attributes={ attributes } />
			</Disabled>
		</Fragment>
	);
};

export default editDynamicActivitiesBlock;
