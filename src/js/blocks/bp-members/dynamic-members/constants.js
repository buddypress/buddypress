/**
 * WordPress dependencies.
 */
import { _x } from '@wordpress/i18n';

/**
 * Members ordering types.
 *
 * @type {Array}
 */
export const TYPES = [
	{
		label: _x( 'Newest', 'Members', 'buddypress' ),
		value: 'newest',
	},
	{
		label: _x( 'Active', 'Members', 'buddypress' ),
		value: 'active',
	},
	{
		label: _x( 'Popular', 'Members', 'buddypress' ),
		value: 'popular',
	},
];
