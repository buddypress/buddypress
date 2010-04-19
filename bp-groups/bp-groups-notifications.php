<?php

function groups_notification_group_updated( $group_id ) {
	global $bp;

	$group = new BP_Groups_Group( $group_id );
	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . __( 'Group Details Updated', 'buddypress' );

	$user_ids = BP_Groups_Member::get_group_member_ids( $group->id );
	foreach ( (array)$user_ids as $user_id ) {
		if ( 'no' == get_usermeta( $user_id, 'notification_groups_group_updated' ) ) continue;

		$ud = bp_core_get_core_userdata( $user_id );

		// Set up and send the message
		$to = $ud->user_email;

		$group_link = site_url( $bp->groups->slug . '/' . $group->slug );
		$settings_link = bp_core_get_user_domain( $user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

		$message = sprintf( __(
'Group details for the group "%1$s" were updated:

To view the group: %2$s

---------------------
', 'buddypress' ), $group->name, $group_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		/* Send the message */
		$to = apply_filters( 'groups_notification_group_updated_to', $to );
		$subject = apply_filters( 'groups_notification_group_updated_subject', $subject, &$group );
		$message = apply_filters( 'groups_notification_group_updated_message', $message, &$group, $group_link );

		wp_mail( $to, $subject, $message );

		unset( $message, $to );
	}
}

function groups_notification_new_membership_request( $requesting_user_id, $admin_id, $group_id, $membership_id ) {
	global $bp;

	bp_core_add_notification( $requesting_user_id, $admin_id, 'groups', 'new_membership_request', $group_id );

	if ( 'no' == get_usermeta( $admin_id, 'notification_groups_membership_request' ) )
		return false;

	$requesting_user_name = bp_core_get_user_displayname( $requesting_user_id );
	$group = new BP_Groups_Group( $group_id );

	$ud = bp_core_get_core_userdata($admin_id);
	$requesting_ud = bp_core_get_core_userdata($requesting_user_id);

	$group_requests = bp_get_group_permalink( $group ) . 'admin/membership-requests';
	$profile_link = bp_core_get_user_domain( $requesting_user_id );
	$settings_link = bp_core_get_user_domain( $requesting_user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

	// Set up and send the message
	$to = $ud->user_email;
	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'Membership request for group: %s', 'buddypress' ), $group->name );

$message = sprintf( __(
'%1$s wants to join the group "%2$s".

Because you are the administrator of this group, you must either accept or reject the membership request.

To view all pending membership requests for this group, please visit:
%3$s

To view %4$s\'s profile: %5$s

---------------------
', 'buddypress' ), $requesting_user_name, $group->name, $group_requests, $requesting_user_name, $profile_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	/* Send the message */
	$to = apply_filters( 'groups_notification_new_membership_request_to', $to );
	$subject = apply_filters( 'groups_notification_new_membership_request_subject', $subject, &$group );
	$message = apply_filters( 'groups_notification_new_membership_request_message', $message, &$group, $requesting_user_name, $profile_link, $group_requests );

	wp_mail( $to, $subject, $message );
}

function groups_notification_membership_request_completed( $requesting_user_id, $group_id, $accepted = true ) {
	global $bp;

	// Post a screen notification first.
	if ( $accepted )
		bp_core_add_notification( $group_id, $requesting_user_id, 'groups', 'membership_request_accepted' );
	else
		bp_core_add_notification( $group_id, $requesting_user_id, 'groups', 'membership_request_rejected' );

	if ( 'no' == get_usermeta( $requesting_user_id, 'notification_membership_request_completed' ) )
		return false;

	$group = new BP_Groups_Group( $group_id );

	$ud = bp_core_get_core_userdata($requesting_user_id);

	$group_link = bp_get_group_permalink( $group );
	$settings_link = bp_core_get_user_domain( $requesting_user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

	// Set up and send the message
	$to = $ud->user_email;

	if ( $accepted ) {
		$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'Membership request for group "%s" accepted', 'buddypress' ), $group->name );
		$message = sprintf( __(
'Your membership request for the group "%1$s" has been accepted.

To view the group please login and visit: %2$s

---------------------
', 'buddypress' ), $group->name, $group_link );

	} else {
		$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'Membership request for group "%s" rejected', 'buddypress' ), $group->name );
		$message = sprintf( __(
'Your membership request for the group "%1$s" has been rejected.

To submit another request please log in and visit: %2$s

---------------------
', 'buddypress' ), $group->name, $group_link );
	}

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	/* Send the message */
	$to = apply_filters( 'groups_notification_membership_request_completed_to', $to );
	$subject = apply_filters( 'groups_notification_membership_request_completed_subject', $subject, &$group );
	$message = apply_filters( 'groups_notification_membership_request_completed_message', $message, &$group, $group_link  );

	wp_mail( $to, $subject, $message );
}

function groups_notification_promoted_member( $user_id, $group_id ) {
	global $bp;

	if ( groups_is_user_admin( $user_id, $group_id ) ) {
		$promoted_to = __( 'an administrator', 'buddypress' );
		$type = 'member_promoted_to_admin';
	} else {
		$promoted_to = __( 'a moderator', 'buddypress' );
		$type = 'member_promoted_to_mod';
	}

	// Post a screen notification first.
	bp_core_add_notification( $group_id, $user_id, 'groups', $type );

	if ( 'no' == get_usermeta( $user_id, 'notification_groups_admin_promotion' ) )
		return false;

	$group = new BP_Groups_Group( $group_id );
	$ud = bp_core_get_core_userdata($user_id);

	$group_link = bp_get_group_permalink( $group );
	$settings_link = bp_core_get_user_domain( $user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

	// Set up and send the message
	$to = $ud->user_email;

	$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'You have been promoted in the group: "%s"', 'buddypress' ), $group->name );

	$message = sprintf( __(
'You have been promoted to %1$s for the group: "%2$s".

To view the group please visit: %3$s

---------------------
', 'buddypress' ), $promoted_to, $group->name, $group_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	/* Send the message */
	$to = apply_filters( 'groups_notification_promoted_member_to', $to );
	$subject = apply_filters( 'groups_notification_promoted_member_subject', $subject, &$group );
	$message = apply_filters( 'groups_notification_promoted_member_message', $message, &$group, $promoted_to, $group_link );

	wp_mail( $to, $subject, $message );
}
add_action( 'groups_promoted_member', 'groups_notification_promoted_member', 10, 2 );

function groups_notification_group_invites( &$group, &$member, $inviter_user_id ) {
	global $bp;

	$inviter_ud = bp_core_get_core_userdata( $inviter_user_id );
	$inviter_name = bp_core_get_userlink( $inviter_user_id, true, false, true );
	$inviter_link = bp_core_get_user_domain( $inviter_user_id );

	$group_link = bp_get_group_permalink( $group );

	if ( !$member->invite_sent ) {
		$invited_user_id = $member->user_id;

		// Post a screen notification first.
		bp_core_add_notification( $group->id, $invited_user_id, 'groups', 'group_invite' );

		if ( 'no' == get_usermeta( $invited_user_id, 'notification_groups_invite' ) )
			return false;

		$invited_ud = bp_core_get_core_userdata($invited_user_id);

		$settings_link = bp_core_get_user_domain( $invited_user_id ) .  BP_SETTINGS_SLUG . '/notifications/';
		$invited_link = bp_core_get_user_domain( $invited_user_id );
		$invites_link = $invited_link . $bp->groups->slug . '/invites';

		// Set up and send the message
		$to = $invited_ud->user_email;

		$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'You have an invitation to the group: "%s"', 'buddypress' ), $group->name );

		$message = sprintf( __(
'One of your friends %1$s has invited you to the group: "%2$s".

To view your group invites visit: %3$s

To view the group visit: %4$s

To view %5$s\'s profile visit: %6$s

---------------------
', 'buddypress' ), $inviter_name, $group->name, $invites_link, $group_link, $inviter_name, $inviter_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		/* Send the message */
		$to = apply_filters( 'groups_notification_group_invites_to', $to );
		$subject = apply_filters( 'groups_notification_group_invites_subject', $subject, &$group );
		$message = apply_filters( 'groups_notification_group_invites_message', $message, &$group, $inviter_name, $inviter_link, $invites_link, $group_link );

		wp_mail( $to, $subject, $message );
	}
}

function groups_at_message_notification( $content, $poster_user_id, $group_id, $activity_id ) {
	global $bp;

	/* Scan for @username strings in an activity update. Notify each user. */
	$pattern = '/[@]+([A-Za-z0-9-_]+)/';
	preg_match_all( $pattern, $content, $usernames );

	/* Make sure there's only one instance of each username */
	if ( !$usernames = array_unique( $usernames[1] ) )
		return false;

	$group = new BP_Groups_Group( $group_id );

	foreach( (array)$usernames as $username ) {
		if ( !$receiver_user_id = bp_core_get_userid($username) )
			continue;

		/* Check the user is a member of the group before sending the update. */
		if ( !groups_is_user_member( $receiver_user_id, $group_id ) )
			continue;

		// Now email the user with the contents of the message (if they have enabled email notifications)
		if ( 'no' != get_usermeta( $user_id, 'notification_activity_new_mention' ) ) {
			$poster_name = bp_core_get_user_displayname( $poster_user_id );

			$message_link = bp_activity_get_permalink( $activity_id );
			$settings_link = bp_core_get_user_domain( $receiver_user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

			$poster_name = stripslashes( $poster_name );
			$content = bp_groups_filter_kses( stripslashes( $content ) );

			// Set up and send the message
			$ud = bp_core_get_core_userdata( $receiver_user_id );
			$to = $ud->user_email;
			$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( '%1$s mentioned you in the group "%2$s"', 'buddypress' ), $poster_name, $group->name );

$message = sprintf( __(
'%1$s mentioned you in the group "%2$s":

"%3$s"

To view and respond to the message, log in and visit: %4$s

---------------------
', 'buddypress' ), $poster_name, $group->name, $content, $message_link );

			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

			/* Send the message */
			$to = apply_filters( 'groups_at_message_notification_to', $to );
			$subject = apply_filters( 'groups_at_message_notification_subject', $subject, &$group, $poster_name );
			$message = apply_filters( 'groups_at_message_notification_message', $message, &$group, $poster_name, $content, $message_link );

			wp_mail( $to, $subject, $message );
		}
	}
}
add_action( 'bp_groups_posted_update', 'groups_at_message_notification', 10, 4 );


?>