<?php
/**
 * BuddyPress Notifications Admin Bar functions.
 *
 * Admin Bar functions for the Notifications component.
 *
 * @package BuddyPress
 * @subpackage NotificationsToolbar
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Build the "Notifications" dropdown.
 *
 * @since 1.9.0
 *
 * @global WP_Admin_Bar $wp_admin_bar The WordPress object implementing a Toolbar API.
 *
 * @return bool
 */
function bp_notifications_toolbar_menu() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
	$menu_link     = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

	return bp_members_admin_bar_notifications_dropdown( $notifications, $menu_link );
}
