<?php
/**
 * Notifications: User's "Notifications" screen handler
 *
 * @package BuddyPress
 * @subpackage NotificationsScreens
 * @since 3.0.0
 */

/**
 * Catch and route the 'unread' notifications screen.
 *
 * @since 1.9.0
 */
function bp_notifications_screen_unread() {

	/**
	 * Fires right before the loading of the notifications unread screen template file.
	 *
	 * @since 1.9.0
	 */
	do_action( 'bp_notifications_screen_unread' );

	/**
	 * Filters the template to load for the notifications unread screen.
	 *
	 * @since 1.9.0
	 *
	 * @param string $template Path to the notifications unread template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_notifications_template_unread', 'members/single/home' ) );
}

/**
 * Handle marking single notifications as read.
 *
 * @since 1.9.0
 *
 * @return bool
 */
function bp_notifications_action_mark_read() {

	// Bail if not the unread screen.
	if ( ! bp_is_notifications_component() || ! bp_is_current_action( 'unread' ) ) {
		return false;
	}

	// Get the action.
	$action = !empty( $_GET['action']          ) ? $_GET['action']          : '';
	$nonce  = !empty( $_GET['_wpnonce']        ) ? $_GET['_wpnonce']        : '';
	$id     = !empty( $_GET['notification_id'] ) ? $_GET['notification_id'] : '';

	// Bail if no action or no ID.
	if ( ( 'read' !== $action ) || empty( $id ) || empty( $nonce ) ) {
		return false;
	}

	// Check the nonce and mark the notification.
	if ( bp_verify_nonce_request( 'bp_notification_mark_read_' . $id ) && bp_notifications_mark_notification( $id, false ) ) {
		bp_core_add_message( __( 'Notification successfully marked read.',         'buddypress' )          );
	} else {
		bp_core_add_message( __( 'There was a problem marking that notification.', 'buddypress' ), 'error' );
	}

	// Redirect.
	bp_core_redirect( bp_displayed_user_domain() . bp_get_notifications_slug() . '/unread/' );
}
add_action( 'bp_actions', 'bp_notifications_action_mark_read' );