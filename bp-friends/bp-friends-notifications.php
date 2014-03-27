<?php

/**
 * BuddyPress Friends Activity Functions
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage FriendsActivity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Emails ********************************************************************/

/**
 * Send notifications related to a new friendship request.
 *
 * When a friendship is requested, an email and a BP notification are sent to
 * the user of whom friendship has been requested ($friend_id).
 *
 * @param int $friendship_id ID of the friendship object.
 * @param int $initiator_id ID of the user who initiated the request.
 * @param int $friend_id ID of the request recipient.
 */
function friends_notification_new_request( $friendship_id, $initiator_id, $friend_id ) {

	$initiator_name = bp_core_get_user_displayname( $initiator_id );

	if ( 'no' == bp_get_user_meta( (int) $friend_id, 'notification_friends_friendship_request', true ) )
		return false;

	$ud                = get_userdata( $friend_id );
	$all_requests_link = bp_core_get_user_domain( $friend_id ) . bp_get_friends_slug() . '/requests/';
	$settings_slug     = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
	$settings_link     = trailingslashit( bp_core_get_user_domain( $friend_id ) .  $settings_slug . '/notifications' );
	$initiator_link    = bp_core_get_user_domain( $initiator_id );

	// Set up and send the message
	$to       = $ud->user_email;
	$subject  = bp_get_email_subject( array( 'text' => sprintf( __( 'New friendship request from %s', 'buddypress' ), $initiator_name ) ) );
	$message  = sprintf( __(
'%1$s wants to add you as a friend.

To view all of your pending friendship requests: %2$s

To view %3$s\'s profile: %4$s

---------------------
', 'buddypress' ), $initiator_name, $all_requests_link, $initiator_name, $initiator_link );

	// Only show the disable notifications line if the settings component is enabled
	if ( bp_is_active( 'settings' ) ) {
		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
	}

	// Send the message
	$to      = apply_filters( 'friends_notification_new_request_to', $to );
	$subject = apply_filters( 'friends_notification_new_request_subject', $subject, $initiator_name );
	$message = apply_filters( 'friends_notification_new_request_message', $message, $initiator_name, $initiator_link, $all_requests_link, $settings_link );

	wp_mail( $to, $subject, $message );

	do_action( 'bp_friends_sent_request_email', $friend_id, $subject, $message, $friendship_id, $initiator_id );
}
add_action( 'friends_friendship_requested', 'friends_notification_new_request', 10, 3 );

/**
 * Send notifications related to the acceptance of a friendship request.
 *
 * When a friendship request is accepted, an email and a BP notification are
 * sent to the user who requested the friendship ($initiator_id).
 *
 * @param int $friendship_id ID of the friendship object.
 * @param int $initiator_id ID of the user who initiated the request.
 * @param int $friend_id ID of the request recipient.
 */
function friends_notification_accepted_request( $friendship_id, $initiator_id, $friend_id ) {

	$friend_name = bp_core_get_user_displayname( $friend_id );

	if ( 'no' == bp_get_user_meta( (int) $initiator_id, 'notification_friends_friendship_accepted', true ) )
		return false;

	$ud            = get_userdata( $initiator_id );
	$friend_link   = bp_core_get_user_domain( $friend_id );
	$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
	$settings_link = trailingslashit( bp_core_get_user_domain( $initiator_id ) . $settings_slug . '/notifications' );

	// Set up and send the message
	$to       = $ud->user_email;
	$subject  = bp_get_email_subject( array( 'text' => sprintf( __( '%s accepted your friendship request', 'buddypress' ), $friend_name ) ) );
	$message  = sprintf( __(
'%1$s accepted your friend request.

To view %2$s\'s profile: %3$s

---------------------
', 'buddypress' ), $friend_name, $friend_name, $friend_link );

	// Only show the disable notifications line if the settings component is enabled
	if ( bp_is_active( 'settings' ) ) {
		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
	}

	// Send the message
	$to      = apply_filters( 'friends_notification_accepted_request_to', $to );
	$subject = apply_filters( 'friends_notification_accepted_request_subject', $subject, $friend_name );
	$message = apply_filters( 'friends_notification_accepted_request_message', $message, $friend_name, $friend_link, $settings_link );

	wp_mail( $to, $subject, $message );

	do_action( 'bp_friends_sent_accepted_email', $initiator_id, $subject, $message, $friendship_id, $friend_id );
}
add_action( 'friends_friendship_accepted', 'friends_notification_accepted_request', 10, 3 );

/** Notifications *************************************************************/

/**
 * Notification formatting callback for bp-friends notifications.
 *
 * @param string $action The kind of notification being rendered.
 * @param int $item_id The primary item ID.
 * @param int $secondary_item_id The secondary item ID.
 * @param int $total_items The total number of messaging-related notifications
 *        waiting for the user.
 * @param string $format 'string' for BuddyBar-compatible notifications;
 *        'array' for WP Toolbar. Default: 'string'.
 * @return array|string
 */
function friends_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	switch ( $action ) {
		case 'friendship_accepted':
			$link = trailingslashit( bp_loggedin_user_domain() . bp_get_friends_slug() . '/my-friends' );

			// Set up the string and the filter
			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( '%d friends accepted your friendship requests', 'buddypress' ), (int) $total_items );
				$filter = 'bp_friends_multiple_friendship_accepted_notification';
			} else {
				$text = sprintf( __( '%s accepted your friendship request', 'buddypress' ),  bp_core_get_user_displayname( $item_id ) );
				$filter = 'bp_friends_single_friendship_accepted_notification';
			}

			break;

		case 'friendship_request':
			$link = bp_loggedin_user_domain() . bp_get_friends_slug() . '/requests/?new';

			// Set up the string and the filter
			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( 'You have %d pending friendship requests', 'buddypress' ), (int) $total_items );
				$filter = 'bp_friends_multiple_friendship_request_notification';
			} else {
				$text = sprintf( __( 'You have a friendship request from %s', 'buddypress' ),  bp_core_get_user_displayname( $item_id ) );
				$filter = 'bp_friends_single_friendship_request_notification';
			}

			break;
	}

	// Return either an HTML link or an array, depending on the requested format
	if ( 'string' == $format ) {
		$return = apply_filters( $filter, '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $item_id );
	} else {
		$return = apply_filters( $filter, array(
			'link' => $link,
			'text' => $text
		), (int) $total_items, $item_id );
	}

	do_action( 'friends_format_notifications', $action, $item_id, $secondary_item_id, $total_items, $return );

	return $return;
}

