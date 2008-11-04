<?php

function groups_notification_new_wire_post( $group_id, $wire_post_id ) {
	global $bp;
	
	$wire_post = new BP_Wire_Post( $bp['groups']['table_name_wire'], $wire_post_id );
	$group = new BP_Groups_Group( $group_id, false, true );
	
	$poster_name = bp_fetch_user_fullname( $wire_post->user_id, false );

	foreach ( $group->user_dataset as $user ) {
		if ( get_userdata( $user->user_id, 'notification-groups-new-wire-post' ) == 'no' ) continue;
		
		$ud = get_userdata( $user->user_id );
		
		// Set up and send the message
		$to = $ud->user_email;
		$subject = sprintf( __( 'New group wire post', 'buddypress' ), $initiator_name );

		$wire_link = site_url() . '/' . $bp['groups']['slug'] . '/' . $group->slug . '/wire';
		$group_link = site_url() . '/' . $bp['groups']['slug'] . '/' . $group->slug;
		$settings_link = site_url() . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/settings/notifications';

		$message = sprintf( __( 
'%s posted on the wire of the group "%s":

"%s"

To view the group wire: %s
To view the group home: %s
---------------------
		', 'buddypress' ), $poster_name, stripslashes($group->name), stripslashes($wire_post->content), $wire_link, $group_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		// Send it
		wp_mail( $to, $subject, $message );
	}
}
add_action( 'groups_new_wire_post', 'groups_notification_new_wire_post', 10, 2 );


function groups_notification_group_updated( $group_id ) {
	global $bp;
	
	$group = new BP_Groups_Group( $group_id, false, true );

	foreach ( $group->user_dataset as $user ) {
		if ( get_userdata( $user->user_id, 'notification-groups-group-updated' ) == 'no' ) continue;
		
		$ud = get_userdata( $user->user_id );
		
		// Set up and send the message
		$to = $ud->user_email;
		$subject = sprintf( __( 'Group Details Updated', 'buddypress' ), $initiator_name );

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
	}
}

?>