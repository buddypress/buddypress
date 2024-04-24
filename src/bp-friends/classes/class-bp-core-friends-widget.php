<?php
/**
 * BuddyPress Friends Widget.
 *
 * @package BuddyPress
 * @subpackage FriendsWidget
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
	 * @deprecated 12.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The main member template loop class.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance The widget settings, as saved by the user.
	 */
	public function widget( $args, $instance ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Process a widget save.
	 *
	 * @since 1.9.0
	 * @deprecated 12.0.0
	 *
	 * @param array $new_instance The parameters saved by the user.
	 * @param array $old_instance The parameters as previously saved to the database.
	 * @return array $instance The processed settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Render the widget edit form.
	 *
	 * @since 1.9.0
	 * @deprecated 12.0.0
	 *
	 * @param array $instance The saved widget settings.
	 */
	public function form( $instance ) {
		_deprecated_function( __METHOD__, '12.0.0' );
	}
}
