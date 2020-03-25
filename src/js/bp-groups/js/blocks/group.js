/**
 * WordPress dependencies.
 */
const { registerBlockType } = wp.blocks;
const { createElement, Fragment } = wp.element;
const { Placeholder, Disabled, PanelBody, SelectControl, ToggleControl, Toolbar, ToolbarButton } = wp.components;
const { InspectorControls, BlockControls } = wp.blockEditor;
const { withSelect } = wp.data;
const { compose } = wp.compose;
const { ServerSideRender } = wp.editor;
const { __ } = wp.i18n;

/**
 * BuddyPress dependencies.
 */
const { AutoCompleter } = bp.blockComponents;

const AVATAR_SIZES = [
	{
		label: __( 'None', 'buddypress' ),
		value: 'none',
	},
	{
		label: __( 'Thumb', 'buddypress' ),
		value: 'thumb',
	},
	{
		label: __( 'Full', 'buddypress' ),
		value: 'full',
	},
];

const editGroup = ( { attributes, setAttributes, bpSettings } ) => {
	const { isAvatarEnabled, isCoverImageEnabled } = bpSettings;
	const { avatarSize, displayDescription, displayActionButton, displayCoverImage } = attributes;

	if ( ! attributes.itemID ) {
		return (
			<Placeholder
				icon="buddicons-groups"
				label={ __( 'BuddyPress Group', 'buddypress' ) }
				instructions={ __( 'Start typing the name of the group you want to feature into this post.', 'buddypress' ) }
			>
				<AutoCompleter
					component="groups"
					objectStatus="public"
					ariaLabel={ __( 'Group\'s name', 'buddypress' ) }
					placeholder={ __( 'Enter Group\'s name here…', 'buddypress' ) }
					onSelectItem={ setAttributes }
					useAvatar={ isAvatarEnabled }
				/>
			</Placeholder>
		);
	}

	return (
		<Fragment>
			<BlockControls>
				<Toolbar>
					<ToolbarButton
						icon="edit"
						title={ __( 'Select another group', 'buddypress' ) }
						onClick={ () =>{
							setAttributes( { itemID: 0 } );
						} }
					/>
				</Toolbar>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Group\'s home button settings', 'buddypress' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Display Group\'s home button', 'buddypress' ) }
						checked={ !! displayActionButton }
						onChange={ () => {
							setAttributes( { displayActionButton: ! displayActionButton } );
						} }
						help={
							displayActionButton
								? __( 'Include a link to the group\'s home page under their name.', 'buddypress' )
								: __( 'Toggle to display a link to the group\'s home page under their name.', 'buddypress' )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Description settings', 'buddypress' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Display group\'s description', 'buddypress' ) }
						checked={ !! displayDescription }
						onChange={ () => {
							setAttributes( { displayDescription: ! displayDescription } );
						} }
						help={
							displayDescription
								? __( 'Include the group\'s description under their name.', 'buddypress' )
								: __( 'Toggle to display the group\'s description under their name.', 'buddypress' )
						}
					/>
				</PanelBody>
				{ isAvatarEnabled && (
					<PanelBody title={ __( 'Avatar settings', 'buddypress' ) } initialOpen={ false }>
						<SelectControl
							label={ __( 'Size', 'buddypress' ) }
							value={ avatarSize }
							options={ AVATAR_SIZES }
							onChange={ ( option ) => {
								setAttributes( { avatarSize: option } );
							} }
						/>
					</PanelBody>
				) }
				{ isCoverImageEnabled && (
					<PanelBody title={ __( 'Cover image settings', 'buddypress' ) } initialOpen={ false }>
						<ToggleControl
							label={ __( 'Display Cover Image', 'buddypress' ) }
							checked={ !! displayCoverImage }
							onChange={ () => {
								setAttributes( { displayCoverImage: ! displayCoverImage } );
							} }
							help={
								displayCoverImage
									? __( 'Include the group\'s cover image over their name.', 'buddypress' )
									: __( 'Toggle to display the group\'s cover image over their name.', 'buddypress' )
							}
						/>
					</PanelBody>
				) }
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/group" attributes={ attributes } />
			</Disabled>
		</Fragment>
	);
};

const editGroupBlock = compose( [
	withSelect( ( select ) => {
		const editorSettings = select( 'core/editor' ).getEditorSettings();
		return {
			bpSettings: editorSettings.bp.groups || {},
		};
	} ),
] )( editGroup );

registerBlockType( 'bp/group', {
	title: __( 'Group', 'buddypress' ),

	description: __( 'BuddyPress Group.', 'buddypress' ),

	icon: 'buddicons-groups',

	category: 'buddypress',

	attributes: {
		itemID: {
			type: 'integer',
			default: 0,
		},
		avatarSize: {
			type: 'string',
			default: 'full',
		},
		displayDescription: {
			type: 'boolean',
			default: true,
		},
		displayActionButton: {
			type: 'boolean',
			default: true,
		},
		displayCoverImage: {
			type: 'boolean',
			default: true,
		},
	},

	edit: editGroupBlock,
} );
