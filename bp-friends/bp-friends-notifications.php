<?php

function friends_notification_new_request( $friendship_id, $initiator_id, $friend_id ) {
	global $bp;
	
	$initiator_name = bp_fetch_user_fullname( $initiator_id, false );

	if ( get_usermeta( $friend_id, 'notification_friends_new_request' ) == 'no' )
		return false;
	
	$ud = get_userdata( $friend_id );
	
	$all_requests_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/friends/requests/';
	$approve_request_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/friends/requests/accept/' . $friendship_id;
	$reject_request_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/friends/requests/reject/' . $friendship_id;
	$settings_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/settings/notifications';
		
	// Set up and send the message
	$to = $ud->user_email;
	$subject = sprintf( __( 'New friendship request from %s', 'buddypress' ), $initiator_name );

	$message = sprintf( __( 
'%s wants to add you as a friend. You have two options:

Accept the friendship request: %s
Reject the friendship request: %s

To view all of your pending friendship requests: %s

---------------------
', 'buddypress' ), $initiator_name, $approve_request_link, $reject_request_link, $all_requests_link, $message_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	// Send it
	wp_mail( $to, $subject, $message );
}
add_action( 'bp_friends_friendship_requested', 'friends_notification_new_request', 10, 3 );

function friends_notification_accepted_request( $friendship_id, $initiator_id, $friend_id ) {
	global $bp;
	
	$friendship = new BP_Friends_Friendship( $friendship_id, false, false );
	
	if ( (int)$friendship->is_confirmed )
		return true;
	
	$friend_name = bp_fetch_user_fullname( $friend_id, false );

	if ( get_usermeta( $initiator_id, 'notification_friends_accepted_request' ) == 'no' )
		return false;
	
	$ud = get_userdata( $initiator_id );
	$friend_ud = get_userdata( $friend_id );
	
	$friend_link = site_url() . '/' . MEMBERS_SLUG . '/' . $friend_ud->user_login;
	$settings_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/settings/notifications';
		
	// Set up and send the message
	$to = $ud->user_email;
	$subject = sprintf( __( '%s accepted your friendship request', 'buddypress' ), $friend_name );

	$message = sprintf( __( 
'%s accepted your friend request.

To view %s\'s profile: %s

---------------------
', 'buddypress' ), $friend_name, $friend_name, $friend_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	// Send it
	wp_mail( $to, $subject, $message );
}
add_action( 'friends_friendship_accepted', 'friends_notification_accepted_request', 10, 3 );


?>