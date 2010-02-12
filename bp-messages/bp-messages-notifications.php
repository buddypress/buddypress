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
		$email_to = $ud->user_email;
		$email_subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'New message from %s', 'buddypress' ), stripslashes( $sender_name ) );

		$email_content = sprintf( __(
'%s sent you a new message:

Subject: %s

"%s"

To view and read your messages please log in and visit: %s

---------------------
', 'buddypress' ), $sender_name, stripslashes( wp_filter_kses( $subject ) ), stripslashes( wp_filter_kses( $content ) ), $message_link );

		$content .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		/* Send the message */
		$email_to = apply_filters( 'messages_notification_new_message_to', $email_to );
		$email_subject = apply_filters( 'messages_notification_new_message_subject', $email_subject );
		$email_content = apply_filters( 'messages_notification_new_message_message', $email_content );

		wp_mail( $email_to, $email_subject, $email_content );
	}
}

?>