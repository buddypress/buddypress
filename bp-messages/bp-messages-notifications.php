<?php

/**
 * BuddyPress Messages Notifications
 *
 * @package BuddyPress
 * @subpackage MessagesNotifications
 */


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function messages_notification_new_message( $args = array() ) {

	// These should be extracted below
	$recipients    = array();
	$email_subject = $email_content = '';

	extract( $args );

	$sender_name = bp_core_get_user_displayname( $sender_id );

	// Bail if no recipients
	if ( ! empty( $recipients ) ) {

		foreach( $recipients as $recipient ) {

			if ( $sender_id == $recipient->user_id || 'no' == bp_get_user_meta( $recipient->user_id, 'notification_messages_new_message', true ) )
				continue;

			// User data and links
			$ud            = get_userdata( $recipient->user_id );

			// Bail if user cannot be found
			if ( empty( $ud ) )
				continue;

			$message_link  = bp_core_get_user_domain( $recipient->user_id ) . bp_get_messages_slug() .'/';
			$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
			$settings_link = bp_core_get_user_domain( $recipient->user_id ) . $settings_slug . '/notifications/';

			// Sender info
			$sender_name   = stripslashes( $sender_name );
			$subject       = stripslashes( wp_filter_kses( $subject ) );
			$content       = stripslashes( wp_filter_kses( $content ) );

			// Set up and send the message
			$email_to      = $ud->user_email;
			$email_subject = bp_get_email_subject( array( 'text' => sprintf( __( 'New message from %s', 'buddypress' ), $sender_name ) ) );

			$email_content = sprintf( __(
'%1$s sent you a new message:

Subject: %2$s

"%3$s"

To view and read your messages please log in and visit: %4$s

---------------------
', 'buddypress' ), $sender_name, $subject, $content, $message_link );

			// Only show the disable notifications line if the settings component is enabled
			if ( bp_is_active( 'settings' ) ) {
				$email_content .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
			}

			// Send the message
			$email_to      = apply_filters( 'messages_notification_new_message_to',      $email_to );
			$email_subject = apply_filters( 'messages_notification_new_message_subject', $email_subject, $sender_name );
			$email_content = apply_filters( 'messages_notification_new_message_message', $email_content, $sender_name, $subject, $content, $message_link, $settings_link );

			wp_mail( $email_to, $email_subject, $email_content );
		}
	}

	do_action( 'bp_messages_sent_notification_email', $recipients, $email_subject, $email_content, $args );
}
