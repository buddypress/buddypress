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
		PanelBody,
		SelectControl,
		ToggleControl,
		Button,
		Dashicon,
		Tooltip,
		ToolbarGroup,
		RangeControl,
	},
	compose: {
		compose,
	},
	data: {
		withSelect,
	},
	element: {
		createElement,
		Fragment,
		useState,
	},
	i18n: {
		__,
		sprintf,
	},
	apiFetch,
	url: {
		addQueryArgs,
	},
} = wp;

/**
 * BuddyPress dependencies.
 */
const { AutoCompleter } = bp.blockComponents;

/**
 * Internal dependencies.
 */
import { AVATAR_SIZES, EXTRA_DATA } from './constants';

/**
 * External dependencies.
 */
const {
	reject,
	remove,
	sortBy,
} = lodash;

const getSlugValue = ( item ) => {
	if ( item && item.mention_name ) {
		return item.mention_name;
	}

	return null;
}

const editMembers = ( { attributes, setAttributes, isSelected, bpSettings } ) => {
	const {
		isAvatarEnabled,
		isMentionEnabled,
	} = bpSettings;
	const {
		itemIDs,
		avatarSize,
		displayMentionSlug,
		displayUserName,
		extraData,
		layoutPreference,
		columns,
	} = attributes;
	const hasMembers = 0 !== itemIDs.length;
	const [ members, setMembers ] = useState( [] );
	const layoutControls = [
		{
			icon: 'text',
			title: __( 'List view', 'buddypress' ),
			onClick: () => setAttributes( { layoutPreference: 'list' } ),
			isActive: layoutPreference === 'list',
		},
		{
			icon: 'screenoptions',
			title: __( 'Grid view', 'buddypress' ),
			onClick: () => setAttributes( { layoutPreference: 'grid' } ),
			isActive: layoutPreference === 'grid',
		},
	];
	let membersList;
	let containerClasses = 'bp-block-members avatar-' + avatarSize;
	let extraDataOptions = EXTRA_DATA;

	if ( layoutPreference === 'grid' ) {
		containerClasses += ' is-grid columns-' + columns;
		extraDataOptions = EXTRA_DATA.filter( ( extra ) => {
			return 'latest_update' !== extra.value;
		} );
	}

	const onSelectedMember = ( { itemID } ) => {
		if ( itemID && -1 === itemIDs.indexOf( itemID ) ) {
			setAttributes( {
				itemIDs: [...itemIDs, parseInt( itemID, 10 ) ]
			} );
		}
	};

	const onRemoveMember = ( ( itemID ) => {
		if ( itemID && -1 !== itemIDs.indexOf( itemID ) ) {
			setMembers( reject( members, ['id', itemID ] ) );
			setAttributes( {
				itemIDs: remove( itemIDs, ( value ) => { return value !== itemID } )
			} );
		}
	} );

	if ( hasMembers && itemIDs.length !== members.length ) {
		apiFetch( {
			path: addQueryArgs( `/buddypress/v1/members`, { populate_extras: true, include: itemIDs } ),
		} ).then( items => {
			setMembers(
				sortBy( items, [ ( item ) => {
					return itemIDs.indexOf( item.id );
				} ] )
			);
		} )
	}

	if ( members.length ) {
		membersList = members.map( ( member ) => {
			let hasActivity = false;
			let memberItemClasses = 'member-content';

			if ( layoutPreference === 'list' && 'latest_update' === extraData && member.latest_update && member.latest_update.rendered ) {
				hasActivity = true;
				memberItemClasses = 'member-content has-activity';
			}

			return (
				<div key={ 'bp-member-' + member.id } className={ memberItemClasses }>
					{ isSelected && (
						<Tooltip text={ __( 'Remove member', 'buddypress' ) }>
							<Button
								className="is-right"
								onClick={ () => onRemoveMember( member.id ) }
								label={ __( 'Remove member', 'buddypress' ) }
							>
								<Dashicon icon="no"/>
							</Button>
						</Tooltip>
					) }
					{ isAvatarEnabled && 'none' !== avatarSize && (
						<div className="item-header-avatar">
							<a href={ member.link } target="_blank">
								<img
									key={ 'avatar-' + member.id }
									className="avatar"
									alt={ sprintf( __( 'Profile photo of %s', 'buddypress' ), member.name ) }
									src={ member.avatar_urls[ avatarSize ] }
								/>
							</a>
						</div>
					) }
					<div className="member-description">
						{ hasActivity && (
							<blockquote className="wp-block-quote">
								<div dangerouslySetInnerHTML={ { __html: member.latest_update.rendered } } />
								<cite>
									{ displayUserName && (
										<span>
											{ member.name }
										</span>
									) }
									&nbsp;
									{ isMentionEnabled && displayMentionSlug && (
										<a href={ member.link } target="_blank">
											(@{ member.mention_name })
										</a>
									) }
								</cite>
							</blockquote>
						) }
						{ ! hasActivity && displayUserName && (
							<strong>
								<a href={ member.link } target="_blank">
									{ member.name }
								</a>
							</strong>
						) }

						{ ! hasActivity && isMentionEnabled && displayMentionSlug && (
							<span className="user-nicename">@{ member.mention_name }</span>
						) }

						{ 'last_activity' === extraData && member.last_activity && member.last_activity.date && (
							<time dateTime={ member.last_activity.date }>
								{ sprintf( __( 'Active %s', 'buddypress' ), member.last_activity.timediff ) }
							</time>
						) }
					</div>
				</div>
			);
		} );
	}

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Display the user name', 'buddypress' ) }
						checked={ !! displayUserName }
						onChange={ () => {
							setAttributes( { displayUserName: ! displayUserName } );
						} }
						help={
							displayUserName
								? __( 'Include the user\'s display name.', 'buddypress' )
								: __( 'Toggle to include user\'s display name.', 'buddypress' )
						}
					/>

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

					<SelectControl
						label={ __( 'BuddyPress extra information', 'buddypress' ) }
						value={ extraData }
						options={ extraDataOptions }
						help={ __( 'Select "None" to show no extra information.', 'buddypress' ) }
						onChange={ ( option ) => {
							setAttributes( { extraData: option } );
						} }
					/>

					{ layoutPreference === 'grid' && (
						<RangeControl
							label={ __( 'Columns', 'buddypress' ) }
							value={ columns }
							onChange={ ( value ) =>
								setAttributes( { columns: value } )
							}
							min={ 2 }
							max={ 4 }
							required
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<BlockControls>
				<ToolbarGroup controls={ layoutControls } />
			</BlockControls>

			{ hasMembers && (
				<div className={ containerClasses }>
					{ membersList }
				</div>
			) }

			{ ( isSelected || 0 === itemIDs.length ) && (
				<Placeholder
					icon={ hasMembers ? '' : 'groups' }
					label={ hasMembers ? '' : __( 'BuddyPress Members', 'buddypress' ) }
					instructions={ __( 'Start typing the name of the member you want to add to the members list.', 'buddypress' ) }
					className={ 0 !== itemIDs.length ? 'is-appender' : 'is-large' }
				>
					<AutoCompleter
						component="members"
						objectQueryArgs={ { exclude: itemIDs } }
						slugValue={ getSlugValue }
						ariaLabel={ __( 'Member\'s username', 'buddypress' ) }
						placeholder={ __( 'Enter Member\'s username here…', 'buddypress' ) }
						onSelectItem={ onSelectedMember }
						useAvatar={ isAvatarEnabled }
					/>
				</Placeholder>
			) }
		</Fragment>
	);
};

const editMembersBlock = compose( [
	withSelect( ( select ) => {
		const editorSettings = select( 'core/editor' ).getEditorSettings();

		return {
			bpSettings: editorSettings.bp.members || {},
		};
	} ),
] )( editMembers );

export default editMembersBlock;
