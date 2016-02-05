<?php
/**
 * BuddyPress Messages Widgets.
 *
 * @package BuddyPress
 * @subpackage Messages
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require dirname( __FILE__ ) . '/classes/class-bp-messages-sitewide-notices-widget.php';

/**
 * Register widgets for the Messages component.
 *
 * @since 1.9.0
 */
function bp_messages_register_widgets() {
	add_action( 'widgets_init', create_function('', 'return register_widget( "BP_Messages_Sitewide_Notices_Widget" );') );
}
add_action( 'bp_register_widgets', 'bp_messages_register_widgets' );
