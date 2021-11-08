/**
 * WordPress dependencies.
 */
const {
	blockEditor: {
		InspectorControls,
		BlockControls,
	},
	components: {
		Placeholder,
		Disabled,
		PanelBody,
		SelectControl,
		ToggleControl,
		Toolbar,
		ToolbarButton,
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
	blockComponents: {
		AutoCompleter,
	},
	blockData: {
		isActive,
	}
} = bp;

/**
 * Internal dependencies.
 */
import { AVATAR_SIZES, GROUP_STATI } from './constants';

const getSlugValue = ( item ) => {
	if ( item && item.status && GROUP_STATI[ item.status ] ) {
		return GROUP_STATI[ item.status ];
	}

	return null;
}

const editGroupBlock = ( { attributes, setAttributes } ) => {
	const isAvatarEnabled = isActive( 'groups', 'avatar' );
	const isCoverImageEnabled = isActive( 'groups', 'cover' );
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
				<Toolbar label={ __( 'Block toolbar', 'buddypress' ) }>
					<ToolbarButton
						icon="edit"
						title={ __( 'Select another group', 'buddypress' ) }
						onClick={ () => {
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

export default editGroupBlock;
