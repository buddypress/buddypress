<?php

function friends_notification_new_request( $friendship_id, $initiator_id, $friend_id ) {
	global $bp;
	
	$initiator_name = bp_fetch_user_fullname( $initiator_id, false );

	if ( 'no' == get_usermeta( (int)$friend_id, 'notification_friends_friendship_request' ) )
		return false;
	
	$ud = get_userdata( $friend_id );
	$initiator_ud = get_userdata( $initiator_id );
	
	$all_requests_link = site_url( MEMBERS_SLUG . '/' . $ud->user_login . '/friends/requests/' );
	$settings_link = site_url( MEMBERS_SLUG . '/' . $ud->user_login . '/settings/notifications' );
	
	$initiator_link = site_url( MEMBERS_SLUG . '/' . $initiator_ud->user_login . '/profile' );

	// Set up and send the message
	$to = $ud->user_email;
	$subject = '[' . get_blog_option( 1, 'blogname' ) . '] ' . sprintf( __( 'New friendship request from %s', 'buddypress' ), $initiator_name );

	$message = sprintf( __( 
"%s wants to add you as a friend.

To view all of your pending friendship requests: %s

To view %s's profile: %s

---------------------
", 'buddypress' ), $initiator_name, $all_requests_link, $initiator_name, $initiator_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	// Send it
	wp_mail( $to, $subject, $message );
}


function friends_notification_accepted_request( $friendship_id, $initiator_id, $friend_id ) {
	global $bp;
	
	$friendship = new BP_Friends_Friendship( $friendship_id, false, false );
	
	$friend_name = bp_fetch_user_fullname( $friend_id, false );

	if ( 'no' == get_usermeta( (int)$initiator_id, 'notification_friends_friendship_accepted' ) )
		return false;
	
	$ud = get_userdata( $initiator_id );
	$friend_ud = get_userdata( $friend_id );
	
	$friend_link = site_url() . '/' . MEMBERS_SLUG . '/' . $friend_ud->user_login;
	$settings_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/settings/notifications';
		
	// Set up and send the message
	$to = $ud->user_email;
	$subject = '[' . get_blog_option( 1, 'blogname' ) . '] ' . sprintf( __( '%s accepted your friendship request', 'buddypress' ), $friend_name );

	$message = sprintf( __( 
'%s accepted your friend request.

To view %s\'s profile: %s

---------------------
', 'buddypress' ), $friend_name, $friend_name, $friend_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	// Send it
	wp_mail( $to, $subject, $message );
}


?>