<?php
/**
 * BuddyPress Groups Widget.
 *
 * @package BuddyPress
 * @subpackage Groups
 * @since 1.0.0
 * @deprecated 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file( basename( __FILE__ ), '12.0.0', '', esc_html__( 'BuddyPress does not include Legacy Widgets anymore, you can restore it using the BP Classic plugin', 'buddypress' ) );

/**
 * Groups widget.
 *
 * @since 1.0.3
 * @deprecated 12.0.0
 */
class BP_Groups_Widget {

	/**
	 * Working as a group, we get things done better.
	 *
	 * @since 1.0.3
	 * @since 9.0.0 Adds the `show_instance_in_rest` property to Widget options.
	 * @deprecated 12.0.0
	 */
	public function __construct() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 */
	public function enqueue_scripts() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Extends our front-end output method.
	 *
	 * @since 1.0.3
	 * @since 15.0.0 The `$args` and `$instance` parameters were removed since they were unused.
	 * @deprecated 12.0.0
	 */
	public function widget() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Extends our update method.
	 *
	 * @since 1.0.3
	 * @since 15.0.0 The `$new_instance` and `$old_instance` parameters were removed since they were unused.
	 * @deprecated 12.0.0
	 */
	public function update() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Extends our form method.
	 *
	 * @since 1.0.3
	 * @since 15.0.0 The `$instance` parameter was removed since it was unused.
	 * @deprecated 12.0.0
	 *
	 * @return mixed
	 */
	public function form() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}
}
