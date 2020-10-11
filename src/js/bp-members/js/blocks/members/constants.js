/**
 * WordPress dependencies.
 */
const {
	i18n: {
		__,
	},
} = wp;

/**
 * Avatar sizes.
 *
 * @type {Array}
 */
export const AVATAR_SIZES = [
	{
		label: __( 'None', 'buddypress' ),
		value: 'none',
	},
	{
		label: __( 'Thumb', 'buddypress' ),
		value: 'thumb',
	},
	{
		label: __( 'Full', 'buddypress' ),
		value: 'full',
	},
];

/**
 * BuddyPress Extra data.
 *
 * @type {Array}
 */
export const EXTRA_DATA = [
	{
		label: __( 'None', 'buddypress' ),
		value: 'none',
	},
	{
		label: __( 'Last time the user was active', 'buddypress' ),
		value: 'last_activity',
	},
	{
		label: __( 'Latest activity the user posted', 'buddypress' ),
		value: 'latest_update',
	},
];
