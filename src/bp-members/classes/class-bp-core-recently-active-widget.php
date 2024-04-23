<?php
/**
 * BuddyPress Members Recently Active widget.
 *
 * @package BuddyPress
 * @subpackage MembersWidgets
 * @since 1.0.3
 * @deprecated 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file( basename( __FILE__ ), '12.0.0', '', esc_html__( 'BuddyPress does not include Legacy Widgets anymore, you can restore it using the BP Classic plugin', 'buddypress' ) );

/**
 * Recently Active Members Widget.
 *
 * @since 1.0.3
 * @deprecated 12.0.0
 */
class BP_Core_Recently_Active_Widget extends WP_Widget {

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
	 * Display the Recently Active widget.
	 *
	 * @since 1.0.3
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
	 * Update the Recently Active widget options.
	 *
	 * @since 1.0.3
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
	 * Output the Recently Active widget options form.
	 *
	 * @since 1.0.3
	 * @deprecated 12.0.0
	 *
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function form( $instance ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since 2.3.0
	 * @deprecated 12.0.0
	 *
	 * @param array $instance Widget instance settings.
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}
}
