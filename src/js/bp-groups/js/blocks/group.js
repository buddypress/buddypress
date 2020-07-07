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

const GROUP_STATI = {
	public: __( 'Public', 'buddypress' ),
	private: __( 'Private', 'buddypress' ),
	hidden: __( 'Hidden', 'buddypress' ),
};

const getSlugValue = ( item ) => {
	if ( item && item.status && GROUP_STATI[ item.status ] ) {
		return GROUP_STATI[ item.status ];
	}

	return null;
}

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
					objectQueryArgs={ { 'show_hidden': false } }
					slugValue={ getSlugValue }
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
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true }>
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

					{ isAvatarEnabled && (
						<SelectControl
							label={ __( 'Avatar size', 'buddypress' ) }
							value={ avatarSize }
							options={ AVATAR_SIZES }
							help={ __( 'Select "None" to disable the avatar.', 'buddypress' ) }
							onChange={ ( option ) => {
								setAttributes( { avatarSize: option } );
							} }
						/>
					) }

					{ isCoverImageEnabled && (
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
					) }
				</PanelBody>
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
