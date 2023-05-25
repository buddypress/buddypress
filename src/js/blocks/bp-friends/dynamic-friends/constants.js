/**
 * WordPress dependencies.
 */
import { _x } from '@wordpress/i18n';

/**
 * Friends ordering types.
 *
 * @type {Array}
 */
export const TYPES = [
	{
		label: _x( 'Newest', 'Friends', 'buddypress' ),
		value: 'newest',
	},
	{
		label: _x( 'Active', 'Friends', 'buddypress' ),
		value: 'active',
	},
	{
		label: _x( 'Popular', 'Friends', 'buddypress' ),
		value: 'popular',
	},
];
