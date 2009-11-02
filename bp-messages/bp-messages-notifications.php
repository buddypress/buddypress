<?php

function messages_notification_new_message( $args ) {
	global $bp;
	extract($args);

	$message = new BP_Messages_Message( $item_id );

	$sender_name = bp_core_get_user_displayname( $message->sender_id );

	for ( $i = 0; $i < count($recipient_ids); $i++ ) {
		if ( $message->sender_id == $recipient_ids[$i] || 'no' == get_usermeta( $recipient_ids[$i], 'notification_messages_new_message' ) ) continue;

		$ud = get_userdata( $recipient_ids[$i] );
		$message_link = bp_core_get_user_domain( $recipient_ids[$i] ) . 'messages/view/' . $message->id;
		$settings_link = bp_core_get_user_domain( $recipient_ids[$i] ) . 'settings/notifications';

		// Set up and send the message
		$to = $ud->user_email;
		$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'New message from %s', 'buddypress' ), stripslashes( $sender_name ) );

		$content = sprintf( __(
'%s sent you a new message:

Subject: %s

"%s"

To view the message: %s

---------------------
', 'buddypress' ), $sender_name, stripslashes( wp_filter_kses( $message->subject ) ), stripslashes( wp_filter_kses( $message->message ) ), $message_link );

		$content .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		// Send it
		wp_mail( $to, $subject, $content );
	}
}


/* This is too expensive to send on normal servers uncomment action at your own risk. */
function messages_notification_new_notice( $message_subject, $message ) {
	global $bp, $wpdb;

	$users = $wpdb->get_results( $wpdb->prepare( "SELECT ID as user_id, user_email, user_login FROM {$wpdb->base_prefix}users WHERE user_status = 0 AND spam = 0 AND deleted = 0" ) );

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