<?php

function bp_activity_new_comment_notification( $comment_id, $params ) {
	global $bp;

	extract( $params );
	$original_activity = new BP_Activity_Activity( $activity_id );

	/* Don't email comments on a member's own activity */
	if ( $original_activity->user_id == $user_id )
		return false;

	if ( !get_usermeta( $user_id, 'notification_activity_new_reply' ) || 'yes' == get_usermeta( $user_id, 'notification_activity_new_reply' ) ) {
		$poster_name = bp_core_get_user_displayname( $user_id );
		$thread_link = bp_activity_get_permalink( $activity_id );
		$settings_link = bp_core_get_user_domain( $original_activity->user_id ) . 'settings/notifications/';

		// Set up and send the message
		$ud = get_userdata( $original_activity->user_id );
		$to = $ud->user_email;
		$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( '%s replied to one of your updates', 'buddypress' ), stripslashes_deep( $poster_name ) );

$message = sprintf( __(
'%s replied to one of your updates:

"%s"

To view your original update and all comments, log in and visit: %s

---------------------
', 'buddypress' ), $poster_name, wp_filter_kses( stripslashes_deep( $content ) ), $thread_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		// Send it
		wp_mail( $to, $subject, $message );
	}
}

?>