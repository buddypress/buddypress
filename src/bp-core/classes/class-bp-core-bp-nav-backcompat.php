<?php
/**
 * Backward compatibility for the $bp->bp_nav global.
 *
 * @since 2.6.0
 * @deprecated 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * bp_nav backward compatibility class.
 *
 * This class is used to provide backward compatibility for extensions that access and modify
 * the $bp->bp_nav global.
 *
 * @since 2.6.0
 * @deprecated 12.0.0
 */
class BP_Core_BP_Nav_BackCompat implements ArrayAccess {
	/**
	 * Nav items.
	 *
	 * @since 2.6.0
	 * @access public
	 * @var array
	 */
	public $backcompat_nav = array();

	/**
	 * Component to which nav items belong.
	 *
	 * @since 2.6.0
	 * @access public
	 * @var array
	 */
	public $component;

	/**
	 * Constructor.
	 *
	 * @since 2.6.0
	 *
	 * @param array $backcompat_nav Optional. Array of nav items.
	 */
	public function __construct( $backcompat_nav = array() ) {
		foreach ( $backcompat_nav as $key => $value ) {
			if ( is_array( $value ) ) {
				$this->backcompat_nav[ $key ] = new self( $value );
			} else {
				$this->backcompat_nav[ $key ] = $value;
			}
		}
	}

	/**
	 * Assign a value to the nav array at the specified offset.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @param mixed $offset Array offset.
	 * @param array $value  Nav item.
	 */
	#[ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		_doing_it_wrong(
			'bp_nav',
			esc_html__( 'The bp_nav and bp_options_nav globals should not be used directly and are deprecated. Please use the BuddyPress nav functions instead.', 'buddypress' ),
			'2.6.0'
		);
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Get a value of the nav array at the specified offset.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @param mixed $offset Array offset.
	 * @return BP_Core_BP_Nav_BackCompat
	 */
	#[ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		_doing_it_wrong(
			'bp_nav',
			esc_html__( 'The bp_nav and bp_options_nav globals should not be used directly and are deprecated. Please use the BuddyPress nav functions instead.', 'buddypress' ),
			'2.6.0'
		);
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Check whether nav array has a value at the specified offset.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @param mixed $offset Array offset.
	 * @return bool
	 */
	#[ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		_doing_it_wrong(
			'bp_nav',
			esc_html__( 'The bp_nav and bp_options_nav globals should not be used directly and are deprecated. Please use the BuddyPress nav functions instead.', 'buddypress' ),
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
	#[ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		_doing_it_wrong(
			'bp_nav',
			esc_html__( 'The bp_nav and bp_options_nav globals should not be used directly and are deprecated. Please use the BuddyPress nav functions instead.', 'buddypress' ),
			'2.6.0'
		);
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Set the component to which the nav belongs.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @param string $component
	 */
	public function set_component( $component ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Get the component to which the a nav item belongs.
	 *
	 * We use the following heuristic to guess, based on an offset, which component the item belongs to:
	 *   - If this is a group, and the offset is the same as the current group's slug, it's a group nav item.
	 *   - Otherwise, it's a member nav item.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @param mixed $offset Array offset.
	 * @return string|array
	 */
	public function get_component( $offset = '' ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Reset the cached nav items.
	 *
	 * Called when the nav API removes items from the nav array.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 */
	public function reset() {
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
	protected function get_nav( $offset ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Get the BP_Core_Nav object corresponding to the component, based on a nav item name.
	 *
	 * The way bp_nav was previously organized makes it impossible to know for sure which component's nav is
	 * being referenced by a given nav item name. We guess in the following manner:
	 *   - If we're looking at a group, and the nav item name (`$offset`) is the same as the slug of the current
	 *     group, we assume that the proper component nav is 'groups'.
	 *   - Otherwise, fall back on 'members'.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @param string $offset Nav item name.
	 * @return BP_Core_Nav
	 */
	protected function get_component_nav( $offset = '' ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Get the nav data, formatted as a flat array.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 *
	 * @return array
	 */
	protected function to_array() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}
}
