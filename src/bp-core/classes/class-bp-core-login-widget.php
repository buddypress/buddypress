<?php
/**
 * BuddyPress Core Login Widget.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.9.0
 * @deprecated 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file( basename( __FILE__ ), '12.0.0', '', esc_html__( 'BuddyPress does not include Legacy Widgets anymore, you can restore it using the BP Classic plugin', 'buddypress' ) );

/**
 * BuddyPress Login Widget.
 *
 * @since 1.9.0
 * @deprecated 12.0.0
 */
class BP_Core_Login_Widget {

	/**
	 * Constructor method.
	 *
	 * @since 1.9.0
	 * @since 9.0.0 Adds the `show_instance_in_rest` property to Widget options.
	 * @deprecated 12.0.0
	 */
	public function __construct() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Display the login widget.
	 *
	 * @since 1.9.0
	 * @deprecated 12.0.0
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	public function widget( $args, $instance ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Update the login widget options.
	 *
	 * @since 1.9.0
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
	 * Output the login widget options form.
	 *
	 * @since 1.9.0
	 * @deprecated 12.0.0
	 *
	 * @param array $instance Settings for this widget.
	 * @return void
	 */
	public function form( $instance = array() ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}
}
