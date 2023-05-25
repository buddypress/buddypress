/**
 * WordPress dependencies.
 */
import { _x } from '@wordpress/i18n';

/**
 * Groups ordering types.
 *
 * @type {Array}
 */
export const TYPES = [
	{
		label: _x( 'Newest', 'Groups', 'buddypress' ),
		value: 'newest',
	},
	{
		label: _x( 'Active', 'Groups', 'buddypress' ),
		value: 'active',
	},
	{
		label: _x( 'Popular', 'Groups', 'buddypress' ),
		value: 'popular',
	},
	{
		label: _x('Alphabetical', 'Groups', 'buddypress' ),
		value: 'alphabetical',
	},
];
