<?php
/**
 * BuddyPress Friends Widget.
 *
 * @package BuddyPress
 * @subpackage Friends
 * @since 1.9.0
 * @deprecated 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file( basename( __FILE__ ), '12.0.0', '', esc_html__( 'BuddyPress does not include Legacy Widgets anymore, you can restore it using the BP Classic plugin', 'buddypress' ) );

/**
 * The User Friends widget class.
 *
 * @since 1.9.0
 * @deprecated 12.0.0
 */
class BP_Core_Friends_Widget {

	/**
	 * Class constructor.
	 *
	 * @since 1.9.0
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
	 * Display the widget.
	 *
	 * @since 1.9.0
	 * @since 15.0.0 The `$args` and `$instance` parameters were removed since they were unused.
	 * @deprecated 12.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 */
	public function widget() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Process a widget save.
	 *
	 * @since 1.9.0
	 * @since 15.0.0 The `$new_instance` and `$old_instance` parameters were removed since they were unused.
	 * @deprecated 12.0.0
	 */
	public function update() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Render the widget edit form.
	 *
	 * @since 1.9.0
	 * @since 15.0.0 The `$instance` parameter was removed since it was unused.
	 * @deprecated 12.0.0
	 */
	public function form() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}
}
