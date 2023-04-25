/**
 * Internal dependencies
 */
import { TYPES as types } from './action-types';

/**
 * Default state.
 */
const DEFAULT_STATE = {
	components: [],
};

/**
 * Reducer for the BuddyPress data store.
 *
 * @param   {Object}  state   The current state in the store.
 * @param   {Object}  action  Action object.
 *
 * @return  {Object}          New or existing state.
 */
const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.GET_ACTIVE_COMPONENTS:
			return {
				...state,
				components: action.list,
			};
	}

	return state;
};

export default reducer;
