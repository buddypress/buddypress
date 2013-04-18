<?php

/**
 * BuddyPress Activity Notifications
 *
 * @package BuddyPress
 * @subpackage ActivityNotifications
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Sends an email notification and a BP notification when someone mentions you in an update
 *
 * @since BuddyPress (1.2)
 *
 * @param int $activity_id The id of the activity update
 * @param int $receiver_user_id The unique user_id of the user who is receiving the update
 *
 * @uses bp_core_add_notification()
 * @uses bp_get_user_meta()
 * @uses bp_core_get_user_displayname()
 * @uses bp_activity_get_permalink()
 * @uses bp_core_get_user_domain()
 * @uses bp_get_settings_slug()
 * @uses bp_activity_filter_kses()
 * @uses bp_core_get_core_userdata()
 * @uses wp_specialchars_decode()
 * @uses get_blog_option()
 * @uses bp_is_active()
 * @uses bp_is_group()
 * @uses bp_get_current_group_name()
 * @uses apply_filters() To call the 'bp_activity_at_message_notification_to' hook
 * @uses apply_filters() To call the 'bp_activity_at_message_notification_subject' hook
 * @uses apply_filters() To call the 'bp_activity_at_message_notification_message' hook
 * @uses wp_mail()
 * @uses do_action() To call the 'bp_activity_sent_mention_email' hook
 */
