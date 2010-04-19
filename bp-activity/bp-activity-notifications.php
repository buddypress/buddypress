<?php

function bp_activity_at_message_notification( $content, $poster_user_id, $activity_id ) {
	global $bp;

	/* Scan for @username strings in an activity update. Notify each user. */
	$pattern = '/[@]+([A-Za-z0-9-_]+)/';
	preg_match_all( $pattern, $content, $usernames );

	/* Make sure there's only one instance of each username */
	if ( !$usernames = array_unique( $usernames[1] ) )
		return false;

	foreach( (array)$usernames as $username ) {
		if ( !$receiver_user_id = bp_core_get_userid( $username ) )
			continue;

		// Now email the user with the contents of the message (if they have enabled email notifications)
		if ( 'no' != get_usermeta( $receiver_user_id, 'notification_activity_new_mention' ) ) {
			$poster_name = bp_core_get_user_displayname( $poster_user_id );

			$message_link = bp_activity_get_permalink( $activity_id );
			$settings_link = bp_core_get_user_domain( $receiver_user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

			$poster_name = stripslashes( $poster_name );
			$content = bp_activity_filter_kses( stripslashes($content) );

			// Set up and send the message
			$ud = bp_core_get_core_userdata( $receiver_user_id );
			$to = $ud->user_email;
			$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( '%s mentioned you in an update', 'buddypress' ), $poster_name );

$message = sprintf( __(
'%1$s mentioned you in an update:

"%2$s"

To view and respond to the message, log in and visit: %3$s

---------------------
', 'buddypress' ), $poster_name, $content, $message_link );

			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

			/* Send the message */
			$to = apply_filters( 'bp_activity_at_message_notification_to', $to );
			$subject = apply_filters( 'bp_activity_at_message_notification_subject', $subject, $poster_name );
			$message = apply_filters( 'bp_activity_at_message_notification_message', $message, $poster_name, $content, $message_link );

			wp_mail( $to, $subject, $message );
		}
	}
}
add_action( 'bp_activity_posted_update', 'bp_activity_at_message_notification', 10, 3 );

function bp_activity_new_comment_notification( $comment_id, $commenter_id, $params ) {
	global $bp;

	extract( $params );

	$original_activity = new BP_Activity_Activity( $activity_id );

	if ( $original_activity->user_id != $commenter_id && 'no' != get_usermeta( $original_activity->user_id, 'notification_activity_new_reply' ) ) {
		$poster_name = bp_core_get_user_displayname( $commenter_id );
		$thread_link = bp_activity_get_permalink( $activity_id );
		$settings_link = bp_core_get_user_domain( $original_activity->user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

		$poster_name = stripslashes( $poster_name );
		$content = bp_activity_filter_kses( stripslashes($content) );

		// Set up and send the message
		$ud = bp_core_get_core_userdata( $original_activity->user_id );
		$to = $ud->user_email;
		$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( '%s replied to one of your updates', 'buddypress' ), $poster_name );

$message = sprintf( __(
'%1$s replied to one of your updates:

"%2$s"

To view your original update and all comments, log in and visit: %3$s

---------------------
', 'buddypress' ), $poster_name, $content, $thread_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		/* Send the message */
		$to = apply_filters( 'bp_activity_new_comment_notification_to', $to );
		$subject = apply_filters( 'bp_activity_new_comment_notification_subject', $subject, $poster_name );
		$message = apply_filters( 'bp_activity_new_comment_notification_message', $message, $poster_name, $content, $thread_link );

		wp_mail( $to, $subject, $message );
	}

	/***
	 * If this is a reply to another comment, send an email notification to the
	 * author of the immediate parent comment.
	 */
	if ( $activity_id == $parent_id )
		return false;

	$parent_comment = new BP_Activity_Activity( $parent_id );

	if ( $parent_comment->user_id != $commenter_id && $original_activity->user_id != $parent_comment->user_id && 'no' != get_usermeta( $parent_comment->user_id, 'notification_activity_new_reply' ) ) {
		$poster_name = bp_core_get_user_displayname( $commenter_id );
		$thread_link = bp_activity_get_permalink( $activity_id );
		$settings_link = bp_core_get_user_domain( $parent_comment->user_id ) .  BP_SETTINGS_SLUG . '/notifications/';

		// Set up and send the message
		$ud = bp_core_get_core_userdata( $parent_comment->user_id );
		$to = $ud->user_email;
		$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( '%s replied to one of your comments', 'buddypress' ), $poster_name );

		$poster_name = stripslashes( $poster_name );
		$content = bp_activity_filter_kses( stripslashes( $content ) );

$message = sprintf( __(
'%1$s replied to one of your comments:

"%2$s"

To view the original activity, your comment and all replies, log in and visit: %3$s

---------------------
', 'buddypress' ), $poster_name, $content, $thread_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		/* Send the message */
		$to = apply_filters( 'bp_activity_new_comment_notification_comment_author_to', $to );
		$subject = apply_filters( 'bp_activity_new_comment_notification_comment_author_subject', $subject, $poster_name );
		$message = apply_filters( 'bp_activity_new_comment_notification_comment_author_message', $message, $poster_name, $content );

		wp_mail( $to, $subject, $message );
	}
}

?>