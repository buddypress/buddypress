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

	/* Send the message */
	$to = apply_filters( 'friends_notification_new_request_to', $to );
	$subject = apply_filters( 'friends_notification_new_request_subject', $subject, $initiator_name );
	$message = apply_filters( 'friends_notification_new_request_message', $message, $initiator_name, $initiator_link, $all_requests_link, $settings_link );

	wp_mail( $to, $subject, $message );

	do_action( 'bp_friends_sent_request_email', $friend_id, $subject, $message, $friendship_id, $initiator_id );
}

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

	/* Send the message */
	$to = apply_filters( 'friends_notification_accepted_request_to', $to );
	$subject = apply_filters( 'friends_notification_accepted_request_subject', $subject, $friend_name );
	$message = apply_filters( 'friends_notification_accepted_request_message', $message, $friend_name, $friend_link, $settings_link );

	wp_mail( $to, $subject, $message );

	do_action( 'bp_friends_sent_accepted_email', $initiator_id, $subject, $message, $friendship_id, $friend_id );
}
