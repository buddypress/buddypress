/**
 * Internal dependencies.
 */
import { TYPES as types } from './action-types';

/**
 * Returns the list of active components.
 *
 * @return {Object} Object for action.
 */
 export function getActiveComponents( list ) {
	return {
		type: types.GET_ACTIVE_COMPONENTS,
		list,
	};
}

/**
 * Returns an action object used to fetch something from the API.
 *
 * @param {string} path Endpoint path.
 * @param {boolean} parse Should we parse the request.
 * @return {Object} Object for action.
 */
export function fetchFromAPI( path, parse ) {
	return {
		type: types.FETCH_FROM_API,
		path,
		parse,
	};
}
