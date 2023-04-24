/**
 * WordPress dependencies.
 */
import {
    InspectorControls,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Placeholder,
	Disabled,
	PanelBody,
	SelectControl,
	ToggleControl,
	Toolbar,
	ToolbarButton,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * BuddyPress dependencies.
 */
import { AutoCompleter } from '@buddypress/block-components';
import { isActive } from '@buddypress/block-data';

/**
 * Internal dependencies.
 */
import { AVATAR_SIZES } from './constants';

const getSlugValue = ( item ) => {
	if ( item && item.mention_name ) {
		return item.mention_name;
	}

	return null;
}

const editMemberBlock = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const isAvatarEnabled = isActive( 'members', 'avatar' );
	const isMentionEnabled = isActive( 'activity', 'mentions' );
	const isCoverImageEnabled = isActive( 'members', 'cover' );
	const { avatarSize, displayMentionSlug, displayActionButton, displayCoverImage } = attributes;

	if ( ! attributes.itemID ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="admin-users"
					label={ __( 'BuddyPress Member', 'buddypress' ) }
					instructions={ __( 'Start typing the name of the member you want to feature into this post.', 'buddypress' ) }
				>
					<AutoCompleter
						component="members"
						slugValue={ getSlugValue }
						ariaLabel={ __( 'Member\'s username', 'buddypress' ) }
						placeholder={ __( 'Enter Member\'s username here…', 'buddypress' ) }
						onSelectItem={ setAttributes }
						useAvatar={ isAvatarEnabled }
					/>
				</Placeholder>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<BlockControls>
				<Toolbar label={ __( 'Block toolbar', 'buddypress' ) }>
					<ToolbarButton
						icon="edit"
						title={ __( 'Select another member', 'buddypress' ) }
						onClick={ () => {
							setAttributes( { itemID: 0 } );
						} }
					/>
				</Toolbar>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Display Profile button', 'buddypress' ) }
						checked={ !! displayActionButton }
						onChange={ () => {
							setAttributes( { displayActionButton: ! displayActionButton } );
						} }
						help={
							displayActionButton
								? __( 'Include a link to the user\'s profile page under their display name.', 'buddypress' )
								: __( 'Toggle to display a link to the user\'s profile page under their display name.', 'buddypress' )
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
									? __( 'Include the user\'s cover image over their display name.', 'buddypress' )
									: __( 'Toggle to display the user\'s cover image over their display name.', 'buddypress' )
							}
						/>
					) }

					{ isMentionEnabled && (
						<ToggleControl
							label={ __( 'Display Mention slug', 'buddypress' ) }
							checked={ !! displayMentionSlug }
							onChange={ () => {
								setAttributes( { displayMentionSlug: ! displayMentionSlug } );
							} }
							help={
								displayMentionSlug
									? __( 'Include the user\'s mention name under their display name.', 'buddypress' )
									: __( 'Toggle to display the user\'s mention name under their display name.', 'buddypress' )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/member" attributes={ attributes } />
			</Disabled>
		</div>
	);
};

export default editMemberBlock;
