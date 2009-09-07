<?php

function friends_notification_new_request( $friendship_id, $initiator_id, $friend_id ) {
	global $bp;
	
	$initiator_name = bp_core_get_user_displayname( $initiator_id );

	if ( 'no' == get_usermeta( (int)$friend_id, 'notification_friends_friendship_request' ) )
		return false;
	
	$ud = get_userdata( $friend_id );
	$initiator_ud = get_userdata( $initiator_id );
	
	$all_requests_link = bp_core_get_user_domain( $friend_id ) . 'friends/requests/';
	$settings_link = bp_core_get_user_domain( $friend_id ) . 'settings/notifications';
	
	$initiator_link = bp_core_get_user_domain( $initiator_id );

	// Set up and send the message
	$to = $ud->user_email;
	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'New friendship request from %s', 'buddypress' ), $initiator_name );

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
	
	$friend_name = bp_core_get_user_displayname( $friend_id );

	if ( 'no' == get_usermeta( (int)$initiator_id, 'notification_friends_friendship_accepted' ) )
		return false;
	
	$ud = get_userdata( $initiator_id );
	
	$friend_link = bp_core_get_user_domain( $friend_id );
	$settings_link = bp_core_get_user_domain( $initiator_id ) . 'settings/notifications'; 
		
	// Set up and send the message
	$to = $ud->user_email;
	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( '%s accepted your friendship request', 'buddypress' ), $friend_name );

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