/**
 * Clear friend-related notifications when ?new=1
 */
function friends_clear_friend_notifications() {
	if ( isset( $_GET['new'] ) && bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_accepted' );
	}
}
add_action( 'bp_activity_screen_my_activity', 'friends_clear_friend_notifications' );

/**
 * Delete any friendship request notifications for the logged in user.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_friends_mark_friendship_request_notifications_by_type() {
	if ( isset( $_GET['new'] ) && bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_screen_requests', 'bp_friends_mark_friendship_request_notifications_by_type' );

/**
 * Delete any friendship acceptance notifications for the logged in user.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_friends_mark_friendship_accepted_notifications_by_type() {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_accepted' );
	}
}
add_action( 'friends_screen_my_friends', 'bp_friends_mark_friendship_accepted_notifications_by_type' );

/**
 * Notify one use that another user has requested their virtual friendship.
 *
 * @since BuddyPress (1.9.0)
 * @param int $friendship_id The unique ID of the friendship
 * @param int $initiator_user_id The friendship initiator user ID
 * @param int $friend_user_id The friendship request reciever user ID
 */
function bp_friends_friendship_requested_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_add_notification( array(
			'user_id'           => $friend_user_id,
			'item_id'           => $initiator_user_id,
			'secondary_item_id' => $friendship_id,
			'component_name'    => buddypress()->friends->id,
			'component_action'  => 'friendship_request',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		) );
	}
}
add_action( 'friends_friendship_requested', 'bp_friends_friendship_requested_notification', 10, 3 );

/**
 * Remove friend request notice when a member rejects another members
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $friendship_id (not used)
 * @param object $friendship
 */
function bp_friends_mark_friendship_rejected_notifications_by_item_id( $friendship_id, $friendship ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_friendship_rejected', 'bp_friends_mark_friendship_rejected_notifications_by_item_id', 10, 2 );

/**
 * Notify a member when another member accepts their virtual friendship request.
 *
 * @since BuddyPress (1.9.0)
 * @param int $friendship_id The unique ID of the friendship
 * @param int $initiator_user_id The friendship initiator user ID
 * @param int $friend_user_id The friendship request reciever user ID
 */
function bp_friends_add_friendship_accepted_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}

	// Remove the friend request notice
	bp_notifications_mark_notifications_by_item_id( $friend_user_id, $initiator_user_id, buddypress()->friends->id, 'friendship_request' );

	// Add a friend accepted notice for the initiating user
	bp_notifications_add_notification(  array(
		'user_id'           => $initiator_user_id,
		'item_id'           => $friend_user_id,
		'secondary_item_id' => $friendship_id,
		'component_name'    => buddypress()->friends->id,
		'component_action'  => 'friendship_accepted',
		'date_notified'     => bp_core_current_time(),
		'is_new'            => 1,
	) );
}
add_action( 'friends_friendship_accepted', 'bp_friends_add_friendship_accepted_notification', 10, 3 );

/**
 * Remove friend request notice when a member withdraws their friend request
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $friendship_id (not used)
 * @param object $friendship
 */
function bp_friends_mark_friendship_withdrawn_notifications_by_item_id( $friendship_id, $friendship ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_delete_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_friendship_withdrawn', 'bp_friends_mark_friendship_withdrawn_notifications_by_item_id', 10, 2 );

/**
 * Remove friendship requests FROM user, used primarily when a user is deleted
 *
 * @since BuddyPress (1.9.0)
 * @param int $user_id
 */
function bp_friends_remove_notifications_data( $user_id = 0 ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_delete_notifications_from_user( $user_id, buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_remove_data', 'bp_friends_remove_notifications_data', 10, 1 );
