<?php
/**
 * BuddyPress Blogs Recent Posts Widget.
 *
 * @package BuddyPress
 * @subpackage BlogsWidgets
 * @since 1.0.0
 * @deprecated 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file( basename( __FILE__ ), '12.0.0', '', __( 'BuddyPress does not include Legacy Widgets anymore, you can restore it using the BP Classic plugin', 'buddypress' ) );

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
	 * @deprecated 12.0.0
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	public function widget( $args, $instance ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Update the networkwide posts widget options.
	 *
	 * @deprecated 12.0.0
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Output the networkwide posts widget options form.
	 *
	 * @deprecated 12.0.0
	 *
	 * @param array $instance Settings for this widget.
	 */
	public function form( $instance ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}
}
