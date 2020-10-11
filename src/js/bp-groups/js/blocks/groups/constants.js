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
 * Group stati.
 *
 * @type {Object}
 */
export const GROUP_STATI = {
	public: __( 'Public', 'buddypress' ),
	private: __( 'Private', 'buddypress' ),
	hidden: __( 'Hidden', 'buddypress' ),
};

/**
 * Group Extra data.
 *
 * @type {Array}
 */
export const EXTRA_INFO = [
	{
		label: __( 'None', 'buddypress' ),
		value: 'none',
	},
	{
		label: __( 'Group\'s description', 'buddypress' ),
		value: 'description',
	},
	{
		label: __( 'Last time the group was active', 'buddypress' ),
		value: 'active',
	},
	{
		label: __( 'Amount of group members', 'buddypress' ),
		value: 'popular',
	},
];
