<?php

function groups_notification_new_wire_post( $group_id, $wire_post_id ) {
	global $bp;
	
	if ( !isset( $_POST['wire-post-email-notify'] ) )
		return false;
	
	$wire_post = new BP_Wire_Post( $bp['groups']['table_name_wire'], $wire_post_id );
	$group = new BP_Groups_Group( $group_id, false, true );
	
	$poster_name = bp_fetch_user_fullname( $wire_post->user_id, false );
	$poster_ud = get_userdata( $wire_post->user_id );
	$poster_profile_link = site_url() . '/' . MEMBERS_SLUG . '/' . $poster_ud->user_login;

	$subject = sprintf( __( 'New wire post on group: %s', 'buddypress' ), stripslashes($group->name) );

	foreach ( $group->user_dataset as $user ) {
		if ( get_usermeta( $user->user_id, 'notification_groups_wire_post' ) == 'no' ) continue;
		
		$ud = get_userdata( $user->user_id );
		
		// Set up and send the message
		$to = $ud->user_email;

		$wire_link = site_url() . '/' . $bp['groups']['slug'] . '/' . $group->slug . '/wire';
		$group_link = site_url() . '/' . $bp['groups']['slug'] . '/' . $group->slug;
		$settings_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/settings/notifications';

		$message = sprintf( __( 
'%s posted on the wire of the group "%s":

"%s"

To view the group wire: %s
To view the group home: %s
To view %s\'s profile page: %s

---------------------
', 'buddypress' ), $poster_name, stripslashes($group->name), stripslashes($wire_post->content), $wire_link, $group_link, $poster_name, $poster_profile_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		// Send it
		wp_mail( $to, $subject, $message );
		
		unset( $message, $to );
	}
}
add_action( 'groups_new_wire_post', 'groups_notification_new_wire_post', 10, 2 );


function groups_notification_group_updated( $group_id ) {
	global $bp;
	
	$group = new BP_Groups_Group( $group_id, false, true );
	$subject = __( 'Group Details Updated', 'buddypress' );

	foreach ( $group->user_dataset as $user ) {
		if ( get_usermeta( $user->user_id, 'notification_groups_group_updated' ) == 'no' ) continue;
		
		$ud = get_userdata( $user->user_id );
		
		// Set up and send the message
		$to = $ud->user_email;

		$group_link = site_url() . '/' . $bp['groups']['slug'] . '/' . $group->slug;
		$settings_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/settings/notifications';

		$message = sprintf( __( 
'Group details for the group "%s" were updated:

To view the group: %s

---------------------
', 'buddypress' ), stripslashes($group->name), $group_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		// Send it
		wp_mail( $to, $subject, $message );

		unset( $message, $to );
	}
}

?>