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
