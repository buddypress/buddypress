/**
 * WordPress dependencies.
 */
const {
	data: {
		useSelect,
	},
} = wp;

/**
 * External dependencies.
 */
const {
	find,
	get,
} = lodash;

/**
 * Internal dependencies.
 */
import BP_CORE_STORE_KEY from './register';

/**
 * Checks whether a component or the feature of an active component is enabled.
 *
 * @since 9.0.0
 *
 * @param {string} component (required) The component to check.
 * @param {string} feature (optional) The feature to check.
 * @return {boolean} Whether a component or the feature of an active component is enabled.
 */
export function isActive( component, feature = '' ) {
	const components = useSelect( ( select ) => {
		return select( BP_CORE_STORE_KEY ).getActiveComponents();
	}, [] );

	const activeComponent = find( components, ['name', component] );

	if ( ! feature ) {
		return !! activeComponent;
	}

	return get( activeComponent, [ 'features', feature ] );
}

export default isActive;

/**
 * Checks whether a component or the feature of an active component is enabled.
 *
 * @since 9.0.0
 *
 * @return {array} An array of objects keyed by activity types.
 */
 export function activityTypes() {
	const components = useSelect( ( select ) => {
		return select( BP_CORE_STORE_KEY ).getActiveComponents();
	}, [] );

	const activityComponent = find( components, ['name', 'activity'] );

	if ( ! activityComponent ) {
		return [];
	}

	const activityTypes = get( activityComponent, [ 'features', 'types' ] );
	let activityTypesList = [];

	Object.entries( activityTypes ).forEach( ( [ type, label ] ) => {
		activityTypesList.push(
			{
				label: label,
				value: type,
			}
		)
	} );

	return activityTypesList;
}

/**
 * Returns the logged in user object.
 *
 * @since 9.0.0
 *
 * @return {Object} The logged in user object.
 */
export function loggedInUser() {
	const loggedInUser = useSelect( ( select ) => {
		const store = select( 'core' );

		if ( store ) {
			return select( 'core' ).getCurrentUser();
		}

		return {};
	}, [] );

	return loggedInUser;
}

/**
 * Returns the post author user object.
 *
 * @since 9.0.0
 *
 * @return {Object} The post author user object.
 */
export function postAuhor() {
	const postAuhor = useSelect( ( select ) => {
		const editorStore = select( 'core/editor' );
		const coreStore = select( 'core' );

		if ( editorStore && coreStore ) {
			const postAuthorId = editorStore.getCurrentPostAttribute( 'author' );
			const authorsList = coreStore.getAuthors();

			return find( authorsList, ['id', postAuthorId] );
		}

		return {};
	}, [] );

	return postAuhor;
}

/**
 * Returns the current post ID.
 *
 * @since 9.0.0
 *
 * @return {integer} The current post ID.
 */
export function currentPostId() {
	const currentPostId = useSelect( ( select ) => {
		const store = select( 'core/editor' );

		if ( store ) {
			return store.getCurrentPostId();
		}

		return 0;
	}, [] );

	return currentPostId;
}

/**
 * Get the current sidebar of a Widget Block.
 *
 * @since 9.0.0
 *
 * @param {string} widgetClientId clientId of the sidebar widget.
 * @return {object} An object containing the sidebar Id.
 */
export function getCurrentWidgetsSidebar( widgetClientId = '' ) {
	const currentWidgetsSidebar = useSelect( ( select ) => {
		const blockEditorStore = select( 'core/block-editor' );
		const widgetsStore = select( 'core/edit-widgets' );

		if ( widgetClientId && widgetsStore && blockEditorStore ) {
			const areas = blockEditorStore.getBlocks();
			const parents = blockEditorStore.getBlockParents( widgetClientId );
			let sidebars = [];

			areas.forEach( ( { clientId, attributes } ) => {
				sidebars.push( {
					id: attributes.id,
					isCurrent: -1 !== parents.indexOf( clientId ),
				} );
			} );

			return find( sidebars, ['isCurrent', true ] );
		}

		return {};
	}, [] );

	return currentWidgetsSidebar;
}
