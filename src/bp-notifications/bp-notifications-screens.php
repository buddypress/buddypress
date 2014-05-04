<?php

/**
 * BuddyPress Notifications Screen Functions.
 *
 * Screen functions are the controllers of BuddyPress. They will execute when
 * their specific URL is caught. They will first save or manipulate data using
 * business functions, then pass on the user to a template file.
 *
 * @package BuddyPress
 * @subpackage NotificationsScreens
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Catch and route the 'unread' notifications screen.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_notifications_screen_unread() {
	do_action( 'bp_notifications_screen_unread' );

	bp_core_load_template( apply_filters( 'bp_notifications_template_unread', 'members/single/home' ) );
}

/**
 * Catch and route the 'read' notifications screen.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_notifications_screen_read() {
	do_action( 'bp_notifications_screen_read' );

	bp_core_load_template( apply_filters( 'bp_notifications_template_read', 'members/single/home' ) );
}

/**
 * Catch and route the 'settings' notifications screen.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_notifications_screen_settings() {

}
