/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Groups ordering types.
 *
 * @type {Array}
 */
export const TYPES = [
	{
		label: __( 'Newest', 'buddypress' ),
		value: 'newest',
	},
	{
		label: __( 'Active', 'buddypress' ),
		value: 'active',
	},
	{
		label: __( 'Popular', 'buddypress' ),
		value: 'popular',
	},
	{
		label: __('Alphabetical', 'buddypress' ),
		value: 'alphabetical',
	},
];
