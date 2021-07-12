/**
 * WordPress dependencies.
 */
const {
	data: {
		registerStore,
	},
} = wp;

/**
 * Internal dependencies.
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducers';
import { controls } from './controls';

registerStore( STORE_KEY, {
	reducer,
	actions,
	selectors,
	controls,
	resolvers,
} );

export const BP_CORE_STORE_KEY = STORE_KEY;

export default BP_CORE_STORE_KEY;
