<?php
/**
 * BuddyPress Blogs Recent Posts Widget.
 *
 * @package BuddyPress
 * @subpackage Blogs
 * @since 1.0.0
 * @deprecated 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file(
	basename( __FILE__ ),
	'12.0.0',
	'',
	esc_html__( 'BuddyPress does not include Legacy Widgets anymore, you can restore it using the BP Classic plugin', 'buddypress' )
);

/**
 * The Recent Networkwide Posts widget.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 */
class BP_Blogs_Recent_Posts_Widget {

	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 * @since 9.0.0 Adds the `show_instance_in_rest` property to Widget options.
	 * @deprecated 12.0.0
	 */
	public function __construct() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Display the networkwide posts widget.
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @since 15.0.0 The `$args` and `$instance` parameters were removed since they were unused.
	 * @deprecated 12.0.0
	 */
	public function widget() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Update the networkwide posts widget options.
	 *
	 * @since 15.0.0 The `$new_instance` and `$old_instance` parameters were removed since they were
	 *               unused.
	 * @deprecated 12.0.0
	 */
	public function update() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Output the networkwide posts widget options form.
	 *
	 * @since 15.0.0 The `$instance` parameter was removed since it was unused.
	 * @deprecated 12.0.0
	 */
	public function form() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}
}
