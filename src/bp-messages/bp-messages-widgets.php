<?php
/**
 * BuddyPress Messages Widgets.
 *
 * @package BuddyPress
 * @subpackage MessagesWidgets
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the Sitewide Notices Legacy Widget.
 *
 * @since 10.0.0
 */
function bp_messages_register_sitewide_notices_widget() {
	register_widget( 'BP_Messages_Sitewide_Notices_Widget' );
}

/**
 * Register widgets for the Messages component.
 *
 * @since 1.9.0
 */
function bp_messages_register_widgets() {
	add_action( 'widgets_init', 'bp_messages_register_sitewide_notices_widget' );
}
add_action( 'bp_register_widgets', 'bp_messages_register_widgets' );
