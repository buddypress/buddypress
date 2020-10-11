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
		_n,
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
import { AVATAR_SIZES, EXTRA_INFO, GROUP_STATI } from './constants';

/**
 * External dependencies.
 */
const {
	reject,
	remove,
	sortBy,
} = lodash;

const getSlugValue = ( item ) => {
	if ( item && item.status && GROUP_STATI[ item.status ] ) {
		return GROUP_STATI[ item.status ];
	}

	return null;
}

const editGroups = ( { attributes, setAttributes, isSelected, bpSettings } ) => {
	const {
		isAvatarEnabled,
	} = bpSettings;
	const {
		itemIDs,
		avatarSize,
		displayGroupName,
		extraInfo,
		layoutPreference,
		columns,
	} = attributes;
	const hasGroups = 0 !== itemIDs.length;
	const [ groups, setGroups ] = useState( [] );
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
	let groupsList;
	let containerClasses = 'bp-block-groups avatar-' + avatarSize;
	let extraInfoOptions = EXTRA_INFO;

	if ( layoutPreference === 'grid' ) {
		containerClasses += ' is-grid columns-' + columns;
		extraInfoOptions = EXTRA_INFO.filter( ( extra ) => {
			return 'description' !== extra.value;
		} );
	}

	const onSelectedGroup = ( { itemID } ) => {
		if ( itemID && -1 === itemIDs.indexOf( itemID ) ) {
			setAttributes( {
				itemIDs: [...itemIDs, parseInt( itemID, 10 ) ]
			} );
		}
	};

	const onRemoveGroup = ( ( itemID ) => {
		if ( itemID && -1 !== itemIDs.indexOf( itemID ) ) {
			setGroups( reject( groups, ['id', itemID ] ) );
			setAttributes( {
				itemIDs: remove( itemIDs, ( value ) => { return value !== itemID } )
			} );
		}
	} );

	if ( hasGroups && itemIDs.length !== groups.length ) {
		apiFetch( {
			path: addQueryArgs( `/buddypress/v1/groups`, { populate_extras: true, include: itemIDs } ),
		} ).then( items => {
			setGroups(
				sortBy( items, [ ( item ) => {
					return itemIDs.indexOf( item.id );
				} ] )
			);
		} )
	}

	if ( groups.length ) {
		groupsList = groups.map( ( group ) => {
			let hasDescription = false;
			let groupItemClasses = 'group-content';

			if ( layoutPreference === 'list' && 'description' === extraInfo && group.description && group.description.rendered ) {
				hasDescription = true;
				groupItemClasses = 'group-content has-description';
			}

			return (
				<div key={ 'bp-group-' + group.id } className={ groupItemClasses }>
					{ isSelected && (
						<Tooltip text={ __( 'Remove group', 'buddypress' ) }>
							<Button
								className="is-right"
								onClick={ () => onRemoveGroup( group.id ) }
								label={ __( 'Remove group', 'buddypress' ) }
							>
								<Dashicon icon="no"/>
							</Button>
						</Tooltip>
					) }
					{ isAvatarEnabled && 'none' !== avatarSize && (
						<div className="item-header-avatar">
							<a href={ group.link } target="_blank">
								<img
									key={ 'avatar-' + group.id }
									className="avatar"
									alt={ sprintf( __( 'Profile photo of %s', 'buddypress' ), group.name ) }
									src={ group.avatar_urls[ avatarSize ] }
								/>
							</a>
						</div>
					) }
					<div className="group-description">
						{ displayGroupName && (
							<strong>
								<a href={ group.link } target="_blank">
									{ group.name }
								</a>
							</strong>
						) }

						{ hasDescription && (
							<div className="group-description-content" dangerouslySetInnerHTML={ { __html: group.description.rendered } } />
						) }

						{ 'active' === extraInfo && group.last_activity && group.last_activity_diff && (
							<time dateTime={ group.last_activity }>
								{ sprintf( __( 'Active %s', 'buddypress' ), group.last_activity_diff ) }
							</time>
						) }

						{ 'popular' === extraInfo && group.total_member_count && (
							<div className="group-meta">
								{  sprintf(
									/* translators: 1: number of group memberss. */
									_n(
										'%1$d member',
										'%1$d members',
										group.total_member_count,
										'buddypress'
									),
									group.total_member_count
								) }
							</div>
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
						label={ __( 'Display the group\'s name', 'buddypress' ) }
						checked={ !! displayGroupName }
						onChange={ () => {
							setAttributes( { displayGroupName: ! displayGroupName } );
						} }
						help={
							displayGroupName
								? __( 'Include the group\'s name.', 'buddypress' )
								: __( 'Toggle to include group\'s name.', 'buddypress' )
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

					<SelectControl
						label={ __( 'Group extra information', 'buddypress' ) }
						value={ extraInfo }
						options={ extraInfoOptions }
						help={ __( 'Select "None" to show no extra information.', 'buddypress' ) }
						onChange={ ( option ) => {
							setAttributes( { extraInfo: option } );
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

			{ hasGroups && (
				<div className={ containerClasses }>
					{ groupsList }
				</div>
			) }

			{ ( isSelected || 0 === itemIDs.length ) && (
				<Placeholder
					icon={ hasGroups ? '' : 'groups' }
					label={ hasGroups ? '' : __( 'BuddyPress Groups', 'buddypress' ) }
					instructions={ __( 'Start typing the name of the group you want to add to the groups list.', 'buddypress' ) }
					className={ 0 !== itemIDs.length ? 'is-appender' : 'is-large' }
				>
					<AutoCompleter
						component="groups"
						objectQueryArgs={ { 'show_hidden': false, exclude: itemIDs } }
						slugValue={ getSlugValue }
						ariaLabel={ __( 'Group\'s name', 'buddypress' ) }
						placeholder={ __( 'Enter Group\'s name here…', 'buddypress' ) }
						onSelectItem={ onSelectedGroup }
						useAvatar={ isAvatarEnabled }
					/>
				</Placeholder>
			) }
		</Fragment>
	);
};

const editGroupsBlock = compose( [
	withSelect( ( select ) => {
		const editorSettings = select( 'core/editor' ).getEditorSettings();

		return {
			bpSettings: editorSettings.bp.groups || {},
		};
	} ),
] )( editGroups );

export default editGroupsBlock;
