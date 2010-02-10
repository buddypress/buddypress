<?php

function messages_notification_new_message( $args ) {
	global $bp;
	extract($args);

	$sender_name = bp_core_get_user_displayname( $sender_id );

	foreach( $recipients as $recipient ) {
		if ( $sender_id == $recipient->user_id || 'no' == get_usermeta( $recipient->user_id, 'notification_messages_new_message' ) ) continue;

		$ud = get_userdata( $recipient->user_id );
		$message_link = bp_core_get_user_domain( $recipient->user_id ) . BP_MESSAGES_SLUG .'/';
		$settings_link = bp_core_get_user_domain( $recipient->user_id ) . 'settings/notifications/';

		// Set up and send the message
		$to = $ud->user_email;
		$email_subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'New message from %s', 'buddypress' ), stripslashes( $sender_name ) );

		$email_content = sprintf( __(
'%s sent you a new message:

Subject: %s

"%s"

To view and read your messages please log in and visit: %s

---------------------
', 'buddypress' ), $sender_name, stripslashes( wp_filter_kses( $subject ) ), stripslashes( wp_filter_kses( $content ) ), $message_link );

		$content .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		// Send it
		wp_mail( $to, $email_subject, $email_content );
	}
}

/* This is too expensive to send on normal servers uncomment action at your own risk. */
function messages_notification_new_notice( $message_subject, $message ) {
	global $bp, $wpdb;

	$status_sql = bp_core_get_status_sql( 'u.' );
	$users = $wpdb->get_results( $wpdb->prepare( "SELECT ID as user_id, user_email, user_login FROM {$wpdb->base_prefix}users WHERE {$status_sql}" ) );

	for ( $i = 0; $i < count($users); $i++ ) {
		if ( get_usermeta( $users[$i]->user_id, 'notification_messages_new_notice' ) == 'no' ) continue;

		$message_link = bp_core_get_user_domain( $users[$i]->user_id ) . 'messages';
		$settings_link = bp_core_get_user_domain( $users[$i]->user_id ) . 'settings/notifications';

		// Set up and send the message
		$to = $users[$i]->user_email;
		$subject = __( 'New Site Notice', 'buddypress' );

		$message = sprintf( __(
'A new site notice has been posted on %s:

"%s: %s"

To view the notice: %s

---------------------
', 'buddypress' ), get_blog_option( BP_ROOT_BLOG, 'blogname' ), stripslashes( wp_filter_kses( $message_subject ) ), stripslashes( wp_filter_kses( $message ) ), $message_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		// Send it
		wp_mail( $to, $subject, $message );

		unset($message);
		unset($subject);
		unset($to);
	}
}
// add_action( 'bp_messages_notice_sent', 'messages_notification_new_notice', 10, 2 );

?>