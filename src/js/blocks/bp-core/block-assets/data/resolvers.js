/**
 * Internal dependencies.
 */
import {
	fetchFromAPI,
	getActiveComponents as getActiveComponentsList,
} from './actions';

/**
 * Resolver for retrieving active BP Components.
 */
export function* getActiveComponents() {
	const list = yield fetchFromAPI(
		'/buddypress/v1/components?status=active',
		true
	);
	yield getActiveComponentsList( list );
}