function bp_activity_at_message_notification( $activity_id, $receiver_user_id ) {

	// Don't leave multiple notifications for the same activity item
	$notifications = BP_Core_Notification::get_all_for_user( $receiver_user_id, 'all' );

	foreach( $notifications as $notification ) {
		if ( $activity_id == $notification->item_id ) {
			return;
		}
	}

	$activity = new BP_Activity_Activity( $activity_id );

	$subject = '';
	$message = '';
	$content = '';

	// Add the BP notification
	bp_core_add_notification( $activity_id, $receiver_user_id, 'activity', 'new_at_mention', $activity->user_id );

	// Now email the user with the contents of the message (if they have enabled email notifications)
	if ( 'no' != bp_get_user_meta( $receiver_user_id, 'notification_activity_new_mention', true ) ) {
		$poster_name = bp_core_get_user_displayname( $activity->user_id );

		$message_link  = bp_activity_get_permalink( $activity_id );
		$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
		$settings_link = bp_core_get_user_domain( $receiver_user_id ) . $settings_slug . '/notifications/';

		$poster_name = stripslashes( $poster_name );
		$content = bp_activity_filter_kses( strip_tags( stripslashes( $activity->content ) ) );

		// Set up and send the message
		$ud       = bp_core_get_core_userdata( $receiver_user_id );
		$to       = $ud->user_email;
		$subject  = bp_get_email_subject( array( 'text' => sprintf( __( '%s mentioned you in an update', 'buddypress' ), $poster_name ) ) );

		if ( bp_is_active( 'groups' ) && bp_is_group() ) {
			$message = sprintf( __(
'%1$s mentioned you in the group "%2$s":

"%3$s"

To view and respond to the message, log in and visit: %4$s

---------------------
', 'buddypress' ), $poster_name, bp_get_current_group_name(), $content, $message_link );
		} else {
			$message = sprintf( __(
'%1$s mentioned you in an update:

"%2$s"

To view and respond to the message, log in and visit: %3$s

---------------------
', 'buddypress' ), $poster_name, $content, $message_link );
		}

		// Only show the disable notifications line if the settings component is enabled
		if ( bp_is_active( 'settings' ) ) {
			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
		}

		/* Send the message */
		$to 	 = apply_filters( 'bp_activity_at_message_notification_to', $to );
		$subject = apply_filters( 'bp_activity_at_message_notification_subject', $subject, $poster_name );
		$message = apply_filters( 'bp_activity_at_message_notification_message', $message, $poster_name, $content, $message_link, $settings_link );

		wp_mail( $to, $subject, $message );
	}

	do_action( 'bp_activity_sent_mention_email', $activity, $subject, $message, $content );
}

/**
 * Sends an email notification and a BP notification when someone mentions you in an update
 *
 * @since BuddyPress (1.2)
 *
 * @param int $comment_id The comment id
 * @param int $commenter_id The unique user_id of the user who posted the comment
 * @param array $params {@link bp_activity_new_comment()}
 *
 * @uses bp_get_user_meta()
 * @uses bp_core_get_user_displayname()
 * @uses bp_activity_get_permalink()
 * @uses bp_core_get_user_domain()
 * @uses bp_get_settings_slug()
 * @uses bp_activity_filter_kses()
 * @uses bp_core_get_core_userdata()
 * @uses wp_specialchars_decode()
 * @uses get_blog_option()
 * @uses bp_get_root_blog_id()
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_to' hook
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_subject' hook
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_message' hook
 * @uses wp_mail()
 * @uses do_action() To call the 'bp_activity_sent_reply_to_update_email' hook
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_comment_author_to' hook
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_comment_author_subject' hook
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_comment_author_message' hook
 * @uses do_action() To call the 'bp_activity_sent_reply_to_reply_email' hook
 */
function bp_activity_new_comment_notification( $comment_id, $commenter_id, $params ) {

	// Set some default parameters
	$activity_id = 0;
	$parent_id   = 0;

	extract( $params );

	$original_activity = new BP_Activity_Activity( $activity_id );

	if ( $original_activity->user_id != $commenter_id && 'no' != bp_get_user_meta( $original_activity->user_id, 'notification_activity_new_reply', true ) ) {
		$poster_name   = bp_core_get_user_displayname( $commenter_id );
		$thread_link   = bp_activity_get_permalink( $activity_id );
		$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
		$settings_link = bp_core_get_user_domain( $original_activity->user_id ) . $settings_slug . '/notifications/';

		$poster_name = stripslashes( $poster_name );
		$content = bp_activity_filter_kses( stripslashes($content) );

		// Set up and send the message
		$ud      = bp_core_get_core_userdata( $original_activity->user_id );
		$to      = $ud->user_email;
		$subject = bp_get_email_subject( array( 'text' => sprintf( __( '%s replied to one of your updates', 'buddypress' ), $poster_name ) ) );
		$message = sprintf( __(
'%1$s replied to one of your updates:

"%2$s"

To view your original update and all comments, log in and visit: %3$s

---------------------
', 'buddypress' ), $poster_name, $content, $thread_link );

		// Only show the disable notifications line if the settings component is enabled
		if ( bp_is_active( 'settings' ) ) {
			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
		}

		/* Send the message */
		$to = apply_filters( 'bp_activity_new_comment_notification_to', $to );
		$subject = apply_filters( 'bp_activity_new_comment_notification_subject', $subject, $poster_name );
		$message = apply_filters( 'bp_activity_new_comment_notification_message', $message, $poster_name, $content, $thread_link, $settings_link );

		wp_mail( $to, $subject, $message );

		do_action( 'bp_activity_sent_reply_to_update_email', $original_activity->user_id, $subject, $message, $comment_id, $commenter_id, $params );
	}

	/***
	 * If this is a reply to another comment, send an email notification to the
	 * author of the immediate parent comment.
	 */
	if ( empty( $parent_id ) || ( $activity_id == $parent_id ) )
		return false;

	$parent_comment = new BP_Activity_Activity( $parent_id );

	if ( $parent_comment->user_id != $commenter_id && $original_activity->user_id != $parent_comment->user_id && 'no' != bp_get_user_meta( $parent_comment->user_id, 'notification_activity_new_reply', true ) ) {
		$poster_name   = bp_core_get_user_displayname( $commenter_id );
		$thread_link   = bp_activity_get_permalink( $activity_id );
		$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
		$settings_link = bp_core_get_user_domain( $parent_comment->user_id ) . $settings_slug . '/notifications/';

		// Set up and send the message
		$ud       = bp_core_get_core_userdata( $parent_comment->user_id );
		$to       = $ud->user_email;
		$subject = bp_get_email_subject( array( 'text' => sprintf( __( '%s replied to one of your comments', 'buddypress' ), $poster_name ) ) );

		$poster_name = stripslashes( $poster_name );
		$content = bp_activity_filter_kses( stripslashes( $content ) );

$message = sprintf( __(
'%1$s replied to one of your comments:

"%2$s"

To view the original activity, your comment and all replies, log in and visit: %3$s

---------------------
', 'buddypress' ), $poster_name, $content, $thread_link );

		// Only show the disable notifications line if the settings component is enabled
		if ( bp_is_active( 'settings' ) ) {
			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
		}

		/* Send the message */
		$to = apply_filters( 'bp_activity_new_comment_notification_comment_author_to', $to );
		$subject = apply_filters( 'bp_activity_new_comment_notification_comment_author_subject', $subject, $poster_name );
		$message = apply_filters( 'bp_activity_new_comment_notification_comment_author_message', $message, $poster_name, $content, $settings_link, $thread_link );

		wp_mail( $to, $subject, $message );

		do_action( 'bp_activity_sent_reply_to_reply_email', $original_activity->user_id, $subject, $message, $comment_id, $commenter_id, $params );
	}
}
