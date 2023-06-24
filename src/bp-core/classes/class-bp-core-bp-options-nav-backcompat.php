<?php
/**
 * Backward compatibility for the $bp->bp_options_nav global.
 *
 * @since 2.6.0
 * @deprecated 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * bp_options_nav backward compatibility class.
 *
 * This class is used to provide backward compatibility for extensions that access and modify
 * the $bp->bp_options_nav global.
 *
 * @since 2.6.0
 * @deprecated 12.0.0
 */
class BP_Core_BP_Options_Nav_BackCompat extends BP_Core_BP_Nav_BackCompat {
	/**
	 * Parent slug of the current nav item.
	 *
	 * @since 2.6.0
	 * @access protected
	 * @var string
	 */
	protected $parent_slug = '';

	/**
	 * Get a value of the nav array at the specified offset.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @param mixed $offset Array offset.
	 * @return BP_Core_BP_Nav_BackCompat
	 */
	public function offsetGet( $offset ) {
		_doing_it_wrong(
			'bp_nav',
			__( 'These globals should not be used directly and are deprecated. Please use the BuddyPress nav functions instead.', 'buddypress' ),
			'2.6.0'
		);
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Unset a nav array value at the specified offset.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @param mixed $offset Array offset.
	 */
	public function offsetUnset( $offset ) {
		_doing_it_wrong(
			'bp_nav',
			__( 'These globals should not be used directly and are deprecated. Please use the BuddyPress nav functions instead.', 'buddypress' ),
			'2.6.0'
		);
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Get the parent slug of the current nav item.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @return string
	 */
	public function get_parent_slug() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Set the parent slug of the current nav item.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 */
	public function set_parent_slug( $slug ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Get the nav object corresponding to the specified offset.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @param mixed $offset Array offset.
	 * @return bool|array
	 */
	public function get_nav( $offset ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}
}
